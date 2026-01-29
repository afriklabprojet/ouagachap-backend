<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtpCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone',
        'code',
        'expires_at',
        'is_used',
        'attempts',
        'max_attempts',
        'purpose',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'is_used' => 'boolean',
            'attempts' => 'integer',
            'max_attempts' => 'integer',
        ];
    }

    const PURPOSE_LOGIN = 'login';
    const PURPOSE_REGISTER = 'register';
    const PURPOSE_PASSWORD_RESET = 'password_reset';
    const PURPOSE_PHONE_VERIFICATION = 'phone_verification';

    // ==================== SCOPES ====================

    public function scopeValid($query, string $phone, string $code)
    {
        return $query->where('phone', $phone)
            ->where('code', $code)
            ->where('is_used', false)
            ->where('expires_at', '>', now())
            ->whereRaw('attempts < max_attempts');
    }

    public function scopeActive($query, string $phone)
    {
        return $query->where('phone', $phone)
            ->where('is_used', false)
            ->where('expires_at', '>', now());
    }

    // ==================== HELPERS ====================

    public static function generate(
        string $phone,
        string $purpose = self::PURPOSE_LOGIN,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): self {
        // Vérifier le rate limiting (max 50 OTP par heure par numéro - développement)
        $recentCount = self::where('phone', $phone)
            ->where('created_at', '>', now()->subHour())
            ->count();

        if ($recentCount >= 50) {
            throw new \Exception('Trop de demandes OTP. Réessayez dans 1 heure.');
        }

        // Invalidate existing codes
        self::where('phone', $phone)
            ->where('is_used', false)
            ->update(['is_used' => true]);

        return self::create([
            'phone' => $phone,
            'code' => str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT),
            'expires_at' => now()->addMinutes(5),
            'attempts' => 0,
            'max_attempts' => 3,
            'purpose' => $purpose,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }

    public static function verify(string $phone, string $code): array
    {
        $otpCode = self::where('phone', $phone)
            ->where('is_used', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$otpCode) {
            return ['success' => false, 'message' => 'Code invalide ou expiré'];
        }

        // Incrémenter les tentatives
        $otpCode->increment('attempts');

        // Vérifier si max tentatives atteint
        if ($otpCode->attempts >= $otpCode->max_attempts) {
            $otpCode->update(['is_used' => true]);
            return ['success' => false, 'message' => 'Nombre maximum de tentatives atteint'];
        }

        // Vérifier le code
        if ($otpCode->code !== $code) {
            $remaining = $otpCode->max_attempts - $otpCode->attempts;
            return ['success' => false, 'message' => "Code incorrect. {$remaining} tentative(s) restante(s)"];
        }

        $otpCode->update(['is_used' => true]);

        return ['success' => true, 'message' => 'Code vérifié avec succès'];
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function hasMaxAttempts(): bool
    {
        return $this->attempts >= $this->max_attempts;
    }

    public function getRemainingAttempts(): int
    {
        return max(0, $this->max_attempts - $this->attempts);
    }

    public function getTimeRemaining(): int
    {
        if ($this->isExpired()) {
            return 0;
        }
        return $this->expires_at->diffInSeconds(now());
    }
}

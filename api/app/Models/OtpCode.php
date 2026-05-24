<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

class OtpCode extends Model
{
    use HasFactory, MassPrunable;

    const PURPOSE_LOGIN              = 'login';
    const PURPOSE_REGISTER           = 'register';
    const PURPOSE_PASSWORD_RESET     = 'password_reset';
    const PURPOSE_PHONE_VERIFICATION = 'phone_verification';

    const OTP_EXPIRY_MINUTES  = 5;
    const RATE_LIMIT_MAX      = 3;
    const RATE_LIMIT_WINDOW   = 15; // minutes

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

    protected $casts = [
        'expires_at' => 'datetime',
        'is_used'    => 'boolean',
        'attempts'   => 'integer',
        'max_attempts' => 'integer',
    ];

    public function prunable(): Builder
    {
        return static::where('expires_at', '<', now()->subDay());
    }

    // ==================== SCOPES ====================

    public function scopeValid(Builder $query, string $phone, string $code): Builder
    {
        return $query->where('phone', $phone)
            ->where('code', $code)
            ->where('is_used', false)
            ->where('expires_at', '>', now())
            ->whereColumn('attempts', '<', 'max_attempts');
    }

    public function scopeActive(Builder $query, string $phone): Builder
    {
        return $query->where('phone', $phone)
            ->where('is_used', false)
            ->where('expires_at', '>', now());
    }

    // ==================== STATIC METHODS ====================

    /**
     * @throws \Exception
     */
    public static function generate(
        string $phone,
        string $purpose   = self::PURPOSE_LOGIN,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): self {
        // Rate limiting
        $recentCount = static::where('phone', $phone)
            ->where('created_at', '>=', now()->subMinutes(self::RATE_LIMIT_WINDOW))
            ->count();

        if ($recentCount >= self::RATE_LIMIT_MAX) {
            throw new \RuntimeException('OTP_RATE_LIMIT_EXCEEDED');
        }

        // Invalidate existing codes
        static::where('phone', $phone)
            ->where('is_used', false)
            ->update(['is_used' => true]);

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        return static::create([
            'phone'      => $phone,
            'code'       => $code,
            'expires_at' => now()->addMinutes(self::OTP_EXPIRY_MINUTES),
            'is_used'    => false,
            'attempts'   => 0,
            'max_attempts' => 3,
            'purpose'    => $purpose,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }

    public static function verify(string $phone, string $code): array
    {
        $otp = static::where('phone', $phone)
            ->where('is_used', false)
            ->latest()
            ->first();

        if (! $otp) {
            return ['success' => false, 'message' => 'Aucun code OTP trouvé.'];
        }

        if ($otp->isExpired()) {
            return ['success' => false, 'message' => 'Code OTP expiré.'];
        }

        if ($otp->hasMaxAttempts()) {
            return ['success' => false, 'message' => 'Nombre maximum de tentatives atteint.'];
        }

        if ($otp->code !== $code) {
            $otp->increment('attempts');
            if ($otp->fresh()->hasMaxAttempts()) {
                $otp->update(['is_used' => true]);
                return ['success' => false, 'message' => 'Nombre maximum de tentatives atteint.'];
            }
            return ['success' => false, 'message' => 'Code incorrect.'];
        }

        $otp->update(['is_used' => true]);

        return ['success' => true, 'message' => 'Code vérifié avec succès.'];
    }

    // ==================== HELPERS ====================

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function getTimeRemaining(): int
    {
        if ($this->isExpired()) {
            return 0;
        }

        return (int) now()->diffInSeconds($this->expires_at);
    }

    public function getRemainingAttempts(): int
    {
        return max(0, $this->max_attempts - $this->attempts);
    }

    public function hasMaxAttempts(): bool
    {
        return $this->attempts >= $this->max_attempts;
    }
}

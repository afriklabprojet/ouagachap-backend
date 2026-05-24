<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CourierPasswordResetService
{
    private const GENERIC_MESSAGE = 'Si cet identifiant correspond à un compte coursier, un code de réinitialisation a été envoyé.';
    private const RATE_LIMIT_CODE = 'OTP_RATE_LIMIT_EXCEEDED';
    private const UNSUPPORTED_COUNTRY_MESSAGE = 'Votre pays n\'est pas encore supporté.';
    private const SMS_FAILURE_MESSAGE = 'Impossible d\'envoyer le SMS. Réessayez dans quelques instants.';
    private const EMAIL_FAILURE_MESSAGE = 'Impossible d\'envoyer l\'email. Réessayez dans quelques instants.';
    private const INVALID_CODE_MESSAGE = 'Code incorrect ou expiré.';
    private const MAX_ATTEMPTS_MESSAGE = 'Nombre maximum de tentatives atteint.';

    public function __construct(
        private readonly SmsService $smsService,
    ) {}

    public function sendOtp(
        string $identifier,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): array {
        $identifier = trim($identifier);
        $result = ['success' => true, 'message' => self::GENERIC_MESSAGE];

        if ($this->isEmail($identifier)) {
            $email = $this->normalizeEmail($identifier);
            $courier = $this->findCourierByEmail($email);
            if ($courier !== null) {
                $result = $this->generateAndSendEmailOtp($courier, $email, $ipAddress, $userAgent);
            }
        } else {
            $normalized = $this->normalizePhone($identifier);
            $formatted = $this->formatPhone($identifier);

            if (! $this->isCountryAllowed($normalized)) {
                $result = ['success' => false, 'message' => self::UNSUPPORTED_COUNTRY_MESSAGE];
            } else {
                $courier = $this->findCourierByPhone($formatted, $normalized);
                if ($courier !== null) {
                    $result = $this->generateAndSendSmsOtp($courier, $normalized, $formatted, $ipAddress, $userAgent);
                }
            }
        }

        return $result;
    }

    public function reset(string $identifier, string $code, string $password): array
    {
        $result = ['success' => false, 'message' => self::INVALID_CODE_MESSAGE];
        $identifier = trim($identifier);
        $otpKey = null;
        $courier = null;

        if ($this->isEmail($identifier)) {
            $otpKey = $this->normalizeEmail($identifier);
            $courier = $this->findCourierByEmail($otpKey);
        } else {
            $otpKey = $this->normalizePhone($identifier);
            $courier = $this->findCourierByPhone($this->formatPhone($identifier), $otpKey);
        }

        if ($courier !== null) {
            $otpResult = $this->verifyOtpForPurpose($otpKey, $code, OtpCode::PURPOSE_PASSWORD_RESET);
            $result = $otpResult['success']
                ? $this->updateCourierPassword($courier, $password)
                : ['success' => false, 'message' => $otpResult['message']];
        }

        return $result;
    }

    private function generateAndSendSmsOtp(
        User $courier,
        string $normalized,
        string $formatted,
        ?string $ipAddress,
        ?string $userAgent,
    ): array {
        $result = ['success' => true, 'message' => self::GENERIC_MESSAGE];

        try {
            $otp = OtpCode::generate($normalized, OtpCode::PURPOSE_PASSWORD_RESET, $ipAddress, $userAgent);
            $smsResult = $this->smsService->sendOtp($formatted, $otp->code);

            if (! ($smsResult['success'] ?? false)) {
                $otp->update(['is_used' => true]);
                Log::warning('Courier password reset SMS failed', [
                    'user' => $courier->id,
                    'error' => $smsResult['error'] ?? null,
                ]);
                $result = ['success' => false, 'message' => self::SMS_FAILURE_MESSAGE];
            }
        } catch (\Exception $exception) {
            $result = $exception->getMessage() === self::RATE_LIMIT_CODE ? [
                'success' => false,
                'code' => self::RATE_LIMIT_CODE,
                'message' => 'Trop de demandes OTP. Veuillez patienter.',
            ] : [
                'success' => false,
                'message' => $exception->getMessage(),
            ];
        }

        return $result;
    }

    private function generateAndSendEmailOtp(
        User $courier,
        string $email,
        ?string $ipAddress,
        ?string $userAgent,
    ): array {
        $result = ['success' => true, 'message' => self::GENERIC_MESSAGE];

        try {
            $otp = OtpCode::generate($email, OtpCode::PURPOSE_PASSWORD_RESET, $ipAddress, $userAgent);

            Mail::raw(
                "Votre code de réinitialisation OUAGA CHAP est : {$otp->code}\n\nCe code expire dans " . OtpCode::OTP_EXPIRY_MINUTES . " minutes.",
                function ($mail) use ($email) {
                    $mail->to($email)->subject('Code de réinitialisation OUAGA CHAP');
                }
            );
        } catch (\Exception $exception) {
            if (isset($otp)) {
                $otp->update(['is_used' => true]);
            }

            Log::warning('Courier password reset email failed', [
                'user' => $courier->id,
                'error' => $exception->getMessage(),
            ]);

            $result = $exception->getMessage() === self::RATE_LIMIT_CODE ? [
                'success' => false,
                'code' => self::RATE_LIMIT_CODE,
                'message' => 'Trop de demandes OTP. Veuillez patienter.',
            ] : [
                'success' => false,
                'message' => self::EMAIL_FAILURE_MESSAGE,
            ];
        }

        return $result;
    }

    private function verifyOtpForPurpose(string $phone, string $code, string $purpose): array
    {
        $otp = OtpCode::where('phone', $phone)
            ->where('purpose', $purpose)
            ->where('is_used', false)
            ->latest()
            ->first();
        $result = ['success' => false, 'message' => 'Aucun code OTP trouvé.'];

        if ($otp !== null) {
            $result = match (true) {
                $otp->isExpired() => ['success' => false, 'message' => 'Code OTP expiré.'],
                $otp->hasMaxAttempts() => ['success' => false, 'message' => self::MAX_ATTEMPTS_MESSAGE],
                $otp->code !== $code => $this->rejectInvalidOtp($otp),
                default => $this->acceptOtp($otp),
            };
        }

        return $result;
    }

    private function rejectInvalidOtp(OtpCode $otp): array
    {
        $otp->increment('attempts');
        $reachedLimit = $otp->fresh()->hasMaxAttempts();

        if ($reachedLimit) {
            $otp->update(['is_used' => true]);
        }

        return [
            'success' => false,
            'message' => $reachedLimit ? self::MAX_ATTEMPTS_MESSAGE : 'Code incorrect.',
        ];
    }

    private function acceptOtp(OtpCode $otp): array
    {
        $otp->update(['is_used' => true]);

        return ['success' => true, 'message' => 'Code vérifié avec succès.'];
    }

    private function updateCourierPassword(User $courier, string $password): array
    {
        $courier->forceFill(['password' => Hash::make($password)])->save();
        $courier->tokens()->delete();

        return ['success' => true, 'message' => 'Mot de passe réinitialisé avec succès.'];
    }

    private function findCourierByPhone(string $formatted, string $normalized): ?User
    {
        return User::whereIn('phone', [$formatted, $normalized])
            ->where('role', UserRole::COURIER)
            ->first();
    }

    private function findCourierByEmail(string $email): ?User
    {
        return User::whereRaw('LOWER(email) = ?', [$email])
            ->where('role', UserRole::COURIER)
            ->first();
    }

    private function isEmail(string $identifier): bool
    {
        return filter_var($identifier, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[\s\-]/', '', $phone);
        return preg_replace('/^(\+226|00226)/', '', $phone);
    }

    private function formatPhone(string $phone): string
    {
        return '+226' . $this->normalizePhone($phone);
    }

    private function isCountryAllowed(string $phone): bool
    {
        $digitsOnly = preg_replace('/\D/', '', $phone);
        $allowed = config('otp.allowed_countries');

        return strlen($digitsOnly) === 8 && (empty($allowed) || in_array('226', $allowed, true));
    }
}

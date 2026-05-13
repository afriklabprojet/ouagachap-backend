<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\OtpCode;
use App\Models\User;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;

class AuthService
{
    private mixed $firebaseAuth = null;

    public function __construct()
    {
        try {
            $this->firebaseAuth = app('firebase.auth');
        } catch (\Throwable) {
            $this->firebaseAuth = null;
        }
    }

    // ==================== PHONE NORMALIZATION ====================

    public function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[\s\-]/', '', $phone);
        $phone = preg_replace('/^(\+226|00226)/', '', $phone);
        return $phone;
    }

    public function formatPhone(string $phone): string
    {
        return '+226' . $this->normalizePhone($phone);
    }

    // ==================== DEMO MODE ====================

    private function isDemoMode(): bool
    {
        if (app()->environment('production')) {
            return false;
        }
        return (bool) config('otp.demo_mode', false);
    }

    // ==================== COUNTRY CHECK ====================

    private function isCountryAllowed(string $phone): bool
    {
        // After normalizePhone(), a Burkina Faso number is exactly 8 digits
        // If the number still has a country prefix (e.g. +33...), it's not Burkina
        $digitsOnly = preg_replace('/\D/', '', $phone);
        if (strlen($digitsOnly) !== 8) {
            return false;
        }

        $allowed = config('otp.allowed_countries');
        if (empty($allowed)) {
            return true;
        }
        foreach ($allowed as $code) {
            if ($code === '226') {
                return true;
            }
        }
        return false;
    }

    // ==================== OTP ====================

    public function sendOtp(
        string $phone,
        string $purpose    = OtpCode::PURPOSE_LOGIN,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): array {
        $normalized  = $this->normalizePhone($phone);
        $formatted   = $this->formatPhone($phone);
        $driver      = config('otp.driver', 'sms');

        // Country check
        if (! $this->isCountryAllowed($normalized)) {
            return [
                'success' => false,
                'message' => 'Votre pays n\'est pas encore supporté.',
            ];
        }

        if ($driver === 'firebase') {
            // Firebase Phone Auth — client handles OTP itself; we just store a backup
            try {
                OtpCode::generate($normalized, $purpose, $ipAddress, $userAgent);
            } catch (\Exception) {
                // Non-blocking
            }
            return [
                'success' => true,
                'method'  => 'firebase',
                'phone'   => $formatted,
            ];
        }

        // SMS mode
        try {
            $otp = OtpCode::generate($normalized, $purpose, $ipAddress, $userAgent);
            Log::info('OTP generated', ['phone' => $normalized]);

            return [
                'success'    => true,
                'method'     => 'sms',
                'phone'      => $formatted,
                'expires_at' => $otp->expires_at->toIso8601String(),
            ];
        } catch (\Exception $e) {
            if ($e->getMessage() === 'OTP_RATE_LIMIT_EXCEEDED') {
                return [
                    'success'   => false,
                    'code'      => 'OTP_RATE_LIMIT_EXCEEDED',
                    'message'   => 'Trop de demandes OTP. Veuillez patienter.',
                ];
            }
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    // ==================== FIREBASE TOKEN ====================

    public function verifyFirebaseToken(string $idToken, string $phone): array
    {
        if ($this->firebaseAuth === null) {
            return [
                'success'        => false,
                'message'        => 'Firebase Auth non configuré.',
                'fallback_to_otp' => false,
            ];
        }

        try {
            $verifiedToken = $this->firebaseAuth->verifyIdToken($idToken);
            $claims        = $verifiedToken->claims();
            $uid           = $claims->get('sub');

            return [
                'success'     => true,
                'firebase_uid' => $uid,
                'phone'       => $phone,
            ];
        } catch (FailedToVerifyToken $e) {
            return [
                'success'        => false,
                'message'        => 'Token Firebase invalide.',
                'fallback_to_otp' => false,
            ];
        } catch (ConnectException | RequestException $e) {
            return [
                'success'        => false,
                'message'        => 'Erreur réseau Firebase.',
                'fallback_to_otp' => true,
            ];
        } catch (\Throwable $e) {
            return [
                'success'        => false,
                'message'        => 'Erreur Firebase: ' . $e->getMessage(),
                'fallback_to_otp' => false,
            ];
        }
    }

    // ==================== OTP VERIFY ====================

    public function verifyOtp(
        string $phone,
        string $code,
        string $platform  = 'mobile',
        ?string $appType  = null,
        bool $createToken = true
    ): array {
        $phone = $this->normalizePhone($phone);

        // Detect Firebase JWT token (3-part dot-separated, >100 chars)
        if ($this->isFirebaseToken($code)) {
            if ($this->firebaseAuth === null) {
                return [
                    'success' => false,
                    'message' => 'Firebase Auth non configuré pour ce type de token.',
                ];
            }
            $fbResult = $this->verifyFirebaseToken($code, $phone);
            if (! $fbResult['success']) {
                return $fbResult;
            }
            // Continue to authenticate user
            return $this->authenticateUser($phone, $platform, $appType, $fbResult['firebase_uid'] ?? null, $createToken);
        }

        // Demo mode
        $demoCode = config('otp.demo_code', '');
        $isDemoLogin = $this->isDemoMode() && $code === $demoCode && !empty($demoCode);

        if (! $isDemoLogin) {
            $result = OtpCode::verify($phone, $code);
            if (! $result['success']) {
                return $result;
            }
        }

        return $this->authenticateUser($phone, $platform, $appType, null, $createToken);
    }

    private function isFirebaseToken(string $code): bool
    {
        return substr_count($code, '.') === 2 && strlen($code) > 100;
    }

    // ==================== LOGOUT ====================

    public function logout(User $user): array
    {
        $user->currentAccessToken()?->delete();

        return ['success' => true, 'message' => 'Déconnexion réussie.'];
    }

    public function logoutAll(User $user): array
    {
        $user->tokens()->delete();

        return ['success' => true, 'message' => 'Déconnexion de tous les appareils réussie.'];
    }

    // ==================== REFRESH TOKEN ====================

    public function refreshToken(User $user): array
    {
        if ($user->status !== UserStatus::ACTIVE) {
            return [
                'success' => false,
                'message' => 'Votre compte est suspendu.',
            ];
        }

        // Revoke current token if any
        if ($user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        $abilities = match ($user->role) {
            UserRole::COURIER => ['courier:*'],
            UserRole::ADMIN   => ['admin:*'],
            default           => ['client:*'],
        };

        $token = $user->createToken('auth_token', $abilities);

        return [
            'success' => true,
            'token'   => $token->plainTextToken,
        ];
    }

    // ==================== DELETE ACCOUNT ====================

    public function deleteAccount(User $user): array
    {
        // Courier cannot delete if has active deliveries
        if ($user->role === UserRole::COURIER) {
            $activeOrders = \App\Models\Order::where('courier_id', $user->id)
                ->whereIn('status', ['assigned', 'picked_up', 'in_transit'])
                ->exists();

            if ($activeOrders) {
                return [
                    'success' => false,
                    'message' => 'Impossible de supprimer votre compte : vous avez des livraisons en cours.',
                ];
            }
        }

        // Revoke all tokens
        $user->tokens()->delete();

        // Anonymize data before soft-delete
        $user->update([
            'phone'     => 'deleted_' . $user->id,
            'name'      => 'Compte supprimé',
            'email'     => null,
            'fcm_token' => null,
            'firebase_uid' => null,
        ]);

        $user->delete();

        return [
            'success' => true,
            'message' => 'Votre compte a été supprimé.',
        ];
    }

    // ==================== VALIDATE ROLE ====================

    private function validateUserRoleForApp(User $user, ?string $appType): array
    {
        if ($appType === null) {
            return ['success' => true];
        }
        if ($user->role === UserRole::ADMIN) {
            return ['success' => false, 'message' => 'Cette application n\'est pas disponible pour les administrateurs.'];
        }
        if ($appType === 'courier' && $user->role === UserRole::CLIENT) {
            return ['success' => false, 'message' => 'Votre compte client n\'est pas valide pour cette application. Utilisez l\'application OUAGA CHAP Client.'];
        }
        if ($appType === 'client' && $user->role === UserRole::COURIER) {
            return ['success' => false, 'message' => 'Votre compte coursier n\'est pas valide pour cette application. Utilisez l\'application OUAGA CHAP Coursier.'];
        }
        return ['success' => true];
    }

    // ==================== AUTHENTICATE USER ====================

    private function authenticateUser(string $phone, string $platform = 'mobile', ?string $appType = null, ?string $firebaseUid = null, bool $createToken = true): array
    {
        $registrationData = Cache::pull("registration:{$phone}");

        $user = User::where('phone', $phone)->first();

        if (! $user) {
            $roleFromApp = ($appType === 'courier') ? UserRole::COURIER : UserRole::CLIENT;
            $user = User::create([
                'phone'        => $phone,
                'name'         => $registrationData['name'] ?? null,
                'email'        => $registrationData['email'] ?? null,
                'role'         => $roleFromApp,
                'status'       => ($roleFromApp === UserRole::COURIER) ? UserStatus::PENDING : UserStatus::ACTIVE,
                'firebase_uid' => $firebaseUid,
            ]);
        } else {
            $updates = [];
            if ($firebaseUid) {
                $updates['firebase_uid'] = $firebaseUid;
            }
            if ($registrationData) {
                if (empty($user->name) && ! empty($registrationData['name'])) {
                    $updates['name'] = $registrationData['name'];
                }
                if (empty($user->email) && ! empty($registrationData['email'])) {
                    $updates['email'] = $registrationData['email'];
                }
            }
            if (! empty($updates)) {
                $user->update($updates);
                $user->refresh();
            }
        }

        // Status checks
        if ($user->status === UserStatus::SUSPENDED) {
            return ['success' => false, 'message' => 'Votre compte est suspendu. Contactez le support.'];
        }

        if ($user->status === UserStatus::PENDING) {
            return ['success' => false, 'message' => 'Votre compte est en attente de validation.'];
        }

        if ($user->status === UserStatus::REJECTED) {
            return ['success' => false, 'message' => 'Votre compte a été rejeté. Contactez le support.'];
        }

        // App type validation
        $roleCheck = $this->validateUserRoleForApp($user, $appType);
        if (! $roleCheck['success']) {
            return $roleCheck;
        }

        $abilities = match ($user->role) {
            UserRole::COURIER => ['courier:*'],
            UserRole::ADMIN   => ['admin:*'],
            default           => ['client:*'],
        };

        $token = $user->createToken('api-token', $abilities)->plainTextToken;

        return [
            'success' => true,
            'message' => 'Connexion réussie.',
            'user'    => $user,
            'token'   => $token,
        ];
    }

    public function registerClient(array $data): array
    {
        $phone = $this->normalizePhone($data['phone']);

        $existing = User::where('phone', $phone)->first();
        if ($existing) {
            return ['success' => false, 'message' => 'Ce numéro est déjà inscrit.'];
        }

        // Store in cache for use when user authenticates via Firebase
        \Illuminate\Support\Facades\Cache::put("registration:{$phone}", [
            'name'  => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
        ], now()->addMinutes(30));

        return [
            'success' => true,
            'message' => 'Informations enregistrées. Veuillez vous connecter via Firebase.',
        ];
    }

    // ==================== REGISTRATION ====================

    public function registerCourier(array $data): array
    {
        $phone = $this->normalizePhone($data['phone']);

        $existing = User::where('phone', $phone)
            ->where('role', UserRole::COURIER)
            ->first();

        if ($existing) {
            return ['success' => false, 'message' => 'Ce numéro est déjà inscrit comme coursier.'];
        }

        // Convert existing client to courier
        $client = User::where('phone', $phone)->first();
        if ($client) {
            $client->update([
                'role'          => UserRole::COURIER,
                'status'        => UserStatus::PENDING,
                'name'          => $data['name'] ?? $client->name,
                'vehicle_type'  => $data['vehicle_type'] ?? null,
                'vehicle_plate' => $data['vehicle_plate'] ?? null,
                'vehicle_model' => $data['vehicle_model'] ?? null,
            ]);
            $client->refresh();
            return [
                'success' => true,
                'message' => 'Inscription réussie. Votre compte est en attente de validation.',
                'user'    => $client,
            ];
        }

        $user = User::create([
            'phone'         => $phone,
            'name'          => $data['name'] ?? null,
            'role'          => UserRole::COURIER,
            'status'        => UserStatus::PENDING,
            'vehicle_type'  => $data['vehicle_type'] ?? null,
            'vehicle_plate' => $data['vehicle_plate'] ?? null,
            'vehicle_model' => $data['vehicle_model'] ?? null,
        ]);

        return [
            'success' => true,
            'message' => 'Inscription réussie. Votre compte est en attente de validation.',
            'user'    => $user,
        ];
    }
}

<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\OtpCode;
use App\Models\User;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;
use Laravel\Sanctum\PersonalAccessToken;

class AuthService
{
    private const CLIENT_TOKEN_ABILITY = 'client:*';
    private const COURIER_TOKEN_ABILITY = 'courier:*';
    private const ACCOUNT_SUSPENDED_MESSAGE = 'Votre compte est suspendu. Contactez le support.';
    private const ACCOUNT_PENDING_MESSAGE = 'Votre compte est en attente de validation.';
    private const ACCOUNT_REJECTED_MESSAGE = 'Votre compte a été rejeté. Contactez le support.';
    private const LOGIN_SUCCESS_MESSAGE = 'Connexion réussie.';
    private const COURIER_REGISTRATION_PENDING_MESSAGE = 'Inscription réussie. Votre compte est en attente de validation.';

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
        if (app()->environment('production', 'staging')) {
            if (config('otp.demo_mode', false)) {
                Log::critical('AUTH_OTP_DEMO_MODE is enabled in a production/staging environment — ignoring.', [
                    'env' => app()->environment(),
                ]);
            }
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
        $isAllowed = strlen($digitsOnly) === 8;

        if ($isAllowed) {
            $allowed = config('otp.allowed_countries');
            $isAllowed = empty($allowed) || in_array('226', $allowed, true);
        }

        return $isAllowed;
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
        $result      = null;

        // Country check
        if (! $this->isCountryAllowed($normalized)) {
            $result = [
                'success' => false,
                'message' => 'Votre pays n\'est pas encore supporté.',
            ];
        } elseif ($driver === 'firebase') {
            // Firebase Phone Auth — client handles OTP itself; we just store a backup
            try {
                OtpCode::generate($normalized, $purpose, $ipAddress, $userAgent);
            } catch (\Exception) {
                // Non-blocking
            }
            $result = [
                'success' => true,
                'method'  => 'firebase',
                'phone'   => $formatted,
            ];
        } else {
            // SMS mode
            try {
                $otp = OtpCode::generate($normalized, $purpose, $ipAddress, $userAgent);
                Log::info('OTP generated', ['phone' => $normalized]);

                $result = [
                    'success'    => true,
                    'method'     => 'sms',
                    'phone'      => $formatted,
                    'expires_at' => $otp->expires_at->toIso8601String(),
                ];
            } catch (\Exception $e) {
                $result = $e->getMessage() === 'OTP_RATE_LIMIT_EXCEEDED' ? [
                    'success'   => false,
                    'code'      => 'OTP_RATE_LIMIT_EXCEEDED',
                    'message'   => 'Trop de demandes OTP. Veuillez patienter.',
                ] : [
                    'success' => false,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return $result;
    }

    // ==================== FIREBASE TOKEN ====================

    public function verifyFirebaseToken(string $idToken, string $phone): array
    {
        if ($this->firebaseAuth === null) {
            $result = [
                'success'        => false,
                'message'        => 'Firebase Auth non configuré.',
                'fallback_to_otp' => false,
            ];
        } else {
            try {
                $verifiedToken = $this->firebaseAuth->verifyIdToken($idToken);
                $claims        = $verifiedToken->claims();
                $uid           = $claims->get('sub');

                $result = [
                    'success'     => true,
                    'firebase_uid' => $uid,
                    'phone'       => $phone,
                ];
            } catch (FailedToVerifyToken $e) {
                $result = [
                    'success'        => false,
                    'message'        => 'Token Firebase invalide.',
                    'fallback_to_otp' => false,
                ];
            } catch (ConnectException | RequestException $e) {
                $result = [
                    'success'        => false,
                    'message'        => 'Erreur réseau Firebase.',
                    'fallback_to_otp' => true,
                ];
            } catch (\Throwable $e) {
                $result = [
                    'success'        => false,
                    'message'        => 'Erreur Firebase: ' . $e->getMessage(),
                    'fallback_to_otp' => false,
                ];
            }
        }

        return $result;
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
        $platform = $platform ?: 'mobile';
        $createToken = $createToken || $platform === 'mobile';
        $result = null;

        // Detect Firebase JWT token (3-part dot-separated, >100 chars)
        if (substr_count($code, '.') === 2 && strlen($code) > 100) {
            $result = $this->verifyFirebaseOtpToken($phone, $code, $appType);
        } else {
            // Demo mode
            $demoCode = config('otp.demo_code', '');
            $isDemoLogin = $this->isDemoMode() && $code === $demoCode && !empty($demoCode);

            if (! $isDemoLogin) {
                $otpResult = OtpCode::verify($phone, $code);
                if (! $otpResult['success']) {
                    $result = $otpResult;
                }
            }

            if ($result === null) {
                $result = $this->authenticateUser($phone, $appType);
            }
        }

        return $result;
    }

    private function verifyFirebaseOtpToken(string $phone, string $code, ?string $appType): array
    {
        if ($this->firebaseAuth === null) {
            return [
                'success' => false,
                'message' => 'Firebase Auth non configuré pour ce type de token.',
            ];
        }

        $fbResult = $this->verifyFirebaseToken($code, $phone);

        return $fbResult['success']
            ? $this->authenticateUser($phone, $appType, $fbResult['firebase_uid'] ?? null)
            : $fbResult;
    }

    // ==================== LOGOUT ====================

    public function logout(User $user): array
    {
        $accessToken = $user->currentAccessToken();
        if ($accessToken instanceof PersonalAccessToken) {
            $accessToken->delete();
        }

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
        $accessToken = $user->currentAccessToken();
        if ($accessToken instanceof PersonalAccessToken) {
            $accessToken->delete();
        }

        $abilities = match ($user->role) {
            UserRole::COURIER => [self::COURIER_TOKEN_ABILITY],
            UserRole::ADMIN   => ['admin:*'],
            default           => [self::CLIENT_TOKEN_ABILITY],
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
        $result = ['success' => true];

        if ($appType === null) {
            $result = ['success' => true];
        } elseif ($user->role === UserRole::ADMIN) {
            $result = ['success' => false, 'message' => 'Cette application n\'est pas disponible pour les administrateurs.'];
        } elseif ($appType === 'courier' && $user->role === UserRole::CLIENT) {
            $result = ['success' => false, 'message' => 'Votre compte client n\'est pas valide pour cette application. Utilisez l\'application OUAGA CHAP Client.'];
        } elseif ($appType === 'client' && $user->role === UserRole::COURIER) {
            $result = ['success' => false, 'message' => 'Votre compte coursier n\'est pas valide pour cette application. Utilisez l\'application OUAGA CHAP Coursier.'];
        }

        return $result;
    }

    // ==================== AUTHENTICATE USER ====================

    private function authenticateUser(string $phone, ?string $appType = null, ?string $firebaseUid = null): array
    {
        $registrationData = Cache::pull("registration:{$phone}");
        $user = $this->resolveAuthenticatedUser($phone, $appType, $firebaseUid, $registrationData);

        $statusMessage = match ($user->status) {
            UserStatus::SUSPENDED => self::ACCOUNT_SUSPENDED_MESSAGE,
            UserStatus::PENDING => self::ACCOUNT_PENDING_MESSAGE,
            UserStatus::REJECTED => self::ACCOUNT_REJECTED_MESSAGE,
            default => null,
        };

        $result = $statusMessage === null
            ? ['success' => true]
            : ['success' => false, 'message' => $statusMessage];

        if (! $result['success']) {
            return $result;
        }

        $result = $this->validateUserRoleForApp($user, $appType);
        if (! $result['success']) {
            return $result;
        }

        $abilities = match ($user->role) {
            UserRole::COURIER => [self::COURIER_TOKEN_ABILITY],
            UserRole::ADMIN   => ['admin:*'],
            default           => [self::CLIENT_TOKEN_ABILITY],
        };

        $token = $user->createToken('api-token', $abilities)->plainTextToken;

        return [
            'success' => true,
            'message' => self::LOGIN_SUCCESS_MESSAGE,
            'user'    => $user,
            'token'   => $token,
        ];
    }

    private function resolveAuthenticatedUser(
        string $phone,
        ?string $appType,
        ?string $firebaseUid,
        ?array $registrationData,
    ): User {
        $user = User::where('phone', $phone)->first();

        if (! $user) {
            $roleFromApp = ($appType === 'courier') ? UserRole::COURIER : UserRole::CLIENT;
            $referralService = app(ReferralService::class);
            $user = User::create([
                'phone'         => $phone,
                'name'          => $registrationData['name'] ?? null,
                'email'         => $registrationData['email'] ?? null,
                'password'      => $registrationData['password'] ?? null,
                'role'          => $roleFromApp,
                'status'        => ($roleFromApp === UserRole::COURIER) ? UserStatus::PENDING : UserStatus::ACTIVE,
                'firebase_uid'  => $firebaseUid,
                'referral_code' => $referralService->generateReferralCode(),
            ]);

            if (!empty($registrationData['referral_code'])) {
                $referralService->applyReferralCode($user, strtoupper($registrationData['referral_code']));
            }
        } else {
            $updates = array_filter([
                'firebase_uid' => $firebaseUid,
                'name' => empty($user->name) ? ($registrationData['name'] ?? null) : null,
                'email' => empty($user->email) ? ($registrationData['email'] ?? null) : null,
            ], static fn($value) => $value !== null && $value !== '');

            $user->update($updates);
            $user->refresh();
        }

        return $user;
    }

    public function loginClient(array $data): array
    {
        $phone = $this->formatPhone($data['phone']);
        $legacyPhone = $this->normalizePhone($data['phone']);

        $user = User::whereIn('phone', [$phone, $legacyPhone])->first();
        $errorMessage = null;

        if (! $user || empty($user->password) || ! Hash::check($data['password'], $user->password)) {
            // Message générique — évite l'énumération de numéros de téléphone valides
            $errorMessage = 'Numéro ou mot de passe incorrect.';
        } elseif ($user->status === UserStatus::SUSPENDED) {
            $errorMessage = self::ACCOUNT_SUSPENDED_MESSAGE;
        } elseif ($user->status === UserStatus::PENDING) {
            $errorMessage = self::ACCOUNT_PENDING_MESSAGE;
        } elseif ($user->status === UserStatus::REJECTED) {
            $errorMessage = self::ACCOUNT_REJECTED_MESSAGE;
        } else {
            $roleCheck = $this->validateUserRoleForApp($user, 'client');
            if (! $roleCheck['success']) {
                $errorMessage = $roleCheck['message'];
            }
        }

        if ($errorMessage !== null) {
            return ['success' => false, 'message' => $errorMessage];
        }

        $token = $user->createToken('api-token', [self::CLIENT_TOKEN_ABILITY])->plainTextToken;

        return [
            'success' => true,
            'message' => self::LOGIN_SUCCESS_MESSAGE,
            'user'    => $user,
            'token'   => $token,
        ];
    }

    public function loginCourier(array $data): array
    {
        $phone = $this->formatPhone($data['phone']);
        $legacyPhone = $this->normalizePhone($data['phone']);

        $user = User::whereIn('phone', [$phone, $legacyPhone])
            ->where('role', UserRole::COURIER)
            ->first();
        $errorMessage = null;

        if (! $user || empty($user->password) || ! Hash::check($data['password'], $user->password)) {
            // Message générique — évite l'énumération de numéros de téléphone valides
            $errorMessage = 'Numéro ou mot de passe incorrect.';
        } elseif ($user->status === UserStatus::SUSPENDED) {
            $errorMessage = self::ACCOUNT_SUSPENDED_MESSAGE;
        } elseif ($user->status === UserStatus::PENDING) {
            $errorMessage = self::ACCOUNT_PENDING_MESSAGE;
        } elseif ($user->status === UserStatus::REJECTED) {
            $errorMessage = self::ACCOUNT_REJECTED_MESSAGE;
        } else {
            $roleCheck = $this->validateUserRoleForApp($user, 'courier');
            if (! $roleCheck['success']) {
                $errorMessage = $roleCheck['message'];
            }
        }

        if ($errorMessage !== null) {
            return ['success' => false, 'message' => $errorMessage];
        }

        $token = $user->createToken('api-token', [self::COURIER_TOKEN_ABILITY])->plainTextToken;

        return [
            'success' => true,
            'message' => self::LOGIN_SUCCESS_MESSAGE,
            'user'    => $user,
            'token'   => $token,
        ];
    }

    public function registerClient(array $data): array
    {
        $phone = $this->formatPhone($data['phone']);
        $legacyPhone = $this->normalizePhone($data['phone']);
        $email = strtolower(trim($data['email']));

        $existing = User::whereIn('phone', [$phone, $legacyPhone])->first();
        if ($existing) {
            return [
                'success' => false,
                'message' => 'Ce numéro est déjà inscrit. Connectez-vous avec votre numéro et votre mot de passe.',
            ];
        }

        $existingEmail = User::whereRaw('LOWER(email) = ?', [$email])->first();
        if ($existingEmail) {
            return [
                'success' => false,
                'message' => 'Cet email est déjà utilisé. Connectez-vous ou utilisez une autre adresse email.',
            ];
        }

        $referralService = app(ReferralService::class);

        $newClient = User::create([
            'phone'         => $phone,
            'name'          => $data['name'],
            'email'         => $email,
            'password'      => Hash::make($data['password']),
            'role'          => UserRole::CLIENT,
            'status'        => UserStatus::ACTIVE,
            'referral_code' => $referralService->generateReferralCode(),
        ]);

        // Appliquer le code parrain si fourni
        if (!empty($data['referral_code'])) {
            $referralService->applyReferralCode($newClient, strtoupper($data['referral_code']));
        }

        return [
            'success' => true,
            'message' => 'Compte créé. Connectez-vous avec votre numéro et votre mot de passe.',
        ];
    }

    // ==================== REGISTRATION ====================

    public function registerCourier(array $data): array
    {
        $phone = $this->normalizePhone($data['phone']);
        $email = isset($data['email']) && $data['email'] !== null
            ? strtolower(trim($data['email']))
            : null;

        $formattedPhone = $this->formatPhone($phone);

        $existing = User::whereIn('phone', [$phone, $formattedPhone])
            ->where('role', UserRole::COURIER)
            ->first();

        $registrationError = null;
        if ($existing) {
            $registrationError = 'Ce numéro est déjà inscrit comme coursier.';
        } elseif ($email !== null) {
            $existingEmail = User::whereRaw('LOWER(email) = ?', [$email])->first();
            if ($existingEmail !== null && $this->normalizePhone($existingEmail->phone) !== $phone) {
                $registrationError = 'Cet email est déjà utilisé. Connectez-vous ou utilisez une autre adresse email.';
            }
        }

        if ($registrationError !== null) {
            return ['success' => false, 'message' => $registrationError];
        }

        // Convert existing client to courier
        $client = User::whereIn('phone', [$phone, $formattedPhone])->first();
        if ($client) {
            $client->update([
                'role'          => UserRole::COURIER,
                'status'        => UserStatus::PENDING,
                'name'          => $data['name'] ?? $client->name,
                'email'         => $email ?? $client->email,
                'password'      => isset($data['password']) ? Hash::make($data['password']) : $client->password,
                'vehicle_type'  => $data['vehicle_type'] ?? null,
                'vehicle_plate' => $data['vehicle_plate'] ?? null,
                'vehicle_model' => $data['vehicle_model'] ?? null,
            ]);
            $client->refresh();
            return [
                'success' => true,
                'message' => self::COURIER_REGISTRATION_PENDING_MESSAGE,
                'user'    => $client,
            ];
        }

        $referralService = app(ReferralService::class);

        $user = User::create([
            'phone'         => $phone,
            'name'          => $data['name'] ?? null,
            'email'         => $email,
            'password'      => isset($data['password']) ? Hash::make($data['password']) : null,
            'role'          => UserRole::COURIER,
            'status'        => UserStatus::PENDING,
            'vehicle_type'  => $data['vehicle_type'] ?? null,
            'vehicle_plate' => $data['vehicle_plate'] ?? null,
            'vehicle_model' => $data['vehicle_model'] ?? null,
            'referral_code' => $referralService->generateReferralCode(),
        ]);

        return [
            'success' => true,
            'message' => self::COURIER_REGISTRATION_PENDING_MESSAGE,
            'user'    => $user,
        ];
    }

}

<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;

class AuthService
{
    protected ?FirebaseAuth $firebaseAuth = null;

    public function __construct(
        protected SmsService $smsService
    ) {
        // Initialiser Firebase Auth si disponible
        try {
            $this->firebaseAuth = app('firebase.auth');
        } catch (\Exception $e) {
            Log::warning('Firebase Auth not configured: ' . $e->getMessage());
        }
    }

    /**
     * Normalize phone number to standard format
     */
    public function normalizePhone(string $phone): string
    {
        // Remove spaces and dashes
        $phone = preg_replace('/[\s\-]/', '', $phone);
        
        // Convert to standard format without prefix
        if (str_starts_with($phone, '+226')) {
            $phone = substr($phone, 4);
        } elseif (str_starts_with($phone, '00226')) {
            $phone = substr($phone, 5);
        }
        
        return $phone;
    }

    /**
     * Check if we should use Firebase for OTP
     */
    protected function useFirebaseOtp(): bool
    {
        return $this->firebaseAuth !== null && config('otp.driver', 'firebase') === 'firebase';
    }

    /**
     * Check if demo mode is enabled
     */
    protected function isDemoMode(): bool
    {
        return config('otp.demo_mode', false) || app()->environment('local');
    }

    /**
     * Send OTP code to phone number (fallback mode)
     * Note: En mode Firebase, l'OTP est envoyé directement par Firebase SDK côté client
     */
    public function sendOtp(string $phone): array
    {
        $phone = $this->normalizePhone($phone);
        
        // Vérifier le pays autorisé
        $allowedCountries = config('otp.allowed_countries', []);
        if (!empty($allowedCountries) && !in_array('226', $allowedCountries)) {
            return [
                'success' => false,
                'message' => 'Ce pays n\'est pas supporté.',
            ];
        }
        
        // Si Firebase est configuré, indiquer que l'OTP sera envoyé via Firebase
        if ($this->useFirebaseOtp()) {
            Log::info('OTP request - Firebase mode', ['phone' => substr($phone, 0, 4) . '****']);
            return [
                'success' => true,
                'message' => 'Utilisez Firebase Phone Auth côté client.',
                'method' => 'firebase',
                'phone' => '+226' . $phone,
            ];
        }
        
        // Fallback: OTP manuel via SMS
        $otp = OtpCode::generate($phone);
        
        // Send SMS via configured driver (Twilio or log)
        $smsResult = $this->smsService->sendOtp($phone, $otp->code);
        
        if (!$smsResult['success']) {
            Log::error('Failed to send OTP SMS', [
                'phone' => substr($phone, 0, 4) . '****',
                'error' => $smsResult['error'] ?? 'Unknown error',
            ]);
        }
        
        return [
            'success' => true,
            'message' => 'Code OTP envoyé avec succès.',
            'method' => 'sms',
            'expires_at' => $otp->expires_at->toIso8601String(),
            // Only include debug info in development with log driver
            'debug_code' => (config('app.debug') && config('sms.default') === 'log') 
                ? $otp->code 
                : null,
        ];
    }

    /**
     * Verify OTP using Firebase ID Token or manual code
     * 
     * @param string $phone
     * @param string $code Code OTP ou Firebase ID Token
     * @param string $deviceName
     * @param string|null $appType Type d'application (client, courier)
     * @param bool $useFirebase Si true, traite $code comme un Firebase ID Token
     */
    public function verifyOtp(
        string $phone, 
        string $code, 
        string $deviceName = 'mobile', 
        ?string $appType = null,
        bool $useFirebase = false
    ): array {
        $phone = $this->normalizePhone($phone);
        
        // Mode Firebase: vérifier le ID Token
        if ($useFirebase || $this->isFirebaseToken($code)) {
            Log::info('OTP verification - Firebase mode', ['phone' => substr($phone, 0, 4) . '****']);
            return $this->verifyFirebaseToken($code, $phone, $deviceName, $appType);
        }
        
        // Mode démo : accepter le code configuré
        $demoCode = config('otp.demo_code', '123456');
        $isDemoCode = $code === $demoCode && $this->isDemoMode();
        
        if ($isDemoCode) {
            Log::info('OTP verification - Demo mode', ['phone' => substr($phone, 0, 4) . '****']);
        }
        
        if (!$isDemoCode) {
            $verification = OtpCode::verify($phone, $code);
            
            if (!$verification['success']) {
                return [
                    'success' => false,
                    'message' => $verification['message'] ?? 'Code OTP invalide ou expiré.',
                ];
            }
        }
        
        return $this->authenticateUser($phone, $deviceName, $appType);
    }

    /**
     * Vérifie si le code ressemble à un Firebase ID Token (JWT)
     */
    protected function isFirebaseToken(string $code): bool
    {
        // Firebase ID Tokens sont des JWT longs (>100 caractères)
        // et contiennent des points (header.payload.signature)
        return strlen($code) > 100 && substr_count($code, '.') === 2;
    }

    /**
     * Verify Firebase ID Token and authenticate user
     */
    public function verifyFirebaseToken(
        string $idToken, 
        ?string $expectedPhone = null,
        string $deviceName = 'mobile',
        ?string $appType = null
    ): array {
        if ($this->firebaseAuth === null) {
            Log::error('Firebase Auth not available for token verification');
            return [
                'success' => false,
                'message' => 'Firebase Auth non configuré sur le serveur.',
            ];
        }

        try {
            // Vérifier le token Firebase
            $verifiedIdToken = $this->firebaseAuth->verifyIdToken($idToken);
            
            // Extraire les claims
            $claims = $verifiedIdToken->claims();
            $firebaseUid = $claims->get('sub');
            $phoneNumber = $claims->get('phone_number');
            
            if (empty($phoneNumber)) {
                return [
                    'success' => false,
                    'message' => 'Le token Firebase ne contient pas de numéro de téléphone.',
                ];
            }

            // Normaliser le numéro de Firebase (+226XXXXXXXX -> XXXXXXXX)
            $normalizedPhone = $this->normalizePhone($phoneNumber);
            
            // Si un numéro attendu est fourni, vérifier qu'il correspond
            if ($expectedPhone !== null) {
                $expectedNormalized = $this->normalizePhone($expectedPhone);
                if ($normalizedPhone !== $expectedNormalized) {
                    Log::warning('Phone mismatch in Firebase token', [
                        'expected' => substr($expectedNormalized, 0, 4) . '****',
                        'received' => substr($normalizedPhone, 0, 4) . '****',
                    ]);
                    return [
                        'success' => false,
                        'message' => 'Le numéro de téléphone ne correspond pas.',
                    ];
                }
            }

            Log::info('Firebase token verified', [
                'firebase_uid' => $firebaseUid,
                'phone' => substr($normalizedPhone, 0, 4) . '****',
            ]);

            return $this->authenticateUser($normalizedPhone, $deviceName, $appType, $firebaseUid);

        } catch (FailedToVerifyToken $e) {
            Log::error('Firebase token verification failed', [
                'error' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'message' => 'Token Firebase invalide ou expiré.',
            ];
        } catch (\Exception $e) {
            Log::error('Firebase verification error', [
                'error' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'message' => 'Erreur lors de la vérification Firebase.',
            ];
        }
    }

    /**
     * Authenticate or create user and return token
     */
    protected function authenticateUser(
        string $phone, 
        string $deviceName, 
        ?string $appType,
        ?string $firebaseUid = null
    ): array {
        // Find or create user
        $user = User::firstOrCreate(
            ['phone' => $phone],
            [
                'role' => UserRole::CLIENT,
                'status' => UserStatus::ACTIVE,
                'firebase_uid' => $firebaseUid,
            ]
        );

        // Mettre à jour le Firebase UID si nouveau
        if ($firebaseUid && $user->firebase_uid !== $firebaseUid) {
            $user->update(['firebase_uid' => $firebaseUid]);
        }

        // Validation du rôle selon l'application
        if ($appType !== null) {
            $roleValidation = $this->validateUserRoleForApp($user, $appType);
            if (!$roleValidation['success']) {
                return $roleValidation;
            }
        }
        
        // Check if user is suspended
        if ($user->status === UserStatus::SUSPENDED) {
            return [
                'success' => false,
                'message' => 'Votre compte est suspendu. Contactez le support.',
            ];
        }
        
        // Check if courier is pending approval
        if ($user->role === UserRole::COURIER && $user->status === UserStatus::PENDING) {
            return [
                'success' => false,
                'message' => 'Votre compte coursier est en attente de validation par un administrateur.',
            ];
        }
        
        // Create token
        $token = $user->createToken($deviceName)->plainTextToken;
        
        return [
            'success' => true,
            'message' => 'Connexion réussie.',
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Valide que le rôle de l'utilisateur correspond à l'application utilisée
     * 
     * @param User $user
     * @param string $appType Type d'application (client, courier)
     * @return array
     */
    protected function validateUserRoleForApp(User $user, string $appType): array
    {
        // Un admin ne peut pas se connecter aux apps mobiles
        if ($user->role === UserRole::ADMIN) {
            return [
                'success' => false,
                'message' => 'Les comptes administrateurs ne peuvent pas se connecter aux applications mobiles.',
            ];
        }

        // Validation selon le type d'app
        if ($appType === 'client' && $user->role !== UserRole::CLIENT) {
            return [
                'success' => false,
                'message' => 'Ce compte est un compte coursier. Veuillez utiliser l\'application OUAGA CHAP Coursier.',
            ];
        }

        if ($appType === 'courier' && $user->role !== UserRole::COURIER) {
            return [
                'success' => false,
                'message' => 'Ce compte est un compte client. Veuillez utiliser l\'application OUAGA CHAP Client.',
            ];
        }

        return ['success' => true];
    }

    /**
     * Register a new courier
     */
    public function registerCourier(array $data): array
    {
        $data['phone'] = $this->normalizePhone($data['phone']);
        
        $user = User::create([
            'phone' => $data['phone'],
            'name' => $data['name'],
            'role' => UserRole::COURIER,
            'status' => UserStatus::PENDING, // Needs admin approval
            'vehicle_type' => $data['vehicle_type'],
            'vehicle_plate' => $data['vehicle_plate'],
            'vehicle_model' => $data['vehicle_model'] ?? null,
            'is_available' => false,
        ]);
        
        return [
            'success' => true,
            'message' => 'Inscription réussie. Votre compte est en attente de validation.',
            'user' => $user,
        ];
    }

    /**
     * Logout user (revoke current token)
     */
    public function logout(User $user): array
    {
        $user->currentAccessToken()->delete();
        
        return [
            'success' => true,
            'message' => 'Déconnexion réussie.',
        ];
    }

    /**
     * Logout from all devices
     */
    public function logoutAll(User $user): array
    {
        $user->tokens()->delete();
        
        return [
            'success' => true,
            'message' => 'Déconnexion de tous les appareils réussie.',
        ];
    }
}

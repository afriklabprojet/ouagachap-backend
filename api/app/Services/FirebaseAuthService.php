<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;

/**
 * Firebase Authentication Service
 *
 * Authentification 100% Firebase :
 * 1. Flutter gère l'OTP phone via Firebase Auth SDK
 * 2. Flutter envoie le Firebase ID Token à l'API
 * 3. L'API vérifie le token et retourne un Sanctum token
 */
class FirebaseAuthService
{
    private ?FirebaseAuth $firebaseAuth = null;

    public function __construct()
    {
        try {
            $this->firebaseAuth = app('firebase.auth');
        } catch (\Throwable $e) {
            Log::warning('FirebaseAuthService: Firebase Auth non configuré: ' . $e->getMessage());
        }
    }

    /**
     * Authentifier via Firebase ID Token
     *
     * @param string $idToken    Firebase ID Token (JWT)
     * @param string $appType    'client' | 'courier'
     * @param string $deviceName Nom de l'appareil pour le token Sanctum
     */
    public function authenticateWithToken(
        string $idToken,
        string $appType,
        string $deviceName = 'mobile'
    ): array {
        if ($this->firebaseAuth === null) {
            Log::error('FirebaseAuthService: Firebase Auth SDK non configuré');
            return [
                'success' => false,
                'message' => 'Service Firebase non disponible. Contactez le support.',
            ];
        }

        // 1. Vérifier le token Firebase
        try {
            $verifiedToken = $this->firebaseAuth->verifyIdToken($idToken);
        } catch (FailedToVerifyToken $e) {
            Log::warning('FirebaseAuthService: Token invalide', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Token Firebase invalide ou expiré. Veuillez vous reconnecter.',
            ];
        } catch (\Exception $e) {
            Log::error('FirebaseAuthService: Erreur vérification', [
                'class' => get_class($e),
                'error' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'message' => 'Erreur lors de la vérification Firebase.',
            ];
        }

        // 2. Extraire les claims
        $claims      = $verifiedToken->claims();
        $firebaseUid = $claims->get('sub');
        $rawPhone    = $claims->get('phone_number');

        if (empty($rawPhone)) {
            return [
                'success' => false,
                'message' => 'Le token Firebase ne contient pas de numéro de téléphone.',
            ];
        }

        $phone = $this->normalizePhone($rawPhone);

        Log::info('FirebaseAuthService: Token vérifié', [
            'uid'   => $firebaseUid,
            'phone' => substr($phone, 0, 4) . '****',
            'app'   => $appType,
        ]);

        // 3. Créer ou récupérer l'utilisateur
        return $this->findOrCreateUser($phone, $firebaseUid, $appType, $deviceName);
    }

    /**
     * Créer ou récupérer l'utilisateur et retourner le token Sanctum
     */
    private function findOrCreateUser(
        string $phone,
        string $firebaseUid,
        string $appType,
        string $deviceName
    ): array {
        // Données de pré-inscription éventuellement stockées par registerClient/registerCourier
        $registrationData = Cache::pull("registration:{$phone}");

        try {
            $user = User::firstOrCreate(
                ['phone' => $phone],
                [
                    'name'        => $registrationData['name'] ?? null,
                    'email'       => $registrationData['email'] ?? null,
                    'role'        => $appType === 'courier' ? UserRole::COURIER : UserRole::CLIENT,
                    'status'      => $appType === 'courier' ? UserStatus::PENDING : UserStatus::ACTIVE,
                    'firebase_uid' => $firebaseUid,
                ]
            );
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            // Race condition — un autre processus a créé l'utilisateur en parallèle
            $user = User::where('phone', $phone)->firstOrFail();
        }

        // Mettre à jour les données si l'utilisateur existait déjà
        $updates = [];

        if (!empty($registrationData['name']) && empty($user->name)) {
            $updates['name'] = $registrationData['name'];
        }
        if (!empty($registrationData['email']) && empty($user->email)) {
            $updates['email'] = $registrationData['email'];
        }
        if ($user->firebase_uid !== $firebaseUid) {
            $updates['firebase_uid'] = $firebaseUid;
        }

        if (!empty($updates)) {
            $user->update($updates);
        }

        // Valider le rôle selon l'app
        $roleCheck = $this->validateRole($user, $appType);
        if (!$roleCheck['success']) {
            return $roleCheck;
        }

        // Vérifier le statut du compte
        if ($user->status === UserStatus::SUSPENDED) {
            return [
                'success' => false,
                'message' => 'Votre compte est suspendu. Contactez le support.',
            ];
        }

        if ($user->role === UserRole::COURIER && $user->status === UserStatus::PENDING) {
            return [
                'success' => false,
                'message' => 'Votre compte coursier est en attente de validation par un administrateur.',
            ];
        }

        // Émettre le token Sanctum
        $abilities = match ($user->role) {
            UserRole::CLIENT  => ['client:*'],
            UserRole::COURIER => ['courier:*'],
            UserRole::ADMIN   => ['admin:*'],
        };

        $token = $user->createToken($deviceName, $abilities)->plainTextToken;

        Log::info('FirebaseAuthService: Connexion réussie', [
            'user_id' => $user->id,
            'role'    => $user->role->value,
        ]);

        return [
            'success' => true,
            'message' => 'Connexion réussie.',
            'token'   => $token,
            'user'    => $user->only([
                'id', 'name', 'email', 'phone', 'role', 'status',
                'avatar', 'firebase_uid', 'fcm_token',
                'wallet_balance', 'kyc_status',
            ]),
        ];
    }

    /**
     * Valider que le rôle de l'utilisateur correspond à l'app
     */
    private function validateRole(User $user, string $appType): array
    {
        if ($user->role === UserRole::ADMIN) {
            return [
                'success' => false,
                'message' => 'Les comptes admin ne peuvent pas se connecter aux apps mobiles.',
            ];
        }

        if ($appType === 'client' && $user->role !== UserRole::CLIENT) {
            return [
                'success' => false,
                'message' => 'Ce compte est un compte coursier. Utilisez l\'app OUAGA CHAP Coursier.',
            ];
        }

        if ($appType === 'courier' && $user->role !== UserRole::COURIER) {
            return [
                'success' => false,
                'message' => 'Ce compte est un compte client. Utilisez l\'app OUAGA CHAP Client.',
            ];
        }

        return ['success' => true];
    }

    /**
     * Normaliser le numéro de téléphone (supprimer préfixe +226)
     */
    public function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[\s\-]/', '', $phone);

        if (str_starts_with($phone, '+226')) {
            return substr($phone, 4);
        }

        if (str_starts_with($phone, '00226')) {
            return substr($phone, 5);
        }

        return $phone;
    }
}

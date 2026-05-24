<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\FirebaseAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Firebase Authentication Controller
 *
 * Authentification mobile via Firebase Phone Auth.
 * Le client Flutter gère l'OTP, envoie le Firebase ID Token, et reçoit un Sanctum token.
 */
class FirebaseAuthController extends Controller
{
    public function __construct(
        private readonly FirebaseAuthService $firebaseAuthService,
    ) {}

    /**
     * POST /api/v1/auth/firebase
     *
     * Échanger un Firebase ID Token contre un Sanctum token.
     *
     * @bodyParam firebase_token string required Firebase ID Token. Example: eyJhbGci...
     * @bodyParam role string required Rôle de l'application. Example: client
     * @bodyParam device_name string Nom de l'appareil. Example: iPhone 15
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'firebase_token' => ['required', 'string', 'min:100'],
            'role'           => ['required', 'string', 'in:client,courier'],
            'device_name'    => ['nullable', 'string', 'max:100'],
            'device_id'      => ['nullable', 'string', 'max:255'],
            'platform'       => ['nullable', 'string', 'in:android,ios,web'],
            'device_type'    => ['nullable', 'string', 'max:100'],
        ]);

        $deviceName = $validated['device_name']
            ?? ($request->userAgent() ?? 'mobile');

        $result = $this->firebaseAuthService->authenticateWithToken(
            idToken:    $validated['firebase_token'],
            appType:    $validated['role'],
            deviceName: $deviceName,
            deviceId:   $validated['device_id'] ?? null,
            platform:   $validated['platform'] ?? null,
            deviceType: $validated['device_type'] ?? null,
        );

        if (!$result['success']) {
            $statusCode = match (true) {
                str_contains($result['message'] ?? '', 'suspendu') => 403,
                str_contains($result['message'] ?? '', 'attente')  => 403,
                str_contains($result['message'] ?? '', 'invalide') => 401,
                str_contains($result['message'] ?? '', 'expiré')   => 401,
                default                                             => 422,
            };

            return response()->json([
                'message' => $result['message'],
            ], $statusCode);
        }

        return response()->json([
            'token'                     => $result['token'],
            'token_type'                => 'Bearer',
            'user'                      => $result['user'],
            'duplicate_account_warning' => $result['duplicate_account_warning'] ?? false,
        ]);
    }

    /**
     * GET /api/v1/auth/me
     *
     * Retourner le profil de l'utilisateur connecté.
     *
     * @authenticated
     */
    public function me(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        return response()->json([
            'user' => $user->only([
                'id', 'name', 'email', 'phone', 'role', 'status',
                'avatar', 'firebase_uid', 'fcm_token',
                'wallet_balance', 'kyc_status',
                'vehicle_type', 'vehicle_plate', 'is_available',
            ]),
        ]);
    }

    /**
     * POST /api/v1/auth/logout
     *
     * Révoquer le token courant.
     *
     * @authenticated
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['success' => true, 'message' => 'Déconnexion réussie.']);
    }

    /**
     * POST /api/v1/auth/logout-all
     *
     * Révoquer tous les tokens de l'utilisateur.
     *
     * @authenticated
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json(['success' => true, 'message' => 'Déconnecté de tous les appareils.']);
    }

    /**
     * POST /api/v1/auth/fcm-token
     *
     * Mettre à jour le token FCM (notifications push).
     *
     * @authenticated
     * @bodyParam fcm_token string required Token FCM Firebase. Example: eHs9...
     */
    public function updateFcmToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fcm_token'   => ['required', 'string'],
            'device_type' => ['nullable', 'string', 'in:android,ios'],
        ]);

        $request->user()->update([
            'fcm_token'          => $validated['fcm_token'],
            'device_type'        => $validated['device_type'] ?? $request->user()->device_type,
            'fcm_token_updated_at' => now(),
        ]);

        return response()->json(['message' => 'Token FCM mis à jour.']);
    }

    /**
     * DELETE /api/v1/auth/account
     *
     * Supprimer le compte de l'utilisateur.
     *
     * @authenticated
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        $user = $request->user();

        // Bloquer si coursier avec livraisons actives
        if ($user->role === \App\Enums\UserRole::COURIER) {
            $hasActive = \App\Models\Order::where('courier_id', $user->id)
                ->whereIn('status', ['assigned', 'picked_up', 'in_transit'])
                ->exists();

            if ($hasActive) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer votre compte. Veuillez terminer vos livraisons en cours.',
                ], 409);
            }
        }

        // Révoquer tous les tokens avant suppression
        $user->tokens()->delete();

        // Anonymisation RGPD
        $user->update([
            'phone'        => 'deleted_' . $user->id,
            'email'        => null,
            'name'         => 'Compte supprimé',
            'firebase_uid' => null,
            'fcm_token'    => null,
            'avatar'       => null,
            'status'       => \App\Enums\UserStatus::SUSPENDED,
        ]);

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Votre compte a été supprimé avec succès.',
        ]);
    }
}

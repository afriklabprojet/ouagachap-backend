<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Auth\RegisterCourierRequest;
use App\Http\Requests\Auth\SendOtpRequest;
use App\Http\Requests\Auth\UpdateFcmTokenRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Authentification
 *
 * Endpoints pour l'authentification par OTP et la gestion du profil utilisateur.
 */
class AuthController extends BaseController
{
    public function __construct(
        private AuthService $authService
    ) {}

    /**
     * Envoyer un OTP
     *
     * Envoie un code OTP à 6 chiffres au numéro de téléphone spécifié.
     * Le code expire après 5 minutes.
     *
     * @unauthenticated
     * @bodyParam phone string required Le numéro de téléphone au format Burkina Faso. Example: +22670123456
     * @response 200 {"success": true, "message": "Code OTP envoyé avec succès.", "data": {"expires_in": 300}}
     */
    public function sendOtp(SendOtpRequest $request): JsonResponse
    {
        $result = $this->authService->sendOtp($request->phone);

        return $this->success($result, $result['message']);
    }

    /**
     * Vérifier l'OTP
     *
     * Vérifie le code OTP et connecte/inscrit l'utilisateur.
     * Retourne un token Bearer pour les requêtes authentifiées.
     *
     * @unauthenticated
     * @bodyParam phone string required Le numéro de téléphone. Example: +22670123456
     * @bodyParam code string required Le code OTP à 6 chiffres. Example: 123456
     * @bodyParam device_name string Nom de l'appareil. Example: iPhone 15
     * @bodyParam app_type string Type d'application (client ou courier). Example: client
     * @response 200 {"success": true, "message": "Connexion réussie.", "data": {"user": {"id": 1, "name": "John", "phone": "+22670123456", "role": "client"}, "token": "1|abc123..."}}
     * @response 401 {"success": false, "message": "Code OTP invalide ou expiré."}
     * @response 403 {"success": false, "message": "Ce compte est un compte coursier. Veuillez utiliser l'application OUAGA CHAP Coursier."}
     */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $result = $this->authService->verifyOtp(
            $request->phone,
            $request->code,
            $request->device_name ?? 'mobile',
            $request->app_type
        );

        if (!$result['success']) {
            // Utiliser 403 pour les erreurs de rôle, 401 pour les erreurs d'authentification
            $statusCode = str_contains($result['message'], 'application') ? 403 : 401;
            return $this->error($result['message'], $statusCode);
        }

        return $this->success([
            'user' => $result['user'],
            'token' => $result['token'],
        ], $result['message']);
    }

    /**
     * Inscription coursier
     *
     * Inscrit un nouvel utilisateur en tant que coursier.
     * Le coursier doit ensuite être approuvé par un admin.
     *
     * @unauthenticated
     * @bodyParam phone string required Numéro de téléphone. Example: +22670123456
     * @bodyParam name string required Nom complet. Example: Ouédraogo Ibrahim
     * @bodyParam email string Email optionnel. Example: ibrahim@example.com
     * @bodyParam vehicle_type string required Type de véhicule (moto, vélo, voiture). Example: moto
     * @bodyParam vehicle_plate string Plaque d'immatriculation. Example: 12AB3456
     * @response 201 {"success": true, "message": "Inscription réussie. En attente d'approbation.", "data": {"id": 1, "name": "Ouédraogo Ibrahim", "role": "courier", "status": "pending"}}
     */
    public function registerCourier(RegisterCourierRequest $request): JsonResponse
    {
        $result = $this->authService->registerCourier($request->validated());

        return $this->success($result['user'], $result['message'], 201);
    }

    /**
     * Profil utilisateur
     *
     * Retourne les informations de l'utilisateur connecté.
     *
     * @response 200 {"success": true, "data": {"id": 1, "name": "John", "phone": "+22670123456", "email": null, "role": "client", "wallet_balance": 0}}
     */
    public function me(Request $request): JsonResponse
    {
        return $this->success($request->user());
    }

    /**
     * Mettre à jour le profil
     *
     * Met à jour les informations du profil utilisateur.
     *
     * @bodyParam name string Nouveau nom. Example: Jean Dupont
     * @bodyParam email string Nouvel email. Example: jean@example.com
     * @bodyParam avatar file Photo de profil (jpeg, png, jpg max 2MB).
     * @response 200 {"success": true, "message": "Profil mis à jour.", "data": {"id": 1, "name": "Jean Dupont"}}
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'unique:users,email,' . $request->user()->id],
            'fcm_token' => ['sometimes', 'string'],
            'avatar' => ['sometimes', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ]);

        $user = $request->user();

        // Gérer l'upload de l'avatar
        if ($request->hasFile('avatar')) {
            // Supprimer l'ancien avatar si existant
            if ($user->avatar) {
                \Storage::disk('public')->delete($user->avatar);
            }
            
            // Stocker le nouvel avatar
            $path = $request->file('avatar')->store('avatars', 'public');
            $validated['avatar'] = $path;
        }

        $user->update($validated);

        return $this->success(new UserResource($user->fresh()), 'Profil mis à jour.');
    }

    /**
     * Déconnexion
     *
     * Révoque le token actuel de l'utilisateur.
     *
     * @response 200 {"success": true, "message": "Déconnexion réussie."}
     */
    public function logout(Request $request): JsonResponse
    {
        $result = $this->authService->logout($request->user());

        return $this->success(null, $result['message']);
    }

    /**
     * Déconnexion de tous les appareils
     *
     * Révoque tous les tokens de l'utilisateur.
     *
     * @response 200 {"success": true, "message": "Déconnexion de tous les appareils réussie."}
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $result = $this->authService->logoutAll($request->user());

        return $this->success(null, $result['message']);
    }

    /**
     * Update FCM token for push notifications
     * PUT /api/v1/auth/fcm-token
     */
    public function updateFcmToken(UpdateFcmTokenRequest $request): JsonResponse
    {
        $request->user()->update([
            'fcm_token' => $request->fcm_token,
            'device_type' => $request->device_type,
            'fcm_token_updated_at' => now(),
        ]);

        return $this->success(null, 'Token FCM mis à jour.');
    }
}

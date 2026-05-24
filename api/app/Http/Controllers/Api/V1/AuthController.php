<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Auth\RegisterCourierRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * @group Authentification — endpoints résidants post-migration Firebase.
 *
 * Les endpoints OTP / JWT / SMS / WhatsApp ont été supprimés.
 * L'authentification principale passe par :
 *   POST /api/v1/auth/firebase (FirebaseAuthController)
 *
 * Ce contrôleur conserve uniquement :
 *   - POST /auth/register/courier (inscription coursier, route publique)
 *   - PUT  /auth/profile          (mise à jour profil, route protégée)
 */
class AuthController extends BaseController
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

    // ─────────────────────────────────────────────────────────────
    //  Inscription coursier (route publique)
    // ─────────────────────────────────────────────────────────────

    /**
     * Inscrire un coursier
     *
     * Crée un compte coursier en statut PENDING (validation admin requise).
     * La connexion s'effectue ensuite via Firebase Phone Auth.
     *
     * @unauthenticated
     * @bodyParam phone        string required Numéro BF. Example: +22670123456
     * @bodyParam name         string required Nom complet. Example: Ouédraogo Drissa
     * @bodyParam vehicle_type string required Type de véhicule. Example: moto
     * @bodyParam vehicle_plate string required Immatriculation. Example: AB1234BF
     * @bodyParam vehicle_model string Modèle (optionnel). Example: Honda CB125
     *
     * @response 201 {"success": true, "message": "Inscription réussie. Votre compte est en attente de validation.", "data": {...}}
     * @response 409 {"success": false, "message": "Ce numéro est déjà inscrit comme coursier."}
     */
    public function registerCourier(RegisterCourierRequest $request): JsonResponse
    {
        $result = $this->authService->registerCourier($request->validated());

        if (!$result['success']) {
            return $this->error($result['message'], 409);
        }

        return $this->success(new UserResource($result['user']), $result['message'], 201);
    }

    // ─────────────────────────────────────────────────────────────
    //  Profil (route protégée — auth.api)
    // ─────────────────────────────────────────────────────────────

    /**
     * Mettre à jour le profil
     *
     * Met à jour les informations du profil utilisateur connecté.
     *
     * @bodyParam name   string Nouveau nom. Example: Jean Dupont
     * @bodyParam email  string Nouvel email. Example: jean@example.com
     * @bodyParam avatar file   Photo de profil (jpeg, png, jpg, max 2 MB).
     *
     * @response 200 {"success": true, "message": "Profil mis à jour.", "data": {...}}
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $path = $request->file('avatar')->store('avatars', 'public');
            $validated['avatar'] = $path;
        }

        $user->update($validated);

        return $this->success(new UserResource($user->fresh()), 'Profil mis à jour.');
    }

    // ─────────────────────────────────────────────────────────────
    //  OTP (SMS) — envoyer et vérifier
    // ─────────────────────────────────────────────────────────────

    public function sendOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone'   => ['required', 'string', 'min:8', 'max:20'],
            'purpose' => ['nullable', 'string'],
        ]);

        $result = $this->authService->sendOtp(
            $validated['phone'],
            $validated['purpose'] ?? 'login',
            $request->ip(),
            $request->userAgent(),
        );

        if (!$result['success']) {
            if (($result['code'] ?? null) === 'OTP_RATE_LIMIT_EXCEEDED') {
                return response()->json(['success' => false, 'code' => 'OTP_RATE_LIMIT_EXCEEDED', 'message' => $result['message']], 429);
            }
            $status = str_contains($result['message'] ?? '', 'rate') ? 429 : 422;
            return $this->error($result['message'], $status);
        }

        return $this->success(
            ['expires_at' => $result['expires_at'] ?? null],
            $result['message'] ?? 'Code envoyé.',
        );
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone'    => ['required', 'string', 'min:8', 'max:20'],
            'code'     => ['required', 'string', 'digits:6'],
            'platform' => ['nullable', 'string'],
            'app_type' => ['nullable', 'string'],
        ]);

        $result = $this->authService->verifyOtp(
            $validated['phone'],
            $validated['code'],
            $validated['platform'] ?? 'mobile',
            $validated['app_type'] ?? null,
        );

        if (!$result['success']) {
            $status = 401;
            if (str_contains($result['message'] ?? '', 'suspendu')) {
                $status = 403;
            } elseif (str_contains($result['message'] ?? '', 'maximum')) {
                $status = 429;
            }
            return $this->error($result['message'], $status);
        }

        return $this->success(
            [
                'user'  => new UserResource($result['user']),
                'token' => $result['token'] ?? null,
            ],
            $result['message'] ?? 'Connexion réussie.',
        );
    }

    // ─────────────────────────────────────────────────────────────
    //  Profil courant
    // ─────────────────────────────────────────────────────────────

    public function me(Request $request): JsonResponse
    {
        return $this->success(new UserResource($request->user()));
    }

    // ─────────────────────────────────────────────────────────────
    //  Logout
    // ─────────────────────────────────────────────────────────────

    public function logout(Request $request): JsonResponse
    {
        $result = $this->authService->logout($request->user());
        return $this->success(null, $result['message']);
    }

    // ─────────────────────────────────────────────────────────────
    //  Refresh token
    // ─────────────────────────────────────────────────────────────

    public function refreshToken(Request $request): JsonResponse
    {
        $result = $this->authService->refreshToken($request->user());

        if (!$result['success']) {
            return $this->error($result['message'], 403);
        }

        return $this->success(
            [
                'token' => $result['token'],
                'user'  => new UserResource($request->user()->fresh()),
            ],
            'Token renouvelé.',
        );
    }
}

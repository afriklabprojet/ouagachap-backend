<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Http\Requests\Auth\RegisterClientRequest;
use App\Http\Requests\Auth\RegisterCourierRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Services\CdnService;
use App\Services\CourierDeviceAttestationService;
use App\Services\CourierPasswordResetService;
use App\Services\PhoneVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

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
    private const BF_PHONE_RULE = 'regex:/^(\+226|00226)?[0-9]{8}$/';

    private const LOGIN_SUCCESS_MESSAGE = 'Connexion réussie.';

    public function __construct(
        private readonly AuthService $authService,
        private readonly PhoneVerificationService $phoneVerificationService,
        private readonly CourierPasswordResetService $courierPasswordResetService,
        private readonly CourierDeviceAttestationService $courierDeviceAttestationService,
    ) {}

    // ─────────────────────────────────────────────────────────────
    //  Vérification numéro de téléphone (route protégée)
    // ─────────────────────────────────────────────────────────────

    /**
     * Envoyer un OTP de vérification du numéro
     *
     * Envoie un code SMS à 6 chiffres sur le numéro du compte.
     * Valide uniquement 10 minutes.
     *
     * @response 200 {"success": true, "message": "Code envoyé par SMS."}
     * @response 409 {"success": false, "message": "Ce numéro est déjà vérifié."}
     */
    public function sendPhoneVerificationOtp(Request $request): JsonResponse
    {
        $result = $this->phoneVerificationService->sendOtp($request->user());

        if (! $result['success']) {
            return $this->error($result['message'], 409);
        }

        return $this->success(null, $result['message']);
    }

    /**
     * Vérifier le code OTP reçu par SMS
     *
     * @bodyParam code string required Code à 6 chiffres reçu par SMS. Example: 123456
     *
     * @response 200 {"success": true, "message": "Numéro vérifié.", "data": {"user": {...}}}
     * @response 422 {"success": false, "message": "Code incorrect."}
     */
    public function verifyPhoneOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'digits:6'],
        ]);

        $result = $this->phoneVerificationService->verifyOtp($request->user(), $validated['code']);

        if (! $result['success']) {
            return $this->error($result['message'], 422);
        }

        return $this->success(
            ['user' => new UserResource($result['user'])],
            $result['message'],
        );
    }

    // ─────────────────────────────────────────────────────────────
    //  Connexion client (route publique — sans OTP)
    // ─────────────────────────────────────────────────────────────

    /**
     * Connecter un client
     *
     * Authentifie directement par numéro de téléphone et mot de passe.
     * Retourne un token Sanctum si le compte existe et est actif.
     *
     * @unauthenticated
     *
     * @bodyParam phone string required Numéro BF. Example: +22670123456
     * @bodyParam password string required Mot de passe. Example: password123
     *
     * @response 200 {"success": true, "data": {"user": {...}, "token": "..."}}
     * @response 404 {"success": false, "message": "Aucun compte trouvé pour ce numéro."}
     * @response 403 {"success": false, "message": "Votre compte est suspendu."}
     */
    public function loginClient(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', self::BF_PHONE_RULE],
            'password' => ['required', 'string'],
        ]);

        $result = $this->authService->loginClient($validated);

        if (! $result['success']) {
            $message = $result['message'] ?? '';
            $status = 404;
            if (str_contains($message, 'incorrect')) {
                $status = 401;
            } elseif (str_contains($message, 'suspendu') || str_contains($message, 'attente') || str_contains($message, 'rejeté')) {
                $status = 403;
            }

            return $this->error($result['message'], $status);
        }

        $this->issueCourierAttestationSessionIfApplicable($request, $result);

        return $this->success(
            [
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
                'expires_at' => $result['expires_at'] ?? null,
            ],
            $result['message'] ?? self::LOGIN_SUCCESS_MESSAGE,
        );
    }

    // ─────────────────────────────────────────────────────────────
    //  Connexion coursier (route publique — sans OTP)
    // ─────────────────────────────────────────────────────────────

    /**
     * Connecter un coursier
     *
     * Authentifie directement par numéro de téléphone et mot de passe.
     * Retourne un token Sanctum coursier si le compte existe et est actif.
     *
     * @unauthenticated
     *
     * @bodyParam phone string required Numéro BF. Example: +22670123456
     * @bodyParam password string required Mot de passe. Example: password123
     *
     * @response 200 {"success": true, "data": {"user": {...}, "token": "..."}}
     * @response 404 {"success": false, "message": "Aucun compte coursier trouvé pour ce numéro."}
     * @response 403 {"success": false, "message": "Votre compte est en attente de validation."}
     */
    public function loginCourier(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', self::BF_PHONE_RULE],
            'password' => ['required', 'string'],
        ]);

        $result = $this->authService->loginCourier($validated);

        if (! $result['success']) {
            $message = $result['message'] ?? '';
            $status = 404;
            if (str_contains($message, 'incorrect') || str_contains($message, 'configuré')) {
                $status = 401;
            } elseif (str_contains($message, 'suspendu') || str_contains($message, 'attente') || str_contains($message, 'rejeté')) {
                $status = 403;
            }

            return $this->error($result['message'], $status);
        }

        $deviceAttestation = $this->buildCourierAttestationPayload($request, $result);

        return $this->success(
            [
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
                'expires_at' => $result['expires_at'] ?? null,
                'device_attestation' => $deviceAttestation,
            ],
            $result['message'] ?? self::LOGIN_SUCCESS_MESSAGE,
        );
    }

    /**
     * Demander un code de réinitialisation du mot de passe coursier.
     *
     * @unauthenticated
     *
     * @bodyParam phone string required Numéro BF. Example: +22670123456
     */
    public function forgotCourierPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'identifier' => ['nullable', 'string', 'max:255', 'required_without:phone'],
            'phone' => ['nullable', 'string', 'max:255', 'required_without:identifier'],
        ]);

        $identifier = $validated['identifier'] ?? $validated['phone'];

        $result = $this->courierPasswordResetService->sendOtp(
            $identifier,
            $request->ip(),
            $request->userAgent(),
        );

        if (! $result['success']) {
            $status = ($result['code'] ?? null) === 'OTP_RATE_LIMIT_EXCEEDED' ? 429 : 422;

            return $this->error($result['message'], $status);
        }

        return $this->success(null, $result['message']);
    }

    /**
     * Réinitialiser le mot de passe coursier avec le code reçu par SMS.
     *
     * @unauthenticated
     *
     * @bodyParam phone string required Numéro BF. Example: +22670123456
     * @bodyParam code string required Code SMS à 6 chiffres. Example: 123456
     * @bodyParam password string required Nouveau mot de passe. Example: password123
     * @bodyParam password_confirmation string required Confirmation du mot de passe. Example: password123
     */
    public function resetCourierPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'identifier' => ['nullable', 'string', 'max:255', 'required_without:phone'],
            'phone' => ['nullable', 'string', 'max:255', 'required_without:identifier'],
            'code' => ['required', 'string', 'digits:6'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
        ]);

        $identifier = $validated['identifier'] ?? $validated['phone'];

        $result = $this->courierPasswordResetService->reset(
            $identifier,
            $validated['code'],
            $validated['password'],
        );

        if (! $result['success']) {
            return $this->error($result['message'], 422);
        }

        return $this->success(null, $result['message']);
    }

    // ─────────────────────────────────────────────────────────────
    //  Inscription client (route publique — sans OTP)
    // ─────────────────────────────────────────────────────────────

    /**
     * Pré-inscrire un client
     *
     * Crée un compte client actif avec numéro, email et mot de passe.
     *
     * @unauthenticated
     *
     * @bodyParam phone string required Numéro BF. Example: +22670123456
     * @bodyParam name  string required Nom complet. Example: Abdoulaye Ouédraogo
     * @bodyParam email string required Email du client. Example: a@example.com
     * @bodyParam password string required Mot de passe du client. Example: password123
     *
     * @response 200 {"success": true, "message": "Compte créé. Connectez-vous avec votre numéro."}
     * @response 409 {"success": false, "message": "Ce numéro est déjà inscrit."}
     */
    public function registerClient(RegisterClientRequest $request): JsonResponse
    {
        $result = $this->authService->registerClient($request->validated());

        if (! $result['success']) {
            return $this->error($result['message'], 409);
        }

        return $this->success(null, 'Compte créé. Connectez-vous avec votre numéro et votre mot de passe.');
    }

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
     *
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

        if (! $result['success']) {
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
            $cdn = app(CdnService::class);
            if ($user->avatar) {
                $cdn->delete($cdn->pathFromUrl($user->avatar) ?: $user->avatar);
            }
            $url = $cdn->upload($request->file('avatar'), 'avatars');
            $validated['avatar'] = $url;
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
            'phone' => ['required', 'string', 'min:8', 'max:20'],
            'purpose' => ['nullable', 'string'],
        ]);

        $result = $this->authService->sendOtp(
            $validated['phone'],
            $validated['purpose'] ?? 'login',
            $request->ip(),
            $request->userAgent(),
        );

        if (! $result['success']) {
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
            'phone' => ['required', 'string', 'min:8', 'max:20'],
            'code' => ['required', 'string', 'digits:6'],
            'platform' => ['nullable', 'string'],
            'app_type' => ['nullable', 'string'],
        ]);

        $result = $this->authService->verifyOtp(
            $validated['phone'],
            $validated['code'],
            $validated['platform'] ?? 'mobile',
            $validated['app_type'] ?? null,
        );

        if (! $result['success']) {
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
                'user' => new UserResource($result['user']),
                'token' => $result['token'] ?? null,
                'expires_at' => $result['expires_at'] ?? null,
            ],
            $result['message'] ?? self::LOGIN_SUCCESS_MESSAGE,
        );
    }

    // ─────────────────────────────────────────────────────────────
    //  Profil courant
    // ─────────────────────────────────────────────────────────────

    public function me(Request $request): JsonResponse
    {
        return $this->withAuthSensitiveHeaders(
            $this->success(new UserResource($request->user()))
        );
    }

    // ─────────────────────────────────────────────────────────────
    //  Logout
    // ─────────────────────────────────────────────────────────────

    public function logout(Request $request): JsonResponse
    {
        $result = $this->authService->logout($request->user());

        return $this->withAuthSensitiveHeaders(
            $this->success(null, $result['message'])
        );
    }

    // ─────────────────────────────────────────────────────────────
    //  Refresh token
    // ─────────────────────────────────────────────────────────────

    public function refreshToken(Request $request): JsonResponse
    {
        $result = $this->authService->refreshToken($request->user());

        if (! $result['success']) {
            return $this->error($result['message'], 403);
        }

        $deviceAttestation = $this->buildCourierAttestationPayload($request, [
            'user' => $request->user(),
            'access_token_id' => $result['access_token_id'] ?? null,
        ]);

        return $this->withAuthSensitiveHeaders(
            $this->success(
                [
                    'token' => $result['token'],
                    'expires_at' => $result['expires_at'] ?? null,
                    'user' => new UserResource($request->user()->fresh()),
                    'device_attestation' => $deviceAttestation,
                ],
                'Token renouvelé.',
            )
        );
    }

    private function buildCourierAttestationPayload(Request $request, array $result): ?array
    {
        $user = $result['user'] ?? null;
        $accessTokenId = $result['access_token_id'] ?? null;

        $shouldSkip = ! $user instanceof \App\Models\User
            || ! is_int($accessTokenId)
            || $user->role !== UserRole::COURIER
            || ! $this->courierDeviceAttestationService->isEnforced();

        if ($shouldSkip) {
            return null;
        }

        $session = $this->courierDeviceAttestationService->issueChallengeForRequest(
            user: $user,
            personalAccessTokenId: $accessTokenId,
            request: $request,
        );

        return $session === null
            ? null
            : $this->courierDeviceAttestationService->toApiPayload($session);
    }
}

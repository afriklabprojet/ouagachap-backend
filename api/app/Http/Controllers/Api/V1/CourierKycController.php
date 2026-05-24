<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\KycStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Api\V1\BaseController;
use App\Services\CdnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourierKycController extends BaseController
{
    /**
     * Soumission des documents KYC par un coursier.
     * POST /api/v1/kyc/submit
     */
    public function submit(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== UserRole::COURIER) {
            return $this->error('Seuls les coursiers peuvent soumettre des documents KYC.', 403);
        }

        if ($user->kyc_status === KycStatus::APPROVED) {
            return $this->error('Vos documents sont déjà approuvés.', 422);
        }

        $validated = $request->validate([
            'identity_document' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'selfie'            => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:5120'],
        ]);

        $cdn        = app(CdnService::class);
        $idDocUrl   = $cdn->upload($validated['identity_document'], "kyc/{$user->id}/id");
        $selfieUrl  = $cdn->upload($validated['selfie'], "kyc/{$user->id}/selfie");

        $user->update([
            'identity_document_url' => $idDocUrl,
            'selfie_url'            => $selfieUrl,
            'kyc_status'            => KycStatus::PENDING,
            'documents_submitted_at' => now(),
            'kyc_rejection_reason'  => null,
        ]);

        return $this->success([
            'kyc_status'            => $user->kyc_status,
            'documents_submitted_at' => $user->documents_submitted_at,
        ], 'Vos documents ont été soumis et sont en cours de vérification.');
    }

    /**
     * Consulter le statut KYC du coursier connecté.
     * GET /api/v1/kyc/status
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->success([
            'kyc_status'             => $user->kyc_status,
            'kyc_status_label'       => $user->kyc_status->label(),
            'documents_submitted_at' => $user->documents_submitted_at,
            'documents_verified_at'  => $user->documents_verified_at,
            'kyc_rejection_reason'   => $user->kyc_rejection_reason,
        ]);
    }
}

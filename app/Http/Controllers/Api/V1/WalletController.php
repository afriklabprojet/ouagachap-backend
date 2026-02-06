<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Portefeuille Coursier
 *
 * APIs pour la gestion du portefeuille et des retraits des coursiers
 */
class WalletController extends Controller
{
    public function __construct(
        private WalletService $walletService
    ) {}

    /**
     * Mon portefeuille
     *
     * Récupère les informations du portefeuille du coursier connecté
     *
     * @authenticated
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "balance": "15000.00",
     *     "pending_balance": "2000.00",
     *     "total_earned": "50000.00",
     *     "total_withdrawn": "33000.00",
     *     "available_for_withdrawal": "15000.00",
     *     "pending_withdrawals_count": 1,
     *     "pending_withdrawals_amount": "2000.00"
     *   }
     * }
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'courier') {
            return response()->json([
                'success' => false,
                'message' => 'Cette fonctionnalité est réservée aux coursiers',
            ], 403);
        }

        $stats = $this->walletService->getWalletStats($user);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Demander un retrait
     *
     * Créer une demande de retrait de fonds
     *
     * @authenticated
     * @bodyParam amount numeric required Montant à retirer (min: 500 FCFA). Example: 5000
     * @bodyParam payment_method string required Méthode de paiement (mobile_money, bank_transfer). Example: mobile_money
     * @bodyParam phone string Numéro de téléphone Mobile Money. Example: 22670123456
     * @bodyParam provider string Opérateur (orange_money, moov_money). Example: orange_money
     * @bodyParam bank_name string Nom de la banque (si virement). Example: SGBF
     * @bodyParam bank_account string Numéro de compte bancaire. Example: BF001234567890
     *
     * @response 201 {
     *   "success": true,
     *   "message": "Demande de retrait créée avec succès",
     *   "data": {
     *     "id": 1,
     *     "amount": "5000.00",
     *     "status": "pending",
     *     "payment_method": "mobile_money",
     *     "payment_phone": "22670123456",
     *     "created_at": "2026-01-20T10:00:00Z"
     *   }
     * }
     */
    public function requestWithdrawal(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'courier') {
            return response()->json([
                'success' => false,
                'message' => 'Cette fonctionnalité est réservée aux coursiers',
            ], 403);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:500',
            'payment_method' => 'required|in:mobile_money,bank_transfer',
            'phone' => 'required_if:payment_method,mobile_money|string',
            'provider' => 'required_if:payment_method,mobile_money|in:orange_money,moov_money',
            'bank_name' => 'required_if:payment_method,bank_transfer|string',
            'bank_account' => 'required_if:payment_method,bank_transfer|string',
        ]);

        try {
            $withdrawal = $this->walletService->requestWithdrawal(
                $user,
                $validated['amount'],
                $validated['payment_method'],
                $validated
            );

            return response()->json([
                'success' => true,
                'message' => 'Demande de retrait créée avec succès',
                'data' => $withdrawal,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Historique des retraits
     *
     * Liste paginée des demandes de retrait du coursier
     *
     * @authenticated
     * @queryParam status string Filtrer par statut (pending, approved, completed, rejected). Example: pending
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "current_page": 1,
     *     "data": [
     *       {
     *         "id": 1,
     *         "amount": "5000.00",
     *         "status": "completed",
     *         "payment_method": "mobile_money",
     *         "transaction_reference": "TXN123456",
     *         "created_at": "2026-01-20T10:00:00Z",
     *         "completed_at": "2026-01-20T12:00:00Z"
     *       }
     *     ],
     *     "total": 10
     *   }
     * }
     */
    public function withdrawalHistory(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'courier') {
            return response()->json([
                'success' => false,
                'message' => 'Cette fonctionnalité est réservée aux coursiers',
            ], 403);
        }

        $status = $request->query('status');
        $withdrawals = $this->walletService->getWithdrawalHistory($user, $status);

        return response()->json([
            'success' => true,
            'data' => $withdrawals,
        ]);
    }

    /**
     * Annuler un retrait
     *
     * Annuler une demande de retrait en attente
     *
     * @authenticated
     * @urlParam withdrawal int required ID du retrait. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Demande de retrait annulée"
     * }
     */
    public function cancelWithdrawal(Request $request, int $withdrawalId): JsonResponse
    {
        $user = $request->user();

        $withdrawal = \App\Models\Withdrawal::where('id', $withdrawalId)
            ->where('user_id', $user->id)
            ->first();

        if (!$withdrawal) {
            return response()->json([
                'success' => false,
                'message' => 'Retrait non trouvé',
            ], 404);
        }

        if (!$withdrawal->isPending()) {
            return response()->json([
                'success' => false,
                'message' => 'Seuls les retraits en attente peuvent être annulés',
            ], 422);
        }

        // Annuler et rembourser
        $withdrawal->wallet->cancelWithdrawal($withdrawal->amount);
        $withdrawal->update(['status' => 'rejected', 'rejection_reason' => 'Annulé par le coursier']);

        return response()->json([
            'success' => true,
            'message' => 'Demande de retrait annulée',
        ]);
    }
}

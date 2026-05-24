<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\WithdrawalRequest;
use App\Models\SappayTransaction;
use App\Models\Withdrawal;
use App\Services\SappayService;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * @group Portefeuille Coursier
 *
 * APIs pour la gestion du portefeuille et des retraits des coursiers
 */
class WalletController extends BaseController
{
    public function __construct(
        private WalletService $walletService,
        private SappayService $sappayService,
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
        $wallet = $this->walletService->getOrCreateWallet($request->user());
        $this->authorize('view', $wallet);

        $stats = $this->walletService->getWalletStats($request->user());

        return $this->success($stats);
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
    public function requestWithdrawal(WithdrawalRequest $request): JsonResponse
    {
        $this->authorize('create', Withdrawal::class);

        $validated = $request->validated();

        try {
            $withdrawal = $this->walletService->requestWithdrawal(
                $request->user(),
                $validated['amount'],
                $validated['payment_method'],
                $validated
            );

            return $this->success($withdrawal, 'Demande de retrait créée avec succès', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
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
        $this->authorize('viewAny', Withdrawal::class);

        $status = $request->query('status');
        $withdrawals = $this->walletService->getWithdrawalHistory($request->user(), $status);

        return $this->success($withdrawals);
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
    public function cancelWithdrawal(Request $request, Withdrawal $withdrawal): JsonResponse
    {
        // Masquer l'existence du retrait si il n'appartient pas à l'utilisateur courant
        if ($withdrawal->user_id !== $request->user()->id) {
            return $this->error('Retrait non trouvé', 404);
        }

        if (!$withdrawal->isPending()) {
            return $this->error('Seuls les retraits en attente peuvent être annulés', 422);
        }

        // Annuler et rembourser de manière atomique
        DB::transaction(function () use ($withdrawal) {
            $withdrawal->wallet->cancelWithdrawal((float) $withdrawal->amount);
            $withdrawal->update(['status' => 'rejected', 'rejection_reason' => 'Annulé par le coursier']);
        });

        return $this->success(null, 'Demande de retrait annulée');
    }

    /**
     * Retrait direct Mobile Money (payout automatique)
     *
     * Débite immédiatement le wallet et déclenche une tentative de virement B2C.
     * Si l'opérateur n'est pas encore connecté, le retrait passe en file admin.
     *
     * @authenticated
     * @bodyParam amount integer required Montant (min: 500 FCFA). Example: 5000
     * @bodyParam provider string required Opérateur (orange_money, moov_money, telecel_money, coris_money). Example: orange_money
     * @bodyParam phone string required Numéro Mobile Money destinataire. Example: 22670123456
     *
     * @response 202 {
     *   "success": true,
     *   "message": "Retrait en cours de traitement",
     *   "data": {
     *     "id": 42,
     *     "amount": "5000.00",
     *     "status": "processing",
     *     "payment_provider": "orange_money",
     *     "payment_phone": "22670123456",
     *     "created_at": "2026-05-17T10:00:00Z"
     *   }
     * }
     */
    public function withdrawDirect(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount'   => 'required|numeric|min:500|max:500000',
            'provider' => 'required|in:orange_money,moov_money,telecel_money,coris_money',
            'phone'    => 'required|string|min:8|max:15',
        ]);

        $this->authorize('create', Withdrawal::class);

        try {
            $withdrawal = $this->walletService->initiateDirectWithdrawal(
                user:     $request->user(),
                amount:   (float) $validated['amount'],
                provider: $validated['provider'],
                phone:    $validated['phone'],
            );

            return $this->success($withdrawal, 'Retrait en cours de traitement', 202);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * Initier une recharge wallet via Sappay
     *
     * @authenticated
     * @bodyParam amount integer required Montant à recharger (min: 100 FCFA). Example: 1000
     * @bodyParam payment_method string required Opérateur (orange_money, moov_money, telecel_money, coris_money). Example: orange_money
     * @bodyParam phone string required Numéro Mobile Money. Example: 22670123456
     */
    public function initiateRecharge(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount'         => 'required|integer|min:100|max:500000',
            'payment_method' => 'required|in:orange_money,moov_money,telecel_money,coris_money',
            'phone'          => 'required|string|min:8|max:15',
        ]);

        $result = $this->sappayService->initiatePayment(
            user:            $request->user(),
            amountFcfa:      (int) $validated['amount'],
            paymentMethod:   $validated['payment_method'],
            customerMsisdn:  $validated['phone'],
            type:            'wallet_recharge',
        );

        if (!$result['success']) {
            return $this->error($result['message'], 422);
        }

        return $this->success($result['data'], $result['message']);
    }

    /**
     * Confirmer une recharge wallet via OTP Sappay
     *
     * @authenticated
     * @bodyParam transaction_id integer required ID de la transaction Sappay. Example: 42
     * @bodyParam otp string required Code OTP reçu par SMS. Example: 123456
     * @bodyParam trans_id string Identifiant de transaction opérateur (si requis). Example: TXN789
     */
    public function confirmRecharge(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'transaction_id' => 'required|integer',
            'otp'            => 'required|string|min:4|max:10',
            'trans_id'       => 'nullable|string',
        ]);

        $transaction = SappayTransaction::where('id', $validated['transaction_id'])
            ->where('user_id', $request->user()->id)
            ->where('type', 'wallet_recharge')
            ->first();

        if (!$transaction) {
            return $this->notFound('Transaction non trouvée');
        }

        if (!$transaction->isPending()) {
            return $this->error('Transaction déjà traitée', 422);
        }

        $result = $this->sappayService->confirmPayment(
            transaction: $transaction,
            otp:         $validated['otp'],
            transId:     $validated['trans_id'] ?? null,
        );

        if (!$result['success']) {
            return $this->error($result['message'], 422);
        }

        $wallet = $this->walletService->getOrCreateWallet($request->user()->fresh());

        return $this->success([
            'transaction_id' => $transaction->id,
            'status'         => 'success',
            'new_balance'    => (float) $wallet->balance,
        ], $result['message']);
    }
}

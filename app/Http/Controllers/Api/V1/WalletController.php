<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\WithdrawalRequest;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\Withdrawal;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * @group Portefeuille Coursier
 *
 * APIs pour la gestion du portefeuille et des retraits des coursiers
 */
class WalletController extends BaseController
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
     * Initier une recharge wallet coursier
     *
     * @authenticated
     * @bodyParam amount integer required Montant à recharger (min: 100 FCFA). Example: 1000
     * @bodyParam provider string required Opérateur (orange_money, moov_money, wave, telecel_money, coris_money). Example: orange_money
     * @bodyParam phone string required Numéro Mobile Money. Example: 70123456
     */
    public function initiateRecharge(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount'   => 'required|integer|min:100|max:500000',
            'provider' => 'required|in:orange_money,moov_money,wave,telecel_money,coris_money',
            'phone'    => 'required|string|min:8|max:15',
        ]);

        $user          = $request->user();
        $transactionId = 'RECH-COUR-' . strtoupper(Str::random(8));

        try {
            WalletTransaction::create([
                'user_id'        => $user->id,
                'transaction_id' => $transactionId,
                'amount'         => $validated['amount'],
                'type'           => 'recharge',
                'method'         => $validated['provider'],
                'phone_number'   => $validated['phone'],
                'status'         => 'pending',
            ]);

            return $this->success([
                'transaction_id' => $transactionId,
                'amount'         => (int) $validated['amount'],
                'provider'       => $validated['provider'],
                'phone'          => $validated['phone'],
                'status'         => 'pending',
                'instructions'   => $this->getProviderInstructions($validated['provider']),
            ], 'Recharge initiée avec succès');

        } catch (\Exception $e) {
            Log::error('Courier recharge initiation failed', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);

            return $this->error('Erreur lors de l\'initiation de la recharge', 500);
        }
    }

    /**
     * Confirmer une recharge wallet coursier
     *
     * @authenticated
     * @bodyParam transaction_id string required ID de la transaction. Example: RECH-COUR-ABC123
     */
    public function confirmRecharge(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'transaction_id' => 'required|string',
        ]);

        $transaction = WalletTransaction::where('transaction_id', $validated['transaction_id'])
            ->where('user_id', $request->user()->id)
            ->where('status', 'pending')
            ->first();

        if (!$transaction) {
            return $this->notFound('Transaction non trouvée ou déjà traitée');
        }

        try {
            DB::transaction(function () use ($transaction) {
                $transaction->update([
                    'status'       => 'success',
                    'completed_at' => now(),
                ]);

                $wallet = $this->walletService->getOrCreateWallet($transaction->user);
                $wallet->credit((float) $transaction->amount);
                $transaction->user->syncWalletBalance();
            });

            $wallet = $this->walletService->getOrCreateWallet($transaction->user->fresh());

            return $this->success([
                'new_balance' => (float) $wallet->balance,
            ], 'Recharge confirmée avec succès');

        } catch (\Exception $e) {
            Log::error('Courier recharge confirmation failed', [
                'transaction_id' => $validated['transaction_id'],
                'error'          => $e->getMessage(),
            ]);

            return $this->error('Erreur lors de la confirmation', 500);
        }
    }

    /**
     * Instructions selon le provider
     */
    private function getProviderInstructions(string $provider): string
    {
        return match ($provider) {
            'orange_money'  => 'Vous allez recevoir une demande Orange Money. Composez #144# ou ouvrez l\'app Orange Money pour confirmer.',
            'moov_money'    => 'Vous allez recevoir une demande Moov Money. Composez *555# ou ouvrez l\'app Moov Money pour confirmer.',
            'telecel_money' => 'Vous allez recevoir une demande Telecel Money. Ouvrez l\'app Telecel pour confirmer.',
            'coris_money'   => 'Vous allez recevoir une demande Coris Money. Ouvrez l\'app Coris pour confirmer.',
            'wave'          => 'Vous allez recevoir une demande Wave. Ouvrez l\'app Wave pour confirmer.',
            default         => 'Veuillez confirmer le paiement sur votre téléphone.',
        };
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\SappayTransaction;
use App\Models\WalletTransaction;
use App\Services\SappayService;
use App\Services\TransactionVelocityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Portefeuille Client
 *
 * APIs pour la gestion du portefeuille client (recharge)
 */
class ClientWalletController extends BaseController
{
    public function __construct(
        private SappayService $sappayService,
        private TransactionVelocityService $velocityService,
    ) {}

    /**
     * Mon solde
     *
     * @authenticated
     * @response 200 {"success": true, "data": {"balance": 5000, "currency": "FCFA"}}
     */
    public function balance(Request $request): JsonResponse
    {
        // fresh() bypasse le cache Auth middleware → solde toujours à jour
        $user = $request->user()->fresh();

        return $this->success([
            'id'       => $user->id,
            'balance'  => (int) $user->wallet_balance,
            'currency' => 'FCFA',
        ]);
    }

    /**
     * Initier une recharge via Sappay
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

        $velocityCheck = $this->velocityService->checkRechargeVelocity($request->user());
        if ($velocityCheck['blocked']) {
            return $this->error($velocityCheck['message'], 429);
        }

        $result = $this->sappayService->initiatePayment(
            user:           $request->user(),
            amountFcfa:     (int) $validated['amount'],
            paymentMethod:  $validated['payment_method'],
            customerMsisdn: $validated['phone'],
            type:           'wallet_recharge',
        );

        if (!$result['success']) {
            return $this->error($result['message'], 422);
        }

        return $this->success($result['data'], $result['message']);
    }

    /**
     * Confirmer une recharge via OTP Sappay
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

        $this->velocityService->recordRecharge($request->user());

        $user = $request->user()->fresh();

        return $this->success([
            'transaction_id' => $transaction->id,
            'status'         => 'success',
            'new_balance'    => (int) $user->wallet_balance,
        ], $result['message']);
    }

    /**
     * Historique des recharges
     *
     * @authenticated
     */
    public function history(Request $request): JsonResponse
    {
        $transactions = WalletTransaction::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(fn ($t) => [
                'id'             => $t->id,
                'transaction_id' => $t->transaction_id,
                'amount'         => (int) $t->amount,
                'type'           => $t->type,
                'provider'       => $t->method,
                'status'         => $t->status,
                'created_at'     => $t->created_at->toISOString(),
            ]);

        return $this->success($transactions);
    }
}

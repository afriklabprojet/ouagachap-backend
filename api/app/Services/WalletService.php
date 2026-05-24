<?php

namespace App\Services;

use App\Exceptions\Domain\InsufficientBalanceException;
use App\Exceptions\Domain\InvalidWithdrawalStateException;
use App\Jobs\ProcessWithdrawalJob;
use App\Models\Order;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\DB;

class WalletService
{
    /**
     * Obtenir ou créer le portefeuille d'un utilisateur
     */
    public function getOrCreateWallet(User $user): Wallet
    {
        return DB::transaction(function () use ($user) {
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();
            $legacyBalance = max(0, (float) $user->wallet_balance);

            if ($wallet === null) {
                return Wallet::create([
                    'user_id' => $user->id,
                    'balance' => $legacyBalance,
                    'pending_balance' => 0,
                    'total_earned' => $legacyBalance,
                    'total_withdrawn' => 0,
                ]);
            }

            if ($this->canHydrateLegacyBalance($wallet, $legacyBalance)) {
                $wallet->forceFill([
                    'balance' => $legacyBalance,
                    'total_earned' => $legacyBalance,
                ])->save();
            }

            return $wallet->refresh();
        });
    }

    private function canHydrateLegacyBalance(Wallet $wallet, float $legacyBalance): bool
    {
        return $legacyBalance > 0
            && (float) $wallet->balance === 0.0
            && (float) $wallet->pending_balance === 0.0
            && (float) $wallet->total_earned === 0.0
            && (float) $wallet->total_withdrawn === 0.0;
    }

    /**
     * Créditer le portefeuille d'un coursier après livraison
     */
    public function creditCourierForDelivery(User $courier, float $deliveryFee, ?Order $order = null): Wallet
    {
        $wallet = $this->getOrCreateWallet($courier);
        $wallet->credit($deliveryFee);

        if ($order !== null) {
            $method = in_array($order->payment_method, ['orange_money', 'moov_money', 'cash', 'bank_transfer', 'wave', 'mtn_money', 'djamo'], true)
                ? $order->payment_method
                : 'cash';

            WalletTransaction::create([
                'user_id' => $courier->id,
                'amount' => $deliveryFee,
                'type' => 'delivery_earning',
                'method' => $method,
                'status' => 'success',
                'provider_transaction_id' => $order->id,
                'provider_response' => json_encode([
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'total_price' => (float) $order->total_price,
                    'commission_amount' => (float) $order->commission_amount,
                    'courier_earnings' => $deliveryFee,
                ]),
                'completed_at' => now(),
            ]);
        }

        // Synchroniser le cache User.wallet_balance
        $courier->syncWalletBalance();

        return $wallet;
    }

    /**
     * Demander un retrait
     */
    public function requestWithdrawal(
        User $user,
        float $amount,
        string $paymentMethod = 'mobile_money',
        array $paymentDetails = []
    ): Withdrawal {
        $wallet = $this->getOrCreateWallet($user);

        if ($amount < 500) {
            throw InsufficientBalanceException::belowMinimum($amount, 500);
        }

        if ($wallet->available_balance < $amount) {
            throw InsufficientBalanceException::forWithdrawal(
                (float) $wallet->available_balance,
                $amount
            );
        }

        return DB::transaction(function () use ($wallet, $user, $amount, $paymentMethod, $paymentDetails) {
            // Débiter le portefeuille (montant en attente)
            $wallet->debit($amount);

            // Synchroniser le cache User.wallet_balance
            $user->syncWalletBalance();

            // Créer la demande de retrait
            return Withdrawal::create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'amount' => $amount,
                'status' => 'pending',
                'payment_method' => $paymentMethod,
                'payment_phone' => $paymentDetails['phone'] ?? null,
                'payment_provider' => $paymentDetails['provider'] ?? null,
                'bank_name' => $paymentDetails['bank_name'] ?? null,
                'bank_account' => $paymentDetails['bank_account'] ?? null,
            ]);
        });
    }

    /**
     * Approuver un retrait (admin)
     */
    public function approveWithdrawal(Withdrawal $withdrawal, User $admin): void
    {
        if (! $withdrawal->isPending()) {
            throw InvalidWithdrawalStateException::cannotApprove();
        }

        $withdrawal->approve($admin->id);
    }

    /**
     * Rejeter un retrait (admin)
     */
    public function rejectWithdrawal(Withdrawal $withdrawal, string $reason, User $admin): void
    {
        if (! $withdrawal->isPending()) {
            throw InvalidWithdrawalStateException::cannotReject();
        }

        $withdrawal->reject($reason, $admin->id);
    }

    /**
     * Marquer un retrait comme complété (après paiement effectif)
     */
    public function completeWithdrawal(Withdrawal $withdrawal, string $transactionReference): void
    {
        if (! $withdrawal->isApproved()) {
            throw InvalidWithdrawalStateException::mustBeApprovedBeforeCompletion();
        }

        $withdrawal->complete($transactionReference);
    }

    /**
     * Obtenir l'historique des retraits
     */
    public function getWithdrawalHistory(User $user, ?string $status = null)
    {
        $query = Withdrawal::where('user_id', $user->id)
            ->orderBy('created_at', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        return $query->paginate(15);
    }

    /**
     * Initier un retrait direct avec tentative de payout automatique.
     *
     * Le montant est débité immédiatement. ProcessWithdrawalJob tente le
     * virement B2C ; en cas d'échec il notifie l'admin pour traitement manuel.
     */
    public function initiateDirectWithdrawal(
        User $user,
        float $amount,
        string $provider,
        string $phone,
    ): Withdrawal {
        $wallet = $this->getOrCreateWallet($user);

        if ($amount < 500) {
            throw InsufficientBalanceException::belowMinimum($amount, 500);
        }

        if ($wallet->available_balance < $amount) {
            throw InsufficientBalanceException::forWithdrawal(
                (float) $wallet->available_balance,
                $amount
            );
        }

        $withdrawal = DB::transaction(function () use ($wallet, $user, $amount, $provider, $phone) {
            $wallet->debit($amount);
            $user->syncWalletBalance();

            return Withdrawal::create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'amount' => $amount,
                'status' => 'processing',
                'payment_method' => 'mobile_money',
                'payment_phone' => $phone,
                'payment_provider' => $provider,
            ]);
        });

        // Dispatch async — 3 retries with exponential back-off
        ProcessWithdrawalJob::dispatch($withdrawal->id)
            ->onQueue('payouts')
            ->delay(now()->addSeconds(5));

        return $withdrawal;
    }

    /**
     * Statistiques du portefeuille
     */
    public function getWalletStats(User $user): array
    {
        $wallet = $this->getOrCreateWallet($user);

        $pendingWithdrawals = Withdrawal::where('user_id', $user->id)
            ->pending()
            ->sum('amount');

        return [
            'balance' => $wallet->balance,
            'pending_balance' => $wallet->pending_balance,
            'total_earned' => $wallet->total_earned,
            'total_withdrawn' => $wallet->total_withdrawn,
            'available_for_withdrawal' => $wallet->available_balance,
            'pending_withdrawals_count' => Withdrawal::where('user_id', $user->id)->pending()->count(),
            'pending_withdrawals_amount' => $pendingWithdrawals,
        ];
    }
}

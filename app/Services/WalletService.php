<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\DB;

class WalletService
{
    /**
     * Obtenir ou créer le portefeuille d'un utilisateur
     */
    public function getOrCreateWallet(User $user): Wallet
    {
        return Wallet::firstOrCreate(
            ['user_id' => $user->id],
            [
                'balance' => 0,
                'pending_balance' => 0,
                'total_earned' => 0,
                'total_withdrawn' => 0,
            ]
        );
    }

    /**
     * Créditer le portefeuille d'un coursier après livraison
     */
    public function creditCourierForDelivery(User $courier, float $deliveryFee): Wallet
    {
        $wallet = $this->getOrCreateWallet($courier);
        $wallet->credit($deliveryFee);

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

        if ($wallet->available_balance < $amount) {
            throw new \Exception('Solde insuffisant pour ce retrait');
        }

        if ($amount < 500) {
            throw new \Exception('Le montant minimum de retrait est de 500 FCFA');
        }

        return DB::transaction(function () use ($wallet, $user, $amount, $paymentMethod, $paymentDetails) {
            // Débiter le portefeuille (montant en attente)
            $wallet->debit($amount);

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
        if (!$withdrawal->isPending()) {
            throw new \Exception('Ce retrait ne peut pas être approuvé');
        }

        $withdrawal->approve($admin->id);
    }

    /**
     * Rejeter un retrait (admin)
     */
    public function rejectWithdrawal(Withdrawal $withdrawal, string $reason, User $admin): void
    {
        if (!$withdrawal->isPending()) {
            throw new \Exception('Ce retrait ne peut pas être rejeté');
        }

        $withdrawal->reject($reason, $admin->id);
    }

    /**
     * Marquer un retrait comme complété (après paiement effectif)
     */
    public function completeWithdrawal(Withdrawal $withdrawal, string $transactionReference): void
    {
        if (!$withdrawal->isApproved()) {
            throw new \Exception('Ce retrait doit être approuvé avant d\'être complété');
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

<?php

namespace App\Observers;

use App\Models\Wallet;
use Illuminate\Support\Facades\Log;

class WalletObserver
{
    /**
     * Sync users.wallet_balance whenever a Wallet balance changes.
     *
     * Ensures the denormalized balance on the User model stays consistent
     * regardless of which code path mutates the Wallet (credit, debit,
     * withdrawal, direct update, etc.).
     */
    public function updated(Wallet $wallet): void
    {
        if (! $wallet->wasChanged('balance')) {
            return;
        }

        $user = $wallet->user;

        if ($user === null) {
            Log::warning('WalletObserver: wallet #' . $wallet->id . ' has no associated user — skipping sync.');
            return;
        }

        $user->syncWalletBalance();
    }
}

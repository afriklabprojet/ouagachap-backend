<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncWalletBalances extends Command
{
    protected $signature = 'wallet:sync-balances {--dry-run : Afficher les modifications sans les appliquer}';

    protected $description = 'Synchroniser User.wallet_balance avec le solde du Wallet (source de vérité)';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $this->info($dryRun ? '[DRY RUN] Vérification des désynchronisations...' : 'Synchronisation des soldes...');

        $wallets = Wallet::with('user')->get();
        $synced = 0;
        $skipped = 0;

        foreach ($wallets as $wallet) {
            $user = $wallet->user;

            if (!$user) {
                $this->warn("Wallet #{$wallet->id} sans utilisateur (user_id: {$wallet->user_id})");
                continue;
            }

            $currentCache = (float) $user->wallet_balance;
            $realBalance = (float) $wallet->balance;

            if (abs($currentCache - $realBalance) > 0.01) {
                if ($dryRun) {
                    $this->line(
                        "  User #{$user->id} ({$user->phone}): cache={$currentCache} FCFA → réel={$realBalance} FCFA (diff: " .
                        round($realBalance - $currentCache, 2) . " FCFA)"
                    );
                } else {
                    // Lock both rows to prevent a concurrent withdrawal reading stale cache
                    DB::transaction(function () use ($user, $wallet, $realBalance) {
                        Wallet::lockForUpdate()->find($wallet->id);
                        User::lockForUpdate()->find($user->id);
                        $user->update(['wallet_balance' => $realBalance]);
                    });
                    $this->line("  User #{$user->id} ({$user->phone}): {$currentCache} → {$realBalance} FCFA");
                }
                $synced++;
            } else {
                $skipped++;
            }
        }

        // Also create wallets for users who don't have one
        $usersWithoutWallet = User::whereDoesntHave('wallet')
            ->where('wallet_balance', '>', 0)
            ->get();

        foreach ($usersWithoutWallet as $user) {
            if ($dryRun) {
                $this->warn("  User #{$user->id} ({$user->phone}): wallet_balance={$user->wallet_balance} FCFA mais PAS de Wallet model");
            } else {
                DB::transaction(function () use ($user) {
                    Wallet::create([
                        'user_id'         => $user->id,
                        'balance'         => $user->wallet_balance,
                        'pending_balance' => 0,
                        'total_earned'    => $user->wallet_balance,
                        'total_withdrawn' => 0,
                    ]);
                });
                $this->line("  User #{$user->id}: Wallet créé avec solde {$user->wallet_balance} FCFA");
            }
            $synced++;
        }

        $this->newLine();
        $action = $dryRun ? 'à synchroniser' : 'synchronisés';
        $this->info("Résultat: {$synced} {$action}, {$skipped} déjà en sync.");

        if ($dryRun && $synced > 0) {
            $this->comment("Relancez sans --dry-run pour appliquer les modifications.");
        }

        return Command::SUCCESS;
    }
}

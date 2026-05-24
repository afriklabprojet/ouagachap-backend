<?php

namespace App\Console\Commands;

use App\Models\Zone;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migration one-time : ajouter commission_rate aux zones + initialiser à 0.15.
 * Anciennement accessible via curl https://ouagachap.com/scripts/add_commission.php
 * Usage : php artisan zones:add-commission
 */
class AddCommissionToZones extends Command
{
    protected $signature = 'zones:add-commission';
    protected $description = 'Ajouter la colonne commission_rate aux zones et initialiser à 15%';

    public function handle(): int
    {
        // 1. Add commission_rate column if not exists (DDL outside transaction — MySQL auto-commits DDL)
        if (!Schema::hasColumn('zones', 'commission_rate')) {
            try {
                DB::statement("ALTER TABLE zones ADD COLUMN commission_rate DECIMAL(5,2) DEFAULT 0.15 AFTER price_per_km");
                $this->info("✅ Colonne 'commission_rate' ajoutée à la table zones");
            } catch (\Throwable $e) {
                $this->error("❌ Impossible d'ajouter la colonne : " . $e->getMessage());
                return Command::FAILURE;
            }
        } else {
            $this->line("⏭️ Colonne 'commission_rate' existe déjà");
        }

        // 2. Set commission_rate = 0.15 for all zones that have NULL (wrapped in transaction)
        try {
            $updated = DB::transaction(fn () =>
                DB::table('zones')
                    ->whereNull('commission_rate')
                    ->update(['commission_rate' => 0.15])
            );
        } catch (\Throwable $e) {
            $this->error("❌ Mise à jour des zones échouée : " . $e->getMessage());
            return Command::FAILURE;
        }
        $this->info("✅ {$updated} zone(s) mises à jour avec commission_rate = 0.15 (15%)");

        // 3. Clear cache
        Cache::forget('zones:active');
        Cache::forget('config:general');
        $this->info('✅ Cache vidé');

        // 4. List results
        $this->newLine();
        $this->table(
            ['Zone', 'Base (FCFA)', 'Par km (FCFA)', 'Commission'],
            Zone::withTrashed(false)->get()->map(fn($z) => [
                $z->name,
                $z->base_price,
                $z->price_per_km,
                ($z->commission_rate * 100) . '%',
            ])
        );

        $this->info('✅ Terminé ! La commission est maintenant modifiable dans Admin → Zones.');

        return Command::SUCCESS;
    }
}

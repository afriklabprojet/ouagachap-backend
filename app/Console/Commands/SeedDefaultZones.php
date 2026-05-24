<?php

namespace App\Console\Commands;

use App\Models\Zone;
use Illuminate\Console\Command;

/**
 * Seeder des zones de livraison par défaut pour Ouagadougou.
 * Anciennement accessible via curl https://ouagachap.com/scripts/seed_zones.php
 * Usage : php artisan zones:seed-default
 */
class SeedDefaultZones extends Command
{
    protected $signature = 'zones:seed-default {--force : Écraser les zones existantes}';
    protected $description = 'Créer les zones de livraison par défaut pour Ouagadougou';

    private function defaultZones(): array
    {
        $commission = (float) config('app.commission_rate', 0.15);

        return [
            [
                'name'            => 'Centre-ville',
                'code'            => 'centre',
                'description'     => "Quartiers centraux : Koulouba, Paspanga, Bilbalgo, Larlé, Zone du Bois",
                'base_price'      => 500,
                'price_per_km'    => 150,
                'commission_rate' => $commission,
                'is_active'       => true,
            ],
            [
                'name'            => 'Ouaga Standard',
                'code'            => 'standard',
                'description'     => "Zone standard : Patte d'oie, Tampouy, Somgandé, Karpala, Dassasgho, Cissin",
                'base_price'      => 500,
                'price_per_km'    => 200,
                'commission_rate' => $commission,
                'is_active'       => true,
            ],
            [
                'name'            => 'Ouaga 2000 & Périphérie',
                'code'            => 'peripherie',
                'description'     => 'Zones éloignées : Ouaga 2000, Bassinko, Kilwin, Saaba, Koubri, Tabtenga',
                'base_price'      => 750,
                'price_per_km'    => 250,
                'commission_rate' => $commission,
                'is_active'       => true,
            ],
        ];
    }

    public function handle(): int
    {
        $force   = $this->option('force');
        $created = 0;
        $skipped = 0;
        $zones   = $this->defaultZones();

        foreach ($zones as $zoneData) {
            $existing = Zone::where('code', $zoneData['code'])->first();

            if ($existing && !$force) {
                $this->line("⏭️  Zone '{$zoneData['name']}' existe déjà (--force pour écraser)");
                $skipped++;
                continue;
            }

            if ($existing && $force) {
                $existing->update($zoneData);
                $this->info("🔄 Zone '{$zoneData['name']}' mise à jour");
            } else {
                Zone::create($zoneData);
                $this->info("✅ Zone '{$zoneData['name']}' créée (base: {$zoneData['base_price']} FCFA, {$zoneData['price_per_km']} FCFA/km)");
                $created++;
            }
        }

        $this->newLine();
        $this->info("📊 Terminé : {$created} zone(s) créée(s), {$skipped} ignorée(s)");

        $this->newLine();
        $this->table(
            ['Zone', 'Code', 'Base (FCFA)', 'Par km', 'Exemple 5km', 'Actif'],
            collect($zones)->map(fn($z) => [
                $z['name'],
                $z['code'],
                $z['base_price'],
                $z['price_per_km'],
                $z['base_price'] + ($z['price_per_km'] * 5),
                'Oui',
            ])
        );

        return Command::SUCCESS;
    }
}

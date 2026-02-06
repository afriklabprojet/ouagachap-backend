<?php

namespace Database\Seeders;

use App\Models\Zone;
use Illuminate\Database\Seeder;

class TestZonesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🗺️ Création des zones de livraison...');
        $this->command->newLine();

        $zones = [
            [
                'name' => 'Centre-ville',
                'code' => 'CENTRE',
                'description' => 'Zone centrale de Ouagadougou incluant les secteurs 1 à 5',
                'base_price' => 500,
                'price_per_km' => 150,
                'is_active' => true,
            ],
            [
                'name' => 'Ouaga 2000',
                'code' => 'OUAGA2000',
                'description' => 'Quartier résidentiel et diplomatique',
                'base_price' => 600,
                'price_per_km' => 175,
                'is_active' => true,
            ],
            [
                'name' => 'Zone Industrielle',
                'code' => 'KOSSODO',
                'description' => 'Zone industrielle de Kossodo',
                'base_price' => 700,
                'price_per_km' => 200,
                'is_active' => true,
            ],
            [
                'name' => 'Dassasgho',
                'code' => 'DASSAS',
                'description' => 'Quartier résidentiel au nord-est',
                'base_price' => 550,
                'price_per_km' => 165,
                'is_active' => true,
            ],
            [
                'name' => 'Cité An III',
                'code' => 'AN3',
                'description' => 'Secteur résidentiel populaire',
                'base_price' => 500,
                'price_per_km' => 150,
                'is_active' => true,
            ],
            [
                'name' => 'Zone du Bois',
                'code' => 'BOIS',
                'description' => 'Quartier commercial et artisanal',
                'base_price' => 550,
                'price_per_km' => 160,
                'is_active' => true,
            ],
            [
                'name' => 'Wemtenga',
                'code' => 'WEMTENGA',
                'description' => 'Quartier résidentiel Est',
                'base_price' => 550,
                'price_per_km' => 160,
                'is_active' => true,
            ],
            [
                'name' => 'Périphérie Nord',
                'code' => 'NORD',
                'description' => 'Zone périurbaine nord (tarif majoré)',
                'base_price' => 800,
                'price_per_km' => 250,
                'is_active' => true,
            ],
            [
                'name' => 'Périphérie Sud',
                'code' => 'SUD',
                'description' => 'Zone périurbaine sud (tarif majoré)',
                'base_price' => 800,
                'price_per_km' => 250,
                'is_active' => false, // Zone inactive pour test
            ],
        ];

        foreach ($zones as $zoneData) {
            $zone = Zone::updateOrCreate(
                ['code' => $zoneData['code']],
                $zoneData
            );

            $status = $zone->is_active ? '✅' : '❌';
            $this->command->line("  {$status} {$zone->name} ({$zone->code}) - Base: {$zone->base_price} FCFA, Par km: {$zone->price_per_km} FCFA");
        }

        $this->command->newLine();
        $this->displaySummary();
    }

    private function displaySummary(): void
    {
        $total = Zone::count();
        $active = Zone::where('is_active', true)->count();
        $avgBasePrice = Zone::where('is_active', true)->avg('base_price');
        $avgPricePerKm = Zone::where('is_active', true)->avg('price_per_km');

        $this->command->info('🗺️ Résumé des zones:');
        $this->command->table(
            ['Métrique', 'Valeur'],
            [
                ['Total zones', $total],
                ['Zones actives', $active],
                ['Prix de base moyen', number_format($avgBasePrice, 0) . ' FCFA'],
                ['Prix/km moyen', number_format($avgPricePerKm, 0) . ' FCFA'],
            ]
        );
    }
}

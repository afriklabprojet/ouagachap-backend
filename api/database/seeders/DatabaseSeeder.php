<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Usage:
     *   php artisan db:seed                    # Données minimales (prod-ready)
     *   php artisan db:seed --class=TestDataSeeder  # Données de test complètes
     */
    public function run(): void
    {
        // Créer les rôles et permissions d'abord
        $this->call(RolesAndPermissionsSeeder::class);
        
        // Créer les admins via le seeder dédié
        $this->call(AdminSeeder::class);
        
        // Créer les FAQs
        $this->call(FaqSeeder::class);

        // Create test client
        User::create([
            'phone' => '70100001',
            'name' => 'Aminata Ouédraogo',
            'role' => UserRole::CLIENT,
            'status' => UserStatus::ACTIVE,
        ]);

        // Create test courier
        User::create([
            'phone' => '70200001',
            'name' => 'Moussa Sawadogo',
            'role' => UserRole::COURIER,
            'status' => UserStatus::ACTIVE,
            'vehicle_type' => 'moto',
            'vehicle_plate' => 'BF-1234-AB',
            'vehicle_model' => 'Honda CG 125',
            'is_available' => true,
            'current_latitude' => 12.3714,
            'current_longitude' => -1.5197,
        ]);

        // Create zones
        Zone::create([
            'name' => 'Centre-ville',
            'code' => 'CENTRE',
            'description' => 'Quartiers du centre de Ouagadougou',
            'base_price' => 500,
            'price_per_km' => 200,
            'is_active' => true,
        ]);

        Zone::create([
            'name' => 'Périphérie',
            'code' => 'PERIPHERIE',
            'description' => 'Quartiers périphériques',
            'base_price' => 600,
            'price_per_km' => 250,
            'is_active' => true,
        ]);

        Zone::create([
            'name' => 'Ouaga 2000',
            'code' => 'OUAGA2000',
            'description' => 'Zone Ouaga 2000',
            'base_price' => 700,
            'price_per_km' => 200,
            'is_active' => true,
        ]);
    }
}

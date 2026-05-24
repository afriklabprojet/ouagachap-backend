<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TestOrderSeeder extends Seeder
{
    /**
     * Crée des commandes de test pour tester l'app coursier.
     * 
     * Usage: php artisan db:seed --class=TestOrderSeeder
     */
    public function run(): void
    {
        // Récupérer le client et le coursier de test
        $client = User::where('role', UserRole::CLIENT)->first();
        $courier = User::where('role', UserRole::COURIER)->first();
        $zone = Zone::first();

        if (!$client || !$courier || !$zone) {
            $this->command->error('❌ Veuillez d\'abord exécuter: php artisan db:seed');
            return;
        }

        // Générer un suffixe unique basé sur l'heure
        $suffix = now()->format('His');

        // Commande 1: Assignée au coursier (prête à être récupérée)
        $order1 = Order::create([
            'id' => Str::uuid(),
            'order_number' => 'OC-' . now()->format('Ymd') . '-' . $suffix . 'A',
            'client_id' => $client->id,
            'courier_id' => $courier->id,
            'zone_id' => $zone->id,
            'status' => OrderStatus::ASSIGNED,
            
            // Point de récupération - Marché Rood Woko
            'pickup_address' => 'Marché Rood Woko, Secteur 4, Ouagadougou',
            'pickup_latitude' => 12.3686,
            'pickup_longitude' => -1.5275,
            'pickup_contact_name' => 'Fatou Compaoré',
            'pickup_contact_phone' => '+22670123456',
            'pickup_instructions' => 'Boutique N°45, demander Fatou',
            
            // Point de livraison - Ouaga 2000
            'dropoff_address' => 'Villa 123, Cité AN III, Ouaga 2000',
            'dropoff_latitude' => 12.3421,
            'dropoff_longitude' => -1.4892,
            'dropoff_contact_name' => 'Ibrahim Traoré',
            'dropoff_contact_phone' => '+22676543210',
            'dropoff_instructions' => 'Sonner au portail vert, 2ème villa à gauche',
            
            'package_description' => 'Colis contenant des vêtements (2 kg)',
            'package_size' => 'medium',
            
            'distance_km' => 4.5,
            'base_price' => 500,
            'distance_price' => 900,
            'total_price' => 1400,
            'commission_amount' => 210, // 15%
            'courier_earnings' => 1190,
            
            'recipient_confirmation_code' => '1234',
            'assigned_at' => now(),
        ]);

        $this->command->info("✅ Commande 1 créée: {$order1->order_number} (Assignée)");

        // Commande 2: En attente (pas encore assignée)
        $order2 = Order::create([
            'id' => Str::uuid(),
            'order_number' => 'OC-' . now()->format('Ymd') . '-' . $suffix . 'B',
            'client_id' => $client->id,
            'courier_id' => null, // Pas encore assignée
            'zone_id' => $zone->id,
            'status' => OrderStatus::PENDING,
            
            // Point de récupération - Zone du Bois
            'pickup_address' => 'Restaurant Le Verdoyant, Zone du Bois',
            'pickup_latitude' => 12.3750,
            'pickup_longitude' => -1.5150,
            'pickup_contact_name' => 'Chef Abdoulaye',
            'pickup_contact_phone' => '+22678901234',
            'pickup_instructions' => 'Commande prête à la caisse',
            
            // Point de livraison - Koulouba
            'dropoff_address' => 'Résidence Koulouba, Porte 7',
            'dropoff_latitude' => 12.3800,
            'dropoff_longitude' => -1.5050,
            'dropoff_contact_name' => 'Madame Sana',
            'dropoff_contact_phone' => '+22665432109',
            'dropoff_instructions' => 'Appeler en arrivant',
            
            'package_description' => 'Repas chaud - À livrer rapidement',
            'package_size' => 'small',
            
            'distance_km' => 2.8,
            'base_price' => 500,
            'distance_price' => 560,
            'total_price' => 1060,
            'commission_amount' => 159,
            'courier_earnings' => 901,
            
            'recipient_confirmation_code' => '5678',
        ]);

        $this->command->info("✅ Commande 2 créée: {$order2->order_number} (En attente)");

        // Commande 3: Déjà récupérée (en cours de livraison)
        $order3 = Order::create([
            'id' => Str::uuid(),
            'order_number' => 'OC-' . now()->format('Ymd') . '-' . $suffix . 'C',
            'client_id' => $client->id,
            'courier_id' => $courier->id,
            'zone_id' => $zone->id,
            'status' => OrderStatus::PICKED_UP,
            
            // Point de récupération - Pharmacie
            'pickup_address' => 'Pharmacie Centrale, Avenue Kwame Nkrumah',
            'pickup_latitude' => 12.3700,
            'pickup_longitude' => -1.5200,
            'pickup_contact_name' => 'Dr. Kaboré',
            'pickup_contact_phone' => '+22670111222',
            'pickup_instructions' => 'Ordonnance N°456',
            
            // Point de livraison - 1200 Logements
            'dropoff_address' => 'Cité 1200 Logements, Bâtiment C, Apt 12',
            'dropoff_latitude' => 12.3550,
            'dropoff_longitude' => -1.5350,
            'dropoff_contact_name' => 'Rasmata Ouédraogo',
            'dropoff_contact_phone' => '+22679876543',
            'dropoff_instructions' => 'Bâtiment C au fond à droite',
            
            'package_description' => 'Médicaments urgents',
            'package_size' => 'small',
            
            'distance_km' => 3.2,
            'base_price' => 500,
            'distance_price' => 640,
            'total_price' => 1140,
            'commission_amount' => 171,
            'courier_earnings' => 969,
            
            'recipient_confirmation_code' => '9012',
            'assigned_at' => now()->subMinutes(20),
            'picked_up_at' => now()->subMinutes(10),
        ]);

        $this->command->info("✅ Commande 3 créée: {$order3->order_number} (En cours de livraison)");

        $this->command->newLine();
        $this->command->info('📦 3 commandes de test créées avec succès !');
        $this->command->newLine();
        $this->command->info('� Codes de confirmation (le CLIENT donne ce code au COURSIER):');
        $this->command->info('   Commande 1: 1234');
        $this->command->info('   Commande 2: 5678');
        $this->command->info('   Commande 3: 9012');
        $this->command->newLine();
        $this->command->info('📱 Pour tester dans l\'app coursier:');
        $this->command->info('   Téléphone: 70200001');
        $this->command->info('   OTP: 123456');
        $this->command->newLine();
        $this->command->info('📱 Pour tester dans l\'app client:');
        $this->command->info('   Téléphone: 70100001');
        $this->command->info('   OTP: 123456');
    }
}

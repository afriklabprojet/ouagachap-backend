<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TestOrdersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer un client test s'il n'existe pas
        $client = User::firstOrCreate(
            ['phone' => '70111111'],
            [
                'name' => 'Client Test',
                'role' => 'client',
                'status' => 'active',
            ]
        );

        // Commandes de test à Ouagadougou
        $orders = [
            [
                'pickup_address' => 'Restaurant Le Délice, Avenue Kwame Nkrumah, Ouaga 2000',
                'pickup_latitude' => 12.3569,
                'pickup_longitude' => -1.5149,
                'pickup_contact_name' => 'Chef Amadou',
                'pickup_contact_phone' => '+22670111222',
                'dropoff_address' => 'Résidence Palm Beach, Cité An III, Secteur 30',
                'dropoff_latitude' => 12.3789,
                'dropoff_longitude' => -1.5234,
                'dropoff_contact_name' => 'Mme Ouédraogo',
                'dropoff_contact_phone' => '+22670333444',
                'package_description' => 'Repas: 2 poulets braisés + accompagnements',
                'package_size' => 'medium',
                'distance_km' => 3.2,
                'total_price' => 1500,
            ],
            [
                'pickup_address' => 'Pharmacie du Centre, Rue de la Chance, Secteur 4',
                'pickup_latitude' => 12.3672,
                'pickup_longitude' => -1.5245,
                'pickup_contact_name' => 'Dr Kaboré',
                'pickup_contact_phone' => '+22670222333',
                'dropoff_address' => 'Quartier Wemtenga, près de l\'école primaire',
                'dropoff_latitude' => 12.3512,
                'dropoff_longitude' => -1.4989,
                'dropoff_contact_name' => 'Ibrahim Sanou',
                'dropoff_contact_phone' => '+22670444555',
                'package_description' => 'Médicaments urgents',
                'package_size' => 'small',
                'distance_km' => 2.8,
                'total_price' => 1000,
            ],
            [
                'pickup_address' => 'Supermarché Marina Market, Ouaga 2000',
                'pickup_latitude' => 12.3598,
                'pickup_longitude' => -1.5078,
                'pickup_contact_name' => 'Caissier Marina',
                'pickup_contact_phone' => '+22670555666',
                'dropoff_address' => 'Villa 245, Secteur 15, Dassasgho',
                'dropoff_latitude' => 12.3856,
                'dropoff_longitude' => -1.4823,
                'dropoff_contact_name' => 'Fatou Compaoré',
                'dropoff_contact_phone' => '+22670666777',
                'package_description' => 'Courses: fruits, légumes, produits frais',
                'package_size' => 'large',
                'distance_km' => 4.5,
                'total_price' => 2000,
            ],
            [
                'pickup_address' => 'Boutique Africaine Mode, Avenue de l\'Indépendance',
                'pickup_latitude' => 12.3645,
                'pickup_longitude' => -1.5312,
                'pickup_contact_name' => 'Awa Diallo',
                'pickup_contact_phone' => '+22670777888',
                'dropoff_address' => 'Hôtel Silmandé, Ouaga 2000',
                'dropoff_latitude' => 12.3534,
                'dropoff_longitude' => -1.5156,
                'dropoff_contact_name' => 'Marie Dupont',
                'dropoff_contact_phone' => '+22670888999',
                'package_description' => 'Robe traditionnelle pour événement',
                'package_size' => 'medium',
                'distance_km' => 2.1,
                'total_price' => 1200,
            ],
            [
                'pickup_address' => 'Pâtisserie Délices de France, Zone du Bois',
                'pickup_latitude' => 12.3712,
                'pickup_longitude' => -1.5389,
                'pickup_contact_name' => 'Pâtissier Jean',
                'pickup_contact_phone' => '+22670999000',
                'dropoff_address' => 'Immeuble BICIA, Centre-ville',
                'dropoff_latitude' => 12.3678,
                'dropoff_longitude' => -1.5267,
                'dropoff_contact_name' => 'Secrétariat Direction',
                'dropoff_contact_phone' => '+22670000111',
                'package_description' => 'Gâteau d\'anniversaire - FRAGILE',
                'package_size' => 'medium',
                'distance_km' => 1.8,
                'total_price' => 1500,
            ],
        ];

        foreach ($orders as $index => $orderData) {
            // Calculer les frais
            $basePrice = 500;
            $distancePrice = $orderData['distance_km'] * 200;
            $totalPrice = $orderData['total_price'];
            $commissionAmount = $totalPrice * 0.15; // 15% commission
            $courierEarnings = $totalPrice - $commissionAmount;

            Order::create([
                'id' => Str::uuid(),
                'order_number' => 'ORD-' . now()->format('Ymd') . '-' . str_pad($index + 1, 4, '0', STR_PAD_LEFT),
                'client_id' => $client->id,
                'status' => 'pending',
                'pickup_address' => $orderData['pickup_address'],
                'pickup_latitude' => $orderData['pickup_latitude'],
                'pickup_longitude' => $orderData['pickup_longitude'],
                'pickup_contact_name' => $orderData['pickup_contact_name'],
                'pickup_contact_phone' => $orderData['pickup_contact_phone'],
                'pickup_instructions' => 'Sonner à l\'entrée',
                'dropoff_address' => $orderData['dropoff_address'],
                'dropoff_latitude' => $orderData['dropoff_latitude'],
                'dropoff_longitude' => $orderData['dropoff_longitude'],
                'dropoff_contact_name' => $orderData['dropoff_contact_name'],
                'dropoff_contact_phone' => $orderData['dropoff_contact_phone'],
                'dropoff_instructions' => 'Appeler en arrivant',
                'package_description' => $orderData['package_description'],
                'package_size' => $orderData['package_size'],
                'distance_km' => $orderData['distance_km'],
                'base_price' => $basePrice,
                'distance_price' => $distancePrice,
                'total_price' => $totalPrice,
                'commission_amount' => $commissionAmount,
                'courier_earnings' => $courierEarnings,
                'recipient_confirmation_code' => rand(1000, 9999),
            ]);
        }

        $this->command->info('✅ ' . count($orders) . ' commandes de test créées avec succès!');
    }
}

<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

class TestUsersSeeder extends Seeder
{
    /**
     * Clients de test avec différents profils
     * Note: Les numéros sont stockés sans préfixe +226 pour correspondre à la normalisation du AuthService
     */
    private array $clients = [
        [
            'phone' => '70100001',
            'name' => 'Aminata Ouédraogo',
            'email' => 'aminata@test.bf',
            'status' => UserStatus::ACTIVE,
        ],
        [
            'phone' => '70100002',
            'name' => 'Mamadou Traoré',
            'email' => 'mamadou@test.bf',
            'status' => UserStatus::ACTIVE,
        ],
        [
            'phone' => '70100003',
            'name' => 'Fatimata Kaboré',
            'email' => 'fatimata@test.bf',
            'status' => UserStatus::ACTIVE,
        ],
        [
            'phone' => '70100004',
            'name' => 'Ibrahim Sanogo',
            'email' => 'ibrahim@test.bf',
            'status' => UserStatus::ACTIVE,
        ],
        [
            'phone' => '70100005',
            'name' => 'Awa Compaoré',
            'email' => null,
            'status' => UserStatus::ACTIVE,
        ],
        [
            'phone' => '70100006',
            'name' => 'Seydou Diallo',
            'email' => 'seydou@test.bf',
            'status' => UserStatus::ACTIVE,
        ],
        [
            'phone' => '70100007',
            'name' => 'Mariam Sawadogo',
            'email' => null,
            'status' => UserStatus::ACTIVE,
        ],
        [
            'phone' => '70100008',
            'name' => 'Ousmane Zongo',
            'email' => 'ousmane@test.bf',
            'status' => UserStatus::SUSPENDED, // Client suspendu pour test
        ],
        [
            'phone' => '70100009',
            'name' => 'Aissata Bamba',
            'email' => null,
            'status' => UserStatus::ACTIVE,
        ],
        [
            'phone' => '70100010',
            'name' => 'Adama Ouattara',
            'email' => 'adama@test.bf',
            'status' => UserStatus::ACTIVE,
        ],
    ];

    /**
     * Coursiers de test avec différents profils et véhicules
     */
    private array $couriers = [
        [
            'phone' => '70200001',
            'name' => 'Moussa Sawadogo',
            'email' => 'moussa.coursier@test.bf',
            'status' => UserStatus::ACTIVE,
            'vehicle_type' => 'moto',
            'vehicle_plate' => 'BF-1234-AB',
            'vehicle_model' => 'Honda CG 125',
            'is_available' => true,
            'current_latitude' => 12.3714,
            'current_longitude' => -1.5197,
            'average_rating' => 4.8,
            'total_ratings' => 156,
            'total_orders' => 234,
            'wallet_balance' => 125000,
        ],
        [
            'phone' => '70200002',
            'name' => 'Boukary Zoungrana',
            'email' => 'boukary@test.bf',
            'status' => UserStatus::ACTIVE,
            'vehicle_type' => 'moto',
            'vehicle_plate' => 'BF-5678-CD',
            'vehicle_model' => 'Yamaha YBR 125',
            'is_available' => true,
            'current_latitude' => 12.3650,
            'current_longitude' => -1.5250,
            'average_rating' => 4.5,
            'total_ratings' => 89,
            'total_orders' => 145,
            'wallet_balance' => 87500,
        ],
        [
            'phone' => '70200003',
            'name' => 'Hamidou Kinda',
            'email' => null,
            'status' => UserStatus::ACTIVE,
            'vehicle_type' => 'moto',
            'vehicle_plate' => 'BF-9012-EF',
            'vehicle_model' => 'Suzuki GN 125',
            'is_available' => false, // Pas disponible
            'current_latitude' => 12.3580,
            'current_longitude' => -1.5100,
            'average_rating' => 4.2,
            'total_ratings' => 45,
            'total_orders' => 78,
            'wallet_balance' => 34000,
        ],
        [
            'phone' => '70200004',
            'name' => 'Saidou Tapsoba',
            'email' => 'saidou@test.bf',
            'status' => UserStatus::ACTIVE,
            'vehicle_type' => 'velo',
            'vehicle_plate' => null,
            'vehicle_model' => 'VTT Standard',
            'is_available' => true,
            'current_latitude' => 12.3800,
            'current_longitude' => -1.5300,
            'average_rating' => 4.9,
            'total_ratings' => 67,
            'total_orders' => 98,
            'wallet_balance' => 45000,
        ],
        [
            'phone' => '70200005',
            'name' => 'Abdoulaye Ouédraogo',
            'email' => null,
            'status' => UserStatus::ACTIVE,
            'vehicle_type' => 'voiture',
            'vehicle_plate' => 'BF-3456-GH',
            'vehicle_model' => 'Toyota Corolla',
            'is_available' => true,
            'current_latitude' => 12.3450,
            'current_longitude' => -1.5050,
            'average_rating' => 4.7,
            'total_ratings' => 112,
            'total_orders' => 189,
            'wallet_balance' => 215000,
        ],
        [
            'phone' => '70200006',
            'name' => 'Rasmané Compaoré',
            'email' => 'rasmane@test.bf',
            'status' => UserStatus::PENDING, // En attente d'approbation
            'vehicle_type' => 'moto',
            'vehicle_plate' => 'BF-7890-IJ',
            'vehicle_model' => 'Bajaj Boxer',
            'is_available' => false,
            'current_latitude' => null,
            'current_longitude' => null,
            'average_rating' => 0,
            'total_ratings' => 0,
            'total_orders' => 0,
            'wallet_balance' => 0,
        ],
        [
            'phone' => '70200007',
            'name' => 'Yacouba Kaboré',
            'email' => null,
            'status' => UserStatus::SUSPENDED, // Suspendu pour test
            'vehicle_type' => 'moto',
            'vehicle_plate' => 'BF-1122-KL',
            'vehicle_model' => 'TVS Apache',
            'is_available' => false,
            'current_latitude' => 12.3600,
            'current_longitude' => -1.5150,
            'average_rating' => 2.8,
            'total_ratings' => 23,
            'total_orders' => 34,
            'wallet_balance' => 12000,
        ],
        [
            'phone' => '70200008',
            'name' => 'Souleymane Dao',
            'email' => 'souleymane@test.bf',
            'status' => UserStatus::ACTIVE,
            'vehicle_type' => 'moto',
            'vehicle_plate' => 'BF-3344-MN',
            'vehicle_model' => 'Haojue Lucky',
            'is_available' => true,
            'current_latitude' => 12.3720,
            'current_longitude' => -1.5400,
            'average_rating' => 4.6,
            'total_ratings' => 78,
            'total_orders' => 123,
            'wallet_balance' => 67000,
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🧑 Création des clients de test...');
        
        foreach ($this->clients as $clientData) {
            $user = User::updateOrCreate(
                ['phone' => $clientData['phone']],
                array_merge($clientData, ['role' => UserRole::CLIENT])
            );
            $this->command->line("  ✓ Client: {$clientData['name']}");
        }

        $this->command->newLine();
        $this->command->info('🏍️ Création des coursiers de test...');

        foreach ($this->couriers as $courierData) {
            $user = User::updateOrCreate(
                ['phone' => $courierData['phone']],
                array_merge($courierData, ['role' => UserRole::COURIER])
            );
            $statusIcon = match($courierData['status']) {
                UserStatus::ACTIVE => '🟢',
                UserStatus::PENDING => '🟡',
                UserStatus::SUSPENDED => '🔴',
                default => '⚪',
            };
            $this->command->line("  {$statusIcon} Coursier: {$courierData['name']} ({$courierData['vehicle_type']})");
        }

        $this->command->newLine();
        $this->command->table(
            ['Type', 'Total', 'Actifs', 'En attente', 'Suspendus'],
            [
                [
                    'Clients',
                    count($this->clients),
                    collect($this->clients)->where('status', UserStatus::ACTIVE)->count(),
                    collect($this->clients)->where('status', UserStatus::PENDING)->count(),
                    collect($this->clients)->where('status', UserStatus::SUSPENDED)->count(),
                ],
                [
                    'Coursiers',
                    count($this->couriers),
                    collect($this->couriers)->where('status', UserStatus::ACTIVE)->count(),
                    collect($this->couriers)->where('status', UserStatus::PENDING)->count(),
                    collect($this->couriers)->where('status', UserStatus::SUSPENDED)->count(),
                ],
            ]
        );
    }
}

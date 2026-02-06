<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Les admins par défaut à créer
     */
    private array $admins = [
        [
            'name' => 'Super Admin',
            'phone' => '70000001',
            'email' => 'admin@ouagachap.bf',
            'password' => 'Admin@2024!',
        ],
        [
            'name' => 'Admin Support',
            'phone' => '70000002',
            'email' => 'support@ouagachap.bf',
            'password' => 'Support@2024!',
        ],
        [
            'name' => 'Admin Operations',
            'phone' => '70000003',
            'email' => 'operations@ouagachap.bf',
            'password' => 'Operations@2024!',
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->admins as $adminData) {
            User::updateOrCreate(
                ['phone' => $adminData['phone']],
                [
                    'name' => $adminData['name'],
                    'email' => $adminData['email'],
                    'password' => $adminData['password'], // Le cast 'hashed' hash automatiquement
                    'role' => UserRole::ADMIN,
                    'status' => UserStatus::ACTIVE,
                ]
            );

            $this->command->info("Admin créé: {$adminData['name']} ({$adminData['email']})");
        }

        $this->command->newLine();
        $this->command->warn('⚠️  IMPORTANT: Changez les mots de passe en production!');
        $this->command->table(
            ['Nom', 'Email', 'Téléphone', 'Mot de passe'],
            collect($this->admins)->map(fn($a) => [
                $a['name'],
                $a['email'],
                $a['phone'],
                $a['password']
            ])->toArray()
        );
    }
}

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
     * Crée les comptes administrateurs par défaut.
     *
     * ⚠️  À exécuter UNE SEULE FOIS en production.
     * Changez les mots de passe après la première connexion !
     *
     * Usage :
     *   php artisan db:seed --class=AdminSeeder
     */
    public function run(): void
    {
        $admins = [
            [
                'name'     => 'Koffi Parfait',
                'email'    => 'kteya96@gmail.com',
                'phone'    => '+22676000001',
                'password' => 'REMOVED_ADMIN_SECRET_1',
            ],
            [
                'name'     => 'Admin Opérations',
                'email'    => 'ops@ouagachap.com',
                'phone'    => '+22676000002',
                'password' => 'REMOVED_ADMIN_SECRET_2',
            ],
            [
                'name'     => 'Modérateur Support',
                'email'    => 'support@ouagachap.com',
                'phone'    => '+22676000003',
                'password' => 'REMOVED_ADMIN_SECRET_3',
            ],
        ];

        foreach ($admins as $data) {
            $user = User::withTrashed()
                ->where('email', $data['email'])
                ->first();

            if ($user) {
                // Réactive si soft-deleted et met à jour le rôle
                if ($user->trashed()) {
                    $user->restore();
                }

                $user->update([
                    'name'     => $data['name'],
                    'password' => Hash::make($data['password']),
                    'role'     => UserRole::ADMIN,
                    'status'   => UserStatus::ACTIVE,
                ]);

                $this->command->info("Admin existant mis à jour (mdp réinitialisé) : {$data['email']}");
            } else {
                User::create([
                    'name'     => $data['name'],
                    'email'    => $data['email'],
                    'phone'    => $data['phone'],
                    'password' => Hash::make($data['password']),
                    'role'     => UserRole::ADMIN,
                    'status'   => UserStatus::ACTIVE,
                ]);

                $this->command->info("Admin créé : {$data['email']} | MDP: {$data['password']}");
            }
        }

        $this->command->warn('⚠️  Changez les mots de passe après la première connexion !');
    }
}

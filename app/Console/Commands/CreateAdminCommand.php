<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'admin:create 
                            {--name= : Nom de l\'administrateur}
                            {--email= : Email de l\'administrateur}
                            {--phone= : Numéro de téléphone}
                            {--password= : Mot de passe}';

    /**
     * The console command description.
     */
    protected $description = 'Créer un nouvel administrateur OUAGA CHAP';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('');
        $this->info('╔══════════════════════════════════════════╗');
        $this->info('║    Création d\'un Administrateur          ║');
        $this->info('║           OUAGA CHAP                     ║');
        $this->info('╚══════════════════════════════════════════╝');
        $this->info('');

        // Collecter les informations
        $name = $this->option('name') ?? $this->ask('Nom complet de l\'administrateur');
        $email = $this->option('email') ?? $this->ask('Adresse email');
        $phone = $this->option('phone') ?? $this->ask('Numéro de téléphone (8 chiffres, ex: 70123456)');
        $password = $this->option('password') ?? $this->secret('Mot de passe (min 8 caractères)');

        // Valider les données
        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password' => $password,
        ], [
            'name' => 'required|string|min:2|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|digits:8|unique:users,phone',
            'password' => 'required|min:8',
        ], [
            'name.required' => 'Le nom est requis',
            'email.required' => 'L\'email est requis',
            'email.email' => 'L\'email doit être valide',
            'email.unique' => 'Cet email est déjà utilisé',
            'phone.required' => 'Le téléphone est requis',
            'phone.digits' => 'Le téléphone doit avoir 8 chiffres',
            'phone.unique' => 'Ce numéro est déjà utilisé',
            'password.required' => 'Le mot de passe est requis',
            'password.min' => 'Le mot de passe doit avoir au moins 8 caractères',
        ]);

        if ($validator->fails()) {
            $this->error('Erreurs de validation:');
            foreach ($validator->errors()->all() as $error) {
                $this->error("  ✗ {$error}");
            }
            return Command::FAILURE;
        }

        // Confirmation
        $this->info('');
        $this->table(
            ['Champ', 'Valeur'],
            [
                ['Nom', $name],
                ['Email', $email],
                ['Téléphone', $phone],
                ['Rôle', 'Administrateur'],
            ]
        );

        if (!$this->confirm('Créer cet administrateur?', true)) {
            $this->warn('Création annulée.');
            return Command::SUCCESS;
        }

        // Créer l'admin
        try {
            $admin = User::create([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'password' => Hash::make($password),
                'role' => UserRole::ADMIN,
                'status' => UserStatus::ACTIVE,
            ]);

            $this->info('');
            $this->info('✅ Administrateur créé avec succès!');
            $this->info('');
            $this->table(
                ['ID', 'Nom', 'Email', 'Téléphone'],
                [[$admin->id, $admin->name, $admin->email, $admin->phone]]
            );
            $this->info('');
            $this->warn('🔐 Connexion au panel admin: /admin');
            $this->warn('   Email: ' . $email);
            $this->warn('   Mot de passe: [celui que vous avez défini]');
            $this->info('');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Erreur lors de la création: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

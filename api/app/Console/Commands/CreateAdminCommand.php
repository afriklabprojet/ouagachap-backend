<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Crée un compte administrateur de manière interactive.
 *
 * Usage :
 *   php artisan admin:create
 *   php artisan admin:create --email=admin@example.com --name="Mon Admin" --password=secret
 */
class CreateAdminCommand extends Command
{
    protected $signature = 'admin:create
                            {--email= : Email de l\'administrateur}
                            {--name=  : Nom complet}
                            {--phone= : Numéro de téléphone}
                            {--password= : Mot de passe (min. 8 caractères)}'; // NOSONAR php:S2068

    protected $description = 'Créer un compte administrateur pour le panel Filament';

    public function handle(): int
    {
        $this->info('=== Création d\'un compte administrateur OUAGA CHAP ===');
        $this->newLine();

        [$email, $name, $phone, $plainPassword] = $this->collectInputs();

        if (!$this->validateInputs($email, $name, $plainPassword)) {
            return self::FAILURE;
        }

        $existingResult = $this->handleExistingUser((string) $email, (string) $name, (string) $plainPassword);
        if ($existingResult !== null) {
            return $existingResult;
        }

        return $this->createNewAdmin((string) $email, (string) $name, $phone !== null ? (string) $phone : null, (string) $plainPassword);
    }

    /**
     * @return array<int, string|null>
     */
    private function collectInputs(): array
    {
        $email = $this->option('email') ?? $this->ask('Email de l\'administrateur');
        $name  = $this->option('name')  ?? $this->ask('Nom complet', 'Admin');
        $phone = $this->option('phone') ?? $this->ask('Téléphone (ex: +22670000001)', null);

        if ($this->option('password')) {
            // Warn: passing password via CLI flag exposes it in process list (ps aux / shell history)
            $this->warn('⚠️  Mot de passe fourni en clair via --password. Préférez le mode interactif pour éviter l\'exposition dans ps/history.');
            $plainPassword = $this->option('password');
        } else {
            $plainPassword = $this->secret('Mot de passe (min. 8 caractères)');
        }

        return [$email, $name, $phone, $plainPassword];
    }

    private function validateInputs(mixed $email, mixed $name, mixed $plainPassword): bool
    {
        $validator = Validator::make(
            ['email' => $email, 'name' => $name, 'password' => $plainPassword],
            [
                'email'    => ['required', 'email'],
                'name'     => ['required', 'string', 'min:2'],
                'password' => ['required', 'string', 'min:8'],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return false;
        }

        return true;
    }

    private function handleExistingUser(string $email, string $name, string $plainPassword): ?int
    {
        $existing = User::withTrashed()->where('email', $email)->first();

        if (!$existing) {
            return null;
        }

        if ($existing->trashed()) {
            return $this->restoreExistingAdmin($existing, $name, $plainPassword);
        }

        return $this->promoteExistingUser($existing, $email);
    }

    private function restoreExistingAdmin(User $existing, string $name, string $plainPassword): int
    {
        $existing->restore();
        $existing->update([
            'name'     => $name,
            'role'     => UserRole::ADMIN,
            'status'   => UserStatus::ACTIVE,
            'password' => Hash::make($plainPassword),
        ]);

        $this->info("✅ Compte restauré et promu en admin : {$existing->email}");

        return self::SUCCESS;
    }

    private function promoteExistingUser(User $existing, string $email): int
    {
        if ($existing->isAdmin()) {
            $this->warn("Un admin avec cet email existe déjà : {$email}");

            return self::SUCCESS;
        }

        if (!$this->confirm("Cet utilisateur existe (rôle: {$existing->role->value}). Promouvoir en admin ?")) {
            $this->info('Opération annulée.');

            return self::SUCCESS;
        }

        $existing->update(['role' => UserRole::ADMIN, 'status' => UserStatus::ACTIVE]);
        $this->info("✅ Utilisateur promu en admin : {$email}");

        return self::SUCCESS;
    }

    private function createNewAdmin(string $email, string $name, ?string $phone, string $plainPassword): int
    {
        $admin = User::create([
            'name'     => $name,
            'email'    => $email,
            'phone'    => $phone,
            'password' => Hash::make($plainPassword),
            'role'     => UserRole::ADMIN,
            'status'   => UserStatus::ACTIVE,
        ]);

        $this->newLine();
        $this->info('✅ Administrateur créé avec succès !');
        $this->table(
            ['Champ', 'Valeur'],
            [
                ['ID',    $admin->id],
                ['Nom',   $admin->name],
                ['Email', $admin->email],
                ['Rôle',  $admin->role->label()],
            ]
        );
        $this->newLine();
        $this->warn('⚠️  Notez le mot de passe — il ne sera plus affiché.');

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;

class ListAdminsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'admin:list';

    /**
     * The console command description.
     */
    protected $description = 'Lister tous les administrateurs OUAGA CHAP';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $admins = User::where('role', UserRole::ADMIN)->get();

        if ($admins->isEmpty()) {
            $this->warn('Aucun administrateur trouvé.');
            $this->info('Utilisez "php artisan admin:create" pour en créer un.');
            return Command::SUCCESS;
        }

        $this->info('');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('              Administrateurs OUAGA CHAP (' . $admins->count() . ')');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('');

        $this->table(
            ['ID', 'Nom', 'Email', 'Téléphone', 'Status', 'Créé le'],
            $admins->map(fn($admin) => [
                $admin->id,
                $admin->name,
                $admin->email ?? '-',
                $admin->phone,
                $admin->status->value,
                $admin->created_at->format('d/m/Y H:i'),
            ])->toArray()
        );

        $this->info('');
        $this->info('Panel Admin: /admin');
        $this->info('');

        return Command::SUCCESS;
    }
}

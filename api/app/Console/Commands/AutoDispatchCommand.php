<?php

namespace App\Console\Commands;

use App\Services\SmartDispatcherService;
use Illuminate\Console\Command;

class AutoDispatchCommand extends Command
{
    protected $signature = 'dispatch:auto
        {--dry-run : Afficher les résultats sans assigner les coursiers}
        {--verbose-details : Afficher le détail de chaque commande traitée}';

    protected $description = 'Assigne automatiquement les commandes PENDING sans coursier (même logique que le cron toutes les 2 min)';

    public function handle(SmartDispatcherService $dispatcher): int
    {
        $dryRun = $this->option('dry-run');
        $verboseDetails = $this->option('verbose-details');

        if ($dryRun) {
            $this->warn('Mode dry-run : aucune assignation ne sera effectuée.');
        }

        $this->info('Démarrage du dispatch automatique…');

        $result = $dispatcher->autoDispatchPending();

        $this->table(
            ['Catégorie', 'Nombre'],
            [
                ['✅ Assignées',  $result['dispatched']],
                ['❌ Échouées',   $result['failed']],
                ['⏭️  Ignorées',  $result['skipped']],
                ['📦 Total',      $result['dispatched'] + $result['failed'] + $result['skipped']],
            ]
        );

        if ($verboseDetails && !empty($result['details'])) {
            $this->newLine();
            $this->line('Détails :');
            $rows = array_map(fn ($d) => [
                $d['order_id'],
                $d['status'],
                $d['courier_id'] ?? '—',
                $d['reason']     ?? ($d['score'] ? 'score: ' . round($d['score']['total'] ?? 0, 1) : '—'),
            ], $result['details']);

            $this->table(['Commande', 'Statut', 'Coursier', 'Info'], $rows);
        }

        if ($result['failed'] > 0) {
            $this->warn("{$result['failed']} commande(s) n'ont pas pu être assignées.");
        }

        $this->info('Dispatch terminé.');

        return self::SUCCESS;
    }
}

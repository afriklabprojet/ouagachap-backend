<?php

namespace App\Console\Commands;

use App\Repositories\OrderRepository;
use App\Repositories\UserRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

class HealthCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'health:check';

    /**
     * The console command description.
     */
    protected $description = 'Vérifier la santé de l\'application OUAGA CHAP';

    /**
     * Execute the console command.
     */
    public function handle(
        OrderRepository $orderRepository,
        UserRepository $userRepository
    ): int {
        $this->info('');
        $this->info('╔══════════════════════════════════════════╗');
        $this->info('║      Health Check - OUAGA CHAP API       ║');
        $this->info('╚══════════════════════════════════════════╝');
        $this->info('');

        $checks = [];

        // Database
        $checks['Database'] = $this->checkDatabase();

        // Cache
        $checks['Cache'] = $this->checkCache();

        // Queue
        $checks['Queue'] = $this->checkQueue();

        // Storage
        $checks['Storage'] = $this->checkStorage();

        // Stats
        $this->displayStats($orderRepository, $userRepository);

        // Display results
        $this->info('');
        $this->info('Résultats des vérifications:');
        $this->info('');

        $allPassed = true;
        foreach ($checks as $name => $result) {
            $status = $result['success'] ? '✓' : '✗';
            $color = $result['success'] ? 'green' : 'red';
            $message = $result['message'];
            $time = $result['time_ms'] ?? 0;

            if ($result['success']) {
                $this->info("  <fg=green>{$status}</> {$name}: {$message} ({$time}ms)");
            } else {
                $this->error("  {$status} {$name}: {$message}");
                $allPassed = false;
            }
        }

        $this->info('');

        if ($allPassed) {
            $this->info('✅ Tous les services sont opérationnels');
            return Command::SUCCESS;
        } else {
            $this->error('❌ Certains services présentent des problèmes');
            return Command::FAILURE;
        }
    }

    protected function checkDatabase(): array
    {
        $start = microtime(true);
        try {
            DB::connection()->getPdo();
            $users = DB::table('users')->count();
            return [
                'success' => true,
                'message' => "Connecté ({$users} utilisateurs)",
                'time_ms' => round((microtime(true) - $start) * 1000, 2),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    protected function checkCache(): array
    {
        $start = microtime(true);
        try {
            $key = 'health_check_' . time();
            Cache::put($key, 'test', 10);
            $value = Cache::get($key);
            Cache::forget($key);
            
            if ($value !== 'test') {
                throw new \Exception('Cache read/write failed');
            }

            return [
                'success' => true,
                'message' => 'Lecture/écriture OK',
                'time_ms' => round((microtime(true) - $start) * 1000, 2),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    protected function checkQueue(): array
    {
        $start = microtime(true);
        try {
            $connection = config('queue.default');
            $pending = DB::table('jobs')->count();

            return [
                'success' => true,
                'message' => "Driver: {$connection}, {$pending} jobs en attente",
                'time_ms' => round((microtime(true) - $start) * 1000, 2),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    protected function checkStorage(): array
    {
        $start = microtime(true);
        try {
            $storagePath = storage_path('app');
            $writable = is_writable($storagePath);

            if (!$writable) {
                throw new \Exception('Storage non accessible en écriture');
            }

            $freeSpace = disk_free_space($storagePath);
            $freeSpaceGB = round($freeSpace / 1024 / 1024 / 1024, 2);

            return [
                'success' => true,
                'message' => "Accessible, {$freeSpaceGB} GB libre",
                'time_ms' => round((microtime(true) - $start) * 1000, 2),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    protected function displayStats(
        OrderRepository $orderRepository,
        UserRepository $userRepository
    ): void {
        $this->info('');
        $this->info('Statistiques rapides:');
        $this->info('');

        try {
            $userStats = $userRepository->getDashboardStats();
            $orderStats = $orderRepository->getDashboardStats();

            $this->table(
                ['Métrique', 'Valeur'],
                [
                    ['Clients actifs', $userStats['clients']['active']],
                    ['Coursiers actifs', $userStats['couriers']['active']],
                    ['Coursiers disponibles', $userStats['couriers']['available_now']],
                    ['Commandes aujourd\'hui', $orderStats['today']['total']],
                    ['Commandes en attente', $orderStats['by_status']['pending']],
                    ['CA aujourd\'hui', number_format($orderStats['today']['revenue'], 0, ',', ' ') . ' XOF'],
                ]
            );
        } catch (\Exception $e) {
            $this->warn('Impossible de charger les statistiques: ' . $e->getMessage());
        }
    }
}

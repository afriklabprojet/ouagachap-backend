<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TestDataSeeder extends Seeder
{
    /**
     * Seeder maître pour générer toutes les données de test.
     * 
     * Usage:
     *   php artisan db:seed --class=TestDataSeeder
     * 
     * Ce seeder crée un environnement de test complet avec:
     * - Zones de livraison
     * - Utilisateurs (clients et coursiers)
     * - Commandes dans tous les statuts
     * - Paiements
     * - Notations
     * - Codes promo
     * - FAQs
     * 
     * ⚠️ ATTENTION: Ce seeder est conçu pour les environnements de développement/test uniquement.
     */
    public function run(): void
    {
        $this->command->newLine();
        $this->command->info('╔══════════════════════════════════════════════════════════════╗');
        $this->command->info('║           🚀 OUAGA CHAP - TEST DATA SEEDER 🚀                ║');
        $this->command->info('╚══════════════════════════════════════════════════════════════╝');
        $this->command->newLine();

        // Vérification de l'environnement
        if (app()->environment('production')) {
            $this->command->error('❌ Ce seeder ne peut pas être exécuté en production !');
            return;
        }

        $this->command->warn('⚠️  Environnement: ' . app()->environment());
        $this->command->warn('⚠️  Ce seeder va créer des données de test.');
        $this->command->newLine();

        $startTime = microtime(true);

        try {
            DB::beginTransaction();

            // 1. Rôles et permissions (si pas déjà fait)
            $this->command->newLine();
            $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->command->info('📋 ÉTAPE 1/8: Rôles et Permissions');
            $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->callSilentlyIfExists(RolesAndPermissionsSeeder::class);

            // 2. Administrateur
            $this->command->newLine();
            $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->command->info('👤 ÉTAPE 2/8: Administrateur');
            $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->callSilentlyIfExists(AdminSeeder::class);

            // 3. Zones de livraison
            $this->command->newLine();
            $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->command->info('🗺️ ÉTAPE 3/8: Zones de livraison');
            $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->call(TestZonesSeeder::class);

            // 4. Utilisateurs de test
            $this->command->newLine();
            $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->command->info('👥 ÉTAPE 4/8: Utilisateurs de test');
            $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->call(TestUsersSeeder::class);

            // 5. Commandes
            $this->command->newLine();
            $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->command->info('📦 ÉTAPE 5/8: Commandes de test');
            $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->call(TestOrdersCompleteSeeder::class);

            // 6. Notations
            $this->command->newLine();
            $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->command->info('⭐ ÉTAPE 6/8: Notations de test');
            $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->call(TestRatingsSeeder::class);

            // 7. Codes promo
            $this->command->newLine();
            $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->command->info('🎁 ÉTAPE 7/8: Codes promo de test');
            $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->call(TestPromoCodesSeeder::class);

            // 8. FAQs
            $this->command->newLine();
            $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->command->info('❓ ÉTAPE 8/8: FAQs de test');
            $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->call(TestFaqsSeeder::class);

            DB::commit();

            $duration = round(microtime(true) - $startTime, 2);

            $this->command->newLine();
            $this->command->info('╔══════════════════════════════════════════════════════════════╗');
            $this->command->info('║              ✅ DONNÉES DE TEST CRÉÉES !                    ║');
            $this->command->info('╚══════════════════════════════════════════════════════════════╝');
            $this->command->newLine();

            $this->displayFinalSummary();

            $this->command->newLine();
            $this->command->info("⏱️  Temps d'exécution: {$duration} secondes");
            $this->command->newLine();

            // Informations de connexion
            $this->displayLoginInfo();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('❌ Erreur lors de la création des données: ' . $e->getMessage());
            $this->command->error($e->getTraceAsString());
        }
    }

    private function callSilentlyIfExists(string $seederClass): void
    {
        if (class_exists($seederClass)) {
            $this->callSilent($seederClass);
            $this->command->line('  ✅ ' . class_basename($seederClass) . ' exécuté');
        } else {
            $this->command->line('  ⏭️  ' . class_basename($seederClass) . ' non trouvé, ignoré');
        }
    }

    private function displayFinalSummary(): void
    {
        $stats = [];

        // Compter les enregistrements
        $tables = [
            'users' => '👥 Utilisateurs',
            'orders' => '📦 Commandes',
            'payments' => '💳 Paiements',
            'zones' => '🗺️ Zones',
            'promo_codes' => '🎁 Codes promo',
            'faqs' => '❓ FAQs',
        ];

        foreach ($tables as $table => $label) {
            if (Schema::hasTable($table)) {
                $count = DB::table($table)->count();
                $stats[] = [$label, $count];
            }
        }

        // Ajouter les stats utilisateurs par rôle
        if (Schema::hasTable('users')) {
            $clients = DB::table('users')->where('role', 'client')->count();
            $couriers = DB::table('users')->where('role', 'courier')->count();
            $admins = DB::table('users')->where('role', 'admin')->count();
            $stats[] = ['  └─ Clients', $clients];
            $stats[] = ['  └─ Coursiers', $couriers];
            $stats[] = ['  └─ Admins', $admins];
        }

        // Ajouter les stats commandes par statut
        if (Schema::hasTable('orders')) {
            $statuses = DB::table('orders')
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();
            foreach ($statuses as $status => $count) {
                $stats[] = ["  └─ {$status}", $count];
            }
        }

        $this->command->info('📊 Résumé final:');
        $this->command->table(['Entité', 'Nombre'], $stats);
    }

    private function displayLoginInfo(): void
    {
        $this->command->info('🔐 Informations de connexion pour les tests:');
        $this->command->newLine();
        
        $this->command->table(
            ['Rôle', 'Téléphone', 'OTP (dev)'],
            [
                ['Admin', 'admin@ouagachap.com', 'Via Filament'],
                ['Client #1', '70100001', '123456'],
                ['Client #2', '70100002', '123456'],
                ['Coursier #1', '70200001', '123456'],
                ['Coursier #2', '70200002', '123456'],
            ]
        );

        $this->command->newLine();
        $this->command->info('📱 Applications de test:');
        $this->command->line('  • Client: Utilisez n\'importe quel téléphone 7010000X');
        $this->command->line('  • Coursier: Utilisez n\'importe quel téléphone 7020000X');
        $this->command->line('  • Admin: Accédez à /admin avec les identifiants du seeder');
        $this->command->newLine();
        $this->command->info('💡 Rappel: En mode local (APP_ENV=local), l\'OTP est toujours 123456');
        $this->command->newLine();
        $this->command->warn('⚠️  Séparation des rôles:');
        $this->command->line('  • Un client ne peut PAS se connecter à l\'app coursier');
        $this->command->line('  • Un coursier ne peut PAS se connecter à l\'app client');
    }
}

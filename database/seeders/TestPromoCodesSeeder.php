<?php

namespace Database\Seeders;

use App\Models\PromoCode;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TestPromoCodesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🎁 Création des codes promo de test...');
        $this->command->newLine();

        $promoCodes = [
            // Codes de bienvenue
            [
                'code' => 'BIENVENUE',
                'name' => 'Code Bienvenue',
                'description' => 'Code de bienvenue pour les nouveaux utilisateurs',
                'type' => 'percentage',
                'value' => 20,
                'min_order_amount' => 1000,
                'max_discount' => 500,
                'max_uses' => 1000,
                'current_uses' => 245,
                'max_uses_per_user' => 1,
                'first_order_only' => true,
                'starts_at' => now()->subMonths(3),
                'expires_at' => now()->addMonths(6),
                'is_active' => true,
            ],
            [
                'code' => 'NEWUSER50',
                'name' => '50% Premier Achat',
                'description' => '50% de réduction pour le premier achat',
                'type' => 'percentage',
                'value' => 50,
                'min_order_amount' => 1500,
                'max_discount' => 1000,
                'max_uses' => 500,
                'current_uses' => 123,
                'max_uses_per_user' => 1,
                'first_order_only' => true,
                'starts_at' => now()->subMonth(),
                'expires_at' => now()->addMonths(2),
                'is_active' => true,
            ],

            // Codes promotionnels saisonniers
            [
                'code' => 'TABASKI2024',
                'name' => 'Promo Tabaski',
                'description' => 'Promotion spéciale Tabaski',
                'type' => 'percentage',
                'value' => 15,
                'min_order_amount' => 2000,
                'max_discount' => 750,
                'max_uses' => 2000,
                'current_uses' => 890,
                'max_uses_per_user' => 3,
                'first_order_only' => false,
                'starts_at' => now()->subWeeks(2),
                'expires_at' => now()->addWeek(),
                'is_active' => true,
            ],
            [
                'code' => 'FASO',
                'name' => 'Fête Nationale',
                'description' => 'Fête nationale - Livraison offerte',
                'type' => 'fixed',
                'value' => 500,
                'min_order_amount' => 500,
                'max_discount' => null,
                'max_uses' => 1000,
                'current_uses' => 567,
                'max_uses_per_user' => 2,
                'first_order_only' => false,
                'starts_at' => now()->subDays(10),
                'expires_at' => now()->subDays(3),
                'is_active' => false, // Expiré
            ],

            // Codes partenaires
            [
                'code' => 'MARINAFOOD',
                'name' => 'Partenariat Marina',
                'description' => 'Partenariat Marina Market - Livraison à 300 FCFA',
                'type' => 'fixed',
                'value' => 400,
                'min_order_amount' => 1000,
                'max_discount' => null,
                'max_uses' => 9999, // Presque illimité
                'current_uses' => 1234,
                'max_uses_per_user' => 100, // Haute limite
                'first_order_only' => false,
                'starts_at' => now()->subMonths(2),
                'expires_at' => now()->addMonths(4),
                'is_active' => true,
            ],
            [
                'code' => 'SILMANDE',
                'name' => 'Clients Silmandé',
                'description' => 'Clients Hôtel Silmandé - 25% de réduction',
                'type' => 'percentage',
                'value' => 25,
                'min_order_amount' => 1500,
                'max_discount' => 1500,
                'max_uses' => 500,
                'current_uses' => 89,
                'max_uses_per_user' => 5,
                'first_order_only' => false,
                'starts_at' => now()->subMonths(1),
                'expires_at' => now()->addMonths(5),
                'is_active' => true,
            ],

            // Codes VIP/Fidélité
            [
                'code' => 'VIP2024',
                'name' => 'Code VIP',
                'description' => 'Code exclusif pour clients VIP',
                'type' => 'percentage',
                'value' => 30,
                'min_order_amount' => 0,
                'max_discount' => 2000,
                'max_uses' => 100,
                'current_uses' => 45,
                'max_uses_per_user' => 10,
                'first_order_only' => false,
                'starts_at' => now()->subMonths(2),
                'expires_at' => now()->addYear(),
                'is_active' => true,
            ],
            [
                'code' => 'FIDELE10',
                'name' => 'Fidélité 10%',
                'description' => 'Programme fidélité - 10% permanent',
                'type' => 'percentage',
                'value' => 10,
                'min_order_amount' => 500,
                'max_discount' => 500,
                'max_uses' => 99999, // Presque illimité
                'current_uses' => 2345,
                'max_uses_per_user' => 999, // Haute limite
                'first_order_only' => false,
                'starts_at' => now()->subYear(),
                'expires_at' => now()->addYears(2),
                'is_active' => true,
            ],

            // Codes test/dev
            [
                'code' => 'TESTFREE',
                'name' => 'Test Gratuit',
                'description' => 'Code de test - Livraison gratuite (dev uniquement)',
                'type' => 'free_delivery',
                'value' => 100,
                'min_order_amount' => 0,
                'max_discount' => null,
                'max_uses' => 99999, // Presque illimité
                'current_uses' => 0,
                'max_uses_per_user' => 999, // Haute limite
                'first_order_only' => false,
                'starts_at' => now()->subYear(),
                'expires_at' => now()->addYears(5),
                'is_active' => config('app.env') === 'local',
            ],
            [
                'code' => 'DEMO500',
                'name' => 'Démo 500 FCFA',
                'description' => 'Code démo - 500 FCFA de réduction',
                'type' => 'fixed',
                'value' => 500,
                'min_order_amount' => 1000,
                'max_discount' => null,
                'max_uses' => 50,
                'current_uses' => 12,
                'max_uses_per_user' => 1,
                'first_order_only' => false,
                'starts_at' => now(),
                'expires_at' => now()->addMonth(),
                'is_active' => true,
            ],

            // Codes expirés (pour tester les validations)
            [
                'code' => 'NOEL2023',
                'name' => 'Noël 2023',
                'description' => 'Promotion Noël 2023 (expirée)',
                'type' => 'percentage',
                'value' => 25,
                'min_order_amount' => 1500,
                'max_discount' => 1000,
                'max_uses' => 1000,
                'current_uses' => 876,
                'max_uses_per_user' => 2,
                'first_order_only' => false,
                'starts_at' => now()->subYear(),
                'expires_at' => now()->subMonths(6),
                'is_active' => false,
            ],
            [
                'code' => 'FLASHSALE',
                'name' => 'Vente Flash',
                'description' => 'Vente flash terminée',
                'type' => 'percentage',
                'value' => 40,
                'min_order_amount' => 2000,
                'max_discount' => 2000,
                'max_uses' => 100,
                'current_uses' => 100, // Épuisé
                'max_uses_per_user' => 1,
                'first_order_only' => false,
                'starts_at' => now()->subWeeks(3),
                'expires_at' => now()->subWeek(),
                'is_active' => false,
            ],

            // Codes futurs (pas encore actifs)
            [
                'code' => 'RAMADAN25',
                'name' => 'Ramadan 2025',
                'description' => 'Promotion Ramadan 2025',
                'type' => 'percentage',
                'value' => 25,
                'min_order_amount' => 1000,
                'max_discount' => 1500,
                'max_uses' => 5000,
                'current_uses' => 0,
                'max_uses_per_user' => 5,
                'first_order_only' => false,
                'starts_at' => now()->addMonths(2),
                'expires_at' => now()->addMonths(3),
                'is_active' => true, // Actif mais pas encore valide (date future)
            ],
        ];

        foreach ($promoCodes as $promoData) {
            try {
                PromoCode::updateOrCreate(
                    ['code' => $promoData['code']],
                    $promoData
                );
                $status = $promoData['is_active'] ? '✅' : '❌';
                $this->command->line("  {$status} {$promoData['code']} - {$promoData['description']}");
            } catch (\Exception $e) {
                $this->command->warn("  ⚠️  Impossible de créer {$promoData['code']}: {$e->getMessage()}");
            }
        }

        $this->command->newLine();
        $this->displaySummary();
    }

    private function displaySummary(): void
    {
        try {
            $total = PromoCode::count();
            $active = PromoCode::where('is_active', true)
                ->where('starts_at', '<=', now())
                ->where('expires_at', '>=', now())
                ->count();
            $expired = PromoCode::where('expires_at', '<', now())->count();
            $future = PromoCode::where('starts_at', '>', now())->count();

            $this->command->info('🎁 Résumé des codes promo:');
            $this->command->table(
                ['Statut', 'Nombre'],
                [
                    ['Total', $total],
                    ['Actifs', $active],
                    ['Expirés', $expired],
                    ['À venir', $future],
                ]
            );

            // Top codes les plus utilisés
            $topCodes = PromoCode::orderBy('current_uses', 'desc')
                ->take(5)
                ->get(['code', 'current_uses', 'type', 'value']);

            if ($topCodes->isNotEmpty()) {
                $this->command->newLine();
                $this->command->info('🏆 Top 5 codes les plus utilisés:');
                $this->command->table(
                    ['Code', 'Utilisations', 'Réduction'],
                    $topCodes->map(fn($code) => [
                        $code->code,
                        $code->current_uses,
                        $code->type === 'percentage' 
                            ? "{$code->value}%" 
                            : "{$code->value} FCFA"
                    ])->toArray()
                );
            }
        } catch (\Exception $e) {
            $this->command->warn("⚠️  Impossible d'afficher le résumé: {$e->getMessage()}");
        }
    }
}

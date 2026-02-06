<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Rating;
use App\Models\User;
use App\Enums\OrderStatus;
use Illuminate\Database\Seeder;

class TestRatingsSeeder extends Seeder
{
    /**
     * Tags positifs pour les coursiers
     */
    private array $positiveCourierTags = [
        'rapide',
        'professionnel',
        'aimable',
        'ponctuel',
        'soigneux',
        'communicatif',
    ];

    /**
     * Tags négatifs pour les coursiers
     */
    private array $negativeCourierTags = [
        'lent',
        'impoli',
        'retard',
        'colis_abime',
        'difficile_joindre',
    ];

    /**
     * Tags positifs pour les clients
     */
    private array $positiveClientTags = [
        'clair',
        'patient',
        'disponible',
        'respectueux',
        'genereux',
    ];

    /**
     * Tags négatifs pour les clients
     */
    private array $negativeClientTags = [
        'impatient',
        'absent',
        'imprecis',
        'difficile',
    ];

    /**
     * Commentaires positifs pour coursiers
     */
    private array $positiveCourierComments = [
        'Excellent service ! Le coursier était très professionnel et rapide.',
        'Livraison impeccable, le colis était en parfait état.',
        'Très bon coursier, ponctuel et souriant. Je recommande !',
        'Service de qualité, le coursier a bien communiqué sur l\'avancement.',
        'Rapide et efficace, mon colis est arrivé avant l\'heure prévue.',
        'Coursier très aimable et professionnel. Parfait !',
        'Service 5 étoiles, rien à redire.',
        'Excellent ! Le coursier a pris soin de mon colis fragile.',
        'Très satisfait de la livraison, coursier au top !',
        'Ponctuel et courtois, une très bonne expérience.',
    ];

    /**
     * Commentaires négatifs pour coursiers
     */
    private array $negativeCourierComments = [
        'Livraison en retard de 30 minutes sans explication.',
        'Le coursier était difficile à joindre par téléphone.',
        'Mon colis était légèrement abîmé à la livraison.',
        'Attitude un peu froide du coursier.',
        'Service correct mais pourrait être amélioré.',
    ];

    /**
     * Commentaires pour clients
     */
    private array $positiveClientComments = [
        'Client très agréable et disponible.',
        'Adresse facile à trouver, client présent à l\'heure.',
        'Client patient et compréhensif.',
        'Très bon accueil, client respectueux.',
        'Client généreux avec le pourboire !',
    ];

    private array $negativeClientComments = [
        'Client absent au moment de la livraison.',
        'Adresse difficile à trouver, manque de précision.',
        'Client un peu impatient.',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $deliveredOrders = Order::where('status', OrderStatus::DELIVERED)
            ->with(['client', 'courier'])
            ->get();

        if ($deliveredOrders->isEmpty()) {
            $this->command->error('❌ Aucune commande livrée trouvée. Exécutez d\'abord TestOrdersCompleteSeeder.');
            return;
        }

        $this->command->info('⭐ Création des notations de test...');
        $this->command->newLine();

        $ratedCount = 0;
        $totalOrders = $deliveredOrders->count();

        foreach ($deliveredOrders as $order) {
            // 80% des commandes sont notées par le client
            if (rand(1, 100) <= 80) {
                $this->createClientToCourierRating($order);
                $ratedCount++;
            }

            // 60% des commandes sont notées par le coursier
            if (rand(1, 100) <= 60) {
                $this->createCourierToClientRating($order);
            }
        }

        $this->command->newLine();
        $this->updateUserRatings();
        $this->displaySummary();
    }

    private function createClientToCourierRating(Order $order): void
    {
        // Distribution réaliste des notes (plus de bonnes notes)
        $rating = $this->getRealisticRating();
        
        $isPositive = $rating >= 4;
        $tags = $isPositive 
            ? $this->getRandomTags($this->positiveCourierTags, rand(1, 3))
            : $this->getRandomTags($this->negativeCourierTags, rand(1, 2));
        
        $comment = rand(1, 100) <= 70 // 70% laissent un commentaire
            ? ($isPositive 
                ? $this->positiveCourierComments[array_rand($this->positiveCourierComments)]
                : $this->negativeCourierComments[array_rand($this->negativeCourierComments)])
            : null;

        // Mettre à jour la commande avec la note
        $order->update([
            'courier_rating' => $rating,
            'courier_review' => $comment,
        ]);

        // Créer l'enregistrement dans la table ratings si elle existe
        if (class_exists(Rating::class) && method_exists(Rating::class, 'create')) {
            try {
                Rating::create([
                    'order_id' => $order->id,
                    'rater_id' => $order->client_id,
                    'rated_id' => $order->courier_id,
                    'type' => 'client_to_courier',
                    'rating' => $rating,
                    'comment' => $comment,
                    'tags' => $tags,
                    'is_visible' => true,
                    'created_at' => $order->delivered_at?->addMinutes(rand(5, 1440)), // 5min à 24h après
                ]);
            } catch (\Exception $e) {
                // Table ratings n'existe peut-être pas, on continue
            }
        }
    }

    private function createCourierToClientRating(Order $order): void
    {
        $rating = $this->getRealisticRating(true); // Encore plus positif pour les clients
        
        $isPositive = $rating >= 4;
        $tags = $isPositive 
            ? $this->getRandomTags($this->positiveClientTags, rand(1, 2))
            : $this->getRandomTags($this->negativeClientTags, rand(1, 2));
        
        $comment = rand(1, 100) <= 50 // 50% des coursiers laissent un commentaire
            ? ($isPositive 
                ? $this->positiveClientComments[array_rand($this->positiveClientComments)]
                : $this->negativeClientComments[array_rand($this->negativeClientComments)])
            : null;

        // Mettre à jour la commande avec la note
        $order->update([
            'client_rating' => $rating,
            'client_review' => $comment,
        ]);

        // Créer l'enregistrement dans la table ratings si elle existe
        if (class_exists(Rating::class) && method_exists(Rating::class, 'create')) {
            try {
                Rating::create([
                    'order_id' => $order->id,
                    'rater_id' => $order->courier_id,
                    'rated_id' => $order->client_id,
                    'type' => 'courier_to_client',
                    'rating' => $rating,
                    'comment' => $comment,
                    'tags' => $tags,
                    'is_visible' => true,
                    'created_at' => $order->delivered_at?->addMinutes(rand(30, 2880)), // 30min à 48h après
                ]);
            } catch (\Exception $e) {
                // Table ratings n'existe peut-être pas, on continue
            }
        }
    }

    private function getRealisticRating(bool $morePositive = false): int
    {
        // Distribution réaliste des notes
        $distribution = $morePositive
            ? [5 => 50, 4 => 35, 3 => 10, 2 => 4, 1 => 1] // Plus positif
            : [5 => 40, 4 => 35, 3 => 15, 2 => 7, 1 => 3]; // Normal

        $rand = rand(1, 100);
        $cumulative = 0;

        foreach ($distribution as $rating => $percentage) {
            $cumulative += $percentage;
            if ($rand <= $cumulative) {
                return $rating;
            }
        }

        return 5;
    }

    private function getRandomTags(array $tags, int $count): array
    {
        shuffle($tags);
        return array_slice($tags, 0, min($count, count($tags)));
    }

    private function updateUserRatings(): void
    {
        $this->command->line('📊 Mise à jour des moyennes utilisateurs...');

        // Mettre à jour les moyennes des coursiers
        $couriers = User::where('role', 'courier')->get();
        foreach ($couriers as $courier) {
            $ratings = Order::where('courier_id', $courier->id)
                ->whereNotNull('courier_rating')
                ->pluck('courier_rating');

            if ($ratings->isNotEmpty()) {
                $courier->update([
                    'average_rating' => round($ratings->avg(), 2),
                    'total_ratings' => $ratings->count(),
                ]);
            }
        }

        // Mettre à jour les moyennes des clients
        $clients = User::where('role', 'client')->get();
        foreach ($clients as $client) {
            $ratings = Order::where('client_id', $client->id)
                ->whereNotNull('client_rating')
                ->pluck('client_rating');

            if ($ratings->isNotEmpty()) {
                $client->update([
                    'average_rating' => round($ratings->avg(), 2),
                    'total_ratings' => $ratings->count(),
                ]);
            }
        }
    }

    private function displaySummary(): void
    {
        $courierRatings = Order::whereNotNull('courier_rating');
        $clientRatings = Order::whereNotNull('client_rating');

        $this->command->newLine();
        $this->command->info('⭐ Résumé des notations:');
        $this->command->table(
            ['Type', 'Nombre', 'Moyenne'],
            [
                ['Client → Coursier', $courierRatings->count(), number_format($courierRatings->avg('courier_rating'), 2)],
                ['Coursier → Client', $clientRatings->count(), number_format($clientRatings->avg('client_rating'), 2)],
            ]
        );

        // Distribution des notes coursiers
        $distribution = Order::whereNotNull('courier_rating')
            ->selectRaw('courier_rating, COUNT(*) as count')
            ->groupBy('courier_rating')
            ->orderBy('courier_rating', 'desc')
            ->pluck('count', 'courier_rating')
            ->toArray();

        $this->command->newLine();
        $this->command->info('📊 Distribution des notes coursiers:');
        foreach ([5, 4, 3, 2, 1] as $star) {
            $count = $distribution[$star] ?? 0;
            $bar = str_repeat('⭐', $star) . str_repeat('☆', 5 - $star);
            $this->command->line("  {$bar} : {$count}");
        }
    }
}

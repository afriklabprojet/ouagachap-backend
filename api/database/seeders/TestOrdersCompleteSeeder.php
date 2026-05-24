<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TestOrdersCompleteSeeder extends Seeder
{
    /**
     * Adresses réalistes à Ouagadougou
     */
    private array $pickupAddresses = [
        ['address' => 'Restaurant Le Délice, Avenue Kwame Nkrumah, Ouaga 2000', 'lat' => 12.3569, 'lng' => -1.5149, 'name' => 'Chef Amadou', 'phone' => '+22670111222'],
        ['address' => 'Pharmacie du Centre, Rue de la Chance, Secteur 4', 'lat' => 12.3672, 'lng' => -1.5245, 'name' => 'Dr Kaboré', 'phone' => '+22670222333'],
        ['address' => 'Supermarché Marina Market, Ouaga 2000', 'lat' => 12.3598, 'lng' => -1.5078, 'name' => 'Caissier Marina', 'phone' => '+22670555666'],
        ['address' => 'Boutique Africaine Mode, Avenue de l\'Indépendance', 'lat' => 12.3645, 'lng' => -1.5312, 'name' => 'Awa Diallo', 'phone' => '+22670777888'],
        ['address' => 'Pâtisserie Délices de France, Zone du Bois', 'lat' => 12.3712, 'lng' => -1.5389, 'name' => 'Pâtissier Jean', 'phone' => '+22670999000'],
        ['address' => 'Librairie Jeunesse d\'Afrique, Rue du Commerce', 'lat' => 12.3623, 'lng' => -1.5287, 'name' => 'Mme Sidibé', 'phone' => '+22671111222'],
        ['address' => 'Électronique Yennenga, Avenue Yennenga', 'lat' => 12.3756, 'lng' => -1.5123, 'name' => 'Technicien Ali', 'phone' => '+22671333444'],
        ['address' => 'Boulangerie La Parisienne, Secteur 15', 'lat' => 12.3834, 'lng' => -1.4967, 'name' => 'Boulanger Pierre', 'phone' => '+22671555666'],
        ['address' => 'Quincaillerie Moderne, Zone Industrielle', 'lat' => 12.3489, 'lng' => -1.5456, 'name' => 'M. Ouédraogo', 'phone' => '+22671777888'],
        ['address' => 'Fleuriste Belle Rose, Cité An III', 'lat' => 12.3701, 'lng' => -1.5201, 'name' => 'Fleuriste Marie', 'phone' => '+22671999000'],
    ];

    private array $dropoffAddresses = [
        ['address' => 'Résidence Palm Beach, Cité An III, Secteur 30', 'lat' => 12.3789, 'lng' => -1.5234, 'name' => 'Mme Ouédraogo', 'phone' => '+22670333444'],
        ['address' => 'Quartier Wemtenga, près de l\'école primaire', 'lat' => 12.3512, 'lng' => -1.4989, 'name' => 'Ibrahim Sanou', 'phone' => '+22670444555'],
        ['address' => 'Villa 245, Secteur 15, Dassasgho', 'lat' => 12.3856, 'lng' => -1.4823, 'name' => 'Fatou Compaoré', 'phone' => '+22670666777'],
        ['address' => 'Hôtel Silmandé, Ouaga 2000', 'lat' => 12.3534, 'lng' => -1.5156, 'name' => 'Marie Dupont', 'phone' => '+22670888999'],
        ['address' => 'Immeuble BICIA, Centre-ville', 'lat' => 12.3678, 'lng' => -1.5234, 'name' => 'Directeur Coulibaly', 'phone' => '+22672111222'],
        ['address' => 'Université de Ouagadougou, Campus Nord', 'lat' => 12.3812, 'lng' => -1.5089, 'name' => 'Étudiant Traoré', 'phone' => '+22672333444'],
        ['address' => 'Clinique Les Genêts, Secteur 9', 'lat' => 12.3567, 'lng' => -1.5345, 'name' => 'Infirmière Binta', 'phone' => '+22672555666'],
        ['address' => 'Ambassade de France, Ouaga 2000', 'lat' => 12.3523, 'lng' => -1.5112, 'name' => 'M. Martin', 'phone' => '+22672777888'],
        ['address' => 'Stade du 4 Août, Tribunes VIP', 'lat' => 12.3634, 'lng' => -1.5267, 'name' => 'Coach Seydou', 'phone' => '+22672999000'],
        ['address' => 'Aéroport International, Terminal', 'lat' => 12.3534, 'lng' => -1.5123, 'name' => 'Passager Sanogo', 'phone' => '+22673111222'],
    ];

    private array $packageDescriptions = [
        ['desc' => 'Repas: 2 poulets braisés + accompagnements', 'size' => 'medium'],
        ['desc' => 'Médicaments urgents', 'size' => 'small'],
        ['desc' => 'Courses: fruits, légumes, produits frais', 'size' => 'large'],
        ['desc' => 'Robe traditionnelle pour événement', 'size' => 'medium'],
        ['desc' => 'Gâteau d\'anniversaire 3 étages', 'size' => 'large'],
        ['desc' => 'Documents juridiques confidentiels', 'size' => 'small'],
        ['desc' => 'Téléphone réparé avec accessoires', 'size' => 'small'],
        ['desc' => 'Pain frais et viennoiseries', 'size' => 'medium'],
        ['desc' => 'Outils de bricolage', 'size' => 'large'],
        ['desc' => 'Bouquet de fleurs premium', 'size' => 'medium'],
        ['desc' => 'Colis express - Ne pas ouvrir', 'size' => 'small'],
        ['desc' => 'Ordinateur portable pour réparation', 'size' => 'medium'],
        ['desc' => 'Vêtements traditionnels', 'size' => 'medium'],
        ['desc' => 'Plat de riz au gras', 'size' => 'medium'],
        ['desc' => 'Pizza familiale + boissons', 'size' => 'large'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clients = User::where('role', 'client')->where('status', 'active')->get();
        $couriers = User::where('role', 'courier')->where('status', 'active')->get();
        $zone = Zone::first();

        if ($clients->isEmpty() || $couriers->isEmpty()) {
            $this->command->error('❌ Aucun client ou coursier trouvé. Exécutez d\'abord TestUsersSeeder.');
            return;
        }

        $this->command->info('📦 Création des commandes de test...');
        $this->command->newLine();

        // Commandes en attente (PENDING)
        $this->createOrdersWithStatus(OrderStatus::PENDING, 5, $clients, null, $zone);

        // Commandes assignées (ASSIGNED)
        $this->createOrdersWithStatus(OrderStatus::ASSIGNED, 4, $clients, $couriers, $zone);

        // Commandes en récupération (PICKED_UP)
        $this->createOrdersWithStatus(OrderStatus::PICKED_UP, 3, $clients, $couriers, $zone);

        // Commandes livrées (DELIVERED) - avec paiements
        $this->createOrdersWithStatus(OrderStatus::DELIVERED, 15, $clients, $couriers, $zone, true);

        // Commandes annulées (CANCELLED)
        $this->createOrdersWithStatus(OrderStatus::CANCELLED, 3, $clients, $couriers, $zone);

        $this->command->newLine();
        $this->displaySummary();
    }

    private function createOrdersWithStatus(
        OrderStatus $status,
        int $count,
        $clients,
        $couriers,
        $zone,
        bool $withPayment = false
    ): void {
        $statusLabel = $status->value;
        $statusEmoji = match($status) {
            OrderStatus::PENDING => '⏳',
            OrderStatus::ASSIGNED => '👤',
            OrderStatus::PICKED_UP => '🏍️',
            OrderStatus::DELIVERED => '✅',
            OrderStatus::CANCELLED => '❌',
        };

        $this->command->line("{$statusEmoji} Création de {$count} commandes {$statusLabel}...");

        for ($i = 0; $i < $count; $i++) {
            $pickup = $this->pickupAddresses[array_rand($this->pickupAddresses)];
            $dropoff = $this->dropoffAddresses[array_rand($this->dropoffAddresses)];
            $package = $this->packageDescriptions[array_rand($this->packageDescriptions)];
            $client = $clients->random();
            $courier = $status !== OrderStatus::PENDING && $couriers ? $couriers->random() : null;

            $distance = $this->calculateDistance(
                $pickup['lat'], $pickup['lng'],
                $dropoff['lat'], $dropoff['lng']
            );

            $basePrice = $zone->base_price ?? 500;
            $pricePerKm = $zone->price_per_km ?? 200;
            $distancePrice = $distance * $pricePerKm;
            $totalPrice = $basePrice + $distancePrice;
            $commission = $totalPrice * 0.15; // 15% commission
            $courierEarnings = $totalPrice - $commission;

            // Dates réalistes
            $createdAt = now()->subDays(rand(0, 30))->subHours(rand(0, 23));
            $assignedAt = $status !== OrderStatus::PENDING ? $createdAt->copy()->addMinutes(rand(5, 30)) : null;
            $pickedUpAt = in_array($status, [OrderStatus::PICKED_UP, OrderStatus::DELIVERED]) 
                ? $assignedAt?->copy()->addMinutes(rand(10, 45)) 
                : null;
            $deliveredAt = $status === OrderStatus::DELIVERED 
                ? $pickedUpAt?->copy()->addMinutes(rand(15, 60)) 
                : null;
            $cancelledAt = $status === OrderStatus::CANCELLED 
                ? $createdAt->copy()->addMinutes(rand(5, 120)) 
                : null;

            $order = Order::create([
                'id' => Str::uuid(),
                'order_number' => 'OC' . now()->format('ymd') . strtoupper(Str::random(4)),
                'client_id' => $client->id,
                'courier_id' => $courier?->id,
                'zone_id' => $zone?->id,
                'status' => $status,
                
                'pickup_address' => $pickup['address'],
                'pickup_latitude' => $pickup['lat'],
                'pickup_longitude' => $pickup['lng'],
                'pickup_contact_name' => $pickup['name'],
                'pickup_contact_phone' => $pickup['phone'],
                
                'dropoff_address' => $dropoff['address'],
                'dropoff_latitude' => $dropoff['lat'],
                'dropoff_longitude' => $dropoff['lng'],
                'dropoff_contact_name' => $dropoff['name'],
                'dropoff_contact_phone' => $dropoff['phone'],
                
                'package_description' => $package['desc'],
                'package_size' => $package['size'],
                
                'distance_km' => round($distance, 2),
                'base_price' => $basePrice,
                'distance_price' => round($distancePrice, 2),
                'total_price' => round($totalPrice, 2),
                'commission_amount' => round($commission, 2),
                'courier_earnings' => round($courierEarnings, 2),
                
                'assigned_at' => $assignedAt,
                'picked_up_at' => $pickedUpAt,
                'delivered_at' => $deliveredAt,
                'cancelled_at' => $cancelledAt,
                'cancellation_reason' => $status === OrderStatus::CANCELLED 
                    ? $this->getRandomCancellationReason() 
                    : null,
                
                'created_at' => $createdAt,
                'updated_at' => $deliveredAt ?? $cancelledAt ?? $pickedUpAt ?? $assignedAt ?? $createdAt,
            ]);

            // Créer un paiement pour les commandes livrées
            if ($withPayment && $status === OrderStatus::DELIVERED) {
                $this->createPayment($order);
            }
        }
    }

    private function createPayment(Order $order): void
    {
        $methods = [PaymentMethod::ORANGE_MONEY, PaymentMethod::MOOV_MONEY, PaymentMethod::CASH];
        $method = $methods[array_rand($methods)];

        $phoneNumber = '+22670' . rand(100000, 999999);

        Payment::create([
            'order_id' => $order->id,
            'user_id' => $order->client_id,
            'amount' => $order->total_price,
            'method' => $method,
            'status' => PaymentStatus::SUCCESS,
            'phone_number' => $phoneNumber,
            'provider_transaction_id' => $method !== PaymentMethod::CASH 
                ? 'TXN' . strtoupper(Str::random(10)) 
                : null,
            'paid_at' => $order->delivered_at,
            'created_at' => $order->created_at,
            'updated_at' => $order->delivered_at,
        ]);
    }

    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    private function getRandomCancellationReason(): string
    {
        $reasons = [
            'Client indisponible au point de récupération',
            'Changement d\'avis du client',
            'Adresse de livraison incorrecte',
            'Problème de paiement',
            'Coursier non disponible dans le délai',
            'Commande en double',
            'Restaurant fermé',
            'Produit non disponible',
        ];

        return $reasons[array_rand($reasons)];
    }

    private function displaySummary(): void
    {
        $stats = Order::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $this->command->table(
            ['Statut', 'Nombre'],
            collect($stats)->map(fn($count, $status) => [$status, $count])->toArray()
        );

        $totalRevenue = Order::where('status', OrderStatus::DELIVERED)->sum('total_price');
        $totalCommission = Order::where('status', OrderStatus::DELIVERED)->sum('commission_amount');

        $this->command->newLine();
        $this->command->info("💰 Chiffre d'affaires total: " . number_format($totalRevenue, 0, ',', ' ') . ' FCFA');
        $this->command->info("💵 Commissions totales: " . number_format($totalCommission, 0, ',', ' ') . ' FCFA');
    }
}

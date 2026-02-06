<?php

namespace Tests\Unit\Repositories;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Order;
use App\Models\User;
use App\Models\Zone;
use App\Repositories\OrderRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected OrderRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new OrderRepository();
    }

    // =========================================================================
    // CLIENT ORDERS TESTS
    // =========================================================================

    public function test_get_client_orders_returns_only_client_orders(): void
    {
        $client = User::factory()->client()->create();
        $otherClient = User::factory()->client()->create();
        
        Order::factory()->count(3)->create(['client_id' => $client->id]);
        Order::factory()->count(2)->create(['client_id' => $otherClient->id]);

        $orders = $this->repository->getClientOrders($client);

        $this->assertCount(3, $orders);
    }

    public function test_get_client_orders_filters_by_status(): void
    {
        $client = User::factory()->client()->create();
        
        Order::factory()->count(2)->create([
            'client_id' => $client->id,
            'status' => OrderStatus::PENDING,
        ]);
        Order::factory()->count(3)->create([
            'client_id' => $client->id,
            'status' => OrderStatus::DELIVERED,
        ]);

        $orders = $this->repository->getClientOrders($client, 'delivered');

        $this->assertCount(3, $orders);
    }

    // =========================================================================
    // AVAILABLE ORDERS TESTS
    // =========================================================================

    public function test_get_available_orders_returns_only_pending_without_courier(): void
    {
        $courier = User::factory()->courier()->create();
        
        // Commandes disponibles
        Order::factory()->count(3)->create([
            'status' => OrderStatus::PENDING,
            'courier_id' => null,
        ]);
        
        // Commande déjà assignée
        Order::factory()->create([
            'status' => OrderStatus::PENDING,
            'courier_id' => $courier->id,
        ]);
        
        // Commande avec autre statut
        Order::factory()->create([
            'status' => OrderStatus::DELIVERED,
            'courier_id' => null,
        ]);

        $orders = $this->repository->getAvailableOrders();

        $this->assertCount(3, $orders);
    }

    public function test_get_available_orders_filters_by_zone(): void
    {
        $zone1 = Zone::factory()->create();
        $zone2 = Zone::factory()->create();
        
        Order::factory()->count(2)->create([
            'status' => OrderStatus::PENDING,
            'courier_id' => null,
            'zone_id' => $zone1->id,
        ]);
        Order::factory()->create([
            'status' => OrderStatus::PENDING,
            'courier_id' => null,
            'zone_id' => $zone2->id,
        ]);

        $orders = $this->repository->getAvailableOrders($zone1->id);

        $this->assertCount(2, $orders);
    }

    // =========================================================================
    // COURIER ACTIVE ORDER TESTS
    // =========================================================================

    public function test_get_courier_active_order_returns_assigned_order(): void
    {
        $courier = User::factory()->courier()->create();
        
        $activeOrder = Order::factory()->create([
            'courier_id' => $courier->id,
            'status' => OrderStatus::ASSIGNED,
        ]);
        
        // Autre commande livrée
        Order::factory()->create([
            'courier_id' => $courier->id,
            'status' => OrderStatus::DELIVERED,
        ]);

        $order = $this->repository->getCourierActiveOrder($courier);

        $this->assertNotNull($order);
        $this->assertEquals($activeOrder->id, $order->id);
    }

    public function test_get_courier_active_order_returns_null_if_none(): void
    {
        $courier = User::factory()->courier()->create();
        
        Order::factory()->create([
            'courier_id' => $courier->id,
            'status' => OrderStatus::DELIVERED,
        ]);

        $order = $this->repository->getCourierActiveOrder($courier);

        $this->assertNull($order);
    }

    // =========================================================================
    // DASHBOARD STATS TESTS
    // =========================================================================

    public function test_get_dashboard_stats_returns_correct_structure(): void
    {
        $stats = $this->repository->getDashboardStats();

        $this->assertArrayHasKey('today', $stats);
        $this->assertArrayHasKey('this_month', $stats);
        $this->assertArrayHasKey('by_status', $stats);
        
        $this->assertArrayHasKey('total', $stats['today']);
        $this->assertArrayHasKey('pending', $stats['today']);
        $this->assertArrayHasKey('delivered', $stats['today']);
        $this->assertArrayHasKey('revenue', $stats['today']);
    }

    public function test_get_dashboard_stats_counts_correctly(): void
    {
        Order::factory()->count(2)->create([
            'status' => OrderStatus::PENDING,
            'created_at' => now(),
        ]);
        Order::factory()->count(3)->create([
            'status' => OrderStatus::DELIVERED,
            'created_at' => now(),
            'total_price' => 1000,
        ]);

        // Clear cache first
        $this->repository->clearDashboardCache();
        
        $stats = $this->repository->getDashboardStats();

        $this->assertEquals(5, $stats['today']['total']);
        $this->assertEquals(2, $stats['today']['pending']);
        $this->assertEquals(3, $stats['today']['delivered']);
        $this->assertEquals(3000, $stats['today']['revenue']);
    }
}

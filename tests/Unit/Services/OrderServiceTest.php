<?php

namespace Tests\Unit\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Models\Zone;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    protected OrderService $orderService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orderService = app(OrderService::class);
    }

    // =========================================================================
    // DISTANCE CALCULATION TESTS
    // =========================================================================

    public function test_calculate_distance_returns_correct_value(): void
    {
        // Distance connue: Centre-ville Ouaga à Ouaga 2000 (~7km)
        $distance = $this->orderService->calculateDistance(
            12.3714, -1.5197, // Centre-ville
            12.3200, -1.4800  // Ouaga 2000
        );

        $this->assertGreaterThan(5, $distance);
        $this->assertLessThan(10, $distance);
    }

    public function test_calculate_distance_same_point_returns_zero(): void
    {
        $distance = $this->orderService->calculateDistance(
            12.3714, -1.5197,
            12.3714, -1.5197
        );

        $this->assertEquals(0, $distance);
    }

    // =========================================================================
    // ESTIMATE TESTS
    // =========================================================================

    public function test_get_estimate_returns_correct_structure(): void
    {
        Zone::create([
            'name' => 'Test Zone',
            'code' => 'TEST',
            'base_price' => 500,
            'price_per_km' => 200,
            'is_active' => true,
        ]);

        $estimate = $this->orderService->getEstimate([
            'pickup_latitude' => 12.3714,
            'pickup_longitude' => -1.5197,
            'dropoff_latitude' => 12.3200,
            'dropoff_longitude' => -1.4800,
        ]);

        $this->assertArrayHasKey('distance_km', $estimate);
        $this->assertArrayHasKey('base_price', $estimate);
        $this->assertArrayHasKey('distance_price', $estimate);
        $this->assertArrayHasKey('total_price', $estimate);
        $this->assertArrayHasKey('commission_amount', $estimate);
        $this->assertArrayHasKey('courier_earnings', $estimate);
        $this->assertArrayHasKey('currency', $estimate);
        $this->assertEquals('XOF', $estimate['currency']);
    }

    public function test_get_estimate_calculates_correct_price(): void
    {
        $zone = Zone::create([
            'name' => 'Test Zone',
            'code' => 'TEST',
            'base_price' => 500,
            'price_per_km' => 100,
            'is_active' => true,
        ]);

        // 0km de distance = base_price seulement
        $estimate = $this->orderService->getEstimate([
            'pickup_latitude' => 12.3714,
            'pickup_longitude' => -1.5197,
            'dropoff_latitude' => 12.3714,
            'dropoff_longitude' => -1.5197,
            'zone_id' => $zone->id,
        ]);

        $this->assertEquals(500, $estimate['base_price']);
        $this->assertEquals(0, $estimate['distance_price']);
        $this->assertEquals(500, $estimate['total_price']);
    }

    public function test_get_estimate_applies_commission(): void
    {
        Zone::create([
            'name' => 'Test Zone',
            'code' => 'TEST',
            'base_price' => 1000,
            'price_per_km' => 0,
            'is_active' => true,
        ]);

        $estimate = $this->orderService->getEstimate([
            'pickup_latitude' => 12.3714,
            'pickup_longitude' => -1.5197,
            'dropoff_latitude' => 12.3714,
            'dropoff_longitude' => -1.5197,
        ]);

        // Commission 15%
        $this->assertEquals(150, $estimate['commission_amount']);
        $this->assertEquals(850, $estimate['courier_earnings']);
    }

    // =========================================================================
    // ORDER CREATION TESTS
    // =========================================================================

    public function test_create_order_with_valid_data(): void
    {
        $client = User::factory()->client()->create();
        
        $zone = Zone::create([
            'name' => 'Test Zone',
            'code' => 'TEST',
            'base_price' => 500,
            'price_per_km' => 200,
            'is_active' => true,
        ]);

        $order = $this->orderService->createOrder($client, [
            'pickup_address' => '123 Rue Test, Ouagadougou',
            'pickup_latitude' => 12.3714,
            'pickup_longitude' => -1.5197,
            'pickup_contact_name' => 'Amadou',
            'pickup_contact_phone' => '+22670123456',
            'dropoff_address' => '456 Avenue Test, Ouagadougou',
            'dropoff_latitude' => 12.3200,
            'dropoff_longitude' => -1.4800,
            'dropoff_contact_name' => 'Fatou',
            'dropoff_contact_phone' => '+22670654321',
            'package_description' => 'Colis test',
            'package_size' => 'small',
            'zone_id' => $zone->id,
        ]);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals($client->id, $order->client_id);
        $this->assertEquals(OrderStatus::PENDING, $order->status);
        $this->assertNotNull($order->order_number);
        $this->assertNotNull($order->recipient_confirmation_code);
    }

    public function test_create_order_generates_unique_order_number(): void
    {
        $client = User::factory()->client()->create();
        
        Zone::create([
            'name' => 'Test Zone',
            'code' => 'TEST',
            'base_price' => 500,
            'price_per_km' => 200,
            'is_active' => true,
        ]);

        $orderData = [
            'pickup_address' => 'Test Address',
            'pickup_latitude' => 12.3714,
            'pickup_longitude' => -1.5197,
            'pickup_contact_name' => 'Test',
            'pickup_contact_phone' => '+22670123456',
            'dropoff_address' => 'Test Dropoff',
            'dropoff_latitude' => 12.3200,
            'dropoff_longitude' => -1.4800,
            'dropoff_contact_name' => 'Test2',
            'dropoff_contact_phone' => '+22670654321',
            'package_description' => 'Test package',
        ];

        $order1 = $this->orderService->createOrder($client, $orderData);
        $order2 = $this->orderService->createOrder($client, $orderData);

        $this->assertNotEquals($order1->order_number, $order2->order_number);
    }

    public function test_create_order_creates_status_history(): void
    {
        $client = User::factory()->client()->create();
        
        Zone::create([
            'name' => 'Test Zone',
            'code' => 'TEST',
            'base_price' => 500,
            'price_per_km' => 200,
            'is_active' => true,
        ]);

        $order = $this->orderService->createOrder($client, [
            'pickup_address' => 'Test',
            'pickup_latitude' => 12.3714,
            'pickup_longitude' => -1.5197,
            'pickup_contact_name' => 'Test',
            'pickup_contact_phone' => '+22670123456',
            'dropoff_address' => 'Test',
            'dropoff_latitude' => 12.3200,
            'dropoff_longitude' => -1.4800,
            'dropoff_contact_name' => 'Test',
            'dropoff_contact_phone' => '+22670654321',
            'package_description' => 'Test',
        ]);

        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'status' => OrderStatus::PENDING->value,
        ]);
    }

    public function test_create_order_links_recipient_user_if_exists(): void
    {
        $client = User::factory()->client()->create();
        $recipient = User::factory()->client()->create(['phone' => '+22670654321']);
        
        Zone::create([
            'name' => 'Test Zone',
            'code' => 'TEST',
            'base_price' => 500,
            'price_per_km' => 200,
            'is_active' => true,
        ]);

        $order = $this->orderService->createOrder($client, [
            'pickup_address' => 'Test',
            'pickup_latitude' => 12.3714,
            'pickup_longitude' => -1.5197,
            'pickup_contact_name' => 'Test',
            'pickup_contact_phone' => '+22670123456',
            'dropoff_address' => 'Test',
            'dropoff_latitude' => 12.3200,
            'dropoff_longitude' => -1.4800,
            'dropoff_contact_name' => 'Test',
            'dropoff_contact_phone' => '70654321', // Sans préfixe
            'package_description' => 'Test',
        ]);

        $this->assertEquals($recipient->id, $order->recipient_user_id);
    }

    // =========================================================================
    // COURIER ORDER RETRIEVAL TESTS
    // =========================================================================

    public function test_get_client_orders_returns_only_client_orders(): void
    {
        $client = User::factory()->client()->create();
        $otherClient = User::factory()->client()->create();
        
        Order::factory()->count(3)->create(['client_id' => $client->id]);
        Order::factory()->count(2)->create(['client_id' => $otherClient->id]);

        $orders = $this->orderService->getClientOrders($client);

        $this->assertEquals(3, $orders->total());
    }

    public function test_get_client_orders_filters_by_status(): void
    {
        $client = User::factory()->client()->create();
        
        Order::factory()->count(2)->create([
            'client_id' => $client->id,
            'status' => OrderStatus::PENDING,
        ]);
        Order::factory()->create([
            'client_id' => $client->id,
            'status' => OrderStatus::DELIVERED,
        ]);

        // Use string status as the method expects
        $pendingOrders = $this->orderService->getClientOrders($client, 'pending');

        $this->assertEquals(2, $pendingOrders->total());
    }

    public function test_get_courier_orders_returns_only_assigned_orders(): void
    {
        $courier = User::factory()->courier()->create();
        $otherCourier = User::factory()->courier()->create();
        
        Order::factory()->count(2)->create(['courier_id' => $courier->id]);
        Order::factory()->create(['courier_id' => $otherCourier->id]);
        Order::factory()->create(['courier_id' => null]); // Unassigned

        $orders = $this->orderService->getCourierOrders($courier);

        $this->assertEquals(2, $orders->total());
    }

    public function test_get_available_orders_excludes_assigned_orders(): void
    {
        $courier = User::factory()->courier()->create();
        
        Order::factory()->count(2)->create([
            'status' => OrderStatus::PENDING,
            'courier_id' => null,
        ]);
        Order::factory()->create([
            'status' => OrderStatus::PENDING,
            'courier_id' => $courier->id,
        ]);

        $availableOrders = $this->orderService->getAvailableOrders();

        $this->assertEquals(2, $availableOrders->total());
    }

    // =========================================================================
    // COURIER ASSIGNMENT TESTS
    // =========================================================================

    public function test_assign_courier_success(): void
    {
        $client = User::factory()->client()->create();
        $courier = User::factory()->courier()->active()->create([
            'is_available' => true,
        ]);
        
        $order = Order::factory()->create([
            'client_id' => $client->id,
            'status' => OrderStatus::PENDING,
            'courier_id' => null,
        ]);

        $result = $this->orderService->assignOrder($order, $courier);

        $this->assertTrue($result['success']);
        $this->assertEquals($courier->id, $order->fresh()->courier_id);
    }

    public function test_assign_courier_fails_for_non_pending_order(): void
    {
        $courier = User::factory()->courier()->active()->create([
            'is_available' => true,
        ]);
        
        $order = Order::factory()->create([
            'status' => OrderStatus::PICKED_UP, // Not PENDING
        ]);

        $result = $this->orderService->assignOrder($order, $courier);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('ne peut plus être acceptée', $result['message']);
    }

    public function test_assign_courier_fails_when_courier_has_active_delivery(): void
    {
        $courier = User::factory()->courier()->active()->create([
            'is_available' => true,
        ]);
        
        // Courier already has an active order
        Order::factory()->create([
            'courier_id' => $courier->id,
            'status' => OrderStatus::PICKED_UP,
        ]);
        
        $newOrder = Order::factory()->create([
            'status' => OrderStatus::PENDING,
            'courier_id' => null,
        ]);

        $result = $this->orderService->assignOrder($newOrder, $courier);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('livraison en cours', $result['message']);
    }

    public function test_assign_courier_by_id_with_invalid_order(): void
    {
        $courier = User::factory()->courier()->create();

        $result = $this->orderService->assignCourier(999999, $courier->id);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Commande non trouvée', $result['message']);
    }

    public function test_assign_courier_by_id_with_invalid_courier(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::PENDING]);

        $result = $this->orderService->assignCourier($order->id, 999999);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Coursier non trouvé', $result['message']);
    }

    // =========================================================================
    // STATUS TRANSITION TESTS
    // =========================================================================

    public function test_update_status_to_picked_up(): void
    {
        $courier = User::factory()->courier()->create();
        $order = Order::factory()->create([
            'courier_id' => $courier->id,
            'status' => OrderStatus::ASSIGNED,
        ]);

        $result = $this->orderService->updateStatus(
            $order,
            OrderStatus::PICKED_UP,
            $courier
        );

        $this->assertTrue($result['success']);
        $this->assertEquals(OrderStatus::PICKED_UP, $order->fresh()->status);
    }

    public function test_update_status_to_delivered(): void
    {
        $courier = User::factory()->courier()->create();
        $order = Order::factory()->create([
            'courier_id' => $courier->id,
            'status' => OrderStatus::PICKED_UP,
        ]);

        $result = $this->orderService->updateStatus(
            $order,
            OrderStatus::DELIVERED,
            $courier,
            null,
            12.3714,
            -1.5197
        );

        $this->assertTrue($result['success']);
        $this->assertEquals(OrderStatus::DELIVERED, $order->fresh()->status);
    }

    public function test_update_status_fails_for_invalid_transition(): void
    {
        $user = User::factory()->client()->create();
        $order = Order::factory()->create([
            'status' => OrderStatus::PENDING,
        ]);

        // Cannot go from PENDING directly to DELIVERED
        $result = $this->orderService->updateStatus(
            $order,
            OrderStatus::DELIVERED,
            $user
        );

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('transition', $result['message']);
    }

    public function test_cancel_order_with_note(): void
    {
        $client = User::factory()->client()->create();
        $order = Order::factory()->create([
            'client_id' => $client->id,
            'status' => OrderStatus::PENDING,
        ]);

        $result = $this->orderService->updateStatus(
            $order,
            OrderStatus::CANCELLED,
            $client,
            'Client a changé d\'avis'
        );

        $this->assertTrue($result['success']);
        $this->assertEquals(OrderStatus::CANCELLED, $order->fresh()->status);
    }

    // =========================================================================
    // ORDER DETAILS TESTS
    // =========================================================================

    public function test_get_order_details_loads_all_relations(): void
    {
        $client = User::factory()->client()->create();
        $courier = User::factory()->courier()->create();
        $zone = Zone::create([
            'name' => 'Test Zone',
            'code' => 'TEST',
            'base_price' => 500,
            'price_per_km' => 200,
            'is_active' => true,
        ]);
        
        $order = Order::factory()->create([
            'client_id' => $client->id,
            'courier_id' => $courier->id,
            'zone_id' => $zone->id,
        ]);

        $orderDetails = $this->orderService->getOrderDetails($order->id);

        $this->assertNotNull($orderDetails);
        $this->assertTrue($orderDetails->relationLoaded('client'));
        $this->assertTrue($orderDetails->relationLoaded('courier'));
        $this->assertTrue($orderDetails->relationLoaded('zone'));
        $this->assertTrue($orderDetails->relationLoaded('statusHistories'));
    }

    public function test_get_order_details_returns_null_for_invalid_id(): void
    {
        $orderDetails = $this->orderService->getOrderDetails('invalid-uuid');

        $this->assertNull($orderDetails);
    }

    // =========================================================================
    // AVAILABLE ORDERS FOR COURIER LOCATION TESTS
    // =========================================================================

    public function test_get_available_orders_for_courier_filters_by_radius(): void
    {
        // Order proche (centre-ville Ouaga)
        Order::factory()->create([
            'status' => OrderStatus::PENDING,
            'courier_id' => null,
            'pickup_latitude' => 12.3714,
            'pickup_longitude' => -1.5197,
        ]);

        // Order loin (hors rayon)
        Order::factory()->create([
            'status' => OrderStatus::PENDING,
            'courier_id' => null,
            'pickup_latitude' => 12.5000, // ~15km away
            'pickup_longitude' => -1.3000,
        ]);

        $orders = $this->orderService->getAvailableOrdersForCourier(
            12.3714, // Position coursier centre-ville
            -1.5197,
            5 // Rayon 5km
        );

        // Seule la commande proche devrait être retournée
        $this->assertCount(1, $orders);
    }

    public function test_get_available_orders_for_courier_sorts_by_distance(): void
    {
        // Order proche
        Order::factory()->create([
            'status' => OrderStatus::PENDING,
            'courier_id' => null,
            'pickup_latitude' => 12.3720, // Très proche
            'pickup_longitude' => -1.5190,
        ]);

        // Order moyennement proche
        Order::factory()->create([
            'status' => OrderStatus::PENDING,
            'courier_id' => null,
            'pickup_latitude' => 12.3500, // ~2km
            'pickup_longitude' => -1.5000,
        ]);

        $orders = $this->orderService->getAvailableOrdersForCourier(
            12.3714,
            -1.5197,
            10
        );

        $this->assertGreaterThanOrEqual(2, count($orders));
        // Premier devrait être le plus proche
        if (count($orders) >= 2) {
            $this->assertLessThan($orders[1]['distance'], $orders[0]['distance']);
        }
    }

    public function test_get_available_orders_for_courier_without_location(): void
    {
        Order::factory()->count(3)->create([
            'status' => OrderStatus::PENDING,
            'courier_id' => null,
        ]);

        $orders = $this->orderService->getAvailableOrdersForCourier();

        $this->assertCount(3, $orders);
    }
}

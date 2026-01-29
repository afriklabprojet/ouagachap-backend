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
}

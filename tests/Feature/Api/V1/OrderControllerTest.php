<?php

namespace Tests\Feature\Api\V1;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $client;
    protected User $courier;
    protected Zone $zone;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = User::factory()->create(['role' => UserRole::CLIENT]);
        $this->courier = User::factory()->create([
            'role' => UserRole::COURIER,
            'is_available' => true,
            'current_latitude' => 12.3714,
            'current_longitude' => -1.5197,
        ]);
        $this->zone = Zone::factory()->create();
    }

    // =========================================================================
    // ESTIMATE TESTS
    // =========================================================================

    public function test_client_can_get_delivery_estimate(): void
    {
        $response = $this->actingAs($this->client, 'sanctum')
            ->postJson('/api/v1/orders/estimate', [
                'pickup_latitude' => '12.371400',
                'pickup_longitude' => '-1.519700',
                'dropoff_latitude' => '12.380000',
                'dropoff_longitude' => '-1.510000',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'distance_km',
                    'base_price',
                    'distance_price',
                    'total_price',
                    'currency',
                ],
            ]);
    }

    // =========================================================================
    // CREATE ORDER TESTS
    // =========================================================================

    public function test_client_can_create_order(): void
    {
        $response = $this->actingAs($this->client, 'sanctum')
            ->postJson('/api/v1/orders', [
                'pickup_address' => '123 Rue Test, Ouagadougou',
                'pickup_latitude' => '12.371400',
                'pickup_longitude' => '-1.519700',
                'pickup_contact_name' => 'Jean Dupont',
                'pickup_contact_phone' => '70123456',
                'dropoff_address' => '456 Avenue Test, Ouagadougou',
                'dropoff_latitude' => '12.380000',
                'dropoff_longitude' => '-1.510000',
                'dropoff_contact_name' => 'Marie Dupont',
                'dropoff_contact_phone' => '70654321',
                'package_description' => 'Documents importants',
                'package_size' => 'small',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'order_number',
                    'status',
                    'total_price',
                ],
            ]);

        $this->assertDatabaseHas('orders', [
            'client_id' => $this->client->id,
            'status' => OrderStatus::PENDING->value,
        ]);
    }

    public function test_courier_cannot_create_order(): void
    {
        $response = $this->actingAs($this->courier, 'sanctum')
            ->postJson('/api/v1/orders', [
                'pickup_address' => '123 Rue Test',
                'pickup_latitude' => 12.3714,
                'pickup_longitude' => -1.5197,
                'pickup_contact_name' => 'Jean',
                'pickup_contact_phone' => '70123456',
                'dropoff_address' => '456 Avenue Test',
                'dropoff_latitude' => 12.3800,
                'dropoff_longitude' => -1.5100,
                'dropoff_contact_name' => 'Marie',
                'dropoff_contact_phone' => '70654321',
                'package_description' => 'Test',
            ]);

        $response->assertStatus(403);
    }

    // =========================================================================
    // LIST ORDERS TESTS
    // =========================================================================

    public function test_client_can_list_own_orders(): void
    {
        Order::factory()->count(3)->create(['client_id' => $this->client->id]);
        Order::factory()->count(2)->create(); // Other client's orders

        $response = $this->actingAs($this->client, 'sanctum')
            ->getJson('/api/v1/orders');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_client_cannot_see_other_orders(): void
    {
        $otherClient = User::factory()->create(['role' => UserRole::CLIENT]);
        $order = Order::factory()->create(['client_id' => $otherClient->id]);

        $response = $this->actingAs($this->client, 'sanctum')
            ->getJson("/api/v1/orders/{$order->id}");

        $response->assertStatus(403);
    }

    // =========================================================================
    // ORDER STATUS TESTS
    // =========================================================================

    public function test_client_can_cancel_pending_order(): void
    {
        $order = Order::factory()->create([
            'client_id' => $this->client->id,
            'status' => OrderStatus::PENDING,
        ]);

        $response = $this->actingAs($this->client, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/cancel", [
                'reason' => 'Changed my mind',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::CANCELLED->value,
        ]);
    }

    public function test_client_cannot_cancel_delivered_order(): void
    {
        $order = Order::factory()->create([
            'client_id' => $this->client->id,
            'status' => OrderStatus::DELIVERED,
        ]);

        $response = $this->actingAs($this->client, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/cancel");

        $response->assertStatus(422);
    }

    // =========================================================================
    // COURIER ORDER ACCEPTANCE TESTS
    // =========================================================================

    public function test_courier_can_accept_available_order(): void
    {
        $order = Order::factory()->create([
            'client_id' => $this->client->id,
            'status' => OrderStatus::PENDING,
        ]);

        $response = $this->actingAs($this->courier, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/accept");

        $response->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'courier_id' => $this->courier->id,
            'status' => OrderStatus::ASSIGNED->value,
        ]);
    }

    public function test_courier_can_update_order_status(): void
    {
        $order = Order::factory()->create([
            'client_id' => $this->client->id,
            'courier_id' => $this->courier->id,
            'status' => OrderStatus::ASSIGNED,
        ]);

        $response = $this->actingAs($this->courier, 'sanctum')
            ->putJson("/api/v1/orders/{$order->id}/status", [
                'status' => 'picked_up',
                'latitude' => 12.3714,
                'longitude' => -1.5197,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::PICKED_UP->value,
        ]);
    }

    // =========================================================================
    // AVAILABLE ORDERS FOR COURIERS
    // =========================================================================

    public function test_courier_can_list_available_orders(): void
    {
        Order::factory()->count(3)->create(['status' => OrderStatus::PENDING]);
        Order::factory()->count(2)->create(['status' => OrderStatus::DELIVERED]);

        $response = $this->actingAs($this->courier, 'sanctum')
            ->getJson('/api/v1/orders/available');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }
}

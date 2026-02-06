<?php

namespace Tests\Feature\Api\V1;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourierControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $courier;
    protected User $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->courier = User::factory()->create([
            'role' => UserRole::COURIER,
            'status' => UserStatus::ACTIVE,
            'is_available' => true,
            'current_latitude' => 12.3714,
            'current_longitude' => -1.5197,
        ]);

        $this->client = User::factory()->create(['role' => UserRole::CLIENT]);
    }

    // =========================================================================
    // LOCATION UPDATE TESTS
    // =========================================================================

    public function test_courier_can_update_location(): void
    {
        $response = $this->actingAs($this->courier, 'sanctum')
            ->putJson('/api/v1/courier/location', [
                'latitude' => 12.3800,
                'longitude' => -1.5100,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $this->courier->id,
            'current_latitude' => 12.3800,
            'current_longitude' => -1.5100,
        ]);
    }

    public function test_client_cannot_update_courier_location(): void
    {
        $response = $this->actingAs($this->client, 'sanctum')
            ->putJson('/api/v1/courier/location', [
                'latitude' => 12.3800,
                'longitude' => -1.5100,
            ]);

        $response->assertStatus(403);
    }

    // =========================================================================
    // AVAILABILITY TESTS
    // =========================================================================

    public function test_courier_can_toggle_availability(): void
    {
        $response = $this->actingAs($this->courier, 'sanctum')
            ->putJson('/api/v1/courier/availability', [
                'is_available' => false,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $this->courier->id,
            'is_available' => false,
        ]);
    }

    // =========================================================================
    // DASHBOARD TESTS
    // =========================================================================

    public function test_courier_can_access_dashboard(): void
    {
        // Create some orders for the courier
        Order::factory()->count(5)->create([
            'courier_id' => $this->courier->id,
            'status' => OrderStatus::DELIVERED,
        ]);

        $response = $this->actingAs($this->courier, 'sanctum')
            ->getJson('/api/v1/courier/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_orders',
                    'wallet_balance',
                    'average_rating',
                    'today',
                    'this_week',
                    'this_month',
                ],
            ]);
    }

    // =========================================================================
    // CURRENT ORDER TESTS
    // =========================================================================

    public function test_courier_can_get_current_order(): void
    {
        $order = Order::factory()->create([
            'client_id' => $this->client->id,
            'courier_id' => $this->courier->id,
            'status' => OrderStatus::ASSIGNED,
        ]);

        $response = $this->actingAs($this->courier, 'sanctum')
            ->getJson('/api/v1/courier/current-order');

        $response->assertStatus(200)
            ->assertJsonPath('data.id', (string) $order->id);
    }

    public function test_courier_returns_null_when_no_current_order(): void
    {
        $response = $this->actingAs($this->courier, 'sanctum')
            ->getJson('/api/v1/courier/current-order');

        $response->assertStatus(200)
            ->assertJsonPath('data', null);
    }

    // =========================================================================
    // EARNINGS TESTS
    // =========================================================================

    public function test_courier_can_view_earnings(): void
    {
        Order::factory()->count(3)->create([
            'courier_id' => $this->courier->id,
            'status' => OrderStatus::DELIVERED,
            'courier_earnings' => 500,
        ]);

        $response = $this->actingAs($this->courier, 'sanctum')
            ->getJson('/api/v1/courier/earnings');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'meta',
            ]);
    }

    // =========================================================================
    // COURIER ORDERS HISTORY
    // =========================================================================

    public function test_courier_can_view_order_history(): void
    {
        Order::factory()->count(5)->create([
            'courier_id' => $this->courier->id,
            'status' => OrderStatus::DELIVERED,
        ]);

        $response = $this->actingAs($this->courier, 'sanctum')
            ->getJson('/api/v1/courier/orders');

        $response->assertStatus(200);
        $this->assertCount(5, $response->json('data'));
    }

    // =========================================================================
    // NEARBY COURIERS (PUBLIC) - Requires MySQL for Haversine formula
    // =========================================================================

    /**
     * @group mysql
     */
    public function test_can_find_nearby_couriers(): void
    {
        // Skip on SQLite (no trig functions)
        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('This test requires MySQL for Haversine formula.');
        }

        // Create available couriers
        User::factory()->count(3)->create([
            'role' => UserRole::COURIER,
            'status' => UserStatus::ACTIVE,
            'is_available' => true,
            'current_latitude' => 12.3714,
            'current_longitude' => -1.5197,
        ]);

        $response = $this->getJson('/api/v1/couriers/nearby?latitude=12.3700&longitude=-1.5200');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    }

    // =========================================================================
    // INACTIVE COURIER TESTS
    // =========================================================================

    public function test_suspended_courier_cannot_accept_orders(): void
    {
        $suspendedCourier = User::factory()->create([
            'role' => UserRole::COURIER,
            'status' => UserStatus::SUSPENDED,
        ]);

        $order = Order::factory()->create([
            'client_id' => $this->client->id,
            'status' => OrderStatus::PENDING,
        ]);

        $response = $this->actingAs($suspendedCourier, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/accept");

        $response->assertStatus(403);
    }
}

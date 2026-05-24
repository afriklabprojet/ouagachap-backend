<?php

namespace Tests\Unit\Services;

use App\Enums\OrderStatus;
use App\Enums\UserStatus;
use App\Models\Order;
use App\Models\User;
use App\Services\CourierService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourierServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CourierService $courierService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->courierService = app(CourierService::class);
    }

    // =========================================================================
    // LOCATION UPDATE TESTS
    // =========================================================================

    public function test_update_location_saves_coordinates(): void
    {
        $courier = User::factory()->courier()->create([
            'current_latitude' => null,
            'current_longitude' => null,
        ]);

        $result = $this->courierService->updateLocation($courier, 12.3714, -1.5197);

        $this->assertTrue($result['success']);
        $this->assertEquals(12.3714, $courier->fresh()->current_latitude);
        $this->assertEquals(-1.5197, $courier->fresh()->current_longitude);
    }

    public function test_update_location_returns_correct_structure(): void
    {
        $courier = User::factory()->courier()->create();

        $result = $this->courierService->updateLocation($courier, 12.3714, -1.5197);

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('location', $result);
        $this->assertArrayHasKey('latitude', $result['location']);
        $this->assertArrayHasKey('longitude', $result['location']);
        $this->assertArrayHasKey('updated_at', $result['location']);
    }

    // =========================================================================
    // AVAILABILITY TESTS
    // =========================================================================

    public function test_update_availability_to_online_for_active_courier(): void
    {
        $courier = User::factory()->courier()->active()->create([
            'is_available' => false,
            'wallet_balance' => 2000,
        ]);

        $result = $this->courierService->updateAvailability($courier, true);

        $this->assertTrue($result['success']);
        $this->assertTrue($courier->fresh()->is_available);
        $this->assertStringContainsString('en ligne', $result['message']);
    }

    public function test_update_availability_to_offline(): void
    {
        $courier = User::factory()->courier()->active()->create([
            'is_available' => true,
        ]);

        $result = $this->courierService->updateAvailability($courier, false);

        $this->assertTrue($result['success']);
        $this->assertFalse($courier->fresh()->is_available);
        $this->assertStringContainsString('hors ligne', $result['message']);
    }

    public function test_update_availability_fails_for_inactive_courier(): void
    {
        $courier = User::factory()->courier()->create([
            'status' => UserStatus::PENDING,
            'is_available' => false,
        ]);

        $result = $this->courierService->updateAvailability($courier, true);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('actif', $result['message']);
    }

    public function test_update_availability_fails_for_suspended_courier(): void
    {
        $courier = User::factory()->courier()->create([
            'status' => UserStatus::SUSPENDED,
            'is_available' => false,
        ]);

        $result = $this->courierService->updateAvailability($courier, true);

        $this->assertFalse($result['success']);
    }

    // =========================================================================
    // COURIER STATS TESTS (Using actual method structure)
    // =========================================================================

    public function test_get_courier_stats_returns_correct_structure(): void
    {
        $courier = User::factory()->courier()->active()->create();

        $stats = $this->courierService->getCourierStats($courier);

        // Vérification de la structure réelle retournée
        $this->assertArrayHasKey('today', $stats);
        $this->assertArrayHasKey('this_week', $stats);
        $this->assertArrayHasKey('this_month', $stats);
        $this->assertArrayHasKey('average_rating', $stats);
        $this->assertArrayHasKey('total_orders', $stats);

        // Vérification nested structure
        $this->assertArrayHasKey('orders', $stats['today']);
        $this->assertArrayHasKey('earnings', $stats['today']);
    }

    public function test_get_courier_stats_counts_today_orders(): void
    {
        $courier = User::factory()->courier()->active()->create();

        // 3 commandes aujourd'hui
        Order::factory()->count(3)->create([
            'courier_id' => $courier->id,
            'created_at' => today(),
        ]);

        $stats = $this->courierService->getCourierStats($courier);

        $this->assertEquals(3, $stats['today']['orders']);
    }

    public function test_get_courier_stats_calculates_earnings(): void
    {
        $courier = User::factory()->courier()->active()->create();

        // Commandes livrées avec gains
        Order::factory()->create([
            'courier_id' => $courier->id,
            'status' => OrderStatus::DELIVERED,
            'courier_earnings' => 1000,
            'delivered_at' => today(),
        ]);

        Order::factory()->create([
            'courier_id' => $courier->id,
            'status' => OrderStatus::DELIVERED,
            'courier_earnings' => 1500,
            'delivered_at' => today(),
        ]);

        $stats = $this->courierService->getCourierStats($courier);

        $this->assertEquals(2500, $stats['today']['earnings']);
    }

    // =========================================================================
    // COURIER MANAGEMENT TESTS
    // =========================================================================

    public function test_approve_courier_sets_status_to_active(): void
    {
        $courier = User::factory()->courier()->create([
            'status' => UserStatus::PENDING,
        ]);

        $result = $this->courierService->approveCourier($courier);

        $this->assertTrue($result['success']);
        $this->assertEquals(UserStatus::ACTIVE, $courier->fresh()->status);
    }

    public function test_approve_courier_fails_for_non_courier(): void
    {
        $client = User::factory()->client()->create();

        $result = $this->courierService->approveCourier($client);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('coursier', $result['message']);
    }

    public function test_suspend_courier_sets_status_and_unavailable(): void
    {
        $courier = User::factory()->courier()->active()->create([
            'is_available' => true,
        ]);

        $result = $this->courierService->suspendCourier($courier, 'Non-conformité');

        $this->assertTrue($result['success']);
        $this->assertEquals(UserStatus::SUSPENDED, $courier->fresh()->status);
        $this->assertFalse($courier->fresh()->is_available);
    }

    public function test_suspend_courier_fails_for_non_courier(): void
    {
        $client = User::factory()->client()->create();

        $result = $this->courierService->suspendCourier($client, 'Test');

        $this->assertFalse($result['success']);
    }

    // =========================================================================
    // GET ALL COURIERS TESTS
    // =========================================================================

    public function test_get_all_couriers_returns_only_couriers(): void
    {
        User::factory()->courier()->count(3)->create();
        User::factory()->client()->count(2)->create();

        $couriers = $this->courierService->getAllCouriers();

        $this->assertEquals(3, $couriers->total());
    }

    public function test_get_all_couriers_filters_by_status(): void
    {
        User::factory()->courier()->create(['status' => UserStatus::ACTIVE]);
        User::factory()->courier()->create(['status' => UserStatus::ACTIVE]);
        User::factory()->courier()->create(['status' => UserStatus::PENDING]);

        $activeCouriers = $this->courierService->getAllCouriers('active');

        $this->assertEquals(2, $activeCouriers->total());
    }

    // =========================================================================
    // EARNINGS HISTORY TESTS
    // =========================================================================

    public function test_get_earnings_history_returns_completed_orders(): void
    {
        $courier = User::factory()->courier()->active()->create();

        Order::factory()->count(3)->create([
            'courier_id' => $courier->id,
            'status' => OrderStatus::DELIVERED,
            'delivered_at' => now(),
        ]);

        Order::factory()->create([
            'courier_id' => $courier->id,
            'status' => OrderStatus::PENDING, // Not completed
        ]);

        $history = $this->courierService->getEarningsHistory($courier);

        $this->assertEquals(3, $history->total());
    }

    public function test_get_earnings_history_orders_by_delivered_date(): void
    {
        $courier = User::factory()->courier()->active()->create();

        $older = Order::factory()->create([
            'courier_id' => $courier->id,
            'status' => OrderStatus::DELIVERED,
            'delivered_at' => now()->subDay(),
        ]);

        $newer = Order::factory()->create([
            'courier_id' => $courier->id,
            'status' => OrderStatus::DELIVERED,
            'delivered_at' => now(),
        ]);

        $history = $this->courierService->getEarningsHistory($courier);

        $this->assertEquals($newer->id, $history->first()->id);
    }

    // ========== Vehicle score calculation (via reflection) ==========

    public function test_calculate_vehicle_score_moto_small_parcel(): void
    {
        $courier = User::factory()->courier()->create(['vehicle_type' => 'moto']);
        $score = $this->invokeProtected('calculateVehicleScore', $courier, ['is_large' => false, 'weight' => 5]);

        $this->assertGreaterThanOrEqual(80, $score);
    }

    public function test_calculate_vehicle_score_moto_large_parcel_penalized(): void
    {
        $courier = User::factory()->courier()->create(['vehicle_type' => 'moto']);
        $score = $this->invokeProtected('calculateVehicleScore', $courier, ['is_large' => true, 'weight' => 25]);

        $this->assertLessThan(80, $score);
    }

    public function test_calculate_vehicle_score_moto_food_bonus(): void
    {
        $courier = User::factory()->courier()->create(['vehicle_type' => 'moto']);
        $score = $this->invokeProtected('calculateVehicleScore', $courier, ['order_type' => 'food']);

        $this->assertEquals(90, $score); // 80 base + 10 food bonus
    }

    public function test_calculate_vehicle_score_voiture_fragile_bonus(): void
    {
        $courier = User::factory()->courier()->create(['vehicle_type' => 'voiture']);
        $score = $this->invokeProtected('calculateVehicleScore', $courier, ['is_fragile' => true, 'is_large' => true, 'weight' => 35]);

        // 80 base + 20(large) + 15(fragile) + 15(weight>30) = 130, capped at 100
        $this->assertEquals(100, $score);
    }

    public function test_calculate_vehicle_score_capped_at_100(): void
    {
        $courier = User::factory()->courier()->create(['vehicle_type' => 'voiture']);
        $score = $this->invokeProtected('calculateVehicleScore', $courier, ['is_fragile' => true, 'is_large' => true, 'weight' => 50]);

        $this->assertLessThanOrEqual(100, $score);
    }

    public function test_calculate_vehicle_score_camionnette_large_parcel(): void
    {
        $courier = User::factory()->courier()->create(['vehicle_type' => 'camionnette']);
        $score = $this->invokeProtected('calculateVehicleScore', $courier, ['is_large' => true, 'weight' => 60]);

        $this->assertGreaterThan(80, $score);
    }

    public function test_calculate_vehicle_score_camionnette_small_parcel_penalized(): void
    {
        $courier = User::factory()->courier()->create(['vehicle_type' => 'camionnette']);
        $score = $this->invokeProtected('calculateVehicleScore', $courier, ['is_large' => false, 'weight' => 2]);

        $this->assertLessThan(80, $score); // 80 - 20 = 60
    }

    public function test_calculate_vehicle_score_tricycle_medium(): void
    {
        $courier = User::factory()->courier()->create(['vehicle_type' => 'tricycle']);
        $score = $this->invokeProtected('calculateVehicleScore', $courier, ['is_large' => true, 'weight' => 30]);

        $this->assertGreaterThanOrEqual(90, $score); // 80 + 10(large) + 10(weight)
    }

    // ========== Response score ==========

    public function test_calculate_response_score_new_courier_gets_neutral(): void
    {
        $courier = User::factory()->courier()->create();
        $score = $this->invokeProtected('calculateResponseScore', $courier);

        $this->assertEquals(70.0, $score);
    }

    public function test_calculate_response_score_perfect_delivery_history(): void
    {
        $courier = User::factory()->courier()->create();
        Order::factory()->count(5)->create([
            'courier_id' => $courier->id,
            'status' => OrderStatus::DELIVERED,
            'assigned_at' => now()->subHour(),
            'delivered_at' => now(),
        ]);

        $score = $this->invokeProtected('calculateResponseScore', $courier);
        $this->assertEquals(100.0, $score);
    }

    public function test_calculate_response_score_mixed_history(): void
    {
        $courier = User::factory()->courier()->create();
        // 3 delivered, 2 cancelled → 60% acceptance
        Order::factory()->count(3)->create([
            'courier_id' => $courier->id,
            'status' => OrderStatus::DELIVERED,
            'assigned_at' => now()->subHour(),
        ]);
        Order::factory()->count(2)->create([
            'courier_id' => $courier->id,
            'status' => OrderStatus::CANCELLED,
            'assigned_at' => now()->subHour(),
        ]);

        $score = $this->invokeProtected('calculateResponseScore', $courier);
        $this->assertEquals(60.0, $score);
    }

    // ========== Helper to invoke protected methods ==========

    private function invokeProtected(string $method, ...$args): mixed
    {
        $reflection = new \ReflectionMethod($this->courierService, $method);
        $reflection->setAccessible(true);
        return $reflection->invoke($this->courierService, ...$args);
    }
}

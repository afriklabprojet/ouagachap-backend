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
}

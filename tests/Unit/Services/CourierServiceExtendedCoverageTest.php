<?php

namespace Tests\Unit\Services;

use App\Enums\OrderStatus;
use App\Events\CourierLocationUpdated;
use App\Events\OrderTrackingUpdate;
use App\Models\Order;
use App\Models\User;
use App\Services\CourierService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CourierServiceExtendedCoverageTest extends TestCase
{
    use RefreshDatabase;

    protected CourierService $courierService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->courierService = app(CourierService::class);
    }

    // =========================================================================
    // UPDATE LOCATION WITH ACTIVE ORDER → broadcasts tracking update
    // =========================================================================

    public function test_update_location_broadcasts_tracking_for_assigned_order(): void
    {
        Event::fake([CourierLocationUpdated::class, OrderTrackingUpdate::class]);

        $courier = User::factory()->courier()->active()->create();
        $client = User::factory()->client()->create();

        Order::factory()->create([
            'courier_id' => $courier->id,
            'client_id' => $client->id,
            'status' => OrderStatus::ASSIGNED,
            'pickup_latitude' => 12.37,
            'pickup_longitude' => -1.52,
            'dropoff_latitude' => 12.40,
            'dropoff_longitude' => -1.50,
        ]);

        $result = $this->courierService->updateLocation($courier, 12.3714, -1.5197);

        $this->assertTrue($result['success']);
        Event::assertDispatched(CourierLocationUpdated::class);
        Event::assertDispatched(OrderTrackingUpdate::class);
    }

    public function test_update_location_broadcasts_tracking_for_in_transit_order(): void
    {
        Event::fake([CourierLocationUpdated::class, OrderTrackingUpdate::class]);

        $courier = User::factory()->courier()->active()->create();
        $client = User::factory()->client()->create();

        Order::factory()->create([
            'courier_id' => $courier->id,
            'client_id' => $client->id,
            'status' => OrderStatus::IN_TRANSIT,
            'pickup_latitude' => 12.37,
            'pickup_longitude' => -1.52,
            'dropoff_latitude' => 12.40,
            'dropoff_longitude' => -1.50,
        ]);

        $result = $this->courierService->updateLocation($courier, 12.39, -1.51);

        $this->assertTrue($result['success']);
        Event::assertDispatched(OrderTrackingUpdate::class);
    }

    public function test_update_location_no_tracking_without_active_order(): void
    {
        Event::fake([CourierLocationUpdated::class, OrderTrackingUpdate::class]);

        $courier = User::factory()->courier()->active()->create();

        $result = $this->courierService->updateLocation($courier, 12.3714, -1.5197);

        $this->assertTrue($result['success']);
        Event::assertDispatched(CourierLocationUpdated::class);
        Event::assertNotDispatched(OrderTrackingUpdate::class);
    }

    // =========================================================================
    // COURIER STATS EDGE CASES
    // =========================================================================

    public function test_get_courier_stats_zero_when_no_orders(): void
    {
        $courier = User::factory()->courier()->active()->create();

        $stats = $this->courierService->getCourierStats($courier);

        $this->assertEquals(0, $stats['today']['orders']);
        $this->assertEquals(0, $stats['today']['earnings']);
        $this->assertEquals(0, $stats['this_week']['orders']);
        $this->assertEquals(0, $stats['this_week']['earnings']);
        $this->assertEquals(0, $stats['this_month']['orders']);
        $this->assertEquals(0, $stats['this_month']['earnings']);
    }

    public function test_get_courier_stats_includes_week_and_month(): void
    {
        $courier = User::factory()->courier()->active()->create();

        // Order created today (guaranteed to be within this week and month)
        Order::factory()->create([
            'courier_id' => $courier->id,
            'status' => OrderStatus::DELIVERED,
            'courier_earnings' => 2000,
            'delivered_at' => now(),
            'created_at' => now(),
        ]);

        $stats = $this->courierService->getCourierStats($courier);

        $this->assertGreaterThanOrEqual(1, $stats['this_week']['orders']);
        $this->assertGreaterThanOrEqual(1, $stats['this_month']['orders']);
    }

    // =========================================================================
    // AVAILABILITY EDGE CASES
    // =========================================================================

    public function test_update_availability_no_event_when_status_unchanged(): void
    {
        Event::fake();
        $courier = User::factory()->courier()->active()->create(['is_available' => true, 'wallet_balance' => 2000]);

        $result = $this->courierService->updateAvailability($courier, true);

        $this->assertTrue($result['success']);
        // Event should not fire when status doesn't actually change
        Event::assertNotDispatched(\App\Events\CourierWentOnline::class);
    }

    // =========================================================================
    // SUSPEND COURIER
    // =========================================================================

    public function test_suspend_courier_returns_courier_data(): void
    {
        $courier = User::factory()->courier()->active()->create([
            'is_available' => true,
        ]);

        $result = $this->courierService->suspendCourier($courier, 'Violation des conditions');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('courier', $result);
        $this->assertStringContainsString('suspendu', $result['message']);
    }
}

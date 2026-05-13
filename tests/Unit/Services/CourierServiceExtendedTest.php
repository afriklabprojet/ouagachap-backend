<?php

namespace Tests\Unit\Services;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Order;
use App\Models\User;
use App\Services\CourierService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CourierServiceExtendedTest extends TestCase
{
    use RefreshDatabase;

    protected CourierService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CourierService::class);
        Event::fake([
            \App\Events\CourierLocationUpdated::class,
            \App\Events\CourierWentOnline::class,
            \App\Events\OrderTrackingUpdate::class,
        ]);
    }

    /** @test */
    public function update_location_returns_success(): void
    {
        $courier = User::factory()->courier()->create([
            'status' => UserStatus::ACTIVE,
        ]);

        $result = $this->service->updateLocation($courier, 12.3456, -1.5167);

        $this->assertTrue($result['success']);
        $this->assertEquals(12.3456, $result['location']['latitude']);
        $this->assertEquals(-1.5167, $result['location']['longitude']);
    }

    /** @test */
    public function update_availability_to_online_when_active(): void
    {
        $courier = User::factory()->courier()->create([
            'status' => UserStatus::ACTIVE,
            'is_available' => false,
            'wallet_balance' => 2000,
        ]);

        $result = $this->service->updateAvailability($courier, true);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['is_available']);
    }

    /** @test */
    public function update_availability_fails_when_not_active(): void
    {
        $courier = User::factory()->courier()->create([
            'status' => UserStatus::SUSPENDED,
            'is_available' => false,
        ]);

        $result = $this->service->updateAvailability($courier, true);

        $this->assertFalse($result['success']);
        $this->assertStringContains('actif', $result['message']);
    }

    /** @test */
    public function update_availability_to_offline(): void
    {
        $courier = User::factory()->courier()->create([
            'status' => UserStatus::ACTIVE,
            'is_available' => true,
        ]);

        $result = $this->service->updateAvailability($courier, false);

        $this->assertTrue($result['success']);
        $this->assertFalse($result['is_available']);
    }

    /** @test */
    public function get_courier_stats_returns_expected_keys(): void
    {
        $courier = User::factory()->courier()->create([
            'status' => UserStatus::ACTIVE,
            'wallet_balance' => 5000,
            'total_orders' => 10,
            'average_rating' => 4.2,
            'total_ratings' => 8,
        ]);

        $stats = $this->service->getCourierStats($courier);

        $this->assertArrayHasKey('wallet_balance', $stats);
        $this->assertArrayHasKey('total_orders', $stats);
        $this->assertArrayHasKey('average_rating', $stats);
        $this->assertArrayHasKey('today', $stats);
        $this->assertArrayHasKey('this_week', $stats);
        $this->assertArrayHasKey('this_month', $stats);
        $this->assertEquals(5000, $stats['wallet_balance']);
    }

    /** @test */
    public function get_earnings_history_returns_paginator(): void
    {
        $courier = User::factory()->courier()->create([
            'status' => UserStatus::ACTIVE,
        ]);

        $result = $this->service->getEarningsHistory($courier);

        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $result);
    }

    /** @test */
    public function get_all_couriers_returns_paginator(): void
    {
        User::factory()->courier()->count(3)->create();

        $result = $this->service->getAllCouriers();

        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $result);
        $this->assertEquals(3, $result->total());
    }

    /** @test */
    public function get_all_couriers_filters_by_status(): void
    {
        User::factory()->courier()->create(['status' => UserStatus::ACTIVE]);
        User::factory()->courier()->create(['status' => UserStatus::SUSPENDED]);

        $active = $this->service->getAllCouriers('active');
        $this->assertEquals(1, $active->total());
    }

    /** @test */
    public function approve_courier_activates_account(): void
    {
        $courier = User::factory()->courier()->create([
            'status' => UserStatus::PENDING,
        ]);

        $result = $this->service->approveCourier($courier);

        $this->assertTrue($result['success']);
        $courier->refresh();
        $this->assertEquals(UserStatus::ACTIVE, $courier->status);
    }

    /** @test */
    public function approve_non_courier_fails(): void
    {
        $client = User::factory()->client()->create();

        $result = $this->service->approveCourier($client);

        $this->assertFalse($result['success']);
    }

    /** @test */
    public function suspend_courier_deactivates_and_sets_unavailable(): void
    {
        $courier = User::factory()->courier()->create([
            'status' => UserStatus::ACTIVE,
            'is_available' => true,
        ]);

        $result = $this->service->suspendCourier($courier, 'Violation des règles');

        $this->assertTrue($result['success']);
        $courier->refresh();
        $this->assertEquals(UserStatus::SUSPENDED, $courier->status);
        $this->assertFalse($courier->is_available);
    }

    /** @test */
    public function suspend_non_courier_fails(): void
    {
        $client = User::factory()->client()->create();

        $result = $this->service->suspendCourier($client, 'Test');

        $this->assertFalse($result['success']);
    }

    /**
     * Helper to check string contains (PHPUnit 11 compatible)
     */
    private function assertStringContains(string $needle, string $haystack): void
    {
        $this->assertTrue(
            str_contains($haystack, $needle),
            "Failed asserting that '{$haystack}' contains '{$needle}'"
        );
    }
}

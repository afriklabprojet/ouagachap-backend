<?php

namespace Tests\Unit\Services;

use App\Services\GeofenceService;
use App\Models\User;
use App\Models\Order;
use App\Models\Zone;
use App\Models\GeofenceAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeofenceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected GeofenceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new GeofenceService();
    }

    // ==========================================
    // Tests pour calculateDistance (Haversine)
    // ==========================================

    public function test_calculate_distance_same_point_returns_zero(): void
    {
        $distance = $this->service->calculateDistance(
            12.3714, -1.5197, // Ouagadougou
            12.3714, -1.5197
        );

        $this->assertEquals(0, $distance);
    }

    public function test_calculate_distance_known_points(): void
    {
        // Ouagadougou centre à l'aéroport (~2.5km)
        $distance = $this->service->calculateDistance(
            12.3714, -1.5197,  // Centre
            12.3522, -1.5129   // Aéroport
        );

        // Distance approximative entre 2-3km
        $this->assertGreaterThan(1500, $distance);
        $this->assertLessThan(5000, $distance);
    }

    public function test_calculate_distance_returns_meters(): void
    {
        // 1 degré latitude ≈ 111km
        $distance = $this->service->calculateDistance(
            12.0, -1.5,
            13.0, -1.5  // 1 degré plus au nord
        );

        // Devrait être environ 111km (111000m)
        $this->assertGreaterThan(100000, $distance);
        $this->assertLessThan(120000, $distance);
    }

    public function test_calculate_distance_symmetry(): void
    {
        $distanceAB = $this->service->calculateDistance(12.3714, -1.5197, 12.4, -1.5);
        $distanceBA = $this->service->calculateDistance(12.4, -1.5, 12.3714, -1.5197);

        $this->assertEquals($distanceAB, $distanceBA, '', 0.01);
    }

    public function test_calculate_distance_small_differences(): void
    {
        // 100m de différence environ
        $distance = $this->service->calculateDistance(
            12.3714, -1.5197,
            12.3723, -1.5197  // ~100m au nord
        );

        $this->assertGreaterThan(50, $distance);
        $this->assertLessThan(200, $distance);
    }

    // ==========================================
    // Tests pour getUnreadAlerts
    // ==========================================

    public function test_get_unread_alerts_for_courier(): void
    {
        $courier = User::factory()->create(['role' => 'courier']);
        $client = User::factory()->create(['role' => 'client']);

        $order = Order::factory()->create([
            'client_id' => $client->id,
            'courier_id' => $courier->id,
        ]);

        GeofenceAlert::create([
            'order_id' => $order->id,
            'courier_id' => $courier->id,
            'type' => GeofenceAlert::TYPE_PROXIMITY_PICKUP,
            'latitude' => 12.3714,
            'longitude' => -1.5197,
            'is_read' => false,
        ]);

        GeofenceAlert::create([
            'order_id' => $order->id,
            'courier_id' => $courier->id,
            'type' => GeofenceAlert::TYPE_PROXIMITY_DELIVERY,
            'latitude' => 12.3714,
            'longitude' => -1.5197,
            'is_read' => true, // Cette alerte est lue
        ]);

        $unread = $this->service->getUnreadAlerts($courier->id);

        $this->assertCount(1, $unread);
        $this->assertEquals(GeofenceAlert::TYPE_PROXIMITY_PICKUP, $unread->first()->type);
    }

    public function test_get_unread_alerts_only_for_specific_courier(): void
    {
        $courier1 = User::factory()->create(['role' => 'courier']);
        $courier2 = User::factory()->create(['role' => 'courier']);
        $client = User::factory()->create(['role' => 'client']);

        $order1 = Order::factory()->create(['client_id' => $client->id, 'courier_id' => $courier1->id]);
        $order2 = Order::factory()->create(['client_id' => $client->id, 'courier_id' => $courier2->id]);

        GeofenceAlert::create([
            'order_id' => $order1->id,
            'courier_id' => $courier1->id,
            'type' => GeofenceAlert::TYPE_PROXIMITY_PICKUP,
            'latitude' => 12.3714,
            'longitude' => -1.5197,
            'is_read' => false,
        ]);

        GeofenceAlert::create([
            'order_id' => $order2->id,
            'courier_id' => $courier2->id,
            'type' => GeofenceAlert::TYPE_PROXIMITY_DELIVERY,
            'latitude' => 12.3714,
            'longitude' => -1.5197,
            'is_read' => false,
        ]);

        $unread = $this->service->getUnreadAlerts($courier1->id);

        $this->assertCount(1, $unread);
        $this->assertEquals($courier1->id, $unread->first()->courier_id);
    }

    public function test_get_unread_alerts_empty_when_all_read(): void
    {
        $courier = User::factory()->create(['role' => 'courier']);
        $client = User::factory()->create(['role' => 'client']);

        $order = Order::factory()->create(['client_id' => $client->id, 'courier_id' => $courier->id]);

        GeofenceAlert::create([
            'order_id' => $order->id,
            'courier_id' => $courier->id,
            'type' => GeofenceAlert::TYPE_PROXIMITY_PICKUP,
            'latitude' => 12.3714,
            'longitude' => -1.5197,
            'is_read' => true,
        ]);

        $unread = $this->service->getUnreadAlerts($courier->id);

        $this->assertCount(0, $unread);
    }

    // ==========================================
    // Tests pour markAlertsAsRead
    // ==========================================

    public function test_mark_alerts_as_read(): void
    {
        $courier = User::factory()->create(['role' => 'courier']);
        $client = User::factory()->create(['role' => 'client']);

        $order = Order::factory()->create([
            'client_id' => $client->id,
            'courier_id' => $courier->id,
        ]);

        $alert1 = GeofenceAlert::create([
            'order_id' => $order->id,
            'courier_id' => $courier->id,
            'type' => GeofenceAlert::TYPE_PROXIMITY_PICKUP,
            'latitude' => 12.3714,
            'longitude' => -1.5197,
            'is_read' => false,
        ]);

        $alert2 = GeofenceAlert::create([
            'order_id' => $order->id,
            'courier_id' => $courier->id,
            'type' => GeofenceAlert::TYPE_PROXIMITY_DELIVERY,
            'latitude' => 12.3714,
            'longitude' => -1.5197,
            'is_read' => false,
        ]);

        $count = $this->service->markAlertsAsRead([$alert1->id, $alert2->id]);

        $this->assertEquals(2, $count);
        $this->assertTrue($alert1->fresh()->is_read);
        $this->assertTrue($alert2->fresh()->is_read);
    }

    public function test_mark_alerts_as_read_partial(): void
    {
        $courier = User::factory()->create(['role' => 'courier']);
        $client = User::factory()->create(['role' => 'client']);

        $order = Order::factory()->create(['client_id' => $client->id, 'courier_id' => $courier->id]);

        $alert1 = GeofenceAlert::create([
            'order_id' => $order->id,
            'courier_id' => $courier->id,
            'type' => GeofenceAlert::TYPE_PROXIMITY_PICKUP,
            'latitude' => 12.3714,
            'longitude' => -1.5197,
            'is_read' => false,
        ]);

        $alert2 = GeofenceAlert::create([
            'order_id' => $order->id,
            'courier_id' => $courier->id,
            'type' => GeofenceAlert::TYPE_PROXIMITY_DELIVERY,
            'latitude' => 12.3714,
            'longitude' => -1.5197,
            'is_read' => false,
        ]);

        // Marquer seulement la première
        $count = $this->service->markAlertsAsRead([$alert1->id]);

        $this->assertEquals(1, $count);
        $this->assertTrue($alert1->fresh()->is_read);
        $this->assertFalse($alert2->fresh()->is_read);
    }

    public function test_mark_alerts_as_read_empty_array(): void
    {
        $count = $this->service->markAlertsAsRead([]);

        $this->assertEquals(0, $count);
    }

    // ==========================================
    // Tests pour GeofenceAlert model static methods
    // ==========================================

    public function test_create_proximity_pickup_alert(): void
    {
        $courier = User::factory()->create(['role' => 'courier']);
        $client = User::factory()->create(['role' => 'client']);

        $order = Order::factory()->create([
            'client_id' => $client->id,
            'courier_id' => $courier->id,
        ]);

        $alert = GeofenceAlert::createProximityPickup($order, 12.3714, -1.5197, 150.5);

        $this->assertEquals($order->id, $alert->order_id);
        $this->assertEquals($courier->id, $alert->courier_id);
        $this->assertEquals(GeofenceAlert::TYPE_PROXIMITY_PICKUP, $alert->type);
        $this->assertEquals(12.3714, $alert->latitude);
        $this->assertEquals(-1.5197, $alert->longitude);
        $this->assertEquals(150.5, $alert->distance_meters);
        $this->assertStringContainsString('151m', $alert->message); // rounded
    }

    public function test_create_proximity_delivery_alert(): void
    {
        $courier = User::factory()->create(['role' => 'courier']);
        $client = User::factory()->create(['role' => 'client']);

        $order = Order::factory()->create([
            'client_id' => $client->id,
            'courier_id' => $courier->id,
        ]);

        $alert = GeofenceAlert::createProximityDelivery($order, 12.38, -1.52, 100.0);

        $this->assertEquals(GeofenceAlert::TYPE_PROXIMITY_DELIVERY, $alert->type);
        $this->assertStringContainsString('livraison', $alert->message);
    }

    public function test_create_out_of_bounds_alert(): void
    {
        $courier = User::factory()->create(['role' => 'courier']);
        $client = User::factory()->create(['role' => 'client']);

        $order = Order::factory()->create([
            'client_id' => $client->id,
            'courier_id' => $courier->id,
        ]);

        $alert = GeofenceAlert::createOutOfBounds($order, 14.0, -1.5);

        $this->assertEquals(GeofenceAlert::TYPE_OUT_OF_BOUNDS, $alert->type);
        $this->assertStringContainsString('hors', $alert->message);
    }

    // ==========================================
    // Tests pour les constantes
    // ==========================================

    public function test_proximity_threshold_constant(): void
    {
        $this->assertEquals(200, GeofenceService::PROXIMITY_THRESHOLD);
    }

    public function test_out_of_bounds_threshold_constant(): void
    {
        $this->assertEquals(5000, GeofenceService::OUT_OF_BOUNDS_THRESHOLD);
    }

    // ==========================================
    // Tests edge cases
    // ==========================================

    public function test_calculate_distance_negative_coordinates(): void
    {
        // Coordonnées en Afrique de l'Ouest (longitude négative)
        $distance = $this->service->calculateDistance(
            12.3714, -1.5197,
            12.3714, -2.0  // Plus à l'ouest
        );

        $this->assertGreaterThan(0, $distance);
    }

    public function test_calculate_distance_very_close_points(): void
    {
        // Points à 1m de distance
        $distance = $this->service->calculateDistance(
            12.3714000, -1.5197000,
            12.3714009, -1.5197000  // ~1m au nord
        );

        $this->assertLessThan(5, $distance);
    }
}

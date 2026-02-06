<?php

namespace Tests\Unit\Repositories;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Tests pour UserRepository
 */
class UserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected UserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new UserRepository();
    }

    // =========================================================================
    // TESTS DE getAvailableCouriers
    // =========================================================================

    /** @test */
    public function get_available_couriers_returns_active_available_couriers(): void
    {
        // Coursier disponible (doit apparaître)
        User::factory()->courier()->create([
            'status' => UserStatus::ACTIVE,
            'is_available' => true,
            'current_latitude' => 12.3714,
            'current_longitude' => -1.5197,
        ]);

        // Coursier non disponible (ne doit pas apparaître)
        User::factory()->courier()->create([
            'status' => UserStatus::ACTIVE,
            'is_available' => false,
        ]);

        // Coursier suspendu (ne doit pas apparaître)
        User::factory()->courier()->create([
            'status' => UserStatus::SUSPENDED,
            'is_available' => true,
        ]);

        // Client (ne doit pas apparaître)
        User::factory()->client()->create();

        $result = $this->repository->getAvailableCouriers();

        $this->assertCount(1, $result);
    }

    /** @test */
    public function get_available_couriers_requires_location(): void
    {
        // Coursier sans localisation (ne doit pas apparaître)
        User::factory()->courier()->create([
            'status' => UserStatus::ACTIVE,
            'is_available' => true,
            'current_latitude' => null,
            'current_longitude' => null,
        ]);

        // Coursier avec localisation
        User::factory()->courier()->create([
            'status' => UserStatus::ACTIVE,
            'is_available' => true,
            'current_latitude' => 12.3714,
            'current_longitude' => -1.5197,
        ]);

        $result = $this->repository->getAvailableCouriers();

        $this->assertCount(1, $result);
    }

    /** @test */
    public function get_available_couriers_returns_correct_fields(): void
    {
        User::factory()->courier()->create([
            'status' => UserStatus::ACTIVE,
            'is_available' => true,
            'current_latitude' => 12.3714,
            'current_longitude' => -1.5197,
            'vehicle_type' => 'motorcycle',
            'average_rating' => 4.5,
        ]);

        $result = $this->repository->getAvailableCouriers()->first();

        $this->assertNotNull($result->id);
        $this->assertNotNull($result->name);
        $this->assertNotNull($result->phone);
        $this->assertNotNull($result->vehicle_type);
    }

    // =========================================================================
    // TESTS DE findNearbyCouriers
    // =========================================================================

    /**
     * @test
     * @group mysql-only
     */
    public function find_nearby_couriers_returns_collection(): void
    {
        // Ce test nécessite MySQL pour la formule Haversine avec HAVING
        $this->markTestSkipped('Requires MySQL for Haversine formula with HAVING clause');
    }

    /**
     * @test
     * @group mysql-only
     */
    public function find_nearby_couriers_includes_distance(): void
    {
        $this->markTestSkipped('Requires MySQL for Haversine formula with HAVING clause');
    }

    /**
     * @test
     * @group mysql-only
     */
    public function find_nearby_couriers_respects_limit(): void
    {
        $this->markTestSkipped('Requires MySQL for Haversine formula with HAVING clause');
    }

    /**
     * @test
     * @group mysql-only
     */
    public function find_nearby_couriers_only_returns_active_available(): void
    {
        $this->markTestSkipped('Requires MySQL for Haversine formula with HAVING clause');
    }

    // =========================================================================
    // TESTS DE getClients
    // =========================================================================

    /** @test */
    public function get_clients_returns_paginated_clients(): void
    {
        User::factory()->count(5)->client()->create();
        User::factory()->count(3)->courier()->create();

        $result = $this->repository->getClients();

        $this->assertCount(5, $result->items());
        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $result);
    }

    /** @test */
    public function get_clients_respects_pagination(): void
    {
        User::factory()->count(25)->client()->create();

        $result = $this->repository->getClients(10);

        $this->assertCount(10, $result->items());
        $this->assertEquals(25, $result->total());
    }

    /** @test */
    public function get_clients_searches_by_name(): void
    {
        User::factory()->client()->create(['name' => 'Jean Dupont']);
        User::factory()->client()->create(['name' => 'Marie Martin']);
        User::factory()->client()->create(['name' => 'Paul Durand']);

        $result = $this->repository->getClients(15, 'Jean');

        $this->assertCount(1, $result->items());
        $this->assertEquals('Jean Dupont', $result->items()[0]->name);
    }

    /** @test */
    public function get_clients_searches_by_phone(): void
    {
        User::factory()->client()->create(['phone' => '+22670001111']);
        User::factory()->client()->create(['phone' => '+22670002222']);

        $result = $this->repository->getClients(15, '70001111');

        $this->assertCount(1, $result->items());
    }

    /** @test */
    public function get_clients_searches_by_email(): void
    {
        User::factory()->client()->create(['email' => 'jean@test.com']);
        User::factory()->client()->create(['email' => 'marie@test.com']);

        $result = $this->repository->getClients(15, 'jean@');

        $this->assertCount(1, $result->items());
    }

    /** @test */
    public function get_clients_orders_by_created_at_desc(): void
    {
        User::factory()->client()->create(['created_at' => now()->subDays(5)]);
        $newClient = User::factory()->client()->create(['created_at' => now()]);

        $result = $this->repository->getClients();

        $this->assertEquals($newClient->id, $result->items()[0]->id);
    }

    // =========================================================================
    // TESTS DE getCouriers
    // =========================================================================

    /** @test */
    public function get_couriers_returns_paginated_couriers(): void
    {
        User::factory()->count(5)->courier()->create();
        User::factory()->count(3)->client()->create();

        $result = $this->repository->getCouriers();

        $this->assertCount(5, $result->items());
    }

    /** @test */
    public function get_couriers_filters_by_status(): void
    {
        User::factory()->count(3)->courier()->create(['status' => UserStatus::ACTIVE]);
        User::factory()->count(2)->courier()->create(['status' => UserStatus::PENDING]);

        $result = $this->repository->getCouriers(15, null, UserStatus::ACTIVE->value);

        $this->assertCount(3, $result->items());
    }

    /** @test */
    public function get_couriers_searches_by_name(): void
    {
        User::factory()->courier()->create(['name' => 'Amadou Diallo']);
        User::factory()->courier()->create(['name' => 'Moussa Koné']);

        $result = $this->repository->getCouriers(15, 'Amadou');

        $this->assertCount(1, $result->items());
    }

    /** @test */
    public function get_couriers_searches_by_vehicle_plate(): void
    {
        User::factory()->courier()->create(['vehicle_plate' => 'AB-1234-BF']);
        User::factory()->courier()->create(['vehicle_plate' => 'CD-5678-BF']);

        $result = $this->repository->getCouriers(15, 'AB-1234');

        $this->assertCount(1, $result->items());
    }

    // =========================================================================
    // TESTS DE getDashboardStats
    // =========================================================================

    /** @test */
    public function get_dashboard_stats_returns_correct_structure(): void
    {
        $result = $this->repository->getDashboardStats();

        $this->assertArrayHasKey('clients', $result);
        $this->assertArrayHasKey('couriers', $result);
    }

    /** @test */
    public function get_dashboard_stats_counts_clients_correctly(): void
    {
        // Nettoyer avant
        User::query()->delete();
        Cache::flush();
        
        User::factory()->count(3)->client()->create(['status' => UserStatus::ACTIVE]);
        User::factory()->count(2)->client()->create(['status' => UserStatus::PENDING]);
        User::factory()->client()->create(['status' => UserStatus::ACTIVE, 'created_at' => now()]);

        $result = $this->repository->getDashboardStats();

        $this->assertEquals(6, $result['clients']['total']);
        $this->assertEquals(4, $result['clients']['active']); // 3 + 1 créé aujourd'hui
        $this->assertGreaterThanOrEqual(1, $result['clients']['new_today']);
    }

    /** @test */
    public function get_dashboard_stats_counts_couriers_correctly(): void
    {
        User::factory()->count(3)->courier()->create([
            'status' => UserStatus::ACTIVE,
            'is_available' => true,
        ]);
        User::factory()->count(2)->courier()->create([
            'status' => UserStatus::PENDING,
        ]);

        Cache::flush();
        $result = $this->repository->getDashboardStats();

        $this->assertEquals(5, $result['couriers']['total']);
        $this->assertEquals(3, $result['couriers']['active']);
        $this->assertEquals(3, $result['couriers']['available_now']);
        $this->assertEquals(2, $result['couriers']['pending_approval']);
    }

    /** @test */
    public function get_dashboard_stats_caches_results(): void
    {
        Cache::flush();
        
        $this->repository->getDashboardStats();
        
        $this->assertTrue(Cache::has('users:dashboard_stats'));
    }

    // =========================================================================
    // TESTS DE getTopCouriers
    // =========================================================================

    /** @test */
    public function get_top_couriers_returns_collection(): void
    {
        User::factory()->count(5)->courier()->create([
            'status' => UserStatus::ACTIVE,
            'total_orders' => 10,
            'average_rating' => 4.5,
        ]);

        $result = $this->repository->getTopCouriers();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /** @test */
    public function get_top_couriers_excludes_couriers_with_no_orders(): void
    {
        User::factory()->courier()->create([
            'status' => UserStatus::ACTIVE,
            'total_orders' => 0,
        ]);
        User::factory()->courier()->create([
            'status' => UserStatus::ACTIVE,
            'total_orders' => 10,
        ]);

        $result = $this->repository->getTopCouriers();

        $this->assertCount(1, $result);
    }

    /** @test */
    public function get_top_couriers_orders_by_rating_then_orders(): void
    {
        $lowRating = User::factory()->courier()->create([
            'status' => UserStatus::ACTIVE,
            'total_orders' => 100,
            'average_rating' => 3.0,
        ]);
        $highRating = User::factory()->courier()->create([
            'status' => UserStatus::ACTIVE,
            'total_orders' => 50,
            'average_rating' => 5.0,
        ]);

        $result = $this->repository->getTopCouriers();

        $this->assertEquals($highRating->id, $result->first()->id);
    }

    /** @test */
    public function get_top_couriers_respects_limit(): void
    {
        User::factory()->count(15)->courier()->create([
            'status' => UserStatus::ACTIVE,
            'total_orders' => 10,
        ]);

        $result = $this->repository->getTopCouriers(5);

        $this->assertCount(5, $result);
    }

    /** @test */
    public function get_top_couriers_only_includes_active_couriers(): void
    {
        User::factory()->courier()->create([
            'status' => UserStatus::ACTIVE,
            'total_orders' => 10,
        ]);
        User::factory()->courier()->create([
            'status' => UserStatus::SUSPENDED,
            'total_orders' => 50,
        ]);

        $result = $this->repository->getTopCouriers();

        $this->assertCount(1, $result);
    }

    // =========================================================================
    // TESTS DE clearCache
    // =========================================================================

    /** @test */
    public function clear_cache_removes_dashboard_stats_cache(): void
    {
        Cache::put('users:dashboard_stats', ['test' => true], 3600);
        
        $this->repository->clearCache();
        
        $this->assertFalse(Cache::has('users:dashboard_stats'));
    }

    // =========================================================================
    // TESTS DES PROPRIÉTÉS
    // =========================================================================

    /** @test */
    public function repository_has_cache_prefix(): void
    {
        $reflection = new \ReflectionClass(UserRepository::class);
        $property = $reflection->getProperty('cachePrefix');
        $property->setAccessible(true);

        $this->assertEquals('users:', $property->getValue($this->repository));
    }

    /** @test */
    public function repository_has_cache_ttl(): void
    {
        $reflection = new \ReflectionClass(UserRepository::class);
        $property = $reflection->getProperty('cacheTtl');
        $property->setAccessible(true);

        $this->assertEquals(300, $property->getValue($this->repository));
    }
}

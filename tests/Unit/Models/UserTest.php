<?php

namespace Tests\Unit\Models;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function is_client_returns_true_for_clients(): void
    {
        $client = User::factory()->client()->create();

        $this->assertTrue($client->isClient());
        $this->assertFalse($client->isCourier());
        $this->assertFalse($client->isAdmin());
    }

    /** @test */
    public function is_courier_returns_true_for_couriers(): void
    {
        $courier = User::factory()->courier()->create();

        $this->assertTrue($courier->isCourier());
        $this->assertFalse($courier->isClient());
        $this->assertFalse($courier->isAdmin());
    }

    /** @test */
    public function is_admin_returns_true_for_admins(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isClient());
        $this->assertFalse($admin->isCourier());
    }

    /** @test */
    public function is_active_checks_user_status(): void
    {
        $activeUser = User::factory()->create(['status' => UserStatus::ACTIVE]);
        $suspendedUser = User::factory()->create(['status' => UserStatus::SUSPENDED]);

        $this->assertTrue($activeUser->isActive());
        $this->assertFalse($suspendedUser->isActive());
    }

    /** @test */
    public function can_accept_orders_requires_courier_role(): void
    {
        $client = User::factory()->client()->create(['is_available' => true]);

        $this->assertFalse($client->canAcceptOrders());
    }

    /** @test */
    public function can_accept_orders_requires_active_status(): void
    {
        $courier = User::factory()->courier()->create([
            'status' => UserStatus::SUSPENDED,
            'is_available' => true,
        ]);

        $this->assertFalse($courier->canAcceptOrders());
    }

    /** @test */
    public function can_accept_orders_requires_availability(): void
    {
        $courier = User::factory()->courier()->create([
            'status' => UserStatus::ACTIVE,
            'is_available' => false,
        ]);

        $this->assertFalse($courier->canAcceptOrders());
    }

    /** @test */
    public function can_accept_orders_false_when_has_active_delivery(): void
    {
        $courier = User::factory()->courier()->create([
            'status' => UserStatus::ACTIVE,
            'is_available' => true,
        ]);

        Order::factory()->create([
            'courier_id' => $courier->id,
            'status' => OrderStatus::ASSIGNED,
        ]);

        $this->assertFalse($courier->canAcceptOrders());
        $this->assertTrue($courier->hasActiveDelivery());
    }

    /** @test */
    public function can_accept_orders_true_when_available_and_no_active_delivery(): void
    {
        $courier = User::factory()->courier()->create([
            'status' => UserStatus::ACTIVE,
            'is_available' => true,
        ]);

        Order::factory()->create([
            'courier_id' => $courier->id,
            'status' => OrderStatus::DELIVERED,
        ]);

        $this->assertTrue($courier->canAcceptOrders());
        $this->assertFalse($courier->hasActiveDelivery());
    }

    /** @test */
    public function has_active_delivery_checks_courier_orders(): void
    {
        $courier = User::factory()->courier()->create();

        $this->assertFalse($courier->hasActiveDelivery());

        Order::factory()->create([
            'courier_id' => $courier->id,
            'status' => OrderStatus::PICKED_UP,
        ]);

        $courier->refresh();
        $this->assertTrue($courier->hasActiveDelivery());
    }

    /** @test */
    public function update_location_updates_coordinates_and_timestamp(): void
    {
        $user = User::factory()->create([
            'current_latitude' => null,
            'current_longitude' => null,
        ]);

        $user->updateLocation(12.3714, -1.5197);

        $user->refresh();
        $this->assertEquals(12.3714, $user->current_latitude);
        $this->assertEquals(-1.5197, $user->current_longitude);
        $this->assertNotNull($user->location_updated_at);
    }

    /** @test */
    public function update_rating_calculates_new_average(): void
    {
        $user = User::factory()->create([
            'average_rating' => 4.0,
            'total_ratings' => 2,
        ]);

        $user->updateRating(5);

        $user->refresh();
        $this->assertEquals(4.33, $user->average_rating);
        $this->assertEquals(3, $user->total_ratings);
    }

    /** @test */
    public function update_rating_works_for_first_rating(): void
    {
        $user = User::factory()->create([
            'average_rating' => 0,
            'total_ratings' => 0,
        ]);

        $user->updateRating(5);

        $user->refresh();
        $this->assertEquals(5.0, $user->average_rating);
        $this->assertEquals(1, $user->total_ratings);
    }

    /** @test */
    public function increment_total_orders_increases_count(): void
    {
        $user = User::factory()->create(['total_orders' => 5]);

        $user->incrementTotalOrders();

        $user->refresh();
        $this->assertEquals(6, $user->total_orders);
    }

    /** @test */
    public function add_to_wallet_increases_balance(): void
    {
        $user = User::factory()->create(['wallet_balance' => 1000]);
        // Créer un Wallet avec le même solde (source de vérité depuis le refactoring)
        \App\Models\Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => 1000,
        ]);

        $user->addToWallet(500);

        $user->refresh();
        $this->assertEquals(1500, $user->wallet_balance);
    }

    /** @test */
    public function get_avatar_url_returns_null_when_no_avatar(): void
    {
        $user = User::factory()->create(['avatar' => null]);

        $this->assertNull($user->avatar_url);
    }

    /** @test */
    public function get_avatar_url_returns_full_url_when_already_complete(): void
    {
        $user = User::factory()->create(['avatar' => 'https://example.com/avatar.jpg']);

        $this->assertEquals('https://example.com/avatar.jpg', $user->avatar_url);
    }

    /** @test */
    public function get_avatar_url_generates_storage_url_for_relative_path(): void
    {
        $user = User::factory()->create(['avatar' => 'avatars/user123.jpg']);

        $url = $user->avatar_url;
        $this->assertStringContainsString('/storage/avatars/user123.jpg', $url);
    }

    /** @test */
    public function clients_scope_filters_by_client_role(): void
    {
        User::factory()->client()->count(3)->create();
        User::factory()->courier()->count(2)->create();

        $clients = User::clients()->get();

        $this->assertCount(3, $clients);
        $this->assertTrue($clients->every(fn($u) => $u->role === UserRole::CLIENT));
    }

    /** @test */
    public function couriers_scope_filters_by_courier_role(): void
    {
        User::factory()->client()->count(2)->create();
        User::factory()->courier()->count(4)->create();

        $couriers = User::couriers()->get();

        $this->assertCount(4, $couriers);
        $this->assertTrue($couriers->every(fn($u) => $u->role === UserRole::COURIER));
    }

    /** @test */
    public function admins_scope_filters_by_admin_role(): void
    {
        User::factory()->client()->create();
        User::factory()->courier()->create();
        User::factory()->admin()->count(2)->create();

        $admins = User::admins()->get();

        $this->assertCount(2, $admins);
        $this->assertTrue($admins->every(fn($u) => $u->role === UserRole::ADMIN));
    }

    /** @test */
    public function active_scope_filters_by_active_status(): void
    {
        User::factory()->count(3)->create(['status' => UserStatus::ACTIVE]);
        User::factory()->count(2)->create(['status' => UserStatus::SUSPENDED]);

        $active = User::active()->get();

        $this->assertCount(3, $active);
        $this->assertTrue($active->every(fn($u) => $u->status === UserStatus::ACTIVE));
    }

    /** @test */
    public function available_scope_filters_by_availability(): void
    {
        User::factory()->count(3)->create(['is_available' => true, 'wallet_balance' => 2000]);
        User::factory()->count(2)->create(['is_available' => false]);

        $available = User::available()->get();

        $this->assertCount(3, $available);
        $this->assertTrue($available->every(fn($u) => $u->is_available === true));
    }

    /** @test */
    public function user_has_client_orders_relationship(): void
    {
        $client = User::factory()->client()->create();
        Order::factory()->count(3)->create(['client_id' => $client->id]);

        $this->assertCount(3, $client->clientOrders);
    }

    /** @test */
    public function user_has_courier_orders_relationship(): void
    {
        $courier = User::factory()->courier()->create();
        Order::factory()->count(2)->create(['courier_id' => $courier->id]);

        $this->assertCount(2, $courier->courierOrders);
    }

    /** @test */
    public function user_has_payments_relationship(): void
    {
        $user = User::factory()->create();
        \App\Models\Payment::factory()->count(4)->create(['user_id' => $user->id]);

        $this->assertCount(4, $user->payments);
    }
}

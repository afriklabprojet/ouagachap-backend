<?php

namespace Tests\Unit\Policies;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\User;
use App\Policies\OrderPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderPolicyTest extends TestCase
{
    use RefreshDatabase;

    private OrderPolicy $policy;
    private User $admin;
    private User $client1;
    private User $client2;
    private User $courier;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->policy = new OrderPolicy();
        
        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);
        
        $this->client1 = User::factory()->create([
            'role' => UserRole::CLIENT,
        ]);
        
        $this->client2 = User::factory()->create([
            'role' => UserRole::CLIENT,
        ]);
        
        $this->courier = User::factory()->create([
            'role' => UserRole::COURIER,
        ]);
        
        $this->order = Order::factory()->create([
            'client_id' => $this->client1->id,
            'courier_id' => null,
            'status' => OrderStatus::PENDING,
        ]);
    }

    // =========================================================================
    // ADMIN BYPASS TESTS
    // =========================================================================

    public function test_admin_can_view_any_order(): void
    {
        // Admin bypass via Gate (before() method)
        $this->assertTrue($this->admin->can('view', $this->order));
    }

    public function test_admin_can_update_any_order(): void
    {
        // Admin bypass via Gate (before() method)
        $this->assertTrue($this->admin->can('update', $this->order));
    }

    public function test_admin_can_delete_any_order(): void
    {
        // Admin bypass via Gate (before() method)
        $this->assertTrue($this->admin->can('delete', $this->order));
    }

    // =========================================================================
    // CLIENT OWNERSHIP TESTS (IDOR Prevention)
    // =========================================================================

    public function test_client_can_view_own_order(): void
    {
        $this->assertTrue($this->policy->view($this->client1, $this->order));
    }

    public function test_client_cannot_view_others_order(): void
    {
        $this->assertFalse($this->policy->view($this->client2, $this->order));
    }

    public function test_client_cannot_update_order(): void
    {
        // Clients cannot update orders (only cancel their own)
        $this->assertFalse($this->policy->update($this->client1, $this->order));
    }

    public function test_client_cannot_delete_order(): void
    {
        $this->assertFalse($this->policy->delete($this->client1, $this->order));
    }

    // =========================================================================
    // ORDER CREATION TESTS
    // =========================================================================

    public function test_client_can_create_order(): void
    {
        $this->assertTrue($this->policy->create($this->client1));
    }

    public function test_courier_cannot_create_order(): void
    {
        $this->assertFalse($this->policy->create($this->courier));
    }

    // =========================================================================
    // COURIER ACCESS TESTS
    // =========================================================================

    public function test_courier_cannot_view_unassigned_order(): void
    {
        // Order has no courier assigned
        $this->assertFalse($this->policy->view($this->courier, $this->order));
    }

    public function test_assigned_courier_can_view_order(): void
    {
        // Assign courier to order
        $this->order->update(['courier_id' => $this->courier->id]);
        
        $this->assertTrue($this->policy->view($this->courier, $this->order->fresh()));
    }

    public function test_other_courier_cannot_view_assigned_order(): void
    {
        $otherCourier = User::factory()->create(['role' => UserRole::COURIER]);
        
        // Assign first courier to order
        $this->order->update(['courier_id' => $this->courier->id]);
        
        $this->assertFalse($this->policy->view($otherCourier, $this->order->fresh()));
    }

    public function test_assigned_courier_can_update_order(): void
    {
        $this->order->update(['courier_id' => $this->courier->id]);
        
        $this->assertTrue($this->policy->update($this->courier, $this->order->fresh()));
    }

    public function test_unassigned_courier_cannot_update_order(): void
    {
        $this->assertFalse($this->policy->update($this->courier, $this->order));
    }

    // =========================================================================
    // CANCEL PERMISSION TESTS
    // =========================================================================

    public function test_client_can_cancel_own_pending_order(): void
    {
        $this->assertTrue($this->policy->cancel($this->client1, $this->order));
    }

    public function test_client_cannot_cancel_others_order(): void
    {
        $this->assertFalse($this->policy->cancel($this->client2, $this->order));
    }

    public function test_client_cannot_cancel_delivered_order(): void
    {
        $this->order->update(['status' => OrderStatus::DELIVERED]);
        
        $this->assertFalse($this->policy->cancel($this->client1, $this->order->fresh()));
    }

    public function test_client_can_cancel_assigned_order(): void
    {
        $this->order->update([
            'status' => OrderStatus::ASSIGNED,
            'courier_id' => $this->courier->id,
        ]);
        
        $this->assertTrue($this->policy->cancel($this->client1, $this->order->fresh()));
    }

    public function test_courier_can_cancel_assigned_order(): void
    {
        $this->order->update([
            'status' => OrderStatus::ASSIGNED,
            'courier_id' => $this->courier->id,
        ]);
        
        $this->assertTrue($this->policy->cancel($this->courier, $this->order->fresh()));
    }

    // =========================================================================
    // ASSIGN PERMISSION TESTS
    // =========================================================================

    public function test_available_courier_can_accept_pending_order(): void
    {
        $this->courier->update(['is_available' => true]);
        
        $this->assertTrue($this->policy->accept($this->courier, $this->order));
    }

    public function test_unavailable_courier_cannot_accept_order(): void
    {
        $this->courier->update(['is_available' => false]);
        
        $this->assertFalse($this->policy->accept($this->courier, $this->order));
    }

    public function test_courier_cannot_accept_already_assigned_order(): void
    {
        $otherCourier = User::factory()->create([
            'role' => UserRole::COURIER,
            'is_available' => true,
        ]);
        
        $this->order->update([
            'status' => OrderStatus::ASSIGNED,
            'courier_id' => $otherCourier->id,
        ]);
        
        $this->courier->update(['is_available' => true]);
        
        $this->assertFalse($this->policy->accept($this->courier, $this->order->fresh()));
    }

    public function test_client_cannot_accept_order(): void
    {
        $this->assertFalse($this->policy->accept($this->client1, $this->order));
    }

    // =========================================================================
    // VIEW ANY (LIST) TESTS
    // =========================================================================

    public function test_all_authenticated_users_can_view_order_list(): void
    {
        $this->assertTrue($this->policy->viewAny($this->client1));
        $this->assertTrue($this->policy->viewAny($this->courier));
        $this->assertTrue($this->policy->viewAny($this->admin));
    }
}

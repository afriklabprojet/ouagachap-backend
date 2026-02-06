<?php

namespace Tests\Unit\Policies;

use App\Enums\UserRole;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Policies\PaymentPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentPolicyTest extends TestCase
{
    use RefreshDatabase;

    private PaymentPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new PaymentPolicy();
    }

    /** @test */
    public function admin_can_do_anything_via_before(): void
    {
        $admin = User::factory()->admin()->create();

        $result = $this->policy->before($admin, 'anyAbility');

        $this->assertTrue($result);
    }

    /** @test */
    public function non_admin_returns_null_from_before(): void
    {
        $client = User::factory()->client()->create();

        $result = $this->policy->before($client, 'anyAbility');

        $this->assertNull($result);
    }

    /** @test */
    public function courier_returns_null_from_before(): void
    {
        $courier = User::factory()->courier()->create();

        $result = $this->policy->before($courier, 'anyAbility');

        $this->assertNull($result);
    }

    /** @test */
    public function anyone_can_view_any_payments(): void
    {
        $client = User::factory()->client()->create();
        $courier = User::factory()->courier()->create();

        $this->assertTrue($this->policy->viewAny($client));
        $this->assertTrue($this->policy->viewAny($courier));
    }

    /** @test */
    public function user_can_view_own_payment(): void
    {
        $client = User::factory()->client()->create();
        $order = Order::factory()->create(['client_id' => $client->id]);
        $payment = Payment::factory()->create([
            'user_id' => $client->id,
            'order_id' => $order->id,
        ]);

        $this->assertTrue($this->policy->view($client, $payment));
    }

    /** @test */
    public function user_cannot_view_other_user_payment(): void
    {
        $client = User::factory()->client()->create();
        $otherClient = User::factory()->client()->create();
        $order = Order::factory()->create(['client_id' => $otherClient->id]);
        $payment = Payment::factory()->create([
            'user_id' => $otherClient->id,
            'order_id' => $order->id,
        ]);

        $this->assertFalse($this->policy->view($client, $payment));
    }

    /** @test */
    public function only_clients_can_create_payments(): void
    {
        $client = User::factory()->client()->create();
        $courier = User::factory()->courier()->create();

        $this->assertTrue($this->policy->create($client));
        $this->assertFalse($this->policy->create($courier));
    }

    /** @test */
    public function user_can_check_status_of_own_payment(): void
    {
        $client = User::factory()->client()->create();
        $order = Order::factory()->create(['client_id' => $client->id]);
        $payment = Payment::factory()->create([
            'user_id' => $client->id,
            'order_id' => $order->id,
        ]);

        $this->assertTrue($this->policy->checkStatus($client, $payment));
    }

    /** @test */
    public function user_cannot_check_status_of_other_payment(): void
    {
        $client = User::factory()->client()->create();
        $otherClient = User::factory()->client()->create();
        $order = Order::factory()->create(['client_id' => $otherClient->id]);
        $payment = Payment::factory()->create([
            'user_id' => $otherClient->id,
            'order_id' => $order->id,
        ]);

        $this->assertFalse($this->policy->checkStatus($client, $payment));
    }

    /** @test */
    public function policy_class_exists(): void
    {
        $this->assertInstanceOf(PaymentPolicy::class, $this->policy);
    }

    /** @test */
    public function policy_has_before_method(): void
    {
        $this->assertTrue(method_exists($this->policy, 'before'));
    }

    /** @test */
    public function policy_has_view_any_method(): void
    {
        $this->assertTrue(method_exists($this->policy, 'viewAny'));
    }

    /** @test */
    public function policy_has_view_method(): void
    {
        $this->assertTrue(method_exists($this->policy, 'view'));
    }

    /** @test */
    public function policy_has_create_method(): void
    {
        $this->assertTrue(method_exists($this->policy, 'create'));
    }

    /** @test */
    public function policy_has_check_status_method(): void
    {
        $this->assertTrue(method_exists($this->policy, 'checkStatus'));
    }
}

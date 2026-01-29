<?php

namespace Tests\Feature\Api\V1;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $client;
    protected Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = User::factory()->create(['role' => UserRole::CLIENT]);
        $this->order = Order::factory()->create([
            'client_id' => $this->client->id,
            'status' => OrderStatus::PENDING,
            'total_price' => 1500,
        ]);
    }

    // =========================================================================
    // PAYMENT METHODS
    // =========================================================================

    public function test_can_get_payment_methods(): void
    {
        $response = $this->getJson('/api/v1/payments/methods');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['value', 'label'],
                ],
            ]);
    }

    // =========================================================================
    // INITIATE PAYMENT
    // =========================================================================

    public function test_client_can_initiate_payment(): void
    {
        $response = $this->actingAs($this->client, 'sanctum')
            ->postJson('/api/v1/payments/initiate', [
                'order_id' => $this->order->id,
                'method' => PaymentMethod::ORANGE_MONEY->value,
                'phone_number' => '+22670123456',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'payment' => ['id', 'transaction_id', 'amount', 'status'],
                ],
            ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $this->order->id,
            'amount' => $this->order->total_price,
        ]);
    }

    public function test_cannot_pay_for_other_client_order(): void
    {
        $otherClient = User::factory()->create(['role' => UserRole::CLIENT]);
        $otherOrder = Order::factory()->create(['client_id' => $otherClient->id]);

        $response = $this->actingAs($this->client, 'sanctum')
            ->postJson('/api/v1/payments/initiate', [
                'order_id' => $otherOrder->id,
                'method' => PaymentMethod::ORANGE_MONEY->value,
                'phone_number' => '+22670123456',
            ]);

        $response->assertStatus(403);
    }

    public function test_cannot_double_pay_order(): void
    {
        // First payment
        Payment::factory()->create([
            'order_id' => $this->order->id,
            'status' => PaymentStatus::SUCCESS->value,
        ]);

        $response = $this->actingAs($this->client, 'sanctum')
            ->postJson('/api/v1/payments/initiate', [
                'order_id' => $this->order->id,
                'method' => PaymentMethod::ORANGE_MONEY->value,
                'phone_number' => '+22670123456',
            ]);

        $response->assertStatus(400); // Already paid
    }

    public function test_courier_cannot_initiate_payment(): void
    {
        $courier = User::factory()->create(['role' => UserRole::COURIER]);

        $response = $this->actingAs($courier, 'sanctum')
            ->postJson('/api/v1/payments/initiate', [
                'order_id' => $this->order->id,
                'method' => PaymentMethod::ORANGE_MONEY->value,
                'phone_number' => '+22670123456',
            ]);

        $response->assertStatus(403);
    }

    // =========================================================================
    // PAYMENT STATUS
    // =========================================================================

    public function test_client_can_check_payment_status(): void
    {
        $payment = Payment::factory()->create([
            'order_id' => $this->order->id,
            'user_id' => $this->client->id,
            'status' => PaymentStatus::PENDING->value,
        ]);

        $response = $this->actingAs($this->client, 'sanctum')
            ->getJson("/api/v1/payments/{$payment->id}/status");

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data' => ['status']]);
    }

    // =========================================================================
    // WEBHOOK TESTS
    // =========================================================================

    public function test_webhook_updates_payment_status(): void
    {
        $payment = Payment::factory()->create([
            'order_id' => $this->order->id,
            'status' => PaymentStatus::PENDING->value,
            'transaction_id' => 'TXN123456',
        ]);

        $response = $this->postJson('/api/v1/payments/webhook', [
            'transaction_id' => 'TXN123456',
            'status' => 'SUCCESS',
            'provider_transaction_id' => 'PROV123',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::SUCCESS->value,
        ]);
    }
}

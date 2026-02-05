<?php

namespace Tests\Unit\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private PaymentService $paymentService;
    private User $client;
    private User $otherClient;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->paymentService = app(PaymentService::class);
        
        $this->client = User::factory()->create([
            'role' => UserRole::CLIENT,
            'phone' => '70123456',
        ]);
        
        $this->otherClient = User::factory()->create([
            'role' => UserRole::CLIENT,
            'phone' => '70999888',
        ]);
        
        $this->order = Order::factory()->create([
            'client_id' => $this->client->id,
            'status' => OrderStatus::PENDING,
            'total_price' => 1500,
        ]);
    }

    // =========================================================================
    // OWNERSHIP TESTS (IDOR Prevention)
    // =========================================================================

    public function test_client_cannot_pay_for_others_order(): void
    {
        $result = $this->paymentService->initiatePayment(
            $this->order,
            $this->otherClient, // Wrong user
            PaymentMethod::ORANGE_MONEY,
            '70999888'
        );

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('non autorisé', $result['message']);
    }

    public function test_owner_can_pay_for_own_order(): void
    {
        $result = $this->paymentService->initiatePayment(
            $this->order,
            $this->client, // Correct user
            PaymentMethod::ORANGE_MONEY,
            '70123456'
        );

        // Should succeed (or pending in mock mode)
        $this->assertTrue($result['success'] || isset($result['pending']));
    }

    // =========================================================================
    // DOUBLE PAYMENT PREVENTION TESTS
    // =========================================================================

    public function test_double_payment_is_prevented(): void
    {
        // First, create a successful payment for the order
        Payment::factory()->create([
            'order_id' => $this->order->id,
            'user_id' => $this->client->id,
            'status' => PaymentStatus::SUCCESS,
            'amount' => $this->order->total_price,
        ]);

        // Try to pay again
        $result = $this->paymentService->initiatePayment(
            $this->order->fresh(), // Fresh to reload relations
            $this->client,
            PaymentMethod::ORANGE_MONEY,
            '70123456'
        );

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('déjà été payée', $result['message']);
    }

    public function test_pending_payment_can_be_retried(): void
    {
        // Create a pending payment
        Payment::factory()->create([
            'order_id' => $this->order->id,
            'user_id' => $this->client->id,
            'status' => PaymentStatus::PENDING,
            'amount' => $this->order->total_price,
        ]);

        // Retry payment should work (updateOrCreate behavior)
        $result = $this->paymentService->initiatePayment(
            $this->order->fresh(),
            $this->client,
            PaymentMethod::ORANGE_MONEY,
            '70123456'
        );

        // Should be able to retry
        $this->assertTrue($result['success'] || isset($result['pending']));
    }

    public function test_failed_payment_can_be_retried(): void
    {
        // Create a failed payment
        Payment::factory()->create([
            'order_id' => $this->order->id,
            'user_id' => $this->client->id,
            'status' => PaymentStatus::FAILED,
            'amount' => $this->order->total_price,
        ]);

        // Retry payment should work
        $result = $this->paymentService->initiatePayment(
            $this->order->fresh(),
            $this->client,
            PaymentMethod::ORANGE_MONEY,
            '70123456'
        );

        // Should be able to retry after failure
        $this->assertTrue($result['success'] || isset($result['pending']));
    }

    // =========================================================================
    // ORDER STATUS VALIDATION TESTS
    // =========================================================================

    public function test_cannot_pay_for_delivered_order(): void
    {
        $this->order->update(['status' => OrderStatus::DELIVERED]);

        $result = $this->paymentService->initiatePayment(
            $this->order->fresh(),
            $this->client,
            PaymentMethod::ORANGE_MONEY,
            '70123456'
        );

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('ne peut plus être payée', $result['message']);
    }

    public function test_cannot_pay_for_cancelled_order(): void
    {
        $this->order->update(['status' => OrderStatus::CANCELLED]);

        $result = $this->paymentService->initiatePayment(
            $this->order->fresh(),
            $this->client,
            PaymentMethod::ORANGE_MONEY,
            '70123456'
        );

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('ne peut plus être payée', $result['message']);
    }

    public function test_can_pay_for_pending_order(): void
    {
        $this->order->update(['status' => OrderStatus::PENDING]);

        $result = $this->paymentService->initiatePayment(
            $this->order->fresh(),
            $this->client,
            PaymentMethod::ORANGE_MONEY,
            '70123456'
        );

        $this->assertTrue($result['success'] || isset($result['pending']));
    }

    public function test_can_pay_for_assigned_order(): void
    {
        $this->order->update(['status' => OrderStatus::ASSIGNED]);

        $result = $this->paymentService->initiatePayment(
            $this->order->fresh(),
            $this->client,
            PaymentMethod::ORANGE_MONEY,
            '70123456'
        );

        $this->assertTrue($result['success'] || isset($result['pending']));
    }

    // =========================================================================
    // PAYMENT STATUS CHECK TESTS
    // =========================================================================

    public function test_check_status_returns_payment_info(): void
    {
        $payment = Payment::factory()->create([
            'order_id' => $this->order->id,
            'user_id' => $this->client->id,
            'status' => PaymentStatus::SUCCESS,
            'amount' => 1500,
        ]);

        $result = $this->paymentService->checkStatus($payment);

        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('payment', $result);
        $this->assertEquals('success', $result['status']);
    }

    // =========================================================================
    // USER PAYMENT HISTORY TESTS
    // =========================================================================

    public function test_get_user_payments_returns_paginated_results(): void
    {
        // Create multiple payments
        Payment::factory()->count(5)->create([
            'user_id' => $this->client->id,
        ]);

        $payments = $this->paymentService->getUserPayments($this->client, 10);

        $this->assertCount(5, $payments);
    }

    public function test_user_sees_only_their_payments(): void
    {
        // Create payments for both users
        Payment::factory()->count(3)->create([
            'user_id' => $this->client->id,
        ]);
        
        Payment::factory()->count(2)->create([
            'user_id' => $this->otherClient->id,
        ]);

        $payments = $this->paymentService->getUserPayments($this->client, 10);

        $this->assertCount(3, $payments);
        
        foreach ($payments as $payment) {
            $this->assertEquals($this->client->id, $payment->user_id);
        }
    }
}

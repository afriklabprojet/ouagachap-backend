<?php

namespace Tests\Unit\Jobs;

use App\Enums\OrderStatus;
use App\Jobs\CreditCourierWalletJob;
use App\Models\Order;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Tests pour CreditCourierWalletJob
 */
class CreditCourierWalletJobTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function job_can_be_instantiated(): void
    {
        $order = Order::factory()->create();
        
        $job = new CreditCourierWalletJob($order);

        $this->assertInstanceOf(CreditCourierWalletJob::class, $job);
        $this->assertEquals($order->id, $job->order->id);
    }

    /** @test */
    public function job_has_retry_configuration(): void
    {
        $order = Order::factory()->create();
        $job = new CreditCourierWalletJob($order);

        $this->assertEquals(3, $job->tries);
        $this->assertEquals(60, $job->backoff);
    }

    /** @test */
    public function job_implements_should_queue(): void
    {
        $this->assertTrue(
            in_array(
                \Illuminate\Contracts\Queue\ShouldQueue::class,
                class_implements(CreditCourierWalletJob::class)
            )
        );
    }

    /** @test */
    public function job_can_be_dispatched(): void
    {
        Queue::fake();
        
        $order = Order::factory()->create();
        
        CreditCourierWalletJob::dispatch($order);

        Queue::assertPushed(CreditCourierWalletJob::class);
    }

    /** @test */
    public function handle_skips_non_delivered_orders(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::PENDING,
        ]);

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(fn($msg) => str_contains($msg, 'non livrée'));

        $mockWalletService = $this->mock(WalletService::class);
        $mockWalletService->shouldNotReceive('creditCourierForDelivery');

        $job = new CreditCourierWalletJob($order);
        $job->handle($mockWalletService);
    }

    /** @test */
    public function handle_skips_orders_without_courier(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::DELIVERED,
            'courier_id' => null,
        ]);

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(fn($msg) => str_contains($msg, 'Pas de coursier'));

        $mockWalletService = $this->mock(WalletService::class);
        $mockWalletService->shouldNotReceive('creditCourierForDelivery');

        $job = new CreditCourierWalletJob($order);
        $job->handle($mockWalletService);
    }

    /** @test */
    public function handle_credits_courier_for_delivered_order(): void
    {
        $courier = User::factory()->courier()->create();
        $order = Order::factory()->create([
            'status' => OrderStatus::DELIVERED,
            'courier_id' => $courier->id,
            'courier_earnings' => 2500,
        ]);

        // Créer un vrai Wallet via factory
        $wallet = \App\Models\Wallet::factory()->create([
            'user_id' => $courier->id,
            'balance' => 5000,
        ]);

        $mockWalletService = $this->mock(WalletService::class);
        $mockWalletService->shouldReceive('creditCourierForDelivery')
            ->once()
            ->with(\Mockery::on(fn($c) => $c->id === $courier->id), 2500)
            ->andReturn($wallet);

        Log::shouldReceive('info')
            ->once()
            ->withArgs(fn($msg) => str_contains($msg, 'crédité'));

        $job = new CreditCourierWalletJob($order);
        $job->handle($mockWalletService);
    }

    /** @test */
    public function job_logs_error_on_failure(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->withArgs(fn($msg) => str_contains($msg, 'Failed to credit courier'));

        $order = Order::factory()->create();
        $job = new CreditCourierWalletJob($order);
        
        $job->failed(new \Exception('Test error'));
    }

    /** @test */
    public function job_uses_required_traits(): void
    {
        $traits = class_uses_recursive(CreditCourierWalletJob::class);
        
        $this->assertContains(\Illuminate\Bus\Queueable::class, $traits);
        $this->assertContains(\Illuminate\Queue\SerializesModels::class, $traits);
        $this->assertContains(\Illuminate\Queue\InteractsWithQueue::class, $traits);
    }
}

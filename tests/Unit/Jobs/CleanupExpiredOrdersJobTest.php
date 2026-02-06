<?php

namespace Tests\Unit\Jobs;

use App\Enums\OrderStatus;
use App\Jobs\CleanupExpiredOrdersJob;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Tests pour CleanupExpiredOrdersJob
 */
class CleanupExpiredOrdersJobTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function job_can_be_instantiated(): void
    {
        $job = new CleanupExpiredOrdersJob();

        $this->assertInstanceOf(CleanupExpiredOrdersJob::class, $job);
    }

    /** @test */
    public function job_implements_should_queue(): void
    {
        $this->assertTrue(
            in_array(
                \Illuminate\Contracts\Queue\ShouldQueue::class,
                class_implements(CleanupExpiredOrdersJob::class)
            )
        );
    }

    /** @test */
    public function job_can_be_dispatched(): void
    {
        Queue::fake();
        
        CleanupExpiredOrdersJob::dispatch();

        Queue::assertPushed(CleanupExpiredOrdersJob::class);
    }

    /** @test */
    public function handle_cancels_expired_pending_orders(): void
    {
        // Commande expirée (> 24h)
        $expiredOrder = Order::factory()->create([
            'status' => OrderStatus::PENDING,
            'created_at' => now()->subHours(25),
        ]);

        // Commande récente (< 24h) - ne doit pas être annulée
        $recentOrder = Order::factory()->create([
            'status' => OrderStatus::PENDING,
            'created_at' => now()->subHours(12),
        ]);

        Log::shouldReceive('info')->atLeast()->once();

        $job = new CleanupExpiredOrdersJob();
        $job->handle();

        $expiredOrder->refresh();
        $recentOrder->refresh();

        $this->assertEquals(OrderStatus::CANCELLED->value, $expiredOrder->status->value);
        $this->assertEquals(OrderStatus::PENDING->value, $recentOrder->status->value);
    }

    /** @test */
    public function handle_sets_cancellation_reason(): void
    {
        $expiredOrder = Order::factory()->create([
            'status' => OrderStatus::PENDING,
            'created_at' => now()->subHours(25),
        ]);

        Log::shouldReceive('info')->atLeast()->once();

        $job = new CleanupExpiredOrdersJob();
        $job->handle();

        $expiredOrder->refresh();

        $this->assertNotNull($expiredOrder->cancellation_reason);
        $this->assertStringContainsString('expirée', $expiredOrder->cancellation_reason);
    }

    /** @test */
    public function handle_does_not_cancel_non_pending_orders(): void
    {
        // Commande livrée expirée - ne doit pas être touchée
        $deliveredOrder = Order::factory()->create([
            'status' => OrderStatus::DELIVERED,
            'created_at' => now()->subHours(48),
        ]);

        // Commande assignée expirée - ne doit pas être touchée
        $assignedOrder = Order::factory()->create([
            'status' => OrderStatus::ASSIGNED,
            'created_at' => now()->subHours(48),
        ]);

        Log::shouldReceive('info')->atLeast()->once();

        $job = new CleanupExpiredOrdersJob();
        $job->handle();

        $deliveredOrder->refresh();
        $assignedOrder->refresh();

        $this->assertEquals(OrderStatus::DELIVERED->value, $deliveredOrder->status->value);
        $this->assertEquals(OrderStatus::ASSIGNED->value, $assignedOrder->status->value);
    }

    /** @test */
    public function handle_logs_cleanup_results(): void
    {
        Order::factory()->count(3)->create([
            'status' => OrderStatus::PENDING,
            'created_at' => now()->subHours(30),
        ]);

        Log::shouldReceive('info')
            ->atLeast()
            ->once()
            ->withArgs(fn($msg) => str_contains($msg, 'annulée') || str_contains($msg, 'terminé'));

        $job = new CleanupExpiredOrdersJob();
        $job->handle();
    }

    /** @test */
    public function handle_works_with_no_expired_orders(): void
    {
        // Aucune commande expirée
        Order::factory()->create([
            'status' => OrderStatus::PENDING,
            'created_at' => now()->subHours(12),
        ]);

        Log::shouldReceive('info')
            ->once()
            ->withArgs(fn($msg) => str_contains($msg, '0 commandes'));

        $job = new CleanupExpiredOrdersJob();
        $job->handle();
    }

    /** @test */
    public function job_uses_required_traits(): void
    {
        $traits = class_uses_recursive(CleanupExpiredOrdersJob::class);
        
        $this->assertContains(\Illuminate\Bus\Queueable::class, $traits);
        $this->assertContains(\Illuminate\Queue\SerializesModels::class, $traits);
    }
}

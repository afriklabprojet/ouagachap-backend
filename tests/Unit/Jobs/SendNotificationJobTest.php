<?php

namespace Tests\Unit\Jobs;

use App\Enums\NotificationType;
use App\Jobs\SendNotificationJob;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Tests pour SendNotificationJob
 */
class SendNotificationJobTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function job_can_be_instantiated(): void
    {
        $user = User::factory()->create();
        
        $job = new SendNotificationJob(
            user: $user,
            type: NotificationType::ORDER_CREATED,
        );

        $this->assertInstanceOf(SendNotificationJob::class, $job);
        $this->assertEquals($user->id, $job->user->id);
        $this->assertEquals(NotificationType::ORDER_CREATED, $job->type);
    }

    /** @test */
    public function job_accepts_optional_parameters(): void
    {
        $user = User::factory()->create();
        
        $job = new SendNotificationJob(
            user: $user,
            type: NotificationType::ORDER_DELIVERED,
            data: ['order_id' => '123'],
            customTitle: 'Custom Title',
            customMessage: 'Custom Message',
        );

        $this->assertEquals(['order_id' => '123'], $job->data);
        $this->assertEquals('Custom Title', $job->customTitle);
        $this->assertEquals('Custom Message', $job->customMessage);
    }

    /** @test */
    public function job_has_default_empty_data(): void
    {
        $user = User::factory()->create();
        
        $job = new SendNotificationJob(
            user: $user,
            type: NotificationType::ORDER_CREATED,
        );

        $this->assertEquals([], $job->data);
    }

    /** @test */
    public function job_has_retry_configuration(): void
    {
        $user = User::factory()->create();
        $job = new SendNotificationJob($user, NotificationType::ORDER_CREATED);

        $this->assertEquals(3, $job->tries);
        $this->assertEquals(60, $job->backoff);
    }

    /** @test */
    public function job_implements_should_queue(): void
    {
        $this->assertTrue(
            in_array(
                \Illuminate\Contracts\Queue\ShouldQueue::class,
                class_implements(SendNotificationJob::class)
            )
        );
    }

    /** @test */
    public function job_can_be_dispatched(): void
    {
        Queue::fake();
        
        $user = User::factory()->create();
        
        SendNotificationJob::dispatch($user, NotificationType::ORDER_CREATED);

        Queue::assertPushed(SendNotificationJob::class);
    }

    /** @test */
    public function job_calls_notification_service(): void
    {
        $user = User::factory()->create();
        $type = NotificationType::ORDER_CREATED;
        $data = ['key' => 'value'];
        $customTitle = 'Title';
        $customMessage = 'Message';

        $mockService = $this->mock(NotificationService::class);
        $mockService->shouldReceive('send')
            ->once()
            ->with($user, $type, $data, $customTitle, $customMessage);

        $job = new SendNotificationJob($user, $type, $data, $customTitle, $customMessage);
        $job->handle($mockService);
    }

    /** @test */
    public function job_logs_error_on_failure(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message) {
                return str_contains($message, 'Failed to send notification');
            });

        $user = User::factory()->create();
        $job = new SendNotificationJob($user, NotificationType::ORDER_CREATED);
        
        $job->failed(new \Exception('Test error'));
    }

    /** @test */
    public function job_uses_queueable_trait(): void
    {
        $this->assertTrue(
            in_array(
                \Illuminate\Bus\Queueable::class,
                class_uses_recursive(SendNotificationJob::class)
            )
        );
    }

    /** @test */
    public function job_uses_serializes_models_trait(): void
    {
        $this->assertTrue(
            in_array(
                \Illuminate\Queue\SerializesModels::class,
                class_uses_recursive(SendNotificationJob::class)
            )
        );
    }
}

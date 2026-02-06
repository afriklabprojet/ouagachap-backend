<?php

namespace Tests\Unit\Listeners;

use App\Events\OrderCreated;
use App\Events\OrderStatusChanged;
use App\Listeners\SendOrderCreatedNotification;
use App\Listeners\SendOrderStatusNotification;
use App\Models\Order;
use App\Services\PushNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Tests pour les Listeners
 */
class OrderListenersTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // TESTS DE SendOrderCreatedNotification
    // =========================================================================

    /** @test */
    public function send_order_created_listener_exists(): void
    {
        $this->assertTrue(class_exists(SendOrderCreatedNotification::class));
    }

    /** @test */
    public function send_order_created_listener_implements_should_queue(): void
    {
        $this->assertTrue(
            in_array(
                \Illuminate\Contracts\Queue\ShouldQueue::class,
                class_implements(SendOrderCreatedNotification::class)
            )
        );
    }

    /** @test */
    public function send_order_created_listener_uses_interacts_with_queue(): void
    {
        $traits = class_uses_recursive(SendOrderCreatedNotification::class);
        
        $this->assertContains(\Illuminate\Queue\InteractsWithQueue::class, $traits);
    }

    /** @test */
    public function send_order_created_listener_handles_event(): void
    {
        $order = Order::factory()->create();
        $event = new OrderCreated($order);

        $mockPushService = $this->mock(PushNotificationService::class);
        $mockPushService->shouldReceive('notifyOrderCreated')->once()->with($order);
        $mockPushService->shouldReceive('broadcastToAvailableCouriers')->once()->with($order);

        $listener = new SendOrderCreatedNotification($mockPushService);
        $listener->handle($event);
    }

    /** @test */
    public function send_order_created_listener_notifies_client(): void
    {
        $order = Order::factory()->create();
        $event = new OrderCreated($order);

        $mockPushService = $this->mock(PushNotificationService::class);
        $mockPushService->shouldReceive('notifyOrderCreated')
            ->once()
            ->with(\Mockery::on(fn($o) => $o->id === $order->id));
        $mockPushService->shouldReceive('broadcastToAvailableCouriers')->once();

        $listener = new SendOrderCreatedNotification($mockPushService);
        $listener->handle($event);
    }

    /** @test */
    public function send_order_created_listener_broadcasts_to_couriers(): void
    {
        $order = Order::factory()->create();
        $event = new OrderCreated($order);

        $mockPushService = $this->mock(PushNotificationService::class);
        $mockPushService->shouldReceive('notifyOrderCreated')->once();
        $mockPushService->shouldReceive('broadcastToAvailableCouriers')
            ->once()
            ->with(\Mockery::on(fn($o) => $o->id === $order->id));

        $listener = new SendOrderCreatedNotification($mockPushService);
        $listener->handle($event);
    }

    // =========================================================================
    // TESTS DE SendOrderStatusNotification
    // =========================================================================

    /** @test */
    public function send_order_status_listener_exists(): void
    {
        $this->assertTrue(class_exists(SendOrderStatusNotification::class));
    }

    /** @test */
    public function send_order_status_listener_implements_should_queue(): void
    {
        $this->assertTrue(
            in_array(
                \Illuminate\Contracts\Queue\ShouldQueue::class,
                class_implements(SendOrderStatusNotification::class)
            )
        );
    }

    /** @test */
    public function send_order_status_listener_handles_picked_up(): void
    {
        $order = Order::factory()->create();
        $event = new OrderStatusChanged($order, 'assigned', 'picked_up');

        $mockPushService = $this->mock(PushNotificationService::class);
        $mockPushService->shouldReceive('notifyOrderPickedUp')
            ->once()
            ->with(\Mockery::on(fn($o) => $o->id === $order->id));

        $listener = new SendOrderStatusNotification($mockPushService);
        $listener->handle($event);
    }

    /** @test */
    public function send_order_status_listener_handles_delivered(): void
    {
        $order = Order::factory()->create();
        $event = new OrderStatusChanged($order, 'picked_up', 'delivered');

        $mockPushService = $this->mock(PushNotificationService::class);
        $mockPushService->shouldReceive('notifyOrderDelivered')
            ->once()
            ->with(\Mockery::on(fn($o) => $o->id === $order->id));
        $mockPushService->shouldReceive('notifyCourierEarnings')
            ->once()
            ->with(\Mockery::on(fn($o) => $o->id === $order->id));

        $listener = new SendOrderStatusNotification($mockPushService);
        $listener->handle($event);
    }

    /** @test */
    public function send_order_status_listener_handles_cancelled(): void
    {
        $order = Order::factory()->create();
        $event = new OrderStatusChanged($order, 'assigned', 'cancelled');

        $mockPushService = $this->mock(PushNotificationService::class);
        $mockPushService->shouldReceive('notifyOrderCancelled')
            ->once()
            ->with(\Mockery::on(fn($o) => $o->id === $order->id));

        $listener = new SendOrderStatusNotification($mockPushService);
        $listener->handle($event);
    }

    /** @test */
    public function send_order_status_listener_ignores_other_statuses(): void
    {
        $order = Order::factory()->create();
        $event = new OrderStatusChanged($order, 'pending', 'assigned');

        $mockPushService = $this->mock(PushNotificationService::class);
        $mockPushService->shouldNotReceive('notifyOrderPickedUp');
        $mockPushService->shouldNotReceive('notifyOrderDelivered');
        $mockPushService->shouldNotReceive('notifyOrderCancelled');
        $mockPushService->shouldNotReceive('notifyCourierEarnings');

        $listener = new SendOrderStatusNotification($mockPushService);
        $listener->handle($event);
    }

    // =========================================================================
    // TESTS D'INTÉGRATION EVENTS/LISTENERS
    // =========================================================================

    /** @test */
    public function event_listener_registration_can_be_tested(): void
    {
        Event::fake();
        
        $order = Order::factory()->create();
        
        event(new OrderCreated($order));

        Event::assertDispatched(OrderCreated::class);
    }

    /** @test */
    public function listeners_are_properly_namespaced(): void
    {
        $this->assertEquals(
            'App\Listeners\SendOrderCreatedNotification',
            SendOrderCreatedNotification::class
        );
        $this->assertEquals(
            'App\Listeners\SendOrderStatusNotification',
            SendOrderStatusNotification::class
        );
    }

    /** @test */
    public function listeners_have_handle_method(): void
    {
        $this->assertTrue(method_exists(SendOrderCreatedNotification::class, 'handle'));
        $this->assertTrue(method_exists(SendOrderStatusNotification::class, 'handle'));
    }

    /** @test */
    public function listeners_accept_correct_event_types(): void
    {
        $reflection = new \ReflectionMethod(SendOrderCreatedNotification::class, 'handle');
        $params = $reflection->getParameters();
        
        $this->assertCount(1, $params);
        $this->assertEquals(OrderCreated::class, $params[0]->getType()->getName());

        $reflection = new \ReflectionMethod(SendOrderStatusNotification::class, 'handle');
        $params = $reflection->getParameters();
        
        $this->assertCount(1, $params);
        $this->assertEquals(OrderStatusChanged::class, $params[0]->getType()->getName());
    }
}

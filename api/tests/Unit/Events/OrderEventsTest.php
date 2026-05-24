<?php

namespace Tests\Unit\Events;

use App\Events\OrderCreated;
use App\Events\OrderStatusChanged;
use App\Events\PaymentCompleted;
use App\Events\OrderAssigned;
use App\Events\CourierLocationUpdated;
use App\Events\NewOrderAvailable;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Tests pour les Events
 */
class OrderEventsTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // TESTS DE OrderCreated
    // =========================================================================

    /** @test */
    public function order_created_event_can_be_instantiated(): void
    {
        $order = Order::factory()->create();
        
        $event = new OrderCreated($order);

        $this->assertInstanceOf(OrderCreated::class, $event);
        $this->assertEquals($order->id, $event->order->id);
    }

    /** @test */
    public function order_created_event_can_be_dispatched(): void
    {
        Event::fake();
        
        $order = Order::factory()->create();
        
        OrderCreated::dispatch($order);

        Event::assertDispatched(OrderCreated::class, function ($event) use ($order) {
            return $event->order->id === $order->id;
        });
    }

    /** @test */
    public function order_created_uses_dispatchable_trait(): void
    {
        $traits = class_uses_recursive(OrderCreated::class);
        
        $this->assertContains(\Illuminate\Foundation\Events\Dispatchable::class, $traits);
    }

    /** @test */
    public function order_created_uses_serializes_models_trait(): void
    {
        $traits = class_uses_recursive(OrderCreated::class);
        
        $this->assertContains(\Illuminate\Queue\SerializesModels::class, $traits);
    }

    // =========================================================================
    // TESTS DE OrderStatusChanged
    // =========================================================================

    /** @test */
    public function order_status_changed_event_can_be_instantiated(): void
    {
        $order = Order::factory()->create();
        
        $event = new OrderStatusChanged($order, 'pending', 'assigned');

        $this->assertInstanceOf(OrderStatusChanged::class, $event);
        $this->assertEquals($order->id, $event->order->id);
        $this->assertEquals('pending', $event->previousStatus);
        $this->assertEquals('assigned', $event->newStatus);
    }

    /** @test */
    public function order_status_changed_event_can_be_dispatched(): void
    {
        Event::fake();
        
        $order = Order::factory()->create();
        
        OrderStatusChanged::dispatch($order, 'pending', 'delivered');

        Event::assertDispatched(OrderStatusChanged::class, function ($event) {
            return $event->previousStatus === 'pending' 
                && $event->newStatus === 'delivered';
        });
    }

    /** @test */
    public function order_status_changed_stores_both_statuses(): void
    {
        $order = Order::factory()->create();
        
        $event = new OrderStatusChanged($order, 'assigned', 'picked_up');

        $this->assertNotEquals($event->previousStatus, $event->newStatus);
    }

    // =========================================================================
    // TESTS DE PaymentCompleted
    // =========================================================================

    /** @test */
    public function payment_completed_event_can_be_instantiated(): void
    {
        $order = Order::factory()->create();
        $payment = Payment::factory()->create(['order_id' => $order->id]);
        
        $event = new PaymentCompleted($payment, $order);

        $this->assertInstanceOf(PaymentCompleted::class, $event);
        $this->assertEquals($payment->id, $event->payment->id);
        $this->assertEquals($order->id, $event->order->id);
    }

    /** @test */
    public function payment_completed_event_can_be_dispatched(): void
    {
        Event::fake();
        
        $order = Order::factory()->create();
        $payment = Payment::factory()->create(['order_id' => $order->id]);
        
        PaymentCompleted::dispatch($payment, $order);

        Event::assertDispatched(PaymentCompleted::class, function ($event) use ($payment, $order) {
            return $event->payment->id === $payment->id 
                && $event->order->id === $order->id;
        });
    }

    // =========================================================================
    // TESTS DES TRAITS COMMUNS
    // =========================================================================

    /** @test */
    public function all_events_use_dispatchable(): void
    {
        $events = [
            OrderCreated::class,
            OrderStatusChanged::class,
            PaymentCompleted::class,
        ];

        foreach ($events as $eventClass) {
            $traits = class_uses_recursive($eventClass);
            $this->assertContains(
                \Illuminate\Foundation\Events\Dispatchable::class, 
                $traits,
                "{$eventClass} should use Dispatchable trait"
            );
        }
    }

    /** @test */
    public function all_events_use_serializes_models(): void
    {
        $events = [
            OrderCreated::class,
            OrderStatusChanged::class,
            PaymentCompleted::class,
        ];

        foreach ($events as $eventClass) {
            $traits = class_uses_recursive($eventClass);
            $this->assertContains(
                \Illuminate\Queue\SerializesModels::class, 
                $traits,
                "{$eventClass} should use SerializesModels trait"
            );
        }
    }

    /** @test */
    public function all_events_use_interacts_with_sockets(): void
    {
        $events = [
            OrderCreated::class,
            OrderStatusChanged::class,
            PaymentCompleted::class,
        ];

        foreach ($events as $eventClass) {
            $traits = class_uses_recursive($eventClass);
            $this->assertContains(
                \Illuminate\Broadcasting\InteractsWithSockets::class, 
                $traits,
                "{$eventClass} should use InteractsWithSockets trait"
            );
        }
    }

    // =========================================================================
    // TESTS D'INTÉGRATION
    // =========================================================================

    /** @test */
    public function multiple_events_can_be_dispatched_in_sequence(): void
    {
        Event::fake();
        
        $order = Order::factory()->create();
        $payment = Payment::factory()->create(['order_id' => $order->id]);
        
        // Simuler le flux complet
        OrderCreated::dispatch($order);
        OrderStatusChanged::dispatch($order, 'pending', 'assigned');
        OrderStatusChanged::dispatch($order, 'assigned', 'picked_up');
        PaymentCompleted::dispatch($payment, $order);
        OrderStatusChanged::dispatch($order, 'picked_up', 'delivered');

        Event::assertDispatched(OrderCreated::class, 1);
        Event::assertDispatched(OrderStatusChanged::class, 3);
        Event::assertDispatched(PaymentCompleted::class, 1);
    }

    /** @test */
    public function event_order_is_loaded_from_database(): void
    {
        $order = Order::factory()->create(['total_price' => 5000]);
        
        $event = new OrderCreated($order);

        // L'ordre doit être un modèle avec ses attributs
        $this->assertEquals(5000, $event->order->total_price);
    }
}

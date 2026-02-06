<?php

namespace Tests\Unit\Enums;

use App\Enums\OrderStatus;
use Tests\TestCase;

/**
 * Tests pour OrderStatus Enum
 */
class OrderStatusTest extends TestCase
{
    // ==================== Values Tests ====================

    public function test_pending_value(): void
    {
        $this->assertEquals('pending', OrderStatus::PENDING->value);
    }

    public function test_assigned_value(): void
    {
        $this->assertEquals('assigned', OrderStatus::ASSIGNED->value);
    }

    public function test_picked_up_value(): void
    {
        $this->assertEquals('picked_up', OrderStatus::PICKED_UP->value);
    }

    public function test_delivered_value(): void
    {
        $this->assertEquals('delivered', OrderStatus::DELIVERED->value);
    }

    public function test_cancelled_value(): void
    {
        $this->assertEquals('cancelled', OrderStatus::CANCELLED->value);
    }

    // ==================== Label Tests ====================

    public function test_pending_label(): void
    {
        $this->assertEquals('En attente', OrderStatus::PENDING->label());
    }

    public function test_assigned_label(): void
    {
        $this->assertEquals('Assignée', OrderStatus::ASSIGNED->label());
    }

    public function test_picked_up_label(): void
    {
        $this->assertEquals('Récupérée', OrderStatus::PICKED_UP->label());
    }

    public function test_delivered_label(): void
    {
        $this->assertEquals('Livrée', OrderStatus::DELIVERED->label());
    }

    public function test_cancelled_label(): void
    {
        $this->assertEquals('Annulée', OrderStatus::CANCELLED->label());
    }

    // ==================== Color Tests ====================

    public function test_pending_color(): void
    {
        $this->assertEquals('warning', OrderStatus::PENDING->color());
    }

    public function test_assigned_color(): void
    {
        $this->assertEquals('info', OrderStatus::ASSIGNED->color());
    }

    public function test_picked_up_color(): void
    {
        $this->assertEquals('primary', OrderStatus::PICKED_UP->color());
    }

    public function test_delivered_color(): void
    {
        $this->assertEquals('success', OrderStatus::DELIVERED->color());
    }

    public function test_cancelled_color(): void
    {
        $this->assertEquals('danger', OrderStatus::CANCELLED->color());
    }

    // ==================== Transition Tests ====================

    public function test_pending_can_transition_to_assigned(): void
    {
        $this->assertTrue(OrderStatus::PENDING->canTransitionTo(OrderStatus::ASSIGNED));
    }

    public function test_pending_can_transition_to_cancelled(): void
    {
        $this->assertTrue(OrderStatus::PENDING->canTransitionTo(OrderStatus::CANCELLED));
    }

    public function test_pending_cannot_transition_to_delivered(): void
    {
        $this->assertFalse(OrderStatus::PENDING->canTransitionTo(OrderStatus::DELIVERED));
    }

    public function test_pending_cannot_transition_to_picked_up(): void
    {
        $this->assertFalse(OrderStatus::PENDING->canTransitionTo(OrderStatus::PICKED_UP));
    }

    public function test_assigned_can_transition_to_picked_up(): void
    {
        $this->assertTrue(OrderStatus::ASSIGNED->canTransitionTo(OrderStatus::PICKED_UP));
    }

    public function test_assigned_can_transition_to_cancelled(): void
    {
        $this->assertTrue(OrderStatus::ASSIGNED->canTransitionTo(OrderStatus::CANCELLED));
    }

    public function test_assigned_cannot_transition_to_delivered(): void
    {
        $this->assertFalse(OrderStatus::ASSIGNED->canTransitionTo(OrderStatus::DELIVERED));
    }

    public function test_picked_up_can_transition_to_delivered(): void
    {
        $this->assertTrue(OrderStatus::PICKED_UP->canTransitionTo(OrderStatus::DELIVERED));
    }

    public function test_picked_up_can_transition_to_cancelled(): void
    {
        $this->assertTrue(OrderStatus::PICKED_UP->canTransitionTo(OrderStatus::CANCELLED));
    }

    public function test_delivered_cannot_transition(): void
    {
        $this->assertFalse(OrderStatus::DELIVERED->canTransitionTo(OrderStatus::PENDING));
        $this->assertFalse(OrderStatus::DELIVERED->canTransitionTo(OrderStatus::ASSIGNED));
        $this->assertFalse(OrderStatus::DELIVERED->canTransitionTo(OrderStatus::CANCELLED));
    }

    public function test_cancelled_cannot_transition(): void
    {
        $this->assertFalse(OrderStatus::CANCELLED->canTransitionTo(OrderStatus::PENDING));
        $this->assertFalse(OrderStatus::CANCELLED->canTransitionTo(OrderStatus::ASSIGNED));
        $this->assertFalse(OrderStatus::CANCELLED->canTransitionTo(OrderStatus::DELIVERED));
    }

    // ==================== Allowed Transitions Tests ====================

    public function test_allowed_transitions_from_pending(): void
    {
        $transitions = OrderStatus::allowedTransitions(OrderStatus::PENDING);
        
        $this->assertCount(2, $transitions);
        $this->assertContains(OrderStatus::ASSIGNED, $transitions);
        $this->assertContains(OrderStatus::CANCELLED, $transitions);
    }

    public function test_allowed_transitions_from_assigned(): void
    {
        $transitions = OrderStatus::allowedTransitions(OrderStatus::ASSIGNED);
        
        $this->assertCount(2, $transitions);
        $this->assertContains(OrderStatus::PICKED_UP, $transitions);
        $this->assertContains(OrderStatus::CANCELLED, $transitions);
    }

    public function test_allowed_transitions_from_picked_up(): void
    {
        $transitions = OrderStatus::allowedTransitions(OrderStatus::PICKED_UP);
        
        $this->assertCount(2, $transitions);
        $this->assertContains(OrderStatus::DELIVERED, $transitions);
        $this->assertContains(OrderStatus::CANCELLED, $transitions);
    }

    public function test_allowed_transitions_from_delivered(): void
    {
        $transitions = OrderStatus::allowedTransitions(OrderStatus::DELIVERED);
        
        $this->assertEmpty($transitions);
    }

    public function test_allowed_transitions_from_cancelled(): void
    {
        $transitions = OrderStatus::allowedTransitions(OrderStatus::CANCELLED);
        
        $this->assertEmpty($transitions);
    }

    // ==================== Cases Tests ====================

    public function test_all_cases_count(): void
    {
        $this->assertCount(5, OrderStatus::cases());
    }

    public function test_all_cases_have_labels(): void
    {
        foreach (OrderStatus::cases() as $status) {
            $this->assertNotEmpty($status->label());
            $this->assertIsString($status->label());
        }
    }

    public function test_all_cases_have_colors(): void
    {
        foreach (OrderStatus::cases() as $status) {
            $this->assertNotEmpty($status->color());
            $this->assertIsString($status->color());
        }
    }

    // ==================== From Value Tests ====================

    public function test_from_valid_value(): void
    {
        $status = OrderStatus::from('pending');
        $this->assertEquals(OrderStatus::PENDING, $status);
    }

    public function test_try_from_valid_value(): void
    {
        $status = OrderStatus::tryFrom('delivered');
        $this->assertEquals(OrderStatus::DELIVERED, $status);
    }

    public function test_try_from_invalid_value(): void
    {
        $status = OrderStatus::tryFrom('invalid_status');
        $this->assertNull($status);
    }

    // ==================== Workflow Tests ====================

    public function test_complete_delivery_workflow(): void
    {
        // Simule le workflow complet d'une livraison réussie
        $status = OrderStatus::PENDING;
        
        // pending -> assigned
        $this->assertTrue($status->canTransitionTo(OrderStatus::ASSIGNED));
        $status = OrderStatus::ASSIGNED;
        
        // assigned -> picked_up
        $this->assertTrue($status->canTransitionTo(OrderStatus::PICKED_UP));
        $status = OrderStatus::PICKED_UP;
        
        // picked_up -> delivered
        $this->assertTrue($status->canTransitionTo(OrderStatus::DELIVERED));
        $status = OrderStatus::DELIVERED;
        
        // delivered est final
        $this->assertEmpty(OrderStatus::allowedTransitions($status));
    }

    public function test_cancellation_workflow(): void
    {
        // Peut être annulée à n'importe quelle étape avant livraison
        $this->assertTrue(OrderStatus::PENDING->canTransitionTo(OrderStatus::CANCELLED));
        $this->assertTrue(OrderStatus::ASSIGNED->canTransitionTo(OrderStatus::CANCELLED));
        $this->assertTrue(OrderStatus::PICKED_UP->canTransitionTo(OrderStatus::CANCELLED));
        
        // Mais pas après livraison
        $this->assertFalse(OrderStatus::DELIVERED->canTransitionTo(OrderStatus::CANCELLED));
    }
}

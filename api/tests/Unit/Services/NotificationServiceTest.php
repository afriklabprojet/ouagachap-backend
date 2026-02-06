<?php

namespace Tests\Unit\Services;

use App\Enums\NotificationType;
use Tests\TestCase;

/**
 * Tests pour NotificationService et NotificationType
 * Note: Tests unitaires sans dépendance DB pour éviter les problèmes de concurrence SQLite
 */
class NotificationServiceTest extends TestCase
{
    // ==================== NotificationType Enum Tests ====================

    public function test_notification_type_order_created(): void
    {
        $type = NotificationType::ORDER_CREATED;
        
        $this->assertEquals('order_created', $type->value);
        $this->assertEquals('Nouvelle commande', $type->getTitle());
        $this->assertContains('push', $type->getChannel());
        $this->assertFalse($type->isOptional());
    }

    public function test_notification_type_order_confirmed(): void
    {
        $type = NotificationType::ORDER_CONFIRMED;
        
        $this->assertEquals('order_confirmed', $type->value);
        $this->assertEquals('Commande confirmée', $type->getTitle());
        $this->assertContains('push', $type->getChannel());
        $this->assertFalse($type->isOptional());
    }

    public function test_notification_type_order_assigned(): void
    {
        $type = NotificationType::ORDER_ASSIGNED;
        
        $this->assertEquals('order_assigned', $type->value);
        $this->assertEquals('Coursier assigné', $type->getTitle());
        $this->assertFalse($type->isOptional());
    }

    public function test_notification_type_order_picked_up(): void
    {
        $type = NotificationType::ORDER_PICKED_UP;
        
        $this->assertEquals('order_picked_up', $type->value);
        $this->assertEquals('Colis récupéré', $type->getTitle());
    }

    public function test_notification_type_order_in_transit(): void
    {
        $type = NotificationType::ORDER_IN_TRANSIT;
        
        $this->assertEquals('order_in_transit', $type->value);
        $this->assertEquals('Livraison en cours', $type->getTitle());
    }

    public function test_notification_type_order_delivered(): void
    {
        $type = NotificationType::ORDER_DELIVERED;
        
        $this->assertEquals('order_delivered', $type->value);
        $this->assertEquals('Livraison effectuée', $type->getTitle());
        
        // ORDER_DELIVERED utilise push + sms
        $channels = $type->getChannel();
        $this->assertContains('push', $channels);
        $this->assertContains('sms', $channels);
    }

    public function test_notification_type_order_cancelled(): void
    {
        $type = NotificationType::ORDER_CANCELLED;
        
        $this->assertEquals('order_cancelled', $type->value);
        $this->assertEquals('Commande annulée', $type->getTitle());
    }

    public function test_notification_type_new_order_available(): void
    {
        $type = NotificationType::NEW_ORDER_AVAILABLE;
        
        $this->assertEquals('new_order_available', $type->value);
        $this->assertEquals('Nouvelle commande disponible', $type->getTitle());
    }

    public function test_notification_type_order_accepted(): void
    {
        $type = NotificationType::ORDER_ACCEPTED;
        
        $this->assertEquals('order_accepted', $type->value);
        $this->assertEquals('Commande acceptée', $type->getTitle());
    }

    public function test_notification_type_payment_received(): void
    {
        $type = NotificationType::PAYMENT_RECEIVED;
        
        $this->assertEquals('payment_received', $type->value);
        $this->assertEquals('Paiement reçu', $type->getTitle());
    }

    public function test_notification_type_payment_failed(): void
    {
        $type = NotificationType::PAYMENT_FAILED;
        
        $this->assertEquals('payment_failed', $type->value);
        $this->assertEquals('Échec du paiement', $type->getTitle());
    }

    public function test_notification_type_wallet_credited(): void
    {
        $type = NotificationType::WALLET_CREDITED;
        
        $this->assertEquals('wallet_credited', $type->value);
        $this->assertEquals('Portefeuille crédité', $type->getTitle());
    }

    public function test_notification_type_withdrawal_requested(): void
    {
        $type = NotificationType::WITHDRAWAL_REQUESTED;
        
        $this->assertEquals('withdrawal_requested', $type->value);
        $this->assertEquals('Demande de retrait', $type->getTitle());
        $this->assertContains('push', $type->getChannel());
    }

    public function test_notification_type_withdrawal_approved(): void
    {
        $type = NotificationType::WITHDRAWAL_APPROVED;
        
        $this->assertEquals('withdrawal_approved', $type->value);
        $this->assertEquals('Retrait approuvé', $type->getTitle());
    }

    public function test_notification_type_withdrawal_completed(): void
    {
        $type = NotificationType::WITHDRAWAL_COMPLETED;
        
        $this->assertEquals('withdrawal_completed', $type->value);
        $this->assertEquals('Retrait effectué', $type->getTitle());
        
        // WITHDRAWAL_COMPLETED utilise push + sms
        $channels = $type->getChannel();
        $this->assertContains('push', $channels);
        $this->assertContains('sms', $channels);
    }

    public function test_notification_type_withdrawal_rejected(): void
    {
        $type = NotificationType::WITHDRAWAL_REJECTED;
        
        $this->assertEquals('withdrawal_rejected', $type->value);
        $this->assertEquals('Retrait rejeté', $type->getTitle());
    }

    public function test_notification_type_promotional(): void
    {
        $type = NotificationType::PROMOTIONAL;
        
        $this->assertEquals('promotional', $type->value);
        $this->assertEquals('Offre spéciale', $type->getTitle());
        
        // PROMOTIONAL est optionnel
        $this->assertTrue($type->isOptional());
    }

    // ==================== getDefaultMessage Tests ====================

    public function test_get_default_message_order_created(): void
    {
        $message = NotificationType::ORDER_CREATED->getDefaultMessage(['order_number' => 'ABC123']);
        $this->assertStringContainsString('ABC123', $message);
    }

    public function test_get_default_message_order_confirmed(): void
    {
        $message = NotificationType::ORDER_CONFIRMED->getDefaultMessage(['order_number' => 'XYZ789']);
        $this->assertStringContainsString('XYZ789', $message);
    }

    public function test_get_default_message_payment_received(): void
    {
        $message = NotificationType::PAYMENT_RECEIVED->getDefaultMessage(['amount' => 5000]);
        $this->assertStringContainsString('5000', $message);
        $this->assertStringContainsString('FCFA', $message);
    }

    public function test_get_default_message_new_order_available(): void
    {
        $message = NotificationType::NEW_ORDER_AVAILABLE->getDefaultMessage(['distance' => 3.5]);
        $this->assertStringContainsString('3.5', $message);
    }

    public function test_get_default_message_wallet_credited(): void
    {
        $message = NotificationType::WALLET_CREDITED->getDefaultMessage(['amount' => 10000]);
        $this->assertStringContainsString('10000', $message);
        $this->assertStringContainsString('FCFA', $message);
    }

    public function test_get_default_message_withdrawal_completed(): void
    {
        $message = NotificationType::WITHDRAWAL_COMPLETED->getDefaultMessage(['amount' => 25000]);
        $this->assertStringContainsString('25000', $message);
        $this->assertStringContainsString('FCFA', $message);
    }

    public function test_get_default_message_with_missing_data(): void
    {
        $message = NotificationType::ORDER_CREATED->getDefaultMessage([]);
        $this->assertNotEmpty($message);
        
        $message = NotificationType::PAYMENT_RECEIVED->getDefaultMessage([]);
        $this->assertStringContainsString('?', $message);
    }

    public function test_get_default_message_promotional(): void
    {
        $message = NotificationType::PROMOTIONAL->getDefaultMessage(['message' => 'Super promo!']);
        $this->assertStringContainsString('Super promo!', $message);
        
        // Sans message personnalisé
        $message = NotificationType::PROMOTIONAL->getDefaultMessage([]);
        $this->assertStringContainsString('offres', $message);
    }

    // ==================== isOptional Tests ====================

    public function test_is_optional_only_promotional(): void
    {
        // Seul PROMOTIONAL est optionnel
        $this->assertTrue(NotificationType::PROMOTIONAL->isOptional());
        
        // Tous les autres sont obligatoires
        $this->assertFalse(NotificationType::ORDER_CREATED->isOptional());
        $this->assertFalse(NotificationType::ORDER_DELIVERED->isOptional());
        $this->assertFalse(NotificationType::PAYMENT_RECEIVED->isOptional());
        $this->assertFalse(NotificationType::WITHDRAWAL_COMPLETED->isOptional());
    }

    // ==================== getChannel Tests ====================

    public function test_get_channel_push_and_sms(): void
    {
        // Types avec push + sms
        $pushAndSmsTypes = [
            NotificationType::ORDER_DELIVERED,
            NotificationType::WITHDRAWAL_COMPLETED,
        ];

        foreach ($pushAndSmsTypes as $type) {
            $channels = $type->getChannel();
            $this->assertContains('push', $channels, "{$type->value} should have push channel");
            $this->assertContains('sms', $channels, "{$type->value} should have sms channel");
        }
    }

    public function test_get_channel_push_only(): void
    {
        // Types avec push seulement
        $pushOnlyTypes = [
            NotificationType::ORDER_CREATED,
            NotificationType::ORDER_CONFIRMED,
            NotificationType::ORDER_ASSIGNED,
            NotificationType::ORDER_PICKED_UP,
            NotificationType::ORDER_IN_TRANSIT,
            NotificationType::ORDER_CANCELLED,
            NotificationType::NEW_ORDER_AVAILABLE,
            NotificationType::ORDER_ACCEPTED,
            NotificationType::PAYMENT_RECEIVED,
            NotificationType::PAYMENT_FAILED,
            NotificationType::WALLET_CREDITED,
            NotificationType::WITHDRAWAL_REQUESTED,
            NotificationType::WITHDRAWAL_APPROVED,
            NotificationType::WITHDRAWAL_REJECTED,
            NotificationType::PROMOTIONAL,
        ];

        foreach ($pushOnlyTypes as $type) {
            $channels = $type->getChannel();
            $this->assertContains('push', $channels, "{$type->value} should have push channel");
            $this->assertNotContains('sms', $channels, "{$type->value} should not have sms channel");
        }
    }

    // ==================== All Types Have Required Methods ====================

    public function test_all_types_have_title(): void
    {
        foreach (NotificationType::cases() as $type) {
            $title = $type->getTitle();
            $this->assertIsString($title, "{$type->value} should have a string title");
            $this->assertNotEmpty($title, "{$type->value} should have a non-empty title");
        }
    }

    public function test_all_types_have_channel(): void
    {
        foreach (NotificationType::cases() as $type) {
            $channels = $type->getChannel();
            $this->assertIsArray($channels, "{$type->value} should have array of channels");
            $this->assertNotEmpty($channels, "{$type->value} should have at least one channel");
        }
    }

    public function test_all_types_have_default_message(): void
    {
        foreach (NotificationType::cases() as $type) {
            $message = $type->getDefaultMessage();
            $this->assertIsString($message, "{$type->value} should have a string message");
            $this->assertNotEmpty($message, "{$type->value} should have a non-empty message");
        }
    }

    // ==================== Value Consistency Tests ====================

    public function test_notification_type_values_are_snake_case(): void
    {
        foreach (NotificationType::cases() as $type) {
            $this->assertMatchesRegularExpression(
                '/^[a-z_]+$/',
                $type->value,
                "Type {$type->name} value should be snake_case"
            );
        }
    }
}

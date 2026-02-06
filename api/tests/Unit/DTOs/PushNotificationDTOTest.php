<?php

namespace Tests\Unit\DTOs;

use App\DTOs\PushNotificationDTO;
use App\Enums\NotificationChannel;
use PHPUnit\Framework\TestCase;

/**
 * Tests pour le DTO PushNotificationDTO
 * 
 * Teste la création de notifications push et les templates métier
 */
class PushNotificationDTOTest extends TestCase
{
    // =========================================================================
    // TESTS DU CONSTRUCTEUR
    // =========================================================================

    /** @test */
    public function it_can_be_instantiated_with_required_parameters(): void
    {
        $dto = new PushNotificationDTO(
            title: 'Test Title',
            body: 'Test Body',
            channel: NotificationChannel::GENERAL,
        );

        $this->assertEquals('Test Title', $dto->title);
        $this->assertEquals('Test Body', $dto->body);
        $this->assertEquals(NotificationChannel::GENERAL, $dto->channel);
    }

    /** @test */
    public function it_has_default_values_for_optional_parameters(): void
    {
        $dto = new PushNotificationDTO(
            title: 'Test',
            body: 'Test',
            channel: NotificationChannel::GENERAL,
        );

        $this->assertEquals([], $dto->data);
        $this->assertNull($dto->imageUrl);
        $this->assertNull($dto->actionUrl);
        $this->assertEquals([], $dto->actions);
        $this->assertNull($dto->badgeCount);
        $this->assertFalse($dto->silent);
    }

    /** @test */
    public function it_accepts_all_optional_parameters(): void
    {
        $dto = new PushNotificationDTO(
            title: 'Test',
            body: 'Test',
            channel: NotificationChannel::GENERAL,
            data: ['key' => 'value'],
            imageUrl: 'https://example.com/image.jpg',
            actionUrl: 'https://example.com/action',
            actions: [['action' => 'view', 'title' => 'View']],
            badgeCount: 5,
            silent: true,
        );

        $this->assertEquals(['key' => 'value'], $dto->data);
        $this->assertEquals('https://example.com/image.jpg', $dto->imageUrl);
        $this->assertEquals('https://example.com/action', $dto->actionUrl);
        $this->assertCount(1, $dto->actions);
        $this->assertEquals(5, $dto->badgeCount);
        $this->assertTrue($dto->silent);
    }

    /** @test */
    public function properties_are_readonly(): void
    {
        $dto = new PushNotificationDTO(
            title: 'Test',
            body: 'Test',
            channel: NotificationChannel::GENERAL,
        );

        $reflection = new \ReflectionClass($dto);
        $titleProperty = $reflection->getProperty('title');
        
        $this->assertTrue($titleProperty->isReadOnly());
    }

    // =========================================================================
    // TESTS DE forOrderCreated
    // =========================================================================

    /** @test */
    public function for_order_created_returns_correct_notification(): void
    {
        $dto = PushNotificationDTO::forOrderCreated('TRK-12345');

        $this->assertStringContainsString('Commande créée', $dto->title);
        $this->assertStringContainsString('TRK-12345', $dto->body);
        $this->assertEquals(NotificationChannel::ORDER_STATUS, $dto->channel);
    }

    /** @test */
    public function for_order_created_includes_tracking_number_in_data(): void
    {
        $dto = PushNotificationDTO::forOrderCreated('TRK-12345');

        $this->assertArrayHasKey('type', $dto->data);
        $this->assertEquals('order_created', $dto->data['type']);
        $this->assertArrayHasKey('tracking_number', $dto->data);
        $this->assertEquals('TRK-12345', $dto->data['tracking_number']);
    }

    /** @test */
    public function for_order_created_has_celebration_emoji(): void
    {
        $dto = PushNotificationDTO::forOrderCreated('TRK-001');
        
        $this->assertStringContainsString('🎉', $dto->title);
    }

    // =========================================================================
    // TESTS DE forNewOrderAvailable
    // =========================================================================

    /** @test */
    public function for_new_order_available_returns_correct_notification(): void
    {
        $dto = PushNotificationDTO::forNewOrderAvailable(
            orderId: 'order-123',
            pickupAddress: 'Zone A, Ouagadougou',
            deliveryAddress: 'Zone B, Ouagadougou',
            distance: 5.5,
            earnings: 2500,
        );

        $this->assertStringContainsString('Nouvelle course', $dto->title);
        $this->assertEquals(NotificationChannel::NEW_ORDER, $dto->channel);
    }

    /** @test */
    public function for_new_order_available_includes_all_details_in_body(): void
    {
        $dto = PushNotificationDTO::forNewOrderAvailable(
            orderId: 'order-123',
            pickupAddress: 'Zone A',
            deliveryAddress: 'Zone B',
            distance: 5.5,
            earnings: 2500,
        );

        $this->assertStringContainsString('Zone A', $dto->body);
        $this->assertStringContainsString('Zone B', $dto->body);
        $this->assertStringContainsString('5.5', $dto->body);
        $this->assertStringContainsString('2500', $dto->body);
        $this->assertStringContainsString('FCFA', $dto->body);
    }

    /** @test */
    public function for_new_order_available_has_accept_and_reject_actions(): void
    {
        $dto = PushNotificationDTO::forNewOrderAvailable(
            orderId: 'order-123',
            pickupAddress: 'Zone A',
            deliveryAddress: 'Zone B',
            distance: 5.0,
            earnings: 2000,
        );

        $this->assertCount(2, $dto->actions);
        
        $actionNames = array_column($dto->actions, 'action');
        $this->assertContains('accept', $actionNames);
        $this->assertContains('reject', $actionNames);
    }

    /** @test */
    public function for_new_order_available_includes_all_data(): void
    {
        $dto = PushNotificationDTO::forNewOrderAvailable(
            orderId: 'order-123',
            pickupAddress: 'Zone A',
            deliveryAddress: 'Zone B',
            distance: 5.5,
            earnings: 2500,
        );

        $this->assertEquals('new_order', $dto->data['type']);
        $this->assertEquals('order-123', $dto->data['order_id']);
        $this->assertEquals('Zone A', $dto->data['pickup_address']);
        $this->assertEquals('Zone B', $dto->data['delivery_address']);
        $this->assertEquals('5.5', $dto->data['distance']);
        $this->assertEquals('2500', $dto->data['earnings']);
    }

    // =========================================================================
    // TESTS DE forOrderAssigned
    // =========================================================================

    /** @test */
    public function for_order_assigned_returns_correct_notification(): void
    {
        $dto = PushNotificationDTO::forOrderAssigned(
            orderId: 'order-123',
            courierName: 'Jean Dupont',
        );

        $this->assertStringContainsString('Coursier', $dto->title);
        $this->assertStringContainsString('Jean Dupont', $dto->body);
        $this->assertEquals(NotificationChannel::ORDER_STATUS, $dto->channel);
    }

    /** @test */
    public function for_order_assigned_can_include_courier_photo(): void
    {
        $dto = PushNotificationDTO::forOrderAssigned(
            orderId: 'order-123',
            courierName: 'Jean',
            courierPhoto: 'https://example.com/photo.jpg',
        );

        $this->assertEquals('https://example.com/photo.jpg', $dto->imageUrl);
    }

    /** @test */
    public function for_order_assigned_has_track_and_call_actions(): void
    {
        $dto = PushNotificationDTO::forOrderAssigned(
            orderId: 'order-123',
            courierName: 'Jean',
        );

        $actionNames = array_column($dto->actions, 'action');
        $this->assertContains('track', $actionNames);
        $this->assertContains('call', $actionNames);
    }

    // =========================================================================
    // TESTS DE forOrderPickedUp
    // =========================================================================

    /** @test */
    public function for_order_picked_up_returns_correct_notification(): void
    {
        $dto = PushNotificationDTO::forOrderPickedUp(
            orderId: 'order-123',
            deliveryAddress: 'Zone B, Ouagadougou',
        );

        $this->assertStringContainsString('Colis', $dto->title);
        $this->assertStringContainsString('en route', $dto->title);
        $this->assertStringContainsString('Zone B', $dto->body);
        $this->assertEquals(NotificationChannel::ORDER_STATUS, $dto->channel);
    }

    /** @test */
    public function for_order_picked_up_has_track_action(): void
    {
        $dto = PushNotificationDTO::forOrderPickedUp('order-123', 'Zone B');

        $this->assertNotEmpty($dto->actions);
        $actionNames = array_column($dto->actions, 'action');
        $this->assertContains('track', $actionNames);
    }

    // =========================================================================
    // TESTS DE forOrderDelivered
    // =========================================================================

    /** @test */
    public function for_order_delivered_returns_correct_notification(): void
    {
        $dto = PushNotificationDTO::forOrderDelivered(
            orderId: 'order-123',
            recipientName: 'Marie',
            totalAmount: 5000,
        );

        $this->assertStringContainsString('Livraison effectuée', $dto->title);
        $this->assertStringContainsString('Marie', $dto->body);
        $this->assertStringContainsString('OUAGA CHAP', $dto->body);
        $this->assertEquals(NotificationChannel::ORDER_STATUS, $dto->channel);
    }

    /** @test */
    public function for_order_delivered_has_rate_and_order_again_actions(): void
    {
        $dto = PushNotificationDTO::forOrderDelivered('order-123', 'Marie', 5000);

        $actionNames = array_column($dto->actions, 'action');
        $this->assertContains('rate', $actionNames);
        $this->assertContains('order_again', $actionNames);
    }

    /** @test */
    public function for_order_delivered_includes_amount_in_data(): void
    {
        $dto = PushNotificationDTO::forOrderDelivered('order-123', 'Marie', 5000);

        $this->assertEquals('5000', $dto->data['amount']);
    }

    // =========================================================================
    // TESTS DE forEarningsCredited
    // =========================================================================

    /** @test */
    public function for_earnings_credited_returns_correct_notification(): void
    {
        $dto = PushNotificationDTO::forEarningsCredited(
            orderId: 'order-123',
            orderNumber: 'CMD-456',
            amount: 2000,
            newBalance: 15000,
        );

        $this->assertStringContainsString('Gain crédité', $dto->title);
        $this->assertStringContainsString('+2000', $dto->body);
        $this->assertStringContainsString('CMD-456', $dto->body);
        $this->assertStringContainsString('15000', $dto->body);
        $this->assertEquals(NotificationChannel::PAYMENTS, $dto->channel);
    }

    /** @test */
    public function for_earnings_credited_uses_money_emoji(): void
    {
        $dto = PushNotificationDTO::forEarningsCredited('order-123', 'CMD-456', 2000, 15000);

        $this->assertStringContainsString('💵', $dto->title);
    }

    // =========================================================================
    // TESTS DE forPaymentReceived
    // =========================================================================

    /** @test */
    public function for_payment_received_returns_correct_notification(): void
    {
        $dto = PushNotificationDTO::forPaymentReceived(
            orderId: 'order-123',
            amount: 5000,
            paymentMethod: 'Orange Money',
        );

        $this->assertStringContainsString('Paiement confirmé', $dto->title);
        $this->assertStringContainsString('5000', $dto->body);
        $this->assertStringContainsString('Orange Money', $dto->body);
        $this->assertEquals(NotificationChannel::PAYMENTS, $dto->channel);
    }

    // =========================================================================
    // TESTS DE forNewChatMessage
    // =========================================================================

    /** @test */
    public function for_new_chat_message_returns_correct_notification(): void
    {
        $dto = PushNotificationDTO::forNewChatMessage(
            orderId: 'order-123',
            senderName: 'Jean',
            messagePreview: 'Je suis arrivé devant la porte',
        );

        $this->assertStringContainsString('Jean', $dto->title);
        $this->assertStringContainsString('Je suis arrivé', $dto->body);
        $this->assertEquals(NotificationChannel::CHAT, $dto->channel);
    }

    /** @test */
    public function for_new_chat_message_has_reply_action(): void
    {
        $dto = PushNotificationDTO::forNewChatMessage('order-123', 'Jean', 'Hello');

        $actionNames = array_column($dto->actions, 'action');
        $this->assertContains('reply', $actionNames);
    }

    /** @test */
    public function for_new_chat_message_uses_chat_emoji(): void
    {
        $dto = PushNotificationDTO::forNewChatMessage('order-123', 'Jean', 'Hello');

        $this->assertStringContainsString('💬', $dto->title);
    }

    // =========================================================================
    // TESTS DE forNewRating
    // =========================================================================

    /** @test */
    public function for_new_rating_returns_correct_notification(): void
    {
        $dto = PushNotificationDTO::forNewRating(
            rating: 5,
            comment: 'Excellent service!',
            newAverage: 4.8,
        );

        $this->assertStringContainsString('évaluation', $dto->title);
        $this->assertStringContainsString('Excellent service!', $dto->body);
        $this->assertStringContainsString('4.8', $dto->body);
        $this->assertEquals(NotificationChannel::RATINGS, $dto->channel);
    }

    /** @test */
    public function for_new_rating_shows_correct_number_of_stars(): void
    {
        $dto = PushNotificationDTO::forNewRating(5, null, 4.8);
        $this->assertEquals(5, substr_count($dto->title, '⭐'));

        $dto = PushNotificationDTO::forNewRating(3, null, 3.5);
        $this->assertEquals(3, substr_count($dto->title, '⭐'));

        $dto = PushNotificationDTO::forNewRating(1, null, 1.0);
        $this->assertEquals(1, substr_count($dto->title, '⭐'));
    }

    /** @test */
    public function for_new_rating_handles_null_comment(): void
    {
        $dto = PushNotificationDTO::forNewRating(
            rating: 4,
            comment: null,
            newAverage: 4.2,
        );

        $this->assertStringNotContainsString('""', $dto->body);
        $this->assertStringContainsString('4.2', $dto->body);
    }

    // =========================================================================
    // TESTS DE forPromotion
    // =========================================================================

    /** @test */
    public function for_promotion_returns_correct_notification(): void
    {
        $dto = PushNotificationDTO::forPromotion(
            title: 'Offre spéciale',
            body: '-20% sur votre prochaine livraison',
            promoCode: 'PROMO20',
            imageUrl: 'https://example.com/promo.jpg',
        );

        $this->assertStringContainsString('Offre spéciale', $dto->title);
        $this->assertStringContainsString('🎁', $dto->title);
        $this->assertStringContainsString('-20%', $dto->body);
        $this->assertEquals(NotificationChannel::PROMOTIONS, $dto->channel);
        $this->assertEquals('PROMO20', $dto->data['promo_code']);
        $this->assertEquals('https://example.com/promo.jpg', $dto->imageUrl);
    }

    /** @test */
    public function for_promotion_handles_null_promo_code(): void
    {
        $dto = PushNotificationDTO::forPromotion(
            title: 'Offre',
            body: 'Description',
        );

        $this->assertNull($dto->data['promo_code']);
    }

    // =========================================================================
    // TESTS DE forCourierArriving
    // =========================================================================

    /** @test */
    public function for_courier_arriving_returns_correct_notification(): void
    {
        $dto = PushNotificationDTO::forCourierArriving(
            orderId: 'order-123',
            minutesAway: 5,
        );

        $this->assertStringContainsString('Coursier en approche', $dto->title);
        $this->assertStringContainsString('5 minutes', $dto->body);
        $this->assertEquals(NotificationChannel::ORDER_STATUS, $dto->channel);
    }

    /** @test */
    public function for_courier_arriving_shows_almost_there_when_very_close(): void
    {
        $dto = PushNotificationDTO::forCourierArriving('order-123', 1);
        $this->assertStringContainsString('presque arrivé', $dto->body);

        $dto = PushNotificationDTO::forCourierArriving('order-123', 2);
        $this->assertStringContainsString('presque arrivé', $dto->body);
    }

    /** @test */
    public function for_courier_arriving_shows_eta_when_further(): void
    {
        $dto = PushNotificationDTO::forCourierArriving('order-123', 10);
        $this->assertStringContainsString('10 minutes', $dto->body);
        $this->assertStringNotContainsString('presque arrivé', $dto->body);
    }

    // =========================================================================
    // TESTS DE urgent
    // =========================================================================

    /** @test */
    public function urgent_returns_notification_with_urgent_channel(): void
    {
        $dto = PushNotificationDTO::urgent(
            title: 'Alerte',
            body: 'Message urgent',
            data: ['custom' => 'value'],
        );

        $this->assertStringContainsString('🚨', $dto->title);
        $this->assertStringContainsString('Alerte', $dto->title);
        $this->assertEquals('Message urgent', $dto->body);
        $this->assertEquals(NotificationChannel::URGENT, $dto->channel);
    }

    /** @test */
    public function urgent_merges_custom_data_with_type(): void
    {
        $dto = PushNotificationDTO::urgent('Title', 'Body', ['key' => 'value']);

        $this->assertEquals('urgent', $dto->data['type']);
        $this->assertEquals('value', $dto->data['key']);
    }

    // =========================================================================
    // TESTS DE toArray
    // =========================================================================

    /** @test */
    public function to_array_returns_complete_array(): void
    {
        $dto = new PushNotificationDTO(
            title: 'Test Title',
            body: 'Test Body',
            channel: NotificationChannel::GENERAL,
            data: ['key' => 'value'],
            imageUrl: 'https://example.com/image.jpg',
            actions: [['action' => 'view', 'title' => 'View']],
            badgeCount: 3,
            silent: true,
        );

        $array = $dto->toArray();

        $this->assertEquals('Test Title', $array['title']);
        $this->assertEquals('Test Body', $array['body']);
        $this->assertEquals('general', $array['channel']);
        $this->assertEquals(['key' => 'value'], $array['data']);
        $this->assertEquals('https://example.com/image.jpg', $array['image_url']);
        $this->assertEquals([['action' => 'view', 'title' => 'View']], $array['actions']);
        $this->assertEquals(3, $array['badge_count']);
        $this->assertTrue($array['silent']);
    }

    /** @test */
    public function to_array_uses_channel_value_not_enum(): void
    {
        $dto = new PushNotificationDTO(
            title: 'Test',
            body: 'Test',
            channel: NotificationChannel::ORDER_STATUS,
        );

        $array = $dto->toArray();

        $this->assertEquals('order_status', $array['channel']);
        $this->assertIsString($array['channel']);
    }

    // =========================================================================
    // TESTS DE VALIDATION DES DONNÉES
    // =========================================================================

    /** @test */
    public function data_values_are_strings_in_templates(): void
    {
        // Vérifie que les valeurs numériques sont converties en strings pour FCM
        $dto = PushNotificationDTO::forNewOrderAvailable(
            orderId: 'order-123',
            pickupAddress: 'Zone A',
            deliveryAddress: 'Zone B',
            distance: 5.5,
            earnings: 2500,
        );

        $this->assertIsString($dto->data['distance']);
        $this->assertIsString($dto->data['earnings']);
    }

    /** @test */
    public function emoji_prefixes_are_consistent(): void
    {
        // Vérifie que les templates critiques ont des emojis
        // Range étendu pour inclure tous les emojis (Misc symbols, dingbats, emoticons, etc.)
        $emojiPattern = '/[\x{1F300}-\x{1F9FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}\x{2702}-\x{27B0}\x{2705}\x{2714}]/u';
        
        $orderCreated = PushNotificationDTO::forOrderCreated('TRK-001');
        $this->assertMatchesRegularExpression($emojiPattern, $orderCreated->title);

        $newOrder = PushNotificationDTO::forNewOrderAvailable('1', 'A', 'B', 5.0, 2000);
        $this->assertMatchesRegularExpression($emojiPattern, $newOrder->title);

        $delivered = PushNotificationDTO::forOrderDelivered('1', 'Marie', 5000);
        $this->assertMatchesRegularExpression($emojiPattern, $delivered->title);
    }

    // =========================================================================
    // TESTS DE COHÉRENCE MÉTIER
    // =========================================================================

    /** @test */
    public function courier_facing_notifications_use_new_order_channel(): void
    {
        $dto = PushNotificationDTO::forNewOrderAvailable('1', 'A', 'B', 5.0, 2000);
        $this->assertEquals(NotificationChannel::NEW_ORDER, $dto->channel);
    }

    /** @test */
    public function client_facing_order_notifications_use_order_status_channel(): void
    {
        $this->assertEquals(NotificationChannel::ORDER_STATUS, PushNotificationDTO::forOrderCreated('TRK-001')->channel);
        $this->assertEquals(NotificationChannel::ORDER_STATUS, PushNotificationDTO::forOrderAssigned('1', 'Jean')->channel);
        $this->assertEquals(NotificationChannel::ORDER_STATUS, PushNotificationDTO::forOrderPickedUp('1', 'Zone B')->channel);
        $this->assertEquals(NotificationChannel::ORDER_STATUS, PushNotificationDTO::forOrderDelivered('1', 'Marie', 5000)->channel);
        $this->assertEquals(NotificationChannel::ORDER_STATUS, PushNotificationDTO::forCourierArriving('1', 5)->channel);
    }

    /** @test */
    public function payment_notifications_use_payments_channel(): void
    {
        $this->assertEquals(NotificationChannel::PAYMENTS, PushNotificationDTO::forEarningsCredited('1', 'CMD-001', 2000, 15000)->channel);
        $this->assertEquals(NotificationChannel::PAYMENTS, PushNotificationDTO::forPaymentReceived('1', 5000, 'Cash')->channel);
    }

    /** @test */
    public function chat_notifications_use_chat_channel(): void
    {
        $this->assertEquals(NotificationChannel::CHAT, PushNotificationDTO::forNewChatMessage('1', 'Jean', 'Hello')->channel);
    }

    /** @test */
    public function rating_notifications_use_ratings_channel(): void
    {
        $this->assertEquals(NotificationChannel::RATINGS, PushNotificationDTO::forNewRating(5, 'Super!', 4.8)->channel);
    }

    /** @test */
    public function promotional_notifications_use_promotions_channel(): void
    {
        $this->assertEquals(NotificationChannel::PROMOTIONS, PushNotificationDTO::forPromotion('Offre', 'Description')->channel);
    }
}

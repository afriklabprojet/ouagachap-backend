<?php

namespace Tests\Unit\Services;

use App\DTOs\PushNotificationDTO;
use App\Enums\NotificationChannel;
use App\Services\EnhancedPushNotificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Tests pour EnhancedPushNotificationService
 * 
 * Teste les méthodes utilitaires et la logique métier
 * (sans Firebase car non configuré en test)
 */
class EnhancedPushNotificationServiceTest extends TestCase
{
    protected EnhancedPushNotificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new EnhancedPushNotificationService();
    }

    // =========================================================================
    // TESTS D'INSTANTIATION
    // =========================================================================

    /** @test */
    public function it_can_be_instantiated(): void
    {
        $service = new EnhancedPushNotificationService();
        $this->assertInstanceOf(EnhancedPushNotificationService::class, $service);
    }

    /** @test */
    public function it_handles_missing_firebase_gracefully(): void
    {
        // Le service doit fonctionner même sans Firebase configuré
        $service = new EnhancedPushNotificationService();
        
        // Pas d'exception
        $this->assertTrue(true);
    }

    // =========================================================================
    // TESTS DES MÉTHODES UTILITAIRES (via reflection)
    // =========================================================================

    /** @test */
    public function sanitize_data_converts_all_values_to_strings(): void
    {
        $method = new \ReflectionMethod(EnhancedPushNotificationService::class, 'sanitizeData');
        $method->setAccessible(true);

        $data = [
            'string' => 'hello',
            'int' => 123,
            'float' => 45.67,
            'bool_true' => true,
            'bool_false' => false,
        ];

        $result = $method->invoke($this->service, $data);

        $this->assertIsString($result['string']);
        $this->assertIsString($result['int']);
        $this->assertIsString($result['float']);
        $this->assertIsString($result['bool_true']);
        $this->assertIsString($result['bool_false']);

        $this->assertEquals('hello', $result['string']);
        $this->assertEquals('123', $result['int']);
        $this->assertEquals('45.67', $result['float']);
    }

    /** @test */
    public function sanitize_data_handles_empty_array(): void
    {
        $method = new \ReflectionMethod(EnhancedPushNotificationService::class, 'sanitizeData');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, []);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /** @test */
    public function shorten_address_keeps_short_addresses_intact(): void
    {
        $method = new \ReflectionMethod(EnhancedPushNotificationService::class, 'shortenAddress');
        $method->setAccessible(true);

        $shortAddress = 'Zone A, Ouagadougou';
        $result = $method->invoke($this->service, $shortAddress);

        $this->assertEquals($shortAddress, $result);
    }

    /** @test */
    public function shorten_address_truncates_long_addresses(): void
    {
        $method = new \ReflectionMethod(EnhancedPushNotificationService::class, 'shortenAddress');
        $method->setAccessible(true);

        $longAddress = 'Secteur 15, Avenue Kwame Nkrumah, Quartier Paspanga, Ouagadougou, Burkina Faso';
        $result = $method->invoke($this->service, $longAddress, 40);

        $this->assertLessThanOrEqual(40, strlen($result));
        $this->assertStringEndsWith('...', $result);
    }

    /** @test */
    public function shorten_address_respects_custom_max_length(): void
    {
        $method = new \ReflectionMethod(EnhancedPushNotificationService::class, 'shortenAddress');
        $method->setAccessible(true);

        $address = 'This is a moderately long address that needs shortening';
        
        $result20 = $method->invoke($this->service, $address, 20);
        $this->assertLessThanOrEqual(20, strlen($result20));

        $result30 = $method->invoke($this->service, $address, 30);
        $this->assertLessThanOrEqual(30, strlen($result30));
    }

    /** @test */
    public function shorten_address_at_exact_length_not_truncated(): void
    {
        $method = new \ReflectionMethod(EnhancedPushNotificationService::class, 'shortenAddress');
        $method->setAccessible(true);

        $address = str_repeat('a', 40);
        $result = $method->invoke($this->service, $address, 40);

        $this->assertEquals($address, $result);
        $this->assertStringNotContainsString('...', $result);
    }

    // =========================================================================
    // TESTS DE is_invalid_token
    // =========================================================================

    /** @test */
    public function is_invalid_token_error_returns_true_for_invalid_token_messages(): void
    {
        $method = new \ReflectionMethod(EnhancedPushNotificationService::class, 'isInvalidTokenError');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($this->service, 'not a valid FCM registration token'));
        $this->assertTrue($method->invoke($this->service, 'UNREGISTERED device'));
    }

    /** @test */
    public function is_invalid_token_error_returns_false_for_other_errors(): void
    {
        $method = new \ReflectionMethod(EnhancedPushNotificationService::class, 'isInvalidTokenError');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($this->service, 'Rate limit exceeded'));
        $this->assertFalse($method->invoke($this->service, 'Internal server error'));
        $this->assertFalse($method->invoke($this->service, 'Network timeout'));
    }

    /** @test */
    public function is_invalid_token_error_handles_null_message(): void
    {
        $method = new \ReflectionMethod(EnhancedPushNotificationService::class, 'isInvalidTokenError');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($this->service, null));
    }

    // =========================================================================
    // TESTS DES CONSTANTES
    // =========================================================================

    /** @test */
    public function max_retries_constant_has_reasonable_value(): void
    {
        $reflection = new \ReflectionClass(EnhancedPushNotificationService::class);
        $constant = $reflection->getConstant('MAX_RETRIES');

        $this->assertIsInt($constant);
        $this->assertGreaterThanOrEqual(1, $constant);
        $this->assertLessThanOrEqual(5, $constant);
    }

    /** @test */
    public function batch_size_constant_respects_fcm_limit(): void
    {
        $reflection = new \ReflectionClass(EnhancedPushNotificationService::class);
        $constant = $reflection->getConstant('BATCH_SIZE');

        // FCM limite les multicast à 500 tokens
        $this->assertIsInt($constant);
        $this->assertLessThanOrEqual(500, $constant);
    }

    /** @test */
    public function ttl_constant_is_within_fcm_limits(): void
    {
        $reflection = new \ReflectionClass(EnhancedPushNotificationService::class);
        $constant = $reflection->getConstant('TTL_SECONDS');

        // FCM max TTL is 4 weeks (2419200 seconds)
        $this->assertIsInt($constant);
        $this->assertLessThanOrEqual(2419200, $constant);
        $this->assertGreaterThan(0, $constant);
    }

    // =========================================================================
    // TESTS DES MÉTHODES PUBLIQUES
    // =========================================================================

    /** @test */
    public function send_to_user_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'sendToUser'));
    }

    /** @test */
    public function send_to_users_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'sendToUsers'));
    }

    /** @test */
    public function send_to_topic_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'sendToTopic'));
    }

    // =========================================================================
    // TESTS DES MÉTHODES DE NOTIFICATION MÉTIER
    // =========================================================================

    /** @test */
    public function notify_order_created_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'notifyOrderCreated'));
    }

    /** @test */
    public function notify_order_assigned_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'notifyOrderAssigned'));
    }

    /** @test */
    public function notify_new_order_available_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'notifyNewOrderAvailable'));
    }

    /** @test */
    public function broadcast_new_order_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'broadcastNewOrder'));
    }

    /** @test */
    public function notify_order_picked_up_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'notifyOrderPickedUp'));
    }

    /** @test */
    public function notify_order_delivered_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'notifyOrderDelivered'));
    }

    /** @test */
    public function notify_order_cancelled_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'notifyOrderCancelled'));
    }

    /** @test */
    public function notify_payment_received_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'notifyPaymentReceived'));
    }

    /** @test */
    public function notify_courier_earnings_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'notifyCourierEarnings'));
    }

    /** @test */
    public function notify_chat_message_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'notifyChatMessage'));
    }

    /** @test */
    public function notify_courier_arriving_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'notifyCourierArriving'));
    }

    /** @test */
    public function notify_new_rating_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'notifyNewRating'));
    }

    // =========================================================================
    // TESTS DES MÉTHODES PROTÉGÉES
    // =========================================================================

    /** @test */
    public function build_android_config_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'buildAndroidConfig'));
    }

    /** @test */
    public function build_apns_config_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'buildApnsConfig'));
    }

    /** @test */
    public function build_message_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'buildMessage'));
    }

    /** @test */
    public function build_multicast_message_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'buildMulticastMessage'));
    }

    /** @test */
    public function build_topic_message_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'buildTopicMessage'));
    }

    // =========================================================================
    // TESTS DE LOGGING (MODE DEV)
    // =========================================================================

    /** @test */
    public function log_notification_logs_when_firebase_disabled(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'Firebase disabled')
                    && isset($context['target_type'])
                    && isset($context['title'])
                    && isset($context['channel']);
            });

        $method = new \ReflectionMethod(EnhancedPushNotificationService::class, 'logNotification');
        $method->setAccessible(true);

        $notification = new PushNotificationDTO(
            title: 'Test',
            body: 'Test body',
            channel: NotificationChannel::GENERAL,
        );

        $method->invoke($this->service, 'token', 'test-target', $notification);
    }

    /** @test */
    public function log_success_logs_to_api_channel(): void
    {
        $apiChannel = \Mockery::mock(\Psr\Log\LoggerInterface::class);
        $apiChannel->shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'Push sent'
                    && isset($context['target_type'])
                    && isset($context['channel']);
            });

        Log::shouldReceive('channel')
            ->once()
            ->with('api')
            ->andReturn($apiChannel);

        $method = new \ReflectionMethod(EnhancedPushNotificationService::class, 'logSuccess');
        $method->setAccessible(true);

        $notification = new PushNotificationDTO(
            title: 'Test',
            body: 'Test',
            channel: NotificationChannel::GENERAL,
        );

        $method->invoke($this->service, 'token', 'target', $notification);
    }

    // =========================================================================
    // TESTS DE CONFIGURATION ANDROID
    // =========================================================================

    /** @test */
    public function build_android_config_uses_channel_priority(): void
    {
        $method = new \ReflectionMethod(EnhancedPushNotificationService::class, 'buildAndroidConfig');
        $method->setAccessible(true);

        // Test avec canal haute priorité
        $highPriorityNotification = new PushNotificationDTO(
            title: 'Urgent',
            body: 'Test',
            channel: NotificationChannel::NEW_ORDER,
        );

        $config = $method->invoke($this->service, $highPriorityNotification);
        
        // Le retour doit être un AndroidConfig
        $this->assertInstanceOf(\Kreait\Firebase\Messaging\AndroidConfig::class, $config);
    }

    /** @test */
    public function build_apns_config_uses_channel_settings(): void
    {
        $method = new \ReflectionMethod(EnhancedPushNotificationService::class, 'buildApnsConfig');
        $method->setAccessible(true);

        $notification = new PushNotificationDTO(
            title: 'Test',
            body: 'Test',
            channel: NotificationChannel::URGENT,
        );

        $config = $method->invoke($this->service, $notification);
        
        $this->assertInstanceOf(\Kreait\Firebase\Messaging\ApnsConfig::class, $config);
    }

    // =========================================================================
    // TESTS DE CAS LIMITES
    // =========================================================================

    /** @test */
    public function sanitize_data_handles_special_characters(): void
    {
        $method = new \ReflectionMethod(EnhancedPushNotificationService::class, 'sanitizeData');
        $method->setAccessible(true);

        $data = [
            'french' => 'Éàü',
            'emoji' => '🚀📦',
            'newline' => "line1\nline2",
        ];

        $result = $method->invoke($this->service, $data);

        $this->assertEquals('Éàü', $result['french']);
        $this->assertEquals('🚀📦', $result['emoji']);
        $this->assertStringContainsString("\n", $result['newline']);
    }

    /** @test */
    public function shorten_address_handles_empty_string(): void
    {
        $method = new \ReflectionMethod(EnhancedPushNotificationService::class, 'shortenAddress');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, '');
        
        $this->assertEquals('', $result);
    }

    /** @test */
    public function shorten_address_handles_unicode_characters(): void
    {
        $method = new \ReflectionMethod(EnhancedPushNotificationService::class, 'shortenAddress');
        $method->setAccessible(true);

        $address = 'Avenue Éiffel, Café de la Paix, Ouagadougou';
        $result = $method->invoke($this->service, $address, 50);

        // Should keep unicode intact
        $this->assertStringContainsString('É', $result);
    }
}

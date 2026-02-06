<?php

namespace Tests\Unit;

use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SmsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        // Forcer le driver "log" pour les tests
        config(['sms.default' => 'log']);
        $this->service = new SmsService();
    }

    // ==========================================
    // Tests pour normalizePhone
    // ==========================================

    public function test_normalize_phone_with_local_format(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('normalizePhone');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, '70000000');

        $this->assertEquals('+22670000000', $result);
    }

    public function test_normalize_phone_with_leading_zero(): void
    {
        $result = $this->normalizePhone('070000000');

        $this->assertEquals('+22670000000', $result);
    }

    public function test_normalize_phone_with_country_code(): void
    {
        $result = $this->normalizePhone('22670000000');

        $this->assertEquals('+22670000000', $result);
    }

    public function test_normalize_phone_with_plus_sign(): void
    {
        $result = $this->normalizePhone('+22670000000');

        $this->assertEquals('+22670000000', $result);
    }

    public function test_normalize_phone_removes_spaces(): void
    {
        $result = $this->normalizePhone('70 00 00 00');

        $this->assertEquals('+22670000000', $result);
    }

    public function test_normalize_phone_removes_dashes(): void
    {
        $result = $this->normalizePhone('70-00-00-00');

        $this->assertEquals('+22670000000', $result);
    }

    public function test_normalize_phone_removes_dots(): void
    {
        $result = $this->normalizePhone('70.00.00.00');

        $this->assertEquals('+22670000000', $result);
    }

    public function test_normalize_phone_with_multiple_leading_zeros(): void
    {
        $result = $this->normalizePhone('00070000000');

        $this->assertEquals('+22670000000', $result);
    }

    // ==========================================
    // Tests pour maskPhone
    // ==========================================

    public function test_mask_phone(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('maskPhone');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, '+22670123456');

        $this->assertEquals('+226701****56', $result);
    }

    // ==========================================
    // Tests pour send (via log driver)
    // ==========================================

    public function test_send_sms_via_log_driver(): void
    {
        $result = $this->service->send('70000000', 'Test message');

        $this->assertTrue($result['success']);
        $this->assertEquals('log', $result['provider']);
        $this->assertStringStartsWith('log_', $result['message_id']);
        $this->assertEquals('Test message', $result['debug_message']);
    }

    public function test_send_returns_debug_message_in_log_mode(): void
    {
        $result = $this->service->send('70000000', 'Hello World');

        $this->assertEquals('Hello World', $result['debug_message']);
    }

    // ==========================================
    // Tests pour sendOtp
    // ==========================================

    public function test_send_otp(): void
    {
        // Configure the OTP template
        config(['sms.templates.otp' => 'Votre code OuagaChap est :code. Valide :minutes minutes.']);
        config(['sms.otp.expires_minutes' => 5]);
        
        // Recreate service with new config
        $this->service = new SmsService();

        $result = $this->service->sendOtp('70000000', '123456');

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('123456', $result['debug_message']);
    }

    public function test_send_otp_includes_code_in_message(): void
    {
        config(['sms.templates.otp' => 'Code: :code']);
        $this->service = new SmsService();

        $result = $this->service->sendOtp('70000000', '999888');

        $this->assertStringContainsString('999888', $result['debug_message']);
    }

    // ==========================================
    // Tests pour sendOrderNotification
    // ==========================================

    public function test_send_order_notification_with_template(): void
    {
        config(['sms.templates.order_confirmed' => 'Commande :order_number confirmée!']);
        $this->service = new SmsService();

        $result = $this->service->sendOrderNotification(
            '70000000',
            'order_confirmed',
            ['order_number' => 'CMD-12345']
        );

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('CMD-12345', $result['debug_message']);
    }

    public function test_send_order_notification_replaces_multiple_placeholders(): void
    {
        config(['sms.templates.order_status' => 'Commande :order - Statut: :status']);
        $this->service = new SmsService();

        $result = $this->service->sendOrderNotification(
            '71000000',
            'order_status',
            ['order' => 'CMD-67890', 'status' => 'En cours']
        );

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('CMD-67890', $result['debug_message']);
        $this->assertStringContainsString('En cours', $result['debug_message']);
    }

    // ==========================================
    // Tests pour isConfigured
    // ==========================================

    public function test_is_configured_returns_true_for_log_driver(): void
    {
        $this->assertTrue($this->service->isConfigured());
    }

    public function test_is_configured_returns_false_for_unconfigured_twilio(): void
    {
        config([
            'sms.default' => 'twilio',
            'sms.drivers.twilio.sid' => '',
            'sms.drivers.twilio.token' => '',
            'sms.drivers.twilio.from' => '',
        ]);
        
        $twilioService = new SmsService();

        $this->assertFalse($twilioService->isConfigured());
    }

    public function test_is_configured_returns_true_with_valid_twilio_config(): void
    {
        config([
            'sms.default' => 'twilio',
            'sms.drivers.twilio.sid' => 'ACxxxxxxxxx',
            'sms.drivers.twilio.token' => 'auth_token',
            'sms.drivers.twilio.from' => '+1234567890',
        ]);
        
        $twilioService = new SmsService();

        $this->assertTrue($twilioService->isConfigured());
    }

    // ==========================================
    // Tests pour getDriver
    // ==========================================

    public function test_get_driver_returns_log(): void
    {
        $this->assertEquals('log', $this->service->getDriver());
    }

    public function test_get_driver_returns_twilio(): void
    {
        config(['sms.default' => 'twilio']);
        $twilioService = new SmsService();

        $this->assertEquals('twilio', $twilioService->getDriver());
    }

    // ==========================================
    // Tests pour différents opérateurs BF
    // ==========================================

    public function test_send_to_orange_number(): void
    {
        $result = $this->service->send('70123456', 'Test Orange');

        $this->assertTrue($result['success']);
    }

    public function test_send_to_moov_number(): void
    {
        $result = $this->service->send('71123456', 'Test Moov');

        $this->assertTrue($result['success']);
    }

    public function test_send_to_telecel_number(): void
    {
        $result = $this->service->send('76123456', 'Test Telecel');

        $this->assertTrue($result['success']);
    }

    // ==========================================
    // Tests edge cases
    // ==========================================

    public function test_send_empty_message(): void
    {
        $result = $this->service->send('70000000', '');

        $this->assertTrue($result['success']);
        $this->assertEquals('', $result['debug_message']);
    }

    public function test_send_long_message(): void
    {
        $longMessage = str_repeat('A', 500);
        $result = $this->service->send('70000000', $longMessage);

        $this->assertTrue($result['success']);
        $this->assertEquals(500, strlen($result['debug_message']));
    }

    public function test_send_message_with_special_characters(): void
    {
        $message = "Bonjour! Votre commande #123 est prête. Prix: 1500 FCFA (avec 10% réduction).";
        $result = $this->service->send('70000000', $message);

        $this->assertTrue($result['success']);
        $this->assertEquals($message, $result['debug_message']);
    }

    public function test_send_message_with_emojis(): void
    {
        $message = "📦 Votre colis est en route! 🚚";
        $result = $this->service->send('70000000', $message);

        $this->assertTrue($result['success']);
        $this->assertEquals($message, $result['debug_message']);
    }

    // Helper pour les tests
    private function normalizePhone(string $phone): string
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('normalizePhone');
        $method->setAccessible(true);
        return $method->invoke($this->service, $phone);
    }
}

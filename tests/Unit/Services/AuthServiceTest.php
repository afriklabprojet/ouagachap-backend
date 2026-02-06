<?php

namespace Tests\Unit\Services;

use App\Models\OtpCode;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AuthService $authService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authService = app(AuthService::class);
    }

    // =========================================================================
    // PHONE NORMALIZATION TESTS
    // =========================================================================

    public function test_normalize_phone_removes_country_code(): void
    {
        $this->assertEquals('70123456', $this->authService->normalizePhone('+22670123456'));
        $this->assertEquals('70123456', $this->authService->normalizePhone('0022670123456'));
    }

    public function test_normalize_phone_handles_plain_number(): void
    {
        $this->assertEquals('70123456', $this->authService->normalizePhone('70123456'));
    }

    public function test_normalize_phone_removes_spaces_and_dashes(): void
    {
        $this->assertEquals('70123456', $this->authService->normalizePhone('70 12 34 56'));
        $this->assertEquals('70123456', $this->authService->normalizePhone('70-12-34-56'));
    }

    // =========================================================================
    // OTP SEND TESTS
    // =========================================================================

    public function test_send_otp_creates_otp_code(): void
    {
        // Force SMS mode (not Firebase)
        config(['otp.driver' => 'sms']);
        config(['sms.default' => 'log']);

        $result = $this->authService->sendOtp('70123456');

        $this->assertTrue($result['success']);
        $this->assertEquals('sms', $result['method']);
        $this->assertArrayHasKey('expires_at', $result);
        $this->assertDatabaseHas('otp_codes', [
            'phone' => '70123456',
        ]);
    }

    public function test_send_otp_returns_debug_code_in_dev(): void
    {
        config(['app.debug' => true]);
        config(['otp.driver' => 'sms']);
        config(['sms.default' => 'log']);

        $result = $this->authService->sendOtp('70123456');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('debug_code', $result);
        $this->assertEquals(6, strlen($result['debug_code']));
    }

    // =========================================================================
    // OTP VERIFY TESTS
    // =========================================================================

    public function test_verify_otp_with_valid_code_creates_user(): void
    {
        $phone = '70123456';
        $otp = OtpCode::generate($phone);

        $result = $this->authService->verifyOtp($phone, $otp->code);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('token', $result);
        $this->assertDatabaseHas('users', ['phone' => $phone]);
    }

    public function test_verify_otp_with_invalid_code_fails(): void
    {
        $phone = '70123456';
        OtpCode::generate($phone);

        $result = $this->authService->verifyOtp($phone, '000000');

        $this->assertFalse($result['success']);
        // Le message peut être "invalide" ou "incorrect"
        $this->assertTrue(
            str_contains($result['message'], 'invalide') || 
            str_contains($result['message'], 'incorrect') ||
            str_contains($result['message'], 'Code')
        );
    }

    public function test_verify_otp_with_demo_code_works_in_local(): void
    {
        $this->app['env'] = 'local';
        $phone = '70123456';

        $result = $this->authService->verifyOtp($phone, '123456');

        $this->assertTrue($result['success']);
    }

    public function test_verify_otp_returns_existing_user(): void
    {
        $user = User::factory()->client()->create(['phone' => '70123456']);
        $otp = OtpCode::generate($user->phone);

        $result = $this->authService->verifyOtp($user->phone, $otp->code);

        $this->assertTrue($result['success']);
        $this->assertEquals($user->id, $result['user']->id);
    }

    public function test_verify_otp_rejects_suspended_user(): void
    {
        $user = User::factory()->create([
            'phone' => '70123456',
            'status' => \App\Enums\UserStatus::SUSPENDED,
        ]);
        $otp = OtpCode::generate($user->phone);

        $result = $this->authService->verifyOtp($user->phone, $otp->code);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('suspendu', $result['message']);
    }

    // =========================================================================
    // APP TYPE ROLE VALIDATION TESTS
    // =========================================================================

    public function test_client_cannot_login_to_courier_app(): void
    {
        $this->app['env'] = 'local';
        $client = User::factory()->client()->create(['phone' => '70123456']);

        $result = $this->authService->verifyOtp($client->phone, '123456', 'mobile', 'courier');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('compte client', $result['message']);
        $this->assertStringContainsString('OUAGA CHAP Client', $result['message']);
    }

    public function test_courier_cannot_login_to_client_app(): void
    {
        $this->app['env'] = 'local';
        $courier = User::factory()->courier()->create(['phone' => '70123456']);

        $result = $this->authService->verifyOtp($courier->phone, '123456', 'mobile', 'client');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('compte coursier', $result['message']);
        $this->assertStringContainsString('OUAGA CHAP Coursier', $result['message']);
    }

    public function test_client_can_login_to_client_app(): void
    {
        $this->app['env'] = 'local';
        $client = User::factory()->client()->create(['phone' => '70123456']);

        $result = $this->authService->verifyOtp($client->phone, '123456', 'mobile', 'client');

        $this->assertTrue($result['success']);
        $this->assertEquals($client->id, $result['user']->id);
    }

    public function test_courier_can_login_to_courier_app(): void
    {
        $this->app['env'] = 'local';
        $courier = User::factory()->courier()->active()->create(['phone' => '70123456']);

        $result = $this->authService->verifyOtp($courier->phone, '123456', 'mobile', 'courier');

        $this->assertTrue($result['success']);
        $this->assertEquals($courier->id, $result['user']->id);
    }

    public function test_admin_cannot_login_to_mobile_apps(): void
    {
        $this->app['env'] = 'local';
        $admin = User::factory()->admin()->create(['phone' => '70123456']);

        // Test client app
        $result = $this->authService->verifyOtp($admin->phone, '123456', 'mobile', 'client');
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('administrateurs', $result['message']);

        // Test courier app
        $result = $this->authService->verifyOtp($admin->phone, '123456', 'mobile', 'courier');
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('administrateurs', $result['message']);
    }

    public function test_verify_otp_without_app_type_allows_any_role(): void
    {
        $this->app['env'] = 'local';
        $courier = User::factory()->courier()->active()->create(['phone' => '70123456']);

        // Sans app_type, la connexion devrait être autorisée (rétrocompatibilité)
        $result = $this->authService->verifyOtp($courier->phone, '123456', 'mobile', null);

        $this->assertTrue($result['success']);
    }

    public function test_pending_courier_cannot_login(): void
    {
        $this->app['env'] = 'local';
        $courier = User::factory()->courier()->create([
            'phone' => '70123456',
            'status' => \App\Enums\UserStatus::PENDING,
        ]);

        $result = $this->authService->verifyOtp($courier->phone, '123456', 'mobile', 'courier');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('attente de validation', $result['message']);
    }
}

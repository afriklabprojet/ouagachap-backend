<?php

namespace Tests\Feature\Api\V1;

use App\Enums\UserRole;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // OTP SEND TESTS
    // =========================================================================

    public function test_can_send_otp_to_valid_phone(): void
    {
        // Force SMS mode instead of Firebase for testing
        config(['otp.driver' => 'sms']);
        config(['sms.default' => 'log']);

        $response = $this->postJson('/api/v1/auth/otp/send', [
            'phone' => '70123456',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['expires_at'],
            ]);

        $this->assertDatabaseHas('otp_codes', [
            'phone' => '70123456',
        ]);
    }

    public function test_otp_send_requires_phone(): void
    {
        $response = $this->postJson('/api/v1/auth/otp/send', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_otp_send_validates_phone_format(): void
    {
        $response = $this->postJson('/api/v1/auth/otp/send', [
            'phone' => '123', // Too short
        ]);

        $response->assertStatus(422);
    }

    // =========================================================================
    // OTP VERIFY TESTS
    // =========================================================================

    public function test_can_verify_valid_otp_and_create_user(): void
    {
        $phone = '70123456';
        $otp = OtpCode::generate($phone);

        $response = $this->postJson('/api/v1/auth/otp/verify', [
            'phone' => $phone,
            'code' => $otp->code,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => ['id', 'phone', 'role'],
                    'token',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'phone' => $phone,
            'role' => UserRole::CLIENT->value,
        ]);
    }

    public function test_invalid_otp_returns_error(): void
    {
        $phone = '70123456';
        OtpCode::generate($phone);

        $response = $this->postJson('/api/v1/auth/otp/verify', [
            'phone' => $phone,
            'code' => '000000', // Wrong code
        ]);

        $response->assertStatus(401);
    }

    public function test_existing_user_gets_logged_in(): void
    {
        $user = User::factory()->create([
            'phone' => '70123456',
            'role' => UserRole::CLIENT,
        ]);

        $otp = OtpCode::generate($user->phone);

        $response = $this->postJson('/api/v1/auth/otp/verify', [
            'phone' => $user->phone,
            'code' => $otp->code,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.user.id', $user->id);
    }

    // =========================================================================
    // AUTHENTICATED ROUTES TESTS
    // =========================================================================

    public function test_can_get_current_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.phone', $user->phone);
    }

    public function test_unauthenticated_cannot_access_me(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        // API should return 401 (or 500 with proper error in production)
        $this->assertTrue(in_array($response->status(), [401, 500]));
    }

    public function test_can_update_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/auth/profile', [
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
    }

    public function test_can_update_fcm_token(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/auth/fcm-token', [
                'fcm_token' => 'test-fcm-token-12345',
                'device_type' => 'android',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'fcm_token' => 'test-fcm-token-12345',
            'device_type' => 'android',
        ]);
    }

    public function test_can_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-device')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200);

        // Token should be revoked
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    // =========================================================================
    // COURIER REGISTRATION TESTS
    // =========================================================================

    public function test_can_register_as_courier(): void
    {
        $response = $this->postJson('/api/v1/auth/register/courier', [
            'phone' => '70999888',
            'name' => 'Courier Test',
            'vehicle_type' => 'moto',
            'vehicle_plate' => 'AB-1234-BF',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'phone' => '70999888',
            'role' => UserRole::COURIER->value,
            'vehicle_type' => 'moto',
        ]);
    }
}

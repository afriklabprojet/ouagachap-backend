<?php

namespace Tests\Unit\Models;

use App\Models\OtpCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OtpCodeTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_generates_otp_with_6_digit_code(): void
    {
        $otp = OtpCode::generate('+22670123456', OtpCode::PURPOSE_LOGIN);

        $this->assertNotNull($otp);
        $this->assertEquals(6, strlen($otp->code));
        $this->assertMatchesRegularExpression('/^\d{6}$/', $otp->code);
    }

    /** @test */
    public function it_generates_otp_with_correct_expiration(): void
    {
        $otp = OtpCode::generate('+22670123456');

        $this->assertFalse($otp->isExpired());

        // Le temps restant devrait être positif et proche de 5 minutes
        $remaining = $otp->getTimeRemaining();
        $this->assertGreaterThanOrEqual(0, $remaining);
        $this->assertLessThanOrEqual(305, $remaining); // 5 min + 5s margin
    }

    /** @test */
    public function it_invalidates_existing_codes_when_generating_new_one(): void
    {
        $phone = '+22670123456';

        $firstOtp = OtpCode::generate($phone);
        $firstOtp->refresh(); // Ensure cast is applied
        $this->assertFalse($firstOtp->is_used);

        $secondOtp = OtpCode::generate($phone);

        $firstOtp->refresh();
        $this->assertTrue($firstOtp->is_used);

        $secondOtp->refresh();
        $this->assertFalse($secondOtp->is_used);
    }

    /** @test */
    public function it_stores_ip_address_and_user_agent(): void
    {
        $otp = OtpCode::generate(
            '+22670123456',
            OtpCode::PURPOSE_LOGIN,
            '192.168.1.1',
            'Mozilla/5.0'
        );

        $this->assertEquals('192.168.1.1', $otp->ip_address);
        $this->assertEquals('Mozilla/5.0', $otp->user_agent);
    }

    /** @test */
    public function it_initializes_with_default_values(): void
    {
        $otp = OtpCode::generate('+22670123456');
        $otp->refresh(); // Ensure all casts are applied

        $this->assertEquals(0, $otp->attempts);
        $this->assertEquals(3, $otp->max_attempts);
        $this->assertFalse($otp->is_used);
        $this->assertEquals(OtpCode::PURPOSE_LOGIN, $otp->purpose);
    }

    /** @test */
    public function it_verifies_correct_code_successfully(): void
    {
        $phone = '+22670123456';
        $otp = OtpCode::generate($phone);

        $result = OtpCode::verify($phone, $otp->code);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('succès', $result['message']);

        $otp->refresh();
        $this->assertTrue($otp->is_used);
    }

    /** @test */
    public function it_rejects_incorrect_code(): void
    {
        $phone = '+22670123456';
        $otp = OtpCode::generate($phone);

        $result = OtpCode::verify($phone, '000000');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('incorrect', $result['message']);

        $otp->refresh();
        $this->assertEquals(1, $otp->attempts);
        $this->assertFalse($otp->is_used);
    }

    /** @test */
    public function it_tracks_remaining_attempts(): void
    {
        $phone = '+22670123456';
        $otp = OtpCode::generate($phone);

        $this->assertEquals(3, $otp->getRemainingAttempts());

        OtpCode::verify($phone, '000000'); // Wrong code
        $otp->refresh();
        $this->assertEquals(2, $otp->getRemainingAttempts());

        OtpCode::verify($phone, '111111'); // Wrong code
        $otp->refresh();
        $this->assertEquals(1, $otp->getRemainingAttempts());
    }

    /** @test */
    public function it_marks_used_after_max_attempts(): void
    {
        $phone = '+22670123456';
        $otp = OtpCode::generate($phone);

        OtpCode::verify($phone, '000000');
        OtpCode::verify($phone, '111111');
        $result = OtpCode::verify($phone, '222222');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('maximum', $result['message']);

        $otp->refresh();
        $this->assertTrue($otp->is_used);
        $this->assertTrue($otp->hasMaxAttempts());
    }

    /** @test */
    public function it_rejects_expired_code(): void
    {
        $phone = '+22670123456';
        $otp = OtpCode::factory()->create([
            'phone' => $phone,
            'code' => '123456',
            'expires_at' => now()->subMinute(),
            'is_used' => false,
        ]);

        $result = OtpCode::verify($phone, '123456');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('expiré', $result['message']);
        $this->assertTrue($otp->isExpired());
        $this->assertEquals(0, $otp->getTimeRemaining());
    }

    /** @test */
    public function it_rejects_already_used_code(): void
    {
        $phone = '+22670123456';
        $otp = OtpCode::factory()->create([
            'phone' => $phone,
            'code' => '123456',
            'is_used' => true,
        ]);

        $result = OtpCode::verify($phone, '123456');

        $this->assertFalse($result['success']);
    }

    /** @test */
    public function valid_scope_filters_correctly(): void
    {
        $phone = '+22670123456';

        $validOtp = OtpCode::factory()->create([
            'phone' => $phone,
            'code' => '123456',
            'is_used' => false,
            'expires_at' => now()->addMinutes(5),
            'attempts' => 0,
            'max_attempts' => 3,
        ]);

        $expiredOtp = OtpCode::factory()->create([
            'phone' => $phone,
            'code' => '654321',
            'expires_at' => now()->subMinute(),
        ]);

        $result = OtpCode::valid($phone, '123456')->first();

        $this->assertNotNull($result);
        $this->assertEquals($validOtp->id, $result->id);
    }

    /** @test */
    public function active_scope_returns_unused_non_expired_codes(): void
    {
        $phone = '+22670123456';

        OtpCode::factory()->create(['phone' => $phone, 'is_used' => false, 'expires_at' => now()->addMinutes(5)]);
        OtpCode::factory()->create(['phone' => $phone, 'is_used' => true, 'expires_at' => now()->addMinutes(5)]);
        OtpCode::factory()->create(['phone' => $phone, 'is_used' => false, 'expires_at' => now()->subMinute()]);

        $active = OtpCode::active($phone)->get();

        $this->assertCount(1, $active);
    }

    /** @test */
    public function it_enforces_rate_limiting(): void
    {
        $phone = '+22670123456';

        // Create 3 OTPs in the last 15 minutes (at the limit)
        OtpCode::factory()->count(3)->create([
            'phone' => $phone,
            'created_at' => now()->subMinutes(5),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('OTP_RATE_LIMIT_EXCEEDED');

        OtpCode::generate($phone);
    }

    /** @test */
    public function it_allows_generation_after_rate_limit_window(): void
    {
        $phone = '+22670123456';

        // Create 3 OTPs more than 15 minutes ago
        OtpCode::factory()->count(3)->create([
            'phone' => $phone,
            'created_at' => now()->subMinutes(20),
        ]);

        $otp = OtpCode::generate($phone);

        $this->assertNotNull($otp);
    }

    /** @test */
    public function it_supports_different_purposes(): void
    {
        $otp1 = OtpCode::generate('+22670123456', OtpCode::PURPOSE_LOGIN);
        $otp2 = OtpCode::generate('+22670123457', OtpCode::PURPOSE_REGISTER);
        $otp3 = OtpCode::generate('+22670123458', OtpCode::PURPOSE_PASSWORD_RESET);
        $otp4 = OtpCode::generate('+22670123459', OtpCode::PURPOSE_PHONE_VERIFICATION);

        $this->assertEquals(OtpCode::PURPOSE_LOGIN, $otp1->purpose);
        $this->assertEquals(OtpCode::PURPOSE_REGISTER, $otp2->purpose);
        $this->assertEquals(OtpCode::PURPOSE_PASSWORD_RESET, $otp3->purpose);
        $this->assertEquals(OtpCode::PURPOSE_PHONE_VERIFICATION, $otp4->purpose);
    }

    /** @test */
    public function it_casts_fields_correctly(): void
    {
        $otp = OtpCode::factory()->create([
            'expires_at' => '2026-02-20 15:00:00',
            'is_used' => 1,
            'attempts' => '2',
            'max_attempts' => '3',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $otp->expires_at);
        $this->assertIsBool($otp->is_used);
        $this->assertIsInt($otp->attempts);
        $this->assertIsInt($otp->max_attempts);
    }
}

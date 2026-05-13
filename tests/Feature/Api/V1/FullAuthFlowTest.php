<?php

namespace Tests\Feature\Api\V1;

use App\Enums\UserRole;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test du flux d'authentification complet :
 * OTP Send → Verify → Me → Refresh → Logout → Logout All
 */
class FullAuthFlowTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // COMPLETE AUTH FLOW: REGISTER → LOGIN → PROFILE → LOGOUT
    // =========================================================================

    public function test_complete_client_auth_flow(): void
    {
        config(['otp.driver' => 'sms', 'sms.default' => 'log']);
        $phone = '70111222';

        // 1. Envoyer un OTP
        $sendResponse = $this->postJson('/api/v1/auth/otp/send', [
            'phone' => $phone,
        ]);

        $sendResponse->assertStatus(200)
            ->assertJson(['success' => true]);

        // Récupérer le code OTP depuis la DB
        $otp = OtpCode::where('phone', $phone)->latest()->first();
        $this->assertNotNull($otp);

        // 2. Vérifier l'OTP → obtenir un token
        $verifyResponse = $this->postJson('/api/v1/auth/otp/verify', [
            'phone' => $phone,
            'code' => $otp->code,
        ]);

        $verifyResponse->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['token', 'user'],
            ]);

        $token = $verifyResponse->json('data.token');
        $this->assertNotEmpty($token);

        // 3. Accéder au profil avec le token
        $meResponse = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson('/api/v1/auth/me');

        $meResponse->assertStatus(200)
            ->assertJsonPath('data.phone', $phone);

        // 4. Mettre à jour le profil
        $updateResponse = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->putJson('/api/v1/auth/profile', [
            'name' => 'Amadou Test',
        ]);

        $updateResponse->assertStatus(200);

        // 5. Rafraîchir le token
        $refreshResponse = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson('/api/v1/auth/refresh-token');

        $refreshResponse->assertStatus(200)
            ->assertJsonStructure(['data' => ['token']]);

        $newToken = $refreshResponse->json('data.token');

        // Reset auth guard cache after refresh so the new token is resolved fresh
        $this->app['auth']->forgetGuards();

        // 6. Se déconnecter
        $logoutResponse = $this->withHeaders([
            'Authorization' => "Bearer {$newToken}",
        ])->postJson('/api/v1/auth/logout');

        $logoutResponse->assertStatus(200);

        // Reset auth guard cache so revoked token is re-evaluated from DB
        $this->app['auth']->forgetGuards();

        // 7. Ancien token ne fonctionne plus
        $this->withHeaders([
            'Authorization' => "Bearer {$newToken}",
        ])->getJson('/api/v1/auth/me')
            ->assertStatus(401);
    }

    // =========================================================================
    // MULTI-DEVICE LOGOUT ALL
    // =========================================================================

    public function test_logout_all_revokes_all_sessions(): void
    {
        config(['otp.driver' => 'sms', 'sms.default' => 'log']);
        $phone = '70333444';

        // Créer l'utilisateur et obtenir 2 tokens (2 appareils)
        $this->postJson('/api/v1/auth/otp/send', ['phone' => $phone]);
        $otp1 = OtpCode::where('phone', $phone)->latest()->first();

        $token1 = $this->postJson('/api/v1/auth/otp/verify', [
            'phone' => $phone,
            'code' => $otp1->code,
        ])->json('data.token');

        // Deuxième session
        $this->postJson('/api/v1/auth/otp/send', ['phone' => $phone]);
        $otp2 = OtpCode::where('phone', $phone)->latest()->first();

        $token2 = $this->postJson('/api/v1/auth/otp/verify', [
            'phone' => $phone,
            'code' => $otp2->code,
        ])->json('data.token');

        // Les deux tokens fonctionnent
        $this->withHeaders(['Authorization' => "Bearer {$token1}"])
            ->getJson('/api/v1/auth/me')
            ->assertStatus(200);

        $this->withHeaders(['Authorization' => "Bearer {$token2}"])
            ->getJson('/api/v1/auth/me')
            ->assertStatus(200);

        // Logout-all depuis token1
        $this->withHeaders(['Authorization' => "Bearer {$token1}"])
            ->postJson('/api/v1/auth/logout-all')
            ->assertStatus(200);

        // Reset auth guard cache so revoked tokens are re-evaluated from DB
        $this->app['auth']->forgetGuards();

        // Les deux tokens sont révoqués
        $this->withHeaders(['Authorization' => "Bearer {$token1}"])
            ->getJson('/api/v1/auth/me')
            ->assertStatus(401);

        $this->withHeaders(['Authorization' => "Bearer {$token2}"])
            ->getJson('/api/v1/auth/me')
            ->assertStatus(401);
    }

    // =========================================================================
    // FCM TOKEN UPDATE
    // =========================================================================

    public function test_authenticated_user_can_update_fcm_token(): void
    {
        $user = User::factory()->create(['role' => UserRole::CLIENT]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/auth/fcm-token', [
                'fcm_token' => 'fake-fcm-token-abc123xyz',
            ]);

        $response->assertStatus(200);
    }

    // =========================================================================
    // COURIER AUTH FLOW
    // =========================================================================

    public function test_courier_auth_flow(): void
    {
        config(['otp.driver' => 'sms', 'sms.default' => 'log']);
        $phone = '70555666';

        // 1. Envoyer OTP via route OTP
        $this->postJson('/api/v1/auth/otp/send', [
            'phone' => $phone,
        ])->assertStatus(200);

        $otp = OtpCode::where('phone', $phone)->latest()->first();
        $this->assertNotNull($otp);

        // 2. Vérifier l'OTP via route OTP
        $verifyResponse = $this->postJson('/api/v1/auth/otp/verify', [
            'phone' => $phone,
            'code' => $otp->code,
        ]);

        $verifyResponse->assertStatus(200)
            ->assertJsonStructure(['data' => ['token']]);
    }

    // =========================================================================
    // UNAUTHENTICATED ACCESS BLOCKED
    // =========================================================================

    public function test_unauthenticated_cannot_access_protected_routes(): void
    {
        $this->getJson('/api/v1/auth/me')->assertStatus(401);
        $this->postJson('/api/v1/auth/logout')->assertStatus(401);
        $this->getJson('/api/v1/orders')->assertStatus(401);
        $this->getJson('/api/v1/notifications')->assertStatus(401);
    }

    // =========================================================================
    // INVALID OTP REJECTED
    // =========================================================================

    public function test_invalid_otp_is_rejected(): void
    {
        config(['otp.driver' => 'sms', 'sms.default' => 'log']);
        $phone = '70777888';

        $this->postJson('/api/v1/auth/otp/send', ['phone' => $phone])
            ->assertStatus(200);

        // Code invalide
        $response = $this->postJson('/api/v1/auth/otp/verify', [
            'phone' => $phone,
            'code' => '000000',
        ]);

        $response->assertStatus(401);
    }

    // =========================================================================
    // EXPIRED OTP REJECTED
    // =========================================================================

    public function test_expired_otp_is_rejected(): void
    {
        config(['otp.driver' => 'sms', 'sms.default' => 'log']);
        $phone = '70999000';

        $this->postJson('/api/v1/auth/otp/send', ['phone' => $phone])
            ->assertStatus(200);

        // Expirer le code manuellement
        $otp = OtpCode::where('phone', $phone)->latest()->first();
        $otp->update(['expires_at' => now()->subMinutes(10)]);

        $response = $this->postJson('/api/v1/auth/otp/verify', [
            'phone' => $phone,
            'code' => $otp->code,
        ]);

        $response->assertStatus(401);
    }

    // =========================================================================
    // ACCOUNT DELETION (GDPR/RGPD)
    // =========================================================================

    public function test_user_can_delete_account(): void
    {
        $user = User::factory()->create(['role' => UserRole::CLIENT]);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->deleteJson('/api/v1/auth/account');

        $response->assertStatus(200);

        // Reset auth guard cache so revoked token is re-evaluated from DB
        $this->app['auth']->forgetGuards();

        // Token révoqué après suppression
        $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson('/api/v1/auth/me')
            ->assertStatus(401);
    }
}

<?php

namespace Tests\Unit\Middleware;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Middleware\EnsureIsAdmin;
use App\Http\Middleware\EnsureIsClient;
use App\Http\Middleware\EnsureIsCourier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // EnsureIsAdmin Tests
    // =========================================================================

    /** @test */
    public function admin_can_pass_admin_middleware(): void
    {
        $admin = User::factory()->admin()->create();
        $request = Request::create('/api/admin/test', 'GET');
        $request->setUserResolver(fn() => $admin);

        $middleware = new EnsureIsAdmin();
        $response = $middleware->handle($request, fn($req) => response()->json(['success' => true]));

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function client_cannot_pass_admin_middleware(): void
    {
        $client = User::factory()->client()->create();
        $request = Request::create('/api/admin/test', 'GET');
        $request->setUserResolver(fn() => $client);

        $middleware = new EnsureIsAdmin();
        $response = $middleware->handle($request, fn($req) => response()->json(['success' => true]));

        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('FORBIDDEN_NOT_ADMIN', $data['code']);
    }

    /** @test */
    public function courier_cannot_pass_admin_middleware(): void
    {
        $courier = User::factory()->courier()->create();
        $request = Request::create('/api/admin/test', 'GET');
        $request->setUserResolver(fn() => $courier);

        $middleware = new EnsureIsAdmin();
        $response = $middleware->handle($request, fn($req) => response()->json(['success' => true]));

        $this->assertEquals(403, $response->getStatusCode());
    }

    /** @test */
    public function guest_cannot_pass_admin_middleware(): void
    {
        $request = Request::create('/api/admin/test', 'GET');
        $request->setUserResolver(fn() => null);

        $middleware = new EnsureIsAdmin();
        $response = $middleware->handle($request, fn($req) => response()->json(['success' => true]));

        $this->assertEquals(403, $response->getStatusCode());
    }

    // =========================================================================
    // EnsureIsClient Tests
    // =========================================================================

    /** @test */
    public function client_can_pass_client_middleware(): void
    {
        $client = User::factory()->client()->create();
        $request = Request::create('/api/client/test', 'GET');
        $request->setUserResolver(fn() => $client);

        $middleware = new EnsureIsClient();
        $response = $middleware->handle($request, fn($req) => response()->json(['success' => true]));

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function admin_cannot_pass_client_middleware(): void
    {
        $admin = User::factory()->admin()->create();
        $request = Request::create('/api/client/test', 'GET');
        $request->setUserResolver(fn() => $admin);

        $middleware = new EnsureIsClient();
        $response = $middleware->handle($request, fn($req) => response()->json(['success' => true]));

        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('FORBIDDEN_NOT_CLIENT', $data['code']);
    }

    /** @test */
    public function courier_cannot_pass_client_middleware(): void
    {
        $courier = User::factory()->courier()->create();
        $request = Request::create('/api/client/test', 'GET');
        $request->setUserResolver(fn() => $courier);

        $middleware = new EnsureIsClient();
        $response = $middleware->handle($request, fn($req) => response()->json(['success' => true]));

        $this->assertEquals(403, $response->getStatusCode());
    }

    /** @test */
    public function guest_cannot_pass_client_middleware(): void
    {
        $request = Request::create('/api/client/test', 'GET');
        $request->setUserResolver(fn() => null);

        $middleware = new EnsureIsClient();
        $response = $middleware->handle($request, fn($req) => response()->json(['success' => true]));

        $this->assertEquals(403, $response->getStatusCode());
    }

    // =========================================================================
    // EnsureIsCourier Tests
    // =========================================================================

    /** @test */
    public function active_courier_can_pass_courier_middleware(): void
    {
        $courier = User::factory()->courier()->create([
            'status' => UserStatus::ACTIVE,
        ]);
        $request = Request::create('/api/courier/test', 'GET');
        $request->setUserResolver(fn() => $courier);

        $middleware = new EnsureIsCourier();
        $response = $middleware->handle($request, fn($req) => response()->json(['success' => true]));

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function inactive_courier_cannot_pass_courier_middleware(): void
    {
        $courier = User::factory()->courier()->create([
            'status' => UserStatus::SUSPENDED,
        ]);
        $request = Request::create('/api/courier/test', 'GET');
        $request->setUserResolver(fn() => $courier);

        $middleware = new EnsureIsCourier();
        $response = $middleware->handle($request, fn($req) => response()->json(['success' => true]));

        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('COURIER_NOT_ACTIVE', $data['code']);
    }

    /** @test */
    public function pending_courier_cannot_pass_courier_middleware(): void
    {
        $courier = User::factory()->courier()->create([
            'status' => UserStatus::PENDING,
        ]);
        $request = Request::create('/api/courier/test', 'GET');
        $request->setUserResolver(fn() => $courier);

        $middleware = new EnsureIsCourier();
        $response = $middleware->handle($request, fn($req) => response()->json(['success' => true]));

        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('COURIER_NOT_ACTIVE', $data['code']);
    }

    /** @test */
    public function client_cannot_pass_courier_middleware(): void
    {
        $client = User::factory()->client()->create();
        $request = Request::create('/api/courier/test', 'GET');
        $request->setUserResolver(fn() => $client);

        $middleware = new EnsureIsCourier();
        $response = $middleware->handle($request, fn($req) => response()->json(['success' => true]));

        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('FORBIDDEN_NOT_COURIER', $data['code']);
    }

    /** @test */
    public function admin_cannot_pass_courier_middleware(): void
    {
        $admin = User::factory()->admin()->create();
        $request = Request::create('/api/courier/test', 'GET');
        $request->setUserResolver(fn() => $admin);

        $middleware = new EnsureIsCourier();
        $response = $middleware->handle($request, fn($req) => response()->json(['success' => true]));

        $this->assertEquals(403, $response->getStatusCode());
    }

    /** @test */
    public function guest_cannot_pass_courier_middleware(): void
    {
        $request = Request::create('/api/courier/test', 'GET');
        $request->setUserResolver(fn() => null);

        $middleware = new EnsureIsCourier();
        $response = $middleware->handle($request, fn($req) => response()->json(['success' => true]));

        $this->assertEquals(403, $response->getStatusCode());
    }

    // =========================================================================
    // Middleware Class Tests
    // =========================================================================

    /** @test */
    public function admin_middleware_class_exists(): void
    {
        $this->assertTrue(class_exists(EnsureIsAdmin::class));
    }

    /** @test */
    public function client_middleware_class_exists(): void
    {
        $this->assertTrue(class_exists(EnsureIsClient::class));
    }

    /** @test */
    public function courier_middleware_class_exists(): void
    {
        $this->assertTrue(class_exists(EnsureIsCourier::class));
    }

    /** @test */
    public function all_role_middlewares_have_handle_method(): void
    {
        $this->assertTrue(method_exists(EnsureIsAdmin::class, 'handle'));
        $this->assertTrue(method_exists(EnsureIsClient::class, 'handle'));
        $this->assertTrue(method_exists(EnsureIsCourier::class, 'handle'));
    }
}

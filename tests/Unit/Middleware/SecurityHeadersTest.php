<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    private SecurityHeaders $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new SecurityHeaders();
    }

    /** @test */
    public function adds_x_content_type_options_header(): void
    {
        $request = Request::create('/api/test', 'GET');
        
        $response = $this->middleware->handle($request, fn($req) => new Response('OK'));

        $this->assertEquals('nosniff', $response->headers->get('X-Content-Type-Options'));
    }

    /** @test */
    public function adds_x_frame_options_header(): void
    {
        $request = Request::create('/api/test', 'GET');
        
        $response = $this->middleware->handle($request, fn($req) => new Response('OK'));

        $this->assertEquals('DENY', $response->headers->get('X-Frame-Options'));
    }

    /** @test */
    public function adds_x_xss_protection_header(): void
    {
        $request = Request::create('/api/test', 'GET');
        
        $response = $this->middleware->handle($request, fn($req) => new Response('OK'));

        $this->assertEquals('1; mode=block', $response->headers->get('X-XSS-Protection'));
    }

    /** @test */
    public function adds_referrer_policy_header(): void
    {
        $request = Request::create('/api/test', 'GET');
        
        $response = $this->middleware->handle($request, fn($req) => new Response('OK'));

        $this->assertEquals('strict-origin-when-cross-origin', $response->headers->get('Referrer-Policy'));
    }

    /** @test */
    public function adds_permissions_policy_header(): void
    {
        $request = Request::create('/api/test', 'GET');
        
        $response = $this->middleware->handle($request, fn($req) => new Response('OK'));

        $permissionsPolicy = $response->headers->get('Permissions-Policy');
        $this->assertNotNull($permissionsPolicy);
        $this->assertStringContainsString('geolocation', $permissionsPolicy);
        $this->assertStringContainsString('camera', $permissionsPolicy);
        $this->assertStringContainsString('microphone', $permissionsPolicy);
    }

    /** @test */
    public function adds_content_security_policy_for_api(): void
    {
        $request = Request::create('/api/v1/orders', 'GET');
        
        $response = $this->middleware->handle($request, fn($req) => new Response('OK'));

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertNotNull($csp);
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
    }

    /** @test */
    public function uses_relaxed_csp_for_admin_routes(): void
    {
        $request = Request::create('/admin/dashboard', 'GET');
        
        $response = $this->middleware->handle($request, fn($req) => new Response('OK'));

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertNotNull($csp);
        $this->assertStringContainsString('unsafe-inline', $csp);
        $this->assertStringContainsString('unsafe-eval', $csp);
        $this->assertStringContainsString('cdn.jsdelivr.net', $csp);
    }

    /** @test */
    public function uses_relaxed_csp_for_livewire_routes(): void
    {
        $request = Request::create('/livewire/component', 'GET');
        
        $response = $this->middleware->handle($request, fn($req) => new Response('OK'));

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertNotNull($csp);
        $this->assertStringContainsString('unsafe-inline', $csp);
    }

    /** @test */
    public function strict_csp_for_public_routes(): void
    {
        $request = Request::create('/page', 'GET');
        
        $response = $this->middleware->handle($request, fn($req) => new Response('OK'));

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertNotNull($csp);
        $this->assertStringContainsString("base-uri 'self'", $csp);
        $this->assertStringContainsString("form-action 'self'", $csp);
    }

    /** @test */
    public function middleware_passes_request_to_next(): void
    {
        $request = Request::create('/api/test', 'GET');
        $nextCalled = false;
        
        $this->middleware->handle($request, function ($req) use (&$nextCalled) {
            $nextCalled = true;
            return new Response('OK');
        });

        $this->assertTrue($nextCalled);
    }

    /** @test */
    public function middleware_returns_response_from_next(): void
    {
        $request = Request::create('/api/test', 'GET');
        
        $response = $this->middleware->handle($request, fn($req) => new Response('Custom Content'));

        $this->assertEquals('Custom Content', $response->getContent());
    }

    /** @test */
    public function middleware_preserves_response_status_code(): void
    {
        $request = Request::create('/api/test', 'GET');
        
        $response = $this->middleware->handle($request, fn($req) => new Response('Created', 201));

        $this->assertEquals(201, $response->getStatusCode());
    }

    /** @test */
    public function middleware_class_exists(): void
    {
        $this->assertInstanceOf(SecurityHeaders::class, $this->middleware);
    }

    /** @test */
    public function middleware_has_handle_method(): void
    {
        $this->assertTrue(method_exists($this->middleware, 'handle'));
    }

    /** @test */
    public function csp_allows_fonts_from_google(): void
    {
        $request = Request::create('/api/test', 'GET');
        
        $response = $this->middleware->handle($request, fn($req) => new Response('OK'));

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString('fonts.googleapis.com', $csp);
        $this->assertStringContainsString('fonts.gstatic.com', $csp);
    }

    /** @test */
    public function csp_allows_images_from_various_sources(): void
    {
        $request = Request::create('/api/test', 'GET');
        
        $response = $this->middleware->handle($request, fn($req) => new Response('OK'));

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("img-src 'self'", $csp);
        $this->assertStringContainsString('data:', $csp);
        $this->assertStringContainsString('blob:', $csp);
    }
}

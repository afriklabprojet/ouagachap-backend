<?php

// Stub for missing dev-only packages (not installed in production vendor)
if (! class_exists('Knuckles\\Scribe\\ScribeServiceProvider')) {
    require_once __DIR__.'/../app/Providers/ScribeStub.php';
}

if (! class_exists('Laravel\\Pail\\PailServiceProvider')) {
    class_alias('Illuminate\\Support\\ServiceProvider', 'Laravel\\Pail\\PailServiceProvider');
}

use App\Http\Middleware\EnsureIsAdmin;
use App\Http\Middleware\EnsureIsClient;
use App\Http\Middleware\EnsureIsCourier;
use App\Http\Middleware\EnsureUserActive;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\IdempotencyMiddleware;
use App\Http\Middleware\LogApiRequests;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\TrackLastSeen;
use App\Http\Middleware\VerifySappayIp;
use App\Exceptions\Domain\DomainException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Global middleware for all API routes
        $middleware->api(prepend: [
            ForceJsonResponse::class,
            SecurityHeaders::class,
        ]);

        // Append logging and tracking (after response is ready)
        $middleware->api(append: [
            TrackLastSeen::class,
            LogApiRequests::class,
        ]);

        // Register middleware aliases
        $middleware->alias([
            'role.client'  => EnsureIsClient::class,
            'role.courier' => EnsureIsCourier::class,
            'role.admin'   => EnsureIsAdmin::class,
            'user.active'  => EnsureUserActive::class,
            'auth.api'     => \App\Http\Middleware\AuthenticateSanctumApi::class,
            'idempotent'   => IdempotencyMiddleware::class,
            'sappay.ip'    => VerifySappayIp::class,
        ]);

        // Configure trusted proxies for load balancers
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Render all exceptions as JSON for API requests
        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e) {
            return $request->is('api/*') || $request->expectsJson();
        });

        // Custom renderable exceptions
        $exceptions->renderable(function (\Illuminate\Http\Exceptions\HttpResponseException $e, Request $request) {
            // Rate limit custom responses use HttpResponseException — pass through the embedded response
            return $e->getResponse();
        });

        $exceptions->renderable(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non authentifié.',
                    'code' => 'UNAUTHENTICATED',
                ], 401);
            }
        });

        $exceptions->renderable(function (ValidationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides.',
                    'code' => 'VALIDATION_ERROR',
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        $exceptions->renderable(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ressource non trouvée.',
                    'code' => 'NOT_FOUND',
                ], 404);
            }
        });

        $exceptions->renderable(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Méthode HTTP non autorisée.',
                    'code' => 'METHOD_NOT_ALLOWED',
                ], 405);
            }
        });

        $exceptions->renderable(function (DomainException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'code'    => $e->getErrorCode(),
                ], $e->getStatusCode());
            }
        });

        $exceptions->renderable(function (HttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'Erreur serveur.',
                    'code' => 'HTTP_ERROR',
                ], $e->getStatusCode());
            }
        });

        // Generic exception handler (hide details in production)
        $exceptions->renderable(function (Throwable $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $isProduction = config('app.env') === 'production';

                return response()->json([
                    'success' => false,
                    'message' => $isProduction ? 'Erreur interne du serveur.' : $e->getMessage(),
                    'code' => 'SERVER_ERROR',
                    ...($isProduction ? [] : [
                        'exception' => get_class($e),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ]),
                ], 500);
            }
        });
    })->create();

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogApiRequests
{
    private array $sensitiveFields = [
        'password',
        'password_confirmation',
        'pin',
        'otp',
        'code',
        'token',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        $duration = round((microtime(true) - $startTime) * 1000, 2);

        $this->logRequest($request, $response, $duration);

        return $response;
    }

    private function logRequest(Request $request, Response $response, float $duration): void
    {
        $context = [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_id' => $request->user()?->id,
            'user_agent' => $request->userAgent(),
            'status' => $response->getStatusCode(),
            'duration_ms' => $duration,
            'input' => $this->sanitizeInput($request->all()),
        ];

        $level = $response->getStatusCode() >= 400 ? 'warning' : 'info';

        Log::channel('api')->{$level}('API Request', $context);
    }

    private function sanitizeInput(array $input): array
    {
        foreach ($this->sensitiveFields as $field) {
            if (isset($input[$field])) {
                $input[$field] = '***REDACTED***';
            }
        }

        return $input;
    }
}

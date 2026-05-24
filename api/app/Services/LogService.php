<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

/**
 * Service de logging structuré avec contexte
 */
class LogService
{
    /**
     * Log une action utilisateur
     */
    public static function userAction(string $action, array $context = []): void
    {
        $user = Auth::user();
        
        Log::channel('daily')->info($action, array_merge([
            'user_id' => $user?->id,
            'user_role' => $user?->role?->value,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ], $context));
    }

    /**
     * Log une erreur API
     */
    public static function apiError(string $message, \Throwable $exception, array $context = []): void
    {
        Log::channel('daily')->error($message, array_merge([
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => collect($exception->getTrace())->take(5)->toArray(),
            'user_id' => Auth::id(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'ip' => request()->ip(),
        ], $context));
    }

    /**
     * Log un paiement
     */
    public static function payment(string $action, array $context = []): void
    {
        Log::channel('daily')->info("Payment: {$action}", array_merge([
            'user_id' => Auth::id(),
            'timestamp' => now()->toIso8601String(),
        ], $context));
    }

    /**
     * Log une commande
     */
    public static function order(string $action, string $orderId, array $context = []): void
    {
        Log::channel('daily')->info("Order: {$action}", array_merge([
            'order_id' => $orderId,
            'user_id' => Auth::id(),
            'timestamp' => now()->toIso8601String(),
        ], $context));
    }

    /**
     * Log une action coursier
     */
    public static function courier(string $action, array $context = []): void
    {
        Log::channel('daily')->info("Courier: {$action}", array_merge([
            'courier_id' => Auth::id(),
            'timestamp' => now()->toIso8601String(),
        ], $context));
    }

    /**
     * Log une action admin
     */
    public static function admin(string $action, array $context = []): void
    {
        Log::channel('daily')->info("Admin: {$action}", array_merge([
            'admin_id' => Auth::id(),
            'admin_name' => Auth::user()?->name,
            'ip' => request()->ip(),
            'timestamp' => now()->toIso8601String(),
        ], $context));
    }

    /**
     * Log de sécurité
     */
    public static function security(string $event, array $context = []): void
    {
        Log::channel('daily')->warning("Security: {$event}", array_merge([
            'user_id' => Auth::id(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'timestamp' => now()->toIso8601String(),
        ], $context));
    }

    /**
     * Log performance (queries lentes, etc.)
     */
    public static function performance(string $metric, float $value, array $context = []): void
    {
        if ($value > 100) { // Seulement si > 100ms
            Log::channel('daily')->warning("Performance: {$metric}", array_merge([
                'value_ms' => $value,
                'url' => request()->fullUrl(),
                'method' => request()->method(),
                'timestamp' => now()->toIso8601String(),
            ], $context));
        }
    }

    /**
     * Log webhook
     */
    public static function webhook(string $provider, string $action, array $context = []): void
    {
        Log::channel('daily')->info("Webhook {$provider}: {$action}", array_merge([
            'ip' => request()->ip(),
            'timestamp' => now()->toIso8601String(),
        ], $context));
    }
}

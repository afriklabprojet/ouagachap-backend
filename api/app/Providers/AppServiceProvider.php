<?php

namespace App\Providers;

use App\Events\CourierWentOnline;
use App\Events\OrderAssigned;
use App\Events\OrderCreated;
use App\Events\OrderMessageSent;
use App\Events\OrderStatusChanged;
use App\Events\PaymentCompleted;
use App\Listeners\NotifyAdminCourierAvailability;
use App\Listeners\SendOrderAssignedNotification;
use App\Listeners\SendOrderCreatedNotification;
use App\Listeners\SendOrderMessageNotification;
use App\Listeners\SendOrderStatusNotification;
use App\Listeners\SendPaymentNotification;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Wallet;
use App\Models\Withdrawal;
use App\Policies\OrderPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\WalletPolicy;
use App\Policies\WithdrawalPolicy;
use App\Repositories\OrderRepository;
use App\Repositories\UserRepository;
use App\Services\CacheService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Enregistrer les singletons
        $this->app->singleton(OrderRepository::class);
        $this->app->singleton(UserRepository::class);
        $this->app->singleton(CacheService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Forcer HTTPS en production
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        $this->registerPolicies();
        $this->registerEvents();
        $this->registerObservers();
        $this->configureRateLimiting();
        $this->configureQueryLogging();
    }

    /**
     * Register policies
     */
    protected function registerPolicies(): void
    {
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::policy(Wallet::class, WalletPolicy::class);
        Gate::policy(Withdrawal::class, WithdrawalPolicy::class);
    }

    /**
     * Register event listeners
     */
    protected function registerEvents(): void
    {
        Event::listen(OrderCreated::class, SendOrderCreatedNotification::class);
        Event::listen(OrderAssigned::class, SendOrderAssignedNotification::class);
        Event::listen(OrderStatusChanged::class, SendOrderStatusNotification::class);
        Event::listen(PaymentCompleted::class, SendPaymentNotification::class);
        Event::listen(OrderMessageSent::class, SendOrderMessageNotification::class);

        // Notification admin quand un coursier change de disponibilité
        Event::listen(CourierWentOnline::class, NotifyAdminCourierAvailability::class);
    }

    /**
     * Register model observers for cache invalidation
     */
    protected function registerObservers(): void
    {
        \App\Models\Zone::observe(\App\Observers\ZoneObserver::class);
        \App\Models\Order::observe(\App\Observers\OrderObserver::class);
        \App\Models\Wallet::observe(\App\Observers\WalletObserver::class);
    }

    /**
     * Configure rate limiting
     */
    protected function configureRateLimiting(): void
    {
        // General API rate limit
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(
                $request->user()?->id ?: $request->ip()
            )->response(function () {
                return response()->json([
                    'success' => false,
                    'message' => 'Trop de requêtes. Réessayez dans une minute.',
                    'code' => 'RATE_LIMIT_EXCEEDED',
                ], 429);
            });
        });

        // Auth endpoints (stricter)
        RateLimiter::for('auth', function (Request $request) {
            $phone = (string) ($request->input('phone') ?: '');
            $device = (string) ($request->header('X-Device-Id') ?: 'unknown-device');
            $keyBase = $phone !== '' ? $phone : $request->ip();

            return [
                Limit::perMinute(5)->by('auth:phone:' . $keyBase),
                Limit::perMinutes(15, 10)->by('auth:device:' . $device),
                Limit::perHour(40)->by('auth:ip:' . $request->ip()),
            ];
        });

        // OTP requests (3 par 15 minutes en production)
        RateLimiter::for('otp', function (Request $request) {
            $phone = (string) ($request->input('phone') ?: '');
            $device = (string) ($request->header('X-Device-Id') ?: 'unknown-device');
            $keyBase = $phone !== '' ? $phone : $request->ip();

            $otpRateLimitResponse = function () {
                return response()->json([
                    'success' => false,
                    'message' => 'Trop de demandes OTP. Réessayez dans 15 minutes.',
                    'code' => 'OTP_RATE_LIMIT_EXCEEDED',
                ], 429);
            };

            return [
                Limit::perMinutes(15, 3)->by('otp:phone:' . $keyBase)->response($otpRateLimitResponse),
                Limit::perHour(8)->by('otp:device:' . $device)->response($otpRateLimitResponse),
                Limit::perHour(15)->by('otp:ip:' . $request->ip())->response($otpRateLimitResponse),
            ];
        });

        // Order creation
        RateLimiter::for('orders', function (Request $request) {
            return Limit::perMinute(10)->by(
                $request->user()?->id ?: $request->ip()
            )->response(function () {
                return response()->json([
                    'success' => false,
                    'message' => 'Trop de commandes. Réessayez plus tard.',
                    'code' => 'ORDER_RATE_LIMIT_EXCEEDED',
                ], 429);
            });
        });

        // Payment operations (strict)
        RateLimiter::for('payments', function (Request $request) {
            return Limit::perMinute(5)->by(
                $request->user()?->id ?: $request->ip()
            )->response(function () {
                return response()->json([
                    'success' => false,
                    'message' => 'Trop de tentatives de paiement. Réessayez plus tard.',
                    'code' => 'PAYMENT_RATE_LIMIT_EXCEEDED',
                ], 429);
            });
        });

        // Location updates (high frequency allowed)
        RateLimiter::for('location', function (Request $request) {
            return Limit::perMinute(120)->by(
                $request->user()?->id ?: $request->ip()
            );
        });

        // Account deletion (very strict — prevent abuse)
        RateLimiter::for('account-delete', function (Request $request) {
            return Limit::perHour(3)->by(
                $request->user()?->id ?: $request->ip()
            )->response(function () {
                return response()->json([
                    'success' => false,
                    'message' => 'Trop de tentatives de suppression. Réessayez plus tard.',
                    'code' => 'ACCOUNT_DELETE_RATE_LIMIT',
                ], 429);
            });
        });

        // Wallet operations (withdrawals, recharges)
        RateLimiter::for('wallet', function (Request $request) {
            return Limit::perMinute(5)->by(
                $request->user()?->id ?: $request->ip()
            )->response(function () {
                return response()->json([
                    'success' => false,
                    'message' => 'Trop d\'opérations wallet. Réessayez plus tard.',
                    'code' => 'WALLET_RATE_LIMIT_EXCEEDED',
                ], 429);
            });
        });
    }

    /**
     * Configure query logging for performance monitoring
     */
    protected function configureQueryLogging(): void
    {
        // Log slow queries in development
        if (config('app.debug') && config('app.env') === 'local') {
            DB::listen(function ($query) {
                if ($query->time > 100) { // > 100ms
                    Log::warning('Slow Query Detected', [
                        'sql' => $query->sql,
                        'bindings' => $query->bindings,
                        'time_ms' => $query->time,
                    ]);
                }
            });
        }
    }
}

<?php

use App\Http\Controllers\Api\V1\ActivityLogController;
use App\Http\Controllers\Api\V1\AdminController;
use App\Http\Controllers\Api\V1\AdminDispatchController;
use App\Http\Controllers\Api\V1\LiveMapController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\FirebaseAuthController;
use App\Http\Controllers\Api\V1\ConfigController;
use App\Http\Controllers\Api\V1\CourierController;
use App\Http\Controllers\Api\V1\ExportController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\GeofenceController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\OrderChatController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\PromoCodeController;
use App\Http\Controllers\Api\V1\RatingController;
use App\Http\Controllers\Api\V1\WalletController;
use App\Http\Controllers\Api\V1\ClientWalletController;
use App\Http\Controllers\Api\V1\ServiceController;
use App\Http\Controllers\Api\V1\SupportController;
use App\Http\Controllers\Api\V1\ZoneController;
use App\Http\Controllers\Api\V1\JekoPaymentController;
use App\Http\Controllers\Api\V1\JekoWebhookController;
use App\Http\Controllers\Api\V1\SmsWebhookController;
use App\Http\Controllers\Api\V1\WhatsAppWebhookController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - OUAGA CHAP (Secured)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () { // NOSONAR

    // ==================== PUBLIC ROUTES ====================

    // Health check (diagnostics MySQL, Redis, Reverb, Storage)
    Route::get('/health', HealthController::class)
        ->middleware('throttle:10,1');

    // Admin Login (public)
    Route::post('/admin/login', [AdminController::class, 'login'])
        ->middleware('throttle:5,1');

    // ==================== FIREBASE AUTH (NOUVEAU — remplace OTP) ====================
    // Flutter gère l'OTP phone via Firebase SDK, puis envoie le Firebase ID Token
    Route::post('/auth/firebase', [FirebaseAuthController::class, 'login'])
        ->middleware('throttle:10,1')
        ->name('auth.firebase.login');

    // ==================== COURIER AUTH — DÉPRÉCIÉ (410) ====================
    // OTP supprimé — Flutter utilise Firebase Phone Auth → POST /auth/firebase
    Route::prefix('courier')->group(function () {
        Route::post('/login',      fn() => response()->json(['success' => false, 'message' => 'Endpoint déprécié. Utilisez POST /api/v1/auth/firebase', 'code' => 410], 410));
        Route::post('/verify-otp', fn() => response()->json(['success' => false, 'message' => 'Endpoint déprécié. Utilisez POST /api/v1/auth/firebase', 'code' => 410], 410));
    });

    // Configuration (public)
    Route::prefix('config')->group(function () {
        Route::get('/general', [ConfigController::class, 'general']);
        Route::get('/websocket', [ConfigController::class, 'websocket']);
        Route::get('/zones', [ConfigController::class, 'zones']);
    });

    // Authentication deprecated endpoints (410 — no rate limiting needed)
    Route::prefix('auth')->group(function () {
        // Inscription client supprimée — Firebase Phone Auth gère tout
        Route::post('/register',              fn() => response()->json(['success' => false, 'message' => 'Endpoint déprécié. Utilisez POST /api/v1/auth/firebase', 'code' => 410], 410));
        // JWT refresh supprimé — Sanctum tokens uniquement
        Route::post('/refresh-token/mobile',  fn() => response()->json(['success' => false, 'message' => 'Endpoint déprécié. Utilisez POST /api/v1/auth/firebase', 'code' => 410], 410));
    });

    // Courier registration (public, rate limited)
    Route::post('/auth/register/courier', [AuthController::class, 'registerCourier'])
        ->middleware('throttle:auth');

    // OTP SMS routes (public, rate limited)
    Route::prefix('auth')->middleware('throttle:auth')->group(function () {
        Route::post('/otp/send',   [AuthController::class, 'sendOtp']);
        Route::post('/otp/verify', [AuthController::class, 'verifyOtp']);
    });

    // Zones (public, read-only)
    Route::get('/zones', [ZoneController::class, 'index']);
    Route::get('/zones/{zone}', [ZoneController::class, 'show']);

    // Payment methods (public)
    Route::get('/payments/methods', [PaymentController::class, 'methods']);

    // Payment webhook (signature validated in controller)
    Route::post('/payments/webhook', [PaymentController::class, 'webhook'])
        ->middleware('throttle:60,1');

    // ==================== JEKO WEBHOOK (SIGNATURE VALIDATED IN CONTROLLER) ====================
    Route::post('/jeko/webhook', [JekoWebhookController::class, 'handle'])
        ->middleware('throttle:60,1');

    // ==================== SMS DELIVERY WEBHOOK (Infobip) ====================
    Route::post('/webhooks/sms/delivery', [SmsWebhookController::class, 'handle'])
        ->middleware('throttle:60,1');

    // ==================== WHATSAPP WEBHOOK (META CLOUD API) ====================
    // GET : vérification initiale du webhook par Meta (challenge handshake)
    Route::get('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'verify'])
        ->middleware('throttle:30,1')
        ->name('whatsapp.webhook.verify');
    // POST : réception des événements (messages entrants, statuts de livraison)
    Route::post('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'handle'])
        ->middleware('throttle:60,1')
        ->name('whatsapp.webhook.handle');

    // Mock confirmation endpoint (sandbox only - excluded in production)
    if (config('jeko.sandbox')) {
        Route::post('/jeko/mock-confirm/{reference}', function ($reference) {
            $service = app(\App\Services\JekoPaymentService::class);
            return response()->json($service->mockConfirmPayment($reference));
        })->withoutMiddleware(['auth:sanctum'])->middleware('throttle:10,1');
    }

    // ==================== TRACKING PUBLIC (pour destinataires sans compte) ====================
    Route::post('/track-order', [App\Http\Controllers\Api\V1\IncomingOrderController::class, 'searchByOrderNumber'])
        ->middleware('throttle:10,1');

    // ==================== SUPPORT PUBLIC ====================
    Route::prefix('support')->group(function () {
        Route::get('/contact', [SupportController::class, 'contactInfo']);
        Route::get('/faqs', [SupportController::class, 'faqs']);
        Route::post('/faqs/{id}/view', [SupportController::class, 'viewFaq']);
    });

    // ==================== AUTHENTICATED ROUTES ====================

    Route::middleware(['auth.api', 'user.active', 'throttle:api'])->group(function () {

        // Auth management
        Route::prefix('auth')->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::put('/profile', [AuthController::class, 'updateProfile']);
            Route::post('/profile', [AuthController::class, 'updateProfile']); // Pour FormData avec fichiers
            Route::put('/fcm-token', [FirebaseAuthController::class, 'updateFcmToken']);
            Route::post('/fcm-token', [FirebaseAuthController::class, 'updateFcmToken']); // alias POST
            Route::post('/logout', [FirebaseAuthController::class, 'logout']);
            Route::post('/logout-all', [FirebaseAuthController::class, 'logoutAll']);
            Route::post('/refresh-token', [AuthController::class, 'refreshToken']);
            Route::delete('/account', [FirebaseAuthController::class, 'deleteAccount'])
                ->middleware('throttle:account-delete');
        });

        // Profile update alias (pour Flutter)
        Route::post('/user/profile', [AuthController::class, 'updateProfile']);

        // FCM Token endpoint (alias pour Flutter)
        Route::post('/user/fcm-token', [FirebaseAuthController::class, 'updateFcmToken']);

        // ==================== NOTIFICATIONS IN-APP ====================

        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
            Route::post('/mark-read', [NotificationController::class, 'markManyAsRead']);
            Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
            Route::delete('/clear-read', [NotificationController::class, 'clearRead']);
            Route::post('/{notification}/read', [NotificationController::class, 'markAsRead']);
            Route::delete('/{notification}', [NotificationController::class, 'destroy']);
        });

        // ==================== TRAFFIC INCIDENTS ====================

        Route::prefix('traffic')->group(function () {
            Route::get('/incidents', [App\Http\Controllers\Api\V1\TrafficController::class, 'index']);
            Route::post('/incidents', [App\Http\Controllers\Api\V1\TrafficController::class, 'store']);
            Route::post('/incidents/{incident}/confirm', [App\Http\Controllers\Api\V1\TrafficController::class, 'confirm']);
            Route::post('/incidents/{incident}/resolve', [App\Http\Controllers\Api\V1\TrafficController::class, 'resolve']);
            Route::get('/types', [App\Http\Controllers\Api\V1\TrafficController::class, 'types']);
            Route::get('/stats', [App\Http\Controllers\Api\V1\TrafficController::class, 'stats']);
        });

        // ==================== KYC COURSIER ====================

        Route::prefix('kyc')->group(function () {
            Route::post('/submit', [App\Http\Controllers\Api\V1\CourierKycController::class, 'submit']);
            Route::get('/status', [App\Http\Controllers\Api\V1\CourierKycController::class, 'status']);
        });

        // ==================== GAMIFICATION / QUÊTES ====================

        Route::get('/quests', [App\Http\Controllers\Api\V1\QuestController::class, 'index']);

        // ==================== CHAP PASS (ABONNEMENTS) ====================

        Route::prefix('subscriptions')->group(function () {
            Route::post('/', [App\Http\Controllers\Api\V1\SubscriptionController::class, 'subscribe']);
            Route::get('/current', [App\Http\Controllers\Api\V1\SubscriptionController::class, 'current']);
            Route::delete('/cancel', [App\Http\Controllers\Api\V1\SubscriptionController::class, 'cancel']);
        });

        // ==================== CLIENT ROUTES ====================

        Route::middleware('role.client')->group(function () {

            // ==================== SERVICES ====================
            Route::get('/services', [ServiceController::class, 'index']);
            Route::get('/services/{serviceId}', [ServiceController::class, 'show']);

            // ==================== CLIENT WALLET (RECHARGE) ====================
            Route::prefix('client-wallet')->group(function () {
                Route::get('/balance', [ClientWalletController::class, 'balance']);
                Route::post('/recharge', [ClientWalletController::class, 'initiateRecharge'])
                    ->middleware('throttle:wallet');
                Route::post('/recharge/confirm', [ClientWalletController::class, 'confirmRecharge'])
                    ->middleware('throttle:wallet');
                Route::get('/recharge/history', [ClientWalletController::class, 'history']);
            });

            // Order creation (rate limited)
            Route::post('/orders/estimate', [OrderController::class, 'estimate']);
            Route::post('/orders', [OrderController::class, 'store'])
                ->middleware('throttle:orders');

            // Client's orders
            Route::get('/orders', [OrderController::class, 'index']);

            // Cancel order (client)
            Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])
                ->whereUuid('order');

            // Rate courier
            Route::post('/orders/{order}/rate-courier', [OrderController::class, 'rateCourier'])
                ->whereUuid('order');

            // Payment initiation (rate limited)
            Route::post('/payments/initiate', [PaymentController::class, 'initiate'])
                ->middleware('throttle:payments');

            // ==================== PROMO CODES (CLIENT) ====================
            Route::prefix('promo-codes')->group(function () {
                Route::post('/validate', [PromoCodeController::class, 'validate']);
                Route::post('/apply', [PromoCodeController::class, 'apply']);
                Route::get('/available', [PromoCodeController::class, 'available']);
                Route::get('/history', [PromoCodeController::class, 'history']);
            });

            // ==================== SAVED ADDRESSES (CLIENT) ====================
            Route::prefix('addresses')->group(function () {
                Route::get('/', [\App\Http\Controllers\Api\SavedAddressController::class, 'index']);
                Route::post('/', [\App\Http\Controllers\Api\SavedAddressController::class, 'store']);
                Route::get('/{id}', [\App\Http\Controllers\Api\SavedAddressController::class, 'show']);
                Route::put('/{id}', [\App\Http\Controllers\Api\SavedAddressController::class, 'update']); // NOSONAR
                Route::delete('/{id}', [\App\Http\Controllers\Api\SavedAddressController::class, 'destroy']); // NOSONAR
                Route::post('/{id}/set-default', [\App\Http\Controllers\Api\SavedAddressController::class, 'setDefault']);
            });

            // ==================== RATINGS (CLIENT) ====================
            Route::prefix('ratings')->group(function () {
                Route::get('/received', [RatingController::class, 'received']);
                Route::get('/given', [RatingController::class, 'given']);
                Route::get('/stats', [RatingController::class, 'stats']); // NOSONAR
            });

            // ==================== COLIS ENTRANTS (INCOMING) ====================
            Route::prefix('incoming-orders')->group(function () {
                Route::get('/', [App\Http\Controllers\Api\V1\IncomingOrderController::class, 'index']);
                Route::get('/{id}', [App\Http\Controllers\Api\V1\IncomingOrderController::class, 'show']); // NOSONAR
                Route::get('/{id}/track', [App\Http\Controllers\Api\V1\IncomingOrderController::class, 'track']);
                Route::post('/{id}/confirm', [App\Http\Controllers\Api\V1\IncomingOrderController::class, 'confirmReceipt']);
            });

            // ==================== JEKO PAYMENTS (CLIENT) ====================
            Route::prefix('jeko')->group(function () {
                Route::get('/payment-methods', [JekoPaymentController::class, 'paymentMethods']);
                Route::post('/recharge', [JekoPaymentController::class, 'initiateWalletRecharge']);
                Route::post('/pay-order', [JekoPaymentController::class, 'initiateOrderPayment']);
                Route::get('/status/{transactionId}', [JekoPaymentController::class, 'checkStatus']);
                Route::get('/transactions', [JekoPaymentController::class, 'transactionHistory']);
                Route::get('/callback/success', [JekoPaymentController::class, 'paymentSuccess']);
                Route::get('/callback/error', [JekoPaymentController::class, 'paymentError']);
            });
        });

        // ==================== COURIER ROUTES ====================

        Route::middleware('role.courier')->group(function () {
            // Courier profile & auth (aliases pour app Flutter coursier)
            Route::get('/courier/profile', [FirebaseAuthController::class, 'me']);
            Route::post('/courier/logout', [FirebaseAuthController::class, 'logout']);
            Route::post('/courier/logout-all', [FirebaseAuthController::class, 'logoutAll']);

            // Courier profile/status
            Route::put('/courier/location', [CourierController::class, 'updateLocation'])
                ->middleware('throttle:location');
            Route::post('/courier/location', [CourierController::class, 'updateLocation'])
                ->middleware('throttle:location');
            Route::post('/courier/status', [CourierController::class, 'updateOnlineStatus']);
            Route::put('/courier/availability', [CourierController::class, 'updateAvailability']);
            Route::get('/courier/dashboard', [CourierController::class, 'dashboard']);
            Route::get('/courier/orders', [CourierController::class, 'orders']);
            Route::get('/courier/current-order', [CourierController::class, 'currentOrder']);
            Route::get('/courier/earnings', [CourierController::class, 'earnings']);

            // Routes pour l'app Flutter coursier
            Route::get('/courier/available-orders', [CourierController::class, 'availableOrders']);
            Route::get('/courier/active-delivery', [CourierController::class, 'activeDelivery']);
            Route::get('/courier/delivery-history', [CourierController::class, 'deliveryHistory']);
            Route::get('/courier/orders/{order}', [CourierController::class, 'showOrder']);
            Route::post('/courier/orders/{order}/accept', [CourierController::class, 'acceptOrder']);
            Route::put('/courier/orders/{order}/status', [CourierController::class, 'updateOrderStatus']);
            Route::post('/courier/orders/{order}/confirm-delivery', [CourierController::class, 'confirmDelivery']);

            // ==================== SMART DISPATCH (COURSIER) ====================
            Route::post('/courier/battery', [CourierController::class, 'updateBattery']);
            Route::get('/courier/route-plan', [CourierController::class, 'routePlan']);

            // Wallet & Withdrawals
            Route::get('/wallet', [WalletController::class, 'show']);
            Route::post('/wallet/withdraw', [WalletController::class, 'requestWithdrawal'])
                ->middleware('throttle:wallet');
            Route::get('/wallet/withdrawals', [WalletController::class, 'withdrawalHistory']);
            Route::delete('/wallet/withdrawals/{withdrawal}', [WalletController::class, 'cancelWithdrawal']);
            // Wallet Recharge (courrier)
            Route::post('/wallet/recharge', [WalletController::class, 'initiateRecharge'])
                ->middleware('throttle:wallet');
            Route::post('/wallet/recharge/confirm', [WalletController::class, 'confirmRecharge'])
                ->middleware('throttle:wallet');

            // Rate client (via commandes partagées)
            Route::post('/orders/{order}/rate-client', [OrderController::class, 'rateClient'])
                ->whereUuid('order');

            // ==================== GEOFENCE (COURIER) ====================
            Route::prefix('geofence')->group(function () {
                Route::post('/position', [GeofenceController::class, 'updatePosition']);
                Route::get('/alerts', [GeofenceController::class, 'myAlerts']);
                Route::get('/orders/{order}/alerts', [GeofenceController::class, 'orderAlerts']);
            });

            // ==================== RATINGS (COURIER) ====================
            Route::prefix('ratings')->group(function () {
                Route::get('/received', [RatingController::class, 'received']);
                Route::get('/given', [RatingController::class, 'given']);
                Route::get('/stats', [RatingController::class, 'stats']); // NOSONAR
            });
        });

        // ==================== SUPPORT (Client & Coursier partagé) ====================
        Route::prefix('support')->group(function () {
            // Chat Support
            Route::get('/chats', [SupportController::class, 'chats']);
            Route::post('/chats', [SupportController::class, 'getOrCreateChat']);
            Route::get('/chats/{chatId}/messages', [SupportController::class, 'chatMessages']);
            Route::post('/chats/{chatId}/messages', [SupportController::class, 'sendMessage']);
            Route::post('/chats/{chatId}/close', [SupportController::class, 'closeChat']);

            // Complaints / Tickets
            Route::get('/complaints', [SupportController::class, 'complaints']);
            Route::post('/complaints', [SupportController::class, 'createComplaint']);
            Route::get('/complaints/{complaintId}', [SupportController::class, 'complaintDetails']);
            Route::post('/complaints/{complaintId}/messages', [SupportController::class, 'addComplaintMessage']);
        });

        // ==================== SHARED AUTHENTICATED ROUTES ====================

        // View order details (accessible by owner: client or courier)
        Route::get('/orders/{order}', [OrderController::class, 'show'])
            ->whereUuid('order');
        Route::get('/orders/{order}/tracking', [OrderController::class, 'tracking'])
            ->whereUuid('order');

        // Courier public profile (accessible by authenticated clients)
        Route::get('/couriers/{courier}/profile', [CourierController::class, 'publicProfile']);

        // ==================== CHAT COMMANDE (Client <-> Coursier) ====================
        Route::prefix('orders/{order}/chat')->whereUuid('order')->group(function () {
            Route::get('/', [OrderChatController::class, 'show']);
            Route::get('/messages', [OrderChatController::class, 'messages']);
            Route::post('/messages', [OrderChatController::class, 'sendMessage']);
            Route::post('/read', [OrderChatController::class, 'markAsRead']);
        });

        // Payment status (accessible by payment owner)
        Route::get('/payments', [PaymentController::class, 'index']);
        Route::get('/payments/{payment}/status', [PaymentController::class, 'status']);

        // Find nearby couriers (for admins or internal use)
        Route::get('/couriers/nearby', [CourierController::class, 'nearby'])
            ->middleware('role.admin');

        // ==================== GEOFENCE PUBLIC ====================
        Route::prefix('geofence')->group(function () {
            Route::get('/zones-pricing', [GeofenceController::class, 'zonesWithPricing']);
            Route::post('/dynamic-pricing', [GeofenceController::class, 'dynamicPricing']);
            Route::post('/check-position', [GeofenceController::class, 'checkPosition']);
        });

        // ==================== ACTIVITY LOGS (USER) ====================
        Route::get('/activity/my-activity', [ActivityLogController::class, 'myActivity']);

        // ==================== ADMIN EXPORTS ====================

        Route::middleware('role.admin')->prefix('exports')->group(function () {
            Route::get('/orders/csv', [ExportController::class, 'ordersCSV']);
            Route::get('/orders/pdf', [ExportController::class, 'ordersPDF']);
            Route::get('/payments/csv', [ExportController::class, 'paymentsCSV']);
            Route::get('/withdrawals/csv', [ExportController::class, 'withdrawalsCSV']);
            Route::get('/couriers/csv', [ExportController::class, 'couriersCSV']);
            Route::get('/revenue/pdf', [ExportController::class, 'revenueReportPDF']);
        });

        // ==================== ADMIN ACTIVITY LOGS ====================

        Route::middleware('role.admin')->prefix('activity-logs')->group(function () {
            Route::get('/', [ActivityLogController::class, 'index']);
            Route::get('/stats', [ActivityLogController::class, 'stats']); // NOSONAR
            Route::get('/export', [ActivityLogController::class, 'export']);
            Route::get('/subject', [ActivityLogController::class, 'forSubject']);
            Route::get('/{log}', [ActivityLogController::class, 'show']);
        });

        // ==================== ADMIN MANAGEMENT ====================

        Route::middleware('role.admin')->prefix('admin')->group(function () {
            // Dashboard stats
            Route::get('/dashboard', [AdminController::class, 'dashboard']);

            // Gestion des admins
            Route::get('/users', [AdminController::class, 'index']);
            Route::post('/users', [AdminController::class, 'store']);
            Route::get('/users/{admin}', [AdminController::class, 'show']);
            Route::put('/users/{admin}', [AdminController::class, 'update']); // NOSONAR
            Route::delete('/users/{admin}', [AdminController::class, 'destroy']); // NOSONAR
            Route::post('/users/{admin}/change-password', [AdminController::class, 'changePassword']);
            Route::post('/users/{admin}/suspend', [AdminController::class, 'suspend']);
            Route::post('/users/{admin}/activate', [AdminController::class, 'activate']);

            // ==================== SMART DISPATCH (ADMIN) ====================
            Route::post('/orders/{order}/smart-dispatch', [AdminDispatchController::class, 'smartDispatch'])->whereUuid('order');
            Route::get('/orders/{order}/dispatch-suggestions', [AdminDispatchController::class, 'dispatchSuggestions'])->whereUuid('order');
            Route::get('/orders/{order}/dispatch-context', [AdminDispatchController::class, 'dispatchContext'])->whereUuid('order');
            Route::get('/orders/{order}/eta', [AdminDispatchController::class, 'getEta'])->whereUuid('order');
            Route::post('/dispatch/auto', [AdminDispatchController::class, 'autoDispatch']);

            // ==================== CARTE LIVE (ADMIN) ====================
            Route::prefix('map')->group(function () {
                Route::get('/live-couriers', [LiveMapController::class, 'liveCouriers']);
                Route::get('/active-orders', [LiveMapController::class, 'activeOrders']);
                Route::get('/heatmap-data', [LiveMapController::class, 'heatmapData']);
                Route::get('/stats', [LiveMapController::class, 'mapStats']);
            });
        });
    });

    // ==================== BROADCASTING AUTH ====================
    // Route d'authentification pour les canaux WebSocket privés
    Broadcast::routes(['middleware' => ['auth:sanctum']]);
});

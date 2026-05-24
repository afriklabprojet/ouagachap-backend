<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PaymentMethod;
use App\Http\Requests\Payment\InitiatePaymentRequest;
use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * @group Paiements
 *
 * Endpoints pour gérer les paiements Mobile Money.
 */
class PaymentController extends BaseController
{
    public function __construct(
        private PaymentService $paymentService
    ) {}

    /**
     * Initier un paiement
     *
     * Initie un paiement Mobile Money pour une commande.
     * Supporte Orange Money et Moov Money.
     *
     * @bodyParam order_id string required UUID de la commande. Example: 550e8400-e29b-41d4-a716-446655440000
     * @bodyParam method string required Méthode de paiement (orange_money, moov_money, cash). Example: orange_money
     * @bodyParam phone_number string required Numéro pour le paiement. Example: +22670123456
     * @response 200 {"success": true, "message": "Paiement initié.", "data": {"payment": {"id": 1, "transaction_id": "TXN-ABC123", "amount": 1500, "status": "pending"}}}
     * @response 400 {"success": false, "message": "Cette commande a déjà été payée."}
     * @response 403 {"success": false, "message": "Accès non autorisé."}
     */
    public function initiate(InitiatePaymentRequest $request): JsonResponse
    {
        $order = Order::find($request->order_id);

        if (!$order) {
            return $this->notFound('Commande non trouvée.');
        }

        // Policy-based authorization
        if ($request->user()->cannot('create', Payment::class)) {
            // @codeCoverageIgnoreStart
            return $this->forbidden('Seuls les clients peuvent initier un paiement.');
            // @codeCoverageIgnoreEnd
        }

        if ($order->client_id !== $request->user()->id) {
            return $this->forbidden('Vous ne pouvez payer que vos propres commandes.');
        }

        $result = $this->paymentService->initiatePayment(
            $order,
            $request->user(),
            PaymentMethod::from($request->method),
            $request->phone_number
        );

        if (!$result['success'] && !isset($result['pending'])) {
            return $this->error($result['message']);
        }

        return $this->success($result, $result['message']);
    }

    /**
     * Check payment status
     * GET /api/v1/payments/{payment}/status
     */
    public function status(int $paymentId, Request $request): JsonResponse
    {
        $payment = Payment::with('order:id,order_number')->find($paymentId);

        if (!$payment) {
            return $this->notFound('Paiement non trouvé.');
        }

        // Policy-based authorization
        if ($request->user()->cannot('checkStatus', $payment)) {
            return $this->forbidden('Accès non autorisé à ce paiement.');
        }

        $result = $this->paymentService->checkStatus($payment);

        return $this->success($result);
    }

    /**
     * Get user's payment history
     * GET /api/v1/payments
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 15), 100);
        $payments = $this->paymentService->getUserPayments($request->user(), $perPage);

        return $this->paginated($payments, 'Historique des paiements.');
    }

    /**
     * Handle webhook from payment provider
     * POST /api/v1/payments/webhook
     */
    public function webhook(Request $request): JsonResponse
    {
        $rawBody  = $request->getContent();
        $signature = $request->header('X-Webhook-Signature', '');

        // Première couche : rejet immédiat avant tout traitement
        if (! $this->paymentService->verifyWebhookSignature($rawBody, $signature)) {
            Log::channel('security')->warning('Payment webhook: signature invalide', [
                'ip' => $request->ip(),
            ]);
            return $this->error('Invalid webhook signature', 401);
        }

        // Le service revalide en interne (défense en profondeur) si appelé par d'autres chemins
        $result = $this->paymentService->handleWebhook($request->all(), $rawBody, $signature);

        if (! ($result['success'] ?? false)) {
            return $this->error($result['message'] ?? 'Erreur webhook.', 422);
        }

        return $this->success(null, $result['message']);
    }

    /**
     * Get payment methods
     * GET /api/v1/payments/methods
     *
     * Retourne les méthodes supportées par Sappay (Mobile Money BF) + espèces.
     */
    public function methods(): JsonResponse
    {
        $sappayMethods = collect(config('sappay.payment_methods', []))
            ->map(fn ($m, $code) => [
                'code'  => $code,
                'value' => $code,
                'name'  => $m['name'],
                'label' => $m['name'],
                'icon'  => $m['icon'],
                'color' => $m['color'],
            ])
            ->values();

        $allMethods = $sappayMethods->push([
            'code'  => PaymentMethod::CASH->value,
            'value' => PaymentMethod::CASH->value,
            'name'  => PaymentMethod::CASH->label(),
            'label' => PaymentMethod::CASH->label(),
            'icon'  => '💵',
            'color' => '#666666',
        ]);

        return $this->success($allMethods, 'Méthodes de paiement disponibles.');
    }
}

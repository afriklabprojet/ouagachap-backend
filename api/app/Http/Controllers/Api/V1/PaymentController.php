<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PaymentMethod;
use App\Http\Requests\Payment\InitiatePaymentRequest;
use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        // Check if user owns the order
        if ($order->client_id !== $request->user()->id) {
            return $this->forbidden('Accès non autorisé.');
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

        // Check authorization
        if ($payment->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            return $this->forbidden('Accès non autorisé.');
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
        $perPage = $request->query('per_page', 15);
        $payments = $this->paymentService->getUserPayments($request->user(), $perPage);

        return $this->paginated($payments, 'Historique des paiements.');
    }

    /**
     * Handle webhook from payment provider
     * POST /api/v1/payments/webhook
     */
    public function webhook(Request $request): JsonResponse
    {
        // In production, validate webhook signature here
        
        $result = $this->paymentService->handleWebhook($request->all());

        return $this->success($result, $result['message']);
    }

    /**
     * Get payment methods
     * GET /api/v1/payments/methods
     */
    public function methods(): JsonResponse
    {
        $methods = collect(PaymentMethod::cases())->map(fn($method) => [
            'value' => $method->value,
            'label' => $method->label(),
        ]);

        return $this->success($methods, 'Méthodes de paiement disponibles.');
    }
}

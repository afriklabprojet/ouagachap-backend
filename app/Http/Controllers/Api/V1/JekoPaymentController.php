<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\JekoTransaction;
use App\Models\Wallet;
use App\Services\JekoPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class JekoPaymentController extends Controller
{
    public function __construct(
        private JekoPaymentService $jekoService
    ) {}

    /**
     * Obtenir les méthodes de paiement disponibles
     */
    public function paymentMethods(): JsonResponse
    {
        $methods = collect(config('jeko.payment_methods', []))
            ->filter(fn($m) => $m['enabled'] ?? true)
            ->map(fn($m, $key) => [
                'code' => $key,
                'name' => $m['name'],
                'icon' => $m['icon'],
            ])
            ->values();

        return response()->json([
            'success' => true,
            'data' => $methods,
        ]);
    }

    /**
     * Initier une recharge de wallet via JEKO
     */
    public function initiateWalletRecharge(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:100|max:1000000',
            'payment_method' => 'required|string|in:wave,orange,mtn,moov,djamo',
        ]);

        $user = $request->user();
        $amount = $request->amount;
        $paymentMethod = $request->payment_method;

        try {
            // Créer la requête de paiement JEKO
            $result = $this->jekoService->createPaymentRequest(
                user: $user,
                amountFcfa: (int) $amount,
                paymentMethod: $paymentMethod,
                type: 'wallet_recharge',
                metadata: ['description' => "Recharge portefeuille - {$amount} FCFA"]
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Erreur lors de la création du paiement',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Paiement initié avec succès',
                'data' => [
                    'transaction_id' => $result['data']['transaction_id'],
                    'jeko_id' => $result['data']['jeko_id'],
                    'redirect_url' => $result['data']['redirect_url'],
                    'amount' => $amount,
                    'payment_method' => $paymentMethod,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur initiation paiement JEKO', [
                'user_id' => $user->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'initiation du paiement',
            ], 500);
        }
    }

    /**
     * Initier un paiement de commande via JEKO
     */
    public function initiateOrderPayment(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'payment_method' => 'required|string|in:wave,orange,mtn,moov,djamo',
        ]);

        $user = $request->user();
        $order = \App\Models\Order::findOrFail($request->order_id);

        // Vérifier que l'utilisateur est bien le client de la commande
        if ($order->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à payer cette commande',
            ], 403);
        }

        // Vérifier que la commande n'est pas déjà payée
        if ($order->payment_status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Cette commande est déjà payée',
            ], 400);
        }

        try {
            $result = $this->jekoService->createPaymentRequest(
                user: $user,
                amountFcfa: (int) $order->total_price,
                paymentMethod: $request->payment_method,
                type: 'order_payment',
                metadata: [
                    'order_id' => $order->id,
                    'description' => "Paiement commande #{$order->tracking_number}"
                ]
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Erreur lors de la création du paiement',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Paiement initié avec succès',
                'data' => [
                    'transaction_id' => $result['data']['transaction_id'],
                    'jeko_id' => $result['data']['jeko_id'],
                    'redirect_url' => $result['data']['redirect_url'],
                    'order' => [
                        'id' => $order->id,
                        'tracking_number' => $order->tracking_number,
                        'total' => $order->total_price,
                    ],
                    'payment_method' => $request->payment_method,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur initiation paiement commande JEKO', [
                'user_id' => $user->id,
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'initiation du paiement',
            ], 500);
        }
    }

    /**
     * Vérifier le statut d'un paiement
     */
    public function checkStatus(Request $request, string $transactionId): JsonResponse
    {
        $user = $request->user();

        $transaction = JekoTransaction::where('id', $transactionId)
            ->where('user_id', $user->id)
            ->first();

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction non trouvée',
            ], 404);
        }

        // Si la transaction est en attente, vérifier avec JEKO
        if ($transaction->isPending() && $transaction->jeko_id) {
            $jekoStatus = $this->jekoService->getPaymentStatus($transaction->jeko_id);
            
            if ($jekoStatus && isset($jekoStatus['status']) && $jekoStatus['status'] !== 'pending') {
                // Mettre à jour le statut local
                if ($jekoStatus['status'] === 'success') {
                    $this->processSuccessfulPayment($transaction, $jekoStatus);
                } else {
                    $transaction->markAsError($jekoStatus['message'] ?? 'Paiement échoué');
                }
            }
        }

        // Recharger la transaction
        $transaction->refresh();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $transaction->id,
                'jeko_id' => $transaction->jeko_id,
                'reference' => $transaction->reference,
                'type' => $transaction->type,
                'amount' => $transaction->amount,
                'currency' => $transaction->currency,
                'fees' => $transaction->fees,
                'status' => $transaction->status,
                'status_label' => $transaction->status_label,
                'payment_method' => $transaction->payment_method,
                'payment_method_name' => $transaction->payment_method_name,
                'created_at' => $transaction->created_at,
                'executed_at' => $transaction->executed_at,
            ],
        ]);
    }

    /**
     * Historique des transactions JEKO de l'utilisateur
     */
    public function transactionHistory(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $transactions = JekoTransaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $transactions->items(),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }

    /**
     * Callback après paiement réussi (deeplink success)
     */
    public function paymentSuccess(Request $request): JsonResponse
    {
        $transactionId = $request->query('transaction_id');
        
        if (!$transactionId) {
            return response()->json([
                'success' => false,
                'message' => 'ID de transaction manquant',
            ], 400);
        }

        $transaction = JekoTransaction::find($transactionId);

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction non trouvée',
            ], 404);
        }

        // Vérifier le statut avec JEKO
        if ($transaction->isPending() && $transaction->jeko_id) {
            $jekoStatus = $this->jekoService->checkPaymentStatus($transaction->jeko_id);
            
            if ($jekoStatus && $jekoStatus['status'] === 'success') {
                $this->processSuccessfulPayment($transaction, $jekoStatus);
            }
        }

        $transaction->refresh();

        return response()->json([
            'success' => true,
            'data' => [
                'transaction_id' => $transaction->id,
                'status' => $transaction->status,
                'status_label' => $transaction->status_label,
                'type' => $transaction->type,
                'amount' => $transaction->amount,
                'message' => $transaction->isSuccessful() 
                    ? 'Paiement effectué avec succès' 
                    : 'Le paiement est en cours de traitement',
            ],
        ]);
    }

    /**
     * Callback après paiement échoué (deeplink error)
     */
    public function paymentError(Request $request): JsonResponse
    {
        $transactionId = $request->query('transaction_id');
        
        if ($transactionId) {
            $transaction = JekoTransaction::find($transactionId);
            
            if ($transaction && $transaction->isPending()) {
                $transaction->markAsError('Paiement annulé ou échoué');
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Le paiement a échoué ou a été annulé',
            'data' => [
                'transaction_id' => $transactionId,
            ],
        ]);
    }

    /**
     * Traiter un paiement réussi
     */
    private function processSuccessfulPayment(JekoTransaction $transaction, array $jekoData): void
    {
        DB::transaction(function () use ($transaction, $jekoData) {
            // Mettre à jour la transaction
            $transaction->update([
                'status' => 'success',
                'fees' => $jekoData['fees'] ?? 0,
                'jeko_transaction_id' => $jekoData['transaction_id'] ?? null,
                'counterpart_label' => $jekoData['counterpart_label'] ?? null,
                'executed_at' => now(),
            ]);

            // Traiter selon le type de transaction
            if ($transaction->type === 'wallet_recharge') {
                $this->processWalletRecharge($transaction);
            } elseif ($transaction->type === 'order_payment') {
                $this->processOrderPayment($transaction);
            }
        });
    }

    /**
     * Traiter une recharge de wallet
     */
    private function processWalletRecharge(JekoTransaction $transaction): void
    {
        $wallet = Wallet::firstOrCreate(
            ['user_id' => $transaction->user_id],
            ['balance' => 0]
        );

        // Créditer le wallet
        $wallet->increment('balance', $transaction->amount);

        // Enregistrer la transaction wallet
        $wallet->transactions()->create([
            'type' => 'deposit',
            'amount' => $transaction->amount,
            'balance_after' => $wallet->balance,
            'description' => "Recharge via {$transaction->payment_method_name}",
            'reference' => $transaction->reference,
            'metadata' => [
                'jeko_transaction_id' => $transaction->id,
                'payment_method' => $transaction->payment_method,
            ],
        ]);

        Log::info('Wallet rechargé via JEKO', [
            'user_id' => $transaction->user_id,
            'amount' => $transaction->amount,
            'new_balance' => $wallet->balance,
        ]);
    }

    /**
     * Traiter un paiement de commande
     */
    private function processOrderPayment(JekoTransaction $transaction): void
    {
        $orderId = $transaction->metadata['order_id'] ?? null;
        
        if (!$orderId) {
            Log::error('Order ID manquant dans la transaction JEKO', [
                'transaction_id' => $transaction->id,
            ]);
            return;
        }

        $order = \App\Models\Order::find($orderId);
        
        if (!$order) {
            Log::error('Commande non trouvée pour paiement JEKO', [
                'order_id' => $orderId,
                'transaction_id' => $transaction->id,
            ]);
            return;
        }

        // Mettre à jour le statut de paiement de la commande
        $order->update([
            'payment_status' => 'paid',
            'payment_method' => "jeko_{$transaction->payment_method}",
            'paid_at' => now(),
        ]);

        Log::info('Commande payée via JEKO', [
            'order_id' => $order->id,
            'amount' => $transaction->amount,
            'payment_method' => $transaction->payment_method,
        ]);
    }
}

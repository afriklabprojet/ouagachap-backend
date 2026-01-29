<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * @group Portefeuille Client
 *
 * APIs pour la gestion du portefeuille client (recharge)
 */
class ClientWalletController extends Controller
{
    /**
     * Mon solde
     *
     * Récupère le solde du portefeuille client
     *
     * @authenticated
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "balance": 5000,
     *     "currency": "FCFA"
     *   }
     * }
     */
    public function balance(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'balance' => (int) $user->wallet_balance,
                'currency' => 'FCFA',
            ],
        ]);
    }

    /**
     * Initier une recharge
     *
     * Démarre le processus de recharge via Mobile Money
     *
     * @authenticated
     * @bodyParam amount integer required Montant à recharger (min: 100 FCFA). Example: 1000
     * @bodyParam provider string required Opérateur (orange_money, moov_money). Example: orange_money
     * @bodyParam phone string required Numéro de téléphone Mobile Money. Example: 70123456
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Recharge initiée avec succès",
     *   "data": {
     *     "transaction_id": "RECH-ABC123",
     *     "amount": 1000,
     *     "provider": "orange_money",
     *     "phone": "70123456",
     *     "status": "pending",
     *     "instructions": "Vous allez recevoir une demande de paiement sur votre téléphone. Veuillez confirmer avec votre code PIN Mobile Money."
     *   }
     * }
     */
    public function initiateRecharge(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|integer|min:100|max:500000',
            'provider' => 'required|in:orange_money,moov_money',
            'phone' => 'required|string|min:8|max:15',
        ]);

        $user = $request->user();
        $transactionId = 'RECH-' . strtoupper(Str::random(8));

        try {
            // Créer une transaction wallet en attente
            $transaction = WalletTransaction::create([
                'user_id' => $user->id,
                'transaction_id' => $transactionId,
                'amount' => $validated['amount'],
                'type' => 'recharge',
                'method' => $validated['provider'],
                'phone_number' => $validated['phone'],
                'status' => 'pending',
            ]);

            // En mode développement, simuler le succès automatique
            if (config('app.env') === 'local' || config('app.debug')) {
                // Simuler un délai puis confirmer
                $this->simulatePaymentSuccess($transaction, $user);
            }

            return response()->json([
                'success' => true,
                'message' => 'Recharge initiée avec succès',
                'data' => [
                    'transaction_id' => $transactionId,
                    'amount' => (int) $validated['amount'],
                    'provider' => $validated['provider'],
                    'phone' => $validated['phone'],
                    'status' => 'pending',
                    'instructions' => $this->getProviderInstructions($validated['provider']),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Recharge initiation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'initiation de la recharge',
            ], 500);
        }
    }

    /**
     * Confirmer une recharge (webhook ou simulation)
     *
     * @authenticated
     * @bodyParam transaction_id string required ID de la transaction. Example: RECH-ABC123
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Recharge confirmée",
     *   "data": {
     *     "new_balance": 6000
     *   }
     * }
     */
    public function confirmRecharge(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'transaction_id' => 'required|string',
        ]);

        $transaction = WalletTransaction::where('transaction_id', $validated['transaction_id'])
            ->where('status', 'pending')
            ->first();

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction non trouvée ou déjà traitée',
            ], 404);
        }

        try {
            DB::transaction(function () use ($transaction) {
                // Mettre à jour le statut de la transaction
                $transaction->update([
                    'status' => 'success',
                    'completed_at' => now(),
                ]);

                // Créditer le wallet du client
                $user = $transaction->user;
                $user->addToWallet($transaction->amount);
            });

            $user = $transaction->user->fresh();

            return response()->json([
                'success' => true,
                'message' => 'Recharge confirmée avec succès',
                'data' => [
                    'new_balance' => (int) $user->wallet_balance,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Recharge confirmation failed', [
                'transaction_id' => $validated['transaction_id'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la confirmation',
            ], 500);
        }
    }

    /**
     * Historique des recharges
     *
     * @authenticated
     * @response 200 {
     *   "success": true,
     *   "data": [
     *     {
     *       "id": 1,
     *       "transaction_id": "RECH-ABC123",
     *       "amount": 1000,
     *       "provider": "orange_money",
     *       "status": "completed",
     *       "created_at": "2026-01-20T10:00:00Z"
     *     }
     *   ]
     * }
     */
    public function history(Request $request): JsonResponse
    {
        $user = $request->user();

        $transactions = WalletTransaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'transaction_id' => $transaction->transaction_id,
                    'amount' => (int) $transaction->amount,
                    'type' => $transaction->type,
                    'provider' => $transaction->method,
                    'status' => $transaction->status,
                    'created_at' => $transaction->created_at->toISOString(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $transactions,
        ]);
    }

    /**
     * Simulation de paiement réussi (dev only)
     */
    private function simulatePaymentSuccess(WalletTransaction $transaction, User $user): void
    {
        // Simulation immédiate en dev
        DB::transaction(function () use ($transaction, $user) {
            $transaction->update([
                'status' => 'success',
                'completed_at' => now(),
            ]);

            $user->addToWallet($transaction->amount);
        });

        Log::info('Simulated recharge success', [
            'transaction_id' => $transaction->transaction_id,
            'amount' => $transaction->amount,
            'user_id' => $user->id,
        ]);
    }

    /**
     * Instructions selon le provider
     */
    private function getProviderInstructions(string $provider): string
    {
        return match ($provider) {
            'orange_money' => 'Vous allez recevoir une demande de paiement Orange Money. Composez #144# ou ouvrez l\'app Orange Money pour confirmer.',
            'moov_money' => 'Vous allez recevoir une demande de paiement Moov Money. Composez *555# ou ouvrez l\'app Moov Money pour confirmer.',
            default => 'Veuillez confirmer le paiement sur votre téléphone.',
        };
    }
}

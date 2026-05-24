<?php

namespace App\Jobs;

use App\Models\SappayTransaction;
use App\Notifications\SappayTransactionStuckNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Polling fallback for Sappay transactions stuck in `pending`.
 *
 * Sappay is webhook-driven and has no server-side status query endpoint.
 * This job is dispatched with a delay (e.g. 30 min) after payment initiation.
 * If the transaction is still pending when the job runs, the webhook never
 * arrived — expire the transaction and alert admin for manual handling.
 */
class ProcessSappayPaymentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int   $tries   = 3;
    public array $backoff = [60, 120, 300];
    public int   $timeout = 60;

    public function __construct(
        private int $transactionId
    ) {}

    public function handle(): void
    {
        $transaction = SappayTransaction::with('user')->find($this->transactionId);

        if (!$transaction) {
            Log::warning('ProcessSappayPaymentJob: transaction introuvable', ['id' => $this->transactionId]);
            return;
        }

        if (!$transaction->isPending()) {
            return;
        }

        // Transaction is still pending — the Sappay webhook never arrived.
        // Expire it and notify admin for manual review.
        $transaction->markAsExpired();

        Log::warning('ProcessSappayPaymentJob: transaction expirée — webhook Sappay non reçu', [
            'transaction_id' => $this->transactionId,
            'invoice_id'     => $transaction->invoice_id,
            'user_id'        => $transaction->user_id,
            'amount'         => $transaction->amount,
        ]);

        $adminEmail = config('mail.admin_address');
        if ($adminEmail) {
            Notification::route('mail', $adminEmail)
                ->notify(new SappayTransactionStuckNotification($transaction));
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessSappayPaymentJob: échec permanent', [
            'transaction_id' => $this->transactionId,
            'error'          => $exception->getMessage(),
        ]);
    }
}

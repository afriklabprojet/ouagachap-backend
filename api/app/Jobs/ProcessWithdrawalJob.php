<?php

namespace App\Jobs;

use App\Models\Withdrawal;
use App\Notifications\WithdrawalFailedAdminNotification;
use App\Services\SappayService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Attempts an automatic B2C disbursement for a courier withdrawal via Sappay.
 *
 * Flow:
 *   1. Call Sappay disburse API.
 *   2. On success  → mark Withdrawal completed, confirm wallet debit.
 *   3. On failure  → mark Withdrawal as pending (admin queue), alert admin.
 */
class ProcessWithdrawalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 90;

    /** Exponential back-off: 1 min, 5 min, 15 min */
    public array $backoff = [60, 300, 900];

    public function __construct(
        private readonly int $withdrawalId
    ) {}

    public function handle(SappayService $sappay): void
    {
        $withdrawal = Withdrawal::with(['user', 'wallet'])->find($this->withdrawalId);

        if (!$withdrawal) {
            Log::warning('ProcessWithdrawalJob: withdrawal not found', ['id' => $this->withdrawalId]);
            return;
        }

        if ($withdrawal->status !== 'processing') {
            return;
        }

        $provider = $withdrawal->payment_provider;

        if (! config("sappay.payment_methods.{$provider}")) {
            $this->fallbackToAdmin($withdrawal, "Opérateur '{$provider}' non supporté par Sappay.");
            return;
        }

        try {
            $result = $sappay->disburse(
                courier:       $withdrawal->user,
                amountFcfa:    (int) $withdrawal->amount,
                paymentMethod: $provider,
                msisdn:        $withdrawal->payment_phone,
                withdrawalRef: (string) $withdrawal->id,
            );

            if ($result['success']) {
                $this->markCompleted($withdrawal, $result['reference']);
                return;
            }

            throw new \RuntimeException($result['message']);

        } catch (\Throwable $e) {
            Log::error('ProcessWithdrawalJob: disbursement failed', [
                'withdrawal_id' => $this->withdrawalId,
                'provider'      => $provider,
                'attempt'       => $this->attempts(),
                'error'         => $e->getMessage(),
            ]);

            if ($this->attempts() < $this->tries) {
                throw $e;
            }

            $this->fallbackToAdmin($withdrawal, $e->getMessage());
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::critical('ProcessWithdrawalJob: permanently failed', [
            'withdrawal_id' => $this->withdrawalId,
            'error'         => $exception->getMessage(),
        ]);

        $withdrawal = Withdrawal::with(['user', 'wallet'])->find($this->withdrawalId);
        if ($withdrawal && $withdrawal->status === 'processing') {
            $this->fallbackToAdmin($withdrawal, $exception->getMessage());
        }
    }

    // ──────────────────────────────────────────────────────────────────────────

    private function markCompleted(Withdrawal $withdrawal, string $reference): void
    {
        DB::transaction(function () use ($withdrawal, $reference) {
            $withdrawal->complete($reference);
        });

        Log::info('ProcessWithdrawalJob: withdrawal completed', [
            'withdrawal_id' => $this->withdrawalId,
            'reference'     => $reference,
        ]);

        \Sentry\configureScope(function (\Sentry\State\Scope $scope) use ($withdrawal, $reference): void {
            $scope->setContext('business_event', [
                'event'         => 'courier_withdrawal_success',
                'withdrawal_id' => $withdrawal->id,
                'courier_id'    => $withdrawal->user_id,
                'amount_fcfa'   => $withdrawal->amount,
                'method'        => $withdrawal->payment_provider,
                'reference'     => $reference,
            ]);
            \Sentry\captureMessage(
                "[BUSINESS] Retrait coursier {$withdrawal->amount} FCFA — withdrawal #{$withdrawal->id}",
                \Sentry\Severity::info()
            );
        });
    }

    /**
     * Revert the withdrawal to `pending` so an admin can process it manually,
     * and send an admin notification.
     */
    private function fallbackToAdmin(Withdrawal $withdrawal, string $reason): void
    {
        DB::transaction(function () use ($withdrawal, $reason) {
            $withdrawal->update([
                'status'           => 'pending',
                'rejection_reason' => null,
            ]);
        });

        Log::warning('ProcessWithdrawalJob: fallback to manual processing', [
            'withdrawal_id' => $this->withdrawalId,
            'reason'        => $reason,
        ]);

        try {
            Notification::route('mail', config('mail.admin_address'))
                ->notify(new WithdrawalFailedAdminNotification($withdrawal, $reason));
        } catch (\Throwable $e) {
            Log::error('ProcessWithdrawalJob: admin notification failed', ['error' => $e->getMessage()]);
        }
    }
}

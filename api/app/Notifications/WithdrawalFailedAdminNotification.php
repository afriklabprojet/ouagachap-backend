<?php

namespace App\Notifications;

use App\Models\Withdrawal;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WithdrawalFailedAdminNotification extends Notification
{
    public function __construct(
        private readonly Withdrawal $withdrawal,
        private readonly string $reason,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $w = $this->withdrawal;

        return (new MailMessage())
            ->error()
            ->subject('[Action requise] Retrait automatique échoué — #' . $w->id)
            ->line('Le payout automatique pour le retrait ci-dessous a échoué et nécessite un traitement manuel.')
            ->line('**Retrait #' . $w->id . '**')
            ->line('Coursier : ' . ($w->user->name ?? 'ID ' . $w->user_id))
            ->line('Montant : ' . number_format((float) $w->amount, 0, ',', ' ') . ' FCFA')
            ->line('Opérateur : ' . $w->payment_provider)
            ->line('Numéro : ' . $w->payment_phone)
            ->line('Raison de l\'échec : ' . $this->reason)
            ->action('Traiter dans l\'admin', url('/admin/withdrawals/' . $w->id));
    }
}

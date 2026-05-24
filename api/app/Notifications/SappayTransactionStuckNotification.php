<?php

namespace App\Notifications;

use App\Models\SappayTransaction;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SappayTransactionStuckNotification extends Notification
{
    public function __construct(
        private readonly SappayTransaction $transaction,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $t = $this->transaction;

        return (new MailMessage())
            ->error()
            ->subject('[Action requise] Transaction Sappay bloquée — #' . $t->id)
            ->line('Une transaction Sappay est restée en statut `pending` et a été expirée automatiquement.')
            ->line('**Transaction #' . $t->id . '**')
            ->line('Utilisateur : ' . ($t->user->name ?? 'ID ' . $t->user_id))
            ->line('Montant : ' . number_format((float) $t->amount, 0, ',', ' ') . ' FCFA')
            ->line('Type : ' . $t->type)
            ->line('Méthode : ' . $t->payment_method)
            ->line('Invoice Sappay : ' . ($t->invoice_id ?? 'N/A'))
            ->line('Créée le : ' . $t->created_at->toDateTimeString())
            ->action('Voir dans l\'admin', url('/admin/sappay-transactions/' . $t->id));
    }
}

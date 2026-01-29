<?php

namespace App\Filament\Resources\NotificationResource\Pages;

use App\Filament\Resources\NotificationResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;

class ViewNotification extends ViewRecord
{
    protected static string $resource = NotificationResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Détails de la notification')
                    ->schema([
                        Components\TextEntry::make('user.name')
                            ->label('Destinataire'),
                        Components\TextEntry::make('type')
                            ->label('Type')
                            ->badge()
                            ->formatStateUsing(fn ($state) => match($state) {
                                'order_status' => '📦 Commande',
                                'payment' => '💳 Paiement',
                                'promo' => '🎁 Promotion',
                                'system' => '🔔 Système',
                                'wallet' => '💰 Portefeuille',
                                default => $state,
                            }),
                        Components\TextEntry::make('title')
                            ->label('Titre'),
                        Components\TextEntry::make('message')
                            ->label('Message')
                            ->columnSpanFull(),
                        Components\TextEntry::make('action_url')
                            ->label('URL d\'action')
                            ->url(fn ($record) => $record->action_url)
                            ->openUrlInNewTab()
                            ->placeholder('-'),
                    ])->columns(2),

                Components\Section::make('Statut')
                    ->schema([
                        Components\IconEntry::make('is_read')
                            ->label('Lu')
                            ->boolean(),
                        Components\TextEntry::make('read_at')
                            ->label('Date de lecture')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('Non lu'),
                        Components\TextEntry::make('created_at')
                            ->label('Créée le')
                            ->dateTime('d/m/Y H:i'),
                    ])->columns(3),
            ]);
    }
}

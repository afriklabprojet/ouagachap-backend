<?php

namespace App\Filament\Resources\NotificationResource\Pages;

use App\Filament\Resources\NotificationResource;
use App\Filament\Pages\SendNotification;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListNotifications extends ListRecords
{
    protected static string $resource = NotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('send_bulk')
                ->label('📢 Envoyer en masse')
                ->icon('heroicon-o-megaphone')
                ->url(SendNotification::getUrl())
                ->color('primary'),
            Actions\CreateAction::make()
                ->label('Nouvelle notification'),
        ];
    }
}

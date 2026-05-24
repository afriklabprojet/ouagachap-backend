<?php

namespace App\Filament\Resources\SupportChatResource\Pages;

use App\Filament\Resources\SupportChatResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSupportChat extends ViewRecord
{
    protected static string $resource = SupportChatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}

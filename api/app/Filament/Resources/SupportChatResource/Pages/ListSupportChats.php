<?php

namespace App\Filament\Resources\SupportChatResource\Pages;

use App\Filament\Resources\SupportChatResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSupportChats extends ListRecords
{
    protected static string $resource = SupportChatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

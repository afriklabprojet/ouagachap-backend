<?php

namespace App\Filament\Resources\AutoAlertResource\Pages;

use App\Filament\Resources\AutoAlertResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAutoAlerts extends ListRecords
{
    protected static string $resource = AutoAlertResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

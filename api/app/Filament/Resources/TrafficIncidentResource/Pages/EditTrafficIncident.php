<?php

namespace App\Filament\Resources\TrafficIncidentResource\Pages;

use App\Filament\Resources\TrafficIncidentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTrafficIncident extends EditRecord
{
    protected static string $resource = TrafficIncidentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

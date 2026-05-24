<?php

namespace App\Filament\Resources\TrafficIncidentResource\Pages;

use App\Filament\Resources\TrafficIncidentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTrafficIncident extends ViewRecord
{
    protected static string $resource = TrafficIncidentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

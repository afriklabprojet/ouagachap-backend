<?php

namespace App\Filament\Resources\TrafficIncidentResource\Pages;

use App\Filament\Resources\TrafficIncidentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTrafficIncident extends CreateRecord
{
    protected static string $resource = TrafficIncidentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

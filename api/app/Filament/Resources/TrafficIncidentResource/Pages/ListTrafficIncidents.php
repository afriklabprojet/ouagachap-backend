<?php

namespace App\Filament\Resources\TrafficIncidentResource\Pages;

use App\Filament\Resources\TrafficIncidentResource;
use Filament\Resources\Pages\ListRecords;

class ListTrafficIncidents extends ListRecords
{
    protected static string $resource = TrafficIncidentResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

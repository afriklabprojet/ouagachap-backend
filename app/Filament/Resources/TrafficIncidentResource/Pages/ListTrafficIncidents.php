<?php

namespace App\Filament\Resources\TrafficIncidentResource\Pages;

use App\Filament\Resources\TrafficIncidentResource;
use App\Filament\Resources\TrafficIncidentResource\Widgets\TrafficStatsOverview;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListTrafficIncidents extends ListRecords
{
    protected static string $resource = TrafficIncidentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            TrafficStatsOverview::class,
        ];
    }
}

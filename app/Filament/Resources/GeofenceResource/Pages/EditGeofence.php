<?php

namespace App\Filament\Resources\GeofenceResource\Pages;

use App\Filament\Resources\GeofenceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGeofence extends EditRecord
{
    protected static string $resource = GeofenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

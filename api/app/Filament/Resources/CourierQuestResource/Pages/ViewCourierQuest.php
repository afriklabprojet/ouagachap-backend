<?php

namespace App\Filament\Resources\CourierQuestResource\Pages;

use App\Filament\Resources\CourierQuestResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCourierQuest extends ViewRecord
{
    protected static string $resource = CourierQuestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}

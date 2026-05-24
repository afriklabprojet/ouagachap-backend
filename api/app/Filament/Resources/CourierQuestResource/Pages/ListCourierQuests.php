<?php

namespace App\Filament\Resources\CourierQuestResource\Pages;

use App\Filament\Resources\CourierQuestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCourierQuests extends ListRecords
{
    protected static string $resource = CourierQuestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

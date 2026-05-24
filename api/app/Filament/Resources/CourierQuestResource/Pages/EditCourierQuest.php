<?php

namespace App\Filament\Resources\CourierQuestResource\Pages;

use App\Filament\Resources\CourierQuestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCourierQuest extends EditRecord
{
    protected static string $resource = CourierQuestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

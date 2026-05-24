<?php

namespace App\Filament\Resources\SubscriptionPlanConfigResource\Pages;

use App\Filament\Resources\SubscriptionPlanConfigResource;
use Filament\Resources\Pages\ListRecords;

class ListSubscriptionPlanConfigs extends ListRecords
{
    protected static string $resource = SubscriptionPlanConfigResource::class;

    protected function getHeaderActions(): array
    {
        // Pas de bouton "Créer" — les plans sont fixes (Basic & Premium)
        return [];
    }
}

<?php

namespace App\Filament\Resources\SubscriptionPlanConfigResource\Pages;

use App\Filament\Resources\SubscriptionPlanConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSubscriptionPlanConfig extends EditRecord
{
    protected static string $resource = SubscriptionPlanConfigResource::class;

    protected function getHeaderActions(): array
    {
        // Pas de suppression — les configs sont permanentes
        return [];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Tarifs mis à jour avec succès';
    }
}

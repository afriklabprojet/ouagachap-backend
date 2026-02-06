<?php

namespace App\Filament\Resources\CourierResource\Pages;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\CourierResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCourier extends CreateRecord
{
    protected static string $resource = CourierResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['role'] = UserRole::COURIER;
        $data['status'] = $data['status'] ?? UserStatus::PENDING;
        
        return $data;
    }
}

<?php

namespace App\Filament\Resources\CourierResource\Pages;

use App\Enums\UserRole;
use App\Exports\CouriersExport;
use App\Filament\Resources\CourierResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;
use Maatwebsite\Excel\Facades\Excel;

class ListCouriers extends ListRecords
{
    protected static string $resource = CourierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export_excel')
                ->label('📥 Exporter Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->form([
                    \Filament\Forms\Components\Select::make('status')
                        ->label('Statut')
                        ->options([
                            '' => 'Tous les statuts',
                            'pending' => 'En attente',
                            'approved' => 'Approuvés',
                            'suspended' => 'Suspendus',
                            'rejected' => 'Rejetés',
                        ])
                        ->default(''),
                    \Filament\Forms\Components\Toggle::make('is_available')
                        ->label('En ligne uniquement'),
                ])
                ->action(function (array $data) {
                    $filename = 'coursiers_' . now()->format('Y-m-d_His') . '.xlsx';
                    return Excel::download(
                        new CouriersExport(
                            $data['status'] ?: null,
                            $data['is_available'] ?? null
                        ),
                        $filename
                    );
                }),
            Actions\CreateAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Exports\OrdersExport;
use App\Filament\Resources\OrderResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;
use Maatwebsite\Excel\Facades\Excel;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

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
                            'assigned' => 'Assignées',
                            'picked_up' => 'Récupérées',
                            'delivered' => 'Livrées',
                            'cancelled' => 'Annulées',
                        ])
                        ->default(''),
                    \Filament\Forms\Components\DatePicker::make('date_from')
                        ->label('Du'),
                    \Filament\Forms\Components\DatePicker::make('date_to')
                        ->label('Au'),
                ])
                ->action(function (array $data) {
                    $filename = 'commandes_' . now()->format('Y-m-d_His') . '.xlsx';
                    return Excel::download(
                        new OrdersExport(
                            $data['status'] ?: null,
                            $data['date_from'] ?? null,
                            $data['date_to'] ?? null
                        ),
                        $filename
                    );
                }),
            Actions\CreateAction::make(),
        ];
    }
}

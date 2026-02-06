<?php

namespace App\Filament\Resources\WithdrawalResource\Pages;

use App\Exports\WithdrawalsExport;
use App\Filament\Resources\WithdrawalResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListWithdrawals extends ListRecords
{
    protected static string $resource = WithdrawalResource::class;

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
                            'processing' => 'En cours',
                            'completed' => 'Complétés',
                            'rejected' => 'Rejetés',
                            'failed' => 'Échoués',
                        ])
                        ->default(''),
                    \Filament\Forms\Components\DatePicker::make('date_from')
                        ->label('Du'),
                    \Filament\Forms\Components\DatePicker::make('date_to')
                        ->label('Au'),
                ])
                ->action(function (array $data) {
                    $filename = 'retraits_' . now()->format('Y-m-d_His') . '.xlsx';
                    return Excel::download(
                        new WithdrawalsExport(
                            $data['status'] ?: null,
                            $data['date_from'] ?? null,
                            $data['date_to'] ?? null
                        ),
                        $filename
                    );
                }),
        ];
    }
}

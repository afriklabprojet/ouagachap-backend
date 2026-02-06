<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Exports\PaymentsExport;
use App\Filament\Resources\PaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;

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
                            'failed' => 'Échoués',
                            'refunded' => 'Remboursés',
                        ])
                        ->default(''),
                    \Filament\Forms\Components\Select::make('provider')
                        ->label('Fournisseur')
                        ->options([
                            '' => 'Tous',
                            'orange_money' => 'Orange Money',
                            'moov_money' => 'Moov Money',
                            'coris_money' => 'Coris Money',
                            'cash' => 'Espèces',
                        ])
                        ->default(''),
                    \Filament\Forms\Components\DatePicker::make('date_from')
                        ->label('Du'),
                    \Filament\Forms\Components\DatePicker::make('date_to')
                        ->label('Au'),
                ])
                ->action(function (array $data) {
                    $filename = 'paiements_' . now()->format('Y-m-d_His') . '.xlsx';
                    return Excel::download(
                        new PaymentsExport(
                            $data['status'] ?: null,
                            $data['provider'] ?: null,
                            $data['date_from'] ?? null,
                            $data['date_to'] ?? null
                        ),
                        $filename
                    );
                }),
        ];
    }
}

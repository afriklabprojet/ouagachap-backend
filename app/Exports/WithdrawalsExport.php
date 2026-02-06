<?php

namespace App\Exports;

use App\Models\Withdrawal;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WithdrawalsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    use Exportable;

    protected ?string $status = null;
    protected ?string $dateFrom = null;
    protected ?string $dateTo = null;

    public function __construct(?string $status = null, ?string $dateFrom = null, ?string $dateTo = null)
    {
        $this->status = $status;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
    }

    public function query(): Builder
    {
        $query = Withdrawal::query()
            ->with(['user', 'processedBy']);

        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'ID',
            'Date demande',
            'Coursier',
            'Téléphone',
            'Montant (FCFA)',
            'Méthode',
            'Numéro destination',
            'Statut',
            'Traité par',
            'Date traitement',
            'Notes',
        ];
    }

    public function map($withdrawal): array
    {
        return [
            $withdrawal->id,
            $withdrawal->created_at->format('d/m/Y H:i'),
            $withdrawal->user?->name ?? 'N/A',
            $withdrawal->user?->phone ?? 'N/A',
            number_format($withdrawal->amount ?? 0, 0, ',', ' '),
            $this->formatMethod($withdrawal->method ?? $withdrawal->payment_method),
            $withdrawal->phone_number ?? 'N/A',
            $this->formatStatus($withdrawal->status),
            $withdrawal->processedBy?->name ?? 'N/A',
            $withdrawal->processed_at?->format('d/m/Y H:i') ?? 'N/A',
            $withdrawal->notes ?? '',
        ];
    }

    protected function formatMethod(?string $method): string
    {
        return match($method) {
            'orange_money' => 'Orange Money',
            'moov_money' => 'Moov Money',
            'coris_money' => 'Coris Money',
            'bank_transfer' => 'Virement bancaire',
            default => $method ?? 'N/A',
        };
    }

    protected function formatStatus(?string $status): string
    {
        return match($status) {
            'pending' => 'En attente',
            'processing' => 'En cours',
            'completed' => 'Complété',
            'rejected' => 'Rejeté',
            'failed' => 'Échoué',
            default => $status ?? 'N/A',
        };
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'EF4444'], // Rouge
                ],
            ],
        ];
    }
}

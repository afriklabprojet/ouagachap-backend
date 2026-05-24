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

    protected ?string $status;
    protected ?string $dateFrom;
    protected ?string $dateTo;

    public function __construct(?string $status = null, ?string $dateFrom = null, ?string $dateTo = null)
    {
        $this->status = $status;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
    }

    public function query(): Builder
    {
        $query = Withdrawal::query()
            ->with(['user', 'approvedBy']);

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
            'Date',
            'Coursier',
            'Téléphone',
            'Montant (FCFA)',
            'Méthode',
            'Numéro paiement',
            'Statut',
            'Raison rejet',
            'Approuvé par',
            'Date approbation',
            'Référence',
        ];
    }

    public function map($withdrawal): array
    {
        return [
            $withdrawal->id,
            $withdrawal->created_at?->format('d/m/Y H:i') ?? 'N/A',
            $withdrawal->user?->name ?? 'N/A',
            $withdrawal->user?->phone ?? 'N/A',
            number_format($withdrawal->amount ?? 0, 0, ',', ' '),
            $withdrawal->payment_method ?? 'N/A',
            $withdrawal->payment_phone ?? 'N/A',
            $this->formatStatus($withdrawal->status),
            $withdrawal->rejection_reason ?? 'N/A',
            $withdrawal->approvedBy?->name ?? 'N/A',
            $withdrawal->approved_at?->format('d/m/Y H:i') ?? 'N/A',
            $withdrawal->transaction_reference ?? 'N/A',
        ];
    }

    protected function formatStatus(?string $status): string
    {
        return match($status) {
            'pending' => 'En attente',
            'approved' => 'Approuvé',
            'completed' => 'Complété',
            'rejected' => 'Rejeté',
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
                    'startColor' => ['rgb' => 'EF4444'],
                ],
            ],
        ];
    }
}

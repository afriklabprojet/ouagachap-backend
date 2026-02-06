<?php

namespace App\Exports;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PaymentsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    use Exportable;

    protected ?string $status = null;
    protected ?string $provider = null;
    protected ?string $dateFrom = null;
    protected ?string $dateTo = null;

    public function __construct(?string $status = null, ?string $provider = null, ?string $dateFrom = null, ?string $dateTo = null)
    {
        $this->status = $status;
        $this->provider = $provider;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
    }

    public function query(): Builder
    {
        $query = Payment::query()
            ->with(['order.client', 'order.courier']);

        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->provider) {
            $query->where('provider', $this->provider);
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
            'ID Transaction',
            'Date',
            'ID Commande',
            'Client',
            'Coursier',
            'Montant (FCFA)',
            'Fournisseur',
            'Numéro Mobile',
            'Statut',
            'Référence externe',
            'Date confirmation',
        ];
    }

    public function map($payment): array
    {
        return [
            $payment->id,
            $payment->created_at->format('d/m/Y H:i'),
            $payment->order?->tracking_code ?? $payment->order_id,
            $payment->order?->client?->name ?? 'N/A',
            $payment->order?->courier?->name ?? 'Non assigné',
            number_format($payment->amount ?? 0, 0, ',', ' '),
            $this->formatProvider($payment->provider),
            $payment->phone_number ?? 'N/A',
            $this->formatStatus($payment->status),
            $payment->external_reference ?? 'N/A',
            $payment->confirmed_at?->format('d/m/Y H:i') ?? 'N/A',
        ];
    }

    protected function formatProvider(?string $provider): string
    {
        return match($provider) {
            'orange_money' => 'Orange Money',
            'moov_money' => 'Moov Money',
            'coris_money' => 'Coris Money',
            'cash' => 'Espèces',
            default => $provider ?? 'N/A',
        };
    }

    protected function formatStatus(?string $status): string
    {
        return match($status) {
            'pending' => 'En attente',
            'processing' => 'En cours',
            'completed' => 'Complété',
            'failed' => 'Échoué',
            'refunded' => 'Remboursé',
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
                    'startColor' => ['rgb' => '3B82F6'], // Bleu
                ],
            ],
        ];
    }
}

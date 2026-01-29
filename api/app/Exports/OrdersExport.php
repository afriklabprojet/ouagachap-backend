<?php

namespace App\Exports;

use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OrdersExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
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
        $query = Order::query()
            ->with(['client', 'courier', 'payment']);

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
            'ID Commande',
            'Date',
            'Client',
            'Téléphone Client',
            'Coursier',
            'Statut',
            'Adresse Pickup',
            'Adresse Livraison',
            'Distance (km)',
            'Prix Base (FCFA)',
            'Supplément Taille',
            'Frais Spéciaux',
            'Total (FCFA)',
            'Gains Coursier (FCFA)',
            'Commission (FCFA)',
            'Paiement',
            'Statut Paiement',
        ];
    }

    public function map($order): array
    {
        return [
            $order->tracking_code ?? $order->id,
            $order->created_at->format('d/m/Y H:i'),
            $order->client?->name ?? 'N/A',
            $order->client?->phone ?? 'N/A',
            $order->courier?->name ?? 'Non assigné',
            $this->formatStatus($order->status),
            $order->pickup_address ?? 'N/A',
            $order->delivery_address ?? 'N/A',
            number_format($order->distance ?? 0, 2),
            number_format($order->base_price ?? 0, 0, ',', ' '),
            number_format($order->size_supplement ?? 0, 0, ',', ' '),
            number_format($order->special_fees ?? 0, 0, ',', ' '),
            number_format($order->total_price ?? 0, 0, ',', ' '),
            number_format($order->courier_earnings ?? 0, 0, ',', ' '),
            number_format($order->platform_fee ?? 0, 0, ',', ' '),
            $order->payment?->provider ?? 'N/A',
            $order->payment?->status ?? 'N/A',
        ];
    }

    protected function formatStatus(?string $status): string
    {
        return match($status) {
            'pending' => 'En attente',
            'assigned' => 'Assignée',
            'picked_up' => 'Récupérée',
            'delivered' => 'Livrée',
            'cancelled' => 'Annulée',
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
                    'startColor' => ['rgb' => 'F97316'], // Orange OUAGA CHAP
                ],
            ],
        ];
    }
}

<?php

namespace App\Exports;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CouriersExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    use Exportable;

    protected ?string $status = null;
    protected ?bool $isAvailable = null;

    public function __construct(?string $status = null, ?bool $isAvailable = null)
    {
        $this->status = $status;
        $this->isAvailable = $isAvailable;
    }

    public function query(): Builder
    {
        $query = User::query()
            ->where('role', UserRole::COURIER)
            ->with(['wallet']);

        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->isAvailable !== null) {
            $query->where('is_available', $this->isAvailable);
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nom',
            'Téléphone',
            'Email',
            'Véhicule',
            'Plaque',
            'Statut',
            'En ligne',
            'Commandes livrées',
            'Note moyenne',
            'Solde Wallet (FCFA)',
            'Total gains (FCFA)',
            'Date inscription',
        ];
    }

    public function map($user): array
    {
        return [
            $user->id,
            $user->name,
            $user->phone,
            $user->email ?? 'N/A',
            $user->vehicle_type ?? 'N/A',
            $user->vehicle_plate ?? 'N/A',
            $this->formatStatus($user->status),
            $user->is_available ? 'Oui' : 'Non',
            $user->total_orders ?? 0,
            number_format($user->average_rating ?? 0, 1),
            number_format($user->wallet?->balance ?? 0, 0, ',', ' '),
            number_format($user->total_earnings ?? 0, 0, ',', ' '),
            $user->created_at->format('d/m/Y'),
        ];
    }

    protected function formatStatus(?string $status): string
    {
        return match($status) {
            'pending' => 'En attente',
            'approved' => 'Approuvé',
            'suspended' => 'Suspendu',
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
                    'startColor' => ['rgb' => '10B981'], // Vert OUAGA CHAP
                ],
            ],
        ];
    }
}

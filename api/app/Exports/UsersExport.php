<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UsersExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    use Exportable;

    protected string $dateFrom;
    protected string $dateTo;
    protected ?string $role;

    public function __construct(string $dateFrom, string $dateTo, ?string $role = null)
    {
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->role = $role;
    }

    public function query(): Builder
    {
        $query = User::query()
            ->withCount('clientOrders');

        if ($this->role) {
            $query->where('role', $this->role);
        }

        $query->whereDate('created_at', '>=', $this->dateFrom);
        $query->whereDate('created_at', '<=', $this->dateTo);

        return $query->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'Nom',
            'Téléphone',
            'Email',
            'Rôle',
            'Statut',
            'Commandes',
            'Solde Portefeuille',
            'Date inscription',
        ];
    }

    public function map($user): array
    {
        return [
            $user->name,
            $user->phone,
            $user->email ?? 'N/A',
            $user->role instanceof \BackedEnum ? $user->role->value : ($user->role ?? 'N/A'),
            $user->status instanceof \BackedEnum ? $user->status->value : ($user->status ?? 'N/A'),
            $user->client_orders_count ?? 0,
            number_format($user->wallet_balance ?? 0, 0, ',', ' '),
            $user->created_at?->format('d/m/Y H:i') ?? 'N/A',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '10B981'],
                ],
            ],
        ];
    }
}

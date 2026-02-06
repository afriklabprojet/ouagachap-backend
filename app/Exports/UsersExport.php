<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UsersExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected string $startDate;
    protected string $endDate;
    protected string $role;

    public function __construct(string $startDate, string $endDate, string $role = 'client')
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->role = $role;
    }

    public function query()
    {
        return User::query()
            ->where('role', $this->role)
            ->whereBetween('created_at', [$this->startDate, $this->endDate . ' 23:59:59'])
            ->withCount('clientOrders')
            ->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'Nom',
            'Téléphone',
            'Email',
            'Statut',
            'Nombre de commandes',
            'Date inscription',
        ];
    }

    public function map($user): array
    {
        return [
            $user->name,
            $user->phone,
            $user->email ?? 'N/A',
            $this->getStatusLabel($user->status->value ?? $user->status),
            $user->client_orders_count,
            $user->created_at->format('d/m/Y H:i'),
        ];
    }

    protected function getStatusLabel(string $status): string
    {
        return match ($status) {
            'active' => 'Actif',
            'inactive' => 'Inactif',
            'suspended' => 'Suspendu',
            'pending' => 'En attente',
            default => $status,
        };
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '3B82F6'],
                ],
            ],
        ];
    }
}

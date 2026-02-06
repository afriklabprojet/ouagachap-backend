<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Models\Withdrawal;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Collection;

class ExportService
{
    /**
     * Export des commandes en CSV
     */
    public function ordersToCSV(array $filters = []): string
    {
        $orders = $this->getOrdersQuery($filters)->get();

        $csv = "Numéro;Date;Client;Coursier;Statut;Montant Total;Frais Livraison;Mode Paiement\n";

        foreach ($orders as $order) {
            $csv .= implode(';', [
                $order->order_number,
                $order->created_at->format('d/m/Y H:i'),
                $order->client->name ?? 'N/A',
                $order->courier->name ?? 'Non assigné',
                $this->translateStatus($order->status->value ?? $order->status),
                number_format($order->total_price, 0, ',', ' '),
                number_format($order->courier_earnings, 0, ',', ' '),
                $order->payment_method ?? 'N/A',
            ]) . "\n";
        }

        return $csv;
    }

    /**
     * Export des commandes en PDF
     */
    public function ordersToPDF(array $filters = [])
    {
        $orders = $this->getOrdersQuery($filters)->get();
        
        $data = [
            'orders' => $orders,
            'filters' => $filters,
            'generated_at' => now(),
            'total_revenue' => $orders->sum('total_price'),
            'total_fees' => $orders->sum('courier_earnings'),
        ];

        $pdf = Pdf::loadView('exports.orders', $data);
        
        return $pdf->download('commandes_' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Export des paiements en CSV
     */
    public function paymentsToCSV(array $filters = []): string
    {
        $payments = $this->getPaymentsQuery($filters)->get();

        $csv = "Référence;Date;Commande;Montant;Méthode;Statut;Opérateur\n";

        foreach ($payments as $payment) {
            $csv .= implode(';', [
                $payment->reference,
                $payment->created_at->format('d/m/Y H:i'),
                $payment->order->order_number ?? 'N/A',
                number_format($payment->amount, 0, ',', ' '),
                $payment->method ?? 'N/A',
                $this->translatePaymentStatus($payment->status),
                $payment->provider ?? 'N/A',
            ]) . "\n";
        }

        return $csv;
    }

    /**
     * Export des retraits en CSV
     */
    public function withdrawalsToCSV(array $filters = []): string
    {
        $withdrawals = $this->getWithdrawalsQuery($filters)->get();

        $csv = "ID;Date;Coursier;Téléphone;Montant;Méthode;Statut;Référence Transaction\n";

        foreach ($withdrawals as $w) {
            $csv .= implode(';', [
                $w->id,
                $w->created_at->format('d/m/Y H:i'),
                $w->user->name ?? 'N/A',
                $w->user->phone ?? 'N/A',
                number_format($w->amount, 0, ',', ' '),
                $w->payment_method === 'mobile_money' ? 'Mobile Money' : 'Virement',
                $this->translateWithdrawalStatus($w->status),
                $w->transaction_reference ?? 'N/A',
            ]) . "\n";
        }

        return $csv;
    }

    /**
     * Export des coursiers en CSV
     */
    public function couriersToCSV(): string
    {
        $couriers = User::where('role', 'courier')
            ->with(['courierOrders'])
            ->withCount(['courierOrders as deliveries_count' => fn ($q) => $q->where('status', 'delivered')])
            ->get();

        $csv = "Nom;Téléphone;Email;Livraisons;Note;Disponible;Date Inscription\n";

        foreach ($couriers as $courier) {
            $csv .= implode(';', [
                $courier->name,
                $courier->phone ?? 'N/A',
                $courier->email ?? 'N/A',
                $courier->deliveries_count,
                $courier->average_rating ?? 'N/A',
                $courier->is_available ? 'Oui' : 'Non',
                $courier->created_at->format('d/m/Y'),
            ]) . "\n";
        }

        return $csv;
    }

    /**
     * Rapport de revenus en PDF
     */
    public function revenueReportPDF(string $startDate, string $endDate)
    {
        $orders = Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'delivered')
            ->get();

        $dailyStats = $orders->groupBy(fn ($o) => $o->created_at->format('Y-m-d'))
            ->map(fn ($group) => [
                'count' => $group->count(),
                'revenue' => $group->sum('total_price'),
                'fees' => $group->sum('courier_earnings'),
            ]);

        $data = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'daily_stats' => $dailyStats,
            'total_orders' => $orders->count(),
            'total_revenue' => $orders->sum('total_price'),
            'total_fees' => $orders->sum('courier_earnings'),
            'generated_at' => now(),
        ];

        $pdf = Pdf::loadView('exports.revenue-report', $data);
        
        return $pdf->download("rapport_revenus_{$startDate}_{$endDate}.pdf");
    }

    // ========== Private methods ==========

    private function getOrdersQuery(array $filters)
    {
        $query = Order::with(['client', 'courier']);

        if (!empty($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('created_at', 'desc');
    }

    private function getPaymentsQuery(array $filters)
    {
        $query = Payment::with(['order']);

        if (!empty($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('created_at', 'desc');
    }

    private function getWithdrawalsQuery(array $filters)
    {
        $query = Withdrawal::with(['user']);

        if (!empty($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('created_at', 'desc');
    }

    private function translateStatus(string $status): string
    {
        return match($status) {
            'pending' => 'En attente',
            'confirmed' => 'Confirmée',
            'assigned' => 'Assignée',
            'picked_up' => 'Récupérée',
            'in_transit' => 'En transit',
            'delivered' => 'Livrée',
            'cancelled' => 'Annulée',
            default => $status,
        };
    }

    private function translatePaymentStatus(string $status): string
    {
        return match($status) {
            'pending' => 'En attente',
            'completed' => 'Complété',
            'failed' => 'Échoué',
            'refunded' => 'Remboursé',
            default => $status,
        };
    }

    private function translateWithdrawalStatus(string $status): string
    {
        return match($status) {
            'pending' => 'En attente',
            'approved' => 'Approuvé',
            'completed' => 'Complété',
            'rejected' => 'Rejeté',
            default => $status,
        };
    }
}

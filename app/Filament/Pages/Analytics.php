<?php

namespace App\Filament\Pages;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Filament\Pages\Page;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Illuminate\Support\Facades\DB;

class Analytics extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string $view = 'filament.pages.analytics';

    protected static ?string $navigationLabel = 'Analytics Avancées';

    protected static ?string $title = 'Analytics Avancées';

    protected static ?string $navigationGroup = 'Analyse';

    protected static ?int $navigationSort = 2;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'start_date' => now()->subDays(30)->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Période d\'analyse')
                    ->schema([
                        DatePicker::make('start_date')
                            ->label('Date de début')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->live(),

                        DatePicker::make('end_date')
                            ->label('Date de fin')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->after('start_date')
                            ->live(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function getKpiData(): array
    {
        $data = $this->form->getState();
        $startDate = $data['start_date'] ?? now()->subDays(30)->format('Y-m-d');
        $endDate = $data['end_date'] ?? now()->format('Y-m-d');
        
        // Période précédente pour comparaison
        $daysDiff = now()->parse($startDate)->diffInDays(now()->parse($endDate));
        $prevStartDate = now()->parse($startDate)->subDays($daysDiff)->format('Y-m-d');
        $prevEndDate = now()->parse($startDate)->subDay()->format('Y-m-d');

        // KPIs actuels
        $totalOrders = Order::whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])->count();
        $deliveredOrders = Order::whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])->where('status', 'delivered')->count();
        $revenue = Order::whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])->where('status', 'delivered')->sum('total_price');
        $newUsers = User::where('role', 'client')->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])->count();
        $activeCouriers = User::where('role', 'courier')->where('status', 'active')->count();

        // KPIs période précédente
        $prevOrders = Order::whereBetween('created_at', [$prevStartDate, $prevEndDate . ' 23:59:59'])->count();
        $prevRevenue = Order::whereBetween('created_at', [$prevStartDate, $prevEndDate . ' 23:59:59'])->where('status', 'delivered')->sum('total_price');
        $prevUsers = User::where('role', 'client')->whereBetween('created_at', [$prevStartDate, $prevEndDate . ' 23:59:59'])->count();

        return [
            'total_orders' => [
                'value' => $totalOrders,
                'change' => $prevOrders > 0 ? round((($totalOrders - $prevOrders) / $prevOrders) * 100, 1) : 0,
            ],
            'delivered_orders' => [
                'value' => $deliveredOrders,
                'rate' => $totalOrders > 0 ? round(($deliveredOrders / $totalOrders) * 100, 1) : 0,
            ],
            'revenue' => [
                'value' => $revenue,
                'change' => $prevRevenue > 0 ? round((($revenue - $prevRevenue) / $prevRevenue) * 100, 1) : 0,
            ],
            'new_users' => [
                'value' => $newUsers,
                'change' => $prevUsers > 0 ? round((($newUsers - $prevUsers) / $prevUsers) * 100, 1) : 0,
            ],
            'active_couriers' => [
                'value' => $activeCouriers,
            ],
            'avg_order_value' => [
                'value' => $totalOrders > 0 ? round($revenue / $totalOrders) : 0,
            ],
        ];
    }

    public function getOrdersByDayData(): array
    {
        $data = $this->form->getState();
        $startDate = $data['start_date'] ?? now()->subDays(30)->format('Y-m-d');
        $endDate = $data['end_date'] ?? now()->format('Y-m-d');

        $orders = Order::whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(total_price) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'labels' => $orders->pluck('date')->map(fn ($d) => \Carbon\Carbon::parse($d)->format('d/m'))->toArray(),
            'orders' => $orders->pluck('count')->toArray(),
            'revenue' => $orders->pluck('total')->toArray(),
        ];
    }

    public function getStatusDistribution(): array
    {
        $data = $this->form->getState();
        $startDate = $data['start_date'] ?? now()->subDays(30)->format('Y-m-d');
        $endDate = $data['end_date'] ?? now()->format('Y-m-d');

        $statuses = Order::whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            'labels' => ['En attente', 'Acceptée', 'Récupérée', 'En transit', 'Livrée', 'Annulée'],
            'values' => [
                $statuses['pending'] ?? 0,
                $statuses['accepted'] ?? 0,
                $statuses['picked_up'] ?? 0,
                $statuses['in_transit'] ?? 0,
                $statuses['delivered'] ?? 0,
                $statuses['cancelled'] ?? 0,
            ],
            'colors' => ['#fbbf24', '#3b82f6', '#8b5cf6', '#06b6d4', '#10b981', '#ef4444'],
        ];
    }

    public function getTopCouriers(): array
    {
        $data = $this->form->getState();
        $startDate = $data['start_date'] ?? now()->subDays(30)->format('Y-m-d');
        $endDate = $data['end_date'] ?? now()->format('Y-m-d');

        return User::where('role', 'courier')
            ->withCount(['courierOrders as delivered_count' => fn ($q) => $q->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])->where('status', 'delivered')])
            ->withSum(['courierOrders as total_earnings' => fn ($q) => $q->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])->where('status', 'delivered')], 'courier_earnings')
            ->orderBy('delivered_count', 'desc')
            ->limit(10)
            ->get()
            ->map(fn ($c) => [
                'name' => $c->name,
                'phone' => $c->phone,
                'delivered' => $c->delivered_count,
                'earnings' => $c->total_earnings ?? 0,
                'rating' => $c->average_rating,
            ])
            ->toArray();
    }

    public function getHourlyDistribution(): array
    {
        $data = $this->form->getState();
        $startDate = $data['start_date'] ?? now()->subDays(30)->format('Y-m-d');
        $endDate = $data['end_date'] ?? now()->format('Y-m-d');

        $hourly = Order::whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
            ->selectRaw('CAST(strftime("%H", created_at) AS INTEGER) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('count', 'hour')
            ->toArray();

        $hours = [];
        $counts = [];
        for ($i = 0; $i < 24; $i++) {
            $hours[] = sprintf('%02d:00', $i);
            $counts[] = $hourly[$i] ?? 0;
        }

        return [
            'labels' => $hours,
            'values' => $counts,
        ];
    }

    public function getPaymentMethods(): array
    {
        $data = $this->form->getState();
        $startDate = $data['start_date'] ?? now()->subDays(30)->format('Y-m-d');
        $endDate = $data['end_date'] ?? now()->format('Y-m-d');

        $methods = Payment::whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
            ->where('status', 'completed')
            ->selectRaw('method, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('method')
            ->get()
            ->mapWithKeys(fn ($p) => [$p->method => ['count' => $p->count, 'total' => $p->total]])
            ->toArray();

        return $methods;
    }
}

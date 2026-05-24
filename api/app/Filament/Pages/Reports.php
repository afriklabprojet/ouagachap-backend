<?php

namespace App\Filament\Pages;

use App\Exports\OrdersExport;
use App\Exports\CouriersExport;
use App\Exports\UsersExport;
use App\Exports\PaymentsExport;
use App\Models\Order;
use App\Models\User;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class Reports extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static string $view = 'filament.pages.reports';

    protected static ?string $navigationLabel = 'Rapports & Exports';

    protected static ?string $title = 'Rapports & Exports';

    protected static ?string $navigationGroup = 'Analyse';

    protected static ?int $navigationSort = 1;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'start_date' => now()->subMonth()->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
            'report_type' => 'orders',
            'export_format' => 'excel',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Paramètres du rapport')
                    ->schema([
                        Select::make('report_type')
                            ->label('Type de rapport')
                            ->options([
                                'orders' => '📦 Commandes',
                                'couriers' => '🏍️ Coursiers',
                                'clients' => '👥 Clients',
                                'payments' => '💰 Paiements',
                                'revenue' => '📈 Revenus',
                            ])
                            ->required(),

                        Select::make('export_format')
                            ->label('Format d\'export')
                            ->options([
                                'excel' => '📊 Excel (.xlsx)',
                                'csv' => '📄 CSV',
                                'pdf' => '📕 PDF',
                            ])
                            ->required(),

                        DatePicker::make('start_date')
                            ->label('Date de début')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        DatePicker::make('end_date')
                            ->label('Date de fin')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->after('start_date'),
                    ])
                    ->columns(4),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate')
                ->label('Générer le rapport')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->action('generateReport'),
        ];
    }

    public function generateReport()
    {
        $data = $this->form->getState();
        $startDate = $data['start_date'];
        $endDate = $data['end_date'];
        $reportType = $data['report_type'];
        $format = $data['export_format'];

        $filename = "rapport_{$reportType}_" . now()->format('Y-m-d_His');

        try {
            if ($format === 'pdf') {
                return $this->generatePdfReport($reportType, $startDate, $endDate, $filename);
            }

            $export = match ($reportType) {
                'orders' => new OrdersExport($startDate, $endDate),
                'couriers' => new CouriersExport($startDate, $endDate),
                'clients' => new UsersExport($startDate, $endDate, 'client'),
                'payments' => new PaymentsExport($startDate, $endDate),
                'revenue' => new PaymentsExport($startDate, $endDate),
            };

            $extension = $format === 'csv' ? 'csv' : 'xlsx';
            $writerType = $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX;

            Notification::make()
                ->title('Rapport généré avec succès')
                ->body("Le fichier {$filename}.{$extension} est en cours de téléchargement.")
                ->success()
                ->send();

            return Excel::download($export, "{$filename}.{$extension}", $writerType);
        } catch (\Exception $e) {
            Notification::make()
                ->title('Erreur lors de la génération')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function generatePdfReport(string $reportType, string $startDate, string $endDate, string $filename)
    {
        // Toutes les requêtes de rapport passent par le replica analytique
        $data = match ($reportType) {
            'orders' => [
                'title' => 'Rapport des Commandes',
                'items' => Order::on('mysql_analytics')->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
                    ->with(['client', 'courier'])
                    ->orderBy('created_at', 'desc')
                    ->get(),
                'stats' => [
                    'total'     => Order::on('mysql_analytics')->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])->count(),
                    'delivered' => Order::on('mysql_analytics')->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])->where('status', 'delivered')->count(),
                    'revenue'   => Order::on('mysql_analytics')->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])->where('status', 'delivered')->sum('total_price'),
                ],
            ],
            'couriers' => [
                'title' => 'Rapport des Coursiers',
                'items' => User::on('mysql_analytics')->where('role', 'courier')
                    ->withCount(['courierOrders' => fn ($q) => $q->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])])
                    ->orderBy('courier_orders_count', 'desc')
                    ->get(),
                'stats' => [
                    'total'     => User::on('mysql_analytics')->where('role', 'courier')->count(),
                    'active'    => User::on('mysql_analytics')->where('role', 'courier')->where('status', 'active')->count(),
                    'available' => User::on('mysql_analytics')->where('role', 'courier')->where('is_available', true)->count(),
                ],
            ],
            'clients' => [
                'title' => 'Rapport des Clients',
                'items' => User::on('mysql_analytics')->where('role', 'client')
                    ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
                    ->withCount('clientOrders')
                    ->orderBy('created_at', 'desc')
                    ->get(),
                'stats' => [
                    'total'  => User::on('mysql_analytics')->where('role', 'client')->count(),
                    'new'    => User::on('mysql_analytics')->where('role', 'client')->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])->count(),
                    'active' => User::on('mysql_analytics')->where('role', 'client')->where('status', 'active')->count(),
                ],
            ],
            'payments' => [
                'title' => 'Rapport des Paiements',
                'items' => Payment::on('mysql_analytics')->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
                    ->with(['user', 'order'])
                    ->orderBy('created_at', 'desc')
                    ->get(),
                'stats' => [
                    'total'     => Payment::on('mysql_analytics')->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])->count(),
                    'completed' => Payment::on('mysql_analytics')->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])->where('status', 'success')->count(),
                    'amount'    => Payment::on('mysql_analytics')->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])->where('status', 'success')->sum('amount'),
                ],
            ],
            'revenue' => [
                'title' => 'Rapport des Revenus',
                'items' => Payment::on('mysql_analytics')->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
                    ->where('status', 'success')
                    ->selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(amount) as total')
                    ->groupBy('date')
                    ->orderBy('date', 'desc')
                    ->get(),
                'stats' => [
                    'total_revenue' => Payment::on('mysql_analytics')->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])->where('status', 'success')->sum('amount'),
                    'transactions'  => Payment::on('mysql_analytics')->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])->where('status', 'success')->count(),
                    'avg_per_day'   => Payment::on('mysql_analytics')->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])->where('status', 'success')->avg('amount'),
                ],
            ],
        };

        $data['start_date'] = $startDate;
        $data['end_date'] = $endDate;
        $data['report_type'] = $reportType;
        $data['generated_at'] = now()->format('d/m/Y H:i');

        $pdf = Pdf::loadView('exports.report-pdf', $data);

        Notification::make()
            ->title('Rapport PDF généré')
            ->body("Le fichier {$filename}.pdf est en cours de téléchargement.")
            ->success()
            ->send();

        return response()->streamDownload(
            fn () => print($pdf->output()),
            "{$filename}.pdf"
        );
    }

    public function getOrdersStats(): array
    {
        $data = $this->form->getState();
        $startDate = $data['start_date'] ?? now()->subMonth()->format('Y-m-d');
        $endDate = $data['end_date'] ?? now()->format('Y-m-d');

        return [
            'total'     => Order::on('mysql_analytics')->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])->count(),
            'delivered' => Order::on('mysql_analytics')->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])->where('status', 'delivered')->count(),
            'cancelled' => Order::on('mysql_analytics')->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])->where('status', 'cancelled')->count(),
            'revenue'   => Order::on('mysql_analytics')->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])->where('status', 'delivered')->sum('total_price'),
        ];
    }
}

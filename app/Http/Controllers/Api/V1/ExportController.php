<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ExportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * @group Exports
 *
 * APIs pour exporter les données (CSV, PDF)
 * Réservé aux administrateurs
 */
class ExportController extends Controller
{
    public function __construct(
        private ExportService $exportService
    ) {}

    /**
     * Export des commandes en CSV
     *
     * @authenticated
     * @queryParam start_date string Date de début (YYYY-MM-DD). Example: 2026-01-01
     * @queryParam end_date string Date de fin (YYYY-MM-DD). Example: 2026-01-31
     * @queryParam status string Filtrer par statut. Example: delivered
     */
    public function ordersCSV(Request $request): Response
    {
        $filters = $request->only(['start_date', 'end_date', 'status']);
        $csv = $this->exportService->ordersToCSV($filters);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="commandes_' . now()->format('Y-m-d') . '.csv"',
        ]);
    }

    /**
     * Export des commandes en PDF
     *
     * @authenticated
     * @queryParam start_date string Date de début (YYYY-MM-DD). Example: 2026-01-01
     * @queryParam end_date string Date de fin (YYYY-MM-DD). Example: 2026-01-31
     * @queryParam status string Filtrer par statut. Example: delivered
     */
    public function ordersPDF(Request $request)
    {
        $filters = $request->only(['start_date', 'end_date', 'status']);
        return $this->exportService->ordersToPDF($filters);
    }

    /**
     * Export des paiements en CSV
     *
     * @authenticated
     * @queryParam start_date string Date de début (YYYY-MM-DD). Example: 2026-01-01
     * @queryParam end_date string Date de fin (YYYY-MM-DD). Example: 2026-01-31
     * @queryParam status string Filtrer par statut. Example: completed
     */
    public function paymentsCSV(Request $request): Response
    {
        $filters = $request->only(['start_date', 'end_date', 'status']);
        $csv = $this->exportService->paymentsToCSV($filters);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="paiements_' . now()->format('Y-m-d') . '.csv"',
        ]);
    }

    /**
     * Export des retraits en CSV
     *
     * @authenticated
     * @queryParam start_date string Date de début (YYYY-MM-DD). Example: 2026-01-01
     * @queryParam end_date string Date de fin (YYYY-MM-DD). Example: 2026-01-31
     * @queryParam status string Filtrer par statut. Example: pending
     */
    public function withdrawalsCSV(Request $request): Response
    {
        $filters = $request->only(['start_date', 'end_date', 'status']);
        $csv = $this->exportService->withdrawalsToCSV($filters);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="retraits_' . now()->format('Y-m-d') . '.csv"',
        ]);
    }

    /**
     * Export des coursiers en CSV
     *
     * @authenticated
     */
    public function couriersCSV(): Response
    {
        $csv = $this->exportService->couriersToCSV();

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="coursiers_' . now()->format('Y-m-d') . '.csv"',
        ]);
    }

    /**
     * Rapport de revenus en PDF
     *
     * @authenticated
     * @queryParam start_date string required Date de début (YYYY-MM-DD). Example: 2026-01-01
     * @queryParam end_date string required Date de fin (YYYY-MM-DD). Example: 2026-01-31
     */
    public function revenueReportPDF(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        return $this->exportService->revenueReportPDF(
            $request->start_date,
            $request->end_date
        );
    }
}

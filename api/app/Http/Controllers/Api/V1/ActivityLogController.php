<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    /**
     * Lister les logs d'activité (Admin seulement)
     */
    public function index(Request $request): JsonResponse
    {
        $query = ActivityLog::with('user:id,name,phone,role')
            ->orderByDesc('created_at');

        // Filtres
        if ($request->has('log_type')) {
            $query->where('log_type', $request->log_type);
        }

        if ($request->has('subject_type')) {
            $query->where('subject_type', 'like', '%' . $request->subject_type . '%');
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        $logs = $query->paginate($request->get('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    /**
     * Voir un log spécifique
     */
    public function show(ActivityLog $log): JsonResponse
    {
        $log->load('user:id,name,phone,role');

        return response()->json([
            'success' => true,
            'data' => $log,
        ]);
    }

    /**
     * Statistiques des logs
     */
    public function stats(Request $request): JsonResponse
    {
        $days = $request->get('days', 30);
        $startDate = now()->subDays($days);

        $stats = [
            'total_logs' => ActivityLog::where('created_at', '>=', $startDate)->count(),
            'by_type' => ActivityLog::where('created_at', '>=', $startDate)
                ->selectRaw('log_type, COUNT(*) as count')
                ->groupBy('log_type')
                ->pluck('count', 'log_type'),
            'by_subject' => ActivityLog::where('created_at', '>=', $startDate)
                ->selectRaw('subject_type, COUNT(*) as count')
                ->groupBy('subject_type')
                ->get()
                ->mapWithKeys(function ($item) {
                    $shortName = class_basename($item->subject_type);
                    return [$shortName => $item->count];
                }),
            'by_day' => ActivityLog::where('created_at', '>=', $startDate)
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->pluck('count', 'date'),
            'top_users' => ActivityLog::where('created_at', '>=', $startDate)
                ->whereNotNull('user_id')
                ->selectRaw('user_id, COUNT(*) as count')
                ->groupBy('user_id')
                ->orderByDesc('count')
                ->limit(10)
                ->with('user:id,name,phone')
                ->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Logs d'un sujet spécifique (ex: toutes les actions sur une commande)
     */
    public function forSubject(Request $request): JsonResponse
    {
        $request->validate([
            'subject_type' => 'required|string',
            'subject_id' => 'required',
        ]);

        $logs = ActivityLog::where('subject_type', 'like', '%' . $request->subject_type . '%')
            ->where('subject_id', $request->subject_id)
            ->with('user:id,name,phone')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    /**
     * Logs de l'utilisateur connecté (pour voir son propre historique)
     */
    public function myActivity(Request $request): JsonResponse
    {
        $logs = ActivityLog::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    /**
     * Exporter les logs (CSV)
     */
    public function export(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
        ]);

        $logs = ActivityLog::whereBetween('created_at', [$request->date_from, $request->date_to])
            ->with('user:id,name,phone')
            ->orderBy('created_at')
            ->get();

        $filename = "activity_logs_{$request->date_from}_{$request->date_to}.csv";

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($logs) {
            $handle = fopen('php://output', 'w');
            
            // En-têtes CSV
            fputcsv($handle, [
                'ID', 'Date', 'Utilisateur', 'Type', 'Sujet', 'Description', 'IP'
            ]);

            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->id,
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->user?->name ?? 'Système',
                    $log->log_type,
                    class_basename($log->subject_type) . ' #' . $log->subject_id,
                    $log->description,
                    $log->ip_address,
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}

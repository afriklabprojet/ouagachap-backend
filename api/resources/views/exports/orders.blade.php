<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport des Commandes - OUAGA CHAP</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #f97316;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #f97316;
            margin: 0;
            font-size: 24px;
        }
        .header p {
            color: #666;
            margin: 5px 0 0;
        }
        .summary {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .summary-grid {
            display: table;
            width: 100%;
        }
        .summary-item {
            display: table-cell;
            text-align: center;
            padding: 10px;
        }
        .summary-item .value {
            font-size: 20px;
            font-weight: bold;
            color: #f97316;
        }
        .summary-item .label {
            font-size: 11px;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            font-size: 10px;
        }
        th {
            background-color: #f97316;
            color: white;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .status-delivered {
            color: #10b981;
            font-weight: bold;
        }
        .status-cancelled {
            color: #ef4444;
        }
        .status-pending {
            color: #f59e0b;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .text-right {
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🛵 OUAGA CHAP</h1>
        <p>Rapport des Commandes</p>
        <p>Généré le {{ $generated_at->format('d/m/Y à H:i') }}</p>
    </div>

    <div class="summary">
        <div class="summary-grid">
            <div class="summary-item">
                <div class="value">{{ $orders->count() }}</div>
                <div class="label">Commandes</div>
            </div>
            <div class="summary-item">
                <div class="value">{{ number_format($total_revenue, 0, ',', ' ') }} FCFA</div>
                <div class="label">Revenus Totaux</div>
            </div>
            <div class="summary-item">
                <div class="value">{{ number_format($total_fees, 0, ',', ' ') }} FCFA</div>
                <div class="label">Frais de Livraison</div>
            </div>
        </div>
    </div>

    @if(!empty($filters))
    <p><strong>Filtres appliqués :</strong>
        @if(!empty($filters['start_date'])) Du {{ $filters['start_date'] }} @endif
        @if(!empty($filters['end_date'])) au {{ $filters['end_date'] }} @endif
        @if(!empty($filters['status'])) - Statut: {{ $filters['status'] }} @endif
    </p>
    @endif

    <table>
        <thead>
            <tr>
                <th>N° Commande</th>
                <th>Date</th>
                <th>Client</th>
                <th>Coursier</th>
                <th>Statut</th>
                <th class="text-right">Montant</th>
                <th class="text-right">Frais</th>
            </tr>
        </thead>
        <tbody>
            @foreach($orders as $order)
            <tr>
                <td>{{ $order->order_number }}</td>
                <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                <td>{{ $order->client->name ?? 'N/A' }}</td>
                <td>{{ $order->courier->name ?? 'Non assigné' }}</td>
                <td class="status-{{ $order->status->value ?? $order->status }}">
                    @php
                        $statusLabels = [
                            'pending' => 'En attente',
                            'confirmed' => 'Confirmée',
                            'assigned' => 'Assignée',
                            'picked_up' => 'Récupérée',
                            'in_transit' => 'En transit',
                            'delivered' => 'Livrée',
                            'cancelled' => 'Annulée',
                        ];
                        $status = $order->status->value ?? $order->status;
                    @endphp
                    {{ $statusLabels[$status] ?? $status }}
                </td>
                <td class="text-right">{{ number_format($order->total_price, 0, ',', ' ') }}</td>
                <td class="text-right">{{ number_format($order->courier_earnings, 0, ',', ' ') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>OUAGA CHAP - Service de livraison rapide à Ouagadougou</p>
        <p>Ce document est généré automatiquement</p>
    </div>
</body>
</html>

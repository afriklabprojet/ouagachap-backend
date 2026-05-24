<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport de Revenus - OUAGA CHAP</title>
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
        .period {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 20px;
        }
        .summary {
            background: linear-gradient(135deg, #f97316, #fb923c);
            color: white;
            padding: 20px;
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
            font-size: 24px;
            font-weight: bold;
        }
        .summary-item .label {
            font-size: 11px;
            opacity: 0.9;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f97316;
            color: white;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .text-right {
            text-align: right;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .highlight {
            background-color: #fef3c7;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🛵 OUAGA CHAP</h1>
        <p>Rapport de Revenus</p>
    </div>

    <div class="period">
        <strong>Période :</strong> Du {{ $start_date }} au {{ $end_date }}
        <br>
        <small>Généré le {{ $generated_at->format('d/m/Y à H:i') }}</small>
    </div>

    <div class="summary">
        <div class="summary-grid">
            <div class="summary-item">
                <div class="value">{{ $total_orders }}</div>
                <div class="label">Commandes Livrées</div>
            </div>
            <div class="summary-item">
                <div class="value">{{ number_format($total_revenue, 0, ',', ' ') }}</div>
                <div class="label">Revenus Totaux (FCFA)</div>
            </div>
            <div class="summary-item">
                <div class="value">{{ number_format($total_fees, 0, ',', ' ') }}</div>
                <div class="label">Frais de Livraison (FCFA)</div>
            </div>
        </div>
    </div>

    <h3>Détail par jour</h3>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th class="text-right">Commandes</th>
                <th class="text-right">Revenus (FCFA)</th>
                <th class="text-right">Frais Livraison (FCFA)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($daily_stats->sortKeys() as $date => $stats)
            <tr>
                <td>{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</td>
                <td class="text-right">{{ $stats['count'] }}</td>
                <td class="text-right">{{ number_format($stats['revenue'], 0, ',', ' ') }}</td>
                <td class="text-right">{{ number_format($stats['fees'], 0, ',', ' ') }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="highlight">
                <td><strong>TOTAL</strong></td>
                <td class="text-right"><strong>{{ $total_orders }}</strong></td>
                <td class="text-right"><strong>{{ number_format($total_revenue, 0, ',', ' ') }}</strong></td>
                <td class="text-right"><strong>{{ number_format($total_fees, 0, ',', ' ') }}</strong></td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <p>OUAGA CHAP - Service de livraison rapide à Ouagadougou</p>
        <p>Ce document est généré automatiquement et fait office de rapport financier</p>
    </div>
</body>
</html>

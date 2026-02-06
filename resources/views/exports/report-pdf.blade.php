<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} - OUAGA CHAP</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            color: #333;
            line-height: 1.4;
        }
        
        .header {
            background: linear-gradient(135deg, #E85D04, #059669);
            color: white;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .header h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .header p {
            font-size: 12px;
            opacity: 0.9;
        }
        
        .meta-info {
            display: flex;
            justify-content: space-between;
            background: #f8f9fa;
            padding: 10px 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .meta-info span {
            font-size: 10px;
        }
        
        .stats-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        
        .stat-box {
            display: table-cell;
            width: 25%;
            padding: 10px;
            text-align: center;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
        }
        
        .stat-box h3 {
            font-size: 18px;
            color: #E85D04;
            margin-bottom: 5px;
        }
        
        .stat-box p {
            font-size: 9px;
            color: #666;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        table th {
            background: #E85D04;
            color: white;
            padding: 8px 5px;
            text-align: left;
            font-size: 9px;
            font-weight: bold;
        }
        
        table td {
            padding: 6px 5px;
            border-bottom: 1px solid #e9ecef;
            font-size: 9px;
        }
        
        table tr:nth-child(even) {
            background: #f8f9fa;
        }
        
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #333;
            color: white;
            padding: 10px;
            text-align: center;
            font-size: 8px;
        }
        
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 8px;
            font-weight: bold;
        }
        
        .badge-success { background: #d4edda; color: #155724; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
        
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🚀 OUAGA CHAP</h1>
        <p>{{ $title }}</p>
    </div>

    <div class="meta-info">
        <span><strong>Période:</strong> {{ \Carbon\Carbon::parse($start_date)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($end_date)->format('d/m/Y') }}</span>
        <span><strong>Généré le:</strong> {{ $generated_at }}</span>
    </div>

    @if(isset($stats))
    <div class="stats-grid">
        @foreach($stats as $label => $value)
        <div class="stat-box">
            <h3>
                @if(str_contains($label, 'revenue') || str_contains($label, 'amount'))
                    {{ number_format($value, 0, ',', ' ') }} F
                @else
                    {{ number_format($value) }}
                @endif
            </h3>
            <p>{{ ucfirst(str_replace('_', ' ', $label)) }}</p>
        </div>
        @endforeach
    </div>
    @endif

    @if($report_type === 'orders')
    <table>
        <thead>
            <tr>
                <th>Code</th>
                <th>Client</th>
                <th>Coursier</th>
                <th>Adresse livraison</th>
                <th>Prix</th>
                <th>Statut</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $order)
            <tr>
                <td>{{ $order->tracking_code }}</td>
                <td>{{ $order->client?->name ?? 'N/A' }}</td>
                <td>{{ $order->courier?->name ?? 'Non assigné' }}</td>
                <td>{{ \Str::limit($order->delivery_address, 30) }}</td>
                <td>{{ number_format($order->total_price, 0, ',', ' ') }} F</td>
                <td>
                    <span class="badge 
                        @if($order->status->value === 'delivered') badge-success
                        @elseif($order->status->value === 'cancelled') badge-danger
                        @elseif(in_array($order->status->value, ['pending', 'accepted'])) badge-warning
                        @else badge-info
                        @endif">
                        {{ $order->status->value }}
                    </span>
                </td>
                <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align: center; padding: 20px;">Aucune commande trouvée</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @elseif($report_type === 'couriers')
    <table>
        <thead>
            <tr>
                <th>Nom</th>
                <th>Téléphone</th>
                <th>Véhicule</th>
                <th>Statut</th>
                <th>Note</th>
                <th>Commandes</th>
                <th>Solde</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $courier)
            <tr>
                <td>{{ $courier->name }}</td>
                <td>{{ $courier->phone }}</td>
                <td>{{ $courier->vehicle_type ?? 'N/A' }}</td>
                <td>
                    <span class="badge {{ $courier->status->value === 'active' ? 'badge-success' : 'badge-warning' }}">
                        {{ $courier->status->value }}
                    </span>
                </td>
                <td>{{ number_format($courier->average_rating, 1) }}/5</td>
                <td>{{ $courier->courier_orders_count ?? 0 }}</td>
                <td>{{ number_format($courier->wallet_balance, 0, ',', ' ') }} F</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align: center; padding: 20px;">Aucun coursier trouvé</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @elseif($report_type === 'clients')
    <table>
        <thead>
            <tr>
                <th>Nom</th>
                <th>Téléphone</th>
                <th>Email</th>
                <th>Statut</th>
                <th>Commandes</th>
                <th>Inscription</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $client)
            <tr>
                <td>{{ $client->name }}</td>
                <td>{{ $client->phone }}</td>
                <td>{{ $client->email ?? 'N/A' }}</td>
                <td>
                    <span class="badge {{ $client->status->value === 'active' ? 'badge-success' : 'badge-warning' }}">
                        {{ $client->status->value }}
                    </span>
                </td>
                <td>{{ $client->client_orders_count ?? 0 }}</td>
                <td>{{ $client->created_at->format('d/m/Y') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align: center; padding: 20px;">Aucun client trouvé</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @elseif($report_type === 'payments')
    <table>
        <thead>
            <tr>
                <th>Référence</th>
                <th>Utilisateur</th>
                <th>Commande</th>
                <th>Montant</th>
                <th>Méthode</th>
                <th>Statut</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $payment)
            <tr>
                <td>{{ $payment->reference ?? 'N/A' }}</td>
                <td>{{ $payment->user?->name ?? 'N/A' }}</td>
                <td>{{ $payment->order?->tracking_code ?? 'N/A' }}</td>
                <td>{{ number_format($payment->amount, 0, ',', ' ') }} F</td>
                <td>{{ $payment->method ?? 'N/A' }}</td>
                <td>
                    <span class="badge 
                        @if($payment->status->value === 'completed') badge-success
                        @elseif($payment->status->value === 'failed') badge-danger
                        @else badge-warning
                        @endif">
                        {{ $payment->status->value }}
                    </span>
                </td>
                <td>{{ $payment->created_at->format('d/m/Y H:i') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align: center; padding: 20px;">Aucun paiement trouvé</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @elseif($report_type === 'revenue')
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Nombre de transactions</th>
                <th>Total (FCFA)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $item)
            <tr>
                <td>{{ \Carbon\Carbon::parse($item->date)->format('d/m/Y') }}</td>
                <td>{{ $item->count }}</td>
                <td>{{ number_format($item->total, 0, ',', ' ') }} F</td>
            </tr>
            @empty
            <tr>
                <td colspan="3" style="text-align: center; padding: 20px;">Aucune donnée trouvée</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    @endif

    <div class="footer">
        <p>OUAGA CHAP - Service de livraison rapide à Ouagadougou | Rapport généré automatiquement | {{ $generated_at }}</p>
    </div>
</body>
</html>

<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            📅 Période d'analyse
        </x-slot>

        {{ $this->form }}
    </x-filament::section>

    @php
        $kpi = $this->getKpiData();
    @endphp

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mt-6">
        <x-filament::section>
            <div class="text-center">
                <p class="text-3xl font-bold text-primary-600">{{ number_format($kpi['total_orders']['value']) }}</p>
                <p class="text-sm text-gray-500 mt-1">Total commandes</p>
                @if($kpi['total_orders']['change'] != 0)
                    <p class="text-xs mt-1 {{ $kpi['total_orders']['change'] > 0 ? 'text-success-600' : 'text-danger-600' }}">
                        {{ $kpi['total_orders']['change'] > 0 ? '↑' : '↓' }} {{ abs($kpi['total_orders']['change']) }}%
                    </p>
                @endif
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-center">
                <p class="text-3xl font-bold text-success-600">{{ $kpi['delivered_orders']['rate'] }}%</p>
                <p class="text-sm text-gray-500 mt-1">Taux livraison</p>
                <p class="text-xs text-gray-400 mt-1">{{ $kpi['delivered_orders']['value'] }} livrées</p>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-center">
                <p class="text-3xl font-bold text-warning-600">{{ number_format($kpi['revenue']['value'], 0, ',', ' ') }}</p>
                <p class="text-sm text-gray-500 mt-1">Revenus (FCFA)</p>
                @if($kpi['revenue']['change'] != 0)
                    <p class="text-xs mt-1 {{ $kpi['revenue']['change'] > 0 ? 'text-success-600' : 'text-danger-600' }}">
                        {{ $kpi['revenue']['change'] > 0 ? '↑' : '↓' }} {{ abs($kpi['revenue']['change']) }}%
                    </p>
                @endif
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-center">
                <p class="text-3xl font-bold text-info-600">{{ number_format($kpi['new_users']['value']) }}</p>
                <p class="text-sm text-gray-500 mt-1">Nouveaux clients</p>
                @if($kpi['new_users']['change'] != 0)
                    <p class="text-xs mt-1 {{ $kpi['new_users']['change'] > 0 ? 'text-success-600' : 'text-danger-600' }}">
                        {{ $kpi['new_users']['change'] > 0 ? '↑' : '↓' }} {{ abs($kpi['new_users']['change']) }}%
                    </p>
                @endif
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-center">
                <p class="text-3xl font-bold text-purple-600">{{ number_format($kpi['active_couriers']['value']) }}</p>
                <p class="text-sm text-gray-500 mt-1">Coursiers actifs</p>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-center">
                <p class="text-3xl font-bold text-gray-700">{{ number_format($kpi['avg_order_value']['value'], 0, ',', ' ') }}</p>
                <p class="text-sm text-gray-500 mt-1">Panier moyen (F)</p>
            </div>
        </x-filament::section>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        <!-- Graphique évolution commandes -->
        <x-filament::section>
            <x-slot name="heading">
                📈 Évolution des commandes
            </x-slot>

            @php
                $chartData = $this->getOrdersByDayData();
            @endphp

            <div wire:ignore>
                <canvas id="ordersChart" height="200"></canvas>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const ctx = document.getElementById('ordersChart').getContext('2d');
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: @json($chartData['labels']),
                            datasets: [{
                                label: 'Commandes',
                                data: @json($chartData['orders']),
                                borderColor: '#E85D04',
                                backgroundColor: 'rgba(232, 93, 4, 0.1)',
                                fill: true,
                                tension: 0.4
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: { display: false }
                            },
                            scales: {
                                y: { beginAtZero: true }
                            }
                        }
                    });
                });
            </script>
        </x-filament::section>

        <!-- Distribution par statut -->
        <x-filament::section>
            <x-slot name="heading">
                🥧 Distribution par statut
            </x-slot>

            @php
                $statusData = $this->getStatusDistribution();
            @endphp

            <div wire:ignore>
                <canvas id="statusChart" height="200"></canvas>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const ctx2 = document.getElementById('statusChart').getContext('2d');
                    new Chart(ctx2, {
                        type: 'doughnut',
                        data: {
                            labels: @json($statusData['labels']),
                            datasets: [{
                                data: @json($statusData['values']),
                                backgroundColor: @json($statusData['colors']),
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: { position: 'bottom' }
                            }
                        }
                    });
                });
            </script>
        </x-filament::section>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        <!-- Distribution horaire -->
        <x-filament::section>
            <x-slot name="heading">
                🕐 Distribution horaire des commandes
            </x-slot>

            @php
                $hourlyData = $this->getHourlyDistribution();
            @endphp

            <div wire:ignore>
                <canvas id="hourlyChart" height="200"></canvas>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const ctx3 = document.getElementById('hourlyChart').getContext('2d');
                    new Chart(ctx3, {
                        type: 'bar',
                        data: {
                            labels: @json($hourlyData['labels']),
                            datasets: [{
                                label: 'Commandes',
                                data: @json($hourlyData['values']),
                                backgroundColor: '#059669',
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: { display: false }
                            },
                            scales: {
                                y: { beginAtZero: true }
                            }
                        }
                    });
                });
            </script>
        </x-filament::section>

        <!-- Top coursiers -->
        <x-filament::section>
            <x-slot name="heading">
                🏆 Top 10 Coursiers
            </x-slot>

            @php
                $topCouriers = $this->getTopCouriers();
            @endphp

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b dark:border-gray-700">
                            <th class="text-left py-2 px-2">#</th>
                            <th class="text-left py-2 px-2">Coursier</th>
                            <th class="text-center py-2 px-2">Livrées</th>
                            <th class="text-right py-2 px-2">Gains</th>
                            <th class="text-center py-2 px-2">Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topCouriers as $index => $courier)
                            <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="py-2 px-2">
                                    @if($index === 0)
                                        🥇
                                    @elseif($index === 1)
                                        🥈
                                    @elseif($index === 2)
                                        🥉
                                    @else
                                        {{ $index + 1 }}
                                    @endif
                                </td>
                                <td class="py-2 px-2">
                                    <div class="font-medium">{{ $courier['name'] }}</div>
                                    <div class="text-xs text-gray-500">{{ $courier['phone'] }}</div>
                                </td>
                                <td class="text-center py-2 px-2">
                                    <span class="px-2 py-1 bg-success-100 text-success-700 rounded-full text-xs font-medium">
                                        {{ $courier['delivered'] }}
                                    </span>
                                </td>
                                <td class="text-right py-2 px-2 font-medium">
                                    {{ number_format($courier['earnings'], 0, ',', ' ') }} F
                                </td>
                                <td class="text-center py-2 px-2">
                                    ⭐ {{ number_format($courier['rating'], 1) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-gray-500">Aucune donnée disponible</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>

    <!-- Méthodes de paiement -->
    <x-filament::section class="mt-6">
        <x-slot name="heading">
            💳 Répartition des paiements
        </x-slot>

        @php
            $paymentMethods = $this->getPaymentMethods();
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            @foreach($paymentMethods as $method => $data)
                <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg text-center">
                    <div class="text-2xl mb-2">
                        @if($method === 'orange_money')
                            🟠 Orange Money
                        @elseif($method === 'moov_money')
                            🔵 Moov Money
                        @elseif($method === 'cash')
                            💵 Espèces
                        @elseif($method === 'wallet')
                            👛 Portefeuille
                        @else
                            💳 {{ $method }}
                        @endif
                    </div>
                    <p class="text-xl font-bold">{{ $data['count'] }} transactions</p>
                    <p class="text-sm text-gray-500">{{ number_format($data['total'], 0, ',', ' ') }} FCFA</p>
                </div>
            @endforeach

            @if(empty($paymentMethods))
                <div class="col-span-4 text-center py-8 text-gray-500">
                    Aucune donnée de paiement disponible pour cette période
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-panels::page>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush

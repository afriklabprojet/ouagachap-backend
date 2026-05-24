<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Formulaire période --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-calendar-days" class="w-5 h-5 text-primary-500" />
                    <span>Période d'analyse</span>
                </div>
            </x-slot>
            {{ $this->form }}
        </x-filament::section>

        {{-- KPI Cards --}}
        @php $kpi = $this->getKpiData(); @endphp
        <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">

            <div class="bg-white dark:bg-gray-900 rounded-xl p-4 border border-gray-200 dark:border-gray-700 shadow-sm">
                <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Commandes</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($kpi['total_orders']['value']) }}</div>
                @if($kpi['total_orders']['change'] != 0)
                    <div class="text-xs mt-1 {{ $kpi['total_orders']['change'] >= 0 ? 'text-success-600' : 'text-danger-600' }}">
                        {{ $kpi['total_orders']['change'] >= 0 ? '↑' : '↓' }} {{ abs($kpi['total_orders']['change']) }}% vs période préc.
                    </div>
                @endif
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-xl p-4 border border-gray-200 dark:border-gray-700 shadow-sm">
                <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Livrées</div>
                <div class="text-2xl font-bold text-success-600">{{ number_format($kpi['delivered_orders']['value']) }}</div>
                <div class="text-xs mt-1 text-gray-500 dark:text-gray-400">Taux : {{ $kpi['delivered_orders']['rate'] }}%</div>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-xl p-4 border border-gray-200 dark:border-gray-700 shadow-sm">
                <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Revenus</div>
                <div class="text-2xl font-bold text-primary-600">{{ number_format($kpi['revenue']['value'], 0, ',', ' ') }} FCFA</div>
                @if($kpi['revenue']['change'] != 0)
                    <div class="text-xs mt-1 {{ $kpi['revenue']['change'] >= 0 ? 'text-success-600' : 'text-danger-600' }}">
                        {{ $kpi['revenue']['change'] >= 0 ? '↑' : '↓' }} {{ abs($kpi['revenue']['change']) }}% vs période préc.
                    </div>
                @endif
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-xl p-4 border border-gray-200 dark:border-gray-700 shadow-sm">
                <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Nouveaux clients</div>
                <div class="text-2xl font-bold text-info-600">{{ number_format($kpi['new_users']['value']) }}</div>
                @if($kpi['new_users']['change'] != 0)
                    <div class="text-xs mt-1 {{ $kpi['new_users']['change'] >= 0 ? 'text-success-600' : 'text-danger-600' }}">
                        {{ $kpi['new_users']['change'] >= 0 ? '↑' : '↓' }} {{ abs($kpi['new_users']['change']) }}% vs période préc.
                    </div>
                @endif
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-xl p-4 border border-gray-200 dark:border-gray-700 shadow-sm">
                <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Coursiers actifs</div>
                <div class="text-2xl font-bold text-warning-600">{{ number_format($kpi['active_couriers']['value']) }}</div>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-xl p-4 border border-gray-200 dark:border-gray-700 shadow-sm">
                <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Panier moyen</div>
                <div class="text-2xl font-bold text-gray-700 dark:text-gray-300">{{ number_format($kpi['avg_order_value']['value'], 0, ',', ' ') }}</div>
                <div class="text-xs mt-1 text-gray-400">FCFA</div>
            </div>

        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            {{-- Répartition par statut --}}
            @php $statusData = $this->getStatusDistribution(); @endphp
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-chart-pie" class="w-5 h-5 text-primary-500" />
                        <span>Répartition par statut</span>
                    </div>
                </x-slot>

                <div class="space-y-3">
                    @php $statusTotal = array_sum($statusData['values']) ?: 1; @endphp
                    @foreach($statusData['labels'] as $i => $label)
                        @php
                            $count = $statusData['values'][$i];
                            $color = $statusData['colors'][$i];
                            $pct = round(($count / $statusTotal) * 100, 1);
                        @endphp
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-700 dark:text-gray-300">{{ $label }}</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ number_format($count) }} <span class="text-gray-400 text-xs">({{ $pct }}%)</span></span>
                            </div>
                            <div class="h-2 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-300" style="width: {{ $pct }}%; background-color: {{ $color }};"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>

            {{-- Méthodes de paiement --}}
            @php $payments = $this->getPaymentMethods(); @endphp
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-credit-card" class="w-5 h-5 text-success-500" />
                        <span>Méthodes de paiement</span>
                    </div>
                </x-slot>

                @if(count($payments) > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs text-gray-500 dark:text-gray-400 uppercase border-b border-gray-200 dark:border-gray-700">
                                    <th class="pb-2">Méthode</th>
                                    <th class="pb-2 text-right">Transactions</th>
                                    <th class="pb-2 text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @foreach($payments as $method => $data)
                                    <tr>
                                        <td class="py-2 font-medium text-gray-900 dark:text-gray-100 capitalize">{{ $method }}</td>
                                        <td class="py-2 text-right text-gray-700 dark:text-gray-300">{{ number_format($data['count']) }}</td>
                                        <td class="py-2 text-right text-primary-600 font-medium">{{ number_format($data['total'], 0, ',', ' ') }} FCFA</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-6">Aucune transaction sur cette période</p>
                @endif
            </x-filament::section>

        </div>

        {{-- Top Coursiers --}}
        @php $topCouriers = $this->getTopCouriers(); @endphp
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-trophy" class="w-5 h-5 text-warning-500" />
                    <span>Top 10 Coursiers</span>
                </div>
            </x-slot>

            @if(count($topCouriers) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-gray-500 dark:text-gray-400 uppercase border-b border-gray-200 dark:border-gray-700">
                                <th class="pb-2 pr-4">#</th>
                                <th class="pb-2 pr-4">Nom</th>
                                <th class="pb-2 pr-4">Téléphone</th>
                                <th class="pb-2 text-right pr-4">Livraisons</th>
                                <th class="pb-2 text-right pr-4">Gains</th>
                                <th class="pb-2 text-right">Note</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($topCouriers as $i => $courier)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                    <td class="py-2 pr-4">
                                        @if($i === 0)
                                            <span class="text-warning-500 font-bold">🥇</span>
                                        @elseif($i === 1)
                                            <span class="text-gray-400 font-bold">🥈</span>
                                        @elseif($i === 2)
                                            <span class="text-amber-600 font-bold">🥉</span>
                                        @else
                                            <span class="text-gray-400">{{ $i + 1 }}</span>
                                        @endif
                                    </td>
                                    <td class="py-2 pr-4 font-medium text-gray-900 dark:text-gray-100">{{ $courier['name'] }}</td>
                                    <td class="py-2 pr-4 text-gray-500 dark:text-gray-400">{{ $courier['phone'] }}</td>
                                    <td class="py-2 pr-4 text-right font-semibold text-success-600">{{ number_format($courier['delivered']) }}</td>
                                    <td class="py-2 pr-4 text-right font-semibold text-primary-600">{{ number_format($courier['earnings'], 0, ',', ' ') }} FCFA</td>
                                    <td class="py-2 text-right">
                                        @if($courier['rating'])
                                            <span class="inline-flex items-center gap-1 text-warning-500">
                                                ⭐ {{ number_format($courier['rating'], 1) }}
                                            </span>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-6">Aucune donnée sur cette période</p>
            @endif
        </x-filament::section>

        {{-- Répartition horaire --}}
        @php $hourly = $this->getHourlyDistribution(); @endphp
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-clock" class="w-5 h-5 text-info-500" />
                    <span>Activité par heure</span>
                </div>
            </x-slot>

            <div class="space-y-1">
                @php $maxHourly = max($hourly['values']) ?: 1; @endphp
                @foreach($hourly['labels'] as $i => $hour)
                    @if($hourly['values'][$i] > 0)
                        <div class="flex items-center gap-3">
                            <span class="text-xs text-gray-500 dark:text-gray-400 w-12 text-right">{{ $hour }}</span>
                            <div class="flex-1 h-4 bg-gray-100 dark:bg-gray-800 rounded overflow-hidden">
                                <div
                                    class="h-full bg-primary-400 dark:bg-primary-600 rounded transition-all duration-300"
                                    style="width: {{ ($hourly['values'][$i] / $maxHourly) * 100 }}%;"
                                ></div>
                            </div>
                            <span class="text-xs font-medium text-gray-700 dark:text-gray-300 w-8">{{ $hourly['values'][$i] }}</span>
                        </div>
                    @endif
                @endforeach
            </div>
        </x-filament::section>

    </div>
</x-filament-panels::page>

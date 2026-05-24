<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-filament::icon icon="heroicon-o-chart-bar" class="h-5 w-5 text-primary-500" />
                <span>Métriques en temps réel</span>
                <span class="text-xs text-gray-400 font-normal ml-2">Actualisation toutes les 10s</span>
            </div>
        </x-slot>

        {{-- Ligne 1 : KPIs principaux --}}
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 mb-6">

            {{-- Commandes aujourd'hui --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Commandes aujourd'hui</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $todayOrders }}</p>
                @if ($ordersTrend['direction'] !== 'neutral')
                    <p class="mt-1 text-xs {{ $ordersTrend['direction'] === 'up' ? 'text-green-600' : 'text-red-500' }}">
                        {{ $ordersTrend['direction'] === 'up' ? '▲' : '▼' }} {{ $ordersTrend['value'] }}% vs hier
                    </p>
                @endif
            </div>

            {{-- Livraisons aujourd'hui --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Livrées aujourd'hui</p>
                <p class="mt-1 text-2xl font-bold text-green-600 dark:text-green-400">{{ $todayDelivered }}</p>
                <p class="mt-1 text-xs text-gray-400">Taux : {{ $deliveryRate }}%</p>
            </div>

            {{-- Revenus --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Revenus du jour</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($todayRevenue, 0, ',', ' ') }} F</p>
                @if ($revenueTrend['direction'] !== 'neutral')
                    <p class="mt-1 text-xs {{ $revenueTrend['direction'] === 'up' ? 'text-green-600' : 'text-red-500' }}">
                        {{ $revenueTrend['direction'] === 'up' ? '▲' : '▼' }} {{ $revenueTrend['value'] }}% vs hier
                    </p>
                @endif
            </div>

            {{-- Coursiers en ligne --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Coursiers en ligne</p>
                <p class="mt-1 text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $onlineCouriers }}</p>
                <p class="mt-1 text-xs text-gray-400">/ {{ $totalCouriers }} total</p>
            </div>
        </div>

        {{-- Ligne 2 : Commandes en cours --}}
        <div class="grid grid-cols-2 gap-4 mb-6">
            <div class="rounded-xl border border-yellow-200 dark:border-yellow-700 bg-yellow-50 dark:bg-yellow-900/20 p-4">
                <p class="text-xs font-medium text-yellow-700 dark:text-yellow-400 uppercase tracking-wide">En attente</p>
                <p class="mt-1 text-2xl font-bold text-yellow-700 dark:text-yellow-300">{{ $pendingOrders }}</p>
            </div>
            <div class="rounded-xl border border-blue-200 dark:border-blue-700 bg-blue-50 dark:bg-blue-900/20 p-4">
                <p class="text-xs font-medium text-blue-700 dark:text-blue-400 uppercase tracking-wide">En cours de livraison</p>
                <p class="mt-1 text-2xl font-bold text-blue-700 dark:text-blue-300">{{ $inProgressOrders }}</p>
            </div>
        </div>

        {{-- Ligne 3 : Statistiques 7 jours --}}
        <div>
            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">7 derniers jours</p>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-gray-400 uppercase tracking-wide border-b border-gray-200 dark:border-gray-700">
                            <th class="pb-2 pr-4">Jour</th>
                            <th class="pb-2 pr-4">Commandes</th>
                            <th class="pb-2">Revenus</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($weeklyStats as $stat)
                            <tr class="border-b border-gray-100 dark:border-gray-700/50">
                                <td class="py-1.5 pr-4 text-gray-600 dark:text-gray-300 font-medium">{{ $stat['day'] }}</td>
                                <td class="py-1.5 pr-4 text-gray-800 dark:text-gray-200">{{ $stat['orders'] }}</td>
                                <td class="py-1.5 text-gray-800 dark:text-gray-200">{{ number_format($stat['revenue'], 0, ',', ' ') }} F</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </x-filament::section>
</x-filament-widgets::widget>

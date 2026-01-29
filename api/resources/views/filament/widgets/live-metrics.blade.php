<x-filament-widgets::widget>
    <div class="space-y-6">
        {{-- Métriques principales --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Commandes aujourd'hui --}}
            <div class="relative overflow-hidden rounded-2xl bg-white dark:bg-gray-800 p-6 shadow-lg border border-gray-100 dark:border-gray-700 group hover:shadow-xl transition-all duration-300">
                <div class="absolute top-0 right-0 -mt-4 -mr-4 h-24 w-24 rounded-full bg-gradient-to-br from-blue-500/10 to-indigo-500/10 group-hover:scale-150 transition-transform duration-500"></div>
                
                <div class="relative">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 shadow-lg shadow-blue-500/30">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </div>
                        
                        @if($ordersTrend['direction'] === 'up')
                            <span class="flex items-center gap-1 text-emerald-600 dark:text-emerald-400 text-sm font-semibold bg-emerald-100 dark:bg-emerald-900/30 px-2 py-1 rounded-lg">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                </svg>
                                +{{ $ordersTrend['value'] }}%
                            </span>
                        @elseif($ordersTrend['direction'] === 'down')
                            <span class="flex items-center gap-1 text-red-600 dark:text-red-400 text-sm font-semibold bg-red-100 dark:bg-red-900/30 px-2 py-1 rounded-lg">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0v-8m0 8l-8-8-4 4-6-6"/>
                                </svg>
                                -{{ $ordersTrend['value'] }}%
                            </span>
                        @endif
                    </div>
                    
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Commandes</p>
                        <p class="text-3xl font-black text-gray-900 dark:text-white mt-1">{{ $todayOrders }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            <span class="text-emerald-600 dark:text-emerald-400 font-semibold">{{ $todayDelivered }}</span> livrées
                        </p>
                    </div>
                </div>
            </div>

            {{-- Revenus --}}
            <div class="relative overflow-hidden rounded-2xl bg-white dark:bg-gray-800 p-6 shadow-lg border border-gray-100 dark:border-gray-700 group hover:shadow-xl transition-all duration-300">
                <div class="absolute top-0 right-0 -mt-4 -mr-4 h-24 w-24 rounded-full bg-gradient-to-br from-emerald-500/10 to-green-500/10 group-hover:scale-150 transition-transform duration-500"></div>
                
                <div class="relative">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-green-600 shadow-lg shadow-emerald-500/30">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        
                        @if($revenueTrend['direction'] === 'up')
                            <span class="flex items-center gap-1 text-emerald-600 dark:text-emerald-400 text-sm font-semibold bg-emerald-100 dark:bg-emerald-900/30 px-2 py-1 rounded-lg">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                </svg>
                                +{{ $revenueTrend['value'] }}%
                            </span>
                        @elseif($revenueTrend['direction'] === 'down')
                            <span class="flex items-center gap-1 text-red-600 dark:text-red-400 text-sm font-semibold bg-red-100 dark:bg-red-900/30 px-2 py-1 rounded-lg">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0v-8m0 8l-8-8-4 4-6-6"/>
                                </svg>
                                -{{ $revenueTrend['value'] }}%
                            </span>
                        @endif
                    </div>
                    
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Revenus</p>
                        <p class="text-3xl font-black text-gray-900 dark:text-white mt-1">{{ number_format($todayRevenue, 0, ',', ' ') }} <span class="text-base font-medium">F</span></p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Aujourd'hui</p>
                    </div>
                </div>
            </div>

            {{-- Coursiers en ligne --}}
            <div class="relative overflow-hidden rounded-2xl bg-white dark:bg-gray-800 p-6 shadow-lg border border-gray-100 dark:border-gray-700 group hover:shadow-xl transition-all duration-300">
                <div class="absolute top-0 right-0 -mt-4 -mr-4 h-24 w-24 rounded-full bg-gradient-to-br from-orange-500/10 to-amber-500/10 group-hover:scale-150 transition-transform duration-500"></div>
                
                <div class="relative">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-orange-500 to-amber-600 shadow-lg shadow-orange-500/30">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        
                        <span class="flex items-center gap-1">
                            <span class="relative flex h-3 w-3">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex h-3 w-3 rounded-full bg-emerald-500"></span>
                            </span>
                            <span class="text-sm font-medium text-emerald-600 dark:text-emerald-400">Live</span>
                        </span>
                    </div>
                    
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Coursiers</p>
                        <p class="text-3xl font-black text-gray-900 dark:text-white mt-1">
                            {{ $onlineCouriers }}<span class="text-base font-medium text-gray-400">/{{ $totalCouriers }}</span>
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            En ligne
                        </p>
                    </div>
                </div>
                
                {{-- Barre de progression --}}
                @if($totalCouriers > 0)
                    <div class="mt-4">
                        <div class="h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                            <div class="h-full rounded-full bg-gradient-to-r from-orange-500 to-amber-500 transition-all duration-500"
                                 style="width: {{ ($onlineCouriers / $totalCouriers) * 100 }}%"></div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Taux de livraison --}}
            <div class="relative overflow-hidden rounded-2xl bg-white dark:bg-gray-800 p-6 shadow-lg border border-gray-100 dark:border-gray-700 group hover:shadow-xl transition-all duration-300">
                <div class="absolute top-0 right-0 -mt-4 -mr-4 h-24 w-24 rounded-full bg-gradient-to-br from-purple-500/10 to-pink-500/10 group-hover:scale-150 transition-transform duration-500"></div>
                
                <div class="relative">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-purple-500 to-pink-600 shadow-lg shadow-purple-500/30">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Taux livraison</p>
                        <p class="text-3xl font-black text-gray-900 dark:text-white mt-1">{{ $deliveryRate }}<span class="text-base font-medium">%</span></p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Aujourd'hui</p>
                    </div>
                </div>
                
                {{-- Gauge --}}
                <div class="mt-4">
                    <div class="h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-500
                                    {{ $deliveryRate >= 90 ? 'bg-gradient-to-r from-emerald-500 to-green-500' : '' }}
                                    {{ $deliveryRate >= 70 && $deliveryRate < 90 ? 'bg-gradient-to-r from-amber-500 to-orange-500' : '' }}
                                    {{ $deliveryRate < 70 ? 'bg-gradient-to-r from-red-500 to-pink-500' : '' }}"
                             style="width: {{ $deliveryRate }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Graphique de la semaine (mini sparkline) --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            {{-- En attente et en cours --}}
            <a href="{{ route('filament.admin.resources.orders.index', ['tableFilters[status][value]' => 'pending']) }}"
               class="flex items-center justify-between p-5 rounded-xl bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 border border-amber-200 dark:border-amber-800 hover:shadow-lg transition-all duration-300 group">
                <div class="flex items-center gap-4">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-400 to-orange-500 shadow-lg">
                        <span class="text-2xl">⏳</span>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-amber-700 dark:text-amber-300">En attente d'assignation</p>
                        <p class="text-3xl font-black text-amber-900 dark:text-amber-100">{{ $pendingOrders }}</p>
                    </div>
                </div>
                <svg class="w-6 h-6 text-amber-600 dark:text-amber-400 group-hover:translate-x-2 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                </svg>
            </a>

            <a href="{{ route('filament.admin.resources.orders.index', ['tableFilters[status][value]' => 'assigned']) }}"
               class="flex items-center justify-between p-5 rounded-xl bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-200 dark:border-blue-800 hover:shadow-lg transition-all duration-300 group">
                <div class="flex items-center gap-4">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-400 to-indigo-500 shadow-lg">
                        <span class="text-2xl">🛵</span>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-blue-700 dark:text-blue-300">En cours de livraison</p>
                        <p class="text-3xl font-black text-blue-900 dark:text-blue-100">{{ $inProgressOrders }}</p>
                    </div>
                </div>
                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400 group-hover:translate-x-2 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                </svg>
            </a>
        </div>
    </div>
</x-filament-widgets::widget>

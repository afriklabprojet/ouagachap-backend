<x-filament-widgets::widget>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                Actions rapides
            </h3>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
            <a href="{{ route('filament.admin.resources.orders.create') }}"
               class="group relative flex flex-col items-center justify-center p-6 rounded-2xl bg-gradient-to-br from-orange-500 to-amber-600 shadow-lg shadow-orange-500/25 hover:shadow-xl hover:shadow-orange-500/40 hover:-translate-y-1 transition-all duration-300">
                <div class="absolute inset-0 rounded-2xl bg-white/10 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative flex flex-col items-center">
                    <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-white/20 backdrop-blur-sm mb-3">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                    </div>
                    <span class="text-sm font-bold text-white text-center">Nouvelle<br>commande</span>
                </div>
            </a>

            <a href="{{ route('filament.admin.resources.couriers.index') }}"
               class="group relative flex flex-col items-center justify-center p-6 rounded-2xl bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 hover:border-blue-500 dark:hover:border-blue-500 shadow-lg hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                <div class="relative flex flex-col items-center">
                    <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 shadow-lg shadow-blue-500/30 mb-3">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <span class="text-sm font-bold text-gray-700 dark:text-gray-200 text-center">Coursiers</span>
                    @if($pendingCouriers > 0)
                        <span class="absolute -top-2 -right-2 flex h-6 w-6 items-center justify-center rounded-full bg-red-500 text-xs font-bold text-white animate-pulse">{{ $pendingCouriers }}</span>
                    @endif
                </div>
            </a>

            <a href="{{ route('filament.admin.resources.withdrawals.index') }}"
               class="group relative flex flex-col items-center justify-center p-6 rounded-2xl bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 hover:border-emerald-500 dark:hover:border-emerald-500 shadow-lg hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                <div class="relative flex flex-col items-center">
                    <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-green-600 shadow-lg shadow-emerald-500/30 mb-3">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <span class="text-sm font-bold text-gray-700 dark:text-gray-200 text-center">Retraits</span>
                    @if($pendingWithdrawals > 0)
                        <span class="absolute -top-2 -right-2 flex h-6 w-6 items-center justify-center rounded-full bg-amber-500 text-xs font-bold text-white">{{ $pendingWithdrawals }}</span>
                    @endif
                </div>
            </a>

            <a href="{{ route('filament.admin.pages.send-notification') }}"
               class="group relative flex flex-col items-center justify-center p-6 rounded-2xl bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 hover:border-purple-500 dark:hover:border-purple-500 shadow-lg hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                <div class="relative flex flex-col items-center">
                    <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-gradient-to-br from-purple-500 to-pink-600 shadow-lg shadow-purple-500/30 mb-3">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                    </div>
                    <span class="text-sm font-bold text-gray-700 dark:text-gray-200 text-center">Notifications</span>
                </div>
            </a>

            <a href="{{ route('filament.admin.pages.settings') }}"
               class="group relative flex flex-col items-center justify-center p-6 rounded-2xl bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 hover:border-gray-500 dark:hover:border-gray-500 shadow-lg hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                <div class="relative flex flex-col items-center">
                    <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-gradient-to-br from-gray-600 to-gray-800 shadow-lg shadow-gray-500/30 mb-3">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <span class="text-sm font-bold text-gray-700 dark:text-gray-200 text-center">Paramètres</span>
                </div>
            </a>
        </div>

        @if($pendingWithdrawalsAmount > 0 || $todayNewClients > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
                @if($pendingWithdrawalsAmount > 0)
                    <div class="flex items-center gap-3 p-4 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-500/20">
                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-amber-800 dark:text-amber-200">Retraits en attente</p>
                            <p class="text-lg font-bold text-amber-900 dark:text-amber-100">{{ number_format($pendingWithdrawalsAmount, 0, ',', ' ') }} FCFA</p>
                        </div>
                    </div>
                @endif

                @if($todayNewClients > 0)
                    <div class="flex items-center gap-3 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-500/20">
                            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-emerald-800 dark:text-emerald-200">Nouveaux clients aujourd'hui</p>
                            <p class="text-lg font-bold text-emerald-900 dark:text-emerald-100">+{{ $todayNewClients }}</p>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-filament-widgets::widget>

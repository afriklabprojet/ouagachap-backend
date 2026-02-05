<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Leaflet CSS dans le head --}}
        @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
        <style>
            .courier-marker { background: transparent !important; border: none !important; }
            .leaflet-popup-content-wrapper { border-radius: 12px !important; }
            .stat-number-green { color: #059669 !important; -webkit-text-fill-color: #059669 !important; }
            .stat-number-gray { color: #374151 !important; -webkit-text-fill-color: #374151 !important; }
            .stat-number-orange { color: #ea580c !important; -webkit-text-fill-color: #ea580c !important; }
            .stat-label { color: #6b7280 !important; -webkit-text-fill-color: #6b7280 !important; }
        </style>
        @endpush

        {{-- Header avec statistiques --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="relative overflow-hidden rounded-2xl bg-white p-6 shadow-xl border border-gray-200">
                <div class="absolute top-0 right-0 -mt-4 -mr-4 h-24 w-24 rounded-full bg-emerald-500/10"></div>
                <div class="relative flex items-center justify-between">
                    <div>
                        <p class="stat-label text-sm font-medium">Coursiers en ligne</p>
                        <p class="stat-number-green text-4xl font-black mt-1">{{ $this->getOnlineCouriersCount() }}</p>
                    </div>
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-500">
                        <span class="relative flex h-4 w-4">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-white opacity-75"></span>
                            <span class="relative inline-flex h-4 w-4 rounded-full bg-white"></span>
                        </span>
                    </div>
                </div>
            </div>

            <div class="relative overflow-hidden rounded-2xl bg-white p-6 shadow-xl border border-gray-200">
                <div class="absolute top-0 right-0 -mt-4 -mr-4 h-24 w-24 rounded-full bg-gray-500/10"></div>
                <div class="relative flex items-center justify-between">
                    <div>
                        <p class="stat-label text-sm font-medium">Hors ligne</p>
                        <p class="stat-number-gray text-4xl font-black mt-1">{{ $this->getOfflineCouriersCount() }}</p>
                    </div>
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-gray-500">
                        <x-heroicon-o-user-minus class="w-8 h-8 text-white"/>
                    </div>
                </div>
            </div>

            <div class="relative overflow-hidden rounded-2xl bg-white p-6 shadow-xl border border-gray-200">
                <div class="absolute top-0 right-0 -mt-4 -mr-4 h-24 w-24 rounded-full bg-orange-500/10"></div>
                <div class="relative flex items-center justify-between">
                    <div>
                        <p class="stat-label text-sm font-medium">Total coursiers</p>
                        <p class="stat-number-orange text-4xl font-black mt-1">{{ $this->getTotalCouriersCount() }}</p>
                    </div>
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-orange-500">
                        <x-heroicon-o-users class="w-8 h-8 text-white"/>
                    </div>
                </div>
            </div>
        </div>

        {{-- Carte et liste --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2" wire:ignore>
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-200 dark:border-gray-700">
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-slate-800 to-slate-900">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-orange-500">
                                    <x-heroicon-o-map-pin class="w-5 h-5 text-white"/>
                                </div>
                                <div>
                                    <h3 class="font-bold text-white">Carte en temps réel</h3>
                                    <p class="text-xs text-gray-400">Ouagadougou, Burkina Faso</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-red-500 text-white text-sm font-bold animate-pulse">
                                <span class="relative flex h-2 w-2">
                                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-white opacity-75"></span>
                                    <span class="relative inline-flex h-2 w-2 rounded-full bg-white"></span>
                                </span>
                                LIVE
                            </div>
                        </div>
                    </div>
                    <div id="tracking-map" style="height: 500px; width: 100%; background: #e5e7eb;"></div>
                    <div class="p-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex flex-wrap items-center gap-6">
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Légende:</span>
                            <div class="flex items-center gap-2"><span class="text-xl">🏍️</span><span class="text-sm text-gray-600 dark:text-gray-300">Moto</span></div>
                            <div class="flex items-center gap-2"><span class="text-xl">🚲</span><span class="text-sm text-gray-600 dark:text-gray-300">Vélo</span></div>
                            <div class="flex items-center gap-2"><span class="text-xl">🚗</span><span class="text-sm text-gray-600 dark:text-gray-300">Voiture</span></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-1">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 h-full">
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <span class="relative flex h-2 w-2">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                            </span>
                            Coursiers actifs ({{ count($this->getCouriers()) }})
                        </h3>
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-gray-700 max-h-[520px] overflow-y-auto">
                        @forelse($this->getCouriers() as $courier)
                            <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors cursor-pointer"
                                 onclick="window.focusOnCourier({{ $courier['lat'] }}, {{ $courier['lng'] }})">
                                <div class="flex items-center gap-3">
                                    <div class="relative flex-shrink-0">
                                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-orange-500 to-amber-600 text-xl shadow-lg">
                                            @switch($courier['vehicle'])
                                                @case('moto') 🏍️ @break
                                                @case('velo') 🚲 @break
                                                @case('voiture') 🚗 @break
                                                @default 🛵
                                            @endswitch
                                        </div>
                                        <span class="absolute -bottom-1 -right-1 flex h-4 w-4">
                                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                                            <span class="relative inline-flex h-4 w-4 rounded-full bg-emerald-500 border-2 border-white dark:border-gray-800"></span>
                                        </span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-semibold text-gray-900 dark:text-white truncate">{{ $courier['name'] }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $courier['phone'] }}</p>
                                    </div>
                                    <div class="flex flex-col items-end gap-1">
                                        <span class="inline-flex items-center text-xs font-medium text-amber-600 bg-amber-100 px-2 py-0.5 rounded-full">⭐ {{ $courier['rating'] }}</span>
                                        <span class="text-xs text-gray-500">📦 {{ $courier['deliveries'] }}</span>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="p-8 text-center">
                                <div class="text-5xl mb-4">📍</div>
                                <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Aucun coursier en ligne</h4>
                                <p class="text-sm text-gray-500">Les coursiers avec GPS actif apparaîtront ici.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- Données JSON cachées --}}
        <div id="couriers-json" style="display:none;">{{ json_encode($this->getCouriers()) }}</div>

        @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
        <script>
            (function() {
                var map = null;
                var markers = {};

                function init() {
                    var el = document.getElementById('tracking-map');
                    if (!el || map) return;
                    if (typeof L === 'undefined') { setTimeout(init, 200); return; }

                    map = L.map('tracking-map').setView([12.3714, -1.5197], 13);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '© OpenStreetMap', maxZoom: 19
                    }).addTo(map);

                    loadMarkers();
                }

                function loadMarkers() {
                    var el = document.getElementById('couriers-json');
                    if (!el || !map) return;
                    try {
                        var data = JSON.parse(el.textContent);
                        for (var k in markers) map.removeLayer(markers[k]);
                        markers = {};

                        data.forEach(function(c) {
                            var emoji = c.vehicle === 'moto' ? '🏍️' : c.vehicle === 'velo' ? '🚲' : c.vehicle === 'voiture' ? '🚗' : '🛵';
                            var icon = L.divIcon({
                                className: 'courier-marker',
                                html: '<div style="font-size:32px;filter:drop-shadow(0 2px 4px rgba(0,0,0,.3))">' + emoji + '</div>',
                                iconSize: [40, 40], iconAnchor: [20, 20]
                            });
                            markers[c.id] = L.marker([c.lat, c.lng], {icon: icon})
                                .addTo(map)
                                .bindPopup('<b>' + c.name + '</b><br>' + c.phone);
                        });

                        if (data.length > 0) {
                            var bounds = L.latLngBounds(data.map(function(c) { return [c.lat, c.lng]; }));
                            map.fitBounds(bounds, {padding: [50, 50], maxZoom: 15});
                        }
                    } catch(e) { console.error(e); }
                }

                window.focusOnCourier = function(lat, lng) {
                    if (map) map.flyTo([lat, lng], 16);
                };

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', function() { setTimeout(init, 100); });
                } else {
                    setTimeout(init, 100);
                }
            })();
        </script>
        @endpush
    </div>
</x-filament-panels::page>

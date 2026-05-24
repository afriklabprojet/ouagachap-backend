<x-filament-widgets::widget>
    <div class="space-y-4" wire:poll.5s="$refresh">
        {{-- Header avec statistiques --}}
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-800 via-slate-900 to-slate-800 p-6 shadow-2xl">
            {{-- Effet de brillance animé --}}
            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/5 to-transparent -translate-x-full animate-[shimmer_3s_infinite]"></div>
            
            <div class="relative flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                {{-- Titre --}}
                <div class="flex items-center gap-4">
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-orange-500 to-amber-600 shadow-lg shadow-orange-500/30">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-2xl font-black text-white">Suivi GPS en Direct</h2>
                        <p class="text-sm text-gray-400 flex items-center gap-2 mt-1">
                            <span class="relative flex h-2 w-2">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-400 opacity-75"></span>
                                <span class="relative inline-flex h-2 w-2 rounded-full bg-red-500"></span>
                            </span>
                            Actualisation automatique toutes les 5 secondes
                        </p>
                    </div>
                </div>
                
                {{-- Statistiques --}}
                <div class="flex items-center gap-4">
                    {{-- En ligne --}}
                    <div class="flex items-center gap-3 px-5 py-3 rounded-xl bg-emerald-500/20 border border-emerald-500/30">
                        <div class="relative">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative flex h-3 w-3 rounded-full bg-emerald-500"></span>
                        </div>
                        <div>
                            <span class="text-3xl font-black text-emerald-400">{{ $this->getOnlineCouriersCount() }}</span>
                            <span class="text-xs text-emerald-300 block">En ligne</span>
                        </div>
                    </div>
                    
                    {{-- Hors ligne --}}
                    <div class="flex items-center gap-3 px-5 py-3 rounded-xl bg-gray-500/20 border border-gray-500/30">
                        <span class="flex h-3 w-3 rounded-full bg-gray-500"></span>
                        <div>
                            <span class="text-3xl font-black text-gray-400">{{ $this->getOfflineCouriersCount() }}</span>
                            <span class="text-xs text-gray-500 block">Hors ligne</span>
                        </div>
                    </div>
                    
                    {{-- Total --}}
                    <div class="flex items-center gap-3 px-5 py-3 rounded-xl bg-blue-500/20 border border-blue-500/30">
                        <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <div>
                            <span class="text-3xl font-black text-blue-400">{{ $this->getTotalCouriersCount() }}</span>
                            <span class="text-xs text-blue-300 block">Total</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Carte principale avec Leaflet --}}
        <div class="relative rounded-2xl overflow-hidden shadow-2xl border border-gray-200 dark:border-gray-700">
            {{-- Container de la carte --}}
            <div id="couriers-live-map" class="w-full bg-gray-100 dark:bg-gray-800" style="height: 500px; z-index: 1;"></div>
            
            {{-- Badge LIVE --}}
            <div class="absolute top-4 left-4 z-[1000]">
                <div class="flex items-center gap-2 px-4 py-2 rounded-full bg-red-500 text-white font-bold text-sm shadow-lg animate-pulse">
                    <span class="relative flex h-2 w-2">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-white opacity-75"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-white"></span>
                    </span>
                    LIVE
                </div>
            </div>

            {{-- Légende --}}
            <div class="absolute top-4 right-4 z-[1000]">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl p-4 border border-gray-200 dark:border-gray-700 min-w-[160px]">
                    <h4 class="font-bold text-gray-900 dark:text-white mb-3 text-sm flex items-center gap-2">
                        <span>📍</span> Légende
                    </h4>
                    <div class="space-y-2">
                        <div class="flex items-center gap-3 text-sm">
                            <span class="text-xl">🏍️</span>
                            <span class="text-gray-600 dark:text-gray-300">Moto</span>
                        </div>
                        <div class="flex items-center gap-3 text-sm">
                            <span class="text-xl">🚲</span>
                            <span class="text-gray-600 dark:text-gray-300">Vélo</span>
                        </div>
                        <div class="flex items-center gap-3 text-sm">
                            <span class="text-xl">🚗</span>
                            <span class="text-gray-600 dark:text-gray-300">Voiture</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Boutons de contrôle --}}
            <div class="absolute bottom-4 right-4 z-[1000] flex flex-col gap-2">
                <button onclick="recenterMap()" 
                        class="flex items-center justify-center w-12 h-12 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all"
                        title="Recentrer la carte">
                    <svg class="w-5 h-5 text-gray-700 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                    </svg>
                </button>
                <button onclick="zoomIn()" 
                        class="flex items-center justify-center w-12 h-12 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all"
                        title="Zoom avant">
                    <svg class="w-5 h-5 text-gray-700 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                </button>
                <button onclick="zoomOut()" 
                        class="flex items-center justify-center w-12 h-12 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all"
                        title="Zoom arrière">
                    <svg class="w-5 h-5 text-gray-700 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                    </svg>
                </button>
            </div>

            {{-- Message si aucun coursier --}}
            @if(count($this->getCouriers()) === 0)
                <div class="absolute inset-0 z-[500] flex items-center justify-center bg-gray-900/50 backdrop-blur-sm">
                    <div class="text-center p-8 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-sm mx-4">
                        <div class="text-6xl mb-4">📍</div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Aucun coursier en ligne</h3>
                        <p class="text-gray-500 dark:text-gray-400 text-sm">Les coursiers disponibles avec GPS actif apparaîtront ici en temps réel.</p>
                    </div>
                </div>
            @endif
        </div>

        {{-- Liste des coursiers en ligne --}}
        @php $couriers = $this->getCouriers(); @endphp
        @if(count($couriers) > 0)
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                    <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <span class="relative flex h-2 w-2">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                        </span>
                        Coursiers actifs ({{ count($couriers) }})
                    </h3>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 p-4">
                    @foreach($couriers as $courier)
                        <div class="group flex items-center gap-4 p-4 rounded-xl bg-gray-50 dark:bg-gray-900 hover:bg-orange-50 dark:hover:bg-orange-900/20 border border-gray-200 dark:border-gray-700 hover:border-orange-300 dark:hover:border-orange-700 transition-all cursor-pointer"
                             onclick="focusCourier({{ $courier['lat'] }}, {{ $courier['lng'] }}, '{{ $courier['name'] }}')">
                            {{-- Avatar véhicule --}}
                            <div class="relative flex-shrink-0">
                                <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-gradient-to-br from-orange-500 to-amber-600 text-2xl shadow-lg">
                                    @switch($courier['vehicle'])
                                        @case('moto') 🏍️ @break
                                        @case('velo') 🚲 @break
                                        @case('voiture') 🚗 @break
                                        @default 🛵
                                    @endswitch
                                </div>
                                <span class="absolute -bottom-1 -right-1 flex h-4 w-4">
                                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex h-4 w-4 items-center justify-center rounded-full bg-emerald-500 border-2 border-white dark:border-gray-800"></span>
                                </span>
                            </div>
                            
                            {{-- Infos --}}
                            <div class="flex-1 min-w-0">
                                <p class="font-bold text-gray-900 dark:text-white truncate">{{ $courier['name'] }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $courier['phone'] }}</p>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="inline-flex items-center gap-1 text-xs font-medium text-amber-600 dark:text-amber-400 bg-amber-100 dark:bg-amber-900/30 px-2 py-0.5 rounded-full">
                                        ⭐ {{ $courier['rating'] }}
                                    </span>
                                    <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-600 dark:text-emerald-400 bg-emerald-100 dark:bg-emerald-900/30 px-2 py-0.5 rounded-full">
                                        📦 {{ $courier['deliveries'] }}
                                    </span>
                                </div>
                            </div>
                            
                            {{-- Bouton localiser --}}
                            <div class="flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-orange-500 text-white shadow">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- Leaflet CSS & JS - Chargement direct --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
    <style>
        @keyframes shimmer {
            100% { transform: translateX(100%); }
        }
        .leaflet-popup-content-wrapper {
            border-radius: 12px !important;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15) !important;
        }
        .leaflet-popup-content {
            margin: 12px 16px !important;
        }
        .courier-marker {
            background: transparent;
            border: none;
        }
        #couriers-live-map {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        }
    </style>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script>
        let map;
        let markers = {};
        const ouagadougou = [12.3714, -1.5197];
        
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(initMap, 100);
        });

        function initMap() {
            if (map) return;
            
            const mapContainer = document.getElementById('couriers-live-map');
            if (!mapContainer) {
                setTimeout(initMap, 200);
                return;
            }

            try {
                map = L.map('couriers-live-map', {
                    center: ouagadougou,
                    zoom: 13,
                    zoomControl: false
                });

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap',
                    maxZoom: 19
                }).addTo(map);

                updateCouriers();
            } catch (e) {
                console.error('Erreur initialisation carte:', e);
            }
        }

        function updateCouriers() {
            const couriers = @json($this->getCouriers());
            
            if (!map) return;
            
            couriers.forEach(courier => {
                const vehicleEmoji = {
                    'moto': '🏍️',
                    'velo': '🚲', 
                    'voiture': '🚗'
                }[courier.vehicle] || '🛵';

                const icon = L.divIcon({
                    className: 'courier-marker',
                    html: `<div style="font-size: 32px; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));">${vehicleEmoji}</div>`,
                    iconSize: [40, 40],
                    iconAnchor: [20, 20]
                });

                if (markers[courier.id]) {
                    markers[courier.id].setLatLng([courier.lat, courier.lng]);
                } else {
                    const marker = L.marker([courier.lat, courier.lng], { icon })
                        .addTo(map)
                        .bindPopup(`
                            <div class="text-center">
                                <div class="text-3xl mb-2">${vehicleEmoji}</div>
                                <div class="font-bold text-gray-900">${courier.name}</div>
                                <div class="text-sm text-gray-500">${courier.phone}</div>
                                <div class="flex justify-center gap-2 mt-2">
                                    <span class="text-xs bg-amber-100 text-amber-700 px-2 py-1 rounded">⭐ ${courier.rating}</span>
                                    <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded">📦 ${courier.deliveries}</span>
                                </div>
                            </div>
                        `);
                    markers[courier.id] = marker;
                }
            });

            // Ajuster la vue si des coursiers existent
            if (couriers.length > 0 && Object.keys(markers).length === couriers.length) {
                const bounds = L.latLngBounds(couriers.map(c => [c.lat, c.lng]));
                map.fitBounds(bounds, { padding: [50, 50], maxZoom: 15 });
            }
        }

        function focusCourier(lat, lng, name) {
            if (map) {
                map.flyTo([lat, lng], 16, { duration: 1 });
            }
        }

        function recenterMap() {
            if (map) {
                const couriers = @json($this->getCouriers());
                if (couriers.length > 0) {
                    const bounds = L.latLngBounds(couriers.map(c => [c.lat, c.lng]));
                    map.fitBounds(bounds, { padding: [50, 50] });
                } else {
                    map.setView(ouagadougou, 13);
                }
            }
        }

        function zoomIn() {
            if (map) map.zoomIn();
        }

        function zoomOut() {
            if (map) map.zoomOut();
        }

        // Réinitialiser la carte lors du polling Livewire
        if (typeof Livewire !== 'undefined') {
            Livewire.hook('morph.updated', () => {
                setTimeout(() => {
                    if (!map) initMap();
                    else updateCouriers();
                }, 100);
            });
        }
    </script>
</x-filament-widgets::widget>

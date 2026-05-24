<x-filament-panels::page>

    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
        <style>
            /* ---- Conteneur principal ---- */
            .live-map-wrap { background: #0a0e1a; border-radius: 1rem; overflow: hidden; }

            /* ---- Stat cards ---- */
            .lm-stat-card {
                background: rgba(255,255,255,.05);
                border: 1px solid rgba(255,255,255,.1);
                border-radius: .75rem;
                padding: 1rem 1.25rem;
                display: flex;
                align-items: center;
                gap: .875rem;
                transition: background .2s;
            }
            .lm-stat-card:hover { background: rgba(255,255,255,.09); }
            .lm-stat-icon {
                flex-shrink: 0;
                width: 2.75rem; height: 2.75rem;
                border-radius: .625rem;
                display: flex; align-items: center; justify-content: center;
                font-size: 1.25rem;
            }
            .lm-stat-value { font-size: 1.625rem; font-weight: 700; color: #fff; line-height: 1; }
            .lm-stat-label { font-size: .72rem; color: #8b9ab1; margin-top: .2rem; text-transform: uppercase; letter-spacing: .05em; }

            /* ---- Map ---- */
            #live-map { height: 540px; width: 100%; background: #0d1117; }
            .leaflet-container { font-family: inherit; }

            /* ---- Marqueur coursier SVG ---- */
            .cm-dot {
                width: 20px; height: 20px;
                border-radius: 50%;
                border: 2px solid rgba(255,255,255,.9);
                position: relative;
            }
            .cm-dot::after {
                content: '';
                position: absolute;
                inset: -6px;
                border-radius: 50%;
                animation: cm-pulse 2s ease-out infinite;
            }
            .cm-available { background: #00d4ff; }
            .cm-available::after { background: rgba(0,212,255,.35); }
            .cm-busy { background: #ff6b35; }
            .cm-busy::after { background: rgba(255,107,53,.35); }
            .cm-stale { background: #6b7280; border-color: rgba(255,255,255,.4); }
            .cm-stale::after { display: none; }

            @keyframes cm-pulse {
                0%   { transform: scale(1);   opacity: .9; }
                70%  { transform: scale(2.4); opacity: 0; }
                100% { transform: scale(2.4); opacity: 0; }
            }

            /* ---- Popup ---- */
            .leaflet-popup-content-wrapper {
                background: #1a2035;
                border: 1px solid rgba(255,255,255,.12);
                border-radius: .75rem;
                color: #e2e8f0;
                box-shadow: 0 8px 32px rgba(0,0,0,.5);
            }
            .leaflet-popup-tip { background: #1a2035; }
            .leaflet-popup-content { margin: .75rem 1rem; font-size: .85rem; }

            /* ---- Sidebar coursiers ---- */
            .lm-courier-item {
                display: flex; align-items: center; gap: .75rem;
                padding: .75rem 1rem;
                border-bottom: 1px solid rgba(255,255,255,.06);
                cursor: pointer;
                transition: background .15s;
            }
            .lm-courier-item:hover { background: rgba(255,255,255,.05); }
            .lm-courier-avatar {
                width: 2.5rem; height: 2.5rem; border-radius: .5rem;
                background: linear-gradient(135deg, #ff6b35, #f59e0b);
                display: flex; align-items: center; justify-content: center;
                font-size: 1.1rem; flex-shrink: 0;
            }
            .lm-badge {
                display: inline-flex; align-items: center;
                padding: .2rem .55rem;
                border-radius: 9999px;
                font-size: .68rem; font-weight: 600;
            }
            .lm-badge-available { background: rgba(0,212,255,.15); color: #00d4ff; }
            .lm-badge-busy      { background: rgba(255,107,53,.15); color: #ff9a6c; }
            .lm-badge-stale     { background: rgba(107,114,128,.2); color: #9ca3af; }

            /* ---- Live indicator ---- */
            .lm-live-dot {
                width: 8px; height: 8px; border-radius: 50%;
                background: #22c55e;
                display: inline-block;
                animation: lm-blink 1.4s ease-in-out infinite;
            }
            @keyframes lm-blink {
                0%,100% { opacity: 1; } 50% { opacity: .3; }
            }

            /* ---- Scrollbar sidebar ---- */
            .lm-scroll::-webkit-scrollbar { width: 4px; }
            .lm-scroll::-webkit-scrollbar-track { background: transparent; }
            .lm-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,.15); border-radius: 2px; }
        </style>
    @endpush

    {{-- ============================================================ --}}
    {{-- Données JSON injectées par Livewire (sans wire:ignore)       --}}
    {{-- MutationObserver JS détecte les mises à jour                --}}
    {{-- ============================================================ --}}
    <div id="lm-couriers-json" style="display:none">{{ json_encode($couriers) }}</div>
    <div id="lm-orders-json"   style="display:none">{{ json_encode($orders) }}</div>
    <div id="lm-heatmap-json"  style="display:none">{{ json_encode($heatmap) }}</div>

    {{-- ============================================================ --}}
    {{-- Wrapper principal fond sombre                                --}}
    {{-- ============================================================ --}}
    <div class="live-map-wrap" style="padding: 1.25rem; display: flex; flex-direction: column; gap: 1.25rem;">

        {{-- ---- Header ---- --}}
        <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.75rem;">
            <div style="display:flex; align-items:center; gap:.75rem;">
                <div style="width:2.5rem;height:2.5rem;border-radius:.625rem;background:linear-gradient(135deg,#00d4ff,#0066ff);display:flex;align-items:center;justify-content:center;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="1.8" width="20" height="20">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498 4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 0 0-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0Z" />
                    </svg>
                </div>
                <div>
                    <h2 style="font-size:1.1rem;font-weight:700;color:#fff;margin:0;">Carte Live</h2>
                    <p style="font-size:.72rem;color:#8b9ab1;margin:0;">Ouagadougou, Burkina Faso</p>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:.5rem;background:rgba(34,197,94,.12);border:1px solid rgba(34,197,94,.25);border-radius:9999px;padding:.35rem .9rem;">
                <span class="lm-live-dot"></span>
                <span style="font-size:.75rem;font-weight:700;color:#22c55e;letter-spacing:.06em;">LIVE</span>
                <span style="font-size:.7rem;color:#6b7280;margin-left:.25rem;" id="lm-last-update">—</span>
            </div>
        </div>

        {{-- ---- Stats cards (wire:poll rafraîchit ces valeurs) ---- --}}
        <div wire:poll.5000ms="loadData"
             style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:.75rem;">

            <div class="lm-stat-card">
                <div class="lm-stat-icon" style="background:rgba(99,102,241,.2);">🏍️</div>
                <div>
                    <div class="lm-stat-value">{{ $stats['total_couriers'] }}</div>
                    <div class="lm-stat-label">Total coursiers</div>
                </div>
            </div>

            <div class="lm-stat-card">
                <div class="lm-stat-icon" style="background:rgba(0,212,255,.15);">🟢</div>
                <div>
                    <div class="lm-stat-value" style="color:#00d4ff;">{{ $stats['available_couriers'] }}</div>
                    <div class="lm-stat-label">Disponibles</div>
                </div>
            </div>

            <div class="lm-stat-card">
                <div class="lm-stat-icon" style="background:rgba(255,107,53,.15);">🔶</div>
                <div>
                    <div class="lm-stat-value" style="color:#ff6b35;">{{ $stats['busy_couriers'] }}</div>
                    <div class="lm-stat-label">En livraison</div>
                </div>
            </div>

            <div class="lm-stat-card">
                <div class="lm-stat-icon" style="background:rgba(251,191,36,.15);">📦</div>
                <div>
                    <div class="lm-stat-value" style="color:#fbbf24;">{{ $stats['active_orders'] }}</div>
                    <div class="lm-stat-label">Commandes actives</div>
                </div>
            </div>
        </div>

        {{-- ---- Zone carte + sidebar ---- --}}
        <div style="display:grid;grid-template-columns:1fr 300px;gap:1rem;min-height:560px;">

            {{-- Carte Leaflet (wire:ignore = Livewire ne la touche pas) --}}
            <div wire:ignore
                 style="border-radius:.75rem;overflow:hidden;border:1px solid rgba(255,255,255,.08);">

                {{-- Barre de contrôle carte --}}
                <div style="background:#0d1117;padding:.6rem 1rem;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid rgba(255,255,255,.07);">
                    <div style="display:flex;gap:.5rem;">
                        <button onclick="lmToggleHeatmap()" id="lm-btn-heat"
                                style="background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);border-radius:.4rem;padding:.3rem .7rem;font-size:.72rem;color:#e2e8f0;cursor:pointer;">
                            🌡️ Heatmap
                        </button>
                        <button onclick="lmToggleOrders()" id="lm-btn-orders"
                                style="background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);border-radius:.4rem;padding:.3rem .7rem;font-size:.72rem;color:#e2e8f0;cursor:pointer;">
                            📦 Commandes
                        </button>
                        <button onclick="lmFitAll()"
                                style="background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);border-radius:.4rem;padding:.3rem .7rem;font-size:.72rem;color:#e2e8f0;cursor:pointer;">
                            ⬜ Centrer
                        </button>
                    </div>
                    <span style="font-size:.7rem;color:#6b7280;" id="lm-courier-count">0 coursiers actifs</span>
                </div>

                <div id="live-map"></div>
            </div>

            {{-- Sidebar coursiers ---- --}}
            <div style="background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.08);border-radius:.75rem;display:flex;flex-direction:column;overflow:hidden;">
                <div style="padding:.75rem 1rem;border-bottom:1px solid rgba(255,255,255,.07);display:flex;align-items:center;gap:.5rem;">
                    <span class="lm-live-dot"></span>
                    <span style="font-size:.85rem;font-weight:600;color:#fff;">
                        Coursiers en ligne
                        <span style="color:#8b9ab1;font-weight:400;" id="lm-sidebar-count">({{ count($couriers) }})</span>
                    </span>
                </div>
                <div class="lm-scroll" id="lm-sidebar-list"
                     style="flex:1;overflow-y:auto;max-height:490px;">
                    @forelse($couriers as $c)
                        <div class="lm-courier-item" onclick="lmFocusCourier({{ $c['lat'] }}, {{ $c['lng'] }})">
                            <div class="lm-courier-avatar">
                                @switch($c['vehicle_type'] ?? 'moto')
                                    @case('velo')   🚲 @break
                                    @case('voiture')🚗 @break
                                    @default        🏍️
                                @endswitch
                            </div>
                            <div style="flex:1;min-width:0;">
                                <p style="font-weight:600;color:#fff;font-size:.82rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin:0;">
                                    {{ $c['name'] }}
                                </p>
                                <p style="font-size:.72rem;color:#8b9ab1;margin:0 0 .25rem;">{{ $c['phone'] }}</p>
                                @if($c['available'] && $c['freshness'] < 120)
                                    <span class="lm-badge lm-badge-available">Disponible</span>
                                @elseif($c['freshness'] >= 120)
                                    <span class="lm-badge lm-badge-stale">Hors ligne</span>
                                @else
                                    <span class="lm-badge lm-badge-busy">En livraison</span>
                                @endif
                            </div>
                            <div style="text-align:right;flex-shrink:0;">
                                <div style="font-size:.72rem;color:{{ $c['battery'] < 20 ? '#ef4444' : ($c['battery'] < 50 ? '#f59e0b' : '#22c55e') }};">
                                    🔋 {{ $c['battery'] }}%
                                </div>
                                <div style="font-size:.68rem;color:#6b7280;margin-top:.2rem;">
                                    {{ $c['freshness'] < 60 ? 'Maintenant' : round($c['freshness'] / 60).'min' }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <div style="padding:2rem;text-align:center;">
                            <div style="font-size:2.5rem;margin-bottom:.75rem;">📍</div>
                            <p style="font-size:.82rem;font-weight:600;color:#fff;margin:0 0 .35rem;">Aucun coursier en ligne</p>
                            <p style="font-size:.72rem;color:#6b7280;margin:0;">Les coursiers avec GPS actif apparaîtront ici.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ---- Pied de page ---- --}}
        <div style="display:flex;align-items:center;gap:1.5rem;flex-wrap:wrap;">
            <div style="display:flex;align-items:center;gap:.5rem;">
                <span style="width:10px;height:10px;border-radius:50%;background:#00d4ff;display:inline-block;"></span>
                <span style="font-size:.72rem;color:#8b9ab1;">Disponible</span>
            </div>
            <div style="display:flex;align-items:center;gap:.5rem;">
                <span style="width:10px;height:10px;border-radius:50%;background:#ff6b35;display:inline-block;"></span>
                <span style="font-size:.72rem;color:#8b9ab1;">En livraison</span>
            </div>
            <div style="display:flex;align-items:center;gap:.5rem;">
                <span style="width:10px;height:10px;border-radius:50%;background:#6b7280;display:inline-block;"></span>
                <span style="font-size:.72rem;color:#8b9ab1;">Inactif (&gt;2min)</span>
            </div>
            <div style="display:flex;align-items:center;gap:.5rem;">
                <span style="font-size:.8rem;">🟢</span>
                <span style="font-size:.72rem;color:#8b9ab1;">Point de collecte</span>
            </div>
            <div style="display:flex;align-items:center;gap:.5rem;">
                <span style="font-size:.8rem;">🔴</span>
                <span style="font-size:.72rem;color:#8b9ab1;">Point de livraison</span>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
        <script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js" crossorigin=""></script>
        <script>
        (function () {
            'use strict';

            // ----------------------------------------------------------------
            // État global du module carte
            // ----------------------------------------------------------------
            var map          = null;
            var courierMarkers  = {};
            var pickupMarkers   = {};
            var dropoffMarkers  = {};
            var heatLayer    = null;
            var showHeatmap  = false;
            var showOrders   = true;

            // ----------------------------------------------------------------
            // Icônes Leaflet
            // ----------------------------------------------------------------
            function courierIcon(available, freshness) {
                var cls = freshness > 120 ? 'cm-stale' : (available ? 'cm-available' : 'cm-busy');
                return L.divIcon({
                    className: '',
                    html: '<div class="cm-dot ' + cls + '"></div>',
                    iconSize:   [20, 20],
                    iconAnchor: [10, 10],
                    popupAnchor:[0, -14],
                });
            }

            function pinIcon(color, emoji) {
                return L.divIcon({
                    className: '',
                    html: '<div style="background:' + color + ';width:26px;height:26px;border-radius:50% 50% 50% 0;transform:rotate(-45deg);border:2px solid #fff;display:flex;align-items:center;justify-content:center;"><span style="transform:rotate(45deg);font-size:12px;line-height:1;">' + emoji + '</span></div>',
                    iconSize:   [26, 26],
                    iconAnchor: [13, 26],
                    popupAnchor:[0, -28],
                });
            }

            // ----------------------------------------------------------------
            // Initialisation de la carte
            // ----------------------------------------------------------------
            function initMap() {
                var el = document.getElementById('live-map');
                if (!el || map) return;
                if (typeof L === 'undefined') { setTimeout(initMap, 200); return; }

                map = L.map('live-map', {
                    zoomControl:        true,
                    attributionControl: true,
                }).setView([12.3714, -1.5197], 13);

                L.tileLayer(
                    'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',
                    {
                        attribution: '&copy; <a href="https://carto.com/">CARTO</a> &copy; <a href="https://www.openstreetmap.org/copyright">OSM</a>',
                        subdomains: 'abcd',
                        maxZoom: 20,
                    }
                ).addTo(map);

                // Chargement initial des données
                refreshMap();
            }

            // ----------------------------------------------------------------
            // Mise à jour des marqueurs coursiers
            // ----------------------------------------------------------------
            function refreshMap() {
                var couriersEl = document.getElementById('lm-couriers-json');
                var ordersEl   = document.getElementById('lm-orders-json');
                var heatmapEl  = document.getElementById('lm-heatmap-json');

                if (!map || !couriersEl) return;

                try {
                    var couriers = JSON.parse(couriersEl.textContent || '[]');
                    var orders   = JSON.parse(ordersEl   ? ordersEl.textContent  : '[]');
                    var heatData = JSON.parse(heatmapEl  ? heatmapEl.textContent : '[]');

                    updateCourierMarkers(couriers);
                    if (showOrders)  updateOrderPins(orders);
                    if (showHeatmap) updateHeatmap(heatData);

                    // Mettre à jour le compte
                    var countEl = document.getElementById('lm-courier-count');
                    if (countEl) countEl.textContent = couriers.length + ' coursier' + (couriers.length > 1 ? 's' : '') + ' actif' + (couriers.length > 1 ? 's' : '');

                    // Horodatage
                    var timeEl = document.getElementById('lm-last-update');
                    if (timeEl) {
                        var d = new Date();
                        timeEl.textContent = d.getHours().toString().padStart(2,'0') + ':' + d.getMinutes().toString().padStart(2,'0') + ':' + d.getSeconds().toString().padStart(2,'0');
                    }
                } catch (e) { console.error('[LiveMap] refreshMap error', e); }
            }

            function updateCourierMarkers(couriers) {
                var seen = {};
                couriers.forEach(function(c) {
                    seen[c.id] = true;
                    var latlng = [c.lat, c.lng];
                    if (courierMarkers[c.id]) {
                        courierMarkers[c.id].setLatLng(latlng);
                        // Régénérer l'icône si le statut a changé
                        courierMarkers[c.id].setIcon(courierIcon(c.available, c.freshness));
                    } else {
                        var statusLabel = c.freshness > 120 ? 'Inactif'
                            : (c.available ? 'Disponible' : 'En livraison');
                        var marker = L.marker(latlng, { icon: courierIcon(c.available, c.freshness) })
                            .addTo(map)
                            .bindPopup(
                                '<div style="min-width:160px">'
                                + '<strong style="font-size:.9rem;">' + escHtml(c.name) + '</strong><br>'
                                + '<span style="color:#8b9ab1;font-size:.78rem;">' + escHtml(c.phone) + '</span><br><br>'
                                + '<span style="font-size:.78rem;">🔋 ' + c.battery + '% &nbsp;|&nbsp; ' + statusLabel + '</span>'
                                + '</div>'
                            );
                        courierMarkers[c.id] = marker;
                    }
                });

                // Supprimer les marqueurs des coursiers partis hors ligne
                Object.keys(courierMarkers).forEach(function(id) {
                    if (!seen[id]) {
                        map.removeLayer(courierMarkers[id]);
                        delete courierMarkers[id];
                    }
                });
            }

            function updateOrderPins(orders) {
                // Nettoyer les anciens pins
                Object.values(pickupMarkers).forEach(function(m)  { map.removeLayer(m); });
                Object.values(dropoffMarkers).forEach(function(m) { map.removeLayer(m); });
                pickupMarkers  = {};
                dropoffMarkers = {};

                if (!showOrders) return;

                orders.forEach(function(o) {
                    pickupMarkers[o.id] = L.marker([o.pickup.lat,  o.pickup.lng],  { icon: pinIcon('#22c55e','🟢') })
                        .addTo(map)
                        .bindPopup('<small>Collecte #' + o.id.substring(0,8) + '…</small>');

                    dropoffMarkers[o.id] = L.marker([o.dropoff.lat, o.dropoff.lng], { icon: pinIcon('#ef4444','🔴') })
                        .addTo(map)
                        .bindPopup('<small>Livraison #' + o.id.substring(0,8) + '…</small>');
                });
            }

            function updateHeatmap(points) {
                if (heatLayer) { map.removeLayer(heatLayer); heatLayer = null; }
                if (!showHeatmap || !points.length) return;
                if (typeof L.heatLayer === 'undefined') return;
                heatLayer = L.heatLayer(points, {
                    radius: 25, blur: 20, maxZoom: 17, gradient: { 0.2:'blue', 0.5:'lime', 0.8:'yellow', 1:'red' }
                }).addTo(map);
            }

            // ----------------------------------------------------------------
            // Contrôles exposés globalement
            // ----------------------------------------------------------------
            window.lmToggleHeatmap = function() {
                showHeatmap = !showHeatmap;
                var btn = document.getElementById('lm-btn-heat');
                if (btn) btn.style.background = showHeatmap
                    ? 'rgba(99,102,241,.3)' : 'rgba(255,255,255,.07)';
                refreshMap();
            };

            window.lmToggleOrders = function() {
                showOrders = !showOrders;
                var btn = document.getElementById('lm-btn-orders');
                if (btn) btn.style.background = showOrders
                    ? 'rgba(251,191,36,.2)' : 'rgba(255,255,255,.07)';
                refreshMap();
            };

            window.lmFocusCourier = function(lat, lng) {
                if (map) map.flyTo([lat, lng], 16, { animate: true, duration: 1.2 });
            };

            window.lmFitAll = function() {
                var all = Object.values(courierMarkers)
                    .concat(Object.values(pickupMarkers))
                    .concat(Object.values(dropoffMarkers));
                if (all.length && map) {
                    map.fitBounds(L.latLngBounds(all.map(function(m) { return m.getLatLng(); })),
                        { padding: [50, 50], maxZoom: 15 });
                } else if (map) {
                    map.setView([12.3714, -1.5197], 13);
                }
            };

            // ----------------------------------------------------------------
            // MutationObserver — détecte quand Livewire met à jour les JSON
            // ----------------------------------------------------------------
            function watchJson() {
                var els = ['lm-couriers-json', 'lm-orders-json', 'lm-heatmap-json'];
                els.forEach(function(id) {
                    var el = document.getElementById(id);
                    if (!el) return;
                    new MutationObserver(refreshMap)
                        .observe(el, { childList: true, characterData: true, subtree: true });
                });
            }

            // ----------------------------------------------------------------
            // Sécurité XSS basique pour les popups
            // ----------------------------------------------------------------
            function escHtml(str) {
                return String(str)
                    .replace(/&/g,'&amp;')
                    .replace(/</g,'&lt;')
                    .replace(/>/g,'&gt;')
                    .replace(/"/g,'&quot;');
            }

            // ----------------------------------------------------------------
            // Bootstrap
            // ----------------------------------------------------------------
            function boot() {
                initMap();
                watchJson();
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() { setTimeout(boot, 150); });
            } else {
                setTimeout(boot, 150);
            }

        })();
        </script>
    @endpush

</x-filament-panels::page>

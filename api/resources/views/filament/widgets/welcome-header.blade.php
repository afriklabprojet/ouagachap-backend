<x-filament-widgets::widget>
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-orange-500 via-orange-600 to-amber-500 p-6 shadow-2xl">
        {{-- Motif de fond --}}
        <div class="absolute inset-0 opacity-10">
            <svg class="h-full w-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                <defs>
                    <pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse">
                        <path d="M 10 0 L 0 0 0 10" fill="none" stroke="white" stroke-width="0.5"/>
                    </pattern>
                </defs>
                <rect width="100" height="100" fill="url(#grid)" />
            </svg>
        </div>
        
        {{-- Cercles décoratifs --}}
        <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-white/10 blur-2xl"></div>
        <div class="absolute -bottom-10 -left-10 h-32 w-32 rounded-full bg-white/10 blur-xl"></div>
        
        <div class="relative z-10">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                {{-- Partie gauche: Salutation --}}
                <div class="flex items-center gap-4">
                    {{-- Avatar avec badge en ligne --}}
                    <div class="relative">
                        <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white/20 backdrop-blur-sm shadow-lg">
                            <span class="text-3xl font-bold text-white">
                                {{ substr($userName, 0, 1) }}
                            </span>
                        </div>
                        <span class="absolute -bottom-1 -right-1 flex h-5 w-5">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex h-5 w-5 items-center justify-center rounded-full bg-emerald-500 border-2 border-white">
                                <svg class="h-3 w-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </span>
                        </span>
                    </div>
                    
                    <div>
                        <h1 class="text-2xl lg:text-3xl font-bold text-white">
                            {{ $greeting }}, {{ explode(' ', $userName)[0] }} 👋
                        </h1>
                        <p class="text-white/80 text-sm lg:text-base flex items-center gap-2 mt-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            {{ $currentDate }}
                            <span class="mx-2">•</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="font-mono font-semibold" wire:poll.1s="$refresh">{{ $currentTime }}</span>
                        </p>
                    </div>
                </div>
                
                {{-- Partie droite: Alertes --}}
                @if($alertsCount > 0)
                    <div class="flex flex-wrap gap-2">
                        @foreach($alerts as $alert)
                            <a href="{{ $alert['action'] }}" 
                               class="group flex items-center gap-2 px-4 py-2 rounded-xl backdrop-blur-sm transition-all duration-200 hover:scale-105
                                      {{ $alert['type'] === 'danger' ? 'bg-red-500/30 hover:bg-red-500/50' : '' }}
                                      {{ $alert['type'] === 'warning' ? 'bg-amber-500/30 hover:bg-amber-500/50' : '' }}
                                      {{ $alert['type'] === 'info' ? 'bg-blue-500/30 hover:bg-blue-500/50' : '' }}">
                                <x-dynamic-component :component="$alert['icon']" class="w-5 h-5 text-white"/>
                                <span class="text-sm font-medium text-white">{{ $alert['message'] }}</span>
                                <svg class="w-4 h-4 text-white/70 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-500/30 backdrop-blur-sm">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-sm font-medium text-white">Tout est en ordre !</span>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-filament-widgets::widget>

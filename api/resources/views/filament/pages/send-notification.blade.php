<x-filament-panels::page>
    <form wire:submit="send">
        {{ $this->form }}

        <div class="mt-6 flex justify-end gap-3">
            <x-filament::button type="submit" color="primary" icon="heroicon-o-paper-airplane">
                📤 Envoyer les notifications
            </x-filament::button>
        </div>
    </form>

    {{-- Prévisualisation --}}
    <div class="mt-8">
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
            📱 Prévisualisation
        </h3>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-4 max-w-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-full bg-orange-100 dark:bg-orange-900 flex items-center justify-center">
                    <span class="text-lg">
                        @if ($this->data['type'] ?? '' === 'promo') 🎁 @else 🔔 @endif
                    </span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">
                        {{ $this->data['title'] ?? 'Titre de la notification' }}
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 line-clamp-2">
                        {{ $this->data['message'] ?? 'Contenu du message...' }}
                    </p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                        Maintenant · OUAGA CHAP
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Statistiques --}}
    <div class="mt-8 grid grid-cols-1 md:grid-cols-4 gap-4">
        <x-filament::section>
            <div class="text-center">
                <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                    {{ \App\Models\InAppNotification::whereDate('created_at', today())->count() }}
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Envoyées aujourd'hui</div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-center">
                <div class="text-3xl font-bold text-orange-500">
                    {{ \App\Models\InAppNotification::where('is_read', false)->count() }}
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Non lues</div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-center">
                <div class="text-3xl font-bold text-green-500">
                    {{ \App\Models\User::count() }}
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Utilisateurs totaux</div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-center">
                <div class="text-3xl font-bold text-blue-500">
                    {{ \App\Models\User::where('role', \App\Enums\UserRole::COURIER)->where('is_available', true)->count() }}
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Coursiers en ligne</div>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>

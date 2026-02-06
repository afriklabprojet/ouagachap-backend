<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            📊 Générer un rapport
        </x-slot>
        
        <x-slot name="description">
            Sélectionnez les paramètres pour générer votre rapport personnalisé
        </x-slot>

        <form wire:submit="generateReport">
            {{ $this->form }}
            
            <div class="mt-6">
                <x-filament::button type="submit" color="primary" icon="heroicon-o-arrow-down-tray">
                    Générer et télécharger
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mt-6">
        @php
            $stats = $this->getOrdersStats();
        @endphp
        
        <x-filament::section>
            <div class="flex items-center gap-4">
                <div class="p-3 bg-primary-100 dark:bg-primary-900 rounded-lg">
                    <x-heroicon-o-shopping-bag class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total commandes</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total']) }}</p>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="flex items-center gap-4">
                <div class="p-3 bg-success-100 dark:bg-success-900 rounded-lg">
                    <x-heroicon-o-check-circle class="w-6 h-6 text-success-600 dark:text-success-400" />
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Livrées</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['delivered']) }}</p>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="flex items-center gap-4">
                <div class="p-3 bg-danger-100 dark:bg-danger-900 rounded-lg">
                    <x-heroicon-o-x-circle class="w-6 h-6 text-danger-600 dark:text-danger-400" />
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Annulées</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['cancelled']) }}</p>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="flex items-center gap-4">
                <div class="p-3 bg-warning-100 dark:bg-warning-900 rounded-lg">
                    <x-heroicon-o-currency-dollar class="w-6 h-6 text-warning-600 dark:text-warning-400" />
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Revenus</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['revenue'], 0, ',', ' ') }} F</p>
                </div>
            </div>
        </x-filament::section>
    </div>

    <x-filament::section class="mt-6">
        <x-slot name="heading">
            📋 Types de rapports disponibles
        </x-slot>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <div class="flex items-center gap-3 mb-2">
                    <span class="text-2xl">📦</span>
                    <h3 class="font-semibold">Commandes</h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Liste complète des commandes avec statuts, clients, coursiers et montants.
                </p>
            </div>

            <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <div class="flex items-center gap-3 mb-2">
                    <span class="text-2xl">🏍️</span>
                    <h3 class="font-semibold">Coursiers</h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Performance des coursiers : livraisons, notes, gains et disponibilité.
                </p>
            </div>

            <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <div class="flex items-center gap-3 mb-2">
                    <span class="text-2xl">👥</span>
                    <h3 class="font-semibold">Clients</h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Liste des clients avec nombre de commandes et informations de contact.
                </p>
            </div>

            <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <div class="flex items-center gap-3 mb-2">
                    <span class="text-2xl">💰</span>
                    <h3 class="font-semibold">Paiements</h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Historique des paiements avec méthodes, statuts et références.
                </p>
            </div>

            <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <div class="flex items-center gap-3 mb-2">
                    <span class="text-2xl">📈</span>
                    <h3 class="font-semibold">Revenus</h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Analyse des revenus par jour avec totaux et moyennes.
                </p>
            </div>

            <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600">
                <div class="flex items-center gap-3 mb-2">
                    <span class="text-2xl">🔜</span>
                    <h3 class="font-semibold text-gray-400">Plus à venir...</h3>
                </div>
                <p class="text-sm text-gray-400">
                    Rapports personnalisés, analytics avancées...
                </p>
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page>

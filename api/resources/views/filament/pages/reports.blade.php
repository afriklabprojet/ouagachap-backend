<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Formulaire de filtres --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-funnel" class="w-5 h-5 text-primary-500" />
                    <span>Paramètres du rapport</span>
                </div>
            </x-slot>

            <form wire:submit.prevent="generateReport">
                {{ $this->form }}

                <div class="mt-6 flex items-center justify-end gap-3">
                    <x-filament::button
                        type="submit"
                        icon="heroicon-o-arrow-down-tray"
                        color="primary"
                        size="md"
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading.remove>Générer le rapport</span>
                        <span wire:loading class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 5.373 0 0 12h4z"></path>
                            </svg>
                            Génération en cours...
                        </span>
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>

        {{-- Informations sur les types de rapports --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-information-circle" class="w-5 h-5 text-info-500" />
                    <span>Types de rapports disponibles</span>
                </div>
            </x-slot>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

                <div class="flex items-start gap-3 p-4 rounded-xl bg-warning-50 dark:bg-warning-950 border border-warning-200 dark:border-warning-800">
                    <div class="flex-shrink-0 text-2xl">📦</div>
                    <div>
                        <p class="font-semibold text-warning-800 dark:text-warning-200">Commandes</p>
                        <p class="text-sm text-warning-600 dark:text-warning-400">
                            Historique complet des commandes avec statut, client, coursier et montants.
                        </p>
                    </div>
                </div>

                <div class="flex items-start gap-3 p-4 rounded-xl bg-primary-50 dark:bg-primary-950 border border-primary-200 dark:border-primary-800">
                    <div class="flex-shrink-0 text-2xl">🏍️</div>
                    <div>
                        <p class="font-semibold text-primary-800 dark:text-primary-200">Coursiers</p>
                        <p class="text-sm text-primary-600 dark:text-primary-400">
                            Performances des coursiers, nombre de livraisons et revenus générés.
                        </p>
                    </div>
                </div>

                <div class="flex items-start gap-3 p-4 rounded-xl bg-success-50 dark:bg-success-950 border border-success-200 dark:border-success-800">
                    <div class="flex-shrink-0 text-2xl">👥</div>
                    <div>
                        <p class="font-semibold text-success-800 dark:text-success-200">Clients</p>
                        <p class="text-sm text-success-600 dark:text-success-400">
                            Inscriptions, activité et comportement des clients sur la période.
                        </p>
                    </div>
                </div>

                <div class="flex items-start gap-3 p-4 rounded-xl bg-info-50 dark:bg-info-950 border border-info-200 dark:border-info-800">
                    <div class="flex-shrink-0 text-2xl">💰</div>
                    <div>
                        <p class="font-semibold text-info-800 dark:text-info-200">Paiements</p>
                        <p class="text-sm text-info-600 dark:text-info-400">
                            Transactions, statuts de paiement et montants traités.
                        </p>
                    </div>
                </div>

                <div class="flex items-start gap-3 p-4 rounded-xl bg-danger-50 dark:bg-danger-950 border border-danger-200 dark:border-danger-800">
                    <div class="flex-shrink-0 text-2xl">📈</div>
                    <div>
                        <p class="font-semibold text-danger-800 dark:text-danger-200">Revenus</p>
                        <p class="text-sm text-danger-600 dark:text-danger-400">
                            Synthèse des commissions et revenus de la plateforme.
                        </p>
                    </div>
                </div>

            </div>
        </x-filament::section>

        {{-- Formats d'export --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-document-arrow-down" class="w-5 h-5 text-gray-500" />
                    <span>Formats d'export</span>
                </div>
            </x-slot>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

                <div class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700">
                    <span class="text-2xl">📊</span>
                    <div>
                        <p class="font-medium text-gray-900 dark:text-gray-100">Excel (.xlsx)</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Idéal pour l'analyse et les tableaux croisés</p>
                    </div>
                </div>

                <div class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700">
                    <span class="text-2xl">📄</span>
                    <div>
                        <p class="font-medium text-gray-900 dark:text-gray-100">CSV</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Compatible avec tous les tableurs</p>
                    </div>
                </div>

                <div class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700">
                    <span class="text-2xl">📕</span>
                    <div>
                        <p class="font-medium text-gray-900 dark:text-gray-100">PDF</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Parfait pour l'impression et le partage</p>
                    </div>
                </div>

            </div>
        </x-filament::section>

    </div>
</x-filament-panels::page>

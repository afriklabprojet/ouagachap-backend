<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}
        
        <div class="mt-6 flex justify-end">
            <x-filament::button type="submit" size="lg">
                <x-slot name="icon">
                    <x-heroicon-o-check class="w-5 h-5" />
                </x-slot>
                Sauvegarder les paramètres
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>

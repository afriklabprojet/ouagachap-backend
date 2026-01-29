<x-filament-panels::page>
    <form wire:submit.prevent="save">
        {{ $this->form }}
        
        <div class="mt-6 flex justify-end gap-4">
            <x-filament::button type="submit" color="primary" icon="heroicon-o-check">
                Sauvegarder les modifications
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>

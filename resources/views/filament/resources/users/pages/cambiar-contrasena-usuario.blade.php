<x-filament-panels::page>
    <form wire:submit="guardar" class="space-y-6">
        {{ $this->form }}

        <x-filament::button type="submit" icon="heroicon-o-key">
            Actualizar contraseña
        </x-filament::button>
    </form>
</x-filament-panels::page>
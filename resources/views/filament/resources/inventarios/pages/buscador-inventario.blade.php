<x-filament-panels::page>
    <form wire:submit="buscar" class="space-y-6">
        {{ $this->form }}

        <div class="flex justify-end gap-3">
            <x-filament::button
                type="button"
                color="gray"
                wire:click="limpiar"
            >
                Limpiar
            </x-filament::button>

            <x-filament::button
                type="submit"
                icon="heroicon-o-magnifying-glass"
                wire:loading.attr="disabled"
                wire:target="buscar"
            >
                <span wire:loading.remove wire:target="buscar">
                    Buscar
                </span>

                <span wire:loading wire:target="buscar">
                    Buscando...
                </span>
            </x-filament::button>
        </div>
    </form>

    <div class="mt-6">
        {{ $this->table }}
    </div>
</x-filament-panels::page>
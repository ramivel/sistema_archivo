<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section heading="Filtros" description="Los campos marcados con * son obligatorios.">
            <form wire:submit="generarReporte">
                {{ $this->form }}
                <div class="mt-6 flex gap-3">
                    <x-filament::button type="submit" icon="heroicon-o-magnifying-glass">
                        Generar reporte
                    </x-filament::button>
                    <x-filament::button type="button" color="gray" icon="heroicon-o-arrow-path" wire:click="limpiar">
                        Limpiar
                    </x-filament::button>
                    @if ($reporteGenerado)
                        <x-filament::button tag="a" color="success" icon="heroicon-o-document-arrow-down" :href="$this->urlImprimirReporte()" target="_blank">
                            Imprimir reporte PDF
                        </x-filament::button>
                        <x-filament::button tag="a" color="success" icon="heroicon-o-table-cells" :href="$this->urlDescargarReporteExcel()">
                            Descargar Reporte Excel
                        </x-filament::button>
                    @endif
                </div>
            </form>
        </x-filament::section>

        @if ($reporteGenerado)
            <x-filament::section heading="Resultado">
                {{ $this->table }}
            </x-filament::section>
        @endif
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
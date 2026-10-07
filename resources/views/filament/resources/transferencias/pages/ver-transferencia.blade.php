<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section heading="Datos generales">
            {{ $this->datosGenerales }}

            <div class="pt-4">
                <h3 class="text-sm font-semibold">
                    Documentos de la transferencia
                </h3>
                <div class="mt-4 flex flex-wrap gap-3">
                    @if (
                        auth()->user()?->esEncargadoArchivo()
                        && filled($transferencia->archivo_excel)
                    )
                        <x-filament::button
                            tag="a"
                            icon="heroicon-o-arrow-down-tray"
                            href="{{ route('transferencias.documento', [
                                'transferencia' => $transferencia,
                                'tipo' => 'archivo-excel',
                            ]) }}"
                        >
                            Descargar archivo Excel
                        </x-filament::button>
                    @endif
                    @if (filled($transferencia->archivo_nota_rechazo))
                        <x-filament::button
                            type="button"
                            color="warning"
                            icon="heroicon-o-document-text"
                            x-on:click="$dispatch('open-modal', {
                                id: 'ver-nota-rechazo'
                            })"
                        >
                            Ver nota de rechazo
                        </x-filament::button>
                    @endif
                    @if (filled($transferencia->archivo_formulario_firmado))
                        <x-filament::button
                            type="button"
                            icon="heroicon-o-document-text"
                            x-on:click="$dispatch('open-modal', {
                                id: 'ver-formulario-firmado'
                            })"
                        >
                            Ver formulario firmado
                        </x-filament::button>
                    @endif
                </div>
            </div>
        </x-filament::section>

        <x-filament::section heading="Expedientes" collapsible collapsed>
            {{ $this->table }}
        </x-filament::section>

        <x-filament::section heading="Historial de Acciones" collapsible collapsed>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left">
                            <th class="px-3 py-2">ACCIÓN</th>
                            <th class="px-3 py-2">ESTADO ANTERIOR</th>
                            <th class="px-3 py-2">ESTADO NUEVO</th>
                            <th class="px-3 py-2">OBSERVACIÓN</th>
                            <th class="px-3 py-2">USUARIO RESPONSABLE</th>
                            <th class="px-3 py-2">FECHA DE ACCIÓN</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($transferencia->historial->sortBy('fecha_accion') as $historial)
                            <tr class="border-b">
                                <td class="px-3 py-2">
                                    {{ $historial->accion }}
                                </td>
                                <td class="px-3 py-2">
                                    {{ $historial->estadoAnterior?->valor ?? '—' }}
                                </td>
                                <td class="px-3 py-2">
                                    {{ $historial->estadoNuevo?->valor ?? '—' }}
                                </td>
                                <td class="px-3 py-2">
                                    {{ $historial->observacion ?? '—' }}
                                </td>
                                <td class="px-3 py-2">
                                    {{ $historial->usuario?->usuario ?? '—' }}
                                </td>
                                <td class="px-3 py-2">
                                    {{ $historial->fecha_accion?->format('d/m/Y H:i') ?? '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-3 py-4 text-center">
                                    No existen registros en el historial.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>

    @if (filled($transferencia->archivo_nota_rechazo))
        <x-filament::modal
            id="ver-nota-rechazo"
            width="7xl"
        >
            <x-slot name="heading">
                Nota de rechazo
            </x-slot>

            <iframe
                src="{{ route('transferencias.documento', [
                    'transferencia' => $transferencia,
                    'tipo' => 'nota-rechazo',
                ]) }}"
                class="h-[75vh] w-full rounded-lg border"
            ></iframe>
        </x-filament::modal>
    @endif

    @if (filled($transferencia->archivo_formulario_firmado))
        <x-filament::modal
            id="ver-formulario-firmado"
            width="7xl"
        >
            <x-slot name="heading">
                Formulario de transferencia firmado
            </x-slot>

            <iframe
                src="{{ route('transferencias.documento', [
                    'transferencia' => $transferencia,
                    'tipo' => 'formulario-firmado',
                ]) }}"
                class="h-[75vh] w-full rounded-lg border"
            ></iframe>
        </x-filament::modal>
    @endif

    <x-filament-actions::modals />
</x-filament-panels::page>
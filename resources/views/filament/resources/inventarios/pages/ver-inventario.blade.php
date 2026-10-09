<x-filament-panels::page>
    <div class="space-y-6">
        {{ $this->datosGenerales }}

        <x-filament::section heading="Préstamos" collapsible collapsed>
            {{ $this->table }}
        </x-filament::section>

        <x-filament::section heading="Historial" collapsible collapsed>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left">
                            <th class="px-3 py-2">ACCIÓN</th>
                            <th class="px-3 py-2">ESTADO ANTERIOR</th>
                            <th class="px-3 py-2">ESTADO NUEVO</th>
                            <th class="px-3 py-2">OBSERVACIÓN</th>
                            <th class="px-3 py-2">USUARIO</th>
                            <th class="px-3 py-2">FECHA</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse (
                            $expediente->historial
                                ->sortByDesc('fecha_accion')
                            as $historial
                        )
                            <tr class="border-b align-top">
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
                                <td class="px-3 py-2 whitespace-nowrap">
                                    {{ $historial->fecha_accion?->format('d/m/Y H:i') ?? '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-3 py-4 text-center">No existen registros en el historial.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
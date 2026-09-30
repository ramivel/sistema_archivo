<x-filament-panels::page>
    <div class="space-y-6">
        @php
            $historialObservacion = $transferencia->historial->first(fn ($historial) => $historial->accion === 'OBSERVAR');
            $usuarioObservador = $historialObservacion?->usuario;
        @endphp
        <x-filament::section>
            <x-slot name="heading">
                Datos generales
            </x-slot>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <span class="text-sm font-medium text-gray-600">
                        Usuario que reviso
                    </span>
                    <div class="mt-1 rounded-lg bg-gray-100 p-3">
                        {{ $usuarioObservador?->usuario ?? '—' }}
                    </div>
                </div>
                <div>
                    <span class="text-sm font-medium text-gray-600">
                        Fecha inicio solicitud
                    </span>
                    <div class="mt-1 rounded-lg bg-gray-100 p-3">
                        {{ $transferencia->fecha_solicitud?->format('d/m/Y H:i') ?? '—' }}
                    </div>
                </div>
                <div>
                    <span class="text-sm font-medium text-gray-600">
                        Fecha de observación
                    </span>
                    <div class="mt-1 rounded-lg bg-gray-100 p-3">
                        {{ $historialObservacion?->fecha_accion?->format('d/m/Y H:i') ?? '—' }}
                    </div>
                </div>
            </div>
            <div class="mt-4">
                <span class="text-sm font-medium text-gray-600">
                    Observaciones
                </span>
                <div class="mt-1 min-h-24 rounded-lg bg-gray-100 p-4 whitespace-pre-line">
                    {{ $historialObservacion?->observacion ?? 'SIN OBSERVACIONES' }}
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                Expedientes a transferir
            </x-slot>
            {{ $this->table }}
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                Guardar correcciones
            </x-slot>
            <div class="space-y-4">
                <div>
                    <label class="fi-fo-field-wrp-label inline-flex text-sm font-medium">
                        Observaciones
                    </label>
                    <textarea
                        wire:model="observaciones"
                        wire:input="$set('observaciones', $event.target.value.toUpperCase())"
                        rows="4"
                        maxlength="5000"
                        class="fi-input mt-1 block w-full rounded-lg border-none bg-white px-3 py-2 text-sm shadow-sm ring-1 ring-gray-950/10 focus:ring-2 focus:ring-primary-600"
                    ></textarea>
                </div>
                <div class="flex justify-end">
                    <x-filament::button
                        type="button"
                        icon="heroicon-o-paper-airplane"
                        wire:click="guardarCorreccion"
                        wire:loading.attr="disabled"
                        wire:target="guardarCorreccion"
                    >
                        <span wire:loading.remove wire:target="guardarCorreccion">
                            Guardar y enviar transferencia
                        </span>
                        <span wire:loading wire:target="guardarCorreccion">
                            Guardando...
                        </span>
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>
    </div>
    <x-filament-actions::modals />
</x-filament-panels::page>
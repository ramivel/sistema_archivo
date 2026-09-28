<x-filament-panels::page>
    <form wire:submit="validarArchivo">
        {{ $this->form }}
        <div class="mt-3 flex justify-end">
            <x-filament::button type="submit" wire:loading.attr="disabled" wire:target="validarArchivo">
                <span wire:loading.remove wire:target="validarArchivo" class="flex items-center gap-x-2">
                    <x-filament::icon icon="heroicon-o-check" class="h-5 w-5" />
                    Validar archivo
                </span>
                <span wire:loading.flex wire:target="validarArchivo" class="items-center gap-x-2">
                    Validando archivo...
                </span>
            </x-filament::button>
        </div>
    </form>
     @if ($archivoValidado && $resultadoValidacion)
        <x-filament::section class="mt-3">
            <x-slot name="heading">
                Resumen de validación
            </x-slot>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="rounded-lg border p-4">
                    <div class="text-sm text-gray-500">
                        TOTAL REGISTROS
                    </div>
                    <div class="mt-1 text-2xl font-bold">
                        {{ number_format($resultadoValidacion['total_registros']) }}
                    </div>
                </div>
                <div class="rounded-lg border p-4">
                    <div class="text-sm text-gray-500">
                        REGISTROS VÁLIDOS
                    </div>
                    <div class="mt-1 text-2xl font-bold text-success-600">
                        {{ number_format($resultadoValidacion['total_validos']) }}
                    </div>
                </div>
                <div class="rounded-lg border p-4">
                    <div class="text-sm text-gray-500">
                        REGISTROS INVÁLIDOS
                    </div>
                    <div class="mt-1 text-2xl font-bold text-danger-600">
                        {{ number_format($resultadoValidacion['total_invalidos']) }}
                    </div>
                </div>
            </div>
        </x-filament::section>
    @endif

    @if (
        $archivoValidado
        && $resultadoValidacion
        && ! empty($resultadoValidacion['errores_generales'])
    )
        <x-filament::section class="mt-3">
            <x-slot name="heading">
                Errores generales del archivo
            </x-slot>
            <div class="space-y-2">
                @foreach ($resultadoValidacion['errores_generales'] as $error)
                    <div class="rounded-lg border border-danger-200 bg-danger-50 p-3 text-sm text-danger-700">
                        {{ $error }}
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    @endif

    @if (
        $archivoValidado
        && $resultadoValidacion
        && ! empty($resultadoValidacion['errores'])
    )
        <x-filament::section class="mt-3">
            <x-slot name="heading">
                Expedientes inválidos
            </x-slot>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left">
                            <th class="px-3 py-2">
                                N°
                            </th>
                            <th class="px-3 py-2">
                                N° DE CELDA
                            </th>
                            <th class="px-3 py-2">
                                ERRORES
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($resultadoValidacion['errores'] as $error)
                            <tr class="border-b">
                                <td class="px-3 py-2">
                                    {{ $error['numero'] }}
                                </td>
                                <td class="px-3 py-2">
                                    {{ $error['celda'] }}
                                </td>
                                <td class="px-3 py-2">
                                    {{ $error['errores'] }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endif

    @if (
        $archivoValidado
        && $resultadoValidacion
        && empty($resultadoValidacion['errores_generales'])
        && $resultadoValidacion['total_invalidos'] === 0
    )
        <x-filament::section class="mt-3">
            <x-slot name="heading">
                Guardar regularización
            </x-slot>
            <div class="space-y-4">
                <div>
                    <label class="fi-fo-field-wrp-label inline-flex items-center gap-x-3 text-sm font-medium leading-6">
                        <span>
                            Observaciones *
                        </span>
                    </label>
                    <textarea
                        wire:model="observaciones"
                        wire:input="$set('observaciones', $event.target.value.toUpperCase())"
                        rows="3"
                        maxlength="5000"
                        placeholder="SIN OBSERVACIONES"
                        class="fi-input block w-full rounded-lg border-none bg-white px-3 py-2 text-sm text-gray-950 shadow-sm ring-1 ring-gray-950/10 focus:ring-2 focus:ring-primary-600 dark:bg-white/5 dark:text-white dark:ring-white/20"
                    ></textarea>
                </div>
                <div class="flex justify-end">
                    <x-filament::button
                        type="button"
                        icon="heroicon-o-arrow-down-tray"
                        wire:click="guardarRegularizacion"
                        wire:loading.attr="disabled"
                        wire:target="guardarRegularizacion"
                    >
                        <span wire:loading.remove wire:target="guardarRegularizacion">
                            Guardar Regularización
                        </span>
                        <span wire:loading wire:target="guardarRegularizacion">
                            Guardando...
                        </span>
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section class="mt-3">
            <x-slot name="heading">
                Expedientes a regularizar
            </x-slot>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left">
                            <th class="px-3 py-2">N°</th>
                            <th class="px-3 py-2">CÓDIGO DE REFERENCIA</th>
                            <th class="px-3 py-2">N° DE CAJA</th>
                            <th class="px-3 py-2">PROCEDENCIA</th>
                            <th class="px-3 py-2">SERIE DOCUMENTAL</th>
                            <th class="px-3 py-2">DESCRIPCIÓN DOCUMENTAL</th>
                            <th class="px-3 py-2">SOPORTE</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($resultadoValidacion['expedientes'] as $expediente)
                            <tr class="border-b align-top">
                                <td class="px-3 py-2">
                                    {{ $expediente['numero'] }}
                                </td>
                                <td class="px-3 py-2">
                                    {{ $expediente['codigo_referencia'] }}
                                </td>
                                <td class="px-3 py-2">
                                    {{ $expediente['numero_caja'] }}
                                </td>
                                <td class="px-3 py-2">
                                    {{ implode(', ', $expediente['procedencias']) }}
                                </td>
                                <td class="px-3 py-2">
                                    {{ $expediente['serie_documental'] }}
                                </td>
                                <td class="px-3 py-2">
                                    {{ $expediente['descripcion_lomo'] }}
                                </td>
                                <td class="px-3 py-2">
                                    {{ $expediente['soporte'] }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
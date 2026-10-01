<x-filament-panels::page>
    <div class="space-y-6">
        {{ $this->datosGenerales }}

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
                        Observaciones *
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
                    <x-filament::button type="button" icon="heroicon-o-paper-airplane" x-on:click="$dispatch('open-modal', { id: 'confirmar-guardar-correccion' })">
                        Guardar Transferencia
                    </x-filament::button>
                    <x-filament::modal id="confirmar-guardar-correccion">
                        <x-slot name="heading">Confirmar corrección</x-slot>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            ¿Está seguro de guardar y enviar las correcciones de esta transferencia?
                        </p>
                        <x-slot name="footer">
                            <div class="flex justify-end gap-x-3">
                                <x-filament::button color="gray" x-on:click="$dispatch('close-modal', { id: 'confirmar-guardar-correccion' })">
                                    Cancelar
                                </x-filament::button>
                                <x-filament::button
                                    icon="heroicon-o-check"
                                    wire:click="guardarCorreccion"
                                    wire:loading.attr="disabled"
                                    wire:target="guardarCorreccion"
                                >
                                    <span wire:loading.remove wire:target="guardarCorreccion">
                                        Confirmar y guardar
                                    </span>
                                    <span wire:loading wire:target="guardarCorreccion">
                                        Guardando...
                                    </span>
                                </x-filament::button>
                            </div>
                        </x-slot>
                    </x-filament::modal>
                </div>
            </div>
        </x-filament::section>
    </div>
    <x-filament-actions::modals />
</x-filament-panels::page>
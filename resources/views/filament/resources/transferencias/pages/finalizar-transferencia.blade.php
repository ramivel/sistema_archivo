<x-filament-panels::page>
    <div class="space-y-6">
        {{ $this->datosGenerales }}

        <x-filament::section>
            <x-slot name="heading">
                Expedientes de la transferencia
            </x-slot>
            {{ $this->table }}
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                Finalizar transferencia
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
                    <x-filament::button type="button" icon="heroicon-o-cloud-arrow-up" x-on:click="$dispatch('open-modal', { id: 'confirmar-finalizar-transferencia' })">
                        Finalizar transferencia
                    </x-filament::button>
                    <x-filament::modal id="confirmar-finalizar-transferencia">
                        <x-slot name="heading">Confirmar finalización</x-slot>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            ¿Está seguro de finalizar esta transferencia?
                        </p>
                        <x-slot name="footer">
                            <div class="flex justify-end gap-x-3">
                                <x-filament::button color="gray" x-on:click="$dispatch('close-modal', { id: 'confirmar-finalizar-transferencia' })">
                                    Cancelar
                                </x-filament::button>
                                <x-filament::button
                                    icon="heroicon-o-check"
                                    wire:click="finalizar"
                                    wire:loading.attr="disabled"
                                    wire:target="finalizar"
                                >
                                    <span wire:loading.remove wire:target="finalizar">
                                        Confirmar y finalizar
                                    </span>
                                    <span wire:loading wire:target="finalizar">
                                        Finalizando...
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
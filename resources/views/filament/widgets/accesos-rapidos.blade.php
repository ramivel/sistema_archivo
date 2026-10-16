
<x-filament-widgets::widget>
    <div class="space-y-4">
        <h2 class="text-[16px] font-semibold tracking-tight text-gray-950 dark:text-white">
            Accesos rápidos
        </h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($accesos as $acceso)
                <div class="col-span-1">
                    @if (! empty($acceso['url']))
                        <x-filament::button
                            href="{{ $acceso['url'] }}"
                            tag="a"
                            icon="{{ $acceso['icono'] }}"
                            size="xl"
                            color="gray"
                            title="{{ $acceso['descripcion'] }}"
                            class="w-full"
                        >
                            {{ $acceso['titulo'] }}
                        </x-filament::button>
                    @else
                        <x-filament::button
                            icon="{{ $acceso['icono'] }}"
                            size="xl"
                            color="gray"
                            title="{{ $acceso['descripcion'] }}"
                            disabled
                            class="w-full"
                        >
                            {{ $acceso['titulo'] }}
                        </x-filament::button>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</x-filament-widgets::widget>

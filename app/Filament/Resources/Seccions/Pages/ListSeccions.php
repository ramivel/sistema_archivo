<?php

namespace App\Filament\Resources\Seccions\Pages;

use App\Filament\Resources\Fondos\FondoResource;
use App\Filament\Resources\Subfondos\SubfondoResource;
use App\Filament\Resources\Seccions\SeccionResource;
use App\Models\Parametro;
use Filament\Resources\Pages\ListRecords;

class ListSeccions extends ListRecords
{
    protected static string $resource = SeccionResource::class;
    public Parametro $subfondo;

    public function mount(mixed $subfondo = null): void
    {
        abort_if($subfondo === null, 404);
        $guid = $subfondo instanceof Parametro
            ? $subfondo->guid
            : $subfondo;
        $this->subfondo = Parametro::query()
            ->where('grupo', 'SUBFONDO')
            ->where('guid', $guid)
            ->whereNull('fecha_eliminacion')
            ->firstOrFail();
        session()->put('seccion_subfondo_guid',$this->subfondo->guid);
        session()->put('seccion_subfondo_expires_at',now()->addMinutes(15));
        parent::mount();
    }

    public function getHeading(): string
    {
        return $this->subfondo->valor . ' - Listado Secciones/Áreas';
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getBreadcrumbs(): array
    {
        return [
            FondoResource::getUrl('index') => 'Fondos/Oficinas',
            SubfondoResource::getUrl('index', [
                'fondo' => $this->subfondo->padre?->guid,
            ]) => 'Sub Fondos/Direcciones',
            '#' => 'Secciones/Áreas',
            '' => 'Listado',
        ];
    }
}
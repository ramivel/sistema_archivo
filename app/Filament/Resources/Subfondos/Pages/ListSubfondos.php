<?php

namespace App\Filament\Resources\Subfondos\Pages;

use App\Filament\Resources\Fondos\FondoResource;
use App\Filament\Resources\Subfondos\SubfondoResource;
use App\Models\Parametro;
use Filament\Resources\Pages\ListRecords;

class ListSubfondos extends ListRecords
{
    protected static string $resource = SubfondoResource::class;
    public Parametro $fondo;

    public function mount(mixed $fondo = null): void
    {
        abort_if($fondo === null, 404);
        $guid = $fondo instanceof Parametro
            ? $fondo->guid
            : $fondo;
        $this->fondo = Parametro::query()
            ->where('grupo', 'FONDO')
            ->where('guid', $guid)
            ->whereNull('fecha_eliminacion')
            ->firstOrFail();
        session()->put('subfondo_fondo_guid', $this->fondo->guid);
        session()->put('subfondo_fondo_expires_at', now()->addMinutes(15));
        parent::mount();
    }

    public function getHeading(): string
    {
        return $this->fondo->valor . ' - Listado Sub Fondo/Direcciones';
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getBreadcrumbs(): array
    {
        return [
            FondoResource::getUrl('index') => 'Fondos/Oficinas',
            '#' => 'Sub Fondos/Direcciones',
            '' => 'Listado',
        ];
    }
}
<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Inventarios\InventarioResource;
use App\Filament\Resources\Transferencias\TransferenciaResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class AccesosRapidos extends Widget
{
    protected string $view = 'filament.widgets.accesos-rapidos';
    protected static ?int $sort = -100;
    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        $usuario = User::find(Auth::id());
        return $usuario !== null && (
            $usuario->esAdministrador()
            || $usuario->esEncargadoArchivo()
            || $usuario->esTransferencias()
            || $usuario->esConsultas()
        );
    }

    protected function getViewData(): array
    {
        $usuario = User::find(Auth::id());
        $accesos = [];
        if ($usuario === null) {
            return ['accesos' => []];
        }
        if ($usuario->esAdministrador()) {
            $accesos[] = [
                'titulo' => 'Nuevo usuario',
                'descripcion' => 'Registrar y administrar usuarios del sistema.',
                'icono' => 'heroicon-o-user-plus',
                'url' => UserResource::getUrl('index'),
            ];
        }
        if ($usuario->esEncargadoArchivo() || $usuario->esTransferencias()) {
            $accesos[] = [
                'titulo' => 'Nueva transferencia',
                'descripcion' => 'Registrar una solicitud de transferencia documental.',
                'icono' => 'heroicon-o-arrow-up-tray',
                'url' => TransferenciaResource::getUrl('create'),
            ];
        }
        if ($usuario->esEncargadoArchivo() || $usuario->esConsultas()) {
            $accesos[] = [
                'titulo' => 'Buscador de inventario',
                'descripcion' => 'Consultar expedientes del inventario documental.',
                'icono' => 'heroicon-o-magnifying-glass',
                'url' => InventarioResource::getUrl('buscador'),
            ];
            $accesos[] = [
                'titulo' => 'Buscador de biblioteca',
                'descripcion' => 'Acceso previsto para una futura funcionalidad.',
                'icono' => 'heroicon-o-book-open',
                'url' => null,
                'proximamente' => true,
            ];
        }
        return ['accesos' => $accesos];
    }
}

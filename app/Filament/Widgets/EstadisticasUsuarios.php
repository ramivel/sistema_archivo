<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class EstadisticasUsuarios extends StatsOverviewWidget
{
    protected static ?int $sort = -90;
    public static function canView(): bool
    {
        $usuario = User::find(Auth::id());
        return $usuario !== null && $usuario->esAdministrador();
    }

    protected function getHeading(): ?string
    {
        return 'Estadísticas de usuarios';
    }

    protected function getStats(): array
    {
        $usuarios = User::query();
        $total = (clone $usuarios)->count();
        $activos = (clone $usuarios)->where('activo', true)->count();
        $inactivos = (clone $usuarios)->where('activo', false)->count();
        return [
            Stat::make('Total de usuarios', number_format($total))
                ->description('Usuarios no eliminados')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
            Stat::make('Usuarios activos', number_format($activos))
                ->description('Con acceso habilitado')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
            Stat::make('Usuarios inactivos', number_format($inactivos))
                ->description('Con acceso deshabilitado')
                ->descriptionIcon('heroicon-m-no-symbol')
                ->color('gray'),
        ];
    }
}

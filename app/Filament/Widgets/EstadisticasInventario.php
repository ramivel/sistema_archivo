<?php

namespace App\Filament\Widgets;

use App\Models\InventarioExpediente;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class EstadisticasInventario extends StatsOverviewWidget
{
    protected static ?int $sort = -80;

    public static function canView(): bool
    {
        $usuario = User::find(Auth::id());
        return $usuario !== null && $usuario->esEncargadoArchivo();
    }

    protected function getHeading(): ?string
    {
        return 'Resumen del inventario';
    }

    protected function getStats(): array
    {
        $total = InventarioExpediente::query()->whereNull('fecha_eliminacion')->count();
        $conteos = DB::table('inventario.inventario_expedientes as ie')
            ->join('parametros as p', 'p.id', '=', 'ie.estado_parametro_id')
            ->whereNull('ie.fecha_eliminacion')
            ->whereNull('p.fecha_eliminacion')
            ->where('p.grupo', 'ESTADO_EXPEDIENTE')
            ->select('p.valor', DB::raw('COUNT(ie.id) AS total'))
            ->groupBy('p.valor')
            ->pluck('total', 'valor')
            ->map(fn ($cantidad): int => (int) $cantidad);
        foreach (['DISPONIBLE', 'PRESTADO', 'BAJA'] as $estado) {
            $conteos[$estado] = (int) ($conteos[$estado] ?? 0);
        }
        return [
            Stat::make('Total de expedientes', number_format($total))
                ->description('Expedientes registrados en el inventario')
                ->descriptionIcon('heroicon-m-archive-box')
                ->color('primary'),
            Stat::make('Disponibles', number_format($conteos['DISPONIBLE']))
                ->description('Disponibles para consulta o préstamo')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
            Stat::make('Prestados', number_format($conteos['PRESTADO']))
                ->description('Expedientes en préstamo')
                ->descriptionIcon('heroicon-m-arrow-left-start-on-rectangle')
                ->color('warning'),
            Stat::make('De baja', number_format($conteos['BAJA']))
                ->description('Expedientes dados de baja')
                ->descriptionIcon('heroicon-m-archive-box-x-mark')
                ->color('gray'),
        ];
    }
}

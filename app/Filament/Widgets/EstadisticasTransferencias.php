<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Soporte\DashboardConsultas;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EstadisticasTransferencias extends StatsOverviewWidget
{
    protected static ?int $sort = -70;
    protected int | string | array $columnSpan = 'full';
    protected int | array | null $columns = 4;

    public static function canView(): bool
    {
        $usuario = User::find(Auth::id());
        return $usuario !== null && ($usuario->esEncargadoArchivo() || $usuario->esTransferencias());
    }

    protected function getHeading(): ?string
    {
        $usuario = User::find(Auth::id());
        return $usuario?->esEncargadoArchivo()
            ? 'Resumen de transferencias'
            : 'Resumen de mis transferencias';
    }

    protected function getStats(): array
    {
        $base = DashboardConsultas::transferenciasBase();
        $total = (clone $base)->count('t.id');
        $porEstado = DashboardConsultas::transferenciasBase()
            ->join('parametros as p', 'p.id', '=', 't.estado_parametro_id')
            ->whereNull('p.fecha_eliminacion')
            ->where('p.grupo', 'ESTADO_TRANSFERENCIA')
            ->select('p.valor', DB::raw('COUNT(t.id) AS total'))
            ->groupBy('p.valor')
            ->pluck('total', 'valor')
            ->map(fn ($cantidad): int => (int) $cantidad);
        $contar = static fn (array $estados): int => (int) $porEstado
            ->only($estados)
            ->sum();
        return [
            Stat::make('Total', number_format($total))
                ->description('Transferencias no eliminadas')
                ->descriptionIcon('heroicon-m-rectangle-stack')
                ->color('primary'),
            Stat::make('Iniciado / Corregido', number_format($contar(['INICIADO', 'CORREGIDO'])))
                ->description('Pendientes de revisión o nueva revisión')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('info'),
            Stat::make('Observado', number_format($contar(['OBSERVADO'])))
                ->description('Requieren correcciones')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('warning'),
            Stat::make('Aprobado', number_format($contar(['APROBADO'])))
                ->description('Transferencias aprobadas')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
            Stat::make('Rechazado / Anulado', number_format($contar(['RECHAZADO', 'ANULADO'])))
                ->description('Transferencias rechazadas o anuladas')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),
            Stat::make('Solicitud de anulación', number_format($contar(['SOLICITUD DE ANULACIÓN'])))
                ->description('Pendientes de resolución')
                ->descriptionIcon('heroicon-m-no-symbol')
                ->color('warning'),
            Stat::make('Finalizado', number_format($contar(['FINALIZADO'])))
                ->description('Recepción física concluida')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color('success'),
            Stat::make('Inventariado', number_format($contar(['INVENTARIADO'])))
                ->description('Expedientes incorporados al inventario')
                ->descriptionIcon('heroicon-m-archive-box')
                ->color('success'),
        ];
    }
}

<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Soporte\DashboardConsultas;
use App\Models\User;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GraficoEstadosTransferencias extends ChartWidget
{
    protected static ?int $sort = -69;
    protected ?string $heading = 'Transferencias por estado';
    protected int | string | array $columnSpan = 1;

    public static function canView(): bool
    {
        $usuario = User::find(Auth::id());
        return $usuario !== null && ($usuario->esEncargadoArchivo() || $usuario->esTransferencias());
    }

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getData(): array
    {
        $filas = DashboardConsultas::transferenciasBase()
            ->join('parametros as p', 'p.id', '=', 't.estado_parametro_id')
            ->whereNull('p.fecha_eliminacion')
            ->where('p.grupo', 'ESTADO_TRANSFERENCIA')
            ->selectRaw("CASE
                WHEN p.valor IN ('INICIADO', 'CORREGIDO') THEN 'Iniciado / Corregido'
                WHEN p.valor IN ('RECHAZADO', 'ANULADO') THEN 'Rechazado / Anulado'
                ELSE p.valor
            END AS label, COUNT(t.id) AS total")
            ->groupByRaw("CASE
                WHEN p.valor IN ('INICIADO', 'CORREGIDO') THEN 'Iniciado / Corregido'
                WHEN p.valor IN ('RECHAZADO', 'ANULADO') THEN 'Rechazado / Anulado'
                ELSE p.valor
            END")
            ->orderBy('label')
            ->get();
        return [
            'datasets' => [[
                'label' => 'Transferencias',
                'data' => $filas->pluck('total')->map(fn ($total): int => (int) $total)->all(),
            ]],
            'labels' => $filas->pluck('label')->all(),
        ];
    }
}

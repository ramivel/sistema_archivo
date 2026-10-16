<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Soporte\DashboardConsultas;
use App\Models\User;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GraficoTransferenciasFondoSubfondo extends ChartWidget
{
    protected static ?int $sort = -68;
    protected ?string $heading = 'Transferencias por Fondo / Subfondo';
    protected ?string $maxHeight = '360px';
    protected int | string | array $columnSpan = 1;

    public static function canView(): bool
    {
        $usuario = User::find(Auth::id());
        return $usuario !== null && $usuario->esEncargadoArchivo();
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['display' => false],
            ],
            'scales' => [
                'x' => ['beginAtZero' => true],
            ],
        ];
    }

    protected function getData(): array
    {
        $filas = DashboardConsultas::transferenciasBase()
            ->join('parametros as f', 'f.id', '=', 't.fondo_parametro_id')
            ->join('parametros as sf', 'sf.id', '=', 't.subfondo_parametro_id')
            ->whereNull('f.fecha_eliminacion')
            ->whereNull('sf.fecha_eliminacion')
            ->where('f.grupo', 'FONDO')
            ->where('sf.grupo', 'SUBFONDO')
            ->selectRaw("CONCAT(f.sigla, ' / ', sf.sigla) AS label, COUNT(t.id) AS total")
            ->groupBy('f.sigla', 'sf.sigla')
            ->orderByDesc('total')
            ->get();
        return [
            'datasets' => [[
                'label' => 'Transferencias',
                'data' => $filas->pluck('total')->map(fn ($total) => (int) $total)->all(),
            ]],
            'labels' => $filas->pluck('label')->all(),
        ];
    }
}

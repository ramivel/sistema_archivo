<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Soporte\DashboardConsultas;
use App\Models\User;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GraficoInventarioFondoSubfondo extends ChartWidget
{
    protected static ?int $sort = -78;
    protected ?string $heading = 'Inventario por Fondo / Subfondo';
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
        $filas = DB::table('inventario.inventario_expedientes as ie')
            ->join('parametros as f', 'f.id', '=', 'ie.oficina_parametro_id')
            ->join('parametros as sf', 'sf.id', '=', 'ie.direccion_parametro_id')
            ->whereNull('ie.fecha_eliminacion')
            ->whereNull('f.fecha_eliminacion')
            ->whereNull('sf.fecha_eliminacion')
            ->where('f.grupo', 'FONDO')
            ->where('sf.grupo', 'SUBFONDO')
            ->selectRaw("CONCAT(f.sigla, ' / ', sf.sigla) AS label, COUNT(ie.id) AS total")
            ->groupBy('f.sigla', 'sf.sigla')
            ->orderByDesc('total')
            ->get();
        return [
            'datasets' => [[
                'label' => 'Expedientes',
                'data' => $filas->pluck('total')->map(fn ($total) => (int) $total)->all(),
            ]],
            'labels' => $filas->pluck('label')->all(),
        ];
    }
}

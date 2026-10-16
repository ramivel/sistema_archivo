<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Soporte\DashboardConsultas;
use App\Models\User;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GraficoInventarioSerieDocumental extends ChartWidget
{
    protected static ?int $sort = -77;
    protected ?string $heading = 'Inventario por serie documental';
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
            'indexAxis' => 'y',
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
            ->join('parametros as s', 's.id', '=', 'ie.serie_documental_parametro_id')
            ->whereNull('ie.fecha_eliminacion')
            ->whereNull('s.fecha_eliminacion')
            ->where('s.grupo', 'SERIE_DOCUMENTAL')
            ->selectRaw('s.valor AS label, COUNT(ie.id) AS total')
            ->groupBy('s.valor')
            ->orderByDesc('total')
            ->get();
        $datos = DashboardConsultas::topDiezMasOtros($filas);
        return [
            'datasets' => [[
                'label' => 'Expedientes',
                'data' => $datos['data'],
            ]],
            'labels' => $datos['labels'],
        ];
    }
}

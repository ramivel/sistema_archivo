<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GraficoEstadosInventario extends ChartWidget
{
    protected static ?int $sort = -79;
    protected ?string $heading = 'Expedientes por estado';
    protected int | string | array $columnSpan = 1;

    public static function canView(): bool
    {
        $usuario = User::find(Auth::id());
        return $usuario !== null && $usuario->esEncargadoArchivo();
    }

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getData(): array
    {
        $conteos = DB::table('inventario.inventario_expedientes as ie')
            ->join('parametros as p', 'p.id', '=', 'ie.estado_parametro_id')
            ->whereNull('ie.fecha_eliminacion')
            ->whereNull('p.fecha_eliminacion')
            ->where('p.grupo', 'ESTADO_EXPEDIENTE')
            ->select('p.valor as label', DB::raw('COUNT(ie.id) as total'))
            ->groupBy('p.valor')
            ->orderBy('p.valor')
            ->get();
        return [
            'datasets' => [[
                'label' => 'Expedientes',
                'data' => $conteos->pluck('total')->map(fn ($total): int => (int) $total)->all(),
            ]],
            'labels' => $conteos->pluck('label')->all(),
        ];
    }
}

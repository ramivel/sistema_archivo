<?php

namespace App\Filament\Pages\Reportes\Tables;

use App\Models\InventarioExpediente;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReporteInventarioTable
{
    public static function configure(
        Table $table,
        array $filtros = [],
        bool $reporteGenerado = false
    ): Table {
        return $table
            ->query(
                self::query(
                    filtros: $filtros,
                    reporteGenerado: $reporteGenerado
                )
            )
            ->columns([
                TextColumn::make('estado.valor')
                    ->label('ESTADO')
                    ->badge()
                    ->alignCenter()
                    ->searchable(),
                TextColumn::make('codigo_inventario')
                    ->label('CÓDIGO INVENTARIO')
                    ->searchable(),
                TextColumn::make('codigo_referencia')
                    ->label('CÓDIGO REFERENCIA'),
                TextColumn::make('numero_caja')
                    ->label('CAJA')
                    ->placeholder('—'),
                TextColumn::make('procedencia')
                    ->label('PROCEDENCIA')
                    ->limit(40),
                TextColumn::make('serieDocumental.valor')
                    ->label('SERIE DOCUMENTAL'),
                TextColumn::make('descripcion_lomo')
                    ->label('DESCRIPCIÓN DOCUMENTAL')
                    ->limit(60),
                TextColumn::make('fechas_extremas')
                    ->label('FECHAS EXTREMAS')
                    ->alignCenter(),
                TextColumn::make('soporte.valor')
                    ->label('SOPORTE'),
            ])
            ->defaultSort('id', 'asc')
            ->paginated([50, 100])
            ->defaultPaginationPageOption(50)
            ->striped()
            ->recordUrl(null);
    }

    private static function query(
        array $filtros,
        bool $reporteGenerado
    ): Builder {
        $query = InventarioExpediente::query()
            ->with([
                'oficina',
                'direccion',
                'area',
                'serieDocumental',
                'soporte',
                'estado',
            ]);
        if (! $reporteGenerado) {
            return $query->whereRaw('1 = 0');
        }
        foreach ([
            'oficina_parametro_id',
            'direccion_parametro_id',
            'area_parametro_id',
        ] as $campo) {
            if (filled($filtros[$campo] ?? null)) {
                $query->where(
                    $campo,
                    $filtros[$campo]
                );
            }
        }
        return $query;
    }
}
<?php

namespace App\Filament\Resources\Inventarios\Tables;

use App\Models\InventarioExpediente;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Tables\Enums\RecordActionsPosition;

class BuscadorInventarioTable
{
    public static function configure(
        Table $table,
        array $filtros = [],
        bool $busquedaEjecutada = false
    ): Table {
        return $table
            ->query(
                self::query(
                    filtros: $filtros,
                    busquedaEjecutada: $busquedaEjecutada
                )
            )
            ->columns([
                Tables\Columns\TextColumn::make('estado.valor')
                    ->label('ESTADO')
                    ->badge()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('codigo_inventario')
                    ->label('CÓDIGO INVENTARIO')
                    ->sortable(),
                Tables\Columns\TextColumn::make('numero_caja')
                    ->label('N° CAJA')
                    ->placeholder('—')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('codigo_referencia')
                    ->label('CÓDIGO REFERENCIA'),
                Tables\Columns\TextColumn::make('procedencia')
                    ->label('PROCEDENCIA')
                    ->limit(30),
                Tables\Columns\TextColumn::make(
                    'serieDocumental.valor'
                )
                    ->label('SERIE DOCUMENTAL'),
                Tables\Columns\TextColumn::make('descripcion_lomo')
                    ->label('DESCRIPCIÓN DOCUMENTAL')
                    ->limit(60),
                Tables\Columns\TextColumn::make('detalle')
                    ->label('DETALLE')
                    ->limit(60),
                Tables\Columns\TextColumn::make('tomo_volumen')
                    ->label('TOMO')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('fechas_extremas')
                    ->label('FECHAS EXTREMAS')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('soporte.valor')
                    ->label('SOPORTE'),
            ])
            ->recordActions([
                self::verAction(),
            ])
            ->recordActionsPosition(
                RecordActionsPosition::BeforeColumns
            )
            ->defaultSort('fecha_creacion', 'desc')
            ->paginated([50, 100])
            ->defaultPaginationPageOption(50)
            ->striped()
            ->recordUrl(null);
    }

    private static function query(
        array $filtros,
        bool $busquedaEjecutada
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

        if (! $busquedaEjecutada) {
            return $query->whereRaw('1 = 0');
        }

        foreach ([
            'oficina_parametro_id',
            'direccion_parametro_id',
            'area_parametro_id',
            'serie_documental_parametro_id',
            'soporte_parametro_id',
        ] as $campo) {
            if (filled($filtros[$campo] ?? null)) {
                $query->where($campo, $filtros[$campo]);
            }
        }

        $campo = $filtros['campo'] ?? null;
        $texto = trim((string) ($filtros['texto'] ?? ''));

        $camposPermitidos = [
            'codigo_inventario',
            'codigo_referencia',
            'numero_caja',
            'procedencia',
            'descripcion_lomo',
            'detalle',
            'fechas_extremas',
        ];

        if (
            filled($texto)
            && in_array($campo, $camposPermitidos, true)
        ) {
            $query->where(
                $campo,
                'ILIKE',
                "%{$texto}%"
            );
        }

        return $query;
    }

    private static function verAction(): Action
    {
        return Action::make('ver')
            ->label('Ver')
            ->icon('heroicon-o-eye')
            ->color('gray')
            ->schema([
                Section::make('Información del expediente')
                    ->schema([
                        TextEntry::make('codigo_inventario')
                            ->label('Código de inventario')
                            ->state(
                                fn (InventarioExpediente $record): string =>
                                    $record->codigo_inventario
                            ),

                        TextEntry::make('estado')
                            ->label('Estado')
                            ->state(
                                fn (InventarioExpediente $record): string =>
                                    $record->estado?->valor ?? '—'
                            ),

                        TextEntry::make('origen')
                            ->label('Origen')
                            ->state(
                                fn (InventarioExpediente $record): string =>
                                    $record->origen ?? '—'
                            ),

                        TextEntry::make('oficina')
                            ->label('Oficina')
                            ->state(
                                fn (InventarioExpediente $record): string =>
                                    $record->oficina?->valor ?? '—'
                            ),

                        TextEntry::make('direccion')
                            ->label('Dirección')
                            ->state(
                                fn (InventarioExpediente $record): string =>
                                    $record->direccion?->valor ?? '—'
                            ),

                        TextEntry::make('area')
                            ->label('Área')
                            ->state(
                                fn (InventarioExpediente $record): string =>
                                    $record->area?->valor ?? '—'
                            ),

                        TextEntry::make('numero_caja')
                            ->label('Número de caja')
                            ->state(
                                fn (InventarioExpediente $record): string =>
                                    $record->numero_caja ?? '—'
                            ),

                        TextEntry::make('codigo_referencia')
                            ->label('Código de referencia')
                            ->state(
                                fn (InventarioExpediente $record): string =>
                                    $record->codigo_referencia
                            ),

                        TextEntry::make('procedencia')
                            ->label('Procedencia')
                            ->state(
                                fn (InventarioExpediente $record): string =>
                                    $record->procedencia
                            ),

                        TextEntry::make('serie_documental')
                            ->label('Serie documental')
                            ->state(
                                fn (InventarioExpediente $record): string =>
                                    $record->serieDocumental?->valor ?? '—'
                            ),

                        TextEntry::make('soporte')
                            ->label('Soporte')
                            ->state(
                                fn (InventarioExpediente $record): string =>
                                    $record->soporte?->valor ?? '—'
                            ),

                        TextEntry::make('tomo_volumen')
                            ->label('Tomo / volumen')
                            ->state(
                                fn (InventarioExpediente $record): string =>
                                    (string) (
                                        $record->tomo_volumen ?? '—'
                                    )
                            ),

                        TextEntry::make('fojas')
                            ->label('Fojas')
                            ->state(
                                fn (InventarioExpediente $record): string =>
                                    $record->fojas ?? '—'
                            ),

                        TextEntry::make('fechas_extremas')
                            ->label('Fechas extremas')
                            ->state(
                                fn (InventarioExpediente $record): string =>
                                    $record->fechas_extremas ?? '—'
                            ),

                        TextEntry::make('descripcion_lomo')
                            ->label('Descripción documental')
                            ->state(
                                fn (InventarioExpediente $record): string =>
                                    $record->descripcion_lomo ?? '—'
                            )
                            ->columnSpanFull(),

                        TextEntry::make('detalle')
                            ->label('Detalle')
                            ->state(
                                fn (InventarioExpediente $record): string =>
                                    $record->detalle ?? '—'
                            )
                            ->columnSpanFull(),

                        TextEntry::make('observaciones')
                            ->label('Observaciones')
                            ->state(
                                fn (InventarioExpediente $record): string =>
                                    $record->observaciones ?? '—'
                            )
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
            ])
            ->modalHeading(
                fn (InventarioExpediente $record): string =>
                    "Expediente {$record->codigo_inventario}"
            )
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar');
    }
}
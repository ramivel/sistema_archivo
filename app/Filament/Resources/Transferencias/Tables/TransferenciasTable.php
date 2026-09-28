<?php

namespace App\Filament\Resources\Transferencias\Tables;

use Filament\Tables;
use Filament\Tables\Table;

class TransferenciasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero')
                    ->label('N°')
                    ->rowIndex()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('correlativo')
                    ->label('CORRELATIVO')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('fecha_solicitud')
                    ->label('FECHA INICIO TRANSFERENCIA')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('fecha_observacion')
                    ->label('FECHA OBSERVACIÓN')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('fecha_aprobacion')
                    ->label('FECHA APROBACIÓN')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('fecha_finalizacion')
                    ->label('FECHA FIN')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_expedientes')
                    ->label('TOTAL EXPEDIENTES')
                    ->numeric()
                    ->alignCenter()
                    ->sortable(),
                Tables\Columns\TextColumn::make('estado.valor')
                    ->label('ESTADO')
                    ->badge()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('usuarioSolicitante.nombres')
                    ->label('FUNCIONARIO REMITENTE')
                    ->formatStateUsing(
                        fn ($record): string => trim(
                            "{$record->usuarioSolicitante?->nombres} {$record->usuarioSolicitante?->apellidos}"
                        )
                    )
                    ->searchable([
                        'nombres',
                        'apellidos',
                    ]),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                //
            ])
            ->toolbarActions([
                //
            ])
            ->defaultSort('fecha_solicitud', 'desc')
            ->paginated([500, 1000])
            ->defaultPaginationPageOption(500)
            ->striped()
            ->recordUrl(null);
    }
}
<?php

namespace App\Filament\Resources\Inventarios\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables;
use Filament\Tables\Table;

class InventariosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('codigo_inventario')
                    ->label('CÓDIGO INVENTARIO')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('codigo_referencia')
                    ->label('CÓDIGO REFERENCIA')
                    ->searchable(),

                Tables\Columns\TextColumn::make('oficina.valor')
                    ->label('OFICINA')
                    ->searchable(),

                Tables\Columns\TextColumn::make('direccion.valor')
                    ->label('DIRECCIÓN')
                    ->searchable(),

                Tables\Columns\TextColumn::make('area.valor')
                    ->label('ÁREA')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('numero_caja')
                    ->label('N° CAJA')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('descripcion_lomo')
                    ->label('DESCRIPCIÓN DOCUMENTAL')
                    ->limit(60)
                    ->tooltip(
                        fn ($record): ?string =>
                            $record->descripcion_lomo
                    ),

                Tables\Columns\TextColumn::make('estado.valor')
                    ->label('ESTADO')
                    ->badge()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('origen')
                    ->label('ORIGEN')
                    ->badge()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('fecha_creacion')
                    ->label('FECHA REGISTRO')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('fecha_creacion', 'desc')
            ->paginated([50, 100])
            ->defaultPaginationPageOption(50)
            ->striped()
            ->recordUrl(null);
    }
}
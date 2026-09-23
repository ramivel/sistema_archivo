<?php

namespace App\Filament\Resources\Subfondos\Tables;

use App\Filament\Resources\Fondos\FondoResource;
use App\Filament\Resources\Subfondos\SubfondoResource;
use App\Models\Parametro;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class SubfondosTable
{
    public static function configure(Table $table): Table
    {
        $fondo = SubfondoResource::getFondoFromRoute();
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero')
                    ->label('N°')
                    ->rowIndex(),
                Tables\Columns\TextColumn::make('valor')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sigla')
                    ->label('Sigla')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('secciones_count')
                    ->label('Secciones')
                    ->state(
                        fn (Parametro $record): string =>
                            'Ver Secciones (' . $record->secciones_count . ')'
                    )
                    ->url(
                        fn (Parametro $record): string =>
                            url('/admin/secciones/' . $record->guid)
                    )
                    ->openUrlInNewTab(false)
                    ->color('info')
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->state(
                        fn (Parametro $record): string =>
                            $record->activo
                                ? 'ACTIVO'
                                : 'DESACTIVADO'
                    )
                    ->badge()
                    ->color(
                        fn (Parametro $record): string =>
                            $record->activo
                                ? 'success'
                                : 'danger'
                    ),
            ])

            ->toolbarActions([
                Action::make('atras')
                    ->label('Atrás (Fondos)')
                    ->icon('heroicon-o-arrow-left')
                    ->url(
                        fn (): string =>
                            FondoResource::getUrl('index')
                    ),
                CreateAction::make()
                    ->label('Nuevo Sub Fondo')
                    ->icon('heroicon-o-plus')
                    ->modalHeading(
                        $fondo->valor . ' / Nuevo Sub Fondo'
                    )
                    ->modalSubmitActionLabel('Guardar')
                    ->modalCancelActionLabel('Cancelar')
                    ->using(function (array $data) use ($fondo): Parametro {
                        return Parametro::create([
                            'grupo' => SubfondoResource::getGrupo(),
                            'valor' => $data['valor'],
                            'sigla' => $data['sigla'],
                            'padre_id' => $fondo->id,
                            'usuario_creacion_id' => Auth::id(),
                        ]);
                    })
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Sub Fondo creado')
                            ->body('El sub fondo fue creado correctamente.')
                    ),
            ])

            ->recordActions([
                Action::make('ver')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(
                        $fondo->valor . ' / Sub Fondo'
                    )
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->schema([
                        Section::make()
                            ->schema([
                                TextEntry::make('valor')
                                    ->label('Nombre')
                                    ->columnSpanFull(),
                                TextEntry::make('sigla')
                                    ->label('Sigla')
                                    ->columnSpanFull(),
                            ])
                            ->columns(1),
                    ]),

                EditAction::make()
                    ->label('Editar')
                    ->icon('heroicon-o-pencil-square')
                    ->modalHeading(
                        $fondo->valor . ' / Editar Sub Fondo'
                    )
                    ->modalSubmitActionLabel('Actualizar')
                    ->modalCancelActionLabel('Cancelar')
                    ->using(function (
                        Parametro $record,
                        array $data
                    ): Parametro {
                        $record->update([
                            'valor' => $data['valor'],
                            'sigla' => $data['sigla'],
                            'usuario_actualizacion_id' => Auth::id(),
                        ]);
                        return $record;
                    })
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Sub Fondo actualizado')
                            ->body('El sub fondo fue actualizado correctamente.')
                    ),

                Action::make('activar')
                    ->label('Activar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(
                        fn (Parametro $record): bool =>
                            ! $record->activo
                    )
                    ->requiresConfirmation()
                    ->modalHeading(
                        $fondo->valor . ' / Activar Sub Fondo'
                    )
                    ->modalDescription(
                        '¿Está seguro de activar este sub fondo?'
                    )
                    ->modalSubmitActionLabel('Activar')
                    ->modalCancelActionLabel('Cancelar')
                    ->action(function (Parametro $record): void {
                        $record->update([
                            'activo' => true,
                            'usuario_actualizacion_id' => Auth::id(),
                        ]);
                    })
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Sub Fondo activado')
                            ->body('El sub fondo fue activado correctamente.')
                    ),

                Action::make('desactivar')
                    ->label('Desactivar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(
                        fn (Parametro $record): bool =>
                            $record->activo
                    )
                    ->requiresConfirmation()
                    ->modalHeading(
                        $fondo->valor . ' / Desactivar Sub Fondo'
                    )
                    ->modalDescription(
                        '¿Está seguro de desactivar este sub fondo?'
                    )
                    ->modalSubmitActionLabel('Desactivar')
                    ->modalCancelActionLabel('Cancelar')
                    ->action(function (Parametro $record): void {
                        $record->update([
                            'activo' => false,
                            'usuario_actualizacion_id' => Auth::id(),
                        ]);
                    })
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Sub Fondo desactivado')
                            ->body('El sub fondo fue desactivado correctamente.')
                    ),

                DeleteAction::make()
                    ->label('Eliminar')
                    ->icon('heroicon-o-trash')
                    ->requiresConfirmation()
                    ->modalHeading(
                        $fondo->valor . ' / Eliminar Sub Fondo'
                    )
                    ->modalDescription(
                        '¿Está seguro de eliminar este sub fondo?'
                    )
                    ->modalSubmitActionLabel('Eliminar')
                    ->modalCancelActionLabel('Cancelar')
                    ->using(function (Parametro $record): void {
                        $record->update([
                            'usuario_eliminacion_id' => Auth::id(),
                        ]);
                        $record->delete();
                    })
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Sub Fondo eliminado')
                            ->body('El sub fondo fue eliminado correctamente.')
                    ),
            ])

            ->recordAction('ver')
            ->recordUrl(null)
            ->defaultSort('valor')
            ->defaultPaginationPageOption(500)
            ->paginationPageOptions([
                500,
                1000,
            ])
            ->striped();
    }
}
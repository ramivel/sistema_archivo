<?php

namespace App\Filament\Resources\Procedencias\Tables;

use App\Filament\Resources\Procedencias\ProcedenciaResource;
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

class ProcedenciasTable
{
    public static function configure(Table $table): Table
    {
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
                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->state(
                        fn (Parametro $record): string => $record->activo
                            ? 'ACTIVO'
                            : 'DESACTIVADO'
                    )
                    ->badge()
                    ->color(
                        fn (Parametro $record): string => $record->activo
                            ? 'success'
                            : 'danger'
                    ),
            ])

            ->toolbarActions([
                CreateAction::make()
                    ->label('Nueva Procedencia')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Nueva Procedencia')
                    ->modalSubmitActionLabel('Guardar')
                    ->modalCancelActionLabel('Cancelar')
                    ->using(
                        function (array $data): Parametro {
                            return Parametro::create([
                                'grupo' => ProcedenciaResource::getGrupo(),
                                'valor' => $data['valor'],
                                'sigla' => $data['sigla'],                                
                                'usuario_creacion_id' => Auth::id(),
                            ]);
                        }
                    )
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Procedencia creada')
                            ->body(
                                'La procedencia fue creada correctamente.'
                            )
                    ),
            ])

            ->recordActions([
                Action::make('ver')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Procedencia')
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
                    ->modalHeading('Editar Procedencia')
                    ->modalSubmitActionLabel('Actualizar')
                    ->modalCancelActionLabel('Cancelar')
                    ->using(
                        function (
                            Parametro $record,
                            array $data
                        ): Parametro {
                            $record->update([
                                'valor' => $data['valor'],
                                'sigla' => $data['sigla'],
                                'usuario_actualizacion_id' => Auth::id(),
                            ]);                        
                            return $record;
                        }
                    )
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Procedencia actualizada')
                            ->body(
                                'La procedencia fue actualizada correctamente.'
                            )
                    ),

                Action::make('activar')
                    ->label('Activar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(
                        fn (Parametro $record): bool => ! $record->activo
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Activar procedencia')
                    ->modalDescription(
                        '¿Está seguro de activar esta procedencia?'
                    )
                    ->modalSubmitActionLabel('Activar')
                    ->modalCancelActionLabel('Cancelar')
                    ->action(
                        function (Parametro $record): void {
                            $record->update([
                                'activo' => true,
                                'usuario_actualizacion_id' => Auth::id(),
                            ]);
                        }
                    )
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Procedencia activada')
                            ->body(
                                'La procedencia fue activada correctamente.'
                            )
                    ),

                Action::make('desactivar')
                    ->label('Desactivar')
                    ->icon('heroicon-o-x-circle')
                    ->color('warning')
                    ->visible(
                        fn (Parametro $record): bool => $record->activo
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Desactivar procedencia')
                    ->modalDescription(
                        '¿Está seguro de desactivar esta procedencia?'
                    )
                    ->modalSubmitActionLabel('Desactivar')
                    ->modalCancelActionLabel('Cancelar')
                    ->action(
                        function (Parametro $record): void {
                            $record->update([
                                'activo' => false,
                                'usuario_actualizacion_id' => Auth::id(),
                            ]);
                        }
                    )
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Procedencia desactivada')
                            ->body(
                                'La procedencia fue desactivada correctamente.'
                            )
                    ),

                DeleteAction::make()
                    ->label('Eliminar')
                    ->icon('heroicon-o-trash')
                    ->requiresConfirmation()
                    ->modalHeading('Eliminar procedencia')
                    ->modalDescription(
                        '¿Está seguro de eliminar esta procedencia?'
                    )
                    ->modalSubmitActionLabel('Eliminar')
                    ->modalCancelActionLabel('Cancelar')
                    ->using(
                        function (Parametro $record): void {
                            $record->update([
                                'usuario_eliminacion_id' => Auth::id(),
                            ]);

                            $record->delete();
                        }
                    )
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Procedencia eliminada')
                            ->body(
                                'La procedencia fue eliminada correctamente.'
                            )
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
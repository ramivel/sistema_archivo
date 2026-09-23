<?php

namespace App\Filament\Resources\Soportes\Tables;

use App\Filament\Resources\Soportes\SoporteResource;
use App\Models\Parametro;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class SoportesTable
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
                Tables\Columns\TextColumn::make('descripcion')
                    ->label('Descripción')
                    ->limit(50)
                    ->searchable(),
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
                    ->label('Nuevo Soporte')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Nuevo Soporte')
                    ->modalSubmitActionLabel('Guardar')
                    ->modalCancelActionLabel('Cancelar')
                    ->using(
                        function (array $data): Parametro {
                            return Parametro::create([
                                'grupo' => SoporteResource::getGrupo(),
                                'valor' => $data['valor'],
                                'descripcion' => $data['descripcion'] ?? null,
                                'usuario_creacion_id' => Auth::id(),
                            ]);
                        }
                    )
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Soporte creado')
                            ->body(
                                'El soporte fue creado correctamente.'
                            )
                    ),
            ])

            ->recordActions([

                Action::make('ver')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Soporte')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->schema([
                        Section::make()
                            ->schema([
                                TextEntry::make('valor')
                                    ->label('Nombre')
                                    ->columnSpanFull(),
                                TextEntry::make('descripcion')
                                    ->label('Descripción')
                                    ->columnSpanFull()
                                    ->placeholder(
                                        'Sin descripción registrada.'
                                    ),
                            ])
                            ->columns(1),
                    ]),

                EditAction::make()
                    ->label('Editar')
                    ->icon('heroicon-o-pencil-square')
                    ->modalHeading('Editar Soporte')
                    ->modalSubmitActionLabel('Actualizar')
                    ->modalCancelActionLabel('Cancelar')
                    ->using(
                        function (
                            Parametro $record,
                            array $data
                        ): Parametro {
                            $record->update([
                                'valor' => $data['valor'],
                                'descripcion' => $data['descripcion'] ?? null,
                                'usuario_actualizacion_id' => Auth::id(),
                            ]);
                            return $record;
                        }
                    )
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Soporte actualizado')
                            ->body(
                                'El soporte fue actualizado correctamente.'
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
                    ->modalHeading(
                        fn (Parametro $record): string =>
                            'Activar Soporte / ' . $record->valor
                    )
                    ->modalDescription(
                        '¿Está seguro de activar este soporte?'
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
                            ->title('Soporte activado')
                            ->body(
                                'El soporte fue activado correctamente.'
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
                    ->modalHeading(
                        fn (Parametro $record): string =>
                            'Desactivar Soporte / ' . $record->valor
                    )
                    ->modalDescription(
                        '¿Está seguro de desactivar este soporte?'
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
                            ->title('Soporte desactivado')
                            ->body(
                                'El soporte fue desactivado correctamente.'
                            )
                    ),

                DeleteAction::make()
                    ->label('Eliminar')
                    ->icon('heroicon-o-trash')
                    ->requiresConfirmation()
                    ->modalHeading(
                        fn (Parametro $record): string =>
                            'Eliminar Soporte / ' . $record->valor
                    )
                    ->modalDescription(
                        '¿Está seguro de eliminar este soporte?'
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
                            ->title('Soporte eliminado')
                            ->body(
                                'El soporte fue eliminado correctamente.'
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
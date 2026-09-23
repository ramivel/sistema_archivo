<?php

namespace App\Filament\Resources\SerieDocumentals\Tables;

use Illuminate\Support\Facades\Auth;
use App\Filament\Resources\SerieDocumentals\SerieDocumentalResource;
use App\Models\Parametro;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;

class SerieDocumentalsTable
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
                    ->state(fn (Parametro $record): string => $record->activo ? 'ACTIVO' : 'DESACTIVADO')
                    ->badge()
                    ->color(fn (Parametro $record): string => $record->activo ? 'success' : 'danger'),
            ])

            ->toolbarActions([
                CreateAction::make()
                    ->label('Nueva Serie Documental')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Nueva Serie Documental')
                    ->modalSubmitActionLabel('Guardar')
                    ->modalCancelActionLabel('Cancelar')
                    ->using(function (array $data): Parametro {
                        return Parametro::create([
                            'grupo' => SerieDocumentalResource::getGrupo(),
                            'valor' => $data['valor'],
                            'descripcion' => $data['descripcion'] ?? null,
                            'usuario_creacion_id' => Auth::id(),
                        ]);
                    })
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Serie documental creada')
                            ->body('La serie documental fue creada correctamente.')
                    ),
            ])

            ->recordActions([

                Action::make('ver')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Serie Documental')
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
                    ->modalHeading('Editar Serie Documental')
                    ->modalSubmitActionLabel('Actualizar')
                    ->modalCancelActionLabel('Cancelar')
                    ->using(function (Parametro $record, array $data): Parametro {
                        $record->update([
                            'valor' => $data['valor'],
                            'descripcion' => $data['descripcion'] ?? null,
                            'usuario_actualizacion_id' => Auth::id(),
                        ]);
                        return $record;
                    })
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Serie documental actualizada')
                            ->body('La serie documental fue actualizada correctamente.')
                    ),

                Action::make('activar')
                    ->label('Activar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Parametro $record): bool => ! $record->activo)
                    ->requiresConfirmation()
                    ->modalHeading('Activar serie documental')
                    ->modalDescription('¿Está seguro de activar esta serie documental?')
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
                            ->title('Serie documental activada')
                            ->body('La serie documental fue activada correctamente.')
                    ),

                Action::make('desactivar')
                    ->label('Desactivar')
                    ->icon('heroicon-o-x-circle')
                    ->color('warning')
                    ->visible(fn (Parametro $record): bool => $record->activo)
                    ->requiresConfirmation()
                    ->modalHeading('Desactivar serie documental')
                    ->modalDescription('¿Está seguro de desactivar esta serie documental?')
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
                            ->title('Serie documental desactivada')
                            ->body('La serie documental fue desactivada correctamente.')
                    ),

                DeleteAction::make()
                    ->label('Eliminar')
                    ->icon('heroicon-o-trash')
                    ->requiresConfirmation()
                    ->modalHeading('Eliminar serie documental')
                    ->modalDescription('¿Está seguro de eliminar esta serie documental?')
                    ->modalSubmitActionLabel('Eliminar')
                    ->modalCancelActionLabel('Cancelar')
                    ->using(function (Parametro $record): void {
                        $record->delete();
                    })
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Serie documental eliminada')
                            ->body('La serie documental fue eliminada correctamente.')
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
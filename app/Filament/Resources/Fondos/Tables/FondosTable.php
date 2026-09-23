<?php

namespace App\Filament\Resources\Fondos\Tables;

use App\Filament\Resources\Fondos\FondoResource;
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

class FondosTable
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
                Tables\Columns\TextColumn::make('ubicacion')
                    ->label('Ubicación')
                    ->limit(50)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('subfondos_count')
                    ->label('Sub Fondos')
                    ->state(
                        fn (Parametro $record): string =>
                            'Ver Sub Fondos (' . $record->subfondos_count . ')'
                    )
                    ->url(
                        fn (Parametro $record): string =>
                            url('/admin/subfondos/' . $record->guid)
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
                CreateAction::make()
                    ->label('Nuevo Fondo')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Nuevo Fondo')
                    ->modalSubmitActionLabel('Guardar')
                    ->modalCancelActionLabel('Cancelar')
                    ->using(function (array $data): Parametro {
                        return Parametro::create([
                            'grupo' => FondoResource::getGrupo(),
                            'valor' => $data['valor'],
                            'sigla' => $data['sigla'],
                            'ubicacion' => $data['ubicacion'],
                            'usuario_creacion_id' => Auth::id(),
                        ]);
                    })
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Fondo creado')
                            ->body('El fondo fue creado correctamente.')
                    ),
            ])

            ->recordActions([
                Action::make('ver')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Fondo')
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
                                TextEntry::make('ubicacion')
                                    ->label('Ubicación')
                                    ->columnSpanFull(),
                            ])
                            ->columns(1),
                    ]),

                EditAction::make()
                    ->label('Editar')
                    ->icon('heroicon-o-pencil-square')
                    ->modalHeading('Editar Fondo')
                    ->modalSubmitActionLabel('Actualizar')
                    ->modalCancelActionLabel('Cancelar')
                    ->using(function (
                        Parametro $record,
                        array $data
                    ): Parametro {
                        $record->update([
                            'valor' => $data['valor'],
                            'sigla' => $data['sigla'],
                            'ubicacion' => $data['ubicacion'],
                            'usuario_actualizacion_id' => Auth::id(),
                        ]);
                        return $record;
                    })
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Fondo actualizado')
                            ->body('El fondo fue actualizado correctamente.')
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
                        fn (Parametro $record): string =>
                            'Activar Fondo / ' . $record->valor
                    )
                    ->modalDescription(
                        '¿Está seguro de activar este fondo?'
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
                            ->title('Fondo activado')
                            ->body('El fondo fue activado correctamente.')
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
                        fn (Parametro $record): string =>
                            'Desactivar Fondo / ' . $record->valor
                    )
                    ->modalDescription(
                        '¿Está seguro de desactivar este fondo?'
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
                            ->title('Fondo desactivado')
                            ->body('El fondo fue desactivado correctamente.')
                    ),

                DeleteAction::make()
                    ->label('Eliminar')
                    ->icon('heroicon-o-trash')
                    ->requiresConfirmation()
                    ->modalHeading(
                        fn (Parametro $record): string =>
                            'Eliminar Fondo / ' . $record->valor
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
                            ->title('Fondo eliminado')
                            ->body('El fondo fue eliminado correctamente.')
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
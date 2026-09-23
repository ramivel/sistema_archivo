<?php

namespace App\Filament\Resources\Seccions\Tables;

use App\Filament\Resources\Seccions\SeccionResource;
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

class SeccionsTable
{
    public static function configure(Table $table): Table
    {
        $subfondo = SeccionResource::getSubfondoFromRoute();
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
                    ->label('Atrás (Sub Fondos)')
                    ->icon('heroicon-o-arrow-left')
                    ->url(
                        fn (): string =>
                            SubfondoResource::getUrl('index', [
                                'fondo' => $subfondo->padre?->guid,
                            ])
                    ),
                CreateAction::make()
                    ->label('Nueva Sección')
                    ->icon('heroicon-o-plus')
                    ->modalHeading(
                        $subfondo->valor . ' / Nueva Sección'
                    )
                    ->modalSubmitActionLabel('Guardar')
                    ->modalCancelActionLabel('Cancelar')
                    ->using(function (array $data) use ($subfondo): Parametro {
                        return Parametro::create([
                            'grupo' => SeccionResource::getGrupo(),
                            'valor' => $data['valor'],
                            'sigla' => $data['sigla'],
                            'padre_id' => $subfondo->id,
                            'usuario_creacion_id' => Auth::id(),
                        ]);
                    })
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Sección creada')
                            ->body('La sección fue creada correctamente.')
                    ),
            ])

            ->recordActions([
                Action::make('ver')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(
                        $subfondo->valor . ' / Sección'
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
                        $subfondo->valor . ' / Editar Sección'
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
                            ->title('Sección actualizada')
                            ->body('La sección fue actualizada correctamente.')
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
                            'Activar Sección / ' . $record->valor
                    )
                    ->modalDescription(
                        '¿Está seguro de activar esta sección?'
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
                            ->title('Sección activada')
                            ->body('La sección fue activada correctamente.')
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
                            'Desactivar Sección / ' . $record->valor
                    )
                    ->modalDescription(
                        '¿Está seguro de desactivar esta sección?'
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
                            ->title('Sección desactivada')
                            ->body('La sección fue desactivada correctamente.')
                    ),

                DeleteAction::make()
                    ->label('Eliminar')
                    ->icon('heroicon-o-trash')
                    ->requiresConfirmation()
                    ->modalHeading(
                        fn (Parametro $record): string =>
                            'Eliminar Sección / ' . $record->valor
                    )
                    ->modalDescription(
                        '¿Está seguro de eliminar esta sección?'
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
                            ->title('Sección eliminada')
                            ->body('La sección fue eliminada correctamente.')
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
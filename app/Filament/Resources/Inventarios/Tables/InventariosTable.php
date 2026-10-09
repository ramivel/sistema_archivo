<?php

namespace App\Filament\Resources\Inventarios\Tables;

use App\Filament\Resources\Inventarios\InventarioResource;
use App\Filament\Resources\Inventarios\Schemas\InventarioForm;
use App\Filament\Resources\Inventarios\Schemas\InventarioPrestamoForm;
use App\Models\InventarioExpediente;
use App\Models\User;
use App\Services\InventarioService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Actions\ActionGroup;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class InventariosTable
{
    public static function configure(Table $table): Table
    {
        $user = Auth::user();
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('estado.valor')
                    ->label('ESTADO')
                    ->badge()
                    ->alignCenter()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('codigo_inventario')
                    ->label('CÓDIGO INVENTARIO')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('codigo_referencia')
                    ->label('CÓDIGO REFERENCIA')
                    ->searchable(),
                Tables\Columns\TextColumn::make('oficina.valor')
                    ->label('OFICINA')
                    ->searchable()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('direccion.valor')
                    ->label('DIRECCIÓN')
                    ->searchable()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('area.valor')
                    ->label('ÁREA')
                    ->placeholder('—')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('numero_caja')
                    ->label('N° CAJA')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('descripcion_lomo')
                    ->label('DESCRIPCIÓN DOCUMENTAL')
                    ->limit(60)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('origen')
                    ->label('ORIGEN')
                    ->badge()
                    ->alignCenter()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('fecha_creacion')
                    ->label('FECHA REGISTRO')
                    ->dateTime('d/m/Y H:i')
                    ->searchable()
                    ->sortable(),
            ])
            ->recordActions([
                ActionGroup::make([
                    self::verAction(),
                    self::editarAction($user),
                    self::prestarAction($user),
                    self::devolverAction($user),
                    self::bajaAction($user),
                    self::imprimirEtiquetaAction($user),
                    ])
                        ->label('Acciones')
                        ->icon('heroicon-o-ellipsis-vertical')
                        ->color('gray'),
            ])
            ->recordActionsPosition(
                RecordActionsPosition::BeforeColumns
            )
            ->defaultSort('id', 'desc')
            ->paginated([50, 100])
            ->defaultPaginationPageOption(50)
            ->striped()
            ->recordUrl(null);
    }

    private static function verAction(): Action
    {
        return Action::make('ver')
            ->label('Ver')
            ->icon('heroicon-o-eye')
            ->color('gray')
            ->url(
                fn (
                    InventarioExpediente $record
                ): string =>
                    InventarioResource::getUrl(
                        'ver',
                        ['record' => $record]
                    )
            );
    }

    private static function editarAction(?User $user): Action
    {
        return Action::make('editar')
            ->label('Editar')
            ->icon('heroicon-o-pencil-square')
            ->color('primary')
            ->visible(
                fn (
                    InventarioExpediente $record
                ): bool =>
                    $user?->esEncargadoArchivo()
                    && $record->estado?->valor === 'DISPONIBLE'
            )
            ->schema(InventarioForm::components())
            ->fillForm(
                fn (
                    InventarioExpediente $record
                ): array => [
                    'oficina_parametro_id' => $record->oficina_parametro_id,
                    'direccion_parametro_id' => $record->direccion_parametro_id,
                    'area_parametro_id' => $record->area_parametro_id,
                    'codigo_referencia' => $record->codigo_referencia,
                    'numero_caja' => $record->numero_caja,
                    'procedencia' => $record->procedencia,
                    'serie_documental_parametro_id' => $record->serie_documental_parametro_id,
                    'soporte_parametro_id' => $record->soporte_parametro_id,
                    'descripcion_lomo' => $record->descripcion_lomo,
                    'detalle' => $record->detalle,
                    'tomo_volumen' => $record->tomo_volumen,
                    'fojas' => $record->fojas,
                    'fechas_extremas' => $record->fechas_extremas,
                    'observaciones' => $record->observaciones,
                    'archivo_pdf' => $record->archivo_pdf,
                ]
            )
            ->modalHeading('Editar expediente')
            ->modalSubmitActionLabel('Guardar cambios')
            ->modalCancelActionLabel('Cancelar')
            ->action(
                function (
                    InventarioExpediente $record,
                    array $data
                ): void {
                    try {
                        app(InventarioService::class)->actualizar($record, $data);
                        Notification::make()
                            ->title('Expediente actualizado correctamente')
                            ->body("El expediente {$record->codigo_inventario} fue actualizado correctamente.")
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->danger()
                            ->title('No se pudo actualizar')
                            ->body($e->getMessage())
                            ->persistent()
                            ->send();
                    }
                }
            );
    }

    private static function prestarAction(?User $user): Action
    {
        return Action::make('prestar')
            ->label('Prestar')
            ->icon('heroicon-o-arrow-up-right')
            ->color('warning')
            ->visible(
                fn (
                    InventarioExpediente $record
                ): bool =>
                    $user?->esEncargadoArchivo()
                    && $record->estado?->valor === 'DISPONIBLE'
            )
            ->schema(InventarioPrestamoForm::prestar())
            ->modalHeading('Registrar préstamo')
            ->modalSubmitActionLabel('Registrar préstamo')
            ->modalCancelActionLabel('Cancelar')
            ->action(
                function (
                    InventarioExpediente $record,
                    array $data
                ): void {
                    try {
                        app(InventarioService::class)->prestar($record, $data);
                        Notification::make()
                            ->title('Préstamo registrado correctamente')
                            ->body("El expediente {$record->codigo_inventario} se encuentra en prestamo.")
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->danger()
                            ->title('No se pudo registrar el préstamo')
                            ->body($e->getMessage())
                            ->persistent()
                            ->send();
                    }
                }
            );
    }

    private static function devolverAction(?User $user): Action
    {
        return Action::make('devolver')
            ->label('Devolver')
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('success')
            ->visible(
                fn (
                    InventarioExpediente $record
                ): bool =>
                    $user?->esEncargadoArchivo()
                    && $record->estado?->valor === 'PRESTADO'
            )
            ->schema(InventarioPrestamoForm::devolver())
            ->modalHeading('Registrar devolución')
            ->modalSubmitActionLabel('Registrar devolución')
            ->modalCancelActionLabel('Cancelar')
            ->action(
                function (
                    InventarioExpediente $record,
                    array $data
                ): void {
                    try {
                        app(InventarioService::class)->devolver($record, $data);
                        Notification::make()
                            ->title('Devolución registrada correctamente')
                            ->body("El expediente {$record->codigo_inventario} se encuentra disponible.")
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->danger()
                            ->title('No se pudo registrar la devolución')
                            ->body($e->getMessage())
                            ->persistent()
                            ->send();
                    }
                }
            );
    }

    private static function bajaAction(?User $user): Action
    {
        return Action::make('baja')
            ->label('Dar de baja')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(
                fn (
                    InventarioExpediente $record
                ): bool =>
                    $user?->esEncargadoArchivo()
                    && $record->estado?->valor !== 'BAJA'
            )
            ->schema(InventarioPrestamoForm::baja())
            ->modalHeading('Dar de baja expediente')
            ->modalSubmitActionLabel('Confirmar baja')
            ->modalCancelActionLabel('Cancelar')
            ->action(
                function (
                    InventarioExpediente $record,
                    array $data
                ): void {
                    try {
                        app(InventarioService::class)->darDeBaja($record,$data['motivo_baja']);
                        Notification::make()
                            ->title('Baja registrada correctamente')
                            ->body("El expediente {$record->codigo_inventario} dado de baja del inventario.")
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->danger()
                            ->title('No se pudo dar de baja')
                            ->body($e->getMessage())
                            ->persistent()
                            ->send();
                    }
                }
            );
    }

    private static function imprimirEtiquetaAction(
        ?User $user
    ): Action {
        return Action::make('imprimirEtiqueta')
            ->label('Etiqueta')
            ->icon('heroicon-o-qr-code')
            ->color('gray')
            ->visible(
                fn (): bool =>
                    $user?->esEncargadoArchivo() ?? false
            )
            ->url(
                fn (
                    InventarioExpediente $record
                ): string =>
                    route(
                        'inventario.imprimir-etiqueta',
                        ['expediente' => $record]
                    )
            )
            ->openUrlInNewTab();
    }

}
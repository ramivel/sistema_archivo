<?php

namespace App\Filament\Resources\Transferencias\Tables;

use App\Filament\Resources\Transferencias\TransferenciaResource;
use App\Models\Transferencia;
use App\Models\User;
use App\Services\TransferenciaService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;

class TransferenciasTable
{
    public static function configure(Table $table): Table
    {
        $user = Auth::user();
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
                Tables\Columns\TextColumn::make('estado.valor')
                    ->label('ESTADO')
                    ->badge()
                    ->alignCenter(),
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
                self::observarAction($user),
                Action::make('corregir')
                    ->label('Corregir')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->visible(
                        fn (Transferencia $record): bool =>
                            $user instanceof User
                            && $user->esTransferencias()
                            && $record->estado?->valor === 'OBSERVADO'
                    )
                    ->url(
                        fn (Transferencia $record): string =>
                            TransferenciaResource::getUrl(
                                'corregir',
                                ['record' => $record]
                            )
                    ),
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
    private static function observarAction(?User $user): Action
    {
        return Action::make('observar')
            ->label('Observar')
            ->icon('heroicon-o-eye')
            ->color('warning')
            ->visible(
                fn (Transferencia $record): bool =>
                    $user instanceof User
                    && $user->esEncargadoArchivo()
                    && in_array(
                        $record->estado?->valor,
                        ['INICIADO', 'CORREGIDO'],
                        true
                    )
            )
            ->schema([
                Section::make('Observar Transferencia')
                    ->description('Los campos marcados con * son obligatorios.')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('usuario_remitente')
                            ->label('Usuario remitente')
                            ->state(
                                fn (Transferencia $record): string =>
                                    trim(
                                        "{$record->usuarioSolicitante?->nombres} "
                                        . "{$record->usuarioSolicitante?->apellidos}"
                                    )
                            ),
                        TextEntry::make('fecha_solicitud')
                            ->label('Fecha inicio solicitud')
                            ->state(
                                fn (Transferencia $record): string =>
                                    $record->fecha_solicitud?->format(
                                        'd/m/Y H:i'
                                    ) ?? '-'
                            ),
                        TextEntry::make('total_expedientes')
                            ->label('Total expedientes')
                            ->state(
                                fn (Transferencia $record): string =>
                                    (string) $record->total_expedientes
                            ),
                        TextEntry::make('fondo')
                            ->label('Fondo')
                            ->state(
                                fn (Transferencia $record): string =>
                                    $record->fondo?->valor ?? '-'
                            ),
                        TextEntry::make('subfondo')
                            ->label('Subfondo')
                            ->state(
                                fn (Transferencia $record): string =>
                                    $record->subfondo?->valor ?? '-'
                            ),
                        TextEntry::make('seccion')
                            ->label('Sección')
                            ->state(
                                fn (Transferencia $record): string =>
                                    $record->seccion?->valor ?? '-'
                            ),
                        Textarea::make('observacion')
                            ->label('Observaciones')
                            ->required()
                            ->rows(5)
                            ->maxLength(2000)
                            ->columnSpanFull()
                            ->placeholder(
                                'Describa las observaciones de la transferencia.'
                            )
                            ->extraInputAttributes([
                                'style' => 'text-transform: uppercase',
                            ])
                            ->dehydrateStateUsing(
                                fn (?string $state): string =>
                                    mb_strtoupper(
                                        trim((string) $state),
                                        'UTF-8'
                                    )
                            ),
                    ])
                    ->columns(3),
            ])
            ->modalHeading(
                fn (Transferencia $record): string =>
                    $record->correlativo
            )
            ->modalSubmitActionLabel('Observar Transferencia')
            ->modalCancelActionLabel('Cancelar')
            ->action(function (
                Transferencia $record,
                array $data
            ): void {
                try {
                    app(TransferenciaService::class)->observar(
                        transferencia: $record,
                        observacion: $data['observacion'],
                    );
                    Notification::make()
                        ->success()
                        ->title('Transferencia observada')
                        ->body("La observación de {$record->correlativo} fue registrada correctamente.")
                        ->send();
                } catch (\Throwable $e) {
                    Notification::make()
                        ->danger()
                        ->title("No se pudo observar {$record->correlativo}")
                        ->body($e->getMessage())
                        ->persistent()
                        ->send();
                }
            });
    }
}
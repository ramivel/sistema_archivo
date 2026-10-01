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
                Action::make('observar')
                    ->label('Observar')
                    ->icon('heroicon-o-eye')
                    ->color('warning')
                    ->visible(fn (Transferencia $record): bool =>
                        $user instanceof User
                        && $user->esEncargadoArchivo()
                        && in_array(
                            $record->estado?->valor,
                            ['INICIADO', 'CORREGIDO'],
                            true
                        )
                    )
                    ->schema([
                        Textarea::make('observacion')
                            ->label('Observación')
                            ->required()
                            ->rows(5)
                            ->maxLength(2000)
                            ->placeholder(
                                'Describa las observaciones de la transferencia.'
                            )->extraInputAttributes([
                                'style' => 'text-transform: uppercase',
                            ])
                            ->dehydrateStateUsing(
                                fn (?string $state): ?string => $state === null
                                    ? null
                                    : mb_strtoupper(trim($state), 'UTF-8')
                            ),
                    ])
                    ->requiresConfirmation()
                    ->modalHeading('Observar transferencia')
                    ->modalDescription(
                        fn (Transferencia $record): string =>
                            "Registre las observaciones para {$record->correlativo}."
                    )
                    ->modalSubmitActionLabel('Registrar observación')
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
                                ->body(
                                    'La observación fue registrada correctamente.'
                                )
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->danger()
                                ->title(
                                    'No se pudo observar la transferencia'
                                )
                                ->body($e->getMessage())
                                ->persistent()
                                ->send();
                        }
                    }),
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
}
<?php

namespace App\Filament\Resources\Transferencias\Tables;

use App\Filament\Resources\Transferencias\TransferenciaResource;
use App\Models\Transferencia;
use App\Models\User;
use App\Models\TransferenciaHistorial;
use App\Services\TransferenciaService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\FileUpload;

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
                self::imprimirEtiquetasAction($user),
                self::imprimirSolicitudAction($user),
                self::imprimirFormularioComplementarioAction($user),
                self::aprobarTransferenciaAction($user),
                self::observarAction($user),
                self::corregirAction($user),
                self::solicitarAnulacionAction($user),
                self::aprobarAnulacionAction($user),
                self::rechazarAnulacionAction($user),
                self::rechazarTransferenciaAction($user),
                self::finalizarTransferenciaAction($user),
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

    private static function aprobarTransferenciaAction(?User $user): Action
    {
        return Action::make('aprobarTransferencia')
            ->label('Aprobar transferencia')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->visible(
                fn (Transferencia $record): bool =>
                    $user instanceof User
                    && $user->esEncargadoArchivo()
                    && ! $record->es_regularizacion
                    && in_array(
                        $record->estado?->valor,
                        ['INICIADO', 'CORREGIDO'],
                        true
                    )
            )
            ->schema([
                self::datosTransferenciaSection(),
                self::ultimaObservacionRemitenteSection(),
            ])
            ->modalHeading(
                fn (Transferencia $record): string =>
                    $record->correlativo
            )
            ->modalDescription(
                '¿Está seguro de aprobar esta transferencia?'
            )
            ->modalSubmitActionLabel('Aprobar transferencia')
            ->modalCancelActionLabel('Cancelar')
            ->action(function (Transferencia $record): void {
                try {
                    app(TransferenciaService::class)->aprobarTransferencia($record);
                    Notification::make()
                        ->success()
                        ->title('Transferencia aprobada')
                        ->body("La transferencia {$record->correlativo} " . 'fue aprobada correctamente.')
                        ->send();
                } catch (\Throwable $e) {
                    Notification::make()
                        ->danger()
                        ->title('No se pudo aprobar la transferencia')
                        ->body($e->getMessage())
                        ->persistent()
                        ->send();
                }
            });
    }

    private static function observarAction(?User $user): Action
    {
        return Action::make('observar')
            ->label('Observar transferencia')
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

    private static function corregirAction(?User $user): Action
    {
        return Action::make('corregir')
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
            );
    }

    private static function datosTransferenciaSection(): Section
    {
        return Section::make('Datos de la transferencia')
            ->description('Información general de la transferencia.')
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
            ])
            ->columns(3);
    }

    private static function ultimaObservacion(
        Transferencia $record,
        string $accion
    ): ?string {
        return TransferenciaHistorial::query()
            ->where('transferencia_id', $record->id)
            ->where('accion', $accion)
            ->latest('fecha_accion')
            ->value('observacion');
    }

    private static function ultimaObservacionRemitente(Transferencia $record): string
    {
        return TransferenciaHistorial::query()
            ->where('transferencia_id', $record->id)
            ->whereIn('accion', ['INICIAR', 'CORREGIR'])
            ->latest('fecha_accion')
            ->value('observacion') ?? '-';
    }

    private static function motivoSolicitudAnulacionSection(): Section
    {
        return Section::make('Motivo de solicitud de anulación')
            ->schema([
                TextEntry::make('motivo_solicitud')
                    ->hiddenLabel()
                    ->state(
                        fn (Transferencia $record): string =>
                            self::ultimaObservacion($record, 'SOLICITAR ANULACIÓN') ?? '-'
                    )
                    ->columnSpanFull(),
            ])
            ->columnSpanFull();
    }

    private static function motivoRechazoSolicitudAnulacionSection(): Section
    {
        return Section::make('Motivo de rechazo de anulación')
            ->schema([
                TextEntry::make('motivo_rechazo')
                    ->hiddenLabel()
                    ->state(
                        fn (Transferencia $record): string =>
                            self::ultimaObservacion($record, 'RECHAZAR ANULACIÓN') ?? '-'
                    )
                    ->columnSpanFull(),
            ])
            ->columnSpanFull()
            ->visible(
                fn (Transferencia $record): bool =>
                    filled(
                        self::ultimaObservacion($record, 'RECHAZAR ANULACIÓN')
                    )
            );
    }

    private static function ultimaObservacionRemitenteSection(): Section
    {
        return Section::make('Observaciones del remitente')
            ->schema([
                TextEntry::make('ultima_observacion')
                    ->hiddenLabel()
                    ->state(
                        fn (Transferencia $record): string =>
                            self::ultimaObservacionRemitente($record)
                    )
                    ->columnSpanFull(),
            ])
            ->columnSpanFull();
    }

    private static function solicitarAnulacionAction(?User $user): Action
    {
        return Action::make('solicitarAnulacion')
            ->label('Solicitar anulación')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(
                fn (Transferencia $record): bool =>
                    $user instanceof User
                    && $user->esTransferencias()
                    && $record->usuario_solicitante_id === $user->id
                    && in_array(
                        $record->estado?->valor,
                        [
                            'INICIADO',
                            'OBSERVADO',
                            'CORREGIDO',
                            'APROBADO',
                        ],
                        true
                    )
            )
            ->schema([
                self::datosTransferenciaSection(),
                self::motivoRechazoSolicitudAnulacionSection(),
                Textarea::make('observacion')
                    ->label('Motivo de la anulación')
                    ->required()
                    ->rows(5)
                    ->maxLength(2000)
                    ->columnSpanFull()
                    ->placeholder('Describa el motivo de la solicitud de anulación.')
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
            ->modalHeading(
                fn (Transferencia $record): string =>
                    $record->correlativo
            )
            ->modalSubmitActionLabel('Solicitar anulación')
            ->modalCancelActionLabel('Cancelar')
            ->action(function (
                Transferencia $record,
                array $data
            ): void {
                try {
                    app(TransferenciaService::class)->solicitarAnulacion(
                        transferencia: $record,
                        observacion: $data['observacion'],
                    );
                    Notification::make()
                        ->success()
                        ->title('Solicitud de anulación registrada')
                        ->body("La solicitud de {$record->correlativo} " . 'fue enviada a revisión.')
                        ->send();
                } catch (\Throwable $e) {
                    Notification::make()
                        ->danger()
                        ->title('No se pudo solicitar la anulación')
                        ->body($e->getMessage())
                        ->persistent()
                        ->send();
                }
            });
    }

    private static function aprobarAnulacionAction(?User $user): Action
    {
        return Action::make('aprobarAnulacion')
            ->label('Aprobar anulación')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->visible(
                fn (Transferencia $record): bool =>
                    $user instanceof User
                    && $user->esEncargadoArchivo()
                    && $record->estado?->valor === 'SOLICITUD DE ANULACIÓN'
            )
            ->schema([
                self::datosTransferenciaSection(),
                self::motivoSolicitudAnulacionSection(),
            ])
            ->modalHeading(
                fn (Transferencia $record): string =>
                    $record->correlativo
            )
            ->modalDescription(
                '¿Está seguro de aprobar la solicitud de anulación?'
            )
            ->modalSubmitActionLabel('Aprobar anulación')
            ->modalCancelActionLabel('Cancelar')
            ->action(function (Transferencia $record): void {
                try {
                    app(TransferenciaService::class)->aprobarAnulacion($record);
                    Notification::make()
                        ->success()
                        ->title('Anulación aprobada')
                        ->body("La transferencia {$record->correlativo} " . 'fue anulada correctamente.')
                        ->send();
                } catch (\Throwable $e) {
                    Notification::make()
                        ->danger()
                        ->title('No se pudo aprobar la anulación')
                        ->body($e->getMessage())
                        ->persistent()
                        ->send();
                }
            });
    }

    private static function rechazarAnulacionAction(?User $user): Action
    {
        return Action::make('rechazarAnulacion')
            ->label('Rechazar anulación')
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('warning')
            ->visible(
                fn (Transferencia $record): bool =>
                    $user instanceof User
                    && $user->esEncargadoArchivo()
                    && $record->estado?->valor === 'SOLICITUD DE ANULACIÓN'
            )
            ->schema([
                self::datosTransferenciaSection(),
                self::motivoSolicitudAnulacionSection(),
                Textarea::make('observacion')
                    ->label('Motivo del rechazo')
                    ->required()
                    ->rows(5)
                    ->maxLength(2000)
                    ->columnSpanFull()
                    ->placeholder('Describa el motivo del rechazo de la anulación.')
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
            ->modalHeading(
                fn (Transferencia $record): string =>
                    $record->correlativo
            )
            ->modalSubmitActionLabel('Rechazar anulación')
            ->modalCancelActionLabel('Cancelar')
            ->action(function (
                Transferencia $record,
                array $data
            ): void {
                try {
                    app(TransferenciaService::class)
                        ->rechazarAnulacion(
                            transferencia: $record,
                            observacion: $data['observacion'],
                        );
                    Notification::make()
                        ->success()
                        ->title('Anulación rechazada')
                        ->body("La transferencia {$record->correlativo} " . 'continuará con su proceso.')
                        ->send();
                } catch (\Throwable $e) {
                    Notification::make()
                        ->danger()
                        ->title('No se pudo rechazar la anulación')
                        ->body($e->getMessage())
                        ->persistent()
                        ->send();
                }
            });
    }

    private static function rechazarTransferenciaAction(?User $user): Action
    {
        return Action::make('rechazarTransferencia')
            ->label('Rechazar transferencia')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(
                fn (Transferencia $record): bool =>
                    $user instanceof User
                    && $user->esEncargadoArchivo()
                    && in_array(
                        $record->estado?->valor,
                        ['INICIADO', 'CORREGIDO', 'APROBADO'],
                        true
                    )
            )
            ->schema([
                self::datosTransferenciaSection(),
                self::ultimaObservacionRemitenteSection(),
                Textarea::make('observacion')
                    ->label('Observaciones del rechazo')
                    ->required()
                    ->rows(5)
                    ->maxLength(2000)
                    ->columnSpanFull()
                    ->placeholder(
                        'Describa el motivo del rechazo.'
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
                FileUpload::make('archivo_nota_rechazo')
                    ->label('Nota de rechazo (Subir escaneado con firmas en PDF)')
                    ->disk('local')
                    ->directory('transferencias/rechazos')
                    ->getUploadedFileNameForStorageUsing(
                        fn ($file): string =>
                            'documento_rechazo_' .
                            now()->format('Ymd_His') .
                            '_' .
                            \Illuminate\Support\Str::lower(
                                \Illuminate\Support\Str::random(8)
                            ) .
                            '.' .
                            $file->getClientOriginalExtension()
                    )
                    ->acceptedFileTypes([
                        'application/pdf',
                    ])
                    ->maxSize(10240)
                    ->nullable()
                    ->downloadable()
                    ->openable()
                    ->helperText(
                        'Archivo PDF opcional con la nota de rechazo.'
                    ),
            ])
            ->modalHeading(
                fn (Transferencia $record): string =>
                    $record->correlativo
            )
            ->modalSubmitActionLabel('Rechazar transferencia')
            ->modalCancelActionLabel('Cancelar')
            ->action(function (
                Transferencia $record,
                array $data
            ): void {
                try {
                    app(TransferenciaService::class)->rechazarTransferencia(
                        transferencia: $record,
                        observacion: $data['observacion'],
                        archivoNotaRechazo: $data['archivo_nota_rechazo'] ?? null,
                    );
                    Notification::make()
                        ->success()
                        ->title('Transferencia rechazada')
                        ->body("La transferencia {$record->correlativo} " . 'fue rechazada correctamente.')
                        ->send();
                } catch (\Throwable $e) {
                    Notification::make()
                        ->danger()
                        ->title('No se pudo rechazar la transferencia')
                        ->body($e->getMessage())
                        ->persistent()
                        ->send();
                }
            });
    }

    private static function imprimirEtiquetasAction(?User $user): Action
    {
        return Action::make('imprimirEtiquetas')
            ->label('Imprimir etiquetas')
            ->icon('heroicon-o-qr-code')
            ->color('gray')
            ->visible(
                fn (Transferencia $record): bool =>
                    $user instanceof User
                    && $record->estado?->valor === 'APROBADO'
                    && (
                        $user->esEncargadoArchivo()
                        || (
                            $user->esTransferencias()
                            && $record->usuario_solicitante_id === $user->id
                        )
                    )
            )
            ->url(
                fn (Transferencia $record): string =>
                    route(
                        'transferencias.imprimir-etiquetas',
                        ['transferencia' => $record]
                    )
            )
            ->openUrlInNewTab();
    }

    private static function imprimirSolicitudAction(?User $user): Action
    {
        return Action::make('imprimirSolicitud')
            ->label('Imprimir solicitud de transferencia')
            ->icon('heroicon-o-document-arrow-down')
            ->color('gray')
            ->visible(
                fn (Transferencia $record): bool =>
                    $user instanceof User
                    && (
                        $user->esEncargadoArchivo()
                        || (
                            $user->esTransferencias()
                            && $record->estado?->valor === 'APROBADO'
                            && $record->usuario_solicitante_id === $user->id
                        )
                    )
            )
            ->url(
                fn (Transferencia $record): string =>
                    route(
                        'transferencias.imprimir-solicitud',
                        ['transferencia' => $record]
                    )
            )
            ->openUrlInNewTab();
    }

    private static function imprimirFormularioComplementarioAction(?User $user): Action
    {
        return Action::make('imprimirFormularioComplementario')
            ->label('Imprimir formulario complementario')
            ->icon('heroicon-o-document-arrow-down')
            ->color('gray')
            ->visible(
                fn (Transferencia $record): bool =>
                    $user instanceof User
                    && $user->esEncargadoArchivo()
                    && $record->correccionesArchivo()->exists()
            )
            ->url(
                fn (Transferencia $record): string =>
                    route(
                        'transferencias.imprimir-formulario-complementario',
                        ['transferencia' => $record]
                    )
            )
            ->openUrlInNewTab();
    }

    private static function finalizarTransferenciaAction(
        ?User $user
    ): Action {
        return Action::make('finalizarTransferencia')
            ->label('Finalizar transferencia')
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->visible(
                fn (Transferencia $record): bool =>
                    $user instanceof User
                    && $user->esEncargadoArchivo()
                    && $record->estado?->valor === 'APROBADO'
            )
            ->url(
                fn (Transferencia $record): string =>
                    TransferenciaResource::getUrl(
                        'finalizar',
                        ['record' => $record]
                    )
            );
    }
}
<?php

namespace App\Filament\Resources\Transferencias\Pages;

use App\Filament\Resources\Transferencias\TransferenciaResource;
use App\Models\User;
use App\Models\Parametro;
use App\Models\Transferencia;
use App\Models\TransferenciaExpediente;
use App\Services\TransferenciaService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FinalizarTransferencia extends Page implements HasTable
{
    use InteractsWithTable;
    protected static string $resource = TransferenciaResource::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-check-badge';
    protected string $view = 'filament.resources.transferencias.pages.finalizar-transferencia';
    public Transferencia $transferencia;
    public string $observaciones = 'SIN OBSERVACIONES';

    public function mount(Transferencia $record): void
    {
        $usuario = User::find(Auth::id());
        abort_unless($usuario?->esEncargadoArchivo(),403);
        $record->load([
            'fondo',
            'subfondo',
            'seccion',
            'usuarioSolicitante',
            'estado',
        ]);
        abort_unless(
            (
                $record->es_regularizacion
                && $record->estado?->valor === 'INICIADO'
            )
            || (
                ! $record->es_regularizacion
                && $record->estado?->valor === 'APROBADO'
            ),
            404
        );
        $this->transferencia = $record;
    }

    public function getTitle(): string
    {
        return "Finalizar transferencia {$this->transferencia->correlativo}";
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('volver')
                ->label('VOLVER AL LISTADO')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(
                    fn (): string =>
                        TransferenciaResource::getUrl('index')
                ),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                TransferenciaExpediente::query()
                    ->where(
                        'transferencia_id',
                        $this->transferencia->getKey()
                    )
                    ->where('activo', true)
                    ->with([
                        'serieDocumental',
                        'soporte',
                        'correccionArchivo.serieDocumental',
                        'correccionArchivo.soporte',
                    ])
            )
            ->columns([
                TextColumn::make('editado')
                    ->label('EDITADO')
                    ->state(
                        fn (TransferenciaExpediente $record): ?string =>
                            $record->correccionArchivo && $record->correccionArchivo->usuario_creacion_id !== null
                                ? 'EDITADO' : null
                    )
                    ->badge()
                    ->color('warning')
                    ->placeholder('—'),
                TextColumn::make('fecha_edicion')
                    ->label('FECHA DE EDICIÓN')
                    ->state(
                        fn (TransferenciaExpediente $record): ?string =>
                            $record->correccionArchivo?->fecha_actualizacion
                                ?->format('d/m/Y H:i')
                    )
                    ->size('xs')
                    ->placeholder('—'),
                TextColumn::make('codigo_referencia')
                    ->label('CÓDIGO DE REFERENCIA')
                    ->size('xs')
                    ->state(
                        fn (TransferenciaExpediente $record): string =>
                            $record->correccionArchivo?->codigo_referencia
                            ?? $record->codigo_referencia
                    )
                    ->searchable(),
                TextColumn::make('numero_caja')
                    ->label('N° DE CAJA')
                    ->placeholder('—')
                    ->state(
                        fn (TransferenciaExpediente $record): string =>
                            $record->correccionArchivo?->numero_caja
                            ?? $record->numero_caja
                            ?? '—'
                    )
                    ->size('xs'),
                TextColumn::make('serie_documental')
                    ->label('SERIE DOCUMENTAL')
                    ->state(
                        fn (TransferenciaExpediente $record): string =>
                            $record->correccionArchivo?->serieDocumental?->valor
                            ?? $record->serieDocumental?->valor
                            ?? '—'
                    )
                    ->size('xs'),
                TextColumn::make('descripcion_lomo')
                    ->label('DESCRIPCIÓN DOCUMENTAL')
                    ->limit(80)
                    ->state(
                        fn (TransferenciaExpediente $record): string =>
                            $record->correccionArchivo?->descripcion_lomo
                            ?? $record->descripcion_lomo
                            ?? '—'
                    )
                    ->size('xs'),
                TextColumn::make('soporte')
                    ->label('SOPORTE')
                    ->state(
                        fn (TransferenciaExpediente $record): string =>
                            $record->correccionArchivo?->soporte?->valor
                            ?? $record->soporte?->valor
                            ?? '—'
                    )
                    ->size('xs'),
            ])
            ->recordActions([
                self::verExpedienteAction(),
                self::editarExpedienteAction($this),
            ])
            ->recordActionsPosition(RecordActionsPosition::BeforeColumns)
            ->paginated([50])
            ->defaultPaginationPageOption(50)
            ->recordUrl(null)
            ->striped();
    }

    public function datosGenerales(Schema $schema): Schema
    {
        $historialAprobacion = $this->transferencia
            ->historial
            ->first(
                fn ($historial): bool =>
                    $historial->accion === 'APROBAR'
            );
        $ultimaObservacionRemitente = $this->transferencia
            ->historial
            ->first(
                fn ($historial): bool =>
                    in_array(
                        $historial->accion,
                        ['INICIAR', 'CORREGIR'],
                        true
                    )
            );
        return $schema
            ->components([
                Section::make('Datos generales')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('usuario_remitente')
                            ->label('Usuario remitente')
                            ->state(
                                fn (): string =>
                                    $this->transferencia
                                        ->usuarioSolicitante
                                        ?->usuario
                                        ?? '—'
                            ),
                        TextEntry::make('fecha_inicio')
                            ->label('Fecha inicio solicitud')
                            ->state(
                                fn (): string =>
                                    $this->transferencia
                                        ->fecha_solicitud
                                        ?->format('d/m/Y H:i') ?? '—'
                            ),
                        TextEntry::make('fecha_aprobacion')
                            ->label('Fecha de aprobación')
                            ->state(
                                fn (): string =>
                                    $historialAprobacion
                                        ?->fecha_accion
                                        ?->format('d/m/Y H:i') ?? '—'
                            ),
                        TextEntry::make('total_expedientes')
                            ->label('Total expedientes')
                            ->state(
                                fn (): string =>
                                    number_format(
                                        $this->transferencia->total_expedientes
                                    )
                            ),
                        TextEntry::make('fondo')
                            ->label('Fondo')
                            ->state(
                                fn (): string =>
                                    $this->transferencia->fondo?->valor ?? '—'
                            ),
                        TextEntry::make('subfondo')
                            ->label('Subfondo')
                            ->state(
                                fn (): string =>
                                    $this->transferencia->subfondo?->valor ?? '—'
                            ),
                        TextEntry::make('seccion')
                            ->label('Sección')
                            ->state(
                                fn (): string =>
                                    $this->transferencia->seccion?->valor ?? '—'
                            ),
                        TextEntry::make('observaciones_remitente')
                            ->label('Últimas observaciones del remitente')
                            ->state(
                                fn (): string =>
                                    $ultimaObservacionRemitente
                                        ?->observacion ?? 'SIN OBSERVACIONES'
                            )
                            ->columnSpanFull(),
                    ])
                    ->columns(4)
                    ->columnSpanFull(),
            ]);
    }

    private static function verExpedienteAction(): Action
    {
        return Action::make('ver')
            ->label('Ver')
            ->icon('heroicon-o-eye')
            ->color('gray')
            ->schema([
                Section::make('Información del expediente')
                    ->schema([
                        TextEntry::make('codigo_referencia')
                            ->label('Código de referencia')
                            ->state(
                                fn (TransferenciaExpediente $record): string =>
                                    $record->codigo_referencia
                            ),
                        TextEntry::make('numero_caja')
                            ->label('N° de caja')
                            ->state(
                                fn (TransferenciaExpediente $record): string =>
                                    $record->numero_caja ?? '-'
                            ),
                        TextEntry::make('procedencia')
                            ->label('Procedencia')
                            ->state(
                                fn (TransferenciaExpediente $record): string =>
                                    $record->procedencia
                            ),
                        TextEntry::make('serie_documental')
                            ->label('Serie documental')
                            ->state(
                                fn (TransferenciaExpediente $record): string =>
                                    $record->serieDocumental?->valor ?? '-'
                            ),
                        TextEntry::make('soporte')
                            ->label('Soporte')
                            ->state(
                                fn (TransferenciaExpediente $record): string =>
                                    $record->soporte?->valor ?? '-'
                            ),
                        TextEntry::make('tomo_volumen')
                            ->label('Tomo / volumen')
                            ->state(
                                fn (TransferenciaExpediente $record): string =>
                                    $record->tomo_volumen ?? '-'
                            ),
                        TextEntry::make('fojas')
                            ->label('Fojas')
                            ->state(
                                fn (TransferenciaExpediente $record): string =>
                                    $record->fojas ?? '-'
                            ),
                        TextEntry::make('fechas_extremas')
                            ->label('Fechas extremas')
                            ->state(
                                fn (TransferenciaExpediente $record): string =>
                                    $record->fechas_extremas ?? '-'
                            ),
                        TextEntry::make('descripcion_lomo')
                            ->label('Descripción documental')
                            ->state(
                                fn (TransferenciaExpediente $record): string =>
                                    $record->descripcion_lomo ?? '-'
                            )
                            ->columnSpanFull(),
                        TextEntry::make('detalle')
                            ->label('Detalle')
                            ->state(
                                fn (TransferenciaExpediente $record): string =>
                                    $record->detalle ?? '-'
                            )
                            ->columnSpanFull(),
                        TextEntry::make('observaciones')
                            ->label('Observaciones')
                            ->state(
                                fn (TransferenciaExpediente $record): string =>
                                    $record->observaciones ?? '-'
                            )
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
                Section::make('Información del expediente corregido')
                    ->schema([
                        TextEntry::make('codigo_referencia')
                            ->label('Código de referencia')
                            ->state(
                                fn (TransferenciaExpediente $record): string =>
                                    $record->correccionArchivo->codigo_referencia
                            ),
                        TextEntry::make('numero_caja')
                            ->label('N° de caja')
                            ->state(
                                fn (TransferenciaExpediente $record): string =>
                                    $record->correccionArchivo->numero_caja ?? '-'
                            ),
                        TextEntry::make('procedencia')
                            ->label('Procedencia')
                            ->state(
                                fn (TransferenciaExpediente $record): string =>
                                    $record->correccionArchivo->procedencia
                            ),
                        TextEntry::make('serie_documental')
                            ->label('Serie documental')
                            ->state(
                                fn (TransferenciaExpediente $record): string =>
                                    $record->correccionArchivo->serie_documental ?? '-'
                            ),
                        TextEntry::make('soporte')
                            ->label('Soporte')
                            ->state(
                                fn (TransferenciaExpediente $record): string =>
                                    $record->correccionArchivo->soporte?->valor ?? '-'
                            ),
                        TextEntry::make('tomo_volumen')
                            ->label('Tomo / volumen')
                            ->state(
                                fn (TransferenciaExpediente $record): string =>
                                    $record->correccionArchivo->tomo_volumen ?? '-'
                            ),
                        TextEntry::make('fojas')
                            ->label('Fojas')
                            ->state(
                                fn (TransferenciaExpediente $record): string =>
                                    $record->correccionArchivo->fojas ?? '-'
                            ),
                        TextEntry::make('fechas_extremas')
                            ->label('Fechas extremas')
                            ->state(
                                fn (TransferenciaExpediente $record): string =>
                                    $record->correccionArchivo->fechas_extremas ?? '-'
                            ),
                        TextEntry::make('descripcion_lomo')
                            ->label('Descripción documental')
                            ->state(
                                fn (TransferenciaExpediente $record): string =>
                                    $record->correccionArchivo->descripcion_lomo ?? '-'
                            )
                            ->columnSpanFull(),
                        TextEntry::make('detalle')
                            ->label('Detalle')
                            ->state(
                                fn (TransferenciaExpediente $record): string =>
                                    $record->correccionArchivo->detalle ?? '-'
                            )
                            ->columnSpanFull(),
                        TextEntry::make('observaciones')
                            ->label('Observaciones')
                            ->state(
                                fn (TransferenciaExpediente $record): string =>
                                    $record->correccionArchivo->observaciones ?? '-'
                            )
                            ->columnSpanFull(),
                    ])
                    ->columns(3)
                    ->visible(
                        fn (TransferenciaExpediente $record): bool =>
                            $record->correccionArchivo !== null
                    ),
            ])
            ->modalHeading(
                fn (TransferenciaExpediente $record): string =>
                    "Expediente {$record->codigo_referencia}"
            )
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar');
    }

    private static function editarExpedienteAction(FinalizarTransferencia $page): Action
    {
        return Action::make('editar')
            ->label('Editar')
            ->icon('heroicon-o-pencil-square')
            ->color('primary')
            ->schema([
                TextInput::make('codigo_referencia')
                    ->label('Código de referencia')
                    ->required()
                    ->maxLength(50)
                    ->validationMessages([
                        'required' => 'El código de referencia es obligatorio.',
                        'max' => 'El código de referencia no puede superar los 50 caracteres.',
                    ]),
                TextInput::make('numero_caja')
                    ->label('Número de caja')
                    ->nullable()
                    ->maxLength(20)
                    ->rule(
                        'regex:/^[1-9][0-9]*(\/[1-9][0-9]*)?$/'
                    )
                    ->placeholder('-')
                    ->validationMessages([
                        'regex' =>
                            'El número de caja debe tener un formato válido, por ejemplo: 1, 2, 3, 1/3, 2/3 o 3/3.',
                        'max' =>
                            'El número de caja no puede superar los 20 caracteres.',
                    ]),
                TextInput::make('procedencia')
                    ->label('Procedencia')
                    ->required()
                    ->maxLength(255)
                    ->extraInputAttributes([
                        'style' => 'text-transform: uppercase',
                    ])
                    ->dehydrateStateUsing(
                        fn (?string $state): ?string =>
                            $state === null
                                ? null
                                : mb_strtoupper(trim($state), 'UTF-8')
                    )
                    ->validationMessages([
                        'required' => 'La procedencia es obligatoria.',
                        'max' => 'La procedencia no puede superar los 255 caracteres.',
                    ]),
                Select::make('serie_documental_parametro_id')
                    ->label('Serie documental')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->options(
                        fn (): array => Parametro::query()
                            ->where('grupo', 'SERIE_DOCUMENTAL')
                            ->where('activo', true)
                            ->whereNull('fecha_eliminacion')
                            ->orderBy('orden')
                            ->orderBy('valor')
                            ->pluck('valor', 'id')
                            ->toArray()
                    )
                    ->validationMessages([
                        'required' => 'Debe seleccionar una serie documental.',
                    ]),
                Textarea::make('descripcion_lomo')
                    ->label('Descripción documental')
                    ->rows(3)
                    ->required()
                    ->columnSpanFull()
                    ->extraInputAttributes([
                        'style' => 'text-transform: uppercase',
                    ])
                    ->dehydrateStateUsing(
                        fn (?string $state): ?string => $state === null
                            ? null
                            : mb_strtoupper(trim($state), 'UTF-8')
                    )
                    ->validationMessages([
                        'required' => 'La descripción documental es obligatoria.',
                    ]),
                Textarea::make('detalle')
                    ->label('Detalle')
                    ->rows(5)
                    ->required()
                    ->columnSpanFull()
                    ->extraInputAttributes([
                        'style' => 'text-transform: uppercase',
                    ])
                    ->dehydrateStateUsing(
                        fn (?string $state): ?string => $state === null
                            ? null
                            : mb_strtoupper(trim($state), 'UTF-8')
                    )
                    ->validationMessages([
                        'required' => 'El detalle es obligatorio.',
                    ]),
                TextInput::make('tomo_volumen')
                    ->label('Tomo / volumen')
                    ->nullable()
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->step(1)
                    ->validationMessages([
                        'numeric' => 'El tomo o volumen debe ser un número.',
                        'integer' => 'El tomo o volumen debe ser un número entero.',
                        'min' => 'El tomo o volumen debe ser mayor o igual a 1.',
                    ])
                    ->hintIcon(
                        'heroicon-m-information-circle',
                        tooltip: 'Ingrese únicamente un número entero. Ejemplo: 1, 2 o 3.'
                    ),
                TextInput::make('fojas')
                    ->label('Fojas')
                    ->nullable()
                    ->maxLength(30)
                    ->regex('/^(S\/F|[0-9-]+)$/i')
                    ->extraInputAttributes([
                        'style' => 'text-transform: uppercase',
                    ])
                    ->dehydrateStateUsing(
                        fn (?string $state): ?string =>
                            blank($state)
                                ? null
                                : mb_strtoupper(trim($state), 'UTF-8')
                    )
                    ->validationMessages([
                        'max' => 'El campo fojas no puede superar los 30 caracteres.',
                        'regex' => 'Ingrese la cantidad de fojas total o utilice rangos. Ejemplo: 100 o 1-5 o S/F si no tiene fojas.',
                    ])
                    ->hintIcon(
                        'heroicon-m-information-circle',
                        tooltip: 'Ingrese la cantidad de fojas total o utilice rangos. Ejemplo: 100 o 1-5 o S/F si no tiene fojas.'
                    ),
                TextInput::make('fechas_extremas')
                    ->label('Fechas extremas')
                    ->nullable()
                    ->maxLength(30)
                    ->regex('/^[0-9-]+$/')
                    ->dehydrateStateUsing(
                        fn (?string $state): ?string =>
                            blank($state)
                                ? null
                                : trim($state)
                    )
                    ->validationMessages([
                        'max' => 'Las fechas extremas no pueden superar los 30 caracteres.',
                        'regex' => 'Ingrese un año o un rango de años. Ejemplo: 2026 o 2026-2028.',
                    ])
                    ->hintIcon(
                        'heroicon-m-information-circle',
                        tooltip: 'Ingrese un año o un rango de años. Ejemplo: 2026 o 2026-2028.'
                    ),
                Select::make('soporte_parametro_id')
                    ->label('Soporte')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->options(
                        fn (): array => Parametro::query()
                            ->where('grupo', 'SOPORTE')
                            ->where('activo', true)
                            ->whereNull('fecha_eliminacion')
                            ->orderBy('orden')
                            ->orderBy('valor')
                            ->pluck('valor', 'id')
                            ->toArray()
                    )
                    ->validationMessages([
                        'required' => 'Debe seleccionar un soporte.',
                    ]),
                Textarea::make('observaciones')
                    ->label('Observaciones del expediente')
                    ->rows(5)
                    ->columnSpanFull()
                    ->required()
                    ->extraInputAttributes([
                        'style' => 'text-transform: uppercase',
                    ])
                    ->dehydrateStateUsing(
                        fn (?string $state): ?string => $state === null
                            ? null
                            : mb_strtoupper(trim($state), 'UTF-8')
                    ),
            ])
            ->fillForm(
                fn (TransferenciaExpediente $record): array => [
                    'codigo_referencia' =>
                        $record->correccionArchivo?->codigo_referencia
                        ?? $record->codigo_referencia,
                    'numero_caja' =>
                        $record->correccionArchivo?->numero_caja
                        ?? $record->numero_caja,
                    'procedencia' =>
                        $record->correccionArchivo?->procedencia
                        ?? $record->procedencia,
                    'serie_documental_parametro_id' =>
                        $record->correccionArchivo?->serie_documental_parametro_id
                        ?? $record->serie_documental_parametro_id,
                    'descripcion_lomo' =>
                        $record->correccionArchivo?->descripcion_lomo
                        ?? $record->descripcion_lomo,
                    'detalle' =>
                        $record->correccionArchivo?->detalle
                        ?? $record->detalle,
                    'tomo_volumen' =>
                        $record->correccionArchivo?->tomo_volumen
                        ?? $record->tomo_volumen,
                    'fojas' =>
                        $record->correccionArchivo?->fojas
                        ?? $record->fojas,
                    'fechas_extremas' =>
                        $record->correccionArchivo?->fechas_extremas
                        ?? $record->fechas_extremas,
                    'soporte_parametro_id' =>
                        $record->correccionArchivo?->soporte_parametro_id
                        ?? $record->soporte_parametro_id,
                    'observaciones' =>
                        $record->correccionArchivo?->observaciones
                        ?? $record->observaciones,
                ]
            )
            ->modalHeading('Editar expediente')
            ->modalSubmitActionLabel('Guardar cambios')
            ->modalCancelActionLabel('Cancelar')
            ->action(function (TransferenciaExpediente $record, array $data) use ($page): void {
                try {
                    app(TransferenciaService::class)->actualizarExpedienteCorregidoArchivo(
                        transferencia: $page->transferencia,
                        expedienteGuid: $record->guid,
                        data: $data,
                    );
                    Notification::make()
                        ->success()
                        ->title('Corrección guardada')
                        ->body('La corrección del expediente fue registrada correctamente.')
                        ->send();
                } catch (\Throwable $e) {
                    Notification::make()
                        ->danger()
                        ->title('No se pudo guardar la corrección')
                        ->body($e->getMessage())
                        ->persistent()
                        ->send();
                }
            });
    }

    public function finalizar(): void
    {
        $observacion = trim(mb_strtoupper($this->observaciones, 'UTF-8'));
        if ($observacion === '') {
            $observacion = 'SIN OBSERVACIONES';
        }

        try {
            $transferencia = app(TransferenciaService::class)
                ->finalizar(
                    $this->transferencia,
                    $observacion
                );
            Notification::make()
                ->success()
                ->title('Transferencia finalizada')
                ->body("La transferencia {$transferencia->correlativo} fue finalizada correctamente.")
                ->send();
            $this->redirect(TransferenciaResource::getUrl('index'));
        } catch (\Throwable $e) {
            Notification::make()
                ->danger()
                ->title('No se pudo finalizar la transferencia')
                ->body($e->getMessage())
                ->persistent()
                ->send();
        }
    }
}
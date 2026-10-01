<?php

namespace App\Filament\Resources\Transferencias\Pages;

use App\Filament\Resources\Transferencias\TransferenciaResource;
use App\Models\Parametro;
use App\Models\Transferencia;
use App\Models\TransferenciaExpediente;
use App\Models\User;
use App\Services\TransferenciaService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CorregirTransferencia extends Page implements HasTable
{
    use InteractsWithTable;
    protected static string $resource = TransferenciaResource::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-pencil-square';
    protected string $view = 'filament.resources.transferencias.pages.corregir-transferencia';
    public Transferencia $transferencia;
    public string $observaciones = 'SIN OBSERVACIONES';

    public function mount(Transferencia $record): void
    {
        $usuario = User::find(Auth::id());
        abort_unless($usuario?->perfiles->contains('valor', 'TRANSFERENCIAS'), 403);
        abort_unless($record->usuario_solicitante_id === Auth::id(),403);
        $record->load([
            'fondo',
            'subfondo',
            'seccion',
            'usuarioSolicitante',
            'estado',
            'historial.usuario',
        ]);
        abort_unless($record->estado?->valor === 'OBSERVADO', 404);
        $this->transferencia = $record;
    }

    public function getTitle(): string
    {
        return "Corregir transferencia {$this->transferencia->correlativo}";
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
                    ->where('transferencia_id',$this->transferencia->getKey())
                    ->where('activo', true)
                    ->with([
                        'serieDocumental',
                        'soporte',
                    ])
            )
            ->columns([
                TextColumn::make('editado')
                    ->label('EDITADO')
                    ->state(
                        fn (TransferenciaExpediente $record): ?string =>
                            $record->usuario_actualizacion_id !== null
                            && $record->fecha_actualizacion?->ne(
                                $record->fecha_creacion
                            )
                                ? 'EDITADO'
                                : null
                    )
                    ->size('xs')
                    ->badge()
                    ->color('warning')
                    ->placeholder('—'),
                TextColumn::make('fecha_actualizacion')
                    ->label('FECHA DE EDICIÓN')
                    ->state(
                        fn (TransferenciaExpediente $record): ?string =>
                            $record->usuario_actualizacion_id !== null
                            ? $record->fecha_actualizacion?->format('d/m/Y H:i')
                            : null
                    )
                    ->size('xs')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('codigo_referencia')
                    ->label('CÓDIGO DE REFERENCIA')
                    ->size('xs')
                    ->searchable(),
                TextColumn::make('numero_caja')
                    ->label('N° DE CAJA')
                    ->size('xs')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('serieDocumental.valor')
                    ->label('SERIE DOCUMENTAL')
                    ->size('xs')
                    ->searchable(),
                TextColumn::make('descripcion_lomo')
                    ->label('DESCRIPCIÓN DOCUMENTAL')
                    ->size('xs')
                    ->limit(80),
                TextColumn::make('soporte.valor')
                    ->label('SOPORTE')
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
        $historialObservacion = $this->transferencia
            ->historial
            ->first(
                fn ($historial): bool =>
                    $historial->accion === 'OBSERVAR'
            );
        $usuarioObservador = $historialObservacion?->usuario;
        return $schema
            ->components([
                Section::make('Datos generales')
                    ->description(
                        'Información de la observación registrada.'
                    )
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('usuario_observador')
                            ->label('Usuario que revisó')
                            ->state(
                                fn (): string =>
                                    $usuarioObservador?->usuario ?? '—'
                            ),
                        TextEntry::make('fecha_solicitud')
                            ->label('Fecha inicio solicitud')
                            ->state(
                                fn (): string =>
                                    $this->transferencia
                                        ->fecha_solicitud
                                        ?->format('d/m/Y H:i') ?? '—'
                            ),
                        TextEntry::make('fecha_observacion')
                            ->label('Fecha de observación')
                            ->state(
                                fn (): string =>
                                    $historialObservacion
                                        ?->fecha_accion
                                        ?->format('d/m/Y H:i') ?? '—'
                            ),
                        TextEntry::make('observaciones')
                            ->label('Observaciones')
                            ->state(
                                fn (): string =>
                                    $historialObservacion
                                        ?->observacion
                                        ?? 'SIN OBSERVACIONES'
                            )
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
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
            ])
            ->modalHeading(
                fn (TransferenciaExpediente $record): string =>
                    "Expediente {$record->codigo_referencia}"
            )
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar');
    }

    private static function editarExpedienteAction(CorregirTransferencia $page): Action {
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
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->step(1)
                    ->placeholder('-')
                    ->validationMessages([
                        'numeric' => 'El número de caja debe ser un número.',
                        'integer' => 'El número de caja debe ser un número entero.',
                        'min' => 'El número de caja debe ser mayor o igual a 1.',
                    ])
                    ->hintIcon(
                        'heroicon-m-information-circle',
                        tooltip: 'Ingrese únicamente un número entero. Ejemplo: 1, 2 o 3. Para empastados puede dejarlo vacío.'
                    ),
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
                    'codigo_referencia' => $record->codigo_referencia,
                    'numero_caja' => $record->numero_caja,
                    'serie_documental_parametro_id' => $record->serie_documental_parametro_id,
                    'descripcion_lomo' => $record->descripcion_lomo,
                    'detalle' => $record->detalle,
                    'tomo_volumen' => $record->tomo_volumen,
                    'fojas' => $record->fojas,
                    'fechas_extremas' => $record->fechas_extremas,
                    'soporte_parametro_id' => $record->soporte_parametro_id,
                    'observaciones' => $record->observaciones,
                ]
            )
            ->modalHeading('Editar expediente')
            ->modalSubmitActionLabel('Guardar cambios')
            ->modalCancelActionLabel('Cancelar')
            ->action(function (TransferenciaExpediente $record, array $data) use ($page): void {
                try {
                    app(TransferenciaService::class)
                        ->actualizarExpedienteCorregido(
                            transferencia: $page->transferencia,
                            expedienteGuid: $record->guid,
                            data: $data,
                        );
                    Notification::make()
                        ->success()
                        ->title('Expediente actualizado')
                        ->body(
                            'Los cambios fueron guardados correctamente.'
                        )
                        ->send();
                } catch (\Throwable $e) {
                    Notification::make()
                        ->danger()
                        ->title('No se pudo actualizar el expediente')
                        ->body($e->getMessage())
                        ->persistent()
                        ->send();
                }
            });
    }

    public function guardarCorreccion(): void
    {
        $observacion = trim(mb_strtoupper($this->observaciones, 'UTF-8'));
        if ($observacion === '') {
            $observacion = 'SIN OBSERVACIONES';
        }

        try {
            $transferencia = app(TransferenciaService::class)
                ->corregir(
                    transferencia: $this->transferencia,
                    observacion: $observacion,
                );
            Notification::make()
                ->success()
                ->title('Transferencia corregida')
                ->body("La transferencia {$transferencia->correlativo} " . 'fue enviada nuevamente a revisión.')
                ->send();
            $this->redirect(TransferenciaResource::getUrl('index'));
        } catch (\Throwable $e) {
            Notification::make()
                ->danger()
                ->title('No se pudo guardar la corrección')
                ->body($e->getMessage())
                ->persistent()
                ->send();
        }
    }
}
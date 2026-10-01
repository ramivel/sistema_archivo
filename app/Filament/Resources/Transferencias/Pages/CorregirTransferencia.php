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
                    ->where(
                        'transferencia_id',
                        $this->transferencia->getKey()
                    )
                    ->with([
                        'serieDocumental',
                        'soporte',
                    ])
            )
            ->columns([
                TextColumn::make('codigo_referencia')
                    ->label('CÓDIGO DE REFERENCIA')
                    ->searchable(),
                TextColumn::make('numero_caja')
                    ->label('N° DE CAJA')
                    ->searchable(),
                TextColumn::make('procedencia')
                    ->label('PROCEDENCIA')
                    ->formatStateUsing(
                        fn (?string $state): string =>
                            filled($state) ? $state : '-'
                    )
                    ->limit(80),

                TextColumn::make('serieDocumental.valor')
                    ->label('SERIE DOCUMENTAL')
                    ->searchable(),

                TextColumn::make('descripcion_lomo')
                    ->label('DESCRIPCIÓN DOCUMENTAL')
                    ->limit(80),

                TextColumn::make('soporte.valor')
                    ->label('SOPORTE'),
            ])
            ->recordActions([
                Action::make('editar')
                    ->label('Editar')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->schema([
                        TextInput::make('codigo_referencia')
                            ->label('Código de referencia')
                            ->required()
                            ->maxLength(50),

                        TextInput::make('numero_caja')
                            ->label('Número de caja')
                            ->nullable()
                            ->placeholder('-')
                            ->maxLength(50),

                        TextInput::make('procedencia')
                            ->label('Procedencia')
                            ->required()
                            ->maxLength(50),

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
                            ),

                        Textarea::make('descripcion_lomo')
                            ->label('Descripción documental')
                            ->rows(3)
                            ->columnSpanFull(),

                        Textarea::make('detalle')
                            ->label('Detalle')
                            ->rows(3)
                            ->columnSpanFull(),

                        TextInput::make('tomo_volumen')
                            ->label('Tomo / volumen')
                            ->maxLength(50),

                        TextInput::make('fojas')
                            ->label('Fojas')
                            ->maxLength(30),

                        TextInput::make('fechas_extremas')
                            ->label('Fechas extremas')
                            ->maxLength(30),

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
                            ),

                        Textarea::make('observaciones')
                            ->label('Observaciones del expediente')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->fillForm(
                        fn (TransferenciaExpediente $record): array => [
                            'codigo_referencia' => $record->codigo_referencia,
                            'numero_caja' => $record->numero_caja,
                            'procedencia' => $record->procedencia,
                            'serie_documental_parametro_id' =>
                                $record->serie_documental_parametro_id,
                            'descripcion_lomo' =>
                                $record->descripcion_lomo,
                            'detalle' => $record->detalle,
                            'tomo_volumen' => $record->tomo_volumen,
                            'fojas' => $record->fojas,
                            'fechas_extremas' =>
                                $record->fechas_extremas,
                            'soporte_parametro_id' =>
                                $record->soporte_parametro_id,
                            'observaciones' =>
                                $record->observaciones,
                        ]
                    )
                    ->modalHeading('Editar expediente')
                    ->modalSubmitActionLabel('Guardar cambios')
                    ->modalCancelActionLabel('Cancelar')
                    ->action(function (
                        TransferenciaExpediente $record,
                        array $data
                    ): void {
                        try {
                            app(TransferenciaService::class)
                                ->actualizarExpedienteCorregido(
                                    transferencia: $this->transferencia,
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
                                ->title(
                                    'No se pudo actualizar el expediente'
                                )
                                ->body($e->getMessage())
                                ->persistent()
                                ->send();
                        }
                    }),
            ])
            ->recordActionsPosition(RecordActionsPosition::BeforeColumns)
            ->paginated([50])
            ->defaultPaginationPageOption(50)
            ->recordUrl(null)
            ->striped();
    }
}
<?php

namespace App\Filament\Resources\Transferencias\Pages;

use App\Filament\Resources\Transferencias\TransferenciaResource;
use App\Models\Transferencia;
use App\Models\TransferenciaExpediente;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class VerTransferencia extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = TransferenciaResource::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-eye';
    protected string $view = 'filament.resources.transferencias.pages.ver-transferencia';
    public Transferencia $transferencia;

    public function mount(Transferencia $record): void
    {
        $usuario = User::find(Auth::id());
        abort_unless(
            $usuario instanceof User
            && (
                $usuario->esEncargadoArchivo()
                || (
                    $usuario->esTransferencias()
                    && $record->usuario_solicitante_id === $usuario->id
                )
            ),
            403
        );
        $record->load([
            'fondo',
            'subfondo',
            'seccion',
            'estado',
            'usuarioSolicitante',
            'expedientes.serieDocumental',
            'expedientes.soporte',
            'expedientes.correccionArchivo.serieDocumental',
            'expedientes.correccionArchivo.soporte',
            'expedientes.inventario',
            'historial.estadoAnterior',
            'historial.estadoNuevo',
            'historial.usuario',
        ]);
        $this->transferencia = $record;
    }

    public function getTitle(): string
    {
        return "Transferencia {$this->transferencia->correlativo}";
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('volver')
                ->label('Volver al listado')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(
                    fn (): string =>
                        TransferenciaResource::getUrl('index')
                ),
        ];
    }

    public function datosGenerales(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('correlativo')
                ->label('Correlativo')
                ->state(
                    fn (): string =>
                        $this->transferencia->correlativo
                ),
            TextEntry::make('estado')
                ->label('Estado actual')
                ->badge()
                ->state(
                    fn (): string =>
                        $this->transferencia->estado?->valor ?? '—'
                ),
            TextEntry::make('usuario_remitente')
                ->label('Usuario remitente')
                ->state(
                    fn (): string =>
                        $this->transferencia
                            ->usuarioSolicitante
                            ?->usuario ?? '—'
                ),
            TextEntry::make('fecha_solicitud')
                ->label('Fecha de solicitud')
                ->state(
                    fn (): string =>
                        $this->transferencia
                            ->fecha_solicitud
                            ?->format('d/m/Y H:i') ?? '—'
                ),
            TextEntry::make('fecha_finalizacion')
                ->label('Fecha de finalización')
                ->state(
                    fn (): string =>
                        $this->transferencia
                            ->fecha_finalizacion
                            ?->format('d/m/Y H:i') ?? '—'
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
            TextEntry::make('total_expedientes')
                ->label('Total de expedientes')
                ->state(
                    fn (): string =>
                        number_format(
                            $this->transferencia->total_expedientes
                        )
                ),
        ])
        ->columns(4);
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
                        'inventario',
                    ])
            )
            ->columns([
                TextColumn::make('inventario.codigo_inventario')
                    ->label('CÓDIGO DE INVENTARIO')
                    ->state(
                        fn (
                            TransferenciaExpediente $record
                        ): string =>
                            $record->inventario?->codigo_inventario ?? '—'
                    )
                    ->searchable()
                    ->size('xs'),
                TextColumn::make('codigo_referencia')
                    ->label('CÓDIGO DE REFERENCIA')
                    ->state(
                        fn (
                            TransferenciaExpediente $record
                        ): string =>
                            $record->correccionArchivo
                                ?->codigo_referencia
                            ?? $record->codigo_referencia
                            ?? '—'
                    )
                    ->searchable()
                    ->size('xs'),
                TextColumn::make('numero_caja')
                    ->label('N° DE CAJA')
                    ->state(
                        fn (
                            TransferenciaExpediente $record
                        ): string =>
                            $record->correccionArchivo
                                ?->numero_caja
                            ?? $record->numero_caja
                            ?? '—'
                    )
                    ->searchable()
                    ->size('xs'),
                TextColumn::make('procedencia')
                    ->label('PROCEDENCIA')
                    ->state(
                        fn (
                            TransferenciaExpediente $record
                        ): string =>
                            $record->correccionArchivo
                                ?->procedencia
                            ?? $record->procedencia
                            ?? '—'
                    )
                    ->searchable()
                    ->size('xs'),
                TextColumn::make('serieDocumental.valor')
                    ->label('SERIE DOCUMENTAL')
                    ->state(
                        fn (
                            TransferenciaExpediente $record
                        ): string =>
                            $record->correccionArchivo
                                ?->serieDocumental?->valor
                            ?? $record->serieDocumental?->valor
                            ?? '—'
                    )
                    ->searchable()
                    ->size('xs'),
                TextColumn::make('descripcion_lomo')
                    ->label('DESCRIPCIÓN DOCUMENTAL')
                    ->limit(80)
                    ->state(
                        fn (
                            TransferenciaExpediente $record
                        ): string =>
                            $record->correccionArchivo
                                ?->descripcion_lomo
                            ?? $record->descripcion_lomo
                            ?? '—'
                    )
                    ->searchable()
                    ->size('xs'),
                TextColumn::make('soporte.valor')
                    ->label('SOPORTE')
                    ->state(
                        fn (
                            TransferenciaExpediente $record
                        ): string =>
                            $record->correccionArchivo
                                ?->soporte?->valor
                            ?? $record->soporte?->valor
                            ?? '—'
                    )
                    ->searchable()
                    ->size('xs'),
            ])
            ->recordActions([
                self::verExpedienteAction(),
            ])
            ->recordActionsPosition(
                RecordActionsPosition::BeforeColumns
            )
            ->paginated([50])
            ->defaultPaginationPageOption(50)
            ->recordUrl(null)
            ->striped();
    }

    private static function verExpedienteAction(): Action
    {
        return Action::make('ver')
            ->label('Ver')
            ->icon('heroicon-o-eye')
            ->color('gray')
            ->schema([
                self::expedienteSection(
                    'Información del expediente',
                    false
                ),
                self::expedienteSection(
                    'Información del expediente corregido',
                    true
                ),
            ])
            ->modalHeading(
                fn (
                    TransferenciaExpediente $record
                ): string =>
                    "Expediente {$record->codigo_referencia}"
            )
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar');
    }

    private static function expedienteSection(
        string $titulo,
        bool $corregido
    ): Section {
        return Section::make($titulo)
            ->schema([
                TextEntry::make('codigo_referencia')
                    ->label('Código de referencia')
                    ->state(
                        fn (
                            TransferenciaExpediente $record
                        ): string =>
                            $corregido
                                ? $record->correccionArchivo
                                    ?->codigo_referencia ?? '—'
                                : $record->codigo_referencia ?? '—'
                    ),
                TextEntry::make('numero_caja')
                    ->label('N° de caja')
                    ->state(
                        fn (
                            TransferenciaExpediente $record
                        ): string =>
                            $corregido
                                ? $record->correccionArchivo
                                    ?->numero_caja ?? '—'
                                : $record->numero_caja ?? '—'
                    ),
                TextEntry::make('procedencia')
                    ->label('Procedencia')
                    ->state(
                        fn (
                            TransferenciaExpediente $record
                        ): string =>
                            $corregido
                                ? $record->correccionArchivo
                                    ?->procedencia ?? '—'
                                : $record->procedencia ?? '—'
                    ),
                TextEntry::make('serie_documental')
                    ->label('Serie documental')
                    ->state(
                        fn (
                            TransferenciaExpediente $record
                        ): string =>
                            $corregido
                                ? $record->correccionArchivo
                                    ?->serieDocumental?->valor ?? '—'
                                : $record->serieDocumental?->valor ?? '—'
                    ),
                TextEntry::make('soporte')
                    ->label('Soporte')
                    ->state(
                        fn (
                            TransferenciaExpediente $record
                        ): string =>
                            $corregido
                                ? $record->correccionArchivo
                                    ?->soporte?->valor ?? '—'
                                : $record->soporte?->valor ?? '—'
                    ),
                TextEntry::make('tomo_volumen')
                    ->label('Tomo / volumen')
                    ->state(
                        fn (
                            TransferenciaExpediente $record
                        ): string =>
                            $corregido
                                ? $record->correccionArchivo
                                    ?->tomo_volumen ?? '—'
                                : $record->tomo_volumen ?? '—'
                    ),
                TextEntry::make('fojas')
                    ->label('Fojas')
                    ->state(
                        fn (
                            TransferenciaExpediente $record
                        ): string =>
                            $corregido
                                ? $record->correccionArchivo
                                    ?->fojas ?? '—'
                                : $record->fojas ?? '—'
                    ),
                TextEntry::make('fechas_extremas')
                    ->label('Fechas extremas')
                    ->state(
                        fn (
                            TransferenciaExpediente $record
                        ): string =>
                            $corregido
                                ? $record->correccionArchivo
                                    ?->fechas_extremas ?? '—'
                                : $record->fechas_extremas ?? '—'
                    ),
                TextEntry::make('descripcion_lomo')
                    ->label('Descripción documental')
                    ->state(
                        fn (
                            TransferenciaExpediente $record
                        ): string =>
                            $corregido
                                ? $record->correccionArchivo
                                    ?->descripcion_lomo ?? '—'
                                : $record->descripcion_lomo ?? '—'
                    )
                    ->columnSpanFull(),
                TextEntry::make('detalle')
                    ->label('Detalle')
                    ->state(
                        fn (
                            TransferenciaExpediente $record
                        ): string =>
                            $corregido
                                ? $record->correccionArchivo
                                    ?->detalle ?? '—'
                                : $record->detalle ?? '—'
                    )
                    ->columnSpanFull(),
                TextEntry::make('observaciones')
                    ->label('Observaciones')
                    ->state(
                        fn (
                            TransferenciaExpediente $record
                        ): string =>
                            $corregido
                                ? $record->correccionArchivo
                                    ?->observaciones ?? '—'
                                : $record->observaciones ?? '—'
                    )
                    ->columnSpanFull(),
            ])
            ->columns(3)
            ->visible(
                fn (
                    TransferenciaExpediente $record
                ): bool =>
                    ! $corregido
                    || $record->correccionArchivo !== null
            );
    }

}
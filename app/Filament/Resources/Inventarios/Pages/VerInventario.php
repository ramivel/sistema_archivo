<?php

namespace App\Filament\Resources\Inventarios\Pages;

use App\Filament\Resources\Inventarios\InventarioResource;
use App\Models\InventarioExpediente;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use App\Models\InventarioPrestamo;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Enums\RecordActionsPosition;

class VerInventario extends Page implements HasTable
{
    use InteractsWithTable;
    protected static string $resource = InventarioResource::class;
    protected string $view = 'filament.resources.inventarios.pages.ver-inventario';
    public InventarioExpediente $expediente;

    public function mount(InventarioExpediente $record): void
    {
        abort_unless(InventarioResource::canAccess(), 403);
        $record->load([
            'oficina',
            'direccion',
            'area',
            'serieDocumental',
            'soporte',
            'estado',
            'prestamos.tiposConsulta',
            'prestamos.oficina',
            'prestamos.direccion',
            'prestamos.area',
            'historial.estadoAnterior',
            'historial.estadoNuevo',
            'historial.usuario',
        ]);
        $this->expediente = $record;
    }

    public function getTitle(): string
    {
        return "Expediente {$this->expediente->codigo_inventario}";
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('volver')
                ->label('Volver al inventario')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(
                    fn (): string =>
                        InventarioResource::getUrl('index')
                ),
        ];
    }

    public function datosGenerales(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos del expediente')
                    ->schema([
                        TextEntry::make('codigo_inventario')
                            ->label('Código de inventario')
                            ->state(
                                fn (): string => $this->expediente->codigo_inventario
                            ),
                        TextEntry::make('estado')
                            ->label('Estado')
                            ->badge()
                            ->state(
                                fn (): string =>
                                    $this->expediente
                                        ->estado?->valor ?? '—'
                            ),
                        TextEntry::make('origen')
                            ->label('Origen')
                            ->state(
                                fn (): string =>
                                    $this->expediente
                                        ->origen ?? '—'
                            ),
                        TextEntry::make('oficina')
                            ->label('Fondo')
                            ->state(
                                fn (): string =>
                                    $this->expediente
                                        ->oficina?->valor ?? '—'
                            ),
                        TextEntry::make('direccion')
                            ->label('Subfondo')
                            ->state(
                                fn (): string =>
                                    $this->expediente
                                        ->direccion?->valor ?? '—'
                            ),
                        TextEntry::make('area')
                            ->label('Sección')
                            ->state(
                                fn (): string =>
                                    $this->expediente
                                        ->area?->valor ?? '—'
                            ),
                        TextEntry::make('codigo_referencia')
                            ->label('Código de referencia')
                            ->state(
                                fn (): string =>
                                    $this->expediente
                                        ->codigo_referencia ?? '—'
                            ),
                        TextEntry::make('numero_caja')
                            ->label('N° de caja')
                            ->state(
                                fn (): string =>
                                    $this->expediente
                                        ->numero_caja ?? '—'
                            ),
                        TextEntry::make('procedencia')
                            ->label('Procedencia')
                            ->state(
                                fn (): string =>
                                    $this->expediente
                                        ->procedencia ?? '—'
                            ),
                        TextEntry::make('serie_documental')
                            ->label('Serie documental')
                            ->state(
                                fn (): string =>
                                    $this->expediente
                                        ->serieDocumental?->valor ?? '—'
                            ),
                        TextEntry::make('soporte')
                            ->label('Soporte')
                            ->state(
                                fn (): string =>
                                    $this->expediente
                                        ->soporte?->valor ?? '—'
                            ),
                        TextEntry::make('tomo_volumen')
                            ->label('Tomo / volumen')
                            ->state(
                                fn (): string =>
                                    (string) (
                                        $this->expediente
                                            ->tomo_volumen ?? '—'
                                    )
                            ),
                        TextEntry::make('fojas')
                            ->label('Fojas')
                            ->state(
                                fn (): string =>
                                    $this->expediente
                                        ->fojas ?? '—'
                            ),
                        TextEntry::make('fechas_extremas')
                            ->label('Fechas extremas')
                            ->state(
                                fn (): string =>
                                    $this->expediente
                                        ->fechas_extremas ?? '—'
                            ),
                        TextEntry::make('descripcion_lomo')
                            ->label('Descripción documental')
                            ->state(
                                fn (): string =>
                                    $this->expediente
                                        ->descripcion_lomo ?? '—'
                            )
                            ->columnSpanFull(),
                        TextEntry::make('detalle')
                            ->label('Detalle')
                            ->state(
                                fn (): string =>
                                    $this->expediente
                                        ->detalle ?? '—'
                            )
                            ->columnSpanFull(),
                        TextEntry::make('observaciones')
                            ->label('Observaciones')
                            ->state(
                                fn (): string =>
                                    $this->expediente
                                        ->observaciones ?? '—'
                            )
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                InventarioPrestamo::query()
                    ->where('inventario_expediente_id', $this->expediente->getKey())
                    ->with([
                        'tiposConsulta',
                        'oficina',
                        'direccion',
                        'area',
                        'usuarioSolicitante',
                        'usuarioRegistro',
                    ])
            )
            ->columns([
                TextColumn::make('numero_solicitud')
                    ->label('N° SOLICITUD')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('fecha_prestamo')
                    ->label('FECHA PRÉSTAMO')
                    ->date('d/m/Y')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('fecha_devolucion')
                    ->label('FECHA DEVOLUCIÓN')
                    ->date('d/m/Y')
                    ->placeholder('PENDIENTE')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('nombre_solicitante')
                    ->label('SOLICITANTE')
                    ->searchable(),
                TextColumn::make('telefono')
                    ->label('TELÉFONO')
                    ->searchable(),
                TextColumn::make('tiposConsulta.valor')
                    ->label('TIPO DE CONSULTA')
                    ->badge()
                    ->separator(',')
                    ->searchable(),
            ])
            ->recordActions([
                self::verPrestamoAction(),
            ])
            ->recordActionsPosition(
                RecordActionsPosition::BeforeColumns
            )
            ->defaultSort('fecha_prestamo', 'desc')
            ->paginated([50])
            ->defaultPaginationPageOption(50)
            ->striped()
            ->recordUrl(null);
    }

    private static function verPrestamoAction(): Action
    {
        return Action::make('ver')
            ->label('Ver')
            ->icon('heroicon-o-eye')
            ->color('gray')
            ->schema([
                Section::make('Datos del préstamo')
                    ->schema([
                        TextEntry::make('usuario_registro')
                            ->label('Registrado por')
                            ->state(
                                fn (
                                    InventarioPrestamo $record
                                ): string =>
                                    $record->usuarioRegistro?->usuario ?? '—'
                            ),
                        TextEntry::make('numero_solicitud')
                            ->label('N° solicitud')
                            ->state(
                                fn (
                                    InventarioPrestamo $record
                                ): string =>
                                    $record->numero_solicitud ?? '—'
                            ),
                        TextEntry::make('fecha_prestamo')
                            ->label('Fecha de préstamo')
                            ->state(
                                fn (
                                    InventarioPrestamo $record
                                ): string =>
                                    $record->fecha_prestamo
                                        ?->format('d/m/Y') ?? '—'
                            ),
                        TextEntry::make('nombre_solicitante')
                            ->label('Nombre del solicitante')
                            ->state(
                                fn (
                                    InventarioPrestamo $record
                                ): string =>
                                    $record->nombre_solicitante ?? '—'
                            ),
                        TextEntry::make('cargo')
                            ->label('Cargo')
                            ->state(
                                fn (
                                    InventarioPrestamo $record
                                ): string =>
                                    $record->cargo ?? '—'
                            ),
                        TextEntry::make('telefono')
                            ->label('Teléfono / Celular / Interno')
                            ->state(
                                fn (
                                    InventarioPrestamo $record
                                ): string =>
                                    $record->telefono ?? '—'
                            ),
                        TextEntry::make('oficina')
                            ->label('Oficina')
                            ->state(
                                fn (
                                    InventarioPrestamo $record
                                ): string =>
                                    $record->oficina?->valor ?? '—'
                            ),
                        TextEntry::make('direccion')
                            ->label('Dirección')
                            ->state(
                                fn (
                                    InventarioPrestamo $record
                                ): string =>
                                    $record->direccion?->valor ?? '—'
                            ),
                        TextEntry::make('area')
                            ->label('Área')
                            ->state(
                                fn (
                                    InventarioPrestamo $record
                                ): string =>
                                    $record->area?->valor ?? '—'
                            ),
                        TextEntry::make('tipos_consulta')
                            ->label('Tipo de consulta')
                            ->state(
                                fn (
                                    InventarioPrestamo $record
                                ): string =>
                                    $record->tiposConsulta
                                        ->pluck('valor')
                                        ->join(', ') ?: '—'
                            )
                            ->columnSpanFull(),
                        TextEntry::make('motivo_finalidad')
                            ->label('Motivo / Finalidad')
                            ->state(
                                fn (
                                    InventarioPrestamo $record
                                ): string =>
                                    $record->motivo_finalidad ?? '—'
                            )
                            ->columnSpanFull(),
                        TextEntry::make('fecha_devolucion')
                            ->label('Fecha de devolución')
                            ->state(
                                fn (
                                    InventarioPrestamo $record
                                ): string =>
                                    $record->fecha_devolucion
                                        ?->format('d/m/Y') ?? 'PENDIENTE'
                            ),
                        TextEntry::make('observaciones_devolucion')
                            ->label('Observaciones de devolución')
                            ->state(
                                fn (
                                    InventarioPrestamo $record
                                ): string =>
                                    $record->observaciones_devolucion ?? '—'
                            )
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
            ])
            ->modalHeading(
                fn (
                    InventarioPrestamo $record
                ): string =>
                    "Préstamo {$record->numero_solicitud}"
            )
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar');
    }
}
<?php

namespace App\Filament\Resources\Inventarios\Pages;

use App\Filament\Resources\Inventarios\InventarioResource;
use App\Filament\Resources\Inventarios\Tables\BuscadorInventarioTable;
use App\Models\InventarioExpediente;
use App\Models\Parametro;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Schemas\Components\Utilities\Get;

class BuscadorInventario extends Page implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static string $resource =
        InventarioResource::class;

    protected string $view =
        'filament.resources.inventarios.pages.buscador-inventario';

    public array $filtros = [];

    public bool $busquedaEjecutada = false;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Filtros')
                    ->description(
                        'Seleccione los criterios para realizar la búsqueda documental.'
                    )
                    ->schema([
                        Select::make('oficina_parametro_id')
                            ->label('Oficina')
                            ->placeholder('TODAS')
                            ->live()
                            ->options(fn () => Parametro::query()
                                ->where('grupo', 'FONDO')
                                ->where('activo', true)
                                ->whereNull('fecha_eliminacion')
                                ->orderBy('valor')
                                ->pluck('valor', 'id')
                            )
                            ->afterStateUpdated(function (callable $set): void {
                                $set('direccion_parametro_id', null);
                                $set('area_parametro_id', null);
                            }),

                        Select::make('direccion_parametro_id')
                            ->label('Dirección')
                            ->placeholder('TODAS')
                            ->live()
                            ->options(function (Get $get): array {
                                $oficinaId = $get('oficina_parametro_id');

                                if (blank($oficinaId)) {
                                    return [];
                                }

                                return Parametro::query()
                                    ->where('grupo', 'SUBFONDO')
                                    ->where('padre_id', $oficinaId)
                                    ->where('activo', true)
                                    ->whereNull('fecha_eliminacion')
                                    ->orderBy('valor')
                                    ->pluck('valor', 'id')
                                    ->toArray();
                            })
                            ->afterStateUpdated(function (callable $set): void {
                                $set('area_parametro_id', null);
                            }),

                        Select::make('area_parametro_id')
                            ->label('Área')
                            ->placeholder('TODAS')
                            ->live()
                            ->options(function (Get $get): array {
                                $direccionId = $get('direccion_parametro_id');

                                if (blank($direccionId)) {
                                    return [];
                                }

                                return Parametro::query()
                                    ->where('grupo', 'SECCION')
                                    ->where('padre_id', $direccionId)
                                    ->where('activo', true)
                                    ->whereNull('fecha_eliminacion')
                                    ->orderBy('valor')
                                    ->pluck('valor', 'id')
                                    ->toArray();
                            }),



                        Select::make('serie_documental_parametro_id')
                            ->label('Serie documental')
                            ->placeholder('TODAS')
                            ->options(
                                fn (): array =>
                                    self::parametroOptions(
                                        'SERIE_DOCUMENTAL'
                                    )
                            ),

                        Select::make('soporte_parametro_id')
                            ->label('Soporte')
                            ->placeholder('TODOS')
                            ->options(
                                fn (): array =>
                                    self::parametroOptions('SOPORTE')
                            ),

                        TextInput::make('texto')
                            ->label('Texto a buscar')
                            ->maxLength(255)
                            ->columnSpan(2),

                        Select::make('campo')
                            ->label('Campo de búsqueda')
                            ->placeholder('Seleccione un campo')
                            ->options([
                                'codigo_inventario' =>
                                    'Código de inventario',
                                'codigo_referencia' =>
                                    'Código de referencia',
                                'numero_caja' =>
                                    'Número de caja',
                                'procedencia' =>
                                    'Procedencia',
                                'descripcion_lomo' =>
                                    'Descripción documental',
                                'detalle' => 'Detalle',
                                'fechas_extremas' =>
                                    'Fechas extremas',
                            ]),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ])
            ->statePath('filtros');
    }

    public function buscar(): void
    {
        $filtros = $this->form->getState();

        $camposFiltros = collect($filtros)
            ->except(['campo', 'texto']);

        $hayFiltroSeleccionado = $camposFiltros
            ->filter(
                fn ($valor): bool =>
                    filled($valor)
            )
            ->isNotEmpty();

        $hayTexto = filled(
            trim((string) ($filtros['texto'] ?? ''))
        );

        if (! $hayFiltroSeleccionado && ! $hayTexto) {
            Notification::make()
                ->warning()
                ->title('Debe ingresar un criterio de búsqueda')
                ->body(
                    'Seleccione al menos un filtro o registre un texto de búsqueda.'
                )
                ->send();

            $this->busquedaEjecutada = false;
            $this->resetTable();

            return;
        }

        if ($hayTexto && blank($filtros['campo'] ?? null)) {
            Notification::make()
                ->warning()
                ->title('Seleccione el campo de búsqueda')
                ->body(
                    'Debe indicar dónde desea realizar la búsqueda.'
                )
                ->send();

            $this->busquedaEjecutada = false;
            $this->resetTable();

            return;
        }

        $this->filtros = $filtros;
        $this->busquedaEjecutada = true;
        $this->resetTable();
    }

    public function limpiar(): void
    {
        $this->filtros = [];
        $this->form->fill();
        $this->busquedaEjecutada = false;
        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        return BuscadorInventarioTable::configure(
            table: $table,
            filtros: $this->filtros,
            busquedaEjecutada: $this->busquedaEjecutada
        );
    }

    private static function parametroOptions(
        string $grupo
    ): array {
        return Parametro::query()
            ->where('grupo', $grupo)
            ->where('activo', true)
            ->whereNull('fecha_eliminacion')
            ->orderBy('valor')
            ->pluck('valor', 'id')
            ->toArray();
    }
}
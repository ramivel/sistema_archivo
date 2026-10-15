<?php

namespace App\Filament\Pages\Reportes;

use App\Enums\NavigationGroupEnum;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Filament\Pages\Reportes\Tables\ReporteInventarioTable;
use App\Filament\Resources\Inventarios\InventarioResource;
use App\Models\Parametro;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class ReportePorFondoSubfondoSeccion extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static string|\UnitEnum|null $navigationGroup = NavigationGroupEnum::REPORTES->value;
    protected static ?string $navigationLabel = 'Por Fondo/Subfondo/Sección';
    protected static ?string $title = 'Reporte por Fondo/Subfondo/Sección';
    protected static ?string $slug = 'reportes/fondo-subfondo-seccion';
    protected string $view = 'filament.pages.reportes.reporte-por-fondo-subfondo-seccion';
    public ?array $data = [];
    public array $filtros = [];
    public bool $reporteGenerado = false;

    public static function shouldRegisterNavigation(): bool
    {
        return self::canAccess();
    }

    public static function canViewAny(): bool
    {
        return self::canAccess();
    }

    public function mount(): void
    {
        abort_unless(self::canAccess(), 403);
        $this->form->fill();
    }

    public static function canAccess(array $parameters = []): bool
    {
        $usuario = User::find(Auth::id());
        return $usuario !== null && $usuario->esEncargadoArchivo();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('oficina_parametro_id')
                    ->label('Fondo')
                    ->required()
                    ->placeholder('Seleccione una opción')
                    ->options(
                        fn (): array => Parametro::query()
                            ->where('grupo', 'FONDO')
                            ->where('activo', true)
                            ->whereNull('fecha_eliminacion')
                            ->orderBy('valor')
                            ->pluck('valor', 'id')
                            ->toArray()
                    )
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function (Set $set): void {
                        $set('direccion_parametro_id', null);
                        $set('area_parametro_id', null);
                    })
                    ->validationMessages([
                        'required' => 'Debe seleccionar el fondo.',
                    ]),
                Select::make('direccion_parametro_id')
                    ->label('Subfondo')
                    ->required()
                    ->placeholder('Seleccione una opción')
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
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function (Set $set): void {
                        $set('area_parametro_id', null);
                    })
                    ->validationMessages([
                        'required' => 'Debe seleccionar el subfondo.',
                    ]),
                Select::make('area_parametro_id')
                    ->label('Sección')
                    ->placeholder('Seleccione una opción')
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
                    })
                    ->searchable()
                    ->preload()
                    ->live(),
            ])
            ->columns(3)
            ->statePath('data');
    }

    public function generarReporte(): void
    {
        $data = $this->form->getState();
        if ( blank($data['oficina_parametro_id'] ?? null) || blank($data['direccion_parametro_id'] ?? null)) {
            Notification::make()
                ->warning()
                ->title('Filtros incompletos')
                ->body('Debe seleccionar el Fondo y el Subfondo.')
                ->send();
            return;
        }
        $this->filtros = $data;
        $this->reporteGenerado = true;
        $this->resetTable();
    }

    public function limpiar(): void
    {
        $this->filtros = [];
        $this->form->fill();
        $this->reporteGenerado = false;
        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        return ReporteInventarioTable::configure(
            table: $table,
            filtros: $this->filtros,
            reporteGenerado: $this->reporteGenerado,
        );
    }

    public function urlImprimirReporte(): string
    {
        return route('reportes.inventario.pdf', array_filter([
            'oficina_parametro_id' => $this->filtros['oficina_parametro_id']
                ?? $this->filtros['fondo_parametro_id']
                ?? null,

            'direccion_parametro_id' => $this->filtros['direccion_parametro_id']
                ?? $this->filtros['subfondo_parametro_id']
                ?? null,

            'area_parametro_id' => $this->filtros['area_parametro_id']
                ?? $this->filtros['seccion_parametro_id']
                ?? null,
        ], fn ($valor) => $valor !== null && $valor !== ''));
    }

}
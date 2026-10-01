<?php

namespace App\Filament\Resources\Transferencias\Pages;

use App\Filament\Resources\Transferencias\TransferenciaResource;
use App\Models\Parametro;
use App\Models\User;
use App\Services\TransferenciaExcelService;
use App\Services\TransferenciaService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CrearTransferencia extends Page
{
    protected static string $resource = TransferenciaResource::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-up-tray';
    protected string $view = 'filament.resources.transferencias.pages.crear-transferencia';
    public ?array $data = [];
    public bool $archivoValidado = false;
    public ?array $resultadoValidacion = null;
    public string $observaciones = 'SIN OBSERVACIONES';

    public function mount(): void
    {
        $user = Auth::user();
        if ($user instanceof User && $user->esTransferencias()) {
            $this->form->fill([
                'fondo_parametro_id' => $user->oficina_parametro_id,
                'subfondo_parametro_id' => $user->direccion_parametro_id,
                'seccion_parametro_id' => $user->area_parametro_id,
            ]);
            return;
        }
        $this->form->fill();
    }

    public function getTitle(): string
    {
        return 'Nueva Transferencia';
    }

    public function form(Schema $schema): Schema
    {
        $user = Auth::user();
        return $schema
            ->components([
                Section::make('Datos de la transferencia')
                    ->description('Los campos marcados con * son obligatorios.')
                    ->schema([
                        Select::make('fondo_parametro_id')
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
                            ->disabled(fn (): bool => $user instanceof User && $user->esTransferencias())
                            ->dehydrated()
                            ->afterStateUpdated(
                                function (Set $set): void {
                                    $set('subfondo_parametro_id', null);
                                    $set('seccion_parametro_id', null);
                                }
                            ),
                        Select::make('subfondo_parametro_id')
                            ->label('Subfondo')
                            ->required()
                            ->placeholder('Seleccione una opción')
                            ->options(
                                function (Get $get): array {
                                    $fondoId = $get('fondo_parametro_id');
                                    if (blank($fondoId)) {
                                        return [];
                                    }
                                    return Parametro::query()
                                        ->where('grupo', 'SUBFONDO')
                                        ->where('padre_id', $fondoId)
                                        ->where('activo', true)
                                        ->whereNull('fecha_eliminacion')
                                        ->orderBy('valor')
                                        ->pluck('valor', 'id')
                                        ->toArray();
                                }
                            )
                            ->searchable()
                            ->preload()
                            ->live()
                            ->disabled(fn (): bool => $user instanceof User && $user->esTransferencias())
                            ->dehydrated()
                            ->afterStateUpdated(
                                function (Set $set): void {
                                    $set('seccion_parametro_id', null);
                                }
                            ),
                        Select::make('seccion_parametro_id')
                            ->label('Sección')
                            ->placeholder('Seleccione una opción')
                            ->options(
                                function (Get $get): array {
                                    $subfondoId = $get('subfondo_parametro_id');
                                    if (blank($subfondoId)) {
                                        return [];
                                    }
                                    return Parametro::query()
                                        ->where('grupo', 'SECCION')
                                        ->where('padre_id', $subfondoId)
                                        ->where('activo', true)
                                        ->whereNull('fecha_eliminacion')
                                        ->orderBy('valor')
                                        ->pluck('valor', 'id')
                                        ->toArray();
                                }
                            )
                            ->searchable()
                            ->preload()
                            ->live()
                            ->disabled(fn (): bool => $user instanceof User && $user->esTransferencias())
                            ->dehydrated(),
                        FileUpload::make('archivo_excel')
                            ->label('Archivo Excel (Max 50 MB)')
                            ->acceptedFileTypes([
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'text/csv',
                            ])
                            ->disk('local')
                            ->directory('transferencias')
                            ->getUploadedFileNameForStorageUsing(
                                fn ($file): string =>
                                    'transferencia_' .
                                    now()->format('Ymd_His') .
                                    '_' .
                                    \Illuminate\Support\Str::lower(
                                        \Illuminate\Support\Str::random(8)
                                    ) .
                                    '.' .
                                    $file->getClientOriginalExtension()
                            )
                            ->required()
                            ->maxSize(51200)
                            ->helperText(
                                'Seleccione el archivo Excel que contiene los expedientes de la transferencia.'
                            )
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('volver')
                ->label('VOLVER AL LISTADO')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(
                    fn (): string => TransferenciaResource::getUrl('index')
                ),
        ];
    }

    public function validarArchivo(): void
    {
        $data = $this->form->getState();
        $fondo = Parametro::query()
            ->whereKey($data['fondo_parametro_id'] ?? null)
            ->where('grupo', 'FONDO')
            ->where('activo', true)
            ->whereNull('fecha_eliminacion')
            ->first();
        $subfondo = Parametro::query()
            ->whereKey($data['subfondo_parametro_id'] ?? null)
            ->where('grupo', 'SUBFONDO')
            ->where(
                'padre_id',
                $data['fondo_parametro_id'] ?? null
            )
            ->where('activo', true)
            ->whereNull('fecha_eliminacion')
            ->first();
        $seccion = Parametro::query()
            ->whereKey($data['seccion_parametro_id'] ?? null)
            ->where('grupo', 'SECCION')
            ->where(
                'padre_id',
                $data['subfondo_parametro_id'] ?? null
            )
            ->where('activo', true)
            ->whereNull('fecha_eliminacion')
            ->first();
        if (! $fondo || ! $subfondo) {
            Notification::make()
                ->danger()
                ->title('Datos incompletos')
                ->body('Debe seleccionar un Fondo y Subfondo válidos.')
                ->send();
            return;
        }
        $archivo = $data['archivo_excel'] ?? null;
        if (! is_string($archivo) || $archivo === '') {
            Notification::make()
                ->danger()
                ->title('Archivo requerido')
                ->body('Debe seleccionar un archivo Excel antes de validar.')
                ->send();
            return;
        }

        try {
            $resultado = app(TransferenciaExcelService::class)->validar(
                archivo: $archivo,
                disk: 'local',
                fondo: $fondo->valor,
                subfondo: $subfondo->valor,
                seccion: $seccion?->valor,
                esRegularizacion: false,
                procedenciaAutomatica: app(TransferenciaService::class)
                    ->generarProcedencia(
                        fondo: $fondo,
                        subfondo: $subfondo,
                        seccion: $seccion,
                    ),
            );
            $this->resultadoValidacion = $resultado;
            $this->archivoValidado = true;
            if (! empty($resultado['errores_generales']) || $resultado['total_invalidos'] > 0) {
                Storage::disk('local')->delete($archivo);
                $this->data['archivo_excel'] = null;
                $this->archivoValidado = false;
                $erroresGenerales = $resultado['errores_generales'] ?? [];
                Notification::make()
                    ->danger()
                    ->title('Archivo con errores')
                    ->body(implode("\n", $erroresGenerales))
                    ->persistent()
                    ->send();
                return;
            }
            Notification::make()
                ->success()
                ->title('Archivo validado correctamente')
                ->body("Se validaron {$resultado['total_registros']} registros correctamente.")
                ->send();
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($archivo);
            $this->data['archivo_excel'] = null;
            $this->archivoValidado = false;
            $this->resultadoValidacion = null;
            Notification::make()
                ->danger()
                ->title('Error al validar el archivo')
                ->body($e->getMessage())
                ->persistent()
                ->send();
        }
    }

    public function guardarTransferencia(): void
    {
        if (! $this->archivoValidado || ! $this->resultadoValidacion || empty($this->resultadoValidacion['expedientes'])) {
            Notification::make()
                ->danger()
                ->title('No se puede guardar')
                ->body('Debe validar correctamente el archivo antes de guardar la transferencia.')
                ->send();
            return;
        }
        $data = $this->form->getState();
        $archivo = $data['archivo_excel'] ?? null;
        if (! is_string($archivo) || $archivo === '') {
            Notification::make()
                ->danger()
                ->title('Archivo requerido')
                ->body('No se encontró el archivo Excel validado.')
                ->send();
            return;
        }
        if (! Storage::disk('local')->exists($archivo)) {
            Notification::make()
                ->danger()
                ->title('Archivo no encontrado')
                ->body('El archivo Excel validado ya no se encuentra disponible.')
                ->send();
            return;
        }
        $fondo = Parametro::query()
            ->whereKey($data['fondo_parametro_id'] ?? null)
            ->where('grupo', 'FONDO')
            ->where('activo', true)
            ->whereNull('fecha_eliminacion')
            ->first();
        $subfondo = Parametro::query()
            ->whereKey($data['subfondo_parametro_id'] ?? null)
            ->where('grupo', 'SUBFONDO')
            ->where(
                'padre_id',
                $data['fondo_parametro_id'] ?? null
            )
            ->where('activo', true)
            ->whereNull('fecha_eliminacion')
            ->first();
        $seccion = Parametro::query()
            ->whereKey($data['seccion_parametro_id'] ?? null)
            ->where('grupo', 'SECCION')
            ->where(
                'padre_id',
                $data['subfondo_parametro_id'] ?? null
            )
            ->where('activo', true)
            ->whereNull('fecha_eliminacion')
            ->first();
        if (! $fondo || ! $subfondo) {
            Notification::make()
                ->danger()
                ->title('Datos incompletos')
                ->body('Debe seleccionar un Fondo y Subfondo válidos.')
                ->send();
            return;
        }
        $observacion = trim(mb_strtoupper($this->observaciones,'UTF-8'));
        if ($observacion === '') {
            $observacion = 'SIN OBSERVACIONES';
        }

        try {
            $transferencia = app(TransferenciaService::class)->guardar(
                fondoId: $fondo->id,
                subfondoId: $subfondo->id,
                seccionId: $seccion?->id,
                archivo: $archivo,
                expedientes: $this->resultadoValidacion['expedientes'],
                observacion: $observacion,
                esRegularizacion: false,
            );
            $this->archivoValidado = false;
            $this->resultadoValidacion = null;
            Notification::make()
                ->title('Transferencia guardada correctamente')
                ->body("La transferencia {$transferencia->correlativo} fue registrada correctamente.")
                ->success()
                ->send();
            $this->redirect(TransferenciaResource::getUrl('index'));
        } catch (\Throwable $e) {
            Notification::make()
                ->danger()
                ->title('No se pudo guardar la transferencia')
                ->body($e->getMessage())
                ->persistent()
                ->send();
        }
    }
}
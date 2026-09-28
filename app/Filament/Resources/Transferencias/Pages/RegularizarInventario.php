<?php

namespace App\Filament\Resources\Transferencias\Pages;

use App\Filament\Resources\Transferencias\TransferenciaResource;
use App\Services\TransferenciaExcelService;
use App\Models\Parametro;
use App\Models\Transferencia;
use App\Models\TransferenciaExpediente;
use App\Models\TransferenciaHistorial;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RegularizarInventario extends Page
{
    protected static string $resource = TransferenciaResource::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected string $view = 'filament.resources.transferencias.pages.regularizar-inventario';
    public ?array $data = [];
    public bool $archivoValidado = false;
    public ?array $resultadoValidacion = null;
    public string $observaciones = 'SIN OBSERVACIONES';

    public function mount(): void
    {
        $this->form->fill();
    }

    public function getTitle(): string
    {
        return 'Regularizar Inventario';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos de la regularización')
                    ->description('Los campos marcados con * son obligatorios.')
                    ->schema([
                        Select::make('fondo_parametro_id')
                            ->label('Fondo')
                            ->required()
                            ->placeholder('Seleccione una opción')
                            ->options(fn (): array => Parametro::query()
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
                            ->preload(),
                        FileUpload::make('archivo_excel')
                            ->label('Archivo Excel (Max 50 MB)')
                            ->acceptedFileTypes([
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'text/csv',
                            ])
                            ->disk('local')
                            ->directory('transferencias/regularizaciones')
                            ->getUploadedFileNameForStorageUsing(
                                fn ($file): string =>
                                    'regularizacion_' .
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
                                'Seleccione el archivo Excel que contiene los expedientes a regularizar.'
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
                ->body(
                    'Debe seleccionar un Fondo y Subfondo válidos.'
                )
                ->send();
            return;
        }
        $archivo = $data['archivo_excel'] ?? null;
        if (! is_string($archivo) || $archivo === '') {
            Notification::make()
                ->danger()
                ->title('Archivo requerido')
                ->body(
                    'Debe seleccionar un archivo Excel antes de validar.'
                )
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
            );
            $this->resultadoValidacion = $resultado;
            $this->archivoValidado = true;
            if (!empty($resultado['errores_generales']) || $resultado['total_invalidos'] > 0) {
                Storage::disk('local')->delete($archivo);
                $this->data['archivo_excel'] = null;
                Notification::make()
                    ->danger()
                    ->title('Archivo con errores')
                    ->body('Se encontraron errores durante la validación.')
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

    public function guardarRegularizacion(): void
    {
        if (! $this->archivoValidado || ! $this->resultadoValidacion || empty($this->resultadoValidacion['expedientes'])) {
            Notification::make()
                ->danger()
                ->title('No se puede guardar')
                ->body('Debe validar correctamente el archivo antes de guardar la regularización.')
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
                ->body('La ubicación archivística seleccionada ya no es válida.')
                ->send();
            return;
        }
        $observacion = trim(mb_strtoupper($this->observaciones,'UTF-8'));
        if ($observacion === '') {
            $observacion = 'SIN OBSERVACIONES';
        }

        try {
            $transferencia = DB::transaction(
                function () use (
                    $data,
                    $archivo,
                    $fondo,
                    $subfondo,
                    $seccion,
                    $observacion
                ): Transferencia {
                    $usuarioId = Auth::id();
                    $anio = now()->year;
                    $sigla = "{$fondo->sigla}/{$subfondo->sigla}/REG";
                    $correlativo = $this->generarCorrelativo(sigla: $sigla,anio: $anio);
                    $estadoTranferencia = Parametro::query()
                        ->where('grupo', 'ESTADO_TRANSFERENCIA')
                        ->where('valor', 'INICIADO')
                        ->where('activo', true)
                        ->whereNull('fecha_eliminacion')
                        ->firstOrFail();
                    $transferencia = Transferencia::create([
                        'correlativo' => $correlativo,
                        'fondo_parametro_id' => $fondo->id,
                        'subfondo_parametro_id' => $subfondo->id,
                        'seccion_parametro_id' => $seccion?->id,
                        'usuario_solicitante_id' => $usuarioId,
                        'estado_parametro_id' => $estadoTranferencia->id,
                        'archivo_excel' => $archivo,
                        'total_expedientes' => count($this->resultadoValidacion['expedientes']),
                        'fecha_solicitud' => now(),
                        'es_regularizacion' => true,
                        'usuario_creacion_id' => $usuarioId,
                    ]);
                    $series = Parametro::query()
                        ->where('grupo', 'SERIE_DOCUMENTAL')
                        ->where('activo', true)
                        ->whereNull('fecha_eliminacion')
                        ->get()
                        ->keyBy(
                            fn (Parametro $parametro): string =>
                                mb_strtoupper(trim($parametro->valor), 'UTF-8')
                        );
                    $soportes = Parametro::query()
                        ->where('grupo', 'SOPORTE')
                        ->where('activo', true)
                        ->whereNull('fecha_eliminacion')
                        ->get()
                        ->keyBy(
                            fn (Parametro $parametro): string =>
                                mb_strtoupper(trim($parametro->valor),'UTF-8')
                        );
                    $procedencias = Parametro::query()
                        ->where('grupo', 'PROCEDENCIA')
                        ->where('activo', true)
                        ->whereNull('fecha_eliminacion')
                        ->get()
                        ->keyBy(
                            fn (Parametro $parametro): string =>
                                mb_strtoupper(trim((string) $parametro->sigla),'UTF-8')
                        );
                    foreach ($this->resultadoValidacion['expedientes'] as $expediente) {
                        $serie = $series->get(
                            mb_strtoupper(trim((string) $expediente['serie_documental']), 'UTF-8')
                        );
                        $soporte = $soportes->get(
                            mb_strtoupper(trim((string) $expediente['soporte']), 'UTF-8')
                        );
                        if (! $serie || ! $soporte) {
                            throw new \RuntimeException(
                                'No se encontró una parametrización requerida durante el guardado.'
                            );
                        }
                        $registroExpediente = TransferenciaExpediente::create([
                            'transferencia_id' => $transferencia->id,
                            'codigo_referencia' => $expediente['codigo_referencia'],
                            'numero_caja' => $expediente['numero_caja'],
                            'serie_documental_parametro_id' => $serie->id,
                            'descripcion_lomo' => $expediente['descripcion_lomo'],
                            'detalle' => $expediente['detalle'],
                            'tomo_volumen' => $expediente['tomo_volumen'],
                            'fojas' => $expediente['fojas'],
                            'fechas_extremas' => $expediente['fechas_extremas'],
                            'soporte_parametro_id' => $soporte->id,
                            'observaciones' => $expediente['observaciones'],
                            'usuario_creacion_id' => $usuarioId,
                        ]);
                        $procedenciaIds = [];
                        foreach ($expediente['procedencias'] as $procedencia) {
                            $parametro = $procedencias->get(
                                mb_strtoupper(trim((string) $procedencia),'UTF-8')
                            );
                            if (! $parametro) {
                                throw new \RuntimeException(
                                    "No se encontró la procedencia {$procedencia} durante el guardado."
                                );
                            }
                            $procedenciaIds[] = $parametro->id;
                        }
                        if ($procedenciaIds !== []) {
                            $registroExpediente
                                ->procedencias()
                                ->attach($procedenciaIds);
                        }
                    }
                    TransferenciaHistorial::create([
                        'transferencia_id' => $transferencia->id,
                        'estado_anterior_parametro_id' => null,
                        'estado_nuevo_parametro_id' => $estadoTranferencia->id,
                        'accion' => 'INICIAR',
                        'fecha_accion' => now(),
                        'observacion' => $observacion,
                        'usuario_id' => $usuarioId,
                    ]);
                    $this->actualizarCorrelativo(
                        sigla: $sigla,
                        anio: $anio,
                        usuarioId: $usuarioId,
                    );
                    return $transferencia;
                }
            );
            $this->archivoValidado = false;
            $this->resultadoValidacion = null;
            Notification::make()
                ->success()
                ->title('Regularización guardada correctamente')
                ->body(
                    "La regularización {$transferencia->correlativo} fue registrada correctamente."
                )
                ->persistent()
                ->send();
            $this->redirect(TransferenciaResource::getUrl('index'));
        } catch (\Throwable $e) {
            Notification::make()
                ->danger()
                ->title('No se pudo guardar la regularización')
                ->body($e->getMessage())
                ->persistent()
                ->send();
        }
    }

    private function generarCorrelativo(
        string $sigla,
        int $anio,
    ): string {
        $correlativo = DB::table('correlativos')
            ->where('anio', $anio)
            ->where('sigla', $sigla)
            ->first();
        $secuencia = $correlativo ? $correlativo->secuencia + 1 : 1;
        return "{$sigla}/{$secuencia}/{$anio}";
    }

    private function actualizarCorrelativo(
        string $sigla,
        int $anio,
        int $usuarioId,
    ): void {
        $correlativo = DB::table('correlativos')
            ->where('anio', $anio)
            ->where('sigla', $sigla)
            ->lockForUpdate()
            ->first();
        if ($correlativo) {
            DB::table('correlativos')
                ->where('id', $correlativo->id)
                ->update([
                    'secuencia' => $correlativo->secuencia + 1,
                    'usuario_actualizacion_id' => $usuarioId,
                ]);
            return;
        }
        DB::table('correlativos')->insert([
            'anio' => $anio,
            'sigla' => $sigla,
            'secuencia' => 1,
            'usuario_creacion_id' => $usuarioId,
            'fecha_creacion' => now(),
        ]);
    }
}
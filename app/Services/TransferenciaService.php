<?php

namespace App\Services;

use App\Models\Parametro;
use App\Models\Transferencia;
use App\Models\TransferenciaExpediente;
use App\Models\TransferenciaHistorial;
use App\Models\TransferenciaExpedienteCorreccionArchivo;
use App\Models\InventarioExpediente;
use App\Models\InventarioHistorial;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransferenciaService
{
    public function guardar(
        int $fondoId,
        int $subfondoId,
        ?int $seccionId,
        string $archivo,
        array $expedientes,
        string $observacion,
        bool $esRegularizacion = false,
    ): Transferencia {
        return DB::transaction(function () use (
            $fondoId,
            $subfondoId,
            $seccionId,
            $archivo,
            $expedientes,
            $observacion,
            $esRegularizacion,
        ) {
            $usuarioId = Auth::id();
            $anio = now()->year;
            $fondo = Parametro::query()
                ->where('id', $fondoId)
                ->where('grupo', 'FONDO')
                ->where('activo', true)
                ->whereNull('fecha_eliminacion')
                ->firstOrFail();
            $subfondo = Parametro::query()
                ->where('id', $subfondoId)
                ->where('grupo', 'SUBFONDO')
                ->where('activo', true)
                ->whereNull('fecha_eliminacion')
                ->firstOrFail();
            $seccion = null;
            if ($seccionId) {
                $seccion = Parametro::query()
                    ->where('id', $seccionId)
                    ->where('grupo', 'SECCION')
                    ->where('activo', true)
                    ->whereNull('fecha_eliminacion')
                    ->firstOrFail();
            }
            $series = Parametro::query()
                ->where('grupo', 'SERIE_DOCUMENTAL')
                ->where('activo', true)
                ->whereNull('fecha_eliminacion')
                ->get()
                ->keyBy(
                    fn (Parametro $parametro) => $this->normalizarTexto($parametro->valor)
                );
            $soportes = Parametro::query()
                ->where('grupo', 'SOPORTE')
                ->where('activo', true)
                ->whereNull('fecha_eliminacion')
                ->get()
                ->keyBy(
                    fn (Parametro $parametro) => $this->normalizarTexto($parametro->valor)
                );
            $sigla = $this->generarSigla($fondo, $subfondo, $esRegularizacion);
            $correlativo = $this->generarCorrelativo($sigla, $anio);
            $estadoIniciado = Parametro::query()
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
                'estado_parametro_id' => $estadoIniciado->id,
                'archivo_excel' => $archivo,
                'total_expedientes' => count($expedientes),
                'fecha_solicitud' => now(),
                'es_regularizacion' => $esRegularizacion,
                'usuario_creacion_id' => $usuarioId,
            ]);
            foreach ($expedientes as $expediente) {
                $procedencia = $esRegularizacion ? trim((string) ($expediente['procedencia'] ?? '')) : $this->generarProcedencia(fondo: $fondo, subfondo: $subfondo, seccion: $seccion);
                if ($procedencia === '') {
                    throw new \RuntimeException(
                        'La procedencia es obligatoria para todos los expedientes.'
                    );
                }
                $serie = $series->get($this->normalizarTexto($expediente['serie_documental']));
                $soporte = $soportes->get($this->normalizarTexto($expediente['soporte']));
                if (! $serie || ! $soporte) {
                    throw new \RuntimeException(
                        'No se encontró una parametrización requerida durante el guardado.'
                    );
                }
                TransferenciaExpediente::create([
                    'transferencia_id' => $transferencia->id,
                    'codigo_referencia' => $this->normalizarTexto(
                        $expediente['codigo_referencia']
                    ),
                    'numero_caja' => $this->normalizarTexto(
                        $expediente['numero_caja'] ?? null
                    ),
                    'procedencia' => $this->normalizarTexto($procedencia),
                    'serie_documental_parametro_id' => $serie->id,
                    'descripcion_lomo' => $this->normalizarTexto(
                        $expediente['descripcion_lomo']
                    ),
                    'detalle' => $this->normalizarTexto(
                        $expediente['detalle']
                    ),
                    'tomo_volumen' => $expediente['tomo_volumen'],
                    'fojas' => $this->normalizarTexto(
                        $expediente['fojas']
                    ),
                    'fechas_extremas' => $this->normalizarTexto(
                        $expediente['fechas_extremas']
                    ),
                    'soporte_parametro_id' => $soporte->id,
                    'observaciones' => $this->normalizarTexto(
                        $expediente['observaciones']
                    ),
                    'usuario_creacion_id' => $usuarioId,
                ]);
            }
            TransferenciaHistorial::create([
                'transferencia_id' => $transferencia->id,
                'estado_anterior_parametro_id' => null,
                'estado_nuevo_parametro_id' => $estadoIniciado->id,
                'accion' => 'INICIAR',
                'fecha_accion' => now(),
                'observacion' => $observacion,
                'usuario_id' => $usuarioId,
            ]);
            $this->actualizarCorrelativo($sigla,$anio,$usuarioId);
            return $transferencia;
        });
    }

    private function generarSigla(
        Parametro $fondo,
        Parametro $subfondo,
        bool $esRegularizacion,
    ): string {
        $sigla = "{$fondo->sigla}/{$subfondo->sigla}";
        if ($esRegularizacion)
            $sigla .= '/REG';
        else
            $sigla .= '/TRANS';
        return $sigla;
    }

    private function generarCorrelativo(
        string $sigla,
        int $anio,
    ): string {
        $correlativo = DB::table('correlativos')
            ->where('anio', $anio)
            ->where('sigla', $sigla)
            ->lockForUpdate()
            ->first();
        $secuencia = $correlativo ? $correlativo->secuencia + 1 : 1;
        return $anio === 0 ? "{$sigla}/{$secuencia}" : "{$sigla}/{$secuencia}/{$anio}";
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
            'activo' => true,
            'usuario_creacion_id' => $usuarioId,
            'fecha_creacion' => now(),
        ]);
    }

    public function observar(
        Transferencia $transferencia,
        string $observacion,
    ): Transferencia {
        return DB::transaction(function () use ($transferencia, $observacion) {
            $transferencia->refresh();
            $estadoAnterior = $transferencia->estado()->firstOrFail();
            if (! in_array($estadoAnterior->valor, ['INICIADO', 'CORREGIDO'], true)) {
                throw new \RuntimeException(
                    'La transferencia no se encuentra en un estado que permita registrar observaciones.'
                );
            }
            $estadoObservado = Parametro::query()
                ->where('grupo', 'ESTADO_TRANSFERENCIA')
                ->where('valor', 'OBSERVADO')
                ->where('activo', true)
                ->whereNull('fecha_eliminacion')
                ->firstOrFail();
            $usuarioId = Auth::id();
            $observacion = $this->normalizarTexto($observacion);
            $transferencia->update([
                'estado_parametro_id' => $estadoObservado->id,
                'usuario_actualizacion_id' => $usuarioId,
            ]);
            TransferenciaHistorial::create([
                'transferencia_id' => $transferencia->id,
                'estado_anterior_parametro_id' => $estadoAnterior->id,
                'estado_nuevo_parametro_id' => $estadoObservado->id,
                'accion' => 'OBSERVAR',
                'fecha_accion' => now(),
                'observacion' => $observacion,
                'usuario_id' => $usuarioId,
            ]);
            return $transferencia->fresh(['estado']);
        });
    }

    public function actualizarExpedienteCorregido(
        Transferencia $transferencia,
        string $expedienteGuid,
        array $data,
    ): TransferenciaExpediente {
        return DB::transaction(function () use (
            $transferencia,
            $expedienteGuid,
            $data
        ) {
            if ($transferencia->estado?->valor !== 'OBSERVADO') {
                throw new \RuntimeException(
                    'La transferencia ya no permite modificaciones.'
                );
            }
            $expediente = TransferenciaExpediente::query()
                ->where('guid', $expedienteGuid)
                ->where(
                    'transferencia_id',
                    $transferencia->getKey()
                )
                ->firstOrFail();
            $expediente->update([
                'codigo_referencia' => $data['codigo_referencia'],
                'numero_caja' => $data['numero_caja'] ?? null,
                'serie_documental_parametro_id' => $data['serie_documental_parametro_id'],
                'descripcion_lomo' => $data['descripcion_lomo'] ?? null,
                'detalle' => $data['detalle'] ?? null,
                'tomo_volumen' => $data['tomo_volumen'] ?? null,
                'fojas' => $data['fojas'] ?? null,
                'fechas_extremas' => $data['fechas_extremas'] ?? null,
                'soporte_parametro_id' => $data['soporte_parametro_id'],
                'observaciones' => $data['observaciones'] ?? null,
                'usuario_actualizacion_id' => Auth::id(),
            ]);
            return $expediente->fresh([
                'serieDocumental',
                'soporte',
            ]);
        });
    }

    public function corregir(
        Transferencia $transferencia,
        string $observacion,
    ): Transferencia {
        return DB::transaction(function () use (
            $transferencia,
            $observacion
        ) {
            $transferencia->refresh();
            $estadoAnterior = $transferencia->estado()->firstOrFail();
            if ($estadoAnterior->valor !== 'OBSERVADO') {
                throw new \RuntimeException(
                    'La transferencia no se encuentra observada.'
                );
            }
            $estadoCorregido = Parametro::query()
                ->where('grupo', 'ESTADO_TRANSFERENCIA')
                ->where('valor', 'CORREGIDO')
                ->where('activo', true)
                ->whereNull('fecha_eliminacion')
                ->firstOrFail();
            $transferencia->update([
                'estado_parametro_id' => $estadoCorregido->id,
                'usuario_actualizacion_id' => Auth::id(),
            ]);
            TransferenciaHistorial::create([
                'transferencia_id' => $transferencia->getKey(),
                'estado_anterior_parametro_id' => $estadoAnterior->id,
                'estado_nuevo_parametro_id' => $estadoCorregido->id,
                'accion' => 'CORREGIR',
                'fecha_accion' => now(),
                'observacion' => $observacion,
                'usuario_id' => Auth::id(),
            ]);
            return $transferencia->fresh(['estado']);
        });
    }

    public function generarProcedencia(
        Parametro $fondo,
        Parametro $subfondo,
        ?Parametro $seccion = null,
    ): string {
        $procedencia = collect([
            $fondo->sigla,
            $subfondo->sigla,
            $seccion?->sigla,
        ])
            ->filter(fn (?string $sigla): bool => filled($sigla))
            ->implode('/');
        if ($procedencia === '') {
            throw new \RuntimeException(
                'No se pudo generar la procedencia porque faltan siglas.'
            );
        }
        return $procedencia;
    }

    public function solicitarAnulacion(
        Transferencia $transferencia,
        string $observacion,
    ): Transferencia {
        return DB::transaction(function () use (
            $transferencia,
            $observacion
        ) {
            $transferencia->refresh();
            $estadoAnterior = $transferencia->estado()->firstOrFail();
            if (! in_array(
                $estadoAnterior->valor,
                ['INICIADO', 'OBSERVADO', 'CORREGIDO', 'APROBADO'],
                true
            )) {
                throw new \RuntimeException(
                    'La transferencia no permite solicitar anulación.'
                );
            }
            $estadoSolicitud = Parametro::query()
                ->where('grupo', 'ESTADO_TRANSFERENCIA')
                ->where('valor', 'SOLICITUD DE ANULACIÓN')
                ->where('activo', true)
                ->whereNull('fecha_eliminacion')
                ->firstOrFail();
            $observacion = $this->normalizarTexto($observacion);
            if ($observacion === '') {
                throw new \RuntimeException(
                    'Debe registrar el motivo de la anulación.'
                );
            }
            $usuarioId = Auth::id();
            $transferencia->update([
                'estado_parametro_id' => $estadoSolicitud->id,
                'usuario_actualizacion_id' => $usuarioId,
            ]);
            TransferenciaHistorial::create([
                'transferencia_id' => $transferencia->id,
                'estado_anterior_parametro_id' => $estadoAnterior->id,
                'estado_nuevo_parametro_id' => $estadoSolicitud->id,
                'accion' => 'SOLICITAR ANULACIÓN',
                'fecha_accion' => now(),
                'observacion' => $observacion,
                'usuario_id' => $usuarioId,
            ]);
            return $transferencia->fresh(['estado']);
        });
    }

    public function aprobarAnulacion(
        Transferencia $transferencia,
    ): Transferencia {
        return DB::transaction(function () use ($transferencia) {
            $transferencia->refresh();
            $estadoAnterior = $transferencia->estado()->firstOrFail();
            if ($estadoAnterior->valor !== 'SOLICITUD DE ANULACIÓN') {
                throw new \RuntimeException(
                    'La transferencia no tiene una solicitud de anulación pendiente.'
                );
            }
            $estadoAnulado = Parametro::query()
                ->where('grupo', 'ESTADO_TRANSFERENCIA')
                ->where('valor', 'ANULADO')
                ->where('activo', true)
                ->whereNull('fecha_eliminacion')
                ->firstOrFail();
            $usuarioId = Auth::id();
            $transferencia->update([
                'estado_parametro_id' => $estadoAnulado->id,
                'usuario_actualizacion_id' => $usuarioId,
            ]);
            TransferenciaHistorial::create([
                'transferencia_id' => $transferencia->id,
                'estado_anterior_parametro_id' => $estadoAnterior->id,
                'estado_nuevo_parametro_id' => $estadoAnulado->id,
                'accion' => 'APROBAR ANULACIÓN',
                'fecha_accion' => now(),
                'observacion' => null,
                'usuario_id' => $usuarioId,
            ]);
            return $transferencia->fresh(['estado']);
        });
    }

    public function rechazarAnulacion(
        Transferencia $transferencia,
        string $observacion,
    ): Transferencia {
        return DB::transaction(function () use (
            $transferencia,
            $observacion
        ) {
            $transferencia->refresh();
            $estadoActual = $transferencia->estado()->firstOrFail();
            if ($estadoActual->valor !== 'SOLICITUD DE ANULACIÓN') {
                throw new \RuntimeException(
                    'La transferencia no tiene una solicitud de anulación pendiente.'
                );
            }
            $historialSolicitud = TransferenciaHistorial::query()
                ->where('transferencia_id', $transferencia->id)
                ->where('accion', 'SOLICITAR ANULACIÓN')
                ->latest('fecha_accion')
                ->firstOrFail();
            $estadoAnterior = Parametro::query()
                ->whereKey(
                    $historialSolicitud->estado_anterior_parametro_id
                )
                ->where('grupo', 'ESTADO_TRANSFERENCIA')
                ->firstOrFail();
            $observacion = $this->normalizarTexto($observacion);
            if ($observacion === '') {
                throw new \RuntimeException(
                    'Debe registrar el motivo del rechazo.'
                );
            }
            $usuarioId = Auth::id();
            $transferencia->update([
                'estado_parametro_id' => $estadoAnterior->id,
                'usuario_actualizacion_id' => $usuarioId,
            ]);
            TransferenciaHistorial::create([
                'transferencia_id' => $transferencia->id,
                'estado_anterior_parametro_id' => $estadoActual->id,
                'estado_nuevo_parametro_id' => $estadoAnterior->id,
                'accion' => 'RECHAZAR ANULACIÓN',
                'fecha_accion' => now(),
                'observacion' => $observacion,
                'usuario_id' => $usuarioId,
            ]);
            return $transferencia->fresh(['estado']);
        });
    }

    public function aprobarTransferencia(
        Transferencia $transferencia,
    ): Transferencia {
        return DB::transaction(function () use ($transferencia) {
            $transferencia->refresh();
            if ($transferencia->es_regularizacion) {
                throw new \RuntimeException(
                    'Las regularizaciones no pueden aprobarse mediante esta acción.'
                );
            }
            $estadoAnterior = $transferencia->estado()->firstOrFail();
            if (! in_array(
                $estadoAnterior->valor,
                ['INICIADO', 'CORREGIDO'],
                true
            )) {
                throw new \RuntimeException(
                    'La transferencia no se encuentra en un estado aprobable.'
                );
            }
            $estadoAprobado = Parametro::query()
                ->where('grupo', 'ESTADO_TRANSFERENCIA')
                ->where('valor', 'APROBADO')
                ->where('activo', true)
                ->whereNull('fecha_eliminacion')
                ->firstOrFail();
            $usuarioId = Auth::id();
            $transferencia->update([
                'estado_parametro_id' => $estadoAprobado->id,
                'usuario_actualizacion_id' => $usuarioId,
            ]);
            TransferenciaHistorial::create([
                'transferencia_id' => $transferencia->id,
                'estado_anterior_parametro_id' => $estadoAnterior->id,
                'estado_nuevo_parametro_id' => $estadoAprobado->id,
                'accion' => 'APROBAR',
                'fecha_accion' => now(),
                'observacion' => null,
                'usuario_id' => $usuarioId,
            ]);
            return $transferencia->fresh(['estado']);
        });
    }
    public function rechazarTransferencia(
        Transferencia $transferencia,
        string $observacion,
        ?string $archivoNotaRechazo = null,
    ): Transferencia {
        return DB::transaction(function () use (
            $transferencia,
            $observacion,
            $archivoNotaRechazo,
        ) {
            $transferencia->refresh();
            $estadoAnterior = $transferencia->estado()->firstOrFail();
            if (! in_array(
                $estadoAnterior->valor,
                ['INICIADO', 'CORREGIDO', 'APROBADO'],
                true
            )) {
                throw new \RuntimeException(
                    'La transferencia no se encuentra en un estado rechazable.'
                );
            }
            $estadoRechazado = Parametro::query()
                ->where('grupo', 'ESTADO_TRANSFERENCIA')
                ->where('valor', 'RECHAZADO')
                ->where('activo', true)
                ->whereNull('fecha_eliminacion')
                ->firstOrFail();
            $observacion = $this->normalizarTexto($observacion);
            if ($observacion === '') {
                throw new \RuntimeException(
                    'Debe registrar la observación del rechazo.'
                );
            }
            $usuarioId = Auth::id();
            $datosActualizacion = [
                'estado_parametro_id' => $estadoRechazado->id,
                'usuario_actualizacion_id' => $usuarioId,
            ];
            if (filled($archivoNotaRechazo))
                $datosActualizacion['archivo_nota_rechazo'] = $archivoNotaRechazo;
            $transferencia->update($datosActualizacion);
            TransferenciaHistorial::create([
                'transferencia_id' => $transferencia->id,
                'estado_anterior_parametro_id' => $estadoAnterior->id,
                'estado_nuevo_parametro_id' => $estadoRechazado->id,
                'accion' => 'RECHAZAR',
                'fecha_accion' => now(),
                'observacion' => $observacion,
                'usuario_id' => $usuarioId,
            ]);
            return $transferencia->fresh(['estado']);
        });
    }

    public function actualizarExpedienteCorregidoArchivo(
        Transferencia $transferencia,
        string $expedienteGuid,
        array $data
    ): TransferenciaExpedienteCorreccionArchivo {
        return DB::transaction(function () use (
            $transferencia,
            $expedienteGuid,
            $data
        ) {
            $estadoPermitido = $transferencia->es_regularizacion ? 'INICIADO' : 'APROBADO';
            if ($transferencia->estado?->valor !== $estadoPermitido) {
                throw new \RuntimeException(
                    'La transferencia no se encuentra en un estado válido para realizar correcciones.'
                );
            }
            $expediente = TransferenciaExpediente::query()
                ->where('guid', $expedienteGuid)
                ->where(
                    'transferencia_id',
                    $transferencia->getKey()
                )
                ->firstOrFail();
            $datos = [
                'codigo_referencia' => $data['codigo_referencia'],
                'numero_caja' => $data['numero_caja'] ?? null,
                'procedencia' => $data['procedencia'],
                'serie_documental_parametro_id' => $data['serie_documental_parametro_id'],
                'descripcion_lomo' => $data['descripcion_lomo'] ?? null,
                'detalle' => $data['detalle'] ?? null,
                'tomo_volumen' => $data['tomo_volumen'] ?? null,
                'fojas' => $data['fojas'] ?? null,
                'fechas_extremas' => $data['fechas_extremas'] ?? null,
                'soporte_parametro_id' => $data['soporte_parametro_id'],
                'observaciones' => $data['observaciones'] ?? null,
            ];
            $correccion = TransferenciaExpedienteCorreccionArchivo::query()
                ->where('transferencia_expediente_id', $expediente->getKey())
                ->first();
            if ($correccion) {
                $correccion->update([
                    ...$datos,
                    'usuario_actualizacion_id' => Auth::id(),
                    'fecha_actualizacion' => now(),
                ]);
            } else {
                $correccion = TransferenciaExpedienteCorreccionArchivo::create([
                    ...$datos,
                    'transferencia_expediente_id' =>
                        $expediente->getKey(),
                    'usuario_creacion_id' => Auth::id(),
                    'fecha_creacion' => now(),
                ]);
            }
            return $correccion->fresh([
                'serieDocumental',
                'soporte',
            ]);
        });
    }

    public function finalizar(
        Transferencia $transferencia,
        string $observacion,
    ): Transferencia {
        return DB::transaction(function () use (
            $transferencia,
            $observacion
        ) {
            $transferencia->refresh();
            $estadoAnterior = $transferencia->estado()->firstOrFail();
            $estadoPermitido = $transferencia->es_regularizacion ? 'INICIADO' : 'APROBADO';
            if ($estadoAnterior->valor !== $estadoPermitido) {
                throw new \RuntimeException(
                    'La transferencia no se encuentra en un estado válido para finalizar.'
                );
            }
            $estadoFinalizado = Parametro::query()
                ->where('grupo', 'ESTADO_TRANSFERENCIA')
                ->where('valor', 'FINALIZADO')
                ->where('activo', true)
                ->whereNull('fecha_eliminacion')
                ->firstOrFail();
            $transferencia->update([
                'estado_parametro_id' => $estadoFinalizado->id,
                'fecha_finalizacion' => now(),
                'usuario_actualizacion_id' => Auth::id(),
            ]);
            TransferenciaHistorial::create([
                'transferencia_id' => $transferencia->getKey(),
                'estado_anterior_parametro_id' => $estadoAnterior->id,
                'estado_nuevo_parametro_id' => $estadoFinalizado->id,
                'accion' => 'FINALIZAR',
                'fecha_accion' => now(),
                'observacion' => $observacion,
                'usuario_id' => Auth::id(),
            ]);
            return $transferencia->fresh(['estado']);
        });
    }

    public function migrarAlInventario(
        Transferencia $transferencia,
        ?string $archivoFormularioFirmado
    ): Transferencia {
        return DB::transaction(function () use (
            $transferencia,
            $archivoFormularioFirmado
        ): Transferencia {
            $transferencia->load([
                'estado',
                'usuarioSolicitante.oficina',
                'usuarioSolicitante.direccion',
                'usuarioSolicitante.area',
                'expedientes.correccionArchivo',
                'expedientes.serieDocumental',
                'expedientes.soporte',
            ]);
            if ($transferencia->estado?->valor !== 'FINALIZADO') {
                throw new \RuntimeException(
                    'Solo se pueden incorporar transferencias finalizadas.'
                );
            }
            $estadoDisponible = Parametro::query()
                ->where('grupo', 'ESTADO_EXPEDIENTE')
                ->where('valor', 'DISPONIBLE')
                ->where('activo', true)
                ->whereNull('fecha_eliminacion')
                ->firstOrFail();
            $estadoInventariado = Parametro::query()
                ->where('grupo', 'ESTADO_TRANSFERENCIA')
                ->where('valor', 'INVENTARIADO')
                ->where('activo', true)
                ->whereNull('fecha_eliminacion')
                ->firstOrFail();
            $oficina = $transferencia->usuarioSolicitante?->oficina;
            $direccion = $transferencia->usuarioSolicitante?->direccion;
            $area = $transferencia->usuarioSolicitante?->area;
            if (! $oficina || ! $direccion) {
                throw new \RuntimeException(
                    'El usuario remitente no tiene oficina o dirección configurada.'
                );
            }
            foreach (
                $transferencia->expedientes
                    ->where('activo', true)
                    ->sortBy('id')
                    ->values() as $expediente
            ) {
                if (
                    InventarioExpediente::query()
                        ->where(
                            'transferencia_expediente_id',
                            $expediente->getKey()
                        )
                        ->exists()
                ) {
                    continue;
                }
                $correccion = $expediente->correccionArchivo;
                $origen = $correccion ?? $expediente;
                $siglaInventario = "{$oficina->sigla}/{$direccion->sigla}";
                $codigoInventario = $this->generarCorrelativo($siglaInventario,0);
                $inventario = InventarioExpediente::create([
                    'codigo_inventario' => $codigoInventario,
                    'oficina_parametro_id' => $oficina->getKey(),
                    'direccion_parametro_id' => $direccion->getKey(),
                    'area_parametro_id' => $area?->getKey(),
                    'codigo_referencia' => $origen->codigo_referencia,
                    'numero_caja' => $origen->numero_caja,
                    'procedencia' => $origen->procedencia,
                    'serie_documental_parametro_id' => $origen->serie_documental_parametro_id,
                    'descripcion_lomo' => $origen->descripcion_lomo,
                    'detalle' => $origen->detalle,
                    'tomo_volumen' => $origen->tomo_volumen,
                    'fojas' => $origen->fojas,
                    'fechas_extremas' => $origen->fechas_extremas,
                    'soporte_parametro_id' => $origen->soporte_parametro_id,
                    'observaciones' => $origen->observaciones,
                    'estado_parametro_id' => $estadoDisponible->getKey(),
                    'origen' => 'TRANSFERENCIA',
                    'transferencia_expediente_id' => $expediente->getKey(),
                    'usuario_creacion_id' => Auth::id(),
                ]);
                InventarioHistorial::create([
                    'inventario_expediente_id' => $inventario->getKey(),
                    'accion' => 'INCORPORAR DESDE TRANSFERENCIA',
                    'estado_anterior_parametro_id' => null,
                    'estado_nuevo_parametro_id' => $estadoDisponible->getKey(),
                    'observacion' => $correccion
                        ? 'Expediente incorporado utilizando la corrección realizada por Archivo.'
                        : 'Expediente incorporado desde la transferencia.',
                    'datos_anteriores' => null,
                    'datos_nuevos' => $inventario->toArray(),
                    'usuario_id' => Auth::id(),
                    'fecha_accion' => now(),
                ]);
                $this->actualizarCorrelativo($siglaInventario, 0, Auth::id());
            }
            $datosActualizacion = [
                'estado_parametro_id' => $estadoInventariado->getKey(),
                'usuario_actualizacion_id' => Auth::id(),
            ];
            if (filled($archivoFormularioFirmado))
                $datosActualizacion['archivo_formulario_firmado'] = $archivoFormularioFirmado;
            $transferencia->update($datosActualizacion);
            TransferenciaHistorial::create([
                'transferencia_id' => $transferencia->getKey(),
                'estado_anterior_parametro_id' => $transferencia->estado_parametro_id,
                'estado_nuevo_parametro_id' => $estadoInventariado->getKey(),
                'accion' => 'INCORPORAR AL INVENTARIO',
                'fecha_accion' => now(),
                'observacion' => 'Transferencia incorporada al inventario general.',
                'usuario_id' => Auth::id(),
            ]);
            return $transferencia->fresh(['estado']);
        });
    }

    private function normalizarTexto(mixed $valor): ?string
    {
        if (blank($valor)) {
            return null;
        }
        return mb_strtoupper(
            trim((string) $valor),
            'UTF-8'
        );
    }

}
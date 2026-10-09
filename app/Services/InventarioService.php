<?php

namespace App\Services;

use App\Models\InventarioExpediente;
use App\Models\InventarioHistorial;
use App\Models\InventarioPrestamo;
use App\Models\Parametro;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventarioService
{
    public function crear(array $data): InventarioExpediente
    {
        return DB::transaction(function () use ($data) {
            $oficina = $this->parametro($data['oficina_parametro_id'], 'FONDO');
            $direccion = $this->parametro($data['direccion_parametro_id'], 'SUBFONDO');
            $estado = $this->estado('DISPONIBLE');
            $sigla = "{$oficina->sigla}/{$direccion->sigla}";
            $codigoInventario = $this->generarCodigo($sigla);
            $expediente = InventarioExpediente::create([
                'codigo_inventario' => $codigoInventario,
                'oficina_parametro_id' => $oficina->id,
                'direccion_parametro_id' => $direccion->id,
                'area_parametro_id' => $data['area_parametro_id'] ?? null,
                'codigo_referencia' => $this->normalizarTexto($data['codigo_referencia']),
                'numero_caja' => $this->normalizarTexto($data['numero_caja'] ?? null),
                'procedencia' => $this->normalizarTexto($data['procedencia']),
                'serie_documental_parametro_id' => $data['serie_documental_parametro_id'],
                'descripcion_lomo' => $this->normalizarTexto($data['descripcion_lomo']),
                'detalle' => $this->normalizarTexto($data['detalle']),
                'tomo_volumen' => $data['tomo_volumen'],
                'fojas' => $this->normalizarTexto($data['fojas']),
                'fechas_extremas' => $this->normalizarTexto($data['fechas_extremas']),
                'soporte_parametro_id' => $data['soporte_parametro_id'],
                'observaciones' => $this->normalizarTexto($data['observaciones']),
                'archivo_pdf' => $data['archivo_pdf'] ?? null,
                'estado_parametro_id' => $estado->id,
                'origen' => 'MANUAL',
                'usuario_creacion_id' => Auth::id(),
            ]);
            $this->actualizarCorrelativo($sigla, Auth::id());
            $this->registrarHistorial(
                expediente: $expediente,
                accion: 'REGISTRAR',
                estadoAnterior: null,
                estadoNuevo: $estado->id,
                observacion: 'EXPEDIENTE REGISTRADO MANUALMENTE.',
                datosAnteriores: null,
                datosNuevos: $expediente->getAttributes()
            );
            return $expediente->fresh();
        });
    }

    public function actualizar(
        InventarioExpediente $expediente,
        array $data
    ): InventarioExpediente {
        return DB::transaction(function () use ($expediente, $data) {
            $estadoAnterior = $expediente->estado_parametro_id;
            $datosAnteriores = $expediente->getAttributes();
            $expediente->update([
                'oficina_parametro_id' => $data['oficina_parametro_id'],
                'direccion_parametro_id' => $data['direccion_parametro_id'],
                'area_parametro_id' => $data['area_parametro_id'] ?? null,
                'codigo_referencia' => $this->normalizarTexto($data['codigo_referencia']),
                'numero_caja' => $this->normalizarTexto($data['numero_caja'] ?? null),
                'procedencia' => $this->normalizarTexto($data['procedencia']),
                'serie_documental_parametro_id' => $data['serie_documental_parametro_id'],
                'descripcion_lomo' => $this->normalizarTexto($data['descripcion_lomo']),
                'detalle' => $this->normalizarTexto($data['detalle']),
                'tomo_volumen' => $data['tomo_volumen'],
                'fojas' => $this->normalizarTexto($data['fojas']),
                'fechas_extremas' => $this->normalizarTexto($data['fechas_extremas']),
                'soporte_parametro_id' => $data['soporte_parametro_id'],
                'observaciones' => $this->normalizarTexto($data['observaciones']),
                'archivo_pdf' => $data['archivo_pdf'] ?? $expediente->archivo_pdf,
                'usuario_actualizacion_id' => Auth::id(),
            ]);
            $this->registrarHistorial(
                expediente: $expediente,
                accion: 'ACTUALIZAR',
                estadoAnterior: $estadoAnterior,
                estadoNuevo: $expediente->estado_parametro_id,
                observacion: 'EXPEDIENTE ACTUALIZADO.',
                datosAnteriores: $datosAnteriores,
                datosNuevos: $expediente->getAttributes()
            );
            return $expediente->fresh();
        });
    }

    public function prestar(
        InventarioExpediente $expediente,
        array $data
    ): InventarioPrestamo {
        return DB::transaction(function () use (
            $expediente,
            $data
        ): InventarioPrestamo {
            $expediente->load('estado');
            if ($expediente->estado?->valor !== 'DISPONIBLE') {
                throw new \RuntimeException(
                    'El expediente no se encuentra disponible para préstamo.'
                );
            }
            $estadoAnterior = $expediente->estado_parametro_id;
            $datosAnteriores = $expediente->getAttributes();
            $estadoPrestado = $this->estado('PRESTADO');
            $prestamo = InventarioPrestamo::create([
                'inventario_expediente_id' => $expediente->id,
                'usuario_solicitante_id' => $data['usuario_solicitante_id'] ?? null,
                'usuario_registro_id' => Auth::id(),
                'numero_solicitud' => $this->normalizarTexto($data['numero_solicitud']),
                'fecha_prestamo' => $data['fecha_prestamo'],
                'nombre_solicitante' => $this->normalizarTexto($data['nombre_solicitante']),
                'cargo' => $this->normalizarTexto($data['cargo']),
                'oficina_parametro_id' => $data['oficina_parametro_id'],
                'direccion_parametro_id' => $data['direccion_parametro_id'],
                'area_parametro_id' => $data['area_parametro_id'] ?? null,
                'telefono' => $this->normalizarTexto($data['telefono']),
                'motivo_finalidad' => $this->normalizarTexto($data['motivo_finalidad']),
            ]);
            $prestamo->tiposConsulta()->syncWithPivotValues(
                $data['tipos_consulta'],
                [
                    'fecha_creacion' => now(),
                ]
            );
            $expediente->update([
                'estado_parametro_id' => $estadoPrestado->id,
                'usuario_actualizacion_id' => Auth::id(),
            ]);
            $expediente->refresh();
            $this->registrarHistorial(
                expediente: $expediente,
                accion: 'PRESTAR',
                estadoAnterior: $estadoAnterior,
                estadoNuevo: $estadoPrestado->id,
                observacion: $prestamo->motivo_finalidad,
                datosAnteriores: $datosAnteriores,
                datosNuevos: $expediente->getAttributes()
            );
            return $prestamo->fresh([
                'tiposConsulta',
                'usuarioSolicitante',
                'oficina',
                'direccion',
                'area',
            ]);
        });
    }

    public function devolver(
        InventarioExpediente $expediente,
        array $data
    ): InventarioPrestamo {
        return DB::transaction(function () use (
            $expediente,
            $data
        ): InventarioPrestamo {
            $expediente->load('estado');
            if ($expediente->estado?->valor !== 'PRESTADO') {
                throw new \RuntimeException(
                    'El expediente no se encuentra prestado.'
                );
            }
            $prestamo = $expediente->prestamos()
                ->whereNull('fecha_devolucion')
                ->latest('fecha_prestamo')
                ->first();
            if (! $prestamo) {
                throw new \RuntimeException(
                    'No se encontró un préstamo activo.'
                );
            }
            $estadoAnterior = $expediente->estado_parametro_id;
            $datosAnteriores = $expediente->getAttributes();
            $estadoDisponible = $this->estado('DISPONIBLE');
            $prestamo->update([
                'fecha_devolucion' => $data['fecha_devolucion'],
                'observaciones_devolucion' =>
                    $this->normalizarTexto(
                        $data['observaciones_devolucion']
                    ),
            ]);
            $expediente->update([
                'estado_parametro_id' => $estadoDisponible->id,
                'usuario_actualizacion_id' => Auth::id(),
            ]);
            $expediente->refresh();
            $this->registrarHistorial(
                expediente: $expediente,
                accion: 'DEVOLVER',
                estadoAnterior: $estadoAnterior,
                estadoNuevo: $estadoDisponible->id,
                observacion: $prestamo->observaciones_devolucion,
                datosAnteriores: $datosAnteriores,
                datosNuevos: $expediente->getAttributes()
            );
            return $prestamo->fresh([
                'tiposConsulta',
                'usuarioSolicitante',
                'oficina',
                'direccion',
                'area',
            ]);
        });
    }

    public function darDeBaja(
        InventarioExpediente $expediente,
        string $motivo
    ): InventarioExpediente {
        return DB::transaction(function () use (
            $expediente,
            $motivo
        ) {
            $expediente->load('estado');

            if ($expediente->estado?->valor === 'PRESTADO') {
                throw new \RuntimeException(
                    'No se puede dar de baja un expediente prestado.'
                );
            }

            if ($expediente->estado?->valor === 'BAJA') {
                throw new \RuntimeException(
                    'El expediente ya se encuentra dado de baja.'
                );
            }

            $estadoAnterior = $expediente->estado_parametro_id;
            $estadoBaja = $this->estado('BAJA');
            $motivo = $this->normalizarTexto($motivo);

            if (blank($motivo)) {
                throw new \RuntimeException(
                    'Debe registrar el motivo de la baja.'
                );
            }

            $expediente->update([
                'estado_parametro_id' => $estadoBaja->id,
                'usuario_actualizacion_id' => Auth::id(),
            ]);

            $this->registrarHistorial(
                expediente: $expediente,
                accion: 'DAR DE BAJA',
                estadoAnterior: $estadoAnterior,
                estadoNuevo: $estadoBaja->id,
                observacion: $motivo
            );

            return $expediente->fresh();
        });
    }

    private function parametro(
        int $id,
        string $grupo
    ): Parametro {
        return Parametro::query()
            ->whereKey($id)
            ->where('grupo', $grupo)
            ->where('activo', true)
            ->whereNull('fecha_eliminacion')
            ->firstOrFail();
    }

    private function estado(string $valor): Parametro
    {
        return Parametro::query()
            ->where('grupo', 'ESTADO_EXPEDIENTE')
            ->where('valor', $valor)
            ->where('activo', true)
            ->whereNull('fecha_eliminacion')
            ->firstOrFail();
    }

    private function generarCodigo(string $sigla): string
    {
        $correlativo = DB::table('correlativos')
            ->where('anio', 0)
            ->where('sigla', $sigla)
            ->lockForUpdate()
            ->first();
        $secuencia = $correlativo
            ? $correlativo->secuencia + 1
            : 1;
        return "{$sigla}/{$secuencia}";
    }

    private function actualizarCorrelativo(
        string $sigla,
        int $usuarioId
    ): void {
        $correlativo = DB::table('correlativos')
            ->where('anio', 0)
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
            'anio' => 0,
            'sigla' => $sigla,
            'secuencia' => 1,
            'activo' => true,
            'usuario_creacion_id' => $usuarioId,
            'fecha_creacion' => now(),
        ]);
    }

    private function registrarHistorial(
        InventarioExpediente $expediente,
        string $accion,
        ?int $estadoAnterior,
        ?int $estadoNuevo,
        ?string $observacion,
        ?array $datosAnteriores = null,
        ?array $datosNuevos = null
    ): void {
        InventarioHistorial::create([
            'inventario_expediente_id' => $expediente->id,
            'accion' => $accion,
            'estado_anterior_parametro_id' => $estadoAnterior,
            'estado_nuevo_parametro_id' => $estadoNuevo,
            'observacion' => $observacion,
            'datos_anteriores' => $datosAnteriores,
            'datos_nuevos' => $datosNuevos ?? $expediente->getAttributes(),
            'usuario_id' => Auth::id(),
            'fecha_accion' => now(),
        ]);
    }

    private function normalizarTexto(mixed $valor): ?string
    {
        if (blank($valor)) {
            return null;
        }

        return mb_strtoupper(trim((string) $valor), 'UTF-8');
    }
}
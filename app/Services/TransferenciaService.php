<?php

namespace App\Services;

use App\Models\Parametro;
use App\Models\Transferencia;
use App\Models\TransferenciaExpediente;
use App\Models\TransferenciaHistorial;
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
                    fn (Parametro $parametro) => mb_strtoupper(
                        trim((string) $parametro->valor),
                        'UTF-8'
                    )
                );
            $soportes = Parametro::query()
                ->where('grupo', 'SOPORTE')
                ->where('activo', true)
                ->whereNull('fecha_eliminacion')
                ->get()
                ->keyBy(
                    fn (Parametro $parametro) => mb_strtoupper(
                        trim((string) $parametro->valor),
                        'UTF-8'
                    )
                );
            $procedencias = Parametro::query()
                ->where('grupo', 'PROCEDENCIA')
                ->where('activo', true)
                ->whereNull('fecha_eliminacion')
                ->get()
                ->keyBy(
                    fn (Parametro $parametro) => mb_strtoupper(
                        trim((string) $parametro->sigla),
                        'UTF-8'
                    )
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
                $serie = $series->get(mb_strtoupper(trim((string) $expediente['serie_documental']),'UTF-8'));
                $soporte = $soportes->get(mb_strtoupper(trim((string) $expediente['soporte']),'UTF-8'));
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
                    $parametro = $procedencias->get(mb_strtoupper(trim((string) $procedencia),'UTF-8'));
                    if (! $parametro) {
                        throw new \RuntimeException(
                            "No se encontró la procedencia {$procedencia} durante el guardado."
                        );
                    }
                    $procedenciaIds[] = $parametro->id;
                }
                if ($procedenciaIds !== []) {
                    $registroExpediente->procedencias()->attach($procedenciaIds);
                }
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
            'activo' => true,
            'usuario_creacion_id' => $usuarioId,
            'fecha_creacion' => now(),
        ]);
    }
}
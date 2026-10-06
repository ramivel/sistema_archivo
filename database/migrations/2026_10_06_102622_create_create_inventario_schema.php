<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP SCHEMA IF EXISTS inventario CASCADE');
        DB::statement('CREATE SCHEMA inventario');

        Schema::create('inventario.inventario_expedientes', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('guid')->unique()->default(DB::raw('gen_random_uuid()'));
            $table->string('codigo_inventario', 100)->unique();
            $table->bigInteger('oficina_parametro_id');
            $table->bigInteger('direccion_parametro_id');
            $table->bigInteger('area_parametro_id')->nullable();
            $table->string('codigo_referencia', 50);
            $table->string('numero_caja', 50)->nullable();
            $table->text('procedencia');
            $table->bigInteger('serie_documental_parametro_id');
            $table->text('descripcion_lomo');
            $table->text('detalle');
            $table->unsignedInteger('tomo_volumen');
            $table->string('fojas', 30);
            $table->string('fechas_extremas', 30);
            $table->bigInteger('soporte_parametro_id');
            $table->text('observaciones');
            $table->string('archivo_pdf')->nullable();
            $table->bigInteger('estado_parametro_id');
            $table->string('origen', 20)->default('MANUAL');
            $table->bigInteger('transferencia_expediente_id')->nullable()->unique();
            $table->bigInteger('usuario_creacion_id');
            $table->bigInteger('usuario_actualizacion_id')->nullable();
            $table->bigInteger('usuario_eliminacion_id')->nullable();
            $table->timestamp('fecha_creacion');
            $table->timestamp('fecha_actualizacion')->nullable();
            $table->timestamp('fecha_eliminacion')->nullable();

            $table->index('codigo_referencia');
            $table->index('numero_caja');
            $table->index('estado_parametro_id');
            $table->index(['oficina_parametro_id', 'direccion_parametro_id',]);

            $table->foreign('oficina_parametro_id')->references('id')->on('parametros');
            $table->foreign('direccion_parametro_id')->references('id')->on('parametros');
            $table->foreign('area_parametro_id')->references('id')->on('parametros');
            $table->foreign('serie_documental_parametro_id')->references('id')->on('parametros');
            $table->foreign('soporte_parametro_id')->references('id')->on('parametros');
            $table->foreign('estado_parametro_id')->references('id')->on('parametros');
            $table->foreign('transferencia_expediente_id')->references('id')->on('transferencias.transferencia_expedientes');
            $table->foreign('usuario_creacion_id')->references('id')->on('usuarios');
            $table->foreign('usuario_actualizacion_id')->references('id')->on('usuarios');
            $table->foreign('usuario_eliminacion_id')->references('id')->on('usuarios');
        });

        Schema::create('inventario.inventario_prestamos', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('guid')->unique()->default(DB::raw('gen_random_uuid()'));
            $table->bigInteger('inventario_expediente_id');
            $table->bigInteger('usuario_solicitante_id');
            $table->bigInteger('usuario_registro_id');
            $table->timestamp('fecha_prestamo');
            $table->timestamp('fecha_devolucion')->nullable();
            $table->text('observaciones_prestamo');
            $table->text('observaciones_devolucion')->nullable();
            $table->timestamp('fecha_creacion');
            $table->timestamp('fecha_actualizacion')->nullable();

            $table->index(['inventario_expediente_id', 'fecha_devolucion',]);

            $table->foreign('inventario_expediente_id')->references('id')->on('inventario.inventario_expedientes');
            $table->foreign('usuario_solicitante_id')->references('id')->on('usuarios');
            $table->foreign('usuario_registro_id')->references('id')->on('usuarios');
        });

        Schema::create('inventario.inventario_historial',function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->uuid('guid')->unique()->default(DB::raw('gen_random_uuid()'));
                $table->bigInteger('inventario_expediente_id');
                $table->string('accion', 50);
                $table->bigInteger('estado_anterior_parametro_id')->nullable();
                $table->bigInteger('estado_nuevo_parametro_id')->nullable();
                $table->text('observacion')->nullable();
                $table->jsonb('datos_anteriores')->nullable();
                $table->jsonb('datos_nuevos')->nullable();
                $table->bigInteger('usuario_id');
                $table->timestamp('fecha_accion');

                $table->index(['inventario_expediente_id', 'fecha_accion',]);

                $table->foreign('inventario_expediente_id')->references('id')->on('inventario.inventario_expedientes');
                $table->foreign('estado_anterior_parametro_id')->references('id')->on('parametros');
                $table->foreign('estado_nuevo_parametro_id')->references('id')->on('parametros');
                $table->foreign('usuario_id')->references('id')->on('usuarios');
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('inventario.inventario_historial');
        Schema::dropIfExists('inventario.inventario_prestamos');
        Schema::dropIfExists('inventario.inventario_expedientes');

        DB::statement('DROP SCHEMA IF EXISTS inventario CASCADE');
    }
};
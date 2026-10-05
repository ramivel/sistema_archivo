<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transferencias.transferencia_expediente_correcciones_archivo',
            function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->uuid('guid')->default(DB::raw('gen_random_uuid()'));
                $table->unsignedBigInteger('transferencia_expediente_id');
                $table->string('codigo_referencia', 50);
                $table->string('numero_caja', 50)->nullable();
                $table->text('procedencia');
                $table->unsignedBigInteger('serie_documental_parametro_id');
                $table->text('descripcion_lomo')->nullable();
                $table->text('detalle')->nullable();
                $table->string('tomo_volumen', 50)->nullable();
                $table->string('fojas', 30)->nullable();
                $table->string('fechas_extremas', 30)->nullable();
                $table->unsignedBigInteger('soporte_parametro_id');
                $table->text('observaciones')->nullable();
                $table->unsignedBigInteger('usuario_creacion_id');
                $table->unsignedBigInteger('usuario_actualizacion_id')->nullable();
                $table->timestamp('fecha_creacion');
                $table->timestamp('fecha_actualizacion')->nullable();

                $table->unique('guid','uk_correccion_archivo_guid');
                $table->unique('transferencia_expediente_id','uk_correccion_archivo_expediente');
                $table->foreign('transferencia_expediente_id','fk_correccion_archivo_expediente')
                    ->references('id')
                    ->on('transferencias.transferencia_expedientes');
                $table->foreign('serie_documental_parametro_id','fk_correccion_archivo_serie')
                    ->references('id')
                    ->on('parametros');
                $table->foreign('soporte_parametro_id','fk_correccion_archivo_soporte')
                    ->references('id')
                    ->on('parametros');
                $table->foreign('usuario_creacion_id','fk_correccion_archivo_usuario_creacion')
                    ->references('id')
                    ->on('usuarios');
                $table->foreign('usuario_actualizacion_id','fk_correccion_archivo_usuario_actualizacion')
                    ->references('id')
                    ->on('usuarios');
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'transferencias.transferencia_expediente_correcciones_archivo'
        );
    }
};
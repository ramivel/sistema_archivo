<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transferencias.transferencias', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('guid')->default(DB::raw('gen_random_uuid()'));
            $table->string('correlativo', 100);
            $table->unsignedBigInteger('fondo_parametro_id');
            $table->unsignedBigInteger('subfondo_parametro_id');
            $table->unsignedBigInteger('seccion_parametro_id')->nullable();
            $table->unsignedBigInteger('usuario_solicitante_id');
            $table->unsignedBigInteger('estado_parametro_id');
            $table->string('archivo_excel', 255);
            $table->string('archivo_nota_rechazo', 255)->nullable();
            $table->string('archivo_formulario_firmado', 255)->nullable();
            $table->integer('total_expedientes')->default(0);
            $table->timestamp('fecha_solicitud');
            $table->timestamp('fecha_finalizacion')->nullable();
            $table->boolean('es_regularizacion')->default(false);
            $table->unsignedBigInteger('usuario_creacion_id');
            $table->unsignedBigInteger('usuario_actualizacion_id')->nullable();
            $table->unsignedBigInteger('usuario_eliminacion_id')->nullable();
            $table->timestamp('fecha_creacion');
            $table->timestamp('fecha_actualizacion')->nullable();
            $table->timestamp('fecha_eliminacion')->nullable();

            $table->unique('guid', 'uk_transferencia_guid');
            $table->unique('correlativo', 'uk_transferencia_correlativo');
            $table->foreign('fondo_parametro_id','fk_transferencia_fondo')
                ->references('id')
                ->on('parametros');
            $table->foreign('subfondo_parametro_id','fk_transferencia_subfondo')
                ->references('id')
                ->on('parametros');
            $table->foreign('seccion_parametro_id','fk_transferencia_seccion')
                ->references('id')
                ->on('parametros');
            $table->foreign('usuario_solicitante_id','fk_transferencia_usuario')
                ->references('id')
                ->on('usuarios');
            $table->foreign('estado_parametro_id','fk_transferencia_estado')
                ->references('id')
                ->on('parametros');
            $table->foreign('usuario_creacion_id','fk_transferencia_usuario_creacion')
                ->references('id')
                ->on('usuarios');
            $table->foreign('usuario_actualizacion_id','fk_transferencia_usuario_actualizacion')
                ->references('id')
                ->on('usuarios');
            $table->foreign('usuario_eliminacion_id','fk_transferencia_usuario_eliminacion')
                ->references('id')
                ->on('usuarios');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transferencias.transferencias');
    }
};

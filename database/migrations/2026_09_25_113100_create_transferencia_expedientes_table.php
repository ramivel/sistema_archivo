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
        Schema::create('transferencias.transferencia_expedientes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('guid')->default(DB::raw('gen_random_uuid()'));
            $table->unsignedBigInteger('transferencia_id');
            $table->string('codigo_referencia', 50);
            $table->string('numero_caja', 50);
            $table->unsignedBigInteger('serie_documental_parametro_id');
            $table->text('descripcion_lomo')->nullable();
            $table->text('detalle')->nullable();
            $table->string('tomo_volumen', 50)->nullable();
            $table->string('fojas', 30)->nullable();
            $table->string('fechas_extremas', 30)->nullable();
            $table->unsignedBigInteger('soporte_parametro_id');
            $table->text('observaciones')->nullable();
            $table->boolean('activo')->default(true);
            $table->unsignedBigInteger('usuario_creacion_id');
            $table->unsignedBigInteger('usuario_actualizacion_id')->nullable();
            $table->timestamp('fecha_creacion');
            $table->timestamp('fecha_actualizacion')->nullable();

            $table->unique('guid','uk_transferencia_expediente_guid');
            $table->foreign('transferencia_id','fk_transferencia_expediente_transferencia')
                ->references('id')
                ->on('transferencias.transferencias');
            $table->foreign('serie_documental_parametro_id','fk_transferencia_expediente_serie')
                ->references('id')
                ->on('parametros');
            $table->foreign('soporte_parametro_id','fk_transferencia_expediente_soporte')
                ->references('id')
                ->on('parametros');
            $table->foreign('usuario_creacion_id','fk_transferencia_expediente_usuario_creacion')
                ->references('id')
                ->on('usuarios');
            $table->foreign('usuario_actualizacion_id','fk_transferencia_expediente_usuario_actualizacion')
                ->references('id')
                ->on('usuarios');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transferencias.transferencia_expedientes');
    }
};

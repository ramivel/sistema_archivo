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
        Schema::create('transferencias.transferencia_historial', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('guid')->default(DB::raw('gen_random_uuid()'));
            $table->unsignedBigInteger('transferencia_id');
            $table->unsignedBigInteger('estado_anterior_parametro_id')->nullable();
            $table->unsignedBigInteger('estado_nuevo_parametro_id');
            $table->string('accion', 50);
            $table->timestamp('fecha_accion');
            $table->text('observacion')->nullable();
            $table->unsignedBigInteger('usuario_id');

            $table->unique('guid','uk_transferencia_historial_guid');
            $table->foreign('transferencia_id','fk_historial_transferencia')
                ->references('id')
                ->on('transferencias.transferencias');
            $table->foreign('estado_anterior_parametro_id','fk_historial_estado_anterior')
                ->references('id')
                ->on('parametros');
            $table->foreign('estado_nuevo_parametro_id','fk_historial_estado_nuevo')
                ->references('id')
                ->on('parametros');
            $table->foreign('usuario_id','fk_historial_usuario')
                ->references('id')
                ->on('usuarios');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transferencias.transferencia_historial');
    }
};

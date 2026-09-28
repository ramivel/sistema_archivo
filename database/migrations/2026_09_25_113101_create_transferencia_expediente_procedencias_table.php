<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transferencias.transferencia_expediente_procedencias', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('transferencia_expediente_id');
            $table->unsignedBigInteger('procedencia_parametro_id');

            $table->unique(
                ['transferencia_expediente_id', 'procedencia_parametro_id'],
                'uk_expediente_procedencia'
            );
            $table->foreign('transferencia_expediente_id','fk_expediente_procedencia_expediente')
                ->references('id')
                ->on('transferencias.transferencia_expedientes');
            $table->foreign('procedencia_parametro_id','fk_expediente_procedencia_parametro')
                ->references('id')
                ->on('parametros');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transferencias.transferencia_expediente_procedencias');
    }
};

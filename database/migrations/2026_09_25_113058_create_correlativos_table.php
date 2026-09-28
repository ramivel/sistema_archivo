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
        Schema::create('correlativos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('anio');
            $table->string('sigla', 100);
            $table->integer('secuencia')->default(0);
            $table->boolean('activo')->default(true);
            $table->text('observaciones')->nullable();

            $table->unsignedBigInteger('usuario_creacion_id');
            $table->unsignedBigInteger('usuario_actualizacion_id')->nullable();
            $table->unsignedBigInteger('usuario_eliminacion_id')->nullable();
            $table->timestamp('fecha_creacion');
            $table->timestamp('fecha_actualizacion')->nullable();
            $table->timestamp('fecha_eliminacion')->nullable();
            $table->unique(['anio', 'sigla'],'uk_correlativo');
            $table->foreign('usuario_creacion_id','fk_correlativo_usuario_creacion')
                ->references('id')
                ->on('usuarios');
            $table->foreign('usuario_actualizacion_id','fk_correlativo_usuario_actualizacion')
                ->references('id')
                ->on('usuarios');
            $table->foreign('usuario_eliminacion_id','fk_correlativo_usuario_eliminacion')
                ->references('id')
                ->on('usuarios');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('correlativos');
    }
};

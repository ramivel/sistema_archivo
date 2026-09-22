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
        Schema::create('parametros', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('guid')->default(DB::raw('gen_random_uuid()'));
            $table->string('grupo', 50);
            $table->string('valor', 200);
            $table->string('sigla', 50)->nullable();
            $table->text('descripcion')->nullable();
            $table->string('ubicacion', 250)->nullable();
            $table->unsignedBigInteger('padre_id')->nullable();
            $table->integer('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->unsignedBigInteger('usuario_creacion_id');
            $table->unsignedBigInteger('usuario_actualizacion_id')->nullable();
            $table->unsignedBigInteger('usuario_eliminacion_id')->nullable();
            $table->timestamp('fecha_creacion');
            $table->timestamp('fecha_actualizacion')->nullable();
            $table->timestamp('fecha_eliminacion')->nullable();

            $table->foreign('padre_id')
                ->references('id')
                ->on('parametros');
            $table->unique('guid', 'uk_parametro_guid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parametros');
    }
};

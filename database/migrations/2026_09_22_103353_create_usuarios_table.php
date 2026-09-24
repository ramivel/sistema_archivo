<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('guid')->default(DB::raw('gen_random_uuid()'));
            $table->string('nombres', 100);
            $table->string('apellidos', 100);
            $table->string('documento_identidad', 30);
            $table->unsignedBigInteger('expedido_parametro_id');
            $table->unsignedBigInteger('oficina_parametro_id');
            $table->unsignedBigInteger('direccion_parametro_id');
            $table->unsignedBigInteger('area_parametro_id')->nullable();
            $table->string('email', 150);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('telefonos', 150)->nullable();
            $table->string('usuario', 50);
            $table->string('password', 255);
            $table->rememberToken();
            $table->boolean('activo')->default(true);
            $table->timestamp('ultimo_acceso')->nullable();
            $table->unsignedBigInteger('usuario_creacion_id');
            $table->unsignedBigInteger('usuario_actualizacion_id')->nullable();
            $table->unsignedBigInteger('usuario_eliminacion_id')->nullable();
            $table->timestamp('fecha_creacion');
            $table->timestamp('fecha_actualizacion')->nullable();
            $table->timestamp('fecha_eliminacion')->nullable();

            $table->unique('guid', 'uk_usuario_guid');
            $table->unique('usuario', 'uk_usuario_usuario');
            $table->unique(
                ['documento_identidad', 'expedido_parametro_id'],
                'uk_usuario_documento_expedido'
            );
            $table->foreign('expedido_parametro_id', 'fk_usuario_expedido')
                ->references('id')
                ->on('parametros');
            $table->foreign('oficina_parametro_id', 'fk_usuario_oficina')
                ->references('id')
                ->on('parametros');
            $table->foreign('direccion_parametro_id', 'fk_usuario_direccion')
                ->references('id')
                ->on('parametros');
            $table->foreign('area_parametro_id', 'fk_usuario_area')
                ->references('id')
                ->on('parametros');
            $table->foreign('usuario_creacion_id', 'fk_usuario_creacion')
                ->references('id')
                ->on('usuarios');
            $table->foreign('usuario_actualizacion_id', 'fk_usuario_actualizacion')
                ->references('id')
                ->on('usuarios');
            $table->foreign('usuario_eliminacion_id', 'fk_usuario_eliminacion')
                ->references('id')
                ->on('usuarios');
        });

        Schema::create('usuario_perfiles', function (Blueprint $table) {
            $table->unsignedBigInteger('usuario_id');
            $table->unsignedBigInteger('perfil_parametro_id');

            $table->primary(
                ['usuario_id','perfil_parametro_id'],
                'pk_usuario_perfiles'
            );
            $table->foreign('usuario_id','fk_usuario_perfiles_usuario')
                ->references('id')
                ->on('usuarios')
                ->cascadeOnDelete();
            $table->foreign('perfil_parametro_id','fk_usuario_perfiles_perfil')
                ->references('id')
                ->on('parametros');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
            $table->foreign('user_id')->references('id')->on('usuarios')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuario_perfiles');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('usuarios');
    }
};

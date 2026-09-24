<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UsuariosSeeder extends Seeder
{
    public function run(): void
    {
        $nombres = env('ADMIN_NOMBRES');
        $apellidos = env('ADMIN_APELLIDOS');
        $documento = env('ADMIN_DOCUMENTO');
        $expedido = env('ADMIN_EXPEDIDO');

        $email = env('ADMIN_EMAIL');
        $usuario = env('ADMIN_USUARIO');
        $password = env('ADMIN_PASSWORD');

        if (
            empty($nombres) ||
            empty($apellidos) ||
            empty($documento) ||
            empty($expedido) ||
            empty($email) ||
            empty($usuario) ||
            empty($password)
        ) {
            throw new \RuntimeException(
                'Faltan variables obligatorias para crear el usuario administrador en el archivo .env.'
            );
        }

        $expedidoParametroId = DB::table('parametros')
            ->where('grupo', 'LUGAR_EXPEDICION')
            ->where('valor', $expedido)
            ->where('activo', true)
            ->value('id');
        if (!$expedidoParametroId) {
            throw new \RuntimeException(
                "No existe el parámetro LUGAR_EXPEDICION con valor: {$expedido}"
            );
        }
        $oficinaParametroId = 1;
        $direccionParametroId = 2;
        $areaParametroId = 4;
        $perfilParametroId = DB::table('parametros')
            ->where('grupo', 'PERFIL')
            ->where('valor', 'ADMINISTRADOR')
            ->where('activo', true)
            ->value('id');
        if (!$perfilParametroId) {
            throw new \RuntimeException(
                'No existe el perfil ADMINISTRADOR en la tabla parametros.'
            );
        }
        $usuarioCreacionId = 1;

        DB::transaction(function () use (
            $nombres,
            $apellidos,
            $documento,
            $expedidoParametroId,
            $oficinaParametroId,
            $direccionParametroId,
            $areaParametroId,
            $perfilParametroId,
            $usuarioCreacionId,
            $email,
            $usuario,
            $password,
        ) {
            $usuarioExistente = User::withTrashed()
                ->where('usuario', $usuario)
                ->first();
            if ($usuarioExistente) {
                return;
            }
            $nuevoUsuario = User::create([
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'documento_identidad' => $documento,
                'expedido_parametro_id' => $expedidoParametroId,
                'oficina_parametro_id' => $oficinaParametroId,
                'direccion_parametro_id' => $direccionParametroId,
                'area_parametro_id' => $areaParametroId,
                'email' => $email,
                'usuario' => $usuario,
                'password' => $password,
                'usuario_creacion_id' => $usuarioCreacionId,
            ]);
            DB::table('usuario_perfiles')->insert([
                'usuario_id' => $nuevoUsuario->id,
                'perfil_parametro_id' => $perfilParametroId,
            ]);
        });
    }

}

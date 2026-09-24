<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'nombres',
    'apellidos',
    'documento_identidad',
    'expedido_parametro_id',
    'oficina_parametro_id',
    'direccion_parametro_id',
    'area_parametro_id',
    'email',
    'email_verified_at',
    'telefonos',
    'usuario',
    'password',
    'activo',
    'ultimo_acceso',
    'usuario_creacion_id',
    'usuario_actualizacion_id',
    'usuario_eliminacion_id',
    'fecha_creacion',
    'fecha_actualizacion',
    'fecha_eliminacion',
])]
#[Hidden([
    'password',
    'remember_token',
])]
class User extends Authenticatable implements HasName
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;
    protected $table = 'usuarios';
    public const CREATED_AT = 'fecha_creacion';
    public const UPDATED_AT = 'fecha_actualizacion';
    public const DELETED_AT = 'fecha_eliminacion';

    public function getRouteKeyName(): string
    {
        return 'guid';
    }

    public function getFilamentName(): string
    {
        return trim("{$this->nombres} {$this->apellidos}");
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
            'ultimo_acceso' => 'datetime',
            'fecha_creacion' => 'datetime',
            'fecha_actualizacion' => 'datetime',
            'fecha_eliminacion' => 'datetime',
        ];
    }

    /**
     * Relaciones de Tablas
     */
    public function expedido(): BelongsTo
    {
        return $this->belongsTo(
            Parametro::class,
            'expedido_parametro_id'
        );
    }
    public function oficina(): BelongsTo
    {
        return $this->belongsTo(
            Parametro::class,
            'oficina_parametro_id'
        );
    }
    public function direccion(): BelongsTo
    {
        return $this->belongsTo(
            Parametro::class,
            'direccion_parametro_id'
        );
    }
    public function area(): BelongsTo
    {
        return $this->belongsTo(
            Parametro::class,
            'area_parametro_id'
        );
    }
    public function perfiles(): BelongsToMany
    {
        return $this->belongsToMany(
            Parametro::class,
            'usuario_perfiles',
            'usuario_id',
            'perfil_parametro_id'
        )
            ->where('parametros.grupo', 'PERFIL')
            ->where('parametros.activo', true)
            ->orderBy('parametros.orden');
    }
    public function usuarioCreacion(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'usuario_creacion_id'
        );
    }
    public function usuarioActualizacion(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'usuario_actualizacion_id'
        );
    }
    public function usuarioEliminacion(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'usuario_eliminacion_id'
        );
    }

}
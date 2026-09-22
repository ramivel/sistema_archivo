<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Parametro extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'parametros';
    public const CREATED_AT = 'fecha_creacion';
    public const UPDATED_AT = 'fecha_actualizacion';
    public const DELETED_AT = 'fecha_eliminacion';
    protected $fillable = [
        'grupo',
        'valor',
        'sigla',
        'descripcion',
        'ubicacion',
        'padre_id',
        'orden',
        'activo',
        'usuario_creacion_id',
        'usuario_actualizacion_id',
        'usuario_eliminacion_id',
        'fecha_creacion',
        'fecha_actualizacion',
        'fecha_eliminacion',
    ];

    public function getRouteKeyName(): string
    {
        return 'guid';
    }

    protected function casts(): array
    {
        return [
            'orden' => 'integer',
            'activo' => 'boolean',
            'fecha_creacion' => 'datetime',
            'fecha_actualizacion' => 'datetime',
            'fecha_eliminacion' => 'datetime',
        ];
    }

    public function padre(): BelongsTo
    {
        return $this->belongsTo(
            self::class,
            'padre_id'
        );
    }

    public function hijos(): HasMany
    {
        return $this->hasMany(
            self::class,
            'padre_id'
        );
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
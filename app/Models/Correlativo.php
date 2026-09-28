<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Correlativo extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'correlativos';
    public const CREATED_AT = 'fecha_creacion';
    public const UPDATED_AT = 'fecha_actualizacion';
    public const DELETED_AT = 'fecha_eliminacion';

    protected $fillable = [
        'anio',
        'sigla',
        'secuencia',
        'activo',
        'observaciones',
        'usuario_creacion_id',
        'usuario_actualizacion_id',
        'usuario_eliminacion_id',
        'fecha_creacion',
        'fecha_actualizacion',
        'fecha_eliminacion',
    ];

    protected function casts(): array
    {
        return [
            'anio' => 'integer',
            'secuencia' => 'integer',
            'activo' => 'boolean',
            'fecha_creacion' => 'datetime',
            'fecha_actualizacion' => 'datetime',
            'fecha_eliminacion' => 'datetime',
        ];
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
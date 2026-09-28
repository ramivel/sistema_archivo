<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transferencia extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'transferencias.transferencias';
    public const CREATED_AT = 'fecha_creacion';
    public const UPDATED_AT = 'fecha_actualizacion';
    public const DELETED_AT = 'fecha_eliminacion';

    protected $fillable = [        
        'correlativo',
        'fondo_parametro_id',
        'subfondo_parametro_id',
        'seccion_parametro_id',
        'usuario_solicitante_id',
        'estado_parametro_id',
        'archivo_excel',
        'archivo_nota_rechazo',
        'archivo_formulario_firmado',
        'total_expedientes',
        'fecha_solicitud',
        'fecha_finalizacion',
        'es_regularizacion',
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
            'total_expedientes' => 'integer',
            'fecha_solicitud' => 'datetime',
            'fecha_finalizacion' => 'datetime',
            'es_regularizacion' => 'boolean',
            'fecha_creacion' => 'datetime',
            'fecha_actualizacion' => 'datetime',
            'fecha_eliminacion' => 'datetime',
        ];
    }

    public function fondo(): BelongsTo
    {
        return $this->belongsTo(
            Parametro::class,
            'fondo_parametro_id'
        );
    }

    public function subfondo(): BelongsTo
    {
        return $this->belongsTo(
            Parametro::class,
            'subfondo_parametro_id'
        );
    }

    public function seccion(): BelongsTo
    {
        return $this->belongsTo(
            Parametro::class,
            'seccion_parametro_id'
        );
    }

    public function usuarioSolicitante(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'usuario_solicitante_id'
        );
    }

    public function estado(): BelongsTo
    {
        return $this->belongsTo(
            Parametro::class,
            'estado_parametro_id'
        );
    }

    public function expedientes(): HasMany
    {
        return $this->hasMany(
            TransferenciaExpediente::class,
            'transferencia_id'
        );
    }

    public function historial(): HasMany
    {
        return $this->hasMany(
            TransferenciaHistorial::class,
            'transferencia_id'
        )->orderByDesc('fecha_accion');
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
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TransferenciaExpediente extends Model
{
    use HasFactory;
    protected $table = 'transferencias.transferencia_expedientes';
    public const CREATED_AT = 'fecha_creacion';
    public const UPDATED_AT = 'fecha_actualizacion';

    protected $fillable = [
        'transferencia_id',
        'codigo_referencia',
        'numero_caja',
        'serie_documental_parametro_id',
        'descripcion_lomo',
        'detalle',
        'tomo_volumen',
        'fojas',
        'fechas_extremas',
        'soporte_parametro_id',
        'observaciones',
        'activo',
        'usuario_creacion_id',
        'usuario_actualizacion_id',
        'fecha_creacion',
        'fecha_actualizacion',
    ];

    public function getRouteKeyName(): string
    {
        return 'guid';
    }

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'fecha_creacion' => 'datetime',
            'fecha_actualizacion' => 'datetime',
        ];
    }

    public function transferencia(): BelongsTo
    {
        return $this->belongsTo(
            Transferencia::class,
            'transferencia_id'
        );
    }

    public function serieDocumental(): BelongsTo
    {
        return $this->belongsTo(
            Parametro::class,
            'serie_documental_parametro_id'
        );
    }

    public function soporte(): BelongsTo
    {
        return $this->belongsTo(
            Parametro::class,
            'soporte_parametro_id'
        );
    }

    public function procedencias(): BelongsToMany
    {
        return $this->belongsToMany(
            Parametro::class,
            'transferencias.transferencia_expediente_procedencias',
            'transferencia_expediente_id',
            'procedencia_parametro_id'
        )
            ->where('parametros.grupo', 'PROCEDENCIA')
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
}
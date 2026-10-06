<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventarioExpediente extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'inventario.inventario_expedientes';
    public const CREATED_AT = 'fecha_creacion';
    public const UPDATED_AT = 'fecha_actualizacion';
    public const DELETED_AT = 'fecha_eliminacion';

    protected $fillable = [
        'codigo_inventario',
        'oficina_parametro_id',
        'direccion_parametro_id',
        'area_parametro_id',
        'codigo_referencia',
        'numero_caja',
        'procedencia',
        'serie_documental_parametro_id',
        'descripcion_lomo',
        'detalle',
        'tomo_volumen',
        'fojas',
        'fechas_extremas',
        'soporte_parametro_id',
        'observaciones',
        'archivo_pdf',
        'estado_parametro_id',
        'origen',
        'transferencia_expediente_id',
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
            'tomo_volumen' => 'integer',
            'fecha_creacion' => 'datetime',
            'fecha_actualizacion' => 'datetime',
            'fecha_eliminacion' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'guid';
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

    public function estado(): BelongsTo
    {
        return $this->belongsTo(
            Parametro::class,
            'estado_parametro_id'
        );
    }

    public function transferenciaExpediente(): BelongsTo
    {
        return $this->belongsTo(
            TransferenciaExpediente::class,
            'transferencia_expediente_id'
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

    public function prestamos(): HasMany
    {
        return $this->hasMany(
            InventarioPrestamo::class,
            'inventario_expediente_id'
        );
    }

    public function historial(): HasMany
    {
        return $this->hasMany(
            InventarioHistorial::class,
            'inventario_expediente_id'
        )->orderByDesc('fecha_accion');
    }
}
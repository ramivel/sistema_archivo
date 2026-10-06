<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventarioHistorial extends Model
{
    use HasFactory;
    protected $table = 'inventario.inventario_historial';
    public $timestamps = false;

    protected $fillable = [
        'inventario_expediente_id',
        'accion',
        'estado_anterior_parametro_id',
        'estado_nuevo_parametro_id',
        'observacion',
        'datos_anteriores',
        'datos_nuevos',
        'usuario_id',
        'fecha_accion',
    ];

    protected function casts(): array
    {
        return [
            'datos_anteriores' => 'array',
            'datos_nuevos' => 'array',
            'fecha_accion' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'guid';
    }

    public function expediente(): BelongsTo
    {
        return $this->belongsTo(
            InventarioExpediente::class,
            'inventario_expediente_id'
        );
    }

    public function estadoAnterior(): BelongsTo
    {
        return $this->belongsTo(
            Parametro::class,
            'estado_anterior_parametro_id'
        );
    }

    public function estadoNuevo(): BelongsTo
    {
        return $this->belongsTo(
            Parametro::class,
            'estado_nuevo_parametro_id'
        );
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'usuario_id'
        );
    }
}
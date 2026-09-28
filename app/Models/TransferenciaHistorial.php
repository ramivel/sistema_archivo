<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferenciaHistorial extends Model
{
    use HasFactory;
    protected $table = 'transferencias.transferencia_historial';
    public $timestamps = false;

    protected $fillable = [
        'transferencia_id',
        'estado_anterior_parametro_id',
        'estado_nuevo_parametro_id',
        'accion',
        'fecha_accion',
        'observacion',
        'usuario_id',
    ];

    public function getRouteKeyName(): string
    {
        return 'guid';
    }

    protected function casts(): array
    {
        return [
            'fecha_accion' => 'datetime',
        ];
    }

    public function transferencia(): BelongsTo
    {
        return $this->belongsTo(
            Transferencia::class,
            'transferencia_id'
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
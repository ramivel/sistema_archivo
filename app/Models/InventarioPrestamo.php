<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventarioPrestamo extends Model
{
    use HasFactory;

    protected $table = 'inventario.inventario_prestamos';
    public const CREATED_AT = 'fecha_creacion';
    public const UPDATED_AT = 'fecha_actualizacion';

    protected $fillable = [
        'inventario_expediente_id',
        'usuario_solicitante_id',
        'usuario_registro_id',
        'fecha_prestamo',
        'fecha_devolucion',
        'observaciones_prestamo',
        'observaciones_devolucion',
        'fecha_creacion',
        'fecha_actualizacion',
    ];

    protected function casts(): array
    {
        return [
            'fecha_prestamo' => 'datetime',
            'fecha_devolucion' => 'datetime',
            'fecha_creacion' => 'datetime',
            'fecha_actualizacion' => 'datetime',
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

    public function usuarioSolicitante(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'usuario_solicitante_id'
        );
    }

    public function usuarioRegistro(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'usuario_registro_id'
        );
    }
}
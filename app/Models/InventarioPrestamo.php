<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
        'numero_solicitud',
        'fecha_prestamo',
        'nombre_solicitante',
        'cargo',
        'oficina_parametro_id',
        'direccion_parametro_id',
        'area_parametro_id',
        'telefono',
        'motivo_finalidad',
        'fecha_devolucion',
        'observaciones_devolucion',
        'fecha_creacion',
        'fecha_actualizacion',
    ];

    protected function casts(): array
    {
        return [
            'fecha_prestamo' => 'date',
            'fecha_devolucion' => 'date',
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

    public function tiposConsulta(): BelongsToMany
    {
        return $this->belongsToMany(
            Parametro::class,
            'inventario.inventario_prestamo_tipo_consulta',
            'inventario_prestamo_id',
            'tipo_consulta_parametro_id'
        )->withPivot('fecha_creacion');
    }
}
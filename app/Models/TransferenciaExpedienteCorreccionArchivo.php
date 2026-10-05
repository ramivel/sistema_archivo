<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferenciaExpedienteCorreccionArchivo extends Model
{
    use HasFactory;
    protected $table = 'transferencias.transferencia_expediente_correcciones_archivo';

    public const CREATED_AT = 'fecha_creacion';
    public const UPDATED_AT = 'fecha_actualizacion';

    protected $fillable = [
        'transferencia_expediente_id',
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
        'usuario_creacion_id',
        'usuario_actualizacion_id',
        'fecha_creacion',
        'fecha_actualizacion',
    ];

    protected function casts(): array
    {
        return [
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
        return $this->belongsTo(TransferenciaExpediente::class,'transferencia_expediente_id');
    }

    public function serieDocumental(): BelongsTo
    {
        return $this->belongsTo(Parametro::class,'serie_documental_parametro_id');
    }

    public function soporte(): BelongsTo
    {
        return $this->belongsTo(Parametro::class,'soporte_parametro_id');
    }

    public function usuarioCreacion(): BelongsTo
    {
        return $this->belongsTo(User::class,'usuario_creacion_id');
    }

    public function usuarioActualizacion(): BelongsTo
    {
        return $this->belongsTo(User::class,'usuario_actualizacion_id');
    }
}
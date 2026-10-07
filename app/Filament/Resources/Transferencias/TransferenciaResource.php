<?php

namespace App\Filament\Resources\Transferencias;

use App\Filament\Resources\Transferencias\Pages\CrearTransferencia;
use App\Filament\Resources\Transferencias\Pages\ListTransferencias;
use App\Filament\Resources\Transferencias\Pages\RegularizarInventario;
use App\Filament\Resources\Transferencias\Pages\CorregirTransferencia;
use App\Filament\Resources\Transferencias\Pages\FinalizarTransferencia;
use App\Filament\Resources\Transferencias\Pages\VerTransferencia;
use App\Filament\Resources\Transferencias\Tables\TransferenciasTable;
use App\Models\User;
use App\Models\Transferencia;
use App\Models\TransferenciaHistorial;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TransferenciaResource extends Resource
{
    protected static ?string $model = Transferencia::class;
    protected static ?string $recordTitleAttribute = 'correlativo';
    protected static string|\UnitEnum|null $navigationGroup = 'Inventario';
    protected static ?string $navigationLabel = 'Transferencias';
    protected static ?string $modelLabel = 'Transferencia';
    protected static ?string $pluralModelLabel = 'Transferencias';
    protected static ?string $slug = 'transferencias';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-up-tray';

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return TransferenciasTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with([
                'fondo',
                'subfondo',
                'seccion',
                'usuarioSolicitante',
                'estado',
            ]);
        $usuario = User::find(Auth::id());
        if ($usuario?->perfiles->contains('valor', 'TRANSFERENCIAS'))
            $query->where('usuario_solicitante_id',Auth::id());
        $query->addSelect([
            'fecha_observacion' => TransferenciaHistorial::query()
                ->select('fecha_accion')
                ->whereColumn(
                    'transferencia_id',
                    'transferencias.transferencias.id'
                )
                ->where('accion', 'OBSERVAR')
                ->latest('fecha_accion')
                ->limit(1),
            'fecha_aprobacion' => TransferenciaHistorial::query()
                ->select('fecha_accion')
                ->whereColumn(
                    'transferencia_id',
                    'transferencias.transferencias.id'
                )
                ->where('accion', 'APROBAR')
                ->latest('fecha_accion')
                ->limit(1),
        ]);
        if ($usuario?->perfiles->contains('valor', 'ENCARGADO ARCHIVO')) {
            $query->orderByRaw("
                CASE
                    WHEN estado_parametro_id IN (
                        SELECT id
                        FROM parametros
                        WHERE grupo = 'ESTADO_TRANSFERENCIA'
                        AND valor IN ('RECHAZADO', 'ANULADO', 'FINALIZADO')
                    ) THEN 1
                    ELSE 0
                END
            ");
        }
        return $query->orderByDesc('fecha_solicitud');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTransferencias::route('/'),
            'create' => CrearTransferencia::route('/create'),
            'regularizar' => RegularizarInventario::route('/regularizar'),
            'corregir' => CorregirTransferencia::route('/{record}/corregir'),
            'finalizar' => FinalizarTransferencia::route('/{record}/finalizar'),
            'ver' => VerTransferencia::route('/{record}/ver'),
        ];
    }
}
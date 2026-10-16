<?php

namespace App\Filament\Widgets\Soporte;

use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class DashboardConsultas
{
    public static function usuarioActual(): ?User
    {
        return User::find(Auth::id());
    }

    public static function transferenciasBase(): Builder
    {
        $query = DB::table('transferencias.transferencias as t')->whereNull('t.fecha_eliminacion');
        $usuario = self::usuarioActual();
        if ($usuario?->esTransferencias() && ! $usuario->esEncargadoArchivo()) {
            $query->where('t.usuario_solicitante_id', Auth::id());
        }
        return $query;
    }

    /**
     * Recibe filas ya agregadas por SQL con las columnas label y total.
     * Devuelve las diez categorías mayores y, si corresponde, una categoría Otros.
     */
    public static function topDiezMasOtros(Collection $filas): array
    {
        $ordenadas = $filas
            ->map(fn ($fila): array => [
                'label' => (string) $fila->label,
                'total' => (int) $fila->total,
            ])
            ->sortByDesc('total')
            ->values();
        $principales = $ordenadas->take(10)->values();
        $restantes = $ordenadas->skip(10);
        $labels = $principales->pluck('label')->all();
        $datos = $principales->pluck('total')->all();
        if ($restantes->isNotEmpty()) {
            $labels[] = 'Otros';
            $datos[] = (int) $restantes->sum('total');
        }
        return ['labels' => $labels, 'data' => $datos];
    }
}

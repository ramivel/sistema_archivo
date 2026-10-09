<?php

use App\Services\PlantillaTransferenciaExcelService;
use App\Services\TransferenciaService;
use Illuminate\Support\Facades\Route;
use App\Models\Transferencia;
use App\Models\InventarioExpediente;
use App\Services\TransferenciaPdfService;

Route::get('/', function () {
    return redirect('/admin');
});
Route::middleware('auth')->get(
    '/transferencias/plantilla-excel',
    function (PlantillaTransferenciaExcelService $service) {
        $ruta = $service->generar();
        return response()
            ->download($ruta,'plantilla_transferencia.xlsx')
            ->deleteFileAfterSend(true);
    }
)->name('transferencias.plantilla-transferencia-excel');
Route::middleware('auth')->get(
    '/transferencias/plantilla-regularizacion-excel',
    function (PlantillaTransferenciaExcelService $service) {
        $ruta = $service->generar(esRegularizacion: true);
        return response()
            ->download($ruta, 'plantilla_regularizacion.xlsx')
            ->deleteFileAfterSend(true);
    }
)->name('transferencias.plantilla-regularizacion-excel');
Route::middleware('auth')->get(
    '/transferencias/{transferencia}/imprimir-etiquetas',
    function (
        Transferencia $transferencia,
        TransferenciaPdfService $service
    ) {
        return $service->descargarEtiquetas($transferencia);
    }
)->name('transferencias.imprimir-etiquetas');

Route::middleware('auth')->get(
    '/transferencias/{transferencia}/imprimir-solicitud',
    function (
        Transferencia $transferencia,
        TransferenciaPdfService $service
    ) {
        return $service->descargarSolicitud($transferencia);
    }
)->name('transferencias.imprimir-solicitud');

Route::middleware('auth')->get(
    '/transferencias/{transferencia}/imprimir-formulario-complementario',
    function (
        Transferencia $transferencia,
        TransferenciaPdfService $service
    ) {
        return $service->descargarFormularioComplementario(
            $transferencia
        );
    }
)->name('transferencias.imprimir-formulario-complementario');

Route::middleware('auth')->get(
    '/transferencias/{transferencia}/documento/{tipo}',
    function (
        Transferencia $transferencia,
        string $tipo,
        TransferenciaService $service
    ) {
        $ruta = $service->obtenerRutaDocumento(
            transferencia: $transferencia,
            tipo: $tipo
        );
        if ($tipo === 'archivo-excel') {
            return response()->download($ruta);
        }
        return response()->file($ruta, [
            'Content-Type' => 'application/pdf',
        ]);
    }
)->name('transferencias.documento');

Route::middleware('auth')->get(
    '/inventario/{expediente}/imprimir-etiqueta',
    function (
        InventarioExpediente $expediente,
        TransferenciaPdfService $service
    ) {
        return $service->descargarEtiquetaInventario(
            $expediente
        );
    }
)->name('inventario.imprimir-etiqueta');
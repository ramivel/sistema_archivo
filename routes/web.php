<?php

use App\Services\PlantillaTransferenciaExcelService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

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

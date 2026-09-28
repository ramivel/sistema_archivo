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
        Auth::id();
        $ruta = $service->generar();
        return response()
            ->download($ruta,'plantilla_transferencia.xlsx')
            ->deleteFileAfterSend(true);
    }
)->name('transferencias.plantilla-excel');

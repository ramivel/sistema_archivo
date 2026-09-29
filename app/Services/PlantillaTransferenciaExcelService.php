<?php

namespace App\Services;

use App\Models\Parametro;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PlantillaTransferenciaExcelService
{
    public function generar(): string
    {
        $rutaConfigurada = env('PLANTILLA_TRANSFERENCIA_EXCEL');
        $rutaPlantilla = base_path($rutaConfigurada);
        if (! is_file($rutaPlantilla)) {
            throw new \RuntimeException(
                "No se encontró la plantilla de transferencia Excel: {$rutaPlantilla}"
            );
        }
        $spreadsheet = IOFactory::load($rutaPlantilla);
        $usuario = User::find(Auth::id());
        if ($usuario) {
            $this->reemplazarUbicacionUsuario($spreadsheet, $usuario);
        }
        $this->crearHojaParametrica($spreadsheet);
        $rutaTemporal = storage_path('app/private/transferencias/plantillas/plantilla_transferencia.xlsx');
        $directorio = dirname($rutaTemporal);
        if (! is_dir($directorio)) {
            mkdir($directorio, 0755, true);
        }
        $writer = new Xlsx($spreadsheet);
        $writer->save($rutaTemporal);
        return $rutaTemporal;
    }

    private function reemplazarUbicacionUsuario(Spreadsheet $spreadsheet, User $usuario): void
    {
        $hoja = $spreadsheet->getSheetByName('TRANSFERENCIA');
        if (! $hoja) {
            throw new \RuntimeException(
                'La plantilla de transferencia Excel debe contener una hoja llamada "TRANSFERENCIA".'
            );
        }
        $fondo = Parametro::query()
            ->whereKey($usuario->oficina_parametro_id)
            ->where('grupo', 'FONDO')
            ->where('activo', true)
            ->whereNull('fecha_eliminacion')
            ->first();
        $subfondo = Parametro::query()
            ->whereKey($usuario->direccion_parametro_id)
            ->where('grupo', 'SUBFONDO')
            ->where('activo', true)
            ->whereNull('fecha_eliminacion')
            ->first();
        $seccion = Parametro::query()
            ->whereKey($usuario->area_parametro_id)
            ->where('grupo', 'SECCION')
            ->where('activo', true)
            ->whereNull('fecha_eliminacion')
            ->first();
        $hoja->setCellValue('C1', $fondo?->valor);
        $hoja->setCellValue('C2', $subfondo?->valor);
        $hoja->setCellValue('C3', $seccion?->valor);
    }

    private function crearHojaParametrica(Spreadsheet $spreadsheet): void {
        $hojaExistente = $spreadsheet->getSheetByName('PARAMETRICA');
        if ($hojaExistente) {
            $indice = $spreadsheet->getIndex($hojaExistente);
            $spreadsheet->removeSheetByIndex($indice);
        }
        $hoja = new Worksheet($spreadsheet,'PARAMETRICA');
        $spreadsheet->addSheet($hoja);
        $parametricas = [
            1 => [
                'FONDO:',
                'FONDO',
                'valor',
            ],
            2 => [
                'SUBFONDO:',
                'SUBFONDO',
                'valor',
            ],
            3 => [
                'SECCION:',
                'SECCION',
                'valor',
            ],
            4 => [
                'PROCEDENCIA:',
                'PROCEDENCIA',
                'sigla',
            ],
            5 => [
                'SERIE DOCUMENTAL:',
                'SERIE_DOCUMENTAL',
                'valor',
            ],
            6 => [
                'SOPORTE:',
                'SOPORTE',
                'valor',
            ],
        ];
        foreach ($parametricas as $fila => [$etiqueta, $grupo, $campo]) {
            $hoja->setCellValue("A{$fila}",$etiqueta);
            $valores = Parametro::query()
                ->where('grupo', $grupo)
                ->where('activo', true)
                ->whereNull('fecha_eliminacion')
                ->orderBy('orden')
                ->orderBy($campo)
                ->pluck($campo)
                ->filter(fn ($valor) => filled($valor))
                ->values()
                ->all();
            foreach ($valores as $indice => $valor) {
                $columna = $indice + 2;
                $letraColumna = Coordinate::stringFromColumnIndex($columna);
                $hoja->setCellValue(
                    "{$letraColumna}{$fila}",
                    $valor
                );
            }
        }
        $this->configurarFormato($hoja);
    }

    private function configurarFormato(Worksheet $hoja): void {
        $hoja->getColumnDimension('A')->setWidth(24);
        $hoja->getColumnDimension('B')->setWidth(35);
        $ultimaColumna = $hoja->getHighestColumn();
        $ultimaColumnaIndice = Coordinate::columnIndexFromString($ultimaColumna);
        for ($columna = 2; $columna <= $ultimaColumnaIndice; $columna++) {
            $letraColumna = Coordinate::stringFromColumnIndex($columna);
            $hoja->getColumnDimension($letraColumna)->setWidth(35);
        }
        $hoja
            ->getStyle("A1:{$ultimaColumna}6")
            ->getAlignment()
            ->setVertical('top');
        $hoja
            ->getStyle('A1:A6')
            ->getFont()
            ->setBold(true);
        $hoja->freezePane('B1');
    }
}
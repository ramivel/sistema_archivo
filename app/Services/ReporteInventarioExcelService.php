<?php

namespace App\Services;

use App\Models\InventarioExpediente;
use App\Models\Parametro;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReporteInventarioExcelService
{
    private const TITULO = 'REPORTE POR FONDO / SUBFONDO / SECCIÓN';
    private const CABECERAS = [
        'ESTADO',
        'COD INVENTARIO',
        'COD REF',
        'CAJA',
        'PROCEDENCIA',
        'SERIE DOCUMENTAL',
        'DESCRIPCIÓN DOCUMENTAL',
        'DETALLE',
        'TOMO',
        'FOJAS',
        'FECHAS EXTREMAS',
        'SOPORTE',
        'OBSERVACIONES',
    ];
    private const ANCHOS_COLUMNAS = [
        'A' => 18,
        'B' => 20,
        'C' => 16,
        'D' => 12,
        'E' => 28,
        'F' => 28,
        'G' => 45,
        'H' => 45,
        'I' => 14,
        'J' => 14,
        'K' => 20,
        'L' => 18,
        'M' => 45,
    ];

    public function generar(array $filtros): string
    {
        $expedientes = InventarioExpediente::query()
            ->with([
                'oficina',
                'direccion',
                'area',
                'serieDocumental',
                'soporte',
                'estado',
            ])
            ->when(
                filled($filtros['oficina_parametro_id'] ?? null),
                fn ($query) => $query->where(
                    'oficina_parametro_id',
                    $filtros['oficina_parametro_id']
                )
            )
            ->when(
                filled($filtros['direccion_parametro_id'] ?? null),
                fn ($query) => $query->where(
                    'direccion_parametro_id',
                    $filtros['direccion_parametro_id']
                )
            )
            ->when(
                filled($filtros['area_parametro_id'] ?? null),
                fn ($query) => $query->where(
                    'area_parametro_id',
                    $filtros['area_parametro_id']
                )
            )
            ->orderBy('id')
            ->get();
        $spreadsheet = new Spreadsheet();
        $hoja = $spreadsheet->getActiveSheet();
        $hoja->setTitle('Reporte');
        $ultimaColumna = 'M';
        $hoja->mergeCells("A1:{$ultimaColumna}1");
        $hoja->mergeCells("A2:{$ultimaColumna}2");
        $hoja->setCellValue('A1', self::TITULO);
        $hoja->setCellValue('A2', $this->textoFiltrosReporte($filtros));
        $hoja->getStyle("A1:{$ultimaColumna}1")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $hoja->getStyle("A2:{$ultimaColumna}2")->applyFromArray([
            'font' => [
                'size' => 11,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);
        $hoja->getRowDimension(1)->setRowHeight(24);
        $hoja->getRowDimension(2)->setRowHeight(30);
        $filaEncabezados = 4;
        foreach (self::CABECERAS as $indice => $cabecera) {
            $columna = chr(ord('A') + $indice);
            $hoja->setCellValue(
                "{$columna}{$filaEncabezados}",
                $cabecera
            );
        }
        $hoja->getStyle("A{$filaEncabezados}:{$ultimaColumna}{$filaEncabezados}")
            ->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => [
                        'rgb' => 'FFFFFF',
                    ],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => [
                        'rgb' => '1F4E78',
                    ],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => [
                            'rgb' => 'B7C9D6',
                        ],
                    ],
                ],
            ]);
        $hoja->getRowDimension($filaEncabezados)->setRowHeight(36);
        if ($expedientes->isEmpty()) {
            $hoja->mergeCells("A5:{$ultimaColumna}5");
            $hoja->setCellValue(
                'A5',
                'No se encontraron expedientes con los filtros seleccionados.'
            );
            $hoja->getStyle("A5:{$ultimaColumna}5")->applyFromArray([
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => [
                            'rgb' => 'D9D9D9',
                        ],
                    ],
                ],
            ]);
        } else {
            $fila = 5;
            foreach ($expedientes as $expediente) {
                $datos = [
                    $expediente->estado?->valor ?? '-',
                    $expediente->codigo_inventario ?? '-',
                    $expediente->codigo_referencia ?? '-',
                    $expediente->numero_caja ?? '-',
                    $expediente->procedencia ?? '-',
                    $expediente->serieDocumental?->valor ?? '-',
                    $expediente->descripcion_lomo ?? '-',
                    $expediente->detalle ?? '-',
                    $expediente->tomo_volumen ?? '-',
                    $expediente->fojas ?? '-',
                    $expediente->fechas_extremas ?? '-',
                    $expediente->soporte?->valor ?? '-',
                    $expediente->observaciones ?? '-',
                ];
                foreach ($datos as $indice => $valor) {
                    $columna = chr(ord('A') + $indice);
                    $hoja->setCellValueExplicit(
                        "{$columna}{$fila}",
                        $this->valorCelda($valor),
                        DataType::TYPE_STRING
                    );
                }
                $fila++;
            }
            $ultimaFila = $fila - 1;
            $hoja->getStyle("A5:{$ultimaColumna}{$ultimaFila}")
                ->applyFromArray([
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_TOP,
                        'wrapText' => true,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => [
                                'rgb' => 'D9D9D9',
                            ],
                        ],
                    ],
                ]);
            $hoja->setAutoFilter(
                "A{$filaEncabezados}:{$ultimaColumna}{$ultimaFila}"
            );
        }
        foreach (self::ANCHOS_COLUMNAS as $columna => $ancho) {
            $hoja->getColumnDimension($columna)->setWidth($ancho);
        }
        $hoja->freezePane('A5');
        $hoja->getPageSetup()
            ->setOrientation(
                \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE
            );
        $hoja->getPageSetup()
            ->setPaperSize(
                \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_LETTER
            );
        $nombreArchivo = 'reporte_inventario.xlsx';
        $rutaTemporal = storage_path(
            "app/private/reportes/{$nombreArchivo}"
        );
        $directorio = dirname($rutaTemporal);
        if (! is_dir($directorio)) {
            mkdir($directorio, 0755, true);
        }
        (new Xlsx($spreadsheet))->save($rutaTemporal);
        return $rutaTemporal;
    }

    private function textoFiltrosReporte(array $filtros): string
    {
        $oficina = Parametro::find(
            $filtros['oficina_parametro_id'] ?? null
        );
        $direccion = Parametro::find(
            $filtros['direccion_parametro_id'] ?? null
        );
        $area = Parametro::find(
            $filtros['area_parametro_id'] ?? null
        );
        return 'FONDO: ' . ($oficina?->valor ?? '-')
            . ' | SUBFONDO: ' . ($direccion?->valor ?? '-')
            . ' | SECCIÓN: ' . ($area?->valor ?? 'TODAS');
    }

    private function valorCelda(mixed $valor): string
    {
        $valor = trim((string) $valor);
        return $valor !== '' ? $valor : '-';
    }
}
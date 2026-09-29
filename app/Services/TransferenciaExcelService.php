<?php

namespace App\Services;

use App\Models\Parametro;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Throwable;

class TransferenciaExcelService
{
    private const FILA_DATOS = 5;
    private const NOMBRE_HOJA = 'TRANSFERENCIA';
    private const TAMANO_BLOQUE = 2000;
    private const COLUMNAS = [
        'CODIGO DE REFERENCIA',
        'N° DE CAJA',
        'PROCEDENCIA',
        'SERIE DOCUMENTAL',
        'DESCRIPCION DOCUMENTAL (LOMO)',
        'DETALLE',
        'TOMO / VOLUMEN',
        'FOJAS',
        'FECHAS EXTREMAS (AÑOS)',
        'SOPORTE',
        'OBSERVACIONES',
    ];

    public function validar(
        string $archivo,
        string $disk,
        string $fondo,
        string $subfondo,
        ?string $seccion,
    ): array {
        $ruta = Storage::disk($disk)->path($archivo);
        if (! is_file($ruta)) {
            throw new \RuntimeException(
                'No se encontró el archivo Excel cargado.'
            );
        }
        $reader = IOFactory::createReaderForFile($ruta);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($ruta);
        if (! $spreadsheet->sheetNameExists(self::NOMBRE_HOJA)) {
            throw new \RuntimeException(
                'El archivo Excel debe contener una hoja llamada "TRANSFERENCIA".'
            );
        }
        $sheet = $spreadsheet->getSheetByName(self::NOMBRE_HOJA);
        $resultado = [
            'total_registros' => 0,
            'total_validos' => 0,
            'total_invalidos' => 0,
            'errores_generales' => [],
            'errores' => [],
            'expedientes' => [],
        ];
        $this->validarUbicacionArchivistica(
            $sheet,
            $fondo,
            $subfondo,
            $seccion,
            $resultado
        );
        $this->validarEncabezados($sheet, $resultado);
        if (! empty($resultado['errores_generales'])) {
            return $resultado;
        }
        $highestRow = $sheet->getHighestDataRow();
        for (
            $inicio = self::FILA_DATOS;
            $inicio <= $highestRow;
            $inicio += self::TAMANO_BLOQUE
        ) {
            $fin = min(
                $inicio + self::TAMANO_BLOQUE - 1,
                $highestRow
            );
            $filter = new ExcelChunkReadFilter(
                $inicio,
                $fin
            );
            $reader->setReadFilter($filter);
            $chunkSpreadsheet = $reader->load($ruta);
            $chunkSheet = $chunkSpreadsheet->getSheetByName(self::NOMBRE_HOJA);
            for ($fila = $inicio; $fila <= $fin; $fila++) {
                $valores = $this->obtenerFila(
                    $chunkSheet,
                    $fila
                );
                if ($this->filaVacia($valores)) {
                    continue;
                }
                $resultado['total_registros']++;
                $errores = $this->validarFila(
                    $valores,
                    $fila
                );
                if ($errores !== []) {
                    $resultado['total_invalidos']++;
                    $resultado['errores'][] = [
                        'numero' => $resultado['total_registros'],
                        'celda' => $fila,
                        'errores' => implode(' ', $errores),
                    ];
                    continue;
                }
                $resultado['total_validos']++;
                $resultado['expedientes'][] = [
                    'numero' => $resultado['total_registros'],
                    'codigo_referencia' => $valores[0],
                    'numero_caja' => $valores[1],
                    'procedencias' => $this->normalizarProcedencias(
                        $valores[2]
                    ),
                    'serie_documental' => $valores[3],
                    'descripcion_lomo' => $valores[4],
                    'detalle' => $valores[5],
                    'tomo_volumen' => $valores[6],
                    'fojas' => $valores[7],
                    'fechas_extremas' => $valores[8],
                    'soporte' => $valores[9],
                    'observaciones' => $valores[10],
                ];
            }
            $chunkSpreadsheet->disconnectWorksheets();
            unset($chunkSpreadsheet, $chunkSheet);
        }
        return $resultado;
    }

    private function validarUbicacionArchivistica(
        Worksheet $sheet,
        string $fondo,
        string $subfondo,
        ?string $seccion,
        array &$resultado
    ): void {
        $excelFondo = $this->normalizar(
            $sheet->getCell('C1')->getValue()
        );
        $excelSubfondo = $this->normalizar(
            $sheet->getCell('C2')->getValue()
        );
        $excelSeccion = $this->normalizar(
            $sheet->getCell('C3')->getValue()
        );
        if ($excelFondo !== $this->normalizar($fondo)) {
            $resultado['errores_generales'][] = "El FONDO del Excel no coincide. Excel: {$excelFondo}. Seleccionado: {$fondo}.";
        }
        if ($excelSubfondo !== $this->normalizar($subfondo)) {
            $resultado['errores_generales'][] = "El SUBFONDO del Excel no coincide. Excel: {$excelSubfondo}. Seleccionado: {$subfondo}.";
        }
        if ($seccion !== null && $excelSeccion !== $this->normalizar($seccion)) {
            $resultado['errores_generales'][] = "La SECCIÓN del Excel no coincide. Excel: {$excelSeccion}. Seleccionado: {$seccion}.";
        }
    }

    private function validarEncabezados(
        Worksheet $sheet,
        array &$resultado
    ): void {
        foreach (self::COLUMNAS as $indice => $esperada) {
            $columna = $this->numeroColumna($indice);
            $actual = $this->normalizar(
                $sheet->getCell(
                    $columna . '4'
                )->getValue()
            );
            if ($actual !== $this->normalizar($esperada)) {
                $resultado['errores_generales'][] = "La columna {$columna}4 debe ser \"{$esperada}\" y se encontró \"{$actual}\".";
            }
        }
    }

    private function validarFila(
        array $valores,
        int $fila
    ): array {
        $errores = [];
        if ($this->vacio($valores[0])) {
            $errores[] = "CELDA A{$fila}: CODIGO DE REFERENCIA es obligatorio.";
        }
        if ($this->vacio($valores[1])) {
            $errores[] = "CELDA B{$fila}: N° DE CAJA es obligatorio.";
        }
        if ($this->vacio($valores[2])) {
            $errores[] = "CELDA C{$fila}: PROCEDENCIA es obligatorio.";
        }
        if ($this->vacio($valores[3])) {
            $errores[] = "CELDA D{$fila}: SERIE DOCUMENTAL es obligatorio.";
        } elseif (! $this->existeParametro(
            'SERIE_DOCUMENTAL',
            $valores[3]
        )) {
            $errores[] = "CELDA D{$fila}: SERIE DOCUMENTAL \"{$valores[3]}\" no existe en la parametrización.";
        }
        if ($this->vacio($valores[9])) {
            $errores[] = "CELDA J{$fila}: SOPORTE es obligatorio.";
        } elseif (! $this->existeParametro(
            'SOPORTE',
            $valores[9]
        )) {
            $errores[] = "CELDA J{$fila}: SOPORTE \"{$valores[9]}\" no existe en la parametrización.";
        }
        if (! $this->vacio($valores[2])) {
            $procedencias = $this->normalizarProcedencias($valores[2]);
            foreach ($procedencias as $procedencia) {
                if (! $this->existeParametro('PROCEDENCIA',$procedencia,'sigla')) {
                    $errores[] = "CELDA C{$fila}: PROCEDENCIA \"{$procedencia}\" no existe en la parametrización.";
                }
            }
        }
        if (! $this->vacio($valores[7])) {
            $fojas = preg_replace('/\s+/', '', mb_strtoupper((string) $valores[7], 'UTF-8'));
            if ($fojas !== 'S/F' && ! preg_match('/^[0-9-]+$/', $fojas)) {
                $errores[] = "CELDA H{$fila}: FOJAS solo puede contener números, el carácter \"-\" o \"S/F\".";
            }
        }
        if (! $this->vacio($valores[8])) {
            $fechasExtremas = preg_replace('/\s+/', '', (string) $valores[8]);
            if (! preg_match('/^[0-9-]+$/', $fechasExtremas)) {
                $errores[] = "CELDA I{$fila}: FECHAS EXTREMAS (AÑOS) solo puede contener números y el carácter \"-\".";
            }
        }
        return $errores;
    }

    private function existeParametro(
        string $grupo,
        string $valor,
        string $campo = 'valor'
    ): bool {
        return Parametro::query()
            ->where('grupo', $grupo)
            ->where('activo', true)
            ->whereNull('fecha_eliminacion')
            ->whereRaw(
                "UPPER(TRIM({$campo})) = ?",
                [mb_strtoupper(trim($valor), 'UTF-8')]
            )
            ->exists();
    }

    private function obtenerFila(
        Worksheet $sheet,
        int $fila
    ): array {
        $valores = [];
        for ($columna = 0; $columna < count(self::COLUMNAS); $columna++) {
            $letra = $this->numeroColumna($columna);
            $valor = $sheet->getCell($letra . $fila)->getValue();
            if (is_string($valor)) {
                $valor = trim($valor);
                if ($columna === 7 || $columna === 8)
                    $valor = preg_replace('/\s+/', '', $valor);
            }
            $valores[] = $valor;
        }
        return $valores;
    }

    private function normalizarProcedencias(mixed $valor): array {
        if ($this->vacio($valor)) {
            return [];
        }
        return collect(explode(',', (string) $valor))
            ->map(fn ($procedencia) => mb_strtoupper(trim($procedencia), 'UTF-8'))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function normalizar(mixed $valor): string
    {
        $valor = trim((string) $valor);
        if (str_ends_with($valor, ':')) {
            $valor = substr($valor, 0, -1);
        }
        return mb_strtoupper(trim($valor), 'UTF-8');
    }

    private function vacio(mixed $valor): bool
    {
        return $valor === null || trim((string) $valor) === '';
    }

    private function filaVacia(array $valores): bool
    {
        foreach ($valores as $valor) {
            if (!$this->vacio($valor)) {
                return false;
            }
        }
        return true;
    }

    private function numeroColumna(int $indice): string
    {
        return chr(65 + $indice);
    }
}

class ExcelChunkReadFilter implements IReadFilter
{
    public function __construct(
        private readonly int $inicio,
        private readonly int $fin,
    ) {
    }

    public function readCell(
        $columnAddress,
        $row,
        $worksheetName = ''
    ): bool {
        return $row >= $this->inicio && $row <= $this->fin;
    }
}
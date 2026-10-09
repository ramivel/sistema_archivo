<?php

namespace App\Services;

use App\Models\Parametro;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TransferenciaExcelService
{
    private const FILA_DATOS = 5;
    private const NOMBRE_HOJA = 'TRANSFERENCIA';
    private const TAMANO_BLOQUE = 2000;
    private const COLUMNAS_TRANSFERENCIA = [
        'CODIGO DE REFERENCIA',
        'N° DE CAJA',
        'SERIE DOCUMENTAL',
        'DESCRIPCION DOCUMENTAL (LOMO)',
        'DETALLE',
        'TOMO / VOLUMEN',
        'FOJAS',
        'FECHAS EXTREMAS (AÑOS)',
        'SOPORTE',
        'OBSERVACIONES',
    ];
    private const COLUMNAS_REGULARIZACION = [
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
        bool $esRegularizacion = false,
        ?string $procedenciaAutomatica = null,
    ): array {
        $ruta = Storage::disk($disk)->path($archivo);
        if (! is_file($ruta)) {
            throw new \RuntimeException(
                'No se encontró el archivo Excel cargado.'
            );
        }
        $columnas = $esRegularizacion
            ? self::COLUMNAS_REGULARIZACION
            : self::COLUMNAS_TRANSFERENCIA;
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
        $this->validarEncabezados(
            $sheet,
            $columnas,
            $resultado
        );
        if (! empty($resultado['errores_generales'])) {
            return $resultado;
        }
        $highestRow = $sheet->getHighestDataRow();
        for ($inicio = self::FILA_DATOS; $inicio <= $highestRow; $inicio += self::TAMANO_BLOQUE) {
            $fin = min($inicio + self::TAMANO_BLOQUE - 1, $highestRow);
            $reader->setReadFilter(new ExcelChunkReadFilter($inicio, $fin));
            $chunkSpreadsheet = $reader->load($ruta);
            $chunkSheet = $chunkSpreadsheet->getSheetByName(self::NOMBRE_HOJA);
            for ($fila = $inicio; $fila <= $fin; $fila++) {
                $valores = $this->obtenerFila(
                    $chunkSheet,
                    $fila,
                    count($columnas)
                );
                if ($this->filaVacia($valores)) {
                    continue;
                }
                $resultado['total_registros']++;
                $errores = $this->validarFila(
                    valores: $valores,
                    fila: $fila,
                    esRegularizacion: $esRegularizacion,
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
                if ($esRegularizacion) {
                    $resultado['expedientes'][] = [
                        'numero' => $resultado['total_registros'],
                        'codigo_referencia' => $valores[0],
                        'numero_caja' => $this->vacio($valores[1]) ? null : trim((string) $valores[1]),
                        'procedencia' => $this->normalizarProcedencia($valores[2]),
                        'serie_documental' => $valores[3],
                        'descripcion_lomo' => $valores[4],
                        'detalle' => $valores[5],
                        'tomo_volumen' => $valores[6],
                        'fojas' => $valores[7],
                        'fechas_extremas' => $valores[8],
                        'soporte' => $valores[9],
                        'observaciones' => $valores[10],
                    ];
                    continue;
                }
                $resultado['expedientes'][] = [
                    'numero' => $resultado['total_registros'],
                    'codigo_referencia' => $valores[0],
                    'numero_caja' => $this->vacio($valores[1]) ? null : trim((string) $valores[1]),
                    'procedencia' => $procedenciaAutomatica,
                    'serie_documental' => $valores[2],
                    'descripcion_lomo' => $valores[3],
                    'detalle' => $valores[4],
                    'tomo_volumen' => $valores[5],
                    'fojas' => $valores[6],
                    'fechas_extremas' => $valores[7],
                    'soporte' => $valores[8],
                    'observaciones' => $valores[9],
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
        array $columnas,
        array &$resultado,
    ): void {
        foreach ($columnas as $indice => $esperada) {
            $columna = $this->numeroColumna($indice);
            $actual = $this->normalizar($sheet->getCell($columna . '4')->getValue());
            if ($actual !== $this->normalizar($esperada)) {
                $resultado['errores_generales'][] =
                    "La columna {$columna}4 debe ser "
                    . "\"{$esperada}\" y se encontró \"{$actual}\".";
            }
        }
    }

    private function validarFila(
        array $valores,
        int $fila,
        bool $esRegularizacion,
    ): array {
        $errores = [];

        $indiceProcedencia = $esRegularizacion ? 2 : null;
        $indiceSerie = $esRegularizacion ? 3 : 2;
        $indiceDescripcion = $esRegularizacion ? 4 : 3;
        $indiceDetalle = $esRegularizacion ? 5 : 4;
        $indiceTomo = $esRegularizacion ? 6 : 5;
        $indiceFojas = $esRegularizacion ? 7 : 6;
        $indiceFechas = $esRegularizacion ? 8 : 7;
        $indiceSoporte = $esRegularizacion ? 9 : 8;
        $indiceObservaciones = $esRegularizacion ? 10 : 9;

        if ($this->vacio($valores[0])) {
            $errores[] =
                "CELDA A{$fila}: CODIGO DE REFERENCIA es obligatorio.";
        } elseif (mb_strlen(trim((string) $valores[0])) > 50) {
            $errores[] =
                "CELDA A{$fila}: CODIGO DE REFERENCIA no puede superar los 50 caracteres.";
        }

        if (
            $esRegularizacion
            && $this->vacio($valores[$indiceProcedencia])
        ) {
            $errores[] =
                "CELDA C{$fila}: PROCEDENCIA es obligatoria.";
        }

        if (
            ! $this->vacio($valores[1])
            && (
                mb_strlen(trim((string) $valores[1])) > 20
                || ! preg_match(
                    '/^[1-9][0-9]*(\/[1-9][0-9]*)?$/',
                    trim((string) $valores[1])
                )
            )
        ) {
            $errores[] =
                "CELDA B{$fila}: N° DE CAJA debe tener un formato válido, por ejemplo 1, 2, 3, 1/3, 2/3 o 3/3.";
        }

        if ($this->vacio($valores[$indiceSerie])) {
            $columna = $this->numeroColumna($indiceSerie);

            $errores[] =
                "CELDA {$columna}{$fila}: SERIE DOCUMENTAL es obligatoria.";
        } elseif (
            ! $this->existeParametro(
                'SERIE_DOCUMENTAL',
                $valores[$indiceSerie]
            )
        ) {
            $columna = $this->numeroColumna($indiceSerie);

            $errores[] =
                "CELDA {$columna}{$fila}: SERIE DOCUMENTAL "
                . "\"{$valores[$indiceSerie]}\" no existe.";
        }

        if ($this->vacio($valores[$indiceDescripcion])) {
            $columna = $this->numeroColumna($indiceDescripcion);

            $errores[] =
                "CELDA {$columna}{$fila}: DESCRIPCION DOCUMENTAL (LOMO) es obligatoria.";
        }

        if ($this->vacio($valores[$indiceDetalle])) {
            $columna = $this->numeroColumna($indiceDetalle);

            $errores[] =
                "CELDA {$columna}{$fila}: DETALLE es obligatorio.";
        }

        if ($this->vacio($valores[$indiceTomo])) {
            $columna = $this->numeroColumna($indiceTomo);

            $errores[] =
                "CELDA {$columna}{$fila}: TOMO / VOLUMEN es obligatorio.";
        } elseif (
            ! preg_match(
                '/^[1-9][0-9]*$/',
                trim((string) $valores[$indiceTomo])
            )
        ) {
            $columna = $this->numeroColumna($indiceTomo);

            $errores[] =
                "CELDA {$columna}{$fila}: TOMO / VOLUMEN debe ser un número entero mayor o igual a 1.";
        }

        if ($this->vacio($valores[$indiceFojas])) {
            $columna = $this->numeroColumna($indiceFojas);

            $errores[] =
                "CELDA {$columna}{$fila}: FOJAS es obligatorio.";
        } else {
            $fojas = preg_replace(
                '/\s+/',
                '',
                mb_strtoupper(
                    trim((string) $valores[$indiceFojas]),
                    'UTF-8'
                )
            );

            if (mb_strlen($fojas) > 30) {
                $columna = $this->numeroColumna($indiceFojas);

                $errores[] =
                    "CELDA {$columna}{$fila}: FOJAS no puede superar los 30 caracteres.";
            } elseif (! preg_match('/^(S\/F|[0-9-]+)$/i', $fojas)) {
                $columna = $this->numeroColumna($indiceFojas);

                $errores[] =
                    "CELDA {$columna}{$fila}: FOJAS debe contener números, rangos con guion o S/F.";
            }
        }

        $columna = $this->numeroColumna($indiceFechas);
        $fechas = preg_replace('/\s+/', '',trim((string) $valores[$indiceFechas]));
        if (mb_strlen($fechas) > 30) {
            $errores[] = "CELDA {$columna}{$fila}: FECHAS EXTREMAS no puede superar los 30 caracteres.";
        } elseif ($esRegularizacion && in_array($fechas, ['', '-'], true)) {
            // En regularizaciones se permite vacío o "-".
        } elseif (
            ! preg_match(
                '/^[0-9]{4}(?:-[0-9]{4})?$/',
                $fechas
            )
        ) {
            $mensaje = $esRegularizacion
                ? 'FECHAS EXTREMAS debe estar vacío, contener "-" o tener el formato AAAA o AAAA-AAAA.'
                : 'FECHAS EXTREMAS es obligatorio y debe tener el formato AAAA o AAAA-AAAA.';

            $errores[] = "CELDA {$columna}{$fila}: {$mensaje}";
        }

        if ($this->vacio($valores[$indiceSoporte])) {
            $columna = $this->numeroColumna($indiceSoporte);

            $errores[] =
                "CELDA {$columna}{$fila}: SOPORTE es obligatorio.";
        } elseif (
            ! $this->existeParametro(
                'SOPORTE',
                $valores[$indiceSoporte]
            )
        ) {
            $columna = $this->numeroColumna($indiceSoporte);

            $errores[] =
                "CELDA {$columna}{$fila}: SOPORTE "
                . "\"{$valores[$indiceSoporte]}\" no existe.";
        }

        if ($this->vacio($valores[$indiceObservaciones])) {
            $columna = $this->numeroColumna($indiceObservaciones);

            $errores[] =
                "CELDA {$columna}{$fila}: OBSERVACIONES es obligatorio.";
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
        int $fila,
        int $cantidadColumnas,
    ): array {
        $valores = [];
        for ($columna = 0; $columna < $cantidadColumnas; $columna++) {
            $letra = $this->numeroColumna($columna);
            $valor = $sheet->getCell($letra . $fila)->getValue();
            if (is_string($valor)) {
                $valor = trim($valor);
                if (in_array($columna, [6, 7, 8], true)) {
                    $valor = preg_replace('/\s+/', '', $valor);
                }
            }
            $valores[] = $valor;
        }
        return $valores;
    }

    private function normalizarProcedencia(mixed $valor): ?string
    {
        if ($this->vacio($valor))
            return null;
        return mb_strtoupper(trim((string) $valor),'UTF-8');
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
        foreach ($valores as $valor)
            if (!$this->vacio($valor))
                return false;
        return true;
    }

    private function numeroColumna(int $indice): string
    {
        return chr(65 + $indice);
    }
}

class ExcelChunkReadFilter implements IReadFilter
{
    public function __construct(private readonly int $inicio, private readonly int $fin,) {
    }

    public function readCell($columnAddress, $row, $worksheetName = ''): bool {
        return $row >= $this->inicio && $row <= $this->fin;
    }
}
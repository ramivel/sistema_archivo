<?php

namespace App\Services;

use App\Models\Transferencia;
use App\Models\TransferenciaExpediente;
use App\Models\InventarioExpediente;
use Fpdf\Fpdf;
use Illuminate\Support\Str;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;

class TransferenciaPdf extends Fpdf
{
    public const FUENTE_TEXTO = 'Times';
    private ?string $bannerPath = null;
    private ?string $footerPath = null;
    private bool $mostrarEncabezado = true;
    private bool $mostrarPie = true;

    public function setBannerPath(?string $path): void
    {
        $this->bannerPath = $path;
    }

    public function setFooterPath(?string $path): void
    {
        $this->footerPath = $path;
    }

    public function setMostrarEncabezado(bool $mostrar): void
    {
        $this->mostrarEncabezado = $mostrar;
    }

    public function setMostrarPie(bool $mostrar): void
    {
        $this->mostrarPie = $mostrar;
    }

    public function Header(): void
    {
        if (! $this->mostrarEncabezado) {
            return;
        }
        if ($this->bannerPath && is_file($this->bannerPath))
            $this->Image($this->bannerPath, 10, 8, 196, 0, 'PNG');
        $this->SetY(40);
    }

    public function Footer(): void
    {
        if (! $this->mostrarPie) {
            return;
        }
        $ancho = 196;
        $altoFooter = $this->alturaFooter($ancho);
        if ($altoFooter > 0)
            $this->Image($this->footerPath,10,$this->GetPageHeight() - $altoFooter - 6,$ancho,0,'PNG');
        $this->SetY($this->GetPageHeight() - $altoFooter - 12);
        $this->SetFont(self::FUENTE_TEXTO, '', 9);
        $this->Cell(0,5,$this->PageNo() . ' - {nb}',0,0,'C');
    }

    public function alturaFooter(float $ancho = 196): float
    {
        if (! $this->footerPath || ! is_file($this->footerPath))
            return 0;
        $dimensiones = getimagesize($this->footerPath);
        if (! $dimensiones || $dimensiones[0] <= 0)
            return 0;
        return $ancho * ($dimensiones[1] / $dimensiones[0]);
    }

    public function lineCount(float $width, string $text): int
    {
        $text = $this->encode($text);
        $text = str_replace("\r", '', $text);
        $anchoDisponible = max(1, $width - 2);
        $cantidadCaracteres = strlen($text);
        if ($cantidadCaracteres === 0)
            return 1;
        $separador = -1;
        $inicioLinea = 0;
        $indice = 0;
        $anchoLinea = 0;
        $lineas = 1;
        while ($indice < $cantidadCaracteres) {
            $caracter = $text[$indice];
            if ($caracter === "\n") {
                $indice++;
                $separador = -1;
                $inicioLinea = $indice;
                $anchoLinea = 0;
                $lineas++;
                continue;
            }
            if ($caracter === ' ') {
                $separador = $indice;
            }
            $anchoLinea += $this->GetStringWidth($caracter);
            if ($anchoLinea > $anchoDisponible) {
                if ($separador === -1) {
                    if ($indice === $inicioLinea) {
                        $indice++;
                    }
                } else {
                    $indice = $separador + 1;
                }
                $separador = -1;
                $inicioLinea = $indice;
                $anchoLinea = 0;
                $lineas++;
                continue;
            }
            $indice++;
        }
        return max(1, $lineas);
    }

    public function headerRow(
        array $widths,
        array $texts,
        float $lineHeight = 3.5
    ): void {
        $this->SetFont(self::FUENTE_TEXTO, 'B', 6);
        $lineas = [];
        foreach ($texts as $indice => $text) {
            $lineas[] = max(
                1,
                $this->lineCount(
                    $widths[$indice],
                    $text
                )
            );
        }
        $alto = max($lineas) * $lineHeight;
        $x = $this->GetX();
        $y = $this->GetY();
        foreach ($texts as $indice => $text) {
            $ancho = $widths[$indice];
            $this->Rect(
                $x,
                $y,
                $ancho,
                $alto
            );
            $this->SetXY($x, $y);
            $this->MultiCell(
                $ancho,
                $lineHeight,
                $this->encode($text),
                0,
                'C'
            );
            $x += $ancho;
        }
        $this->SetXY(
            $this->GetX(),
            $y + $alto
        );
    }

    public function row(
        array $widths,
        array $texts,
        float $lineHeight = 3.5,
        int $fontSize = 8,
        array $alignments = []
    ): void {
        $this->SetFont(self::FUENTE_TEXTO, '', $fontSize);
        $lineas = [];
        foreach ($texts as $indice => $text) {
            $valor = $this->normalizeCell((string) $text);
            $lineas[] = max(
                1,
                $this->lineCount(
                    $widths[$indice],
                    $valor
                )
            );
        }
        $alto = max($lineas) * $lineHeight;
        $startX = $this->GetX();
        $startY = $this->GetY();
        foreach ($texts as $indice => $text) {
            $valor = $this->normalizeCell((string) $text);
            $ancho = $widths[$indice];
            $alineacion = $alignments[$indice] ?? 'L';
            $this->Rect($startX, $startY, $ancho, $alto);
            $this->SetXY($startX, $startY);
            $this->MultiCell(
                $ancho,
                $lineHeight,
                $this->encode($valor),
                0,
                $alineacion
            );
            $startX += $ancho;
        }
        $this->SetXY($startX,$startY + $alto);
    }

    public function rowHeight(
        array $widths,
        array $texts,
        float $lineHeight = 3.5,
        int $fontSize = 8
    ): float {
        $this->SetFont(self::FUENTE_TEXTO, '', $fontSize);
        $lineas = [];
        foreach ($texts as $indice => $text) {
            $valor = $this->normalizeCell((string) $text);
            $lineas[] = max(
                1,
                $this->lineCount(
                    $widths[$indice],
                    $valor
                )
            );
        }
        return max($lineas) * $lineHeight;
    }

    public function encode(?string $text): string
    {
        return iconv(
            'UTF-8',
            'windows-1252//TRANSLIT',
            trim((string) $text)
        ) ?: '';
    }

    public function normalizeCell(?string $text): string
    {
        $text = preg_replace('/\s+/u', ' ',trim((string) $text)) ?: '';
        return $text !== '' ? $text : '-';
    }

    public function limiteContenidoInferior(float $separacion = 16): float
    {
        return $this->GetPageHeight() - $this->alturaFooter() - $separacion;
    }
}

class TransferenciaPdfService
{
    private const MARGEN = 10;
    private const ETIQUETAS_FILAS_POR_PAGINA = 5;
    private const ETIQUETA_ALTO = 42;
    private const ETIQUETA_SEPARACION = 4;
    private const ETIQUETA_ANCHO_TOTAL = 196;
    private const ETIQUETA_ANCHO_LOGO = 35;
    private const ETIQUETA_ANCHO_QR = 35;
    private const ETIQUETA_ANCHO_DESCRIPCION = 75;
    private const ETIQUETA_ANCHO_CODIGO = 9;
    private const ETIQUETA_ANCHO_CODIGO_INVENTARIO = 42;
    private const ETIQUETA_TAMANO_QR = 32;
    private const ETIQUETA_TAMANO_LOGO = 31;
    private const ETIQUETA_TAMANO_FUENTE = 10;
    private const ETIQUETA_MAX_CARACTERES_DESCRIPCION = 180;
    private const ETIQUETA_TAMANO_CODIGO_INVENTARIO = 14;

    public function descargarEtiquetas(Transferencia $transferencia)
    {
        $transferencia->loadMissing([
            'fondo',
            'subfondo',
            'seccion',
            'usuarioSolicitante',
            'expedientes.correccionArchivo',
            'expedientes.inventario',
        ]);

        $pdf = new TransferenciaPdf('P', 'mm', 'Letter');
        $pdf->setMostrarEncabezado(false);
        $pdf->setMostrarPie(false);
        $pdf->SetMargins(self::MARGEN, self::MARGEN, self::MARGEN);
        $pdf->SetAutoPageBreak(true, self::MARGEN);

        $logo = resource_path('images/logo.png');

        foreach (
            $transferencia->expedientes
                ->where('activo', true)
                ->sortBy('id')
                ->values() as $indice => $expediente
        ) {
            if ($indice % self::ETIQUETAS_FILAS_POR_PAGINA === 0) {
                $pdf->AddPage();
                $pdf->SetFont(
                    TransferenciaPdf::FUENTE_TEXTO,
                    'B',
                    12
                );
                $pdf->Cell(
                    0,
                    8,
                    $pdf->encode($transferencia->correlativo),
                    0,
                    1,
                    'C'
                );
                $pdf->Ln(3);
                $y = $pdf->GetY();
            }

            $origen = $expediente->correccionArchivo ?? $expediente;
            $codigoInventario = $expediente->inventario
                ?->codigo_inventario ?? '-';

            $x = self::MARGEN;
            $alto = self::ETIQUETA_ALTO;
            $ancho = self::ETIQUETA_ANCHO_TOTAL;

            $xLogo = $x;
            $xQr = $xLogo + self::ETIQUETA_ANCHO_LOGO;
            $xDescripcion = $xQr + self::ETIQUETA_ANCHO_QR;

            $xCodigoInventario = $xDescripcion
                + self::ETIQUETA_ANCHO_DESCRIPCION;

            $xCodigo = $xCodigoInventario
                + self::ETIQUETA_ANCHO_CODIGO_INVENTARIO;

            $qrPath = $this->crearArchivoQr(
                $this->textoQrEtiqueta(
                    $transferencia,
                    $expediente
                )
            );

            $pdf->Rect($x, $y, $ancho, $alto);

            $pdf->Line(
                $xLogo + self::ETIQUETA_ANCHO_LOGO,
                $y,
                $xLogo + self::ETIQUETA_ANCHO_LOGO,
                $y + $alto
            );

            $pdf->Line(
                $xQr + self::ETIQUETA_ANCHO_QR,
                $y,
                $xQr + self::ETIQUETA_ANCHO_QR,
                $y + $alto
            );

            $pdf->Line(
                $xDescripcion + self::ETIQUETA_ANCHO_DESCRIPCION,
                $y,
                $xDescripcion + self::ETIQUETA_ANCHO_DESCRIPCION,
                $y + $alto
            );

            $pdf->Line(
                $xCodigoInventario
                    + self::ETIQUETA_ANCHO_CODIGO_INVENTARIO,
                $y,
                $xCodigoInventario
                    + self::ETIQUETA_ANCHO_CODIGO_INVENTARIO,
                $y + $alto
            );

            $this->agregarLogoCentrado(
                $pdf,
                $logo,
                $xLogo,
                $y,
                self::ETIQUETA_ANCHO_LOGO,
                $alto,
                self::ETIQUETA_TAMANO_LOGO
            );

            $pdf->Image(
                $qrPath,
                $xQr + (
                    (
                        self::ETIQUETA_ANCHO_QR -
                        self::ETIQUETA_TAMANO_QR
                    ) / 2
                ),
                $y + (
                    (
                        $alto -
                        self::ETIQUETA_TAMANO_QR
                    ) / 2
                ),
                self::ETIQUETA_TAMANO_QR,
                self::ETIQUETA_TAMANO_QR,
                'PNG'
            );

            $this->escribirTextoCentrado(
                $pdf,
                $this->limitarTextoEtiqueta(
                    $origen->descripcion_lomo,
                    self::ETIQUETA_MAX_CARACTERES_DESCRIPCION
                ),
                $xDescripcion,
                $y,
                self::ETIQUETA_ANCHO_DESCRIPCION,
                $alto,
                self::ETIQUETA_TAMANO_FUENTE,
                self::ETIQUETA_TAMANO_FUENTE
            );

            $this->escribirTextoCentrado(
                $pdf,
                $codigoInventario,
                $xCodigoInventario,
                $y,
                self::ETIQUETA_ANCHO_CODIGO_INVENTARIO,
                $alto,
                self::ETIQUETA_TAMANO_CODIGO_INVENTARIO,
                self::ETIQUETA_TAMANO_CODIGO_INVENTARIO
            );

            $this->escribirTextoCentrado(
                $pdf,
                $origen->codigo_referencia ?? '-',
                $xCodigo,
                $y,
                self::ETIQUETA_ANCHO_CODIGO,
                $alto,
                self::ETIQUETA_TAMANO_FUENTE,
                self::ETIQUETA_TAMANO_FUENTE
            );

            $this->eliminarArchivoTemporal($qrPath);

            $y += $alto + self::ETIQUETA_SEPARACION;
        }

        $nombreArchivo = $this->sanitizarNombreArchivo(
            'etiquetas_' . $transferencia->correlativo
        ) . '.pdf';

        return $this->respuestaPdf($pdf, $nombreArchivo);
    }

    private function escribirTextoCentrado(
        TransferenciaPdf $pdf,
        string $texto,
        float $x,
        float $y,
        float $ancho,
        float $alto,
        int $tamanoMaximo,
        int $tamanoMinimo
    ): void {
        $texto = preg_replace(
            '/\s+/',
            ' ',
            trim($texto)
        ) ?: '-';
        $tamano = $tamanoMaximo;
        $lineas = 1;
        while ($tamano >= $tamanoMinimo) {
            $pdf->SetFont(TransferenciaPdf::FUENTE_TEXTO, 'B', $tamano);
            $lineas = $pdf->lineCount($ancho - 4, $texto);
            if ( ($lineas * 4) <= ($alto - 4) )
                break;
            $tamano--;
        }
        $altoLinea = max(3, min(7, ($alto - 4) / max(1, $lineas)));
        $altoTexto = $lineas * $altoLinea;
        $posicionY = $y + (
            ($alto - $altoTexto) / 2
        );
        $pdf->SetXY($x + 2, $posicionY);
        $pdf->SetFont(TransferenciaPdf::FUENTE_TEXTO, 'B', $tamano);
        $pdf->MultiCell($ancho - 4,$altoLinea,$pdf->encode($texto),0,'C');
    }

    private function agregarLogoCentrado(
        TransferenciaPdf $pdf,
        string $logo,
        float $x,
        float $y,
        float $anchoColumna,
        float $altoFila,
        float $anchoLogo
    ): void {
        if (!is_file($logo))
            return;
        $dimensiones = getimagesize($logo);
        if (! $dimensiones || $dimensiones[0] <= 0)
            return;
        $altoLogo = $anchoLogo * ($dimensiones[1] / $dimensiones[0]);
        $pdf->Image(
            $logo,
            $x + (
                ($anchoColumna - $anchoLogo) / 2
            ),
            $y + (
                ($altoFila - $altoLogo) / 2
            ),
            $anchoLogo,
            0,
            'PNG'
        );
    }

    private function limitarTextoEtiqueta(
        ?string $texto,
        int $maximo
    ): string {
        $texto = preg_replace(
            '/\s+/u',
            ' ',
            trim((string) $texto)
        ) ?: '-';

        if (mb_strlen($texto, 'UTF-8') <= $maximo) {
            return $texto;
        }

        $marcador = '[...]';
        $longitud = $maximo - mb_strlen($marcador, 'UTF-8');

        return rtrim(
            mb_substr($texto, 0, $longitud, 'UTF-8')
        ) . $marcador;
    }

    private const MARGEN_LEFT_RIGHT_SOLICITUD = 10;
    private const MARGEN_TOP_SOLICITUD = 42;
    private const MARGEN_LEFT_TABLE = 5;
    private const TAMANIO_FUENTE_TABLA = 6;
    private const ESPACIO_ANTES_TABLA = 8;
    private const ALTURA_LINEA_TABLA = 2.2;
    private const MAX_CARACTERES_DETALLE = 1000;
    private const MAX_LINEAS_DETALLE = 30;
    private const MARCADOR_RECORTE = ' [...]';
    private const FIRMA_ANCHO = 49;
    private const FIRMA_ALTO = 40;
    private const FIRMA_ALTO_LINEA = 3;
    private const SOLICITUD_ANCHOS_COLUMNAS = [
        9,
        9,
        18,
        22,
        30,
        45,
        9,
        9,
        14,
        14,
        24,
    ];
    private const SOLICITUD_CABECERAS = [
        "COD\nREF",
        'CAJA',
        'PROCEDENCIA',
        "SERIE\nDOCUMENTAL",
        "DESCRIPCIÓN\nDOCUMENTAL",
        'DETALLE',
        'TOMO',
        'FOJAS',
        "FECHAS\nEXTREMAS",
        'SOPORTE',
        'OBSERVACIONES',
    ];
    private const SOLICITUD_ALINEACION = [
        'C',
        'C',
        'C',
        'C',
        'J',
        'J',
        'C',
        'C',
        'C',
        'C',
        'J',
    ];

    public function descargarSolicitud(Transferencia $transferencia)
    {
        $transferencia->loadMissing([
            'fondo',
            'subfondo',
            'seccion',
            'usuarioSolicitante',
            'expedientes.serieDocumental',
            'expedientes.soporte',
        ]);
        $pdf = new TransferenciaPdf('P', 'mm', 'Letter');
        $pdf->AliasNbPages();
        $pdf->setBannerPath(resource_path('images/banner.png'));
        $pdf->setFooterPath(resource_path('images/footer.png'));
        $pdf->SetMargins(self::MARGEN_LEFT_RIGHT_SOLICITUD, self::MARGEN_TOP_SOLICITUD, self::MARGEN_LEFT_RIGHT_SOLICITUD);
        $pdf->SetAutoPageBreak(
            true,
            $pdf->alturaFooter() + 16
        );
        $pdf->AddPage();

        $qrPath = $this->crearArchivoQr($this->textoQrSolicitud($transferencia));

        $pdf->SetFont(TransferenciaPdf::FUENTE_TEXTO, 'B', 12);
        $pdf->SetXY(10, self::MARGEN_TOP_SOLICITUD - 8);
        $pdf->MultiCell(0,7,$pdf->encode('FORMULARIO DE TRANSFERENCIA Y RELACIÓN DE ENTREGA DOCUMENTAL'),0,'C');
        $pdf->SetXY(10, self::MARGEN_TOP_SOLICITUD - 1);
        $pdf->Cell(0,7,$pdf->encode($transferencia->correlativo),0,1,'C');
        $pdf->Image($qrPath,175,self::MARGEN_TOP_SOLICITUD + 8,30,0,'PNG');
        $this->eliminarArchivoTemporal($qrPath);
        $this->agregarDatosGenerales($pdf,$transferencia);
        $this->agregarTablaExpedientes($pdf,$transferencia);
        $this->agregarFirmas($pdf);
        return $this->respuestaPdf(
            $pdf,
            $this->sanitizarNombreArchivo(
                'solicitud_' . $transferencia->correlativo
            ) . '.pdf'
        );
    }

    public function descargarFormularioComplementario(Transferencia $transferencia)
    {
        $transferencia->loadMissing([
            'fondo',
            'subfondo',
            'seccion',
            'usuarioSolicitante',
            'expedientes.correccionArchivo.serieDocumental',
            'expedientes.correccionArchivo.soporte',
        ]);
        $expedientes = $transferencia->expedientes
            ->where('activo', true)
            ->filter(
                fn (TransferenciaExpediente $expediente): bool =>
                    $expediente->correccionArchivo !== null
            )
            ->sortBy('id')
            ->values();

        abort_if(
            $expedientes->isEmpty(),
            404,
            'La transferencia no tiene expedientes corregidos por Archivo.'
        );

        $pdf = new TransferenciaPdf('P', 'mm', 'Letter');
        $pdf->AliasNbPages();
        $pdf->setBannerPath(resource_path('images/banner.png'));
        $pdf->setFooterPath(resource_path('images/footer.png'));
        $pdf->SetMargins(
            self::MARGEN_LEFT_RIGHT_SOLICITUD,
            self::MARGEN_TOP_SOLICITUD,
            self::MARGEN_LEFT_RIGHT_SOLICITUD
        );
        $pdf->SetAutoPageBreak(
            true,
            $pdf->alturaFooter() + 16
        );
        $pdf->AddPage();

        $qrPath = $this->crearArchivoQr(
            $this->textoQrSolicitud($transferencia)
        );

        $pdf->SetFont(TransferenciaPdf::FUENTE_TEXTO, 'B', 12);
        $pdf->SetXY(10, self::MARGEN_TOP_SOLICITUD - 8);
        $pdf->MultiCell(
            0,
            7,
            $pdf->encode(
                'FORMULARIO DE TRANSFERENCIA Y RELACIÓN DE ENTREGA DOCUMENTAL'
            ),
            0,
            'C'
        );

        $pdf->SetXY(10, self::MARGEN_TOP_SOLICITUD - 1);
        $pdf->Cell(
            0,
            7,
            $pdf->encode($transferencia->correlativo),
            0,
            1,
            'C'
        );

        $pdf->Image(
            $qrPath,
            175,
            self::MARGEN_TOP_SOLICITUD + 8,
            30,
            0,
            'PNG'
        );

        $this->eliminarArchivoTemporal($qrPath);
        $this->agregarDatosGeneralesComplementario($pdf, $transferencia);
        $this->agregarTablaExpedientesComplementaria($pdf, $expedientes);
        $this->agregarFirmaResponsableArchivo($pdf);

        return $this->respuestaPdf(
            $pdf,
            $this->sanitizarNombreArchivo(
                'formulario_complementario_' . $transferencia->correlativo
            ) . '.pdf'
        );
    }

    private function agregarDatosGenerales(TransferenciaPdf $pdf, Transferencia $transferencia): void
    {
        $datos = array(
            array('FONDO', $transferencia->fondo?->valor ?? '-'),
            array('SUBFONDO', $transferencia->subfondo?->valor ?? '-'),
            array('SECCIÓN', $transferencia->seccion?->valor ?? '-'),
            array('USUARIO REMITENTE', $this->usuarioRemitente($transferencia)),
            array('FECHA INICIO', $transferencia->fecha_solicitud?->format('d/m/Y H:i') ?? '-'),
            array('TOTAL EXPEDIENTES', (string) $transferencia->total_expedientes),
        );
        $pdf->SetXY(self::MARGEN_LEFT_RIGHT_SOLICITUD, self::MARGEN_TOP_SOLICITUD + 10);
        foreach ($datos as $fila) {
            $pdf->SetFont(TransferenciaPdf::FUENTE_TEXTO, 'B', 10);
            $pdf->Cell(45,5,$pdf->encode($fila[0] . ':'),0,0,'R');
            $pdf->SetFont(TransferenciaPdf::FUENTE_TEXTO, '', 10);
            $pdf->Cell(0,5,$pdf->encode($fila[1]),0,1,'L');
        }
        $pdf->Ln(self::ESPACIO_ANTES_TABLA);
    }

    private function agregarDatosGeneralesComplementario(TransferenciaPdf $pdf, Transferencia $transferencia): void
    {
        $datos = [
            ['FONDO', $transferencia->fondo?->valor ?? '-'],
            ['SUBFONDO', $transferencia->subfondo?->valor ?? '-'],
            ['SECCIÓN', $transferencia->seccion?->valor ?? '-'],
            ['USUARIO ARCHIVO', $this->usuarioArchivo()],
            ['FECHA INICIO', $transferencia->fecha_solicitud?->format('d/m/Y H:i') ?? '-'],
            ['TOTAL EXPEDIENTES', (string) $transferencia->total_expedientes],
        ];

        $pdf->SetXY(
            self::MARGEN_LEFT_RIGHT_SOLICITUD,
            self::MARGEN_TOP_SOLICITUD + 10
        );

        foreach ($datos as $fila) {
            $pdf->SetFont(TransferenciaPdf::FUENTE_TEXTO, 'B', 10);
            $pdf->Cell(
                45,
                5,
                $pdf->encode($fila[0] . ':'),
                0,
                0,
                'R'
            );

            $pdf->SetFont(TransferenciaPdf::FUENTE_TEXTO, '', 10);
            $pdf->Cell(
                0,
                5,
                $pdf->encode($fila[1]),
                0,
                1,
                'L'
            );
        }

        $pdf->Ln(self::ESPACIO_ANTES_TABLA);
    }

    private function agregarTablaExpedientes(TransferenciaPdf $pdf, Transferencia $transferencia): void
    {
        $anchos = self::SOLICITUD_ANCHOS_COLUMNAS;
        $cabeceras = self::SOLICITUD_CABECERAS;
        $agregarEncabezado = function () use (
            $pdf,
            $anchos,
            $cabeceras
        ): void {
            $pdf->SetXY(self::MARGEN_LEFT_TABLE, $pdf->GetY() - 5);
            $pdf->headerRow($anchos,$cabeceras);
        };
        $agregarEncabezado();
        $huboTextoRecortado = false;
        foreach ($transferencia->expedientes->where('activo', true)->sortBy('id')->values() as $indice => $expediente) {
            $detallePdf = $this->prepararDetallePdf($pdf, $expediente->detalle, $anchos[5]);
            if ($detallePdf['recortado'])
                $huboTextoRecortado = true;
            $datosFila = [
                $expediente->codigo_referencia ?? '-',
                $expediente->numero_caja ?? '-',
                $expediente->procedencia ?? '-',
                $expediente->serieDocumental?->valor ?? '-',
                $expediente->descripcion_lomo ?? '-',
                $detallePdf['texto'],
                $expediente->tomo_volumen ?? '-',
                $expediente->fojas ?? '-',
                $expediente->fechas_extremas ?? '-',
                $expediente->soporte?->valor ?? '-',
                $expediente->observaciones ?? '-',
            ];
            $altoFila = $pdf->rowHeight($anchos,$datosFila,self::ALTURA_LINEA_TABLA,self::TAMANIO_FUENTE_TABLA);
            if ($pdf->GetY() + $altoFila > $pdf->limiteContenidoInferior()) {
                $pdf->AddPage();
                $agregarEncabezado();
            }
            $pdf->SetX(self::MARGEN_LEFT_TABLE);
            $pdf->row($anchos,$datosFila,self::ALTURA_LINEA_TABLA,self::TAMANIO_FUENTE_TABLA,self::SOLICITUD_ALINEACION);
        }
        if ($huboTextoRecortado) {
            $leyenda = ' [...] DEBIDO A QUE EL TEXTO ES DEMASIADO EXTENSO, '
                . 'SE RECORTÓ. PARA VERIFICAR EL TEXTO COMPLETO, '
                . 'CONSULTE LA DESCRIPCIÓN DEL EXPEDIENTE DIRECTAMENTE '
                . 'EN EL SISTEMA.';
            $altoLeyenda = 12;
            if ($pdf->GetY() + $altoLeyenda > $pdf->limiteContenidoInferior())
                $pdf->AddPage();
            $pdf->SetX(self::MARGEN_LEFT_TABLE);
            $pdf->SetFont(TransferenciaPdf::FUENTE_TEXTO, 'I', self::TAMANIO_FUENTE_TABLA);
            $pdf->MultiCell(array_sum($anchos),4,$pdf->encode($leyenda),0,'L');
        }
    }

    private function agregarTablaExpedientesComplementaria(
        TransferenciaPdf $pdf,
        Collection $expedientes
    ): void {
        $anchos = self::SOLICITUD_ANCHOS_COLUMNAS;
        $cabeceras = self::SOLICITUD_CABECERAS;

        $agregarEncabezado = function () use (
            $pdf,
            $anchos,
            $cabeceras
        ): void {
            $pdf->SetXY(
                self::MARGEN_LEFT_TABLE,
                $pdf->GetY() - 5
            );

            $pdf->headerRow($anchos, $cabeceras);
        };

        $agregarEncabezado();

        $huboTextoRecortado = false;

        foreach ($expedientes as $expediente) {
            $correccion = $expediente->correccionArchivo;

            $detallePdf = $this->prepararDetallePdf(
                $pdf,
                $correccion->detalle,
                $anchos[5]
            );

            if ($detallePdf['recortado']) {
                $huboTextoRecortado = true;
            }

            $datosFila = [
                $correccion->codigo_referencia ?? '-',
                $correccion->numero_caja ?? '-',
                $correccion->procedencia ?? '-',
                $correccion->serieDocumental?->valor ?? '-',
                $correccion->descripcion_lomo ?? '-',
                $detallePdf['texto'],
                $correccion->tomo_volumen ?? '-',
                $correccion->fojas ?? '-',
                $correccion->fechas_extremas ?? '-',
                $correccion->soporte?->valor ?? '-',
                $correccion->observaciones ?? '-',
            ];

            $altoFila = $pdf->rowHeight(
                $anchos,
                $datosFila,
                self::ALTURA_LINEA_TABLA,
                self::TAMANIO_FUENTE_TABLA
            );

            if (
                $pdf->GetY() + $altoFila
                > $pdf->limiteContenidoInferior()
            ) {
                $pdf->AddPage();
                $agregarEncabezado();
            }

            $pdf->SetX(self::MARGEN_LEFT_TABLE);
            $pdf->row(
                $anchos,
                $datosFila,
                self::ALTURA_LINEA_TABLA,
                self::TAMANIO_FUENTE_TABLA,
                self::SOLICITUD_ALINEACION
            );
        }

        if ($huboTextoRecortado) {
            $leyenda = ' [...] DEBIDO A QUE EL TEXTO ES DEMASIADO EXTENSO, '
                . 'SE RECORTÓ. PARA VERIFICAR EL TEXTO COMPLETO, '
                . 'CONSULTE LA DESCRIPCIÓN DEL EXPEDIENTE DIRECTAMENTE '
                . 'EN EL SISTEMA.';

            if (
                $pdf->GetY() + 12
                > $pdf->limiteContenidoInferior()
            ) {
                $pdf->AddPage();
            }

            $pdf->SetX(self::MARGEN_LEFT_TABLE);
            $pdf->SetFont(
                TransferenciaPdf::FUENTE_TEXTO,
                'I',
                self::TAMANIO_FUENTE_TABLA
            );
            $pdf->MultiCell(
                array_sum($anchos),
                4,
                $pdf->encode($leyenda),
                0,
                'L'
            );
        }
    }

    private function agregarFirmas(TransferenciaPdf $pdf): void
    {
        $ancho = self::FIRMA_ANCHO;
        $alto = self::FIRMA_ALTO;
        $altoLinea = self::FIRMA_ALTO_LINEA;
        if ($pdf->GetY() + $alto > $pdf->limiteContenidoInferior())
            $pdf->AddPage();
        $pdf->Ln(8);
        $xInicial = self::MARGEN_LEFT_RIGHT_SOLICITUD;
        $y = $pdf->GetY();
        $firmas = [
            "FIRMA Y SELLO\nFUNCIONARIO REMITENTE",
            "FIRMA Y SELLO\nAUTORIZADO POR EL INMEDIATO SUPERIOR DEL REMITENTE",
            "FIRMA Y SELLO\nAUTORIZADO POR EL JEFE(A) DE ADMINISTRACIÓN",
            "FIRMA Y SELLO\nRESPONSABLE DE ARCHIVO",
        ];
        foreach ($firmas as $indice => $texto) {
            $x = $xInicial + ($indice * $ancho);
            $pdf->Rect($x,$y,$ancho,$alto);
            $pdf->SetFont(TransferenciaPdf::FUENTE_TEXTO, 'B', 7);
            $lineas = $pdf->lineCount($ancho - 4,$texto);
            $altoTexto = $lineas * $altoLinea;
            $pdf->SetXY($x + 2, $y + $alto - $altoTexto - 3);
            $pdf->MultiCell($ancho - 4,$altoLinea,$pdf->encode($texto),0,'C');
        }
    }

    private function agregarFirmaResponsableArchivo(
        TransferenciaPdf $pdf
    ): void {
        $ancho = self::FIRMA_ANCHO;
        $alto = self::FIRMA_ALTO;
        $altoLinea = self::FIRMA_ALTO_LINEA;

        if (
            $pdf->GetY() + $alto
            > $pdf->limiteContenidoInferior()
        ) {
            $pdf->AddPage();
        }

        $pdf->Ln(8);

        $x = (
            $pdf->GetPageWidth() - $ancho
        ) / 2;

        $y = $pdf->GetY();

        $texto = "FIRMA Y SELLO\nRESPONSABLE DE ARCHIVO";

        $pdf->Rect($x, $y, $ancho, $alto);

        $pdf->SetFont(
            TransferenciaPdf::FUENTE_TEXTO,
            'B',
            10
        );

        $lineas = $pdf->lineCount(
            $ancho - 4,
            $texto
        );

        $altoTexto = $lineas * $altoLinea;

        $pdf->SetXY(
            $x + 2,
            $y + $alto - $altoTexto - 3
        );

        $pdf->MultiCell(
            $ancho - 4,
            $altoLinea,
            $pdf->encode($texto),
            0,
            'C'
        );
    }

    private function crearArchivoQr(string $contenido): string
    {
        $ruta = storage_path('app/qr_' . Str::uuid() . '.png');
        $options = new QROptions([
            'outputType' => QROutputInterface::GDIMAGE_PNG,
            'scale' => 8,
            'addQuietzone' => true,
        ]);
        (new QRCode($options))->render($contenido,$ruta);
        return $ruta;
    }

    private function eliminarArchivoTemporal(string $ruta): void
    {
        if (is_file($ruta))
            unlink($ruta);
    }

    private function respuestaPdf(TransferenciaPdf $pdf,string $nombre) {
        return response()->streamDownload(
            function () use ($pdf): void {
                echo $pdf->Output('S');
            },
            $nombre,
            [
                'Content-Type' => 'application/pdf',
            ]
        );
    }

    private function textoQrEtiqueta(
        Transferencia $transferencia,
        TransferenciaExpediente $expediente
    ): string {
        $origen = $expediente->correccionArchivo ?? $expediente;
        $descripcion = preg_replace(
            '/[\r\n|]+/u',
            ' ',
            trim((string) ($origen->descripcion_lomo ?? '-'))
        ) ?: '-';
        return implode('|', [
            $transferencia->correlativo,
            $transferencia->fondo?->valor ?? '-',
            $transferencia->subfondo?->valor ?? '-',
            $transferencia->seccion?->valor ?? '-',
            $this->usuarioRemitente($transferencia),
            $transferencia->fecha_solicitud?->format('d/m/Y') ?? '-',
            $transferencia->total_expedientes,
            $expediente->inventario?->codigo_inventario ?? '-',
            $origen->codigo_referencia ?? '-',
            $descripcion,
        ]);
    }

    private function textoQrSolicitud(Transferencia $transferencia): string
    {
        return implode('|', [
            $transferencia->correlativo,
            $transferencia->fondo?->valor ?? '-',
            $transferencia->subfondo?->valor ?? '-',
            $transferencia->seccion?->valor ?? '-',
            $this->usuarioRemitente($transferencia),
            $transferencia->fecha_solicitud?->format('d/m/Y') ?? '-',
            $transferencia->total_expedientes,
        ]);
    }

    private function prepararDetallePdf(
        TransferenciaPdf $pdf,
        ?string $detalle,
        float $ancho
    ): array {
        $textoOriginal = $pdf->normalizeCell($detalle);
        $texto = $textoOriginal;
        $marcador = self::MARCADOR_RECORTE;
        $recortado = false;
        $pdf->SetFont(TransferenciaPdf::FUENTE_TEXTO, '', self::TAMANIO_FUENTE_TABLA);
        $maximo = self::MAX_CARACTERES_DETALLE;
        if (mb_strlen($texto, 'UTF-8') > $maximo) {
            $texto = mb_substr($texto, 0, $maximo - mb_strlen($marcador, 'UTF-8'),'UTF-8');
            $texto = rtrim($texto) . $marcador;
            $recortado = true;
        }
        while ($pdf->lineCount($ancho, $texto) > self::MAX_LINEAS_DETALLE) {
            $recortado = true;
            $longitud = mb_strlen($texto, 'UTF-8');
            $longitudNueva = max(1, $longitud - 50);
            $texto = mb_substr($texto, 0, $longitudNueva, 'UTF-8');
            $texto = rtrim(preg_replace('/\s+\S*$/u','',$texto) ?: $texto);
            $texto .= $marcador;
        }
        return [
            'texto' => $texto,
            'recortado' => $recortado,
        ];
    }

    private function usuarioRemitente(Transferencia $transferencia): string
    {
        return trim(
            ($transferencia->usuarioSolicitante?->nombres ?? '') . ' ' .
            ($transferencia->usuarioSolicitante?->apellidos ?? '')
        ) ?: '-';
    }

    private function sanitizarNombreArchivo(string $nombre,string $reemplazo = '_'): string
    {
        $nombre = trim($nombre);
        $caracteresNoPermitidos = [
            '<',
            '>',
            ':',
            '"',
            '/',
            '\\',
            '|',
            '?',
            '*',
        ];
        $nombre = str_replace($caracteresNoPermitidos,$reemplazo,$nombre);
        $nombre = preg_replace('/[\x00-\x1F]/u',$reemplazo,$nombre) ?: '';
        $nombre = preg_replace('/\s+/',$reemplazo,$nombre) ?: '';
        return trim($nombre, $reemplazo . '. ') ?: 'archivo';
    }

    private function usuarioArchivo(): string
    {
        $usuario = Auth::user();
        return trim(
            ($usuario?->nombres ?? '') . ' ' .
            ($usuario?->apellidos ?? '')
        ) ?: ($usuario?->usuario ?? '-');
    }

    public function descargarEtiquetaInventario(InventarioExpediente $expediente) {
        $expediente->loadMissing([
            'oficina',
            'direccion',
            'area',
            'serieDocumental',
            'soporte',
        ]);
        $pdf = new TransferenciaPdf('P', 'mm', 'Letter');
        $pdf->setMostrarEncabezado(false);
        $pdf->setMostrarPie(false);
        $pdf->SetMargins(self::MARGEN, self::MARGEN, self::MARGEN);
        $pdf->SetAutoPageBreak(true, self::MARGEN);
        $pdf->AddPage();

        $logo = resource_path('images/logo.png');
        $y = $pdf->GetY();
        $x = self::MARGEN;
        $alto = self::ETIQUETA_ALTO;
        $ancho = self::ETIQUETA_ANCHO_TOTAL;

        $xLogo = $x;
        $xQr = $xLogo + self::ETIQUETA_ANCHO_LOGO;
        $xDescripcion = $xQr + self::ETIQUETA_ANCHO_QR;

        $xCodigoInventario = $xDescripcion + self::ETIQUETA_ANCHO_DESCRIPCION;
        $xCodigo = $xCodigoInventario + self::ETIQUETA_ANCHO_CODIGO_INVENTARIO;
        $qrPath = $this->crearArchivoQr($this->textoQrEtiquetaInventario($expediente));

        $pdf->Rect($x, $y, $ancho, $alto);
        $pdf->Line($xLogo + self::ETIQUETA_ANCHO_LOGO,$y,$xLogo + self::ETIQUETA_ANCHO_LOGO,$y + $alto);
        $pdf->Line($xQr + self::ETIQUETA_ANCHO_QR,$y,$xQr + self::ETIQUETA_ANCHO_QR,$y + $alto);
        $pdf->Line($xDescripcion + self::ETIQUETA_ANCHO_DESCRIPCION,$y,$xDescripcion + self::ETIQUETA_ANCHO_DESCRIPCION,$y + $alto);
        $pdf->Line($xCodigoInventario+ self::ETIQUETA_ANCHO_CODIGO_INVENTARIO,$y,$xCodigoInventario+ self::ETIQUETA_ANCHO_CODIGO_INVENTARIO,$y + $alto);

        $this->agregarLogoCentrado(
            $pdf,
            $logo,
            $xLogo,
            $y,
            self::ETIQUETA_ANCHO_LOGO,
            $alto,
            self::ETIQUETA_TAMANO_LOGO
        );

        $pdf->Image(
            $qrPath,
            $xQr + (
                (
                    self::ETIQUETA_ANCHO_QR
                    - self::ETIQUETA_TAMANO_QR
                ) / 2
            ),
            $y + (
                (
                    $alto - self::ETIQUETA_TAMANO_QR
                ) / 2
            ),
            self::ETIQUETA_TAMANO_QR,
            self::ETIQUETA_TAMANO_QR,
            'PNG'
        );

        $this->escribirTextoCentrado(
            $pdf,
            $this->limitarTextoEtiqueta(
                $expediente->descripcion_lomo,
                self::ETIQUETA_MAX_CARACTERES_DESCRIPCION
            ),
            $xDescripcion,
            $y,
            self::ETIQUETA_ANCHO_DESCRIPCION,
            $alto,
            self::ETIQUETA_TAMANO_FUENTE,
            self::ETIQUETA_TAMANO_FUENTE
        );

        $this->escribirTextoCentrado(
            $pdf,
            $expediente->codigo_inventario,
            $xCodigoInventario,
            $y,
            self::ETIQUETA_ANCHO_CODIGO_INVENTARIO,
            $alto,
            self::ETIQUETA_TAMANO_CODIGO_INVENTARIO,
            self::ETIQUETA_TAMANO_CODIGO_INVENTARIO
        );

        $this->eliminarArchivoTemporal($qrPath);
        return $this->respuestaPdf(
            $pdf,
            $this->sanitizarNombreArchivo(
                'etiqueta_' . $expediente->codigo_inventario
            ) . '.pdf'
        );
    }

    private function textoQrEtiquetaInventario(
        InventarioExpediente $expediente
    ): string {
        $descripcion = preg_replace(
            '/[\r\n|]+/u',
            ' ',
            trim((string) (
                $expediente->descripcion_lomo ?? '-'
            ))
        ) ?: '-';
        return implode('|', [
            $expediente->codigo_inventario,
            $expediente->oficina?->valor ?? '-',
            $expediente->direccion?->valor ?? '-',
            $expediente->area?->valor ?? '-',
            $expediente->codigo_referencia ?? '-',
            $expediente->procedencia ?? '-',
            $expediente->serieDocumental?->valor ?? '-',
            $descripcion,
        ]);
    }

}
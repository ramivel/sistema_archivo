<?php

namespace Database\Seeders;

use App\Models\Parametro;
use Illuminate\Database\Seeder;

class ParametrosSeeder extends Seeder
{
    public function run(): void
    {
        // Usuario que realiza la carga inicial
        $usuarioCreacionId = 1;

        $parametros = [
            // =====================================================
            // 1. FONDO
            // =====================================================
            [
                'grupo' => 'FONDO',
                'valor' => 'AUTORIDAD JURISDICCIONAL ADMINISTRATIVA MINERA',
                'sigla' => 'AJAM',
                'ubicacion' => 'LA PAZ, CALLE ANDRÉS MUÑOZ Nº 2564 (SOPOCACHI)',
                'orden' => 1,
            ],
            // =====================================================
            // 2. SUBFONDO
            // =====================================================
            [
                'grupo' => 'SUBFONDO',
                'valor' => 'DIRECCIÓN EJECUTIVA NACIONAL',
                'sigla' => 'DEN',
                'padre_id' => 1,
                'orden' => 1,
            ],
            [
                'grupo' => 'SUBFONDO',
                'valor' => 'DIRECCIÓN JURÍDICA',
                'sigla' => 'DJU',
                'padre_id' => 1,
                'orden' => 2,
            ],
            [
                'grupo' => 'SUBFONDO',
                'valor' => 'DIRECCIÓN ADMINISTRATIVA FINANCIERA',
                'sigla' => 'DAF',
                'padre_id' => 1,
                'orden' => 2,
            ],
            // =====================================================
            // 3. SECCIÓN
            // =====================================================
            [
                'grupo' => 'SECCION',
                'valor' => 'UNIDAD DE TECNOLOGÍAS DE INFORMACIÓN Y COMUNICACIÓN',
                'sigla' => 'UTIC',
                'padre_id' => 2,
                'orden' => 1,
            ],
            [
                'grupo' => 'SECCION',
                'valor' => 'UNIDAD DE AUDITORIA INTERNA',
                'sigla' => 'UAI',
                'padre_id' => 2,
                'orden' => 2,
            ],
            [
                'grupo' => 'SECCION',
                'valor' => 'ENLACE ADMINISTRATIVO',
                'sigla' => 'EA',
                'padre_id' => 2,
                'orden' => 3,
            ],
            [
                'grupo' => 'SECCION',
                'valor' => 'ASESORÍA GENERAL',
                'sigla' => 'AG',
                'padre_id' => 2,
                'orden' => 4,
            ],
            [
                'grupo' => 'SECCION',
                'valor' => 'REGIONALES EX AGJAM',
                'sigla' => 'ARJAM',
                'padre_id' => 2,
                'orden' => 5,
            ],
            [
                'grupo' => 'SECCION',
                'valor' => 'UNIDAD DE ASUNTOS TÉCNICOS - EX AGJAM',
                'sigla' => 'UAT',
                'padre_id' => 2,
                'orden' => 6,
            ],
            [
                'grupo' => 'SECCION',
                'valor' => 'UNIDAD DE COMUNICACIÓN',
                'sigla' => 'UCOM',
                'padre_id' => 2,
                'orden' => 7,
            ],
            [
                'grupo' => 'SECCION',
                'valor' => 'UNIDAD DE TRANSPARENCIA Y LUCHA CONTRA LA CORRUPCIÓN',
                'sigla' => 'UTLCC',
                'padre_id' => 2,
                'orden' => 8,
            ],
            [
                'grupo' => 'SECCION',
                'valor' => 'UNIDAD DE CONTRATOS MINEROS - EX AGJAM - DIRECCIÓN DEPARTAMENTAL LA PAZ',
                'sigla' => 'UCM',
                'padre_id' => 2,
                'orden' => 9,
            ],
            // =====================================================
            // 4. LUGAR DE EXPEDICIÓN
            // =====================================================
            [
                'grupo' => 'LUGAR_EXPEDICION',
                'valor' => 'QR',
                'descripcion' => 'CODIGO QR',
                'orden' => 1,
            ],
            [
                'grupo' => 'LUGAR_EXPEDICION',
                'valor' => 'LP',
                'descripcion' => 'LA PAZ',
                'orden' => 2,
            ],
            [
                'grupo' => 'LUGAR_EXPEDICION',
                'valor' => 'SC',
                'descripcion' => 'SANTA CRUZ',
                'orden' => 3,
            ],
            [
                'grupo' => 'LUGAR_EXPEDICION',
                'valor' => 'CB',
                'descripcion' => 'COCHABAMBA',
                'orden' => 4,
            ],
            [
                'grupo' => 'LUGAR_EXPEDICION',
                'valor' => 'OR',
                'descripcion' => 'ORURO',
                'orden' => 5,
            ],
            [
                'grupo' => 'LUGAR_EXPEDICION',
                'valor' => 'PT',
                'descripcion' => 'POTOSI',
                'orden' => 6,
            ],
            [
                'grupo' => 'LUGAR_EXPEDICION',
                'valor' => 'CH',
                'descripcion' => 'CHUQUISACA',
                'orden' => 7,
            ],
            [
                'grupo' => 'LUGAR_EXPEDICION',
                'valor' => 'TJ',
                'descripcion' => 'TARIJA',
                'orden' => 8,
            ],
            [
                'grupo' => 'LUGAR_EXPEDICION',
                'valor' => 'BE',
                'descripcion' => 'BENI',
                'orden' => 9,
            ],
            [
                'grupo' => 'LUGAR_EXPEDICION',
                'valor' => 'PA',
                'descripcion' => 'PANDO',
                'orden' => 10,
            ],
            // =====================================================
            // 5. PERFILES
            // =====================================================
            [
                'grupo' => 'PERFIL',
                'valor' => 'ADMINISTRADOR',
                'descripcion' => 'PERFIL ENCARGADO DE ADMINISTRAR USUARIOS, PERFILES Y CONFIGURACIONES DEL SISTEMA.',
                'orden' => 1,
            ],
            [
                'grupo' => 'PERFIL',
                'valor' => 'ENCARGADO ARCHIVO',
                'descripcion' => 'PERFIL RESPONSABLE DE GESTIONAR Y ADMINISTRAR LA DOCUMENTACIÓN DEL ARCHIVO.',
                'orden' => 2,
            ],
            [
                'grupo' => 'PERFIL',
                'valor' => 'TRANSFERENCIAS',
                'descripcion' => 'PERFIL RESPONSABLE DE GESTIONAR Y REGISTRAR LAS TRANSFERENCIAS DOCUMENTALES.',
                'orden' => 3,
            ],
            [
                'grupo' => 'PERFIL',
                'valor' => 'CONSULTAS',
                'descripcion' => 'PERFIL CON ACCESO DE SOLO CONSULTA A LA INFORMACIÓN Y DOCUMENTACIÓN DEL SISTEMA.',
                'orden' => 4,
            ],
            // =====================================================
            // 7. SERIES DOCUMENTALES
            // =====================================================
            [
                'grupo' => 'SERIE_DOCUMENTAL',
                'valor' => 'CORRESPONDENCIA',
                'descripcion' => 'DOCUMENTOS DE COMUNICACIÓN OFICIAL RECIBIDA O EMITIDA POR LA INSTITUCIÓN.',
                'orden' => 1,
            ],
            [
                'grupo' => 'SERIE_DOCUMENTAL',
                'valor' => 'AUDITORÍA DE CONFIABILIDAD',
                'descripcion' => 'DOCUMENTACIÓN DE AUDITORÍAS DESTINADAS A EVALUAR LA CONFIABILIDAD DE LA INFORMACIÓN FINANCIERA.',
                'orden' => 2,
            ],
            [
                'grupo' => 'SERIE_DOCUMENTAL',
                'valor' => 'AUDITORÍA ESPECIAL',
                'descripcion' => 'DOCUMENTACIÓN GENERADA POR AUDITORÍAS REALIZADAS SOBRE ASUNTOS ESPECÍFICOS.',
                'orden' => 3,
            ],
            [
                'grupo' => 'SERIE_DOCUMENTAL',
                'valor' => 'AUDITORÍA SAYCO',
                'descripcion' => 'DOCUMENTACIÓN DE AUDITORÍAS SOBRE EL SISTEMA DE ADMINISTRACIÓN Y CONTROL.',
                'orden' => 4,
            ],
            [
                'grupo' => 'SERIE_DOCUMENTAL',
                'valor' => 'AUDITORÍA OPERATIVA',
                'descripcion' => 'DOCUMENTACIÓN DE AUDITORÍAS QUE EVALÚAN LA EFICACIA, EFICIENCIA Y ECONOMÍA INSTITUCIONAL.',
                'orden' => 5,
            ],
            [
                'grupo' => 'SERIE_DOCUMENTAL',
                'valor' => 'RESOLUCIÓN',
                'descripcion' => 'DOCUMENTO OFICIAL QUE CONTIENE UNA DECISIÓN O DETERMINACIÓN EMITIDA POR AUTORIDAD COMPETENTE.',
                'orden' => 6,
            ],
            [
                'grupo' => 'SERIE_DOCUMENTAL',
                'valor' => 'CONTRATOS',
                'descripcion' => 'DOCUMENTOS QUE FORMALIZAN ACUERDOS Y OBLIGACIONES ENTRE LA INSTITUCIÓN Y OTRAS PARTES.',
                'orden' => 7,
            ],
            [
                'grupo' => 'SERIE_DOCUMENTAL',
                'valor' => 'MEMORÁNDUM',
                'descripcion' => 'COMUNICACIÓN OFICIAL INTERNA UTILIZADA PARA TRANSMITIR INSTRUCCIONES, DISPOSICIONES O INFORMACIÓN.',
                'orden' => 8,
            ],
            [
                'grupo' => 'SERIE_DOCUMENTAL',
                'valor' => 'CASO DE DENUNCIA',
                'descripcion' => 'DOCUMENTACIÓN RELACIONADA CON LA RECEPCIÓN, INVESTIGACIÓN Y SEGUIMIENTO DE UNA DENUNCIA.',
                'orden' => 9,
            ],
            [
                'grupo' => 'SERIE_DOCUMENTAL',
                'valor' => 'RESOLUCIÓN DE REVERSIÓN',
                'descripcion' => 'DOCUMENTO QUE DISPONE O DETERMINA LA REVERSIÓN DE UN DERECHO, TRÁMITE O ACTO ADMINISTRATIVO.',
                'orden' => 10,
            ],
            [
                'grupo' => 'SERIE_DOCUMENTAL',
                'valor' => 'PROCESO DE REVERSIÓN',
                'descripcion' => 'DOCUMENTACIÓN GENERADA DURANTE EL PROCEDIMIENTO ADMINISTRATIVO DE REVERSIÓN.',
                'orden' => 11,
            ],
            [
                'grupo' => 'SERIE_DOCUMENTAL',
                'valor' => 'RESOLUCIÓN JERÁRQUICA',
                'descripcion' => 'DECISIÓN EMITIDA POR AUTORIDAD JERÁRQUICA QUE RESUELVE UN RECURSO ADMINISTRATIVO.',
                'orden' => 12,
            ],
            [
                'grupo' => 'SERIE_DOCUMENTAL',
                'valor' => 'PROCESO SUMARIO',
                'descripcion' => 'DOCUMENTACIÓN GENERADA DURANTE LA SUSTANCIACIÓN DE UN PROCESO ADMINISTRATIVO SUMARIO.',
                'orden' => 13,
            ],
            [
                'grupo' => 'SERIE_DOCUMENTAL',
                'valor' => 'AMPARO',
                'descripcion' => 'DOCUMENTACIÓN RELACIONADA CON PROCESOS O ACCIONES DE AMPARO CONSTITUCIONAL.',
                'orden' => 14,
            ],
            [
                'grupo' => 'SERIE_DOCUMENTAL',
                'valor' => 'RESOLUCIÓN REVOCATORIA',
                'descripcion' => 'DOCUMENTO QUE DEJA SIN EFECTO TOTAL O PARCIALMENTE UNA DECISIÓN ADMINISTRATIVA PREVIA.',
                'orden' => 15,
            ],
            [
                'grupo' => 'SERIE_DOCUMENTAL',
                'valor' => 'CONVENIO',
                'descripcion' => 'DOCUMENTO QUE FORMALIZA ACUERDOS DE COOPERACIÓN O COMPROMISOS ENTRE PARTES.',
                'orden' => 16,
            ],
            [
                'grupo' => 'SERIE_DOCUMENTAL',
                'valor' => 'FILE PERSONAL',
                'descripcion' => 'EXPEDIENTE QUE CONTIENE DOCUMENTACIÓN ADMINISTRATIVA Y LABORAL DE UN SERVIDOR PÚBLICO.',
                'orden' => 17,
            ],
            [
                'grupo' => 'SERIE_DOCUMENTAL',
                'valor' => 'IMPUESTOS',
                'descripcion' => 'DOCUMENTACIÓN RELACIONADA CON OBLIGACIONES, DECLARACIONES Y TRÁMITES TRIBUTARIOS INSTITUCIONALES.',
                'orden' => 18,
            ],
            [
                'grupo' => 'SERIE_DOCUMENTAL',
                'valor' => 'COLECCIÓN BIBLIOGRÁFICA',
                'descripcion' => 'DOCUMENTACIÓN QUE HACE REFERENCIA A LA COLECCION BIBLIOGRÁFICA.',
                'orden' => 19,
            ],
            // =====================================================
            // 8. SOPORTES
            // =====================================================
            [
                'grupo' => 'SOPORTE',
                'valor' => 'SOBRE',
                'descripcion' => 'UNIDAD DE CONSERVACIÓN UTILIZADA PARA PROTEGER Y CONTENER DOCUMENTOS.',
                'orden' => 1,
            ],
            [
                'grupo' => 'SOPORTE',
                'valor' => 'EMPASTADO',
                'descripcion' => 'DOCUMENTACIÓN ENCUADERNADA MEDIANTE UNA CUBIERTA RÍGIDA PARA SU CONSERVACIÓN.',
                'orden' => 2,
            ],
            [
                'grupo' => 'SOPORTE',
                'valor' => 'CARPETILLA',
                'descripcion' => 'CUBIERTA UTILIZADA PARA REUNIR, PROTEGER Y ORGANIZAR DOCUMENTOS DE UN EXPEDIENTE.',
                'orden' => 3,
            ],
            [
                'grupo' => 'SOPORTE',
                'valor' => 'ANILLADO',
                'descripcion' => 'DOCUMENTACIÓN PERFORADA Y UNIDA MEDIANTE ANILLOS PARA FACILITAR SU ORGANIZACIÓN.',
                'orden' => 4,
            ],
            [
                'grupo' => 'SOPORTE',
                'valor' => 'LEGAJO',
                'descripcion' => 'CONJUNTO DE DOCUMENTOS REUNIDOS Y ORDENADOS POR UN ASUNTO O TRÁMITE DETERMINADO.',
                'orden' => 5,
            ],
            [
                'grupo' => 'SOPORTE',
                'valor' => 'CUADERNO',
                'descripcion' => 'CONJUNTO DE HOJAS UNIDAS QUE CONTIENEN INFORMACIÓN O REGISTROS DOCUMENTALES.',
                'orden' => 6,
            ],
            // =====================================================
            // 9. ESTADO TRANSFERENCIA
            // =====================================================
            [
                'grupo' => 'ESTADO_TRANSFERENCIA',
                'valor' => 'INICIADO',
                'descripcion' => 'ESTADO CUANDO SE REGISTRA Y ENVÍA UNA NUEVA SOLICITUD DE TRANSFERENCIA.',
                'orden' => 1,
            ],
            [
                'grupo' => 'ESTADO_TRANSFERENCIA',
                'valor' => 'OBSERVADO',
                'descripcion' => 'ESTADO CUANDO EL USUARIO ENCARGADO ARCHIVO REVISA LA TRANSFERENCIA Y REGISTRA OBSERVACIONES QUE DEBEN SER CORREGIDAS.',
                'orden' => 2,
            ],
            [
                'grupo' => 'ESTADO_TRANSFERENCIA',
                'valor' => 'CORREGIDO',
                'descripcion' => 'ESTADO CUANDO EL USUARIO SOLICITANTE REALIZA LAS CORRECCIONES SOLICITADAS Y VUELVE A ENVIAR LA TRANSFERENCIA.',
                'orden' => 3,
            ],
            [
                'grupo' => 'ESTADO_TRANSFERENCIA',
                'valor' => 'APROBADO',
                'descripcion' => 'ESTADO CUANDO EL USUARIO ENCARGADO ARCHIVO APRUEBA LA TRANSFERENCIA.',
                'orden' => 4,
            ],
            [
                'grupo' => 'ESTADO_TRANSFERENCIA',
                'valor' => 'RECHAZADO',
                'descripcion' => 'ESTADO CUANDO EL USUARIO ENCARGADO ARCHIVO RECHAZA LA TRANSFERENCIA.',
                'orden' => 5,
            ],
            [
                'grupo' => 'ESTADO_TRANSFERENCIA',
                'valor' => 'SOLICITUD DE ANULACIÓN',
                'descripcion' => 'ESTADO CUANDO EL USUARIO SOLICITANTE SOLICITA LA ANULACIÓN DE UNA TRANSFERENCIA.',
                'orden' => 6,
            ],
            [
                'grupo' => 'ESTADO_TRANSFERENCIA',
                'valor' => 'ANULADO',
                'descripcion' => 'ESTADO CUANDO EL USUARIO ENCARGADO ARCHIVO APRUEBA UNA SOLICITUD DE ANULACIÓN.',
                'orden' => 7,
            ],
            [
                'grupo' => 'ESTADO_TRANSFERENCIA',
                'valor' => 'FINALIZADO',
                'descripcion' => 'ESTADO GENERADO CUANDO EL USUARIO ENCARGADO ARCHIVO RECIBE Y VALIDA EL FORMULARIO DE TRANSFERENCIA Y LOS EXPEDIENTES FÍSICOS. REPRESENTA LA CONCLUSIÓN DEL PROCESO DE TRANSFERENCIA. POSTERIORMENTE, LA INFORMACIÓN PASARÁ AL INVENTARIO DEL SISTEMA.',
                'orden' => 8,
            ],
            [
                'grupo' => 'ESTADO_TRANSFERENCIA',
                'valor' => 'INVENTARIADO',
                'descripcion' => 'ESTADO GENERADO CUANDO LOS EXPEDIENTES DE LA TRANSFERENCIA HAN SIDO INCORPORADOS CORRECTAMENTE AL INVENTARIO GENERAL DEL ARCHIVO.',
                'orden' => 9,
            ],
            // =====================================================
            // 10. ESTADO EXPEDIENTE
            // =====================================================
            [
                'grupo' => 'ESTADO_EXPEDIENTE',
                'valor' => 'DISPONIBLE',
                'descripcion' => 'EL EXPEDIENTE SE ENCUENTRA DISPONIBLE PARA CONSULTA O PRÉSTAMO.',
                'orden' => 1,
            ],
            [
                'grupo' => 'ESTADO_EXPEDIENTE',
                'valor' => 'PRESTADO',
                'descripcion' => 'EL EXPEDIENTE SE ENCUENTRA PRESTADO TEMPORALMENTE.',
                'orden' => 2,
            ],
            [
                'grupo' => 'ESTADO_EXPEDIENTE',
                'valor' => 'BAJA',
                'descripcion' => 'EL EXPEDIENTE FUE DADO DE BAJA DEL INVENTARIO DOCUMENTAL.',
                'orden' => 3,
            ],

        ];

        foreach ($parametros as $parametro) {
            Parametro::create([
                'grupo' => $parametro['grupo'],
                'valor' => $parametro['valor'],
                'sigla' => $parametro['sigla'] ?? null,
                'descripcion' => $parametro['descripcion'] ?? null,
                'ubicacion' => $parametro['ubicacion'] ?? null,
                'padre_id' => $parametro['padre_id'] ?? null,
                'orden' => $parametro['orden'] ?? 0,
                'usuario_creacion_id' => $usuarioCreacionId,
            ]);
        }
    }
}
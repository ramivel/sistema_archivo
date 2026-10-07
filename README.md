# Sistema de Administración de Archivos AJAM

Sistema web para administrar, controlar y mantener la trazabilidad de la documentación archivística de la Autoridad Jurisdiccional Administrativa Minera (AJAM).

## Estado del proyecto

El proyecto se encuentra en desarrollo activo.

## Funcionalidades

- Transferencias documentales.
- Regularización de transferencias.
- Validación de plantillas Excel.
- Observación, corrección, aprobación y rechazo.
- Solicitud, aprobación y rechazo de anulaciones.
- Finalización e incorporación al inventario general.
- Correcciones realizadas por Archivo.
- Generación de formularios PDF, formularios complementarios y etiquetas con QR.
- Visualización de transferencias e historial.
- Buscador documental del inventario.
- Registro manual de expedientes.

## Flujo de transferencias

```text
INICIADO → OBSERVADO → CORREGIDO → APROBADO → FINALIZADO → INVENTARIADO
```

Las regularizaciones pueden ser finalizadas por Archivo cuando se encuentran en estado `INICIADO`.

## Perfiles

### TRANSFERENCIAS

Puede crear transferencias, cargar plantillas Excel, corregir transferencias observadas, consultar sus transferencias y solicitar anulaciones.

### ENCARGADO ARCHIVO

Puede revisar, observar, aprobar, rechazar y finalizar transferencias; corregir expedientes físicos; incorporar expedientes al inventario; descargar documentos; imprimir etiquetas y administrar el inventario.

### CONSULTAS

Puede consultar el inventario general y utilizar el buscador documental.

## Tecnologías

- PHP 8.4.
- Laravel 13.
- Filament 5.8.
- PostgreSQL.
- Tailwind CSS.
- PhpSpreadsheet.
- FPDF.
- Chillerlan QRCode.
- Composer, Node.js y npm.

## Requisitos

- PHP 8.3 o superior; PHP 8.4 recomendado.
- Composer.
- Node.js y npm.
- PostgreSQL.
- Extensiones PHP requeridas por Laravel y PhpSpreadsheet.
- Servidor web local o de producción.

En desarrollo se recomienda utilizar Laragon.

## Instalación en development

### 1. Obtener el proyecto

```bash
git clone URL_DEL_REPOSITORIO sistema_archivo
cd sistema_archivo
```

### 2. Instalar dependencias

```bash
composer install
npm install
```

### 3. Crear y configurar `.env`

Windows:

```bash
copy .env.example .env
```

Linux:

```bash
cp .env.example .env
```

Configurar como mínimo:

```env
APP_NAME="Sistema de Administración de Archivos AJAM"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=archivo_ajam
DB_USERNAME=postgres
DB_PASSWORD=CAMBIAR_CONTRASEÑA
```

### 4. Preparar la aplicación

```bash
php artisan key:generate
php artisan storage:link
```

Crear previamente en PostgreSQL la base de datos `archivo_ajam`.

### 5. Migrar y cargar datos iniciales

```bash
php artisan migrate:fresh --seed
```

> Este comando elimina toda la información existente. Utilizarlo únicamente en desarrollo.

### 6. Ejecutar el proyecto

```bash
php artisan serve
npm run dev
```

También puede utilizarse:

```bash
composer run dev
```

La aplicación estará disponible en `http://127.0.0.1:8000`.

## Instalación en production

### 1. Obtener el proyecto

```bash
git clone URL_DEL_REPOSITORIO sistema_archivo
cd sistema_archivo
```

### 2. Instalar dependencias sin paquetes de desarrollo

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
```

### 3. Configurar `.env`

```env
APP_NAME="Sistema de Administración de Archivos AJAM"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://dominio-del-sistema.gob.bo

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=archivo_ajam
DB_USERNAME=USUARIO_PRODUCCION
DB_PASSWORD=CONTRASEÑA_SEGURA
```

### 4. Preparar la aplicación

```bash
php artisan key:generate --force
php artisan storage:link
```

Si `.env` ya contiene una clave válida, no se debe reemplazar.

### 5. Ejecutar migraciones

```bash
php artisan migrate --force
```

No ejecutar `php artisan migrate:fresh --seed` en producción, porque elimina la información.

### 6. Compilar recursos

```bash
npm run build
```

### 7. Optimizar Laravel

```bash
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### 8. Permisos

El usuario del servidor web debe tener permisos de escritura sobre `storage/` y `bootstrap/cache/`.

En Linux:

```bash
chmod -R 775 storage bootstrap/cache
```

### 9. Respaldos

Antes de actualizar en producción, respaldar:

- Base de datos PostgreSQL.
- `storage/app`.
- Documentos PDF y Excel.
- Archivo `.env`.

## Base de datos

El sistema utiliza PostgreSQL con estos esquemas:

### `public`

Usuarios, parámetros, correlativos y tablas generales.

### `transferencias`

Transferencias, expedientes, historial y correcciones realizadas por Archivo.

### `inventario`

Expedientes incorporados al inventario, estados e historial del inventario.

## Estructura principal

```text
app/
├── Filament/Resources/
│   ├── Inventarios/
│   └── Transferencias/
├── Models/
└── Services/

database/
├── migrations/
└── seeders/

resources/
├── css/
├── images/
└── views/filament/

routes/web.php
storage/app/
```

## Servicios principales

### `TransferenciaService`

Contiene la lógica de creación, observación, corrección, aprobación, rechazo, finalización, incorporación al inventario, correlativos y documentos asociados.

### `TransferenciaExcelService`

Lee y valida las plantillas de transferencias normales y regularizaciones, incluyendo columnas, parámetros y datos de cada expediente.

### `TransferenciaPdfService`

Genera formularios, formularios complementarios, etiquetas, códigos QR, tablas de expedientes y firmas.

## Plantillas Excel

```text
plantilla_transferencia_2026.xlsx
plantilla_regularizacion_2026.xlsx
```

En una transferencia normal, la procedencia se genera automáticamente con el formato:

```text
SIGLA FONDO/SIGLA SUBFONDO/SIGLA SECCIÓN
```

Si no existe sección, se omite ese segmento. En una regularización, la procedencia se ingresa en la plantilla y es obligatoria.

## Convenciones

- Las rutas utilizan `guid` como identificador externo.
- Los identificadores numéricos se utilizan para relaciones internas.
- Las operaciones registran el usuario mediante `Auth::id()`.
- Los textos se almacenan en mayúsculas.
- Las transferencias conservan su información original.
- Las correcciones de Archivo se guardan en una tabla independiente.
- Al incorporar al inventario se prioriza la corrección de Archivo.
- Los expedientes físicos no se modifican después de ser remitidos.
- Cuando corresponde, se utiliza borrado lógico.

## Documentos

Las rutas principales de almacenamiento son:

```text
transferencias/
transferencias/rechazos/
transferencias/finalizados/
transferencias/plantillas/
```

Los documentos PDF asociados se visualizan según el perfil y autorización del usuario.

## Comandos útiles

```bash
php artisan optimize:clear
php artisan route:list
php artisan route:list --path=admin/transferencias
php artisan migrate
php artisan migrate:fresh --seed
php artisan storage:link
php artisan test
npm run dev
npm run build
```

Aplicar formato al código:

```bash
./vendor/bin/pint
```

En Windows:

```bash
vendor\bin\pint
```

## Pruebas recomendadas

Antes de cambios importantes:

```bash
php artisan test
php artisan optimize:clear
php artisan route:list
```

También se debe verificar la migración desde cero, los seeders, plantillas Excel, generación PDF, documentos cargados, permisos por perfil e incorporación al inventario.

## Seguridad

No versionar:

```text
.env
.env.*
storage/app/*
credenciales de base de datos
documentos reales
archivos PDF institucionales
plantillas con información sensible
```

Nunca publicar contraseñas, claves de API, credenciales de PostgreSQL, credenciales del servidor ni documentos archivísticos reales.

## Mantenimiento

```bash
composer outdated
npm outdated
composer install
npm install
php artisan optimize:clear
php artisan migrate
npm run build
```

En producción, toda actualización debe realizarse después de verificar respaldos, compatibilidad de migraciones, servicios y permisos de almacenamiento.

## Funcionalidades pendientes

- Préstamos de expedientes.
- Devolución de expedientes prestados.
- Baja de expedientes.
- Historial ampliado del inventario.
- Buscador documental avanzado.
- Reportes adicionales.
- Administración completa de documentos digitales.
- Mejoras de trazabilidad y auditoría.

## Licencia

Este proyecto es de uso institucional de la Autoridad Jurisdiccional Administrativa Minera (AJAM).

La distribución, copia o modificación del sistema debe realizarse de acuerdo con las políticas institucionales correspondientes.

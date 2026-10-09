<?php

namespace App\Filament\Resources\Inventarios\Schemas;

use App\Models\Parametro;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class InventarioForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(self::components());
    }

    public static function components(): array
    {
        return [
            Section::make('Datos del expediente')
                ->description('Los campos marcados con * son obligatorios.')
                ->schema([
                    Select::make('oficina_parametro_id')
                        ->label('Fondo')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->live()
                        ->options(
                            fn (): array => self::parametros('FONDO')
                        )
                        ->afterStateUpdated(function (callable $set): void {
                            $set('direccion_parametro_id', null);
                            $set('area_parametro_id', null);
                        })                        
                        ->validationMessages([
                            'required' => 'Debe seleccionar el fondo documental.',
                        ]),
                    Select::make('direccion_parametro_id')
                        ->label('Subfondo')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->live()
                        ->options(function (Get $get): array {
                            $oficinaId = $get('oficina_parametro_id');
                            if (blank($oficinaId)) {
                                return [];
                            }
                            return Parametro::query()
                                ->where('grupo', 'SUBFONDO')
                                ->where('padre_id', $oficinaId)
                                ->where('activo', true)
                                ->whereNull('fecha_eliminacion')
                                ->orderBy('valor')
                                ->pluck('valor', 'id')
                                ->toArray();
                        })
                        ->afterStateUpdated(
                            fn (callable $set): mixed =>
                                $set('area_parametro_id', null)
                        )                        
                        ->validationMessages([
                            'required' => 'Debe seleccionar el subfondo.',
                        ]),
                    Select::make('area_parametro_id')
                        ->label('Sección')
                        ->searchable()
                        ->preload()
                        ->options(function (Get $get): array {
                            $direccionId = $get('direccion_parametro_id');
                            if (blank($direccionId)) {
                                return [];
                            }
                            return Parametro::query()
                                ->where('grupo', 'SECCION')
                                ->where('padre_id', $direccionId)
                                ->where('activo', true)
                                ->whereNull('fecha_eliminacion')
                                ->orderBy('valor')
                                ->pluck('valor', 'id')
                                ->toArray();
                        }),
                    TextInput::make('codigo_referencia')
                        ->label('Código de referencia')
                        ->required()
                        ->maxLength(50)
                        ->extraInputAttributes([
                            'style' => 'text-transform: uppercase',
                        ])
                        ->dehydrateStateUsing(
                            fn (?string $state): ?string =>
                                $state === null
                                    ? null
                                    : mb_strtoupper(trim($state), 'UTF-8')
                        )
                        ->hintIcon(
                            'heroicon-m-information-circle',
                            tooltip: 'Registre el código utilizado para identificar el expediente o unidad documental.'
                        )
                        ->validationMessages([
                            'required' => 'Debe ingresar el código de referencia.',
                            'max' => 'El código de referencia no puede superar los 50 caracteres.',
                        ]),
                    TextInput::make('numero_caja')
                        ->label('N° de caja')
                        ->required()
                        ->maxLength(50)
                        ->regex('/^[1-9][0-9]*(\/[1-9][0-9]*)?$/')
                        ->placeholder('-')
                        ->extraInputAttributes([
                            'style' => 'text-transform: uppercase',
                        ])
                        ->dehydrateStateUsing(
                            fn (?string $state): ?string =>
                                $state === null
                                    ? null
                                    : mb_strtoupper(trim($state), 'UTF-8')
                        )
                        ->validationMessages([
                            'required' => 'Debe ingresar el número de caja.',
                            'regex' => 'El número de caja debe tener el formato 1 o 1/16.',
                            'max' => 'El número de caja no puede superar los 50 caracteres.',
                        ])
                        ->hintIcon(
                            'heroicon-m-information-circle',
                            tooltip: 'El número de caja debe tener el formato 1 o 1/16.'
                        ),
                    TextInput::make('procedencia')
                        ->label('Procedencia')
                        ->required()
                        ->extraInputAttributes([
                            'style' => 'text-transform: uppercase',
                        ])
                        ->dehydrateStateUsing(
                            fn (?string $state): ?string =>
                                $state === null
                                    ? null
                                    : mb_strtoupper(trim($state), 'UTF-8')
                        )
                        ->hintIcon(
                            'heroicon-m-information-circle',
                            tooltip: 'Registre la sigla correspondiente a la procedencia. Las siglas válidas se encuentran en la hoja PARAMETRICAS. Cuando exista más de una procedencia, sepárelas mediante /, por ejemplo: AJAM/DAF/URH.'
                        )
                        ->validationMessages([
                            'required' => 'Debe ingresar la procedencia.',
                        ]),
                    Select::make('serie_documental_parametro_id')
                        ->label('Serie documental')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->options(
                            fn (): array => self::parametros('SERIE_DOCUMENTAL')
                        )                        
                        ->validationMessages([
                            'required' => 'Debe seleccionar la serie documental.',
                        ]),
                    Select::make('soporte_parametro_id')
                        ->label('Soporte')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->options(
                            fn (): array => self::parametros('SOPORTE')
                        )                        
                        ->validationMessages([
                            'required' => 'Debe seleccionar el soporte.',
                        ]),
                    Textarea::make('descripcion_lomo')
                        ->label('Descripción documental')
                        ->required()
                        ->rows(3)
                        ->columnSpanFull()
                        ->extraInputAttributes([
                            'style' => 'text-transform: uppercase',
                        ])
                        ->dehydrateStateUsing(
                            fn (?string $state): ?string =>
                                $state === null
                                    ? null
                                    : mb_strtoupper(trim($state), 'UTF-8')
                        )
                        ->hintIcon(
                            'heroicon-m-information-circle',
                            tooltip: 'Registre la descripción visible en el lomo o carátula, por ejemplo: Informes, Reglamento o Resolución Administrativa.'
                        )
                        ->validationMessages([
                            'required' => 'Debe ingresar la descripción documental.',
                        ]),
                    Textarea::make('detalle')
                        ->label('Detalle')
                        ->required()
                        ->rows(5)
                        ->columnSpanFull()
                        ->extraInputAttributes([
                            'style' => 'text-transform: uppercase',
                        ])
                        ->dehydrateStateUsing(
                            fn (?string $state): ?string =>
                                $state === null
                                    ? null
                                    : mb_strtoupper(trim($state), 'UTF-8')
                        )
                        ->hintIcon(
                            'heroicon-m-information-circle',
                            tooltip: 'Registre el contenido del expediente con el detalle necesario. Puede utilizar varias líneas.'
                        )
                        ->validationMessages([
                            'required' => 'Debe ingresar el detalle del expediente.',
                        ]),
                    TextInput::make('tomo_volumen')
                        ->label('Tomo / volumen')
                        ->required()
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->step(1)
                        ->hintIcon(
                            'heroicon-m-information-circle',
                            tooltip: 'Ingrese el número de tomo o volumen. Registre 1 si el expediente corresponde a un único tomo.'
                        )
                        ->validationMessages([
                            'required' => 'Debe ingresar el tomo o volumen.',
                            'numeric' => 'El tomo o volumen debe ser numérico.',
                            'integer' => 'El tomo o volumen debe ser un número entero.',
                            'min' => 'El tomo o volumen debe ser mayor o igual a 1.',
                        ]),
                    TextInput::make('fojas')
                        ->label('Fojas')
                        ->required()
                        ->maxLength(30)
                        ->regex('/^(S\/F|[0-9-]+)$/i')
                        ->extraInputAttributes([
                            'style' => 'text-transform: uppercase',
                        ])
                        ->dehydrateStateUsing(
                            fn (?string $state): ?string =>
                                $state === null
                                    ? null
                                    : mb_strtoupper(trim($state), 'UTF-8')
                        )
                        ->hintIcon(
                            'heroicon-m-information-circle',
                            tooltip: 'Ingrese la cantidad total de fojas. Ejemplos: 100, 1-5 o S/F si no tiene fojas.'
                        )
                        ->validationMessages([
                            'required' => 'Debe ingresar la cantidad de fojas.',
                            'regex' => 'Las fojas deben contener números, rangos o el valor S/F.',
                            'max' => 'El campo fojas no puede superar los 30 caracteres.',
                        ]),
                    TextInput::make('fechas_extremas')
                        ->label('Fechas extremas')
                        ->required()
                        ->maxLength(30)
                        ->regex('/^[0-9-]+$/')
                        ->extraInputAttributes([
                            'style' => 'text-transform: uppercase',
                        ])
                        ->dehydrateStateUsing(
                            fn (?string $state): ?string =>
                                $state === null
                                    ? null
                                    : mb_strtoupper(trim($state), 'UTF-8')
                        )
                        ->hintIcon(
                            'heroicon-m-information-circle',
                            tooltip: 'Ingrese el año o rango de años con formato AAAA o AAAA-AAAA. Ejemplos: 2018 o 2018-2020.'
                        )
                        ->validationMessages([
                            'required' => 'Debe ingresar las fechas extremas.',
                            'regex' => 'Utilice el formato AAAA o AAAA-AAAA.',
                            'max' => 'Las fechas extremas no pueden superar los 30 caracteres.',
                        ]),
                    Textarea::make('observaciones')
                        ->label('Observaciones')
                        ->required()
                        ->rows(4)
                        ->columnSpanFull()
                        ->extraInputAttributes([
                            'style' => 'text-transform: uppercase',
                        ])
                        ->dehydrateStateUsing(
                            fn (?string $state): ?string =>
                                $state === null
                                    ? null
                                    : mb_strtoupper(trim($state), 'UTF-8')
                        )
                        ->hintIcon(
                            'heroicon-m-information-circle',
                            tooltip: 'Registre información adicional, anexos o soportes digitales como CD, DVD u otros.'
                        )
                        ->validationMessages([
                            'required' => 'Debe ingresar las observaciones.',
                        ]),
                    FileUpload::make('archivo_pdf')
                        ->label('Documento PDF')
                        ->disk('local')
                        ->directory('inventario/expedientes')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(20480)
                        ->nullable()
                        ->downloadable()
                        ->openable()
                        ->columnSpanFull()
                        ->hintIcon(
                            'heroicon-m-information-circle',
                            tooltip: 'Adjunte opcionalmente el documento digital del expediente en formato PDF.'
                        )
                        ->validationMessages([
                            'mimetypes' => 'El documento debe estar en formato PDF.',
                            'max' => 'El archivo no puede superar los 20 MB.',
                        ]),
                ])
                ->columns(3)
                ->columnSpanFull()
        ];
    }

    private static function parametros(string $grupo): array
    {
        return Parametro::query()
            ->where('grupo', $grupo)
            ->where('activo', true)
            ->whereNull('fecha_eliminacion')
            ->orderBy('orden')
            ->orderBy('valor')
            ->pluck('valor', 'id')
            ->toArray();
    }
}
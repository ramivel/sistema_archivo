<?php

namespace App\Filament\Resources\Inventarios\Schemas;

use App\Models\Parametro;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;

class InventarioPrestamoForm
{
    public static function prestar(): array
    {
        return [
            Section::make('Datos del préstamo')
                ->description('Los campos marcados con * son obligatorios.')
                ->schema([
                    TextInput::make('numero_solicitud')
                        ->label('N° solicitud')
                        ->required()
                        ->maxLength(50)
                        ->extraInputAttributes([
                            'style' => 'text-transform: uppercase',
                        ])
                        ->dehydrateStateUsing(
                            fn (?string $state): ?string => $state === null
                                ? null
                                : mb_strtoupper(
                                    trim($state),
                                    'UTF-8'
                                )
                        )
                        ->validationMessages([
                            'required' => 'Debe ingresar el número de solicitud.',
                            'max' => 'El número de solicitud no puede superar los 50 caracteres.',
                        ]),
                    DatePicker::make('fecha_prestamo')
                        ->label('Fecha de préstamo')
                        ->required()
                        ->validationMessages([
                            'required' => 'Debe seleccionar la fecha de préstamo.',
                            'date' => 'La fecha de préstamo no es válida.',
                        ]),
                    TextInput::make('nombre_solicitante')
                        ->label('Nombre completo del usuario solicitante')
                        ->required()
                        ->maxLength(200)
                        ->extraInputAttributes([
                            'style' => 'text-transform: uppercase',
                        ])
                        ->dehydrateStateUsing(
                            fn (?string $state): ?string => $state === null
                                ? null
                                : mb_strtoupper(
                                    trim($state),
                                    'UTF-8'
                                )
                        )
                        ->validationMessages([
                            'required' => 'Debe ingresar el nombre completo del usuario solicitante.',
                            'max' => 'El nombre no puede superar los 200 caracteres.',
                        ]),
                    TextInput::make('cargo')
                        ->label('Cargo')
                        ->required()
                        ->maxLength(150)
                        ->extraInputAttributes([
                            'style' => 'text-transform: uppercase',
                        ])
                        ->dehydrateStateUsing(
                            fn (?string $state): ?string => $state === null
                                ? null
                                : mb_strtoupper(
                                    trim($state),
                                    'UTF-8'
                                )
                        )
                        ->validationMessages([
                            'required' => 'Debe ingresar el cargo del usuario solicitante.',
                            'max' => 'El cargo no puede superar los 150 caracteres.',
                        ]),
                    Select::make('oficina_parametro_id')
                        ->label('Oficina')
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
                            'required' => 'Debe seleccionar la oficina.',
                        ]),
                    Select::make('direccion_parametro_id')
                        ->label('Dirección')
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
                        ->afterStateUpdated(function (callable $set): void {
                            $set('area_parametro_id', null);
                        })
                        ->validationMessages([
                            'required' => 'Debe seleccionar la dirección.',
                        ]),
                    Select::make('area_parametro_id')
                        ->label('Área')
                        ->nullable()
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
                    TextInput::make('telefono')
                        ->label('Teléfono / Celular / Interno')
                        ->required()
                        ->maxLength(100)
                        ->extraInputAttributes([
                            'style' => 'text-transform: uppercase',
                        ])
                        ->dehydrateStateUsing(
                            fn (?string $state): ?string => $state === null
                                ? null
                                : mb_strtoupper(
                                    trim($state),
                                    'UTF-8'
                                )
                        )
                        ->validationMessages([
                            'required' => 'Debe ingresar un teléfono, celular o interno.',
                            'max' => 'El teléfono no puede superar los 100 caracteres.',
                        ]),
                    Select::make('tipos_consulta')
                        ->label('Tipo de consulta')
                        ->required()
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->columnSpanFull()
                        ->options(
                            fn (): array => self::parametros('TIPO_CONSULTA')
                        )
                        ->validationMessages([
                            'required' => 'Debe seleccionar al menos un tipo de consulta.',
                        ]),
                    Textarea::make('motivo_finalidad')
                        ->label('Motivo / Finalidad')
                        ->required()
                        ->rows(4)
                        ->columnSpanFull()
                        ->extraInputAttributes([
                            'style' => 'text-transform: uppercase',
                        ])
                        ->dehydrateStateUsing(
                            fn (?string $state): ?string => $state === null
                                ? null
                                : mb_strtoupper(
                                    trim($state),
                                    'UTF-8'
                                )
                        )
                        ->validationMessages([
                            'required' => 'Debe ingresar el motivo o finalidad del préstamo.',
                        ]),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ];
    }

    public static function devolver(): array
    {
        return [
            Section::make('Datos de la devolución')
                ->description('Los campos marcados con * son obligatorios.')
                ->schema([
                    DatePicker::make('fecha_devolucion')
                        ->label('Fecha de devolución')
                        ->required()
                        ->validationMessages([
                            'required' => 'Debe seleccionar la fecha de devolución.',
                            'date' => 'La fecha de devolución no es válida.',
                        ]),
                    Textarea::make('observaciones_devolucion')
                        ->label('Observaciones de devolución')
                        ->required()
                        ->rows(4)
                        ->extraInputAttributes([
                            'style' => 'text-transform: uppercase',
                        ])
                        ->dehydrateStateUsing(
                            fn (?string $state): ?string => $state === null
                                ? null
                                : mb_strtoupper(
                                    trim($state),
                                    'UTF-8'
                                )
                        )
                        ->validationMessages([
                            'required' => 'Debe ingresar la observacióm de devolucion.',
                        ]),
                ])
                ->columns(1)
        ];
    }

    public static function baja(): array
    {
        return [
            Section::make('Datos de la baja')
                ->description('Los campos marcados con * son obligatorios.')
                ->schema([
                    Textarea::make('motivo_baja')
                        ->label('Motivo de baja del expediente')
                        ->required()
                        ->rows(4)
                        ->extraInputAttributes([
                            'style' => 'text-transform: uppercase',
                        ])
                        ->dehydrateStateUsing(
                            fn (?string $state): ?string => $state === null
                                ? null
                                : mb_strtoupper(
                                    trim($state),
                                    'UTF-8'
                                )
                        )
                        ->validationMessages([
                            'required' => 'Debe ingresar el motivo de baja del expediente.',
                        ]),
                ])
                ->columns(1)
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
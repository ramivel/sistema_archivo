<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\Parametro;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->description('Los campos marcados con * son obligatorios.')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('nombres')
                            ->label('Nombres')
                            ->required()
                            ->maxLength(100)
                            ->autofocus()
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
                            ),
                        TextInput::make('apellidos')
                            ->label('Apellidos')
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
                            ),
                        TextInput::make('documento_identidad')
                            ->label('Documento de identidad')
                            ->required()
                            ->maxLength(30)
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
                            ),
                        Select::make('expedido_parametro_id')
                            ->label('Expedido')
                            ->required()
                            ->placeholder('Seleccione una opción')
                            ->options(fn () => Parametro::query()
                                ->where('grupo', 'LUGAR_EXPEDICION')
                                ->where('activo', true)
                                ->orderBy('orden')
                                ->pluck('valor', 'id')
                            )
                            ->searchable()
                            ->preload(),
                        Select::make('oficina_parametro_id')
                            ->label('Oficina')
                            ->required()
                            ->placeholder('Seleccione una opción')
                            ->options(fn () => Parametro::query()
                                ->where('grupo', 'FONDO')
                                ->where('activo', true)
                                ->orderBy('orden')
                                ->pluck('valor', 'id')
                            )
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (callable $set): void {
                                $set('direccion_parametro_id', null);
                                $set('area_parametro_id', null);
                            }),
                        Select::make('direccion_parametro_id')
                            ->label('Dirección')
                            ->required()
                            ->placeholder('Seleccione una opción')
                            ->options(function (Get $get): array {
                                $oficinaId = $get('oficina_parametro_id');
                                if (!$oficinaId) {
                                    return [];
                                }
                                return Parametro::query()
                                    ->where('grupo', 'SUBFONDO')
                                    ->where('padre_id', $oficinaId)
                                    ->where('activo', true)
                                    ->orderBy('orden')
                                    ->pluck('valor', 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (callable $set): void {
                                $set('area_parametro_id', null);
                            }),
                        Select::make('area_parametro_id')
                            ->label('Área')
                            ->placeholder('Seleccione una opción')
                            ->options(function (Get $get): array {
                                $direccionId = $get('direccion_parametro_id');
                                if (!$direccionId) {
                                    return [];
                                }
                                return Parametro::query()
                                    ->where('grupo', 'SECCION')
                                    ->where('padre_id', $direccionId)
                                    ->where('activo', true)
                                    ->orderBy('orden')
                                    ->pluck('valor', 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload(),
                        TextInput::make('email')
                            ->label('Correo electrónico')
                            ->email()
                            ->required()
                            ->maxLength(150)
                            ->dehydrateStateUsing(
                                fn (?string $state): ?string => $state === null
                                    ? null
                                    : mb_strtolower(
                                        trim($state),
                                        'UTF-8'
                                    )
                            ),
                        TextInput::make('telefonos')
                            ->label('Teléfono / Celular / Interno')
                            ->maxLength(150)
                            ->dehydrateStateUsing(
                                fn (?string $state): ?string => $state === null
                                    ? null
                                    : trim($state)
                            ),
                        TextInput::make('usuario')
                            ->label('Usuario')
                            ->required()
                            ->maxLength(50)
                            ->visibleOn('create')
                            ->unique(
                                table: 'usuarios',
                                column: 'usuario',
                                ignoreRecord: true,
                            ),
                        Select::make('perfiles')
                            ->label('Perfil(es)')
                            ->required()
                            ->multiple()
                            ->placeholder('Seleccione una o más opciones')
                            ->relationship(
                                'perfiles',
                                'valor',
                                fn ($query) => $query
                                    ->where('parametros.grupo', 'PERFIL')
                                    ->where('parametros.activo', true)
                                    ->orderBy('parametros.orden')
                            )
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(2),
            ]);
    }
}
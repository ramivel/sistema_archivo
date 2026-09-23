<?php

namespace App\Filament\Resources\Perfils\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PerfilForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->description('Los campos marcados con * son obligatorios.')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('valor')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(200)
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
                        Textarea::make('descripcion')
                            ->label('Descripción')
                            ->rows(4)
                            ->maxLength(65535)
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
                    ])
                    ->columns(1),
            ]);
    }
}
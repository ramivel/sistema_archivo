<?php

namespace App\Filament\Resources\Fondos\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FondoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
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
                                : mb_strtoupper(trim($state), 'UTF-8')
                        ),
                    TextInput::make('sigla')
                        ->label('Sigla')
                        ->required()
                        ->maxLength(50)
                        ->extraInputAttributes([
                            'style' => 'text-transform: uppercase',
                        ])
                        ->dehydrateStateUsing(
                            fn (?string $state): ?string => $state === null
                                ? null
                                : mb_strtoupper(trim($state), 'UTF-8')
                        ),
                    TextInput::make('ubicacion')
                        ->label('Ubicación')
                        ->required()
                        ->maxLength(250)
                        ->extraInputAttributes([
                            'style' => 'text-transform: uppercase',
                        ])
                        ->dehydrateStateUsing(
                            fn (?string $state): ?string => $state === null
                                ? null
                                : mb_strtoupper(trim($state), 'UTF-8')
                        ),
                ])
                ->columns(1),
        ]);
    }
}
<?php

namespace App\Filament\Resources\Perfils;

use App\Filament\Resources\Perfils\Pages\ListPerfils;
use App\Filament\Resources\Perfils\Schemas\PerfilForm;
use App\Filament\Resources\Perfils\Tables\PerfilsTable;
use App\Models\Parametro;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PerfilResource extends Resource
{
    protected static ?string $model = Parametro::class;
    protected static string $grupo = 'PERFIL';
    protected static ?string $recordTitleAttribute = 'valor';
    protected static string|\UnitEnum|null $navigationGroup = 'Administración';
    protected static ?string $navigationLabel = 'Perfiles';
    protected static ?string $modelLabel = 'Perfil';
    protected static ?string $pluralModelLabel = 'Perfiles';
    protected static ?string $slug = 'perfiles';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    public static function form(Schema $schema): Schema
    {
        return PerfilForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PerfilsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('grupo', static::$grupo);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPerfils::route('/'),
        ];
    }

    public static function getGrupo(): string
    {
        return static::$grupo;
    }
}
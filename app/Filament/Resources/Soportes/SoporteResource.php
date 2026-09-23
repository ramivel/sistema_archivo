<?php

namespace App\Filament\Resources\Soportes;

use App\Filament\Resources\Soportes\Pages\ListSoportes;
use App\Filament\Resources\Soportes\Schemas\SoporteForm;
use App\Filament\Resources\Soportes\Tables\SoportesTable;
use App\Models\Parametro;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SoporteResource extends Resource
{
    protected static ?string $model = Parametro::class;
    protected static string $grupo = 'SOPORTE';
    protected static ?string $recordTitleAttribute = 'valor';
    protected static string|\UnitEnum|null $navigationGroup = 'Administración';
    protected static ?string $navigationLabel = 'Soportes';
    protected static ?string $modelLabel = 'Soporte';
    protected static ?string $pluralModelLabel = 'Soportes';
    protected static ?string $slug = 'soportes';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-folder';

    public static function form(Schema $schema): Schema
    {
        return SoporteForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SoportesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('grupo', static::$grupo);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSoportes::route('/'),
        ];
    }

    public static function getGrupo(): string
    {
        return static::$grupo;
    }
}
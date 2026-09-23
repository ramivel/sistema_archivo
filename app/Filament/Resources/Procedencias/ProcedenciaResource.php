<?php

namespace App\Filament\Resources\Procedencias;

use App\Filament\Resources\Procedencias\Pages\ListProcedencias;
use App\Filament\Resources\Procedencias\Schemas\ProcedenciaForm;
use App\Filament\Resources\Procedencias\Tables\ProcedenciasTable;
use App\Models\Parametro;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProcedenciaResource extends Resource
{
    protected static ?string $model = Parametro::class;
    protected static string $grupo = 'PROCEDENCIA';
    protected static ?string $recordTitleAttribute = 'valor';
    protected static string|\UnitEnum|null $navigationGroup = 'Administración';
    protected static ?string $navigationLabel = 'Procedencias';
    protected static ?string $modelLabel = 'Procedencia';
    protected static ?string $pluralModelLabel = 'Procedencias';
    protected static ?string $slug = 'procedencias';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-map-pin';

    public static function form(Schema $schema): Schema
    {
        return ProcedenciaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProcedenciasTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('grupo', static::$grupo);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProcedencias::route('/'),
        ];
    }

    public static function getGrupo(): string
    {
        return static::$grupo;
    }
}
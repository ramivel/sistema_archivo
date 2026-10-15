<?php

namespace App\Filament\Resources\SerieDocumentals;

use App\Enums\NavigationGroupEnum;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Filament\Resources\SerieDocumentals\Pages\ListSerieDocumentals;
use App\Filament\Resources\SerieDocumentals\Schemas\SerieDocumentalForm;
use App\Filament\Resources\SerieDocumentals\Tables\SerieDocumentalsTable;
use App\Models\Parametro;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SerieDocumentalResource extends Resource
{
    protected static ?string $model = Parametro::class;
    protected static string $grupo = 'SERIE_DOCUMENTAL';
    protected static ?string $recordTitleAttribute = 'valor';
    protected static string|\UnitEnum|null $navigationGroup = NavigationGroupEnum::ADMINISTRACION->value;
    protected static ?string $navigationLabel = 'Series Documentales';
    protected static ?string $modelLabel = 'Serie Documental';
    protected static ?string $pluralModelLabel = 'Series Documentales';
    protected static ?string $slug = 'series-documentales';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    public static function form(Schema $schema): Schema
    {
        return SerieDocumentalForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SerieDocumentalsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('grupo', static::$grupo);
    }

    public static function shouldRegisterNavigation(): bool
    {
        $usuario = User::find(Auth::id());
        return $usuario !== null && $usuario->esAdministrador();
    }

    public static function canViewAny(): bool
    {
        $usuario = User::find(Auth::id());
        return $usuario !== null && $usuario->esAdministrador();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSerieDocumentals::route('/'),
        ];
    }

    public static function getGrupo(): string
    {
        return static::$grupo;
    }
    
}
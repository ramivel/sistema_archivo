<?php

namespace App\Filament\Resources\Fondos;

use App\Enums\NavigationGroupEnum;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Filament\Resources\Fondos\Pages\ListFondos;
use App\Filament\Resources\Fondos\Schemas\FondoForm;
use App\Filament\Resources\Fondos\Tables\FondosTable;
use App\Models\Parametro;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FondoResource extends Resource
{
    protected static ?string $model = Parametro::class;
    protected static string $grupo = 'FONDO';
    protected static ?string $recordTitleAttribute = 'valor';
    protected static string|\UnitEnum|null $navigationGroup = NavigationGroupEnum::ADMINISTRACION->value;
    protected static ?string $navigationLabel = 'Fondos/Sub Fondos/Secciones';
    protected static ?string $modelLabel = 'Fondo/Oficina';
    protected static ?string $pluralModelLabel = 'Fondos/Oficinas';
    protected static ?string $slug = 'fondos';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    public static function getNavigationItemActiveRoutePattern(): string|array
    {
        return [
            'filament.admin.resources.fondos.*',
            'filament.admin.resources.subfondos.*',
            'filament.admin.resources.secciones.*',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return FondoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FondosTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('grupo', static::$grupo)
            ->whereNull('padre_id')
            ->withCount([
                'hijos as subfondos_count' => function (Builder $query): void {
                    $query->where('grupo', 'SUBFONDO');
                },
            ]);
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
            'index' => ListFondos::route('/'),
        ];
    }

    public static function getGrupo(): string
    {
        return static::$grupo;
    }
}
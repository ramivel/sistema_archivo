<?php

namespace App\Filament\Resources\Inventarios;

use App\Filament\Resources\Inventarios\Pages\BuscadorInventario;
use App\Filament\Resources\Inventarios\Pages\ListInventarios;
use App\Filament\Resources\Inventarios\Pages\VerInventario;
use App\Filament\Resources\Inventarios\Schemas\InventarioForm;
use App\Filament\Resources\Inventarios\Tables\InventariosTable;
use App\Models\InventarioExpediente;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Filament\Navigation\NavigationItem;

class InventarioResource extends Resource
{
    protected static ?string $model = InventarioExpediente::class;
    protected static string|\UnitEnum|null $navigationGroup = 'Inventario';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';
    protected static ?string $recordTitleAttribute = 'codigo_inventario';
    protected static ?string $navigationLabel = 'Inventario general';
    protected static ?string $modelLabel = 'Expediente';
    protected static ?string $pluralModelLabel = 'Expedientes';
    protected static ?string $slug = 'inventario';

    public static function getNavigationItems(): array
    {
        if (! static::canAccess()) {
            return [];
        }

        $rutaBase = static::getRouteBaseName();

        return [
            NavigationItem::make('Inventario general')
                ->key(static::class . '.index')
                ->group(static::getNavigationGroup())
                ->icon('heroicon-o-archive-box')
                ->isActiveWhen(
                    fn (): bool =>
                        request()->routeIs($rutaBase . '.index')
                )
                ->url(
                    fn (): string =>
                        static::getUrl('index')
                ),

            NavigationItem::make('Buscador documental')
                ->key(static::class . '.buscador')
                ->group(static::getNavigationGroup())
                ->icon('heroicon-o-magnifying-glass')
                ->isActiveWhen(
                    fn (): bool =>
                        request()->routeIs($rutaBase . '.buscador')
                )
                ->url(
                    fn (): string =>
                        static::getUrl('buscador')
                ),
        ];
    }

    public static function canAccess(): bool
    {
        $usuario = User::find(Auth::id());
        return $usuario instanceof User
            && (
                $usuario->esEncargadoArchivo()
                || $usuario->esConsultas()
            );
    }

    public static function form(Schema $schema): Schema
    {
        return InventarioForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InventariosTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInventarios::route('/'),
            'buscador' => BuscadorInventario::route('/buscador'),
            'ver' => VerInventario::route('/{record}/ver'),
        ];
    }
}

<?php

namespace App\Filament\Resources\Subfondos;

use App\Filament\Resources\Subfondos\Pages\ListSubfondos;
use App\Filament\Resources\Subfondos\Schemas\SubfondoForm;
use App\Filament\Resources\Subfondos\Tables\SubfondosTable;
use App\Models\Parametro;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SubfondoResource extends Resource
{
    protected static ?string $model = Parametro::class;
    protected static string $grupo = 'SUBFONDO';
    protected static ?string $recordTitleAttribute = 'valor';
    protected static string|\UnitEnum|null $navigationGroup = 'Administración';
    protected static ?string $navigationLabel = 'Fondos/Sub Fondos/Secciones';
    protected static ?string $modelLabel = 'Sub Fondo/Dirección';
    protected static ?string $pluralModelLabel = 'Sub Fondos/Direcciones';
    protected static ?string $slug = 'subfondos';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office';
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Schema $schema): Schema
    {
        return SubfondoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SubfondosTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $fondo = static::getFondoFromRoute();
        return parent::getEloquentQuery()
            ->where('grupo', static::$grupo)
            ->where('padre_id', $fondo->id)
            ->withCount([
                'hijos as secciones_count' => function (Builder $query): void {
                    $query->where('grupo', 'SECCION');
                },
            ]);
    }

    public static function getFondoFromRoute(): Parametro
    {
        $fondo = request()->route('fondo');
        $guid = $fondo instanceof Parametro
            ? $fondo->guid
            : $fondo;
        $guid ??= session('subfondo_fondo_guid');
        $expiresAt = session('subfondo_fondo_expires_at');
        if (
            $guid === null ||
            $expiresAt === null ||
            now()->greaterThan($expiresAt)
        ) {
            session()->forget([
                'subfondo_fondo_guid',
                'subfondo_fondo_expires_at',
            ]);
            abort(404);
        }
        session()->put(
            'subfondo_fondo_expires_at',
            now()->addMinutes(15)
        );
        return Parametro::query()
            ->where('grupo', 'FONDO')
            ->where('guid', $guid)
            ->whereNull('fecha_eliminacion')
            ->firstOrFail();
    }

    public static function getGrupo(): string
    {
        return static::$grupo;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSubfondos::route('/{fondo?}'),
        ];
    }
}
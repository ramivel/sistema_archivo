<?php

namespace App\Filament\Resources\Seccions;

use App\Filament\Resources\Seccions\Pages\ListSeccions;
use App\Filament\Resources\Seccions\Schemas\SeccionForm;
use App\Filament\Resources\Seccions\Tables\SeccionsTable;
use App\Models\Parametro;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SeccionResource extends Resource
{
    protected static ?string $model = Parametro::class;
    protected static string $grupo = 'SECCION';
    protected static ?string $recordTitleAttribute = 'valor';
    protected static string|\UnitEnum|null $navigationGroup = 'Administración';
    protected static ?string $navigationLabel = 'Fondos/Sub Fondos/Secciones';
    protected static ?string $modelLabel = 'Sección/Área';
    protected static ?string $pluralModelLabel = 'Secciones/Áreas';
    protected static ?string $slug = 'secciones';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-folder';
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Schema $schema): Schema
    {
        return SeccionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SeccionsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $subfondo = static::getSubfondoFromRoute();
        return parent::getEloquentQuery()
            ->where('grupo', static::$grupo)
            ->where('padre_id', $subfondo->id);
    }

    public static function getSubfondoFromRoute(): Parametro
    {
        $subfondo = request()->route('subfondo');
        $guid = $subfondo instanceof Parametro
            ? $subfondo->guid
            : $subfondo;
        $guid ??= session('seccion_subfondo_guid');
        $expiresAt = session('seccion_subfondo_expires_at');
        if (
            $guid === null ||
            $expiresAt === null ||
            now()->greaterThan($expiresAt)
        ) {
            session()->forget([
                'seccion_subfondo_guid',
                'seccion_subfondo_expires_at',
            ]);
            abort(404);
        }
        session()->put(
            'seccion_subfondo_expires_at',
            now()->addMinutes(15)
        );
        return Parametro::query()
            ->where('grupo', 'SUBFONDO')
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
            'index' => ListSeccions::route('/{subfondo?}'),
        ];
    }
}
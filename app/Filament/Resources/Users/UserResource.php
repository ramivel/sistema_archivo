<?php

namespace App\Filament\Resources\Users;

use App\Enums\NavigationGroupEnum;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $recordTitleAttribute = 'nombres';
    protected static string|\UnitEnum|null $navigationGroup = NavigationGroupEnum::ADMINISTRACION->value;
    protected static ?string $navigationLabel = 'Usuarios';
    protected static ?string $modelLabel = 'Usuario';
    protected static ?string $pluralModelLabel = 'Usuarios';
    protected static ?string $slug = 'usuarios';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'expedido',
                'oficina',
                'direccion',
                'area',
                'perfiles',
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
            'index' => ListUsers::route('/'),
        ];
    }
}
<?php

namespace App\Filament\Navigation;

use App\Enums\NavigationGroupEnum;
use Filament\Navigation\NavigationGroup;

final class AdminNavigation
{
    public static function groups(): array
    {
        return [
            NavigationGroup::make()
                ->label(NavigationGroupEnum::ADMINISTRACION->value)
                ->icon('heroicon-o-cog-8-tooth')
                ->collapsed(),
            NavigationGroup::make()
                ->label(NavigationGroupEnum::INVENTARIO->value)
                ->icon('heroicon-o-archive-box')
                ->collapsed(),
            NavigationGroup::make()
                ->label(NavigationGroupEnum::REPORTES->value)
                ->icon('heroicon-o-chart-bar')
                ->collapsed(),
        ];
    }
}
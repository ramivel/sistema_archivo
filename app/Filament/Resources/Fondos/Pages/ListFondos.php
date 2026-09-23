<?php

namespace App\Filament\Resources\Fondos\Pages;

use App\Filament\Resources\Fondos\FondoResource;
use Filament\Resources\Pages\ListRecords;

class ListFondos extends ListRecords
{
    protected static string $resource = FondoResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

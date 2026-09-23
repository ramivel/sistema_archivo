<?php

namespace App\Filament\Resources\Soportes\Pages;

use App\Filament\Resources\Soportes\SoporteResource;
use Filament\Resources\Pages\ListRecords;

class ListSoportes extends ListRecords
{
    protected static string $resource = SoporteResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
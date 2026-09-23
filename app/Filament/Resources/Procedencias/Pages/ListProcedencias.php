<?php

namespace App\Filament\Resources\Procedencias\Pages;

use App\Filament\Resources\Procedencias\ProcedenciaResource;
use Filament\Resources\Pages\ListRecords;

class ListProcedencias extends ListRecords
{
    protected static string $resource = ProcedenciaResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

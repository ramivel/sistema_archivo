<?php

namespace App\Filament\Resources\SerieDocumentals\Pages;

use App\Filament\Resources\SerieDocumentals\SerieDocumentalResource;
use Filament\Resources\Pages\ListRecords;

class ListSerieDocumentals extends ListRecords
{
    protected static string $resource = SerieDocumentalResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

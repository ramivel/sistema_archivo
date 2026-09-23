<?php

namespace App\Filament\Resources\Subfondos\Pages;

use App\Filament\Resources\Subfondos\SubfondoResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditSubfondo extends EditRecord
{
    protected static string $resource = SubfondoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}

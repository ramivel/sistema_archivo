<?php

namespace App\Filament\Resources\Perfils\Pages;

use App\Filament\Resources\Perfils\PerfilResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditPerfil extends EditRecord
{
    protected static string $resource = PerfilResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\Procedencias\Pages;

use App\Filament\Resources\Procedencias\ProcedenciaResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditProcedencia extends EditRecord
{
    protected static string $resource = ProcedenciaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}

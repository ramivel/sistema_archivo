<?php

namespace App\Filament\Resources\Transferencias\Pages;

use App\Filament\Resources\Transferencias\TransferenciaResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListTransferencias extends ListRecords
{
    protected static string $resource = TransferenciaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('plantilla')
                ->label('PLANTILLA TRANSFERENCIA EXCEL')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->url(route('transferencias.plantilla-excel')),
            Action::make('regularizar')
                ->label('REGULARIZAR INVENTARIO')
                ->icon('heroicon-o-clipboard-document-list')
                ->color('primary')
                ->url(
                    fn (): string => TransferenciaResource::getUrl('regularizar')
                )
                ->visible(fn (): bool => $this->esEncargadoArchivo()),
        ];
    }

    private function esEncargadoArchivo(): bool
    {
        return User::find(Auth::id())
            ?->perfiles
            ->contains('valor', 'ENCARGADO ARCHIVO') ?? false;
    }
}
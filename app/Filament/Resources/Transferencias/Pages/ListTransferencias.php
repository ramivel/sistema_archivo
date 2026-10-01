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
        $user = Auth::user();
        return [
            Action::make('plantilla')
                ->label('PLANTILLA TRANSFERENCIA')
                ->icon('heroicon-o-document-arrow-down')
                ->size('sm')
                ->color('gray')
                ->url(route('transferencias.plantilla-transferencia-excel')),
            Action::make('nuevaTransferencia')
                ->label('NUEVA TRANSFERENCIA')
                ->icon('heroicon-o-plus')
                ->size('sm')
                ->color('primary')
                ->url(
                    fn (): string => TransferenciaResource::getUrl('create')
                ),
            Action::make('plantillaRegularizacion')
                ->label('PLANTILLA REGULARIZACION')
                ->icon('heroicon-o-document-arrow-down')
                ->size('sm')
                ->color('gray')
                ->url(route('transferencias.plantilla-regularizacion-excel'))
                ->visible(fn (): bool => $user instanceof User && $user->esEncargadoArchivo()),
            Action::make('regularizar')
                ->label('REGULARIZAR TRANSFERENCIA')
                ->icon('heroicon-o-clipboard-document-list')
                ->size('sm')
                ->color('primary')
                ->url(
                    fn (): string => TransferenciaResource::getUrl('regularizar')
                )
                ->visible(fn (): bool => $user instanceof User && $user->esEncargadoArchivo()),
        ];
    }
}
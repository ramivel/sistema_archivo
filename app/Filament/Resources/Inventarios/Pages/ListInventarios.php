<?php

namespace App\Filament\Resources\Inventarios\Pages;

use App\Filament\Resources\Inventarios\InventarioResource;
use App\Filament\Resources\Inventarios\Schemas\InventarioForm;
use App\Models\InventarioExpediente;
use App\Models\User;
use App\Services\InventarioService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListInventarios extends ListRecords
{
    protected static string $resource = InventarioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('nuevo')
                ->label('Nuevo expediente')
                ->icon('heroicon-o-plus')
                ->visible(
                    fn (): bool =>
                        User::find(Auth::id())
                            ?->esEncargadoArchivo() ?? false
                )
                ->schema(InventarioForm::components())
                ->modalHeading('Registrar nuevo expediente')
                ->modalSubmitActionLabel('Guardar expediente')
                ->modalCancelActionLabel('Cancelar')
                ->action(
                    function (array $data): void {
                        try {
                            $expediente = app(InventarioService::class)->crear($data);
                            Notification::make()
                                ->title('Expediente registrado correctamente')
                                ->body("El expediente {$expediente->codigo_inventario} fue registrado correctamente")
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->danger()
                                ->title('No se pudo registrar el expediente')
                                ->body($e->getMessage())
                                ->persistent()
                                ->send();
                        }
                    }
                )
        ];
    }
}
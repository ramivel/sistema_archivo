<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Illuminate\Validation\ValidationException;
use Filament\Notifications\Notification;

class Login extends BaseLogin
{
    /**
     * Campo utilizado para iniciar sesión.
     */
    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('usuario')
            ->label('Usuario')
            ->required()
            ->autocomplete('username')
            ->autofocus();
    }

    /**
     * Credenciales utilizadas por Laravel para autenticar.
     */
    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            'usuario' => $data['usuario'],
            'activo' => true,
            'password' => $data['password'],
        ];
    }

    /**
     * Mensaje de error asociado al campo usuario.
     */
    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.usuario' => 'Las credenciales proporcionadas no son válidas, puede comunicarse con el administrador del sistema.',
        ]);
    }

    public function mount(): void
    {
        parent::mount();
        if (session()->has('error')) {
            Notification::make()
                ->danger()
                ->title('Acceso denegado')
                ->body(session()->pull('error'))
                ->send();
        }
    }
}
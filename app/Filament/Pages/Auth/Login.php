<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Illuminate\Validation\ValidationException;

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
            'password' => $data['password'],
        ];
    }

    /**
     * Mensaje de error asociado al campo usuario.
     */
    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.usuario' => 'Las credenciales proporcionadas no son válidas.',
        ]);
    }
}
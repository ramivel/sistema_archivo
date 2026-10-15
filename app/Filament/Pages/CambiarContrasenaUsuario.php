<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Filament\Pages\Dashboard;

class CambiarContrasenaUsuario extends Page
{
    protected string $view = 'filament.resources.users.pages.cambiar-contrasena-usuario';
    protected static bool $shouldRegisterNavigation = false;
    public ?array $data = [];

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
        $usuario = User::find(Auth::id());
        $this->form->fill([
            'nombres' => trim($usuario->nombres . ' ' . $usuario->apellidos),
            'documento_identidad' => $usuario->documento_identidad,
            'usuario' => $usuario->usuario,
            'email' => $usuario->email,
        ]);
    }

    public static function canAccess(array $parameters = []): bool
    {
        $usuario = User::find(Auth::id());
        return $usuario !== null && $usuario->activo;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información del usuario')
                    ->schema([
                        TextInput::make('nombres')
                            ->label('Nombre completo')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('documento_identidad')
                            ->label('Documento de identidad')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('usuario')
                            ->label('Usuario')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('email')
                            ->label('Correo electrónico')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2),
                Section::make('Cambiar contraseña')
                    ->description('Para cambiar su contraseña, primero debe ingresar la contraseña actual.')
                    ->schema([
                        TextInput::make('password_actual')
                            ->label('Contraseña actual')
                            ->password()
                            ->revealable()
                            ->required()
                            ->dehydrated(),
                        TextInput::make('password')
                            ->label('Nueva contraseña')
                            ->password()
                            ->revealable()
                            ->required()
                            ->rule(
                                Password::min(8)
                                    ->mixedCase()
                                    ->numbers()
                                    ->symbols()
                            )
                            ->validationMessages([
                                'required' => 'La nueva contraseña es obligatoria.',
                                'min' => 'La contraseña debe tener al menos 8 caracteres.',
                                'password.mixed' => 'La contraseña debe contener al menos una letra mayúscula y una letra minúscula.',
                                'password.numbers' => 'La contraseña debe contener al menos un número.',
                                'password.symbols' => 'La contraseña debe contener al menos un carácter especial.',
                            ]),
                        TextInput::make('password_confirmation')
                            ->label('Confirmar nueva contraseña')
                            ->password()
                            ->revealable()
                            ->required()
                            ->same('password')
                            ->validationMessages([
                                'required' => 'La confirmación de contraseña es obligatoria.',
                                'same' => 'Las contraseñas no coinciden. Revise nuevamente.',
                            ]),
                    ])
                    ->columns(1),
            ])
            ->statePath('data');
    }

    public function guardar(): void
    {
        $datos = $this->form->getState();
        $usuario = User::find(Auth::id());
        abort_unless($usuario !== null && $usuario->activo, 403);
        if (! Hash::check($datos['password_actual'], $usuario->password)) {
            $this->addError(
                'data.password_actual',
                'La contraseña actual es incorrecta.'
            );
            return;
        }
        if (Hash::check($datos['password'], $usuario->password)) {
            $this->addError(
                'data.password',
                'La nueva contraseña debe ser diferente de la actual.'
            );
            return;
        }
        $usuario->update([
            'password' => $datos['password'],
            'usuario_actualizacion_id' => Auth::id(),
        ]);
        session()->put(
            'password_hash_' . Auth::getDefaultDriver(),
            $usuario->getAuthPassword()
        );
        Notification::make()
            ->success()
            ->title('Contraseña actualizada')
            ->body('Su contraseña fue cambiada correctamente.')
            ->send();
        $this->redirect(Dashboard::getUrl());
    }
}
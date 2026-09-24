<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero')
                    ->label('N°')
                    ->rowIndex(),
                Tables\Columns\TextColumn::make('nombres')
                    ->label('Nombre')
                    ->formatStateUsing(
                        fn (User $record): string =>
                            trim($record->nombres . ' ' . $record->apellidos)
                    )
                    ->searchable(['nombres', 'apellidos'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('direccion_completa')
                    ->label('Dirección')
                    ->state(function (User $record): string {
                        $partes = [];
                        if ($record->oficina?->sigla)
                            $partes[] = $record->oficina->sigla;
                        if ($record->direccion?->sigla)
                            $partes[] = $record->direccion->sigla;
                        if ($record->area?->sigla)
                            $partes[] = $record->area->sigla;
                        return implode('/', $partes);
                    })
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('usuario')
                    ->label('Usuario')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Correo')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('telefonos')
                    ->label('Teléfono'),
                Tables\Columns\TextColumn::make('perfiles')
                    ->label('Perfil')
                    ->state(
                        fn (User $record): string =>
                            $record->perfiles
                                ->pluck('valor')
                                ->implode(', ')
                    )
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->state(
                        fn (User $record): string => $record->activo
                            ? 'ACTIVO'
                            : 'DESACTIVADO'
                    )
                    ->badge()
                    ->color(
                        fn (User $record): string => $record->activo
                            ? 'success'
                            : 'danger'
                    ),
            ])

            ->toolbarActions([
                CreateAction::make()
                    ->label('Nuevo Usuario')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Nuevo Usuario')
                    ->modalSubmitActionLabel('Guardar')
                    ->modalCancelActionLabel('Cancelar')
                    ->using(function (array $data): User {
                            $primerNombre = explode(' ', trim($data['nombres']))[0];
                            $primerNombre = ucfirst(mb_strtolower($primerNombre, 'UTF-8'));
                            $passwordInicial = $primerNombre . '.' . trim($data['documento_identidad']).'@';
                            $data['password'] = $passwordInicial;
                            $data['usuario_creacion_id'] = Auth::id();
                            $perfiles = $data['perfiles'] ?? [];
                            unset($data['perfiles']);
                            $user = User::create($data);
                            $user->perfiles()->sync($perfiles);
                            Notification::make()
                                ->success()
                                ->title('Usuario creado correctamente')
                                ->body(
                                    "USUARIO: {$user->usuario}\n" .
                                    "CONTRASEÑA: {$passwordInicial}\n\n" .
                                    'La contraseña solamente se mostrará en este momento.'
                                )
                                ->persistent()
                                ->send();
                            return $user;
                        }
                    )
                    ->successNotification(null),
            ])

            ->recordActions([
                Action::make('ver')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Usuario')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->schema([
                        Section::make()
                            ->schema([
                                TextEntry::make('nombres')
                                    ->label('Nombre Completo')
                                    ->formatStateUsing(
                                        fn (User $record): string =>
                                            trim($record->nombres . ' ' . $record->apellidos)
                                    ),
                                TextEntry::make('documento_identidad')
                                    ->label('Documento de identidad')
                                    ->formatStateUsing(
                                        fn (User $record): string =>
                                            trim($record->documento_identidad . ' ' . $record->expedido?->valor)
                                    ),
                                TextEntry::make('direccion_completa')
                                    ->label('Dirección')
                                    ->state(function (User $record): string {
                                        $partes = [];
                                        if ($record->oficina?->valor)
                                            $partes[] = $record->oficina->valor;
                                        if ($record->direccion?->valor)
                                            $partes[] = $record->direccion->valor;
                                        if ($record->area?->valor)
                                            $partes[] = $record->area->valor;
                                        return implode(' - ', $partes);
                                    })
                                    ->columnSpanFull(),
                                TextEntry::make('email')
                                    ->label('Correo electrónico'),
                                TextEntry::make('telefonos')
                                    ->label('Teléfono / Celular / Interno')
                                    ->placeholder('—'),
                                TextEntry::make('usuario')
                                    ->label('Usuario'),
                                TextEntry::make('perfiles')
                                    ->label('Perfiles')
                                    ->state(
                                        fn (User $record): string =>
                                            $record->perfiles
                                                ->pluck('valor')
                                                ->implode(', ')
                                    ),
                            ])
                            ->columns(2),
                    ]),

                EditAction::make()
                    ->label('Editar')
                    ->icon('heroicon-o-pencil-square')
                    ->modalHeading('Editar Usuario')
                    ->modalSubmitActionLabel('Actualizar')
                    ->modalCancelActionLabel('Cancelar')
                    ->after(function (User $record): void {
                        $record->update([
                            'usuario_actualizacion_id' => Auth::id(),
                        ]);
                    })
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Usuario actualizado')
                            ->body(
                                'El usuario fue actualizado correctamente.'
                            )
                    ),

                Action::make('cambiarContrasena')
                    ->label('Cambiar contraseña')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->modalHeading('Cambiar contraseña')
                    ->modalSubmitActionLabel('Guardar')
                    ->modalCancelActionLabel('Cancelar')
                    ->schema([
                        TextInput::make('usuario')
                            ->label('Usuario')
                            ->default(
                                fn (User $record): string =>
                                    $record->usuario
                            )
                            ->disabled()
                            ->dehydrated(false),
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
                                'required' => 'La contraseña es obligatoria.',
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
                            ->minLength(8)
                            ->same('password')
                            ->validationMessages([
                                'required' => 'La confirmación de contraseña es obligatoria.',
                                'same' => 'Las contraseñas no coinciden. Revise nuevamente.',
                            ]),
                    ])
                    ->action(
                        function (
                            User $record,
                            array $data
                        ): void {
                            $record->update([
                                'password' => $data['password'],
                                'usuario_actualizacion_id' => Auth::id(),
                            ]);
                        }
                    )
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Contraseña actualizada')
                            ->body('La contraseña fue actualizada correctamente.')
                    ),

                Action::make('activar')
                    ->label('Activar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(
                        fn (User $record): bool => ! $record->activo
                    )
                    ->requiresConfirmation()
                    ->modalHeading(
                        fn (User $record): string =>
                            'Activar Usuario / ' . $record->usuario
                    )
                    ->modalDescription(
                        '¿Está seguro de activar este usuario?'
                    )
                    ->modalSubmitActionLabel('Activar')
                    ->modalCancelActionLabel('Cancelar')
                    ->action(
                        function (User $record): void {
                            $record->update([
                                'activo' => true,
                                'usuario_actualizacion_id' => Auth::id(),
                            ]);
                        }
                    )
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Usuario activado')
                            ->body(
                                'El usuario fue activado correctamente.'
                            )
                    ),

                Action::make('desactivar')
                    ->label('Desactivar')
                    ->icon('heroicon-o-x-circle')
                    ->color('warning')
                    ->visible(
                        fn (User $record): bool => $record->activo
                    )
                    ->requiresConfirmation()
                    ->modalHeading(
                        fn (User $record): string =>
                            'Desactivar Usuario / ' . $record->usuario
                    )
                    ->modalDescription(
                        '¿Está seguro de desactivar este usuario?'
                    )
                    ->modalSubmitActionLabel('Desactivar')
                    ->modalCancelActionLabel('Cancelar')
                    ->action(
                        function (User $record): void {
                            $record->update([
                                'activo' => false,
                                'usuario_actualizacion_id' => Auth::id(),
                            ]);
                        }
                    )
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Usuario desactivado')
                            ->body(
                                'El usuario fue desactivado correctamente.'
                            )
                    ),

                DeleteAction::make()
                    ->label('Eliminar')
                    ->icon('heroicon-o-trash')
                    ->requiresConfirmation()
                    ->modalHeading(
                        fn (User $record): string =>
                            'Eliminar Usuario / ' . $record->usuario
                    )
                    ->modalDescription('¿Está seguro de eliminar este usuario?')
                    ->modalSubmitActionLabel('Eliminar')
                    ->modalCancelActionLabel('Cancelar')
                    ->using(
                        function (User $record): void {
                            $record->update([
                                'usuario_eliminacion_id' => Auth::id(),
                                'activo' => false,
                            ]);
                            $record->delete();
                        }
                    )
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Usuario eliminado')
                            ->body(
                                'El usuario fue eliminado correctamente.'
                            )
                    ),
            ])

            ->recordAction('ver')
            ->recordUrl(null)
            ->defaultSort('nombres')
            ->defaultPaginationPageOption(500)
            ->paginationPageOptions([
                500,
                1000,
            ])
            ->striped();
    }
}
<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Login;

class RegistrarUltimoAcceso
{
    public function handle(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }
        User::whereKey($event->user->getAuthIdentifier())
            ->update([
                'ultimo_acceso' => now(),
            ]);
    }
}
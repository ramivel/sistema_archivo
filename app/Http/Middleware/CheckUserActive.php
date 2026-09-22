<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUserActive
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $user = $request->user();
        if ($user && ! $user->activo) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect('/admin/login')
                ->with(
                    'error',
                    'La sesión ha finalizado. Por favor, vuelva a iniciar sesión.'
                );
        }
        return $next($request);
    }
}
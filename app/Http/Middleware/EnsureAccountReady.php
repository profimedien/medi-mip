<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Gesperrte Konten abmelden, bei Erstanmeldung zuerst Passwort vergeben lassen */
class EnsureAccountReady
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->status === 'disabled') {
            Auth::logout();

            return redirect()->route('login')->withErrors(['login' => 'Ihr Zugang ist gesperrt.']);
        }

        if ($user && $user->must_change_password && ! $request->routeIs('password.set', 'password.store', 'logout')) {
            return redirect()->route('password.set');
        }

        return $next($request);
    }
}

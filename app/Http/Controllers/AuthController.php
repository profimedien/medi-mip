<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(Request $request): View
    {
        return view('auth.login', ['login' => $request->query('u')]);
    }

    /**
     * Anmeldung mit Benutzername oder E-Mail und Passwort.
     * Beim ersten Mal statt Passwort der Startcode vom Zugangszettel.
     */
    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $key = 'login:'.Str::lower($data['login']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'login' => 'Zu viele Versuche. Bitte in '.RateLimiter::availableIn($key).' Sekunden erneut probieren.',
            ]);
        }

        $user = User::where('username', Str::lower(trim($data['login'])))
            ->orWhere('email', Str::lower(trim($data['login'])))
            ->first();

        $ok = false;
        if ($user && $user->status !== 'disabled') {
            if ($user->start_code && $this->matchesStartCode($user->start_code, $data['password'])) {
                $ok = true;
                $user->forceFill(['must_change_password' => true])->save();
            } elseif ($user->password && Hash::check($data['password'], $user->password)) {
                $ok = true;
            }
        }

        if (! $ok) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['login' => 'Benutzername oder Passwort stimmen nicht.']);
        }

        RateLimiter::clear($key);
        Auth::login($user, $request->boolean('remember', true));
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        return $user->must_change_password
            ? redirect()->route('password.set')
            : redirect()->intended(route('home'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function showSetPassword(): View
    {
        return view('auth.set-password');
    }

    public function setPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [], ['password' => 'Passwort']);

        $request->user()->forceFill([
            'password' => $request->input('password'),
            'must_change_password' => false,
            'start_code' => null,
            'status' => 'active',
        ])->save();

        return redirect()->route('home')->with('status', 'Ihr Passwort ist gespeichert. Willkommen im Portal!');
    }

    private function matchesStartCode(string $stored, string $input): bool
    {
        $normalize = fn (string $v) => preg_replace('/[^A-Z0-9]/', '', Str::upper($v));

        return hash_equals($normalize($stored), $normalize($input));
    }
}

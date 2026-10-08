<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;
use Throwable;

/**
 * Inicio de sesión con Google OAuth 2.0 (Laravel Socialite).
 */
class GoogleController extends Controller
{
    public static function isEnabled(): bool
    {
        return filled(config('services.google.client_id')) && filled(config('services.google.client_secret'));
    }

    public function redirect(): SymfonyRedirect|RedirectResponse
    {
        abort_unless(self::isEnabled(), 404);

        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        abort_unless(self::isEnabled(), 404);

        try {
            $google = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            Log::warning('Error en el callback de Google', ['error' => $e->getMessage()]);

            return redirect()->route('login')->withErrors(['email' => 'No se pudo iniciar sesión con Google. Inténtalo de nuevo.']);
        }

        if (blank($google->getEmail())) {
            return redirect()->route('login')->withErrors(['email' => 'Tu cuenta de Google no tiene un correo asociado.']);
        }

        $user = User::where('google_id', $google->getId())->first()
            ?? User::where('email', $google->getEmail())->first();

        $isNew = $user === null;
        $user ??= new User(['email' => $google->getEmail()]);

        $user->fill([
            'name' => $user->name ?: ($google->getName() ?: $google->getEmail()),
            'google_id' => $google->getId(),
            'avatar' => $user->avatar ?: $google->getAvatar(),
        ]);

        // Google ya verificó el correo: no hace falta OTP.
        $user->email_verified_at ??= now();
        $user->save();

        if (! $user->is_active) {
            return redirect()->route('login')->withErrors(['email' => 'Tu cuenta está desactivada. Contacta con el administrador.']);
        }

        if ($isNew) {
            event(new Registered($user));
            ActivityLogger::log('auth.register', 'Se registró con Google', $user, user: $user);
        }

        Auth::login($user, remember: true);
        request()->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }
}

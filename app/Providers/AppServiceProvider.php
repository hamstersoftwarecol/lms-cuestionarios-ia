<?php

namespace App\Providers;

use App\Models\Announcement;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\Ai\GeminiClient;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(GeminiClient::class, fn () => GeminiClient::fromConfig());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::setLocale(config('app.locale'));

        Gate::define('admin', fn (User $user) => $user->isAdmin());

        RateLimiter::for('ai', fn (Request $request) => Limit::perMinute(15)->by($request->user()?->id ?: $request->ip())
            ->response(function (Request $request) {
                $message = 'Has hecho demasiadas peticiones a la IA. Espera un minuto.';

                return $request->expectsJson()
                    ? response()->json(['fallback' => true, 'message' => $message], 429)
                    : back()->with('error', $message);
            }));

        $this->registerAuditListeners();
        $this->shareLayoutData();
    }

    /**
     * Registro de auditoría de los eventos de autenticación.
     */
    private function registerAuditListeners(): void
    {
        Event::listen(function (Login $event) {
            $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
            ActivityLogger::log('auth.login', 'Inició sesión', user: $event->user);
        });

        Event::listen(function (Logout $event) {
            if ($event->user) {
                ActivityLogger::log('auth.logout', 'Cerró sesión', user: $event->user);
            }
        });

        Event::listen(function (Failed $event) {
            ActivityLogger::log('auth.failed', 'Intento de inicio de sesión fallido', properties: [
                'email' => $event->credentials['email'] ?? null,
            ], user: $event->user);
        });

        Event::listen(function (Registered $event) {
            if ($event->user->google_id === null) {
                ActivityLogger::log('auth.register', 'Se registró con correo y contraseña', $event->user, user: $event->user);
            }
        });

        Event::listen(fn (Verified $event) => ActivityLogger::log('auth.verified', 'Verificó su correo con código OTP', user: $event->user));
        Event::listen(fn (PasswordReset $event) => ActivityLogger::log('auth.password_reset', 'Restableció su contraseña', user: $event->user));
    }

    /**
     * Notificaciones no leídas y anuncios vigentes para el layout principal.
     */
    private function shareLayoutData(): void
    {
        View::composer('layouts.app', function ($view) {
            $user = request()->user();

            if (! $user) {
                return;
            }

            $view->with([
                'unreadNotifications' => $user->unreadNotifications()->latest()->limit(8)->get(),
                'unreadCount' => $user->unreadNotifications()->count(),
                'announcements' => Announcement::current()->latest()->limit(3)->get(),
            ]);
        });
    }
}

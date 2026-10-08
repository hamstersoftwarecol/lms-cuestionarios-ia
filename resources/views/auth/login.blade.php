<x-guest-layout title="Iniciar sesión">
    <h1 class="text-2xl font-bold">Bienvenido de nuevo</h1>
    <p class="mt-1 text-sm muted">Inicia sesión para continuar estudiando.</p>

    <x-auth-session-status class="mt-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <x-input-label for="email" value="Correo electrónico" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div>
            <div class="flex items-center justify-between">
                <x-input-label for="password" value="Contraseña" />
                @if (Route::has('password.request'))
                    <a class="mb-1 text-xs link" href="{{ route('password.request') }}">¿La olvidaste?</a>
                @endif
            </div>
            <x-text-input id="password" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <label for="remember_me" class="inline-flex items-center gap-2 text-sm muted">
            <input id="remember_me" type="checkbox" class="checkbox" name="remember">
            Mantener la sesión iniciada
        </label>

        <x-primary-button class="w-full">Iniciar sesión</x-primary-button>
    </form>

    @include('auth.partials.google-button')

    <p class="mt-6 text-center text-sm muted">
        ¿No tienes cuenta? <a href="{{ route('register') }}" class="link">Regístrate gratis</a>
    </p>
</x-guest-layout>

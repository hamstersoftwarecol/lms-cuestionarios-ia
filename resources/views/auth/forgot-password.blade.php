<x-guest-layout title="Recuperar contraseña">
    <h1 class="text-2xl font-bold">¿Olvidaste tu contraseña?</h1>
    <p class="mt-1 text-sm muted">Indica tu correo y te enviaremos un enlace para crear una nueva.</p>

    <x-auth-session-status class="mt-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4">
        @csrf
        <div>
            <x-input-label for="email" value="Correo electrónico" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" />
        </div>
        <x-primary-button class="w-full">Enviar enlace de recuperación</x-primary-button>
    </form>

    <p class="mt-6 text-center text-sm"><a href="{{ route('login') }}" class="link">Volver a iniciar sesión</a></p>
</x-guest-layout>

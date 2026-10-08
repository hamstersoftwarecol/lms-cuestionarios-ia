<x-guest-layout title="Crear cuenta">
    <h1 class="text-2xl font-bold">Crea tu cuenta</h1>
    <p class="mt-1 text-sm muted">Te enviaremos un código de 6 dígitos para verificar tu correo.</p>

    <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <x-input-label for="name" value="Nombre completo" />
            <x-text-input id="name" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" value="Correo electrónico" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="password" value="Contraseña" />
                <x-text-input id="password" type="password" name="password" required autocomplete="new-password" />
            </div>
            <div>
                <x-input-label for="password_confirmation" value="Confirmar" />
                <x-text-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" />
            </div>
        </div>
        <x-input-error :messages="$errors->get('password')" />

        <x-primary-button class="w-full">Crear cuenta</x-primary-button>
    </form>

    @include('auth.partials.google-button')

    <p class="mt-6 text-center text-sm muted">
        ¿Ya tienes cuenta? <a href="{{ route('login') }}" class="link">Inicia sesión</a>
    </p>
</x-guest-layout>

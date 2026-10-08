<x-guest-layout title="Confirmar contraseña">
    <h1 class="text-2xl font-bold">Confirma tu contraseña</h1>
    <p class="mt-1 text-sm muted">Es una zona segura de la aplicación. Confirma tu contraseña para continuar.</p>

    <form method="POST" action="{{ route('password.confirm') }}" class="mt-6 space-y-4">
        @csrf
        <div>
            <x-input-label for="password" value="Contraseña" />
            <x-text-input id="password" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>
        <x-primary-button class="w-full">Confirmar</x-primary-button>
    </form>
</x-guest-layout>

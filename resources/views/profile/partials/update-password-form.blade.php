<section>
    <header>
        <h2 class="card-title">{{ $user->password ? 'Cambiar contraseña' : 'Crear contraseña' }}</h2>
        <p class="mt-1 text-sm muted">
            {{ $user->password ? 'Usa una contraseña larga y aleatoria para mantener tu cuenta segura.' : 'Entraste con Google. Crea una contraseña si también quieres iniciar sesión con tu correo.' }}
        </p>
    </header>

    <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-4">
        @csrf
        @method('PUT')

        @if ($user->password)
            <div>
                <x-input-label for="update_password_current_password" value="Contraseña actual" />
                <x-text-input id="update_password_current_password" name="current_password" type="password" autocomplete="current-password" />
                <x-input-error :messages="$errors->updatePassword->get('current_password')" />
            </div>
        @endif

        <div>
            <x-input-label for="update_password_password" value="Nueva contraseña" />
            <x-text-input id="update_password_password" name="password" type="password" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password')" />
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" value="Confirmar contraseña" />
            <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" />
        </div>

        <x-primary-button>Guardar contraseña</x-primary-button>
    </form>
</section>

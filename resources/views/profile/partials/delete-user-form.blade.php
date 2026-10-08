<section class="space-y-4">
    <header>
        <h2 class="card-title text-rose-600">Eliminar cuenta</h2>
        <p class="mt-1 text-sm muted">Se borrarán de forma permanente tus documentos, cuestionarios, intentos, planes y logros.</p>
    </header>

    <x-danger-button type="button" x-data="" x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')">Eliminar mi cuenta</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="POST" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('DELETE')

            <h2 class="text-lg font-semibold">¿Seguro que quieres eliminar tu cuenta?</h2>
            <p class="mt-1 text-sm muted">
                {{ $user->password ? 'Introduce tu contraseña para confirmar.' : 'Escribe ELIMINAR para confirmar.' }}
            </p>

            <div class="mt-6">
                <x-input-label for="password" :value="$user->password ? 'Contraseña' : 'Confirmación'" class="sr-only" />
                <x-text-input id="password" name="password" :type="$user->password ? 'password' : 'text'" :placeholder="$user->password ? 'Contraseña' : 'ELIMINAR'" />
                <x-input-error :messages="$errors->userDeletion->get('password')" />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">Cancelar</x-secondary-button>
                <x-danger-button>Eliminar cuenta</x-danger-button>
            </div>
        </form>
    </x-modal>
</section>

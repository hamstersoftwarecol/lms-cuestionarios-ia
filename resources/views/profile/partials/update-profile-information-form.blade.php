<section>
    <header>
        <h2 class="card-title">Información del perfil</h2>
        <p class="mt-1 text-sm muted">Actualiza tu nombre y tu correo electrónico.</p>
    </header>

    <form id="send-verification" method="POST" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="POST" action="{{ route('profile.update') }}" class="mt-6 space-y-4">
        @csrf
        @method('PATCH')

        <div class="flex items-center gap-4">
            <x-avatar :user="$user" class="h-14 w-14 text-base" />
            <div class="text-sm">
                <p class="font-medium">{{ $user->role->label() }}</p>
                <p class="muted">Miembro desde {{ $user->created_at->translatedFormat('F Y') }}</p>
                @if ($user->google_id) <x-pill color="sky">Cuenta vinculada con Google</x-pill> @endif
            </div>
        </div>

        <div>
            <x-input-label for="name" value="Nombre" />
            <x-text-input id="name" name="name" type="text" :value="old('name', $user->name)" required autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" value="Correo electrónico" />
            <x-text-input id="email" name="email" type="email" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
            <p class="mt-1 text-xs muted">Si cambias el correo tendrás que verificarlo de nuevo con un código.</p>

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <p class="mt-2 text-sm">
                    Tu correo no está verificado.
                    <button form="send-verification" class="link">Enviar un nuevo código</button>
                </p>
            @endif
        </div>

        <x-primary-button>Guardar</x-primary-button>
    </form>
</section>

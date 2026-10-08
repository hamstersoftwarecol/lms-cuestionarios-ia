<x-guest-layout title="Verifica tu correo">
    <div class="text-center">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-3xl dark:bg-brand-500/10">✉️</div>
        <h1 class="mt-4 text-2xl font-bold">Verifica tu correo</h1>
        <p class="mt-2 text-sm muted">
            Enviamos un código de 6 dígitos a <strong class="text-slate-700 dark:text-slate-200">{{ auth()->user()->email }}</strong>.
            Caduca en {{ config('lms.otp.ttl_minutes') }} minutos.
        </p>
    </div>

    @if (session('status') === 'verification-code-sent')
        <x-auth-session-status class="mt-4" status="Te enviamos un nuevo código de verificación." />
    @endif

    <form method="POST" action="{{ route('verification.otp') }}" class="mt-6 space-y-4"
          x-data="{ code: '' }">
        @csrf
        <div>
            <x-input-label for="code" value="Código de verificación" class="sr-only" />
            <input id="code" name="code" x-model="code" inputmode="numeric" autocomplete="one-time-code" maxlength="7" required autofocus
                   placeholder="000000"
                   class="input text-center font-mono text-3xl tracking-[0.5em]" />
            <x-input-error :messages="$errors->get('code')" class="text-center" />
        </div>
        <x-primary-button class="w-full" x-bind:disabled="code.replace(/\D/g, '').length !== 6">Verificar</x-primary-button>
    </form>

    <div class="mt-6 flex items-center justify-between text-sm">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="link">Reenviar código</button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="muted hover:underline">Cerrar sesión</button>
        </form>
    </div>
</x-guest-layout>

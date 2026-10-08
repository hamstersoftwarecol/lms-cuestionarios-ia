<section>
    <header>
        <h2 class="card-title">Sesiones activas</h2>
        <p class="mt-1 text-sm muted">Dispositivos con sesión iniciada. Cierra las que no reconozcas.</p>
    </header>

    @if (! $sessionsSupported)
        <p class="mt-4 text-sm muted">La gestión de sesiones requiere <code>SESSION_DRIVER=database</code>.</p>
    @else
        <ul class="mt-6 space-y-3">
            @foreach ($sessions as $session)
                <li class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-800">
                        @if ($session->is_mobile)
                            <x-heroicon-o-device-phone-mobile class="h-5 w-5 text-slate-500" />
                        @else
                            <x-heroicon-o-computer-desktop class="h-5 w-5 text-slate-500" />
                        @endif
                    </div>
                    <div class="min-w-0 flex-1 text-sm">
                        <p class="font-medium">{{ $session->device }}</p>
                        <p class="text-xs muted">
                            {{ $session->ip_address }} ·
                            @if ($session->is_current)
                                <span class="font-semibold text-emerald-600">Este dispositivo</span>
                            @else
                                activa {{ $session->last_active_at->diffForHumans() }}
                            @endif
                        </p>
                    </div>
                    @unless ($session->is_current)
                        <form method="POST" action="{{ route('sessions.destroy', $session->id) }}">
                            @csrf @method('DELETE')
                            <button class="btn-ghost btn-sm text-rose-600">Cerrar</button>
                        </form>
                    @endunless
                </li>
            @endforeach
        </ul>

        @if ($sessions->count() > 1)
            <form method="POST" action="{{ route('sessions.others') }}" class="mt-6 space-y-3 border-t border-slate-100 pt-4 dark:border-slate-800">
                @csrf @method('DELETE')
                @if ($user->password)
                    <div>
                        <x-input-label for="sessions_password" value="Confirma tu contraseña" />
                        <x-text-input id="sessions_password" name="password" type="password" autocomplete="current-password" required />
                        <x-input-error :messages="$errors->sessions->get('password')" />
                    </div>
                @endif
                <button class="btn-danger btn-sm">Cerrar sesión en los demás dispositivos</button>
            </form>
        @endif
    @endif
</section>

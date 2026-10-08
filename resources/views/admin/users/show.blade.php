<x-app-layout :title="$user->name">
    <x-page-header :title="$user->name" :subtitle="$user->email">
        <a href="{{ route('admin.users.index') }}" class="btn-ghost"><x-heroicon-o-arrow-left class="h-4 w-4" /> Volver</a>
        <x-confirm-form :action="route('admin.users.logout', $user)" message="¿Cerrar todas las sesiones de este usuario?">
            <button class="btn-secondary"><x-heroicon-o-arrow-right-start-on-rectangle class="h-4 w-4" /> Forzar cierre de sesión</button>
        </x-confirm-form>
        @unless ($user->is(auth()->user()))
            <x-confirm-form :action="route('admin.users.destroy', $user)" method="DELETE" message="¿Eliminar definitivamente este usuario y todos sus datos?">
                <button class="btn-danger"><x-heroicon-o-trash class="h-4 w-4" /> Eliminar</button>
            </x-confirm-form>
        @endunless
    </x-page-header>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
        <x-stat-card label="Puntos" :value="number_format($user->points)" />
        <x-stat-card label="Racha" :value="$user->current_streak.' / '.$user->longest_streak" hint="actual / mejor" />
        <x-stat-card label="Documentos" :value="$user->notes_count" />
        <x-stat-card label="Cuestionarios" :value="$user->quizzes_count" :hint="$user->attempts_count.' intentos'" />
        <x-stat-card label="Insignias" :value="$user->badges_count" :hint="$user->study_groups_count.' grupos'" />
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="card card-body space-y-4">
            @csrf @method('PUT')
            <h2 class="card-title">Datos y permisos</h2>
            <div>
                <x-input-label for="name" value="Nombre" />
                <x-text-input id="name" name="name" :value="old('name', $user->name)" required />
                <x-input-error :messages="$errors->get('name')" />
            </div>
            <div>
                <x-input-label for="email" value="Correo" />
                <x-text-input id="email" type="email" name="email" :value="old('email', $user->email)" required />
                <x-input-error :messages="$errors->get('email')" />
            </div>
            <div>
                <x-input-label for="role" value="Rol" />
                <select id="role" name="role" class="input">
                    @foreach ($roles as $role)
                        <option value="{{ $role->value }}" @selected($user->role === $role)>{{ $role->label() }}</option>
                    @endforeach
                </select>
            </div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" class="checkbox" @checked($user->is_active)> Cuenta activa</label>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="verified" value="1" class="checkbox" @checked($user->hasVerifiedEmail())> Correo verificado</label>
            <p class="text-xs muted">Desactivar una cuenta cierra todas sus sesiones al instante.</p>
            <x-primary-button>Guardar cambios</x-primary-button>
            <dl class="space-y-1 border-t border-slate-100 pt-4 text-xs muted dark:border-slate-800">
                <div>Registro: {{ $user->created_at->format('d/m/Y H:i') }}</div>
                <div>Último acceso: {{ $user->last_login_at?->format('d/m/Y H:i') ?? 'Nunca' }}</div>
                <div>Inicio con Google: {{ $user->google_id ? 'Sí' : 'No' }}</div>
            </dl>
        </form>

        <div class="space-y-6 lg:col-span-2">
            <div class="card card-body">
                <h2 class="card-title">Sesiones ({{ $sessions->count() }})</h2>
                <ul class="mt-3 divide-y divide-slate-100 text-sm dark:divide-slate-800">
                    @forelse ($sessions as $session)
                        <li class="flex items-center justify-between gap-2 py-2">
                            <span>{{ $session->device }} · {{ $session->ip_address }} · {{ $session->last_active_at->diffForHumans() }} @if ($session->is_expired) <x-pill color="slate">Caducada</x-pill> @endif</span>
                            @unless ($session->is_current)
                                <form method="POST" action="{{ route('admin.sessions.destroy', $session->id) }}">
                                    @csrf @method('DELETE')
                                    <button class="btn-ghost btn-sm text-rose-600">Cerrar</button>
                                </form>
                            @endunless
                        </li>
                    @empty
                        <li class="py-2 muted">Sin sesiones abiertas.</li>
                    @endforelse
                </ul>
            </div>

            <div class="card card-body">
                <h2 class="card-title">Últimos intentos</h2>
                <ul class="mt-3 divide-y divide-slate-100 text-sm dark:divide-slate-800">
                    @forelse ($attempts as $attempt)
                        <li class="flex justify-between gap-2 py-2"><span class="truncate">{{ $attempt->quiz?->title }}</span><span class="shrink-0">{{ round($attempt->percentage) }}% · {{ $attempt->completed_at->format('d/m H:i') }}</span></li>
                    @empty
                        <li class="py-2 muted">Sin intentos.</li>
                    @endforelse
                </ul>
            </div>

            <div class="card card-body">
                <h2 class="card-title">Actividad</h2>
                <ul class="mt-3 divide-y divide-slate-100 text-sm dark:divide-slate-800">
                    @forelse ($activity as $log)
                        <li class="flex justify-between gap-2 py-2"><span class="truncate">{{ $log->description }}</span><span class="shrink-0 text-xs muted">{{ $log->created_at->format('d/m H:i') }} · {{ $log->ip_address }}</span></li>
                    @empty
                        <li class="py-2 muted">Sin actividad registrada.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>

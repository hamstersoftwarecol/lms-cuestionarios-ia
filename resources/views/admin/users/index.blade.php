<x-app-layout title="Usuarios">
    <x-page-header title="Gestión de usuarios" :subtitle="$users->count().' usuarios registrados'" />

    <div class="card card-body">
        <table data-datatable data-export-title="Usuarios" data-order='[[5, "desc"]]' class="hover w-full">
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Correo</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Puntos</th>
                    <th>Registro</th>
                    <th>Último acceso</th>
                    <th>Docs / Cuest. / Intentos</th>
                    <th class="no-export no-sort">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    <tr>
                        <td>
                            <div class="inline-flex items-center gap-2 align-middle">
                                <x-avatar :user="$user" class="h-8 w-8" />
                                <span class="font-medium">{{ $user->name }}</span>
                            </div>
                        </td>
                        <td>{{ $user->email }} @if ($user->google_id) <span title="Google">🔗</span> @endif</td>
                        <td><x-pill :color="$user->isAdmin() ? 'violet' : 'slate'">{{ $user->role->label() }}</x-pill></td>
                        <td>
                            @if (! $user->is_active)
                                <x-pill color="rose">Desactivado</x-pill>
                            @elseif (! $user->hasVerifiedEmail())
                                <x-pill color="amber">Sin verificar</x-pill>
                            @else
                                <x-pill color="emerald">Activo</x-pill>
                            @endif
                        </td>
                        <td>{{ $user->points }}</td>
                        <td data-order="{{ $user->created_at->timestamp }}">{{ $user->created_at->format('d/m/Y') }}</td>
                        <td data-order="{{ $user->last_login_at?->timestamp ?? 0 }}">{{ $user->last_login_at?->diffForHumans() ?? 'Nunca' }}</td>
                        <td>{{ $user->notes_count }} / {{ $user->quizzes_count }} / {{ $user->attempts_count }}</td>
                        <td>
                            <div class="flex gap-1">
                                <a href="{{ route('admin.users.show', $user) }}" class="btn-secondary btn-sm">Gestionar</a>
                                <form method="POST" action="{{ route('admin.users.logout', $user) }}" onsubmit="return confirm('¿Cerrar todas las sesiones de este usuario?')">
                                    @csrf
                                    <button class="btn-ghost btn-sm" title="Forzar cierre de sesión"><x-heroicon-o-arrow-right-start-on-rectangle class="h-4 w-4" /></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-app-layout>

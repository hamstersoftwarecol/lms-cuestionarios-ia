<x-app-layout title="Sesiones">
    <x-page-header title="Monitorización de sesiones" :subtitle="'Sesiones con usuario identificado · duración máxima: '.$lifetime.' min'" />

    @unless ($supported)
        <div class="rounded-2xl border px-4 py-3 text-sm {{ \App\Support\Palette::alert('warning') }}">
            La monitorización requiere <code>SESSION_DRIVER=database</code> en el archivo <code>.env</code>.
        </div>
    @endunless

    <div class="card card-body">
        <table data-datatable data-export-title="Sesiones activas" data-order='[[4, "desc"]]' class="hover w-full">
            <thead>
                <tr><th>Usuario</th><th>Dispositivo</th><th>IP</th><th>Estado</th><th>Última actividad</th><th class="no-export no-sort">Acciones</th></tr>
            </thead>
            <tbody>
                @foreach ($sessions as $session)
                    <tr>
                        <td>
                            @if ($session->user)
                                <a href="{{ route('admin.users.show', $session->user) }}" class="font-medium hover:text-brand-600">{{ $session->user->name }}</a>
                                <span class="block text-xs muted">{{ $session->user->email }}</span>
                            @else
                                <span class="muted">Usuario eliminado</span>
                            @endif
                        </td>
                        <td>{{ $session->is_mobile ? '📱' : '💻' }} {{ $session->device }}</td>
                        <td>{{ $session->ip_address }}</td>
                        <td>
                            @if ($session->is_current)
                                <x-pill color="indigo">Tu sesión</x-pill>
                            @elseif ($session->is_expired)
                                <x-pill color="slate">Caducada</x-pill>
                            @else
                                <x-pill color="emerald">Activa</x-pill>
                            @endif
                        </td>
                        <td data-order="{{ $session->last_activity }}">{{ $session->last_active_at->format('d/m/Y H:i') }} <span class="text-xs muted">({{ $session->last_active_at->diffForHumans() }})</span></td>
                        <td>
                            @unless ($session->is_current)
                                <div class="flex gap-1">
                                    <form method="POST" action="{{ route('admin.sessions.destroy', $session->id) }}" onsubmit="return confirm('¿Cerrar esta sesión?')">
                                        @csrf @method('DELETE')
                                        <button class="btn-secondary btn-sm">Cerrar sesión</button>
                                    </form>
                                    @if ($session->user)
                                        <form method="POST" action="{{ route('admin.users.logout', $session->user) }}" onsubmit="return confirm('¿Cerrar TODAS las sesiones de este usuario?')">
                                            @csrf
                                            <button class="btn-ghost btn-sm text-rose-600">Todas</button>
                                        </form>
                                    @endif
                                </div>
                            @endunless
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-app-layout>

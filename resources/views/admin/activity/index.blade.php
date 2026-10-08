<x-app-layout title="Auditoría">
    <x-page-header title="Registro de auditoría" subtitle="Acciones de los usuarios y administradores con IP y dispositivo.">
        <form method="GET" class="flex flex-wrap gap-2">
            <select name="action" class="input w-auto" onchange="this.form.submit()">
                <option value="">Todas las acciones</option>
                @foreach ($actions as $action)
                    <option value="{{ $action }}" @selected(request('action') === $action)>{{ $action }}</option>
                @endforeach
            </select>
            <select name="days" class="input w-auto" onchange="this.form.submit()">
                @foreach ([1 => 'Últimas 24 h', 7 => 'Últimos 7 días', 30 => 'Últimos 30 días', 90 => 'Últimos 90 días'] as $value => $label)
                    <option value="{{ $value }}" @selected($days === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </form>
    </x-page-header>

    <div class="card card-body">
        <table data-datatable data-export-title="Auditoría" data-order='[[0, "desc"]]' data-page-length="25" class="hover w-full">
            <thead>
                <tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th>Descripción</th><th>IP</th><th>Dispositivo</th></tr>
            </thead>
            <tbody>
                @foreach ($logs as $log)
                    <tr>
                        <td data-order="{{ $log->created_at->timestamp }}">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                        <td>{{ $log->user?->name ?? 'Sistema' }}<span class="block text-xs muted">{{ $log->user?->email }}</span></td>
                        <td><code class="text-xs">{{ $log->action }}</code></td>
                        <td class="whitespace-normal">{{ $log->description }}</td>
                        <td>{{ $log->ip_address ?? '—' }}</td>
                        <td>{{ \App\Support\UserAgent::describe($log->user_agent) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-app-layout>

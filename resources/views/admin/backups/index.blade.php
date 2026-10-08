<x-app-layout title="Copias de seguridad">
    <x-page-header title="Copias de seguridad" subtitle="Crea, descarga y restaura copias de la base de datos SQLite.">
        @if ($supported)
            <form method="POST" action="{{ route('admin.backups.store') }}">
                @csrf
                <button class="btn-primary"><x-heroicon-o-plus class="h-5 w-5" /> Crear copia ahora</button>
            </form>
        @endif
    </x-page-header>

    @unless ($supported)
        <div class="rounded-2xl border px-4 py-3 text-sm {{ \App\Support\Palette::alert('warning') }}">
            Las copias integradas solo están disponibles con una base de datos SQLite en archivo (<code>DB_CONNECTION=sqlite</code>).
            Para MySQL o PostgreSQL usa las herramientas nativas (<code>mysqldump</code>, <code>pg_dump</code>).
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-3">
            <x-stat-card label="Base de datos actual" :value="$databaseSize ? \Illuminate\Support\Number::fileSize($databaseSize) : '—'" icon="circle-stack" color="indigo" />
            <x-stat-card label="Copias guardadas" :value="$backups->count()" icon="document-duplicate" color="emerald" :hint="'Se conservan las '.config('lms.backups.keep').' más recientes automáticas'" />
            <x-stat-card label="Copia automática" value="Diaria · 02:00" icon="clock" color="amber" hint="php artisan schedule:work" />
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="card card-body lg:col-span-2">
                <table data-datatable data-export-title="Copias de seguridad" data-order='[[1, "desc"]]' class="display w-full">
                    <thead><tr><th>Archivo</th><th>Fecha</th><th>Tamaño</th><th class="no-export no-sort">Acciones</th></tr></thead>
                    <tbody>
                        @foreach ($backups as $backup)
                            <tr>
                                <td class="font-mono text-xs">{{ $backup['name'] }}</td>
                                <td data-order="{{ $backup['created_at']->timestamp }}">{{ $backup['created_at']->format('d/m/Y H:i') }}</td>
                                <td data-order="{{ $backup['size'] }}">{{ \Illuminate\Support\Number::fileSize($backup['size']) }}</td>
                                <td>
                                    <div class="flex flex-wrap gap-1" x-data="{ restoring: false }">
                                        <a href="{{ route('admin.backups.download', $backup['name']) }}" class="btn-secondary btn-sm" title="Descargar"><x-heroicon-o-arrow-down-tray class="h-4 w-4" /></a>
                                        <button type="button" class="btn-secondary btn-sm" @click="restoring = ! restoring">Restaurar</button>
                                        <x-confirm-form :action="route('admin.backups.destroy', $backup['name'])" method="DELETE" message="¿Eliminar esta copia?">
                                            <button class="btn-ghost btn-sm text-rose-600" title="Eliminar"><x-heroicon-o-trash class="h-4 w-4" /></button>
                                        </x-confirm-form>
                                        <form x-show="restoring" x-cloak method="POST" action="{{ route('admin.backups.restore', $backup['name']) }}" class="mt-2 flex w-full gap-2">
                                            @csrf
                                            <input name="confirmation" class="input py-1 text-xs" placeholder="Escribe RESTAURAR" required>
                                            <button class="btn-danger btn-sm">Confirmar</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <x-input-error :messages="$errors->get('confirmation')" class="mt-2" />
            </div>

            <div class="space-y-6">
                <form method="POST" action="{{ route('admin.backups.upload') }}" enctype="multipart/form-data" class="card card-body space-y-3">
                    @csrf
                    <h2 class="card-title">Subir una copia</h2>
                    <p class="text-sm muted">Sube un archivo <code>.sqlite</code> descargado previamente para poder restaurarlo.</p>
                    <input type="file" name="backup" accept=".sqlite,.db" required class="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-brand-700 dark:file:bg-brand-500/10 dark:file:text-brand-300">
                    <x-input-error :messages="$errors->get('backup')" />
                    <button class="btn-secondary w-full"><x-heroicon-o-arrow-up-tray class="h-4 w-4" /> Subir</button>
                </form>
                <div class="rounded-2xl border px-4 py-3 text-sm {{ \App\Support\Palette::alert('warning') }}">
                    <p class="font-semibold">Antes de restaurar</p>
                    <p class="mt-1">La restauración reemplaza toda la base de datos. Se crea automáticamente una copia «pre-restore» del estado actual. Puede que tengas que volver a iniciar sesión.</p>
                </div>
            </div>
        </div>
    @endunless
</x-app-layout>

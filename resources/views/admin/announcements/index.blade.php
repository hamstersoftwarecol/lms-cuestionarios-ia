<x-app-layout title="Anuncios">
    <x-page-header title="Anuncios del sistema" subtitle="Mensajes que aparecen en la parte superior del panel de todos los usuarios.">
        <a href="{{ route('admin.announcements.create') }}" class="btn-primary"><x-heroicon-o-plus class="h-5 w-5" /> Nuevo anuncio</a>
    </x-page-header>

    <div class="space-y-4">
        @forelse ($announcements as $announcement)
            @php($live = $announcement->is_active && (! $announcement->starts_at || $announcement->starts_at->isPast()) && (! $announcement->ends_at || $announcement->ends_at->isFuture()))
            <div class="card card-body flex flex-col gap-4 sm:flex-row sm:items-start">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="font-semibold">{{ $announcement->title }}</h2>
                        <x-pill :color="['info' => 'sky', 'success' => 'emerald', 'warning' => 'amber', 'danger' => 'rose'][$announcement->type] ?? 'slate'">{{ $announcement->typeLabel() }}</x-pill>
                        <x-pill :color="$live ? 'emerald' : 'slate'">{{ $live ? 'Visible' : 'No visible' }}</x-pill>
                    </div>
                    <p class="mt-2 whitespace-pre-line text-sm muted">{{ $announcement->body }}</p>
                    <p class="mt-2 text-xs text-slate-400">
                        {{ $announcement->author?->name ?? 'Sistema' }} · {{ $announcement->created_at->format('d/m/Y H:i') }}
                        @if ($announcement->starts_at) · desde {{ $announcement->starts_at->format('d/m/Y H:i') }} @endif
                        @if ($announcement->ends_at) · hasta {{ $announcement->ends_at->format('d/m/Y H:i') }} @endif
                    </p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('admin.announcements.edit', $announcement) }}" class="btn-secondary btn-sm">Editar</a>
                    <x-confirm-form :action="route('admin.announcements.destroy', $announcement)" method="DELETE" message="¿Eliminar este anuncio?">
                        <button class="btn-ghost btn-sm text-rose-600"><x-heroicon-o-trash class="h-4 w-4" /></button>
                    </x-confirm-form>
                </div>
            </div>
        @empty
            <x-empty-state icon="megaphone" title="No hay anuncios" description="Publica un anuncio para informar a todos los usuarios." />
        @endforelse
    </div>
</x-app-layout>

<x-app-layout title="Mis documentos">
    <x-page-header title="Mis documentos" subtitle="Organiza tus apuntes en carpetas y etiquetas y genera cuestionarios con IA.">
        <a href="{{ route('notes.create') }}" class="btn-primary"><x-heroicon-o-plus class="h-5 w-5" /> Nuevo documento</a>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-4">
        {{-- Carpetas y etiquetas --}}
        <aside class="space-y-6 lg:col-span-1">
            <div class="card card-body" x-data="{ creating: false }">
                <div class="flex items-center justify-between">
                    <h2 class="card-title">Carpetas</h2>
                    <button type="button" class="btn-ghost p-1" @click="creating = ! creating" aria-label="Nueva carpeta"><x-heroicon-o-plus class="h-4 w-4" /></button>
                </div>

                <form x-show="creating" x-cloak method="POST" action="{{ route('folders.store') }}" class="mt-3 space-y-2">
                    @csrf
                    <input name="name" class="input" placeholder="Nombre de la carpeta" required maxlength="60">
                    <div class="flex flex-wrap gap-2">
                        @foreach (\App\Models\Folder::COLORS as $color)
                            <label class="cursor-pointer">
                                <input type="radio" name="color" value="{{ $color }}" class="peer sr-only" @checked($loop->first)>
                                <span class="block h-6 w-6 rounded-full ring-2 ring-transparent ring-offset-2 peer-checked:ring-slate-400 dark:ring-offset-slate-900 {{ \App\Support\Palette::dot($color) }}"></span>
                            </label>
                        @endforeach
                    </div>
                    <button class="btn-primary btn-sm w-full">Crear carpeta</button>
                </form>

                <nav class="mt-3 space-y-1 text-sm">
                    <a href="{{ route('notes.index') }}" @class(['nav-item py-1.5', 'nav-item-active' => ! request()->hasAny(['folder', 'tag', 'favorites'])])>
                        <x-heroicon-o-folder-open class="h-4 w-4" /> <span class="flex-1">Todos</span> <span class="text-xs muted">{{ $totalNotes }}</span>
                    </a>
                    <a href="{{ route('notes.index', ['favorites' => 1]) }}" @class(['nav-item py-1.5', 'nav-item-active' => request()->boolean('favorites')])>
                        <x-heroicon-o-heart class="h-4 w-4" /> <span class="flex-1">Favoritos</span>
                    </a>
                    @foreach ($folders as $folder)
                        <div x-data="{ editing: false }" class="group">
                            <div class="flex items-center">
                                <a href="{{ route('notes.index', ['folder' => $folder->id]) }}" @class(['nav-item flex-1 py-1.5', 'nav-item-active' => request('folder') == $folder->id])>
                                    <span class="h-2.5 w-2.5 rounded-full {{ \App\Support\Palette::dot($folder->color) }}"></span>
                                    <span class="flex-1 truncate">{{ $folder->name }}</span>
                                    <span class="text-xs muted">{{ $folder->notes_count }}</span>
                                </a>
                                <button type="button" class="p-1 text-slate-400 opacity-100 hover:text-slate-600 lg:opacity-0 lg:group-hover:opacity-100" @click="editing = ! editing" aria-label="Editar carpeta">
                                    <x-heroicon-o-pencil-square class="h-4 w-4" />
                                </button>
                            </div>
                            <div x-show="editing" x-cloak class="mt-2 space-y-2 rounded-xl bg-slate-50 p-2 dark:bg-slate-800/60">
                                <form method="POST" action="{{ route('folders.update', $folder) }}" class="space-y-2">
                                    @csrf @method('PUT')
                                    <input name="name" value="{{ $folder->name }}" class="input py-1.5" required maxlength="60">
                                    <select name="color" class="input py-1.5">
                                        @foreach (\App\Models\Folder::COLORS as $color)
                                            <option value="{{ $color }}" @selected($folder->color === $color)>{{ ucfirst($color) }}</option>
                                        @endforeach
                                    </select>
                                    <button class="btn-primary btn-sm w-full">Guardar</button>
                                </form>
                                <x-confirm-form :action="route('folders.destroy', $folder)" method="DELETE" message="¿Eliminar la carpeta? Las notas no se borrarán.">
                                    <button class="btn-ghost btn-sm w-full text-rose-600">Eliminar carpeta</button>
                                </x-confirm-form>
                            </div>
                        </div>
                    @endforeach
                    <a href="{{ route('notes.index', ['folder' => 'none']) }}" @class(['nav-item py-1.5', 'nav-item-active' => request('folder') === 'none'])>
                        <x-heroicon-o-folder class="h-4 w-4" /> Sin carpeta
                    </a>
                </nav>
            </div>

            <div class="card card-body">
                <h2 class="card-title">Etiquetas</h2>
                <div class="mt-3 flex flex-wrap gap-2">
                    @forelse ($tags as $tag)
                        <a href="{{ route('notes.index', ['tag' => $tag->id]) }}"
                           @class(['pill', \App\Support\Palette::pill(request('tag') == $tag->id ? 'indigo' : 'slate')])>
                            #{{ $tag->name }} <span class="opacity-60">{{ $tag->notes_count }}</span>
                        </a>
                    @empty
                        <p class="text-sm muted">Sin etiquetas todavía.</p>
                    @endforelse
                </div>
                <form method="POST" action="{{ route('tags.store') }}" class="mt-3 flex gap-2">
                    @csrf
                    <input name="name" class="input py-1.5" placeholder="Nueva etiqueta" maxlength="50" required>
                    <button class="btn-secondary btn-sm">Añadir</button>
                </form>
                @if (request('tag') && ($currentTag = $tags->firstWhere('id', (int) request('tag'))))
                    <x-confirm-form :action="route('tags.destroy', $currentTag)" method="DELETE" :message="'¿Eliminar la etiqueta #'.$currentTag->name.'?'" class="mt-2">
                        <button class="text-xs text-rose-600 hover:underline">Eliminar #{{ $currentTag->name }}</button>
                    </x-confirm-form>
                @endif
            </div>
        </aside>

        {{-- Listado --}}
        <div class="space-y-4 lg:col-span-3">
            <form method="GET" class="relative">
                @foreach (request()->only(['folder', 'tag', 'favorites']) as $key => $value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endforeach
                <x-heroicon-o-magnifying-glass class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
                <input type="search" name="q" value="{{ request('q') }}" class="input py-2.5 pl-10" placeholder="Buscar en títulos y contenido…">
            </form>

            @if ($notes->isEmpty())
                <x-empty-state icon="document-text" title="No hay documentos aquí" description="Sube un PDF, una imagen o una foto de tus apuntes y la IA extraerá el texto automáticamente.">
                    <a href="{{ route('notes.create') }}" class="btn-primary">Subir documento</a>
                </x-empty-state>
            @else
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($notes as $note)
                        <div class="card group flex flex-col p-5 transition hover:-translate-y-0.5 hover:shadow-md">
                            <div class="flex items-start justify-between gap-2">
                                <x-pill :color="match ($note->source_type) { 'pdf' => 'rose', 'image' => 'violet', 'docx' => 'sky', default => 'slate' }">
                                    {{ $note->sourceLabel() }}
                                </x-pill>
                                <form method="POST" action="{{ route('notes.favorite', $note) }}">
                                    @csrf @method('PATCH')
                                    <button class="text-lg leading-none" title="Favorito">{{ $note->is_favorite ? '❤️' : '🤍' }}</button>
                                </form>
                            </div>
                            <a href="{{ route('notes.show', $note) }}" class="mt-3 line-clamp-2 font-semibold hover:text-brand-600">{{ $note->title }}</a>
                            <p class="mt-2 line-clamp-3 flex-1 text-sm muted">{{ $note->excerpt() }}</p>
                            @if ($note->extraction_status === 'failed')
                                <p class="mt-2 text-xs text-rose-500">⚠️ Texto no extraído</p>
                            @endif
                            <div class="mt-4 flex flex-wrap items-center gap-1.5">
                                @if ($note->folder)
                                    <span class="inline-flex items-center gap-1 text-xs muted"><span class="h-2 w-2 rounded-full {{ \App\Support\Palette::dot($note->folder->color) }}"></span>{{ $note->folder->name }}</span>
                                @endif
                                @foreach ($note->tags->take(3) as $tag)
                                    <span class="text-xs text-brand-600 dark:text-brand-400">#{{ $tag->name }}</span>
                                @endforeach
                            </div>
                            <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3 text-xs muted dark:border-slate-800">
                                <span>{{ $note->created_at->diffForHumans() }}</span>
                                <span>{{ $note->quizzes_count }} {{ $note->quizzes_count === 1 ? 'cuestionario' : 'cuestionarios' }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
                {{ $notes->links() }}
            @endif
        </div>
    </div>
</x-app-layout>

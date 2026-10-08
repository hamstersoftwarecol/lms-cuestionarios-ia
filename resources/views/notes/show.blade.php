<x-app-layout :title="$note->title">
    <x-page-header :title="$note->title">
        <form method="POST" action="{{ route('notes.favorite', $note) }}">
            @csrf @method('PATCH')
            <button class="btn-secondary">{{ $note->is_favorite ? '❤️ Favorito' : '🤍 Favorito' }}</button>
        </form>
        <a href="{{ route('notes.edit', $note) }}" class="btn-secondary"><x-heroicon-o-pencil-square class="h-4 w-4" /> Editar</a>
        <x-confirm-form :action="route('notes.destroy', $note)" method="DELETE" message="¿Eliminar este documento? Sus cuestionarios se conservarán.">
            <button class="btn-ghost text-rose-600"><x-heroicon-o-trash class="h-4 w-4" /></button>
        </x-confirm-form>
    </x-page-header>

    <div class="flex flex-wrap items-center gap-2 text-sm muted">
        <x-pill :color="match ($note->source_type) { 'pdf' => 'rose', 'image' => 'violet', 'docx' => 'sky', default => 'slate' }">{{ $note->sourceLabel() }}</x-pill>
        @if ($note->folder)
            <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full {{ \App\Support\Palette::dot($note->folder->color) }}"></span>{{ $note->folder->name }}</span>
        @endif
        @foreach ($note->tags as $tag)
            <a href="{{ route('notes.index', ['tag' => $tag->id]) }}" class="text-brand-600 dark:text-brand-400">#{{ $tag->name }}</a>
        @endforeach
        <span>· {{ number_format($note->wordCount()) }} palabras · {{ $note->readingMinutes() }} min de lectura</span>
        @if ($note->file_path)
            · <a href="{{ route('notes.file', $note) }}" class="link">Descargar original ({{ $note->original_filename }})</a>
        @endif
    </div>

    @if ($note->extraction_status === 'failed')
        <div class="flex flex-col gap-3 rounded-2xl border px-4 py-3 text-sm sm:flex-row sm:items-center sm:justify-between {{ \App\Support\Palette::alert('danger') }}">
            <p><strong>No se pudo extraer el texto.</strong> {{ $note->extraction_error }}</p>
            @if ($note->file_path)
                <form method="POST" action="{{ route('notes.rescan', $note) }}" x-data="{ busy: false }" @submit="busy = true">
                    @csrf
                    <button class="btn-danger btn-sm" :disabled="busy" x-text="busy ? 'Escaneando…' : 'Reintentar escaneo'">Reintentar escaneo</button>
                </form>
            @endif
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            @if ($note->summary)
                <div class="card card-body border-brand-200 bg-brand-50/50 dark:border-brand-500/30 dark:bg-brand-500/5">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h2 class="card-title">✨ Resumen con IA</h2>
                        <x-tts-button source="#note-summary" />
                    </div>
                    <div id="note-summary" class="prose-note mt-3 text-sm">{{ $note->summary }}</div>
                </div>
            @endif

            <div class="card card-body">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 class="card-title">Contenido</h2>
                    <x-tts-button source="#note-content" />
                </div>
                <div id="note-content" class="prose-note mt-4 text-sm sm:text-base">{{ $note->content ?: 'Sin contenido.' }}</div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="card card-body">
                <h2 class="card-title">Generar cuestionario con IA</h2>
                <p class="mt-1 text-sm muted">Crea preguntas MCQ y SATA con explicaciones a partir de este documento.</p>
                @if ($note->hasContent())
                    <a href="{{ route('quizzes.create', ['note' => $note->id]) }}" class="btn-primary mt-4 w-full"><x-heroicon-o-sparkles class="h-5 w-5" /> Generar cuestionario</a>
                @else
                    <p class="mt-3 text-sm text-rose-500">Añade contenido al documento para poder generar preguntas.</p>
                @endif

                @if ($aiEnabled && $note->hasContent())
                    <form method="POST" action="{{ route('notes.summary', $note) }}" class="mt-2" x-data="{ busy: false }" @submit="busy = true">
                        @csrf
                        <button class="btn-secondary w-full" :disabled="busy">
                            <x-heroicon-o-light-bulb class="h-5 w-5" />
                            <span x-text="busy ? 'Resumiendo…' : @js($note->summary ? 'Regenerar resumen' : 'Resumir con IA')"></span>
                        </button>
                    </form>
                @endif
            </div>

            <div class="card card-body">
                <h2 class="card-title">Cuestionarios de este documento</h2>
                <div class="mt-3 space-y-2">
                    @forelse ($note->quizzes as $quiz)
                        <a href="{{ route('quizzes.show', $quiz) }}" class="flex items-center justify-between rounded-xl border border-slate-200 px-3 py-2 text-sm hover:border-brand-400 dark:border-slate-700">
                            <span class="truncate">{{ $quiz->title }}</span>
                            <x-heroicon-o-chevron-right class="h-4 w-4 shrink-0 text-slate-400" />
                        </a>
                    @empty
                        <p class="text-sm muted">Todavía no hay cuestionarios.</p>
                    @endforelse
                </div>
            </div>

            <div class="card card-body text-sm">
                <h2 class="card-title">Detalles</h2>
                <dl class="mt-3 space-y-2">
                    <div class="flex justify-between"><dt class="muted">Creado</dt><dd>{{ $note->created_at->translatedFormat('j M Y, H:i') }}</dd></div>
                    <div class="flex justify-between"><dt class="muted">Actualizado</dt><dd>{{ $note->updated_at->diffForHumans() }}</dd></div>
                    @if ($note->file_size)
                        <div class="flex justify-between"><dt class="muted">Tamaño</dt><dd>{{ \Illuminate\Support\Number::fileSize($note->file_size) }}</dd></div>
                    @endif
                </dl>
            </div>
        </div>
    </div>
</x-app-layout>

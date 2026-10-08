<x-app-layout title="Nuevo cuestionario">
    <x-page-header title="Nuevo cuestionario" subtitle="La IA lee tu documento y crea preguntas de opción múltiple (MCQ) y de selección múltiple (SATA)." />

    <form method="POST" action="{{ route('quizzes.store') }}" x-data="{ mode: @js(old('mode', 'ai')), busy: false }" @submit="busy = true" class="grid gap-6 lg:grid-cols-3">
        @csrf
        <input type="hidden" name="mode" :value="mode">

        <div class="card card-body space-y-6 lg:col-span-2">
            <div class="grid gap-3 sm:grid-cols-2">
                <button type="button" @click="mode = 'ai'" :class="mode === 'ai' ? 'border-brand-500 ring-2 ring-brand-500/30' : 'border-slate-200 dark:border-slate-700'" class="rounded-2xl border p-4 text-left">
                    <p class="font-semibold">✨ Generar con IA</p>
                    <p class="mt-1 text-sm muted">A partir de uno de tus documentos.</p>
                </button>
                <button type="button" @click="mode = 'manual'" :class="mode === 'manual' ? 'border-brand-500 ring-2 ring-brand-500/30' : 'border-slate-200 dark:border-slate-700'" class="rounded-2xl border p-4 text-left">
                    <p class="font-semibold">✍️ Crear manualmente</p>
                    <p class="mt-1 text-sm muted">Escribe tú las preguntas.</p>
                </button>
            </div>

            <div x-show="mode === 'ai'">
                @if (! $aiEnabled)
                    <div class="mb-4 rounded-2xl border px-4 py-3 text-sm {{ \App\Support\Palette::alert('warning') }}">
                        La IA no está configurada: añade <code>GEMINI_API_KEY</code> a tu <code>.env</code> para generar preguntas automáticamente.
                    </div>
                @endif
                <x-input-label for="note_id" value="Documento de origen" />
                @if ($notes->isEmpty())
                    <p class="text-sm muted">No tienes documentos con texto. <a href="{{ route('notes.create') }}" class="link">Sube uno primero</a>.</p>
                @else
                    <select id="note_id" name="note_id" class="input" :required="mode === 'ai'">
                        <option value="">Selecciona un documento…</option>
                        @foreach ($notes as $note)
                            <option value="{{ $note->id }}" @selected(old('note_id', $selectedNote) == $note->id)>{{ $note->title }} ({{ $note->sourceLabel() }})</option>
                        @endforeach
                    </select>
                @endif
                <x-input-error :messages="$errors->get('note_id')" />
            </div>

            <div>
                <x-input-label for="title" value="Título" />
                <x-text-input id="title" name="title" :value="old('title')" x-bind:required="mode === 'manual'" x-bind:placeholder="mode === 'ai' ? 'Opcional: la IA propondrá uno' : 'Ej.: Repaso del tema 2'" maxlength="255" />
                <x-input-error :messages="$errors->get('title')" />
            </div>

            <div x-show="mode === 'ai'" class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="question_count" value="Número de preguntas" />
                    <input id="question_count" type="number" name="question_count" min="{{ config('lms.quiz.min_questions') }}" max="{{ config('lms.quiz.max_questions') }}"
                           value="{{ old('question_count', config('lms.quiz.default_questions')) }}" class="input">
                    <x-input-error :messages="$errors->get('question_count')" />
                </div>
                <div>
                    <x-input-label for="question_types" value="Tipo de preguntas" />
                    <select id="question_types" name="question_types" class="input">
                        <option value="mixed" @selected(old('question_types') === 'mixed')>Mixto (MCQ + SATA)</option>
                        <option value="mcq" @selected(old('question_types') === 'mcq')>Solo opción múltiple (MCQ)</option>
                        <option value="sata" @selected(old('question_types') === 'sata')>Solo selección múltiple (SATA)</option>
                    </select>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="difficulty" value="Dificultad" />
                    <select id="difficulty" name="difficulty" class="input">
                        @foreach (\App\Enums\Difficulty::cases() as $difficulty)
                            <option value="{{ $difficulty->value }}" @selected(old('difficulty', 'mixed') === $difficulty->value)>{{ $difficulty->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="language" value="Idioma" />
                    <select id="language" name="language" class="input">
                        @foreach ($languages as $code => $label)
                            <option value="{{ $code }}" @selected(old('language', 'es') === $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="card card-body space-y-4">
                <h2 class="card-title">Opciones</h2>
                <div>
                    <x-input-label for="time_limit" value="Límite de tiempo (minutos)" />
                    <input id="time_limit" type="number" name="time_limit" min="1" max="180" value="{{ old('time_limit') }}" class="input" placeholder="Sin límite">
                    <x-input-error :messages="$errors->get('time_limit')" />
                </div>
                <label class="flex items-start gap-3 text-sm">
                    <input type="checkbox" name="is_public" value="1" class="checkbox mt-0.5" @checked(old('is_public'))>
                    <span><span class="font-medium">Público</span><br><span class="muted">Cualquier usuario podrá practicarlo y aparecerá en la pestaña «Públicos».</span></span>
                </label>
            </div>

            <div class="card card-body text-sm">
                <p class="font-semibold">¿Cómo funciona?</p>
                <ul class="mt-2 space-y-2 muted">
                    <li>🧠 <strong>MCQ:</strong> 4 opciones, una correcta.</li>
                    <li>☑️ <strong>SATA:</strong> 5 opciones, varias correctas.</li>
                    <li>💡 Cada pregunta incluye explicación y nivel de dificultad.</li>
                    <li>✏️ Podrás revisar y editar todo antes de compartirlo.</li>
                </ul>
            </div>

            <button class="btn-primary w-full py-3" :disabled="busy">
                <svg x-show="busy" x-cloak class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/><path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                <span x-text="busy ? (mode === 'ai' ? 'La IA está creando las preguntas…' : 'Creando…') : (mode === 'ai' ? 'Generar cuestionario' : 'Crear y añadir preguntas')"></span>
            </button>
            <p x-show="busy && mode === 'ai'" x-cloak class="text-center text-xs muted">Puede tardar entre 10 y 60 segundos.</p>
        </div>
    </form>
</x-app-layout>

<x-app-layout :title="'Editar: '.$quiz->title">
    <x-page-header title="Editar cuestionario" :subtitle="$quiz->title">
        <a href="{{ route('quizzes.show', $quiz) }}" class="btn-secondary">Ver cuestionario</a>
        @if ($quiz->questions->isNotEmpty())
            <a href="{{ route('attempts.create', $quiz) }}" class="btn-primary"><x-heroicon-o-play class="h-4 w-4" /> Probar</a>
        @endif
    </x-page-header>

    <form method="POST" action="{{ route('quizzes.update', $quiz) }}" class="card card-body grid gap-4 sm:grid-cols-2">
        @csrf @method('PUT')
        <div class="sm:col-span-2">
            <x-input-label for="title" value="Título" />
            <x-text-input id="title" name="title" :value="old('title', $quiz->title)" required maxlength="255" />
            <x-input-error :messages="$errors->get('title')" />
        </div>
        <div class="sm:col-span-2">
            <x-input-label for="description" value="Descripción" />
            <textarea id="description" name="description" rows="2" class="input">{{ old('description', $quiz->description) }}</textarea>
        </div>
        <div>
            <x-input-label for="difficulty" value="Dificultad general" />
            <select id="difficulty" name="difficulty" class="input">
                @foreach (\App\Enums\Difficulty::cases() as $difficulty)
                    <option value="{{ $difficulty->value }}" @selected(old('difficulty', $quiz->difficulty->value) === $difficulty->value)>{{ $difficulty->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="language" value="Idioma" />
            <select id="language" name="language" class="input">
                @foreach ($languages as $code => $label)
                    <option value="{{ $code }}" @selected(old('language', $quiz->language) === $code)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="time_limit" value="Límite de tiempo (minutos)" />
            <input id="time_limit" type="number" name="time_limit" min="1" max="180" value="{{ old('time_limit', $quiz->time_limit) }}" class="input" placeholder="Sin límite">
        </div>
        <label class="flex items-center gap-3 self-end pb-2 text-sm">
            <input type="checkbox" name="is_public" value="1" class="checkbox" @checked(old('is_public', $quiz->is_public))>
            Cuestionario público
        </label>
        <div class="flex justify-end sm:col-span-2">
            <x-primary-button>Guardar datos</x-primary-button>
        </div>
    </form>

    <div id="questions" class="space-y-4">
        <h2 class="text-lg font-semibold">Preguntas ({{ $quiz->questions->count() }})</h2>

        @foreach ($quiz->questions as $question)
            <div id="question-{{ $question->id }}" class="card card-body" x-data="{ open: @js(old('_form') === 'q-'.$question->id) }">
                <div class="flex items-start gap-3">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-sm font-bold dark:bg-slate-800">{{ $loop->iteration }}</span>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap gap-1.5">
                            <x-pill :color="$question->type === \App\Enums\QuestionType::Sata ? 'violet' : 'indigo'">{{ $question->type->shortLabel() }}</x-pill>
                            <x-pill :color="$question->difficulty->color()">{{ $question->difficulty->label() }}</x-pill>
                        </div>
                        <p class="mt-2 font-medium">{{ $question->question_text }}</p>
                        <ul x-show="! open" class="mt-2 space-y-1 text-sm">
                            @foreach ($question->options as $i => $option)
                                <li @class(['font-medium text-emerald-600 dark:text-emerald-400' => in_array($i, $question->correct_answers), 'muted' => ! in_array($i, $question->correct_answers)])>
                                    {{ chr(65 + $i) }}. {{ $option }} @if (in_array($i, $question->correct_answers)) ✓ @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="flex shrink-0 gap-1">
                        <button type="button" class="btn-ghost p-2" @click="open = ! open" aria-label="Editar"><x-heroicon-o-pencil-square class="h-4 w-4" /></button>
                        <x-confirm-form :action="route('questions.destroy', $question)" method="DELETE" message="¿Eliminar esta pregunta?">
                            <button class="btn-ghost p-2 text-rose-600" aria-label="Eliminar"><x-heroicon-o-trash class="h-4 w-4" /></button>
                        </x-confirm-form>
                    </div>
                </div>
                <div x-show="open" x-cloak class="mt-4 border-t border-slate-100 pt-4 dark:border-slate-800">
                    @include('quizzes._question-form', ['question' => $question, 'action' => route('questions.update', $question)])
                </div>
            </div>
        @endforeach

        <div class="card card-body border-dashed">
            <h3 class="card-title mb-4">➕ Nueva pregunta</h3>
            @include('quizzes._question-form', ['question' => null, 'action' => route('questions.store', $quiz)])
        </div>
    </div>
</x-app-layout>

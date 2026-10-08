{{-- Editor de una pregunta (nueva o existente). $question puede ser null. --}}
@php
    $formId = $question ? 'q-'.$question->id : 'new';
    $useOld = old('_form') === $formId;
    $state = [
        'type' => $useOld ? old('type') : ($question?->type->value ?? 'mcq'),
        'options' => $useOld ? array_values(old('options', [])) : ($question?->options ?? ['', '', '', '']),
        'correct' => $useOld ? array_map('intval', old('correct_answers', [])) : ($question?->correct_answers ?? []),
    ];
@endphp

<form method="POST" action="{{ $action }}" class="space-y-4"
      x-data="{
          type: @js($state['type']),
          options: @js($state['options'] ?: ['', '']),
          correct: @js($state['correct']),
          toggle(i) {
              if (this.type === 'mcq') { this.correct = [i]; return; }
              this.correct = this.correct.includes(i) ? this.correct.filter(c => c !== i) : [...this.correct, i].sort();
          },
          add() { if (this.options.length < 6) this.options.push('') },
          remove(i) {
              if (this.options.length <= 2) return;
              this.options.splice(i, 1);
              this.correct = this.correct.filter(c => c !== i).map(c => c > i ? c - 1 : c);
          },
      }"
      x-effect="if (type === 'mcq' && correct.length > 1) correct = [correct[0]]">
    @csrf
    @if ($question) @method('PUT') @endif
    <input type="hidden" name="_form" value="{{ $formId }}">

    @if ($useOld && $errors->any())
        <div class="rounded-xl border px-3 py-2 text-sm {{ \App\Support\Palette::alert('danger') }}">
            @foreach ($errors->all() as $error) <p>{{ $error }}</p> @endforeach
        </div>
    @endif

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="sm:col-span-2">
            <label class="label">Pregunta</label>
            <textarea name="question_text" rows="2" class="input" required>{{ $useOld ? old('question_text') : $question?->question_text }}</textarea>
        </div>
        <div class="space-y-3">
            <div>
                <label class="label">Tipo</label>
                <select name="type" x-model="type" class="input">
                    @foreach (\App\Enums\QuestionType::cases() as $type)
                        <option value="{{ $type->value }}">{{ $type->label() }} ({{ $type->shortLabel() }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Dificultad</label>
                <select name="difficulty" class="input">
                    @foreach (\App\Enums\Difficulty::forQuestions() as $difficulty)
                        <option value="{{ $difficulty->value }}" @selected(($useOld ? old('difficulty') : $question?->difficulty?->value ?? 'medium') === $difficulty->value)>{{ $difficulty->label() }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div>
        <label class="label">Opciones <span class="font-normal muted" x-text="type === 'mcq' ? '(marca la correcta)' : '(marca todas las correctas)'"></span></label>
        <div class="space-y-2">
            <template x-for="(option, i) in options" :key="i">
                <div class="flex items-center gap-2">
                    <button type="button" @click="toggle(i)"
                            :class="correct.includes(i) ? 'border-emerald-500 bg-emerald-500 text-white' : 'border-slate-300 dark:border-slate-600'"
                            :title="correct.includes(i) ? 'Correcta' : 'Marcar como correcta'"
                            class="flex h-8 w-8 shrink-0 items-center justify-center border-2 text-sm font-bold"
                            :style="type === 'mcq' ? 'border-radius: 9999px' : 'border-radius: .5rem'">
                        <span x-text="correct.includes(i) ? '✓' : String.fromCharCode(65 + i)"></span>
                    </button>
                    <input :name="'options[' + i + ']'" x-model="options[i]" class="input" required maxlength="500" :placeholder="'Opción ' + String.fromCharCode(65 + i)">
                    <button type="button" @click="remove(i)" class="btn-ghost p-2" :disabled="options.length <= 2" aria-label="Quitar opción">
                        <x-heroicon-o-x-mark class="h-4 w-4" />
                    </button>
                </div>
            </template>
        </div>
        <template x-for="c in correct" :key="'c' + c"><input type="hidden" name="correct_answers[]" :value="c"></template>
        <button type="button" @click="add()" x-show="options.length < 6" class="btn-ghost btn-sm mt-2"><x-heroicon-o-plus class="h-4 w-4" /> Añadir opción</button>
    </div>

    <div>
        <label class="label">Explicación</label>
        <textarea name="explanation" rows="2" class="input" placeholder="¿Por qué esa es la respuesta correcta?">{{ $useOld ? old('explanation') : $question?->explanation }}</textarea>
    </div>

    <div class="flex justify-end">
        <button class="btn-primary btn-sm">{{ $question ? 'Guardar pregunta' : 'Añadir pregunta' }}</button>
    </div>
</form>

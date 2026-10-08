<x-app-layout :title="'Resultado: '.$attempt->quiz->title">
    @php
        $pct = round($attempt->percentage);
        $ring = $pct >= 75 ? 'text-emerald-500' : ($pct >= 50 ? 'text-amber-500' : 'text-rose-500');
    @endphp

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card card-body flex flex-col items-center text-center lg:col-span-1">
            <p class="text-sm font-semibold uppercase tracking-wider muted">{{ $attempt->assessment_type->label() }}</p>
            <div class="relative mt-4 h-40 w-40">
                <svg viewBox="0 0 36 36" class="h-40 w-40 -rotate-90">
                    <circle cx="18" cy="18" r="15.9" fill="none" stroke-width="3" class="stroke-slate-200 dark:stroke-slate-800" />
                    <circle cx="18" cy="18" r="15.9" fill="none" stroke-width="3" stroke-linecap="round" stroke="currentColor"
                            class="{{ $ring }}" stroke-dasharray="{{ $pct }} 100" pathLength="100" />
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center">
                    <span class="text-4xl font-bold">{{ $pct }}%</span>
                    <span class="text-sm muted">{{ $attempt->score }}/{{ $attempt->total_questions }}</span>
                </div>
            </div>
            <p class="mt-4 text-xl font-bold">{{ $attempt->grade() }}</p>
            <p class="text-sm muted">{{ $attempt->quiz->title }}</p>

            <dl class="mt-6 grid w-full grid-cols-3 gap-2 text-center">
                <div class="rounded-xl bg-amber-50 p-3 dark:bg-amber-500/10"><dt class="text-xs muted">Puntos</dt><dd class="font-bold text-amber-600">+{{ $attempt->points_earned }}</dd></div>
                <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-800/60"><dt class="text-xs muted">Tiempo</dt><dd class="font-bold">{{ gmdate($attempt->time_spent >= 3600 ? 'H:i:s' : 'i:s', $attempt->time_spent) }}</dd></div>
                <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-800/60"><dt class="text-xs muted">Confianza</dt><dd class="font-bold">{{ $attempt->confidence_before ?? '—' }}/5</dd></div>
            </dl>

            @if ($attempt->confidence_before)
                @php
                    $calibration = $pct - $attempt->confidence_before * 20;
                @endphp
                <p class="mt-4 text-sm muted">
                    @if (abs($calibration) <= 15)
                        🎯 Tu confianza coincidía con tu resultado. ¡Buena autoevaluación!
                    @elseif ($calibration > 15)
                        💪 Sabías más de lo que creías.
                    @else
                        🤔 Te sentías más seguro de lo que mostró el resultado: repasa las explicaciones.
                    @endif
                </p>
            @endif

            <div class="mt-6 flex w-full flex-col gap-2">
                <a href="{{ route('attempts.create', $attempt->quiz) }}" class="btn-primary"><x-heroicon-o-arrow-path class="h-4 w-4" /> Intentar de nuevo</a>
                <a href="{{ route('quizzes.show', $attempt->quiz) }}" class="btn-secondary">Ver cuestionario</a>
            </div>
        </div>

        <div class="space-y-6 lg:col-span-2">
            @if ($prePost)
                <div class="card card-body">
                    <h2 class="card-title">📈 Tu ganancia de aprendizaje</h2>
                    <div class="mt-4 space-y-3">
                        <div>
                            <div class="flex justify-between text-sm"><span>Evaluación previa</span><span class="font-semibold">{{ round($prePost['pre']) }}%</span></div>
                            <x-progress :value="$prePost['pre']" color="bg-amber-500" class="mt-1" />
                        </div>
                        <div>
                            <div class="flex justify-between text-sm"><span>Evaluación posterior</span><span class="font-semibold">{{ round($prePost['post']) }}%</span></div>
                            <x-progress :value="$prePost['post']" color="bg-emerald-500" class="mt-1" />
                        </div>
                        <p class="text-sm">
                            @if ($prePost['gain'] > 0)
                                🚀 Mejoraste <strong>{{ round($prePost['gain']) }} puntos</strong> después de estudiar.
                            @elseif ($prePost['gain'] == 0)
                                Mantienes el mismo nivel. Prueba a repasar con el resumen de IA y la voz.
                            @else
                                Tu resultado bajó {{ abs(round($prePost['gain'])) }} puntos. Revisa las explicaciones de abajo.
                            @endif
                        </p>
                    </div>
                </div>
            @endif

            <div class="card card-body">
                <h2 class="card-title">📝 Evaluación posterior</h2>
                @if ($attempt->confidence_after)
                    <p class="mt-2 text-sm">Confianza después del cuestionario: <strong>{{ $attempt->confidence_after }}/5</strong>
                        @if ($attempt->confidence_before) (antes: {{ $attempt->confidence_before }}/5) @endif
                    </p>
                    @if ($attempt->reflection)
                        <blockquote class="mt-3 rounded-xl border-l-4 border-brand-500 bg-slate-50 p-3 text-sm italic dark:bg-slate-800/60">{{ $attempt->reflection }}</blockquote>
                    @endif
                @else
                    <form method="POST" action="{{ route('attempts.reflect', $attempt) }}" class="mt-4 space-y-4" x-data="{ confidence: 3 }">
                        @csrf @method('PATCH')
                        <div>
                            <p class="label">Después de ver tus resultados, ¿qué tan seguro te sientes con el tema?</p>
                            <div class="flex items-center gap-4">
                                <input type="range" name="confidence_after" min="1" max="5" x-model="confidence" class="w-full accent-brand-600">
                                <span class="w-10 text-3xl" x-text="['😟', '😕', '😐', '🙂', '😎'][confidence - 1]"></span>
                            </div>
                        </div>
                        <div>
                            <label for="reflection" class="label">¿Qué aprendiste o qué necesitas repasar? (opcional)</label>
                            <textarea id="reflection" name="reflection" rows="3" class="input" maxlength="2000" placeholder="Ej.: Confundo la fase luminosa con el ciclo de Calvin…"></textarea>
                        </div>
                        <x-primary-button>Guardar reflexión</x-primary-button>
                    </form>
                @endif
            </div>

            <div class="space-y-4">
                <h2 class="text-lg font-semibold">Revisión de respuestas</h2>
                @foreach ($answers as $answer)
                    @php
                        $question = $answer->question;
                    @endphp
                    <div @class(['card card-body border-l-4', 'border-l-emerald-500' => $answer->is_correct, 'border-l-rose-500' => ! $answer->is_correct])>
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div class="flex flex-wrap gap-1.5">
                                <x-pill :color="$answer->is_correct ? 'emerald' : 'rose'">{{ $answer->is_correct ? '✓ Correcta' : '✗ Incorrecta' }}</x-pill>
                                <x-pill :color="$question->type === \App\Enums\QuestionType::Sata ? 'violet' : 'indigo'">{{ $question->type->shortLabel() }}</x-pill>
                                <x-pill :color="$question->difficulty->color()">{{ $question->difficulty->label() }}</x-pill>
                            </div>
                            <x-tts-button compact :voice="$voice" :text="$question->question_text.'. '.($question->explanation ?? '')" />
                        </div>
                        <p class="mt-3 font-medium">{{ $loop->iteration }}. {{ $question->question_text }}</p>
                        <ul class="mt-3 space-y-2">
                            @foreach ($question->options as $i => $option)
                                @php
                                    $isCorrect = in_array($i, $question->correct_answers);
                                    $isSelected = in_array($i, $answer->selected_answers ?? []);
                                @endphp
                                <li @class([
                                    'flex items-start gap-2 rounded-xl border px-3 py-2 text-sm',
                                    'border-emerald-300 bg-emerald-50 dark:border-emerald-500/40 dark:bg-emerald-500/10' => $isCorrect,
                                    'border-rose-300 bg-rose-50 dark:border-rose-500/40 dark:bg-rose-500/10' => $isSelected && ! $isCorrect,
                                    'border-slate-200 dark:border-slate-800' => ! $isCorrect && ! $isSelected,
                                ])>
                                    <span class="font-semibold">{{ chr(65 + $i) }}.</span>
                                    <span class="flex-1">{{ $option }}</span>
                                    @if ($isSelected) <span class="text-xs font-medium muted">Tu respuesta</span> @endif
                                    @if ($isCorrect) <span class="text-emerald-600">✓</span> @endif
                                </li>
                            @endforeach
                        </ul>
                        @if ($question->explanation)
                            <div class="mt-3 rounded-xl bg-sky-50 p-3 text-sm text-sky-900 dark:bg-sky-500/10 dark:text-sky-200">💡 {{ $question->explanation }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>

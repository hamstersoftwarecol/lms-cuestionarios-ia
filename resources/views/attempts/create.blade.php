<x-app-layout :title="'Empezar: '.$quiz->title">
    <div class="mx-auto max-w-2xl">
        <form method="POST" action="{{ route('attempts.store', $quiz) }}" class="card card-body space-y-8"
              x-data="{ type: @js(old('assessment_type', $suggested->value)), confidence: {{ (int) old('confidence_before', 3) }} }">
            @csrf
            <div class="text-center">
                <p class="text-sm font-semibold uppercase tracking-wider text-brand-600">Evaluación previa</p>
                <h1 class="mt-2 text-2xl font-bold">{{ $quiz->title }}</h1>
                <p class="mt-1 text-sm muted">
                    {{ $quiz->questions_count }} preguntas
                    @if ($quiz->time_limit) · {{ $quiz->time_limit }} minutos @endif
                    @if ($previousCount) · lo has completado {{ $previousCount }} {{ $previousCount === 1 ? 'vez' : 'veces' }} @endif
                </p>
            </div>

            <div>
                <p class="label">¿Qué tipo de intento es?</p>
                <div class="grid gap-3 sm:grid-cols-3">
                    @foreach (\App\Enums\AssessmentType::cases() as $type)
                        <label class="cursor-pointer">
                            <input type="radio" name="assessment_type" value="{{ $type->value }}" x-model="type" class="peer sr-only">
                            <div class="h-full rounded-2xl border border-slate-200 p-4 transition peer-checked:border-brand-500 peer-checked:ring-2 peer-checked:ring-brand-500/30 dark:border-slate-700">
                                <p class="font-semibold">{{ ['practice' => '🎯', 'pre' => '🧭', 'post' => '🏁'][$type->value] }} {{ $type->label() }}</p>
                                <p class="mt-1 text-xs muted">{{ $type->description() }}</p>
                            </div>
                        </label>
                    @endforeach
                </div>
                <p x-show="type === 'post' && ! {{ $hasPre ? 'true' : 'false' }}" x-cloak class="mt-2 text-xs text-amber-600">
                    Consejo: aún no hiciste una evaluación previa de este cuestionario; sin ella no podremos medir tu ganancia de aprendizaje.
                </p>
            </div>

            <div>
                <p class="label">¿Qué tan seguro te sientes con este tema?</p>
                <div class="flex items-center gap-4">
                    <input type="range" name="confidence_before" min="1" max="5" x-model="confidence" class="w-full accent-brand-600">
                    <span class="w-10 text-3xl" x-text="['😟', '😕', '😐', '🙂', '😎'][confidence - 1]"></span>
                </div>
                <div class="mt-1 flex justify-between text-xs muted"><span>Nada seguro</span><span>Muy seguro</span></div>
                <x-input-error :messages="$errors->get('confidence_before')" />
            </div>

            <ul class="space-y-1 rounded-xl bg-slate-50 p-4 text-sm muted dark:bg-slate-800/60">
                <li>• <strong>MCQ</strong>: elige una única respuesta. <strong>SATA</strong>: selecciona todas las correctas.</li>
                <li>• Usa las teclas <kbd class="rounded bg-white px-1 dark:bg-slate-700">1</kbd>–<kbd class="rounded bg-white px-1 dark:bg-slate-700">6</kbd> para responder y <kbd class="rounded bg-white px-1 dark:bg-slate-700">←</kbd> <kbd class="rounded bg-white px-1 dark:bg-slate-700">→</kbd> para navegar.</li>
                <li>• Puedes escuchar cada pregunta con la voz de IA 🔊.</li>
            </ul>

            <div class="flex justify-between gap-3">
                <a href="{{ route('quizzes.show', $quiz) }}" class="btn-ghost">Volver</a>
                <button class="btn-primary px-8"><x-heroicon-o-play class="h-5 w-5" /> Comenzar</button>
            </div>
        </form>
    </div>
</x-app-layout>

<x-app-layout :title="$quiz->title">
    <x-page-header :title="$quiz->title" :subtitle="$quiz->description">
        <a href="{{ route('attempts.create', $quiz) }}" class="btn-primary"><x-heroicon-o-play class="h-5 w-5" /> Empezar</a>
        @can('update', $quiz)
            <a href="{{ route('quizzes.edit', $quiz) }}" class="btn-secondary"><x-heroicon-o-pencil-square class="h-4 w-4" /> Editar</a>
        @endcan
        @can('delete', $quiz)
            <x-confirm-form :action="route('quizzes.destroy', $quiz)" method="DELETE" message="¿Eliminar este cuestionario y todos sus intentos?">
                <button class="btn-ghost text-rose-600" aria-label="Eliminar"><x-heroicon-o-trash class="h-4 w-4" /></button>
            </x-confirm-form>
        @endcan
    </x-page-header>

    <div class="flex flex-wrap gap-2">
        <x-pill :color="$quiz->difficulty->color()">{{ $quiz->difficulty->label() }}</x-pill>
        <x-pill color="indigo">{{ $stats['mcq'] }} MCQ</x-pill>
        <x-pill color="violet">{{ $stats['sata'] }} SATA</x-pill>
        @if ($quiz->time_limit) <x-pill color="slate">⏱ {{ $quiz->time_limit }} min</x-pill> @endif
        @if ($quiz->generated_by_ai) <x-pill color="violet">✨ Generado con IA</x-pill> @endif
        @if ($quiz->is_public) <x-pill color="sky">Público</x-pill> @endif
        <span class="text-sm muted">por {{ $quiz->user->name }}
            @if ($quiz->note && $quiz->note->user_id === auth()->id()) · de <a href="{{ route('notes.show', $quiz->note) }}" class="link">{{ $quiz->note->title }}</a> @endif
        </span>
    </div>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat-card label="Preguntas" :value="$quiz->questions_count" icon="clipboard-document-list" color="indigo" />
        <x-stat-card label="Intentos totales" :value="$stats['attempts']" icon="users" color="sky" />
        <x-stat-card label="Media global" :value="$stats['avg'].' %'" icon="chart-bar" color="emerald" />
        <x-stat-card label="Mis intentos" :value="$myAttempts->count()" icon="clock" color="amber" :hint="$myAttempts->isNotEmpty() ? 'Mejor: '.round($myAttempts->max('percentage')).' %' : null" />
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            @if ($prePost)
                <div class="card card-body">
                    <h2 class="card-title">📈 Evaluación previa vs. posterior</h2>
                    <div class="mt-4 grid grid-cols-3 gap-4 text-center">
                        <div><p class="text-sm muted">Antes</p><p class="text-2xl font-bold">{{ round($prePost['pre']) }}%</p></div>
                        <div><p class="text-sm muted">Después</p><p class="text-2xl font-bold">{{ round($prePost['post']) }}%</p></div>
                        <div><p class="text-sm muted">Ganancia</p><p @class(['text-2xl font-bold', 'text-emerald-600' => $prePost['gain'] >= 0, 'text-rose-600' => $prePost['gain'] < 0])>{{ $prePost['gain'] >= 0 ? '+' : '' }}{{ round($prePost['gain']) }} pts</p></div>
                    </div>
                </div>
            @endif

            <div class="card">
                <div class="border-b border-slate-100 px-6 py-4 dark:border-slate-800"><h2 class="card-title">Mis intentos</h2></div>
                @if ($myAttempts->isEmpty())
                    <p class="px-6 py-8 text-center text-sm muted">Aún no lo has respondido. Empieza con una <strong>evaluación previa</strong> para medir tu punto de partida.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="table-basic">
                            <thead><tr><th>Fecha</th><th>Tipo</th><th>Resultado</th><th>Puntos</th><th>Tiempo</th><th></th></tr></thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @foreach ($myAttempts as $attempt)
                                    <tr>
                                        <td>{{ $attempt->completed_at->translatedFormat('j M Y, H:i') }}</td>
                                        <td><x-pill :color="match ($attempt->assessment_type->value) { 'pre' => 'amber', 'post' => 'emerald', default => 'slate' }">{{ $attempt->assessment_type->label() }}</x-pill></td>
                                        <td class="font-semibold">{{ $attempt->score }}/{{ $attempt->total_questions }} ({{ round($attempt->percentage) }}%)</td>
                                        <td class="text-amber-600">+{{ $attempt->points_earned }}</td>
                                        <td>{{ gmdate('i:s', $attempt->time_spent) }}</td>
                                        <td><a href="{{ route('attempts.show', $attempt) }}" class="link">Revisar</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            @can('update', $quiz)
                <div class="card card-body">
                    <div class="flex items-center justify-between">
                        <h2 class="card-title">Vista previa de preguntas</h2>
                        <a href="{{ route('quizzes.edit', $quiz) }}#questions" class="text-sm link">Editar preguntas</a>
                    </div>
                    <ol class="mt-4 space-y-4">
                        @foreach ($quiz->questions as $question)
                            <li class="rounded-xl border border-slate-200 p-4 dark:border-slate-800">
                                <div class="flex flex-wrap gap-1.5">
                                    <x-pill :color="$question->type === \App\Enums\QuestionType::Sata ? 'violet' : 'indigo'">{{ $question->type->shortLabel() }}</x-pill>
                                    <x-pill :color="$question->difficulty->color()">{{ $question->difficulty->label() }}</x-pill>
                                </div>
                                <p class="mt-2 font-medium">{{ $loop->iteration }}. {{ $question->question_text }}</p>
                                <ul class="mt-2 space-y-1 text-sm">
                                    @foreach ($question->options as $i => $option)
                                        <li @class(['text-emerald-600 dark:text-emerald-400 font-medium' => in_array($i, $question->correct_answers), 'muted' => ! in_array($i, $question->correct_answers)])>{{ chr(65 + $i) }}. {{ $option }}</li>
                                    @endforeach
                                </ul>
                                @if ($question->explanation)
                                    <p class="mt-2 text-sm muted">💡 {{ $question->explanation }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </div>
            @endcan
        </div>

        <div class="space-y-6">
            <div class="card card-body">
                <h2 class="card-title">🏆 Mejores puntuaciones</h2>
                <ol class="mt-4 space-y-3">
                    @forelse ($ranking as $row)
                        <li class="flex items-center gap-3">
                            <span class="w-6 text-center text-sm font-bold muted">{{ $loop->iteration <= 3 ? ['🥇', '🥈', '🥉'][$loop->index] : $loop->iteration }}</span>
                            <x-avatar :user="$row->user" class="h-8 w-8" />
                            <span class="min-w-0 flex-1 truncate text-sm">{{ $row->user?->name }}</span>
                            <span class="text-sm font-semibold">{{ round($row->best) }}%</span>
                        </li>
                    @empty
                        <p class="text-sm muted">Sé el primero en completarlo.</p>
                    @endforelse
                </ol>
            </div>

            @if ($quiz->user_id === auth()->id() && $groups->isNotEmpty())
                <div class="card card-body">
                    <h2 class="card-title">Compartir con un grupo</h2>
                    <div class="mt-3 space-y-2">
                        @foreach ($groups as $group)
                            <div class="flex items-center justify-between gap-2 text-sm">
                                <span class="truncate">{{ $group->name }}</span>
                                @if ($sharedGroupIds->contains($group->id))
                                    <x-pill color="emerald">Compartido</x-pill>
                                @else
                                    <form method="POST" action="{{ route('groups.quizzes.store', $group) }}">
                                        @csrf
                                        <input type="hidden" name="quiz_id" value="{{ $quiz->id }}">
                                        <button class="btn-secondary btn-sm"><x-heroicon-o-share class="h-4 w-4" /> Compartir</button>
                                    </form>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

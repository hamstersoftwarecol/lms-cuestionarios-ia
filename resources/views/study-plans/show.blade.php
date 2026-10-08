<x-app-layout :title="$plan->title">
    @php($daysLeft = $plan->daysUntilExam())
    <x-page-header :title="$plan->title" :subtitle="'Examen el '.$plan->exam_date->translatedFormat('l j \d\e F Y')">
        <x-confirm-form :action="route('study-plans.regenerate', $plan)" message="Se regenerarán las tareas pendientes desde hoy. Las completadas se conservan. ¿Continuar?">
            <button class="btn-secondary"><x-heroicon-o-arrow-path class="h-4 w-4" /> Regenerar</button>
        </x-confirm-form>
        <x-confirm-form :action="route('study-plans.destroy', $plan)" method="DELETE" message="¿Eliminar el plan y todas sus tareas?">
            <button class="btn-ghost text-rose-600" aria-label="Eliminar"><x-heroicon-o-trash class="h-4 w-4" /></button>
        </x-confirm-form>
    </x-page-header>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat-card label="Faltan" :value="$daysLeft < 0 ? 'Finalizado' : ($daysLeft === 0 ? '¡Hoy!' : $daysLeft.' días')" icon="calendar-days" color="rose" />
        <x-stat-card label="Progreso" :value="$plan->progress().' %'" icon="check-circle" color="emerald" :hint="$plan->completed_tasks_count.' de '.$plan->tasks_count.' tareas'" />
        <x-stat-card label="Tiempo total" :value="round($minutesTotal / 60, 1).' h'" icon="clock" color="sky" :hint="$plan->daily_minutes.' min al día'" />
        <x-stat-card label="Temas" :value="$plan->notes->count() + count($plan->topics ?? [])" icon="book-open" color="violet" />
    </div>

    @if ($plan->description)
        <p class="text-sm muted">{{ $plan->description }}</p>
    @endif

    <div class="space-y-4">
        @foreach ($days as $date => $tasks)
            @php($day = \Illuminate\Support\Carbon::parse($date))
            <div @class(['card', 'ring-2 ring-brand-500' => $day->isToday()])>
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3 dark:border-slate-800">
                    <div>
                        <p class="font-semibold capitalize">{{ $day->isToday() ? 'Hoy' : ($day->isTomorrow() ? 'Mañana' : $day->translatedFormat('l j \d\e F')) }}</p>
                        <p class="text-xs muted">{{ $tasks->sum('duration_minutes') }} min · {{ $tasks->where('is_completed', true)->count() }}/{{ $tasks->count() }} completadas</p>
                    </div>
                    @if ($day->isPast() && ! $day->isToday() && $tasks->contains('is_completed', false))
                        <x-pill color="rose">Atrasado</x-pill>
                    @elseif ($tasks->every('is_completed'))
                        <x-pill color="emerald">✓ Completado</x-pill>
                    @endif
                </div>
                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($tasks as $task)
                        <div class="flex items-start gap-3 px-5 py-3">
                            @if ($task->type !== \App\Enums\TaskType::Exam)
                                <form method="POST" action="{{ route('study-tasks.toggle', $task) }}">
                                    @csrf @method('PATCH')
                                    <button @class([
                                        'mt-0.5 flex h-6 w-6 items-center justify-center rounded-full border-2 text-xs text-white',
                                        'border-emerald-500 bg-emerald-500' => $task->is_completed,
                                        'border-slate-300 hover:border-emerald-500 dark:border-slate-600' => ! $task->is_completed,
                                    ]) title="{{ $task->is_completed ? 'Marcar como pendiente' : 'Marcar como completada' }}">{{ $task->is_completed ? '✓' : '' }}</button>
                                </form>
                            @else
                                <span class="mt-0.5 text-xl">🏁</span>
                            @endif
                            <div class="min-w-0 flex-1">
                                <p @class(['text-sm font-medium', 'line-through muted' => $task->is_completed])>{{ $task->type->icon() }} {{ $task->title }}</p>
                                <p class="text-xs muted">{{ $task->description }}</p>
                            </div>
                            <div class="flex shrink-0 flex-col items-end gap-1">
                                <x-pill :color="$task->type->color()">{{ $task->type->label() }}</x-pill>
                                @if ($task->duration_minutes)<span class="text-xs muted">{{ $task->duration_minutes }} min</span>@endif
                                @if ($task->quiz && ! $task->is_completed)
                                    <a href="{{ route('attempts.create', $task->quiz) }}" class="text-xs link">Practicar →</a>
                                @elseif ($task->note && ! $task->is_completed)
                                    <a href="{{ route($task->type === \App\Enums\TaskType::Quiz ? 'quizzes.create' : 'notes.show', $task->type === \App\Enums\TaskType::Quiz ? ['note' => $task->note_id] : $task->note) }}" class="text-xs link">{{ $task->type === \App\Enums\TaskType::Quiz ? 'Generar cuestionario →' : 'Abrir documento →' }}</a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</x-app-layout>

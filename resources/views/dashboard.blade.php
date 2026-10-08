<x-app-layout title="Panel">
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-brand-600 via-violet-600 to-fuchsia-600 p-6 text-white shadow-lg sm:p-8">
        <div class="relative z-10 flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-sm text-white/80">{{ now()->translatedFormat('l, j \d\e F') }}</p>
                <h1 class="mt-1 text-2xl font-bold sm:text-3xl">¡Hola, {{ \Illuminate\Support\Str::before($user->name, ' ') }}! 👋</h1>
                <p class="mt-2 max-w-xl text-white/85">
                    @if ($stats['streak'] > 0)
                        Llevas una racha de <strong>{{ $stats['streak'] }} {{ $stats['streak'] === 1 ? 'día' : 'días' }}</strong>. ¡No la rompas hoy!
                    @else
                        Completa un cuestionario o una tarea hoy para empezar una nueva racha.
                    @endif
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('notes.create') }}" class="btn bg-white text-brand-700 hover:bg-brand-50"><x-heroicon-o-cloud-arrow-up class="h-5 w-5" /> Subir documento</a>
                <a href="{{ route('quizzes.create') }}" class="btn bg-white/15 text-white ring-1 ring-white/30 hover:bg-white/25"><x-heroicon-o-sparkles class="h-5 w-5" /> Generar cuestionario</a>
            </div>
        </div>
        <div class="pointer-events-none absolute -right-16 -top-16 h-64 w-64 rounded-full bg-white/10 blur-2xl"></div>
    </div>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat-card label="Puntos totales" :value="number_format($stats['points'])" icon="bolt" color="amber"
                     :hint="$weeklyRank['rank'] ? 'Puesto #'.$weeklyRank['rank'].' esta semana' : 'Sin clasificar esta semana'" />
        <x-stat-card label="Racha actual" :value="'🔥 '.$stats['streak']" icon="fire" color="orange" :hint="'Mejor racha: '.$stats['longest_streak'].' días'" />
        <x-stat-card label="Cuestionarios" :value="$stats['quizzes']" icon="academic-cap" color="indigo" :hint="'Media: '.$stats['avg_score'].' %'" />
        <x-stat-card label="Insignias" :value="$stats['badges'].'/'.$stats['total_badges']" icon="trophy" color="emerald" hint="Logros desbloqueados" />
    </div>

    <div class="grid gap-6 lg:grid-cols-3 lg:items-start">
        <div class="card lg:col-span-2">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4 dark:border-slate-800">
                <h2 class="card-title">Tareas para hoy</h2>
                <a href="{{ route('study-plans.index') }}" class="text-sm link">Planificador</a>
            </div>
            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($todayTasks as $task)
                    <div class="flex items-center gap-3 px-6 py-3">
                        <form method="POST" action="{{ route('study-tasks.toggle', $task) }}">
                            @csrf @method('PATCH')
                            <button class="flex h-6 w-6 items-center justify-center rounded-full border-2 border-slate-300 hover:border-emerald-500 dark:border-slate-600" title="Marcar como completada"></button>
                        </form>
                        <span class="text-lg">{{ $task->type->icon() }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">{{ $task->title }}</p>
                            <p class="text-xs muted">
                                {{ $task->plan?->title }} · {{ $task->duration_minutes }} min
                                @if ($task->isOverdue()) · <span class="text-rose-500">atrasada ({{ $task->due_date->translatedFormat('j M') }})</span> @endif
                            </p>
                        </div>
                        @if ($task->quiz_id)
                            <a href="{{ route('attempts.create', $task->quiz_id) }}" class="btn-secondary btn-sm">Practicar</a>
                        @elseif ($task->note_id)
                            <a href="{{ route('notes.show', $task->note_id) }}" class="btn-secondary btn-sm">Abrir</a>
                        @endif
                    </div>
                @empty
                    <div class="px-6 py-10 text-center">
                        <p class="text-3xl">🎯</p>
                        <p class="mt-2 text-sm muted">No tienes tareas pendientes para hoy.</p>
                        <a href="{{ route('study-plans.create') }}" class="btn-secondary btn-sm mt-4">Crear plan de estudio</a>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="space-y-6">
            @if ($upcomingPlan)
                <div class="card card-body">
                    <p class="text-xs font-semibold uppercase tracking-wider muted">Próximo examen</p>
                    <p class="mt-1 font-semibold">{{ $upcomingPlan->title }}</p>
                    <p class="text-sm muted">{{ $upcomingPlan->exam_date->translatedFormat('l j \d\e F') }}</p>
                    <p class="mt-3 text-3xl font-bold text-brand-600 dark:text-brand-400">
                        {{ $upcomingPlan->daysUntilExam() === 0 ? '¡Hoy!' : $upcomingPlan->daysUntilExam().' días' }}
                    </p>
                    <x-progress class="mt-3" :value="$upcomingPlan->progress()" />
                    <a href="{{ route('study-plans.show', $upcomingPlan) }}" class="mt-3 inline-block text-sm link">Ver plan ({{ $upcomingPlan->progress() }} %)</a>
                </div>
            @endif

            <div class="card card-body">
                <div class="flex items-center justify-between">
                    <h2 class="card-title">Insignias</h2>
                    <a href="{{ route('achievements.index') }}" class="text-sm link">Ver todas</a>
                </div>
                @if ($recentBadges->isNotEmpty())
                    <div class="mt-4 flex flex-wrap gap-3">
                        @foreach ($recentBadges as $badge)
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br text-2xl shadow-sm {{ \App\Support\Palette::gradient($badge->color) }}" title="{{ $badge->name }}">{{ $badge->icon }}</div>
                        @endforeach
                    </div>
                @endif
                @if ($nextBadge)
                    <div class="mt-4 rounded-xl bg-slate-50 p-3 dark:bg-slate-800/60">
                        <p class="text-xs muted">Siguiente logro</p>
                        <p class="text-sm font-medium">{{ $nextBadge->icon }} {{ $nextBadge->name }}</p>
                        <x-progress class="mt-2" :value="$nextBadge->percent" color="bg-emerald-500" />
                        <p class="mt-1 text-xs muted">{{ $nextBadge->current }}/{{ $nextBadge->criteria_value }} · {{ $nextBadge->description }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2 lg:items-start">
        <div class="card">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4 dark:border-slate-800">
                <h2 class="card-title">Últimos resultados</h2>
                <a href="{{ route('attempts.index') }}" class="text-sm link">Historial</a>
            </div>
            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($recentAttempts as $attempt)
                    <a href="{{ route('attempts.show', $attempt) }}" class="flex items-center gap-4 px-6 py-3 hover:bg-slate-50 dark:hover:bg-slate-800/40">
                        <div @class([
                            'flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-sm font-bold',
                            'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' => $attempt->percentage >= 75,
                            'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300' => $attempt->percentage >= 50 && $attempt->percentage < 75,
                            'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300' => $attempt->percentage < 50,
                        ])>{{ round($attempt->percentage) }}%</div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">{{ $attempt->quiz?->title }}</p>
                            <p class="text-xs muted">{{ $attempt->assessment_type->label() }} · {{ $attempt->completed_at->diffForHumans() }}</p>
                        </div>
                        <span class="text-sm font-semibold text-amber-600">+{{ $attempt->points_earned }}</span>
                    </a>
                @empty
                    <p class="px-6 py-10 text-center text-sm muted">Aún no has respondido ningún cuestionario.</p>
                @endforelse
            </div>
        </div>

        <div class="card">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4 dark:border-slate-800">
                <h2 class="card-title">Documentos recientes</h2>
                <a href="{{ route('notes.index') }}" class="text-sm link">Ver todos</a>
            </div>
            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($recentNotes as $note)
                    <a href="{{ route('notes.show', $note) }}" class="flex items-center gap-4 px-6 py-3 hover:bg-slate-50 dark:hover:bg-slate-800/40">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-800">
                            <x-heroicon-o-document-text class="h-5 w-5 text-slate-500" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">{{ $note->title }}</p>
                            <p class="text-xs muted">{{ $note->sourceLabel() }} · {{ $note->created_at->diffForHumans() }}</p>
                        </div>
                    </a>
                @empty
                    <div class="px-6 py-10 text-center">
                        <p class="text-sm muted">Sube tu primer documento para empezar.</p>
                        <a href="{{ route('notes.create') }}" class="btn-primary btn-sm mt-4">Subir documento</a>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>

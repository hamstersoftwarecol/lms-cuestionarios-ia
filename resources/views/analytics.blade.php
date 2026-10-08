<x-app-layout title="Mi analítica">
    @php($t = $data['totals'])
    <x-page-header title="Analítica de aprendizaje" subtitle="Tu evolución, tus puntos fuertes y lo que conviene repasar." />

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat-card label="Cuestionarios" :value="$t['attempts']" icon="academic-cap" color="indigo" />
        <x-stat-card label="Nota media" :value="$t['avg_score'].' %'" icon="chart-bar" color="emerald" :hint="'Mejor: '.$t['best_score'].' %'" />
        <x-stat-card label="Tiempo total" :value="$t['study_minutes'].' min'" icon="clock" color="sky" />
        <x-stat-card label="Tareas del plan" :value="$t['tasks_completed'].'/'.$t['tasks_total']" icon="check-circle" color="amber" />
    </div>

    @if ($t['attempts'] === 0)
        <x-empty-state icon="chart-bar" title="Aún no hay datos" description="Completa algunos cuestionarios para ver tu analítica de aprendizaje.">
            <a href="{{ route('quizzes.index') }}" class="btn-primary">Ir a cuestionarios</a>
        </x-empty-state>
    @else
        <div class="grid gap-6 lg:grid-cols-3">
            <div class="card card-body lg:col-span-2">
                <h2 class="card-title">Evolución de tus resultados</h2>
                <div class="mt-4 h-64">
                    <canvas data-chart="{{ json_encode(['type' => 'line', 'max' => 100, 'labels' => $data['trend']->pluck('label'), 'datasets' => [['label' => '% de aciertos', 'data' => $data['trend']->pluck('score')]]]) }}"></canvas>
                </div>
            </div>
            <div class="card card-body">
                <h2 class="card-title">Actividad (14 días)</h2>
                <div class="mt-4 h-64">
                    <canvas data-chart="{{ json_encode(['type' => 'bar', 'labels' => $data['activity']['labels'], 'datasets' => [['label' => 'Cuestionarios', 'data' => $data['activity']['values'], 'color' => '#10b981']]]) }}"></canvas>
                </div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="card card-body">
                <h2 class="card-title">Aciertos por dificultad</h2>
                @php($difficulty = collect(\App\Enums\Difficulty::forQuestions())->mapWithKeys(fn ($d) => [$d->label() => $data['by_difficulty'][$d->value] ?? 0]))
                <div class="mt-4 h-56">
                    <canvas data-chart="{{ json_encode(['type' => 'bar', 'max' => 100, 'labels' => $difficulty->keys(), 'datasets' => [['label' => '% de aciertos', 'data' => $difficulty->values(), 'colors' => ['#10b981', '#f59e0b', '#f43f5e']]]]) }}"></canvas>
                </div>
            </div>
            <div class="card card-body">
                <h2 class="card-title">Aciertos por tipo</h2>
                <div class="mt-4 space-y-4">
                    @foreach (\App\Enums\QuestionType::cases() as $type)
                        @php($value = $data['by_type'][$type->value] ?? 0)
                        <div>
                            <div class="flex justify-between text-sm"><span>{{ $type->label() }} ({{ $type->shortLabel() }})</span><span class="font-semibold">{{ $value }}%</span></div>
                            <x-progress :value="$value" class="mt-1" :color="$type === \App\Enums\QuestionType::Sata ? 'bg-violet-500' : 'bg-brand-600'" />
                        </div>
                    @endforeach
                    @if ($data['confidence']['avg_confidence'] !== null)
                        <div class="rounded-xl bg-slate-50 p-3 text-sm dark:bg-slate-800/60">
                            <p class="font-medium">Calibración de confianza</p>
                            <p class="mt-1 muted">Confianza media declarada: <strong>{{ $data['confidence']['avg_confidence'] }}%</strong> · resultado medio: <strong>{{ $data['confidence']['avg_score'] }}%</strong></p>
                        </div>
                    @endif
                </div>
            </div>
            <div class="card card-body">
                <h2 class="card-title">Temas a reforzar</h2>
                <ul class="mt-4 space-y-3">
                    @foreach ($data['weakest'] as $row)
                        <li>
                            <div class="flex justify-between gap-2 text-sm"><a href="{{ route('quizzes.show', $row->quiz_id) }}" class="truncate link">{{ $row->quiz?->title ?? 'Cuestionario eliminado' }}</a><span class="shrink-0 font-semibold">{{ round($row->avg_score) }}%</span></div>
                            <x-progress :value="$row->avg_score" class="mt-1" :color="$row->avg_score >= 75 ? 'bg-emerald-500' : ($row->avg_score >= 50 ? 'bg-amber-500' : 'bg-rose-500')" />
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="card card-body">
            <h2 class="card-title">📈 Evaluación previa vs. posterior</h2>
            @if ($data['pre_post']->isEmpty())
                <p class="mt-2 text-sm muted">Haz una <strong>evaluación previa</strong> antes de estudiar un tema y una <strong>posterior</strong> después para medir cuánto aprendiste.</p>
            @else
                <div class="mt-4 h-72">
                    <canvas data-chart="{{ json_encode([
                        'type' => 'bar',
                        'max' => 100,
                        'labels' => $data['pre_post']->map(fn ($r) => \Illuminate\Support\Str::limit($r['quiz']?->title ?? '—', 24)),
                        'datasets' => [
                            ['label' => 'Previa', 'data' => $data['pre_post']->pluck('pre'), 'color' => '#f59e0b'],
                            ['label' => 'Posterior', 'data' => $data['pre_post']->pluck('post'), 'color' => '#10b981'],
                        ],
                    ]) }}"></canvas>
                </div>
            @endif
        </div>
    @endif
</x-app-layout>

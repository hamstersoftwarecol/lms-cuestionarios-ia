<x-app-layout title="Analítica global">
    @php($k = $data['kpis'])
    <x-page-header title="Panel de administración" subtitle="Visión general de la plataforma en los últimos 30 días.">
        <a href="{{ route('admin.backups.index') }}" class="btn-secondary"><x-heroicon-o-circle-stack class="h-4 w-4" /> Copias</a>
        <a href="{{ route('admin.announcements.create') }}" class="btn-primary"><x-heroicon-o-megaphone class="h-4 w-4" /> Nuevo anuncio</a>
    </x-page-header>

    <div class="flex flex-wrap items-center gap-3 rounded-2xl border px-4 py-3 text-sm {{ \App\Support\Palette::alert($gemini['configured'] ? 'success' : 'warning') }}">
        <span class="text-lg">{{ $gemini['configured'] ? '✅' : '⚠️' }}</span>
        @if ($gemini['configured'])
            Google Gemini conectado · modelo <code>{{ $gemini['model'] }}</code> · TTS <code>{{ $gemini['tts'] }}</code>
        @else
            Gemini no está configurado: añade <code>GEMINI_API_KEY</code> en <code>.env</code> para activar el escaneo, la generación de cuestionarios y la voz con IA.
        @endif
    </div>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat-card label="Usuarios" :value="number_format($k['users'])" icon="users" color="indigo" :hint="'+'.$k['new_users_week'].' esta semana'" />
        <x-stat-card label="Activos hoy" :value="$k['active_today']" icon="bolt" color="emerald" :hint="$activeSessions.' sesiones abiertas'" />
        <x-stat-card label="Intentos" :value="number_format($k['attempts'])" icon="academic-cap" color="amber" :hint="'Media: '.$k['avg_score'].' %'" />
        <x-stat-card label="Peticiones IA (30 d)" :value="number_format($k['ai_requests'])" icon="cpu-chip" color="violet" :hint="$k['ai_success_rate'].' % éxito · '.number_format($k['ai_tokens']).' tokens'" />
        <x-stat-card label="Documentos" :value="number_format($k['notes'])" icon="document-text" color="sky" />
        <x-stat-card label="Cuestionarios" :value="number_format($k['quizzes'])" icon="clipboard-document-list" color="rose" />
        <x-stat-card label="Preguntas" :value="number_format($k['questions'])" icon="light-bulb" color="teal" />
        <x-stat-card label="Nota media" :value="$k['avg_score'].' %'" icon="chart-bar" color="orange" />
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="card card-body">
            <h2 class="card-title">Registros de usuarios</h2>
            <div class="mt-4 h-64"><canvas data-chart="{{ json_encode(['type' => 'line', 'labels' => $data['registrations']['labels'], 'datasets' => [['label' => 'Nuevos usuarios', 'data' => $data['registrations']['values']]]]) }}"></canvas></div>
        </div>
        <div class="card card-body">
            <h2 class="card-title">Cuestionarios completados</h2>
            <div class="mt-4 h-64"><canvas data-chart="{{ json_encode(['type' => 'bar', 'labels' => $data['attempts']['labels'], 'datasets' => [['label' => 'Intentos', 'data' => $data['attempts']['values'], 'color' => '#10b981']]]) }}"></canvas></div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card card-body">
            <h2 class="card-title">Uso de IA por tipo</h2>
            <div class="mt-4 h-56">
                @if ($data['ai_by_type']->isEmpty())
                    <p class="py-16 text-center text-sm muted">Sin peticiones a la IA todavía.</p>
                @else
                    <canvas data-chart="{{ json_encode(['type' => 'doughnut', 'labels' => $data['ai_by_type']->keys(), 'datasets' => [['label' => 'Peticiones', 'data' => $data['ai_by_type']->values()]]]) }}"></canvas>
                @endif
            </div>
        </div>
        <div class="card card-body">
            <h2 class="card-title">Distribución de notas</h2>
            <div class="mt-4 h-56"><canvas data-chart="{{ json_encode(['type' => 'bar', 'labels' => array_keys($data['score_distribution']), 'datasets' => [['label' => 'Intentos', 'data' => array_values($data['score_distribution']), 'colors' => ['#f43f5e', '#f97316', '#f59e0b', '#84cc16', '#10b981']]]]) }}"></canvas></div>
        </div>
        <div class="card card-body">
            <h2 class="card-title">Tipos de pregunta</h2>
            <div class="mt-4 h-56">
                @if ($data['question_types']->isEmpty())
                    <p class="py-16 text-center text-sm muted">Sin preguntas todavía.</p>
                @else
                    <canvas data-chart="{{ json_encode(['type' => 'pie', 'labels' => $data['question_types']->keys(), 'datasets' => [['label' => 'Preguntas', 'data' => $data['question_types']->values()]]]) }}"></canvas>
                @endif
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card card-body">
            <h2 class="card-title">Cuestionarios más populares</h2>
            <ul class="mt-4 space-y-3 text-sm">
                @forelse ($data['top_quizzes'] as $quiz)
                    <li class="flex items-center justify-between gap-2">
                        <div class="min-w-0"><p class="truncate font-medium">{{ $quiz->title }}</p><p class="text-xs muted">{{ $quiz->user?->name }}</p></div>
                        <div class="shrink-0 text-right"><p class="font-semibold">{{ $quiz->attempts_count }}</p><p class="text-xs muted">{{ round($quiz->attempts_avg_percentage ?? 0) }}%</p></div>
                    </li>
                @empty
                    <p class="muted">Sin datos.</p>
                @endforelse
            </ul>
        </div>
        <div class="card card-body">
            <h2 class="card-title">Usuarios con más puntos</h2>
            <ul class="mt-4 space-y-3 text-sm">
                @foreach ($data['top_users'] as $user)
                    <li class="flex items-center gap-3">
                        <x-avatar :user="$user" class="h-8 w-8" />
                        <a href="{{ route('admin.users.show', $user) }}" class="min-w-0 flex-1 truncate font-medium hover:text-brand-600">{{ $user->name }}</a>
                        <span class="font-semibold text-amber-600">{{ number_format($user->points) }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="card card-body">
            <div class="flex items-center justify-between">
                <h2 class="card-title">Actividad reciente</h2>
                <a href="{{ route('admin.activity.index') }}" class="text-sm link">Auditoría</a>
            </div>
            <ul class="mt-4 space-y-3 text-sm">
                @foreach ($data['recent_activity'] as $log)
                    <li>
                        <p class="truncate"><span class="font-medium">{{ $log->user?->name ?? 'Sistema' }}</span> <span class="muted">{{ $log->description }}</span></p>
                        <p class="text-xs text-slate-400">{{ $log->created_at->diffForHumans() }}</p>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</x-app-layout>

@php
    $user = auth()->user();
    $nav = [
        ['route' => 'dashboard', 'match' => 'dashboard', 'icon' => 'squares-2x2', 'label' => 'Panel'],
        ['route' => 'notes.index', 'match' => 'notes.*', 'icon' => 'document-text', 'label' => 'Mis documentos'],
        ['route' => 'quizzes.index', 'match' => 'quizzes.*', 'icon' => 'academic-cap', 'label' => 'Cuestionarios'],
        ['route' => 'attempts.index', 'match' => 'attempts.*', 'icon' => 'clock', 'label' => 'Historial'],
        ['route' => 'study-plans.index', 'match' => 'study-plans.*', 'icon' => 'calendar-days', 'label' => 'Planificador'],
        ['route' => 'groups.index', 'match' => 'groups.*', 'icon' => 'user-group', 'label' => 'Grupos de estudio'],
        ['route' => 'leaderboard', 'match' => 'leaderboard', 'icon' => 'trophy', 'label' => 'Clasificación'],
        ['route' => 'achievements.index', 'match' => 'achievements.*', 'icon' => 'star', 'label' => 'Logros'],
        ['route' => 'analytics', 'match' => 'analytics', 'icon' => 'chart-bar', 'label' => 'Mi analítica'],
    ];
    $admin = [
        ['route' => 'admin.dashboard', 'match' => 'admin.dashboard', 'icon' => 'presentation-chart-line', 'label' => 'Analítica global'],
        ['route' => 'admin.users.index', 'match' => 'admin.users.*', 'icon' => 'users', 'label' => 'Usuarios'],
        ['route' => 'admin.quizzes.index', 'match' => 'admin.quizzes.*', 'icon' => 'clipboard-document-list', 'label' => 'Cuestionarios'],
        ['route' => 'admin.sessions.index', 'match' => 'admin.sessions.*', 'icon' => 'computer-desktop', 'label' => 'Sesiones'],
        ['route' => 'admin.activity.index', 'match' => 'admin.activity.*', 'icon' => 'finger-print', 'label' => 'Auditoría'],
        ['route' => 'admin.announcements.index', 'match' => 'admin.announcements.*', 'icon' => 'megaphone', 'label' => 'Anuncios'],
        ['route' => 'admin.backups.index', 'match' => 'admin.backups.*', 'icon' => 'circle-stack', 'label' => 'Copias de seguridad'],
    ];
@endphp

<div class="flex h-16 shrink-0 items-center justify-between gap-2 px-5">
    <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
        <x-application-logo class="h-9 w-9" />
        <span class="text-lg font-bold tracking-tight">{{ config('app.name') }}</span>
    </a>
    <button type="button" class="btn-ghost p-2 lg:hidden" @click="sidebar = false" aria-label="Cerrar menú">
        <x-heroicon-o-x-mark class="h-5 w-5" />
    </button>
</div>

<nav class="flex-1 space-y-6 overflow-y-auto px-3 pb-4">
    <div class="space-y-1">
        <a href="{{ route('notes.create') }}" class="btn-primary mb-3 w-full">
            <x-heroicon-o-sparkles class="h-5 w-5" /> Escanear documento
        </a>
        @foreach ($nav as $item)
            <a href="{{ route($item['route']) }}" @class(['nav-item', 'nav-item-active' => request()->routeIs($item['match'])])>
                <x-dynamic-component :component="'heroicon-o-'.$item['icon']" class="h-5 w-5 shrink-0" />
                {{ $item['label'] }}
            </a>
        @endforeach
    </div>

    @if ($user?->isAdmin())
        <div class="space-y-1">
            <p class="px-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Administración</p>
            @foreach ($admin as $item)
                <a href="{{ route($item['route']) }}" @class(['nav-item', 'nav-item-active' => request()->routeIs($item['match'])])>
                    <x-dynamic-component :component="'heroicon-o-'.$item['icon']" class="h-5 w-5 shrink-0" />
                    {{ $item['label'] }}
                </a>
            @endforeach
        </div>
    @endif
</nav>

@if ($user)
    <div class="border-t border-slate-200 p-4 dark:border-slate-800">
        <div class="flex items-center justify-around rounded-xl bg-slate-50 py-2 text-center dark:bg-slate-800/60">
            <div>
                <p class="text-sm font-bold">{{ number_format($user->points) }}</p>
                <p class="text-[11px] muted">puntos</p>
            </div>
            <div class="h-8 w-px bg-slate-200 dark:bg-slate-700"></div>
            <div>
                <p class="text-sm font-bold">🔥 {{ app(\App\Services\GamificationService::class)->effectiveStreak($user) }}</p>
                <p class="text-[11px] muted">racha</p>
            </div>
        </div>
    </div>
@endif

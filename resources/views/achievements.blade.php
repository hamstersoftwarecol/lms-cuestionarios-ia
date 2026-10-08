<x-app-layout title="Logros">
    <x-page-header title="Logros e insignias" :subtitle="$earnedCount.' de '.$badges->count().' insignias desbloqueadas'" />

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-stat-card label="Racha actual" :value="'🔥 '.$streak.' '.($streak === 1 ? 'día' : 'días')" icon="fire" color="orange" />
        <x-stat-card label="Mejor racha" :value="$longestStreak.' días'" icon="bolt" color="amber" />
        <x-stat-card label="Puntos" :value="number_format($points)" icon="star" color="indigo" />
    </div>

    {{-- Calendario de actividad (12 semanas) --}}
    <div class="card card-body">
        <h2 class="card-title">Actividad de las últimas 12 semanas</h2>
        <div class="mt-4 overflow-x-auto">
            <div class="grid w-max grid-flow-col grid-rows-7 gap-1">
                @php($start = today()->subDays(83)->startOfWeek())
                @for ($day = $start->copy(); $day->lte(today()); $day->addDay())
                    @php($count = $calendar[$day->toDateString()] ?? 0)
                    <div title="{{ $day->translatedFormat('j M') }}: {{ $count }} cuestionarios"
                         @class([
                             'h-3.5 w-3.5 rounded-sm',
                             'bg-slate-200 dark:bg-slate-800' => $count === 0,
                             'bg-emerald-200 dark:bg-emerald-900' => $count === 1,
                             'bg-emerald-400 dark:bg-emerald-700' => $count === 2,
                             'bg-emerald-600 dark:bg-emerald-500' => $count >= 3,
                         ])></div>
                @endfor
            </div>
        </div>
        <div class="mt-3 flex items-center gap-2 text-xs muted">
            Menos <span class="h-3 w-3 rounded-sm bg-slate-200 dark:bg-slate-800"></span><span class="h-3 w-3 rounded-sm bg-emerald-200 dark:bg-emerald-900"></span><span class="h-3 w-3 rounded-sm bg-emerald-400 dark:bg-emerald-700"></span><span class="h-3 w-3 rounded-sm bg-emerald-600 dark:bg-emerald-500"></span> Más
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($badges as $badge)
            <div @class(['card card-body flex items-start gap-4', 'opacity-70' => ! $badge->earned_at])>
                <div @class([
                    'flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl text-3xl shadow-sm',
                    'bg-gradient-to-br '.\App\Support\Palette::gradient($badge->color) => $badge->earned_at,
                    'bg-slate-100 grayscale dark:bg-slate-800' => ! $badge->earned_at,
                ])>{{ $badge->icon }}</div>
                <div class="min-w-0 flex-1">
                    <p class="font-semibold">{{ $badge->name }}</p>
                    <p class="text-sm muted">{{ $badge->description }}</p>
                    @if ($badge->earned_at)
                        <p class="mt-2 text-xs font-medium text-emerald-600">✓ Desbloqueada el {{ \Illuminate\Support\Carbon::parse($badge->earned_at)->translatedFormat('j M Y') }}</p>
                    @else
                        <x-progress :value="$badge->percent" class="mt-3" color="bg-brand-500" />
                        <p class="mt-1 text-xs muted">{{ $badge->current }} / {{ $badge->criteria_value }}</p>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</x-app-layout>

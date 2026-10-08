<x-app-layout title="Clasificación">
    <x-page-header title="Tabla de clasificación" :subtitle="$since ? 'Puntos ganados desde el '.$since->translatedFormat('j \d\e F') : 'Puntos de todos los tiempos'">
        <div class="inline-flex rounded-xl bg-slate-100 p-1 dark:bg-slate-800">
            @foreach ($periods as $key => $label)
                <a href="{{ route('leaderboard', ['period' => $key]) }}" @class(['rounded-lg px-4 py-1.5 text-sm font-medium', 'bg-white shadow dark:bg-slate-700' => $period === $key, 'muted' => $period !== $key])>{{ $label }}</a>
            @endforeach
        </div>
    </x-page-header>

    <div class="card card-body flex flex-col items-center justify-between gap-3 sm:flex-row">
        <div class="flex items-center gap-3">
            <x-avatar :user="auth()->user()" class="h-10 w-10" />
            <div>
                <p class="font-semibold">Tu posición</p>
                <p class="text-sm muted">{{ $me['rank'] ? 'Puesto #'.$me['rank'] : 'Aún sin clasificar en este periodo' }}</p>
            </div>
        </div>
        <p class="text-2xl font-bold text-amber-600">{{ number_format($me['points']) }} pts</p>
    </div>

    @if ($ranking->isEmpty())
        <x-empty-state icon="trophy" title="La clasificación está vacía" description="Completa cuestionarios para sumar puntos y aparecer aquí.">
            <a href="{{ route('quizzes.index') }}" class="btn-primary">Ir a cuestionarios</a>
        </x-empty-state>
    @else
        {{-- Podio --}}
        <div class="grid grid-cols-3 items-end gap-3 sm:gap-6">
            @foreach ([1, 0, 2] as $position)
                @if ($row = $ranking->get($position))
                    <div class="flex flex-col items-center text-center">
                        <x-avatar :user="$row->user" :class="\Illuminate\Support\Arr::toCssClasses(['ring-4', 'h-20 w-20 ring-amber-400' => $position === 0, 'h-16 w-16 ring-slate-300' => $position === 1, 'h-16 w-16 ring-orange-300' => $position === 2])" />
                        <p class="mt-2 max-w-full truncate text-sm font-semibold">{{ $row->user->name }}</p>
                        <p class="text-xs font-bold text-amber-600">{{ number_format($row->points) }} pts</p>
                        <div @class([
                            'mt-3 flex w-full items-start justify-center rounded-t-2xl pt-3 text-3xl text-white',
                            'h-32 bg-gradient-to-b from-amber-400 to-amber-500' => $position === 0,
                            'h-24 bg-gradient-to-b from-slate-300 to-slate-400' => $position === 1,
                            'h-20 bg-gradient-to-b from-orange-300 to-orange-400' => $position === 2,
                        ])>{{ ['🥇', '🥈', '🥉'][$position] }}</div>
                    </div>
                @else
                    <div></div>
                @endif
            @endforeach
        </div>

        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table-basic">
                    <thead class="bg-slate-50 dark:bg-slate-800/50">
                        <tr><th class="w-16">#</th><th>Usuario</th><th class="text-right">Cuestionarios</th><th class="text-right">Media</th><th class="text-right">Puntos</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($ranking as $row)
                            <tr @class(['bg-brand-50/60 dark:bg-brand-500/5' => $row->user->id === auth()->id()])>
                                <td class="font-bold">{{ $row->rank <= 3 ? ['🥇', '🥈', '🥉'][$row->rank - 1] : $row->rank }}</td>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <x-avatar :user="$row->user" class="h-8 w-8" />
                                        <span class="font-medium">{{ $row->user->name }}</span>
                                        @if ($row->user->id === auth()->id()) <x-pill color="indigo">Tú</x-pill> @endif
                                    </div>
                                </td>
                                <td class="text-right">{{ $row->quizzes }}</td>
                                <td class="text-right">{{ $row->avg_score }}%</td>
                                <td class="text-right font-bold text-amber-600">{{ number_format($row->points) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</x-app-layout>

<x-app-layout title="Cuestionarios">
    <x-page-header title="Cuestionarios" subtitle="Tus cuestionarios, los compartidos en tus grupos y los públicos.">
        <a href="{{ route('quizzes.create') }}" class="btn-primary"><x-heroicon-o-sparkles class="h-5 w-5" /> Generar con IA</a>
    </x-page-header>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="inline-flex rounded-xl bg-slate-100 p-1 dark:bg-slate-800">
            @foreach (['mine' => 'Míos', 'shared' => 'De mis grupos', 'public' => 'Públicos'] as $key => $label)
                <a href="{{ route('quizzes.index', ['tab' => $key]) }}"
                   @class(['rounded-lg px-4 py-1.5 text-sm font-medium', 'bg-white shadow dark:bg-slate-700' => $tab === $key, 'muted' => $tab !== $key])>{{ $label }}</a>
            @endforeach
        </div>
        <form method="GET" class="relative sm:w-72">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <x-heroicon-o-magnifying-glass class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
            <input type="search" name="q" value="{{ request('q') }}" class="input pl-9" placeholder="Buscar cuestionario…">
        </form>
    </div>

    @if ($quizzes->isEmpty())
        <x-empty-state icon="academic-cap" title="No hay cuestionarios" description="Genera tu primer cuestionario con IA a partir de uno de tus documentos.">
            <a href="{{ route('quizzes.create') }}" class="btn-primary">Generar cuestionario</a>
        </x-empty-state>
    @else
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($quizzes as $quiz)
                <div class="card flex flex-col p-5">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <x-pill :color="$quiz->difficulty->color()">{{ $quiz->difficulty->label() }}</x-pill>
                        @if ($quiz->generated_by_ai) <x-pill color="violet">✨ IA</x-pill> @endif
                        @if ($quiz->is_public) <x-pill color="sky">Público</x-pill> @endif
                        @if ($quiz->time_limit) <x-pill color="slate">⏱ {{ $quiz->time_limit }} min</x-pill> @endif
                    </div>
                    <a href="{{ route('quizzes.show', $quiz) }}" class="mt-3 line-clamp-2 font-semibold hover:text-brand-600">{{ $quiz->title }}</a>
                    <p class="mt-1 line-clamp-2 flex-1 text-sm muted">{{ $quiz->description ?: ($quiz->note ? 'Basado en «'.$quiz->note->title.'»' : 'Cuestionario manual') }}</p>
                    <div class="mt-4 flex items-center justify-between text-xs muted">
                        <span>{{ $quiz->questions_count }} preguntas · {{ $quiz->user->name }}</span>
                        @if ($quiz->my_best !== null)
                            <span class="font-semibold text-emerald-600">Mejor: {{ round($quiz->my_best) }}%</span>
                        @endif
                    </div>
                    <div class="mt-4 flex gap-2 border-t border-slate-100 pt-4 dark:border-slate-800">
                        <a href="{{ route('attempts.create', $quiz) }}" class="btn-primary btn-sm flex-1"><x-heroicon-o-play class="h-4 w-4" /> Empezar</a>
                        <a href="{{ route('quizzes.show', $quiz) }}" class="btn-secondary btn-sm">Detalles</a>
                    </div>
                </div>
            @endforeach
        </div>
        {{ $quizzes->links() }}
    @endif
</x-app-layout>

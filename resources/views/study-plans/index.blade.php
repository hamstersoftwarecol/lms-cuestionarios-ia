<x-app-layout title="Planificador de estudio">
    <x-page-header title="Planificador de estudio" subtitle="Indica la fecha de tu examen y generamos tareas diarias automáticamente.">
        <a href="{{ route('study-plans.create') }}" class="btn-primary"><x-heroicon-o-plus class="h-5 w-5" /> Nuevo plan</a>
    </x-page-header>

    @if ($plans->isEmpty())
        <x-empty-state icon="calendar-days" title="Aún no tienes planes" description="Crea un plan con la fecha del examen y los temas a estudiar: repartiremos el estudio, las autoevaluaciones, los repasos y un simulacro final.">
            <a href="{{ route('study-plans.create') }}" class="btn-primary">Crear mi primer plan</a>
        </x-empty-state>
    @else
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($plans as $plan)
                @php($days = $plan->daysUntilExam())
                <a href="{{ route('study-plans.show', $plan) }}" class="card flex flex-col p-5 transition hover:-translate-y-0.5 hover:shadow-md">
                    <div class="flex items-start justify-between gap-2">
                        <h2 class="font-semibold">{{ $plan->title }}</h2>
                        @if ($days < 0)
                            <x-pill color="slate">Finalizado</x-pill>
                        @elseif ($days === 0)
                            <x-pill color="rose">¡Hoy!</x-pill>
                        @else
                            <x-pill :color="$days <= 3 ? 'rose' : ($days <= 7 ? 'amber' : 'emerald')">{{ $days }} {{ $days === 1 ? 'día' : 'días' }}</x-pill>
                        @endif
                    </div>
                    <p class="mt-1 text-sm muted">Examen: {{ $plan->exam_date->translatedFormat('l j \d\e F Y') }}</p>
                    <div class="mt-4">
                        <div class="mb-1 flex justify-between text-xs muted"><span>{{ $plan->completed_tasks_count }}/{{ $plan->tasks_count }} tareas</span><span>{{ $plan->progress() }}%</span></div>
                        <x-progress :value="$plan->progress()" />
                    </div>
                    <p class="mt-3 text-xs muted">{{ $plan->daily_minutes }} min/día</p>
                </a>
            @endforeach
        </div>
    @endif
</x-app-layout>

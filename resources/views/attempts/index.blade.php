<x-app-layout title="Historial de intentos">
    <x-page-header title="Historial de intentos" subtitle="Todos tus resultados. Exporta a CSV, Excel, PDF o imprime." />

    <div class="card card-body">
        @if ($attempts->isEmpty())
            <p class="py-8 text-center text-sm muted">Todavía no has completado ningún cuestionario.</p>
        @else
            <table data-datatable data-export-title="Historial de cuestionarios" data-order='[[0, "desc"]]' class="hover w-full">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Cuestionario</th>
                        <th>Tipo</th>
                        <th>Aciertos</th>
                        <th>%</th>
                        <th>Puntos</th>
                        <th>Tiempo</th>
                        <th>Confianza</th>
                        <th class="no-export no-sort"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($attempts as $attempt)
                        <tr>
                            <td data-order="{{ $attempt->completed_at->timestamp }}">{{ $attempt->completed_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $attempt->quiz?->title ?? '—' }}</td>
                            <td>{{ $attempt->assessment_type->label() }}</td>
                            <td>{{ $attempt->score }}/{{ $attempt->total_questions }}</td>
                            <td data-order="{{ $attempt->percentage }}">{{ round($attempt->percentage, 1) }}%</td>
                            <td>{{ $attempt->points_earned }}</td>
                            <td data-order="{{ $attempt->time_spent }}">{{ gmdate('H:i:s', $attempt->time_spent) }}</td>
                            <td>{{ $attempt->confidence_before ? $attempt->confidence_before.'/5' : '—' }}{{ $attempt->confidence_after ? ' → '.$attempt->confidence_after.'/5' : '' }}</td>
                            <td><a href="{{ route('attempts.show', $attempt) }}" class="link">Ver</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</x-app-layout>

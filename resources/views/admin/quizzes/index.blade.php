<x-app-layout title="Cuestionarios (admin)">
    <x-page-header title="Todos los cuestionarios" subtitle="Modera el contenido generado por los usuarios." />

    <div class="card card-body">
        <table data-datatable data-export-title="Cuestionarios" data-order='[[6, "desc"]]' class="display w-full">
            <thead>
                <tr><th>Título</th><th>Autor</th><th>Preguntas</th><th>Intentos</th><th>Media</th><th>Visibilidad</th><th>Creado</th><th class="no-export no-sort"></th></tr>
            </thead>
            <tbody>
                @foreach ($quizzes as $quiz)
                    <tr>
                        <td><a href="{{ route('quizzes.show', $quiz) }}" class="font-medium hover:text-brand-600">{{ $quiz->title }}</a> @if ($quiz->generated_by_ai) ✨ @endif</td>
                        <td>{{ $quiz->user?->name }}</td>
                        <td>{{ $quiz->questions_count }}</td>
                        <td>{{ $quiz->attempts_count }}</td>
                        <td>{{ round($quiz->attempts_avg_percentage ?? 0) }}%</td>
                        <td>{{ $quiz->is_public ? 'Público' : 'Privado' }}</td>
                        <td data-order="{{ $quiz->created_at->timestamp }}">{{ $quiz->created_at->format('d/m/Y') }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.quizzes.destroy', $quiz) }}" onsubmit="return confirm('¿Eliminar este cuestionario?')">
                                @csrf @method('DELETE')
                                <button class="btn-ghost btn-sm text-rose-600"><x-heroicon-o-trash class="h-4 w-4" /></button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-app-layout>

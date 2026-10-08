<?php

namespace App\Services;

use App\Enums\TaskType;
use App\Models\StudyPlan;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Genera tareas diarias a partir de la fecha del examen siguiendo tres fases:
 *
 *  1. Aprendizaje (~65 % de los días): estudiar cada tema y autoevaluarse al día siguiente.
 *  2. Repaso espaciado: volver sobre cada tema de forma rotativa.
 *  3. Simulacro general el último día antes del examen.
 */
class StudyPlanGenerator
{
    /**
     * Regenera las tareas pendientes del plan desde hoy (o desde el inicio si es futuro).
     * Las tareas ya completadas se conservan.
     */
    public function generate(StudyPlan $plan): int
    {
        $plan->loadMissing('notes.quizzes');

        $from = CarbonImmutable::parse(max($plan->start_date->toDateString(), today()->toDateString()));
        $exam = CarbonImmutable::parse($plan->exam_date);
        $topics = $this->topics($plan);
        $tasks = collect();

        if ($exam->gt($from) && $topics->isNotEmpty()) {
            $days = collect(CarbonPeriod::create($from, $exam->subDay()))->map(fn ($day) => CarbonImmutable::parse($day))->values();
            $tasks = $this->buildTasks($days, $topics);
        }

        $tasks->push($this->task($exam, TaskType::Exam, "Día del examen: {$plan->title}", '¡Mucho éxito! Descansa bien y repasa solo tus notas clave.'));

        $tasks = $this->assignDurations($tasks, $plan->daily_minutes);

        return DB::transaction(function () use ($plan, $tasks) {
            $plan->tasks()->where('is_completed', false)->delete();

            foreach ($tasks as $task) {
                $plan->tasks()->create($task + ['user_id' => $plan->user_id]);
            }

            return $tasks->count();
        });
    }

    /**
     * @return Collection<int, array{title: string, note_id: int|null, quiz_id: int|null}>
     */
    private function topics(StudyPlan $plan): Collection
    {
        $fromNotes = $plan->notes->map(fn ($note) => [
            'title' => $note->title,
            'note_id' => $note->id,
            'quiz_id' => $note->quizzes->where('user_id', $plan->user_id)->sortByDesc('id')->first()?->id,
        ]);

        $fromText = collect($plan->topics ?? [])
            ->map(fn ($topic) => trim((string) $topic))
            ->filter()
            ->map(fn (string $topic) => ['title' => $topic, 'note_id' => null, 'quiz_id' => null]);

        return $fromNotes->concat($fromText)->values();
    }

    /**
     * @param  Collection<int, CarbonImmutable>  $days
     * @param  Collection<int, array{title: string, note_id: int|null, quiz_id: int|null}>  $topics
     * @return Collection<int, array<string, mixed>>
     */
    private function buildTasks(Collection $days, Collection $topics): Collection
    {
        $tasks = collect();
        $total = $days->count();

        if ($total === 1) {
            foreach ($topics as $topic) {
                $tasks->push($this->task($days[0], TaskType::Review, "Repaso intensivo: {$topic['title']}", 'Revisa los conceptos clave y tus apuntes.', $topic));
            }

            return $tasks->push($this->task($days[0], TaskType::Mock, 'Simulacro general', 'Responde un cuestionario de práctica de todos los temas.'));
        }

        $mockDay = $days->last();
        $studyDays = $days->slice(0, $total - 1)->values();
        $learnLength = max(1, (int) round($studyDays->count() * 0.65));
        $learnDays = $studyDays->slice(0, $learnLength)->values();
        $reviewDays = $studyDays->slice($learnLength)->values();

        foreach ($topics->values() as $i => $topic) {
            $dayIndex = (int) floor($i * $learnDays->count() / $topics->count());
            $quizIndex = min($dayIndex + 1, $learnDays->count() - 1);

            $tasks->push($this->task($learnDays[$dayIndex], TaskType::Study, "Estudiar: {$topic['title']}", 'Lee el material con atención, subraya y toma notas. Usa el lector de voz si te ayuda.', $topic));
            $tasks->push($this->task(
                $learnDays[$quizIndex],
                TaskType::Quiz,
                "Autoevaluación: {$topic['title']}",
                $topic['quiz_id'] ? 'Responde el cuestionario asociado y revisa las explicaciones.' : 'Genera un cuestionario con IA a partir del material y respóndelo.',
                $topic,
            ));
        }

        // Repaso espaciado rotativo: cada día de repaso cubre uno o más temas.
        if ($reviewDays->isNotEmpty()) {
            $perDay = max(1, (int) ceil($topics->count() / $reviewDays->count()));
            $cursor = 0;

            foreach ($reviewDays as $day) {
                for ($n = 0; $n < $perDay; $n++) {
                    $topic = $topics[$cursor % $topics->count()];
                    $tasks->push($this->task($day, TaskType::Review, "Repasar: {$topic['title']}", 'Repaso activo: explica el tema con tus palabras y vuelve a responder el cuestionario.', $topic));
                    $cursor++;
                }
            }
        }

        return $tasks->push($this->task($mockDay, TaskType::Mock, 'Simulacro general', 'Simula el examen: responde cuestionarios de todos los temas con tiempo limitado.'));
    }

    /**
     * Reparte los minutos diarios entre las tareas de cada día (mínimo 10 minutos por tarea).
     *
     * @param  Collection<int, array<string, mixed>>  $tasks
     * @return Collection<int, array<string, mixed>>
     */
    private function assignDurations(Collection $tasks, int $dailyMinutes): Collection
    {
        $perDay = $tasks->countBy('due_date');

        return $tasks->map(function (array $task) use ($perDay, $dailyMinutes) {
            $task['duration_minutes'] = $task['type'] === TaskType::Exam->value
                ? 0
                : max(10, (int) round($dailyMinutes / $perDay[$task['due_date']]));

            return $task;
        })->sortBy('due_date')->values();
    }

    /**
     * @param  array{title?: string, note_id?: int|null, quiz_id?: int|null}  $topic
     * @return array<string, mixed>
     */
    private function task(CarbonImmutable $day, TaskType $type, string $title, string $description, array $topic = []): array
    {
        return [
            'title' => $title,
            'description' => $description,
            'type' => $type->value,
            'due_date' => $day->toDateString(),
            'note_id' => $topic['note_id'] ?? null,
            'quiz_id' => $type === TaskType::Study ? null : ($topic['quiz_id'] ?? null),
            'duration_minutes' => 30,
        ];
    }
}

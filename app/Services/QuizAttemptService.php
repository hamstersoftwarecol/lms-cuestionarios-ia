<?php

namespace App\Services;

use App\Models\Badge;
use App\Models\QuizAttempt;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Corrige un intento, calcula los puntos y dispara la gamificación.
 */
class QuizAttemptService
{
    public function __construct(private readonly GamificationService $gamification) {}

    /**
     * @param  array<int|string, array<int, int|string>|int|string>  $answers  question_id => índices seleccionados
     * @return Collection<int, Badge> insignias nuevas
     */
    public function submit(QuizAttempt $attempt, array $answers): Collection
    {
        $attempt->loadMissing('quiz.questions', 'user');
        $questions = $attempt->quiz->questions;

        DB::transaction(function () use ($attempt, $answers, $questions) {
            $score = 0;
            $points = 0;

            foreach ($questions as $question) {
                $selected = collect((array) ($answers[$question->id] ?? []))
                    ->map(fn ($value) => (int) $value)
                    ->filter(fn (int $index) => $index >= 0 && $index < count($question->options))
                    ->unique()
                    ->values()
                    ->all();

                $isCorrect = $selected !== [] && $question->isCorrect($selected);

                $attempt->answers()->create([
                    'question_id' => $question->id,
                    'selected_answers' => $selected,
                    'is_correct' => $isCorrect,
                ]);

                if ($isCorrect) {
                    $score++;
                    $points += $question->points();
                }
            }

            $total = $questions->count();
            $percentage = $total > 0 ? round($score / $total * 100, 2) : 0;

            if ($total > 0 && $score === $total) {
                $points += (int) round($points * config('lms.quiz.perfect_bonus'));
            }

            $attempt->update([
                'score' => $score,
                'total_questions' => $total,
                'percentage' => $percentage,
                'points_earned' => $points,
                'time_spent' => $attempt->started_at ? (int) min(now()->diffInSeconds($attempt->started_at, true), 86400) : 0,
                'completed_at' => now(),
            ]);

            $this->gamification->awardPoints($attempt->user, $points);
            $this->gamification->registerActivity($attempt->user->refresh());
        });

        ActivityLogger::log('quiz.completed', "Completó «{$attempt->quiz->title}» con {$attempt->percentage}%", $attempt, [
            'score' => $attempt->score,
            'total' => $attempt->total_questions,
            'points' => $attempt->points_earned,
        ], $attempt->user);

        return $this->gamification->checkBadges($attempt->user);
    }
}

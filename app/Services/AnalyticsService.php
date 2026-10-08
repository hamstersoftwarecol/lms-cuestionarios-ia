<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\AiRequest;
use App\Models\Note;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Métricas para el panel de administración y la analítica personal de aprendizaje.
 */
class AnalyticsService
{
    /**
     * @return array<string, mixed>
     */
    public function adminOverview(int $days = 30): array
    {
        $since = today()->subDays($days - 1);
        $completed = QuizAttempt::whereNotNull('completed_at');
        $ai = AiRequest::where('created_at', '>=', $since);

        return [
            'kpis' => [
                'users' => User::count(),
                'new_users_week' => User::where('created_at', '>=', now()->startOfWeek())->count(),
                'active_today' => ActivityLog::where('created_at', '>=', today())->whereNotNull('user_id')->distinct()->count('user_id'),
                'notes' => Note::count(),
                'quizzes' => Quiz::count(),
                'questions' => Question::count(),
                'attempts' => (clone $completed)->count(),
                'avg_score' => round((float) (clone $completed)->avg('percentage'), 1),
                'ai_requests' => (clone $ai)->count(),
                'ai_success_rate' => ($total = (clone $ai)->count()) > 0
                    ? round((clone $ai)->where('success', true)->count() / $total * 100, 1)
                    : 100,
                'ai_tokens' => (int) (clone $ai)->sum(DB::raw('prompt_tokens + output_tokens')),
            ],
            'registrations' => $this->dailySeries(User::query(), 'created_at', $since, $days),
            'attempts' => $this->dailySeries(QuizAttempt::whereNotNull('completed_at'), 'completed_at', $since, $days),
            'ai_by_type' => AiRequest::where('created_at', '>=', $since)
                ->selectRaw('type, COUNT(*) as total')
                ->groupBy('type')
                ->pluck('total', 'type')
                ->mapWithKeys(fn ($total, $type) => [AiRequest::TYPES[$type] ?? $type => (int) $total]),
            'question_types' => Question::selectRaw('type, COUNT(*) as total')->groupBy('type')->pluck('total', 'type')
                ->mapWithKeys(fn ($total, $type) => [strtoupper($type) => (int) $total]),
            'score_distribution' => $this->scoreDistribution(QuizAttempt::whereNotNull('completed_at')),
            'top_quizzes' => Quiz::withCount(['attempts' => fn ($q) => $q->whereNotNull('completed_at')])
                ->withAvg(['attempts' => fn ($q) => $q->whereNotNull('completed_at')], 'percentage')
                ->with('user')
                ->orderByDesc('attempts_count')
                ->limit(5)
                ->get(),
            'top_users' => User::orderByDesc('points')->limit(5)->get(),
            'recent_activity' => ActivityLog::with('user')->latest('created_at')->limit(8)->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function userOverview(User $user): array
    {
        $completed = $user->attempts()->whereNotNull('completed_at');
        $answers = DB::table('attempt_answers')
            ->join('quiz_attempts', 'quiz_attempts.id', '=', 'attempt_answers.quiz_attempt_id')
            ->join('questions', 'questions.id', '=', 'attempt_answers.question_id')
            ->where('quiz_attempts.user_id', $user->id);

        $accuracy = fn (string $column) => (clone $answers)
            ->selectRaw("questions.{$column} as label, COUNT(*) as total, SUM(CASE WHEN attempt_answers.is_correct THEN 1 ELSE 0 END) as correct")
            ->groupBy("questions.{$column}")
            ->get()
            ->mapWithKeys(fn ($row) => [$row->label => $row->total > 0 ? round($row->correct / $row->total * 100, 1) : 0]);

        $confidence = (clone $completed)->whereNotNull('confidence_before')
            ->selectRaw('AVG(confidence_before) as confidence, AVG(percentage) as score')
            ->first();

        return [
            'totals' => [
                'attempts' => (clone $completed)->count(),
                'avg_score' => round((float) (clone $completed)->avg('percentage'), 1),
                'best_score' => round((float) (clone $completed)->max('percentage'), 1),
                'study_minutes' => (int) round((clone $completed)->sum('time_spent') / 60),
                'tasks_completed' => $user->studyTasks()->where('is_completed', true)->count(),
                'tasks_total' => $user->studyTasks()->count(),
                'notes' => $user->notes()->count(),
            ],
            'trend' => (clone $completed)->with('quiz:id,title')->latest('completed_at')->limit(20)->get()->reverse()->values()
                ->map(fn (QuizAttempt $attempt) => [
                    'label' => $attempt->completed_at->format('d/m'),
                    'quiz' => $attempt->quiz?->title,
                    'score' => $attempt->percentage,
                ]),
            'activity' => $this->dailySeries($user->attempts()->whereNotNull('completed_at')->getQuery(), 'completed_at', today()->subDays(13), 14),
            'by_difficulty' => $accuracy('difficulty'),
            'by_type' => $accuracy('type'),
            'pre_post' => $this->prePost($user),
            'confidence' => [
                'avg_confidence' => $confidence?->confidence ? round($confidence->confidence / 5 * 100, 1) : null,
                'avg_score' => $confidence?->score ? round((float) $confidence->score, 1) : null,
            ],
            'weakest' => (clone $completed)->with('quiz:id,title')
                ->selectRaw('quiz_id, AVG(percentage) as avg_score, COUNT(*) as attempts')
                ->groupBy('quiz_id')
                ->orderBy('avg_score')
                ->limit(5)
                ->get(),
        ];
    }

    /**
     * Comparación entre la mejor evaluación previa y la mejor posterior de cada cuestionario.
     *
     * @return Collection<int, array{quiz: Quiz|null, pre: float, post: float, gain: float}>
     */
    public function prePost(User $user, ?int $quizId = null): Collection
    {
        return $user->attempts()
            ->whereNotNull('completed_at')
            ->whereIn('assessment_type', ['pre', 'post'])
            ->when($quizId, fn ($q) => $q->where('quiz_id', $quizId))
            ->with('quiz:id,title')
            ->get()
            ->groupBy('quiz_id')
            ->filter(fn (Collection $attempts) => $attempts->pluck('assessment_type.value')->unique()->count() === 2)
            ->map(function (Collection $attempts) {
                $pre = (float) $attempts->where('assessment_type.value', 'pre')->max('percentage');
                $post = (float) $attempts->where('assessment_type.value', 'post')->max('percentage');

                return [
                    'quiz' => $attempts->first()->quiz,
                    'pre' => $pre,
                    'post' => $post,
                    'gain' => round($post - $pre, 1),
                ];
            })
            ->values();
    }

    /**
     * Serie diaria con ceros en los días sin datos.
     *
     * @return array{labels: array<int, string>, values: array<int, int>}
     */
    public function dailySeries(Builder|\Illuminate\Database\Query\Builder $query, string $column, Carbon $since, int $days): array
    {
        $counts = (clone $query)
            ->where($column, '>=', $since)
            ->selectRaw("DATE({$column}) as day, COUNT(*) as total")
            ->groupBy('day')
            ->pluck('total', 'day');

        $labels = [];
        $values = [];

        for ($i = 0; $i < $days; $i++) {
            $day = $since->copy()->addDays($i);
            $labels[] = $day->format('d/m');
            $values[] = (int) ($counts[$day->toDateString()] ?? 0);
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * @return array<string, int>
     */
    private function scoreDistribution(Builder $query): array
    {
        $buckets = ['0-20' => 0, '21-40' => 0, '41-60' => 0, '61-80' => 0, '81-100' => 0];

        $query->pluck('percentage')->each(function ($percentage) use (&$buckets) {
            $key = match (true) {
                $percentage <= 20 => '0-20',
                $percentage <= 40 => '21-40',
                $percentage <= 60 => '41-60',
                $percentage <= 80 => '61-80',
                default => '81-100',
            };
            $buckets[$key]++;
        });

        return $buckets;
    }
}

<?php

namespace App\Services;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Clasificación semanal, mensual e histórica basada en los puntos de los intentos.
 */
class LeaderboardService
{
    public const PERIODS = [
        'weekly' => 'Semanal',
        'monthly' => 'Mensual',
        'all' => 'Histórica',
    ];

    public function periodStart(string $period): ?CarbonInterface
    {
        return match ($period) {
            'weekly' => now()->startOfWeek(),
            'monthly' => now()->startOfMonth(),
            default => null,
        };
    }

    /**
     * @param  array<int, int>|null  $userIds  restringe la tabla (p. ej. a los miembros de un grupo)
     * @return Collection<int, object{rank: int, user: User, points: int, quizzes: int, avg_score: float}>
     */
    public function top(string $period = 'weekly', int $limit = 50, ?array $userIds = null): Collection
    {
        $start = $this->periodStart($period);

        $rows = DB::table('quiz_attempts')
            ->join('users', 'users.id', '=', 'quiz_attempts.user_id')
            ->whereNotNull('quiz_attempts.completed_at')
            ->where('users.is_active', true)
            ->when($start, fn ($q) => $q->where('quiz_attempts.completed_at', '>=', $start))
            ->when($userIds !== null, fn ($q) => $q->whereIn('quiz_attempts.user_id', $userIds))
            ->groupBy('quiz_attempts.user_id')
            ->selectRaw('quiz_attempts.user_id, SUM(quiz_attempts.points_earned) as points, COUNT(*) as quizzes, AVG(quiz_attempts.percentage) as avg_score')
            ->orderByDesc('points')
            ->orderByDesc('avg_score')
            ->limit($limit)
            ->get();

        $users = User::whereIn('id', $rows->pluck('user_id'))->get()->keyBy('id');

        return $rows->values()->map(fn ($row, $index) => (object) [
            'rank' => $index + 1,
            'user' => $users[$row->user_id],
            'points' => (int) $row->points,
            'quizzes' => (int) $row->quizzes,
            'avg_score' => round((float) $row->avg_score, 1),
        ]);
    }

    /**
     * @return array{rank: int|null, points: int}
     */
    public function position(User $user, string $period = 'weekly'): array
    {
        $start = $this->periodStart($period);

        $base = DB::table('quiz_attempts')
            ->join('users', 'users.id', '=', 'quiz_attempts.user_id')
            ->whereNotNull('quiz_attempts.completed_at')
            ->where('users.is_active', true)
            ->when($start, fn ($q) => $q->where('quiz_attempts.completed_at', '>=', $start));

        $points = (int) (clone $base)->where('quiz_attempts.user_id', $user->id)->sum('quiz_attempts.points_earned');

        if ($points === 0) {
            return ['rank' => null, 'points' => 0];
        }

        $ahead = DB::query()
            ->fromSub((clone $base)->groupBy('quiz_attempts.user_id')->selectRaw('SUM(quiz_attempts.points_earned) as points'), 'totals')
            ->where('points', '>', $points)
            ->count();

        return ['rank' => $ahead + 1, 'points' => $points];
    }
}

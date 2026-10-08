<?php

namespace App\Services;

use App\Models\Badge;
use App\Models\User;
use App\Notifications\BadgeEarned;
use Illuminate\Support\Collection;

/**
 * Motor de gamificación: puntos, rachas diarias e insignias de logros.
 */
class GamificationService
{
    /**
     * Actualiza la racha: +1 si la última actividad fue ayer, se mantiene si fue hoy
     * y se reinicia a 1 si se saltó algún día.
     */
    public function registerActivity(User $user): void
    {
        $today = today();
        $last = $user->last_activity_date;

        if ($last?->isSameDay($today)) {
            return;
        }

        $user->current_streak = $last?->isSameDay($today->copy()->subDay()) ? (int) $user->current_streak + 1 : 1;
        $user->longest_streak = max((int) $user->longest_streak, $user->current_streak);
        $user->last_activity_date = $today;
        $user->save();
    }

    /**
     * Si el usuario no tiene actividad ayer ni hoy, la racha visible ya está rota.
     */
    public function effectiveStreak(User $user): int
    {
        $last = $user->last_activity_date;

        if ($last === null || $last->lt(today()->subDay())) {
            return 0;
        }

        return (int) $user->current_streak;
    }

    public function awardPoints(User $user, int $points): void
    {
        if ($points > 0) {
            $user->increment('points', $points);
        }
    }

    /**
     * Evalúa todas las insignias pendientes y otorga las que el usuario ya cumple.
     *
     * @return Collection<int, Badge>
     */
    public function checkBadges(User $user): Collection
    {
        $owned = $user->badges()->pluck('badges.id');
        $metrics = [];

        $earned = Badge::whereNotIn('id', $owned)->get()->filter(function (Badge $badge) use ($user, &$metrics) {
            $metrics[$badge->criteria_type] ??= $this->metric($user, $badge->criteria_type);

            return $metrics[$badge->criteria_type] >= $badge->criteria_value;
        });

        foreach ($earned as $badge) {
            $user->badges()->attach($badge->id, ['awarded_at' => now()]);
            $user->notify(new BadgeEarned($badge));
            ActivityLogger::log('badge.earned', "Obtuvo la insignia «{$badge->name}»", $badge, user: $user);
        }

        return $earned->values();
    }

    /**
     * @return array<string, int>
     */
    public function progress(User $user): array
    {
        return collect(['quizzes_completed', 'perfect_scores', 'streak', 'notes_created', 'groups_joined', 'tasks_completed'])
            ->mapWithKeys(fn (string $criteria) => [$criteria => $this->metric($user, $criteria)])
            ->all();
    }

    public function metric(User $user, string $criteria): int
    {
        return match ($criteria) {
            'quizzes_completed' => $user->attempts()->whereNotNull('completed_at')->count(),
            'perfect_scores' => $user->attempts()->whereNotNull('completed_at')->where('percentage', '>=', 100)->count(),
            'streak' => (int) max($user->current_streak, $user->longest_streak),
            'notes_created' => $user->notes()->count(),
            'groups_joined' => $user->studyGroups()->count(),
            'tasks_completed' => $user->studyTasks()->where('is_completed', true)->count(),
            'points' => (int) $user->points,
            default => 0,
        };
    }
}

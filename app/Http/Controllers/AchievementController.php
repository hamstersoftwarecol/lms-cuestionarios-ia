<?php

namespace App\Http\Controllers;

use App\Models\Badge;
use App\Services\GamificationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AchievementController extends Controller
{
    public function __invoke(Request $request, GamificationService $gamification): View
    {
        $user = $request->user();
        $earned = $user->badges()->get()->keyBy('id');
        $progress = $gamification->progress($user);

        $badges = Badge::orderBy('id')->get()->map(function (Badge $badge) use ($earned, $progress) {
            $badge->earned_at = $earned->get($badge->id)?->pivot->awarded_at;
            $badge->current = min($progress[$badge->criteria_type] ?? 0, $badge->criteria_value);
            $badge->percent = (int) round($badge->current / max(1, $badge->criteria_value) * 100);

            return $badge;
        });

        return view('achievements', [
            'badges' => $badges,
            'earnedCount' => $earned->count(),
            'streak' => $gamification->effectiveStreak($user),
            'longestStreak' => $user->longest_streak,
            'points' => $user->points,
            'calendar' => $user->attempts()
                ->whereNotNull('completed_at')
                ->where('completed_at', '>=', today()->subDays(83))
                ->get(['completed_at'])
                ->countBy(fn ($attempt) => $attempt->completed_at->toDateString()),
        ]);
    }
}

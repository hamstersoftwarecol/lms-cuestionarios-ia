<?php

namespace App\Http\Controllers;

use App\Models\Badge;
use App\Services\GamificationService;
use App\Services\LeaderboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, GamificationService $gamification, LeaderboardService $leaderboard): View
    {
        $user = $request->user();
        $completed = $user->attempts()->whereNotNull('completed_at');

        $earnedIds = $user->badges()->pluck('badges.id');
        $progress = $gamification->progress($user);
        $nextBadge = Badge::whereNotIn('id', $earnedIds)->get()
            ->map(function (Badge $badge) use ($progress) {
                $badge->current = min($progress[$badge->criteria_type] ?? 0, $badge->criteria_value);
                $badge->percent = (int) round($badge->current / max(1, $badge->criteria_value) * 100);

                return $badge;
            })
            ->sortByDesc('percent')
            ->first();

        return view('dashboard', [
            'user' => $user,
            'stats' => [
                'points' => $user->points,
                'streak' => $gamification->effectiveStreak($user),
                'longest_streak' => $user->longest_streak,
                'quizzes' => (clone $completed)->count(),
                'avg_score' => round((float) (clone $completed)->avg('percentage'), 1),
                'badges' => $earnedIds->count(),
                'total_badges' => Badge::count(),
            ],
            'weeklyRank' => $leaderboard->position($user, 'weekly'),
            'todayTasks' => $user->studyTasks()
                ->with('plan:id,title', 'quiz:id', 'note:id')
                ->where('is_completed', false)
                ->whereDate('due_date', '<=', today())
                ->orderBy('due_date')
                ->limit(6)
                ->get(),
            'recentAttempts' => (clone $completed)->with('quiz:id,title')->latest('completed_at')->limit(5)->get(),
            'recentNotes' => $user->notes()->latest()->limit(4)->get(),
            'recentBadges' => $user->badges()->orderByPivot('awarded_at', 'desc')->limit(4)->get(),
            'nextBadge' => $nextBadge,
            'upcomingPlan' => $user->studyPlans()->where('status', 'active')->whereDate('exam_date', '>=', today())->orderBy('exam_date')->first(),
        ]);
    }
}

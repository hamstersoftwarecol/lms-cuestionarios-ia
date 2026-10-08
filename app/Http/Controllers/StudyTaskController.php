<?php

namespace App\Http\Controllers;

use App\Models\StudyTask;
use App\Services\GamificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StudyTaskController extends Controller
{
    public function toggle(Request $request, StudyTask $studyTask, GamificationService $gamification): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $studyTask);

        $completed = ! $studyTask->is_completed;
        $studyTask->update(['is_completed' => $completed, 'completed_at' => $completed ? now() : null]);

        $badges = collect();

        if ($completed) {
            $gamification->registerActivity($request->user());
            $badges = $gamification->checkBadges($request->user());
        }

        if ($request->expectsJson()) {
            $plan = $studyTask->plan()->withCount(['tasks', 'tasks as completed_tasks_count' => fn ($q) => $q->where('is_completed', true)])->first();

            return response()->json([
                'completed' => $completed,
                'progress' => $plan->progress(),
                'badges' => $badges->map->only('name', 'icon'),
            ]);
        }

        return back()->with('newBadges', $badges->map->only('name', 'icon', 'description')->all());
    }
}

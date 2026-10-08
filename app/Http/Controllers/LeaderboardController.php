<?php

namespace App\Http\Controllers;

use App\Services\LeaderboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaderboardController extends Controller
{
    public function __invoke(Request $request, LeaderboardService $leaderboard): View
    {
        $period = array_key_exists($request->period, LeaderboardService::PERIODS) ? $request->period : 'weekly';

        return view('leaderboard', [
            'period' => $period,
            'periods' => LeaderboardService::PERIODS,
            'ranking' => $leaderboard->top($period, 50),
            'me' => $leaderboard->position($request->user(), $period),
            'since' => $leaderboard->periodStart($period),
        ]);
    }
}

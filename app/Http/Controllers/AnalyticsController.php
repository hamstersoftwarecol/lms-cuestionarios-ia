<?php

namespace App\Http\Controllers;

use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function __invoke(Request $request, AnalyticsService $analytics): View
    {
        return view('analytics', ['data' => $analytics->userOverview($request->user())]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Ai\GeminiClient;
use App\Services\AnalyticsService;
use App\Support\SessionRepository;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(AnalyticsService $analytics, GeminiClient $gemini): View
    {
        return view('admin.dashboard', [
            'data' => $analytics->adminOverview(),
            'activeSessions' => SessionRepository::all()->reject->is_expired->count(),
            'gemini' => ['configured' => $gemini->isConfigured(), 'model' => $gemini->model(), 'tts' => $gemini->ttsModel()],
        ]);
    }
}

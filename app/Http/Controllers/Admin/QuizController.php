<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class QuizController extends Controller
{
    public function index(): View
    {
        return view('admin.quizzes.index', [
            'quizzes' => Quiz::with('user:id,name,email')
                ->withCount(['questions', 'attempts' => fn ($q) => $q->whereNotNull('completed_at')])
                ->withAvg(['attempts' => fn ($q) => $q->whereNotNull('completed_at')], 'percentage')
                ->latest()
                ->get(),
        ]);
    }

    public function destroy(Quiz $quiz): RedirectResponse
    {
        ActivityLogger::log('admin.quiz_deleted', "Eliminó el cuestionario «{$quiz->title}» de {$quiz->user?->email}");
        $quiz->delete();

        return back()->with('success', 'Cuestionario eliminado.');
    }
}

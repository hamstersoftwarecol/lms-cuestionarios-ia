<?php

namespace App\Http\Controllers;

use App\Enums\AssessmentType;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Services\Ai\TextToSpeech;
use App\Services\AnalyticsService;
use App\Services\QuizAttemptService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Flujo de un intento: evaluación previa (tipo + confianza) → preguntas → resultados
 * con explicaciones → evaluación posterior (reflexión y confianza final).
 */
class QuizAttemptController extends Controller
{
    public function index(Request $request): View
    {
        return view('attempts.index', [
            'attempts' => $request->user()->attempts()
                ->whereNotNull('completed_at')
                ->with('quiz:id,title')
                ->latest('completed_at')
                ->limit(500)
                ->get(),
        ]);
    }

    public function create(Request $request, Quiz $quiz): View|RedirectResponse
    {
        $this->authorize('view', $quiz);

        if ($quiz->questions()->doesntExist()) {
            return redirect()->route('quizzes.show', $quiz)->with('error', 'Este cuestionario todavía no tiene preguntas.');
        }

        $previous = $quiz->attempts()->where('user_id', $request->user()->id)->whereNotNull('completed_at');

        return view('attempts.create', [
            'quiz' => $quiz->loadCount('questions'),
            'hasPre' => (clone $previous)->where('assessment_type', AssessmentType::Pre)->exists(),
            'previousCount' => $previous->count(),
            'suggested' => (clone $previous)->exists() ? AssessmentType::Post : AssessmentType::Pre,
        ]);
    }

    public function store(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->authorize('view', $quiz);

        $validated = $request->validate([
            'assessment_type' => ['required', Rule::enum(AssessmentType::class)],
            'confidence_before' => ['required', 'integer', 'between:1,5'],
        ]);

        // Reutiliza un intento sin terminar del mismo cuestionario para no duplicarlos.
        $request->user()->attempts()->where('quiz_id', $quiz->id)->whereNull('completed_at')->delete();

        $attempt = $request->user()->attempts()->create($validated + [
            'quiz_id' => $quiz->id,
            'total_questions' => $quiz->questions()->count(),
            'started_at' => now(),
        ]);

        return redirect()->route('attempts.take', $attempt);
    }

    public function take(Request $request, QuizAttempt $attempt): View|RedirectResponse
    {
        $this->authorize('update', $attempt);

        if ($attempt->isCompleted()) {
            return redirect()->route('attempts.show', $attempt);
        }

        $quiz = $attempt->quiz()->with('questions')->first();
        $deadline = $quiz->time_limit ? $attempt->started_at->copy()->addMinutes($quiz->time_limit) : null;

        return view('attempts.take', [
            'attempt' => $attempt,
            'quiz' => $quiz,
            // Nunca se envían las respuestas correctas al navegador.
            'questions' => $quiz->questions->map(fn ($question) => [
                'id' => $question->id,
                'type' => $question->type->value,
                'text' => $question->question_text,
                'options' => $question->options,
                'difficulty' => $question->difficulty->label(),
            ])->values(),
            'remainingSeconds' => $deadline ? max(0, (int) now()->diffInSeconds($deadline, false)) : null,
            'voices' => TextToSpeech::voices(),
            'voice' => $request->user()->tts_voice,
        ]);
    }

    public function submit(Request $request, QuizAttempt $attempt, QuizAttemptService $service): RedirectResponse
    {
        $this->authorize('update', $attempt);

        if ($attempt->isCompleted()) {
            return redirect()->route('attempts.show', $attempt);
        }

        $validated = $request->validate([
            'answers' => ['nullable', 'array'],
            'answers.*' => ['array'],
            'answers.*.*' => ['integer', 'min:0', 'max:9'],
        ]);

        $badges = $service->submit($attempt, $validated['answers'] ?? []);

        return redirect()->route('attempts.show', $attempt)->with('newBadges', $badges->map->only('name', 'icon', 'description')->all());
    }

    public function show(Request $request, QuizAttempt $attempt, AnalyticsService $analytics): View|RedirectResponse
    {
        $this->authorize('view', $attempt);

        if (! $attempt->isCompleted()) {
            return redirect()->route('attempts.take', $attempt);
        }

        $attempt->load('quiz', 'answers.question');

        return view('attempts.show', [
            'attempt' => $attempt,
            'answers' => $attempt->answers->filter(fn ($answer) => $answer->question)->sortBy(fn ($answer) => $answer->question->position)->values(),
            'prePost' => $analytics->prePost($request->user(), $attempt->quiz_id)->first(),
            'voices' => TextToSpeech::voices(),
            'voice' => $request->user()->tts_voice,
        ]);
    }

    public function reflect(Request $request, QuizAttempt $attempt): RedirectResponse
    {
        $this->authorize('update', $attempt);

        $attempt->update($request->validate([
            'confidence_after' => ['required', 'integer', 'between:1,5'],
            'reflection' => ['nullable', 'string', 'max:2000'],
        ]));

        return back()->with('success', '¡Gracias! Tu evaluación posterior se ha guardado.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\Difficulty;
use App\Models\Note;
use App\Models\Quiz;
use App\Services\ActivityLogger;
use App\Services\Ai\GeminiClient;
use App\Services\Ai\GeminiException;
use App\Services\Ai\QuizGenerator;
use App\Services\AnalyticsService;
use App\Services\GamificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class QuizController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $tab = in_array($request->tab, ['mine', 'shared', 'public'], true) ? $request->tab : 'mine';

        $quizzes = Quiz::query()
            ->with('user:id,name', 'note:id,title')
            ->withCount('questions')
            ->withCount(['attempts as my_attempts_count' => fn ($q) => $q->where('user_id', $user->id)->whereNotNull('completed_at')])
            ->withMax(['attempts as my_best' => fn ($q) => $q->where('user_id', $user->id)], 'percentage')
            ->when($tab === 'mine', fn ($q) => $q->where('user_id', $user->id))
            ->when($tab === 'shared', fn ($q) => $q->where('user_id', '!=', $user->id)
                ->whereHas('studyGroups.members', fn ($m) => $m->where('users.id', $user->id)))
            ->when($tab === 'public', fn ($q) => $q->where('is_public', true))
            ->when($request->filled('q'), fn ($q) => $q->where('title', 'like', '%'.$request->q.'%'))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('quizzes.index', compact('quizzes', 'tab'));
    }

    public function create(Request $request): View
    {
        $notes = $request->user()->notes()
            ->where('extraction_status', 'completed')
            ->whereNotNull('content')
            ->latest()
            ->get(['id', 'title', 'source_type', 'created_at']);

        return view('quizzes.create', [
            'notes' => $notes,
            'selectedNote' => $request->integer('note') ?: null,
            'aiEnabled' => app(GeminiClient::class)->isConfigured(),
            'languages' => config('lms.quiz.languages'),
        ]);
    }

    public function store(Request $request, QuizGenerator $generator, GamificationService $gamification): RedirectResponse
    {
        $user = $request->user();
        $manual = $request->input('mode') === 'manual';
        $quiz = config('lms.quiz');

        $validated = $request->validate([
            'mode' => ['required', Rule::in(['ai', 'manual'])],
            'note_id' => [$manual ? 'nullable' : 'required', Rule::exists('notes', 'id')->where('user_id', $user->id)],
            'title' => [$manual ? 'required' : 'nullable', 'string', 'max:255'],
            'question_count' => ['required_if:mode,ai', 'nullable', 'integer', "between:{$quiz['min_questions']},{$quiz['max_questions']}"],
            'question_types' => ['required_if:mode,ai', 'nullable', Rule::in(['mcq', 'sata', 'mixed'])],
            'difficulty' => ['required', Rule::enum(Difficulty::class)],
            'language' => ['required', Rule::in(array_keys($quiz['languages']))],
            'time_limit' => ['nullable', 'integer', 'between:1,180'],
            'is_public' => ['boolean'],
        ]);

        $note = isset($validated['note_id']) ? Note::find($validated['note_id']) : null;
        $generated = null;

        if (! $manual) {
            if (! $note?->hasContent()) {
                return back()->withInput()->with('error', 'La nota seleccionada no tiene texto. Escanéala de nuevo o añade contenido.');
            }

            set_time_limit(180);

            try {
                $generated = $generator->generate(
                    $note->content,
                    (int) $validated['question_count'],
                    $validated['question_types'],
                    $validated['difficulty'],
                    $validated['language'],
                );
            } catch (GeminiException $e) {
                return back()->withInput()->with('error', $e->getMessage());
            }
        }

        $created = DB::transaction(function () use ($user, $validated, $note, $generated, $request) {
            $quiz = $user->quizzes()->create([
                'note_id' => $note?->id,
                'title' => $validated['title'] ?: ($generated['title'] ?: 'Cuestionario: '.$note?->title),
                'description' => $generated['description'] ?? null,
                'difficulty' => $validated['difficulty'],
                'language' => $validated['language'] === 'auto' ? 'es' : $validated['language'],
                'time_limit' => $validated['time_limit'] ?? null,
                'is_public' => $request->boolean('is_public'),
                'generated_by_ai' => $generated !== null,
            ]);

            foreach ($generated['questions'] ?? [] as $position => $question) {
                $quiz->questions()->create($question + ['position' => $position]);
            }

            return $quiz;
        });

        ActivityLogger::log(
            $generated ? 'ai.quiz_generated' : 'quiz.created',
            $generated
                ? "Generó con IA «{$created->title}» ({$created->questions()->count()} preguntas)"
                : "Creó manualmente «{$created->title}»",
            $created,
        );
        $gamification->registerActivity($user);

        return $generated
            ? redirect()->route('quizzes.show', $created)->with('success', '¡Cuestionario generado con IA! Revisa las preguntas antes de compartirlo.')
            : redirect()->route('quizzes.edit', $created)->with('success', 'Cuestionario creado. Añade tus preguntas.');
    }

    public function show(Request $request, Quiz $quiz, AnalyticsService $analytics): View
    {
        $this->authorize('view', $quiz);
        $user = $request->user();

        $quiz->load('user:id,name', 'note:id,title,user_id', 'questions')->loadCount('questions');

        return view('quizzes.show', [
            'quiz' => $quiz,
            'myAttempts' => $quiz->attempts()->where('user_id', $user->id)->whereNotNull('completed_at')->latest('completed_at')->get(),
            'ranking' => $quiz->attempts()
                ->whereNotNull('completed_at')
                ->selectRaw('user_id, MAX(percentage) as best, MIN(time_spent) as fastest, COUNT(*) as tries')
                ->groupBy('user_id')
                ->orderByDesc('best')
                ->orderBy('fastest')
                ->limit(10)
                ->with('user:id,name,avatar')
                ->get(),
            'prePost' => $analytics->prePost($user, $quiz->id)->first(),
            'groups' => $user->studyGroups()->get(),
            'sharedGroupIds' => $quiz->studyGroups()->pluck('study_groups.id'),
            'stats' => [
                'mcq' => $quiz->questions->where('type.value', 'mcq')->count(),
                'sata' => $quiz->questions->where('type.value', 'sata')->count(),
                'by_difficulty' => $quiz->questions->countBy(fn ($q) => $q->difficulty->label()),
                'attempts' => $quiz->attempts()->whereNotNull('completed_at')->count(),
                'avg' => round((float) $quiz->attempts()->whereNotNull('completed_at')->avg('percentage'), 1),
            ],
        ]);
    }

    public function edit(Quiz $quiz): View
    {
        $this->authorize('update', $quiz);

        return view('quizzes.edit', [
            'quiz' => $quiz->load('questions'),
            'languages' => collect(config('lms.quiz.languages'))->except('auto'),
        ]);
    }

    public function update(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->authorize('update', $quiz);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'difficulty' => ['required', Rule::enum(Difficulty::class)],
            'language' => ['required', Rule::in(array_keys(config('lms.quiz.languages')))],
            'time_limit' => ['nullable', 'integer', 'between:1,180'],
        ]);

        $quiz->update($validated + ['is_public' => $request->boolean('is_public')]);
        ActivityLogger::log('quiz.updated', "Editó «{$quiz->title}»", $quiz);

        return back()->with('success', 'Cuestionario actualizado.');
    }

    public function destroy(Quiz $quiz): RedirectResponse
    {
        $this->authorize('delete', $quiz);

        ActivityLogger::log('quiz.deleted', 'Eliminó «'.Str::limit($quiz->title, 80).'»');
        $quiz->delete();

        return redirect()->route('quizzes.index')->with('success', 'Cuestionario eliminado.');
    }
}

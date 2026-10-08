<?php

namespace App\Http\Controllers;

use App\Models\StudyPlan;
use App\Services\ActivityLogger;
use App\Services\StudyPlanGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudyPlanController extends Controller
{
    public function __construct(private readonly StudyPlanGenerator $generator) {}

    public function index(Request $request): View
    {
        return view('study-plans.index', [
            'plans' => $request->user()->studyPlans()
                ->withCount(['tasks', 'tasks as completed_tasks_count' => fn ($q) => $q->where('is_completed', true)])
                ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
                ->orderBy('exam_date')
                ->get(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('study-plans.create', [
            'notes' => $request->user()->notes()->latest()->get(['id', 'title']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'exam_date' => ['required', 'date', 'after:start_date'],
            'daily_minutes' => ['required', 'integer', 'between:15,600'],
            'notes' => ['nullable', 'array'],
            'notes.*' => ['integer', Rule::exists('notes', 'id')->where('user_id', $user->id)],
            'topics' => ['nullable', 'string', 'max:3000'],
        ], [
            'exam_date.after' => 'La fecha del examen debe ser posterior a la fecha de inicio.',
        ]);

        $topics = collect(preg_split('/\r\n|\r|\n|,/', (string) ($validated['topics'] ?? '')))
            ->map(fn ($topic) => trim($topic))
            ->filter()
            ->unique()
            ->take(50)
            ->values()
            ->all();

        if ($topics === [] && empty($validated['notes'])) {
            return back()->withInput()->withErrors(['topics' => 'Selecciona al menos una nota o escribe algún tema.']);
        }

        $plan = $user->studyPlans()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'start_date' => $validated['start_date'],
            'exam_date' => $validated['exam_date'],
            'daily_minutes' => $validated['daily_minutes'],
            'topics' => $topics,
        ]);
        $plan->notes()->sync($validated['notes'] ?? []);

        $count = $this->generator->generate($plan);
        ActivityLogger::log('plan.created', "Creó el plan «{$plan->title}» con {$count} tareas", $plan);

        return redirect()->route('study-plans.show', $plan)->with('success', "Plan creado con {$count} tareas generadas automáticamente.");
    }

    public function show(StudyPlan $studyPlan): View
    {
        $this->authorize('view', $studyPlan);

        $studyPlan->load('notes:id,title')->loadCount([
            'tasks',
            'tasks as completed_tasks_count' => fn ($q) => $q->where('is_completed', true),
        ]);

        return view('study-plans.show', [
            'plan' => $studyPlan,
            'days' => $studyPlan->tasks()->with('note:id,title', 'quiz:id,title')->get()->groupBy(fn ($task) => $task->due_date->toDateString()),
            'minutesTotal' => $studyPlan->tasks()->sum('duration_minutes'),
        ]);
    }

    public function regenerate(StudyPlan $studyPlan): RedirectResponse
    {
        $this->authorize('update', $studyPlan);

        $count = $this->generator->generate($studyPlan);

        return back()->with('success', "Se regeneraron {$count} tareas pendientes. Las completadas se conservan.");
    }

    public function destroy(StudyPlan $studyPlan): RedirectResponse
    {
        $this->authorize('delete', $studyPlan);

        ActivityLogger::log('plan.deleted', "Eliminó el plan «{$studyPlan->title}»");
        $studyPlan->delete();

        return redirect()->route('study-plans.index')->with('success', 'Plan eliminado.');
    }
}

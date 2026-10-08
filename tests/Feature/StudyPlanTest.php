<?php

namespace Tests\Feature;

use App\Enums\TaskType;
use App\Models\Note;
use App\Models\Quiz;
use App\Models\StudyPlan;
use App\Models\StudyTask;
use App\Models\User;
use App\Services\StudyPlanGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudyPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_plan_generates_daily_tasks_until_the_exam(): void
    {
        $this->seedBadges();
        $user = User::factory()->create();
        $note = Note::factory()->for($user)->create(['title' => 'Genética']);
        $quiz = Quiz::factory()->for($user)->create(['note_id' => $note->id]);

        $this->actingAs($user)->post('/study-plans', [
            'title' => 'Final de Biología',
            'start_date' => today()->toDateString(),
            'exam_date' => today()->addDays(10)->toDateString(),
            'daily_minutes' => 90,
            'notes' => [$note->id],
            'topics' => "Ecología\nEvolución",
        ])->assertRedirect();

        $plan = StudyPlan::firstOrFail();
        $tasks = $plan->tasks()->get();

        $this->assertSame(['Ecología', 'Evolución'], $plan->topics);
        $this->assertCount(3, $tasks->where('type', TaskType::Study));
        $this->assertCount(3, $tasks->where('type', TaskType::Quiz));
        $this->assertTrue($tasks->where('type', TaskType::Review)->isNotEmpty());
        $this->assertSame(today()->addDays(9)->toDateString(), $tasks->firstWhere('type', TaskType::Mock)->due_date->toDateString());
        $this->assertSame(today()->addDays(10)->toDateString(), $tasks->firstWhere('type', TaskType::Exam)->due_date->toDateString());
        $this->assertSame($quiz->id, $tasks->where('type', TaskType::Quiz)->firstWhere('note_id', $note->id)->quiz_id);
        $this->assertTrue($tasks->every(fn (StudyTask $task) => $task->due_date->between(today(), today()->addDays(10))));

        // Ningún día supera el tiempo diario configurado (salvo el mínimo de 10 min por tarea).
        $tasks->where('type', '!=', TaskType::Exam)->groupBy(fn ($t) => $t->due_date->toDateString())
            ->each(fn ($day) => $this->assertLessThanOrEqual(max(90, $day->count() * 10) + $day->count(), $day->sum('duration_minutes')));
    }

    public function test_a_plan_needs_at_least_one_topic(): void
    {
        $this->actingAs(User::factory()->create())->post('/study-plans', [
            'title' => 'Vacío',
            'start_date' => today()->toDateString(),
            'exam_date' => today()->addDays(5)->toDateString(),
            'daily_minutes' => 60,
        ])->assertSessionHasErrors('topics');
    }

    public function test_the_exam_date_must_be_after_the_start_date(): void
    {
        $this->actingAs(User::factory()->create())->post('/study-plans', [
            'title' => 'Mal',
            'start_date' => today()->addDays(3)->toDateString(),
            'exam_date' => today()->addDay()->toDateString(),
            'daily_minutes' => 60,
            'topics' => 'Tema',
        ])->assertSessionHasErrors('exam_date');
    }

    public function test_a_one_day_plan_creates_an_intensive_review_and_mock_exam(): void
    {
        $plan = StudyPlan::factory()->create(['exam_date' => today()->addDay(), 'topics' => ['A', 'B']]);

        app(StudyPlanGenerator::class)->generate($plan);

        $types = $plan->tasks()->pluck('type')->map->value->all();
        $this->assertSame(['review', 'review', 'mock', 'exam'], $types);
    }

    public function test_completing_tasks_counts_for_the_streak_and_planner_badge(): void
    {
        $this->seedBadges();
        $plan = StudyPlan::factory()->create(['exam_date' => today()->addDays(30), 'topics' => range(1, 12)]);
        app(StudyPlanGenerator::class)->generate($plan);
        $user = $plan->user;

        foreach ($plan->tasks()->where('type', '!=', 'exam')->limit(10)->get() as $task) {
            $this->actingAs($user)->patch(route('study-tasks.toggle', $task));
        }

        $this->assertSame(10, $user->studyTasks()->where('is_completed', true)->count());
        $this->assertSame(1, $user->fresh()->current_streak);
        $this->assertTrue($user->badges()->where('slug', 'planner-pro')->exists());
    }

    public function test_toggle_returns_json_progress(): void
    {
        $plan = StudyPlan::factory()->create(['topics' => ['Único']]);
        app(StudyPlanGenerator::class)->generate($plan);
        $task = $plan->tasks()->firstOrFail();

        $this->actingAs($plan->user)->patchJson(route('study-tasks.toggle', $task))
            ->assertOk()
            ->assertJson(['completed' => true]);
    }

    public function test_regenerating_keeps_completed_tasks(): void
    {
        $plan = StudyPlan::factory()->create(['topics' => ['A', 'B', 'C']]);
        $generator = app(StudyPlanGenerator::class);
        $generator->generate($plan);
        $done = $plan->tasks()->first();
        $done->update(['is_completed' => true, 'completed_at' => now()]);

        $generator->generate($plan);

        $this->assertModelExists($done);
        $this->assertSame(1, $plan->tasks()->where('is_completed', true)->count());
    }

    public function test_other_users_cannot_see_or_change_a_plan(): void
    {
        $plan = StudyPlan::factory()->create(['topics' => ['A']]);
        app(StudyPlanGenerator::class)->generate($plan);
        $intruder = User::factory()->create();

        $this->actingAs($intruder)->get(route('study-plans.show', $plan))->assertForbidden();
        $this->actingAs($intruder)->patch(route('study-tasks.toggle', $plan->tasks()->first()))->assertForbidden();
    }
}

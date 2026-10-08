<?php

namespace Tests\Feature;

use App\Models\Note;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\StudyGroup;
use App\Models\StudyPlan;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Recorre todas las pantallas con los datos de demostración para detectar errores de renderizado.
 */
class SmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_public_pages_render(): void
    {
        $this->get('/')->assertOk()->assertSee('La IA crea el examen');
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
        $this->get('/forgot-password')->assertOk();
    }

    public function test_student_pages_render(): void
    {
        $student = User::firstWhere('email', 'demo@lms.test');
        $note = Note::where('user_id', $student->id)->first();
        $quiz = Quiz::where('user_id', $student->id)->first();
        $attempt = QuizAttempt::where('user_id', $student->id)->first();
        $plan = StudyPlan::where('user_id', $student->id)->first();
        $group = StudyGroup::first();

        $this->actingAs($student);

        foreach ([
            '/dashboard', '/notes', '/notes/create', "/notes/{$note->id}", "/notes/{$note->id}/edit",
            '/quizzes', '/quizzes?tab=shared', '/quizzes?tab=public', '/quizzes/create', "/quizzes/create?note={$note->id}",
            "/quizzes/{$quiz->id}", "/quizzes/{$quiz->id}/edit", "/quizzes/{$quiz->id}/start",
            '/attempts', "/attempts/{$attempt->id}",
            '/study-plans', '/study-plans/create', "/study-plans/{$plan->id}",
            '/groups', "/groups/{$group->id}", "/groups/{$group->id}?period=all",
            '/leaderboard', '/leaderboard?period=monthly', '/leaderboard?period=all',
            '/achievements', '/analytics', '/notifications', '/profile',
        ] as $uri) {
            $this->get($uri)->assertOk();
        }
    }

    public function test_admin_pages_render(): void
    {
        $admin = User::firstWhere('email', config('lms.admin.email'));
        $student = User::firstWhere('email', 'demo@lms.test');

        $this->actingAs($admin);

        foreach ([
            '/admin', '/admin/users', "/admin/users/{$student->id}", '/admin/quizzes', '/admin/sessions',
            '/admin/activity', '/admin/activity?days=90&action=auth', '/admin/announcements', '/admin/announcements/create',
            '/admin/backups', '/dashboard',
        ] as $uri) {
            $this->get($uri)->assertOk();
        }
    }

    public function test_an_in_progress_attempt_renders_the_quiz_runner(): void
    {
        $student = User::firstWhere('email', 'demo@lms.test');
        $quiz = Quiz::where('user_id', $student->id)->first();

        $this->actingAs($student)->post("/quizzes/{$quiz->id}/attempts", ['assessment_type' => 'practice', 'confidence_before' => 4]);
        $attempt = QuizAttempt::where('user_id', $student->id)->whereNull('completed_at')->firstOrFail();

        $this->get("/attempts/{$attempt->id}/take")->assertOk()->assertSee('quizRunner', false);
    }
}

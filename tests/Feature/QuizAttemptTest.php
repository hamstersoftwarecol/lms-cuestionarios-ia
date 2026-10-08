<?php

namespace Tests\Feature;

use App\Enums\AssessmentType;
use App\Enums\Difficulty;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Notifications\BadgeEarned;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class QuizAttemptTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Quiz $quiz;

    private Question $mcq;

    private Question $sata;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBadges();
        $this->user = User::factory()->create();
        $this->quiz = Quiz::factory()->for($this->user)->create();
        $this->mcq = Question::factory()->for($this->quiz)->create(['correct_answers' => [2], 'difficulty' => Difficulty::Easy, 'position' => 0]);
        $this->sata = Question::factory()->sata([0, 2])->for($this->quiz)->create(['difficulty' => Difficulty::Hard, 'position' => 1]);
    }

    private function startAttempt(string $type = 'practice'): QuizAttempt
    {
        $this->actingAs($this->user)->post(route('attempts.store', $this->quiz), [
            'assessment_type' => $type,
            'confidence_before' => 3,
        ])->assertRedirect();

        return QuizAttempt::latest('id')->firstOrFail();
    }

    public function test_the_runner_never_exposes_the_correct_answers(): void
    {
        $attempt = $this->startAttempt();

        $this->get(route('attempts.take', $attempt))
            ->assertOk()
            ->assertSee($this->mcq->question_text)
            ->assertDontSee('correct_answers')
            ->assertDontSee($this->mcq->explanation);
    }

    public function test_a_perfect_attempt_is_graded_with_bonus_points_badges_and_streak(): void
    {
        Notification::fake();
        $attempt = $this->startAttempt('pre');

        $this->post(route('attempts.submit', $attempt), [
            'answers' => [
                $this->mcq->id => [2],
                $this->sata->id => [2, 0],
            ],
        ])->assertRedirect(route('attempts.show', $attempt))->assertSessionHas('newBadges');

        $attempt->refresh();
        $this->assertSame(2, $attempt->score);
        $this->assertSame(100.0, $attempt->percentage);
        $this->assertSame(AssessmentType::Pre, $attempt->assessment_type);
        // 10 (fácil) + 20 (difícil) = 30, +25 % por la puntuación perfecta = 38.
        $this->assertSame(38, $attempt->points_earned);
        $this->assertSame(38, $this->user->fresh()->points);
        $this->assertSame(1, $this->user->fresh()->current_streak);
        $this->assertEqualsCanonicalizing(['first-quiz', 'perfect-score'], $this->user->badges()->pluck('slug')->all());
        Notification::assertSentTo($this->user, BadgeEarned::class);
    }

    public function test_sata_questions_require_the_exact_set_of_answers(): void
    {
        $attempt = $this->startAttempt();

        $this->post(route('attempts.submit', $attempt), [
            'answers' => [
                $this->mcq->id => [1],
                $this->sata->id => [0], // Falta una correcta.
            ],
        ]);

        $attempt->refresh();
        $this->assertSame(0, $attempt->score);
        $this->assertSame(0, $attempt->points_earned);
        $this->assertFalse($attempt->answers()->where('question_id', $this->sata->id)->value('is_correct'));
    }

    public function test_out_of_range_options_are_ignored(): void
    {
        $attempt = $this->startAttempt();

        $this->post(route('attempts.submit', $attempt), ['answers' => [$this->mcq->id => [2, 9]]]);

        $this->assertSame([2], $attempt->answers()->where('question_id', $this->mcq->id)->first()->selected_answers);
        $this->assertSame(1, $attempt->fresh()->score);
    }

    public function test_an_attempt_cannot_be_submitted_twice(): void
    {
        $attempt = $this->startAttempt();
        $this->post(route('attempts.submit', $attempt), ['answers' => [$this->mcq->id => [2]]]);
        $points = $this->user->fresh()->points;

        $this->post(route('attempts.submit', $attempt), ['answers' => [$this->mcq->id => [2], $this->sata->id => [0, 2]]]);

        $this->assertSame($points, $this->user->fresh()->points);
        $this->assertSame(2, $attempt->answers()->count());
    }

    public function test_post_assessment_reflection_is_saved_and_learning_gain_is_shown(): void
    {
        $pre = $this->startAttempt('pre');
        $this->post(route('attempts.submit', $pre), ['answers' => [$this->mcq->id => [0]]]);

        $post = $this->startAttempt('post');
        $this->post(route('attempts.submit', $post), ['answers' => [$this->mcq->id => [2], $this->sata->id => [0, 2]]]);

        $this->patch(route('attempts.reflect', $post), ['confidence_after' => 5, 'reflection' => 'Repasar el ciclo de Calvin'])
            ->assertSessionHas('success');

        $this->assertSame(5, $post->fresh()->confidence_after);
        $this->get(route('attempts.show', $post))->assertOk()->assertSee('Mejoraste')->assertSee('100 puntos');
    }

    public function test_attempts_belong_to_their_owner(): void
    {
        $attempt = $this->startAttempt();
        $other = User::factory()->create();

        $this->actingAs($other)->get(route('attempts.take', $attempt))->assertForbidden();
        $this->actingAs($other)->post(route('attempts.submit', $attempt))->assertForbidden();
    }

    public function test_streak_grows_on_consecutive_days_and_resets_after_a_gap(): void
    {
        $this->user->forceFill(['current_streak' => 4, 'longest_streak' => 4, 'last_activity_date' => today()->subDay()])->save();
        $attempt = $this->startAttempt();
        $this->post(route('attempts.submit', $attempt), ['answers' => []]);
        $this->assertSame(5, $this->user->fresh()->current_streak);
        $this->assertTrue($this->user->badges()->where('slug', 'streak-3')->exists());

        $this->user->forceFill(['last_activity_date' => today()->subDays(3)])->save();
        $attempt = $this->startAttempt();
        $this->post(route('attempts.submit', $attempt), ['answers' => []]);
        $this->assertSame(1, $this->user->fresh()->current_streak);
        $this->assertSame(5, $this->user->fresh()->longest_streak);
    }

    public function test_an_expired_timer_still_allows_grading_what_was_answered(): void
    {
        $this->quiz->update(['time_limit' => 1]);
        $attempt = $this->startAttempt();
        $attempt->update(['started_at' => now()->subMinutes(5)]);

        $this->get(route('attempts.take', $attempt))->assertOk()->assertSee('remainingSeconds: 0', false);
        $this->post(route('attempts.submit', $attempt), ['answers' => [$this->mcq->id => [2]]])->assertRedirect();

        $this->assertNotNull($attempt->fresh()->completed_at);
    }
}

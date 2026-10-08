<?php

namespace Tests\Feature;

use App\Models\QuizAttempt;
use App\Models\User;
use App\Services\LeaderboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaderboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_rankings_are_calculated_per_period(): void
    {
        $this->travelTo(now()->startOfMonth()->addDays(14)->setTime(12, 0));

        $ana = User::factory()->create(['name' => 'Ana']);
        $luis = User::factory()->create(['name' => 'Luis']);
        $inactive = User::factory()->inactive()->create();

        QuizAttempt::factory()->for($ana)->create(['points_earned' => 50, 'completed_at' => now()]);
        QuizAttempt::factory()->for($luis)->create(['points_earned' => 30, 'completed_at' => now()]);
        QuizAttempt::factory()->for($luis)->create(['points_earned' => 100, 'completed_at' => now()->subDays(10)]);
        QuizAttempt::factory()->for($ana)->create(['points_earned' => 500, 'completed_at' => now()->subMonths(2)]);
        QuizAttempt::factory()->for($inactive)->create(['points_earned' => 9999, 'completed_at' => now()]);
        QuizAttempt::factory()->inProgress()->for($luis)->create(['points_earned' => 999]);

        $service = app(LeaderboardService::class);

        $this->assertSame(['Ana', 'Luis'], $service->top('weekly')->pluck('user.name')->all());
        $this->assertSame([50, 30], $service->top('weekly')->pluck('points')->all());
        $this->assertSame(['Luis', 'Ana'], $service->top('monthly')->pluck('user.name')->all());
        $this->assertSame(['Ana', 'Luis'], $service->top('all')->pluck('user.name')->all());
        $this->assertSame(['rank' => 2, 'points' => 30], $service->position($luis, 'weekly'));
        $this->assertSame(['rank' => null, 'points' => 0], $service->position(User::factory()->create(), 'weekly'));
    }

    public function test_the_leaderboard_page_highlights_the_current_user(): void
    {
        $user = User::factory()->create(['name' => 'Marta']);
        QuizAttempt::factory()->for($user)->create(['points_earned' => 80]);

        $this->actingAs($user)->get('/leaderboard?period=weekly')->assertOk()->assertSee('Marta')->assertSee('Puesto #1');
    }
}

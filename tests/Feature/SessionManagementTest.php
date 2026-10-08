<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SessionManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['session.driver' => 'database']);
    }

    private function addSession(User $user, string $id, string $agent = 'Mozilla/5.0 (Windows NT 10.0) Chrome/130.0 Safari/537.36'): void
    {
        DB::table('sessions')->insert([
            'id' => $id, 'user_id' => $user->id, 'ip_address' => '192.168.1.10',
            'user_agent' => $agent, 'payload' => '', 'last_activity' => now()->timestamp,
        ]);
    }

    public function test_users_see_their_sessions_on_the_profile_page(): void
    {
        $user = User::factory()->create();
        $this->addSession($user, 'laptop');

        $this->actingAs($user)->get('/profile')->assertOk()->assertSee('Chrome en Windows')->assertSee('192.168.1.10');
    }

    public function test_users_can_close_one_of_their_sessions(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->addSession($user, 'mine');
        $this->addSession($other, 'not-mine');

        $this->actingAs($user)->delete(route('sessions.destroy', 'mine'));
        $this->actingAs($user)->delete(route('sessions.destroy', 'not-mine'));

        $this->assertDatabaseMissing('sessions', ['id' => 'mine']);
        $this->assertDatabaseHas('sessions', ['id' => 'not-mine']);
    }

    public function test_closing_other_sessions_requires_the_password(): void
    {
        $user = User::factory()->create();
        $this->addSession($user, 'phone');

        $this->actingAs($user)->delete(route('sessions.others'), ['password' => 'wrong'])->assertSessionHasErrorsIn('sessions', 'password');
        $this->assertDatabaseHas('sessions', ['id' => 'phone']);

        $this->actingAs($user)->delete(route('sessions.others'), ['password' => 'password'])->assertSessionHas('status', 'sessions-closed');
        $this->assertDatabaseMissing('sessions', ['id' => 'phone']);
    }

    public function test_theme_and_voice_preferences_are_saved(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patchJson(route('preferences.theme'), ['theme' => 'dark'])->assertNoContent();
        $this->assertSame('dark', $user->fresh()->theme);

        $this->actingAs($user)->patch(route('preferences.update'), ['theme' => 'light', 'tts_voice' => 'Charon'])->assertSessionHas('status', 'preferences-updated');
        $this->assertSame('Charon', $user->fresh()->tts_voice);

        $this->actingAs($user)->get('/dashboard')->assertSee('data-theme="light"', false);
    }
}

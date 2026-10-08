<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\ActivityLog;
use App\Models\Announcement;
use App\Models\Quiz;
use App\Models\User;
use App\Notifications\AnnouncementPublished;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    private function fakeSession(User $user, string $id): void
    {
        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $user->id,
            'ip_address' => '10.0.0.1',
            'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) Safari/604.1',
            'payload' => base64_encode('a:0:{}'),
            'last_activity' => now()->timestamp,
        ]);
    }

    public function test_regular_users_cannot_access_the_admin_panel(): void
    {
        $user = User::factory()->create();

        foreach (['/admin', '/admin/users', '/admin/sessions', '/admin/activity', '/admin/backups', '/admin/announcements'] as $uri) {
            $this->actingAs($user)->get($uri)->assertForbidden();
        }
    }

    public function test_the_sidebar_shows_admin_links_only_to_admins(): void
    {
        $this->actingAs(User::factory()->create())->get('/dashboard')->assertDontSee('Administración');
        $this->actingAs($this->admin)->get('/dashboard')->assertSee('Administración');
    }

    public function test_admin_can_change_roles_and_deactivate_users(): void
    {
        config(['session.driver' => 'database']);
        $user = User::factory()->create();
        $this->fakeSession($user, 'session-to-kill');

        $this->actingAs($this->admin)->put(route('admin.users.update', $user), [
            'name' => 'Nuevo nombre',
            'email' => $user->email,
            'role' => 'admin',
            'is_active' => '0',
            'verified' => '1',
        ])->assertSessionHas('success');

        $user->refresh();
        $this->assertSame(Role::Admin, $user->role);
        $this->assertFalse($user->is_active);
        $this->assertSame('Nuevo nombre', $user->name);
        $this->assertDatabaseMissing('sessions', ['id' => 'session-to-kill']);
        $this->assertDatabaseHas(ActivityLog::class, ['action' => 'admin.user_updated', 'user_id' => $this->admin->id]);
    }

    public function test_admins_cannot_demote_themselves(): void
    {
        $this->actingAs($this->admin)->put(route('admin.users.update', $this->admin), [
            'name' => $this->admin->name,
            'email' => $this->admin->email,
            'role' => 'user',
            'is_active' => '1',
        ])->assertSessionHas('error');

        $this->assertTrue($this->admin->fresh()->isAdmin());
    }

    public function test_deactivated_users_are_logged_out_on_their_next_request(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/dashboard')->assertOk();

        $user->forceFill(['is_active' => false])->save();

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_force_logout_removes_all_sessions_and_rotates_remember_token(): void
    {
        config(['session.driver' => 'database']);
        $user = User::factory()->create(['remember_token' => 'old-token']);
        $this->fakeSession($user, 'a');
        $this->fakeSession($user, 'b');

        $this->actingAs($this->admin)->get('/admin/sessions')->assertOk()->assertSee('Safari en iOS');
        $this->actingAs($this->admin)->post(route('admin.users.logout', $user))->assertSessionHas('success');

        $this->assertSame(0, DB::table('sessions')->where('user_id', $user->id)->count());
        $this->assertNotSame('old-token', $user->fresh()->remember_token);
    }

    public function test_a_single_session_can_be_closed(): void
    {
        config(['session.driver' => 'database']);
        $user = User::factory()->create();
        $this->fakeSession($user, 'single');

        $this->actingAs($this->admin)->delete(route('admin.sessions.destroy', 'single'));

        $this->assertDatabaseMissing('sessions', ['id' => 'single']);
    }

    public function test_announcements_are_published_and_notified(): void
    {
        Notification::fake();
        $students = User::factory()->count(2)->create();
        User::factory()->inactive()->create();

        $this->actingAs($this->admin)->post(route('admin.announcements.store'), [
            'title' => 'Mantenimiento',
            'body' => 'El sábado habrá mantenimiento.',
            'type' => 'warning',
            'is_active' => '1',
            'notify' => '1',
        ])->assertRedirect(route('admin.announcements.index'));

        Notification::assertSentTo($students, AnnouncementPublished::class);
        Notification::assertCount(3); // 2 estudiantes + el administrador.

        $this->actingAs($students[0])->get('/dashboard')->assertSee('Mantenimiento');
    }

    public function test_expired_announcements_are_not_shown(): void
    {
        Announcement::create(['title' => 'Viejo aviso', 'body' => 'x', 'type' => 'info', 'is_active' => true, 'ends_at' => now()->subDay()]);
        Announcement::create(['title' => 'Aviso futuro', 'body' => 'x', 'type' => 'info', 'is_active' => true, 'starts_at' => now()->addDay()]);

        $this->actingAs($this->admin)->get('/dashboard')->assertDontSee('Viejo aviso')->assertDontSee('Aviso futuro');
    }

    public function test_admin_can_delete_any_quiz(): void
    {
        $quiz = Quiz::factory()->create();

        $this->actingAs($this->admin)->delete(route('admin.quizzes.destroy', $quiz))->assertSessionHas('success');

        $this->assertModelMissing($quiz);
    }

    public function test_activity_log_records_logins_with_ip(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $log = ActivityLog::where('action', 'auth.login')->firstOrFail();
        $this->assertSame($user->id, $log->user_id);
        $this->assertSame('127.0.0.1', $log->ip_address);
        $this->assertNotNull($user->fresh()->last_login_at);

        $this->actingAs($this->admin)->get('/admin/activity?action=auth')->assertOk()->assertSee('Inició sesión');
    }
}

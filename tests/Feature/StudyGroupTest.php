<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\StudyGroup;
use App\Models\User;
use App\Notifications\GroupActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class StudyGroupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBadges();
    }

    public function test_a_group_is_created_with_a_unique_invite_code(): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner)->post('/groups', ['name' => 'Cálculo I'])->assertRedirect();

        $group = StudyGroup::firstOrFail();
        $this->assertMatchesRegularExpression('/^[A-HJ-NP-Z2-9]{8}$/', $group->invite_code);
        $this->assertTrue($group->isOwner($owner));
        $this->assertTrue($group->hasMember($owner));
        $this->assertTrue($owner->badges()->where('slug', 'team-player')->exists());
    }

    public function test_users_join_with_the_code_in_any_format(): void
    {
        Notification::fake();
        $group = StudyGroup::factory()->create();
        $student = User::factory()->create();
        $code = strtolower(substr($group->invite_code, 0, 4).'-'.substr($group->invite_code, 4));

        $this->actingAs($student)->post('/groups/join', ['code' => " {$code} "])->assertRedirect(route('groups.show', $group));

        $this->assertTrue($group->hasMember($student));
        Notification::assertSentTo($group->owner, GroupActivity::class);
    }

    public function test_invalid_codes_and_full_groups_are_rejected(): void
    {
        $group = StudyGroup::factory()->create(['max_members' => 1]);
        $student = User::factory()->create();

        $this->actingAs($student)->post('/groups/join', ['code' => 'ZZZZZZZZ'])->assertSessionHasErrors('code');
        $this->actingAs($student)->post('/groups/join', ['code' => $group->invite_code])->assertSessionHasErrors('code');
        $this->assertFalse($group->hasMember($student));
    }

    public function test_shared_quizzes_become_visible_to_members_only(): void
    {
        $group = StudyGroup::factory()->create();
        $member = User::factory()->create();
        $group->members()->attach($member, ['role' => 'member']);
        $quiz = Quiz::factory()->withQuestions()->for($group->owner)->create();
        $outsider = User::factory()->create();

        $this->actingAs($member)->get(route('quizzes.show', $quiz))->assertForbidden();

        $this->actingAs($group->owner)->post(route('groups.quizzes.store', $group), ['quiz_id' => $quiz->id])->assertSessionHas('success');

        $this->actingAs($member)->get(route('quizzes.show', $quiz))->assertOk();
        $this->actingAs($member)->get('/quizzes?tab=shared')->assertSee($quiz->title);
        $this->actingAs($outsider)->get(route('quizzes.show', $quiz))->assertForbidden();
        $this->actingAs($outsider)->get(route('groups.show', $group))->assertForbidden();
    }

    public function test_members_cannot_share_quizzes_they_do_not_own(): void
    {
        $group = StudyGroup::factory()->create();
        $member = User::factory()->create();
        $group->members()->attach($member, ['role' => 'member']);
        $foreignQuiz = Quiz::factory()->create();

        $this->actingAs($member)->post(route('groups.quizzes.store', $group), ['quiz_id' => $foreignQuiz->id])->assertSessionHasErrors('quiz_id');
    }

    public function test_only_the_owner_manages_the_group(): void
    {
        $group = StudyGroup::factory()->create();
        $member = User::factory()->create();
        $group->members()->attach($member, ['role' => 'member']);
        $oldCode = $group->invite_code;

        $this->actingAs($member)->post(route('groups.code', $group))->assertForbidden();
        $this->actingAs($member)->delete(route('groups.members.destroy', [$group, $group->owner]))->assertForbidden();

        $this->actingAs($group->owner)->post(route('groups.code', $group));
        $this->assertNotSame($oldCode, $group->fresh()->invite_code);

        $this->actingAs($group->owner)->delete(route('groups.members.destroy', [$group, $member]));
        $this->assertFalse($group->hasMember($member));
    }

    public function test_members_can_leave_but_the_owner_cannot(): void
    {
        $group = StudyGroup::factory()->create();
        $member = User::factory()->create();
        $group->members()->attach($member, ['role' => 'member']);

        $this->actingAs($member)->post(route('groups.leave', $group))->assertRedirect(route('groups.index'));
        $this->assertFalse($group->hasMember($member));

        $this->actingAs($group->owner)->post(route('groups.leave', $group))->assertStatus(422);
    }
}

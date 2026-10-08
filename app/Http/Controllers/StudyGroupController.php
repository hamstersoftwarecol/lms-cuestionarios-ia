<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\StudyGroup;
use App\Models\User;
use App\Notifications\GroupActivity;
use App\Services\ActivityLogger;
use App\Services\GamificationService;
use App\Services\LeaderboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudyGroupController extends Controller
{
    public function __construct(private readonly GamificationService $gamification) {}

    public function index(Request $request): View
    {
        return view('groups.index', [
            'groups' => $request->user()->studyGroups()->with('owner:id,name')->withCount('members', 'quizzes')->latest('study_group_user.joined_at')->get(),
            'code' => Str::upper((string) $request->query('code')),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'max_members' => ['nullable', 'integer', 'between:2,500'],
        ]);

        $group = new StudyGroup($validated + ['max_members' => $validated['max_members'] ?? 50]);
        $group->owner()->associate($request->user())->save();
        $group->members()->attach($request->user()->id, ['role' => 'owner', 'joined_at' => now()]);

        ActivityLogger::log('group.created', "Creó el grupo «{$group->name}»", $group);
        $this->gamification->checkBadges($request->user());

        return redirect()->route('groups.show', $group)->with('success', "Grupo creado. Comparte el código {$group->formattedCode()} con tus compañeros.");
    }

    public function join(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string', 'max:20']]);

        $code = Str::upper(preg_replace('/[^A-Za-z0-9]/', '', $request->code));
        $group = StudyGroup::where('invite_code', $code)->first();
        $user = $request->user();

        if (! $group) {
            return back()->withInput()->withErrors(['code' => 'No existe ningún grupo con ese código de invitación.']);
        }

        if ($group->hasMember($user)) {
            return redirect()->route('groups.show', $group)->with('success', 'Ya eras miembro de este grupo.');
        }

        if ($group->isFull()) {
            return back()->withInput()->withErrors(['code' => 'El grupo está completo.']);
        }

        $group->members()->attach($user->id, ['role' => 'member', 'joined_at' => now()]);
        $group->owner->notify(new GroupActivity('Nuevo miembro', "{$user->name} se unió a «{$group->name}».", route('groups.show', $group)));

        ActivityLogger::log('group.joined', "Se unió al grupo «{$group->name}»", $group);
        $this->gamification->checkBadges($user);

        return redirect()->route('groups.show', $group)->with('success', "¡Te uniste a «{$group->name}»!");
    }

    public function show(Request $request, StudyGroup $group, LeaderboardService $leaderboard): View
    {
        $this->authorize('view', $group);

        $period = array_key_exists($request->period, LeaderboardService::PERIODS) ? $request->period : 'weekly';
        $memberIds = $group->members()->pluck('users.id')->all();

        return view('groups.show', [
            'group' => $group->load('owner:id,name'),
            'members' => $group->members()->orderByPivot('joined_at')->get(),
            'quizzes' => $group->quizzes()->withCount('questions')->with('user:id,name')->latest('quiz_study_group.created_at')->get(),
            'ranking' => $leaderboard->top($period, 20, $memberIds),
            'period' => $period,
            'myQuizzes' => $request->user()->quizzes()->whereNotIn('id', $group->quizzes()->pluck('quizzes.id'))->latest()->get(['id', 'title']),
            'canManage' => $request->user()->can('manage', $group),
        ]);
    }

    public function update(Request $request, StudyGroup $group): RedirectResponse
    {
        $this->authorize('manage', $group);

        $group->update($request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'max_members' => ['required', 'integer', 'between:2,500'],
        ]));

        return back()->with('success', 'Grupo actualizado.');
    }

    public function destroy(StudyGroup $group): RedirectResponse
    {
        $this->authorize('delete', $group);

        ActivityLogger::log('group.deleted', "Eliminó el grupo «{$group->name}»");
        $group->delete();

        return redirect()->route('groups.index')->with('success', 'Grupo eliminado.');
    }

    public function leave(Request $request, StudyGroup $group): RedirectResponse
    {
        abort_if($group->isOwner($request->user()), 422, 'El propietario no puede abandonar el grupo; elimínalo si ya no lo necesitas.');

        $group->members()->detach($request->user()->id);
        ActivityLogger::log('group.left', "Abandonó el grupo «{$group->name}»", $group);

        return redirect()->route('groups.index')->with('success', 'Has salido del grupo.');
    }

    public function regenerateCode(StudyGroup $group): RedirectResponse
    {
        $this->authorize('manage', $group);

        $group->forceFill(['invite_code' => StudyGroup::generateInviteCode()])->save();

        return back()->with('success', "Nuevo código de invitación: {$group->formattedCode()}");
    }

    public function removeMember(StudyGroup $group, User $user): RedirectResponse
    {
        $this->authorize('manage', $group);
        abort_if($group->isOwner($user), 422, 'No puedes expulsar al propietario.');

        $group->members()->detach($user->id);
        ActivityLogger::log('group.member_removed', "Expulsó a {$user->name} de «{$group->name}»", $group);

        return back()->with('success', "{$user->name} ya no es miembro del grupo.");
    }

    public function shareQuiz(Request $request, StudyGroup $group): RedirectResponse
    {
        $this->authorize('contribute', $group);
        $user = $request->user();

        $validated = $request->validate([
            'quiz_id' => ['required', Rule::exists('quizzes', 'id')->where('user_id', $user->id)],
        ]);

        $quiz = Quiz::findOrFail($validated['quiz_id']);
        $group->quizzes()->syncWithoutDetaching([$quiz->id => ['shared_by' => $user->id]]);

        Notification::send(
            $group->members()->where('users.id', '!=', $user->id)->get(),
            new GroupActivity('Cuestionario compartido', "{$user->name} compartió «{$quiz->title}» en {$group->name}.", route('quizzes.show', $quiz), '📝'),
        );
        ActivityLogger::log('group.quiz_shared', "Compartió «{$quiz->title}» en «{$group->name}»", $group);

        return back()->with('success', 'Cuestionario compartido con el grupo.');
    }

    public function unshareQuiz(Request $request, StudyGroup $group, Quiz $quiz): RedirectResponse
    {
        $this->authorize('contribute', $group);
        abort_unless($quiz->user_id === $request->user()->id || $group->isOwner($request->user()), 403);

        $group->quizzes()->detach($quiz->id);

        return back()->with('success', 'Cuestionario retirado del grupo.');
    }
}

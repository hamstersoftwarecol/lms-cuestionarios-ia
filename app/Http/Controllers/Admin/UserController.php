<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Support\SessionRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', [
            'users' => User::withCount(['notes', 'quizzes', 'attempts' => fn ($q) => $q->whereNotNull('completed_at')])
                ->latest()
                ->get(),
        ]);
    }

    public function show(User $user): View
    {
        return view('admin.users.show', [
            'user' => $user->loadCount(['notes', 'quizzes', 'studyGroups', 'badges', 'attempts' => fn ($q) => $q->whereNotNull('completed_at')]),
            'attempts' => $user->attempts()->whereNotNull('completed_at')->with('quiz:id,title')->latest('completed_at')->limit(10)->get(),
            'activity' => $user->activityLogs()->latest('created_at')->limit(15)->get(),
            'sessions' => SessionRepository::forUser($user),
            'roles' => Role::cases(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'role' => ['required', Rule::enum(Role::class)],
            'is_active' => ['boolean'],
            'verified' => ['boolean'],
        ]);

        $isSelf = $user->is($request->user());

        if ($isSelf && ($validated['role'] !== Role::Admin->value || ! $request->boolean('is_active'))) {
            return back()->with('error', 'No puedes quitarte el rol de administrador ni desactivar tu propia cuenta.');
        }

        $user->fill(['name' => $validated['name'], 'email' => $validated['email']]);
        $user->role = Role::from($validated['role']);
        $user->is_active = $request->boolean('is_active');
        $user->email_verified_at = $request->boolean('verified') ? ($user->email_verified_at ?? now()) : null;
        $changes = array_keys($user->getDirty());
        $user->save();

        if (! $user->is_active) {
            SessionRepository::destroyAllFor($user);
        }

        ActivityLogger::log('admin.user_updated', "Actualizó al usuario {$user->email}", $user, ['changes' => $changes]);

        return back()->with('success', 'Usuario actualizado.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_if($user->is($request->user()), 422, 'No puedes eliminar tu propia cuenta desde el panel.');

        SessionRepository::destroyAllFor($user);
        ActivityLogger::log('admin.user_deleted', "Eliminó al usuario {$user->email}");
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'Usuario eliminado.');
    }

    public function forceLogout(Request $request, User $user): RedirectResponse
    {
        $closed = SessionRepository::destroyAllFor($user, exceptId: $user->is($request->user()) ? $request->session()->getId() : null);
        ActivityLogger::log('admin.force_logout', "Forzó el cierre de {$closed} sesiones de {$user->email}", $user);

        return back()->with('success', "Se cerraron {$closed} sesiones de {$user->name}.");
    }
}

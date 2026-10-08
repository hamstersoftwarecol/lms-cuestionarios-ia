<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Services\ActivityLogger;
use App\Services\Ai\TextToSpeech;
use App\Support\SessionRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
            'voices' => TextToSpeech::voices(),
            'sessions' => SessionRepository::forUser($request->user()),
            'sessionsSupported' => SessionRepository::isDatabaseDriver(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();
        ActivityLogger::log('profile.updated', 'Actualizó su perfil');

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Las cuentas creadas con Google no tienen contraseña: confirman escribiendo ELIMINAR.
        $request->validateWithBag('userDeletion', [
            'password' => $request->user()->password === null ? ['required', 'in:ELIMINAR'] : ['required', 'current_password'],
        ], ['password.in' => 'Escribe ELIMINAR para confirmar.']);

        $user = $request->user();
        ActivityLogger::log('profile.deleted', "Eliminó su cuenta ({$user->email})");

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}

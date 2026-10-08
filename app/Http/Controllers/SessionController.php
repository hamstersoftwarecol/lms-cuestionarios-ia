<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use App\Support\SessionRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Gestión de las sesiones activas del propio usuario (perfil).
 */
class SessionController extends Controller
{
    public function destroy(Request $request, string $session): RedirectResponse
    {
        abort_if($session === $request->session()->getId(), 422, 'No puedes cerrar la sesión actual desde aquí.');

        SessionRepository::destroy($session, $request->user()->id);
        ActivityLogger::log('session.revoked', 'Cerró una de sus sesiones remotas');

        return back()->with('status', 'session-closed');
    }

    public function destroyOthers(Request $request): RedirectResponse
    {
        if ($request->user()->password !== null) {
            $request->validateWithBag('sessions', ['password' => ['required', 'current_password']]);
        }

        $closed = SessionRepository::destroyAllFor($request->user(), exceptId: $request->session()->getId());
        ActivityLogger::log('session.revoked_all', "Cerró {$closed} sesiones en otros dispositivos");

        return back()->with('status', 'sessions-closed');
    }
}

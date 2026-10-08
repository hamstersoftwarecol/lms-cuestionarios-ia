<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use App\Support\SessionRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Monitorización de sesiones activas y cierre de sesión forzado.
 */
class SessionController extends Controller
{
    public function index(): View
    {
        return view('admin.sessions.index', [
            'sessions' => SessionRepository::all(),
            'supported' => SessionRepository::isDatabaseDriver(),
            'lifetime' => config('session.lifetime'),
        ]);
    }

    public function destroy(Request $request, string $session): RedirectResponse
    {
        abort_if($session === $request->session()->getId(), 422, 'Esta es tu sesión actual.');

        SessionRepository::destroy($session);
        ActivityLogger::log('admin.session_revoked', 'Forzó el cierre de una sesión', properties: ['session' => substr($session, 0, 8).'…']);

        return back()->with('success', 'Sesión cerrada.');
    }
}

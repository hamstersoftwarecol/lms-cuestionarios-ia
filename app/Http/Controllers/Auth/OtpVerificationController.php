<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OtpVerificationController extends Controller
{
    public function __invoke(Request $request, OtpService $otp): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
        }

        $request->validate(['code' => ['required', 'string', 'regex:/^\s*\d{3}\s*-?\s*\d{3}\s*$/']], [
            'code.regex' => 'El código debe tener 6 dígitos.',
        ]);

        return match ($otp->verify($user, $request->string('code'))) {
            'verified' => redirect()->intended(route('dashboard', absolute: false).'?verified=1')
                ->with('success', '¡Correo verificado! Bienvenido.'),
            'expired' => back()->withErrors(['code' => 'El código ha caducado. Solicita uno nuevo.']),
            'locked' => back()->withErrors(['code' => 'Demasiados intentos fallidos. Solicita un código nuevo.']),
            default => back()->withErrors(['code' => 'El código no es correcto.']),
        };
    }
}

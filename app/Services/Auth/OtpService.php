<?php

namespace App\Services\Auth;

use App\Mail\OtpCodeMail;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/**
 * Verificación del correo mediante un código de un solo uso (OTP) de 6 dígitos.
 */
class OtpService
{
    public function send(User $user): void
    {
        $code = (string) random_int(100000, 999999);
        $minutes = config('lms.otp.ttl_minutes');

        $user->forceFill([
            'otp_code' => Hash::make($code),
            'otp_expires_at' => now()->addMinutes($minutes),
            'otp_attempts' => 0,
        ])->save();

        Mail::to($user)->send(new OtpCodeMail($user, $code, $minutes));
    }

    /**
     * @return 'verified'|'invalid'|'expired'|'locked'
     */
    public function verify(User $user, string $code): string
    {
        if ($user->otp_code === null || $user->otp_expires_at === null || $user->otp_expires_at->isPast()) {
            return 'expired';
        }

        if ($user->otp_attempts >= config('lms.otp.max_attempts')) {
            return 'locked';
        }

        if (! Hash::check(preg_replace('/\D/', '', $code), $user->otp_code)) {
            $user->increment('otp_attempts');

            return 'invalid';
        }

        $user->forceFill([
            'email_verified_at' => now(),
            'otp_code' => null,
            'otp_expires_at' => null,
            'otp_attempts' => 0,
        ])->save();

        event(new Verified($user));

        return 'verified';
    }
}

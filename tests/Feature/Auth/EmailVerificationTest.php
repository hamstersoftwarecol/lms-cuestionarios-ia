<?php

namespace Tests\Feature\Auth;

use App\Mail\OtpCodeMail;
use App\Models\User;
use App\Services\Auth\OtpService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_screen_can_be_rendered(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get('/verify-email')->assertOk()->assertSee('Verifica tu correo');
    }

    public function test_unverified_users_are_redirected_to_the_otp_screen(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('verification.notice'));
    }

    public function test_registration_sends_an_otp_code_by_email(): void
    {
        Mail::fake();

        $this->post('/register', [
            'name' => 'Ana Pérez',
            'email' => 'ana@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        Mail::assertSent(OtpCodeMail::class, fn (OtpCodeMail $mail) => $mail->hasTo('ana@example.com') && preg_match('/^\d{6}$/', $mail->code));
        $this->assertNotNull(User::firstWhere('email', 'ana@example.com')->otp_code);
    }

    public function test_email_can_be_verified_with_the_otp_code(): void
    {
        Mail::fake();
        Event::fake([Verified::class]);
        $user = User::factory()->unverified()->create();

        $user->sendEmailVerificationNotification();
        $code = null;
        Mail::assertSent(OtpCodeMail::class, function (OtpCodeMail $mail) use (&$code) {
            $code = $mail->code;

            return true;
        });

        $response = $this->actingAs($user)->post('/verify-email', ['code' => substr($code, 0, 3).'-'.substr($code, 3)]);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertNull($user->fresh()->otp_code);
        $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
    }

    public function test_email_is_not_verified_with_an_invalid_code(): void
    {
        Mail::fake();
        $user = User::factory()->unverified()->create();
        app(OtpService::class)->send($user);

        $this->actingAs($user)->post('/verify-email', ['code' => '000000'])->assertSessionHasErrors('code');

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
        $this->assertSame(1, $user->fresh()->otp_attempts);
    }

    public function test_expired_codes_are_rejected(): void
    {
        Mail::fake();
        $user = User::factory()->unverified()->create();
        app(OtpService::class)->send($user);
        $user->forceFill(['otp_expires_at' => now()->subMinute()])->save();

        $this->assertSame('expired', app(OtpService::class)->verify($user->fresh(), '123456'));
    }

    public function test_code_is_locked_after_too_many_attempts(): void
    {
        Mail::fake();
        $user = User::factory()->unverified()->create();
        app(OtpService::class)->send($user);
        $user->forceFill(['otp_attempts' => config('lms.otp.max_attempts')])->save();

        $this->assertSame('locked', app(OtpService::class)->verify($user->fresh(), '123456'));
    }

    public function test_a_new_code_can_be_requested(): void
    {
        Mail::fake();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->post('/email/verification-notification')->assertSessionHas('status', 'verification-code-sent');

        Mail::assertSent(OtpCodeMail::class);
    }
}

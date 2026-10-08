<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Factory as Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.google.client_id' => 'client-id', 'services.google.client_secret' => 'secret']);
    }

    private function mockGoogleUser(string $email, string $id = 'google-123'): void
    {
        $googleUser = (new SocialiteUser)->map([
            'id' => $id,
            'name' => 'Laura Gómez',
            'email' => $email,
            'avatar' => 'https://lh3.googleusercontent.com/a/avatar.png',
        ]);

        $provider = Mockery::mock(GoogleProvider::class);
        $provider->shouldReceive('user')->andReturn($googleUser);

        $socialite = Mockery::mock(Socialite::class);
        $socialite->shouldReceive('driver')->with('google')->andReturn($provider);
        $this->app->instance(Socialite::class, $socialite);
    }

    public function test_the_google_button_is_hidden_when_not_configured(): void
    {
        config(['services.google.client_id' => null]);

        $this->get('/login')->assertDontSee('Continuar con Google');
        $this->get('/auth/google/redirect')->assertNotFound();
    }

    public function test_the_redirect_goes_to_google(): void
    {
        $this->get('/login')->assertSee('Continuar con Google');
        $this->get('/auth/google/redirect')->assertRedirectContains('accounts.google.com');
    }

    public function test_a_new_verified_user_is_created_from_google(): void
    {
        $this->mockGoogleUser('laura@gmail.com');

        $this->get('/auth/google/callback')->assertRedirect(route('dashboard', absolute: false));

        $user = User::firstWhere('email', 'laura@gmail.com');
        $this->assertAuthenticatedAs($user);
        $this->assertSame('google-123', $user->google_id);
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertNull($user->password);
    }

    public function test_an_existing_account_is_linked_by_email(): void
    {
        $existing = User::factory()->create(['email' => 'laura@gmail.com']);
        $this->mockGoogleUser('laura@gmail.com');

        $this->get('/auth/google/callback');

        $this->assertAuthenticatedAs($existing);
        $this->assertSame('google-123', $existing->fresh()->google_id);
        $this->assertSame(1, User::count());
    }

    public function test_deactivated_accounts_cannot_sign_in_with_google(): void
    {
        User::factory()->inactive()->create(['email' => 'laura@gmail.com']);
        $this->mockGoogleUser('laura@gmail.com');

        $this->get('/auth/google/callback')->assertRedirect(route('login'));
        $this->assertGuest();
    }
}

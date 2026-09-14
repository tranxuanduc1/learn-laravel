<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class WebAuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_login_form(): void
    {
        $this->get('/login')
            ->assertViewIs('auth.login')
            ->assertSee('name="_token"', false);
    }

    public function test_authenticated_user_is_redirected_away_from_login_form(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/login')
            ->assertRedirect(route('dashboard'));
    }

    public function test_valid_credentials_authenticate_user_and_regenerate_session(): void
    {
        $user = User::factory()->create([
            'email' => 'duc@example.com',
            'password' => 'correct-password',
        ]);
        $csrfToken = 'valid-csrf-token';
        $this->withSession(['_token' => $csrfToken]);
        $oldSessionId = Session::getId();

        $this->post('/login', [
            '_token' => $csrfToken,
            'email' => 'duc@example.com',
            'password' => 'correct-password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($oldSessionId, Session::getId());
    }

    public function test_invalid_credentials_return_error_and_do_not_authenticate_user(): void
    {
        User::factory()->create([
            'email' => 'duc@example.com',
            'password' => 'correct-password',
        ]);
        $csrfToken = 'valid-csrf-token';

        $this->withSession(['_token' => $csrfToken])->from('/login')->post('/login', [
            '_token' => $csrfToken,
            'email' => 'duc@example.com',
            'password' => 'wrong-password',
        ])->assertRedirect('/login')
            ->assertSessionHasErrors([
                'email' => 'The provided credentials are incorrect.',
            ]);

        $this->assertGuest();
    }

    public function test_guest_is_redirected_to_login_from_dashboard(): void
    {
        $this->get('/dashboard')
            ->assertRedirect(route('login'));
    }

    public function test_logout_invalidates_session_and_regenerates_csrf_token(): void
    {
        $user = User::factory()->create();
        $oldCsrfToken = 'old-csrf-token';

        $this->actingAs($user)
            ->withSession([
                '_token' => $oldCsrfToken,
                'temporary-value' => 'must-be-removed',
            ])
            ->post('/logout', ['_token' => $oldCsrfToken])
            ->assertRedirect(route('login'))
            ->assertSessionMissing('temporary-value')
            ->assertSessionHas('_token', fn (string $token): bool => $token !== $oldCsrfToken);

        $this->assertGuest();
    }

    public function test_api_login_still_returns_a_sanctum_personal_access_token(): void
    {
        User::factory()->create([
            'email' => 'duc@example.com',
            'password' => 'correct-password',
        ]);

        $this->postJson('/api/login', [
            'email' => 'duc@example.com',
            'password' => 'correct-password',
        ])->assertOk()
            ->assertJsonStructure(['token']);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebTodoControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_from_todo_list(): void
    {
        $this->get(route('todos.index'))
            ->assertRedirect(route('login'));
    }

    public function test_todo_page_is_a_blade_shell_without_todo_data(): void
    {
        $user = User::factory()->create();

        Todo::query()->create([
            'user_id' => $user->id,
            'title' => 'My private todo',
            'completed' => false,
        ]);

        $this->actingAs($user)
            ->get(route('todos.index'))
            ->assertOk()
            ->assertViewIs('todos.index')
            ->assertSee('action="'.route('todos.store').'"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('Loading todos...')
            ->assertDontSee('My private todo');
    }

    public function test_guest_cannot_access_todo_data_endpoint(): void
    {
        $this->getJson(route('todos.data'))
            ->assertUnauthorized();
    }

    public function test_todo_data_endpoint_only_returns_the_authenticated_users_todos(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Todo::query()->create([
            'user_id' => $user->id,
            'title' => 'My private todo',
            'completed' => false,
        ]);
        Todo::query()->create([
            'user_id' => $otherUser->id,
            'title' => 'Another user private todo',
            'completed' => true,
        ]);

        $this->actingAs($user)
            ->getJson(route('todos.data'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'My private todo')
            ->assertJsonPath('data.0.completed', false)
            ->assertJsonMissing(['title' => 'Another user private todo']);
    }

    public function test_todo_data_endpoint_returns_an_empty_collection(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('todos.data'))
            ->assertOk()
            ->assertExactJson(['data' => []]);
    }

    public function test_guest_cannot_create_a_todo(): void
    {
        $csrfToken = 'valid-csrf-token';

        $this->withSession(['_token' => $csrfToken])->postJson(route('todos.store'), [
            '_token' => $csrfToken,
            'title' => 'Guest todo',
        ])->assertUnauthorized();

        $this->assertDatabaseMissing('todos', [
            'title' => 'Guest todo',
        ]);
    }

    public function test_create_todo_rejects_a_request_without_a_csrf_token(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withMiddleware(ValidateCsrfToken::class)
            ->postJson(route('todos.store'), [
                'title' => 'Missing CSRF token',
            ])->assertStatus(419);

        $this->assertDatabaseMissing('todos', [
            'title' => 'Missing CSRF token',
        ]);
    }

    public function test_valid_payload_creates_a_todo_for_the_authenticated_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $csrfToken = 'valid-csrf-token';

        $this->actingAs($user)
            ->withSession(['_token' => $csrfToken])
            ->postJson(route('todos.store'), [
                '_token' => $csrfToken,
                'title' => 'Created with AJAX',
                'completed' => true,
                'user_id' => $otherUser->id,
            ])->assertCreated()
            ->assertJsonPath('data.title', 'Created with AJAX')
            ->assertJsonPath('data.completed', false);

        $this->assertDatabaseHas('todos', [
            'user_id' => $user->id,
            'title' => 'Created with AJAX',
            'completed' => false,
        ]);
        $this->assertDatabaseMissing('todos', [
            'user_id' => $otherUser->id,
            'title' => 'Created with AJAX',
        ]);
    }

    public function test_invalid_payload_returns_422_and_does_not_create_a_todo(): void
    {
        $user = User::factory()->create();
        $csrfToken = 'valid-csrf-token';

        $this->actingAs($user)
            ->withSession(['_token' => $csrfToken])
            ->postJson(route('todos.store'), [
                '_token' => $csrfToken,
                'title' => '',
            ])->assertUnprocessable()
            ->assertJsonValidationErrors(['title'])
            ->assertJsonPath('errors.title.0', 'The title field is required.');

        $this->assertDatabaseCount('todos', 0);
    }
}

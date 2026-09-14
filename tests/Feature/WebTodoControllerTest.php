<?php

namespace Tests\Feature;

use App\Models\Todo;
use App\Models\User;
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
}

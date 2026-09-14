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

    public function test_user_only_sees_their_own_todos(): void
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
            'completed' => false,
        ]);

        $this->actingAs($user)
            ->get(route('todos.index'))
            ->assertOk()
            ->assertViewIs('todos.index')
            ->assertSee('My private todo')
            ->assertDontSee('Another user private todo');
    }

    public function test_user_sees_an_empty_state_when_they_have_no_todos(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('todos.index'))
            ->assertOk()
            ->assertSee('You do not have any todos yet.');
    }
}

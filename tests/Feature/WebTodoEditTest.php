<?php

namespace Tests\Feature;

use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebTodoEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_open_the_todo_edit_page(): void
    {
        $user = User::factory()->create();
        $todo = Todo::query()->create([
            'user_id' => $user->id,
            'title' => 'Read Laravel docs',
            'completed' => false,
        ]);

        $response = $this->actingAs($user)->get(route('todos.edit', $todo));

        $response
            ->assertOk()
            ->assertViewIs('todos.edit')
            ->assertViewHas('todo', fn (Todo $viewTodo): bool => $viewTodo->is($todo))
            ->assertSee('Read Laravel docs');
    }

    public function test_user_cannot_open_another_users_todo_edit_page(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $todo = Todo::query()->create([
            'user_id' => $owner->id,
            'title' => 'Private todo',
            'completed' => false,
        ]);

        $this->actingAs($otherUser)
            ->get(route('todos.edit', $todo))
            ->assertForbidden();
    }

    public function test_owner_can_update_a_todo_with_json(): void
    {
        $user = User::factory()->create();
        $csrfToken = 'valid-csrf-token';
        $todo = Todo::query()->create([
            'user_id' => $user->id,
            'title' => 'Old title',
            'completed' => false,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['_token' => $csrfToken])
            ->patchJson(route('todos.update', $todo), [
                '_token' => $csrfToken,
                'title' => 'Updated title',
                'completed' => true,
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $todo->id)
            ->assertJsonPath('data.title', 'Updated title')
            ->assertJsonPath('data.completed', true);

        $this->assertDatabaseHas('todos', [
            'id' => $todo->id,
            'user_id' => $user->id,
            'title' => 'Updated title',
            'completed' => true,
        ]);
    }

    public function test_update_returns_validation_errors_for_invalid_payload(): void
    {
        $user = User::factory()->create();
        $csrfToken = 'valid-csrf-token';
        $todo = Todo::query()->create([
            'user_id' => $user->id,
            'title' => 'Keep this title',
            'completed' => false,
        ]);

        $this->actingAs($user)
            ->withSession(['_token' => $csrfToken])
            ->patchJson(route('todos.update', $todo), [
                '_token' => $csrfToken,
                'title' => '',
                'completed' => 'not-a-boolean',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'completed']);

        $this->assertDatabaseHas('todos', [
            'id' => $todo->id,
            'title' => 'Keep this title',
            'completed' => false,
        ]);
    }

    public function test_user_cannot_update_another_users_todo(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $csrfToken = 'valid-csrf-token';
        $todo = Todo::query()->create([
            'user_id' => $owner->id,
            'title' => 'Private todo',
            'completed' => false,
        ]);

        $this->actingAs($otherUser)
            ->withSession(['_token' => $csrfToken])
            ->patchJson(route('todos.update', $todo), [
                '_token' => $csrfToken,
                'title' => 'Changed by another user',
                'completed' => true,
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('todos', [
            'id' => $todo->id,
            'title' => 'Private todo',
            'completed' => false,
        ]);
    }
}

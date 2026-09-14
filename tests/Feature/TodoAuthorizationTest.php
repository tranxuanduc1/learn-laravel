<?php

namespace Tests\Feature;

use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TodoAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_two_users_cannot_read_or_update_each_others_todos(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $csrfToken = 'valid-csrf-token';
        $todoA = $this->createTodo($userA, 'User A private todo');
        $todoB = $this->createTodo($userB, 'User B private todo');

        $this->actingAs($userA)
            ->get(route('todos.edit', $todoB))
            ->assertForbidden();
        $this->actingAs($userB)
            ->get(route('todos.edit', $todoA))
            ->assertForbidden();

        $this->actingAs($userA)
            ->withSession(['_token' => $csrfToken])
            ->patchJson(route('todos.update', $todoB), $this->updatePayload('Changed by user A', $csrfToken))
            ->assertForbidden();
        $this->actingAs($userB)
            ->withSession(['_token' => $csrfToken])
            ->patchJson(route('todos.update', $todoA), $this->updatePayload('Changed by user B', $csrfToken))
            ->assertForbidden();

        $this->assertDatabaseHas('todos', [
            'id' => $todoA->id,
            'title' => 'User A private todo',
            'completed' => false,
        ]);
        $this->assertDatabaseHas('todos', [
            'id' => $todoB->id,
            'title' => 'User B private todo',
            'completed' => false,
        ]);
    }

    public function test_web_ajax_and_api_lists_only_return_the_authenticated_users_todos(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $this->createTodo($userA, 'Visible to user A');
        $this->createTodo($userB, 'Hidden from user A');

        $this->actingAs($userA)
            ->getJson(route('todos.data'))
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Visible to user A')
            ->assertJsonMissing(['title' => 'Hidden from user A']);

        Sanctum::actingAs($userA);

        $this->getJson('/api/todos')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Visible to user A')
            ->assertJsonMissing(['title' => 'Hidden from user A']);

        $this->postJson('/api/todos', [
            'title' => 'Created by user A through API',
            'user_id' => $userB->id,
        ])->assertCreated();

        $this->assertDatabaseHas('todos', [
            'user_id' => $userA->id,
            'title' => 'Created by user A through API',
        ]);
        $this->assertDatabaseMissing('todos', [
            'user_id' => $userB->id,
            'title' => 'Created by user A through API',
        ]);
    }

    public function test_todo_policy_denies_deleting_another_users_todo(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $todo = $this->createTodo($owner, 'Owner private todo');

        $this->assertTrue($owner->can('delete', $todo));
        $this->assertFalse($otherUser->can('delete', $todo));
    }

    private function createTodo(User $user, string $title): Todo
    {
        return Todo::query()->create([
            'user_id' => $user->id,
            'title' => $title,
            'completed' => false,
        ]);
    }

    /** @return array{_token: string, title: string, completed: bool} */
    private function updatePayload(string $title, string $csrfToken): array
    {
        return [
            '_token' => $csrfToken,
            'title' => $title,
            'completed' => true,
        ];
    }
}

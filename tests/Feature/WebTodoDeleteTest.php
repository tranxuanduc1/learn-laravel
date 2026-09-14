<?php

namespace Tests\Feature;

use App\Contracts\TodoServiceInterface;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class WebTodoDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_delete_a_todo_and_receives_204(): void
    {
        $user = User::factory()->create();
        $todo = $this->createTodo($user, 'Delete me');
        $csrfToken = 'valid-csrf-token';

        $this->actingAs($user)
            ->withSession(['_token' => $csrfToken])
            ->deleteJson(route('todos.destroy', $todo), ['_token' => $csrfToken])
            ->assertNoContent();

        $this->assertModelMissing($todo);
    }

    public function test_guest_receives_401_and_cannot_delete_a_todo(): void
    {
        $todo = $this->createTodo(User::factory()->create(), 'Keep me');
        $csrfToken = 'valid-csrf-token';

        $this->withSession(['_token' => $csrfToken])
            ->deleteJson(route('todos.destroy', $todo), ['_token' => $csrfToken])
            ->assertUnauthorized();

        $this->assertModelExists($todo);
    }

    public function test_user_receives_403_when_deleting_another_users_todo(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $todo = $this->createTodo($owner, 'Private todo');
        $csrfToken = 'valid-csrf-token';

        $this->actingAs($otherUser)
            ->withSession(['_token' => $csrfToken])
            ->deleteJson(route('todos.destroy', $todo), ['_token' => $csrfToken])
            ->assertForbidden();

        $this->assertModelExists($todo);
    }

    public function test_missing_todo_returns_404(): void
    {
        $user = User::factory()->create();
        $csrfToken = 'valid-csrf-token';

        $this->actingAs($user)
            ->withSession(['_token' => $csrfToken])
            ->deleteJson(route('todos.destroy', 999999), ['_token' => $csrfToken])
            ->assertNotFound();
    }

    public function test_request_without_csrf_token_returns_419_and_keeps_the_todo(): void
    {
        $user = User::factory()->create();
        $todo = $this->createTodo($user, 'Keep me');

        $this->actingAs($user)
            ->withMiddleware(ValidateCsrfToken::class)
            ->deleteJson(route('todos.destroy', $todo))
            ->assertStatus(419);

        $this->assertModelExists($todo);
    }

    public function test_unexpected_delete_failure_returns_500_and_keeps_the_todo(): void
    {
        $user = User::factory()->create();
        $todo = $this->createTodo($user, 'Keep me');
        $csrfToken = 'valid-csrf-token';
        $this->mock(TodoServiceInterface::class)
            ->shouldReceive('delete')
            ->once()
            ->withArgs(fn (Todo $requestedTodo): bool => $requestedTodo->is($todo))
            ->andThrow(new RuntimeException('Database unavailable'));

        $this->actingAs($user)
            ->withSession(['_token' => $csrfToken])
            ->deleteJson(route('todos.destroy', $todo), ['_token' => $csrfToken])
            ->assertInternalServerError();

        $this->assertModelExists($todo);
    }

    private function createTodo(User $user, string $title): Todo
    {
        return Todo::query()->create([
            'user_id' => $user->id,
            'title' => $title,
            'completed' => false,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Contracts\TodoServiceInterface;
use App\Http\Requests\StoreTodoRequest;
use App\Http\Requests\UpdateTodoRequest;
use App\Http\Resources\TodoResource;
use App\Models\Todo;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class WebTodoController extends Controller
{
    public function __construct(
        private readonly TodoServiceInterface $todoService,
    ) {}

    public function index(): View
    {
        Gate::authorize('viewAny', Todo::class);

        return view('todos.index');
    }

    public function data(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Todo::class);

        return TodoResource::collection(
            $this->todoService->list($request->user()),
        );
    }

    public function store(StoreTodoRequest $request): TodoResource
    {
        $todo = $this->todoService->create(
            $request->user(),
            $request->validated(),
        );

        return new TodoResource($todo);
    }

    public function edit(Todo $todo): View
    {
        Gate::authorize('view', $todo);

        return view('todos.edit', compact('todo'));
    }

    public function update(UpdateTodoRequest $request, Todo $todo): TodoResource
    {
        return new TodoResource(
            $this->todoService->update($todo, $request->validated()),
        );
    }

    public function destroy(Todo $todo): Response
    {
        Gate::authorize('delete', $todo);

        $this->todoService->delete($todo);

        return response()->noContent();
    }
}

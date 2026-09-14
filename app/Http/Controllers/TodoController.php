<?php

namespace App\Http\Controllers;

use App\Contracts\TodoServiceInterface;
use App\Http\Requests\StoreTodoRequest;
use App\Http\Resources\TodoResource;
use App\Models\Todo;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class TodoController extends Controller
{
    public function __construct(
        private readonly TodoServiceInterface $todoService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Todo::class);

        $todos = $this->todoService->list(
            $request->user(),
        );

        return TodoResource::collection($todos);
    }

    public function store(StoreTodoRequest $request): TodoResource
    {
        $todo = $this->todoService->create(
            $request->user(),
            $request->validated(),
        );

        return new TodoResource($todo);
    }
}

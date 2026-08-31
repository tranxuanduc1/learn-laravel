<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreTodoRequest;
use App\Http\Resources\TodoResource;
use App\Services\TodoService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
class TodoController extends Controller
{
    public function __construct(
        private readonly TodoService $todoService,
    ) {
    }
    public function index(Request $request): AnonymousResourceCollection
    {
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

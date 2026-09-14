<?php

namespace App\Http\Controllers;

use App\Contracts\TodoServiceInterface;
use Illuminate\Http\Request;
use App\Http\Requests\StoreTodoRequest;
use App\Http\Resources\TodoResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
class TodoController extends Controller
{
    public function __construct(
        private readonly TodoServiceInterface $todoService,
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

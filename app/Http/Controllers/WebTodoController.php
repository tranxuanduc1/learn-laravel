<?php

namespace App\Http\Controllers;

use App\Contracts\TodoServiceInterface;
use App\Http\Resources\TodoResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\View\View;

class WebTodoController extends Controller
{
    public function __construct(
        private readonly TodoServiceInterface $todoService,
    ) {}

    public function index(): View
    {
        return view('todos.index');
    }

    public function data(Request $request): AnonymousResourceCollection
    {
        return TodoResource::collection(
            $this->todoService->list($request->user()),
        );
    }
}

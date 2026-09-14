<?php

namespace App\Http\Controllers;

use App\Contracts\TodoServiceInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WebTodoController extends Controller
{
    public function __construct(
        private readonly TodoServiceInterface $todoService,
    ) {}

    public function index(Request $request): View
    {
        return view('todos.index', [
            'todos' => $this->todoService->list($request->user()),
        ]);
    }
}

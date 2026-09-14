<?php

use App\Http\Controllers\WebAuthController;
use App\Http\Controllers\WebTodoController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [WebAuthController::class, 'create'])->name('login');
    Route::post('/login', [WebAuthController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::view('/dashboard', 'dashboard')->name('dashboard');
    Route::get('/todos', [WebTodoController::class, 'index'])->name('todos.index');
    Route::get('/todos/data', [WebTodoController::class, 'data'])->name('todos.data');
    Route::post('/todos', [WebTodoController::class, 'store'])->name('todos.store');
    Route::get('/todos/{todo}/edit', [WebTodoController::class, 'edit'])->name('todos.edit');
    Route::patch('/todos/{todo}', [WebTodoController::class, 'update'])->name('todos.update');
    Route::delete('/todos/{todo}', [WebTodoController::class, 'destroy'])->name('todos.destroy');
    Route::post('/logout', [WebAuthController::class, 'destroy'])->name('logout');
});

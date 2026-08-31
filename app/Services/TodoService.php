<?php

namespace App\Services;

use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class TodoService
{
    public function list(User $user): Collection
    {
        return Todo::query()
            ->where('user_id', $user->id)
            ->latest()
            ->get();
    }

    public function create(User $user, array $data): Todo
    {
        return Todo::create([
            'user_id' => $user->id,
            'title' => $data['title'],
            'completed' => false,
        ]);
    }
}
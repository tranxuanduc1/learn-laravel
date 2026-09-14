<?php

namespace App\Contracts;

use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface TodoServiceInterface
{
    public function list(User $user): Collection;

    public function create(User $user, array $data): Todo;

    public function update(Todo $todo, array $data): Todo;

    public function delete(Todo $todo): void;
}

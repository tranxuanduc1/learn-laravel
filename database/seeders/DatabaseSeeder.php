<?php

namespace Database\Seeders;

use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            [
                'email' => env('DEFAULT_USER_EMAIL', 'admin@example.com'),
            ],
            [
                'name' => env('DEFAULT_USER_NAME', 'Default Admin'),
                'password' => env('DEFAULT_USER_PASSWORD', 'change-me'),
                'email_verified_at' => now(),
            ],
        );

        foreach (range(1, 10) as $number) {
            Todo::query()->updateOrCreate(
                [
                    'user_id' => $admin->id,
                    'title' => "Todo {$number}",
                ],
                [
                    'completed' => false,
                ],
            );
        }
    }
}

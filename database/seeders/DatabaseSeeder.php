<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed demo data: `php artisan migrate:fresh --seed`.
     * Log in as test@example.com with the password "password" (see UserFactory).
     */
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        Task::factory()->count(20)->for($user)->create();
        Task::factory()->count(3)->overdue()->for($user)->create();
    }
}

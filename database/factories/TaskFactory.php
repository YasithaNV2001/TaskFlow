<?php

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Generates fake tasks for tests and demo data: Task::factory()->count(10)->create().
 *
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => rtrim(fake()->sentence(4), '.'),
            'description' => fake()->optional()->paragraph(),
            'status' => fake()->randomElement(TaskStatus::cases()),
            'priority' => fake()->randomElement(TaskPriority::cases()),
            'due_date' => fake()->optional(0.8)->dateTimeBetween('-1 week', '+3 weeks'),
        ];
    }

    /**
     * State: a task that is past its due date and not finished.
     */
    public function overdue(): static
    {
        return $this->state(fn () => [
            'status' => TaskStatus::Todo,
            'due_date' => now()->subDays(3),
        ]);
    }

    public function done(): static
    {
        return $this->state(fn () => ['status' => TaskStatus::Done]);
    }
}

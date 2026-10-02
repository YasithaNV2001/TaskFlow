<?php

use App\Models\Task;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('shows task statistics for the logged-in user only', function () {
    $user = User::factory()->create();
    Task::factory()->count(2)->for($user)->create(['status' => 'todo', 'due_date' => now()->addWeek()]);
    Task::factory()->for($user)->create(['status' => 'in_progress', 'due_date' => null]);
    Task::factory()->count(3)->done()->for($user)->create();
    Task::factory()->overdue()->for($user)->create();
    Task::factory()->count(4)->create(); // other users' tasks must not be counted

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('stats.total', 7)
            ->where('stats.todo', 3)
            ->where('stats.in_progress', 1)
            ->where('stats.done', 3)
            ->where('stats.overdue', 1)
            // Unfinished tasks with a due date, soonest first: the overdue one, then the two due next week
            ->has('upcoming', 3)
            ->where('upcoming.0.status', 'todo')
        );
});

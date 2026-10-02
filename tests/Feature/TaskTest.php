<?php

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('access', function () {
    it('redirects guests to the login page', function () {
        $this->get(route('tasks.index'))->assertRedirect(route('login'));
        $this->post(route('tasks.store'), ['title' => 'x'])->assertRedirect(route('login'));
    });
});

describe('listing', function () {
    it('shows only the logged-in user\'s tasks', function () {
        Task::factory()->count(2)->for($this->user)->create();
        Task::factory()->count(3)->create(); // belong to other users

        $this->actingAs($this->user)
            ->get(route('tasks.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('tasks/Index')
                ->has('tasks.data', 2)
                ->where('tasks.total', 2)
                ->has('statuses', 3)
                ->has('priorities', 3)
            );
    });

    it('searches by title and description', function () {
        Task::factory()->for($this->user)->create(['title' => 'Write the report']);
        Task::factory()->for($this->user)->create(['title' => 'Gym', 'description' => 'leg day and report back']);
        Task::factory()->for($this->user)->create(['title' => 'Buy groceries', 'description' => null]);

        $this->actingAs($this->user)
            ->get(route('tasks.index', ['search' => 'report']))
            ->assertInertia(fn (Assert $page) => $page->has('tasks.data', 2));
    });

    it('filters by status and priority', function () {
        Task::factory()->for($this->user)->create(['status' => 'done', 'priority' => 'high']);
        Task::factory()->for($this->user)->create(['status' => 'done', 'priority' => 'low']);
        Task::factory()->for($this->user)->create(['status' => 'todo', 'priority' => 'high']);

        $this->actingAs($this->user)
            ->get(route('tasks.index', ['status' => 'done', 'priority' => 'high']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('tasks.data', 1)
                ->where('filters.status', 'done')
                ->where('filters.priority', 'high')
            );
    });

    it('paginates ten tasks per page', function () {
        Task::factory()->count(15)->for($this->user)->create();

        $this->actingAs($this->user)
            ->get(route('tasks.index', ['page' => 2]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('tasks.data', 5)
                ->where('tasks.current_page', 2)
                ->where('tasks.last_page', 2)
            );
    });

    it('rejects invalid filter values', function () {
        $this->actingAs($this->user)
            ->get(route('tasks.index', ['status' => 'not-a-status']))
            ->assertSessionHasErrors('status');
    });
});

describe('creating', function () {
    it('creates a task for the logged-in user', function () {
        $this->actingAs($this->user)
            ->post(route('tasks.store'), [
                'title' => 'Finish database assignment',
                'description' => 'Chapters 3 and 4',
                'status' => 'in_progress',
                'priority' => 'high',
                'due_date' => '2026-12-01',
            ])
            ->assertRedirect(route('tasks.index'));

        $task = $this->user->tasks()->sole();
        expect($task->title)->toBe('Finish database assignment')
            ->and($task->status)->toBe(TaskStatus::InProgress)
            ->and($task->due_date?->toDateString())->toBe('2026-12-01');
    });

    it('validates the input', function (array $data, string $field) {
        $valid = ['title' => 'A task', 'status' => 'todo', 'priority' => 'medium'];

        $this->actingAs($this->user)
            ->post(route('tasks.store'), array_merge($valid, $data))
            ->assertSessionHasErrors($field);

        expect(Task::count())->toBe(0);
    })->with([
        'missing title' => [['title' => ''], 'title'],
        'title too long' => [['title' => str_repeat('a', 256)], 'title'],
        'unknown status' => [['status' => 'finished'], 'status'],
        'unknown priority' => [['priority' => 'urgent'], 'priority'],
        'invalid date' => [['due_date' => 'tomorrow-ish'], 'due_date'],
    ]);

    it('ignores attempts to set another user as the owner', function () {
        $other = User::factory()->create();

        $this->actingAs($this->user)->post(route('tasks.store'), [
            'title' => 'Sneaky',
            'status' => 'todo',
            'priority' => 'low',
            'user_id' => $other->id,
        ]);

        expect(Task::sole()->user_id)->toBe($this->user->id);
    });
});

describe('updating and deleting', function () {
    it('updates the user\'s own task', function () {
        $task = Task::factory()->for($this->user)->create(['title' => 'Old']);

        $this->actingAs($this->user)
            ->get(route('tasks.edit', $task))
            ->assertInertia(fn (Assert $page) => $page
                ->component('tasks/Edit')
                ->where('task.id', $task->id)
            );

        $this->actingAs($this->user)
            ->put(route('tasks.update', $task), [
                'title' => 'New',
                'status' => 'done',
                'priority' => 'low',
            ])
            ->assertRedirect(route('tasks.index'));

        expect($task->fresh()->title)->toBe('New');
    });

    it('toggles a task between done and to do', function () {
        $task = Task::factory()->for($this->user)->create(['status' => 'todo']);

        $this->actingAs($this->user)->patch(route('tasks.toggle', $task));
        expect($task->fresh()->status)->toBe(TaskStatus::Done);

        $this->actingAs($this->user)->patch(route('tasks.toggle', $task));
        expect($task->fresh()->status)->toBe(TaskStatus::Todo);
    });

    it('deletes the user\'s own task', function () {
        $task = Task::factory()->for($this->user)->create();

        $this->actingAs($this->user)->delete(route('tasks.destroy', $task));

        $this->assertModelMissing($task);
    });

    it('forbids touching another user\'s task', function () {
        $task = Task::factory()->create(['title' => 'Not yours']);
        $valid = ['title' => 'Hacked', 'status' => 'todo', 'priority' => 'low'];

        $this->actingAs($this->user)->get(route('tasks.edit', $task))->assertForbidden();
        $this->actingAs($this->user)->put(route('tasks.update', $task), $valid)->assertForbidden();
        $this->actingAs($this->user)->patch(route('tasks.toggle', $task))->assertForbidden();
        $this->actingAs($this->user)->delete(route('tasks.destroy', $task))->assertForbidden();

        expect($task->fresh()->title)->toBe('Not yours');
    });
});

describe('model', function () {
    it('knows when a task is overdue', function () {
        expect(Task::factory()->overdue()->make()->isOverdue())->toBeTrue()
            ->and(Task::factory()->overdue()->done()->make()->isOverdue())->toBeFalse()
            ->and(Task::factory()->make(['due_date' => null])->isOverdue())->toBeFalse()
            ->and(Task::factory()->make(['status' => 'todo', 'due_date' => now()->addDay()])->isOverdue())->toBeFalse();
    });
});

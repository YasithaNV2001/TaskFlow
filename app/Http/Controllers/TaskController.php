<?php

namespace App\Http\Controllers;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Resource controller for tasks. Each method maps to one route
 * (see `php artisan route:list --name=tasks`).
 */
class TaskController extends Controller
{
    /**
     * GET /tasks: the logged-in user's tasks, with search, filters and pagination.
     */
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(TaskStatus::class)],
            'priority' => ['nullable', Rule::enum(TaskPriority::class)],
        ]);

        // Starting from $request->user()->tasks() guarantees users only ever see their own tasks
        $tasks = $request->user()->tasks()
            ->search($filters['search'] ?? null)
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['priority'] ?? null, fn ($query, $priority) => $query->where('priority', $priority))
            ->orderByRaw('due_date IS NULL') // tasks with a due date first…
            ->orderBy('due_date')            // …soonest first
            ->latest()
            ->paginate(10)
            ->withQueryString();             // keep ?search=…&status=… on pagination links

        return Inertia::render('tasks/Index', [
            'tasks' => $tasks,
            'filters' => [
                'search' => $filters['search'] ?? '',
                'status' => $filters['status'] ?? '',
                'priority' => $filters['priority'] ?? '',
            ],
            'statuses' => TaskStatus::options(),
            'priorities' => TaskPriority::options(),
        ]);
    }

    /**
     * GET /tasks/create: the empty form.
     */
    public function create(): Response
    {
        return Inertia::render('tasks/Create', [
            'statuses' => TaskStatus::options(),
            'priorities' => TaskPriority::options(),
        ]);
    }

    /**
     * POST /tasks: StoreTaskRequest has already validated the input.
     */
    public function store(StoreTaskRequest $request): RedirectResponse
    {
        $request->user()->tasks()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Task created.')]);

        return to_route('tasks.index');
    }

    /**
     * GET /tasks/{task}/edit: route model binding turns {task} into a Task model.
     */
    public function edit(Task $task): Response
    {
        Gate::authorize('update', $task);

        return Inertia::render('tasks/Edit', [
            'task' => $task,
            'statuses' => TaskStatus::options(),
            'priorities' => TaskPriority::options(),
        ]);
    }

    /**
     * PUT /tasks/{task}: UpdateTaskRequest validates and checks ownership.
     */
    public function update(UpdateTaskRequest $request, Task $task): RedirectResponse
    {
        $task->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Task updated.')]);

        return to_route('tasks.index');
    }

    /**
     * PATCH /tasks/{task}/toggle: mark done, or reopen a finished task.
     */
    public function toggle(Task $task): RedirectResponse
    {
        Gate::authorize('update', $task);

        $task->update([
            'status' => $task->status === TaskStatus::Done ? TaskStatus::Todo : TaskStatus::Done,
        ]);

        return back();
    }

    /**
     * DELETE /tasks/{task}
     */
    public function destroy(Task $task): RedirectResponse
    {
        Gate::authorize('delete', $task);

        $task->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Task deleted.')]);

        return back();
    }
}

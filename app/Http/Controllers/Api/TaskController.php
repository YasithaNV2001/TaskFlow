<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FilterTasksRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/**
 * REST API for tasks: JSON in, JSON out, authenticated with a Sanctum token.
 * Reuses the same form requests, policy and query scopes as the web controller.
 */
class TaskController extends Controller
{
    /**
     * GET /api/tasks
     */
    public function index(FilterTasksRequest $request): AnonymousResourceCollection
    {
        $tasks = $request->user()->tasks()
            ->filter($request->filters())
            ->orderByDeadline()
            ->paginate($request->perPage())
            ->withQueryString();

        // Wraps the page in { data: [...], links: {...}, meta: {...} }
        return TaskResource::collection($tasks);
    }

    /**
     * POST /api/tasks → 201 Created
     */
    public function store(StoreTaskRequest $request): TaskResource
    {
        $task = $request->user()->tasks()->create($request->validated());

        return new TaskResource($task->refresh());
    }

    /**
     * GET /api/tasks/{task}
     */
    public function show(Task $task): TaskResource
    {
        Gate::authorize('view', $task);

        return new TaskResource($task);
    }

    /**
     * PUT/PATCH /api/tasks/{task}
     */
    public function update(UpdateTaskRequest $request, Task $task): TaskResource
    {
        $task->update($request->validated());

        return new TaskResource($task);
    }

    /**
     * DELETE /api/tasks/{task} → 204 No Content
     */
    public function destroy(Task $task): Response
    {
        Gate::authorize('delete', $task);

        $task->delete();

        return response()->noContent();
    }
}

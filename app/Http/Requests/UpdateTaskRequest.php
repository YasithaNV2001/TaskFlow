<?php

namespace App\Http\Requests;

use App\Models\Task;

/**
 * Same rules as creating a task, but only the task's owner may update it.
 */
class UpdateTaskRequest extends StoreTaskRequest
{
    public function authorize(): bool
    {
        $task = $this->route('task');

        // Delegates to TaskPolicy::update()
        return $task instanceof Task && ($this->user()?->can('update', $task) ?? false);
    }
}

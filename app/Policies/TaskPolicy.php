<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

/**
 * Authorization rules for tasks: users may only touch their own tasks.
 * Laravel finds this policy automatically because of its name (Task → TaskPolicy).
 */
class TaskPolicy
{
    public function view(User $user, Task $task): bool
    {
        return $this->owns($user, $task);
    }

    public function update(User $user, Task $task): bool
    {
        return $this->owns($user, $task);
    }

    public function delete(User $user, Task $task): bool
    {
        return $this->owns($user, $task);
    }

    private function owns(User $user, Task $task): bool
    {
        return $user->id === $task->user_id;
    }
}

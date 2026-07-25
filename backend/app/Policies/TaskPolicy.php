<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->status;
    }

    public function view(User $user, Task $task): bool
    {
        return $this->canManageAllTasks($user) || $task->created_by === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->status;
    }

    public function update(User $user, Task $task): bool
    {
        return $this->canManageAllTasks($user) || $task->created_by === $user->id;
    }

    public function delete(User $user, Task $task): bool
    {
        return $this->canManageAllTasks($user) || $task->created_by === $user->id;
    }

    private function canManageAllTasks(User $user): bool
    {
        return $user->role()->whereIn('name', ['Admin', 'Manager'])->exists();
    }
}

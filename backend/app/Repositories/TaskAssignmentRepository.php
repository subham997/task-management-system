<?php

namespace App\Repositories;

use App\Models\Task;
use App\Models\TaskAssignment;

class TaskAssignmentRepository
{
    public function findTask(int $taskId): ?Task
    {
        return Task::query()->find($taskId);
    }

    public function findTaskForUpdate(int $taskId): ?Task
    {
        return Task::query()->lockForUpdate()->find($taskId);
    }

    public function existsForTask(Task $task): bool
    {
        return $task->assignments()->exists();
    }

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): TaskAssignment
    {
        return TaskAssignment::query()->create($attributes);
    }
}

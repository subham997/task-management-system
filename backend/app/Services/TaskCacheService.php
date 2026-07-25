<?php

namespace App\Services;

use App\Models\Task;
use Illuminate\Support\Facades\Cache;

class TaskCacheService
{
    /** @param callable(): Task $resolver */
    public function task(int $taskId, callable $resolver): Task
    {
        return Cache::remember(
            $this->taskKey($taskId),
            config('task-cache.ttl.task_details'),
            $resolver
        );
    }

    public function forgetTask(int $taskId): void
    {
        Cache::forget($this->taskKey($taskId));
    }

    public function taskKey(int $taskId): string
    {
        return "tasks:detail:{$taskId}";
    }
}

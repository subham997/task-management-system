<?php

namespace App\Observers;

use App\Models\Task;
use App\Services\TaskCacheService;

class TaskObserver
{
    public function saved(Task $task): void
    {
        $cache = app(TaskCacheService::class);
        $cache->forgetTask($task->id);
        $cache->forgetEligibleUsers($task->id);
    }

    public function deleted(Task $task): void
    {
        $cache = app(TaskCacheService::class);
        $cache->forgetTask($task->id);
        $cache->forgetEligibleUsers($task->id);
    }
}

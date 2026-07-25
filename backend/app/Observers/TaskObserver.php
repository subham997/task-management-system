<?php

namespace App\Observers;

use App\Models\Task;
use App\Services\TaskCacheService;

class TaskObserver
{
    public function saved(Task $task): void
    {
        app(TaskCacheService::class)->forgetTask($task->id);
    }

    public function deleted(Task $task): void
    {
        app(TaskCacheService::class)->forgetTask($task->id);
    }
}

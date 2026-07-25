<?php

namespace App\Services;

use App\Jobs\AssignTaskJob;
use App\Models\Task;
use App\Models\User;
use App\Repositories\TaskRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TaskService
{
    public function __construct(
        private readonly TaskRepository $tasks,
        private readonly TaskCacheService $cache
    ) {}

    /** @param array<string, mixed> $filters */
    public function list(User $user, array $filters): LengthAwarePaginator
    {
        return $this->tasks->paginateFor($user, $filters);
    }

    /** @param array<string, mixed> $attributes */
    public function create(User $user, array $attributes): Task
    {
        return DB::transaction(function () use ($user, $attributes): Task {
            $task = $this->tasks->create($user, $this->withCompletionTimestamp($attributes));

            AssignTaskJob::dispatch($task->id)->afterCommit();
            Log::info('task.created', [
                'task_id' => $task->id,
                'user_id' => $user->id,
            ]);

            return $task;
        });
    }

    public function details(int $taskId): Task
    {
        return $this->cache->task(
            $taskId,
            fn (): Task => $this->tasks->findWithCreator($taskId)
        );
    }

    /** @param array<string, mixed> $attributes */
    public function update(User $user, Task $task, array $attributes): Task
    {
        return DB::transaction(function () use ($user, $task, $attributes): Task {
            $task = $this->tasks->update($task, $this->withCompletionTimestamp($attributes, $task));
            Log::info('task.updated', [
                'task_id' => $task->id,
                'user_id' => $user->id,
            ]);

            return $task;
        });
    }

    public function delete(User $user, Task $task): void
    {
        DB::transaction(function () use ($user, $task): void {
            $taskId = $task->id;
            $this->tasks->delete($task);
            Log::info('task.deleted', [
                'task_id' => $taskId,
                'user_id' => $user->id,
            ]);
        });
    }

    /** @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    private function withCompletionTimestamp(array $attributes, ?Task $task = null): array
    {
        if (($attributes['status'] ?? $task?->status) === 'completed') {
            $attributes['completed_at'] ??= now();
        } elseif (array_key_exists('status', $attributes)) {
            $attributes['completed_at'] = null;
        }

        return $attributes;
    }
}

<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;
use App\Repositories\TaskRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TaskService
{
    public function __construct(private readonly TaskRepository $tasks) {}

    /** @param array<string, mixed> $filters */
    public function list(User $user, array $filters): LengthAwarePaginator
    {
        return $this->tasks->paginateFor($user, $filters);
    }

    /** @param array<string, mixed> $attributes */
    public function create(User $user, array $attributes): Task
    {
        return $this->tasks->create($user, $this->withCompletionTimestamp($attributes));
    }

    /** @param array<string, mixed> $attributes */
    public function update(Task $task, array $attributes): Task
    {
        return $this->tasks->update($task, $this->withCompletionTimestamp($attributes, $task));
    }

    public function delete(Task $task): void
    {
        $this->tasks->delete($task);
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

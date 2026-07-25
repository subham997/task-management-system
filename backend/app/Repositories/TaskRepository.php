<?php

namespace App\Repositories;

use App\Models\Task;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class TaskRepository
{
    /** @param array<string, mixed> $filters */
    public function paginateFor(User $user, array $filters): LengthAwarePaginator
    {
        $query = Task::query()->with('creator');

        if (! $this->canManageAllTasks($user)) {
            $query->where('created_by', $user->id);
        }

        $this->applyFilters($query, $filters);

        return $query->paginate($filters['per_page'] ?? 15)->withQueryString();
    }

    /** @param array<string, mixed> $attributes */
    public function create(User $user, array $attributes): Task
    {
        return Task::query()->create([
            ...$attributes,
            'created_by' => $user->id,
        ])->load('creator');
    }

    /** @param array<string, mixed> $attributes */
    public function update(Task $task, array $attributes): Task
    {
        $task->update($attributes);

        return $task->refresh()->load('creator');
    }

    public function delete(Task $task): void
    {
        $task->delete();
    }

    /** @param array<string, mixed> $filters */
    private function applyFilters(Builder $query, array $filters): void
    {
        $query
            ->when($filters['status'] ?? null, fn (Builder $query, string $status): Builder => $query->where('status', $status))
            ->when($filters['priority'] ?? null, fn (Builder $query, string $priority): Builder => $query->where('priority', $priority))
            ->when($filters['due_from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('due_at', '>=', $date))
            ->when($filters['due_to'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('due_at', '<=', $date))
            ->when($filters['search'] ?? null, function (Builder $query, string $search): Builder {
                return $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            });

        $query->orderBy($filters['sort_by'] ?? 'created_at', $filters['sort_direction'] ?? 'desc');
    }

    private function canManageAllTasks(User $user): bool
    {
        return $user->role()->whereIn('name', ['Admin', 'Manager'])->exists();
    }
}

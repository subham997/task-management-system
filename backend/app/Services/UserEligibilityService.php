<?php

namespace App\Services;

use App\Models\AssignmentRule;
use App\Models\Task;
use App\Models\User;
use App\Repositories\TaskAssignmentRepository;
use Illuminate\Database\Eloquent\Collection;

class UserEligibilityService
{
    public function __construct(
        private readonly RuleEvaluator $ruleEvaluator,
        private readonly TaskAssignmentRepository $assignments,
        private readonly AssignmentCacheService $cache
    ) {}

    /** @return Collection<int, User> */
    public function eligibleFor(AssignmentRule $rule, ?Task $task = null): Collection
    {
        $taskId = $task?->id ?? 0;

        return app(TaskCacheService::class)->eligibleUsers($taskId, $rule, function () use ($rule): Collection {
            return User::query()
                ->where('status', true)
                ->with('role')
                ->withMax('assignedTasks as last_assigned_at', 'assigned_at')
                ->get()
                ->map(function (User $user): User {
                    $profile = $this->cache->userProfile($user->id, fn (): User => $user);
                    $profile->setAttribute(
                        'active_task_count',
                        $this->assignments->activeTaskCountForUser($user->id)
                    );

                    return $profile;
                })
                ->filter(fn (User $user): bool => $this->ruleEvaluator->matches($user, $rule->conditions ?? []))
                ->values();
        });
    }
}

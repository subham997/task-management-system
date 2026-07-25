<?php

namespace App\Services;

use App\Models\AssignmentRule;
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
    public function eligibleFor(AssignmentRule $rule): Collection
    {
        return $this->cache->eligibleUsers($rule, function () use ($rule): Collection {
            return User::query()
                ->where('status', true)
                ->with('role')
                ->withMax('assignedTasks as last_assigned_at', 'assigned_at')
                ->get()
                ->map(function (User $user): User {
                    $user->setAttribute(
                        'active_task_count',
                        $this->assignments->activeTaskCountForUser($user->id)
                    );

                    return $user;
                })
                ->filter(fn (User $user): bool => $this->ruleEvaluator->matches($user, $rule->conditions ?? []))
                ->values();
        });
    }
}

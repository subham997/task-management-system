<?php

namespace App\Services;

use App\Models\AssignmentRule;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class UserEligibilityService
{
    public function __construct(private readonly RuleEvaluator $ruleEvaluator) {}

    /** @return Collection<int, User> */
    public function eligibleFor(AssignmentRule $rule): Collection
    {
        return User::query()
            ->where('status', true)
            ->with('role')
            ->withCount([
                'assignedTasks as active_task_count' => fn ($query) => $query->whereIn('status', ['assigned', 'accepted']),
            ])
            ->withMax('assignedTasks as last_assigned_at', 'assigned_at')
            ->get()
            ->filter(fn (User $user): bool => $this->ruleEvaluator->matches($user, $rule->conditions ?? []))
            ->values();
    }
}

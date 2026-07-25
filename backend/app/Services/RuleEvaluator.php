<?php

namespace App\Services;

use App\Models\User;

class RuleEvaluator
{
    /** @param array<string, mixed> $conditions */
    public function matches(User $user, array $conditions): bool
    {
        if (! $user->status) {
            return false;
        }

        return $this->matchesListCondition($user->id, $conditions['user_ids'] ?? null)
            && $this->matchesListCondition($user->role?->name, $conditions['roles'] ?? $conditions['role'] ?? null)
            && $this->matchesListCondition($user->department, $conditions['departments'] ?? $conditions['department'] ?? null)
            && $this->matchesListCondition($user->designation, $conditions['designations'] ?? $conditions['designation'] ?? null)
            && $this->matchesWorkload($user, $conditions['max_active_tasks'] ?? null);
    }

    private function matchesListCondition(int|string|null $value, mixed $allowedValues): bool
    {
        if ($allowedValues === null || $allowedValues === []) {
            return true;
        }

        return in_array($value, (array) $allowedValues, true);
    }

    private function matchesWorkload(User $user, mixed $maximum): bool
    {
        if ($maximum === null) {
            return true;
        }

        return (int) ($user->active_task_count ?? 0) < (int) $maximum;
    }
}

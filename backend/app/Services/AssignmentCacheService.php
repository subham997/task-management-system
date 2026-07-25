<?php

namespace App\Services;

use App\Models\AssignmentRule;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AssignmentCacheService
{
    /** @param callable(): Collection<int, AssignmentRule> $resolver
     * @return Collection<int, AssignmentRule>
     */
    public function activeRules(callable $resolver): Collection
    {
        return Cache::remember(
            $this->activeRulesKey(),
            config('cache.ttl.assignment_rules'),
            function () use ($resolver): Collection {
                Log::debug('cache.miss', ['cache_key' => $this->activeRulesKey()]);

                return $resolver();
            }
        );
    }

    /** @param callable(): AssignmentRule $resolver */
    public function assignmentRule(int $ruleId, callable $resolver): AssignmentRule
    {
        return Cache::remember(
            $this->assignmentRuleKey($ruleId),
            config('cache.ttl.assignment_rules'),
            function () use ($ruleId, $resolver): AssignmentRule {
                Log::debug('cache.miss', ['cache_key' => $this->assignmentRuleKey($ruleId)]);

                return $resolver();
            }
        );
    }

    /** @param callable(): User $resolver */
    public function userProfile(int $userId, callable $resolver): User
    {
        return Cache::remember(
            $this->userProfileKey($userId),
            config('cache.ttl.user_profile'),
            function () use ($userId, $resolver): User {
                Log::debug('cache.miss', ['cache_key' => $this->userProfileKey($userId)]);

                return $resolver();
            }
        );
    }

    /** @param callable(): int $resolver */
    public function activeTaskCount(int $userId, callable $resolver): int
    {
        return Cache::remember(
            $this->activeTaskCountKey($userId),
            config('cache.ttl.active_task_count'),
            function () use ($userId, $resolver): int {
                Log::debug('cache.miss', ['cache_key' => $this->activeTaskCountKey($userId)]);

                return $resolver();
            }
        );
    }

    public function forgetActiveRules(): void
    {
        Cache::forget($this->activeRulesKey());
    }

    public function forgetActiveTaskCount(int $userId): void
    {
        Cache::forget($this->activeTaskCountKey($userId));
    }

    public function forgetAssignmentRule(int $ruleId): void
    {
        Cache::forget($this->assignmentRuleKey($ruleId));
    }

    public function forgetUserProfile(int $userId): void
    {
        Cache::forget($this->userProfileKey($userId));
    }

    public function activeRulesKey(): string
    {
        return 'assignment:rules:active';
    }

    public function activeTaskCountKey(int $userId): string
    {
        return "user:{$userId}:active-task-count";
    }

    public function assignmentRuleKey(int $ruleId): string
    {
        return "assignment-rule:{$ruleId}";
    }

    public function userProfileKey(int $userId): string
    {
        return "user:{$userId}";
    }
}

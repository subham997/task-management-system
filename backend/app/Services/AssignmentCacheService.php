<?php

namespace App\Services;

use App\Models\AssignmentRule;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class AssignmentCacheService
{
    /** @param callable(): Collection<int, AssignmentRule> $resolver
     * @return Collection<int, AssignmentRule>
     */
    public function activeRules(callable $resolver): Collection
    {
        return Cache::remember(
            $this->activeRulesKey(),
            config('task-cache.ttl.assignment_rules'),
            $resolver
        );
    }

    /** @param callable(): Collection<int, User> $resolver
     * @return Collection<int, User>
     */
    public function eligibleUsers(AssignmentRule $rule, callable $resolver): Collection
    {
        return Cache::remember(
            $this->eligibleUsersKey($rule->id),
            config('task-cache.ttl.eligible_users'),
            $resolver
        );
    }

    /** @param callable(): int $resolver */
    public function activeTaskCount(int $userId, callable $resolver): int
    {
        return Cache::remember(
            $this->activeTaskCountKey($userId),
            config('task-cache.ttl.active_task_count'),
            $resolver
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

    public function invalidateEligibleUsers(): void
    {
        Cache::forever($this->eligibilityVersionKey(), (string) Str::uuid());
    }

    public function activeRulesKey(): string
    {
        return 'assignment:rules:active';
    }

    public function activeTaskCountKey(int $userId): string
    {
        return "assignment:active-task-count:{$userId}";
    }

    public function eligibleUsersKey(int $ruleId): string
    {
        return "assignment:eligible-users:{$ruleId}:{$this->eligibilityVersion()}";
    }

    private function eligibilityVersion(): string
    {
        return Cache::rememberForever($this->eligibilityVersionKey(), fn (): string => (string) Str::uuid());
    }

    private function eligibilityVersionKey(): string
    {
        return 'assignment:eligible-users:version';
    }
}

<?php

namespace App\Services;

use App\Models\AssignmentRule;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class TaskCacheService
{
    /** @param callable(): Task $resolver */
    public function task(int $taskId, callable $resolver): Task
    {
        return Cache::remember(
            $this->taskKey($taskId),
            config('cache.ttl.task_details'),
            $resolver
        );
    }

    public function forgetTask(int $taskId): void
    {
        Cache::forget($this->taskKey($taskId));
    }

    /** @param callable(): Collection<int, User> $resolver
     * @return Collection<int, User>
     */
    public function eligibleUsers(int $taskId, AssignmentRule $rule, callable $resolver): Collection
    {
        $key = $this->eligibleUsersKey($taskId);
        $cached = Cache::get($key, []);
        $version = $this->eligibilityVersion();

        if (($cached['version'] ?? null) === $version && isset($cached['rules'][$rule->id])) {
            return $cached['rules'][$rule->id];
        }

        $cached['version'] = $version;
        $cached['rules'][$rule->id] = $resolver();

        Cache::put($key, $cached, config('cache.ttl.eligible_users'));

        return $cached['rules'][$rule->id];
    }

    public function forgetEligibleUsers(int $taskId): void
    {
        Cache::forget($this->eligibleUsersKey($taskId));
    }

    public function invalidateAllEligibleUsers(): void
    {
        Cache::forever($this->eligibilityVersionKey(), (string) Str::uuid());
    }

    public function taskKey(int $taskId): string
    {
        return "task:{$taskId}";
    }

    public function eligibleUsersKey(int $taskId): string
    {
        return "task:{$taskId}:eligible-users";
    }

    private function eligibilityVersion(): string
    {
        return Cache::rememberForever($this->eligibilityVersionKey(), fn (): string => (string) Str::uuid());
    }

    private function eligibilityVersionKey(): string
    {
        return 'eligible-users:version';
    }
}

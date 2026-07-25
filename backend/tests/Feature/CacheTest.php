<?php

namespace Tests\Feature;

use App\Models\AssignmentRule;
use App\Models\Role;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\User;
use App\Repositories\AssignmentRuleRepository;
use App\Repositories\TaskAssignmentRepository;
use App\Services\AssignmentCacheService;
use App\Services\TaskCacheService;
use App\Services\TaskService;
use App\Services\UserEligibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Role::query()->create(['name' => 'Employee']);
    }

    public function test_task_details_are_cached_and_invalidated_when_a_task_changes(): void
    {
        $task = Task::factory()->create();
        $cache = app(TaskCacheService::class);

        $details = app(TaskService::class)->details($task->id);

        $this->assertSame($task->id, $details->id);
        $this->assertTrue(Cache::has($cache->taskKey($task->id)));

        $task->update(['title' => 'Updated cached task']);

        $this->assertFalse(Cache::has($cache->taskKey($task->id)));
        $this->assertSame('Updated cached task', app(TaskService::class)->details($task->id)->title);
    }

    public function test_assignment_rules_are_cached_and_invalidated_when_a_rule_changes(): void
    {
        $rule = AssignmentRule::factory()->create();
        $cache = app(AssignmentCacheService::class);
        $repository = app(AssignmentRuleRepository::class);

        $this->assertCount(1, $repository->active());
        $this->assertTrue(Cache::has($cache->activeRulesKey()));

        $rule->update(['is_active' => false]);

        $this->assertFalse(Cache::has($cache->activeRulesKey()));
        $this->assertCount(0, $repository->active());
    }

    public function test_assignment_changes_invalidate_active_task_count_cache(): void
    {
        $user = $this->employee();
        $cache = app(AssignmentCacheService::class);
        $repository = app(TaskAssignmentRepository::class);

        $this->assertSame(0, $repository->activeTaskCountForUser($user->id));
        $this->assertTrue(Cache::has($cache->activeTaskCountKey($user->id)));

        TaskAssignment::factory()->create(['assigned_to' => $user->id]);

        $this->assertFalse(Cache::has($cache->activeTaskCountKey($user->id)));
        $this->assertSame(1, $repository->activeTaskCountForUser($user->id));
    }

    public function test_user_profile_changes_invalidate_eligible_users_cache(): void
    {
        $user = $this->employee(['department' => 'Engineering']);
        $rule = AssignmentRule::factory()->create([
            'conditions' => ['departments' => ['Engineering']],
        ]);
        $cache = app(AssignmentCacheService::class);
        $eligibility = app(UserEligibilityService::class);

        $this->assertCount(1, $eligibility->eligibleFor($rule));
        $oldKey = $cache->eligibleUsersKey($rule->id);
        $this->assertTrue(Cache::has($oldKey));

        $user->update(['department' => 'Operations']);

        $newKey = $cache->eligibleUsersKey($rule->id);
        $this->assertNotSame($oldKey, $newKey);
        $this->assertCount(0, $eligibility->eligibleFor($rule));
    }

    /** @param array<string, mixed> $attributes */
    private function employee(array $attributes = []): User
    {
        return User::factory()->create([
            'role_id' => Role::query()->where('name', 'Employee')->value('id'),
            'status' => true,
            ...$attributes,
        ]);
    }
}

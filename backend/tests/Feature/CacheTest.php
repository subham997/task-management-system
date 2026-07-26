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
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Role::query()->create(['name' => 'Employee']);
        Role::query()->create(['name' => 'Manager']);
    }

    public function test_task_cache_miss_hit_and_refresh(): void
    {
        $task = Task::factory()->create(['title' => 'Initial title']);
        $cache = app(TaskCacheService::class);

        $this->assertFalse(Cache::has($cache->taskKey($task->id)));
        $this->assertSame('Initial title', app(TaskService::class)->details($task->id)->title);
        $this->assertTrue(Cache::has($cache->taskKey($task->id)));

        DB::table('tasks')->where('id', $task->id)->update(['title' => 'Database-only change']);
        $this->assertSame('Initial title', app(TaskService::class)->details($task->id)->title);

        $task->refresh()->update(['title' => 'Refreshed title']);
        $this->assertFalse(Cache::has($cache->taskKey($task->id)));
        $this->assertSame('Refreshed title', app(TaskService::class)->details($task->id)->title);
    }

    public function test_cached_task_api_responses_remain_correct(): void
    {
        $manager = $this->employee(['role_id' => Role::query()->where('name', 'Manager')->value('id')]);
        $task = Task::factory()->create(['created_by' => $manager->id, 'title' => 'Cached API task']);
        Sanctum::actingAs($manager);

        $this->getJson("/api/tasks/{$task->id}")
            ->assertOk()
            ->assertJsonPath('data.task.title', 'Cached API task');

        $this->getJson("/api/tasks/{$task->id}")
            ->assertOk()
            ->assertJsonPath('data.task.title', 'Cached API task');

        $this->assertTrue(Cache::has(app(TaskCacheService::class)->taskKey($task->id)));
    }

    public function test_assignment_rules_are_cached_with_the_required_key_and_refreshed_after_update(): void
    {
        $rule = AssignmentRule::factory()->create();
        $cache = app(AssignmentCacheService::class);
        $repository = app(AssignmentRuleRepository::class);

        $this->assertCount(1, $repository->active());
        $this->assertTrue(Cache::has($cache->assignmentRuleKey($rule->id)));

        $rule->update(['is_active' => false]);

        $this->assertFalse(Cache::has($cache->assignmentRuleKey($rule->id)));
        $this->assertCount(0, $repository->active());
    }

    public function test_assignment_changes_refresh_active_task_count_cache(): void
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

    public function test_user_profile_and_eligible_users_caches_refresh_after_a_profile_update(): void
    {
        $user = $this->employee(['department' => 'Engineering']);
        $task = Task::factory()->create();
        $rule = AssignmentRule::factory()->create([
            'conditions' => ['departments' => ['Engineering']],
        ]);
        $assignmentCache = app(AssignmentCacheService::class);
        $taskCache = app(TaskCacheService::class);
        $eligibility = app(UserEligibilityService::class);

        $this->assertCount(1, $eligibility->eligibleFor($rule, $task));
        $this->assertTrue(Cache::has($assignmentCache->userProfileKey($user->id)));
        $this->assertTrue(Cache::has($taskCache->eligibleUsersKey($task->id)));

        $user->update(['department' => 'Operations']);

        $this->assertFalse(Cache::has($assignmentCache->userProfileKey($user->id)));
        $this->assertCount(0, $eligibility->eligibleFor($rule, $task));
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

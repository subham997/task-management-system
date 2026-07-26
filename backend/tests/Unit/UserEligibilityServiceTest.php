<?php

namespace Tests\Unit;

use App\Models\AssignmentRule;
use App\Models\Role;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\User;
use App\Services\UserEligibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class UserEligibilityServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Role::query()->create(['name' => 'Employee']);
    }

    public function test_it_returns_only_users_matching_rule_conditions_and_workload(): void
    {
        $eligible = $this->employee(['department' => 'Engineering']);
        $ineligible = $this->employee(['department' => 'Operations']);
        $task = Task::factory()->create();
        TaskAssignment::factory()->create(['assigned_to' => $ineligible->id, 'status' => 'assigned']);
        $rule = AssignmentRule::factory()->create([
            'conditions' => [
                'roles' => ['Employee'],
                'departments' => ['Engineering', 'Operations'],
                'max_active_tasks' => 1,
            ],
        ]);

        $users = app(UserEligibilityService::class)->eligibleFor($rule, $task);

        $this->assertCount(1, $users);
        $this->assertSame($eligible->id, $users->first()->id);
    }

    /** @param array<string, mixed> $attributes */
    private function employee(array $attributes = []): User
    {
        return User::factory()->create([
            'role_id' => Role::query()->value('id'),
            'status' => true,
            ...$attributes,
        ]);
    }
}

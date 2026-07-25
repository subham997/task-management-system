<?php

namespace Tests\Feature;

use App\Jobs\AssignTaskJob;
use App\Models\AssignmentLog;
use App\Models\AssignmentRule;
use App\Models\Role;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\User;
use App\Services\AssignmentEngine;
use App\Services\TaskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AssignmentEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        collect(['Admin', 'Manager', 'Employee'])->each(function (string $name): void {
            Role::query()->create(['name' => $name]);
        });
    }

    public function test_engine_creates_an_assignment_and_audit_log_for_an_eligible_user(): void
    {
        $creator = $this->userWithRole('Manager');
        $assignee = $this->userWithRole('Employee', ['department' => 'Engineering']);
        $task = Task::factory()->create(['created_by' => $creator->id]);
        $rule = AssignmentRule::factory()->create([
            'created_by' => $creator->id,
            'conditions' => ['roles' => ['Employee'], 'departments' => ['Engineering']],
        ]);

        $assignment = app(AssignmentEngine::class)->assignTask($task->id);

        $this->assertNotNull($assignment);
        $this->assertSame($assignee->id, $assignment->assigned_to);
        $this->assertSame($rule->id, $assignment->assignment_rule_id);
        $this->assertDatabaseHas('assignment_logs', [
            'task_assignment_id' => $assignment->id,
            'event' => 'assigned',
        ]);
        $this->assertSame(1, AssignmentLog::query()->count());
    }

    public function test_engine_does_not_create_an_assignment_when_no_user_is_eligible(): void
    {
        $creator = $this->userWithRole('Manager');
        $this->userWithRole('Employee', ['department' => 'Engineering']);
        $task = Task::factory()->create(['created_by' => $creator->id]);

        AssignmentRule::factory()->create([
            'created_by' => $creator->id,
            'conditions' => ['departments' => ['Operations']],
        ]);

        $assignment = app(AssignmentEngine::class)->assignTask($task->id);

        $this->assertNull($assignment);
        $this->assertDatabaseCount('task_assignments', 0);
        $this->assertDatabaseCount('assignment_logs', 0);
    }

    public function test_engine_selects_the_candidate_with_the_lowest_active_task_count(): void
    {
        $creator = $this->userWithRole('Manager');
        $busyUser = $this->userWithRole('Employee');
        $availableUser = $this->userWithRole('Employee');
        $task = Task::factory()->create(['created_by' => $creator->id]);

        TaskAssignment::factory()->create([
            'assigned_to' => $busyUser->id,
            'assigned_by' => $creator->id,
            'status' => 'assigned',
        ]);

        AssignmentRule::factory()->create([
            'created_by' => $creator->id,
            'conditions' => ['roles' => ['Employee']],
        ]);

        $assignment = app(AssignmentEngine::class)->assignTask($task->id);

        $this->assertNotNull($assignment);
        $this->assertSame($availableUser->id, $assignment->assigned_to);
    }

    public function test_task_creation_dispatches_the_assignment_job(): void
    {
        Queue::fake();
        $creator = $this->userWithRole('Manager');

        $task = app(TaskService::class)->create($creator, ['title' => 'Queue assignment']);

        Queue::assertPushed(AssignTaskJob::class, function (AssignTaskJob $job) use ($task): bool {
            return $job->taskId === $task->id;
        });
    }

    /** @param array<string, mixed> $attributes */
    private function userWithRole(string $roleName, array $attributes = []): User
    {
        return User::factory()->create([
            'role_id' => Role::query()->where('name', $roleName)->value('id'),
            'status' => true,
            ...$attributes,
        ]);
    }
}

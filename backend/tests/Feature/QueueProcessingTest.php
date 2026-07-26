<?php

namespace Tests\Feature;

use App\Jobs\AssignTaskJob;
use App\Models\AssignmentRule;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use App\Services\AssignmentEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Tests\TestCase;

class QueueProcessingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        collect(['Manager', 'Employee'])->each(function (string $name): void {
            Role::query()->create(['name' => $name]);
        });
    }

    public function test_assignment_job_processes_and_creates_an_assignment(): void
    {
        $manager = $this->userWithRole('Manager');
        $assignee = $this->userWithRole('Employee');
        $task = Task::factory()->create(['created_by' => $manager->id]);
        AssignmentRule::factory()->create([
            'created_by' => $manager->id,
            'conditions' => ['roles' => ['Employee']],
        ]);

        (new AssignTaskJob($task->id))->handle(app(AssignmentEngine::class));

        $this->assertDatabaseHas('task_assignments', [
            'task_id' => $task->id,
            'assigned_to' => $assignee->id,
        ]);
    }

    public function test_failed_assignment_job_is_logged_with_structured_context(): void
    {
        Log::spy();

        (new AssignTaskJob(999))->failed(new RuntimeException('Worker failure'));

        Log::shouldHaveReceived('error')
            ->once()
            ->with('queue.assignment_job_failed', \Mockery::on(function (array $context): bool {
                return $context['task_id'] === 999 && $context['exception'] instanceof RuntimeException;
            }));
    }

    private function userWithRole(string $roleName): User
    {
        return User::factory()->create([
            'role_id' => Role::query()->where('name', $roleName)->value('id'),
            'status' => true,
        ]);
    }
}

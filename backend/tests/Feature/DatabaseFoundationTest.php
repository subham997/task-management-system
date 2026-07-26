<?php

namespace Tests\Feature;

use App\Models\AssignmentLog;
use App\Models\AssignmentRule;
use App\Models\Task;
use App\Models\TaskAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_factories_create_related_assignment_records(): void
    {
        $assignment = TaskAssignment::factory()->fromRule()->create();
        $log = AssignmentLog::factory()->create(['task_assignment_id' => $assignment->id]);

        $this->assertInstanceOf(Task::class, $assignment->task);
        $this->assertInstanceOf(AssignmentRule::class, $assignment->assignmentRule);
        $this->assertSame($assignment->id, $log->taskAssignment->id);
        $this->assertTrue($assignment->task->assignments->contains($assignment));
    }

    public function test_database_seeder_creates_the_complete_assignment_foundation(): void
    {
        $this->seed();

        $this->assertDatabaseCount('roles', 3);
        $this->assertDatabaseCount('tasks', 10);
        $this->assertDatabaseCount('assignment_rules', 2);
        $this->assertDatabaseCount('task_assignments', 5);
        $this->assertDatabaseCount('assignment_logs', 5);
    }
}

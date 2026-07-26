<?php

namespace Tests\Unit;

use App\Models\AssignmentRule;
use App\Models\TaskAssignment;
use App\Services\AssignmentLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignmentLoggerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_assignment_audit_log(): void
    {
        $rule = AssignmentRule::factory()->create();
        $assignment = TaskAssignment::factory()->create(['assignment_rule_id' => $rule->id]);

        $log = app(AssignmentLogger::class)->logAssignment($assignment);

        $this->assertSame($assignment->id, $log->task_assignment_id);
        $this->assertSame('assigned', $log->event);
        $this->assertSame($rule->id, $log->metadata['assignment_rule_id']);
    }
}

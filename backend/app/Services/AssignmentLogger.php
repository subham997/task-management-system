<?php

namespace App\Services;

use App\Models\AssignmentLog;
use App\Models\TaskAssignment;

class AssignmentLogger
{
    public function logAssignment(TaskAssignment $assignment): AssignmentLog
    {
        return AssignmentLog::query()->create([
            'task_assignment_id' => $assignment->id,
            'event' => 'assigned',
            'description' => 'Task assigned by the dynamic assignment engine.',
            'metadata' => [
                'assignment_rule_id' => $assignment->assignment_rule_id,
                'assigned_to' => $assignment->assigned_to,
            ],
        ]);
    }
}

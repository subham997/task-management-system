<?php

namespace App\Observers;

use App\Models\TaskAssignment;
use App\Services\AssignmentCacheService;
use App\Services\TaskCacheService;

class TaskAssignmentObserver
{
    public function created(TaskAssignment $assignment): void
    {
        $this->invalidate($assignment);
    }

    public function updated(TaskAssignment $assignment): void
    {
        $this->invalidate($assignment, $assignment->getOriginal('assigned_to'));
    }

    public function deleted(TaskAssignment $assignment): void
    {
        $this->invalidate($assignment, $assignment->assigned_to);
    }

    private function invalidate(TaskAssignment $assignment, ?int $previousAssigneeId = null): void
    {
        $assignmentCache = app(AssignmentCacheService::class);
        $assignmentCache->forgetActiveTaskCount($assignment->assigned_to);

        if ($previousAssigneeId !== null && $previousAssigneeId !== $assignment->assigned_to) {
            $assignmentCache->forgetActiveTaskCount($previousAssigneeId);
        }

        $assignmentCache->invalidateEligibleUsers();
        app(TaskCacheService::class)->forgetTask($assignment->task_id);
    }
}

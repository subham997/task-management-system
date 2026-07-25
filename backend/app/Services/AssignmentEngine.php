<?php

namespace App\Services;

use App\Models\TaskAssignment;
use App\Repositories\AssignmentRuleRepository;
use App\Repositories\TaskAssignmentRepository;
use Illuminate\Support\Facades\DB;

class AssignmentEngine
{
    public function __construct(
        private readonly AssignmentRuleRepository $rules,
        private readonly TaskAssignmentRepository $assignments,
        private readonly UserEligibilityService $eligibility,
        private readonly AssignmentSelector $selector,
        private readonly AssignmentLogger $logger
    ) {}

    public function assignTask(int $taskId): ?TaskAssignment
    {
        return DB::transaction(function () use ($taskId): ?TaskAssignment {
            $task = $this->assignments->findTaskForUpdate($taskId);

            if ($task === null || $this->assignments->existsForTask($task)) {
                return null;
            }

            foreach ($this->rules->active() as $rule) {
                $assignee = $this->selector->select($this->eligibility->eligibleFor($rule));

                if ($assignee === null) {
                    continue;
                }

                $assignment = $this->assignments->create([
                    'task_id' => $task->id,
                    'assigned_to' => $assignee->id,
                    'assignment_rule_id' => $rule->id,
                    'status' => 'assigned',
                    'assigned_at' => now(),
                ]);

                $this->logger->logAssignment($assignment);

                return $assignment;
            }

            return null;
        });
    }
}

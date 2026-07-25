<?php

namespace Database\Seeders;

use App\Models\AssignmentRule;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\User;
use Illuminate\Database\Seeder;

class TaskAssignmentSeeder extends Seeder
{
    public function run(): void
    {
        $assigner = User::query()->first() ?? User::factory()->create();
        $assignee = User::query()->skip(1)->first() ?? User::factory()->create();
        $rule = AssignmentRule::query()->first();

        Task::query()->limit(5)->get()->each(function (Task $task) use ($assigner, $assignee, $rule): void {
            TaskAssignment::query()->create([
                'task_id' => $task->id,
                'assigned_to' => $assignee->id,
                'assigned_by' => $assigner->id,
                'assignment_rule_id' => $rule?->id,
                'status' => 'assigned',
                'assigned_at' => now(),
            ]);
        });
    }
}

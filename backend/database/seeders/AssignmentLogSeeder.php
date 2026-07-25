<?php

namespace Database\Seeders;

use App\Models\AssignmentLog;
use App\Models\TaskAssignment;
use Illuminate\Database\Seeder;

class AssignmentLogSeeder extends Seeder
{
    public function run(): void
    {
        TaskAssignment::query()->get()->each(function (TaskAssignment $assignment): void {
            AssignmentLog::query()->create([
                'task_assignment_id' => $assignment->id,
                'actor_id' => $assignment->assigned_by,
                'event' => 'assigned',
                'description' => 'Task assignment created.',
                'metadata' => ['source' => 'database_seeder'],
            ]);
        });
    }
}

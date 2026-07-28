<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::query()->where('email', 'demo@example.com')->first()
            ?? User::query()->first()
            ?? User::factory()->create();

        $tasks = [
            [
                'title' => 'Prepare monthly project status report',
                'description' => 'Summarise milestones, risks, and next steps for the management review.',
                'status' => 'in_progress',
                'priority' => 'high',
                'due_at' => now()->addDays(2),
            ],
            [
                'title' => 'Review client onboarding checklist',
                'description' => 'Confirm that all required documents and access requests are complete.',
                'status' => 'pending',
                'priority' => 'high',
                'due_at' => now()->addDays(3),
            ],
            [
                'title' => 'Update the sprint planning board',
                'description' => 'Prioritise approved work items for the upcoming sprint.',
                'status' => 'pending',
                'priority' => 'medium',
                'due_at' => now()->addDays(5),
            ],
            [
                'title' => 'Schedule the team retrospective',
                'description' => 'Send a calendar invitation and prepare the retrospective agenda.',
                'status' => 'pending',
                'priority' => 'medium',
                'due_at' => now()->addDays(7),
            ],
            [
                'title' => 'Archive completed support requests',
                'description' => 'Review resolved support requests and archive the completed records.',
                'status' => 'completed',
                'priority' => 'low',
                'due_at' => now()->subDay(),
                'completed_at' => now()->subDay(),
            ],
            [
                'title' => 'Validate the quarterly budget forecast',
                'description' => 'Review planned expenditure and confirm the forecast with finance.',
                'status' => 'in_progress',
                'priority' => 'high',
                'due_at' => now()->addDays(4),
            ],
            [
                'title' => 'Prepare the client meeting agenda',
                'description' => 'Collect discussion points and share the agenda before the meeting.',
                'status' => 'pending',
                'priority' => 'medium',
                'due_at' => now()->addDays(6),
            ],
            [
                'title' => 'Review team workload allocation',
                'description' => 'Identify capacity risks and redistribute urgent work where needed.',
                'status' => 'pending',
                'priority' => 'high',
                'due_at' => now()->addDays(8),
            ],
            [
                'title' => 'Document the release readiness checklist',
                'description' => 'Capture testing, deployment, and communication requirements for the release.',
                'status' => 'pending',
                'priority' => 'medium',
                'due_at' => now()->addDays(10),
            ],
            [
                'title' => 'Follow up on customer feedback',
                'description' => 'Review recent feedback and assign the next action to the appropriate owner.',
                'status' => 'pending',
                'priority' => 'low',
                'due_at' => now()->addDays(12),
            ],
        ];

        Task::query()
            ->where('created_by', $owner->id)
            ->whereNotIn('title', array_column($tasks, 'title'))
            ->delete();

        foreach ($tasks as $task) {
            Task::query()->updateOrCreate(
                [
                    'created_by' => $owner->id,
                    'title' => $task['title'],
                ],
                $task
            );
        }
    }
}

<?php

namespace Database\Factories;

use App\Models\AssignmentRule;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskAssignment>
 */
class TaskAssignmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'assigned_to' => User::factory(),
            'assigned_by' => User::factory(),
            'assignment_rule_id' => null,
            'status' => 'assigned',
            'assigned_at' => now(),
            'accepted_at' => null,
            'completed_at' => null,
        ];
    }

    public function fromRule(): static
    {
        return $this->state(fn (): array => [
            'assignment_rule_id' => AssignmentRule::factory(),
        ]);
    }
}

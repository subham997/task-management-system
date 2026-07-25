<?php

namespace Database\Factories;

use App\Models\AssignmentLog;
use App\Models\TaskAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssignmentLog>
 */
class AssignmentLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'task_assignment_id' => TaskAssignment::factory(),
            'actor_id' => User::factory(),
            'event' => fake()->randomElement(['assigned', 'accepted', 'completed']),
            'description' => fake()->sentence(),
            'metadata' => ['source' => 'factory'],
        ];
    }
}

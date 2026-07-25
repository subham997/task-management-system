<?php

namespace Database\Factories;

use App\Models\AssignmentRule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssignmentRule>
 */
class AssignmentRuleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'created_by' => User::factory(),
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'conditions' => ['priority' => 'high'],
            'actions' => ['assignment_strategy' => 'manual'],
            'priority' => fake()->numberBetween(0, 100),
            'is_active' => true,
        ];
    }
}

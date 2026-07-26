<?php

namespace Tests\Unit;

use App\Models\Role;
use App\Models\User;
use App\Services\RuleEvaluator;
use Tests\TestCase;

class RuleEvaluatorTest extends TestCase
{
    public function test_it_matches_a_user_against_supported_rule_conditions(): void
    {
        $user = new User([
            'status' => true,
            'department' => 'Engineering',
            'designation' => 'Developer',
        ]);
        $user->setAttribute('id', 7);
        $user->setRelation('role', new Role(['name' => 'Employee']));
        $user->setAttribute('active_task_count', 1);

        $matches = (new RuleEvaluator)->matches($user, [
            'user_ids' => [7],
            'roles' => ['Employee'],
            'departments' => ['Engineering'],
            'designations' => ['Developer'],
            'max_active_tasks' => 2,
        ]);

        $this->assertTrue($matches);
    }

    public function test_it_rejects_users_that_exceed_the_workload_limit_or_are_inactive(): void
    {
        $user = new User(['status' => true]);
        $user->setAttribute('active_task_count', 2);

        $evaluator = new RuleEvaluator;

        $this->assertFalse($evaluator->matches($user, ['max_active_tasks' => 2]));

        $this->assertFalse($evaluator->matches(new User, []));
    }
}

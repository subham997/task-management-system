<?php

namespace App\Observers;

use App\Models\AssignmentRule;
use App\Services\AssignmentCacheService;
use App\Services\TaskCacheService;

class AssignmentRuleObserver
{
    public function saved(AssignmentRule $rule): void
    {
        $this->invalidate($rule);
    }

    public function deleted(AssignmentRule $rule): void
    {
        $this->invalidate($rule);
    }

    private function invalidate(AssignmentRule $rule): void
    {
        $cache = app(AssignmentCacheService::class);
        $cache->forgetActiveRules();
        $cache->forgetAssignmentRule($rule->id);
        app(TaskCacheService::class)->invalidateAllEligibleUsers();
    }
}

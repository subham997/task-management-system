<?php

namespace App\Observers;

use App\Models\AssignmentRule;
use App\Services\AssignmentCacheService;

class AssignmentRuleObserver
{
    public function saved(AssignmentRule $rule): void
    {
        $this->invalidate();
    }

    public function deleted(AssignmentRule $rule): void
    {
        $this->invalidate();
    }

    private function invalidate(): void
    {
        $cache = app(AssignmentCacheService::class);
        $cache->forgetActiveRules();
        $cache->invalidateEligibleUsers();
    }
}

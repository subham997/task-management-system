<?php

namespace App\Repositories;

use App\Models\AssignmentRule;
use App\Services\AssignmentCacheService;
use Illuminate\Database\Eloquent\Collection;

class AssignmentRuleRepository
{
    /** @return Collection<int, AssignmentRule> */
    public function active(): Collection
    {
        return app(AssignmentCacheService::class)->activeRules(
            fn (): Collection => AssignmentRule::query()
                ->where('is_active', true)
                ->orderByDesc('priority')
                ->orderBy('id')
                ->get()
        );
    }

    public function find(int $ruleId): ?AssignmentRule
    {
        return AssignmentRule::query()->find($ruleId);
    }
}

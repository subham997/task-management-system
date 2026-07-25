<?php

namespace App\Repositories;

use App\Models\AssignmentRule;
use App\Services\AssignmentCacheService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class AssignmentRuleRepository
{
    /** @return Collection<int, AssignmentRule> */
    public function active(): Collection
    {
        $cache = app(AssignmentCacheService::class);

        return $cache->activeRules(
            fn (): Collection => AssignmentRule::query()
                ->where('is_active', true)
                ->orderByDesc('priority')
                ->orderBy('id')
                ->get()
                ->map(fn (AssignmentRule $rule): AssignmentRule => $cache->assignmentRule($rule->id, fn (): AssignmentRule => $rule))
        );
    }

    public function find(int $ruleId): ?AssignmentRule
    {
        $cache = app(AssignmentCacheService::class);
        $key = $cache->assignmentRuleKey($ruleId);

        if (Cache::has($key)) {
            return Cache::get($key);
        }

        $rule = AssignmentRule::query()->find($ruleId);

        return $rule === null ? null : $cache->assignmentRule($ruleId, fn (): AssignmentRule => $rule);
    }
}

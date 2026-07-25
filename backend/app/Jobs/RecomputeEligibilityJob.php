<?php

namespace App\Jobs;

use App\Repositories\AssignmentRuleRepository;
use App\Services\UserEligibilityService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecomputeEligibilityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $assignmentRuleId) {}

    public function handle(
        AssignmentRuleRepository $rules,
        UserEligibilityService $eligibilityService
    ): void {
        $rule = $rules->find($this->assignmentRuleId);

        if ($rule !== null) {
            $eligibilityService->eligibleFor($rule);
        }
    }
}

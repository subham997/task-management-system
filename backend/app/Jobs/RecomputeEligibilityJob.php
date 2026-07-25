<?php

namespace App\Jobs;

use App\Repositories\AssignmentRuleRepository;
use App\Services\UserEligibilityService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class RecomputeEligibilityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

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

    /** @return list<int> */
    public function backoff(): array
    {
        return [10, 60, 300];
    }

    public function failed(Throwable $exception): void
    {
        Log::error('queue.recompute_eligibility_job_failed', [
            'assignment_rule_id' => $this->assignmentRuleId,
            'exception' => $exception,
        ]);
    }
}

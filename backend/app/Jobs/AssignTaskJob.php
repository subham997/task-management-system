<?php

namespace App\Jobs;

use App\Services\AssignmentEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class AssignTaskJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public readonly int $taskId) {}

    public function handle(AssignmentEngine $assignmentEngine): void
    {
        $assignmentEngine->assignTask($this->taskId);
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return [10, 60, 300];
    }

    public function failed(Throwable $exception): void
    {
        Log::error('queue.assignment_job_failed', [
            'task_id' => $this->taskId,
            'exception' => $exception,
        ]);
    }
}

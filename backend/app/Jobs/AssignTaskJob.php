<?php

namespace App\Jobs;

use App\Services\AssignmentEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AssignTaskJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $taskId) {}

    public function handle(AssignmentEngine $assignmentEngine): void
    {
        $assignmentEngine->assignTask($this->taskId);
    }
}

<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class AssignmentSelector
{
    /** @param Collection<int, User> $eligibleUsers */
    public function select(Collection $eligibleUsers): ?User
    {
        return $eligibleUsers
            ->sortBy([
                ['active_task_count', 'asc'],
                ['last_assigned_at', 'asc'],
                ['id', 'asc'],
            ])
            ->first();
    }
}

<?php

namespace App\Observers;

use App\Models\User;
use App\Services\AssignmentCacheService;

class UserObserver
{
    public function saved(User $user): void
    {
        $this->invalidate($user);
    }

    public function deleted(User $user): void
    {
        $this->invalidate($user);
    }

    private function invalidate(User $user): void
    {
        $cache = app(AssignmentCacheService::class);
        $cache->forgetActiveTaskCount($user->id);
        $cache->invalidateEligibleUsers();
    }
}

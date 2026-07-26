<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\AssignmentSelector;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\TestCase;

class AssignmentSelectorTest extends TestCase
{
    public function test_it_selects_the_lowest_workload_then_least_recently_assigned_user(): void
    {
        $busy = new User;
        $busy->setAttribute('id', 1);
        $busy->setAttribute('active_task_count', 2);
        $busy->setAttribute('last_assigned_at', '2026-07-25 08:00:00');

        $recent = new User;
        $recent->setAttribute('id', 2);
        $recent->setAttribute('active_task_count', 0);
        $recent->setAttribute('last_assigned_at', '2026-07-25 12:00:00');

        $oldest = new User;
        $oldest->setAttribute('id', 3);
        $oldest->setAttribute('active_task_count', 0);
        $oldest->setAttribute('last_assigned_at', '2026-07-25 09:00:00');

        $selected = (new AssignmentSelector)->select(new Collection([$busy, $recent, $oldest]));

        $this->assertSame(3, $selected?->id);
    }

    public function test_it_returns_null_without_eligible_users(): void
    {
        $this->assertNull((new AssignmentSelector)->select(new Collection));
    }
}

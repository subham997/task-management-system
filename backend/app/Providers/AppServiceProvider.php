<?php

namespace App\Providers;

use App\Models\AssignmentRule;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\User;
use App\Observers\AssignmentRuleObserver;
use App\Observers\TaskAssignmentObserver;
use App\Observers\TaskObserver;
use App\Observers\UserObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Task::observe(TaskObserver::class);
        TaskAssignment::observe(TaskAssignmentObserver::class);
        AssignmentRule::observe(AssignmentRuleObserver::class);
        User::observe(UserObserver::class);

        RateLimiter::for('auth', function (Request $request): Limit {
            return Limit::perMinute(5)->by($request->ip().'|'.$request->input('email'));
        });

        RateLimiter::for('api', function (Request $request): Limit {
            return Limit::perMinute(60)->by((string) ($request->user()?->id ?? $request->ip()));
        });
    }
}

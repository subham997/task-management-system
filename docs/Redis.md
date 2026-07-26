# Redis caching

[Documentation index](README.md) · [Architecture](Architecture.md) · [Rule Engine](RuleEngine.md)

## Overview

The application uses Laravel's `Cache` facade to cache data repeatedly read by task views and assignment selection. In Docker, Redis is the intended cache and queue backend. TTL values are configurable in `backend/config/cache.php` through environment variables.

## Cache keys and invalidation

| Cache key | Purpose | Default TTL | Invalidation event |
| --- | --- | ---: | --- |
| `task:{id}` | Task detail and creator data | 300 seconds | Task saved or deleted; task assignment changes. |
| `user:{id}` | User profile and role data | 300 seconds | User saved or deleted. |
| `user:{id}:active-task-count` | Current active assignment workload | 120 seconds | Assignment created, updated, or deleted; affected user changes. |
| `task:{id}:eligible-users` | Eligible candidates for a task/rule evaluation | 120 seconds | Task, user, rule, or assignment changes. |
| `assignment-rule:{id}` | Individual assignment rule | 300 seconds | Rule saved or deleted. |
| `assignment:rules:active` | Active rule list used by the engine | 300 seconds | Rule saved or deleted. |
| `eligible-users:version` | Version marker for broad eligibility invalidation | n/a | Incremented when user/rule/assignment changes affect eligibility. |

The requested key family (`task:{id}`, `user:{id}`, `user:{id}:active-task-count`, `task:{id}:eligible-users`, `assignment-rule:{id}`) is implemented; the last two internal keys support active-rule lookup and broad invalidation.

## Configurable TTLs

| Configuration key | Environment variable | Default |
| --- | --- | ---: |
| `user_profile` | `USER_CACHE_TTL` | 300 seconds |
| `task_details` | `TASK_CACHE_TTL` | 300 seconds |
| `eligible_users` | `ELIGIBLE_USERS_CACHE_TTL` | 120 seconds |
| `active_task_count` | `ACTIVE_TASK_COUNT_CACHE_TTL` | 120 seconds |
| `assignment_rules` | `ASSIGNMENT_RULES_CACHE_TTL` | 300 seconds |

Set `CACHE_STORE=redis` and the relevant `REDIS_*` variables in the Laravel environment to use Redis. A different Laravel cache store may be used in tests or constrained environments.

## Invalidation strategy

Eloquent observers make cached reads lifecycle-aware:

- **Task observer:** forgets task detail and task eligibility data after a task save/delete.
- **User observer:** forgets user profile/workload data and invalidates eligible-user results after profile changes.
- **Assignment-rule observer:** forgets individual/active rule data and invalidates eligibility results after rule changes.
- **Task-assignment observer:** forgets current and prior assignee workload data, task data, and eligible-user results after assignment changes.

This makes updates visible without waiting for an otherwise valid TTL to expire.

## Cache misses and performance

Cache services log structured `cache.miss` events. Caching reduces repeated joins and count queries for task detail, user role/profile loading, active-workload calculations, and rule/eligibility lookups. Repository queries still eager-load required relationships to avoid N+1 behaviour on misses.

## Operational guidance

- Use Redis persistence and memory policies appropriate to the environment.
- Clear application caches after deployment/configuration changes with `php artisan optimize:clear` when appropriate.
- Treat cached eligibility as an optimisation; assignment execution still validates candidates in its workflow.
- Confirm invalidation with the cache feature tests whenever new model lifecycle changes are introduced.

Next: [Testing](Testing.md) · [Deployment](Deployment.md)

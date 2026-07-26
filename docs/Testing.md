# Testing

[Documentation index](README.md) · [API](API.md) · [Rule Engine](RuleEngine.md)

## Strategy

The backend test suite exercises public HTTP behaviour, policies, request validation, domain collaborators, factories/seeders, cache behaviour, and queue dispatch/processing. Tests favour `RefreshDatabase`, focused factories, queue/cache fakes where appropriate, and reusable setup helpers over duplicated fixtures.

## Feature tests

| Area | Covered behaviour |
| --- | --- |
| Authentication | Registration, login, invalid login, logout, and authenticated profile retrieval. |
| Tasks | Create, view, update, delete, pagination, status/priority filters, sorting, and search. |
| Authorization | Admin/Manager management rights and Employee ownership restrictions. |
| Validation | Required title, supported status/priority, due-date constraints, and invalid request payloads. |
| Assignment engine | Matching rules, multiple candidates, no candidate, assignment records, and audit logs. |
| Queue | Assignment job dispatch, job processing, and failure hooks. |
| Redis/cache | Cache hits, misses, invalidation, and refresh after lifecycle changes. |
| API hardening | Standard error envelope behaviour for validation, authentication, authorization, and not-found cases. |

## Unit tests

The rule engine is also tested through focused units:

- `RuleEvaluator` verifies supported JSON conditions.
- `AssignmentSelector` verifies workload, assignment-time, and ID tie-breaking.
- `UserEligibilityService` verifies active-user and rule eligibility behaviour.
- `AssignmentLogger` verifies audit-log persistence.

## Database tests

Factories create roles, users, tasks, rules, assignments, and logs with realistic defaults. Seeder tests validate role and administrative-user seed data. Relationship tests verify the key Eloquent associations across the assignment model.

## Queue and cache testing

Queue fakes verify dispatch without running workers when a request-level test only needs to assert intent. Job tests execute the job/engine path where persistence behaviour is under test. Cache-focused tests use Laravel cache controls to assert a miss, a hit, and a refreshed value after invalidation.

## Commands

Run from `backend/`:

```bash
php artisan test
php artisan route:list
php artisan migrate:fresh --seed
```

For an isolated in-memory migration/seed check, the test configuration uses SQLite. The production-style Docker environment uses MySQL and Redis.

## Coverage summary

The suite is organised around the system's risk boundaries rather than a raw line-percentage target: authentication and policy enforcement, task lifecycle, rule evaluation and selection fairness, persistence/audit records, queue reliability, cache correctness, and database relationships. Extend the closest existing feature or unit test when changing one of those boundaries.

## Test-writing guidance

- Use `RefreshDatabase` for tests that alter persistence.
- Build only data required for the scenario using factories.
- Assert the response envelope as well as status codes for API tests.
- Use `Queue::fake()` when validating dispatch; do not fake it when validating a job's work.
- Reset or isolate cache state in cache behaviour tests.
- Keep authorization tests explicit about actor role and task ownership.

Next: [Deployment](Deployment.md) · [Future Enhancements](FutureEnhancements.md)

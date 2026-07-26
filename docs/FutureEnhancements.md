# Future enhancements

[Documentation index](README.md) · [Architecture](Architecture.md) · [Deployment](Deployment.md)

This document lists intentionally unimplemented improvements. They are opportunities, not claims about current behaviour.

## Product and API

- Add authorised REST endpoints and AngularJS administration screens for assignment rules.
- Add task-assignment/history endpoints for managers and assignees.
- Add a user-profile update API with role/department/designation management.
- Provide a documented queue health/status endpoint if the frontend needs one.
- Add task comments, attachments, labels, notifications, and saved views.

## Rule engine

- Build a validated visual rule builder and candidate-preview workflow.
- Support richer conditions (skills, schedules, geographic constraints, task metadata) and explicit action semantics.
- Add rule versioning, dry runs, explainability, and rollback/audit capabilities.
- Define retry/requeue policy for tasks that have no eligible user.

## Operations and security

- Add queue dashboards/metrics, alerting, and dead-letter operational playbooks.
- Add application health checks, tracing, structured-log aggregation, and SLO dashboards.
- Establish CI for style, static analysis, tests, migrations, and container scanning.
- Add dependency/security scanning, secret management, backup/restore drills, and documented retention policies.
- Consider token abilities, audit retention controls, and stronger frontend content-security policies.

## Frontend and platform

- Gradually migrate AngularJS 1.x to a supported frontend platform.
- Introduce a typed API client, accessibility review, responsive UI testing, and end-to-end browser tests.
- Add API versioning and an OpenAPI specification when external consumers are expected.
- Evaluate read replicas, queue scaling, and cache observability under production load.

Return to the [project README](../README.md).

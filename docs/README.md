# Documentation index

This documentation describes the implementation present in the Task Management System repository. It intentionally distinguishes public HTTP endpoints from internal services and jobs.

| Document | Description |
| --- | --- |
| [Architecture](Architecture.md) | Layers, dependencies, SOLID application, and request flow. |
| [Database](Database.md) | Domain schema, relationships, constraints, and indexes. |
| [API](API.md) | Implemented Sanctum and task API endpoints. |
| [Rule Engine](RuleEngine.md) | Asynchronous rule evaluation and user selection. |
| [Queue](Queue.md) | Jobs, retries, timeouts, and failure handling. |
| [Redis](Redis.md) | Cache keys, TTLs, and invalidation behaviour. |
| [Testing](Testing.md) | Automated-test strategy and commands. |
| [Deployment](Deployment.md) | Docker, Nginx, environment, workers, and production checks. |
| [Future Enhancements](FutureEnhancements.md) | Deliberate next-step opportunities. |

Return to the [project README](../README.md).

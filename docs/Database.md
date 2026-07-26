# Database

[Documentation index](README.md) · [Architecture](Architecture.md) · [Rule Engine](RuleEngine.md)

## Domain schema

The core model separates user identity and roles from task lifecycle and assignment history. Foreign keys express ownership and assignment relationships; indexes support the list, selection, and audit queries used by the application.

```mermaid
erDiagram
    ROLES ||--o{ USERS : "has many"
    USERS ||--o{ TASKS : "creates"
    USERS ||--o{ ASSIGNMENT_RULES : "creates"
    TASKS ||--o{ TASK_ASSIGNMENTS : "has many"
    USERS ||--o{ TASK_ASSIGNMENTS : "is assigned to"
    USERS ||--o{ TASK_ASSIGNMENTS : "assigns"
    ASSIGNMENT_RULES ||--o{ TASK_ASSIGNMENTS : "selects"
    TASK_ASSIGNMENTS ||--o{ ASSIGNMENT_LOGS : "has many"
    USERS ||--o{ ASSIGNMENT_LOGS : "acts in"

    ROLES {
        bigint id PK
        varchar name UK
        text description
        timestamps
    }
    USERS {
        bigint id PK
        bigint role_id FK
        varchar name
        varchar email UK
        varchar department
        varchar designation
        boolean status
        varchar password
        timestamps
    }
    TASKS {
        bigint id PK
        bigint created_by FK
        varchar title
        text description
        varchar status
        varchar priority
        datetime due_at
        datetime completed_at
        timestamps
    }
    ASSIGNMENT_RULES {
        bigint id PK
        bigint created_by FK
        varchar name UK
        json conditions
        json actions
        integer priority
        boolean is_active
        timestamps
    }
    TASK_ASSIGNMENTS {
        bigint id PK
        bigint task_id FK
        bigint assigned_to FK
        bigint assigned_by FK
        bigint assignment_rule_id FK
        varchar status
        datetime assigned_at
        datetime accepted_at
        datetime completed_at
        timestamps
    }
    ASSIGNMENT_LOGS {
        bigint id PK
        bigint task_assignment_id FK
        bigint actor_id FK
        varchar event
        text description
        json metadata
        timestamps
    }
```

## `roles`

**Purpose.** Holds role definitions used for role-based access control.

| Column | Notes |
| --- | --- |
| `id` | Primary key. |
| `name` | Required, unique role name. |
| `description` | Optional role description. |
| `created_at`, `updated_at` | Laravel timestamps. |

- **Primary key:** `id`
- **Foreign keys:** none
- **Relationships:** one role has many users.
- **Indexes:** unique index on `name`.
- **Normalization:** role labels are stored once and referenced from users, avoiding repeated role text.

## `users`

**Purpose.** Laravel identity records extended with assignment-eligibility attributes.

| Column | Notes |
| --- | --- |
| `id` | Primary key. |
| `role_id` | Nullable foreign key to `roles`; null on role deletion. |
| `name`, `email`, `password` | Standard identity fields; email is unique. |
| `email_verified_at`, `remember_token` | Standard Laravel authentication fields. |
| `department`, `designation` | Nullable attributes evaluated by assignment rules. |
| `status` | Boolean activity flag, default active. |
| timestamps | Laravel timestamps. |

- **Primary key:** `id`
- **Foreign keys:** `role_id → roles.id` (`nullOnDelete`).
- **Relationships:** belongs to a role; creates tasks/rules; can be assignment assignee, assigner, and log actor.
- **Indexes:** unique `email`; foreign-key index for `role_id`.
- **Normalization:** role information is normalised into `roles`; optional organisational attributes remain on the user profile because rules query them directly.

## `tasks`

**Purpose.** Stores work items created through the task API.

| Column | Notes |
| --- | --- |
| `id` | Primary key. |
| `created_by` | Required task creator foreign key. |
| `title`, `description` | Task content; description is nullable. |
| `status` | Defaults to `pending`; API accepts `pending`, `in_progress`, `completed`. |
| `priority` | Defaults to `medium`; API accepts `low`, `medium`, `high`. |
| `due_at`, `completed_at` | Nullable lifecycle timestamps. |
| timestamps | Laravel timestamps. |

- **Primary key:** `id`
- **Foreign keys:** `created_by → users.id` (`cascadeOnDelete`).
- **Relationships:** belongs to its creator; has many task assignments.
- **Indexes:** `(created_by, status)`, `(status, priority, due_at)`, and `due_at`.
- **Normalization:** assignments are separate records, so assignment lifecycle and audit history do not duplicate task data.

## `assignment_rules`

**Purpose.** Stores configurable eligibility criteria used by the background assignment engine.

| Column | Notes |
| --- | --- |
| `id` | Primary key. |
| `created_by` | Nullable creator foreign key. |
| `name`, `description` | Unique rule name and optional description. |
| `conditions` | Nullable JSON eligibility conditions. |
| `actions` | Nullable JSON column reserved for rule actions; current evaluator uses conditions. |
| `priority` | Unsigned priority, default `0`; higher rules are evaluated first. |
| `is_active` | Boolean active flag, default true. |
| timestamps | Laravel timestamps. |

- **Primary key:** `id`
- **Foreign keys:** `created_by → users.id` (`nullOnDelete`).
- **Relationships:** belongs to its creator; has many task assignments.
- **Indexes:** unique `name`; composite `(is_active, priority)`.
- **Normalization:** rule metadata is relational while variable condition structure is stored as JSON to support dynamic conditions.

## `task_assignments`

**Purpose.** Represents the assignment of one task to one selected user under an optional rule.

| Column | Notes |
| --- | --- |
| `id` | Primary key. |
| `task_id` | Required task foreign key. |
| `assigned_to` | Required assignee foreign key. |
| `assigned_by` | Nullable user foreign key for manual/system attribution. |
| `assignment_rule_id` | Nullable rule that produced the assignment. |
| `status` | Defaults to `assigned`. |
| `assigned_at`, `accepted_at`, `completed_at` | Assignment lifecycle timestamps. |
| timestamps | Laravel timestamps. |

- **Primary key:** `id`
- **Foreign keys:** `task_id → tasks.id` (cascade); `assigned_to → users.id` (cascade); `assigned_by → users.id` (null on delete); `assignment_rule_id → assignment_rules.id` (null on delete).
- **Relationships:** belongs to task, assignee, assigner, and rule; has many logs.
- **Indexes:** unique `(task_id, assigned_to)`; `(assigned_to, status)`; `(assignment_rule_id, status)`.
- **Normalization:** assignment state is independent of task state and links back to the rule without copying user or rule details.

## `assignment_logs`

**Purpose.** Provides an audit trail for events on task assignments.

| Column | Notes |
| --- | --- |
| `id` | Primary key. |
| `task_assignment_id` | Required parent assignment foreign key. |
| `actor_id` | Nullable user/system actor foreign key. |
| `event` | Event label, such as `assigned`. |
| `description` | Optional human-readable description. |
| `metadata` | Nullable JSON context, including selection rule data. |
| timestamps | Laravel timestamps. |

- **Primary key:** `id`
- **Foreign keys:** `task_assignment_id → task_assignments.id` (cascade); `actor_id → users.id` (null on delete).
- **Relationships:** belongs to a task assignment and optional actor.
- **Indexes:** `(task_assignment_id, created_at)`, `(actor_id, created_at)`, and `event`.
- **Normalization:** the log references its assignment and actor rather than copying mutable records; JSON metadata preserves event-specific context.

## Supporting framework tables

Laravel also supplies migration, cache, job, failed-job, job-batch, personal-access-token, password-reset, and session tables. They support infrastructure concerns and are outside the core domain ERD above.

Next: [Rule Engine](RuleEngine.md) · [Queue](Queue.md) · [Redis](Redis.md)

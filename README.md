# Task Management System

[![Laravel 11](https://img.shields.io/badge/Laravel-11-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![PHP 8.3](https://img.shields.io/badge/PHP-8.3-777BB4?logo=php&logoColor=white)](https://www.php.net)
[![MySQL 8](https://img.shields.io/badge/MySQL-8-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com)
[![Redis](https://img.shields.io/badge/Redis-Cache-DC382D?logo=redis&logoColor=white)](https://redis.io)
[![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?logo=docker&logoColor=white)](https://www.docker.com)

A Dockerised task-management platform built with Laravel 11, MySQL, Redis, Laravel Sanctum, and an AngularJS 1.x user interface. It provides role-aware task management and asynchronous, rule-based task assignment.

## Project overview

The system manages a task from creation through assignment and completion. Its API is protected with Sanctum personal-access tokens; application responsibilities are separated into controllers, services, repositories, Eloquent models, requests, resources, policies, and background jobs.

## Project objectives

- Provide a secure REST API for task management.
- Enforce role-aware access to task data.
- Assign tasks asynchronously using configurable JSON rules.
- Keep common reads fast through Redis-backed caching.
- Keep the codebase testable, maintainable, and ready for containerised deployment.

## Features

- Authentication with registration, login, logout, and current-user endpoints.
- Role-based access control for administrators, managers, and employees.
- Task creation, listing, retrieval, update, deletion, pagination, filters, sorting, and search.
- Dynamic rule-based assignment, including workload-aware selection and audit logs.
- Queue jobs with retry, timeout, and failure logging.
- Redis caching with lifecycle-aware invalidation.
- AngularJS 1.x and Bootstrap single-page frontend served by Nginx.

## Tech stack

| Layer | Technology |
| --- | --- |
| Backend | Laravel 11, PHP 8.3, Laravel Sanctum |
| Data | MySQL 8, Eloquent ORM |
| Asynchronous work | Laravel Queue, Redis or database drivers |
| Cache | Laravel Cache facade, Redis |
| Frontend | AngularJS 1.x, ngRoute, Bootstrap |
| Runtime | Docker Compose, Nginx, PHP-FPM |

## Architecture overview

The Laravel API follows a thin-controller, Service + Repository approach. Controllers coordinate HTTP concerns; form requests validate input; services hold workflows and transactions; repositories centralise persistence; Eloquent models represent relationships. Assignment work is dispatched after a task transaction commits.

```text
AngularJS / API client → Controllers → Services → Repositories → Models → MySQL
                                      ↓
                                 Cache / Queue → Redis
```

See the [Architecture guide](docs/Architecture.md) for diagrams and implementation details.

## Folder structure

```text
.
├── backend/                 # Laravel application
│   ├── app/                 # HTTP, domain services, repositories, jobs, models
│   ├── database/            # Migrations, factories, seeders
│   ├── routes/api.php       # Public API routes
│   └── tests/               # Feature and unit tests
├── frontend/                # Static AngularJS application
├── infra/                   # PHP and Nginx container configuration
├── docs/                    # Project documentation
├── postman/                 # Importable Postman collection
└── docker-compose.yml
```

## Installation

### Docker

```bash
git clone <repository-url> task-management-system
cd task-management-system
docker compose up -d --build
docker compose exec app composer install --no-interaction --prefer-dist
```

The application is available at `http://localhost:8000`; Nginx serves the frontend and forwards `/api` requests to Laravel.

### Environment

Copy the Laravel environment file and configure it for Docker before running application commands:

```bash
cd backend
cp .env.example .env
```

For Docker, configure the copied `backend/.env` with `DB_HOST=mysql`, `DB_DATABASE=task_management`, `DB_USERNAME=laravel`, `DB_PASSWORD=laravel`, `REDIS_HOST=redis`, `REDIS_CLIENT=predis`, `CACHE_STORE=redis`, and `QUEUE_CONNECTION=redis`. The project includes Predis; the PHP container does not require the native Redis extension.

### Composer, migrations, and seeders

Install dependencies and generate the application key through the Docker application container:

```bash
docker compose exec app composer install --no-interaction --prefer-dist
docker compose exec app php artisan key:generate --force
```

### Demo database and seeded data

Database records are **not** stored in GitHub. The repository includes migrations and seeders, so each developer creates local data after cloning. The command below recreates the local database and seeds the Demo Manager account plus exactly 10 professional English sample tasks:

```bash
docker compose exec app php artisan migrate:fresh --seed --force
```

Demo credentials For Manager login:

```text
Email: demo@example.com
Password: password123
```

To restore only the English sample tasks without rebuilding the entire database:

```bash
docker compose exec app php artisan db:seed --class=TaskSeeder --force
```

### Queue worker

Run a worker after the application is configured. Task assignment is asynchronous.

```bash
docker compose exec app php artisan queue:work --tries=3 --timeout=120
```

### AngularJS frontend

The current frontend is a static AngularJS application; it has no separate npm build step. Docker Nginx serves `frontend/` automatically. For local development, run the Docker stack and open `http://localhost:8000`.

## Running the project

```bash
docker compose up -d --build
docker compose exec app composer install --no-interaction --prefer-dist
docker compose exec app php artisan key:generate --force
docker compose exec app php artisan migrate:fresh --seed --force
docker compose exec app php artisan queue:work --tries=3 --timeout=120
```

Open `http://localhost:8000` for the UI. The API base URL is `http://localhost:8000/api`.

## Testing

Run the backend suite from `backend/`:

```bash
php artisan test
```

The test suite uses factories, seeders, queue fakes where appropriate, and an in-memory SQLite configuration. Read the [Testing guide](docs/Testing.md) for coverage areas and commands.

## API overview

Public endpoints cover authentication and tasks. The assignment engine is internal and is triggered after task creation; there is no public assignment-management endpoint in the current route set.

- [API reference](docs/API.md)
- [Postman collection](postman/TaskManagement.postman_collection.json)

## Queue overview

`AssignTaskJob` evaluates active assignment rules after a task is committed. `RecomputeEligibilityJob` refreshes eligibility following rule changes. Both jobs have three attempts, a 120-second timeout, defined backoff intervals, and failure logging. See [Queue processing](docs/Queue.md).

## Redis overview

Redis caches user profiles, task detail, active workload counts, eligible users, and assignment rules. Cache invalidation is driven by Eloquent observers. See [Redis caching](docs/Redis.md).

## Rule engine

Rules filter active users by IDs, roles, departments, designations, and optional maximum active-task count. The selector then prefers the lowest workload and the oldest last assignment. See [Rule engine](docs/RuleEngine.md).

## Assumptions

- Roles are seeded before registration so a new user can receive the Employee role.
- Docker is the intended local runtime; non-Docker users provide equivalent PHP, MySQL, and Redis services.
- Seeded demo data is local only; run the documented migration/seed command after every fresh clone or database reset.
- Assignment configuration is managed through the database/application layer; a public assignment-rule API is not exposed in the current routes.
- The UI includes graceful handling for a queue-status request, but the backend currently exposes no `/api/queue-status` route.

## Documentation

Start with the [documentation index](docs/README.md):

- [Architecture](docs/Architecture.md)
- [Database](docs/Database.md)
- [API](docs/API.md)
- [Rule Engine](docs/RuleEngine.md)
- [Queue](docs/Queue.md)
- [Redis](docs/Redis.md)
- [Testing](docs/Testing.md)
- [Deployment](docs/Deployment.md)
- [Future Enhancements](docs/FutureEnhancements.md)

## Future improvements

Potential next steps include an administrative rule-builder API/UI, assignment endpoints, queue monitoring, metrics, and a modern frontend migration. See [Future Enhancements](docs/FutureEnhancements.md).

## License

This project is currently unlicensed. Add a license file before distributing or reusing it outside its intended scope.

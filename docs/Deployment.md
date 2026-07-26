# Deployment

[Documentation index](README.md) · [Queue](Queue.md) · [Redis](Redis.md)

## Docker and Docker Compose

The root `docker-compose.yml` defines these services:

| Service | Role |
| --- | --- |
| `app` | PHP-FPM Laravel application container. |
| `nginx` | HTTP server; serves AngularJS and forwards Laravel requests. |
| `mysql` | MySQL 8.4 database service. |
| `redis` | Redis 7 cache and queue service. |

Build and start the stack:

```bash
docker compose up -d --build
docker compose ps
```

Nginx publishes port `8000` by default. It serves the static frontend from `frontend/`, uses Laravel’s `public/` directory for API/PHP requests, and relies on the application's front-controller routing.

## Environment variables

Create `backend/.env` from `.env.example` and provide production secrets through your deployment system. At minimum configure:

| Area | Variables |
| --- | --- |
| Application | `APP_ENV`, `APP_KEY`, `APP_DEBUG`, `APP_URL` |
| Database | `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` |
| Cache/queue | `CACHE_STORE`, `QUEUE_CONNECTION`, `QUEUE_FAILED_DRIVER` |
| Redis | `REDIS_CLIENT`, `REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD` |
| Cache TTLs | `USER_CACHE_TTL`, `TASK_CACHE_TTL`, `ELIGIBLE_USERS_CACHE_TTL`, `ACTIVE_TASK_COUNT_CACHE_TTL`, `ASSIGNMENT_RULES_CACHE_TTL` |

For Compose, point database and Redis hosts to `mysql` and `redis`. Never commit real credentials or an environment file containing secrets.

## Application setup

Run these commands in the app container or an equivalent release environment:

```bash
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan optimize
```

Use `migrate:fresh --seed` only for disposable environments because it drops all tables.

## Queue workers

Run a long-lived worker as a supervised process:

```bash
php artisan queue:work redis --tries=3 --timeout=120
```

Use the selected connection name in place of `redis` if required. Restart workers as part of each release. Monitor `failed_jobs` and application logs; see [Queue](Queue.md).

## Redis

Redis backs caching and asynchronous queues when configured. Secure it at the network boundary, provide a password/TLS configuration if your platform requires it, size memory for queues plus cache data, and set an appropriate eviction policy. See [Redis caching](Redis.md).

## MySQL

Use durable volumes/backups, least-privilege database credentials, and a controlled migration process. The application indexes task listing, assignment selection, and audit-log access paths; retain those indexes when managing schema changes.

## Nginx and AngularJS

Nginx is the public edge in the supplied stack. Terminate TLS in Nginx or a trusted upstream load balancer, redirect HTTP to HTTPS in production, and pass the correct `APP_URL`/proxy headers to Laravel. The AngularJS frontend is static and is served directly; it has no separate build artefact in the current repository.

## Production checklist

- [ ] Set `APP_ENV=production` and `APP_DEBUG=false`.
- [ ] Generate and securely store a unique `APP_KEY`.
- [ ] Configure production MySQL, Redis, mail/log destinations, and backups.
- [ ] Run `php artisan migrate --force` and intended seeders.
- [ ] Run at least one supervised queue worker with the documented retry/timeout settings.
- [ ] Configure HTTPS, trusted proxies, CORS as required, and rate-limit-aware upstreams.
- [ ] Build/restart containers, clear stale configuration/cache, and restart queue workers.
- [ ] Verify `php artisan test`, `php artisan route:list`, API authentication, task creation, and a background assignment.
- [ ] Monitor logs, failed jobs, Redis memory, queue depth, and database health.

Next: [Testing](Testing.md) · [Future Enhancements](FutureEnhancements.md)

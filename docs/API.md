# API reference

[Documentation index](README.md) · [Architecture](Architecture.md) · [Postman collection](../postman/TaskManagement.postman_collection.json)

## Conventions

- **Base URL:** `http://localhost:8000/api` in the Docker development setup.
- **Content type:** `application/json`.
- **Authentication:** protected routes require `Authorization: Bearer <Sanctum token>`.
- **Success envelope:** `{ "success": true, "message": "…", "data": {} }`.
- **Failure envelope:** `{ "success": false, "message": "…", "errors": {} }`.
- **Rate limits:** authentication endpoints are limited to 5 requests/minute per IP/email; authenticated API endpoints to 60 requests/minute per user or IP.

The implemented route set contains Authentication, Profile (`auth/me`), and Task endpoints. Assignment is an internal asynchronous workflow, not a public REST resource.

## Authentication

### Register

| Item | Value |
| --- | --- |
| Method / URL | `POST /auth/register` |
| Authentication | None |
| Headers | `Content-Type: application/json` |

Request:

```json
{"name":"Jane Doe","email":"jane@example.com","password":"password","password_confirmation":"password"}
```

Success (`201`):

```json
{"success":true,"message":"Registration successful.","data":{"user":{"id":1,"name":"Jane Doe","email":"jane@example.com","role":{"id":3,"name":"Employee"}},"token":"<sanctum-token>"}}
```

Validation error (`422`):

```json
{"success":false,"message":"The given data was invalid.","errors":{"email":["The email has already been taken."]}}
```

```bash
curl -X POST http://localhost:8000/api/auth/register -H 'Content-Type: application/json' -d '{"name":"Jane Doe","email":"jane@example.com","password":"password","password_confirmation":"password"}'
```

### Login

| Item | Value |
| --- | --- |
| Method / URL | `POST /auth/login` |
| Authentication | None |
| Headers | `Content-Type: application/json` |

Request:

```json
{"email":"admin@example.com","password":"password"}
```

Success (`200`):

```json
{"success":true,"message":"Login successful.","data":{"user":{"id":1,"name":"Admin","email":"admin@example.com","role":{"id":1,"name":"Admin"}},"token":"<sanctum-token>"}}
```

Invalid credentials (`401`):

```json
{"success":false,"message":"The provided credentials are incorrect.","errors":{}}
```

```bash
curl -X POST http://localhost:8000/api/auth/login -H 'Content-Type: application/json' -d '{"email":"admin@example.com","password":"password"}'
```

### Logout

| Item | Value |
| --- | --- |
| Method / URL | `POST /auth/logout` |
| Authentication | Sanctum bearer token |
| Headers | `Authorization: Bearer <token>` |

Success (`200`): `{"success":true,"message":"Logout successful.","data":null}`

```bash
curl -X POST http://localhost:8000/api/auth/logout -H 'Authorization: Bearer <token>'
```

## Profile

### Current user

| Item | Value |
| --- | --- |
| Method / URL | `GET /auth/me` |
| Authentication | Sanctum bearer token |
| Headers | `Authorization: Bearer <token>` |

Success (`200`):

```json
{"success":true,"message":"Authenticated user retrieved successfully.","data":{"id":1,"name":"Admin","email":"admin@example.com","role":{"id":1,"name":"Admin"}}}
```

```bash
curl http://localhost:8000/api/auth/me -H 'Authorization: Bearer <token>'
```

## Tasks

All task endpoints require a bearer token. Active users can list and create tasks; Admin and Manager roles can manage all tasks, while Employees can view, update, and delete only tasks they created.

### List tasks

| Item | Value |
| --- | --- |
| Method / URL | `GET /tasks` |
| Authentication | Sanctum bearer token |
| Query | `status`, `priority`, `search`, `due_from`, `due_to`, `sort_by`, `sort_direction`, `per_page` |

Allowed values: `status` = `pending`, `in_progress`, `completed`; `priority` = `low`, `medium`, `high`; `sort_by` = `title`, `status`, `priority`, `due_at`, `created_at`, `updated_at`; `sort_direction` = `asc`, `desc`; `per_page` = 1–100.

Success (`200`) is a paginated resource collection with `data`, `links`, `meta`, `success`, and `message`:

```json
{"success":true,"message":"Tasks retrieved successfully.","data":[{"id":1,"title":"Prepare report","status":"pending","priority":"high","creator":{"id":1,"name":"Admin","email":"admin@example.com"}}],"links":{},"meta":{}}
```

```bash
curl 'http://localhost:8000/api/tasks?status=pending&search=report&sort_by=due_at&sort_direction=asc&per_page=15' -H 'Authorization: Bearer <token>'
```

### Create task

| Item | Value |
| --- | --- |
| Method / URL | `POST /tasks` |
| Authentication | Sanctum bearer token |
| Headers | `Authorization: Bearer <token>`, `Content-Type: application/json` |

Request fields: `title` is required (maximum 255 characters); `description`, `due_at` are optional; `status` and `priority` are optional constrained values above.

```json
{"title":"Prepare report","description":"Monthly summary","status":"pending","priority":"high","due_at":"2026-08-01 17:00:00"}
```

Success (`201`):

```json
{"success":true,"message":"Task created successfully.","data":{"id":1,"title":"Prepare report","description":"Monthly summary","status":"pending","priority":"high","due_at":"2026-08-01T17:00:00.000000Z","completed_at":null,"creator":{"id":1,"name":"Admin","email":"admin@example.com"}}}
```

```bash
curl -X POST http://localhost:8000/api/tasks -H 'Authorization: Bearer <token>' -H 'Content-Type: application/json' -d '{"title":"Prepare report","priority":"high"}'
```

### View task

| Item | Value |
| --- | --- |
| Method / URL | `GET /tasks/{task}` |
| Authentication | Sanctum bearer token |

Success (`200`) returns the task resource shown above. A missing task returns `404`; a disallowed employee request returns `403`.

```bash
curl http://localhost:8000/api/tasks/1 -H 'Authorization: Bearer <token>'
```

### Update task

| Item | Value |
| --- | --- |
| Method / URL | `PUT` or `PATCH /tasks/{task}` |
| Authentication | Sanctum bearer token |

All creation fields are accepted as optional update fields; supplied `title` must be non-empty and no longer than 255 characters.

```json
{"status":"in_progress","priority":"medium"}
```

Success (`200`): `{"success":true,"message":"Task updated successfully.","data":{…task resource…}}`

```bash
curl -X PATCH http://localhost:8000/api/tasks/1 -H 'Authorization: Bearer <token>' -H 'Content-Type: application/json' -d '{"status":"in_progress"}'
```

### Delete task

| Item | Value |
| --- | --- |
| Method / URL | `DELETE /tasks/{task}` |
| Authentication | Sanctum bearer token |

Success (`200`): `{"success":true,"message":"Task deleted successfully.","data":null}`

```bash
curl -X DELETE http://localhost:8000/api/tasks/1 -H 'Authorization: Bearer <token>'
```

## Standard error responses

| Status | Condition | Shape |
| --- | --- | --- |
| 401 | Missing/invalid Sanctum token | `success: false`, authentication message, `errors: {}` |
| 403 | Policy denial | `success: false`, authorization message, `errors: {}` |
| 404 | Missing route/model | `success: false`, not-found message, `errors: {}` |
| 422 | Request validation error | `success: false`, validation message, field errors |
| 429 | Rate limit exceeded | `success: false`, throttling message, `errors: {}` |
| 500 | Query or unexpected failure | `success: false`, safe server-error message, `errors: {}` |

Next: [Rule Engine](RuleEngine.md) · [Testing](Testing.md)

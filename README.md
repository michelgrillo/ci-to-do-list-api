# To-Do List API

REST API for managing tasks, built with CodeIgniter 4. It provides CRUD operations using JSON.

## Requirements

- PHP 8.2 or later, with the `intl`, `mbstring`, and `mysqli` extensions.
- Composer.
- MySQL or MariaDB.

## Installation and setup

From the project directory:

```powershell
composer install
if (-not (Test-Path .env)) { Copy-Item env .env }
```

Edit `.env` and configure the environment and database connection. For example:

```ini
CI_ENVIRONMENT = development
app.baseURL = 'http://localhost:8080/'

database.default.hostname = localhost
database.default.database = todo_list
database.default.username = root
database.default.password =
database.default.DBDriver = MySQLi
database.default.port = 3306
database.default.charset = utf8mb4
database.default.DBCollat = utf8mb4_general_ci
```

Create the configured database (for example, in phpMyAdmin or MySQL):

```sql
CREATE DATABASE todo_list CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
```

Run the migrations and start the development server:

```powershell
php spark migrate
php spark serve
```

The API will be available at `http://localhost:8080`. To run the tests:

```powershell
composer test
```

## API

Routes are under `/api`. Requests with a body must use `Content-Type: application/json`. Validation errors return HTTP `400`; missing resources return HTTP `404`.

All API errors use the same JSON envelope:

```json
{
  "status": 400,
  "error": 400,
  "messages": {
    "status": "The status field is invalid."
  }
}
```

`messages` contains field-specific validation errors or a general `error` message. Unexpected server errors return HTTP `500` with a generic message; internal database details are written to the application log instead of being exposed in the response.

| Method | Route | Description |
| --- | --- | --- |
| `GET` | `/api/tasks` | Lists tasks. Accepts optional `status` and `responsible` filters, and pagination with `page`. |
| `GET` | `/api/tasks/{id}` | Retrieves a task by ID. |
| `POST` | `/api/tasks` | Creates a task. |
| `PUT` | `/api/tasks/{id}` | Updates an existing task. |
| `DELETE` | `/api/tasks/{id}` | Deletes a task. |

### List and filter tasks

```http
GET /api/tasks?status=in_progress&responsible=Ana&page=1
```

The list uses pages of five tasks. The response contains `data`, `meta` (including the current page, total count, and number of pages), and `links`.

### Create a task

`title`, `responsible`, `status`, `created_at`, and `due_date` are required. `description` and `updated_at` are optional. Allowed statuses are `open`, `in_progress`, and `done`. The title must be unique.

```http
POST /api/tasks
Content-Type: application/json
```

```json
{
  "title": "Prepare report",
  "description": "Gather project data",
  "responsible": "Ana",
  "status": "open",
  "created_at": "2026-11-15 09:00:00",
  "due_date": "2026-12-01"
}
```

On success, the response has HTTP status `201` and includes the generated ID. Task responses expose `id`, `title`, `status`, `responsible`, `due_date`, `created_at`, and `updated_at`; they currently do not include `description`.

### Retrieve, update, and delete a task

```http
GET /api/tasks/1
```

`PUT` accepts a JSON object with the fields to update, for example:

```http
PUT /api/tasks/1
Content-Type: application/json
```

```json
{
  "status": "done"
}
```

`DELETE /api/tasks/1` deletes the task and returns the deleted ID.

## Design decisions

- The application is a JSON REST API built with CodeIgniter 4. Routes delegate to a task controller, model, and transformer.
- Tasks can be filtered by `status` and `responsible`; list results are paginated in groups of five.
- Valid task statuses are `open`, `in_progress`, and `done`.
- Task titles are unique. The model validates uniqueness for useful client feedback, while the database constraint protects stored data.
- `PUT /api/tasks/{id}` supports partial updates for the editable task fields. The API validates the supplied fields and manages timestamps through the model.
- API errors use CodeIgniter's standard JSON envelope (`status`, `error`, and `messages`). Validation failures return `400`, missing tasks return `404`, and unexpected database failures return a generic `500` response while details are logged server-side.
- PHPUnit feature tests exercise the HTTP endpoints using the test database.

## Development history

The following commits capture the main implementation milestones:

- `64842c9` and `7b1f87c` — connected the task API routes to the controller and added the controller and transformer.
- `b53f9a5` and `8448fd4` — aligned task validation and status defaults.
- `84643e9` and `aab764a` — configured PHPUnit and added CRUD and validation tests.
- `92b434b` — improved API validation and error handling.
- `530663b` — enforced unique task titles in model validation and added a duplicate-title test.

## AI-assisted development

An AI coding assistant was used to help review and implement API changes, draft this documentation, and create and run some PHPUnit tests. Test results were reviewed during development; run the full suite locally with `composer test`.

## Potential improvements

- Add authentication and authorization to protect tasks and scope them to users.
- Publish an OpenAPI/Swagger specification and examples of error responses.
- Expand the tests to cover filters, pagination, duplicates, and edge cases.
- Review migrations and indexes across all supported database engines.

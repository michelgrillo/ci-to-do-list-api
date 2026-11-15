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

## Potential improvements

- Add authentication and authorization to protect tasks and scope them to users.
- Publish an OpenAPI/Swagger specification and examples of error responses.
- Expand the tests to cover filters, pagination, duplicates, and edge cases.
- Review migrations and indexes across all supported database engines.

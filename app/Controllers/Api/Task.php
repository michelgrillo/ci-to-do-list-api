<?php

namespace App\Controllers\Api;

use CodeIgniter\Api\ResponseTrait;
use App\Controllers\BaseController;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\HTTP\ResponseInterface;

class Task extends BaseController
{
    use ResponseTrait;

    public function __construct()
    {
        $this->taskModel = model('App\Models\TaskModel');
    }

    /**
     * Get a task by ID or list tasks with optional filters for status and responsible.
     *
     * GET /api/tasks
     * GET /api/tasks/{id}
     * @param int|null $id The ID of the task to retrieve.
     * @return ResponseInterface The response containing the task or list of tasks.
     */
    public function get(?int $id = null)
    {
        $model = $this->taskModel;
        $transformer = new \App\Transformers\TaskTransformer();

        // If an ID is provided, fetch a single record
        if ($id !== null) {
            try {
                $task = $model->find($id);
            } catch (DatabaseException $e) {
                return $this->failDatabaseOperation('retrieve', $e);
            }

            if (! $task) {
                return $this->failNotFound('Task not found');
            }

            return $this->respond($transformer->transform($task));
        }

        // Otherwise, filter by status and responsible if specified
        $status = $this->request->getGet('status');
        $responsible = $this->request->getGet('responsible');
        if ($status) {
            $model->where('status', $status);
        }
        if ($responsible) {
            $model->where('responsible', $responsible);
        }

        return $this->paginate($model, 5, transformWith: \App\Transformers\TaskTransformer::class);
    }

    /**
     * Update a task
     *
     * PUT /api/tasks/{id}
     * @param int $id The ID of the task to update
     * @return ResponseInterface The response containing the updated task.
     */
    public function update(int $id): ResponseInterface
    {
        if ($id <= 0) {
            return $this->failValidationErrors(['id' => 'The ID must be a positive integer.']);
        }

        $data = $this->getJsonObject();
        if ($data instanceof ResponseInterface) {
            return $data;
        }

        $allowedFields = ['title', 'description', 'responsible', 'status', 'due_date'];
        $unknownFields = array_diff(array_keys($data), $allowedFields);
        if ($unknownFields !== []) {
            $errors = [];
            foreach ($unknownFields as $field) {
                $errors[$field] = 'This field cannot be updated.';
            }

            return $this->failValidationErrors($errors);
        }

        $rules = [
            'title'       => 'if_exist|required|string|max_length[255]',
            'description' => 'if_exist|permit_empty|string|max_length[1000]',
            'responsible' => 'if_exist|required|string|max_length[255]',
            'status'      => 'if_exist|required|in_list[open,in_progress,done]',
            'due_date'    => 'if_exist|required|string|valid_date[Y-m-d]',
        ];

        if (! $this->validate($rules, $data)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $model = $this->taskModel;

        try {
            if (! $model->find($id)) {
                return $this->failNotFound('Task not found');
            }

            if (! $model->update($id, $data)) {
                $errors = $model->errors();
                if ($errors !== []) {
                    return $this->failValidationErrors($errors);
                }

                log_message('error', 'Task update failed without model validation errors.');

                return $this->failServerError('Unable to update task.');
            }

            $updatedTask = $model->find($id);
            if (! $updatedTask) {
                log_message('error', 'Updated task {id} could not be retrieved.', ['id' => $id]);

                return $this->failServerError('Unable to retrieve the updated task.');
            }

            return $this->respond((new \App\Transformers\TaskTransformer())->transform($updatedTask));
        } catch (DatabaseException $e) {
            return $this->failDatabaseOperation('update', $e);
        }
    }

    /**
     * Create a new task
     *
     * POST /api/tasks
     * @return ResponseInterface The response containing the created task.
     */
    public function create(): ResponseInterface
    {
        $data = $this->getJsonObject();
        if ($data instanceof ResponseInterface) {
            return $data;
        }

        $rules = [
            'title' => 'required|string|max_length[255]',
            'description' => 'permit_empty|max_length[1000]',
            'responsible' => 'required|string|max_length[255]',
            'status' => 'required|in_list[open,in_progress,done]',
            'created_at' => 'required|date',
            'updated_at' => 'permit_empty|date',
            'due_date' => 'required|date',
        ];

        if (! $this->validate($rules, $data)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $model = $this->taskModel;

        try {
            $id = $model->insert($data);
            if ($id === false) {
                $errors = $model->errors();
                if ($errors !== []) {
                    return $this->failValidationErrors($errors);
                }

                log_message('error', 'Task creation failed without model validation errors.');

                return $this->failServerError('Unable to create task.');
            }

            $newTask = $model->find($id);
            if (! $newTask) {
                log_message('error', 'Created task {id} could not be retrieved.', ['id' => $id]);

                return $this->failServerError('Unable to retrieve the created task.');
            }

            return $this->respondCreated((new \App\Transformers\TaskTransformer())->transform($newTask));
        } catch (DatabaseException $e) {
            return $this->failDatabaseOperation('create', $e);
        }
    }

    /**
     * Delete a task
     *
     * DELETE /api/tasks/{id}
     * @param int $id The ID of the task to delete
     * @return ResponseInterface The response indicating the result of the deletion
     */
    public function delete(int $id): ResponseInterface
    {
        if ($id <= 0) {
            return $this->failValidationErrors(['id' => 'The ID must be a positive integer.']);
        }

        $model = $this->taskModel;

        try {
            if (! $model->find($id)) {
                return $this->failNotFound('Task not found');
            }

            if (! $model->delete($id)) {
                log_message('error', 'Task deletion failed for ID {id}.', ['id' => $id]);

                return $this->failServerError('Unable to delete task.');
            }

            return $this->respondDeleted(['id' => $id]);
        } catch (DatabaseException $e) {
            return $this->failDatabaseOperation('delete', $e);
        }
    }

    /**
     * @return array<string, mixed>|ResponseInterface
     */
    private function getJsonObject(): array|ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
        } catch (HTTPException) {
            return $this->failValidationErrors(['body' => 'The request body must contain valid JSON.']);
        }

        if (! $data instanceof \stdClass || get_object_vars($data) === []) {
            return $this->failValidationErrors(['body' => 'The request body must be a non-empty JSON object.']);
        }

        return get_object_vars($data);
    }

    private function failDatabaseOperation(string $operation, DatabaseException $exception): ResponseInterface
    {
        log_message('error', 'Task {operation} failed: {exception}', [
            'operation' => $operation,
            'exception' => $exception->getMessage(),
        ]);

        return $this->failServerError('An unexpected error occurred.');
    }
}

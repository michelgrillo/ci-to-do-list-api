<?php

namespace App\Controllers\Api;

use CodeIgniter\Api\ResponseTrait;
use App\Controllers\BaseController;
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
            $task = $model->find($id);

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
        $data = $this->request->getJSON(true);

        $rules = [
            'id' => 'required|integer',
        ];

        // check if the provided ID is a valid integer
        if (! is_int($id) || $id <= 0) {
            return $this->failValidationErrors(['id' => 'The ID must be a positive integer.']);
        }

        $model = $this->taskModel;

        if (! $model->find($id)) {
            return $this->failNotFound('Task not found');
        }

        $model->update($id, $data);

        $updatedTask = $model->find($id);

        return $this->respond((new \App\Transformers\TaskTransformer())->transform($updatedTask));
    }

    /**
     * Create a new task
     *
     * POST /api/tasks
     * @return ResponseInterface The response containing the created task.
     */
    public function create(): ResponseInterface
    {
        $data = $this->request->getJSON(true);

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
            $model->insert($data);
            $newTask = $model->find($model->insertID());

            return $this->respondCreated((new \App\Transformers\TaskTransformer())->transform($newTask));
        } catch (\Exception $e) {
            return $this->failInternalError($e->getMessage());
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
        $model = $this->taskModel;

        if (! $model->find($id)) {
            return $this->failNotFound('Task not found');
        }

        $model->delete($id);

        return $this->respondDeleted(['id' => $id]);
    }
}

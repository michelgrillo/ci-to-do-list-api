<?php

namespace App\Transformers;

use CodeIgniter\API\BaseTransformer;

class TaskTransformer extends BaseTransformer
{
    /**
     * Transform the resource into an array.
     *
     * @param mixed $resource
     *
     * @return array<string, mixed>
     */
    public function toArray(mixed $resource): array
    {
        return [
            'id' => $resource['id'] ?? null,
            'title' => trim($resource['title'] ?? '') ?: null,
            'status' => trim($resource['status'] ?? '') ?: null,
            'responsible' => trim($resource['responsible'] ?? '') ?: null,
            'due_date' => trim($resource['due_date'] ?? '') ?: null,
            'created_at' => trim($resource['created_at'] ?? '') ?: null,
            'updated_at' => trim($resource['updated_at'] ?? '') ?: null,
        ];
    }
}

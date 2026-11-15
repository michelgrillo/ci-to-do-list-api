<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class TaskSeeder extends Seeder
{
    public function run()
    {
        // Inserting 10 sample tasks into the 'tasks' table
        $data = [];
        for ($i = 1; $i <= 10; $i++) {
            $data[] = [
                'title' => "Task $i",
                'description' => "Description for Task $i",
                'status' => 'open',
                'created_at' => date('Y-m-d H:i:s'),
                'responsible' => "Responsible for Task $i",
                'due_date' => date('Y-m-d', strtotime("+$i days")),
            ];
        }
        try {
            $this->db->table('tasks')->insertBatch($data);
        } catch (\Exception $e) {
            log_message('error', 'Failed to insert tasks: ' . $e->getMessage());
        }
    }
}

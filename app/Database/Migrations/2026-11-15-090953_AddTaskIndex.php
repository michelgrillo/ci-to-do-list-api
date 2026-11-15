<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTaskIndex extends Migration
{
    public function up()
    {
        // set up index for status and responsible columns on 'tasks' table
        $this->forge->addKey(['responsible', 'status'], false, false, 'idx_responsible_status');
        $this->forge->addKey('id', true); // Ensure primary key exists if not already
        $this->forge->processIndexes('tasks');
    }

    public function down()
    {
        // remove index for status and responsible columns on 'tasks' table
        $this->forge->dropKey('tasks', 'idx_responsible_status');
    }
}

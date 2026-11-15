<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUniqueTitleTask extends Migration
{
    public function up(): void
    {
        $this->forge->addUniqueKey('title', 'unique_title');
        $this->forge->processIndexes('tasks');
    }

    public function down(): void
    {
        $this->forge->dropKey('tasks', 'unique_title');
    }
}

<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

/**
 * @internal
 */
final class TaskApiTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate = false;

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->db === null) {
            $this->db = Database::connect('tests');
            $this->db->initialize();
        }

        $forge = Database::forge('tests');
        $forge->dropTable('tasks', true);
        $forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 5,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'unique'     => true,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 16,
                'default'    => 'pending',
            ],
            'responsible' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'due_date' => [
                'type' => 'DATE',
            ],
            'created_at' => [
                'type' => 'DATETIME',
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $forge->addKey('id', true);
        $forge->createTable('tasks');
    }

    public function testTaskCanBeCreatedReadUpdatedAndDeleted(): void
    {
        $task = [
            'title'       => 'API test task',
            'description' => 'Created by the API test',
            'status'      => 'open',
            'responsible' => 'API tester',
            'due_date'    => '2026-12-01',
            'created_at'  => '2026-11-15 09:00:00',
        ];

        $created = $this->withBodyFormat('json')->post('api/tasks', $task);
        $created->assertStatus(201);

        $createdTask = json_decode($created->getJSON());
        $this->assertIsObject($createdTask);
        $this->assertSame($task['title'], $createdTask->title);
        $this->assertSame($task['status'], $createdTask->status);

        $id = (int) $createdTask->id;
        $this->assertGreaterThan(0, $id);

        $read = $this->get('api/tasks/' . $id);
        $read->assertStatus(200);
        $read->assertJSONFragment([
            'id'    => $id,
            'title' => $task['title'],
        ]);

        $updated = $this->withBodyFormat('json')->put('api/tasks/' . $id, [
            'title'  => 'Updated API test task',
            'status' => 'done',
        ]);
        $updated->assertStatus(200);
        $updated->assertJSONFragment([
            'title'  => 'Updated API test task',
            'status' => 'done',
        ]);

        $deleted = $this->delete('api/tasks/' . $id);
        $deleted->assertStatus(200);
        $deleted->assertJSONFragment(['id' => $id]);

        $this->get('api/tasks/' . $id)->assertStatus(404);
    }

    public function testCreateRejectsInvalidTaskData(): void
    {
        $response = $this->withBodyFormat('json')->post('api/tasks', [
            'title' => '',
        ]);

        $response->assertStatus(400);
        $responseBody = json_decode($response->getJSON(), true);
        $this->assertNotEmpty($responseBody['messages']);
    }
}

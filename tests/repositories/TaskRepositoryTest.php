<?php

namespace Tests\Repositories;

use PHPUnit\Framework\TestCase;
use Repositories\TaskRepository;
use PDO;
use PDOStatement;
use Connections\DbConnection;

class TaskRepositoryTest extends TestCase
{
    private $dbConnectionMock;
    private $readPdoMock;
    private $writePdoMock;
    private $taskRepository;

    protected function setUp(): void
    {
        // Mock the DB connection and PDO instances
        $this->dbConnectionMock = $this->createMock(DbConnection::class);
        $this->readPdoMock = $this->createMock(PDO::class);
        $this->writePdoMock = $this->createMock(PDO::class);

        // Configure the mocks to return the mock PDO objects
        $this->dbConnectionMock->method('getReadConnection')->willReturn($this->readPdoMock);
        $this->dbConnectionMock->method('getWriteConnection')->willReturn($this->writePdoMock);

        // Instantiate TaskRepository with the mocked DbConnection
        $this->taskRepository = new TaskRepository($this->dbConnectionMock);
    }

    public function testGetAllTasksReturnsTasks(): void
    {
        // Prepare mock result
        $mockStmt = $this->createMock(PDOStatement::class);
        $mockStmt->method('fetchAll')->willReturn([
            ['id' => '1', 'description' => 'Test task 1'],
            ['id' => '2', 'description' => 'Test task 2']
        ]);

        // Mock the read PDO to return the prepared statement
        $this->readPdoMock->method('query')->willReturn($mockStmt);

        // Test the method
        $tasks = $this->taskRepository->getAllTasks();

        // Assertions
        $this->assertCount(2, $tasks);
        $this->assertEquals('Test task 1', $tasks[0]['description']);
    }

    public function testAddTaskInsertsTask(): void
    {
        // Prepare the statement mock for insert
        $mockStmt = $this->createMock(PDOStatement::class);
        $mockStmt->expects($this->once())->method('execute')->with([
            'id' => '1234-uuid',
            'description' => 'Test task'
        ]);

        // Mock the write PDO to return the prepared statement
        $this->writePdoMock->method('prepare')->willReturn($mockStmt);

        // Test the method
        $this->taskRepository->addTask('1234-uuid', 'Test task');
    }

    public function testGetTaskByUuidReturnsTask(): void
    {
        // Prepare mock result
        $mockStmt = $this->createMock(PDOStatement::class);
        $mockStmt->method('fetch')->willReturn(['id' => '1234-uuid', 'description' => 'Test task']);

        // Mock the read PDO to return the prepared statement
        $this->readPdoMock->method('prepare')->willReturn($mockStmt);

        // Test the method
        $task = $this->taskRepository->getTaskByUuid('1234-uuid');

        // Assertions
        $this->assertNotNull($task);
        $this->assertEquals('Test task', $task['description']);
    }

    public function testDeleteTaskByUuidReturnsTrue(): void
    {
        // Prepare the statement mock for delete
        $mockStmt = $this->createMock(PDOStatement::class);
        $mockStmt->method('execute')->with(['uuid' => '1234-uuid']);
        $mockStmt->method('rowCount')->willReturn(1);

        // Mock the write PDO to return the prepared statement
        $this->writePdoMock->method('prepare')->willReturn($mockStmt);

        // Test the method
        $result = $this->taskRepository->deleteTaskByUuid('1234-uuid');

        // Assertions
        $this->assertTrue($result);
    }

    public function testDeleteTaskByUuidReturnsFalseWhenNoRowsDeleted(): void
    {
        // Prepare the statement mock for delete
        $mockStmt = $this->createMock(PDOStatement::class);
        $mockStmt->method('execute')->with(['uuid' => '1234-uuid']);
        $mockStmt->method('rowCount')->willReturn(0);

        // Mock the write PDO to return the prepared statement
        $this->writePdoMock->method('prepare')->willReturn($mockStmt);

        // Test the method
        $result = $this->taskRepository->deleteTaskByUuid('1234-uuid');

        // Assertions
        $this->assertFalse($result);
    }

    public function testGetTaskByUuidReturnsNullWhenNotFound(): void
    {
        // Prepare mock result
        $mockStmt = $this->createMock(PDOStatement::class);
        $mockStmt->method('fetch')->willReturn(false);

        // Mock the read PDO to return the prepared statement
        $this->readPdoMock->method('prepare')->willReturn($mockStmt);

        // Test the method
        $task = $this->taskRepository->getTaskByUuid('1234-uuid');

        // Assertions
        $this->assertNull($task);
    }
}
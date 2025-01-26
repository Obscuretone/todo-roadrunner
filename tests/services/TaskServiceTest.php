<?php

use PHPUnit\Framework\TestCase;
use Services\TaskService;
use Repositories\TaskRepository;
use Connections\RedisConnection;

class TaskServiceTest extends TestCase
{
    private $taskService;
    private $taskRepositoryMock;
    private $redisMock;

    protected function setUp(): void
    {
        // Create a mock TaskRepository
        $this->taskRepositoryMock = $this->createMock(TaskRepository::class);

        // Create a mock RedisConnection and Redis object
        $redisConnectionMock = $this->createMock(RedisConnection::class);
        $this->redisMock = $this->createMock(\Redis::class);
        $redisConnectionMock->redis = $this->redisMock;

        // Instantiate TaskService with the mocked dependencies
        $this->taskService = new TaskService($this->taskRepositoryMock, $redisConnectionMock);
    }

    public function testGetTasksFromCache(): void
    {
        // Mock Redis behavior (cache hit)
        $this->redisMock->method('exists')->with('tasks')->willReturn(true);
        $this->redisMock->method('get')->with('tasks')->willReturn(json_encode([
            ['id' => '1', 'description' => 'Task 1'],
            ['id' => '2', 'description' => 'Task 2']
        ]));

        // Call the method under test
        $tasks = $this->taskService->getTasks();

        // Assert the expected result
        $this->assertIsArray($tasks);
        $this->assertCount(2, $tasks);
        $this->assertSame('Task 1', $tasks[0]['description']);
    }

    public function testGetTasksFromDatabaseWhenCacheMiss(): void
    {
        // Mock Redis behavior (cache miss)
        $this->redisMock->method('exists')->with('tasks')->willReturn(false);

        // Mock TaskRepository behavior
        $this->taskRepositoryMock->method('getAllTasks')->willReturn([
            ['id' => '1', 'description' => 'Task 1'],
            ['id' => '2', 'description' => 'Task 2']
        ]);

        // Mock Redis `set` to simulate caching tasks
        $this->redisMock->method('set');

        // Call the method under test
        $tasks = $this->taskService->getTasks();

        // Assert the expected result
        $this->assertIsArray($tasks);
        $this->assertCount(2, $tasks);
        $this->assertSame('Task 1', $tasks[0]['description']);
    }

    public function testAddTaskInvalidatesCache(): void
    {
        // Expect Redis to delete the cache key for tasks
        $this->redisMock->expects($this->once())->method('del')->with('tasks');

        // Mock TaskRepository behavior
        $this->taskRepositoryMock->expects($this->once())
            ->method('addTask')
            ->with($this->isType('string'), 'New Task');

        // Call the method under test
        $this->taskService->addTask('New Task');
    }
}
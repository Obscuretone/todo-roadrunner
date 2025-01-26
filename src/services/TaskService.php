<?php

// services/TaskService.php

namespace Services;

use Repositories\TaskRepository;
use Connections\RedisConnection;
use Redis;
use Exception;
use Ramsey\Uuid\Guid\Guid;

class TaskService
{
    private $taskRepository;
    private $redis;

    // Accept dependencies via constructor
    public function __construct(TaskRepository $taskRepository, RedisConnection $redisConnection)
    {
        $this->taskRepository = $taskRepository;
        $this->redis = $redisConnection->redis;
    }

    // Get all tasks with caching
    public function getTasks(): array
    {
        $cacheKey = 'tasks';  // Cache key for tasks

        try {
            // Check if tasks are in the cache
            if ($this->redis->exists($cacheKey)) {
                // Return the cached tasks
                return json_decode($this->redis->get($cacheKey), true);
            }

            // Fetch tasks from the repository
            $tasks = $this->taskRepository->getAllTasks();

            // Cache the tasks for 60 seconds (or any desired expiration time)
            $this->redis->set($cacheKey, json_encode($tasks), 60);  // Cache expires in 60 seconds

            return $tasks;
        } catch (Exception $e) {
            error_log("Error fetching tasks: " . $e->getMessage());
            throw $e;
        }
    }

    // Add a new task and invalidate cache
    public function addTask(string $description)
    {
        try {
            // Generate a UUID for the new task
            $uuid = Guid::uuid4()->toString();  // Generate UUID in PHP

            // Add the task to the repository
            $this->taskRepository->addTask($uuid, $description);

            // Invalidate the cache for tasks
            $this->redis->del('tasks');  // Delete the cached tasks list

        } catch (Exception $e) {
            error_log("Error adding task: " . $e->getMessage());
            throw $e;
        }
    }

    // Get a single task by UUID
    public function getTaskByUuid(string $uuid): ?array
    {
        // Validate the UUID format
        if (!preg_match('/^[a-f0-9]{8}-([a-f0-9]{4}-){3}[a-f0-9]{12}$/i', $uuid)) {
            throw new \InvalidArgumentException("Invalid UUID format.");
        }

        try {
            // Fetch the task by UUID using the repository
            return $this->taskRepository->getTaskByUuid($uuid);
        } catch (Exception $e) {
            error_log('Error fetching task by UUID: ' . $e->getMessage());
            throw new Exception('Error fetching task by UUID: ' . $e->getMessage());
        }
    }

    // Delete a task by UUID and invalidate cache
    public function deleteTaskByUuid(string $uuid): bool
    {
        try {
            // Validate UUID format
            if (!preg_match('/^[a-f0-9]{8}-([a-f0-9]{4}-){3}[a-f0-9]{12}$/i', $uuid)) {
                throw new \InvalidArgumentException("Invalid UUID format.");
            }

            // Delete the task using the repository
            $deleted = $this->taskRepository->deleteTaskByUuid($uuid);

            // If a row was deleted, invalidate the cache
            if ($deleted) {
                $this->redis->del('tasks');  // Delete the cached tasks list
            }

            return $deleted;
        } catch (Exception $e) {
            error_log('Error deleting task by UUID: ' . $e->getMessage());
            throw new Exception('Error deleting task by UUID: ' . $e->getMessage());
        }
    }
}
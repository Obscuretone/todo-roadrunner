<?php

// services/TaskService.php

namespace Services;

use Connections\DbConnection;
use Connections\RedisConnection;
use Redis;
use PDO;
use PDOException;
use Exception;
use Ramsey\Uuid\Guid\Guid;

class TaskService
{
    private $writePdo;  // Write connection
    private $readPdo;   // Read replica connection
    private $redis;

    // Accept dependencies via constructor
    public function __construct(DbConnection $dbConnection, RedisConnection $redisConnection)
    {
        // Assign read and write database connections
        $this->writePdo = $dbConnection->getWriteConnection();
        $this->readPdo = $dbConnection->getReadConnection();
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

            // Tasks are not cached, so fetch them from the database using the read replica
            $stmt = $this->readPdo->query("SELECT * FROM tasks ORDER BY id ASC");
            $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Cache the tasks for 60 seconds (or any desired expiration time)
            $this->redis->set($cacheKey, json_encode($tasks), 60);  // Cache expires in 60 seconds

            return $tasks;
        } catch (PDOException $e) {
            error_log("Database query failed: " . $e->getMessage());
            throw $e;
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

            // Insert the new task into the database using the write connection
            $stmt = $this->writePdo->prepare("INSERT INTO tasks (id, description) VALUES (:id, :description)");
            $stmt->execute([
                'id' => $uuid,
                'description' => $description
            ]);

            // Invalidate the cache for tasks
            $this->redis->del('tasks');  // Delete the cached tasks list

        } catch (PDOException $e) {
            error_log("Database insertion failed: " . $e->getMessage());
            throw $e;
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
            // Fetch the task by UUID using the read connection
            $stmt = $this->readPdo->prepare("SELECT * FROM tasks WHERE id = :uuid");
            $stmt->execute(['uuid' => $uuid]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);

            return $task ? $task : null;
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

            // Prepare and execute the delete query using the write connection
            $stmt = $this->writePdo->prepare("DELETE FROM tasks WHERE id = :uuid");
            $stmt->execute(['uuid' => $uuid]);

            // If a row was deleted, invalidate the cache
            if ($stmt->rowCount() > 0) {
                $this->redis->del('tasks');  // Delete the cached tasks list
                return true;
            }

            return false;
        } catch (\Exception $e) {
            error_log('Error deleting task by UUID: ' . $e->getMessage());
            throw new Exception('Error deleting task by UUID: ' . $e->getMessage());
        }
    }
}
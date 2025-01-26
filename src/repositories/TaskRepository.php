<?php

// repositories/TaskRepository.php

namespace Repositories;

use PDO;
use PDOException;
use Connections\DbConnection;
use Ramsey\Uuid\Guid\Guid;

class TaskRepository
{
    private $writePdo;
    private $readPdo;

    public function __construct(DbConnection $dbConnection)
    {

        $this->readPdo = $dbConnection->getReadConnection();
        $this->writePdo = $dbConnection->getWriteConnection();
    }

    // Get all tasks from the database
    public function getAllTasks(): array
    {
        try {
            $stmt = $this->readPdo->query("SELECT * FROM tasks ORDER BY id ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching tasks: " . $e->getMessage());
            throw $e;
        }
    }

    // Add a new task to the database
    public function addTask(string $uuid, string $description): void
    {
        try {
            $stmt = $this->writePdo->prepare("INSERT INTO tasks (id, description) VALUES (:id, :description)");
            $stmt->execute([
                'id' => $uuid,
                'description' => $description
            ]);
        } catch (PDOException $e) {
            error_log("Error inserting task: " . $e->getMessage());
            throw $e;
        }
    }

    // Get a single task by UUID
    public function getTaskByUuid(string $uuid): ?array
    {
        try {
            $stmt = $this->readPdo->prepare("SELECT * FROM tasks WHERE id = :uuid");
            $stmt->execute(['uuid' => $uuid]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log("Error fetching task by UUID: " . $e->getMessage());
            throw $e;
        }
    }

    // Delete a task by UUID
    public function deleteTaskByUuid(string $uuid): bool
    {
        try {
            $stmt = $this->writePdo->prepare("DELETE FROM tasks WHERE id = :uuid");
            $stmt->execute(['uuid' => $uuid]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error deleting task by UUID: " . $e->getMessage());
            throw $e;
        }
    }
}
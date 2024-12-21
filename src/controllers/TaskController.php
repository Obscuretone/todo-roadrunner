<?php

namespace Controllers;

use Nyholm\Psr7\Response;
use Services\TaskService;

class TaskController
{
    private TaskService $taskService;

    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    public function getTasks(): Response
    {
        try {
            $tasks = $this->taskService->getTasks();
            return new Response(200, ['Content-Type' => 'application/json'], json_encode($tasks));
        } catch (\Exception $e) {
            return new Response(500, ['Content-Type' => 'application/json'], json_encode(['message' => 'Failed to fetch tasks']));
        }
    }

    public function addTask($request): Response
    {
        $data = json_decode($request->getBody()->getContents(), true);
        
        if (isset($data['description'])) {
            try {
                $this->taskService->addTask($data['description']);
                return new Response(201, ['Content-Type' => 'application/json'], json_encode(['message' => 'Task created successfully']));
            } catch (\Exception $e) {
                return new Response(500, ['Content-Type' => 'application/json'], json_encode(['message' => 'Failed to add task']));
            }
        } else {
            return new Response(400, ['Content-Type' => 'application/json'], json_encode(['message' => 'Task description is required']));
        }
    }

    public function getTaskByUuid($request, $params): Response
    {
        $uuid = $params['uuid'];  // The UUID from the route parameter
        try {
            $task = $this->taskService->getTaskByUuid($uuid);
    
            if ($task) {
                return new Response(200, ['Content-Type' => 'application/json'], json_encode($task));
            } else {
                return new Response(404, ['Content-Type' => 'application/json'], json_encode(['message' => 'Task not found']));
            }
        } catch (\InvalidArgumentException $e) {
            return new Response(400, ['Content-Type' => 'application/json'], json_encode(['message' => $e->getMessage()]));
        } catch (\Exception $e) {
            // Log the exception message for debugging
            error_log('Exception while retrieving task by UUID: ' . $e->getMessage());
            return new Response(500, ['Content-Type' => 'application/json'], json_encode(['message' => 'Error retrieving task by UUID: ' . $e->getMessage()]));
        }
    }

    // controllers/TaskController.php

    public function deleteTaskByUuid($request, $params): Response
    {
        $uuid = $params['uuid'];  // Extract the UUID from the URL parameters

        try {
            // Call the service to delete the task
            $isDeleted = $this->taskService->deleteTaskByUuid($uuid);

            if ($isDeleted) {
                return new Response(200, ['Content-Type' => 'application/json'], json_encode(['message' => 'Task deleted successfully']));
            } else {
                return new Response(404, ['Content-Type' => 'application/json'], json_encode(['message' => 'Task not found']));
            }
        } catch (\Exception $e) {
            // Handle any errors during the deletion
            error_log('Exception while deleting task: ' . $e->getMessage());
            return new Response(500, ['Content-Type' => 'application/json'], json_encode(['message' => 'Error deleting task: ' . $e->getMessage()]));
        }
    }
}
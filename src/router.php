<?php

// router.php

use Nyholm\Psr7\Response;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Services\TaskService;
use Controllers\TaskController;

function createDispatcher()
{
    // Create FastRoute dispatcher and define routes
    return FastRoute\simpleDispatcher(function (RouteCollector $r) {
        $r->addRoute('GET', '/tasks', 'getTasks');
        $r->addRoute('POST', '/tasks', 'addTask');
        $r->addRoute('GET', '/tasks/{uuid}', 'getTaskByUuid');
        $r->addRoute('DELETE', '/tasks/{uuid}', 'deleteTaskByUuid');
    });
}

function handleRequest($psr7, $dispatcher, $taskController)
{
    // Wait for the next request
    try {
        $request = $psr7->waitRequest();
        if ($request === null) {
            return;
        }
    } catch (\Throwable $e) {
        // Handle errors while processing the request
        $psr7->respond(new Response(400));
        return;
    }

    // Dispatch the request and handle the corresponding response
    try {
        $method = $request->getMethod();
        $path = $request->getUri()->getPath();

        // Dispatch the request using FastRoute
        $routeInfo = $dispatcher->dispatch($method, $path);

        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                // Route not found
                $response = new Response(404, ['Content-Type' => 'application/json'], json_encode(['message' => 'Not Found']));
                break;

            case Dispatcher::METHOD_NOT_ALLOWED:
                // Method not allowed for the route
                $response = new Response(405, ['Content-Type' => 'application/json'], json_encode(['message' => 'Method Not Allowed']));
                break;

            case Dispatcher::FOUND:
                // Route matched, call the controller method directly
                $handler = $routeInfo[1]; // This will be the method name (e.g., 'getTasks')

                // Call the corresponding method on the controller
                if (method_exists($taskController, $handler)) {
                    $response = $taskController->$handler($request, $routeInfo[2]); // Pass params for UUID
                } else {
                    // If the method doesn't exist, return a 404
                    $response = new Response(404, ['Content-Type' => 'application/json'], json_encode(['message' => 'Method Not Found']));
                }

                break;
        }

        // Send the response back
        $psr7->respond($response);
    } catch (\Throwable $e) {
        // If there's an exception, return a 500 error
        $response = new Response(500, ['Content-Type' => 'application/json'], json_encode(['message' => 'Something went wrong']));
        $psr7->respond($response);
    }
}


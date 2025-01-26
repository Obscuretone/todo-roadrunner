<?php

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/router.php'; 

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Spiral\RoadRunner\Worker;
use Spiral\RoadRunner\Http\PSR7Worker;
use Services\TaskService;
use Controllers\TaskController;
use Repositories\TaskRepository;
use Connections\DbConnection;
use Connections\RedisConnection;

// Create new RoadRunner worker from global environment
$worker = Worker::create();

// Create common PSR-17 HTTP factory
$factory = new Psr17Factory();
$psr7 = new PSR7Worker($worker, $factory, $factory, $factory);

// Instantiate DbConnection and RedisConnection
$dbConnection = new DbConnection();
$redisConnection = new RedisConnection();
$taskRepository = new TaskRepository($dbConnection);

// Instantiate TaskService and inject dependencies
$taskService = new TaskService($taskRepository, $redisConnection);

// Instantiate TaskController and inject TaskService
$taskController = new TaskController($taskService);

// Create FastRoute dispatcher using the helper function
$dispatcher = createDispatcher();

// Handle the request using the helper function
while (true) {
    handleRequest($psr7, $dispatcher, $taskController);
}
<?php

namespace Connections;

use Redis;

class RedisConnection
{
    public $redis;

    public function __construct()
    {
        // Load configuration
        $config = require __DIR__ . '/../config.php';  // Adjust path if necessary

        $this->redis = new Redis();

        // Use the Redis host from the config file
        $this->redis->connect($config['redis']['host'], 6379);  // Default port for Redis is 6379

        // You can add additional configuration options for Redis connection if needed, e.g. authentication
        if (isset($config['redis']['password'])) {
            $this->redis->auth($config['redis']['password']);  // If you have password configured
        }
    }
}
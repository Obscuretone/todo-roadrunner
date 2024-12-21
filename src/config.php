<?php
return [
    'db' => [
        'write' => [
            'host' => getenv('DB_HOST'),  // Primary (write) database host
            'port' => getenv('DB_PORT'),
            'name' => getenv('DB_NAME'),
            'user' => getenv('DB_USER'),
            'password' => getenv('DB_PASSWORD'),
        ],
        'read' => [
            'host' => getenv('DB_READ_HOST'),  // Read replica database host
            'port' => getenv('DB_READ_PORT'),
            'name' => getenv('DB_NAME'),  // The same DB name for read replica
            'user' => getenv('DB_READ_USER'),
            'password' => getenv('DB_READ_PASSWORD'),
        ],
    ],
    'redis' => [
        'host' => getenv('REDIS_HOST'),
    ],
];
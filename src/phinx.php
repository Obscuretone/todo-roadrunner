<?php

// Load configuration
$config = require __DIR__ . '/config.php';  // Adjust path to config file

return [
    'paths' => [
        'migrations' => '%%PHINX_CONFIG_DIR%%/db/migrations', // Keep as is for migrations
        'seeds' => '%%PHINX_CONFIG_DIR%%/db/seeds' // Keep as is for seeds
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'development',
        'development' => [
            'adapter' => 'pgsql', // Use PostgreSQL adapter
            'host' => $config['db']['write']['host'], // Use the write DB host from the config
            'name' => $config['db']['write']['name'], // Use the DB name from the config
            'user' => $config['db']['write']['user'], // Use the DB user from the config
            'pass' => $config['db']['write']['password'], // Use the DB password from the config
            'port' => $config['db']['write']['port'], // Use the DB port from the config
            'charset' => 'utf8', // Default charset for PostgreSQL
        ]
    ],
    'version_order' => 'creation' // Keep version order as creation
];
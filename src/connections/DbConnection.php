<?php

namespace Connections;

use PDO;

class DbConnection
{
    private $writePdo;
    private $readPdo;

    public function __construct()
    {
        // Load configuration
        $config = require __DIR__ . '/../config.php';  // Adjust path if necessary

        var_dump( sprintf("pgsql:host=%s;port=%s;dbname=%s", $config['db']['read']['host'], $config['db']['read']['port'], $config['db']['read']['name']),
    );

        // Setup write connection (primary DB)
        $this->writePdo = new PDO(
            sprintf("pgsql:host=%s;port=%s;dbname=%s", $config['db']['write']['host'], $config['db']['write']['port'], $config['db']['write']['name']),
            $config['db']['write']['user'],
            $config['db']['write']['password']
        );
        $this->writePdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Enable error handling

        // Setup read connection (read replica)
        $this->readPdo = new PDO(
            sprintf("pgsql:host=%s;port=%s;dbname=%s", $config['db']['read']['host'], $config['db']['read']['port'], $config['db']['read']['name']),
            $config['db']['read']['user'],
            $config['db']['read']['password']
        );
        $this->readPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Enable error handling
    }

    /**
     * Get the write connection (primary database).
     *
     * @return PDO
     */
    public function getWriteConnection(): PDO
    {
        return $this->writePdo;
    }

    /**
     * Get the read connection (read replica).
     *
     * @return PDO
     */
    public function getReadConnection(): PDO
    {
        return $this->readPdo;
    }
}
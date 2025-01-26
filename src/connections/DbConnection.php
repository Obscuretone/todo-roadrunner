<?php

namespace Connections;

use PDO;
use Exception;

class DbConnection
{
    private $writePdo;
    private $readPdo;

    public function __construct()
    {
        // Load configuration
        $config = require __DIR__ . '/../config.php'; // Adjust path if necessary

        // Retry logic parameters
        $maxRetries = 5; // Maximum number of retries
        $retryDelay = 2; // Delay between retries in seconds

        // Setup write connection (primary DB) with retries
        $this->writePdo = $this->connectWithRetries(
            sprintf(
                "pgsql:host=%s;port=%s;dbname=%s",
                $config['db']['write']['host'],
                $config['db']['write']['port'],
                $config['db']['write']['name']
            ),
            $config['db']['write']['user'],
            $config['db']['write']['password'],
            $maxRetries,
            $retryDelay
        );

        // Setup read connection (read replica) with retries
        $this->readPdo = $this->connectWithRetries(
            sprintf(
                "pgsql:host=%s;port=%s;dbname=%s",
                $config['db']['read']['host'],
                $config['db']['read']['port'],
                $config['db']['read']['name']
            ),
            $config['db']['read']['user'],
            $config['db']['read']['password'],
            $maxRetries,
            $retryDelay
        );
    }

    /**
     * Attempt to connect to the database with retry logic.
     *
     * @param string $dsn
     * @param string $user
     * @param string $password
     * @param int $maxRetries
     * @param int $retryDelay
     * @return PDO
     * @throws Exception
     */
    private function connectWithRetries(string $dsn, string $user, string $password, int $maxRetries, int $retryDelay): PDO
    {
        $attempts = 0;

        while (true) {
            try {
                $pdo = new PDO($dsn, $user, $password);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                return $pdo; // Connection successful
            } catch (Exception $e) {
                $attempts++;

                if ($attempts >= $maxRetries) {
                    throw new Exception("Failed to connect to the database after {$maxRetries} attempts: " . $e->getMessage());
                }

                // Log or display retry attempt
                echo "Connection failed (attempt {$attempts}): " . $e->getMessage() . "\nRetrying in {$retryDelay} seconds...\n";

                // Wait before retrying
                sleep($retryDelay);
            }
        }
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
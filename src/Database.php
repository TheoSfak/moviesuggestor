<?php

namespace MovieSuggestor;

use PDO;
use PDOException;

class Database
{
    private ?PDO $connection = null;

    public function __construct(
        private string $host = 'localhost',
        private int $port = 3306,
        private string $dbname = 'moviesuggestor',
        private string $username = 'root',
        private string $password = ''
    ) {
        // Allow environment variables to override defaults
        $this->host = getenv('DB_HOST') ?: $this->host;
        $this->port = (int)(getenv('DB_PORT') ?: $this->port);
        $this->dbname = getenv('DB_NAME') ?: $this->dbname;
        $this->username = getenv('DB_USER') ?: $this->username;
        $this->password = getenv('DB_PASS') ?: $this->password;
    }

    public function connect(): PDO
    {
        if ($this->connection !== null) {
            return $this->connection;
        }

        try {
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->dbname};charset=utf8mb4";
            $this->connection = new PDO($dsn, $this->username, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            return $this->connection;
        } catch (PDOException $e) {
            // Log detailed error for debugging
            error_log("Database connection failed: " . $e->getMessage());
            // Throw generic error to prevent information disclosure
            throw new \RuntimeException("Database connection failed. Please check configuration.");
        }
    }

    public function getConnection(): ?PDO
    {
        return $this->connection;
    }
}

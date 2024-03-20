<?php

namespace App\Database;

class DB
{
    private $pdo;

    public function __construct(
        string $host = DB_HOST,
        string $username = DB_USER,
        string $password = DB_PASS,
        string $database = DB_NAME,
        string $charset = 'utf8'
    ) {
        $config = [
            'driver' => defined('DB_DRIVER') ? constant('DB_DRIVER') : 'mysql', // Default to MySQL if DB_DRIVER constant is not defined
            'host' => $host,
            'dbname' => $database,
            'username' => $username,
            'password' => $password,
            'charset' => $charset
        ];

        $this->connect($config);
    }

    private function connect(array $config)
    {
        $dsn = $this->buildDsn($config);
        $options = $this->getPDOOptions();

        try {
            $this->pdo = new \PDO($dsn, $config['username'], $config['password'], $options);
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            throw new \PDOException("Failed to connect to database: " . $e->getMessage());
        }
    }

    public function getConnection(): \PDO
    {
        if ($this->pdo instanceof \PDO) {
            return $this->pdo;
        } else {
            throw new \PDOException("Database connection has not been established.");
        }
    }

    private function buildDsn(array $config): string
    {
        $dsn = "{$config['driver']}:";
        unset($config['driver'], $config['username'], $config['password']);

        foreach ($config as $key => $value) {
            $dsn .= "$key=$value;";
        }

        // Add SSL options if enabled
        if (defined("DB_SSL") && DB_SSL) {
            $dsn .= "sslmode=require;";
            // Add CA certificate path
            $dsn .= "sslrootcert=" . constant('CA_CERT') . ";";
        }

        return $dsn;
    }

    private function getPDOOptions(): array
    {
        // You can add any default PDO options here if needed
        $options = [];
        if (defined("DB_SSL") && DB_SSL) {
            $options[\PDO::MYSQL_ATTR_SSL_CA] = constant('CA_CERT');
        }
        return $options;
    }

    public function __destruct()
    {
        $this->pdo = null;
    }
}

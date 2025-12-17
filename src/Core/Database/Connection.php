<?php
/**
 * ============================================
 * Database Connection Service
 * ============================================
 * 
 * PURPOSE:
 * Manages PDO database connections with support
 * for multiple connection configurations.
 * 
 * INPUTS:
 * - Connection configuration array
 * 
 * OUTPUTS:
 * - PDO instance
 * - Connection status
 * 
 * SIDE EFFECTS:
 * - Opens database connections
 * 
 * ============================================
 */

namespace App\Core\Database;

use PDO;
use PDOException;

class Connection
{
    private static array $instances = [];
    private array $config;
    private ?PDO $pdo = null;
    private ?string $lastError = null;

    /**
     * Create a new connection instance.
     * 
     * @param array $config Connection configuration
     */
    public function __construct(array $config)
    {
        $this->config = array_merge($this->getDefaultConfig(), $config);
    }

    /**
     * Get default configuration.
     */
    private function getDefaultConfig(): array
    {
        return [
            'driver'   => 'mysql',
            'host'     => 'localhost',
            'port'     => '3306',
            'database' => '',
            'username' => 'root',
            'password' => '',
            'charset'  => 'utf8mb4',
            'options'  => [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_TIMEOUT            => 5,
            ],
        ];
    }

    /**
     * Get a singleton connection instance.
     * 
     * @param string $name Connection name
     * @param array $config Configuration
     * @return self
     */
    public static function getInstance(string $name, array $config = []): self
    {
        if (!isset(self::$instances[$name])) {
            self::$instances[$name] = new self($config);
        }
        return self::$instances[$name];
    }

    /**
     * Establish the database connection.
     * 
     * @return PDO|null Returns PDO instance or null on failure
     */
    public function connect(): ?PDO
    {
        if ($this->pdo !== null) {
            return $this->pdo;
        }

        try {
            $dsn = $this->buildDsn();
            $password = $this->normalizePassword($this->config['password']);
            
            $this->pdo = new PDO(
                $dsn,
                $this->config['username'],
                $password,
                $this->config['options'] ?? []
            );

            $this->lastError = null;
            return $this->pdo;

        } catch (PDOException $e) {
            $this->lastError = $this->formatError($e);
            return null;
        }
    }

    /**
     * Build the DSN string.
     * 
     * @return string DSN string
     */
    private function buildDsn(): string
    {
        $dsn = "{$this->config['driver']}:host={$this->config['host']};port={$this->config['port']}";
        
        if (!empty($this->config['charset'])) {
            $dsn .= ";charset={$this->config['charset']}";
        }
        
        if (!empty($this->config['database'])) {
            $dsn .= ";dbname={$this->config['database']}";
        }

        return $dsn;
    }

    /**
     * Normalize password (handle empty strings).
     * 
     * @param mixed $password
     * @return string|null
     */
    private function normalizePassword($password): ?string
    {
        if ($password === '' || $password === null) {
            return null;
        }
        return (string) $password;
    }

    /**
     * Format error message with helpful hints.
     * 
     * @param PDOException $e
     * @return string
     */
    private function formatError(PDOException $e): string
    {
        $message = $e->getMessage();
        $hint = '';

        if (strpos($message, 'Access denied') !== false) {
            $hint = ' [Hint: Check username/password. For Laragon, try root with empty password.]';
        } elseif (strpos($message, 'Connection refused') !== false) {
            $hint = ' [Hint: MySQL server may not be running.]';
        } elseif (strpos($message, 'Unknown database') !== false) {
            $hint = ' [Hint: Database does not exist. Create it first.]';
        } elseif ($e->getCode() == 2002) {
            $hint = ' [Hint: Try 127.0.0.1 instead of localhost.]';
        }

        return "Connection failed: {$message}{$hint}";
    }

    /**
     * Get the PDO instance (connects if needed).
     * 
     * @return PDO|null
     */
    public function getPdo(): ?PDO
    {
        return $this->pdo ?? $this->connect();
    }

    /**
     * Check if connected.
     * 
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->pdo !== null;
    }

    /**
     * Get the last error message.
     * 
     * @return string|null
     */
    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Close the connection.
     */
    public function disconnect(): void
    {
        $this->pdo = null;
    }

    /**
     * Test connection without storing.
     * 
     * @param array $config
     * @return array ['success' => bool, 'message' => string]
     */
    public static function test(array $config): array
    {
        $conn = new self($config);
        $pdo = $conn->connect();

        if ($pdo) {
            $version = $pdo->query("SELECT VERSION()")->fetchColumn();
            $conn->disconnect();
            return [
                'success' => true,
                'message' => "Connected successfully. MySQL version: {$version}",
                'version' => $version,
            ];
        }

        return [
            'success' => false,
            'message' => $conn->getLastError(),
        ];
    }

    /**
     * Execute a query and return results.
     * 
     * @param string $sql SQL query
     * @param array $params Parameters for prepared statement
     * @return array|false
     */
    public function query(string $sql, array $params = []): array|false
    {
        $pdo = $this->getPdo();
        if (!$pdo) {
            return false;
        }

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Execute a statement (INSERT, UPDATE, DELETE).
     * 
     * @param string $sql SQL statement
     * @param array $params Parameters
     * @return int|false Affected rows or false
     */
    public function execute(string $sql, array $params = []): int|false
    {
        $pdo = $this->getPdo();
        if (!$pdo) {
            return false;
        }

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Get last insert ID.
     * 
     * @return string|false
     */
    public function lastInsertId(): string|false
    {
        return $this->pdo?->lastInsertId() ?? false;
    }
}


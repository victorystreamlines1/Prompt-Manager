<?php
/**
 * ============================================
 * Dashboard - Database Management Service
 * ============================================
 * 
 * PURPOSE:
 * Provides database management operations for
 * the PHP Dashboard module.
 * 
 * DEPENDENCIES:
 * - App\Core\Database\Connection
 * 
 * ============================================
 */

namespace App\Modules\Dashboard;

use App\Core\Database\Connection;
use PDO;

class DatabaseService
{
    private Connection $connection;
    private ?PDO $pdo = null;

    /**
     * Initialize the service.
     * 
     * @param array $config Database configuration
     */
    public function __construct(array $config = [])
    {
        $this->connection = new Connection($config);
    }

    /**
     * Get PDO connection.
     * 
     * @return PDO|null
     */
    private function getPdo(): ?PDO
    {
        if (!$this->pdo) {
            $this->pdo = $this->connection->connect();
        }
        return $this->pdo;
    }

    /**
     * Get last error message.
     * 
     * @return string|null
     */
    public function getLastError(): ?string
    {
        return $this->connection->getLastError();
    }

    /**
     * Test connection.
     * 
     * @return array ['success' => bool, 'message' => string]
     */
    public function testConnection(): array
    {
        $pdo = $this->getPdo();
        
        if (!$pdo) {
            return [
                'success' => false,
                'message' => $this->connection->getLastError()
            ];
        }

        try {
            $version = $pdo->query("SELECT VERSION()")->fetchColumn();
            return [
                'success' => true,
                'message' => "Connected! MySQL version: {$version}",
                'version' => $version
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    // ========================================
    // DATABASE OPERATIONS
    // ========================================

    /**
     * List all databases.
     * 
     * @return array
     */
    public function listDatabases(): array
    {
        $pdo = $this->getPdo();
        if (!$pdo) return [];

        try {
            $result = $pdo->query("SHOW DATABASES")->fetchAll(PDO::FETCH_COLUMN);
            return $result;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Create a database.
     * 
     * @param string $name Database name
     * @param string $charset Character set
     * @param string $collation Collation
     * @return array Result
     */
    public function createDatabase(string $name, string $charset = 'utf8mb4', string $collation = 'utf8mb4_unicode_ci'): array
    {
        $pdo = $this->getPdo();
        if (!$pdo) {
            return ['success' => false, 'message' => $this->getLastError()];
        }

        $safeName = $this->sanitizeName($name);
        
        try {
            $sql = "CREATE DATABASE `{$safeName}` CHARACTER SET {$charset} COLLATE {$collation}";
            $pdo->exec($sql);
            
            return [
                'success' => true,
                'message' => "Database '{$safeName}' created successfully"
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Drop a database.
     * 
     * @param string $name Database name
     * @return array Result
     */
    public function dropDatabase(string $name): array
    {
        $pdo = $this->getPdo();
        if (!$pdo) {
            return ['success' => false, 'message' => $this->getLastError()];
        }

        $safeName = $this->sanitizeName($name);
        
        try {
            $pdo->exec("DROP DATABASE `{$safeName}`");
            
            return [
                'success' => true,
                'message' => "Database '{$safeName}' dropped successfully"
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get database information.
     * 
     * @param string $name Database name
     * @return array Database info
     */
    public function getDatabaseInfo(string $name): array
    {
        $pdo = $this->getPdo();
        if (!$pdo) {
            return ['success' => false, 'message' => $this->getLastError()];
        }

        $safeName = $this->sanitizeName($name);

        try {
            $stmt = $pdo->prepare("
                SELECT 
                    DEFAULT_CHARACTER_SET_NAME as charset,
                    DEFAULT_COLLATION_NAME as collation
                FROM information_schema.SCHEMATA 
                WHERE SCHEMA_NAME = ?
            ");
            $stmt->execute([$safeName]);
            $info = $stmt->fetch(PDO::FETCH_ASSOC);

            // Get table count
            $stmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM information_schema.TABLES 
                WHERE TABLE_SCHEMA = ?
            ");
            $stmt->execute([$safeName]);
            $tableCount = $stmt->fetchColumn();

            // Get size
            $stmt = $pdo->prepare("
                SELECT SUM(DATA_LENGTH + INDEX_LENGTH) as size
                FROM information_schema.TABLES 
                WHERE TABLE_SCHEMA = ?
            ");
            $stmt->execute([$safeName]);
            $size = $stmt->fetchColumn() ?: 0;

            return [
                'success' => true,
                'name' => $safeName,
                'charset' => $info['charset'] ?? 'unknown',
                'collation' => $info['collation'] ?? 'unknown',
                'tables' => (int) $tableCount,
                'size' => $this->formatSize($size)
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    // ========================================
    // TABLE OPERATIONS
    // ========================================

    /**
     * List tables in database.
     * 
     * @param string $database Database name
     * @return array
     */
    public function listTables(string $database): array
    {
        $pdo = $this->getPdo();
        if (!$pdo) return [];

        $safeDb = $this->sanitizeName($database);

        try {
            $stmt = $pdo->prepare("
                SELECT 
                    TABLE_NAME as name,
                    TABLE_ROWS as rows,
                    DATA_LENGTH + INDEX_LENGTH as size,
                    ENGINE as engine
                FROM information_schema.TABLES 
                WHERE TABLE_SCHEMA = ?
                ORDER BY TABLE_NAME
            ");
            $stmt->execute([$safeDb]);
            
            return array_map(function($row) {
                $row['size'] = $this->formatSize($row['size']);
                return $row;
            }, $stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get table structure.
     * 
     * @param string $database Database name
     * @param string $table Table name
     * @return array
     */
    public function getTableStructure(string $database, string $table): array
    {
        $pdo = $this->getPdo();
        if (!$pdo) return [];

        $safeDb = $this->sanitizeName($database);
        $safeTable = $this->sanitizeName($table);

        try {
            $pdo->exec("USE `{$safeDb}`");
            return $pdo->query("DESCRIBE `{$safeTable}`")->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get table data.
     * 
     * @param string $database Database name
     * @param string $table Table name
     * @param int $limit Row limit
     * @param int $offset Offset
     * @return array
     */
    public function getTableData(string $database, string $table, int $limit = 100, int $offset = 0): array
    {
        $pdo = $this->getPdo();
        if (!$pdo) return [];

        $safeDb = $this->sanitizeName($database);
        $safeTable = $this->sanitizeName($table);

        try {
            $pdo->exec("USE `{$safeDb}`");
            
            $stmt = $pdo->prepare("SELECT * FROM `{$safeTable}` LIMIT ? OFFSET ?");
            $stmt->execute([$limit, $offset]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Execute a custom query.
     * 
     * @param string $database Database name
     * @param string $sql SQL query
     * @return array
     */
    public function executeQuery(string $database, string $sql): array
    {
        $pdo = $this->getPdo();
        if (!$pdo) {
            return ['success' => false, 'message' => $this->getLastError()];
        }

        $safeDb = $this->sanitizeName($database);

        try {
            $pdo->exec("USE `{$safeDb}`");
            
            $sqlType = strtoupper(trim(explode(' ', $sql)[0]));
            
            if ($sqlType === 'SELECT' || $sqlType === 'SHOW' || $sqlType === 'DESCRIBE') {
                $stmt = $pdo->query($sql);
                return [
                    'success' => true,
                    'type' => 'query',
                    'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
                    'rowCount' => $stmt->rowCount()
                ];
            } else {
                $affected = $pdo->exec($sql);
                return [
                    'success' => true,
                    'type' => 'execute',
                    'affected' => $affected,
                    'message' => "Query executed. Affected rows: {$affected}"
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    // ========================================
    // UTILITY METHODS
    // ========================================

    /**
     * Sanitize database/table name.
     * 
     * @param string $name
     * @return string
     */
    private function sanitizeName(string $name): string
    {
        return preg_replace('/[^a-zA-Z0-9_]/', '', $name);
    }

    /**
     * Format file size.
     * 
     * @param int $bytes
     * @return string
     */
    private function formatSize(int $bytes): string
    {
        if ($bytes === 0) return '0 B';
        
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = floor(log($bytes, 1024));
        return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
    }
}


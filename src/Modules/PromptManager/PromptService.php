<?php
/**
 * ============================================
 * Prompt Manager - Prompt Service
 * ============================================
 * 
 * PURPOSE:
 * Handles CRUD operations for prompts.
 * 
 * DEPENDENCIES:
 * - App\Core\Database\Connection
 * 
 * ============================================
 */

namespace App\Modules\PromptManager;

use App\Core\Database\Connection;
use PDO;

class PromptService
{
    private Connection $connection;
    private string $tableName = 'reporter_prompt_table';

    /**
     * Initialize the service.
     * 
     * @param array $config Database configuration
     * @param string $tableName Table name
     */
    public function __construct(array $config = [], string $tableName = 'reporter_prompt_table')
    {
        $this->connection = new Connection($config);
        $this->tableName = $tableName;
        
        $this->ensureTable();
    }

    /**
     * Ensure prompts table exists.
     */
    private function ensureTable(): void
    {
        $pdo = $this->connection->getPdo();
        if (!$pdo) return;

        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `{$this->tableName}` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `title` VARCHAR(255) NOT NULL,
                    `content` TEXT NOT NULL,
                    `category` VARCHAR(100) DEFAULT 'general',
                    `tags` VARCHAR(500) DEFAULT '',
                    `is_favorite` TINYINT(1) DEFAULT 0,
                    `usage_count` INT DEFAULT 0,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_category (category),
                    INDEX idx_favorite (is_favorite),
                    FULLTEXT idx_search (title, content, tags)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        } catch (\Exception $e) {
            // Table might already exist
        }
    }

    /**
     * Get all prompts.
     * 
     * @param array $options Filter options
     * @return array
     */
    public function getAll(array $options = []): array
    {
        $pdo = $this->connection->getPdo();
        if (!$pdo) return [];

        $where = ['1=1'];
        $params = [];

        // Filter by category
        if (!empty($options['category'])) {
            $where[] = 'category = ?';
            $params[] = $options['category'];
        }

        // Filter favorites
        if (isset($options['favorite']) && $options['favorite']) {
            $where[] = 'is_favorite = 1';
        }

        // Search
        if (!empty($options['search'])) {
            $where[] = '(title LIKE ? OR content LIKE ? OR tags LIKE ?)';
            $search = '%' . $options['search'] . '%';
            $params = array_merge($params, [$search, $search, $search]);
        }

        // Build query
        $whereStr = implode(' AND ', $where);
        $orderBy = $options['orderBy'] ?? 'updated_at DESC';
        $limit = $options['limit'] ?? 100;
        $offset = $options['offset'] ?? 0;

        try {
            $sql = "SELECT * FROM `{$this->tableName}` WHERE {$whereStr} ORDER BY {$orderBy} LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get single prompt by ID.
     * 
     * @param int $id
     * @return array|null
     */
    public function getById(int $id): ?array
    {
        $pdo = $this->connection->getPdo();
        if (!$pdo) return null;

        try {
            $stmt = $pdo->prepare("SELECT * FROM `{$this->tableName}` WHERE id = ?");
            $stmt->execute([$id]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Create a new prompt.
     * 
     * @param array $data Prompt data
     * @return array Result
     */
    public function create(array $data): array
    {
        $pdo = $this->connection->getPdo();
        if (!$pdo) {
            return ['success' => false, 'message' => 'Database connection failed'];
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO `{$this->tableName}` (title, content, category, tags, is_favorite)
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $data['title'] ?? 'Untitled',
                $data['content'] ?? '',
                $data['category'] ?? 'general',
                $data['tags'] ?? '',
                $data['is_favorite'] ?? 0
            ]);

            return [
                'success' => true,
                'message' => 'Prompt created successfully',
                'id' => $pdo->lastInsertId()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Update a prompt.
     * 
     * @param int $id Prompt ID
     * @param array $data Updated data
     * @return array Result
     */
    public function update(int $id, array $data): array
    {
        $pdo = $this->connection->getPdo();
        if (!$pdo) {
            return ['success' => false, 'message' => 'Database connection failed'];
        }

        $updates = [];
        $params = [];

        foreach (['title', 'content', 'category', 'tags', 'is_favorite'] as $field) {
            if (isset($data[$field])) {
                $updates[] = "{$field} = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($updates)) {
            return ['success' => false, 'message' => 'No data to update'];
        }

        $params[] = $id;

        try {
            $sql = "UPDATE `{$this->tableName}` SET " . implode(', ', $updates) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            return [
                'success' => true,
                'message' => 'Prompt updated successfully'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete a prompt.
     * 
     * @param int $id Prompt ID
     * @return array Result
     */
    public function delete(int $id): array
    {
        $pdo = $this->connection->getPdo();
        if (!$pdo) {
            return ['success' => false, 'message' => 'Database connection failed'];
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM `{$this->tableName}` WHERE id = ?");
            $stmt->execute([$id]);

            return [
                'success' => true,
                'message' => 'Prompt deleted successfully'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Toggle favorite status.
     * 
     * @param int $id Prompt ID
     * @return array Result
     */
    public function toggleFavorite(int $id): array
    {
        $pdo = $this->connection->getPdo();
        if (!$pdo) {
            return ['success' => false, 'message' => 'Database connection failed'];
        }

        try {
            $pdo->exec("
                UPDATE `{$this->tableName}` 
                SET is_favorite = IF(is_favorite = 1, 0, 1) 
                WHERE id = {$id}
            ");

            return [
                'success' => true,
                'message' => 'Favorite status toggled'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Increment usage count.
     * 
     * @param int $id Prompt ID
     * @return bool
     */
    public function incrementUsage(int $id): bool
    {
        $pdo = $this->connection->getPdo();
        if (!$pdo) return false;

        try {
            $pdo->exec("UPDATE `{$this->tableName}` SET usage_count = usage_count + 1 WHERE id = {$id}");
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get all categories.
     * 
     * @return array
     */
    public function getCategories(): array
    {
        $pdo = $this->connection->getPdo();
        if (!$pdo) return [];

        try {
            $stmt = $pdo->query("
                SELECT category, COUNT(*) as count 
                FROM `{$this->tableName}` 
                GROUP BY category 
                ORDER BY count DESC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get statistics.
     * 
     * @return array
     */
    public function getStats(): array
    {
        $pdo = $this->connection->getPdo();
        if (!$pdo) return [];

        try {
            $total = $pdo->query("SELECT COUNT(*) FROM `{$this->tableName}`")->fetchColumn();
            $favorites = $pdo->query("SELECT COUNT(*) FROM `{$this->tableName}` WHERE is_favorite = 1")->fetchColumn();
            $categories = $pdo->query("SELECT COUNT(DISTINCT category) FROM `{$this->tableName}`")->fetchColumn();
            $totalUsage = $pdo->query("SELECT SUM(usage_count) FROM `{$this->tableName}`")->fetchColumn();

            return [
                'total' => (int) $total,
                'favorites' => (int) $favorites,
                'categories' => (int) $categories,
                'total_usage' => (int) ($totalUsage ?: 0)
            ];
        } catch (\Exception $e) {
            return [];
        }
    }
}


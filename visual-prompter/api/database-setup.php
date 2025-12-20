<?php
/**
 * Visual Prompter - Database Setup Script
 * Creates all required tables for the application
 * 
 * Tables created:
 * - visual_prompter_projects: Main project information
 * - visual_prompter_nodes: Nodes within projects
 * - visual_prompter_node_properties: Custom properties for nodes
 * - visual_prompter_connections: Links between nodes
 * - visual_prompter_tables: Table definitions (for database nodes)
 * - visual_prompter_table_columns: Columns within tables
 * - visual_prompter_project_history: Version history/snapshots
 * - visual_prompter_templates: Reusable node templates
 */

header('Content-Type: application/json');

// Database credentials
$host = 'srv1788.hstgr.io';
$dbname = 'u419999707_Mohamed';
$username = 'u419999707_Abuammar';
$password = 'P@master5007';
$port = 3306;

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    $tables = [];

    // 1. Projects Table - Main project information
    $sql = "CREATE TABLE IF NOT EXISTS `visual_prompter_projects` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `uuid` VARCHAR(36) NOT NULL UNIQUE,
        `name` VARCHAR(255) NOT NULL DEFAULT 'Untitled Project',
        `description` TEXT,
        `thumbnail` LONGTEXT COMMENT 'Base64 encoded thumbnail image',
        `canvas_config` JSON COMMENT 'Canvas settings (zoom, offset, etc.)',
        `is_template` TINYINT(1) DEFAULT 0,
        `is_public` TINYINT(1) DEFAULT 0,
        `tags` VARCHAR(500) COMMENT 'Comma-separated tags',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `last_opened_at` TIMESTAMP NULL,
        `version` INT DEFAULT 1,
        INDEX `idx_uuid` (`uuid`),
        INDEX `idx_name` (`name`),
        INDEX `idx_created` (`created_at`),
        INDEX `idx_updated` (`updated_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($sql);
    $tables[] = 'visual_prompter_projects';

    // 2. Nodes Table - All nodes in projects
    $sql = "CREATE TABLE IF NOT EXISTS `visual_prompter_nodes` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `project_id` INT NOT NULL,
        `node_id` INT NOT NULL COMMENT 'LiteGraph node ID within project',
        `node_type` VARCHAR(100) NOT NULL COMMENT 'DatabaseNode, BackendNode, etc.',
        `title` VARCHAR(255),
        `description` LONGTEXT,
        `position_x` FLOAT DEFAULT 0,
        `position_y` FLOAT DEFAULT 0,
        `size_width` FLOAT DEFAULT 200,
        `size_height` FLOAT DEFAULT 100,
        `color` VARCHAR(50),
        `bgcolor` VARCHAR(50),
        `collapsed` TINYINT(1) DEFAULT 0,
        `properties_json` JSON COMMENT 'All node properties as JSON',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`project_id`) REFERENCES `visual_prompter_projects`(`id`) ON DELETE CASCADE,
        UNIQUE KEY `unique_node_in_project` (`project_id`, `node_id`),
        INDEX `idx_project` (`project_id`),
        INDEX `idx_type` (`node_type`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($sql);
    $tables[] = 'visual_prompter_nodes';

    // 3. Connections Table - Links between nodes
    $sql = "CREATE TABLE IF NOT EXISTS `visual_prompter_connections` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `project_id` INT NOT NULL,
        `source_node_id` INT NOT NULL COMMENT 'LiteGraph source node ID',
        `source_slot` INT NOT NULL COMMENT 'Output slot index',
        `target_node_id` INT NOT NULL COMMENT 'LiteGraph target node ID',
        `target_slot` INT NOT NULL COMMENT 'Input slot index',
        `connection_type` VARCHAR(50) DEFAULT 'default',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`project_id`) REFERENCES `visual_prompter_projects`(`id`) ON DELETE CASCADE,
        INDEX `idx_project` (`project_id`),
        INDEX `idx_source` (`source_node_id`),
        INDEX `idx_target` (`target_node_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($sql);
    $tables[] = 'visual_prompter_connections';

    // 4. Database Node Tables - Table definitions for DatabaseNode
    $sql = "CREATE TABLE IF NOT EXISTS `visual_prompter_node_tables` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `node_db_id` INT NOT NULL COMMENT 'Reference to visual_prompter_nodes.id',
        `table_name` VARCHAR(255) NOT NULL,
        `table_order` INT DEFAULT 0,
        `engine` VARCHAR(50) DEFAULT 'InnoDB',
        `charset` VARCHAR(50) DEFAULT 'utf8mb4',
        `collation` VARCHAR(100) DEFAULT 'utf8mb4_unicode_ci',
        `comment` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`node_db_id`) REFERENCES `visual_prompter_nodes`(`id`) ON DELETE CASCADE,
        INDEX `idx_node` (`node_db_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($sql);
    $tables[] = 'visual_prompter_node_tables';

    // 5. Table Columns - Columns within tables
    $sql = "CREATE TABLE IF NOT EXISTS `visual_prompter_table_columns` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `table_id` INT NOT NULL,
        `column_name` VARCHAR(255) NOT NULL,
        `column_type` VARCHAR(100) NOT NULL,
        `column_length` VARCHAR(50),
        `is_nullable` TINYINT(1) DEFAULT 1,
        `is_primary_key` TINYINT(1) DEFAULT 0,
        `is_auto_increment` TINYINT(1) DEFAULT 0,
        `is_unique` TINYINT(1) DEFAULT 0,
        `is_index` TINYINT(1) DEFAULT 0,
        `default_value` VARCHAR(255),
        `column_comment` TEXT,
        `column_order` INT DEFAULT 0,
        `foreign_key_table` VARCHAR(255),
        `foreign_key_column` VARCHAR(255),
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`table_id`) REFERENCES `visual_prompter_node_tables`(`id`) ON DELETE CASCADE,
        INDEX `idx_table` (`table_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($sql);
    $tables[] = 'visual_prompter_table_columns';

    // 6. Project History - Version snapshots
    $sql = "CREATE TABLE IF NOT EXISTS `visual_prompter_project_history` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `project_id` INT NOT NULL,
        `version` INT NOT NULL,
        `snapshot_data` LONGTEXT NOT NULL COMMENT 'Full JSON snapshot of project',
        `change_description` VARCHAR(500),
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`project_id`) REFERENCES `visual_prompter_projects`(`id`) ON DELETE CASCADE,
        INDEX `idx_project` (`project_id`),
        INDEX `idx_version` (`version`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($sql);
    $tables[] = 'visual_prompter_project_history';

    // 7. Templates - Reusable node/project templates
    $sql = "CREATE TABLE IF NOT EXISTS `visual_prompter_templates` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(255) NOT NULL,
        `description` TEXT,
        `category` VARCHAR(100) DEFAULT 'General',
        `template_type` ENUM('node', 'project', 'snippet') DEFAULT 'node',
        `template_data` LONGTEXT NOT NULL COMMENT 'JSON template data',
        `icon` VARCHAR(50),
        `is_system` TINYINT(1) DEFAULT 0 COMMENT 'System templates cannot be deleted',
        `usage_count` INT DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_category` (`category`),
        INDEX `idx_type` (`template_type`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($sql);
    $tables[] = 'visual_prompter_templates';

    // 8. Generated Prompts - Store generated prompts
    $sql = "CREATE TABLE IF NOT EXISTS `visual_prompter_generated_prompts` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `project_id` INT NOT NULL,
        `prompt_text` LONGTEXT NOT NULL,
        `prompt_format` ENUM('text', 'markdown', 'json') DEFAULT 'text',
        `node_count` INT DEFAULT 0,
        `connection_count` INT DEFAULT 0,
        `generated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`project_id`) REFERENCES `visual_prompter_projects`(`id`) ON DELETE CASCADE,
        INDEX `idx_project` (`project_id`),
        INDEX `idx_generated` (`generated_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($sql);
    $tables[] = 'visual_prompter_generated_prompts';

    // 9. User Preferences - Application settings
    $sql = "CREATE TABLE IF NOT EXISTS `visual_prompter_preferences` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `preference_key` VARCHAR(100) NOT NULL UNIQUE,
        `preference_value` TEXT,
        `preference_type` VARCHAR(50) DEFAULT 'string',
        `description` VARCHAR(500),
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_key` (`preference_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($sql);
    $tables[] = 'visual_prompter_preferences';

    // Insert default preferences
    $defaultPrefs = [
        ['auto_save', '1', 'boolean', 'Enable auto-save feature'],
        ['auto_save_interval', '60', 'integer', 'Auto-save interval in seconds'],
        ['default_grid', '1', 'boolean', 'Show grid by default'],
        ['default_zoom', '1', 'float', 'Default canvas zoom level'],
        ['theme', 'dark', 'string', 'Application theme'],
        ['max_history', '50', 'integer', 'Maximum history snapshots per project']
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO `visual_prompter_preferences` 
        (`preference_key`, `preference_value`, `preference_type`, `description`) 
        VALUES (?, ?, ?, ?)");
    
    foreach ($defaultPrefs as $pref) {
        $stmt->execute($pref);
    }

    // Return success
    echo json_encode([
        'success' => true,
        'message' => 'Database setup completed successfully',
        'tables_created' => $tables,
        'tables_count' => count($tables),
        'timestamp' => date('c')
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database setup failed',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}


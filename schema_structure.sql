-- ============================================================
-- PROMPT MANAGER - COMPLETE DATABASE SCHEMA (Structure Only)
-- ============================================================
-- Generated: 2026-02-16
-- Source: Prompt-Manager Application (LIVE DATABASE VERIFIED)
-- Engine: MySQL / InnoDB
-- Charset: utf8mb4 / utf8mb4_unicode_ci
--
-- This file recreates the EXACT table structure used across
-- the entire Prompt Manager application. No data is inserted
-- except default preference values required for operation.
--
-- Verified against live databases on 2026-02-15 via
-- SHOW CREATE TABLE on every table in both databases.
--
-- SOURCE DATABASES:
--   DB1: u419999707_prompt_manager (7 tables with data)
--   DB2: u419999707_Mohamed        (3 tables, empty + 9 Visual Prompter pending)
--
-- TABLES (16 total):
--
--   --- DB1: u419999707_prompt_manager ---
--   1.  report_prompt_databases             (Connection Registry Hub)
--   2.  reporter_prompt_design_templates    (Page Design Templates)
--   3.  reporter_prompt_projects            (Prompt Reporter Projects)
--   4.  reporter_prompt_saved_prompts       (Saved Generated Prompts)
--   5.  reporter_prompt_templates           (Prompt Section Templates)
--   6.  reporter_prompt_tool_order          (Tool Ordering Config)
--   7.  reporter_prompt_uploaded_files      (Uploaded File Tracking)
--
--   --- DB2: u419999707_Mohamed (also in DB1) ---
--   (Tables 4,5,7 also exist in DB2 as empty copies)
--
--   --- Visual Prompter (defined in code, ready to deploy) ---
--   8.  visual_prompter_projects            (Root Entity)
--   9.  visual_prompter_nodes               (Graph Nodes)
--   10. visual_prompter_connections         (Node Links)
--   11. visual_prompter_node_tables         (DB Schema Defs)
--   12. visual_prompter_table_columns       (Column Metadata)
--   13. visual_prompter_project_history     (Version Snapshots)
--   14. visual_prompter_templates           (Reusable Templates)
--   15. visual_prompter_generated_prompts   (AI Prompt Output)
--   16. visual_prompter_preferences         (App Settings)
--
-- EXECUTION ORDER MATTERS: Foreign keys require parent tables
-- to exist first. Run this file top-to-bottom as-is.
-- ============================================================


-- ============================================================
-- TABLE 1: report_prompt_databases
-- ============================================================
-- PURPOSE: Central connection registry hub. Stores credentials
--          for all managed database connections. Every module
--          in the system reads from this table to discover
--          available databases.
-- SOURCE: LIVE DB - u419999707_prompt_manager (10 rows)
-- USED BY: report-prompt-databases.php, prompt-manager.php,
--          visual-prompter/api/get-databases.php
-- ============================================================

CREATE TABLE IF NOT EXISTS `report_prompt_databases` (
    `id` varchar(50) NOT NULL,
    `name` varchar(255) NOT NULL,
    `type` varchar(50) DEFAULT 'shared',
    `host` varchar(255) NOT NULL,
    `dbName` varchar(255) NOT NULL,
    `username` varchar(255) NOT NULL,
    `password` varchar(255) NOT NULL,
    `port` varchar(10) DEFAULT '3306',
    `createdAt` datetime DEFAULT current_timestamp(),
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;


-- ============================================================
-- TABLE 2: reporter_prompt_design_templates
-- ============================================================
-- PURPOSE: Stores page design templates (HTML/CSS layouts)
--          used by the prompt report generator UI.
-- SOURCE: LIVE DB - u419999707_prompt_manager (3 rows)
-- ============================================================

CREATE TABLE IF NOT EXISTS `reporter_prompt_design_templates` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `content` text NOT NULL,
    `sort_order` int(11) DEFAULT 0,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- TABLE 3: reporter_prompt_projects
-- ============================================================
-- PURPOSE: Stores prompt reporter projects with their full
--          configuration: target database credentials, backend
--          definitions, page layouts, frontend configs,
--          language settings, folder structure, and the
--          final generated prompt content.
-- SOURCE: LIVE DB - u419999707_prompt_manager (6 rows)
-- ============================================================

CREATE TABLE IF NOT EXISTS `reporter_prompt_projects` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `description` text DEFAULT NULL,
    `database_id` int(11) DEFAULT NULL,
    `database_name` varchar(255) DEFAULT NULL,
    `database_host` varchar(255) DEFAULT NULL,
    `database_user` varchar(255) DEFAULT NULL,
    `database_pass` varchar(255) DEFAULT NULL,
    `database_port` varchar(10) DEFAULT '3306',
    `include_remote` tinyint(1) DEFAULT 0,
    `include_localhost` tinyint(1) DEFAULT 0,
    `backends` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`backends`)),
    `pages` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`pages`)),
    `frontends` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`frontends`)),
    `project_notes` text DEFAULT NULL,
    `language_settings` text DEFAULT NULL,
    `folder_data` longtext DEFAULT NULL,
    `prompt_content` text DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- TABLE 4: reporter_prompt_saved_prompts
-- ============================================================
-- PURPOSE: Stores saved/generated prompt outputs for later
--          retrieval and reuse.
-- SOURCE: LIVE DB - u419999707_prompt_manager (5 rows)
--         Also exists in u419999707_Mohamed (empty)
-- ============================================================

CREATE TABLE IF NOT EXISTS `reporter_prompt_saved_prompts` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `title` varchar(255) NOT NULL,
    `content` text NOT NULL,
    `created_at` timestamp NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- TABLE 5: reporter_prompt_templates
-- ============================================================
-- PURPOSE: Stores prompt section templates (reusable prompt
--          building blocks) with ordering and active status.
-- SOURCE: LIVE DB - u419999707_prompt_manager (1 row)
--         Also exists in u419999707_Mohamed (empty)
-- ============================================================

CREATE TABLE IF NOT EXISTS `reporter_prompt_templates` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `content` text NOT NULL,
    `sort_order` int(11) DEFAULT 0,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- TABLE 6: reporter_prompt_tool_order
-- ============================================================
-- PURPOSE: Stores the ordering/arrangement of tools in the
--          prompt reporter UI sidebar.
-- SOURCE: LIVE DB - u419999707_prompt_manager (1 row)
-- ============================================================

CREATE TABLE IF NOT EXISTS `reporter_prompt_tool_order` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `tool_order` text NOT NULL,
    `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- TABLE 7: reporter_prompt_uploaded_files
-- ============================================================
-- PURPOSE: Tracks files uploaded through the prompt reporter
--          interface (filename, path, size, MIME type).
-- SOURCE: LIVE DB - u419999707_prompt_manager (0 rows)
--         Also exists in u419999707_Mohamed (empty)
-- ============================================================

CREATE TABLE IF NOT EXISTS `reporter_prompt_uploaded_files` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `filename` varchar(255) NOT NULL,
    `filepath` varchar(500) NOT NULL,
    `filesize` int(11) DEFAULT NULL,
    `filetype` varchar(100) DEFAULT NULL,
    `uploaded_at` timestamp NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- TABLE 8: visual_prompter_projects
-- ============================================================
-- PURPOSE: Root entity for Visual Prompter projects. Holds
--          project metadata, canvas config (JSON), base64
--          thumbnails, and version tracking.
-- NOTE:    Uses dual identity: auto-increment `id` for internal
--          FK efficiency + `uuid` for external/API references.
-- SOURCE: DEFINED IN CODE - visual-prompter/api/database-setup.php
-- USED BY: visual-prompter/api/projects.php
-- ============================================================

CREATE TABLE IF NOT EXISTS `visual_prompter_projects` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- TABLE 9: visual_prompter_nodes
-- ============================================================
-- PURPOSE: All graph nodes within projects. Each node has a
--          LiteGraph internal ID, type, position, size, and
--          a JSON blob for flexible properties.
-- FK:      project_id → visual_prompter_projects(id) CASCADE
-- UNIQUE:  (project_id, node_id) - one LiteGraph ID per project
-- SOURCE: DEFINED IN CODE - visual-prompter/api/database-setup.php
-- ============================================================

CREATE TABLE IF NOT EXISTS `visual_prompter_nodes` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- TABLE 10: visual_prompter_connections
-- ============================================================
-- PURPOSE: Edge definitions for the node graph. Links a source
--          node's output slot to a target node's input slot.
-- FK:      project_id → visual_prompter_projects(id) CASCADE
-- SOURCE: DEFINED IN CODE - visual-prompter/api/database-setup.php
-- ============================================================

CREATE TABLE IF NOT EXISTS `visual_prompter_connections` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- TABLE 11: visual_prompter_node_tables
-- ============================================================
-- PURPOSE: Table definitions attached to DatabaseNode types.
--          Stores the schema design (table name, engine,
--          charset, collation) for each table a user defines
--          inside a database node.
-- FK:      node_db_id → visual_prompter_nodes(id) CASCADE
-- SOURCE: DEFINED IN CODE - visual-prompter/api/database-setup.php
-- ============================================================

CREATE TABLE IF NOT EXISTS `visual_prompter_node_tables` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- TABLE 12: visual_prompter_table_columns
-- ============================================================
-- PURPOSE: Full column metadata within tables. Stores column
--          name, type, length, constraints (PK, AI, UNIQUE,
--          INDEX, NULLABLE), defaults, comments, ordering,
--          and foreign key references.
-- FK:      table_id → visual_prompter_node_tables(id) CASCADE
-- SOURCE: DEFINED IN CODE - visual-prompter/api/database-setup.php
-- ============================================================

CREATE TABLE IF NOT EXISTS `visual_prompter_table_columns` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- TABLE 13: visual_prompter_project_history
-- ============================================================
-- PURPOSE: Version snapshots for projects. Stores a full JSON
--          snapshot of the project state on every save.
--          Capped at 50 snapshots per project (pruned by app).
-- FK:      project_id → visual_prompter_projects(id) CASCADE
-- SOURCE: DEFINED IN CODE - visual-prompter/api/database-setup.php
-- ============================================================

CREATE TABLE IF NOT EXISTS `visual_prompter_project_history` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT NOT NULL,
    `version` INT NOT NULL,
    `snapshot_data` LONGTEXT NOT NULL COMMENT 'Full JSON snapshot of project',
    `change_description` VARCHAR(500),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`project_id`) REFERENCES `visual_prompter_projects`(`id`) ON DELETE CASCADE,
    INDEX `idx_project` (`project_id`),
    INDEX `idx_version` (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- TABLE 14: visual_prompter_templates
-- ============================================================
-- PURPOSE: Reusable node, project, or snippet templates.
--          System templates (is_system=1) are protected from
--          deletion. Tracks usage count.
-- SOURCE: DEFINED IN CODE - visual-prompter/api/database-setup.php
-- ============================================================

CREATE TABLE IF NOT EXISTS `visual_prompter_templates` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- TABLE 15: visual_prompter_generated_prompts
-- ============================================================
-- PURPOSE: Stores AI-generated prompt output from projects.
--          Records the prompt text, format, and counts of
--          nodes/connections used to generate it.
-- FK:      project_id → visual_prompter_projects(id) CASCADE
-- SOURCE: DEFINED IN CODE - visual-prompter/api/database-setup.php
-- ============================================================

CREATE TABLE IF NOT EXISTS `visual_prompter_generated_prompts` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- TABLE 16: visual_prompter_preferences
-- ============================================================
-- PURPOSE: Application-level key-value settings store.
--          Each key is unique. Stores value, type hint,
--          and human-readable description.
-- SOURCE: DEFINED IN CODE - visual-prompter/api/database-setup.php
-- ============================================================

CREATE TABLE IF NOT EXISTS `visual_prompter_preferences` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `preference_key` VARCHAR(100) NOT NULL UNIQUE,
    `preference_value` TEXT,
    `preference_type` VARCHAR(50) DEFAULT 'string',
    `description` VARCHAR(500),
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_key` (`preference_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- DEFAULT PREFERENCE VALUES (Required for application startup)
-- ============================================================
-- These are the only data rows inserted. They are required
-- for the Visual Prompter to function correctly on first load.
-- INSERT IGNORE ensures no duplicates if run multiple times.
-- ============================================================

INSERT IGNORE INTO `visual_prompter_preferences`
    (`preference_key`, `preference_value`, `preference_type`, `description`)
VALUES
    ('auto_save',          '1',    'boolean', 'Enable auto-save feature'),
    ('auto_save_interval', '60',   'integer', 'Auto-save interval in seconds'),
    ('default_grid',       '1',    'boolean', 'Show grid by default'),
    ('default_zoom',       '1',    'float',   'Default canvas zoom level'),
    ('theme',              'dark', 'string',  'Application theme'),
    ('max_history',        '50',   'integer', 'Maximum history snapshots per project');


-- ============================================================
-- SCHEMA COMPLETE
-- ============================================================
-- Total: 16 tables created
--
-- Standalone Tables (no FK dependencies):
--   report_prompt_databases        (Connection Registry Hub)
--   reporter_prompt_design_templates (Page Design Templates)
--   reporter_prompt_projects        (Prompt Reporter Projects)
--   reporter_prompt_saved_prompts   (Saved Generated Prompts)
--   reporter_prompt_templates       (Prompt Section Templates)
--   reporter_prompt_tool_order      (Tool Ordering Config)
--   reporter_prompt_uploaded_files  (Uploaded File Tracking)
--   visual_prompter_templates       (Reusable Templates)
--   visual_prompter_preferences     (App Settings)
--
-- Foreign Key Cascade Chain:
--   visual_prompter_projects
--     ├── visual_prompter_nodes (CASCADE)
--     │     └── visual_prompter_node_tables (CASCADE)
--     │           └── visual_prompter_table_columns (CASCADE)
--     ├── visual_prompter_connections (CASCADE)
--     ├── visual_prompter_project_history (CASCADE)
--     └── visual_prompter_generated_prompts (CASCADE)
-- ============================================================

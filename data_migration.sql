-- ============================================================
-- PROMPT MANAGER - COMPLETE DATA MIGRATION
-- ============================================================
-- Generated: 2026-02-15 21:14:38
-- Source Databases:
--   1. u419999707_prompt_manager (Connection Registry + Prompts)
--   2. u419999707_Mohamed        (Visual Prompter)
-- 
-- IMPORTANT: Run schema_structure.sql FIRST to create tables,
--            then run this file to populate data.
-- 
-- INSERT ORDER: Parent tables first to satisfy FK constraints.
-- All INSERTs use INSERT IGNORE to prevent duplicate key errors
-- if run multiple times.
-- ============================================================

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET AUTOCOMMIT = 0;
START TRANSACTION;

-- ============================================================
-- DATABASE SOURCE: u419999707_prompt_manager
-- Host: srv1788.hstgr.io
-- Tables: report_prompt_databases, reporter_prompt_table
-- ============================================================

-- ============================================================
-- TABLE: report_prompt_databases
-- ============================================================
-- 10 row(s)
INSERT IGNORE INTO `report_prompt_databases` (`id`, `name`, `type`, `host`, `dbName`, `username`, `password`, `port`, `createdAt`) VALUES
    ('1770038332247', 'Prompt Manager (Hostinger)', 'shared', 'srv1788.hstgr.io', 'u419999707_prompt_manager', 'u419999707_prompt_manager', 'P@master5007', '3306', '2026-02-02 13:18:53'),
    ('1770038333134', 'Hbngf (Hostinger)', 'shared', 'srv1788.hstgr.io', 'u419999707_hbngf', 'u419999707_hbngf', 'P@master5007', '3306', '2026-02-02 13:18:54'),
    ('1770038333618', 'Fusion Viewer (Hostinger)', 'shared', 'srv1788.hstgr.io', 'u419999707_Fusion_Viewer', 'u419999707_Fusion_Viewer', 'P@master5007', '3306', '2026-02-02 13:18:53'),
    ('1770038333749', 'Hbdnf (Hostinger)', 'shared', 'srv1788.hstgr.io', 'u419999707_hbdnf', 'u419999707_hbdnf', 'P@master5007', '3306', '2026-02-02 13:18:53'),
    ('1770040157248', 'Designboostdb (Hostinger)', 'shared', 'srv1788.hstgr.io', 'u419999707_designboostDB', 'u419999707_designboostuse', 'P@master5007', '3306', '2026-02-02 13:49:18'),
    ('1770040157340', 'Dbaipromptdict (Hostinger)', 'shared', 'srv1788.hstgr.io', 'u419999707_DBAIpromptdict', 'u419999707_AIpromptdict', 'P@master5007', '3306', '2026-02-02 13:49:18'),
    ('1770040158374', 'Glbuilder (Hostinger)', 'shared', 'srv1788.hstgr.io', 'u419999707_glbuilder', 'u419999707_gl', 'P@master5007', '3306', '2026-02-02 13:49:19'),
    ('1770040158741', 'Mohamed (Hostinger)', 'shared', 'srv1788.hstgr.io', 'u419999707_Mohamed', 'u419999707_Abuammar', 'P@master5007', '3306', '2026-02-02 13:49:19'),
    ('1770040158980', 'Gl Quiz Tracke (Hostinger)', 'shared', 'srv1788.hstgr.io', 'u419999707_GL_Quiz_Tracke', 'u419999707_victorystreaml', 'P@master5007', '3306', '2026-02-02 13:49:18'),
    ('1770040305388', 'Voyyb (Hostinger)', 'shared', 'srv1788.hstgr.io', 'u419999707_vOYYB', 'u419999707_5jHuE', 'P@master5007', '3306', '2026-02-02 13:51:46');

-- TABLE: reporter_prompt_table - Does not exist in source database, skipped

-- ============================================================
-- DATABASE SOURCE: u419999707_Mohamed
-- Host: srv1788.hstgr.io
-- Tables: visual_prompter_projects, visual_prompter_nodes, visual_prompter_connections, visual_prompter_node_tables, visual_prompter_table_columns, visual_prompter_project_history, visual_prompter_templates, visual_prompter_generated_prompts, visual_prompter_preferences
-- ============================================================

-- TABLE: visual_prompter_projects - Does not exist in source database, skipped

-- TABLE: visual_prompter_nodes - Does not exist in source database, skipped

-- TABLE: visual_prompter_connections - Does not exist in source database, skipped

-- TABLE: visual_prompter_node_tables - Does not exist in source database, skipped

-- TABLE: visual_prompter_table_columns - Does not exist in source database, skipped

-- TABLE: visual_prompter_project_history - Does not exist in source database, skipped

-- TABLE: visual_prompter_templates - Does not exist in source database, skipped

-- TABLE: visual_prompter_generated_prompts - Does not exist in source database, skipped

-- TABLE: visual_prompter_preferences - Does not exist in source database, skipped


-- ============================================================
-- FINALIZE TRANSACTION
-- ============================================================

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

-- ============================================================
-- MIGRATION SUMMARY
-- ============================================================
-- Generated: 2026-02-15 21:14:42
-- Total Tables Exported: 1
-- Total Rows Exported:   10
--
-- Per-Table Breakdown:
--   report_prompt_databases: 10 rows
--
-- INSTRUCTIONS:
--   1. First run: schema_structure.sql  (creates all 11 tables)
--   2. Then run:  data_migration.sql    (this file - populates data)
--   3. Verify with: SELECT COUNT(*) FROM each table
-- ============================================================

<?php
/**
 * ============================================================
 * FULL DATA MIGRATION GENERATOR
 * ============================================================
 * 
 * PURPOSE:
 * Connects to BOTH live databases, reads every row from all
 * 11 tables, and generates a complete data_migration.sql file
 * with INSERT statements. Structure-only (no CREATE TABLE).
 * 
 * DATABASES:
 * 1. u419999707_prompt_manager → report_prompt_databases, reporter_prompt_table
 * 2. u419999707_Mohamed        → all visual_prompter_* tables (9 tables)
 * 
 * USAGE:
 * Run this script via browser or CLI:
 *   php generate_data_dump.php
 * 
 * OUTPUT:
 * Creates: data_migration.sql in the same directory
 * 
 * ============================================================
 */

// ============================================================
// CONFIGURATION
// ============================================================

$outputFile = __DIR__ . '/data_migration.sql';

// Database 1: Prompt Manager Hub (report_prompt_databases + reporter_prompt_table)
$db1 = [
    'host'     => 'srv1788.hstgr.io',
    'port'     => 3306,
    'dbname'   => 'u419999707_prompt_manager',
    'username' => 'u419999707_prompt_manager',
    'password' => 'P@master5007',
    'tables'   => [
        'report_prompt_databases',
        'reporter_prompt_table',
    ]
];

// Database 2: Visual Prompter (all visual_prompter_* tables)
$db2 = [
    'host'     => 'srv1788.hstgr.io',
    'port'     => 3306,
    'dbname'   => 'u419999707_Mohamed',
    'username' => 'u419999707_Abuammar',
    'password' => 'P@master5007',
    'tables'   => [
        // ORDER MATTERS: parent tables first (FK dependencies)
        'visual_prompter_projects',
        'visual_prompter_nodes',
        'visual_prompter_connections',
        'visual_prompter_node_tables',
        'visual_prompter_table_columns',
        'visual_prompter_project_history',
        'visual_prompter_templates',
        'visual_prompter_generated_prompts',
        'visual_prompter_preferences',
    ]
];

// ============================================================
// HELPER FUNCTIONS
// ============================================================

/**
 * Connect to a database and return PDO instance
 */
function connectDB($config) {
    try {
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        output("❌ FAILED to connect to {$config['dbname']}: " . $e->getMessage());
        return null;
    }
}

/**
 * Escape a value for SQL INSERT statement
 */
function escapeValue($value, $pdo) {
    if ($value === null) {
        return 'NULL';
    }
    if (is_int($value) || is_float($value)) {
        return $value;
    }
    // Use PDO::quote for proper escaping
    return $pdo->quote($value);
}

/**
 * Get column information for a table
 */
function getColumnInfo($pdo, $tableName) {
    $stmt = $pdo->query("DESCRIBE `{$tableName}`");
    return $stmt->fetchAll();
}

/**
 * Generate INSERT statements for a table
 */
function generateInserts($pdo, $tableName) {
    $output = '';
    
    // Get column info
    $columns = getColumnInfo($pdo, $tableName);
    $columnNames = array_map(function($col) { return $col['Field']; }, $columns);
    $columnList = '`' . implode('`, `', $columnNames) . '`';
    
    // Get all rows
    $stmt = $pdo->query("SELECT * FROM `{$tableName}` ORDER BY 1");
    $rows = $stmt->fetchAll();
    $totalRows = count($rows);
    
    if ($totalRows === 0) {
        $output .= "-- (No data in this table)\n";
        return ['sql' => $output, 'count' => 0];
    }
    
    $output .= "-- {$totalRows} row(s)\n";
    
    // For large tables, use multi-row INSERT (batches of 50)
    $batchSize = 50;
    $batches = array_chunk($rows, $batchSize);
    
    foreach ($batches as $batch) {
        $output .= "INSERT INTO `{$tableName}` ({$columnList}) VALUES\n";
        
        $valueRows = [];
        foreach ($batch as $row) {
            $values = [];
            foreach ($columnNames as $col) {
                $values[] = escapeValue($row[$col] ?? null, $pdo);
            }
            $valueRows[] = '    (' . implode(', ', $values) . ')';
        }
        
        $output .= implode(",\n", $valueRows) . ";\n\n";
    }
    
    return ['sql' => $output, 'count' => $totalRows];
}

/**
 * Output message to console and/or browser
 */
function output($msg) {
    if (php_sapi_name() === 'cli') {
        echo $msg . "\n";
    } else {
        echo "<pre>{$msg}</pre>";
        ob_flush();
        flush();
    }
}

// ============================================================
// MAIN EXECUTION
// ============================================================

$startTime = microtime(true);

output("============================================================");
output("  PROMPT MANAGER - FULL DATA MIGRATION GENERATOR");
output("  Started: " . date('Y-m-d H:i:s'));
output("============================================================");
output("");

$sql = '';
$totalTables = 0;
$totalRows = 0;
$tableStats = [];

// ============================================================
// HEADER
// ============================================================
$sql .= "-- ============================================================\n";
$sql .= "-- PROMPT MANAGER - COMPLETE DATA MIGRATION\n";
$sql .= "-- ============================================================\n";
$sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
$sql .= "-- Source Databases:\n";
$sql .= "--   1. u419999707_prompt_manager (Connection Registry + Prompts)\n";
$sql .= "--   2. u419999707_Mohamed        (Visual Prompter)\n";
$sql .= "-- \n";
$sql .= "-- IMPORTANT: Run schema_structure.sql FIRST to create tables,\n";
$sql .= "--            then run this file to populate data.\n";
$sql .= "-- \n";
$sql .= "-- INSERT ORDER: Parent tables first to satisfy FK constraints.\n";
$sql .= "-- All INSERTs use INSERT IGNORE to prevent duplicate key errors\n";
$sql .= "-- if run multiple times.\n";
$sql .= "-- ============================================================\n\n";

$sql .= "SET NAMES utf8mb4;\n";
$sql .= "SET CHARACTER SET utf8mb4;\n";
$sql .= "SET FOREIGN_KEY_CHECKS = 0;\n";
$sql .= "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n";
$sql .= "SET AUTOCOMMIT = 0;\n";
$sql .= "START TRANSACTION;\n\n";

// ============================================================
// DATABASE 1: u419999707_prompt_manager
// ============================================================

output("📡 Connecting to Database 1: {$db1['dbname']}...");
$pdo1 = connectDB($db1);

if ($pdo1) {
    output("✅ Connected to {$db1['dbname']}");
    
    $sql .= "-- ============================================================\n";
    $sql .= "-- DATABASE SOURCE: {$db1['dbname']}\n";
    $sql .= "-- Host: {$db1['host']}\n";
    $sql .= "-- Tables: " . implode(', ', $db1['tables']) . "\n";
    $sql .= "-- ============================================================\n\n";
    
    foreach ($db1['tables'] as $table) {
        output("  📋 Exporting table: {$table}...");
        
        // Check if table exists
        try {
            $check = $pdo1->query("SHOW TABLES LIKE '{$table}'");
            if ($check->rowCount() === 0) {
                output("    ⚠️  Table '{$table}' does not exist - skipping");
                $sql .= "-- TABLE: {$table} - Does not exist in source database, skipped\n\n";
                continue;
            }
        } catch (Exception $e) {
            output("    ❌ Error checking table '{$table}': " . $e->getMessage());
            continue;
        }
        
        $sql .= "-- ============================================================\n";
        $sql .= "-- TABLE: {$table}\n";
        $sql .= "-- ============================================================\n";
        
        try {
            $result = generateInserts($pdo1, $table);
            
            // Replace INSERT with INSERT IGNORE for safety
            $safeSql = str_replace('INSERT INTO', 'INSERT IGNORE INTO', $result['sql']);
            $sql .= $safeSql;
            
            $totalTables++;
            $totalRows += $result['count'];
            $tableStats[$table] = $result['count'];
            
            output("    ✅ Exported {$result['count']} rows");
        } catch (Exception $e) {
            output("    ❌ Error exporting '{$table}': " . $e->getMessage());
            $sql .= "-- ERROR: Failed to export - " . $e->getMessage() . "\n\n";
        }
    }
    
    $pdo1 = null; // Close connection
    output("");
} else {
    output("❌ Could not connect to {$db1['dbname']} - attempting JSON fallback...");
    
    // ============================================================
    // FALLBACK: Generate from JSON files if DB1 is unreachable
    // ============================================================
    $sql .= "-- ============================================================\n";
    $sql .= "-- DATABASE SOURCE: {$db1['dbname']} (FALLBACK FROM JSON FILES)\n";
    $sql .= "-- NOTE: Live database was unreachable. Data extracted from\n";
    $sql .= "--       local JSON backup files in DatabasesStore/\n";
    $sql .= "-- ============================================================\n\n";
    
    $sql .= "-- ============================================================\n";
    $sql .= "-- TABLE: report_prompt_databases (from JSON backup)\n";
    $sql .= "-- ============================================================\n";
    
    // Load most complete JSON export (the one with 8 connections)
    $jsonFiles = [
        __DIR__ . '/DatabasesStore/report_prompt_databases_2025-12-18T21-23-14.json',
        __DIR__ . '/DatabasesStore/report_prompt_databases_2025-12-18T21-13-59.json',
        __DIR__ . '/DatabasesStore/databases.json',
    ];
    
    $allConnections = [];
    $seenIds = [];
    
    foreach ($jsonFiles as $jsonFile) {
        if (!file_exists($jsonFile)) continue;
        
        $raw = file_get_contents($jsonFile);
        // Strip markdown comments from databases.json
        $raw = preg_replace('/<!--.*?-->/s', '', $raw);
        $raw = preg_replace('/## .*?\n/', '', $raw);
        $raw = trim($raw);
        
        $data = json_decode($raw, true);
        if (!$data || !isset($data['connections'])) continue;
        
        foreach ($data['connections'] as $conn) {
            $id = $conn['id'] ?? '';
            if (!empty($id) && !isset($seenIds[$id])) {
                $seenIds[$id] = true;
                $allConnections[] = $conn;
            }
        }
    }
    
    if (!empty($allConnections)) {
        $count = count($allConnections);
        $sql .= "-- {$count} row(s) from JSON backup\n";
        $sql .= "INSERT IGNORE INTO `report_prompt_databases` (`id`, `name`, `type`, `host`, `dbName`, `username`, `password`, `port`, `createdAt`) VALUES\n";
        
        $valueRows = [];
        foreach ($allConnections as $conn) {
            $createdAt = isset($conn['createdAt']) ? date('Y-m-d H:i:s', strtotime($conn['createdAt'])) : date('Y-m-d H:i:s');
            $id       = addslashes($conn['id'] ?? '');
            $name     = addslashes($conn['name'] ?? '');
            $type     = addslashes($conn['type'] ?? 'shared');
            $host     = addslashes($conn['host'] ?? '');
            $dbName   = addslashes($conn['dbName'] ?? '');
            $username = addslashes($conn['username'] ?? '');
            $password = addslashes($conn['password'] ?? '');
            $port     = addslashes($conn['port'] ?? '3306');
            
            $valueRows[] = "    ('{$id}', '{$name}', '{$type}', '{$host}', '{$dbName}', '{$username}', '{$password}', '{$port}', '{$createdAt}')";
        }
        
        $sql .= implode(",\n", $valueRows) . ";\n\n";
        
        $totalTables++;
        $totalRows += $count;
        $tableStats['report_prompt_databases'] = $count;
        
        output("  ✅ Extracted {$count} connections from JSON backups");
    } else {
        $sql .= "-- WARNING: No JSON backup data found for report_prompt_databases\n\n";
        output("  ⚠️  No JSON backup data found");
    }
    
    $sql .= "-- TABLE: reporter_prompt_table - Cannot export without live DB connection\n";
    $sql .= "-- Run this script from a server that can reach {$db1['host']} to include this data\n\n";
    
    output("");
}

// ============================================================
// DATABASE 2: u419999707_Mohamed
// ============================================================

output("📡 Connecting to Database 2: {$db2['dbname']}...");
$pdo2 = connectDB($db2);

if ($pdo2) {
    output("✅ Connected to {$db2['dbname']}");
    
    $sql .= "-- ============================================================\n";
    $sql .= "-- DATABASE SOURCE: {$db2['dbname']}\n";
    $sql .= "-- Host: {$db2['host']}\n";
    $sql .= "-- Tables: " . implode(', ', $db2['tables']) . "\n";
    $sql .= "-- ============================================================\n\n";
    
    foreach ($db2['tables'] as $table) {
        output("  📋 Exporting table: {$table}...");
        
        // Check if table exists
        try {
            $check = $pdo2->query("SHOW TABLES LIKE '{$table}'");
            if ($check->rowCount() === 0) {
                output("    ⚠️  Table '{$table}' does not exist - skipping");
                $sql .= "-- TABLE: {$table} - Does not exist in source database, skipped\n\n";
                continue;
            }
        } catch (Exception $e) {
            output("    ❌ Error checking table '{$table}': " . $e->getMessage());
            continue;
        }
        
        $sql .= "-- ============================================================\n";
        $sql .= "-- TABLE: {$table}\n";
        $sql .= "-- ============================================================\n";
        
        try {
            $result = generateInserts($pdo2, $table);
            
            // Replace INSERT with INSERT IGNORE for safety
            $safeSql = str_replace('INSERT INTO', 'INSERT IGNORE INTO', $result['sql']);
            $sql .= $safeSql;
            
            $totalTables++;
            $totalRows += $result['count'];
            $tableStats[$table] = $result['count'];
            
            output("    ✅ Exported {$result['count']} rows");
        } catch (Exception $e) {
            output("    ❌ Error exporting '{$table}': " . $e->getMessage());
            $sql .= "-- ERROR: Failed to export - " . $e->getMessage() . "\n\n";
        }
    }
    
    $pdo2 = null; // Close connection
    output("");
} else {
    output("❌ Could not connect to {$db2['dbname']}");
    $sql .= "-- ============================================================\n";
    $sql .= "-- DATABASE SOURCE: {$db2['dbname']} - CONNECTION FAILED\n";
    $sql .= "-- Run this script from a server that can reach {$db2['host']}\n";
    $sql .= "-- ============================================================\n\n";
    output("");
}

// ============================================================
// FOOTER
// ============================================================

$sql .= "\n-- ============================================================\n";
$sql .= "-- FINALIZE TRANSACTION\n";
$sql .= "-- ============================================================\n\n";
$sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";
$sql .= "COMMIT;\n\n";

// Summary block
$sql .= "-- ============================================================\n";
$sql .= "-- MIGRATION SUMMARY\n";
$sql .= "-- ============================================================\n";
$sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
$sql .= "-- Total Tables Exported: {$totalTables}\n";
$sql .= "-- Total Rows Exported:   {$totalRows}\n";
$sql .= "--\n";
$sql .= "-- Per-Table Breakdown:\n";
foreach ($tableStats as $t => $c) {
    $sql .= "--   {$t}: {$c} rows\n";
}
$sql .= "--\n";
$sql .= "-- INSTRUCTIONS:\n";
$sql .= "--   1. First run: schema_structure.sql  (creates all 11 tables)\n";
$sql .= "--   2. Then run:  data_migration.sql    (this file - populates data)\n";
$sql .= "--   3. Verify with: SELECT COUNT(*) FROM each table\n";
$sql .= "-- ============================================================\n";

// ============================================================
// WRITE FILE
// ============================================================

$bytesWritten = file_put_contents($outputFile, $sql);

$elapsedMs = round((microtime(true) - $startTime) * 1000, 2);

output("============================================================");
output("  EXPORT COMPLETE");
output("============================================================");
output("");
output("  📁 Output File:    {$outputFile}");
output("  📊 File Size:      " . number_format($bytesWritten) . " bytes (" . round($bytesWritten / 1024, 2) . " KB)");
output("  📋 Tables Exported: {$totalTables}");
output("  📝 Total Rows:      {$totalRows}");
output("  ⏱️  Duration:       {$elapsedMs}ms");
output("");
output("  Per-Table Breakdown:");
foreach ($tableStats as $t => $c) {
    output("    • {$t}: {$c} rows");
}
output("");
output("  NEXT STEPS:");
output("    1. Run schema_structure.sql on target database");
output("    2. Run data_migration.sql on target database");
output("    3. Verify row counts match");
output("============================================================");

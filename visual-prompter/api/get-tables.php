<?php
/**
 * API Endpoint to fetch tables and their structure from a database
 * Returns JSON list of tables with columns info
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
    exit;
}

// Extract connection parameters
$host = trim($input['host'] ?? '');
$port = intval($input['port'] ?? 3306);
$dbName = trim($input['dbName'] ?? '');
$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';
$dbType = strtolower(trim($input['dbType'] ?? 'mysql'));

// Validate required fields
if (empty($host) || empty($dbName) || empty($username)) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'error' => 'Missing required fields',
        'message' => 'Host, database name, and username are required'
    ]);
    exit;
}

try {
    $pdo = null;
    $tables = [];
    
    switch ($dbType) {
        case 'mysql':
            $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 10
            ]);
            
            // Get all tables
            $stmt = $pdo->query("SHOW TABLES");
            $tableNames = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($tableNames as $tableName) {
                // Get table structure
                $columnsStmt = $pdo->query("DESCRIBE `{$tableName}`");
                $columns = $columnsStmt->fetchAll();
                
                // Get row count
                $countStmt = $pdo->query("SELECT COUNT(*) as count FROM `{$tableName}`");
                $rowCount = $countStmt->fetch()['count'];
                
                // Get primary key
                $primaryKey = null;
                foreach ($columns as $col) {
                    if ($col['Key'] === 'PRI') {
                        $primaryKey = $col['Field'];
                        break;
                    }
                }
                
                $tables[] = [
                    'name' => $tableName,
                    'rowCount' => intval($rowCount),
                    'primaryKey' => $primaryKey,
                    'columns' => array_map(function($col) {
                        return [
                            'name' => $col['Field'],
                            'type' => $col['Type'],
                            'nullable' => $col['Null'] === 'YES',
                            'key' => $col['Key'],
                            'default' => $col['Default'],
                            'extra' => $col['Extra']
                        ];
                    }, $columns)
                ];
            }
            break;
            
        case 'postgresql':
        case 'postgres':
            $dsn = "pgsql:host={$host};port={$port};dbname={$dbName}";
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 10
            ]);
            
            // Get all tables
            $stmt = $pdo->query("
                SELECT tablename 
                FROM pg_tables 
                WHERE schemaname = 'public'
                ORDER BY tablename
            ");
            $tableNames = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($tableNames as $tableName) {
                // Get table structure
                $columnsStmt = $pdo->prepare("
                    SELECT column_name, data_type, is_nullable, column_default
                    FROM information_schema.columns 
                    WHERE table_name = ?
                    ORDER BY ordinal_position
                ");
                $columnsStmt->execute([$tableName]);
                $columns = $columnsStmt->fetchAll();
                
                // Get row count
                $countStmt = $pdo->query("SELECT COUNT(*) as count FROM \"{$tableName}\"");
                $rowCount = $countStmt->fetch()['count'];
                
                $tables[] = [
                    'name' => $tableName,
                    'rowCount' => intval($rowCount),
                    'columns' => array_map(function($col) {
                        return [
                            'name' => $col['column_name'],
                            'type' => $col['data_type'],
                            'nullable' => $col['is_nullable'] === 'YES',
                            'default' => $col['column_default']
                        ];
                    }, $columns)
                ];
            }
            break;
            
        case 'sqlite':
            $dsn = "sqlite:{$dbName}";
            $pdo = new PDO($dsn, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            
            // Get all tables
            $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            $tableNames = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($tableNames as $tableName) {
                // Get table structure
                $columnsStmt = $pdo->query("PRAGMA table_info(`{$tableName}`)");
                $columns = $columnsStmt->fetchAll();
                
                // Get row count
                $countStmt = $pdo->query("SELECT COUNT(*) as count FROM `{$tableName}`");
                $rowCount = $countStmt->fetch()['count'];
                
                $tables[] = [
                    'name' => $tableName,
                    'rowCount' => intval($rowCount),
                    'columns' => array_map(function($col) {
                        return [
                            'name' => $col['name'],
                            'type' => $col['type'],
                            'nullable' => $col['notnull'] == 0,
                            'default' => $col['dflt_value'],
                            'primaryKey' => $col['pk'] == 1
                        ];
                    }, $columns)
                ];
            }
            break;
            
        default:
            // Try MySQL as default
            $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 10
            ]);
            
            $stmt = $pdo->query("SHOW TABLES");
            $tableNames = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($tableNames as $tableName) {
                $columnsStmt = $pdo->query("DESCRIBE `{$tableName}`");
                $columns = $columnsStmt->fetchAll();
                
                $countStmt = $pdo->query("SELECT COUNT(*) as count FROM `{$tableName}`");
                $rowCount = $countStmt->fetch()['count'];
                
                $tables[] = [
                    'name' => $tableName,
                    'rowCount' => intval($rowCount),
                    'columns' => array_map(function($col) {
                        return [
                            'name' => $col['Field'],
                            'type' => $col['Type'],
                            'nullable' => $col['Null'] === 'YES',
                            'key' => $col['Key'],
                            'default' => $col['Default']
                        ];
                    }, $columns)
                ];
            }
    }
    
    // Close connection
    $pdo = null;
    
    echo json_encode([
        'success' => true,
        'tables' => $tables,
        'count' => count($tables),
        'database' => $dbName,
        'dbType' => $dbType
    ], JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    http_response_code(200);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch tables',
        'message' => $e->getMessage(),
        'database' => $dbName
    ]);
    
} catch (Exception $e) {
    http_response_code(200);
    echo json_encode([
        'success' => false,
        'error' => 'Error',
        'message' => $e->getMessage()
    ]);
}


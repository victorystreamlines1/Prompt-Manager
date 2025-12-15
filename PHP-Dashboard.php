<?php
/**
 * ╔══════════════════════════════════════════════════════════════════════════════╗
 * ║                     PHP-DASHBOARD.PHP - ALL-IN-ONE APPLICATION               ║
 * ╠══════════════════════════════════════════════════════════════════════════════╣
 * ║  This file combines:                                                          ║
 * ║  • PHP Backend API (handles database operations)                              ║
 * ║  • HTML Frontend (user interface)                                             ║
 * ║  • CSS Styling (visual design)                                                ║
 * ║  • JavaScript (client-side logic)                                             ║
 * ╠══════════════════════════════════════════════════════════════════════════════╣
 * ║  STRUCTURE:                                                                   ║
 * ║  [SECTION 1] PHP Backend - API Handler (lines ~1-600)                         ║
 * ║  [SECTION 2] HTML Frontend - User Interface (lines ~600+)                     ║
 * ║  [SECTION 3] CSS Styling - Inside <style> tags                                ║
 * ║  [SECTION 4] JavaScript - Inside <script> tags                                ║
 * ╠══════════════════════════════════════════════════════════════════════════════╣
 * ║  HOW IT WORKS:                                                                ║
 * ║  1. If request has 'action' parameter → Process as API request → Return JSON  ║
 * ║  2. If no 'action' parameter → Display HTML Dashboard                         ║
 * ╚══════════════════════════════════════════════════════════════════════════════╝
 */

// ============================================================================
// [SECTION 1] PHP BACKEND - API HANDLER
// ============================================================================
// This section handles all database operations via AJAX requests
// All API requests must include 'action' parameter
// ============================================================================

// Error handling configuration
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_dashboard_errors.log');

// Set CORS headers for API requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 86400');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ============================================================================
// [1.1] CHECK IF THIS IS AN API REQUEST
// ============================================================================
// API requests have 'action' parameter in POST or GET
$rawInput = file_get_contents('php://input');
$jsonData = json_decode($rawInput, true) ?? [];
$requestData = array_merge($_GET, $_POST, $jsonData);
$isApiRequest = isset($requestData['action']) || isset($_GET['action']) || isset($_POST['action']);

// If this is an API request, process it and exit (don't show HTML)
if ($isApiRequest) {
    
    // Set JSON content type for API responses
    header('Content-Type: application/json; charset=utf-8');
    
    // ========================================================================
    // [1.2] CORE FUNCTIONS
    // ========================================================================
    
    /**
     * Send JSON response and exit
     */
    function jsonResponse($success, $message, $data = null, $code = 200) {
        http_response_code($code);
        $response = [
            'success' => $success,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s'),
            'server' => $_SERVER['SERVER_NAME'] ?? 'localhost'
        ];
        
        if ($data !== null && is_array($data)) {
            $response = array_merge($response, $data);
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit();
    }
    
    /**
     * Get database connection using PDO
     * Enhanced with better error messages for localhost debugging
     */
    function getConnection($config) {
        try {
            // Build DSN
            $dsn = "mysql:host={$config['host']};port={$config['port']};charset={$config['charset']}";
            if (!empty($config['dbname'])) {
                $dsn .= ";dbname={$config['dbname']}";
            }
            
            // Handle empty password (common for localhost)
            $password = ($config['password'] === '' || $config['password'] === null) ? null : $config['password'];
            
            // PDO options for better connection handling
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 5, // 5 second timeout
            ];
            
            // Try connection
            if ($password === null) {
                $pdo = new PDO($dsn, $config['username'], null, $options);
            } else {
                $pdo = new PDO($dsn, $config['username'], $password, $options);
            }
            
            return $pdo;
        } catch (PDOException $e) {
            // Enhanced error message for debugging
            $errorCode = $e->getCode();
            $errorMsg = $e->getMessage();
            
            // Common error translations
            $hints = '';
            if (strpos($errorMsg, 'Access denied') !== false) {
                $hints = ' | Hint: Check username/password. For Laragon, try root with empty password.';
            } elseif (strpos($errorMsg, 'Connection refused') !== false || strpos($errorMsg, 'No connection') !== false) {
                $hints = ' | Hint: MySQL server may not be running. Start Laragon/XAMPP MySQL service.';
            } elseif (strpos($errorMsg, 'Unknown database') !== false) {
                $hints = ' | Hint: Database does not exist. Create it first.';
            } elseif ($errorCode == 2002) {
                $hints = ' | Hint: Cannot reach MySQL. Try 127.0.0.1 instead of localhost, or check if MySQL is running.';
            }
            
            jsonResponse(false, "Connection failed ({$config['host']}:{$config['port']}): {$errorMsg}{$hints}", [
                'error_code' => $errorCode,
                'host' => $config['host'],
                'port' => $config['port'],
                'user' => $config['username']
            ], 500);
        }
    }
    
    /**
     * Sanitize table/column names (prevent SQL injection)
     */
    function sanitizeName($name) {
        return preg_replace('/[^a-zA-Z0-9_]/', '', $name);
    }
    
    /**
     * Validate required parameters
     */
    function validateParams($params, $required) {
        $missing = [];
        foreach ($required as $field) {
            if (!isset($params[$field]) || $params[$field] === '') {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            jsonResponse(false, 'Missing required parameters: ' . implode(', ', $missing), null, 400);
        }
    }
    
    // ========================================================================
    // [1.3] DATABASE OPERATIONS
    // ========================================================================
    
    function createDatabase($pdo, $dbName) {
        $dbName = sanitizeName($dbName);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        return "Database '$dbName' created successfully";
    }
    
    function dropDatabase($pdo, $dbName) {
        $dbName = sanitizeName($dbName);
        $pdo->exec("DROP DATABASE IF EXISTS `$dbName`");
        return "Database '$dbName' dropped successfully";
    }
    
    function listDatabases($pdo) {
        $stmt = $pdo->query("SHOW DATABASES");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    function getDatabaseInfo($pdo, $dbName) {
        $stmt = $pdo->query("SELECT VERSION() as version");
        $versionInfo = $stmt->fetch();
        
        $stmt = $pdo->query("SELECT COUNT(*) as table_count FROM information_schema.tables WHERE table_schema = '$dbName'");
        $tableCount = $stmt->fetch();
        
        $stmt = $pdo->query("
            SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb 
            FROM information_schema.tables 
            WHERE table_schema = '$dbName'
        ");
        $dbSize = $stmt->fetch();
        
        return [
            'database_name' => $dbName,
            'mysql_version' => $versionInfo['version'],
            'table_count' => (int)$tableCount['table_count'],
            'database_size_mb' => $dbSize['size_mb'] ?? 0
        ];
    }
    
    // ========================================================================
    // [1.4] TABLE OPERATIONS
    // ========================================================================
    
    function createTable($pdo, $tableName, $columns) {
        $tableName = sanitizeName($tableName);
        
        if (empty($columns) || !is_array($columns)) {
            throw new Exception("Columns definition required");
        }
        
        $columnDefs = [];
        $primaryKeys = [];
        
        foreach ($columns as $col) {
            $name = sanitizeName($col['name']);
            $type = strtoupper($col['type'] ?? 'VARCHAR');
            
            if (!empty($col['length'])) {
                $type .= '(' . intval($col['length']) . ')';
            }
            
            $def = "`$name` $type";
            
            if (isset($col['nullable']) && $col['nullable'] === 'no') {
                $def .= ' NOT NULL';
            }
            
            if (isset($col['autoIncrement']) && $col['autoIncrement'] === 'yes') {
                $def .= ' AUTO_INCREMENT';
            }
            
            if (isset($col['defaultValue']) && $col['defaultValue'] !== '') {
                if (strtoupper($col['defaultValue']) === 'NULL') {
                    $def .= ' DEFAULT NULL';
                } elseif (strtoupper($col['defaultValue']) === 'CURRENT_TIMESTAMP') {
                    $def .= ' DEFAULT CURRENT_TIMESTAMP';
                } else {
                    $def .= ' DEFAULT ' . $pdo->quote($col['defaultValue']);
                }
            }
            
            if (isset($col['unique']) && $col['unique'] === 'yes') {
                $def .= ' UNIQUE';
            }
            
            $columnDefs[] = $def;
            
            if (isset($col['primaryKey']) && $col['primaryKey'] === 'yes') {
                $primaryKeys[] = "`$name`";
            }
        }
        
        $sql = "CREATE TABLE IF NOT EXISTS `$tableName` (" . implode(', ', $columnDefs);
        
        if (count($primaryKeys) > 0) {
            $sql .= ', PRIMARY KEY (' . implode(', ', $primaryKeys) . ')';
        }
        
        $sql .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        return "Table '$tableName' created successfully";
    }
    
    function dropTable($pdo, $tableName) {
        $tableName = sanitizeName($tableName);
        $pdo->exec("DROP TABLE IF EXISTS `$tableName`");
        return "Table '$tableName' dropped successfully";
    }
    
    function listTables($pdo) {
        $stmt = $pdo->query("SHOW TABLES");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    function describeTable($pdo, $tableName) {
        $tableName = sanitizeName($tableName);
        $stmt = $pdo->query("DESCRIBE `$tableName`");
        return $stmt->fetchAll();
    }
    
    function truncateTable($pdo, $tableName) {
        $tableName = sanitizeName($tableName);
        $pdo->exec("TRUNCATE TABLE `$tableName`");
        return "Table '$tableName' truncated successfully";
    }
    
    function renameDatabase($pdo, $oldName, $newName) {
        $oldName = sanitizeName($oldName);
        $newName = sanitizeName($newName);
        
        if ($oldName === $newName) {
            throw new Exception("New name must be different from old name");
        }
        
        $stmt = $pdo->query("SHOW DATABASES LIKE " . $pdo->quote($oldName));
        if (!$stmt->fetch()) {
            throw new Exception("Source database not found");
        }
        
        $stmt = $pdo->query("SHOW DATABASES LIKE " . $pdo->quote($newName));
        if ($stmt->fetch()) {
            throw new Exception("Target database already exists");
        }
        
        $pdo->exec("CREATE DATABASE `$newName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        $stmt = $pdo->query("SHOW TABLES FROM `$oldName`");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($tables as $table) {
            $safeTable = sanitizeName($table);
            $pdo->exec("RENAME TABLE `$oldName`.`$safeTable` TO `$newName`.`$safeTable`");
        }
        
        $pdo->exec("DROP DATABASE `$oldName`");
        
        return "Database renamed from '$oldName' to '$newName' successfully";
    }
    
    function renameTable($pdo, $oldName, $newName) {
        $oldName = sanitizeName($oldName);
        $newName = sanitizeName($newName);
        
        if ($oldName === $newName) {
            throw new Exception("New name must be different from old name");
        }
        
        $pdo->exec("RENAME TABLE `$oldName` TO `$newName`");
        
        return "Table renamed from '$oldName' to '$newName' successfully";
    }
    
    function alterTable($pdo, $tableName, $action, $columnData) {
        $tableName = sanitizeName($tableName);
        
        if (!is_array($columnData) || empty($columnData['name'])) {
            throw new Exception("Invalid column data");
        }
        
        switch ($action) {
            case 'add':
                $colName = sanitizeName($columnData['name']);
                $colType = strtoupper($columnData['type'] ?? 'VARCHAR');
                
                if (!empty($columnData['length'])) {
                    $colType .= '(' . intval($columnData['length']) . ')';
                }
                
                $colDef = "`$colName` $colType";
                
                if (!empty($columnData['nullable']) && $columnData['nullable'] === 'no') {
                    $colDef .= ' NOT NULL';
                }
                
                if (isset($columnData['defaultValue']) && $columnData['defaultValue'] !== '') {
                    if (strtoupper($columnData['defaultValue']) === 'NULL') {
                        $colDef .= ' DEFAULT NULL';
                    } elseif (strtoupper($columnData['defaultValue']) === 'CURRENT_TIMESTAMP') {
                        $colDef .= ' DEFAULT CURRENT_TIMESTAMP';
                    } else {
                        $colDef .= ' DEFAULT ' . $pdo->quote($columnData['defaultValue']);
                    }
                }
                
                $sql = "ALTER TABLE `$tableName` ADD COLUMN $colDef";
                $pdo->exec($sql);
                return "Column added successfully";
                
            case 'modify':
                $colName = sanitizeName($columnData['name']);
                $colType = strtoupper($columnData['type'] ?? 'VARCHAR');
                
                if (!empty($columnData['length'])) {
                    $colType .= '(' . intval($columnData['length']) . ')';
                }
                
                $colDef = "`$colName` $colType";
                
                if (!empty($columnData['nullable']) && $columnData['nullable'] === 'no') {
                    $colDef .= ' NOT NULL';
                }
                
                if (isset($columnData['defaultValue']) && $columnData['defaultValue'] !== '') {
                    if (strtoupper($columnData['defaultValue']) === 'NULL') {
                        $colDef .= ' DEFAULT NULL';
                    } elseif (strtoupper($columnData['defaultValue']) === 'CURRENT_TIMESTAMP') {
                        $colDef .= ' DEFAULT CURRENT_TIMESTAMP';
                    } else {
                        $colDef .= ' DEFAULT ' . $pdo->quote($columnData['defaultValue']);
                    }
                }
                
                $sql = "ALTER TABLE `$tableName` MODIFY COLUMN $colDef";
                $pdo->exec($sql);
                return "Column modified successfully";
                
            case 'drop':
                $colName = sanitizeName($columnData['name']);
                $sql = "ALTER TABLE `$tableName` DROP COLUMN `$colName`";
                $pdo->exec($sql);
                return "Column dropped successfully";
                
            default:
                throw new Exception("Invalid alter action. Use: add, modify, or drop");
        }
    }
    
    // ========================================================================
    // [1.5] CRUD OPERATIONS
    // ========================================================================
    
    function insertRecord($pdo, $tableName, $data) {
        $tableName = sanitizeName($tableName);
        
        if (empty($data)) {
            throw new Exception("No data provided for insert");
        }
        
        $columns = array_keys($data);
        $columns = array_map('sanitizeName', $columns);
        $placeholders = array_fill(0, count($columns), '?');
        
        $sql = "INSERT INTO `$tableName` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_values($data));
        
        return [
            'inserted_id' => $pdo->lastInsertId(),
            'affected_rows' => $stmt->rowCount(),
            'message' => 'Record inserted successfully'
        ];
    }
    
    function selectRecords($pdo, $tableName, $conditions = [], $limit = null, $offset = null, $orderBy = null) {
        $tableName = sanitizeName($tableName);
        
        $sql = "SELECT * FROM `$tableName`";
        $params = [];
        
        if (!empty($conditions)) {
            $whereClauses = [];
            foreach ($conditions as $field => $value) {
                $field = sanitizeName($field);
                $whereClauses[] = "`$field` = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $whereClauses);
        }
        
        if ($orderBy) {
            $orderField = sanitizeName($orderBy['field'] ?? 'id');
            $orderDir = strtoupper($orderBy['direction'] ?? 'ASC');
            $orderDir = in_array($orderDir, ['ASC', 'DESC']) ? $orderDir : 'ASC';
            $sql .= " ORDER BY `$orderField` $orderDir";
        }
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
            if ($offset) {
                $sql .= " OFFSET " . (int)$offset;
            }
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    function updateRecord($pdo, $tableName, $data, $conditions) {
        $tableName = sanitizeName($tableName);
        
        if (empty($data)) {
            throw new Exception("No data provided for update");
        }
        
        if (empty($conditions)) {
            throw new Exception("No conditions provided for update (safety measure)");
        }
        
        $setClauses = [];
        $params = [];
        foreach ($data as $field => $value) {
            $field = sanitizeName($field);
            $setClauses[] = "`$field` = ?";
            $params[] = $value;
        }
        
        $whereClauses = [];
        foreach ($conditions as $field => $value) {
            $field = sanitizeName($field);
            $whereClauses[] = "`$field` = ?";
            $params[] = $value;
        }
        
        $sql = "UPDATE `$tableName` SET " . implode(', ', $setClauses) . " WHERE " . implode(' AND ', $whereClauses);
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return [
            'affected_rows' => $stmt->rowCount(),
            'message' => 'Record updated successfully'
        ];
    }
    
    function deleteRecord($pdo, $tableName, $conditions) {
        $tableName = sanitizeName($tableName);
        
        if (empty($conditions)) {
            throw new Exception("No conditions provided for delete (safety measure)");
        }
        
        $whereClauses = [];
        $params = [];
        foreach ($conditions as $field => $value) {
            $field = sanitizeName($field);
            $whereClauses[] = "`$field` = ?";
            $params[] = $value;
        }
        
        $sql = "DELETE FROM `$tableName` WHERE " . implode(' AND ', $whereClauses);
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return [
            'affected_rows' => $stmt->rowCount(),
            'message' => 'Record deleted successfully'
        ];
    }
    
    function executeCustomQuery($pdo, $query, $params = []) {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        
        if (stripos(trim($query), 'SELECT') === 0) {
            return $stmt->fetchAll();
        }
        
        return [
            'affected_rows' => $stmt->rowCount(),
            'message' => 'Query executed successfully'
        ];
    }
    
    // ========================================================================
    // [1.6] REQUEST HANDLER - PROCESS API ACTIONS
    // ========================================================================
    
    try {
        $action = $requestData['action'] ?? 'test_connection';
        
        // Initialize database config (from request parameters)
        // For database-level operations (create, drop, list, rename), don't specify dbname
        $serverOnlyActions = ['create_database', 'drop_database', 'delete_database', 'list_databases', 'rename_database'];
        $isServerOnlyAction = in_array($action, $serverOnlyActions);
        
        $config = [
            'host' => $requestData['db_host'] ?? '',
            'dbname' => $isServerOnlyAction ? '' : ($requestData['db_name'] ?? ''), // Empty for server-level operations
            'username' => $requestData['db_user'] ?? '',
            'password' => $requestData['db_pass'] ?? '',
            'port' => $requestData['db_port'] ?? '3306',
            'charset' => 'utf8mb4'
        ];
        
        // Validate credentials
        if (empty($config['host']) || empty($config['username'])) {
            jsonResponse(false, 
                '⚠️ Missing database credentials. Please provide db_host, db_user, db_name, and db_pass.', 
                [
                    'received' => [
                        'host' => !empty($config['host']) ? 'provided' : 'missing',
                        'username' => !empty($config['username']) ? 'provided' : 'missing',
                        'dbname' => !empty($config['dbname']) ? 'provided' : 'missing',
                        'password' => isset($requestData['db_pass']) ? 'provided' : 'missing'
                    ],
                    'action' => $action,
                    'isServerOnly' => $isServerOnlyAction
                ], 
                400
            );
        }
        
        // Connect to database (or just server for create/drop/list operations)
        $pdo = getConnection($config);
        
        // Route actions
        switch ($action) {
            
            // ===== DATABASE OPERATIONS =====
            case 'create_database':
                validateParams($requestData, ['db_name']);
                $dbName = sanitizeName($requestData['db_name']);
                $dbUsername = isset($requestData['db_username']) ? sanitizeName($requestData['db_username']) : '';
                $dbPassword = $requestData['db_password'] ?? '';
                
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $message = "Database '$dbName' created successfully";
                
                if (!empty($dbUsername) && !empty($dbPassword)) {
                    $pdo->exec("CREATE USER IF NOT EXISTS '$dbUsername'@'localhost' IDENTIFIED BY '$dbPassword'");
                    $pdo->exec("GRANT ALL PRIVILEGES ON `$dbName`.* TO '$dbUsername'@'localhost'");
                    $pdo->exec("FLUSH PRIVILEGES");
                    $message .= " with user '$dbUsername'";
                }
                
                jsonResponse(true, $message);
                break;
                
            case 'drop_database':
            case 'delete_database':
                validateParams($requestData, ['db_name']);
                $result = dropDatabase($pdo, $requestData['db_name']);
                jsonResponse(true, $result);
                break;
                
            case 'list_databases':
                $databases = listDatabases($pdo);
                jsonResponse(true, 'Databases retrieved', [
                    'databases' => $databases,
                    'count' => count($databases)
                ]);
                break;
                
            case 'database_info':
                $info = getDatabaseInfo($pdo, $config['dbname']);
                jsonResponse(true, 'Database info retrieved', $info);
                break;
                
            case 'rename_database':
                validateParams($requestData, ['old_name', 'new_name']);
                $result = renameDatabase($pdo, $requestData['old_name'], $requestData['new_name']);
                jsonResponse(true, $result);
                break;
                
            // ===== TABLE OPERATIONS =====
            case 'create_table':
                validateParams($requestData, ['table_name', 'columns']);
                $columnsData = is_string($requestData['columns']) 
                    ? json_decode($requestData['columns'], true) 
                    : $requestData['columns'];
                $result = createTable($pdo, $requestData['table_name'], $columnsData);
                jsonResponse(true, $result);
                break;
                
            case 'drop_table':
            case 'delete_table':
                $tableName = $requestData['table'] ?? $requestData['table_name'] ?? '';
                validateParams(['table_name' => $tableName], ['table_name']);
                $result = dropTable($pdo, $tableName);
                jsonResponse(true, $result);
                break;
                
            case 'list_tables':
                $tables = listTables($pdo);
                jsonResponse(true, 'Tables retrieved', [
                    'tables' => $tables,
                    'count' => count($tables)
                ]);
                break;
                
            case 'describe_table':
                validateParams($requestData, ['table']);
                $structure = describeTable($pdo, $requestData['table']);
                jsonResponse(true, 'Table structure retrieved', ['structure' => $structure]);
                break;
                
            case 'truncate_table':
                validateParams($requestData, ['table']);
                $result = truncateTable($pdo, $requestData['table']);
                jsonResponse(true, $result);
                break;
                
            case 'rename_table':
                validateParams($requestData, ['old_table_name', 'new_table_name']);
                $result = renameTable($pdo, $requestData['old_table_name'], $requestData['new_table_name']);
                jsonResponse(true, $result);
                break;
                
            case 'alter_table':
                validateParams($requestData, ['table_name', 'alter_action', 'column_data']);
                $columnData = is_string($requestData['column_data']) 
                    ? json_decode($requestData['column_data'], true) 
                    : $requestData['column_data'];
                $result = alterTable($pdo, $requestData['table_name'], $requestData['alter_action'], $columnData);
                jsonResponse(true, $result);
                break;
                
            // ===== CONNECTION CHECK =====
            case 'check_connection':
                try {
                    $stmt = $pdo->query("SELECT 1");
                    $info = getDatabaseInfo($pdo, $config['dbname']);
                    jsonResponse(true, '✅ Connection successful', [
                        'database' => $config['dbname'],
                        'host' => $config['host'],
                        'table_count' => $info['table_count'],
                        'connected_at' => date('Y-m-d H:i:s')
                    ]);
                } catch (Exception $e) {
                    jsonResponse(false, 'Connection test failed: ' . $e->getMessage(), null, 500);
                }
                break;
                
            // ===== GET TABLE STRUCTURE =====
            case 'get_table_structure':
                validateParams($requestData, ['table_name']);
                $tableName = sanitizeName($requestData['table_name']);
                $stmt = $pdo->query("DESCRIBE `$tableName`");
                $columns = $stmt->fetchAll();
                jsonResponse(true, 'Table structure retrieved', ['columns' => $columns]);
                break;
                
            // ===== GET TABLE DATA WITH PAGINATION =====
            case 'get_table_data':
                validateParams($requestData, ['table_name']);
                $tableName = sanitizeName($requestData['table_name']);
                $page = isset($requestData['page']) ? (int)$requestData['page'] : 1;
                $limit = isset($requestData['limit']) ? (int)$requestData['limit'] : 50;
                $offset = ($page - 1) * $limit;
                
                $stmt = $pdo->query("SELECT COUNT(*) as total FROM `$tableName`");
                $totalRows = $stmt->fetchColumn();
                
                $stmt = $pdo->query("SELECT * FROM `$tableName` LIMIT $limit OFFSET $offset");
                $data = $stmt->fetchAll();
                
                $stmt = $pdo->query("DESCRIBE `$tableName`");
                $columns = $stmt->fetchAll();
                
                $stmt = $pdo->query("SHOW TABLE STATUS LIKE '$tableName'");
                $tableInfo = $stmt->fetch();
                
                jsonResponse(true, 'Table data retrieved', [
                    'data' => $data,
                    'columns' => $columns,
                    'table_info' => [
                        'engine' => $tableInfo['Engine'] ?? 'Unknown',
                        'collation' => $tableInfo['Collation'] ?? 'Unknown',
                        'data_length' => $tableInfo['Data_length'] ?? 0,
                        'avg_row_length' => $tableInfo['Avg_row_length'] ?? 0,
                        'created' => $tableInfo['Create_time'] ?? null,
                        'updated' => $tableInfo['Update_time'] ?? null
                    ],
                    'pagination' => [
                        'current_page' => $page,
                        'total_rows' => (int)$totalRows,
                        'total_pages' => ceil($totalRows / $limit),
                        'limit' => $limit,
                        'showing_from' => $offset + 1,
                        'showing_to' => min($offset + $limit, $totalRows)
                    ]
                ]);
                break;
                
            // ===== GENERATE TABLE SQL =====
            case 'generate_table_sql':
                validateParams($requestData, ['table_name']);
                $tableName = sanitizeName($requestData['table_name']);
                $includeData = isset($requestData['include_data']) && $requestData['include_data'] === 'true';
                
                $sql = "-- Table: $tableName\n\n";
                $sql .= "DROP TABLE IF EXISTS `$tableName`;\n\n";
                
                $stmt = $pdo->query("SHOW CREATE TABLE `$tableName`");
                $createTable = $stmt->fetch();
                $sql .= $createTable['Create Table'] . ";\n\n";
                
                $rowCount = 0;
                
                if ($includeData) {
                    $stmt = $pdo->query("SELECT * FROM `$tableName`");
                    $rows = $stmt->fetchAll();
                    $rowCount = count($rows);
                    
                    if ($rowCount > 0) {
                        $sql .= "-- Data for table: $tableName\n\n";
                        
                        foreach ($rows as $row) {
                            $values = [];
                            foreach ($row as $value) {
                                if ($value === null) {
                                    $values[] = 'NULL';
                                } else {
                                    $values[] = $pdo->quote($value);
                                }
                            }
                            $sql .= "INSERT INTO `$tableName` VALUES (" . implode(', ', $values) . ");\n";
                        }
                        $sql .= "\n";
                    }
                }
                
                jsonResponse(true, 'SQL generated successfully', [
                    'sql' => $sql,
                    'table_name' => $tableName,
                    'has_data' => $includeData,
                    'row_count' => $rowCount,
                    'sql_length' => strlen($sql)
                ]);
                break;
                
            // ===== GENERATE DATABASE SQL =====
            case 'generate_database_sql':
                $includeCreateDB = isset($requestData['include_create_db']) && $requestData['include_create_db'] === 'true';
                $includeData = isset($requestData['include_data']) && $requestData['include_data'] === 'true';
                
                $sql = "";
                $totalRows = 0;
                
                if ($includeCreateDB) {
                    $sql .= "-- Database: {$config['dbname']}\n\n";
                    $sql .= "CREATE DATABASE IF NOT EXISTS `{$config['dbname']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n";
                    $sql .= "USE `{$config['dbname']}`;\n\n";
                }
                
                $tables = listTables($pdo);
                
                foreach ($tables as $table) {
                    $tableName = sanitizeName($table);
                    $sql .= "-- Table: $tableName\n\n";
                    $sql .= "DROP TABLE IF EXISTS `$tableName`;\n\n";
                    
                    $stmt = $pdo->query("SHOW CREATE TABLE `$tableName`");
                    $createTable = $stmt->fetch();
                    $sql .= $createTable['Create Table'] . ";\n\n";
                    
                    if ($includeData) {
                        $stmt = $pdo->query("SELECT * FROM `$tableName`");
                        $rows = $stmt->fetchAll();
                        $totalRows += count($rows);
                        
                        if (count($rows) > 0) {
                            $sql .= "-- Data for table: $tableName\n\n";
                            foreach ($rows as $row) {
                                $values = [];
                                foreach ($row as $value) {
                                    $values[] = ($value === null) ? 'NULL' : $pdo->quote($value);
                                }
                                $sql .= "INSERT INTO `$tableName` VALUES (" . implode(', ', $values) . ");\n";
                            }
                            $sql .= "\n";
                        }
                    }
                }
                
                jsonResponse(true, 'Database SQL generated successfully', [
                    'sql' => $sql,
                    'database_name' => $config['dbname'],
                    'has_create_db' => $includeCreateDB,
                    'has_data' => $includeData,
                    'total_tables' => count($tables),
                    'total_rows' => $totalRows,
                    'sql_length' => strlen($sql)
                ]);
                break;
                
            // ===== EXECUTE SQL =====
            case 'execute_sql':
                validateParams($requestData, ['sql_query']);
                $query = trim($requestData['sql_query']);
                
                $queryType = 'UNKNOWN';
                if (preg_match('/^\s*SELECT/i', $query)) $queryType = 'SELECT';
                elseif (preg_match('/^\s*INSERT/i', $query)) $queryType = 'INSERT';
                elseif (preg_match('/^\s*UPDATE/i', $query)) $queryType = 'UPDATE';
                elseif (preg_match('/^\s*DELETE/i', $query)) $queryType = 'DELETE';
                elseif (preg_match('/^\s*CREATE/i', $query)) $queryType = 'CREATE';
                elseif (preg_match('/^\s*DROP/i', $query)) $queryType = 'DROP';
                elseif (preg_match('/^\s*ALTER/i', $query)) $queryType = 'ALTER';
                elseif (preg_match('/^\s*TRUNCATE/i', $query)) $queryType = 'TRUNCATE';
                elseif (preg_match('/^\s*SHOW/i', $query)) $queryType = 'SHOW';
                elseif (preg_match('/^\s*DESCRIBE/i', $query)) $queryType = 'DESCRIBE';
                
                try {
                    $stmt = $pdo->query($query);
                    
                    if (in_array($queryType, ['SELECT', 'SHOW', 'DESCRIBE'])) {
                        $results = $stmt->fetchAll();
                        $columns = [];
                        if (count($results) > 0) {
                            $columns = array_keys($results[0]);
                        }
                        
                        jsonResponse(true, 'Query executed successfully', [
                            'query_type' => $queryType,
                            'results' => $results,
                            'columns' => $columns,
                            'row_count' => count($results)
                        ]);
                    }
                    elseif (in_array($queryType, ['INSERT', 'UPDATE', 'DELETE'])) {
                        jsonResponse(true, 'Query executed successfully', [
                            'query_type' => $queryType,
                            'affected_rows' => $stmt->rowCount(),
                            'insert_id' => $pdo->lastInsertId()
                        ]);
                    }
                    else {
                        jsonResponse(true, 'Query executed successfully', [
                            'query_type' => $queryType
                        ]);
                    }
                } catch (PDOException $e) {
                    jsonResponse(false, 'SQL Error: ' . $e->getMessage(), [
                        'query_type' => $queryType,
                        'executed_query' => $query
                    ], 400);
                }
                break;
                
            // ===== INSERT RECORD =====
            case 'insert_record':
                validateParams($requestData, ['table_name', 'record_data']);
                $tableName = sanitizeName($requestData['table_name']);
                $recordData = is_string($requestData['record_data']) 
                    ? json_decode($requestData['record_data'], true) 
                    : $requestData['record_data'];
                
                $result = insertRecord($pdo, $tableName, $recordData);
                jsonResponse(true, $result['message'], $result);
                break;
                
            // ===== UPDATE RECORD =====
            case 'update_record':
                validateParams($requestData, ['table_name', 'record_data', 'primary_key', 'primary_value']);
                $tableName = sanitizeName($requestData['table_name']);
                $recordData = is_string($requestData['record_data']) 
                    ? json_decode($requestData['record_data'], true) 
                    : $requestData['record_data'];
                $primaryKey = sanitizeName($requestData['primary_key']);
                $primaryValue = $requestData['primary_value'];
                
                $result = updateRecord($pdo, $tableName, $recordData, [$primaryKey => $primaryValue]);
                jsonResponse(true, $result['message'], $result);
                break;
                
            // ===== DELETE RECORD =====
            case 'delete_record':
                validateParams($requestData, ['table_name', 'primary_key', 'primary_value']);
                $tableName = sanitizeName($requestData['table_name']);
                $primaryKey = sanitizeName($requestData['primary_key']);
                $primaryValue = $requestData['primary_value'];
                
                $result = deleteRecord($pdo, $tableName, [$primaryKey => $primaryValue]);
                jsonResponse(true, $result['message'], $result);
                break;
                
            // ===== SEARCH RECORDS =====
            case 'search_records':
                validateParams($requestData, ['table_name']);
                $tableName = sanitizeName($requestData['table_name']);
                $searchTerm = $requestData['search_term'] ?? '';
                $page = isset($requestData['page']) ? (int)$requestData['page'] : 1;
                $limit = isset($requestData['limit']) ? (int)$requestData['limit'] : 20;
                $offset = ($page - 1) * $limit;
                
                $stmt = $pdo->query("DESCRIBE `$tableName`");
                $columns = $stmt->fetchAll();
                
                if (!empty($searchTerm)) {
                    $searchConditions = [];
                    foreach ($columns as $col) {
                        $searchConditions[] = "`{$col['Field']}` LIKE " . $pdo->quote("%$searchTerm%");
                    }
                    $whereClause = "WHERE " . implode(' OR ', $searchConditions);
                } else {
                    $whereClause = "";
                }
                
                $stmt = $pdo->query("SELECT COUNT(*) as total FROM `$tableName` $whereClause");
                $totalRows = $stmt->fetchColumn();
                
                $stmt = $pdo->query("SELECT * FROM `$tableName` $whereClause LIMIT $limit OFFSET $offset");
                $data = $stmt->fetchAll();
                
                jsonResponse(true, 'Search completed', [
                    'data' => $data,
                    'columns' => $columns,
                    'pagination' => [
                        'current_page' => $page,
                        'total_rows' => (int)$totalRows,
                        'total_pages' => ceil($totalRows / $limit),
                        'limit' => $limit,
                        'showing_from' => $offset + 1,
                        'showing_to' => min($offset + $limit, $totalRows)
                    ]
                ]);
                break;
                
            // ===== INSERT RANDOM DATA =====
            case 'insert_random_data':
                validateParams($requestData, ['table_name', 'records_data']);
                $tableName = sanitizeName($requestData['table_name']);
                $recordsData = is_string($requestData['records_data']) 
                    ? json_decode($requestData['records_data'], true) 
                    : $requestData['records_data'];
                
                $inserted = 0;
                foreach ($recordsData as $record) {
                    try {
                        insertRecord($pdo, $tableName, $record);
                        $inserted++;
                    } catch (Exception $e) {
                        // Continue with next record
                    }
                }
                
                jsonResponse(true, "$inserted random records inserted successfully");
                break;
                
            // ===== CRUD OPERATIONS (Alternative syntax) =====
            case 'insert':
            case 'create':
                validateParams($requestData, ['table', 'data']);
                $result = insertRecord($pdo, $requestData['table'], $requestData['data']);
                jsonResponse(true, $result['message'], $result);
                break;
                
            case 'select':
            case 'read':
                validateParams($requestData, ['table']);
                $records = selectRecords(
                    $pdo,
                    $requestData['table'],
                    $requestData['conditions'] ?? [],
                    $requestData['limit'] ?? null,
                    $requestData['offset'] ?? null,
                    $requestData['orderBy'] ?? null
                );
                jsonResponse(true, 'Records retrieved', ['records' => $records, 'count' => count($records)]);
                break;
                
            case 'update':
                validateParams($requestData, ['table', 'data', 'conditions']);
                $result = updateRecord($pdo, $requestData['table'], $requestData['data'], $requestData['conditions']);
                jsonResponse(true, $result['message'], $result);
                break;
                
            case 'delete':
                validateParams($requestData, ['table', 'conditions']);
                $result = deleteRecord($pdo, $requestData['table'], $requestData['conditions']);
                jsonResponse(true, $result['message'], $result);
                break;
                
            // ===== CUSTOM QUERY =====
            case 'custom_query':
                validateParams($requestData, ['query']);
                $result = executeCustomQuery($pdo, $requestData['query'], $requestData['params'] ?? []);
                jsonResponse(true, 'Query executed', ['result' => $result]);
                break;
                
            // ===== GET COMPREHENSIVE DATABASE INFO =====
            case 'get_database_info':
                try {
                    $info = getDatabaseInfo($pdo, $config['dbname']);
                    
                    $info['host'] = $config['host'];
                    $info['port'] = $config['port'];
                    $info['database'] = $config['dbname'];
                    $info['username'] = $config['username'];
                    $info['password'] = $config['password'];
                    $info['connection_status'] = 'Active ✅';
                    $info['php_version'] = phpversion();
                    $info['server_info'] = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
                    $info['charset'] = $config['charset'];
                    
                    try {
                        $stmt = $pdo->query("SHOW VARIABLES LIKE 'version_comment'");
                        $row = $stmt->fetch();
                        if ($row) {
                            $info['server_type'] = $row['Value'];
                        }
                        
                        $stmt = $pdo->query("SHOW VARIABLES LIKE 'collation_database'");
                        $row = $stmt->fetch();
                        if ($row) {
                            $info['collation'] = $row['Value'];
                        }
                        
                        $stmt = $pdo->query("SHOW STATUS LIKE 'Uptime'");
                        $row = $stmt->fetch();
                        if ($row) {
                            $uptimeSeconds = (int)$row['Value'];
                            $days = floor($uptimeSeconds / 86400);
                            $hours = floor(($uptimeSeconds % 86400) / 3600);
                            $minutes = floor(($uptimeSeconds % 3600) / 60);
                            $info['server_uptime'] = "{$days}d {$hours}h {$minutes}m";
                        }
                        
                        $stmt = $pdo->query("SELECT CONNECTION_ID()");
                        $info['connection_id'] = $stmt->fetchColumn();
                        
                    } catch (Exception $e) {
                        // Ignore if can't get additional info
                    }
                    
                    try {
                        $info['tables'] = listTables($pdo);
                        $info['table_count'] = count($info['tables']);
                    } catch (Exception $e) {
                        $info['tables'] = [];
                        $info['table_count'] = 0;
                    }
                    
                    $info['connection_timestamp'] = date('Y-m-d H:i:s');
                    
                    jsonResponse(true, '✅ Database connection successful!', $info);
                } catch (Exception $e) {
                    jsonResponse(false, '❌ Error getting database info: ' . $e->getMessage(), null, 500);
                }
                break;
            
            // ===== TEST CONNECTION (Default) =====
            case 'test_connection':
            default:
                $info = getDatabaseInfo($pdo, $config['dbname']);
                jsonResponse(true, '✅ Database connection successful!', $info);
                break;
        }
        
    } catch (PDOException $e) {
        $errorData = [
            'error_code' => $e->getCode(),
            'error_message' => $e->getMessage()
        ];
        jsonResponse(false, 'Database error: ' . $e->getMessage(), $errorData, 500);
    } catch (Exception $e) {
        jsonResponse(false, 'Error: ' . $e->getMessage(), ['error_type' => get_class($e)], 400);
    }
    
    exit(); // End API processing
}

// ============================================================================
// [SECTION 2] HTML FRONTEND - USER INTERFACE
// ============================================================================
// If we reach here, it's not an API request - show the HTML dashboard
// ============================================================================
?>
<!--
╔══════════════════════════════════════════════════════════════════════════════╗
║                         DATABASE CONTROL PANEL                                ║
║  LAYOUT: sidebar                                                              ║
║  THEME: gradient-sunset                                                       ║
║  CSS_FRAMEWORK: tailwind                                                      ║
║  LANGUAGE: english                                                            ║
║  SERVER: Localhost (Laragon) + Remote (Hostinger)                             ║
║  DATABASE_OPERATIONS: Manage database connections                             ║
║  TABLE_OPERATIONS: list, create, edit, delete, rename tables                  ║
║  PHASE: All-In-One PHP Edition                                                ║
╚══════════════════════════════════════════════════════════════════════════════╝
-->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>App-AI</title>
    <link rel="icon" type="image/png" href="FuturisticLogo.png">
    <link rel="shortcut icon" type="image/png" href="FuturisticLogo.png">
    <link rel="apple-touch-icon" href="FuturisticLogo.png">
    
    <!-- ============================================================================
         [SECTION 3] CSS STYLING - VISUAL DESIGN
         ============================================================================
         This section contains all CSS styles for the dashboard
         Theme: gradient-sunset (Blue to Red gradient)
         Layout: Sidebar navigation with main content area
         ============================================================================ -->
    <style>
        /* ========================================
           [3.0] RESET & BASE STYLES
           ======================================== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1e3a8a 0%, #991b1b 100%);
            min-height: 100vh;
            color: #fef3c7;
        }

        /* Sidebar Navigation */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 260px;
            height: 100vh;
            background: rgba(0, 0, 0, 0.3);
            -webkit-backdrop-filter: blur(10px);
            backdrop-filter: blur(10px);
            padding: 20px;
            transition: transform 0.3s ease;
            z-index: 1000;
            border-right: 1px solid rgba(251, 191, 36, 0.2);
        }

        .sidebar.collapsed {
            transform: translateX(-260px);
        }

        .sidebar-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(251, 191, 36, 0.3);
        }

        .sidebar-title {
            font-size: 20px;
            font-weight: bold;
            color: #fbbf24;
        }

        .sidebar-toggle {
            background: none;
            border: none;
            color: #fbbf24;
            cursor: pointer;
            font-size: 20px;
            padding: 5px;
        }

        .nav-menu {
            list-style: none;
        }

        .nav-item {
            margin-bottom: 10px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: #fef3c7;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .nav-link:hover {
            background: rgba(251, 191, 36, 0.2);
            color: #fbbf24;
            transform: translateX(5px);
        }

        .nav-link.active {
            background: rgba(251, 191, 36, 0.3);
            color: #fbbf24;
            border-left: 3px solid #f59e0b;
        }

        .nav-icon {
            margin-right: 10px;
            font-size: 18px;
        }
        
        .back-to-index {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 14px;
            margin-top: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .back-to-index:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5);
            border-color: rgba(255, 255, 255, 0.6);
        }
        
        .back-to-index i {
            font-size: 16px;
        }

        /* Main Content Area */
        .main-content {
            margin-left: 260px;
            padding: 30px;
            transition: margin-left 0.3s ease;
            min-height: 100vh;
        }

        .main-content.expanded {
            margin-left: 0;
        }

        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(251, 191, 36, 0.2);
            flex-wrap: wrap;
            gap: 15px;
        }

        .page-title {
            font-size: 32px;
            font-weight: bold;
            color: #fbbf24;
            flex: 0 0 auto;
        }

        .toggle-sidebar-btn {
            display: none;
            background: rgba(251, 191, 36, 0.2);
            border: 1px solid #fbbf24;
            color: #fbbf24;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .toggle-sidebar-btn:hover {
            background: rgba(251, 191, 36, 0.3);
        }

        /* Cards */
        .card {
            background: rgba(255, 255, 255, 0.1);
            -webkit-backdrop-filter: blur(10px);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            border: 1px solid rgba(251, 191, 36, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        }

        .card-title {
            font-size: 20px;
            font-weight: bold;
            color: #fbbf24;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }

        .card-title-icon {
            margin-right: 10px;
        }

        /* Forms */
        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            color: #fef3c7;
            font-weight: 500;
        }

        .form-input, .form-select {
            width: 100%;
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(251, 191, 36, 0.3);
            border-radius: 8px;
            color: #fef3c7;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: #fbbf24;
            background: rgba(255, 255, 255, 0.15);
        }

        .form-input::placeholder {
            color: rgba(254, 243, 199, 0.5);
        }

        /* Buttons */
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: #fbbf24;
            color: #1e3a8a;
        }

        .btn-primary:hover {
            background: #f59e0b;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(251, 191, 36, 0.4);
        }

        .btn-danger {
            background: #dc2626;
            color: white;
        }

        .btn-danger:hover {
            background: #b91c1c;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 38, 38, 0.4);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: #fef3c7;
            border: 1px solid rgba(251, 191, 36, 0.3);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        /* Database List */
        .database-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        /* Database Card - Vertical Flex (3 Rows) */
        .database-item {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(251, 191, 36, 0.2);
            border-radius: 10px;
            padding: 20px;
            transition: all 0.3s ease;
            position: relative;
            display: flex;
            flex-direction: column;
            gap: 15px;
            min-height: 280px;
            cursor: pointer;
        }

        .database-item:hover {
            background: rgba(251, 191, 36, 0.15);
            border-color: #fbbf24;
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(251, 191, 36, 0.3);
        }

        /* Localhost database card hover - Blue theme */
        .database-item[id^="localhost_"]:hover {
            background: rgba(59, 130, 246, 0.2) !important;
            border-color: #3b82f6 !important;
            box-shadow: 0 5px 20px rgba(59, 130, 246, 0.4) !important;
        }

        /* Localhost database card selected - Blue glow */
        .database-item[id^="localhost_"].selected {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.25) 0%, rgba(30, 64, 175, 0.25) 100%) !important;
            border: 2px solid #3b82f6 !important;
            box-shadow: 0 0 30px rgba(59, 130, 246, 0.6), 0 8px 40px rgba(59, 130, 246, 0.4) !important;
        }

        .database-item[id^="localhost_"].selected .database-icon {
            filter: drop-shadow(0 0 15px rgba(59, 130, 246, 1)) !important;
        }

        /* Row 1: Logo/Icon (Centered) */
        .database-icon-row {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid rgba(251, 191, 36, 0.15);
        }

        .database-icon {
            font-size: 48px;
        }

        /* Row 2: Text Content (Connection Name, Database, Host) */
        .database-text-row {
            display: flex;
            flex-direction: column;
            gap: 10px;
            flex: 1;
            padding: 10px 0;
        }

        .database-name {
            font-size: 18px;
            font-weight: bold;
            color: #fbbf24;
            text-align: center;
            word-wrap: break-word;
            overflow-wrap: break-word;
            margin-bottom: 8px;
        }

        .database-info-item {
            font-size: 13px;
            color: rgba(254, 243, 199, 0.8);
            text-align: center;
            word-wrap: break-word;
            overflow-wrap: break-word;
            font-family: monospace;
        }

        /* Row 3: Status + Button */
        .database-actions-row {
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding-top: 10px;
            border-top: 1px solid rgba(251, 191, 36, 0.15);
        }

        .database-status {
            padding: 10px;
            border-radius: 6px;
            font-size: 12px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 40px;
        }

        .database-status.testing {
            background: rgba(59, 130, 246, 0.2);
            border: 1px solid #3b82f6;
            color: #93c5fd;
        }

        .database-status.success {
            background: rgba(34, 197, 94, 0.2);
            border: 1px solid #22c55e;
            color: #86efac;
        }

        .database-status.error {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid #ef4444;
            color: #fca5a5;
        }

        .database-button {
            width: 100%;
            padding: 10px;
            font-size: 13px;
        }

        .database-buttons-row {
            display: flex;
            gap: 8px;
            width: 100%;
        }

        .database-buttons-row .btn {
            flex: 1;
            padding: 8px 6px;
            font-size: 11px;
            white-space: nowrap;
        }

        .btn-credentials {
            background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%) !important;
            border: 1px solid #a78bfa !important;
            color: white !important;
        }

        .btn-credentials:hover {
            background: linear-gradient(135deg, #a78bfa 0%, #7c3aed 100%) !important;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(139, 92, 246, 0.4);
        }

        .lock-icon {
            position: absolute;
            top: 10px;
            right: 10px;
            color: #fbbf24;
            font-size: 16px;
        }

        /* Messages */
        .message {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes logoFloat {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            25% { transform: translateY(-6px) rotate(-1.5deg); }
            50% { transform: translateY(-10px) rotate(0deg); }
            75% { transform: translateY(-6px) rotate(1.5deg); }
        }

        .message.success {
            background: rgba(34, 197, 94, 0.2);
            border: 1px solid #22c55e;
            color: #86efac;
        }

        .message.error {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid #ef4444;
            color: #fca5a5;
        }

        .message.info {
            background: rgba(59, 130, 246, 0.2);
            border: 1px solid #3b82f6;
            color: #93c5fd;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            -webkit-backdrop-filter: blur(5px);
            backdrop-filter: blur(5px);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: linear-gradient(135deg, rgba(30, 58, 138, 0.95) 0%, rgba(153, 27, 27, 0.95) 100%);
            border: 1px solid rgba(251, 191, 36, 0.3);
            border-radius: 12px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-title {
            font-size: 22px;
            font-weight: bold;
            color: #fbbf24;
        }

        .modal-close {
            background: none;
            border: none;
            color: #fef3c7;
            font-size: 24px;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
        }

        .modal-close:hover {
            color: #fbbf24;
        }

        .modal-body {
            margin-bottom: 20px;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        /* Loading Spinner */
        .spinner {
            border: 3px solid rgba(251, 191, 36, 0.3);
            border-top: 3px solid #fbbf24;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
            display: inline-block;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Expandable Section */
        .expandable-section {
            margin-top: 15px;
        }

        .expandable-header {
            cursor: pointer;
            padding: 10px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.3s ease;
        }

        .expandable-header:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .expandable-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }

        .expandable-content.expanded {
            max-height: 500px;
            margin-top: 15px;
        }

        .helper-text {
            font-size: 12px;
            color: rgba(254, 243, 199, 0.7);
            margin-top: 5px;
        }

        .security-notice {
            background: rgba(245, 158, 11, 0.2);
            border: 1px solid #f59e0b;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .section {
            display: none;
        }

        .section.active {
            display: block;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-260px);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .toggle-sidebar-btn {
                display: block;
            }

            .database-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Datalist styling hint */
        .datalist-wrapper {
            position: relative;
        }

        .saved-credentials-badge {
            display: inline-block;
            background: rgba(34, 197, 94, 0.2);
            color: #86efac;
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 4px;
            margin-left: 10px;
        }

        /* Table Operations Styles */
        .connection-status {
            background: rgba(34, 197, 94, 0.2);
            border: 1px solid #22c55e;
            color: #86efac;
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
            font-weight: 500;
        }

        .connection-status.active {
            display: block;
        }

        .column-builder {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(251, 191, 36, 0.2);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }

        .column-row {
            display: grid;
            grid-template-columns: 2fr 2fr 1fr 1fr 1fr auto;
            gap: 10px;
            margin-bottom: 10px;
            align-items: end;
        }

        .column-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 10px;
            padding: 10px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 6px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .checkbox-group label {
            margin: 0;
            cursor: pointer;
            color: #fef3c7;
            font-size: 13px;
        }

        .btn-icon {
            width: 40px;
            height: 40px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .sql-preview {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(251, 191, 36, 0.2);
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            color: #86efac;
            white-space: pre-wrap;
            word-break: break-all;
            max-height: 300px;
            overflow-y: auto;
        }

        .template-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }

        .template-card {
            background: rgba(255, 255, 255, 0.08);
            border: 2px solid rgba(251, 191, 36, 0.2);
            border-radius: 8px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }

        .template-card:hover {
            border-color: #fbbf24;
            background: rgba(251, 191, 36, 0.15);
            transform: translateY(-3px);
        }

        .template-card.selected {
            border-color: #fbbf24;
            background: rgba(251, 191, 36, 0.2);
        }

        .template-icon {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .template-name {
            font-weight: bold;
            color: #fef3c7;
            margin-bottom: 5px;
        }

        .template-desc {
            font-size: 11px;
            color: rgba(254, 243, 199, 0.6);
        }

        .table-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .table-item {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(251, 191, 36, 0.2);
            border-radius: 10px;
            padding: 20px;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            gap: 15px;
            overflow: hidden;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .table-item:hover {
            background: rgba(251, 191, 36, 0.15);
            border-color: #fbbf24;
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(251, 191, 36, 0.3);
        }

        .table-actions {
            display: flex;
            gap: 8px;
            padding-top: 10px;
            border-top: 1px solid rgba(251, 191, 36, 0.15);
        }

        .table-actions .btn {
            flex: 1;
            padding: 8px 12px;
            font-size: 12px;
            white-space: nowrap;
        }

        .table-actions .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* CRUD Action buttons */
        .btn:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        .btn:disabled:hover {
            transform: none;
            box-shadow: none;
        }

        /* SQL Autocomplete - Ghost Text */
        .sql-ac-wrapper {
            position: relative;
            display: inline-block;
            width: 100%;
        }

        .sql-ac-ghost {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            pointer-events: none;
            white-space: pre-wrap;
            word-wrap: break-word;
            overflow: hidden;
            padding: 12px 15px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            line-height: 1.5;
            color: transparent;
            z-index: 1;
        }

        .sql-ac-ghost-text {
            color: rgba(147, 197, 253, 0.4);
            background: rgba(59, 130, 246, 0.1);
        }

        .sql-ac-textarea {
            position: relative;
            background: transparent;
            z-index: 2;
        }

        #sqlSuggestions {
            scrollbar-width: thin;
            scrollbar-color: rgba(251, 191, 36, 0.5) rgba(0, 0, 0, 0.3);
        }

        #sqlSuggestions::-webkit-scrollbar {
            width: 8px;
        }

        #sqlSuggestions::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.3);
        }

        #sqlSuggestions::-webkit-scrollbar-thumb {
            background: rgba(251, 191, 36, 0.5);
            border-radius: 4px;
        }

        .sql-suggestion-item:last-child {
            border-bottom: none !important;
        }

        .table-name {
            font-size: 16px;
            font-weight: bold;
            color: #fef3c7;
            display: flex;
            align-items: center;
            gap: 10px;
            word-wrap: break-word;
            overflow-wrap: break-word;
            word-break: break-word;
            max-width: 100%;
            flex-wrap: wrap;
        }

        .table-name span {
            word-wrap: break-word;
            overflow-wrap: break-word;
            word-break: break-word;
            max-width: calc(100% - 40px);
        }

        .table-icon {
            font-size: 24px;
        }

        /* Tab styles for create table */
        .tab-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid rgba(251, 191, 36, 0.2);
        }

        .tab-btn {
            background: none;
            border: none;
            color: #fef3c7;
            padding: 12px 24px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
            font-size: 14px;
            font-weight: 500;
        }

        .tab-btn:hover {
            color: #fbbf24;
        }

        .tab-btn.active {
            color: #fbbf24;
            border-bottom-color: #fbbf24;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Migration Table Box Styles */
        .migration-table-box {
            flex: 0 0 calc(33.333% - 7px);
            min-width: 120px;
            background: linear-gradient(135deg, rgba(251, 191, 36, 0.1) 0%, rgba(251, 191, 36, 0.05) 100%);
            border: 2px solid rgba(251, 191, 36, 0.3);
            border-radius: 8px;
            padding: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            position: relative;
            user-select: none;
        }

        .migration-table-box:hover {
            transform: translateY(-3px);
            border-color: #fbbf24;
            box-shadow: 0 5px 15px rgba(251, 191, 36, 0.3);
            background: linear-gradient(135deg, rgba(251, 191, 36, 0.2) 0%, rgba(251, 191, 36, 0.1) 100%);
        }

        .migration-table-box.selected {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.3) 0%, rgba(34, 197, 94, 0.15) 100%);
            border-color: #22c55e;
            box-shadow: 0 0 20px rgba(34, 197, 94, 0.4);
        }

        .migration-table-box.selected:hover {
            transform: translateY(-3px);
            border-color: #86efac;
        }

        .migration-table-emoji {
            font-size: 32px;
            margin-bottom: 8px;
            display: block;
        }

        .migration-table-name {
            color: #fef3c7;
            font-size: 13px;
            font-weight: 500;
            word-wrap: break-word;
            line-height: 1.3;
        }

        .migration-table-box.selected .migration-table-name {
            color: #86efac;
        }

        .migration-check-icon {
            position: absolute;
            top: 5px;
            right: 5px;
            background: #22c55e;
            color: white;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .migration-table-box.selected .migration-check-icon {
            opacity: 1;
        }

        /* Prompt Generator selected state - Purple theme */
        .migration-table-box.selected[data-table] {
            box-shadow: 0 0 20px rgba(139, 92, 246, 0.4);
        }

        /* Drag and Drop styles */
        .migration-table-box.dragging {
            opacity: 0.5;
            cursor: grabbing !important;
            transform: scale(1.05) rotate(3deg);
            transition: transform 0.2s;
            box-shadow: 0 10px 30px rgba(251, 191, 36, 0.5);
            border-color: #fbbf24 !important;
            z-index: 1000;
            animation: wiggle 0.3s ease-in-out infinite;
        }

        @keyframes wiggle {
            0%, 100% { transform: scale(1.05) rotate(3deg); }
            50% { transform: scale(1.05) rotate(-3deg); }
        }

        .migration-drop-zone {
            position: relative;
            transition: all 0.3s ease;
        }

        .migration-drop-zone.drag-over {
            border-color: #22c55e !important;
            border-width: 3px !important;
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.2) 0%, rgba(251, 191, 36, 0.2) 100%) !important;
            box-shadow: inset 0 0 40px rgba(34, 197, 94, 0.4), 0 0 20px rgba(251, 191, 36, 0.3);
            animation: dropZonePulse 1s ease-in-out infinite;
        }

        @keyframes dropZonePulse {
            0%, 100% { box-shadow: inset 0 0 40px rgba(34, 197, 94, 0.4), 0 0 20px rgba(251, 191, 36, 0.3); }
            50% { box-shadow: inset 0 0 50px rgba(34, 197, 94, 0.6), 0 0 30px rgba(251, 191, 36, 0.5); }
        }

        .migration-drop-zone.drag-over::before {
            content: '📥 Drop Here to Move Table';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #22c55e;
            font-size: 24px;
            font-weight: bold;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.9);
            pointer-events: none;
            z-index: 1000;
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.95) 0%, rgba(34, 197, 94, 0.2) 100%);
            padding: 25px 40px;
            border-radius: 15px;
            border: 3px solid #22c55e;
            animation: fadeInScale 0.3s ease-out;
        }

        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: translate(-50%, -50%) scale(0.8);
            }
            to {
                opacity: 1;
                transform: translate(-50%, -50%) scale(1);
            }
        }

        /* Migration tables container scrollbar */
        #migrationTablesContainer::-webkit-scrollbar {
            width: 8px;
        }

        #migrationTablesContainer::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 4px;
        }

        #migrationTablesContainer::-webkit-scrollbar-thumb {
            background: rgba(251, 191, 36, 0.5);
            border-radius: 4px;
        }

        #migrationTablesContainer::-webkit-scrollbar-thumb:hover {
            background: rgba(251, 191, 36, 0.7);
        }

        /* Destination tables container scrollbar */
        #migrationDestinationTablesContainer::-webkit-scrollbar {
            width: 8px;
        }

        #migrationDestinationTablesContainer::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 4px;
        }

        #migrationDestinationTablesContainer::-webkit-scrollbar-thumb {
            background: rgba(34, 197, 94, 0.5);
            border-radius: 4px;
        }

        #migrationDestinationTablesContainer::-webkit-scrollbar-thumb:hover {
            background: rgba(34, 197, 94, 0.7);
        }

        /* Prompt Generator tables container scrollbar */
        #promptGenTablesContainer::-webkit-scrollbar {
            width: 8px;
        }

        #promptGenTablesContainer::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 4px;
        }

        #promptGenTablesContainer::-webkit-scrollbar-thumb {
            background: rgba(139, 92, 246, 0.5);
            border-radius: 4px;
        }

        #promptGenTablesContainer::-webkit-scrollbar-thumb:hover {
            background: rgba(139, 92, 246, 0.7);
        }

        /* Destination dropdown hover effect */
        #migrationDestinationSelect:hover {
            border-color: #86efac;
            box-shadow: 0 0 15px rgba(34, 197, 94, 0.3);
        }

        #migrationDestinationSelect:focus {
            border-color: #22c55e;
            box-shadow: 0 0 20px rgba(34, 197, 94, 0.5);
        }

        /* Prompt Generator dropdown hover effect */
        #promptGenDatabaseSelect:hover {
            border-color: #a78bfa;
            box-shadow: 0 0 15px rgba(139, 92, 246, 0.3);
        }

        #promptGenDatabaseSelect:focus {
            border-color: #8b5cf6;
            box-shadow: 0 0 20px rgba(139, 92, 246, 0.5);
        }

        /* Arrow pulse animation */
        @keyframes pulse {
            0%, 100% {
                opacity: 0.5;
                transform: scale(1);
            }
            50% {
                opacity: 1;
                transform: scale(1.2);
            }
        }

        @media (max-width: 768px) {
            .column-row {
                grid-template-columns: 1fr;
            }

            .template-grid {
                grid-template-columns: 1fr;
            }

            .migration-table-box {
                flex: 0 0 calc(50% - 5px);
            }
        }

        /* Hidden sections - for temporary hiding */
        .hidden-section {
            display: none !important;
        }

        /* Table Data Viewer Styles */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
            font-size: 13px;
        }

        .data-table thead {
            background: rgba(251, 191, 36, 0.2);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .data-table th {
            padding: 12px 15px;
            text-align: left;
            color: #fbbf24;
            font-weight: bold;
            border-bottom: 2px solid rgba(251, 191, 36, 0.3);
            white-space: nowrap;
        }

        .data-table td {
            padding: 10px 15px;
            color: #fef3c7;
            border-bottom: 1px solid rgba(251, 191, 36, 0.1);
            word-wrap: break-word;
            max-width: 300px;
        }

        .data-table tbody tr {
            transition: all 0.2s ease;
        }

        .data-table tbody tr:hover {
            background: rgba(251, 191, 36, 0.1);
        }

        .data-table tbody tr:nth-child(even) {
            background: rgba(255, 255, 255, 0.02);
        }

        .data-table tbody tr:nth-child(even):hover {
            background: rgba(251, 191, 36, 0.1);
        }

        /* Actions column hover effect */
        .data-table tbody tr:hover td:first-child {
            background: rgba(251, 191, 36, 0.15) !important;
        }

        .stat-box {
            background: rgba(255, 255, 255, 0.05);
            padding: 15px;
            border-radius: 8px;
            border: 1px solid rgba(251, 191, 36, 0.2);
            transition: all 0.3s ease;
        }

        .stat-box:hover {
            background: rgba(251, 191, 36, 0.1);
            border-color: #fbbf24;
        }

        .stat-label {
            font-size: 12px;
            color: rgba(254, 243, 199, 0.7);
            margin-bottom: 5px;
        }

        .stat-value {
            font-size: 18px;
            color: #fbbf24;
            font-weight: bold;
        }

        .pagination-info {
            color: rgba(254, 243, 199, 0.8);
            font-size: 14px;
        }

        .pagination-buttons {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .page-input {
            width: 60px;
            padding: 8px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(251, 191, 36, 0.3);
            border-radius: 6px;
            color: #fef3c7;
            text-align: center;
        }

        /* SQL Modal specific styles */
        #sqlCode {
            scrollbar-width: thin;
            scrollbar-color: rgba(251, 191, 36, 0.5) rgba(0, 0, 0, 0.3);
        }

        #sqlCode::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }

        #sqlCode::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 5px;
        }

        #sqlCode::-webkit-scrollbar-thumb {
            background: rgba(251, 191, 36, 0.5);
            border-radius: 5px;
        }

        #sqlCode::-webkit-scrollbar-thumb:hover {
            background: rgba(251, 191, 36, 0.7);
        }

        /* Selected database card - Shiny/Glowing effect */
        .database-item.selected {
            background: linear-gradient(135deg, rgba(251, 191, 36, 0.25) 0%, rgba(245, 158, 11, 0.25) 100%);
            border: 2px solid #fbbf24;
            box-shadow: 0 0 30px rgba(251, 191, 36, 0.6), 0 8px 40px rgba(251, 191, 36, 0.4);
            transform: translateY(-5px) scale(1.02);
        }

        .database-item.selected .database-icon {
            animation: glow-pulse 2s ease-in-out infinite;
        }

        @keyframes glow-pulse {
            0%, 100% {
                filter: drop-shadow(0 0 8px rgba(251, 191, 36, 0.8));
            }
            50% {
                filter: drop-shadow(0 0 20px rgba(251, 191, 36, 1));
            }
        }

        /* Connection state indicator */
        .connection-state-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 12px;
            animation: fadeIn 0.3s ease-out;
        }

        .connection-state-badge.connected {
            background: linear-gradient(135deg, #22c55e 0%, #15803d 100%);
            color: white;
            box-shadow: 0 2px 10px rgba(34, 197, 94, 0.4);
        }

        .connection-state-badge.disconnected {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            box-shadow: 0 2px 10px rgba(239, 68, 68, 0.4);
        }

        /* Fade in animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.8);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        /* Database name suggestion buttons */
        .db-suggestion-btn:hover {
            background: rgba(34, 197, 94, 0.2) !important;
            border-color: #22c55e !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3) !important;
        }

        .db-suggestion-btn:active {
            transform: translateY(0) !important;
            box-shadow: 0 2px 6px rgba(34, 197, 94, 0.3) !important;
        }

        /* API URL Input Container */
        .api-url-container {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-left: 20px;
            padding: 8px 15px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 10px;
            border: 1px solid rgba(251, 191, 36, 0.3);
        }

        .api-url-label {
            font-size: 13px;
            font-weight: 600;
            color: #fbbf24;
            white-space: nowrap;
        }

        .api-url-input-wrapper {
            position: relative;
            min-width: 300px;
        }

        .api-url-input {
            width: 100%;
            padding: 8px 35px 8px 12px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(251, 191, 36, 0.3);
            border-radius: 8px;
            color: #fef3c7;
            font-size: 13px;
            font-family: monospace;
            transition: all 0.3s ease;
        }

        .api-url-input:focus {
            outline: none;
            border-color: #fbbf24;
            background: rgba(255, 255, 255, 0.1);
            box-shadow: 0 0 15px rgba(251, 191, 36, 0.3);
        }

        .api-url-dropdown-btn {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(251, 191, 36, 0.2);
            border: none;
            border-radius: 5px;
            padding: 5px 8px;
            cursor: pointer;
            color: #fbbf24;
            font-size: 12px;
            transition: all 0.3s ease;
        }

        .api-url-dropdown-btn:hover {
            background: rgba(251, 191, 36, 0.4);
        }

        .api-url-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            margin-top: 5px;
            background: rgba(0, 0, 0, 0.95);
            border: 1px solid rgba(251, 191, 36, 0.4);
            border-radius: 8px;
            max-height: 250px;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            display: none;
        }

        .api-url-dropdown.show {
            display: block;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .api-url-dropdown-item {
            padding: 10px 15px;
            cursor: pointer;
            color: #fef3c7;
            font-family: monospace;
            font-size: 12px;
            border-bottom: 1px solid rgba(251, 191, 36, 0.1);
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .api-url-dropdown-item:hover {
            background: rgba(251, 191, 36, 0.2);
        }

        .api-url-dropdown-item.current {
            background: rgba(34, 197, 94, 0.2);
            border-left: 3px solid #22c55e;
        }

        .api-url-delete-btn {
            background: rgba(239, 68, 68, 0.3);
            border: none;
            border-radius: 5px;
            padding: 3px 8px;
            cursor: pointer;
            color: #fef3c7;
            font-size: 11px;
            transition: all 0.2s ease;
        }

        .api-url-delete-btn:hover {
            background: rgba(239, 68, 68, 0.6);
        }

        .api-url-dropdown::-webkit-scrollbar {
            width: 8px;
        }

        .api-url-dropdown::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 4px;
        }

        .api-url-dropdown::-webkit-scrollbar-thumb {
            background: rgba(251, 191, 36, 0.5);
            border-radius: 4px;
        }

        .api-url-dropdown::-webkit-scrollbar-thumb:hover {
            background: rgba(251, 191, 36, 0.7);
        }

        /* Connect Toggle Button */
        .connect-toggle-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 28px;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            border: 2px solid;
            position: relative;
            overflow: hidden;
        }

        .connect-toggle-btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .connect-toggle-btn:hover::before {
            width: 300px;
            height: 300px;
        }

        /* Disconnected State (Red) */
        .connect-toggle-btn.disconnected {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            color: white;
            border-color: #ef4444;
            box-shadow: 0 5px 20px rgba(220, 38, 38, 0.4);
        }

        .connect-toggle-btn.disconnected:hover {
            background: linear-gradient(135deg, #b91c1c 0%, #7f1d1d 100%);
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 8px 25px rgba(220, 38, 38, 0.6);
        }

        .connect-toggle-btn.disconnected .toggle-icon {
            animation: disconnectPulse 2s ease-in-out infinite;
        }

        /* Connected State (Green) */
        .connect-toggle-btn.connected {
            background: linear-gradient(135deg, #22c55e 0%, #15803d 100%);
            color: white;
            border-color: #86efac;
            box-shadow: 0 5px 20px rgba(34, 197, 94, 0.4), 0 0 30px rgba(34, 197, 94, 0.3);
            animation: connectedGlow 3s ease-in-out infinite;
        }

        .connect-toggle-btn.connected:hover {
            background: linear-gradient(135deg, #16a34a 0%, #14532d 100%);
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 8px 25px rgba(34, 197, 94, 0.6), 0 0 40px rgba(34, 197, 94, 0.5);
        }

        .connect-toggle-btn.connected .toggle-icon {
            animation: connectedSpin 3s linear infinite;
        }

        .toggle-icon {
            font-size: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1;
        }

        .toggle-text {
            z-index: 1;
            letter-spacing: 0.5px;
        }

        @keyframes disconnectPulse {
            0%, 100% {
                transform: scale(1);
                opacity: 1;
            }
            50% {
                transform: scale(1.2);
                opacity: 0.7;
            }
        }

        @keyframes connectedSpin {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }

        @keyframes connectedGlow {
            0%, 100% {
                box-shadow: 0 5px 20px rgba(34, 197, 94, 0.4), 0 0 30px rgba(34, 197, 94, 0.3);
            }
            50% {
                box-shadow: 0 5px 25px rgba(34, 197, 94, 0.6), 0 0 45px rgba(34, 197, 94, 0.5);
            }
        }

        /* Card header with button */
        .card-header-with-action {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        /* Selected Database Badge in Sidebar */
        .selected-db-badge {
            background: linear-gradient(135deg, rgba(251, 191, 36, 0.25) 0%, rgba(245, 158, 11, 0.3) 100%);
            border: 2px solid #fbbf24;
            border-radius: 10px;
            padding: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 0 20px rgba(251, 191, 36, 0.5), 0 4px 15px rgba(251, 191, 36, 0.3);
            animation: pulse-glow 2s ease-in-out infinite;
            position: relative;
            overflow: hidden;
        }

        .selected-db-badge::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            animation: shine 3s linear infinite;
        }

        @keyframes pulse-glow {
            0%, 100% {
                box-shadow: 0 0 20px rgba(251, 191, 36, 0.5), 0 4px 15px rgba(251, 191, 36, 0.3);
            }
            50% {
                box-shadow: 0 0 30px rgba(251, 191, 36, 0.8), 0 6px 25px rgba(251, 191, 36, 0.5);
            }
        }

        @keyframes shine {
            0% {
                transform: translateX(-100%) translateY(-100%) rotate(45deg);
            }
            100% {
                transform: translateX(100%) translateY(100%) rotate(45deg);
            }
        }

        .selected-db-icon {
            font-size: 28px;
            animation: rotate-sparkle 4s linear infinite;
            filter: drop-shadow(0 0 8px rgba(251, 191, 36, 0.8));
            flex-shrink: 0;
        }

        @keyframes rotate-sparkle {
            0%, 100% {
                transform: rotate(0deg) scale(1);
            }
            25% {
                transform: rotate(10deg) scale(1.1);
            }
            50% {
                transform: rotate(0deg) scale(1);
            }
            75% {
                transform: rotate(-10deg) scale(1.1);
            }
        }

        .selected-db-content {
            flex: 1;
            position: relative;
            z-index: 1;
        }

        .selected-db-label {
            font-size: 11px;
            color: rgba(254, 243, 199, 0.8);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .selected-db-name {
            font-size: 14px;
            color: #fbbf24;
            font-weight: bold;
            word-wrap: break-word;
            overflow-wrap: break-word;
            line-height: 1.3;
            text-shadow: 0 0 10px rgba(251, 191, 36, 0.5);
        }
    </style>
</head>
<body>
    <!-- Sidebar Navigation -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div style="display: flex; align-items: center; gap: 12px;">
                <img src="FuturisticLogo.png" alt="App-AI Logo" style="width: 45px; height: 45px; filter: drop-shadow(0 4px 15px rgba(0,0,0,0.5)); animation: logoFloat 3s ease-in-out infinite;">
                <div>
                    <div style="font-size: 16px; font-weight: bold; background: linear-gradient(135deg, #22c55e 0%, #fbbf24 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">App-AI</div>
                    <div class="sidebar-title" style="font-size: 14px; margin-top: 2px;">🌐 Hostinger DB</div>
                </div>
            </div>
            <button class="sidebar-toggle" onclick="toggleSidebar()">☰</button>
        </div>
        <ul class="nav-menu">
            <li class="nav-item">
                <a class="nav-link active" onclick="showSection('dashboard')">
                    <span class="nav-icon">📊</span>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" onclick="showSection('create')">
                    <span class="nav-icon">➕</span>
                    <span>Create Database (Localhost)</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" onclick="showSection('delete')">
                    <span class="nav-icon">🗑️</span>
                    <span>Delete Database (Localhost)</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" onclick="showSection('rename')">
                    <span class="nav-icon">✏️</span>
                    <span>Rename Database (Localhost)</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" onclick="showSection('credentials')">
                    <span class="nav-icon">🔐</span>
                    <span>Set Credentials</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" onclick="showSection('settings')">
                    <span class="nav-icon">⚙️</span>
                    <span>Hostinger Connections</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" onclick="showSection('generateDatabase')">
                    <span class="nav-icon">🗄️</span>
                    <span>Generate Database SQL</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" onclick="showSection('aiPrompt')">
                    <span class="nav-icon">🤖</span>
                    <span>AI Prompter</span>
                </a>
            </li>
            <!-- TABLE OPERATIONS - HIDDEN -->
            <li class="nav-item hidden-section" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid rgba(251, 191, 36, 0.3);">
                <div style="color: rgba(251, 191, 36, 0.7); font-size: 12px; padding: 0 15px; margin-bottom: 10px;">TABLE OPERATIONS</div>
            </li>
            <!-- Selected Database Alert -->
            <li id="selectedDatabaseAlert" class="nav-item hidden-section" style="padding: 0 10px; margin-bottom: 15px;">
                <div class="selected-db-badge">
                    <div class="selected-db-icon">✨</div>
                    <div class="selected-db-content">
                        <div class="selected-db-label">Database Selected</div>
                        <div id="selectedDatabaseName" class="selected-db-name">Database Name</div>
                    </div>
                </div>
            </li>
            <li class="nav-item hidden-section">
                <a class="nav-link" onclick="showSection('listTables')">
                    <span class="nav-icon">📄</span>
                    <span>List Tables</span>
                </a>
            </li>
            <li class="nav-item hidden-section">
                <a class="nav-link" onclick="showSection('createTable')">
                    <span class="nav-icon">➕</span>
                    <span>Create Table</span>
                </a>
            </li>
            <li class="nav-item hidden-section">
                <a class="nav-link" onclick="showSection('editTable')">
                    <span class="nav-icon">✏️</span>
                    <span>Edit Table</span>
                </a>
            </li>
            <li class="nav-item hidden-section">
                <a class="nav-link" onclick="showSection('deleteTable')">
                    <span class="nav-icon">🗑️</span>
                    <span>Delete Table</span>
                </a>
            </li>
            <li class="nav-item hidden-section">
                <a class="nav-link" onclick="showSection('renameTable')">
                    <span class="nav-icon">✏️</span>
                    <span>Rename Table</span>
                </a>
            </li>
        </ul>
        
        <!-- Back to Prompter Button -->
        <a href="prompter.php" class="back-to-index">
            <span>⬅️</span>
            <span>Back to Prompter</span>
        </a>
    </aside>

    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <div class="content-header">
            <h1 class="page-title">
                Database Control Panel
                <span id="connectionStateBadge" class="connection-state-badge disconnected">🔴 Disconnected</span>
            </h1>
            
            <!-- API URL Configuration (AUTO-DETECTED) -->
            <div class="api-url-container">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span class="api-url-label">
                        <span style="font-size: 16px;">🎯</span> 
                        <span>API Backend:</span>
                    </span>
                    <span id="autoDetectBadge" style="background: linear-gradient(135deg, #22c55e 0%, #15803d 100%); color: white; padding: 3px 8px; border-radius: 12px; font-size: 10px; font-weight: bold; letter-spacing: 0.5px; box-shadow: 0 2px 6px rgba(34, 197, 94, 0.3); display: none;">
                        AUTO
                    </span>
                </div>
                <div class="api-url-input-wrapper">
                    <input 
                        type="text" 
                        id="hostingerApiUrl" 
                        class="api-url-input" 
                        placeholder="Auto-detected based on your domain"
                        title="Auto-detected API URL - You can override this if needed"
                    />
                    <button class="api-url-dropdown-btn" onclick="toggleApiUrlDropdown()">▼</button>
                    <div id="apiUrlDropdown" class="api-url-dropdown"></div>
                </div>
                <button onclick="resetToAutoDetected()" class="btn btn-secondary" style="padding: 8px 16px; font-size: 12px; white-space: nowrap;" title="Reset to auto-detected URL">
                    🔄 Auto
                </button>
            </div>
            
            <button class="toggle-sidebar-btn" onclick="toggleSidebar()">☰ Menu</button>
        </div>

        <!-- Dashboard Section -->
        <section id="dashboard" class="section active">
            <div class="card">
                <div class="card-header-with-action">
                    <h2 class="card-title" style="margin-bottom: 0;">
                        <span class="card-title-icon">🌐</span>
                        All Database Connections
                    </h2>
                    <div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
                        <button id="connectLocalhostBtn" class="connect-toggle-btn disconnected" onclick="toggleLocalhostConnection()">
                            <span class="toggle-icon">🔌</span>
                            <span class="toggle-text">Connect Localhost Laragon</span>
                        </button>
                        <button id="connectToggleBtn" class="connect-toggle-btn disconnected" onclick="toggleHostingerConnection()">
                            <span class="toggle-icon">🔌</span>
                            <span class="toggle-text">Connect Hostinger Shared</span>
                        </button>
                        <button id="refreshAllBtn" class="btn btn-secondary" onclick="refreshAllConnections()" style="padding: 12px 20px; display: none;">
                            <span>🔄</span> Refresh All
                        </button>
                    </div>
                </div>
                <div id="dashboardMessage"></div>
                <div id="configuredConnectionsList" class="database-grid" style="margin-top: 20px;">
                    <p style="color: rgba(254, 243, 199, 0.6); text-align: center; padding: 40px;">
                        No Hostinger connections configured.<br>
                        <span style="font-size: 14px;">Go to <strong>Hostinger Connections</strong> in the sidebar to add your first connection.</span>
                    </p>
                </div>
            </div>

            <div class="card">
                <h2 class="card-title">
                    <span class="card-title-icon">💻</span>
                    Your PC Information
                </h2>
                <div style="background: rgba(59, 130, 246, 0.1); padding: 15px; border-radius: 8px; border: 1px solid rgba(59, 130, 246, 0.3); margin-bottom: 15px;">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                        <span style="font-size: 24px;">🖥️</span>
                        <div>
                            <strong style="color: #fbbf24; font-size: 16px;">Your PC IP Address:</strong>
                            <div style="font-family: monospace; font-size: 18px; color: #3b82f6; margin-top: 5px;">192.168.8.4</div>
                        </div>
                    </div>
                    <div style="font-size: 13px; color: rgba(254, 243, 199, 0.7);">
                        💡 Use this IP when connecting to databases on your local network or for external access to your PC's MySQL server
                    </div>
                </div>
            </div>

            <div class="card">
                <h2 class="card-title">
                    <span class="card-title-icon">📊</span>
                    Quick Guide
                </h2>
                
                <!-- Auto-Detection Info Banner -->
                <div style="background: linear-gradient(135deg, rgba(34, 197, 94, 0.15) 0%, rgba(16, 185, 129, 0.1) 100%); border: 2px solid rgba(34, 197, 94, 0.4); border-radius: 10px; padding: 15px; margin-bottom: 20px;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span style="font-size: 32px;">🎯</span>
                        <div style="flex: 1;">
                            <div style="font-size: 16px; color: #86efac; font-weight: bold; margin-bottom: 5px;">
                                ✨ Smart Auto-Detection Active
                            </div>
                            <div style="font-size: 13px; color: rgba(254, 243, 199, 0.8); line-height: 1.5;">
                                <strong>API Backend:</strong> <code id="displayedApiUrl" style="background: rgba(0,0,0,0.3); padding: 3px 8px; border-radius: 4px; color: #22c55e; font-size: 12px;"></code><br>
                                💡 <strong>No configuration needed!</strong> The system automatically detects the correct API URL based on your domain.
                            </div>
                        </div>
                    </div>
                </div>
                
                <p>Welcome to the Database Control Panel. Manage both Localhost (Laragon) and Hostinger databases.</p>
                <ul style="margin-top: 15px; line-height: 1.8;">
                    <li>✅ <strong style="color: #3b82f6;">🖥️ Localhost Laragon:</strong> Connect to your local MySQL databases (auto-fetch)</li>
                    <li>✅ <strong style="color: #fbbf24;">🌐 Hostinger Remote:</strong> Add multiple remote connections (Shared/VPS)</li>
                    <li>✅ <strong style="color: #22c55e;">🎯 Auto-Detection:</strong> API URL detected automatically from your domain</li>
                    <li>✅ Toggle connections ON/OFF for security (databases hidden when disconnected)</li>
                    <li>✅ Test connection status for each database</li>
                    <li>✅ Manage tables, create, edit, delete, migrate operations</li>
                    <li>✅ All credentials stored locally in your browser</li>
                    <li>✅ Mix local and remote databases in same workspace</li>
                </ul>
                <div style="margin-top: 20px; background: rgba(59, 130, 246, 0.1); border: 1px solid #3b82f6; border-radius: 8px; padding: 12px; font-size: 13px;">
                    <strong style="color: #60a5fa;">💡 Quick Start:</strong>
                    <div style="color: rgba(254, 243, 199, 0.8); margin-top: 8px; line-height: 1.6;">
                        1️⃣ Click <strong style="color: #3b82f6;">🖥️ Connect Localhost Laragon</strong> to access local databases<br>
                        2️⃣ Click <strong style="color: #fbbf24;">🌐 Connect Hostinger Shared</strong> for remote databases<br>
                        3️⃣ Select any database to start working with tables
                    </div>
                </div>
            </div>
        </section>


        <!-- Create Database Section -->
        <section id="create" class="section">
            <div class="card">
                <h2 class="card-title">
                    <span class="card-title-icon">➕</span>
                    Create New Database
                </h2>
                
                <!-- Localhost Indicator -->
                <div style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.15) 0%, rgba(30, 64, 175, 0.1) 100%); border: 2px solid rgba(59, 130, 246, 0.4); border-radius: 10px; padding: 15px; margin-bottom: 20px; display: flex; align-items: center; gap: 15px;">
                    <div style="font-size: 36px;">🖥️</div>
                    <div style="flex: 1;">
                        <div style="font-size: 16px; color: #60a5fa; font-weight: bold; margin-bottom: 5px;">
                            Creating Database on Localhost Laragon
                        </div>
                        <div style="font-size: 13px; color: rgba(147, 197, 253, 0.8); line-height: 1.5;">
                            Server: <strong>localhost</strong> • User: <strong>root</strong> • Password: <strong>(empty)</strong><br>
                            💡 <strong>Note:</strong> Hostinger Shared Hosting doesn't allow database creation via API<br>
                            ⚡ Database will appear in Dashboard after connecting to Localhost
                        </div>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 11px; color: rgba(147, 197, 253, 0.6); margin-bottom: 5px;">STATUS</div>
                        <div id="createDbStatus" style="padding: 6px 12px; background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; border-radius: 6px; color: #fca5a5; font-size: 12px; font-weight: bold;">
                            🔴 Not Connected
                        </div>
                    </div>
                </div>
                
                <div id="createMessage"></div>
                <form onsubmit="createDatabase(event)">
                    <!-- Database Name Suggestions -->
                    <div class="form-group">
                        <label class="form-label">💡 Quick Database Name Suggestions (Click to Use)</label>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 10px; margin-bottom: 15px;">
                            <button type="button" class="btn btn-secondary db-suggestion-btn" style="padding: 10px 15px; font-size: 13px; text-align: left; display: flex; align-items: center; gap: 8px;" onclick="setDatabaseName('my_project_db')">
                                <span>📁</span> my_project_db
                            </button>
                            <button type="button" class="btn btn-secondary db-suggestion-btn" style="padding: 10px 15px; font-size: 13px; text-align: left; display: flex; align-items: center; gap: 8px;" onclick="setDatabaseName('website_db')">
                                <span>🌐</span> website_db
                            </button>
                            <button type="button" class="btn btn-secondary db-suggestion-btn" style="padding: 10px 15px; font-size: 13px; text-align: left; display: flex; align-items: center; gap: 8px;" onclick="setDatabaseName('ecommerce_store')">
                                <span>🛒</span> ecommerce_store
                            </button>
                            <button type="button" class="btn btn-secondary db-suggestion-btn" style="padding: 10px 15px; font-size: 13px; text-align: left; display: flex; align-items: center; gap: 8px;" onclick="setDatabaseName('blog_platform')">
                                <span>📝</span> blog_platform
                            </button>
                            <button type="button" class="btn btn-secondary db-suggestion-btn" style="padding: 10px 15px; font-size: 13px; text-align: left; display: flex; align-items: center; gap: 8px;" onclick="setDatabaseName('user_management')">
                                <span>👥</span> user_management
                            </button>
                            <button type="button" class="btn btn-secondary db-suggestion-btn" style="padding: 10px 15px; font-size: 13px; text-align: left; display: flex; align-items: center; gap: 8px;" onclick="setDatabaseName('inventory_system')">
                                <span>📦</span> inventory_system
                            </button>
                            <button type="button" class="btn btn-secondary db-suggestion-btn" style="padding: 10px 15px; font-size: 13px; text-align: left; display: flex; align-items: center; gap: 8px;" onclick="setDatabaseName('crm_database')">
                                <span>💼</span> crm_database
                            </button>
                            <button type="button" class="btn btn-secondary db-suggestion-btn" style="padding: 10px 15px; font-size: 13px; text-align: left; display: flex; align-items: center; gap: 8px;" onclick="setDatabaseName('api_backend')">
                                <span>🔌</span> api_backend
                            </button>
                            <button type="button" class="btn btn-secondary db-suggestion-btn" style="padding: 10px 15px; font-size: 13px; text-align: left; display: flex; align-items: center; gap: 8px;" onclick="setDatabaseName('app_data')">
                                <span>📱</span> app_data
                            </button>
                            <button type="button" class="btn btn-secondary db-suggestion-btn" style="padding: 10px 15px; font-size: 13px; text-align: left; display: flex; align-items: center; gap: 8px;" onclick="setDatabaseName('test_environment')">
                                <span>🧪</span> test_environment
                            </button>
                            <button type="button" class="btn btn-secondary db-suggestion-btn" style="padding: 10px 15px; font-size: 13px; text-align: left; display: flex; align-items: center; gap: 8px;" onclick="setDatabaseName('development_db')">
                                <span>💻</span> development_db
                            </button>
                            <button type="button" class="btn btn-secondary db-suggestion-btn" style="padding: 10px 15px; font-size: 13px; text-align: left; display: flex; align-items: center; gap: 8px;" onclick="setDatabaseName('analytics_data')">
                                <span>📊</span> analytics_data
                            </button>
                            <button type="button" class="btn btn-secondary db-suggestion-btn" style="padding: 10px 15px; font-size: 13px; text-align: left; display: flex; align-items: center; gap: 8px;" onclick="setDatabaseName('portfolio_site')">
                                <span>🎨</span> portfolio_site
                            </button>
                            <button type="button" class="btn btn-secondary db-suggestion-btn" style="padding: 10px 15px; font-size: 13px; text-align: left; display: flex; align-items: center; gap: 8px;" onclick="setDatabaseName('learning_platform')">
                                <span>📚</span> learning_platform
                            </button>
                            <button type="button" class="btn btn-secondary db-suggestion-btn" style="padding: 10px 15px; font-size: 13px; text-align: left; display: flex; align-items: center; gap: 8px;" onclick="setDatabaseName('booking_system')">
                                <span>📅</span> booking_system
                            </button>
                            <button type="button" class="btn btn-secondary db-suggestion-btn" style="padding: 10px 15px; font-size: 13px; text-align: left; display: flex; align-items: center; gap: 8px;" onclick="setDatabaseName('social_network')">
                                <span>💬</span> social_network
                            </button>
                            <button type="button" class="btn btn-secondary db-suggestion-btn" style="padding: 10px 15px; font-size: 13px; text-align: left; display: flex; align-items: center; gap: 8px;" onclick="setDatabaseName('customer_portal')">
                                <span>🏢</span> customer_portal
                            </button>
                            <button type="button" class="btn btn-secondary db-suggestion-btn" style="padding: 10px 15px; font-size: 13px; text-align: left; display: flex; align-items: center; gap: 8px;" onclick="setDatabaseName('task_manager')">
                                <span>✅</span> task_manager
                            </button>
                            <button type="button" class="btn btn-secondary db-suggestion-btn" style="padding: 10px 15px; font-size: 13px; text-align: left; display: flex; align-items: center; gap: 8px;" onclick="setDatabaseName('finance_tracker')">
                                <span>💰</span> finance_tracker
                            </button>
                            <button type="button" class="btn btn-secondary db-suggestion-btn" style="padding: 10px 15px; font-size: 13px; text-align: left; display: flex; align-items: center; gap: 8px;" onclick="setDatabaseName('event_manager')">
                                <span>🎉</span> event_manager
                            </button>
                        </div>
                        <div style="text-align: center; margin-top: 10px;">
                            <button type="button" class="btn btn-secondary" onclick="generateRandomDbName()" style="padding: 8px 16px; font-size: 13px;">
                                <span>🎲</span> Generate Random Name
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Database Name (Custom or Selected) *</label>
                        <input type="text" id="newDbName" class="form-input" placeholder="Click suggestion above or type your own name" required>
                        <div class="helper-text">💡 Click a suggestion above or type your custom database name</div>
                    </div>

                    <div class="expandable-section">
                        <div class="expandable-header" onclick="toggleExpandable('createCredentials')">
                            <span>🔐 Set Database Credentials (Optional)</span>
                            <span id="createCredentialsToggle">▼</span>
                        </div>
                        <div id="createCredentials" class="expandable-content">
                            <div style="background: rgba(245, 158, 11, 0.1); border: 1px solid #f59e0b; border-radius: 8px; padding: 12px; margin-bottom: 15px; font-size: 13px;">
                                <strong style="color: #fbbf24;">💡 Optional Settings:</strong>
                                <div style="color: rgba(254, 243, 199, 0.8); margin-top: 5px;">
                                    • <strong>Leave empty:</strong> Database will use default localhost credentials (root, no password)<br>
                                    • <strong>Fill both:</strong> Creates dedicated MySQL user with these credentials
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Database Username (Optional)</label>
                                <input type="text" id="newDbUsername" class="form-input" placeholder="e.g., db_user, admin_user (leave empty for default)">
                                <div class="helper-text">Custom MySQL user for this database only</div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Database Password (Optional)</label>
                                <input type="password" id="newDbPassword" class="form-input" placeholder="Enter secure password (leave empty for default)">
                                <div class="helper-text">Password for the custom user</div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" style="background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);">
                        <span>➕</span> Create Database on Localhost
                    </button>
                </form>
                
                <!-- Examples Section -->
                <div style="margin-top: 25px; background: rgba(34, 197, 94, 0.1); border: 1px solid #22c55e; border-radius: 10px; padding: 15px;">
                    <h4 style="color: #86efac; margin: 0 0 12px 0;">✨ Examples:</h4>
                    <div style="font-size: 13px; color: rgba(254, 243, 199, 0.8); line-height: 1.8;">
                        <strong style="color: #fbbf24;">1️⃣ Simple Database (No credentials):</strong><br>
                        <code style="background: rgba(0,0,0,0.3); padding: 3px 8px; border-radius: 4px; color: #86efac;">
                            Name: my_project • Username: (empty) • Password: (empty)
                        </code><br>
                        <span style="font-size: 12px; color: rgba(254, 243, 199, 0.6);">
                            → Uses default: root@localhost with no password
                        </span>
                        <br><br>
                        
                        <strong style="color: #fbbf24;">2️⃣ Secure Database (With credentials):</strong><br>
                        <code style="background: rgba(0,0,0,0.3); padding: 3px 8px; border-radius: 4px; color: #86efac;">
                            Name: my_secure_db • Username: db_admin • Password: MyPass123
                        </code><br>
                        <span style="font-size: 12px; color: rgba(254, 243, 199, 0.6);">
                            → Creates MySQL user 'db_admin' with full privileges on 'my_secure_db'
                        </span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Delete Database Section -->
        <section id="delete" class="section">
            <div class="card">
                <h2 class="card-title">
                    <span class="card-title-icon">🗑️</span>
                    Delete Database
                </h2>
                
                <!-- Localhost Indicator -->
                <div style="background: linear-gradient(135deg, rgba(239, 68, 68, 0.15) 0%, rgba(220, 38, 38, 0.1) 100%); border: 2px solid rgba(239, 68, 68, 0.4); border-radius: 10px; padding: 15px; margin-bottom: 20px; display: flex; align-items: center; gap: 15px;">
                    <div style="font-size: 36px;">🖥️</div>
                    <div style="flex: 1;">
                        <div style="font-size: 16px; color: #fca5a5; font-weight: bold; margin-bottom: 5px;">
                            Deleting Database from Localhost Laragon
                        </div>
                        <div style="font-size: 13px; color: rgba(252, 165, 165, 0.8); line-height: 1.5;">
                            Server: <strong>localhost</strong> • User: <strong>root</strong> • Password: <strong>(empty)</strong><br>
                            ⚠️ <strong>Warning:</strong> This will permanently delete the database and all its data!
                        </div>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 11px; color: rgba(252, 165, 165, 0.6); margin-bottom: 5px;">STATUS</div>
                        <div id="deleteDbStatus" style="padding: 6px 12px; background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; border-radius: 6px; color: #fca5a5; font-size: 12px; font-weight: bold;">
                            🔴 Not Connected
                        </div>
                    </div>
                </div>
                
                <div id="deleteMessage"></div>
                <form onsubmit="deleteDatabase(event)">
                    <div class="form-group">
                        <label class="form-label">Select Database to Delete *</label>
                        <select id="deleteDbSelect" class="form-select" required>
                            <option value="">-- Select Database --</option>
                        </select>
                        <div class="helper-text">⚠️ This action cannot be undone!</div>
                    </div>
                    <button type="submit" class="btn btn-danger">
                        <span>🗑️</span> Delete Database from Localhost
                    </button>
                </form>
            </div>
        </section>

        <!-- Rename Database Section -->
        <section id="rename" class="section">
            <div class="card">
                <h2 class="card-title">
                    <span class="card-title-icon">✏️</span>
                    Rename Database
                </h2>
                
                <!-- Localhost Indicator -->
                <div style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.15) 0%, rgba(217, 119, 6, 0.1) 100%); border: 2px solid rgba(245, 158, 11, 0.4); border-radius: 10px; padding: 15px; margin-bottom: 20px; display: flex; align-items: center; gap: 15px;">
                    <div style="font-size: 36px;">🖥️</div>
                    <div style="flex: 1;">
                        <div style="font-size: 16px; color: #fbbf24; font-weight: bold; margin-bottom: 5px;">
                            Renaming Database on Localhost Laragon
                        </div>
                        <div style="font-size: 13px; color: rgba(251, 191, 36, 0.8); line-height: 1.5;">
                            Server: <strong>localhost</strong> • User: <strong>root</strong> • Password: <strong>(empty)</strong><br>
                            💡 MySQL doesn't have RENAME DATABASE - This creates new DB, copies tables, then drops old one
                        </div>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 11px; color: rgba(251, 191, 36, 0.6); margin-bottom: 5px;">STATUS</div>
                        <div id="renameDbStatus" style="padding: 6px 12px; background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; border-radius: 6px; color: #fca5a5; font-size: 12px; font-weight: bold;">
                            🔴 Not Connected
                        </div>
                    </div>
                </div>
                
                <div id="renameMessage"></div>
                <form onsubmit="renameDatabase(event)">
                    <div class="form-group">
                        <label class="form-label">Select Database to Rename *</label>
                        <select id="renameOldSelect" class="form-select" required>
                            <option value="">-- Select Database --</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">New Database Name *</label>
                        <input type="text" id="renameNewName" class="form-input" placeholder="Enter new name" required>
                        <div class="helper-text">Use alphanumeric characters and underscores only</div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                        <span>✏️</span> Rename Database on Localhost
                    </button>
                </form>
            </div>
        </section>

        <!-- Set Credentials Section -->
        <section id="credentials" class="section">
            <div class="card">
                <h2 class="card-title">
                    <span class="card-title-icon">🔐</span>
                    Set Database Credentials
                </h2>
                <div id="credentialsMessage"></div>
                <p style="margin-bottom: 20px;">Add username and password authentication to secure your database. This creates a database user with specific credentials.</p>
                <form onsubmit="setDatabaseCredentials(event)">
                    <div class="form-group">
                        <label class="form-label">Select Database *</label>
                        <select id="credentialsDbSelect" class="form-select" required onchange="checkExistingCredentials()">
                            <option value="">-- Select Database --</option>
                        </select>
                    </div>
                    <div id="credentialStatus" style="margin-bottom: 15px;"></div>
                    <div class="form-group">
                        <label class="form-label">Username *</label>
                        <input type="text" id="credentialsUsername" class="form-input" placeholder="Enter username" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password *</label>
                        <input type="password" id="credentialsPassword" class="form-input" placeholder="Enter password" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm Password *</label>
                        <input type="password" id="credentialsConfirmPassword" class="form-input" placeholder="Confirm password" required>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <span>🔐</span> Set Credentials
                    </button>
                </form>
            </div>
        </section>

        <!-- Settings Section - Hostinger Connections -->
        <section id="settings" class="section">
            <div class="card">
                <h2 class="card-title">
                    <span class="card-title-icon">⚙️</span>
                    Manage Hostinger Connections
                </h2>
                <div class="security-notice">
                    <strong>🌐 Hostinger Database Connections:</strong> Add your Hostinger database credentials (Shared Hosting or VPS). All data is stored locally in your browser's localStorage.
                </div>
                <div id="settingsMessage"></div>

                <!-- Add New Connection Form -->
                <div style="background: rgba(255, 255, 255, 0.05); padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                    <h3 style="color: #fbbf24; margin-bottom: 15px;">➕ Add New Connection</h3>
                    <form onsubmit="addHostingerConnection(event)">
                        <div class="form-group">
                            <label class="form-label">Connection Name *</label>
                            <input type="text" id="connName" class="form-input" placeholder="e.g., My VPS, Shared Host 1" required>
                            <div class="helper-text">Give a friendly name to identify this connection</div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Connection Type *</label>
                            <select id="connType" class="form-select" required>
                                <option value="shared">Shared Hosting</option>
                                <option value="vps">VPS</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Database Host *</label>
                            <div style="display: flex; gap: 10px; margin-bottom: 5px;">
                                <button type="button" class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px; flex: 1;" onclick="document.getElementById('connHost').value='192.168.8.4'">🖥️ My PC (192.168.8.4)</button>
                                <button type="button" class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px; flex: 1;" onclick="document.getElementById('connHost').value='localhost'">📍 localhost</button>
                                <button type="button" class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px; flex: 1;" onclick="document.getElementById('connHost').value='127.0.0.1'">🔗 127.0.0.1</button>
                            </div>
                            <input type="text" id="connHost" class="form-input" list="hostList" placeholder="e.g., localhost, 192.168.8.4, or Hostinger host" value="192.168.8.4" required>
                            <datalist id="hostList"></datalist>
                            <div class="helper-text">💡 Your PC IP: 192.168.8.4 | Use 'localhost' for shared hosting | Use Hostinger host for remote</div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Database Name *</label>
                            <input type="text" id="connDbName" class="form-input" list="dbNameList" placeholder="Database name" required>
                            <datalist id="dbNameList"></datalist>
                            <div class="helper-text">💡 Select from previous entries or type new name</div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Database Username *</label>
                            <input type="text" id="connUsername" class="form-input" list="usernameList" placeholder="Username" required>
                            <datalist id="usernameList"></datalist>
                            <div class="helper-text">💡 Select from previous entries or type new username</div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Database Password *</label>
                            <div style="position: relative;">
                                <input type="password" id="connPassword" class="form-input" placeholder="Password" required style="padding-right: 45px;">
                                <button type="button" onclick="togglePasswordVisibility()" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: rgba(59, 130, 246, 0.2); border: 1px solid #3b82f6; color: #93c5fd; padding: 6px 10px; border-radius: 6px; cursor: pointer; font-size: 16px; transition: all 0.3s;" id="togglePasswordBtn">
                                    👁️
                                </button>
                            </div>
                            <div class="helper-text">💡 Click the eye icon to show/hide password</div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Port</label>
                            <input type="text" id="connPort" class="form-input" placeholder="3306" value="3306">
                            <div class="helper-text">Default MySQL port is 3306</div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <span>➕</span> Add Connection
                        </button>
                    </form>
                </div>

                <!-- Connections List Table -->
                <div style="margin-top: 30px;">
                    <h3 style="color: #fbbf24; margin-bottom: 15px;">📋 Saved Connections</h3>
                    <div id="hostingerConnectionsTable"></div>
                </div>

                <!-- Export/Import Buttons -->
                <div style="margin-top: 30px; display: flex; gap: 15px; flex-wrap: wrap;">
                    <button class="btn btn-primary" onclick="exportConnectionsToFile()">
                        <span>📤</span> Export (Save to File)
                    </button>
                    <button class="btn btn-primary" onclick="showImportModal()">
                        <span>📥</span> Import (Load from File)
                    </button>
                    <button class="btn btn-danger" onclick="clearAllHostingerConnections()">
                        <span>🗑️</span> Clear All Connections
                    </button>
                </div>

                <!-- Export/Import Info -->
                <div style="margin-top: 20px; background: rgba(59, 130, 246, 0.1); padding: 15px; border-radius: 8px; border: 1px solid rgba(59, 130, 246, 0.3);">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                        <span style="font-size: 24px;">💾</span>
                        <div>
                            <strong style="color: #fbbf24; font-size: 16px;">Backup & Restore with File Picker</strong>
                        </div>
                    </div>
                    <div style="font-size: 13px; color: rgba(254, 243, 199, 0.7); line-height: 1.6;">
                        <strong>📤 Export:</strong> Opens file picker - Choose where to save your backup (JSON file)<br>
                        <strong>📥 Import:</strong> Opens file picker - Select a backup file to restore<br>
                        <strong>💡 Smart Merge:</strong> Import merges with existing connections (no data loss)<br>
                        <strong>🔒 Secure:</strong> Files saved on your computer with full control
                    </div>
                </div>
            </div>
        </section>

        <!-- Generate Database SQL Section -->
        <section id="generateDatabase" class="section">
            <div class="card">
                <h2 class="card-title">
                    <span class="card-title-icon">🗄️</span>
                    Database SQL Tools
                </h2>
                <div id="generateDatabaseMessage"></div>
                
                <!-- Selected Database Info -->
                <div id="selectedDbInfo" style="background: rgba(59, 130, 246, 0.1); padding: 15px; border-radius: 8px; border: 1px solid rgba(59, 130, 246, 0.3); margin-bottom: 20px;">
                    <div style="font-size: 14px; color: rgba(254, 243, 199, 0.9);">
                        <strong>📊 Selected Database:</strong> <span id="selectedDbNameDisplay" style="color: #fbbf24; font-weight: bold;">None</span>
                    </div>
                    <div style="font-size: 13px; color: rgba(254, 243, 199, 0.7); margin-top: 5px;">
                        💡 Please select a database from the Dashboard
                    </div>
                </div>

                <!-- Tabs -->
                <div class="tab-buttons">
                    <button class="tab-btn active" onclick="switchDatabaseSQLTab('generate')">🗄️ Generate SQL Dump</button>
                    <button class="tab-btn" onclick="switchDatabaseSQLTab('executor')">⚡ SQL Executor</button>
                    <button class="tab-btn" onclick="switchDatabaseSQLTab('migration')">🔄 Database Migration</button>
                </div>

                <!-- Tab 1: Generation Options -->
                <div id="generateSQLTab" class="tab-content active">
                <div style="background: rgba(255, 255, 255, 0.05); padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                    <h3 style="color: #fbbf24; margin-bottom: 20px;">📝 SQL Generation Options</h3>
                    
                    <!-- Option 1: Structure Only -->
                    <div style="background: rgba(255, 255, 255, 0.05); border: 2px solid rgba(251, 191, 36, 0.2); border-radius: 10px; padding: 20px; margin-bottom: 15px; transition: all 0.3s;" onmouseover="this.style.borderColor='#fbbf24'; this.style.background='rgba(251, 191, 36, 0.1)';" onmouseout="this.style.borderColor='rgba(251, 191, 36, 0.2)'; this.style.background='rgba(255, 255, 255, 0.05)';">
                        <div style="display: flex; justify-content: space-between; align-items: center; gap: 20px;">
                            <div style="flex: 1;">
                                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                                    <span style="font-size: 32px;">🏗️</span>
                                    <div>
                                        <h4 style="color: #fbbf24; margin: 0;">Tables Structure Only</h4>
                                        <p style="margin: 5px 0 0 0; font-size: 13px; color: rgba(254, 243, 199, 0.7);">
                                            Generate CREATE TABLE statements for all tables (no data)<br>
                                            ✅ Perfect for: Injecting into existing database
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <button class="btn btn-primary" onclick="generateDatabaseSQL(false, false)" style="padding: 12px 24px;">
                                <span>🏗️</span> Generate
                            </button>
                        </div>
                    </div>

                    <!-- Option 2: Full Database (CREATE DATABASE + Structure) -->
                    <div style="background: rgba(255, 255, 255, 0.05); border: 2px solid rgba(251, 191, 36, 0.2); border-radius: 10px; padding: 20px; margin-bottom: 15px; transition: all 0.3s;" onmouseover="this.style.borderColor='#fbbf24'; this.style.background='rgba(251, 191, 36, 0.1)';" onmouseout="this.style.borderColor='rgba(251, 191, 36, 0.2)'; this.style.background='rgba(255, 255, 255, 0.05)';">
                        <div style="display: flex; justify-content: space-between; align-items: center; gap: 20px;">
                            <div style="flex: 1;">
                                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                                    <span style="font-size: 32px;">🗄️</span>
                                    <div>
                                        <h4 style="color: #fbbf24; margin: 0;">Full Database (CREATE DATABASE + Tables)</h4>
                                        <p style="margin: 5px 0 0 0; font-size: 13px; color: rgba(254, 243, 199, 0.7);">
                                            Generate CREATE DATABASE + all table structures (no data)<br>
                                            ✅ Perfect for: Complete database migration
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <button class="btn btn-primary" onclick="generateDatabaseSQL(true, false)" style="padding: 12px 24px;">
                                <span>🗄️</span> Generate
                            </button>
                        </div>
                    </div>

                    <!-- Option 3: Structure + Data -->
                    <div style="background: rgba(255, 255, 255, 0.05); border: 2px solid rgba(251, 191, 36, 0.2); border-radius: 10px; padding: 20px; margin-bottom: 15px; transition: all 0.3s;" onmouseover="this.style.borderColor='#fbbf24'; this.style.background='rgba(251, 191, 36, 0.1)';" onmouseout="this.style.borderColor='rgba(251, 191, 36, 0.2)'; this.style.background='rgba(255, 255, 255, 0.05)';">
                        <div style="display: flex; justify-content: space-between; align-items: center; gap: 20px;">
                            <div style="flex: 1;">
                                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                                    <span style="font-size: 32px;">📦</span>
                                    <div>
                                        <h4 style="color: #fbbf24; margin: 0;">Tables Structure + Data</h4>
                                        <p style="margin: 5px 0 0 0; font-size: 13px; color: rgba(254, 243, 199, 0.7);">
                                            Generate all table structures + INSERT data (no CREATE DATABASE)<br>
                                            ✅ Perfect for: Injecting complete data into existing database
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <button class="btn btn-primary" onclick="generateDatabaseSQL(false, true)" style="padding: 12px 24px;">
                                <span>📦</span> Generate
                            </button>
                        </div>
                    </div>

                    <!-- Option 4: Full Database + Data -->
                    <div style="background: rgba(255, 255, 255, 0.05); border: 2px solid rgba(34, 197, 94, 0.2); border-radius: 10px; padding: 20px; transition: all 0.3s;" onmouseover="this.style.borderColor='#22c55e'; this.style.background='rgba(34, 197, 94, 0.1)';" onmouseout="this.style.borderColor='rgba(34, 197, 94, 0.2)'; this.style.background='rgba(255, 255, 255, 0.05)';">
                        <div style="display: flex; justify-content: space-between; align-items: center; gap: 20px;">
                            <div style="flex: 1;">
                                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                                    <span style="font-size: 32px;">💎</span>
                                    <div>
                                        <h4 style="color: #86efac; margin: 0;">Complete Database Dump (Everything!)</h4>
                                        <p style="margin: 5px 0 0 0; font-size: 13px; color: rgba(254, 243, 199, 0.7);">
                                            Generate CREATE DATABASE + all tables + all data<br>
                                            ✅ Perfect for: Complete backup and restore
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <button class="btn btn-primary" onclick="generateDatabaseSQL(true, true)" style="padding: 12px 24px; background: #22c55e; color: #1e3a8a;">
                                <span>💎</span> Generate All
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Help Section -->
                <div style="background: rgba(245, 158, 11, 0.1); border: 1px solid #f59e0b; border-radius: 8px; padding: 15px;">
                    <h4 style="color: #fbbf24; margin: 0 0 10px 0;">💡 Quick Tips</h4>
                    <ul style="margin: 0; padding-left: 20px; line-height: 1.8; font-size: 13px; color: rgba(254, 243, 199, 0.8);">
                        <li><strong>Structure Only:</strong> Use when you want to recreate table structure in another database</li>
                        <li><strong>+ CREATE DATABASE:</strong> Use when migrating to new server (with database creation permission)</li>
                        <li><strong>+ Data:</strong> Use for complete backup or migration with all data</li>
                        <li><strong>Complete Dump:</strong> Full backup - includes everything</li>
                    </ul>
                </div>
                </div>
                </div>

                <!-- Tab 2: SQL Executor -->
                <div id="sqlExecutorTab" class="tab-content">
                    <div style="background: rgba(255, 255, 255, 0.05); padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                            <h3 style="color: #fbbf24; margin: 0;">⚡ SQL Query Builder & Executor</h3>
                            <button class="btn btn-secondary" onclick="toggleVisualBuilder()" id="toggleBuilderBtn">
                                <span>🎨</span> <span id="builderToggleText">Show Visual Builder</span>
                            </button>
                        </div>
                        
                        <div style="background: rgba(59, 130, 246, 0.1); border: 1px solid #3b82f6; border-radius: 8px; padding: 12px; margin-bottom: 15px; font-size: 13px;">
                            <strong style="color: #93c5fd;">💡 Two Ways:</strong> Use Visual Builder for easy query creation OR write SQL directly in the textarea below.
                        </div>

                        <!-- Visual Query Builder -->
                        <div id="visualQueryBuilder" style="display: none; background: rgba(34, 197, 94, 0.05); border: 2px solid #22c55e; border-radius: 10px; padding: 20px; margin-bottom: 20px;">
                            <h4 style="color: #22c55e; margin: 0 0 20px 0;">🎨 Visual Query Builder</h4>
                            
                            <!-- Step 1: Select Operation -->
                            <div class="form-group">
                                <label class="form-label">1️⃣ Select Operation</label>
                                <select id="queryOperation" class="form-select" onchange="updateQueryBuilder()">
                                    <option value="">-- Choose Operation --</option>
                                    <option value="SELECT">🔍 SELECT - Read data</option>
                                    <option value="INSERT">➕ INSERT - Add new record</option>
                                    <option value="UPDATE">✏️ UPDATE - Modify records</option>
                                    <option value="DELETE">🗑️ DELETE - Remove records</option>
                                </select>
                            </div>

                            <!-- Step 2: Select Table -->
                            <div class="form-group">
                                <label class="form-label">2️⃣ Select Table</label>
                                <select id="queryTable" class="form-select" onchange="loadTableColumnsForQuery()">
                                    <option value="">-- Choose Table --</option>
                                </select>
                                <div class="helper-text" id="tableRecordCount"></div>
                            </div>

                            <!-- Dynamic Query Options (changes based on operation) -->
                            <div id="queryOptionsContainer"></div>

                            <!-- Build Query Button -->
                            <div style="margin-top: 20px;">
                                <button class="btn btn-primary" onclick="buildQueryFromVisual()" style="background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);">
                                    <span>🔨</span> Build SQL Query
                                </button>
                                <button class="btn btn-secondary" onclick="resetQueryBuilder()">
                                    <span>🔄</span> Reset Builder
                                </button>
                            </div>
                        </div>

                        <!-- SQL Input with Ghost Text Autocomplete -->
                        <div class="form-group">
                            <label class="form-label">SQL Query</label>
                            <div class="sql-ac-wrapper">
                                <pre id="sqlGhostLayer" class="sql-ac-ghost"></pre>
                                <textarea id="customSQLInput" class="form-input sql-ac-textarea" rows="8" placeholder="Start typing SQL... (Ctrl+Space for AI suggestions, Tab to accept)" style="font-family: 'Courier New', monospace; font-size: 14px; line-height: 1.5; background: rgba(255, 255, 255, 0.1);" onkeydown="handleSQLAutocompleteKeydown(event)" oninput="handleSQLAutocompleteInput()" onscroll="syncGhostScroll()" onclick="handleSQLAutocompleteInput()" onfocus="handleSQLAutocompleteFocus()" onblur="handleSQLAutocompleteBlur()"></textarea>
                            </div>
                            <div class="helper-text">
                                💡 <strong>Ghost Text Autocomplete:</strong> Press <kbd style="background: rgba(251, 191, 36, 0.2); padding: 2px 6px; border-radius: 4px;">Ctrl+Space</kbd> for AI suggestion | 
                                <kbd style="background: rgba(251, 191, 36, 0.2); padding: 2px 6px; border-radius: 4px;">Tab</kbd> to accept | 
                                <kbd style="background: rgba(251, 191, 36, 0.2); padding: 2px 6px; border-radius: 4px;">Esc</kbd> to dismiss
                            </div>
                        </div>

                        <!-- Execute Button -->
                        <div style="display: flex; gap: 10px; margin-bottom: 20px;">
                            <button class="btn btn-primary" onclick="executeSQLQuery()" id="executeSQLBtn" disabled style="background: linear-gradient(135deg, #22c55e 0%, #15803d 100%);">
                                <span>▶️</span> Execute Query
                            </button>
                            <button class="btn btn-secondary" onclick="clearSQLInput()">
                                <span>🗑️</span> Clear
                            </button>
                            <button class="btn btn-secondary" onclick="showSQLExamples()">
                                <span>📚</span> Examples
                            </button>
                        </div>

                        <!-- Results Container -->
                        <div id="sqlResultsContainer" style="display: none;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                <h4 style="color: #fbbf24; margin: 0;">Query Results</h4>
                                <button class="btn btn-secondary" onclick="closeSQLResults()" style="padding: 6px 12px; font-size: 12px;">
                                    ✖️ Close Results
                                </button>
                            </div>
                            
                            <div id="sqlResultsMessage"></div>
                            <div id="sqlResultsData"></div>
                        </div>

                        <!-- SQL Examples -->
                        <div id="sqlExamplesContainer" style="display: none; background: rgba(59, 130, 246, 0.1); border: 1px solid #3b82f6; border-radius: 8px; padding: 15px; margin-top: 15px;">
                            <h4 style="color: #93c5fd; margin: 0 0 10px 0;">📚 SQL Query Examples</h4>
                            <div style="font-size: 13px; line-height: 1.8; color: rgba(254, 243, 199, 0.9);">
                                <div style="margin-bottom: 10px; cursor: pointer;" onclick="insertSQLExample('SELECT * FROM users LIMIT 10;')">
                                    <strong style="color: #fbbf24;">SELECT:</strong> <code style="background: rgba(0,0,0,0.3); padding: 2px 6px; border-radius: 4px;">SELECT * FROM users LIMIT 10;</code>
                                </div>
                                <div style="margin-bottom: 10px; cursor: pointer;" onclick="insertSQLExample('INSERT INTO users (username, email) VALUES (\\'testuser\\', \\'test@email.com\\');')">
                                    <strong style="color: #fbbf24;">INSERT:</strong> <code style="background: rgba(0,0,0,0.3); padding: 2px 6px; border-radius: 4px;">INSERT INTO users (username, email) VALUES ('testuser', 'test@email.com');</code>
                                </div>
                                <div style="margin-bottom: 10px; cursor: pointer;" onclick="insertSQLExample('UPDATE users SET status = \\'active\\' WHERE id = 1;')">
                                    <strong style="color: #fbbf24;">UPDATE:</strong> <code style="background: rgba(0,0,0,0.3); padding: 2px 6px; border-radius: 4px;">UPDATE users SET status = 'active' WHERE id = 1;</code>
                                </div>
                                <div style="margin-bottom: 10px; cursor: pointer;" onclick="insertSQLExample('DELETE FROM logs WHERE created_at < \\'2024-01-01\\';')">
                                    <strong style="color: #fbbf24;">DELETE:</strong> <code style="background: rgba(0,0,0,0.3); padding: 2px 6px; border-radius: 4px;">DELETE FROM logs WHERE created_at < '2024-01-01';</code>
                                </div>
                                <div style="margin-bottom: 10px; cursor: pointer;" onclick="insertSQLExample('SHOW TABLES;')">
                                    <strong style="color: #fbbf24;">SHOW TABLES:</strong> <code style="background: rgba(0,0,0,0.3); padding: 2px 6px; border-radius: 4px;">SHOW TABLES;</code>
                                </div>
                                <div style="margin-bottom: 10px; cursor: pointer;" onclick="insertSQLExample('DESCRIBE users;')">
                                    <strong style="color: #fbbf24;">DESCRIBE:</strong> <code style="background: rgba(0,0,0,0.3); padding: 2px 6px; border-radius: 4px;">DESCRIBE users;</code>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab 3: Database Migration -->
                <div id="databaseMigrationTab" class="tab-content">
                    <div style="display: flex; gap: 20px; align-items: flex-start;">
                        <!-- Source Tables Container (Left Side - 48%) -->
                        <div style="flex: 0 0 48%; background: linear-gradient(135deg, rgba(30, 58, 138, 0.3) 0%, rgba(153, 27, 27, 0.3) 100%); border: 2px solid rgba(251, 191, 36, 0.3); border-radius: 12px; padding: 20px; backdrop-filter: blur(10px);">
                            <!-- Container Header -->
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid rgba(251, 191, 36, 0.3);">
                                <div>
                                    <h3 style="color: #fbbf24; margin: 0; font-size: 18px; display: flex; align-items: center; gap: 10px;">
                                        <span style="font-size: 24px;">📦</span>
                                        <span>Source Tables</span>
                                    </h3>
                                    <p style="color: rgba(254, 243, 199, 0.7); margin: 5px 0 0 34px; font-size: 13px;" id="migrationSourceDbName">
                                        No database selected
                                    </p>
                                </div>
                                <div style="display: flex; gap: 8px;">
                                    <button onclick="showDatabaseInfo()" class="btn btn-primary" style="padding: 8px 12px; font-size: 12px; background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);" title="Show database credentials and selected tables info">
                                        <span>🗄️</span> DB-Info
                                    </button>
                                    <button onclick="refreshMigrationTables()" class="btn btn-secondary" style="padding: 8px 12px; font-size: 12px;" title="Refresh tables">
                                        <span>🔄</span>
                                    </button>
                                    <button onclick="selectAllMigrationTables()" class="btn btn-secondary" style="padding: 8px 12px; font-size: 12px;" title="Select all">
                                        <span>✅</span>
                                    </button>
                                    <button onclick="deselectAllMigrationTables()" class="btn btn-secondary" style="padding: 8px 12px; font-size: 12px;" title="Deselect all">
                                        <span>❌</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Tables Grid - NOW ALSO A DROP ZONE (for reverse drag from Destination) -->
                            <div id="migrationTablesContainer" 
                                 class="migration-drop-zone"
                                 ondrop="handleSourceDrop(event)"
                                 ondragover="handleSourceDragOver(event)"
                                 ondragleave="handleSourceDragLeave(event)"
                                 style="display: flex; flex-wrap: wrap; gap: 10px; min-height: 200px; max-height: 500px; overflow-y: auto; padding: 5px; transition: all 0.3s;">
                                <!-- Tables will be loaded here dynamically -->
                                <div style="width: 100%; text-align: center; padding: 40px 20px; color: rgba(254, 243, 199, 0.5);">
                                    <div style="font-size: 48px; margin-bottom: 15px;">📦</div>
                                    <p style="font-size: 14px;">Select a database to view tables</p>
                                </div>
                            </div>

                            <!-- Selected Count -->
                            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid rgba(251, 191, 36, 0.2); color: rgba(254, 243, 199, 0.8); font-size: 13px; display: flex; justify-content: space-between; align-items: center;">
                                <span id="migrationSelectedCount">0 tables selected</span>
                                <span id="migrationTotalCount">0 tables total</span>
                            </div>
                        </div>

                        <!-- Arrow Container (Center - Two-Way System) -->
                        <div style="flex: 0 0 60px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 15px;">
                            <div style="font-size: 36px; animation: pulse 2s infinite;">➡️</div>
                            <div style="width: 2px; height: 80px; background: linear-gradient(to bottom, rgba(251, 191, 36, 0.5), rgba(34, 197, 94, 0.5));"></div>
                            <div style="font-size: 36px; animation: pulse 2s infinite; animation-delay: 1s;">⬅️</div>
                        </div>

                        <!-- Destination Database Container (Right Side - 48%) -->
                        <div style="flex: 0 0 48%; background: linear-gradient(135deg, rgba(34, 197, 94, 0.2) 0%, rgba(59, 130, 246, 0.2) 100%); border: 2px solid rgba(34, 197, 94, 0.4); border-radius: 12px; padding: 20px; backdrop-filter: blur(10px);">
                            <!-- Container Header -->
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid rgba(34, 197, 94, 0.3);">
                                <div>
                                    <h3 style="color: #86efac; margin: 0; font-size: 18px; display: flex; align-items: center; gap: 10px;">
                                        <span style="font-size: 24px;">📥</span>
                                        <span>Destination Database</span>
                                    </h3>
                                    <p style="color: rgba(254, 243, 199, 0.7); margin: 5px 0 0 34px; font-size: 13px;">
                                        Select where to copy tables
                                    </p>
                                </div>
                                <div style="display: flex; gap: 8px;">
                                    <button onclick="showDestinationDatabaseInfo()" class="btn btn-primary" style="padding: 8px 12px; font-size: 12px; background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);" title="Show database credentials and selected tables info">
                                        <span>🗄️</span> DB-Info
                                    </button>
                                    <button onclick="refreshDestinationTables()" class="btn btn-secondary" style="padding: 8px 12px; font-size: 12px;" title="Refresh tables">
                                        <span>🔄</span>
                                    </button>
                                    <button onclick="selectAllDestinationTables()" class="btn btn-secondary" style="padding: 8px 12px; font-size: 12px;" title="Select all">
                                        <span>✅</span>
                                    </button>
                                    <button onclick="deselectAllDestinationTables()" class="btn btn-secondary" style="padding: 8px 12px; font-size: 12px;" title="Deselect all">
                                        <span>❌</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Database Selection Dropdown -->
                            <div style="margin-bottom: 20px;">
                                <label style="display: block; color: #fbbf24; font-size: 14px; font-weight: 500; margin-bottom: 10px;">
                                    🎯 Select Destination Database
                                </label>
                                <select id="migrationDestinationSelect" 
                                        onchange="loadDestinationTables()"
                                        style="width: 100%; padding: 12px 15px; background: linear-gradient(135deg, rgba(0, 0, 0, 0.4) 0%, rgba(0, 0, 0, 0.2) 100%); border: 2px solid rgba(34, 197, 94, 0.4); border-radius: 8px; color: #fef3c7; font-size: 14px; cursor: pointer; outline: none; transition: all 0.3s;">
                                    <option value="">-- Select Destination --</option>
                                </select>
                            </div>

                            <!-- Destination Info Display -->
                            <div id="migrationDestinationInfo" style="background: rgba(59, 130, 246, 0.15); border: 1px solid rgba(59, 130, 246, 0.4); border-radius: 8px; padding: 12px 15px; margin-bottom: 20px; display: none;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <span style="font-size: 20px;">🌐</span>
                                    <div style="flex: 1;">
                                        <div style="color: #93c5fd; font-size: 13px; font-weight: 500;" id="migrationDestDbName">Database Name</div>
                                        <div style="color: rgba(254, 243, 199, 0.6); font-size: 11px;" id="migrationDestHost">Host</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Destination Tables Container -->
                            <div id="migrationDestinationTablesContainer" 
                                 class="migration-drop-zone"
                                 ondrop="handleDrop(event)"
                                 ondragover="handleDragOver(event)"
                                 ondragleave="handleDragLeave(event)"
                                 style="display: flex; flex-wrap: wrap; gap: 10px; min-height: 200px; max-height: 340px; overflow-y: auto; padding: 5px; background: rgba(0, 0, 0, 0.2); border-radius: 8px; border: 1px solid rgba(34, 197, 94, 0.2); transition: all 0.3s;">
                                <!-- Destination tables will be loaded here -->
                                <div style="width: 100%; text-align: center; padding: 40px 20px; color: rgba(254, 243, 199, 0.5);">
                                    <div style="font-size: 48px; margin-bottom: 15px;">🎯</div>
                                    <p style="font-size: 14px;">Select a destination database</p>
                                </div>
                            </div>

                            <!-- Destination Tables Count -->
                            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid rgba(34, 197, 94, 0.2); color: rgba(254, 243, 199, 0.8); font-size: 13px; display: flex; justify-content: space-between; align-items: center;">
                                <span id="migrationDestinationSelectedCount">0 tables selected</span>
                                <span id="migrationDestinationCount">0 tables total</span>
                            </div>

                            <!-- Drag & Drop Instruction - TWO-WAY SYSTEM -->
                            <div style="margin-top: 20px; padding-top: 20px; border-top: 2px solid rgba(34, 197, 94, 0.3); color: rgba(254, 243, 199, 0.7); font-size: 13px;">
                                <div style="text-align: center; margin-bottom: 15px;">
                                    <div style="font-size: 32px; margin-bottom: 10px;">⬅️ 🔄 ➡️</div>
                                    <p style="margin: 5px 0; font-weight: bold; background: linear-gradient(135deg, #fbbf24 0%, #22c55e 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-size: 16px;">TWO-WAY MIGRATION SYSTEM</p>
                                    <p style="font-size: 12px; margin-top: 5px;">Drag Source ➡️ Destination OR Destination ⬅️ Source</p>
                                </div>
                                <div style="background: rgba(245, 158, 11, 0.1); border: 1px solid #f59e0b; border-radius: 6px; padding: 10px; font-size: 12px;">
                                    <div style="margin-bottom: 12px; background: rgba(251, 191, 36, 0.1); padding: 8px; border-radius: 6px; border-left: 3px solid #fbbf24;">
                                        <strong style="color: #fbbf24;">📤 SOURCE → DESTINATION (Left to Right):</strong>
                                    </div>
                                    <div style="margin-bottom: 8px; margin-left: 10px;">
                                        <strong style="color: #fbbf24;">🏗️ Drag yellow badge:</strong> Move (Structure Only)
                                    </div>
                                    <div style="margin-bottom: 12px; margin-left: 10px;">
                                        <strong style="color: #22c55e;">✋ Drag green badge:</strong> Move (Structure + Data)
                                    </div>
                                    
                                    <div style="margin-bottom: 12px; background: rgba(34, 197, 94, 0.1); padding: 8px; border-radius: 6px; border-left: 3px solid #22c55e;">
                                        <strong style="color: #86efac;">📥 DESTINATION → SOURCE (Right to Left):</strong>
                                    </div>
                                    <div style="margin-bottom: 8px; margin-left: 10px;">
                                        <strong style="color: #22c55e;">🏗️ Drag green badge:</strong> Move (Structure Only)
                                    </div>
                                    <div style="margin-bottom: 12px; margin-left: 10px;">
                                        <strong style="color: #10b981;">✋ Drag green badge:</strong> Move (Structure + Data)
                                    </div>
                                    
                                    <div style="border-top: 1px dashed rgba(251, 191, 36, 0.3); margin: 12px 0; padding-top: 12px;">
                                        <div style="font-weight: bold; color: #fbbf24; margin-bottom: 8px;">⚙️ COMMON OPERATIONS (Both Sides):</div>
                                    </div>
                                    <div style="margin-bottom: 8px;">
                                        <strong style="color: #3b82f6;">🎲 Blue "Dice" button:</strong> Inject 10 random records
                                    </div>
                                    <div style="margin-bottom: 8px;">
                                        <strong style="color: #a78bfa;">📋 Purple "Copy" button:</strong> Duplicate table in same DB
                                    </div>
                                    <div style="margin-bottom: 8px;">
                                        <strong style="color: #f59e0b;">🧹 Orange "Empty" button:</strong> Delete all data (keep structure)
                                    </div>
                                    <div style="margin-bottom: 8px;">
                                        <strong style="color: #ef4444;">🗑️ Red "Delete" button:</strong> Permanently delete table
                                    </div>
                                    <div style="margin-bottom: 8px;">
                                        <strong style="color: #8b5cf6;">🗄️ Purple "DB-Info" button (header):</strong> View database credentials & connection strings
                                    </div>
                                    <div style="margin-bottom: 8px;">
                                        <strong style="color: #fbbf24;">🖱️ Right-click name:</strong> Rename inline (Enter to save, Esc to cancel)
                                    </div>
                                    <div style="margin-bottom: 8px; background: rgba(34, 197, 94, 0.1); padding: 8px; border-radius: 6px; border: 1px solid rgba(34, 197, 94, 0.3);">
                                        <strong style="color: #86efac;">✨ Multi-Selection:</strong> Click multiple tables → Drag any one → All migrate together!
                                    </div>
                                    <div style="font-size: 11px; opacity: 0.8; margin-top: 8px; padding-top: 8px; border-top: 1px solid rgba(245, 158, 11, 0.2);">
                                        💡 Both containers support ALL operations • Full bi-directional migration
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- AI Prompter Section -->
        <section id="aiPrompt" class="section">
            <div class="card">
                <h2 class="card-title">
                    <span class="card-title-icon">🤖</span>
                    AI Assistant Helper
                </h2>
                <div id="aiPromptMessage"></div>
                
                <!-- Selected Database Info -->
                <div id="aiPromptDbInfo" style="background: rgba(59, 130, 246, 0.1); padding: 15px; border-radius: 8px; border: 1px solid rgba(59, 130, 246, 0.3); margin-bottom: 20px;">
                    <div style="font-size: 14px; color: rgba(254, 243, 199, 0.9);">
                        <strong>📊 Selected Database:</strong> <span id="aiPromptDbNameDisplay" style="color: #fbbf24; font-weight: bold;">None</span>
                    </div>
                    <div style="font-size: 13px; color: rgba(254, 243, 199, 0.7); margin-top: 5px;">
                        💡 Please select a database from the Dashboard to generate AI prompts
                    </div>
                </div>

                <!-- Tabs -->
                <div class="tab-buttons">
                    <button class="tab-btn active" onclick="switchAITab('connection')">🔌 Connection Prompt</button>
                    <button class="tab-btn" onclick="switchAITab('description')">📊 Database Description</button>
                    <button class="tab-btn" onclick="switchAITab('promptGenerator')">✨ Prompt Generator</button>
                </div>

                <!-- Tab 1: Connection Prompt -->
                <div id="aiConnectionTab" class="tab-content active">
                    <div style="background: rgba(255, 255, 255, 0.05); padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                            <h3 style="color: #fbbf24; margin: 0;">📝 AI Connection Prompt</h3>
                            <button class="btn btn-primary" onclick="copyAIConnectionPrompt()" id="copyAIPromptBtn" disabled>
                                <span>📋</span> Copy to Clipboard
                            </button>
                        </div>
                        
                        <div style="background: rgba(245, 158, 11, 0.1); border: 1px solid #f59e0b; border-radius: 8px; padding: 12px; margin-bottom: 15px; font-size: 13px;">
                            <strong style="color: #fbbf24;">💡 How to use:</strong> Copy this prompt and paste it into any AI code editor (Cursor, GitHub Copilot, etc.) to give the AI direct access to your database connection info.
                        </div>

                        <textarea id="aiPromptText" readonly style="width: 100%; min-height: 350px; padding: 20px; background: rgba(0, 0, 0, 0.4); border: 2px solid rgba(251, 191, 36, 0.3); border-radius: 8px; color: #86efac; font-family: 'Courier New', monospace; font-size: 14px; line-height: 1.6; resize: vertical;">Select a database from the Dashboard to generate AI connection prompt...</textarea>
                    </div>
                </div>

                <!-- Tab 2: Database Description -->
                <div id="aiDescriptionTab" class="tab-content">
                    <div style="background: rgba(255, 255, 255, 0.05); padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                            <h3 style="color: #fbbf24; margin: 0;">📊 AI Database Description</h3>
                            <div style="display: flex; gap: 10px;">
                                <button class="btn btn-secondary" onclick="generateDatabaseDescription()" id="generateDescBtn" disabled>
                                    <span>🔄</span> Generate Description
                                </button>
                                <button class="btn btn-primary" onclick="copyDatabaseDescription()" id="copyDescBtn" disabled>
                                    <span>📋</span> Copy to Clipboard
                                </button>
                            </div>
                        </div>
                        
                        <div style="background: rgba(245, 158, 11, 0.1); border: 1px solid #f59e0b; border-radius: 8px; padding: 12px; margin-bottom: 15px; font-size: 13px;">
                            <strong style="color: #fbbf24;">💡 How to use:</strong> This generates a complete description of your database structure for AI context. Include this when asking AI to work with your database schema.
                        </div>

                        <div id="descriptionLoading" style="display: none; text-align: center; padding: 40px;">
                            <div class="spinner"></div>
                            <div style="margin-top: 15px; color: rgba(254, 243, 199, 0.8);">Analyzing database structure...</div>
                        </div>

                        <textarea id="aiDescriptionText" readonly style="width: 100%; min-height: 450px; padding: 20px; background: rgba(0, 0, 0, 0.4); border: 2px solid rgba(251, 191, 36, 0.3); border-radius: 8px; color: #86efac; font-family: 'Courier New', monospace; font-size: 14px; line-height: 1.6; resize: vertical;">Click "Generate Description" button to analyze the database structure...</textarea>
                    </div>
                </div>

                <!-- Tab 3: Prompt Generator -->
                <div id="aiPromptGeneratorTab" class="tab-content">
                    <div style="background: rgba(255, 255, 255, 0.05); padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                            <h3 style="color: #a78bfa; margin: 0;">✨ AI Application Prompt Generator</h3>
                        </div>
                        
                        <div style="background: rgba(139, 92, 246, 0.1); border: 1px solid #8b5cf6; border-radius: 8px; padding: 12px; margin-bottom: 20px; font-size: 13px;">
                            <strong style="color: #a78bfa;">💡 How to use:</strong> Select database + tables, write what you want to build, then click Generate. You'll get a complete AI prompt with database credentials & table structures ready for any AI code editor!
                        </div>

                        <!-- Step 1: Select Database -->
                        <div class="form-group">
                            <label class="form-label" style="color: #a78bfa;">1️⃣ Select Database</label>
                            <select id="promptGenDatabaseSelect" class="form-select" onchange="loadPromptGenTables()" style="border-color: rgba(139, 92, 246, 0.4);">
                                <option value="">-- Select Database --</option>
                            </select>
                            <div class="helper-text">Choose the database for your application</div>
                        </div>

                        <!-- Step 2: Select Tables (will appear after database selection) -->
                        <div id="promptGenTablesSection" style="display: none; margin-top: 20px;">
                            <div style="background: linear-gradient(135deg, rgba(139, 92, 246, 0.2) 0%, rgba(167, 139, 250, 0.1) 100%); border: 2px solid rgba(139, 92, 246, 0.4); border-radius: 12px; padding: 20px; backdrop-filter: blur(10px);">
                                <!-- Header -->
                                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid rgba(139, 92, 246, 0.3);">
                                    <div>
                                        <h4 style="color: #a78bfa; margin: 0; font-size: 18px; display: flex; align-items: center; gap: 10px;">
                                            <span style="font-size: 24px;">📋</span>
                                            <span>Select Tables for Your Application</span>
                                        </h4>
                                        <p style="color: rgba(254, 243, 199, 0.7); margin: 5px 0 0 34px; font-size: 13px;" id="promptGenDbName">
                                            No database selected
                                        </p>
                                    </div>
                                    <div style="display: flex; gap: 8px;">
                                        <button onclick="refreshPromptGenTables()" class="btn btn-secondary" style="padding: 8px 12px; font-size: 12px;" title="Refresh tables">
                                            <span>🔄</span>
                                        </button>
                                        <button onclick="selectAllPromptGenTables()" class="btn btn-secondary" style="padding: 8px 12px; font-size: 12px;" title="Select all">
                                            <span>✅</span>
                                        </button>
                                        <button onclick="deselectAllPromptGenTables()" class="btn btn-secondary" style="padding: 8px 12px; font-size: 12px;" title="Deselect all">
                                            <span>❌</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Tables Grid -->
                                <div id="promptGenTablesContainer" style="display: flex; flex-wrap: wrap; gap: 10px; min-height: 200px; max-height: 400px; overflow-y: auto; padding: 5px; background: rgba(0, 0, 0, 0.2); border-radius: 8px; border: 1px solid rgba(139, 92, 246, 0.2);">
                                    <div style="width: 100%; text-align: center; padding: 40px 20px; color: rgba(254, 243, 199, 0.5);">
                                        <div style="font-size: 48px; margin-bottom: 15px;">📂</div>
                                        <p style="font-size: 14px;">Select a database to view tables</p>
                                    </div>
                                </div>

                                <!-- Selected Count -->
                                <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid rgba(139, 92, 246, 0.2); color: rgba(254, 243, 199, 0.8); font-size: 13px; display: flex; justify-content: space-between; align-items: center;">
                                    <span id="promptGenSelectedCount">0 tables selected</span>
                                    <span id="promptGenTotalCount">0 tables total</span>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Write Your Prompt -->
                        <div id="promptGenInputSection" style="display: none; margin-top: 20px;">
                            <div class="form-group">
                                <label class="form-label" style="color: #a78bfa;">3️⃣ What do you want to build?</label>
                                <textarea id="promptGenCustomInput" class="form-input" rows="6" placeholder="Example: Create a modern dashboard with CRUD operations for all selected tables, authentication system, data validation, and beautiful UI." style="border-color: rgba(139, 92, 246, 0.4); font-size: 14px; line-height: 1.6;"></textarea>
                                <div class="helper-text">Describe your application requirements, features, or any specific instructions for the AI</div>
                            </div>

                            <!-- Application Type Selection -->
                            <div class="form-group" style="background: rgba(139, 92, 246, 0.08); border: 2px solid rgba(139, 92, 246, 0.3); border-radius: 10px; padding: 20px;">
                                <label class="form-label" style="color: #a78bfa; margin-bottom: 15px;">4️⃣ Application Architecture (Optional)</label>
                                
                                <!-- Radio Option 1: Single-PHP -->
                                <div style="background: rgba(59, 130, 246, 0.1); border: 2px solid rgba(59, 130, 246, 0.3); border-radius: 10px; padding: 15px; margin-bottom: 15px; cursor: pointer; transition: all 0.3s;" onclick="selectAppType('single')" id="appType_single">
                                    <div style="display: flex; align-items: start; gap: 15px;">
                                        <input type="radio" name="appType" value="single" id="appTypeSingle" onchange="handleAppTypeChange()" style="width: 20px; height: 20px; cursor: pointer; margin-top: 3px;">
                                        <div style="flex: 1;">
                                            <h4 style="color: #60a5fa; margin: 0 0 8px 0;">📄 Single-PHP (Direct Connection)</h4>
                                            <p style="margin: 0 0 12px 0; font-size: 13px; color: rgba(254, 243, 199, 0.8);">
                                                Generate ONE PHP page with direct database connection (no API)<br>
                                                ✅ Perfect for: Simple apps, admin panels, quick prototypes
                                            </p>
                                            <!-- Filename Input (appears when selected) -->
                                            <div id="singlePhpFilenameSection" style="display: none; margin-top: 12px;">
                                                <label style="display: block; font-size: 12px; color: #93c5fd; margin-bottom: 5px;">📝 PHP Page Filename:</label>
                                                <input type="text" id="singlePhpFilename" class="form-input" placeholder="e.g., dashboard.php, app.php, index.php" value="app.php" style="background: rgba(0, 0, 0, 0.3); border-color: rgba(59, 130, 246, 0.4);" onclick="event.stopPropagation();">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Radio Option 2: Double-API -->
                                <div style="background: rgba(34, 197, 94, 0.1); border: 2px solid rgba(34, 197, 94, 0.3); border-radius: 10px; padding: 15px; cursor: pointer; transition: all 0.3s;" onclick="selectAppType('double')" id="appType_double">
                                    <div style="display: flex; align-items: start; gap: 15px;">
                                        <input type="radio" name="appType" value="double" id="appTypeDouble" onchange="handleAppTypeChange()" style="width: 20px; height: 20px; cursor: pointer; margin-top: 3px;">
                                        <div style="flex: 1;">
                                            <h4 style="color: #86efac; margin: 0 0 8px 0;">🔄 Double-API (Backend + Frontend)</h4>
                                            <p style="margin: 0 0 12px 0; font-size: 13px; color: rgba(254, 243, 199, 0.8);">
                                                Generate TWO pages: Backend PHP (API) + Frontend HTML (JavaScript)<br>
                                                ✅ Perfect for: Professional apps, scalable architecture
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="helper-text" style="margin-top: 10px;">
                                    💡 Leave unselected for generic prompt (no specific file structure)
                                </div>
                            </div>

                            <div style="display: flex; gap: 10px; margin-top: 20px;">
                                <button class="btn btn-primary" onclick="generateAIPrompt()" style="background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);">
                                    <span>✨</span> Generate AI Prompt
                                </button>
                                <button class="btn btn-secondary" onclick="clearPromptGenerator()">
                                    <span>🗑️</span> Clear All
                                </button>
                            </div>
                        </div>

                        <!-- Generated Prompt Output -->
                        <div id="promptGenOutputSection" style="display: none; margin-top: 25px;">
                            <div style="background: linear-gradient(135deg, rgba(139, 92, 246, 0.15) 0%, rgba(167, 139, 250, 0.1) 100%); border: 2px solid rgba(139, 92, 246, 0.4); border-radius: 12px; padding: 20px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                    <h4 style="color: #a78bfa; margin: 0;">🎯 Generated AI Prompt</h4>
                                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                        <!-- Update button - Shows only in Edit mode -->
                                        <button id="saveEditBtn" class="btn btn-primary" onclick="saveEditedPrompt()" style="background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%); display: none;">
                                            <span>✏️</span> Save Edit
                                        </button>
                                        <!-- Save as New - Always visible -->
                                        <button class="btn btn-primary" onclick="saveAsNewPrompt()" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                                            <span>💾</span> Save as New
                                        </button>
                                        <button class="btn btn-primary" onclick="copyGeneratedPrompt()" style="background: linear-gradient(135deg, #22c55e 0%, #15803d 100%);">
                                            <span>📋</span> Copy
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Editing indicator - Shows which prompt we're editing -->
                                <div id="editingIndicator" style="display: none; background: rgba(59, 130, 246, 0.2); border: 2px solid #3b82f6; border-radius: 8px; padding: 12px; margin-bottom: 15px; font-size: 14px;">
                                    <strong style="color: #60a5fa;">✏️ Editing:</strong> <span id="editingPromptName" style="color: #fbbf24; font-weight: bold;"></span>
                                </div>

                                <div style="background: rgba(245, 158, 11, 0.1); border: 1px solid #f59e0b; border-radius: 8px; padding: 12px; margin-bottom: 15px; font-size: 13px;">
                                    <strong style="color: #fbbf24;">✏️ Editable!</strong> You can edit the prompt below before saving or copying.
                                </div>

                                <textarea id="promptGenOutput" style="width: 100%; min-height: 500px; padding: 20px; background: rgba(0, 0, 0, 0.4); border: 2px solid rgba(139, 92, 246, 0.3); border-radius: 8px; color: #86efac; font-family: 'Courier New', monospace; font-size: 14px; line-height: 1.6; resize: vertical;"></textarea>

                                <!-- Stats -->
                                <div id="promptGenStats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; margin-top: 15px;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Info Cards -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                    <div class="stat-box">
                        <div class="stat-label">🖥️ Database Host</div>
                        <div class="stat-value" style="font-size: 14px; word-break: break-all;" id="aiPromptHost">-</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-label">📁 Database Name</div>
                        <div class="stat-value" style="font-size: 14px; word-break: break-all;" id="aiPromptDbName">-</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-label">👤 Username</div>
                        <div class="stat-value" style="font-size: 14px; word-break: break-all;" id="aiPromptUser">-</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-label">🔌 Port</div>
                        <div class="stat-value" style="font-size: 14px;" id="aiPromptPort">-</div>
                    </div>
                </div>

                        <!-- Saved Prompts Table - Directly under Generated Prompt -->
                        <div id="savedPromptsSection" style="margin-top: 30px;">
                            <div style="background: linear-gradient(135deg, rgba(139, 92, 246, 0.1) 0%, rgba(167, 139, 250, 0.05) 100%); border: 2px solid rgba(139, 92, 246, 0.3); border-radius: 12px; padding: 20px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
                                    <h4 style="color: #a78bfa; margin: 0; display: flex; align-items: center; gap: 10px;">
                                        <span style="font-size: 24px;">📚</span>
                                        <span>Saved Prompts</span>
                                    </h4>
                                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                        <button class="btn btn-primary" onclick="exportSavedPrompts()" style="background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);">
                                            <span>📤</span> Export All
                                        </button>
                                        <button class="btn btn-primary" onclick="importSavedPrompts()" style="background: linear-gradient(135deg, #22c55e 0%, #15803d 100%);">
                                            <span>📥</span> Import
                                        </button>
                                        <button class="btn btn-danger" onclick="clearAllPrompts()">
                                            <span>🗑️</span> Clear All
                                        </button>
                                    </div>
                                </div>

                                <div id="savedPromptsTable"></div>

                                <div style="margin-top: 15px; background: rgba(59, 130, 246, 0.1); border: 1px solid #3b82f6; border-radius: 8px; padding: 12px; font-size: 13px;">
                                    <strong style="color: #60a5fa;">💡 Quick Guide:</strong>
                                    <div style="color: rgba(254, 243, 199, 0.7); margin-top: 8px; line-height: 1.6;">
                                        <strong>💾 Save as New:</strong> Add new prompt to table (always creates new record)<br>
                                        <strong>✏️ Edit:</strong> Load prompt for editing (shows "Save Edit" button)<br>
                                        <strong>📋 Copy:</strong> Quick copy to clipboard<br>
                                        <strong>🗑️ Delete:</strong> Remove from table<br>
                                        <strong>📤 Export/📥 Import:</strong> Backup and restore all prompts (file picker)
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- List Tables Section - HIDDEN -->
        <section id="listTables" class="section hidden-section">
            <div id="tableConnectionStatus" class="connection-status"></div>
            
            <!-- Tables List View -->
            <div id="tablesListView" class="card">
                <h2 class="card-title">
                    <span class="card-title-icon">📄</span>
                    Tables in Database
                </h2>
                <div id="listTablesMessage"></div>
                <button class="btn btn-primary" onclick="loadTables()">
                    <span>🔄</span> Refresh Tables
                </button>
                <div id="tableList" class="table-grid"></div>
            </div>

            <!-- Table Data View -->
            <div id="tableDataView" class="card" style="display: none;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
                    <h2 class="card-title" style="margin: 0;">
                        <span class="card-title-icon">📊</span>
                        <span id="currentTableName">Table Data</span>
                    </h2>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <button class="btn btn-primary" onclick="injectRandomRecords()" style="background: linear-gradient(135deg, #22c55e 0%, #15803d 100%);">
                            <span>🎲</span> Inject 10 Random Records
                        </button>
                        <button class="btn btn-primary" onclick="generateSQL(false)">
                            <span>🏗️</span> Generate Structure
                        </button>
                        <button class="btn btn-primary" onclick="generateSQL(true)">
                            <span>📦</span> Generate Structure + Data
                        </button>
                        <button class="btn btn-secondary" onclick="backToTablesList()">
                            <span>⬅️</span> Back to Tables
                        </button>
                    </div>
                </div>
                
                <!-- Table Info Stats -->
                <div id="tableInfoStats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;"></div>
                
                <div id="tableDataMessage"></div>
                
                <!-- Pagination Controls (Top) -->
                <div id="paginationTop" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding: 15px; background: rgba(255,255,255,0.05); border-radius: 8px;"></div>
                
                <!-- Table Data -->
                <div id="tableDataContainer" style="overflow-x: auto; background: rgba(0,0,0,0.2); border-radius: 8px; padding: 10px;"></div>
                
                <!-- Pagination Controls (Bottom) -->
                <div id="paginationBottom" style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px; padding: 15px; background: rgba(255,255,255,0.05); border-radius: 8px;"></div>
            </div>
        </section>

        <!-- Create Table Section - HIDDEN -->
        <section id="createTable" class="section hidden-section">
            <!-- Selected Database Display -->
            <div style="background: rgba(34, 197, 94, 0.2); border: 1px solid #22c55e; color: #86efac; padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; font-weight: 500;">
                ✅ Creating table in: <strong id="createTableDbName" style="color: #fbbf24;">Database Name</strong>
            </div>
            
            <div class="card">
                <h2 class="card-title">
                    <span class="card-title-icon">➕</span>
                    Create New Table
                </h2>
                <div id="createTableMessage"></div>

                <!-- Tab Navigation -->
                <div class="tab-buttons">
                    <button class="tab-btn active" onclick="switchTab('manual')">Manual Definition</button>
                    <button class="tab-btn" onclick="switchTab('template')">Use Template</button>
                    <button class="tab-btn" onclick="switchTab('migration')">Migration</button>
                </div>

                <!-- Manual Tab -->
                <div id="manualTab" class="tab-content active">
                    <form onsubmit="createTableManual(event)">
                        <div class="form-group">
                            <label class="form-label">Table Name *</label>
                            <input type="text" id="newTableName" class="form-input" placeholder="Enter table name" required>
                            <div class="helper-text">Use alphanumeric characters and underscores only</div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Table Columns</label>
                            <div id="columnBuilder" class="column-builder"></div>
                            <button type="button" class="btn btn-secondary" onclick="addColumnRow()">
                                <span>➕</span> Add Column
                            </button>
                        </div>

                        <div id="sqlPreview" class="sql-preview" style="display: none;"></div>

                        <div style="display: flex; gap: 10px; margin-top: 20px;">
                            <button type="button" class="btn btn-secondary" onclick="previewSQL()">
                                <span>👁️</span> Preview SQL
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <span>➕</span> Create Table
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Template Tab -->
                <div id="templateTab" class="tab-content">
                    <form onsubmit="createTableFromTemplate(event)">
                        <div class="form-group">
                            <label class="form-label">Select Template First</label>
                            <div class="template-grid" id="templateGrid"></div>
                        </div>

                        <div class="form-group" id="templateNameSection" style="display: none;">
                            <label class="form-label">Table Name *</label>
                            <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                                <select id="templateSuggestedNames" class="form-select" onchange="useSuggestedName()" style="flex: 1;">
                                    <option value="">-- Choose Suggested Name --</option>
                                </select>
                                <button type="button" class="btn btn-secondary" onclick="clearTemplateName()" style="padding: 8px 16px;">
                                    <span>✖️</span> Clear
                                </button>
                            </div>
                            <input type="text" id="templateTableName" class="form-input" placeholder="Or type custom name..." required>
                            <div class="helper-text">💡 Select a suggested name above or type your own</div>
                        </div>

                        <div id="templateColumnBuilder" class="column-builder" style="display: none; margin-top: 20px;">
                            <h4 style="color: #fbbf24; margin-bottom: 15px;">Template Columns (You can modify)</h4>
                            <div id="templateColumns"></div>
                        </div>

                        <div id="templateSqlPreview" class="sql-preview" style="display: none;"></div>

                        <div style="display: flex; gap: 10px; margin-top: 20px;">
                            <button type="button" class="btn btn-secondary" onclick="previewTemplateSQL()">
                                <span>👁️</span> Preview SQL
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <span>➕</span> Create Table from Template
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Migration Tab -->
                <div id="migrationTab" class="tab-content">
                    <div style="display: flex; gap: 20px; align-items: flex-start;">
                        <!-- Source Tables Container (Left Side - 48%) -->
                        <div style="flex: 0 0 48%; background: linear-gradient(135deg, rgba(30, 58, 138, 0.3) 0%, rgba(153, 27, 27, 0.3) 100%); border: 2px solid rgba(251, 191, 36, 0.3); border-radius: 12px; padding: 20px; backdrop-filter: blur(10px);">
                            <!-- Container Header -->
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid rgba(251, 191, 36, 0.3);">
                                <div>
                                    <h3 style="color: #fbbf24; margin: 0; font-size: 18px; display: flex; align-items: center; gap: 10px;">
                                        <span style="font-size: 24px;">📦</span>
                                        <span>Source Tables</span>
                                    </h3>
                                    <p style="color: rgba(254, 243, 199, 0.7); margin: 5px 0 0 34px; font-size: 13px;" id="migrationSourceDbName">
                                        No database selected
                                    </p>
                                </div>
                                <div style="display: flex; gap: 8px;">
                                    <button onclick="showDatabaseInfo()" class="btn btn-primary" style="padding: 8px 12px; font-size: 12px; background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);" title="Show database credentials and selected tables info">
                                        <span>🗄️</span> DB-Info
                                    </button>
                                    <button onclick="refreshMigrationTables()" class="btn btn-secondary" style="padding: 8px 12px; font-size: 12px;" title="Refresh tables">
                                        <span>🔄</span>
                                    </button>
                                    <button onclick="selectAllMigrationTables()" class="btn btn-secondary" style="padding: 8px 12px; font-size: 12px;" title="Select all">
                                        <span>✅</span>
                                    </button>
                                    <button onclick="deselectAllMigrationTables()" class="btn btn-secondary" style="padding: 8px 12px; font-size: 12px;" title="Deselect all">
                                        <span>❌</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Tables Grid - NOW ALSO A DROP ZONE (for reverse drag from Destination) -->
                            <div id="migrationTablesContainer" 
                                 class="migration-drop-zone"
                                 ondrop="handleSourceDrop(event)"
                                 ondragover="handleSourceDragOver(event)"
                                 ondragleave="handleSourceDragLeave(event)"
                                 style="display: flex; flex-wrap: wrap; gap: 10px; min-height: 200px; max-height: 500px; overflow-y: auto; padding: 5px; transition: all 0.3s;">
                                <!-- Tables will be loaded here dynamically -->
                                <div style="width: 100%; text-align: center; padding: 40px 20px; color: rgba(254, 243, 199, 0.5);">
                                    <div style="font-size: 48px; margin-bottom: 15px;">�</div>
                                    <p style="font-size: 14px;">Select a database to view tables</p>
                                </div>
                            </div>

                            <!-- Selected Count -->
                            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid rgba(251, 191, 36, 0.2); color: rgba(254, 243, 199, 0.8); font-size: 13px; display: flex; justify-content: space-between; align-items: center;">
                                <span id="migrationSelectedCount">0 tables selected</span>
                                <span id="migrationTotalCount">0 tables total</span>
                            </div>
                        </div>

                        <!-- Arrow Container (Center - Two-Way System) -->
                        <div style="flex: 0 0 60px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 15px;">
                            <div style="font-size: 36px; animation: pulse 2s infinite;">➡️</div>
                            <div style="width: 2px; height: 80px; background: linear-gradient(to bottom, rgba(251, 191, 36, 0.5), rgba(34, 197, 94, 0.5));"></div>
                            <div style="font-size: 36px; animation: pulse 2s infinite; animation-delay: 1s;">⬅️</div>
                        </div>

                        <!-- Destination Database Container (Right Side - 48%) -->
                        <div style="flex: 0 0 48%; background: linear-gradient(135deg, rgba(34, 197, 94, 0.2) 0%, rgba(59, 130, 246, 0.2) 100%); border: 2px solid rgba(34, 197, 94, 0.4); border-radius: 12px; padding: 20px; backdrop-filter: blur(10px);">
                            <!-- Container Header -->
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid rgba(34, 197, 94, 0.3);">
                                <div>
                                    <h3 style="color: #86efac; margin: 0; font-size: 18px; display: flex; align-items: center; gap: 10px;">
                                        <span style="font-size: 24px;">📥</span>
                                        <span>Destination Database</span>
                                    </h3>
                                    <p style="color: rgba(254, 243, 199, 0.7); margin: 5px 0 0 34px; font-size: 13px;">
                                        Select where to copy tables
                                    </p>
                                </div>
                                <div style="display: flex; gap: 8px;">
                                    <button onclick="showDestinationDatabaseInfo()" class="btn btn-primary" style="padding: 8px 12px; font-size: 12px; background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);" title="Show database credentials and selected tables info">
                                        <span>🗄️</span> DB-Info
                                    </button>
                                    <button onclick="refreshDestinationTables()" class="btn btn-secondary" style="padding: 8px 12px; font-size: 12px;" title="Refresh tables">
                                        <span>🔄</span>
                                    </button>
                                    <button onclick="selectAllDestinationTables()" class="btn btn-secondary" style="padding: 8px 12px; font-size: 12px;" title="Select all">
                                        <span>✅</span>
                                    </button>
                                    <button onclick="deselectAllDestinationTables()" class="btn btn-secondary" style="padding: 8px 12px; font-size: 12px;" title="Deselect all">
                                        <span>❌</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Database Selection Dropdown -->
                            <div style="margin-bottom: 20px;">
                                <label style="display: block; color: #fbbf24; font-size: 14px; font-weight: 500; margin-bottom: 10px;">
                                    🎯 Select Destination Database
                                </label>
                                <select id="migrationDestinationSelect" 
                                        onchange="loadDestinationTables()"
                                        style="width: 100%; padding: 12px 15px; background: linear-gradient(135deg, rgba(0, 0, 0, 0.4) 0%, rgba(0, 0, 0, 0.2) 100%); border: 2px solid rgba(34, 197, 94, 0.4); border-radius: 8px; color: #fef3c7; font-size: 14px; cursor: pointer; outline: none; transition: all 0.3s;">
                                    <option value="">-- Select Destination --</option>
                                </select>
                            </div>

                            <!-- Destination Info Display -->
                            <div id="migrationDestinationInfo" style="background: rgba(59, 130, 246, 0.15); border: 1px solid rgba(59, 130, 246, 0.4); border-radius: 8px; padding: 12px 15px; margin-bottom: 20px; display: none;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <span style="font-size: 20px;">🌐</span>
                                    <div style="flex: 1;">
                                        <div style="color: #93c5fd; font-size: 13px; font-weight: 500;" id="migrationDestDbName">Database Name</div>
                                        <div style="color: rgba(254, 243, 199, 0.6); font-size: 11px;" id="migrationDestHost">Host</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Destination Tables Container -->
                            <div id="migrationDestinationTablesContainer" 
                                 class="migration-drop-zone"
                                 ondrop="handleDrop(event)"
                                 ondragover="handleDragOver(event)"
                                 ondragleave="handleDragLeave(event)"
                                 style="display: flex; flex-wrap: wrap; gap: 10px; min-height: 200px; max-height: 340px; overflow-y: auto; padding: 5px; background: rgba(0, 0, 0, 0.2); border-radius: 8px; border: 1px solid rgba(34, 197, 94, 0.2); transition: all 0.3s;">
                                <!-- Destination tables will be loaded here -->
                                <div style="width: 100%; text-align: center; padding: 40px 20px; color: rgba(254, 243, 199, 0.5);">
                                    <div style="font-size: 48px; margin-bottom: 15px;">🎯</div>
                                    <p style="font-size: 14px;">Select a destination database</p>
                                </div>
                            </div>

                            <!-- Destination Tables Count -->
                            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid rgba(34, 197, 94, 0.2); color: rgba(254, 243, 199, 0.8); font-size: 13px; display: flex; justify-content: space-between; align-items: center;">
                                <span id="migrationDestinationSelectedCount">0 tables selected</span>
                                <span id="migrationDestinationCount">0 tables total</span>
                            </div>

                            <!-- Drag & Drop Instruction - TWO-WAY SYSTEM -->
                            <div style="margin-top: 20px; padding-top: 20px; border-top: 2px solid rgba(34, 197, 94, 0.3); color: rgba(254, 243, 199, 0.7); font-size: 13px;">
                                <div style="text-align: center; margin-bottom: 15px;">
                                    <div style="font-size: 32px; margin-bottom: 10px;">⬅️ 🔄 ➡️</div>
                                    <p style="margin: 5px 0; font-weight: bold; background: linear-gradient(135deg, #fbbf24 0%, #22c55e 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-size: 16px;">TWO-WAY MIGRATION SYSTEM</p>
                                    <p style="font-size: 12px; margin-top: 5px;">Drag Source ➡️ Destination OR Destination ⬅️ Source</p>
                                </div>
                                <div style="background: rgba(245, 158, 11, 0.1); border: 1px solid #f59e0b; border-radius: 6px; padding: 10px; font-size: 12px;">
                                    <div style="margin-bottom: 12px; background: rgba(251, 191, 36, 0.1); padding: 8px; border-radius: 6px; border-left: 3px solid #fbbf24;">
                                        <strong style="color: #fbbf24;">📤 SOURCE → DESTINATION (Left to Right):</strong>
                                    </div>
                                    <div style="margin-bottom: 8px; margin-left: 10px;">
                                        <strong style="color: #fbbf24;">🏗️ Drag yellow badge:</strong> Move (Structure Only)
                                    </div>
                                    <div style="margin-bottom: 12px; margin-left: 10px;">
                                        <strong style="color: #22c55e;">✋ Drag green badge:</strong> Move (Structure + Data)
                                    </div>
                                    
                                    <div style="margin-bottom: 12px; background: rgba(34, 197, 94, 0.1); padding: 8px; border-radius: 6px; border-left: 3px solid #22c55e;">
                                        <strong style="color: #86efac;">📥 DESTINATION → SOURCE (Right to Left):</strong>
                                    </div>
                                    <div style="margin-bottom: 8px; margin-left: 10px;">
                                        <strong style="color: #22c55e;">🏗️ Drag green badge:</strong> Move (Structure Only)
                                    </div>
                                    <div style="margin-bottom: 12px; margin-left: 10px;">
                                        <strong style="color: #10b981;">✋ Drag green badge:</strong> Move (Structure + Data)
                                    </div>
                                    
                                    <div style="border-top: 1px dashed rgba(251, 191, 36, 0.3); margin: 12px 0; padding-top: 12px;">
                                        <div style="font-weight: bold; color: #fbbf24; margin-bottom: 8px;">⚙️ COMMON OPERATIONS (Both Sides):</div>
                                    </div>
                                    <div style="margin-bottom: 8px;">
                                        <strong style="color: #3b82f6;">🎲 Blue "Dice" button:</strong> Inject 10 random records
                                    </div>
                                    <div style="margin-bottom: 8px;">
                                        <strong style="color: #a78bfa;">📋 Purple "Copy" button:</strong> Duplicate table in same DB
                                    </div>
                                    <div style="margin-bottom: 8px;">
                                        <strong style="color: #f59e0b;">🧹 Orange "Empty" button:</strong> Delete all data (keep structure)
                                    </div>
                                    <div style="margin-bottom: 8px;">
                                        <strong style="color: #ef4444;">🗑️ Red "Delete" button:</strong> Permanently delete table
                                    </div>
                                    <div style="margin-bottom: 8px;">
                                        <strong style="color: #8b5cf6;">🗄️ Purple "DB-Info" button (header):</strong> View database credentials & connection strings
                                    </div>
                                    <div style="margin-bottom: 8px;">
                                        <strong style="color: #fbbf24;">🖱️ Right-click name:</strong> Rename inline (Enter to save, Esc to cancel)
                                    </div>
                                    <div style="margin-bottom: 8px; background: rgba(34, 197, 94, 0.1); padding: 8px; border-radius: 6px; border: 1px solid rgba(34, 197, 94, 0.3);">
                                        <strong style="color: #86efac;">✨ Multi-Selection:</strong> Click multiple tables → Drag any one → All migrate together!
                                    </div>
                                    <div style="font-size: 11px; opacity: 0.8; margin-top: 8px; padding-top: 8px; border-top: 1px solid rgba(245, 158, 11, 0.2);">
                                        💡 Both containers support ALL operations • Full bi-directional migration
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Edit Table Section - HIDDEN -->
        <section id="editTable" class="section hidden-section">
            <!-- Selected Database Display -->
            <div style="background: rgba(34, 197, 94, 0.2); border: 1px solid #22c55e; color: #86efac; padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; font-weight: 500;">
                ✅ Editing tables in: <strong id="editTableDbName" style="color: #fbbf24;">Database Name</strong>
            </div>

            <div class="card">
                <h2 class="card-title">
                    <span class="card-title-icon">✏️</span>
                    Edit Table
                </h2>
                <div id="editTableMessage"></div>
                
                <div class="form-group">
                    <label class="form-label">Select Table *</label>
                    <select id="editTableSelect" class="form-select" onchange="loadTableForEditing()" required>
                        <option value="">-- Select Table --</option>
                    </select>
                    <div class="helper-text" id="editTableHelper">💡 Select a database from Dashboard to see available tables</div>
                </div>

                <!-- Tabs for Structure / Manage Data / Table Info -->
                <div id="editTableTabs" style="display: none;">
                    <div style="background: rgba(59, 130, 246, 0.1); padding: 12px 15px; border-radius: 8px; border: 1px solid rgba(59, 130, 246, 0.3); margin-bottom: 15px;">
                        <div style="font-size: 13px; color: rgba(254, 243, 199, 0.9);">
                            <strong>💡 Three Modes:</strong> <strong style="color: #fbbf24;">Structure</strong> to modify columns | <strong style="color: #22c55e;">Manage Data</strong> to CRUD records | <strong style="color: #3b82f6;">Table Info</strong> for AI context
                        </div>
                    </div>
                    <div class="tab-buttons" style="margin: 20px 0;">
                        <button class="tab-btn active" onclick="switchEditTab('structure')">🏗️ Table Structure</button>
                        <button class="tab-btn" onclick="switchEditTab('manageData')">📝 Manage Data (CRUD)</button>
                        <button class="tab-btn" onclick="switchEditTab('tableInfo')">🤖 Table Info for AI</button>
                    </div>

                    <!-- Structure Tab -->
                    <div id="editStructureTab" class="tab-content active">
                        <h3 style="color: #fbbf24; margin: 20px 0 10px;">Current Columns</h3>
                        <div id="currentColumns"></div>

                        <h3 style="color: #fbbf24; margin: 20px 0 10px;">Add New Column</h3>
                        <div class="column-builder">
                            <div class="column-row">
                                <div class="form-group" style="margin: 0;">
                                    <input type="text" id="newColName" class="form-input" placeholder="Column name">
                                </div>
                                <div class="form-group" style="margin: 0;">
                                    <select id="newColType" class="form-select">
                                        <option value="INT">INT</option>
                                        <option value="VARCHAR">VARCHAR</option>
                                        <option value="TEXT">TEXT</option>
                                        <option value="DATE">DATE</option>
                                        <option value="DATETIME">DATETIME</option>
                                        <option value="TIMESTAMP">TIMESTAMP</option>
                                        <option value="BOOLEAN">BOOLEAN</option>
                                        <option value="DECIMAL">DECIMAL</option>
                                        <option value="FLOAT">FLOAT</option>
                                    </select>
                                </div>
                                <div class="form-group" style="margin: 0;">
                                    <input type="text" id="newColLength" class="form-input" placeholder="Length">
                                </div>
                                <div class="form-group" style="margin: 0;">
                                    <select id="newColNullable" class="form-select">
                                        <option value="yes">NULL</option>
                                        <option value="no">NOT NULL</option>
                                    </select>
                                </div>
                                <div class="form-group" style="margin: 0;">
                                    <input type="text" id="newColDefault" class="form-input" placeholder="Default">
                                </div>
                                <button type="button" class="btn btn-primary btn-icon" onclick="addColumn()">+</button>
                            </div>
                        </div>
                    </div>

                    <!-- Manage Data Tab -->
                    <div id="editDataTab" class="tab-content">
                        <!-- Action Bar -->
                        <div style="background: rgba(255, 255, 255, 0.05); padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                            <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                                <input type="text" id="dataSearchInput" class="form-input" placeholder="🔍 Type to search instantly..." style="flex: 1; min-width: 250px;" oninput="searchTableRecordsInstant()">
                                <button class="btn btn-secondary" onclick="clearSearch()">
                                    <span>✖️</span> Clear
                                </button>
                                <button class="btn btn-primary" onclick="injectRandomRecordsFromEdit()" style="background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);">
                                    <span>🎲</span> Inject 10 Random
                                </button>
                                <button class="btn btn-primary" onclick="showRecordForm('add')" style="background: linear-gradient(135deg, #22c55e 0%, #15803d 100%);">
                                    <span>➕</span> Add New Record
                                </button>
                            </div>
                            <div id="searchStatus" style="margin-top: 10px; font-size: 13px; color: rgba(254, 243, 199, 0.7);"></div>
                        </div>

                        <!-- Data Container -->
                        <div id="dataManagementContainer"></div>

                        <!-- Pagination for data -->
                        <div id="dataPagination" style="margin-top: 20px;"></div>
                    </div>

                    <!-- Table Info for AI Tab -->
                    <div id="editTableInfoTab" class="tab-content">
                        <div style="background: rgba(255, 255, 255, 0.05); padding: 20px; border-radius: 10px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                <h3 style="color: #fbbf24; margin: 0;">🤖 Table Information for AI</h3>
                                <div style="display: flex; gap: 10px;">
                                    <button class="btn btn-secondary" onclick="generateTableInfo()">
                                        <span>🔄</span> Refresh Info
                                    </button>
                                    <button class="btn btn-primary" onclick="copyTableInfo()">
                                        <span>📋</span> Copy to Clipboard
                                    </button>
                                </div>
                            </div>
                            
                            <div style="background: rgba(245, 158, 11, 0.1); border: 1px solid #f59e0b; border-radius: 8px; padding: 12px; margin-bottom: 15px; font-size: 13px;">
                                <strong style="color: #fbbf24;">💡 How to use:</strong> This generates a detailed description of this specific table for AI context. Perfect for asking AI to work with this table's data.
                            </div>

                            <div id="tableInfoLoading" style="display: none; text-align: center; padding: 40px;">
                                <div class="spinner"></div>
                                <div style="margin-top: 15px; color: rgba(254, 243, 199, 0.8);">Analyzing table structure...</div>
                            </div>

                            <textarea id="tableInfoText" readonly style="width: 100%; min-height: 450px; padding: 20px; background: rgba(0, 0, 0, 0.4); border: 2px solid rgba(251, 191, 36, 0.3); border-radius: 8px; color: #86efac; font-family: 'Courier New', monospace; font-size: 14px; line-height: 1.6; resize: vertical;">Table information will be generated automatically when you select a table...</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Delete Table Section - HIDDEN -->
        <section id="deleteTable" class="section hidden-section">
            <!-- Selected Database Display -->
            <div style="background: rgba(34, 197, 94, 0.2); border: 1px solid #22c55e; color: #86efac; padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; font-weight: 500;">
                ✅ Deleting tables from: <strong id="deleteTableDbName" style="color: #fbbf24;">Database Name</strong>
            </div>

            <div class="card">
                <h2 class="card-title">
                    <span class="card-title-icon">🗑️</span>
                    Delete Table
                </h2>
                <div id="deleteTableMessage"></div>
                <form onsubmit="deleteTableAction(event)">
                    <div class="form-group">
                        <label class="form-label">Select Table to Delete *</label>
                        <select id="deleteTableSelect" class="form-select" required>
                            <option value="">-- Select Table --</option>
                        </select>
                        <div class="helper-text">⚠️ This action cannot be undone!</div>
                    </div>
                    <button type="submit" class="btn btn-danger">
                        <span>🗑️</span> Delete Table
                    </button>
                </form>
            </div>
        </section>

        <!-- Rename Table Section - HIDDEN -->
        <section id="renameTable" class="section hidden-section">
            <!-- Selected Database Display -->
            <div style="background: rgba(34, 197, 94, 0.2); border: 1px solid #22c55e; color: #86efac; padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; font-weight: 500;">
                ✅ Renaming tables in: <strong id="renameTableDbName" style="color: #fbbf24;">Database Name</strong>
            </div>

            <div class="card">
                <h2 class="card-title">
                    <span class="card-title-icon">✏️</span>
                    Rename Table
                </h2>
                <div id="renameTableMessage"></div>
                <form onsubmit="renameTableAction(event)">
                    <div class="form-group">
                        <label class="form-label">Select Table to Rename *</label>
                        <select id="renameTableOldSelect" class="form-select" required>
                            <option value="">-- Select Table --</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">New Table Name *</label>
                        <input type="text" id="renameTableNewName" class="form-input" placeholder="Enter new name" required>
                        <div class="helper-text">Use alphanumeric characters and underscores only</div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <span>✏️</span> Rename Table
                    </button>
                </form>
            </div>
        </section>
    </main>

    <!-- Connection Modal -->
    <div id="connectionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Database Connection</h3>
                <button class="modal-close" onclick="closeConnectionModal()">×</button>
            </div>
            <div class="modal-body">
                <div id="modalMessage"></div>
                <div class="form-group">
                    <label class="form-label">Database Username</label>
                    <div class="datalist-wrapper">
                        <input type="text" id="modalUsername" class="form-input" placeholder="Enter username" list="savedUsernames">
                        <datalist id="savedUsernames"></datalist>
                    </div>
                    <div class="helper-text">Select a previously saved username or enter a new one</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Database Password</label>
                    <input type="password" id="modalPassword" class="form-input" placeholder="Enter password">
                </div>
                <div class="helper-text">✅ Credentials will be saved locally for future use</div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeConnectionModal()">Cancel</button>
                <button class="btn btn-primary" onclick="submitConnection()">
                    <span>🔌</span> Connect
                </button>
            </div>
        </div>
    </div>

    <!-- Database Info Modal (Separate from Table Info) -->
    <div id="databaseInfoModal" class="modal">
        <div class="modal-content" style="max-width: 800px; max-height: 90vh; display: flex; flex-direction: column;">
            <div class="modal-header">
                <h3 class="modal-title">
                    <span>🗄️</span> Database Connection Information - <span id="databaseInfoDbName"></span>
                </h3>
                <button class="modal-close" onclick="closeDatabaseInfoModal()">×</button>
            </div>
            <div class="modal-body" style="flex: 1; display: flex; flex-direction: column; overflow: auto;">
                <div style="background: rgba(139, 92, 246, 0.1); border: 1px solid #8b5cf6; border-radius: 8px; padding: 12px; margin-bottom: 15px; font-size: 13px;">
                    <strong style="color: #a78bfa;">💡 How to Use:</strong> Copy this information to use it for connecting to the database from external applications (PHP, Node.js, Python, etc.)
                </div>
                
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <div style="color: #fbbf24; font-size: 14px; font-weight: bold;">Complete Database Connection Information:</div>
                    <button class="btn btn-primary" onclick="copyDatabaseInfoText()" style="padding: 8px 16px;">
                        <span>📋</span> Copy to Clipboard
                    </button>
                </div>
                
                <textarea id="databaseInfoText" readonly style="width: 100%; height: 450px; padding: 15px; background: rgba(0, 0, 0, 0.4); border: 2px solid rgba(139, 92, 246, 0.3); border-radius: 8px; color: #86efac; font-family: 'Courier New', monospace; font-size: 13px; line-height: 1.5; resize: vertical; overflow-y: auto;">Loading...</textarea>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeDatabaseInfoModal()">Close</button>
            </div>
        </div>
    </div>

    <!-- Record Form Modal -->
    <div id="recordFormModal" class="modal">
        <div class="modal-content" style="max-width: 700px; max-height: 90vh; overflow-y: auto;">
            <div class="modal-header">
                <h3 class="modal-title" id="recordFormTitle">Add New Record</h3>
                <button class="modal-close" onclick="closeRecordForm()">×</button>
            </div>
            <div class="modal-body">
                <div id="recordFormMessage"></div>
                <form id="recordForm" onsubmit="saveRecord(event)">
                    <div id="recordFormFields"></div>
                    <div class="modal-footer" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid rgba(251, 191, 36, 0.2);">
                        <button type="button" class="btn btn-secondary" onclick="closeRecordForm()">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <span id="recordFormSaveIcon">💾</span> <span id="recordFormSaveText">Save</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- SQL Generator Modal -->
    <div id="sqlModal" class="modal">
        <div class="modal-content" style="max-width: 900px; max-height: 90vh;">
            <div class="modal-header">
                <h3 class="modal-title">
                    <span>📝</span> Generated SQL for <span id="sqlTableName"></span>
                </h3>
                <button class="modal-close" onclick="closeSQLModal()">×</button>
            </div>
            <div class="modal-body" style="max-height: 70vh; overflow: hidden; display: flex; flex-direction: column;">
                <div id="sqlMessage" style="margin-bottom: 15px;"></div>
                
                <!-- SQL Info -->
                <div id="sqlInfo" style="display: flex; gap: 15px; margin-bottom: 15px; flex-wrap: wrap;"></div>
                
                <!-- SQL Code Container -->
                <div style="position: relative; flex: 1; display: flex; flex-direction: column; overflow: hidden;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <div style="color: rgba(254, 243, 199, 0.8); font-size: 13px;">💡 Copy the SQL below and run it in any MySQL database</div>
                        <button class="btn btn-primary" onclick="copySQLToClipboard()" style="padding: 8px 16px;">
                            <span>📋</span> Copy to Clipboard
                        </button>
                    </div>
                    <textarea id="sqlCode" readonly style="width: 100%; flex: 1; min-height: 400px; padding: 15px; background: rgba(0, 0, 0, 0.4); border: 1px solid rgba(251, 191, 36, 0.3); border-radius: 8px; color: #86efac; font-family: 'Courier New', monospace; font-size: 13px; resize: vertical; line-height: 1.5;"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeSQLModal()">Close</button>
                <button class="btn btn-primary" onclick="downloadSQL()">
                    <span>💾</span> Download SQL File
                </button>
            </div>
        </div>
    </div>

    <!-- Credentials Modal -->
    <div id="credentialsModal" class="modal">
        <div class="modal-content" style="max-width: 650px;">
            <div class="modal-header" style="background: linear-gradient(135deg, rgba(139, 92, 246, 0.3) 0%, rgba(109, 40, 217, 0.3) 100%); margin: -30px -30px 20px -30px; padding: 25px 30px; border-radius: 12px 12px 0 0; border-bottom: 2px solid rgba(139, 92, 246, 0.4);">
                <h3 class="modal-title" style="color: #c4b5fd; display: flex; align-items: center; gap: 12px;">
                    <span style="font-size: 28px;">🔑</span>
                    <span>Database Credentials</span>
                </h3>
                <button class="modal-close" onclick="closeCredentialsModal()" style="color: #c4b5fd;">×</button>
            </div>
            <div class="modal-body">
                <!-- Database Name Header -->
                <div id="credentialsDbHeader" style="text-align: center; margin-bottom: 25px; padding: 15px; background: linear-gradient(135deg, rgba(139, 92, 246, 0.15) 0%, rgba(109, 40, 217, 0.1) 100%); border-radius: 10px; border: 1px solid rgba(139, 92, 246, 0.3);">
                    <div style="font-size: 14px; color: rgba(196, 181, 253, 0.8); margin-bottom: 5px;">Connected Database</div>
                    <div id="credentialsDbName" style="font-size: 22px; font-weight: bold; color: #a78bfa;"></div>
                </div>
                
                <!-- Credentials Grid -->
                <div style="display: grid; gap: 12px; margin-bottom: 20px;">
                    <!-- Host -->
                    <div class="credential-item" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(139, 92, 246, 0.2); border-radius: 10px; padding: 15px; display: flex; align-items: center; gap: 15px;">
                        <div style="width: 45px; height: 45px; background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px;">🖥️</div>
                        <div style="flex: 1;">
                            <div style="font-size: 11px; color: rgba(254, 243, 199, 0.6); text-transform: uppercase; letter-spacing: 1px;">Host</div>
                            <div id="credHost" style="font-size: 16px; color: #fef3c7; font-family: monospace; word-break: break-all;"></div>
                        </div>
                        <button class="btn btn-secondary" onclick="copyCredentialField('credHost')" style="padding: 8px 12px; font-size: 12px;">📋</button>
                    </div>
                    
                    <!-- Database Name -->
                    <div class="credential-item" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(139, 92, 246, 0.2); border-radius: 10px; padding: 15px; display: flex; align-items: center; gap: 15px;">
                        <div style="width: 45px; height: 45px; background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px;">📁</div>
                        <div style="flex: 1;">
                            <div style="font-size: 11px; color: rgba(254, 243, 199, 0.6); text-transform: uppercase; letter-spacing: 1px;">Database Name</div>
                            <div id="credDbName" style="font-size: 16px; color: #fef3c7; font-family: monospace; word-break: break-all;"></div>
                        </div>
                        <button class="btn btn-secondary" onclick="copyCredentialField('credDbName')" style="padding: 8px 12px; font-size: 12px;">📋</button>
                    </div>
                    
                    <!-- Username -->
                    <div class="credential-item" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(139, 92, 246, 0.2); border-radius: 10px; padding: 15px; display: flex; align-items: center; gap: 15px;">
                        <div style="width: 45px; height: 45px; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px;">👤</div>
                        <div style="flex: 1;">
                            <div style="font-size: 11px; color: rgba(254, 243, 199, 0.6); text-transform: uppercase; letter-spacing: 1px;">Username</div>
                            <div id="credUsername" style="font-size: 16px; color: #fef3c7; font-family: monospace; word-break: break-all;"></div>
                        </div>
                        <button class="btn btn-secondary" onclick="copyCredentialField('credUsername')" style="padding: 8px 12px; font-size: 12px;">📋</button>
                    </div>
                    
                    <!-- Password -->
                    <div class="credential-item" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(139, 92, 246, 0.2); border-radius: 10px; padding: 15px; display: flex; align-items: center; gap: 15px;">
                        <div style="width: 45px; height: 45px; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px;">🔒</div>
                        <div style="flex: 1;">
                            <div style="font-size: 11px; color: rgba(254, 243, 199, 0.6); text-transform: uppercase; letter-spacing: 1px;">Password</div>
                            <div id="credPassword" style="font-size: 16px; color: #fef3c7; font-family: monospace; word-break: break-all;"></div>
                        </div>
                        <button class="btn btn-secondary" onclick="copyCredentialField('credPassword')" style="padding: 8px 12px; font-size: 12px;">📋</button>
                    </div>
                    
                    <!-- Port -->
                    <div class="credential-item" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(139, 92, 246, 0.2); border-radius: 10px; padding: 15px; display: flex; align-items: center; gap: 15px;">
                        <div style="width: 45px; height: 45px; background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px;">🔌</div>
                        <div style="flex: 1;">
                            <div style="font-size: 11px; color: rgba(254, 243, 199, 0.6); text-transform: uppercase; letter-spacing: 1px;">Port</div>
                            <div id="credPort" style="font-size: 16px; color: #fef3c7; font-family: monospace;"></div>
                        </div>
                        <button class="btn btn-secondary" onclick="copyCredentialField('credPort')" style="padding: 8px 12px; font-size: 12px;">📋</button>
                    </div>
                </div>
                
                <!-- PHP Code Section -->
                <div style="background: rgba(0, 0, 0, 0.3); border: 1px solid rgba(139, 92, 246, 0.3); border-radius: 10px; overflow: hidden;">
                    <div style="background: linear-gradient(135deg, rgba(139, 92, 246, 0.2) 0%, rgba(109, 40, 217, 0.15) 100%); padding: 12px 15px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(139, 92, 246, 0.2);">
                        <span style="color: #c4b5fd; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                            <span style="font-size: 18px;">🐘</span> PHP Connection Code (PDO)
                        </span>
                        <button class="btn btn-primary" onclick="copyPHPCode()" style="padding: 6px 14px; font-size: 12px; background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);">
                            <span>📋</span> Copy Code
                        </button>
                    </div>
                    <pre id="credPhpCode" style="margin: 0; padding: 15px; color: #86efac; font-family: 'Courier New', monospace; font-size: 12px; line-height: 1.6; overflow-x: auto; white-space: pre-wrap; word-break: break-all;"></pre>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid rgba(139, 92, 246, 0.2); padding-top: 20px; margin-top: 20px;">
                <button class="btn btn-secondary" onclick="closeCredentialsModal()">Close</button>
                <button class="btn btn-primary" onclick="copyAllCredentials()" style="background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);">
                    <span>📋</span> Copy All Credentials
                </button>
            </div>
        </div>
    </div>

    <script>
        // ============================================================================
        // [SECTION 4] JAVASCRIPT - CLIENT-SIDE LOGIC
        // ============================================================================
        // This section contains all client-side JavaScript for the dashboard
        // API requests are sent to THIS SAME FILE (PHP-Dashboard.php)
        // The PHP Backend at the top of this file handles all 'action' requests
        // ============================================================================
        
        console.log('🚀 PHP-Dashboard.php - JavaScript Loading Started...');
        console.log('📄 ALL-IN-ONE MODE: PHP Backend + HTML Frontend in single file');
        
        // ==========================================
        // [SECTION 4.0] AUTO-DETECTION SYSTEM
        // ==========================================
        
        /**
         * 🎯 SMART AUTO-DETECTION (ALL-IN-ONE MODE):
         * - Detects current hostname automatically
         * - Uses THIS SAME PAGE as API endpoint
         * - All requests sent to PHP-Dashboard.php with 'action' parameter
         */
        
        // Get current page location
        const CURRENT_HOSTNAME = window.location.hostname;
        const CURRENT_PROTOCOL = window.location.protocol; // http: or https:
        const IS_LOCALHOST = CURRENT_HOSTNAME === 'localhost' || CURRENT_HOSTNAME === '127.0.0.1';
        
        console.log('🌐 Auto-Detection System:', {
            hostname: CURRENT_HOSTNAME,
            protocol: CURRENT_PROTOCOL,
            isLocalhost: IS_LOCALHOST
        });
        
        // ============================================================================
        // [SECTION 4.1] API URL CONFIGURATION - SAME PAGE (ALL-IN-ONE)
        // ============================================================================
        // This dashboard uses ITSELF as the API endpoint (PHP-Dashboard.php)
        // All API requests go to the same page with 'action' parameter
        // ============================================================================
        
        // Get current page URL (this file: PHP-Dashboard.php)
        const CURRENT_PAGE_URL = window.location.href.split('?')[0]; // Remove query params
        
        // ✅ ALL-IN-ONE MODE: All API requests go to THIS SAME FILE
        // The PHP backend handles connections to BOTH localhost AND remote databases
        // No need for separate API URLs - everything runs from one place!
        const LOCALHOST_API_URL = CURRENT_PAGE_URL;
        
        // For Hostinger databases: Also use CURRENT_PAGE_URL (same file handles everything)
        // PHP can connect to remote MySQL databases directly using the provided credentials
        const AUTO_DETECTED_HOSTINGER_URL = CURRENT_PAGE_URL;
        
        console.log('✅ ALL-IN-ONE API Mode: All requests go to:', CURRENT_PAGE_URL);
        console.log('📄 Current Page URL:', CURRENT_PAGE_URL);
        console.log('🖥️ IS_LOCALHOST:', IS_LOCALHOST);
        
        // Hostinger API URL (DYNAMIC - User can override)
        const HOSTINGER_API_URLS_KEY = 'hostinger_api_urls'; // Store all saved URLs
        const CURRENT_HOSTINGER_API_KEY = 'current_hostinger_api_url'; // Current selected URL
        
        // Fixed API URLs - Now includes PHP-Dashboard.php (this file)
        const FIXED_API_URLS = [
            'http://localhost/PHP-Dashboard.php',
            'https://brainstrack.com/PHP-Dashboard.php',
            'http://brainstrack.com/PHP-Dashboard.php',
            // Legacy support for default.php
            'http://localhost/default.php',
            'https://brainstrack.com/default.php'
        ];
        
        // Variable to hold the current API URL (will change based on connection type)
        // Default to current page URL (ALL-IN-ONE mode)
        let API_URL = LOCALHOST_API_URL;
        
        // Function to get Hostinger API URL - ALL-IN-ONE MODE
        // Always returns current page URL - PHP handles all database connections
        function getHostingerApiUrl() {
            console.log('📡 ALL-IN-ONE: Using current page URL:', CURRENT_PAGE_URL);
            return CURRENT_PAGE_URL;
        }
        
        // Function to set Hostinger API URL
        function setHostingerApiUrl(url) {
            if (!url || url.trim() === '') {
                showCustomToast('⚠️ Please enter a valid URL', 'warning');
                return false;
            }
            
            const trimmedUrl = url.trim();
            
            // Save as current URL
            localStorage.setItem(CURRENT_HOSTINGER_API_KEY, trimmedUrl);
            
            // Add to saved URLs list (only if NOT a fixed URL)
            if (!FIXED_API_URLS.includes(trimmedUrl)) {
                let savedUrls = JSON.parse(localStorage.getItem(HOSTINGER_API_URLS_KEY) || '[]');
                if (!savedUrls.includes(trimmedUrl)) {
                    savedUrls.unshift(trimmedUrl); // Add to beginning
                    localStorage.setItem(HOSTINGER_API_URLS_KEY, JSON.stringify(savedUrls));
                }
            }
            
            // Update dropdown
            loadApiUrlDropdown();
            
            showCustomToast('✅ Hostinger API URL saved successfully!', 'success');
            return true;
        }
        
        // Function to switch API URL based on connection type
        function switchApiUrl(isHostinger = false) {
            if (isHostinger) {
                API_URL = getHostingerApiUrl();
                console.log('🌐 Switched to Hostinger API URL:', API_URL);
            } else {
                API_URL = LOCALHOST_API_URL;
                console.log('🖥️ Switched to Localhost API URL:', API_URL);
            }
        }
        
        try {
            console.log('═══════════════════════════════════════════════════════');
            console.log('🚀 PHP-DASHBOARD.PHP - ALL-IN-ONE APPLICATION');
            console.log('═══════════════════════════════════════════════════════');
            console.log('📄 MODE: Single File (PHP Backend + HTML Frontend)');
            console.log('📍 Current Location:', window.location.href);
            console.log('🌐 Hostname:', CURRENT_HOSTNAME);
            console.log('🔗 Protocol:', CURRENT_PROTOCOL);
            console.log('🖥️ Is Localhost?:', IS_LOCALHOST ? 'YES' : 'NO');
            console.log('───────────────────────────────────────────────────────');
            console.log('📡 API URL (SAME PAGE):', LOCALHOST_API_URL);
            console.log('📄 Current Page URL:', CURRENT_PAGE_URL);
            console.log('✅ Initial API URL:', API_URL);
            console.log('═══════════════════════════════════════════════════════');
            console.log('✅ ALL-IN-ONE mode initialized successfully!');
            console.log('💡 All API requests will go to THIS SAME PAGE');
        } catch (error) {
            console.error('❌ Error during initialization:', error);
        }
        
        const HOSTINGER_CONNECTIONS_KEY = 'hostinger_connections';
        const SELECTED_DATABASE_KEY = 'selected_database_id';
        const CONNECTION_STATE_KEY = 'hostinger_connection_state'; // Track Hostinger connection state
        const LOCALHOST_CONNECTION_STATE_KEY = 'localhost_connection_state'; // NEW: Track localhost connection state
        const LOCALHOST_DATABASES_KEY = 'localhost_databases'; // NEW: Cache localhost databases
        let currentDatabase = null;
        let currentConnection = null;
        let columnCounter = 0;
        let selectedTemplate = null;
        let isHostingerConnected = false; // Hostinger connection state
        let isLocalhostConnected = false; // NEW: Localhost connection state
        
        // Localhost Laragon credentials (static) - Supports multiple configurations
        // Auto-detect the best localhost config based on environment
        const LOCALHOST_CONFIGS = {
            // Primary: Standard Laragon/XAMPP (most common)
            primary: {
                host: 'localhost',
                username: 'root',
                password: '',
                port: '3306'
            },
            // Fallback 1: 127.0.0.1 instead of localhost
            fallback1: {
                host: '127.0.0.1',
                username: 'root',
                password: '',
                port: '3306'
            },
            // Fallback 2: With password (some setups)
            fallback2: {
                host: 'localhost',
                username: 'root',
                password: 'root',
                port: '3306'
            }
        };
        
        // Current active localhost config (can be changed if connection fails)
        let LOCALHOST_CONFIG = LOCALHOST_CONFIGS.primary;
        
        // Try alternate configs if primary fails
        let localhostConfigIndex = 0;
        const configKeys = ['primary', 'fallback1', 'fallback2'];
        
        function tryNextLocalhostConfig() {
            localhostConfigIndex++;
            if (localhostConfigIndex < configKeys.length) {
                LOCALHOST_CONFIG = LOCALHOST_CONFIGS[configKeys[localhostConfigIndex]];
                console.log('🔄 Trying alternate localhost config:', configKeys[localhostConfigIndex], LOCALHOST_CONFIG);
                return true;
            }
            return false;
        }
        
        function resetLocalhostConfig() {
            localhostConfigIndex = 0;
            LOCALHOST_CONFIG = LOCALHOST_CONFIGS.primary;
        }

        // Custom toast notification function
        function showCustomToast(message, type = 'success', duration = 3000) {
            const toast = document.createElement('div');
            
            // Define colors based on type
            const styles = {
                success: {
                    gradient: 'linear-gradient(135deg, #22c55e 0%, #15803d 100%)',
                    border: '#86efac',
                    shadow: 'rgba(34, 197, 94, 0.5)',
                    icon: '✅'
                },
                error: {
                    gradient: 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)',
                    border: '#fca5a5',
                    shadow: 'rgba(239, 68, 68, 0.5)',
                    icon: '❌'
                },
                info: {
                    gradient: 'linear-gradient(135deg, #3b82f6 0%, #1e40af 100%)',
                    border: '#60a5fa',
                    shadow: 'rgba(59, 130, 246, 0.5)',
                    icon: 'ℹ️'
                },
                warning: {
                    gradient: 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)',
                    border: '#fbbf24',
                    shadow: 'rgba(245, 158, 11, 0.5)',
                    icon: '⚠️'
                }
            };
            
            const style = styles[type] || styles.success;
            
            toast.style.cssText = `
                position: fixed;
                bottom: 30px;
                right: 30px;
                background: ${style.gradient};
                color: white;
                padding: 15px 25px;
                border-radius: 12px;
                border: 2px solid ${style.border};
                z-index: 10000;
                box-shadow: 0 8px 25px ${style.shadow};
                animation: slideInRight 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
                min-width: 300px;
                max-width: 500px;
            `;
            
            toast.innerHTML = `
                <div style="display: flex; align-items: center; gap: 12px;">
                    <span style="font-size: 24px; flex-shrink: 0;">${style.icon}</span>
                    <div style="flex: 1;">
                        <div style="font-size: 15px; font-weight: 600; line-height: 1.4;">${message}</div>
                    </div>
                </div>
                <style>
                    @keyframes slideInRight {
                        from { 
                            transform: translateX(400px) scale(0.8); 
                            opacity: 0; 
                        }
                        to { 
                            transform: translateX(0) scale(1); 
                            opacity: 1; 
                        }
                    }
                    @keyframes slideOutRight {
                        from { 
                            transform: translateX(0) scale(1); 
                            opacity: 1; 
                        }
                        to { 
                            transform: translateX(400px) scale(0.8); 
                            opacity: 0; 
                        }
                    }
                </style>
            `;
            
            document.body.appendChild(toast);
            
            // Auto dismiss
            setTimeout(() => {
                toast.style.animation = 'slideOutRight 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55) forwards';
                setTimeout(() => {
                    if (toast.parentNode) {
                        document.body.removeChild(toast);
                    }
                }, 400);
            }, duration);
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // 🔍 DEBUG: Log stored values
            console.log('=== STARTUP DEBUG ===');
            console.log('Stored selectedDatabaseId:', localStorage.getItem(SELECTED_DATABASE_KEY));
            console.log('LOCALHOST_CONFIG:', LOCALHOST_CONFIG);
            console.log('isLocalhostConnected:', isLocalhostConnected);
            console.log('isHostingerConnected:', isHostingerConnected);
            console.log('=====================');
            
            // 🎯 ALL-IN-ONE MODE: All API requests go to this same page
            console.log('🚀 ALL-IN-ONE Mode Active!');
            console.log('📡 All API requests go to:', CURRENT_PAGE_URL);
            
            // Always use current page URL - clear any old settings
            localStorage.setItem(CURRENT_HOSTINGER_API_KEY, CURRENT_PAGE_URL);
            console.log('✅ API URL set to current page:', CURRENT_PAGE_URL);
            
            // Initialize API URL textbox and dropdown
            initApiUrlTextbox();
            
            // Update displayed API URL in Quick Guide
            const displayedApiUrlEl = document.getElementById('displayedApiUrl');
            if (displayedApiUrlEl) {
                displayedApiUrlEl.textContent = AUTO_DETECTED_HOSTINGER_URL;
            }
            
            // Load connection states from localStorage
            loadConnectionState(); // Hostinger
            loadLocalhostConnectionState(); // NEW: Localhost
            
            loadDashboardConnections();
            loadHostingerConnectionsTable();
            addColumnRow(); // Add initial column row for table creation
            
            // Load selected database from localStorage
            setTimeout(() => {
                loadSelectedDatabase();
                
                // Load migration tables if a database is selected
                setTimeout(() => {
                    if (selectedDatabaseId && typeof loadMigrationTables === 'function') {
                        console.log('Initial migration tables load on page load');
                        loadMigrationTables();
                    }
                }, 200);
            }, 100);
        });

        // ========================================
        // LOCALHOST LARAGON CONNECTION SYSTEM
        // ========================================

        // Load localhost connection state
        function loadLocalhostConnectionState() {
            const savedState = localStorage.getItem(LOCALHOST_CONNECTION_STATE_KEY);
            isLocalhostConnected = savedState === 'connected';
            
            console.log('🖥️ Loaded localhost state:', isLocalhostConnected ? 'CONNECTED' : 'DISCONNECTED');
            
            updateLocalhostToggleButton();
        }

        // Save localhost connection state
        function saveLocalhostConnectionState(connected) {
            localStorage.setItem(LOCALHOST_CONNECTION_STATE_KEY, connected ? 'connected' : 'disconnected');
            isLocalhostConnected = connected;
            console.log('💾 Saved localhost state:', connected ? 'CONNECTED' : 'DISCONNECTED');
        }

        // Toggle Localhost connection
        async function toggleLocalhostConnection() {
            const newState = !isLocalhostConnected;
            
            if (newState) {
                // CONNECTING TO LOCALHOST
                // Switch to Localhost API URL (static)
                switchApiUrl(false);
                
                console.log('🟢 CONNECTING to Localhost Laragon...');
                console.log('🖥️ Using API URL:', API_URL);
                
                // Show beautiful connection animation
                showConnectionAnimation('connecting', 'Localhost Laragon');
                
                // Try to fetch databases from localhost
                try {
                    const databases = await fetchLocalhostDatabases();
                    
                    if (databases && databases.length > 0) {
                        // Success! Save databases and update state
                        localStorage.setItem(LOCALHOST_DATABASES_KEY, JSON.stringify(databases));
                        saveLocalhostConnectionState(true);
                        updateLocalhostToggleButton();
                        
                        setTimeout(() => {
                            loadDashboardConnections();
                            updateCreateDatabaseStatus(); // Update Create DB page status
                            updateDeleteDatabaseStatus(); // Update Delete DB page status
                            updateRenameDatabaseStatus(); // Update Rename DB page status
                            loadDatabasesForDropdowns(); // Load databases in dropdowns
                            const countMsg = databases.length > 0 ? `\n📊 ${databases.length} database${databases.length !== 1 ? 's' : ''} found` : '';
                            showCustomToast(`✅ Connected to Localhost Laragon!${countMsg}\nAll local databases are now visible.`, 'success', 3500);
                            
                            if ('vibrate' in navigator) {
                                navigator.vibrate([100, 50, 100]);
                            }
                        }, 800);
                    } else {
                        // No databases found
                        setTimeout(() => {
                            showCustomToast('⚠️ Connected but no databases found on Localhost Laragon.', 'warning', 3000);
                        }, 800);
                        
                        saveLocalhostConnectionState(true);
                        updateLocalhostToggleButton();
                        loadDashboardConnections();
                    }
                } catch (error) {
                    // Connection failed - keep disconnected
                    console.error('❌ Localhost connection failed:', error);
                    
                    // Ensure button stays RED (disconnected)
                    saveLocalhostConnectionState(false);
                    updateLocalhostToggleButton();
                    
                    setTimeout(() => {
                        showCustomToast(`❌ Failed to connect to Localhost Laragon!\n${error.message}\n\nMake sure Laragon MySQL is running.`, 'error', 4000);
                    }, 800);
                }
                
            } else {
                // DISCONNECTING FROM LOCALHOST
                console.log('🔴 DISCONNECTING from Localhost Laragon...');
                
                const databases = getLocalhostDatabases();
                const dbCount = databases.length;
                
                // Confirm if databases are visible
                if (dbCount > 0) {
                    const confirmMsg = `🔴 Disconnect from Localhost Laragon?\n\n${dbCount} database${dbCount !== 1 ? 's' : ''} will be hidden.\n\nContinue?`;
                    if (!confirm(confirmMsg)) {
                        return;
                    }
                }
                
                saveLocalhostConnectionState(false);
                updateLocalhostToggleButton();
                
                showConnectionAnimation('disconnecting', 'Localhost Laragon');
                
                setTimeout(() => {
                    loadDashboardConnections();
                    updateCreateDatabaseStatus(); // Update Create DB page status
                    updateDeleteDatabaseStatus(); // Update Delete DB page status
                    updateRenameDatabaseStatus(); // Update Rename DB page status
                    loadDatabasesForDropdowns(); // Clear dropdowns
                    showCustomToast('⚠️ Disconnected from Localhost Laragon!\nLocal databases are now hidden.', 'warning', 3000);
                    
                    if ('vibrate' in navigator) {
                        navigator.vibrate([200, 100, 200]);
                    }
                }, 800);
            }
        }

        // Fetch databases from localhost
        async function fetchLocalhostDatabases() {
            console.log('📡 Fetching databases from Localhost Laragon...');
            
            // Reset to try from primary config
            resetLocalhostConfig();
            
            // Try each config until one works
            let lastError = null;
            do {
                console.log('🔑 Trying credentials:', {
                    config: configKeys[localhostConfigIndex],
                    host: LOCALHOST_CONFIG.host,
                    user: LOCALHOST_CONFIG.username,
                    password: LOCALHOST_CONFIG.password ? '(set)' : '(empty)',
                    port: LOCALHOST_CONFIG.port
                });
                
                // Get list of databases with localhost credentials
                const result = await apiRequest('list_databases', {
                    db_host: LOCALHOST_CONFIG.host,
                    db_user: LOCALHOST_CONFIG.username,
                    db_pass: LOCALHOST_CONFIG.password,
                    db_port: LOCALHOST_CONFIG.port
                });
                
                if (result.success) {
                    // Handle different response formats (after jsonResponse merge)
                    const databases = result.databases || [];
                    console.log(`✅ Databases fetched with config "${configKeys[localhostConfigIndex]}": ${databases.length} databases`);
                    console.log('📊 Database list:', databases);
                    return databases;
                }
                
                // Store error and try next config
                lastError = result.message || 'Failed to connect to localhost';
                console.warn(`⚠️ Config "${configKeys[localhostConfigIndex]}" failed:`, lastError);
                
            } while (tryNextLocalhostConfig());
            
            // All configs failed
            throw new Error(lastError || 'Failed to connect to localhost MySQL. Make sure MySQL is running.');
        }

        // Get localhost databases from cache
        function getLocalhostDatabases() {
            const saved = localStorage.getItem(LOCALHOST_DATABASES_KEY);
            return saved ? JSON.parse(saved) : [];
        }

        // Show localhost database info
        function showLocalhostInfo(dbName) {
            const info = `🖥️ LOCALHOST DATABASE INFO
${'='.repeat(50)}

Database Name: ${dbName}
Host: ${LOCALHOST_CONFIG.host}
Username: ${LOCALHOST_CONFIG.username}
Password: (empty)
Port: ${LOCALHOST_CONFIG.port}
Server: Laragon MySQL

${'='.repeat(50)}

📋 PHP Connection Code (PDO):
\`\`\`php
$host = '${LOCALHOST_CONFIG.host}';
$dbname = '${dbName}';
$username = '${LOCALHOST_CONFIG.username}';
$password = '';
$port = '${LOCALHOST_CONFIG.port}';

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Connected to Laragon database!";
} catch(PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage();
}
\`\`\`

💡 This is your local Laragon MySQL database.
`;
            
            // Copy to clipboard
            navigator.clipboard.writeText(info).then(() => {
                showCustomToast(`✅ Localhost database info copied!\n🖥️ ${dbName}`, 'success', 2500);
            }).catch(() => {
                alert(info);
            });
        }

        // ==========================================
        // CREDENTIALS MODAL FUNCTIONS
        // ==========================================
        
        // Current credentials data (stored for copy functions)
        let currentCredentials = null;
        
        // Show credentials modal for localhost database
        function showCredentialsModalLocalhost(dbName) {
            currentCredentials = {
                host: LOCALHOST_CONFIG.host,
                dbName: dbName,
                username: LOCALHOST_CONFIG.username,
                password: '',
                port: LOCALHOST_CONFIG.port,
                type: 'localhost'
            };
            showCredentialsModal(currentCredentials);
        }
        
        // Show credentials modal for Hostinger database
        function showCredentialsModalHostinger(connId) {
            const connections = getHostingerConnections();
            const conn = connections.find(c => c.id === connId);
            
            if (!conn) {
                showCustomToast('❌ Connection not found!', 'error', 2500);
                return;
            }
            
            currentCredentials = {
                host: conn.host,
                dbName: conn.dbName,
                username: conn.username,
                password: conn.password || '',
                port: conn.port || '3306',
                type: 'hostinger'
            };
            showCredentialsModal(currentCredentials);
        }
        
        // Display credentials in the modal
        function showCredentialsModal(creds) {
            // Update modal fields
            document.getElementById('credentialsDbName').textContent = creds.dbName;
            document.getElementById('credHost').textContent = creds.host;
            document.getElementById('credDbName').textContent = creds.dbName;
            document.getElementById('credUsername').textContent = creds.username;
            document.getElementById('credPassword').textContent = creds.password || '(empty)';
            document.getElementById('credPort').textContent = creds.port;
            
            // Generate PHP code
            const phpCode = generatePHPCode(creds);
            document.getElementById('credPhpCode').textContent = phpCode;
            
            // Show modal
            document.getElementById('credentialsModal').classList.add('active');
        }
        
        // Close credentials modal
        function closeCredentialsModal() {
            document.getElementById('credentialsModal').classList.remove('active');
        }
        
        // Generate PHP PDO connection code
        function generatePHPCode(creds) {
            const passwordValue = creds.password ? `'${creds.password}'` : "''";
            // Note: Using string concatenation to avoid PHP parsing tags
            const phpOpen = '<' + '?php';
            const phpClose = '?' + '>';
            
            return `${phpOpen}
// Database Connection Configuration
$host = '${creds.host}';
$dbname = '${creds.dbName}';
$username = '${creds.username}';
$password = ${passwordValue};
$port = '${creds.port}';

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    echo "✅ Database connected successfully!";
} catch(PDOException $e) {
    die("❌ Connection failed: " . $e->getMessage());
}
${phpClose}`;
        }
        
        // Copy individual credential field
        function copyCredentialField(fieldId) {
            const element = document.getElementById(fieldId);
            const value = element.textContent;
            
            navigator.clipboard.writeText(value).then(() => {
                showCustomToast(`📋 Copied: ${value}`, 'success', 1500);
            }).catch(() => {
                showCustomToast('❌ Failed to copy!', 'error', 2000);
            });
        }
        
        // Copy PHP code
        function copyPHPCode() {
            const code = document.getElementById('credPhpCode').textContent;
            
            navigator.clipboard.writeText(code).then(() => {
                showCustomToast('✅ PHP code copied to clipboard!', 'success', 2000);
            }).catch(() => {
                showCustomToast('❌ Failed to copy!', 'error', 2000);
            });
        }
        
        // Copy all credentials as formatted text
        function copyAllCredentials() {
            if (!currentCredentials) return;
            
            const creds = currentCredentials;
            const text = `🔑 DATABASE CREDENTIALS
${'═'.repeat(40)}

🖥️  Host:      ${creds.host}
📁  Database:  ${creds.dbName}
👤  Username:  ${creds.username}
🔒  Password:  ${creds.password || '(empty)'}
🔌  Port:      ${creds.port}

${'═'.repeat(40)}

📋 PHP Connection Code (PDO):
${'─'.repeat(40)}
${generatePHPCode(creds)}
${'─'.repeat(40)}

💡 Type: ${creds.type === 'localhost' ? 'Local (Laragon)' : 'Remote (Hostinger)'}
📅 Generated: ${new Date().toLocaleString()}
`;
            
            navigator.clipboard.writeText(text).then(() => {
                showCustomToast('✅ All credentials copied to clipboard!', 'success', 2500);
                closeCredentialsModal();
            }).catch(() => {
                showCustomToast('❌ Failed to copy!', 'error', 2000);
            });
        }

        // Update localhost toggle button
        function updateLocalhostToggleButton() {
            const btn = document.getElementById('connectLocalhostBtn');
            if (!btn) return;

            const icon = btn.querySelector('.toggle-icon');
            const text = btn.querySelector('.toggle-text');
            const databases = getLocalhostDatabases();
            const dbCount = databases.length;

            if (isLocalhostConnected) {
                // CONNECTED STATE (Green)
                btn.classList.remove('disconnected');
                btn.classList.add('connected');
                icon.textContent = '🔗';
                
                if (dbCount > 0) {
                    text.textContent = `Localhost (${dbCount} DB${dbCount !== 1 ? 's' : ''})`;
                } else {
                    text.textContent = 'Connected to Localhost';
                }
            } else {
                // DISCONNECTED STATE (Red)
                btn.classList.remove('connected');
                btn.classList.add('disconnected');
                icon.textContent = '🔌';
                text.textContent = 'Connect Localhost Laragon';
            }
            
            // Update global badge
            updateGlobalConnectionBadge();
        }

        // Update global connection badge (combines both connection states)
        function updateGlobalConnectionBadge() {
            const badge = document.getElementById('connectionStateBadge');
            const refreshBtn = document.getElementById('refreshAllBtn');
            
            if (!badge) return;

            const localhostDbs = getLocalhostDatabases();
            const hostingerConns = getHostingerConnections();
            const localhostCount = isLocalhostConnected ? localhostDbs.length : 0;
            const hostingerCount = isHostingerConnected ? hostingerConns.length : 0;
            const totalCount = localhostCount + hostingerCount;

            if (!isLocalhostConnected && !isHostingerConnected) {
                // Both disconnected
                badge.className = 'connection-state-badge disconnected';
                badge.textContent = '🔴 All Disconnected';
                if (refreshBtn) refreshBtn.style.display = 'none';
            } else if (isLocalhostConnected && isHostingerConnected) {
                // Both connected
                badge.className = 'connection-state-badge connected';
                badge.textContent = `🟢 ${totalCount} Total (🖥️${localhostCount} + 🌐${hostingerCount})`;
                if (refreshBtn) refreshBtn.style.display = 'inline-flex';
            } else if (isLocalhostConnected) {
                // Only localhost connected
                badge.className = 'connection-state-badge connected';
                badge.textContent = `🟢 Localhost (${localhostCount} DB${localhostCount !== 1 ? 's' : ''})`;
                if (refreshBtn) refreshBtn.style.display = 'inline-flex';
            } else if (isHostingerConnected) {
                // Only hostinger connected
                badge.className = 'connection-state-badge connected';
                badge.textContent = `🟢 Hostinger (${hostingerCount} DB${hostingerCount !== 1 ? 's' : ''})`;
                if (refreshBtn) refreshBtn.style.display = 'inline-flex';
            }
        }

        // Refresh all connections
        async function refreshAllConnections() {
            showCustomToast('🔄 Refreshing all connections...', 'info', 2000);
            
            // Refresh localhost if connected
            if (isLocalhostConnected) {
                try {
                    const databases = await fetchLocalhostDatabases();
                    localStorage.setItem(LOCALHOST_DATABASES_KEY, JSON.stringify(databases));
                    updateLocalhostToggleButton();
                    loadDatabasesForDropdowns(); // Update dropdowns
                    console.log('✅ Localhost databases refreshed');
                } catch (error) {
                    console.error('❌ Failed to refresh localhost:', error);
                }
            }
            
            // Reload dashboard
            setTimeout(() => {
                loadDashboardConnections();
                showCustomToast('✅ All connections refreshed!', 'success', 2500);
            }, 500);
        }

        // ==========================================
        // API URL DROPDOWN MANAGEMENT FUNCTIONS
        // ==========================================
        
        // Initialize API URL textbox on page load
        function initApiUrlTextbox() {
            const input = document.getElementById('hostingerApiUrl');
            if (!input) return;
            
            // Load current URL
            const currentUrl = getHostingerApiUrl();
            console.log('🌐 Initializing Hostinger API URL:', currentUrl);
            input.value = currentUrl;
            
            // Show auto-detected indicator
            const badge = document.getElementById('autoDetectBadge');
            if (currentUrl === AUTO_DETECTED_HOSTINGER_URL) {
                input.style.borderColor = '#22c55e';
                input.style.boxShadow = '0 0 10px rgba(34, 197, 94, 0.3)';
                if (badge) badge.style.display = 'inline-block';
                console.log('✨ Using auto-detected URL (no configuration needed!)');
            } else {
                if (badge) badge.style.display = 'none';
            }
            
            // Load dropdown
            loadApiUrlDropdown();
            
            // Add blur event to save when user leaves the textbox
            input.addEventListener('blur', function() {
                const url = this.value.trim();
                if (url && url !== getHostingerApiUrl()) {
                    setHostingerApiUrl(url);
                }
            });
            
            // Add enter key to save
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    const url = this.value.trim();
                    if (url) {
                        setHostingerApiUrl(url);
                        this.blur();
                    }
                }
            });
        }
        
        // Toggle API URL dropdown
        function toggleApiUrlDropdown() {
            const dropdown = document.getElementById('apiUrlDropdown');
            if (!dropdown) return;
            
            dropdown.classList.toggle('show');
            
            // Close dropdown when clicking outside
            if (dropdown.classList.contains('show')) {
                setTimeout(() => {
                    document.addEventListener('click', closeApiUrlDropdownOnClickOutside);
                }, 100);
            }
        }
        
        // Close dropdown when clicking outside
        function closeApiUrlDropdownOnClickOutside(e) {
            const dropdown = document.getElementById('apiUrlDropdown');
            const input = document.getElementById('hostingerApiUrl');
            const btn = document.querySelector('.api-url-dropdown-btn');
            
            if (dropdown && !dropdown.contains(e.target) && e.target !== input && e.target !== btn) {
                dropdown.classList.remove('show');
                document.removeEventListener('click', closeApiUrlDropdownOnClickOutside);
            }
        }
        
        // Load API URL dropdown items
        function loadApiUrlDropdown() {
            const dropdown = document.getElementById('apiUrlDropdown');
            if (!dropdown) return;
            
            const savedUrls = JSON.parse(localStorage.getItem(HOSTINGER_API_URLS_KEY) || '[]');
            const currentUrl = getHostingerApiUrl();
            
            let htmlContent = '';
            
            // Add AUTO-DETECTED URL Section (HIGHLIGHTED)
            const isAutoDetectedCurrent = AUTO_DETECTED_HOSTINGER_URL === currentUrl;
            htmlContent += '<div style="padding: 8px 12px; background: rgba(34, 197, 94, 0.2); border-bottom: 2px solid rgba(34, 197, 94, 0.4); font-size: 11px; color: #86efac; font-weight: 600; letter-spacing: 0.5px;">🎯 AUTO-DETECTED (RECOMMENDED)</div>';
            htmlContent += `
                <div class="api-url-dropdown-item ${isAutoDetectedCurrent ? 'current' : ''}" onclick="selectApiUrl('${AUTO_DETECTED_HOSTINGER_URL.replace(/'/g, "\\'")}')}" style="background: ${isAutoDetectedCurrent ? 'rgba(34, 197, 94, 0.3)' : 'rgba(34, 197, 94, 0.1)'}; border-left: 3px solid #22c55e; font-weight: bold;">
                    <span style="flex: 1; overflow: hidden; text-overflow: ellipsis;">✨ ${AUTO_DETECTED_HOSTINGER_URL}</span>
                    ${isAutoDetectedCurrent ? '<span style="color: #22c55e; margin-left: 5px; font-size: 16px;">✓</span>' : ''}
                </div>
            `;
            
            // Add Fixed URLs Section
            htmlContent += '<div style="padding: 8px 12px; background: rgba(139, 92, 246, 0.15); border-bottom: 2px solid rgba(139, 92, 246, 0.3); font-size: 11px; color: #a78bfa; font-weight: 600; letter-spacing: 0.5px; margin-top: 5px;">📌 OTHER PRESETS</div>';
            
            FIXED_API_URLS.forEach(url => {
                // Skip auto-detected URL if it's already in fixed list
                if (url === AUTO_DETECTED_HOSTINGER_URL) return;
                
                const isCurrent = url === currentUrl;
                htmlContent += `
                    <div class="api-url-dropdown-item ${isCurrent ? 'current' : ''}" onclick="selectApiUrl('${url.replace(/'/g, "\\'")}')}" style="background: ${isCurrent ? 'rgba(34, 197, 94, 0.2)' : 'rgba(139, 92, 246, 0.05)'}; border-left: 3px solid ${isCurrent ? '#22c55e' : '#8b5cf6'};">
                        <span style="flex: 1; overflow: hidden; text-overflow: ellipsis;">🔒 ${url}</span>
                        ${isCurrent ? '<span style="color: #22c55e; margin-left: 5px;">✓</span>' : ''}
                    </div>
                `;
            });
            
            // Add Custom URLs Section (if any exist)
            if (savedUrls.length > 0) {
                htmlContent += '<div style="padding: 8px 12px; background: rgba(251, 191, 36, 0.15); border-bottom: 2px solid rgba(251, 191, 36, 0.3); border-top: 2px solid rgba(251, 191, 36, 0.3); font-size: 11px; color: #fbbf24; font-weight: 600; letter-spacing: 0.5px; margin-top: 5px;">⭐ CUSTOM ENDPOINTS</div>';
                
                savedUrls.forEach(url => {
                    const isCurrent = url === currentUrl;
                    htmlContent += `
                        <div class="api-url-dropdown-item ${isCurrent ? 'current' : ''}" onclick="selectApiUrl('${url.replace(/'/g, "\\'")}')">
                            <span style="flex: 1; overflow: hidden; text-overflow: ellipsis;">${url}</span>
                            ${isCurrent ? '<span style="color: #22c55e; margin-left: 5px;">✓</span>' : ''}
                            <button class="api-url-delete-btn" onclick="event.stopPropagation(); deleteApiUrl('${url.replace(/'/g, "\\'")}');" title="Delete custom URL">✕</button>
                        </div>
                    `;
                });
            }
            
            dropdown.innerHTML = htmlContent;
        }
        
        // Select API URL from dropdown
        function selectApiUrl(url) {
            const input = document.getElementById('hostingerApiUrl');
            if (!input) return;
            
            input.value = url;
            setHostingerApiUrl(url);
            
            // Close dropdown
            const dropdown = document.getElementById('apiUrlDropdown');
            if (dropdown) {
                dropdown.classList.remove('show');
            }
        }
        
        // Reset to auto-detected URL
        function resetToAutoDetected() {
            console.log('🔄 Resetting to auto-detected URL:', AUTO_DETECTED_HOSTINGER_URL);
            
            // Save auto-detected URL
            localStorage.setItem(CURRENT_HOSTINGER_API_KEY, AUTO_DETECTED_HOSTINGER_URL);
            
            // Update input
            const input = document.getElementById('hostingerApiUrl');
            const badge = document.getElementById('autoDetectBadge');
            
            if (input) {
                input.value = AUTO_DETECTED_HOSTINGER_URL;
                
                // Visual feedback
                input.style.background = 'rgba(34, 197, 94, 0.2)';
                input.style.borderColor = '#22c55e';
                input.style.boxShadow = '0 0 10px rgba(34, 197, 94, 0.3)';
                
                if (badge) {
                    badge.style.display = 'inline-block';
                    badge.style.animation = 'pulse 1s ease-in-out 3';
                }
                
                setTimeout(() => {
                    input.style.background = '';
                }, 1000);
            }
            
            loadApiUrlDropdown();
            
            showCustomToast(`✅ Reset to auto-detected URL!\n🎯 ${AUTO_DETECTED_HOSTINGER_URL}\n\nNo configuration needed!`, 'success', 3000);
        }
        
        // Delete API URL from saved list
        function deleteApiUrl(url) {
            // Check if it's a fixed URL
            if (FIXED_API_URLS.includes(url)) {
                showCustomToast('🔒 Cannot delete fixed endpoint!\nFixed endpoints are permanent.', 'warning', 3000);
                return;
            }
            
            const confirmDelete = confirm(`Delete this URL from saved list?\n\n${url}`);
            if (!confirmDelete) return;
            
            let savedUrls = JSON.parse(localStorage.getItem(HOSTINGER_API_URLS_KEY) || '[]');
            savedUrls = savedUrls.filter(u => u !== url);
            localStorage.setItem(HOSTINGER_API_URLS_KEY, JSON.stringify(savedUrls));
            
            // If deleted URL was current, switch to first fixed URL
            const currentUrl = localStorage.getItem(CURRENT_HOSTINGER_API_KEY);
            if (currentUrl === url) {
                // Switch to first available URL (fixed or custom)
                const newUrl = FIXED_API_URLS[0] || (savedUrls.length > 0 ? savedUrls[0] : '');
                if (newUrl) {
                    localStorage.setItem(CURRENT_HOSTINGER_API_KEY, newUrl);
                    document.getElementById('hostingerApiUrl').value = newUrl;
                } else {
                    localStorage.removeItem(CURRENT_HOSTINGER_API_KEY);
                    document.getElementById('hostingerApiUrl').value = '';
                }
            }
            
            loadApiUrlDropdown();
            showCustomToast('🗑️ Custom URL deleted successfully', 'info');
        }
        
        // ========================================
        // HOSTINGER CONNECTION TOGGLE SYSTEM
        // ========================================

        // Load connection state from localStorage
        function loadConnectionState() {
            const savedState = localStorage.getItem(CONNECTION_STATE_KEY);
            isHostingerConnected = savedState === 'connected';
            
            console.log('🔌 Loaded connection state:', isHostingerConnected ? 'CONNECTED' : 'DISCONNECTED');
            
            updateConnectionToggleButton();
        }

        // Save connection state to localStorage
        function saveConnectionState(connected) {
            localStorage.setItem(CONNECTION_STATE_KEY, connected ? 'connected' : 'disconnected');
            isHostingerConnected = connected;
            console.log('💾 Saved connection state:', connected ? 'CONNECTED' : 'DISCONNECTED');
        }

        // Toggle Hostinger connection
        function toggleHostingerConnection() {
            console.log('🔌 toggleHostingerConnection called');
            const newState = !isHostingerConnected;
            const connections = getHostingerConnections();
            const dbCount = connections.length;
            
            if (newState) {
                // CONNECTING - API URL will be auto-detected
                const apiUrl = getHostingerApiUrl();
                const input = document.getElementById('hostingerApiUrl');
                
                console.log('🔌 Connecting with API URL:', apiUrl);
                
                // Minimal validation (auto-detected URL is always valid)
                // Accept both PHP-Dashboard.php and default.php (legacy)
                const isValidUrl = apiUrl && (apiUrl.includes('PHP-Dashboard.php') || apiUrl.includes('default.php') || apiUrl.includes('.php'));
                if (!isValidUrl) {
                    showCustomToast('⚠️ Invalid API URL!\n\nClick "🔄 Auto" button to use auto-detected URL.', 'warning', 4000);
                    if (input) {
                        input.focus();
                        input.style.border = '2px solid #ef4444';
                        setTimeout(() => {
                            input.style.border = '';
                        }, 2000);
                    }
                    return;
                }
                
                // Switch to Hostinger API URL
                switchApiUrl(true);
                
                console.log('🟢 CONNECTING to all Hostinger databases...');
                console.log('🌐 Using API URL:', API_URL);
                saveConnectionState(true);
                updateConnectionToggleButton();
                
                // Show beautiful connection animation
                showConnectionAnimation('connecting');
                
                // Load and display all connections
                setTimeout(() => {
                    loadDashboardConnections();
                    
                    const countMsg = dbCount > 0 ? `\n📊 ${dbCount} database${dbCount !== 1 ? 's' : ''} now accessible` : '';
                    showCustomToast(`✅ Connected to Hostinger Shared!${countMsg}\nAll databases are now visible.`, 'success', 3500);
                    
                    // Play success sound effect (vibrate on mobile if supported)
                    if ('vibrate' in navigator) {
                        navigator.vibrate([100, 50, 100]);
                    }
                }, 800);
                
            } else {
                // DISCONNECTING
                console.log('🔴 DISCONNECTING from all Hostinger databases...');
                
                // Confirm if databases are visible
                if (dbCount > 0) {
                    const confirmMsg = `🔴 Disconnect from Hostinger Shared?\n\n${dbCount} database${dbCount !== 1 ? 's' : ''} will be hidden.\n\nContinue?`;
                    if (!confirm(confirmMsg)) {
                        return; // User cancelled
                    }
                }
                
                saveConnectionState(false);
                updateConnectionToggleButton();
                
                // Show disconnection animation
                showConnectionAnimation('disconnecting');
                
                // Hide all connections
                setTimeout(() => {
                    loadDashboardConnections();
                    showCustomToast('⚠️ Disconnected from Hostinger Shared!\nDatabases are now hidden for security.', 'warning', 3000);
                    
                    // Play warning sound effect
                    if ('vibrate' in navigator) {
                        navigator.vibrate([200, 100, 200]);
                    }
                }, 800);
            }
        }

        // Update connection toggle button appearance
        function updateConnectionToggleButton() {
            const btn = document.getElementById('connectToggleBtn');
            
            if (!btn) return;

            const icon = btn.querySelector('.toggle-icon');
            const text = btn.querySelector('.toggle-text');
            const connections = getHostingerConnections();
            const dbCount = connections.length;

            if (isHostingerConnected) {
                // CONNECTED STATE (Green)
                btn.classList.remove('disconnected');
                btn.classList.add('connected');
                icon.textContent = '🔗';
                
                // Show count if databases exist
                if (dbCount > 0) {
                    text.textContent = `Hostinger (${dbCount} DB${dbCount !== 1 ? 's' : ''})`;
                } else {
                    text.textContent = 'Connected to Hostinger';
                }
            } else {
                // DISCONNECTED STATE (Red)
                btn.classList.remove('connected');
                btn.classList.add('disconnected');
                icon.textContent = '🔌';
                text.textContent = 'Connect Hostinger Shared';
            }
            
            // Update global badge (combines both connections)
            updateGlobalConnectionBadge();
        }

        // Show connection/disconnection animation
        function showConnectionAnimation(type, serverName = 'Hostinger Shared') {
            const overlay = document.createElement('div');
            overlay.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.8); z-index: 9999; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(5px);';
            
            if (type === 'connecting') {
                overlay.innerHTML = `
                    <div style="text-align: center; animation: scaleIn 0.5s ease-out;">
                        <div style="font-size: 80px; margin-bottom: 20px; animation: connectedSpin 2s linear infinite;">🔗</div>
                        <div style="font-size: 28px; color: #22c55e; font-weight: bold; margin-bottom: 10px;">Connecting...</div>
                        <div style="font-size: 16px; color: #86efac;">Establishing connection to ${serverName}</div>
                        <div style="margin-top: 20px;">
                            <div style="width: 200px; height: 6px; background: rgba(34, 197, 94, 0.3); border-radius: 10px; overflow: hidden;">
                                <div style="width: 100%; height: 100%; background: linear-gradient(90deg, #22c55e, #86efac); animation: loadingBar 0.8s ease-out;"></div>
                            </div>
                        </div>
                    </div>
                    <style>
                        @keyframes scaleIn {
                            from { transform: scale(0.5); opacity: 0; }
                            to { transform: scale(1); opacity: 1; }
                        }
                        @keyframes loadingBar {
                            from { transform: translateX(-100%); }
                            to { transform: translateX(0); }
                        }
                    </style>
                `;
            } else {
                overlay.innerHTML = `
                    <div style="text-align: center; animation: scaleIn 0.5s ease-out;">
                        <div style="font-size: 80px; margin-bottom: 20px; animation: disconnectPulse 1s ease-in-out infinite;">🔌</div>
                        <div style="font-size: 28px; color: #ef4444; font-weight: bold; margin-bottom: 10px;">Disconnecting...</div>
                        <div style="font-size: 16px; color: #fca5a5;">Closing connection to ${serverName}</div>
                        <div style="margin-top: 20px;">
                            <div style="width: 200px; height: 6px; background: rgba(239, 68, 68, 0.3); border-radius: 10px; overflow: hidden;">
                                <div style="width: 100%; height: 100%; background: linear-gradient(90deg, #ef4444, #fca5a5); animation: loadingBar 0.8s ease-out;"></div>
                            </div>
                        </div>
                    </div>
                    <style>
                        @keyframes scaleIn {
                            from { transform: scale(0.5); opacity: 0; }
                            to { transform: scale(1); opacity: 1; }
                        }
                        @keyframes loadingBar {
                            from { transform: translateX(-100%); }
                            to { transform: translateX(0); }
                        }
                    </style>
                `;
            }
            
            document.body.appendChild(overlay);
            
            // Remove after animation
            setTimeout(() => {
                overlay.style.animation = 'fadeOut 0.3s ease-out forwards';
                overlay.innerHTML += '<style>@keyframes fadeOut { to { opacity: 0; } }</style>';
                setTimeout(() => {
                    if (overlay.parentNode) {
                        document.body.removeChild(overlay);
                    }
                }, 300);
            }, 800);
        }

        // Sidebar toggle
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            sidebar.classList.toggle('show');
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
        }

        // Section navigation
        function showSection(sectionId) {
            // Hide all sections
            document.querySelectorAll('.section').forEach(section => {
                section.classList.remove('active');
            });
            // Show selected section
            document.getElementById(sectionId).classList.add('active');
            
            // Update active nav link
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });
            event.target.closest('.nav-link').classList.add('active');

            // Close sidebar on mobile
            if (window.innerWidth <= 768) {
                toggleSidebar();
            }

            // Load data when entering certain sections
            if (sectionId === 'dashboard') {
                updateConnectionToggleButton(); // Update Hostinger button state
                updateLocalhostToggleButton(); // Update Localhost button state
                loadDashboardConnections();
            } else if (sectionId === 'create') {
                updateCreateDatabaseStatus(); // Update status indicator
            } else if (sectionId === 'delete') {
                updateDeleteDatabaseStatus(); // Update status indicator
                loadDatabasesForDropdowns(); // Load localhost databases
            } else if (sectionId === 'rename') {
                updateRenameDatabaseStatus(); // Update status indicator
                loadDatabasesForDropdowns(); // Load localhost databases
            } else if (sectionId === 'settings') {
                loadHostingerConnectionsTable();
            } else if (sectionId === 'generateDatabase') {
                updateSelectedDatabaseDisplay();
            } else if (sectionId === 'aiPrompt') {
                updateAIPromptDisplay();
                // Load databases and prompts table for prompt generator
                loadPromptGenDatabases();
                loadSavedPromptsTable();
            } else if (sectionId === 'createTable') {
                updateCreateTableDatabaseDisplay();
            } else if (sectionId === 'editTable') {
                updateEditTableDatabaseDisplay();
                // Auto-load tables for dropdown
                if (selectedDatabaseId) {
                    loadTablesForDropdowns();
                }
            } else if (sectionId === 'deleteTable') {
                updateDeleteTableDatabaseDisplay();
                // Auto-load tables for dropdown
                if (selectedDatabaseId) {
                    loadTablesForDropdowns();
                }
            } else if (sectionId === 'renameTable') {
                updateRenameTableDatabaseDisplay();
                // Auto-load tables for dropdown
                if (selectedDatabaseId) {
                    loadTablesForDropdowns();
                }
            } else if (sectionId === 'listTables') {
                // Auto-load tables when entering List Tables section
                if (selectedDatabaseId) {
                    setTimeout(() => {
                        loadTables();
                    }, 100);
                }
            }

            // Restore selection state when navigating
            setTimeout(() => {
                loadSelectedDatabase();
            }, 50);
        }

        // Update AI Prompt display
        function updateAIPromptDisplay() {
            console.log('=== UPDATE AI PROMPT DISPLAY ===');
            console.log('selectedDatabaseId:', selectedDatabaseId);
            
            const displayEl = document.getElementById('aiPromptDbNameDisplay');
            const promptTextarea = document.getElementById('aiPromptText');
            const copyBtn = document.getElementById('copyAIPromptBtn');
            const generateDescBtn = document.getElementById('generateDescBtn');
            const hostEl = document.getElementById('aiPromptHost');
            const dbNameEl = document.getElementById('aiPromptDbName');
            const userEl = document.getElementById('aiPromptUser');
            const portEl = document.getElementById('aiPromptPort');

            if (!displayEl || !promptTextarea) {
                console.log('❌ Elements not found');
                return;
            }

            if (selectedDatabaseId) {
                // Get connection (supports both Localhost and Hostinger)
                console.log('Getting connection for:', selectedDatabaseId);
                const conn = getConnectionById(selectedDatabaseId);
                console.log('Connection result:', conn);
                
                if (conn) {
                    // Update display
                    displayEl.textContent = `${conn.name} (${conn.dbName})`;
                    displayEl.style.color = '#22c55e';
                    
                    // Update quick info cards
                    if (hostEl) hostEl.textContent = conn.host;
                    if (dbNameEl) dbNameEl.textContent = conn.dbName;
                    if (userEl) userEl.textContent = conn.username;
                    if (portEl) portEl.textContent = conn.port;
                    
                    // Enable generate description button
                    if (generateDescBtn) generateDescBtn.disabled = false;
                    
                    // Generate AI Prompt
                    const prompt = `DATABASE CONNECTION INFORMATION
=====================================

I need you to connect to my MySQL database with the following credentials:

Connection Name: ${conn.name}
Server Type: ${conn.isLocalhost ? 'Localhost (Laragon)' : (conn.type === 'vps' ? 'VPS Server' : 'Shared Hosting')}

Database Host: ${conn.host}
Database Name: ${conn.dbName}
Database Username: ${conn.username}
Database Password: ${conn.password}
Database Port: ${conn.port}

IMPORTANT INSTRUCTIONS FOR AI:
- Use these exact credentials to establish database connection
- Default charset: utf8mb4
- Default collation: utf8mb4_unicode_ci
- Connection method: PDO (PHP) or appropriate driver for your language
- Always use prepared statements for security

SAMPLE CONNECTION CODE (PHP):
\`\`\`php
$host = '${conn.host}';
$dbname = '${conn.dbName}';
$username = '${conn.username}';
$password = '${conn.password}';
$port = '${conn.port}';

try {
    $dsn = "mysql:host=\$host;port=\$port;dbname=\$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    echo "Connected successfully!";
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
\`\`\`

SAMPLE CONNECTION CODE (Node.js):
\`\`\`javascript
const mysql = require('mysql2/promise');

const connection = await mysql.createConnection({
    host: '${conn.host}',
    port: ${conn.port},
    user: '${conn.username}',
    password: '${conn.password}',
    database: '${conn.dbName}',
    charset: 'utf8mb4'
});

console.log('Connected successfully!');
\`\`\`

SAMPLE CONNECTION CODE (Python):
\`\`\`python
import mysql.connector

connection = mysql.connector.connect(
    host='${conn.host}',
    port=${conn.port},
    user='${conn.username}',
    password='${conn.password}',
    database='${conn.dbName}',
    charset='utf8mb4'
)

print("Connected successfully!")
\`\`\`

You can now use this connection to:
- Query tables and data
- Create/modify database schema
- Execute SQL statements
- Perform CRUD operations
- Generate reports and analytics

Please confirm you can connect successfully before proceeding with any operations.
`;
                    
                    promptTextarea.value = prompt;
                    if (copyBtn) copyBtn.disabled = false;
                } else {
                    displayEl.textContent = 'None - Please select from Dashboard';
                    displayEl.style.color = '#fca5a5';
                    promptTextarea.value = 'Select a database from the Dashboard to generate AI connection prompt...';
                    if (copyBtn) copyBtn.disabled = true;
                    if (generateDescBtn) generateDescBtn.disabled = true;
                    if (hostEl) hostEl.textContent = '-';
                    if (dbNameEl) dbNameEl.textContent = '-';
                    if (userEl) userEl.textContent = '-';
                    if (portEl) portEl.textContent = '-';
                }
            } else {
                displayEl.textContent = 'None - Please select from Dashboard';
                displayEl.style.color = '#fca5a5';
                promptTextarea.value = 'Select a database from the Dashboard to generate AI connection prompt...';
                if (copyBtn) copyBtn.disabled = true;
                if (generateDescBtn) generateDescBtn.disabled = true;
                if (hostEl) hostEl.textContent = '-';
                if (dbNameEl) dbNameEl.textContent = '-';
                if (userEl) userEl.textContent = '-';
                if (portEl) portEl.textContent = '-';
            }
        }

        // Switch between AI tabs
        function switchAITab(tabName) {
            // Only target AI section tabs
            const aiSection = document.getElementById('aiPrompt');
            if (!aiSection) return;

            aiSection.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            
            // Hide all tabs
            document.getElementById('aiConnectionTab').classList.remove('active');
            document.getElementById('aiDescriptionTab').classList.remove('active');
            document.getElementById('aiPromptGeneratorTab').classList.remove('active');
            
            if (tabName === 'connection') {
                aiSection.querySelector('.tab-btn:nth-child(1)').classList.add('active');
                document.getElementById('aiConnectionTab').classList.add('active');
            } else if (tabName === 'description') {
                aiSection.querySelector('.tab-btn:nth-child(2)').classList.add('active');
                document.getElementById('aiDescriptionTab').classList.add('active');
            } else if (tabName === 'promptGenerator') {
                aiSection.querySelector('.tab-btn:nth-child(3)').classList.add('active');
                document.getElementById('aiPromptGeneratorTab').classList.add('active');
                // Load databases and prompts table when opening prompt generator
                loadPromptGenDatabases();
                loadSavedPromptsTable();
            }
        }

        // Copy AI Connection Prompt to clipboard
        async function copyAIConnectionPrompt() {
            const promptText = document.getElementById('aiPromptText').value;
            
            try {
                await navigator.clipboard.writeText(promptText);
                showMessage('aiPromptMessage', '✅ AI Connection Prompt copied to clipboard!', 'success');
                
                // Visual feedback
                const btn = document.getElementById('copyAIPromptBtn');
                const originalHtml = btn.innerHTML;
                btn.innerHTML = '<span>✅</span> Copied!';
                btn.style.background = '#22c55e';
                
                setTimeout(() => {
                    btn.innerHTML = originalHtml;
                    btn.style.background = '';
                }, 2000);
            } catch (err) {
                // Fallback
                const textarea = document.getElementById('aiPromptText');
                textarea.select();
                document.execCommand('copy');
                showMessage('aiPromptMessage', '✅ AI Connection Prompt copied to clipboard!', 'success');
            }
        }

        // Generate Database Description for AI
        async function generateDatabaseDescription() {
            console.log('=== GENERATE DATABASE DESCRIPTION ===');
            console.log('selectedDatabaseId:', selectedDatabaseId);
            
            if (!selectedDatabaseId) {
                showMessage('aiPromptMessage', '❌ Please select a database first', 'error');
                return;
            }

            // Get connection (supports both Localhost and Hostinger)
            console.log('Calling getConnectionById with:', selectedDatabaseId);
            const conn = getConnectionById(selectedDatabaseId);
            console.log('Connection result:', conn);
            
            if (!conn) {
                console.error('❌ Connection not found for ID:', selectedDatabaseId);
                showMessage('aiPromptMessage', '❌ Connection not found. ID: ' + selectedDatabaseId, 'error');
                return;
            }

            // Show loading
            document.getElementById('descriptionLoading').style.display = 'block';
            document.getElementById('aiDescriptionText').value = 'Analyzing database structure...';
            showMessage('aiPromptMessage', '🔄 Analyzing database structure...', 'info');

            // Get list of tables
            const tablesResult = await apiRequest('list_tables', {
                db_host: conn.host,
                db_name: conn.dbName,
                db_user: conn.username,
                db_pass: conn.password,
                db_port: conn.port
            });

            if (!tablesResult.success) {
                document.getElementById('descriptionLoading').style.display = 'none';
                showMessage('aiPromptMessage', `❌ ${tablesResult.message}`, 'error');
                return;
            }

            const tablesList = tablesResult.tables || [];

            // Start building description
            let description = `DATABASE STRUCTURE ANALYSIS
=====================================

Connection Name: ${conn.name}
Server Type: ${conn.isLocalhost ? 'Localhost (Laragon)' : (conn.type === 'vps' ? 'VPS Server' : 'Shared Hosting')}
Database Name: ${conn.dbName}
Database Host: ${conn.host}

OVERVIEW:
- Total Tables: ${tablesList.length}
- Database Engine: MySQL/MariaDB
- Character Set: utf8mb4
- Collation: utf8mb4_unicode_ci

`;

            if (tablesList.length === 0) {
                description += `\nDATABASE STATUS: Empty (No tables found)\n`;
                document.getElementById('aiDescriptionText').value = description;
                document.getElementById('descriptionLoading').style.display = 'none';
                document.getElementById('copyDescBtn').disabled = false;
                showMessage('aiPromptMessage', '✅ Database description generated', 'success');
                return;
            }

            description += `\nTABLES IN DATABASE:\n`;
            description += `${'='.repeat(60)}\n\n`;

            // Get structure for each table
            let totalRecords = 0;
            for (let i = 0; i < tablesList.length; i++) {
                const tableName = tablesList[i];
                
                // Get table structure
                const structureResult = await apiRequest('get_table_structure', {
                    db_host: conn.host,
                    db_name: conn.dbName,
                    db_user: conn.username,
                    db_pass: conn.password,
                    db_port: conn.port,
                    table_name: tableName
                });

                // Get table data count
                const dataResult = await apiRequest('get_table_data', {
                    db_host: conn.host,
                    db_name: conn.dbName,
                    db_user: conn.username,
                    db_pass: conn.password,
                    db_port: conn.port,
                    table_name: tableName,
                    page: 1,
                    limit: 1
                });

                if (structureResult.success && dataResult.success) {
                    const rowCount = dataResult.pagination.total_rows;
                    totalRecords += rowCount;

                    description += `TABLE ${i + 1}: ${tableName}\n`;
                    description += `${'-'.repeat(60)}\n`;
                    description += `Records: ${rowCount.toLocaleString()} rows\n`;
                    description += `Columns: ${structureResult.columns.length}\n\n`;

                    description += `Column Structure:\n`;
                    structureResult.columns.forEach((col, idx) => {
                        description += `  ${idx + 1}. ${col.Field}\n`;
                        description += `     Type: ${col.Type}\n`;
                        description += `     Null: ${col.Null}\n`;
                        if (col.Key) description += `     Key: ${col.Key}\n`;
                        if (col.Default !== null) description += `     Default: ${col.Default}\n`;
                        if (col.Extra) description += `     Extra: ${col.Extra}\n`;
                        description += `\n`;
                    });

                    description += `\n`;
                }
            }

            description += `\n${'='.repeat(60)}\n`;
            description += `\nSUMMARY:\n`;
            description += `- Total Tables: ${tablesList.length}\n`;
            description += `- Total Records Across All Tables: ${totalRecords.toLocaleString()}\n`;
            description += `- Database Type: Relational (MySQL)\n`;
            description += `- Server Environment: ${conn.type === 'vps' ? 'VPS (Full Control)' : 'Shared Hosting (Limited Privileges)'}\n\n`;

            description += `RECOMMENDATIONS FOR AI:\n`;
            description += `- Use this structure information when generating queries\n`;
            description += `- Respect foreign key relationships if present\n`;
            description += `- Consider table sizes when performing operations\n`;
            description += `- Use appropriate indexes for optimization\n`;
            description += `- Follow naming conventions observed in the schema\n`;

            // Set the description
            document.getElementById('aiDescriptionText').value = description;
            document.getElementById('descriptionLoading').style.display = 'none';
            document.getElementById('copyDescBtn').disabled = false;
            showMessage('aiPromptMessage', `✅ Database description generated (${tablesList.length} tables analyzed)`, 'success');
        }

        // Copy Database Description to clipboard
        async function copyDatabaseDescription() {
            const descText = document.getElementById('aiDescriptionText').value;
            
            try {
                await navigator.clipboard.writeText(descText);
                showMessage('aiPromptMessage', '✅ Database description copied to clipboard!', 'success');
                
                // Visual feedback
                const btn = document.getElementById('copyDescBtn');
                const originalHtml = btn.innerHTML;
                btn.innerHTML = '<span>✅</span> Copied!';
                btn.style.background = '#22c55e';
                
                setTimeout(() => {
                    btn.innerHTML = originalHtml;
                    btn.style.background = '';
                }, 2000);
            } catch (err) {
                // Fallback
                const textarea = document.getElementById('aiDescriptionText');
                textarea.select();
                document.execCommand('copy');
                showMessage('aiPromptMessage', '✅ Database description copied to clipboard!', 'success');
            }
        }

        // Update selected database display in Delete Table section
        function updateDeleteTableDatabaseDisplay() {
            const displayEl = document.getElementById('deleteTableDbName');
            if (!displayEl) return;

            if (selectedDatabaseId) {
                // Get connection info (supports both Localhost and Hostinger)
                const conn = getConnectionById(selectedDatabaseId);
                
                if (conn) {
                    displayEl.textContent = `${conn.name} (${conn.dbName})`;
                } else {
                    displayEl.textContent = 'None - Please select from Dashboard';
                }
            } else {
                displayEl.textContent = 'None - Please select from Dashboard';
            }
        }

        // Update selected database display in Rename Table section
        function updateRenameTableDatabaseDisplay() {
            const displayEl = document.getElementById('renameTableDbName');
            if (!displayEl) return;

            if (selectedDatabaseId) {
                // Get connection info (supports both Localhost and Hostinger)
                const conn = getConnectionById(selectedDatabaseId);
                
                if (conn) {
                    displayEl.textContent = `${conn.name} (${conn.dbName})`;
                } else {
                    displayEl.textContent = 'None - Please select from Dashboard';
                }
            } else {
                displayEl.textContent = 'None - Please select from Dashboard';
            }
        }

        // Update selected database display in Edit Table section
        function updateEditTableDatabaseDisplay() {
            const displayEl = document.getElementById('editTableDbName');
            const helperEl = document.getElementById('editTableHelper');
            if (!displayEl) return;

            if (selectedDatabaseId) {
                // Get connection info (supports both Localhost and Hostinger)
                const conn = getConnectionById(selectedDatabaseId);
                
                if (conn) {
                    displayEl.textContent = `${conn.name} (${conn.dbName})`;
                    if (helperEl) {
                        helperEl.innerHTML = '✅ Database connected - Loading tables...';
                        helperEl.style.color = '#86efac';
                    }
                } else {
                    displayEl.textContent = 'None - Please select from Dashboard';
                    if (helperEl) {
                        helperEl.innerHTML = '💡 Select a database from Dashboard to see available tables';
                        helperEl.style.color = '';
                    }
                }
            } else {
                displayEl.textContent = 'None - Please select from Dashboard';
                if (helperEl) {
                    helperEl.innerHTML = '💡 Select a database from Dashboard to see available tables';
                    helperEl.style.color = '';
                }
            }
        }

        // Update selected database display in Create Table section
        function updateCreateTableDatabaseDisplay() {
            const displayEl = document.getElementById('createTableDbName');
            if (!displayEl) return;

            if (selectedDatabaseId) {
                // Use getConnectionById to support BOTH Localhost and Hostinger
                const conn = getConnectionById(selectedDatabaseId);
                
                if (conn) {
                    displayEl.textContent = `${conn.name} (${conn.dbName})`;
                } else {
                    displayEl.textContent = 'None - Please select from Dashboard';
                }
            } else {
                displayEl.textContent = 'None - Please select from Dashboard';
            }
        }

        // Update selected database display in Generate Database section
        function updateSelectedDatabaseDisplay() {
            const displayEl = document.getElementById('selectedDbNameDisplay');
            const executeSQLBtn = document.getElementById('executeSQLBtn');
            
            if (!displayEl) return;

            if (selectedDatabaseId) {
                // Use getConnectionById to support BOTH Localhost and Hostinger
                const conn = getConnectionById(selectedDatabaseId);
                
                if (conn) {
                    displayEl.textContent = `${conn.name} (${conn.dbName})`;
                    displayEl.style.color = '#22c55e';
                    // Enable SQL executor button
                    if (executeSQLBtn) executeSQLBtn.disabled = false;
                } else {
                    displayEl.textContent = 'None - Please select from Dashboard';
                    displayEl.style.color = '#fca5a5';
                    if (executeSQLBtn) executeSQLBtn.disabled = true;
                }
            } else {
                displayEl.textContent = 'None - Please select from Dashboard';
                displayEl.style.color = '#fca5a5';
                if (executeSQLBtn) executeSQLBtn.disabled = true;
            }
        }

        // ========================================
        // SQL EXECUTOR FUNCTIONS
        // ========================================

        // Switch between Database SQL tabs
        function switchDatabaseSQLTab(tabName) {
            const section = document.getElementById('generateDatabase');
            if (!section) return;

            section.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            
            document.getElementById('generateSQLTab').classList.remove('active');
            document.getElementById('sqlExecutorTab').classList.remove('active');
            document.getElementById('databaseMigrationTab').classList.remove('active');
            
            if (tabName === 'generate') {
                section.querySelector('.tab-btn:nth-child(1)').classList.add('active');
                document.getElementById('generateSQLTab').classList.add('active');
            } else if (tabName === 'executor') {
                section.querySelector('.tab-btn:nth-child(2)').classList.add('active');
                document.getElementById('sqlExecutorTab').classList.add('active');
                // Initialize autocomplete when switching to executor tab
                initSQLAutocomplete();
            } else if (tabName === 'migration') {
                section.querySelector('.tab-btn:nth-child(3)').classList.add('active');
                document.getElementById('databaseMigrationTab').classList.add('active');
                // Initialize migration tab
                initMigrationTab();
            }
        }

        // Execute SQL Query
        async function executeSQLQuery() {
            if (!selectedDatabaseId) {
                showMessage('generateDatabaseMessage', '❌ Please select a database first', 'error');
                return;
            }

            // Use getConnectionById to support BOTH Localhost and Hostinger
            const conn = getConnectionById(selectedDatabaseId);
            
            if (!conn) {
                showMessage('generateDatabaseMessage', '❌ Connection not found', 'error');
                return;
            }
            
            console.log('⚡ Executing SQL on:', conn.name, '| Type:', conn.type || 'hostinger');

            const sqlQuery = document.getElementById('customSQLInput').value.trim();
            
            if (!sqlQuery) {
                showMessage('generateDatabaseMessage', '❌ Please enter an SQL query', 'error');
                return;
            }

            showMessage('generateDatabaseMessage', '🔄 Executing SQL query...', 'info');

            const result = await apiRequest('execute_sql', {
                db_host: conn.host,
                db_name: conn.dbName,
                db_user: conn.username,
                db_pass: conn.password,
                db_port: conn.port,
                sql_query: sqlQuery
            });

            if (result.success) {
                showMessage('generateDatabaseMessage', `✅ ${result.message}`, 'success');
                displaySQLResults(result);
            } else {
                showMessage('generateDatabaseMessage', `❌ ${result.message}`, 'error');
                displaySQLError(result);
            }
        }

        // Display SQL results
        function displaySQLResults(result) {
            const container = document.getElementById('sqlResultsContainer');
            const messageEl = document.getElementById('sqlResultsMessage');
            const dataEl = document.getElementById('sqlResultsData');
            
            container.style.display = 'block';
            
            // Show success message
            messageEl.innerHTML = `
                <div class="message success" style="display: block; margin-bottom: 15px;">
                    ✅ ${result.message}<br>
                    <strong>Query Type:</strong> ${result.query_type}
                </div>
            `;

            // Display results based on query type
            if (result.results && result.results.length > 0) {
                // SELECT/SHOW/DESCRIBE results - display as table
                let html = '<div style="overflow-x: auto; background: rgba(0,0,0,0.2); border-radius: 8px; padding: 10px; max-height: 500px; overflow-y: auto;">';
                html += '<table class="data-table">';
                
                // Header
                html += '<thead><tr>';
                result.columns.forEach(col => {
                    html += `<th>${col}</th>`;
                });
                html += '</tr></thead>';
                
                // Body
                html += '<tbody>';
                result.results.forEach(row => {
                    html += '<tr>';
                    result.columns.forEach(col => {
                        const value = row[col];
                        const displayValue = value === null ? '<span style="color: #fca5a5; font-style: italic;">NULL</span>' : value;
                        html += `<td title="${value}">${displayValue}</td>`;
                    });
                    html += '</tr>';
                });
                html += '</tbody></table></div>';
                
                html += `<div style="margin-top: 10px; padding: 10px; background: rgba(59, 130, 246, 0.1); border-radius: 6px; font-size: 13px; color: #93c5fd;">
                    📊 <strong>${result.row_count} row(s)</strong> returned | <strong>${result.columns.length} column(s)</strong>
                </div>`;
                
                dataEl.innerHTML = html;
            } else if (result.affected_rows !== undefined) {
                // INSERT/UPDATE/DELETE results
                let html = '<div style="padding: 20px; background: rgba(34, 197, 94, 0.1); border: 1px solid #22c55e; border-radius: 8px;">';
                html += `<div style="font-size: 16px; color: #86efac; margin-bottom: 10px;"><strong>✅ Operation Completed</strong></div>`;
                html += `<div style="font-size: 14px; color: rgba(254, 243, 199, 0.9);">`;
                html += `📊 <strong>Affected Rows:</strong> ${result.affected_rows}<br>`;
                if (result.insert_id) {
                    html += `🔑 <strong>Last Insert ID:</strong> ${result.insert_id}`;
                }
                html += `</div></div>`;
                dataEl.innerHTML = html;
            } else {
                // DDL queries (CREATE, DROP, ALTER)
                dataEl.innerHTML = `
                    <div style="padding: 20px; background: rgba(34, 197, 94, 0.1); border: 1px solid #22c55e; border-radius: 8px; text-align: center;">
                        <div style="font-size: 48px; margin-bottom: 10px;">✅</div>
                        <div style="font-size: 16px; color: #86efac;"><strong>Query Executed Successfully</strong></div>
                        <div style="font-size: 13px; color: rgba(254, 243, 199, 0.7); margin-top: 5px;">No results to display (DDL operation)</div>
                    </div>
                `;
            }
        }

        // Display SQL error
        function displaySQLError(result) {
            const container = document.getElementById('sqlResultsContainer');
            const messageEl = document.getElementById('sqlResultsMessage');
            const dataEl = document.getElementById('sqlResultsData');
            
            container.style.display = 'block';
            
            messageEl.innerHTML = `
                <div class="message error" style="display: block; margin-bottom: 15px;">
                    ❌ ${result.message}
                </div>
            `;

            dataEl.innerHTML = `
                <div style="padding: 20px; background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; border-radius: 8px;">
                    <div style="font-size: 14px; color: #fca5a5; margin-bottom: 10px;"><strong>Error Details:</strong></div>
                    <div style="font-size: 13px; color: rgba(254, 243, 199, 0.9); font-family: monospace;">
                        ${result.message}
                    </div>
                    ${result.executed_query ? `
                        <div style="margin-top: 15px; padding: 10px; background: rgba(0,0,0,0.3); border-radius: 6px;">
                            <div style="font-size: 12px; color: rgba(254, 243, 199, 0.6); margin-bottom: 5px;">Executed Query:</div>
                            <div style="font-size: 13px; color: #86efac; font-family: monospace;">${result.executed_query}</div>
                        </div>
                    ` : ''}
                </div>
            `;
        }

        // Clear SQL input
        function clearSQLInput() {
            document.getElementById('customSQLInput').value = '';
            closeSQLResults();
        }

        // Close SQL results
        function closeSQLResults() {
            document.getElementById('sqlResultsContainer').style.display = 'none';
        }

        // Show/Hide SQL examples
        function showSQLExamples() {
            const container = document.getElementById('sqlExamplesContainer');
            if (container.style.display === 'none' || !container.style.display) {
                container.style.display = 'block';
            } else {
                container.style.display = 'none';
            }
        }

        // Insert SQL example into textarea
        function insertSQLExample(example) {
            document.getElementById('customSQLInput').value = example;
            document.getElementById('sqlExamplesContainer').style.display = 'none';
        }

        // ========================================
        // VISUAL QUERY BUILDER
        // ========================================

        let queryBuilderColumns = [];
        let queryBuilderTableData = null;

        // Toggle Visual Builder
        function toggleVisualBuilder() {
            const builder = document.getElementById('visualQueryBuilder');
            const toggleText = document.getElementById('builderToggleText');
            
            if (builder.style.display === 'none' || !builder.style.display) {
                builder.style.display = 'block';
                toggleText.textContent = 'Hide Visual Builder';
                loadTablesForQueryBuilder();
            } else {
                builder.style.display = 'none';
                toggleText.textContent = 'Show Visual Builder';
            }
        }

        // Load tables for query builder
        async function loadTablesForQueryBuilder() {
            if (!selectedDatabaseId) {
                return;
            }

            // Use getConnectionById to support BOTH Localhost and Hostinger
            const conn = getConnectionById(selectedDatabaseId);
            
            if (!conn) return;

            const result = await apiRequest('list_tables', {
                db_host: conn.host,
                db_name: conn.dbName,
                db_user: conn.username,
                db_pass: conn.password,
                db_port: conn.port
            });

            if (result.success && result.tables && result.tables.length > 0) {
                const select = document.getElementById('queryTable');
                select.innerHTML = '<option value="">-- Choose Table --</option>' + 
                    result.tables.map(t => `<option value="${t}">${t}</option>`).join('');
            }
        }

        // Load columns for selected table
        async function loadTableColumnsForQuery() {
            const tableName = document.getElementById('queryTable').value;
            
            if (!tableName || !selectedDatabaseId) {
                queryBuilderColumns = [];
                document.getElementById('tableRecordCount').textContent = '';
                updateQueryBuilder();
                return;
            }

            // Use getConnectionById to support BOTH Localhost and Hostinger
            const conn = getConnectionById(selectedDatabaseId);
            
            if (!conn) return;

            // Get table structure
            const structResult = await apiRequest('get_table_structure', {
                db_host: conn.host,
                db_name: conn.dbName,
                db_user: conn.username,
                db_pass: conn.password,
                db_port: conn.port,
                table_name: tableName
            });

            // Get record count
            const dataResult = await apiRequest('get_table_data', {
                db_host: conn.host,
                db_name: conn.dbName,
                db_user: conn.username,
                db_pass: conn.password,
                db_port: conn.port,
                table_name: tableName,
                page: 1,
                limit: 1
            });

            if (structResult.success) {
                queryBuilderColumns = structResult.columns;
                if (dataResult.success) {
                    queryBuilderTableData = dataResult;
                    document.getElementById('tableRecordCount').textContent = `📊 ${dataResult.pagination.total_rows.toLocaleString()} records in this table`;
                }
                updateQueryBuilder();
            }
        }

        // Update query builder based on operation
        function updateQueryBuilder() {
            const operation = document.getElementById('queryOperation').value;
            const tableName = document.getElementById('queryTable').value;
            const container = document.getElementById('queryOptionsContainer');

            if (!operation || !tableName || queryBuilderColumns.length === 0) {
                container.innerHTML = '';
                return;
            }

            let html = '';

            if (operation === 'SELECT') {
                html = buildSELECTOptions();
            } else if (operation === 'INSERT') {
                html = buildINSERTOptions();
            } else if (operation === 'UPDATE') {
                html = buildUPDATEOptions();
            } else if (operation === 'DELETE') {
                html = buildDELETEOptions();
            }

            container.innerHTML = html;
        }

        // Build SELECT options
        function buildSELECTOptions() {
            let html = '<div style="background: rgba(59, 130, 246, 0.1); padding: 15px; border-radius: 8px; margin-top: 15px;">';
            
            // Select columns
            html += '<div class="form-group">';
            html += '<label class="form-label">3️⃣ Select Columns</label>';
            html += '<div style="max-height: 200px; overflow-y: auto; background: rgba(0,0,0,0.2); padding: 10px; border-radius: 6px;">';
            html += '<div class="checkbox-group" style="margin-bottom: 10px;"><input type="checkbox" id="selectAllCols" onchange="toggleAllColumns(this.checked)"><label for="selectAllCols" style="margin-left: 8px; color: #22c55e; font-weight: bold;">✨ SELECT * (All Columns)</label></div>';
            
            queryBuilderColumns.forEach((col, idx) => {
                html += `<div class="checkbox-group" style="margin-bottom: 8px;">`;
                html += `<input type="checkbox" id="col_${idx}" class="query-column-check" value="${col.Field}">`;
                html += `<label for="col_${idx}" style="margin-left: 8px;">${col.Field} <span style="color: rgba(254, 243, 199, 0.6); font-size: 12px;">(${col.Type})</span></label>`;
                html += `</div>`;
            });
            html += '</div></div>';

            // WHERE clause
            html += '<div class="form-group">';
            html += '<label class="form-label">4️⃣ WHERE Condition (Optional)</label>';
            html += '<div id="whereConditions"></div>';
            html += '<button type="button" class="btn btn-secondary" onclick="addWhereCondition()" style="padding: 8px 16px; font-size: 13px; margin-top: 10px;">➕ Add Condition</button>';
            html += '</div>';

            // ORDER BY
            html += '<div class="form-group">';
            html += '<label class="form-label">5️⃣ ORDER BY (Optional)</label>';
            html += '<div style="display: flex; gap: 10px;">';
            html += '<select id="orderByColumn" class="form-select" style="flex: 2;"><option value="">-- No sorting --</option>';
            queryBuilderColumns.forEach(col => {
                html += `<option value="${col.Field}">${col.Field}</option>`;
            });
            html += '</select>';
            html += '<select id="orderByDirection" class="form-select" style="flex: 1;"><option value="ASC">⬆️ ASC</option><option value="DESC">⬇️ DESC</option></select>';
            html += '</div></div>';

            // LIMIT
            html += '<div class="form-group">';
            html += '<label class="form-label">6️⃣ LIMIT (Optional)</label>';
            html += '<input type="number" id="queryLimit" class="form-input" placeholder="e.g., 10, 100, 1000" min="1">';
            html += '</div>';

            html += '</div>';
            
            // Auto-add first WHERE condition
            setTimeout(() => addWhereCondition(), 100);
            
            return html;
        }

        // Build INSERT options
        function buildINSERTOptions() {
            let html = '<div style="background: rgba(34, 197, 94, 0.1); padding: 15px; border-radius: 8px; margin-top: 15px;">';
            html += '<div class="form-group"><label class="form-label">3️⃣ Enter Values for Each Column</label></div>';
            
            queryBuilderColumns.forEach(col => {
                const isAutoIncrement = col.Extra && col.Extra.toLowerCase().includes('auto_increment');
                if (isAutoIncrement) return; // Skip auto-increment
                
                html += `<div class="form-group">`;
                html += `<label class="form-label">${col.Field} ${col.Null === 'NO' ? '<span style="color: #ef4444;">*</span>' : ''}</label>`;
                html += `<input type="text" id="insert_${col.Field}" class="form-input" placeholder="${col.Type}" ${col.Null === 'NO' ? 'required' : ''}>`;
                html += `<div class="helper-text">${col.Type}${col.Null === 'YES' ? ' (Optional - leave empty for NULL)' : ' (Required)'}</div>`;
                html += `</div>`;
            });
            
            html += '</div>';
            return html;
        }

        // Build UPDATE options
        function buildUPDATEOptions() {
            let html = '<div style="background: rgba(245, 158, 11, 0.1); padding: 15px; border-radius: 8px; margin-top: 15px;">';
            
            // SET clause
            html += '<div class="form-group">';
            html += '<label class="form-label">3️⃣ SET (Columns to Update)</label>';
            html += '<div id="updateSetFields"></div>';
            html += '<button type="button" class="btn btn-secondary" onclick="addUpdateField()" style="padding: 8px 16px; font-size: 13px; margin-top: 10px;">➕ Add Field to Update</button>';
            html += '</div>';

            // WHERE clause
            html += '<div class="form-group">';
            html += '<label class="form-label">4️⃣ WHERE Condition (Required for safety)</label>';
            html += '<div id="updateWhereConditions"></div>';
            html += '<button type="button" class="btn btn-secondary" onclick="addUpdateWhereCondition()" style="padding: 8px 16px; font-size: 13px; margin-top: 10px;">➕ Add Condition</button>';
            html += '</div>';

            html += '</div>';
            
            setTimeout(() => {
                addUpdateField();
                addUpdateWhereCondition();
            }, 100);
            
            return html;
        }

        // Build DELETE options  
        function buildDELETEOptions() {
            let html = '<div style="background: rgba(239, 68, 68, 0.1); padding: 15px; border-radius: 8px; margin-top: 15px;">';
            
            html += '<div style="background: rgba(245, 158, 11, 0.2); border: 1px solid #f59e0b; border-radius: 6px; padding: 10px; margin-bottom: 15px; font-size: 13px;">';
            html += '<strong>⚠️ Warning:</strong> DELETE is permanent! Always use WHERE condition to avoid deleting all records.';
            html += '</div>';

            // WHERE clause
            html += '<div class="form-group">';
            html += '<label class="form-label">3️⃣ WHERE Condition (REQUIRED)</label>';
            html += '<div id="deleteWhereConditions"></div>';
            html += '<button type="button" class="btn btn-secondary" onclick="addDeleteWhereCondition()" style="padding: 8px 16px; font-size: 13px; margin-top: 10px;">➕ Add Condition</button>';
            html += '</div>';

            // LIMIT (safety)
            html += '<div class="form-group">';
            html += '<label class="form-label">4️⃣ LIMIT (Safety limit)</label>';
            html += '<input type="number" id="deleteLimit" class="form-input" placeholder="e.g., 1, 10, 100" min="1" value="1">';
            html += '<div class="helper-text">⚠️ Recommended: Set a limit to prevent accidental mass deletion</div>';
            html += '</div>';

            html += '</div>';
            
            setTimeout(() => addDeleteWhereCondition(), 100);
            
            return html;
        }

        // Add WHERE condition for SELECT
        function addWhereCondition() {
            const container = document.getElementById('whereConditions');
            if (!container) return;

            const conditionId = 'where_' + Date.now();
            const html = `
                <div id="${conditionId}" style="display: flex; gap: 10px; margin-bottom: 10px; align-items: end;">
                    <select class="form-select where-column" style="flex: 2;">
                        <option value="">-- Column --</option>
                        ${queryBuilderColumns.map(c => `<option value="${c.Field}">${c.Field}</option>`).join('')}
                    </select>
                    <select class="form-select where-operator" style="flex: 1;">
                        <option value="=">=</option>
                        <option value="!=">!=</option>
                        <option value=">">></option>
                        <option value="<"><</option>
                        <option value=">=">>=</option>
                        <option value="<="><=</option>
                        <option value="LIKE">LIKE</option>
                        <option value="NOT LIKE">NOT LIKE</option>
                        <option value="IN">IN</option>
                        <option value="IS NULL">IS NULL</option>
                        <option value="IS NOT NULL">IS NOT NULL</option>
                    </select>
                    <input type="text" class="form-input where-value" placeholder="Value" style="flex: 2;">
                    <select class="form-select where-logic" style="flex: 1;">
                        <option value="AND">AND</option>
                        <option value="OR">OR</option>
                    </select>
                    <button type="button" class="btn btn-danger btn-icon" onclick="document.getElementById('${conditionId}').remove()">×</button>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
        }

        // Add UPDATE field
        function addUpdateField() {
            const container = document.getElementById('updateSetFields');
            if (!container) return;

            const fieldId = 'update_' + Date.now();
            const html = `
                <div id="${fieldId}" style="display: flex; gap: 10px; margin-bottom: 10px; align-items: end;">
                    <select class="form-select update-column" style="flex: 1;">
                        <option value="">-- Column --</option>
                        ${queryBuilderColumns.map(c => `<option value="${c.Field}">${c.Field} (${c.Type})</option>`).join('')}
                    </select>
                    <input type="text" class="form-input update-value" placeholder="New Value" style="flex: 2;">
                    <button type="button" class="btn btn-danger btn-icon" onclick="document.getElementById('${fieldId}').remove()">×</button>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
        }

        // Add UPDATE WHERE condition
        function addUpdateWhereCondition() {
            const container = document.getElementById('updateWhereConditions');
            if (!container) return;

            const conditionId = 'upwhere_' + Date.now();
            const html = `
                <div id="${conditionId}" style="display: flex; gap: 10px; margin-bottom: 10px; align-items: end;">
                    <select class="form-select update-where-column" style="flex: 2;">
                        <option value="">-- Column --</option>
                        ${queryBuilderColumns.map(c => `<option value="${c.Field}">${c.Field}</option>`).join('')}
                    </select>
                    <select class="form-select update-where-operator" style="flex: 1;">
                        <option value="=">=</option>
                        <option value="!=">!=</option>
                        <option value=">">></option>
                        <option value="<"><</option>
                        <option value="LIKE">LIKE</option>
                    </select>
                    <input type="text" class="form-input update-where-value" placeholder="Value" style="flex: 2;">
                    <select class="form-select update-where-logic" style="flex: 1;">
                        <option value="AND">AND</option>
                        <option value="OR">OR</option>
                    </select>
                    <button type="button" class="btn btn-danger btn-icon" onclick="document.getElementById('${conditionId}').remove()">×</button>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
        }

        // Add DELETE WHERE condition
        function addDeleteWhereCondition() {
            const container = document.getElementById('deleteWhereConditions');
            if (!container) return;

            const conditionId = 'delwhere_' + Date.now();
            const html = `
                <div id="${conditionId}" style="display: flex; gap: 10px; margin-bottom: 10px; align-items: end;">
                    <select class="form-select delete-where-column" style="flex: 2;">
                        <option value="">-- Column --</option>
                        ${queryBuilderColumns.map(c => `<option value="${c.Field}">${c.Field}</option>`).join('')}
                    </select>
                    <select class="form-select delete-where-operator" style="flex: 1;">
                        <option value="=">=</option>
                        <option value="!=">!=</option>
                        <option value=">">></option>
                        <option value="<"><</option>
                        <option value="LIKE">LIKE</option>
                    </select>
                    <input type="text" class="form-input delete-where-value" placeholder="Value" style="flex: 2;">
                    <select class="form-select delete-where-logic" style="flex: 1;">
                        <option value="AND">AND</option>
                        <option value="OR">OR</option>
                    </select>
                    <button type="button" class="btn btn-danger btn-icon" onclick="document.getElementById('${conditionId}').remove()">×</button>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
        }

        // Toggle all columns
        function toggleAllColumns(checked) {
            document.querySelectorAll('.query-column-check').forEach(cb => {
                cb.checked = checked;
            });
        }

        // Build SQL query from visual inputs
        function buildQueryFromVisual() {
            const operation = document.getElementById('queryOperation').value;
            const tableName = document.getElementById('queryTable').value;

            if (!operation || !tableName) {
                showMessage('generateDatabaseMessage', '❌ Please select operation and table', 'error');
                return;
            }

            let sql = '';

            if (operation === 'SELECT') {
                sql = buildSELECTQuery(tableName);
            } else if (operation === 'INSERT') {
                sql = buildINSERTQuery(tableName);
            } else if (operation === 'UPDATE') {
                sql = buildUPDATEQuery(tableName);
            } else if (operation === 'DELETE') {
                sql = buildDELETEQuery(tableName);
            }

            if (sql) {
                document.getElementById('customSQLInput').value = sql;
                showMessage('generateDatabaseMessage', '✅ SQL query built successfully! Review and execute below.', 'success');
                // Scroll to SQL textarea
                document.getElementById('customSQLInput').scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        // Build SELECT query
        function buildSELECTQuery(tableName) {
            // Get selected columns
            const selectAll = document.getElementById('selectAllCols').checked;
            let columns = '*';
            
            if (!selectAll) {
                const selectedCols = Array.from(document.querySelectorAll('.query-column-check:checked')).map(cb => cb.value);
                if (selectedCols.length === 0) {
                    showMessage('generateDatabaseMessage', '❌ Please select at least one column or use SELECT *', 'error');
                    return null;
                }
                columns = selectedCols.map(c => `\`${c}\``).join(', ');
            }

            let sql = `SELECT ${columns} FROM \`${tableName}\``;

            // WHERE clause
            const whereClauses = buildWhereClause('.where-column', '.where-operator', '.where-value', '.where-logic');
            if (whereClauses) {
                sql += ` WHERE ${whereClauses}`;
            }

            // ORDER BY
            const orderBy = document.getElementById('orderByColumn').value;
            if (orderBy) {
                const direction = document.getElementById('orderByDirection').value;
                sql += ` ORDER BY \`${orderBy}\` ${direction}`;
            }

            // LIMIT
            const limit = document.getElementById('queryLimit').value;
            if (limit) {
                sql += ` LIMIT ${limit}`;
            }

            sql += ';';
            return sql;
        }

        // Build INSERT query
        function buildINSERTQuery(tableName) {
            const columns = [];
            const values = [];

            queryBuilderColumns.forEach(col => {
                const isAutoIncrement = col.Extra && col.Extra.toLowerCase().includes('auto_increment');
                if (isAutoIncrement) return;

                const input = document.getElementById(`insert_${col.Field}`);
                if (input) {
                    const value = input.value.trim();
                    if (value) {
                        columns.push(`\`${col.Field}\``);
                        values.push(`'${value.replace(/'/g, "\\'")}'`);
                    } else if (col.Null === 'NO') {
                        showMessage('generateDatabaseMessage', `❌ ${col.Field} is required (NOT NULL)`, 'error');
                        return null;
                    }
                }
            });

            if (columns.length === 0) {
                showMessage('generateDatabaseMessage', '❌ Please fill at least one field', 'error');
                return null;
            }

            return `INSERT INTO \`${tableName}\` (${columns.join(', ')}) VALUES (${values.join(', ')});`;
        }

        // Build UPDATE query
        function buildUPDATEQuery(tableName) {
            // Get SET fields
            const setColumns = document.querySelectorAll('.update-column');
            const setValues = document.querySelectorAll('.update-value');
            const setParts = [];

            for (let i = 0; i < setColumns.length; i++) {
                const col = setColumns[i].value;
                const val = setValues[i].value.trim();
                if (col && val) {
                    setParts.push(`\`${col}\` = '${val.replace(/'/g, "\\'")}'`);
                }
            }

            if (setParts.length === 0) {
                showMessage('generateDatabaseMessage', '❌ Please specify at least one field to update', 'error');
                return null;
            }

            let sql = `UPDATE \`${tableName}\` SET ${setParts.join(', ')}`;

            // WHERE clause
            const whereClauses = buildWhereClause('.update-where-column', '.update-where-operator', '.update-where-value', '.update-where-logic');
            if (whereClauses) {
                sql += ` WHERE ${whereClauses}`;
            } else {
                showMessage('generateDatabaseMessage', '⚠️ No WHERE clause! This will update ALL records. Add condition for safety.', 'error');
                return null;
            }

            sql += ';';
            return sql;
        }

        // Build DELETE query
        function buildDELETEQuery(tableName) {
            let sql = `DELETE FROM \`${tableName}\``;

            // WHERE clause
            const whereClauses = buildWhereClause('.delete-where-column', '.delete-where-operator', '.delete-where-value', '.delete-where-logic');
            if (whereClauses) {
                sql += ` WHERE ${whereClauses}`;
            } else {
                showMessage('generateDatabaseMessage', '⚠️ No WHERE clause! This will delete ALL records. Add condition for safety.', 'error');
                return null;
            }

            // LIMIT
            const limit = document.getElementById('deleteLimit').value;
            if (limit) {
                sql += ` LIMIT ${limit}`;
            }

            sql += ';';
            return sql;
        }

        // Build WHERE clause helper
        function buildWhereClause(colSelector, opSelector, valSelector, logicSelector) {
            const columns = document.querySelectorAll(colSelector);
            const operators = document.querySelectorAll(opSelector);
            const values = document.querySelectorAll(valSelector);
            const logics = document.querySelectorAll(logicSelector);

            const conditions = [];

            for (let i = 0; i < columns.length; i++) {
                const col = columns[i].value;
                const op = operators[i].value;
                const val = values[i].value.trim();

                if (col && op) {
                    let condition = `\`${col}\` ${op}`;
                    
                    if (op === 'IS NULL' || op === 'IS NOT NULL') {
                        // No value needed
                    } else if (op === 'IN') {
                        condition += ` (${val})`;
                    } else if (val) {
                        if (op === 'LIKE' || op === 'NOT LIKE') {
                            condition += ` '%${val.replace(/'/g, "\\'")}%'`;
                        } else {
                            condition += ` '${val.replace(/'/g, "\\'")}'`;
                        }
                    }

                    if (i < columns.length - 1 && logics[i]) {
                        condition += ` ${logics[i].value}`;
                    }

                    conditions.push(condition);
                }
            }

            return conditions.join(' ');
        }

        // Reset query builder
        function resetQueryBuilder() {
            document.getElementById('queryOperation').value = '';
            document.getElementById('queryTable').value = '';
            document.getElementById('queryOptionsContainer').innerHTML = '';
            document.getElementById('tableRecordCount').textContent = '';
            queryBuilderColumns = [];
            queryBuilderTableData = null;
        }

        // ========================================
        // SQL GHOST TEXT AUTOCOMPLETE
        // ========================================

        /*
         * SQL Autocomplete with Ghost Text (Inline suggestions like GitHub Copilot)
         * 
         * Configuration:
         * - window.WEBLLM_MODEL = "model-name" (optional, for WebLLM)
         * - window.AI_COMPLETE_ENDPOINT = "url" (optional, for remote LLM API)
         * - Set enableLLM: false to disable LLM features
         * 
         * Usage:
         * - Type SQL and ghost text appears automatically
         * - Press Tab to accept suggestion
         * - Press Esc to dismiss
         * - Press Ctrl+Space to manually request suggestion
         */

        let sqlACState = {
            currentSuggestion: '',
            debounceTimer: null,
            tablesCache: [],
            columnsCache: {},
            keywords: [
                'SELECT', 'FROM', 'WHERE', 'AND', 'OR', 'NOT', 'IN', 'BETWEEN', 'LIKE', 'IS', 'NULL',
                'INSERT', 'INTO', 'VALUES', 'UPDATE', 'SET', 'DELETE', 'JOIN', 'LEFT JOIN', 'RIGHT JOIN',
                'INNER JOIN', 'OUTER JOIN', 'CROSS JOIN', 'ON', 'USING', 'ORDER BY', 'GROUP BY', 
                'HAVING', 'LIMIT', 'OFFSET', 'AS', 'DISTINCT', 'COUNT', 'SUM', 'AVG', 'MAX', 'MIN', 
                'ASC', 'DESC', 'CREATE TABLE', 'ALTER TABLE', 'DROP TABLE', 'TRUNCATE', 'SHOW TABLES', 
                'DESCRIBE', 'DESC', 'EXPLAIN', 'UNION', 'UNION ALL', 'WITH', 'CASE', 'WHEN', 'THEN',
                'ELSE', 'END', 'IF', 'EXISTS', 'PRIMARY KEY', 'FOREIGN KEY', 'REFERENCES', 'INDEX',
                'AUTO_INCREMENT', 'DEFAULT', 'CASCADE', 'RESTRICT'
            ],
            templates: {
                'sel': 'SELECT * FROM table_name;',
                'select': 'SELECT column1, column2 FROM table_name WHERE condition;',
                'ins': 'INSERT INTO table_name (column1, column2) VALUES (value1, value2);',
                'insert': 'INSERT INTO table_name (column1, column2) VALUES (value1, value2);',
                'upd': 'UPDATE table_name SET column1 = value1 WHERE condition;',
                'update': 'UPDATE table_name SET column1 = value1 WHERE condition;',
                'del': 'DELETE FROM table_name WHERE condition LIMIT 1;',
                'delete': 'DELETE FROM table_name WHERE condition LIMIT 1;',
                'create': 'CREATE TABLE table_name (\n  id INT PRIMARY KEY AUTO_INCREMENT,\n  column_name VARCHAR(255)\n);',
                'alter': 'ALTER TABLE table_name ADD COLUMN column_name VARCHAR(255);',
                'drop': 'DROP TABLE IF EXISTS table_name;',
                'show': 'SHOW TABLES;',
                'desc': 'DESCRIBE table_name;'
            }
        };

        // Load schema data (tables and columns)
        async function loadSQLAutocompleteData() {
            if (!selectedDatabaseId) return;

            // Use getConnectionById to support BOTH Localhost and Hostinger
            const conn = getConnectionById(selectedDatabaseId);
            
            if (!conn) return;

            // Get tables
            const tablesResult = await apiRequest('list_tables', {
                db_host: conn.host,
                db_name: conn.dbName,
                db_user: conn.username,
                db_pass: conn.password,
                db_port: conn.port
            });

            if (tablesResult.success && tablesResult.tables) {
                sqlACState.tablesCache = tablesResult.tables;
                
                // Get columns for each table
                for (const table of sqlACState.tablesCache.slice(0, 15)) {
                    const structResult = await apiRequest('get_table_structure', {
                        db_host: conn.host,
                        db_name: conn.dbName,
                        db_user: conn.username,
                        db_pass: conn.password,
                        db_port: conn.port,
                        table_name: table
                    });
                    
                    if (structResult.success && structResult.columns) {
                        sqlACState.columnsCache[table] = structResult.columns.map(c => c.Field);
                    }
                }
            }
        }

        // Handle keyboard events for ghost autocomplete
        function handleSQLAutocompleteKeydown(event) {
            const textarea = document.getElementById('customSQLInput');
            const hasSuggestion = sqlACState.currentSuggestion.length > 0;

            // Tab - Accept ghost suggestion
            if (event.key === 'Tab' && hasSuggestion) {
                event.preventDefault();
                acceptGhostSuggestion();
                return;
            }

            // Esc - Clear ghost suggestion
            if (event.key === 'Escape' && hasSuggestion) {
                event.preventDefault();
                clearGhostSuggestion();
                return;
            }

            // Ctrl+Space - Force request suggestion
            if (event.ctrlKey && event.code === 'Space') {
                event.preventDefault();
                requestGhostSuggestion(true);
                return;
            }
        }

        // Handle input (debounced suggestion)
        function handleSQLAutocompleteInput() {
            if (sqlACState.debounceTimer) {
                clearTimeout(sqlACState.debounceTimer);
            }

            sqlACState.debounceTimer = setTimeout(() => {
                requestGhostSuggestion(false);
            }, 300);
        }

        // Handle focus
        function handleSQLAutocompleteFocus() {
            // Could re-enable ghost if needed
        }

        // Handle blur
        function handleSQLAutocompleteBlur() {
            // Hide ghost on blur
            setTimeout(() => clearGhostSuggestion(), 200);
        }

        // Sync ghost layer scroll with textarea
        function syncGhostScroll() {
            const textarea = document.getElementById('customSQLInput');
            const ghost = document.getElementById('sqlGhostLayer');
            if (ghost && textarea) {
                ghost.scrollTop = textarea.scrollTop;
                ghost.scrollLeft = textarea.scrollLeft;
            }
        }

        // Request ghost suggestion
        async function requestGhostSuggestion(forced) {
            const textarea = document.getElementById('customSQLInput');
            if (!textarea) return;

            const text = textarea.value;
            const cursorPos = textarea.selectionStart;
            const textBeforeCursor = text.substring(0, cursorPos);
            
            // Don't suggest if cursor is not at end of text
            if (cursorPos < text.length && !forced) {
                clearGhostSuggestion();
                return;
            }

            // Get current context
            const lines = textBeforeCursor.split('\n');
            const currentLine = lines[lines.length - 1];
            const words = currentLine.trim().split(/\s+/);
            const currentWord = words[words.length - 1].toLowerCase();
            const previousWord = words.length > 1 ? words[words.length - 2].toUpperCase() : '';

            let suggestion = '';

            // Template suggestions (at start of line)
            if (currentLine.trim() === currentWord && currentWord.length >= 2) {
                const template = sqlACState.templates[currentWord];
                if (template) {
                    suggestion = template.substring(currentWord.length);
                }
            }
            // After FROM/JOIN - suggest table names
            else if (previousWord === 'FROM' || previousWord === 'JOIN' || previousWord === 'INTO' || previousWord === 'UPDATE') {
                const matchingTable = sqlACState.tablesCache.find(t => 
                    t.toLowerCase().startsWith(currentWord) && t.toLowerCase() !== currentWord
                );
                if (matchingTable) {
                    suggestion = matchingTable.substring(currentWord.length);
                } else if (sqlACState.tablesCache.length > 0 && currentWord.length === 0) {
                    suggestion = sqlACState.tablesCache[0];
                }
            }
            // After table.column - suggest columns
            else if (currentLine.match(/\w+\.$/)) {
                const match = currentLine.match(/(\w+)\.$/);
                const tableName = match[1];
                if (sqlACState.columnsCache[tableName] && sqlACState.columnsCache[tableName].length > 0) {
                    suggestion = sqlACState.columnsCache[tableName][0];
                }
            }
            // Keyword completion
            else if (currentWord.length >= 2) {
                const matchingKeyword = sqlACState.keywords.find(kw => 
                    kw.toLowerCase().startsWith(currentWord) && kw.toLowerCase() !== currentWord
                );
                if (matchingKeyword) {
                    suggestion = matchingKeyword.substring(currentWord.length);
                }
            }

            // Update ghost suggestion
            if (suggestion) {
                sqlACState.currentSuggestion = suggestion;
                updateGhostLayer(textBeforeCursor, suggestion);
            } else {
                clearGhostSuggestion();
            }
        }

        // Update ghost layer
        function updateGhostLayer(prefix, suggestion) {
            const ghost = document.getElementById('sqlGhostLayer');
            if (!ghost) return;

            // Display: prefix (transparent) + suggestion (ghosted)
            ghost.innerHTML = prefix + `<span class="sql-ac-ghost-text">${suggestion}</span>`;
            ghost.style.display = 'block';
        }

        // Clear ghost suggestion
        function clearGhostSuggestion() {
            sqlACState.currentSuggestion = '';
            const ghost = document.getElementById('sqlGhostLayer');
            if (ghost) {
                ghost.innerHTML = '';
                ghost.style.display = 'none';
            }
        }

        // Accept ghost suggestion (Tab key)
        function acceptGhostSuggestion() {
            const textarea = document.getElementById('customSQLInput');
            if (!textarea || !sqlACState.currentSuggestion) return;

            const cursorPos = textarea.selectionStart;
            const text = textarea.value;
            
            // Insert suggestion at cursor
            const newText = text.substring(0, cursorPos) + sqlACState.currentSuggestion + text.substring(cursorPos);
            textarea.value = newText;
            
            // Move cursor to end of inserted text
            const newCursorPos = cursorPos + sqlACState.currentSuggestion.length;
            textarea.setSelectionRange(newCursorPos, newCursorPos);
            
            clearGhostSuggestion();
            textarea.focus();
        }

        // Initialize SQL autocomplete when entering SQL Executor tab
        function initSQLAutocomplete() {
            if (selectedDatabaseId) {
                loadSQLAutocompleteData();
            }
            
            // Initialize ghost layer on first load
            setTimeout(() => {
                const textarea = document.getElementById('customSQLInput');
                const ghost = document.getElementById('sqlGhostLayer');
                if (textarea && ghost) {
                    // Make sure ghost matches textarea styling
                    const styles = window.getComputedStyle(textarea);
                    ghost.style.fontFamily = styles.fontFamily;
                    ghost.style.fontSize = styles.fontSize;
                    ghost.style.lineHeight = styles.lineHeight;
                    ghost.style.padding = styles.padding;
                }
            }, 100);
        }

        // ========================================
        // DATABASE MIGRATION FUNCTIONS
        // ========================================

        // Initialize migration tab
        function initMigrationTab() {
            // Update source database display
            if (selectedDatabaseId) {
                // Use getConnectionById to support BOTH Localhost and Hostinger
                const conn = getConnectionById(selectedDatabaseId);
                
                if (conn) {
                    document.getElementById('migrationSourceDb').textContent = `${conn.name} (${conn.dbName})`;
                    document.getElementById('migrationSourceInfo').textContent = `Host: ${conn.host} | Type: ${conn.type || 'hostinger'}`;
                    
                    // Load destination options (all connections except source)
                    const destSelect = document.getElementById('migrationDestinationDb');
                    destSelect.innerHTML = '<option value="">-- Select Destination Database --</option>';
                    
                    // Get all connections (both Hostinger and Localhost)
                    const hostingerConns = getHostingerConnections();
                    const localhostDbs = getLocalhostDatabases();
                    
                    // Add Hostinger connections
                    hostingerConns.forEach(c => {
                        if (`conn_${c.id}` !== selectedDatabaseId) {
                            destSelect.innerHTML += `<option value="conn_${c.id}">${c.name} (${c.dbName}) - ${c.host}</option>`;
                        }
                    });
                    
                    // Add Localhost databases
                    localhostDbs.forEach(dbName => {
                        const localhostId = `localhost_${dbName}`;
                        if (localhostId !== selectedDatabaseId) {
                            destSelect.innerHTML += `<option value="${localhostId}">🖥️ ${dbName} (Localhost)</option>`;
                        }
                    });
                } else {
                    document.getElementById('migrationSourceDb').textContent = 'None selected';
                    document.getElementById('migrationSourceInfo').textContent = 'Please select a database from Dashboard';
                }
            } else {
                document.getElementById('migrationSourceDb').textContent = 'None selected';
                document.getElementById('migrationSourceInfo').textContent = 'Please select a database from Dashboard';
            }
        }

        // Update destination info
        function updateMigrationDestinationInfo() {
            const destId = document.getElementById('migrationDestinationDb').value;
            const infoEl = document.getElementById('migrationDestInfo');
            const startBtn = document.getElementById('startMigrationBtn');
            
            if (destId) {
                // Use getConnectionById to support BOTH Localhost and Hostinger
                const conn = getConnectionById(destId);
                if (conn) {
                    infoEl.textContent = `📥 Destination: ${conn.host} | ${conn.type || 'hostinger'} | ${conn.dbName}`;
                    infoEl.style.color = '#22c55e';
                    if (startBtn) startBtn.disabled = false;
                }
            } else {
                infoEl.textContent = 'Choose where to migrate the data';
                infoEl.style.color = '';
                if (startBtn) startBtn.disabled = true;
            }
        }

        // Select migration type
        function selectMigrationType(type) {
            document.getElementById('migrationOverwrite').checked = (type === 'overwrite');
            document.getElementById('migrationAppend').checked = (type === 'append');
            
            // Visual feedback
            const overwriteCard = document.getElementById('migrationType_overwrite');
            const appendCard = document.getElementById('migrationType_append');
            
            if (type === 'overwrite') {
                overwriteCard.style.borderColor = '#ef4444';
                overwriteCard.style.background = 'rgba(239, 68, 68, 0.2)';
                appendCard.style.borderColor = 'rgba(34, 197, 94, 0.3)';
                appendCard.style.background = 'rgba(34, 197, 94, 0.1)';
            } else {
                appendCard.style.borderColor = '#22c55e';
                appendCard.style.background = 'rgba(34, 197, 94, 0.2)';
                overwriteCard.style.borderColor = '#ef4444';
                overwriteCard.style.background = 'rgba(239, 68, 68, 0.1)';
            }
        }

        // Show/Hide migration options
        function showMigrationOptions() {
            const optionsDiv = document.getElementById('migrationOptions');
            if (optionsDiv.style.display === 'none' || !optionsDiv.style.display) {
                optionsDiv.style.display = 'block';
            } else {
                optionsDiv.style.display = 'none';
            }
        }

        // Migration state
        let migrationCancelled = false;

        // Cancel migration
        function cancelMigration() {
            if (confirm('⛔ Are you sure you want to cancel the migration?\n\nTables already migrated will remain in the destination.')) {
                migrationCancelled = true;
                document.getElementById('cancelMigrationBtn').disabled = true;
                showMessage('generateDatabaseMessage', '⛔ Migration cancellation requested...', 'info');
            }
        }

        // Start database migration
        async function startDatabaseMigration() {
            const sourceId = selectedDatabaseId;
            const destId = document.getElementById('migrationDestinationDb').value;
            const migrationType = document.querySelector('input[name="migrationType"]:checked')?.value;

            if (!sourceId || !destId) {
                showMessage('generateDatabaseMessage', '❌ Please select both source and destination databases', 'error');
                return;
            }

            if (!migrationType) {
                showMessage('generateDatabaseMessage', '❌ Please select migration type (Overwrite or Append)', 'error');
                return;
            }

            // Use getConnectionById to support BOTH Localhost and Hostinger
            const sourceConn = getConnectionById(sourceId);
            const destConn = getConnectionById(destId);

            if (!sourceConn || !destConn) {
                showMessage('generateDatabaseMessage', '❌ Connection not found', 'error');
                return;
            }
            
            console.log('🔄 Migration:', sourceConn.name, '→', destConn.name);

            // Confirmation
            const confirmMsg = migrationType === 'overwrite' 
                ? `⚠️ OVERWRITE Migration\n\nThis will DELETE ALL tables in:\n"${destConn.name} (${destConn.dbName})"\n\nAnd replace with tables from:\n"${sourceConn.name} (${sourceConn.dbName})"\n\nAll existing data in destination will be LOST!\n\nAre you absolutely sure?`
                : `✅ APPEND Migration\n\nThis will ADD tables and data from:\n"${sourceConn.name} (${sourceConn.dbName})"\n\nTo:\n"${destConn.name} (${destConn.dbName})"\n\nExisting data will be preserved.\n\nContinue?`;

            if (!confirm(confirmMsg)) {
                return;
            }

            // Reset cancel flag
            migrationCancelled = false;

            // Show progress and cancel button
            document.getElementById('migrationProgress').style.display = 'block';
            document.getElementById('migrationResults').style.display = 'none';
            document.getElementById('startMigrationBtn').style.display = 'none';
            document.getElementById('cancelMigrationBtn').style.display = 'inline-flex';
            document.getElementById('cancelMigrationBtn').disabled = false;
            
            updateMigrationProgress(0, 'Starting migration...');

            const migrationLog = [];

            try {
                // Get source tables
                updateMigrationProgress(5, 'Fetching source tables...');
                const tablesResult = await apiRequest('list_tables', {
                    db_host: sourceConn.host,
                    db_name: sourceConn.dbName,
                    db_user: sourceConn.username,
                    db_pass: sourceConn.password,
                    db_port: sourceConn.port
                });

                if (!tablesResult.success) {
                    throw new Error(`Failed to get source tables: ${tablesResult.message}`);
                }

                const tables = tablesResult.tables;
                const totalTables = tables.length;
                
                if (totalTables === 0) {
                    throw new Error('Source database has no tables to migrate');
                }

                let migratedTables = 0;
                let totalRecords = 0;
                let failedTables = [];
                const includeData = document.getElementById('migrationIncludeData').checked;

                // Migrate each table
                for (let i = 0; i < tables.length; i++) {
                    // Check if migration was cancelled
                    if (migrationCancelled) {
                        migrationLog.push(`\n⛔ Migration cancelled by user at table ${i + 1}/${totalTables}`);
                        throw new Error(`Migration cancelled by user. ${migratedTables} tables migrated before cancellation.`);
                    }

                    const tableName = tables[i];
                    const progress = 5 + ((i + 1) / totalTables) * 90; // 5-95%
                    
                    updateMigrationProgress(progress, `Migrating table ${i + 1}/${totalTables}: ${tableName}`);
                    migrationLog.push(`\n📋 Processing: ${tableName}`);

                    try {
                        // Step 1: Get table SQL from source
                        migrationLog.push(`  - Generating SQL from source...`);
                        const sqlResult = await apiRequest('generate_table_sql', {
                            db_host: sourceConn.host,
                            db_name: sourceConn.dbName,
                            db_user: sourceConn.username,
                            db_pass: sourceConn.password,
                            db_port: sourceConn.port,
                            table_name: tableName,
                            include_data: includeData ? 'true' : 'false'
                        });

                        if (!sqlResult.success) {
                            migrationLog.push(`  ❌ Failed to generate SQL: ${sqlResult.message}`);
                            failedTables.push({ table: tableName, error: sqlResult.message });
                            continue;
                        }

                        migrationLog.push(`  ✅ SQL generated (${sqlResult.row_count || 0} rows)`);
                        let sql = sqlResult.sql;

                        // Step 2: Modify SQL based on migration type
                        if (migrationType === 'append') {
                            // For append, comment out DROP and use IF NOT EXISTS
                            sql = sql.replace(/DROP TABLE IF EXISTS `\w+`;/gi, '-- $&');
                            sql = sql.replace(/CREATE TABLE `/gi, 'CREATE TABLE IF NOT EXISTS `');
                            migrationLog.push(`  - Modified for APPEND mode`);
                        } else {
                            migrationLog.push(`  - Using OVERWRITE mode (with DROP)`);
                        }

                        // Step 3: Execute complete SQL on destination
                        migrationLog.push(`  - Executing SQL on destination...`);

                        try {
                            // Try executing the complete SQL first
                            const execResult = await apiRequest('execute_sql', {
                                db_host: destConn.host,
                                db_name: destConn.dbName,
                                db_user: destConn.username,
                                db_pass: destConn.password,
                                db_port: destConn.port || '3306',
                                sql_query: sql
                            });

                            if (execResult.success) {
                                migrationLog.push(`  ✅ SQL executed successfully`);
                            } else {
                                // If complete SQL fails, try splitting and executing individually
                                migrationLog.push(`  ⚠️ Complete SQL failed, trying statement by statement...`);
                                console.warn('Complete SQL failed:', execResult.message);
                                
                                // Remove comment lines then split
                                const sqlWithoutComments = sql
                                    .split('\n')
                                    .filter(line => !line.trim().startsWith('--'))
                                    .join('\n');
                                
                                const statements = sqlWithoutComments
                                    .split(';')
                                    .map(s => s.trim())
                                    .filter(s => s.length > 10);

                                migrationLog.push(`  - Found ${statements.length} statements to execute`);

                                for (let i = 0; i < statements.length; i++) {
                                    const statement = statements[i].trim();
                                    if (!statement) continue;

                                    const stmtResult = await apiRequest('execute_sql', {
                                        db_host: destConn.host,
                                        db_name: destConn.dbName,
                                        db_user: destConn.username,
                                        db_pass: destConn.password,
                                        db_port: destConn.port || '3306',
                                        sql_query: statement.endsWith(';') ? statement : statement + ';'
                                    });

                                    if (!stmtResult.success) {
                                        migrationLog.push(`  ⚠️ Statement ${i + 1} failed: ${stmtResult.message.substring(0, 80)}`);
                                        console.error(`Statement ${i + 1} error:`, stmtResult.message);
                                    } else {
                                        migrationLog.push(`  ✅ Statement ${i + 1} executed`);
                                    }
                                }
                            }
                        } catch (execError) {
                            migrationLog.push(`  ❌ Execution error: ${execError.message}`);
                            throw execError;
                        }

                        // Table migrated successfully
                        migratedTables++;
                        if (sqlResult.row_count) {
                            totalRecords += sqlResult.row_count;
                        }
                        migrationLog.push(`  ✅ Table migrated successfully!`);

                    } catch (tableError) {
                        migrationLog.push(`  ❌ Error: ${tableError.message}`);
                        failedTables.push({ table: tableName, error: tableError.message });
                    }
                }

                // Complete
                if (!migrationCancelled) {
                    updateMigrationProgress(100, 'Migration completed!');
                }
                
                // Show detailed results
                const resultsMsg = migrationLog.join('\n');
                console.log('Migration Log:\n', resultsMsg);
                
                showMigrationResults(true, migratedTables, totalTables, totalRecords, migrationType, failedTables, migrationCancelled);
                
            } catch (error) {
                const errorDetails = migrationLog.join('\n') + '\n\n❌ Fatal Error: ' + error.message;
                console.error('Migration Error:', errorDetails);
                showMigrationResults(false, 0, 0, 0, migrationType, [], false, errorDetails);
            } finally {
                // Reset buttons
                document.getElementById('startMigrationBtn').style.display = 'inline-flex';
                document.getElementById('startMigrationBtn').disabled = false;
                document.getElementById('cancelMigrationBtn').style.display = 'none';
                document.getElementById('cancelMigrationBtn').disabled = true;
            }
        }

        // Update migration progress
        function updateMigrationProgress(percentage, message) {
            document.getElementById('migrationProgressBar').style.width = percentage + '%';
            document.getElementById('migrationProgressText').textContent = Math.round(percentage) + '%';
            document.getElementById('migrationProgressDetails').innerHTML = `
                <div style="font-size: 14px; color: rgba(254, 243, 199, 0.9);">${message}</div>
            `;
        }

        // Show migration results
        function showMigrationResults(success, migratedTables, totalTables, totalRecords, type, failedTables = [], cancelled = false, errorDetails = '') {
            const container = document.getElementById('migrationResults');
            container.style.display = 'block';

            if (success && migratedTables > 0) {
                const statusIcon = cancelled ? '⛔' : (migratedTables === totalTables ? '✅' : '⚠️');
                const statusTitle = cancelled ? 'Migration Cancelled' : (migratedTables === totalTables ? 'Migration Completed' : 'Migration Partially Completed');
                const statusColor = cancelled ? '#f59e0b' : (migratedTables === totalTables ? '#86efac' : '#fbbf24');
                let failedSection = '';
                if (failedTables.length > 0) {
                    failedSection = `
                        <div style="background: rgba(245, 158, 11, 0.2); border: 1px solid #f59e0b; border-radius: 6px; padding: 15px; margin-top: 15px;">
                            <h4 style="color: #fbbf24; margin: 0 0 10px 0;">⚠️ ${failedTables.length} Table(s) Failed:</h4>
                            ${failedTables.map(f => `
                                <div style="font-size: 13px; color: rgba(254, 243, 199, 0.8); margin-bottom: 8px;">
                                    ❌ <strong>${f.table}</strong>: ${f.error.substring(0, 100)}
                                </div>
                            `).join('')}
                        </div>
                    `;
                }

                container.innerHTML = `
                    <div style="background: rgba(34, 197, 94, 0.2); border: 1px solid #22c55e; border-radius: 8px; padding: 20px;">
                        <div style="font-size: 48px; text-align: center; margin-bottom: 15px;">${statusIcon}</div>
                        <h3 style="color: ${statusColor}; text-align: center; margin: 0 0 20px 0;">${statusTitle}!</h3>
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                            <div style="background: rgba(0,0,0,0.2); padding: 15px; border-radius: 6px;">
                                <div style="font-size: 12px; color: rgba(254, 243, 199, 0.7);">Migration Type</div>
                                <div style="font-size: 18px; color: #fbbf24; font-weight: bold;">${type === 'overwrite' ? '⚠️ Overwrite' : '✅ Append'}</div>
                            </div>
                            <div style="background: rgba(0,0,0,0.2); padding: 15px; border-radius: 6px;">
                                <div style="font-size: 12px; color: rgba(254, 243, 199, 0.7);">Tables Migrated</div>
                                <div style="font-size: 18px; color: ${migratedTables === totalTables ? '#22c55e' : '#f59e0b'}; font-weight: bold;">${migratedTables} / ${totalTables}</div>
                            </div>
                            <div style="background: rgba(0,0,0,0.2); padding: 15px; border-radius: 6px;">
                                <div style="font-size: 12px; color: rgba(254, 243, 199, 0.7);">Records Migrated</div>
                                <div style="font-size: 18px; color: #fbbf24; font-weight: bold;">${totalRecords.toLocaleString()}</div>
                            </div>
                        </div>

                        <div style="margin-top: 20px; text-align: center; font-size: 14px; color: rgba(254, 243, 199, 0.9);">
                            ${cancelled 
                                ? `⛔ Migration was cancelled. ${migratedTables} tables were migrated before cancellation.`
                                : migratedTables === totalTables 
                                    ? `🎉 All data has been successfully ${type === 'overwrite' ? 'replaced' : 'merged'} in the destination database!`
                                    : `⚠️ ${migratedTables} of ${totalTables} tables migrated. Check console for details.`
                            }
                        </div>

                        ${failedSection}

                        <div style="margin-top: 15px; text-align: center;">
                            <button class="btn btn-secondary" onclick="showMigrationLog()" style="padding: 8px 16px; font-size: 13px;">
                                📋 View Migration Log
                            </button>
                        </div>
                    </div>
                `;
                
                const msgType = cancelled ? 'info' : (migratedTables === totalTables ? 'success' : 'info');
                const msgText = cancelled 
                    ? `⛔ Migration cancelled. ${migratedTables}/${totalTables} tables migrated.`
                    : `✅ Migration completed! ${migratedTables}/${totalTables} tables migrated.`;
                showMessage('generateDatabaseMessage', msgText, msgType);
            } else {
                container.innerHTML = `
                    <div style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; border-radius: 8px; padding: 20px;">
                        <div style="font-size: 48px; text-align: center; margin-bottom: 15px;">❌</div>
                        <h3 style="color: #fca5a5; text-align: center; margin: 0 0 20px 0;">Migration Failed</h3>
                        <div style="background: rgba(0,0,0,0.3); padding: 15px; border-radius: 6px; font-family: monospace; font-size: 12px; color: #fef3c7; max-height: 300px; overflow-y: auto; white-space: pre-wrap;">
${errorDetails || 'Unknown error occurred'}
                        </div>
                        <div style="margin-top: 15px; text-align: center;">
                            <button class="btn btn-secondary" onclick="showMigrationLog()" style="padding: 8px 16px; font-size: 13px;">
                                📋 View Full Log
                            </button>
                        </div>
                    </div>
                `;
                showMessage('generateDatabaseMessage', `❌ Migration failed`, 'error');
            }

            setTimeout(() => {
                container.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }, 100);
        }

        // Show migration log in console
        function showMigrationLog() {
            alert('📋 Migration log has been written to browser console.\n\nPress F12 and check the Console tab for detailed migration log.');
            console.log('='.repeat(60));
            console.log('MIGRATION LOG - Check messages above');
            console.log('='.repeat(60));
        }

        // Test database connection (for repair/diagnostic)
        async function testDatabaseConnection() {
            const destId = document.getElementById('migrationDestinationDb').value;
            const diagnosticEl = document.getElementById('diagnosticResults');

            if (!destId) {
                diagnosticEl.innerHTML = '<div class="message error" style="display: block;">❌ Please select a destination database first</div>';
                return;
            }

            // Use getConnectionById to support BOTH Localhost and Hostinger
            const conn = getConnectionById(destId);

            if (!conn) {
                diagnosticEl.innerHTML = '<div class="message error" style="display: block;">❌ Connection not found</div>';
                return;
            }

            diagnosticEl.innerHTML = '<div class="message info" style="display: block;">🔍 Testing connection...</div>';

            const result = await apiRequest('check_connection', {
                db_host: conn.host,
                db_name: conn.dbName,
                db_user: conn.username,
                db_pass: conn.password,
                db_port: conn.port
            });

            if (result.success) {
                diagnosticEl.innerHTML = `<div class="message success" style="display: block;">✅ ${result.message}<br>Database is accessible and working!</div>`;
            } else {
                diagnosticEl.innerHTML = `<div class="message error" style="display: block;">❌ Connection failed: ${result.message}<br><br>Try updating the connection in Hostinger Connections.</div>`;
            }
        }

        // Check database tables
        async function checkDatabaseTables() {
            const destId = document.getElementById('migrationDestinationDb').value;
            const diagnosticEl = document.getElementById('diagnosticResults');

            if (!destId) {
                diagnosticEl.innerHTML = '<div class="message error" style="display: block;">❌ Please select a destination database first</div>';
                return;
            }

            // Use getConnectionById to support BOTH Localhost and Hostinger
            const conn = getConnectionById(destId);

            if (!conn) {
                diagnosticEl.innerHTML = '<div class="message error" style="display: block;">❌ Connection not found</div>';
                return;
            }

            diagnosticEl.innerHTML = '<div class="message info" style="display: block;">🔍 Checking tables...</div>';

            const result = await apiRequest('list_tables', {
                db_host: conn.host,
                db_name: conn.dbName,
                db_user: conn.username,
                db_pass: conn.password,
                db_port: conn.port
            });

            if (result.success) {
                diagnosticEl.innerHTML = `
                    <div class="message success" style="display: block;">
                        ✅ Found ${result.tables.length} table(s) in ${conn.dbName}<br>
                        Tables: ${result.tables.join(', ')}
                    </div>
                `;
            } else {
                diagnosticEl.innerHTML = `<div class="message error" style="display: block;">❌ Failed to list tables: ${result.message}</div>`;
            }
        }

        // Repair all tables (REPAIR TABLE command)
        async function repairAllTables() {
            const destId = document.getElementById('migrationDestinationDb').value;
            const diagnosticEl = document.getElementById('diagnosticResults');

            if (!destId) {
                diagnosticEl.innerHTML = '<div class="message error" style="display: block;">❌ Please select a destination database first</div>';
                return;
            }

            if (!confirm('🔧 Repair All Tables?\n\nThis will run REPAIR TABLE on all tables in the destination database.\n\nContinue?')) {
                return;
            }

            // Use getConnectionById to support BOTH Localhost and Hostinger
            const conn = getConnectionById(destId);

            if (!conn) {
                diagnosticEl.innerHTML = '<div class="message error" style="display: block;">❌ Connection not found</div>';
                return;
            }

            diagnosticEl.innerHTML = '<div class="message info" style="display: block;">🔧 Repairing tables...</div>';

            // Get tables
            const tablesResult = await apiRequest('list_tables', {
                db_host: conn.host,
                db_name: conn.dbName,
                db_user: conn.username,
                db_pass: conn.password,
                db_port: conn.port
            });

            if (!tablesResult.success) {
                diagnosticEl.innerHTML = `<div class="message error" style="display: block;">❌ Failed to get tables: ${tablesResult.message}</div>`;
                return;
            }

            let repairLog = [];
            for (const table of tablesResult.tables) {
                const repairResult = await apiRequest('execute_sql', {
                    db_host: conn.host,
                    db_name: conn.dbName,
                    db_user: conn.username,
                    db_pass: conn.password,
                    db_port: conn.port,
                    sql_query: `REPAIR TABLE \`${table}\`;`
                });

                if (repairResult.success) {
                    repairLog.push(`✅ ${table}: Repaired`);
                } else {
                    repairLog.push(`⚠️ ${table}: ${repairResult.message}`);
                }
            }

            diagnosticEl.innerHTML = `
                <div class="message success" style="display: block;">
                    🔧 Repair completed for ${tablesResult.tables.length} table(s)<br><br>
                    ${repairLog.join('<br>')}
                </div>
            `;
        }

        // Expandable section toggle
        function toggleExpandable(id) {
            const content = document.getElementById(id);
            const toggle = document.getElementById(id + 'Toggle');
            content.classList.toggle('expanded');
            toggle.textContent = content.classList.contains('expanded') ? '▲' : '▼';
        }

        // Show message
        function showMessage(elementId, message, type) {
            const messageEl = document.getElementById(elementId);
            messageEl.className = `message ${type}`;
            messageEl.textContent = message;
            messageEl.style.display = 'block';
            setTimeout(() => {
                messageEl.style.display = 'none';
            }, 5000);
        }

        // ========================================================================
        // [SECTION 4.2] API REQUEST FUNCTIONS - ALL-IN-ONE MODE
        // ========================================================================
        // All API requests go to THIS SAME FILE (PHP-Dashboard.php)
        // The PHP code at the top handles all database operations
        // ========================================================================
        
        // Helper: Get API URL for a specific connection ID
        // ✅ ALL-IN-ONE MODE: Always returns CURRENT_PAGE_URL
        // PHP handles ALL database connections (localhost AND remote)
        function getApiUrlForConnectionId(connId) {
            // Always use the same page - PHP handles everything!
            console.log('📡 ALL-IN-ONE: Using current page for:', connId || 'default');
            return CURRENT_PAGE_URL;
        }

        // API request helper - Sends ALL requests to THIS SAME PAGE
        // The PHP backend handles connections to BOTH localhost AND Hostinger databases
        async function apiRequest(action, data = {}, connectionId = null) {
            try {
                // ✅ ALL-IN-ONE MODE: Always use current page URL
                // PHP will connect to the correct database using the provided credentials
                const apiUrl = CURRENT_PAGE_URL;
                
                console.log(`📡 API Request: ${action} → ${apiUrl}`);
                
                const formData = new FormData();
                formData.append('action', action);
                for (const key in data) {
                    formData.append(key, data[key]);
                }

                const response = await fetch(apiUrl, {
                    method: 'POST',
                    body: formData
                });

                return await response.json();
            } catch (error) {
                console.error('API Request Error:', error);
                return { success: false, message: 'Network error: ' + error.message };
            }
        }

        // ========================================
        // DATABASE SELECTION & TABLE OPERATIONS
        // ========================================
        
        let selectedDatabaseId = null;

        // Load selected database from localStorage
        function loadSelectedDatabase() {
            console.log('=== LOAD SELECTED DATABASE ===');
            const savedSelection = localStorage.getItem(SELECTED_DATABASE_KEY);
            console.log('savedSelection from localStorage:', savedSelection);
            
            if (savedSelection) {
                selectedDatabaseId = savedSelection;
                console.log('✅ Set selectedDatabaseId to:', selectedDatabaseId);
                
                // Verify the connection exists
                const testConn = getConnectionById(selectedDatabaseId);
                console.log('Test connection result:', testConn);
                
                // Apply selection to card if it exists
                const card = document.getElementById(selectedDatabaseId);
                console.log('Card element found:', !!card);
                if (card) {
                    card.classList.add('selected');
                }

                // Show deselect button
                const deselectBtn = document.getElementById('deselectDatabaseBtn');
                if (deselectBtn) {
                    deselectBtn.classList.add('active');
                }

                // Update selected database badge
                updateSelectedDatabaseBadge();

                // Update selected database display in Generate Database section
                updateSelectedDatabaseDisplay();

                // Update selected database display in Create Table section
                updateCreateTableDatabaseDisplay();

                // Update selected database display in Edit Table section
                updateEditTableDatabaseDisplay();

                // Update selected database display in Delete Table section
                updateDeleteTableDatabaseDisplay();

                // Update selected database display in Rename Table section
                updateRenameTableDatabaseDisplay();

                // Update AI Prompt display
                updateAIPromptDisplay();

                // Show TABLE OPERATIONS in sidebar
                showTableOperations();
            }
        }

        // Update selected database badge with database name
        function updateSelectedDatabaseBadge() {
            if (!selectedDatabaseId) return;

            const badgeNameEl = document.getElementById('selectedDatabaseName');
            if (!badgeNameEl) return;

            // Check if it's localhost or hostinger
            if (selectedDatabaseId.startsWith('localhost_')) {
                const dbName = selectedDatabaseId.replace('localhost_', '');
                badgeNameEl.textContent = `🖥️ ${dbName} (Localhost)`;
            } else if (selectedDatabaseId.startsWith('conn_')) {
                const connId = selectedDatabaseId.replace('conn_', '');
                const connections = getHostingerConnections();
                const conn = connections.find(c => c.id === connId);
                
                if (conn) {
                    badgeNameEl.textContent = `🌐 ${conn.name}`;
                }
            }
        }

        // Select database
        function selectDatabase(connId) {
            console.log('=== SELECT DATABASE ===');
            console.log('Selecting database:', connId);
            
            // Deselect previous if any
            if (selectedDatabaseId) {
                const prevCard = document.getElementById(selectedDatabaseId);
                if (prevCard) {
                    prevCard.classList.remove('selected');
                }
            }

            // Select new database
            selectedDatabaseId = connId;
            const card = document.getElementById(connId);
            if (card) {
                card.classList.add('selected');
            }

            // Save to localStorage
            localStorage.setItem(SELECTED_DATABASE_KEY, connId);

            console.log('✅ Database selected and saved:', connId);

            // Update selected database badge
            updateSelectedDatabaseBadge();

            // Update selected database display in Generate Database section
            updateSelectedDatabaseDisplay();

            // Update selected database display in Create Table section
            updateCreateTableDatabaseDisplay();

            // Update selected database display in Edit Table section
            updateEditTableDatabaseDisplay();

            // Update selected database display in Delete Table section
            updateDeleteTableDatabaseDisplay();

            // Update selected database display in Rename Table section
            updateRenameTableDatabaseDisplay();

            // Update AI Prompt display
            updateAIPromptDisplay();

            // Update migration tables - load immediately when database is selected
            console.log('📋 Loading migration tables...');
            if (typeof loadMigrationTables === 'function') {
                loadMigrationTables();
            }

            // Load tables for dropdowns (Edit/Delete/Rename Table sections)
            console.log('📋 Loading tables for dropdowns...');
            if (typeof loadTablesForDropdowns === 'function') {
                loadTablesForDropdowns();
            }

            // Show TABLE OPERATIONS in sidebar
            showTableOperations();
            
            // Show toast notification
            const dbName = getSelectedDatabaseName(connId);
            const serverType = connId.startsWith('localhost_') ? '🖥️ Localhost' : '🌐 Hostinger';
            showCustomToast(`✅ Database Selected\n${serverType}: ${dbName}\nLoading tables...`, 'info', 2500);
            
            console.log('✅ Database selection completed');
        }

        // Get selected database name from ID
        function getSelectedDatabaseName(connId) {
            if (connId.startsWith('localhost_')) {
                return connId.replace('localhost_', '');
            } else if (connId.startsWith('conn_')) {
                const id = connId.replace('conn_', '');
                const connections = getHostingerConnections();
                const conn = connections.find(c => c.id === id);
                return conn ? conn.dbName : 'Unknown';
            }
            return 'Unknown';
        }

        // Get connection object from ID (supports both Localhost and Hostinger)
        function getConnectionById(connId) {
            console.log('🔍 getConnectionById called with:', connId);
            console.log('🔍 connId type:', typeof connId);
            
            if (!connId) {
                console.warn('⚠️ getConnectionById called with null/undefined');
                return null;
            }
            
            console.log('🔍 Checking if starts with localhost_:', connId.startsWith('localhost_'));
            console.log('🔍 Checking if starts with conn_:', connId.startsWith('conn_'));
            
            if (connId.startsWith('localhost_')) {
                // LOCALHOST database
                const dbName = connId.replace('localhost_', '');
                console.log('🖥️ LOCALHOST_CONFIG:', LOCALHOST_CONFIG);
                const localhostConn = {
                    id: connId,
                    name: dbName,
                    dbName: dbName,
                    host: LOCALHOST_CONFIG.host,
                    username: LOCALHOST_CONFIG.username,
                    password: LOCALHOST_CONFIG.password,
                    port: LOCALHOST_CONFIG.port,
                    type: 'localhost',
                    isLocalhost: true
                };
                console.log('🖥️ Returning Localhost connection:', localhostConn);
                return localhostConn;
            } else if (connId.startsWith('conn_')) {
                // HOSTINGER connection
                const actualId = connId.replace('conn_', '');
                const connections = getHostingerConnections();
                const conn = connections.find(c => c.id === actualId);
                
                if (conn) {
                    const hostingerConn = {
                        ...conn,
                        id: connId,
                        isLocalhost: false
                    };
                    console.log('🌐 Returning Hostinger connection for:', conn.name);
                    return hostingerConn;
                } else {
                    console.error('❌ Hostinger connection not found for ID:', actualId);
                }
            } else {
                console.error('❌ Invalid connection ID format:', connId);
            }
            
            return null;
        }

        // Deselect all databases
        function deselectAllDatabases() {
            // Remove selection from card
            if (selectedDatabaseId) {
                const card = document.getElementById(selectedDatabaseId);
                if (card) {
                    card.classList.remove('selected');
                }
            }

            selectedDatabaseId = null;

            // Remove from localStorage
            localStorage.removeItem(SELECTED_DATABASE_KEY);

            // Hide deselect button
            const deselectBtn = document.getElementById('deselectDatabaseBtn');
            if (deselectBtn) {
                deselectBtn.classList.remove('active');
            }

            // Update selected database display in Generate Database section
            updateSelectedDatabaseDisplay();

            // Hide TABLE OPERATIONS in sidebar
            hideTableOperations();
        }

        // Show TABLE OPERATIONS in sidebar
        function showTableOperations() {
            const tableOpsSections = document.querySelectorAll('.nav-item.hidden-section, .section.hidden-section');
            tableOpsSections.forEach(section => {
                section.classList.remove('hidden-section');
            });

            // Show selected database badge
            const badge = document.getElementById('selectedDatabaseAlert');
            if (badge) {
                badge.classList.remove('hidden-section');
            }
        }

        // Hide TABLE OPERATIONS in sidebar
        function hideTableOperations() {
            // Get all TABLE OPERATIONS nav items
            const navItems = document.querySelectorAll('.nav-item');
            let foundTableOpsHeader = false;
            
            navItems.forEach(item => {
                // Check if this is the TABLE OPERATIONS header or comes after it
                const text = item.textContent.trim();
                if (text.includes('TABLE OPERATIONS')) {
                    foundTableOpsHeader = true;
                    item.classList.add('hidden-section');
                } else if (foundTableOpsHeader && (text.includes('List Tables') || text.includes('Create Table') || text.includes('Edit Table') || text.includes('Delete Table') || text.includes('Rename Table'))) {
                    item.classList.add('hidden-section');
                }
            });

            // Hide selected database badge
            const badge = document.getElementById('selectedDatabaseAlert');
            if (badge) {
                badge.classList.add('hidden-section');
            }

            // Hide TABLE OPERATIONS sections
            const sections = ['listTables', 'createTable', 'editTable', 'deleteTable', 'renameTable'];
            sections.forEach(id => {
                const section = document.getElementById(id);
                if (section) {
                    section.classList.add('hidden-section');
                }
            });
        }

        // ========================================
        // HOSTINGER CONNECTIONS MANAGEMENT
        // ========================================

        // Get saved connections
        function getHostingerConnections() {
            const saved = localStorage.getItem(HOSTINGER_CONNECTIONS_KEY);
            return saved ? JSON.parse(saved) : [];
        }

        // Save connections
        function saveHostingerConnections(connections) {
            localStorage.setItem(HOSTINGER_CONNECTIONS_KEY, JSON.stringify(connections));
        }

        // Add new connection
        function addHostingerConnection(event) {
            event.preventDefault();
            
            const connection = {
                id: Date.now().toString(),
                name: document.getElementById('connName').value.trim(),
                type: document.getElementById('connType').value,
                host: document.getElementById('connHost').value.trim(),
                dbName: document.getElementById('connDbName').value.trim(),
                username: document.getElementById('connUsername').value.trim(),
                password: document.getElementById('connPassword').value,
                port: document.getElementById('connPort').value.trim() || '3306',
                createdAt: new Date().toISOString()
            };

            const connections = getHostingerConnections();
            connections.push(connection);
            saveHostingerConnections(connections);

            showMessage('settingsMessage', `✅ Connection "${connection.name}" added successfully!`, 'success');
            
            // Reset form
            document.getElementById('connName').value = '';
            document.getElementById('connHost').value = '192.168.8.4';
            document.getElementById('connDbName').value = '';
            document.getElementById('connUsername').value = '';
            document.getElementById('connPassword').value = '';
            document.getElementById('connPort').value = '3306';
            
            // Reset password visibility
            const passwordInput = document.getElementById('connPassword');
            const toggleBtn = document.getElementById('togglePasswordBtn');
            passwordInput.type = 'password';
            toggleBtn.textContent = '👁️';
            toggleBtn.style.background = 'rgba(59, 130, 246, 0.2)';
            toggleBtn.style.borderColor = '#3b82f6';
            toggleBtn.style.color = '#93c5fd';
            
            loadHostingerConnectionsTable();
            loadDashboardConnections();
            
            // Update toggle button to reflect new count
            updateConnectionToggleButton();
        }

        // Edit connection
        function editHostingerConnection(id) {
            const connections = getHostingerConnections();
            const conn = connections.find(c => c.id === id);
            
            if (!conn) return;

            // Fill form with connection data
            document.getElementById('connName').value = conn.name;
            document.getElementById('connType').value = conn.type;
            document.getElementById('connHost').value = conn.host;
            document.getElementById('connDbName').value = conn.dbName;
            document.getElementById('connUsername').value = conn.username;
            document.getElementById('connPassword').value = conn.password;
            document.getElementById('connPort').value = conn.port;

            // Delete old connection
            deleteHostingerConnection(id, true);
            
            // Scroll to form
            document.querySelector('#settings .card').scrollIntoView({ behavior: 'smooth' });
        }

        // Delete connection
        function deleteHostingerConnection(id, silent = false) {
            if (!silent && !confirm('Are you sure you want to delete this connection?')) {
                return;
            }

            // Check if deleted connection is the selected one
            const connId = `conn_${id}`;
            if (selectedDatabaseId === connId) {
                deselectAllDatabases();
            }

            let connections = getHostingerConnections();
            connections = connections.filter(c => c.id !== id);
            saveHostingerConnections(connections);

            if (!silent) {
                showMessage('settingsMessage', '✅ Connection deleted successfully!', 'success');
            }
            
            loadHostingerConnectionsTable();
            loadDashboardConnections();
            
            // Update toggle button to reflect new count
            updateConnectionToggleButton();
        }

        // Clear all connections
        function clearAllHostingerConnections() {
            if (!confirm('Are you sure you want to delete ALL connections? This action cannot be undone!')) {
                return;
            }

            // Deselect if any database was selected
            deselectAllDatabases();

            localStorage.removeItem(HOSTINGER_CONNECTIONS_KEY);
            showMessage('settingsMessage', '✅ All connections cleared!', 'success');
            
            loadHostingerConnectionsTable();
            loadDashboardConnections();
            
            // Update toggle button to reflect no connections
            updateConnectionToggleButton();
        }

        // Toggle password visibility in Settings
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('connPassword');
            const toggleBtn = document.getElementById('togglePasswordBtn');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleBtn.textContent = '🙈';
                toggleBtn.style.background = 'rgba(34, 197, 94, 0.2)';
                toggleBtn.style.borderColor = '#22c55e';
                toggleBtn.style.color = '#86efac';
            } else {
                passwordInput.type = 'password';
                toggleBtn.textContent = '👁️';
                toggleBtn.style.background = 'rgba(59, 130, 246, 0.2)';
                toggleBtn.style.borderColor = '#3b82f6';
                toggleBtn.style.color = '#93c5fd';
            }
        }

        // Populate autocomplete fields from previous connections
        function populateAutocompleteFields() {
            const connections = getHostingerConnections();
            
            // Get unique values
            const hosts = [...new Set(connections.map(c => c.host))];
            const dbNames = [...new Set(connections.map(c => c.dbName))];
            const usernames = [...new Set(connections.map(c => c.username))];

            // Populate Host datalist
            const hostList = document.getElementById('hostList');
            hostList.innerHTML = hosts.map(h => `<option value="${h}">`).join('');

            // Populate Database Name datalist
            const dbNameList = document.getElementById('dbNameList');
            dbNameList.innerHTML = dbNames.map(db => `<option value="${db}">`).join('');

            // Populate Username datalist
            const usernameList = document.getElementById('usernameList');
            usernameList.innerHTML = usernames.map(u => `<option value="${u}">`).join('');
        }

        // Toggle password visibility in table
        function toggleTablePassword(connId, password) {
            const pwdSpan = document.getElementById(`pwd_${connId}`);
            const pwdBtn = document.getElementById(`pwdBtn_${connId}`);
            
            if (pwdSpan.textContent.includes('•')) {
                // Show password
                pwdSpan.textContent = password;
                pwdBtn.textContent = '🙈';
                pwdBtn.style.background = 'rgba(34, 197, 94, 0.2)';
                pwdBtn.style.borderColor = '#22c55e';
                pwdBtn.style.color = '#86efac';
            } else {
                // Hide password
                pwdSpan.textContent = '•'.repeat(password.length);
                pwdBtn.textContent = '👁️';
                pwdBtn.style.background = 'rgba(59, 130, 246, 0.2)';
                pwdBtn.style.borderColor = '#3b82f6';
                pwdBtn.style.color = '#93c5fd';
            }
        }

        // Load connections table in Settings
        function loadHostingerConnectionsTable() {
            const tableEl = document.getElementById('hostingerConnectionsTable');
            const connections = getHostingerConnections();

            // Populate autocomplete fields
            populateAutocompleteFields();

            if (connections.length === 0) {
                tableEl.innerHTML = '<p style="color: rgba(254, 243, 199, 0.6); text-align: center; padding: 20px;">No connections saved yet.</p>';
                return;
            }

            let html = `
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: rgba(251, 191, 36, 0.1); border-bottom: 2px solid rgba(251, 191, 36, 0.3);">
                                <th style="padding: 12px; text-align: left; color: #fbbf24;">Name</th>
                                <th style="padding: 12px; text-align: left; color: #fbbf24;">Type</th>
                                <th style="padding: 12px; text-align: left; color: #fbbf24;">Host</th>
                                <th style="padding: 12px; text-align: left; color: #fbbf24;">Database</th>
                                <th style="padding: 12px; text-align: left; color: #fbbf24;">Username</th>
                                <th style="padding: 12px; text-align: left; color: #fbbf24;">Password</th>
                                <th style="padding: 12px; text-align: center; color: #fbbf24;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>`;

            connections.forEach(conn => {
                const typeIcon = conn.type === 'vps' ? '🖥️' : '🌐';
                const maskedPassword = '•'.repeat(conn.password.length);
                html += `
                    <tr style="border-bottom: 1px solid rgba(251, 191, 36, 0.1);">
                        <td style="padding: 12px; color: #fef3c7;"><strong>${conn.name}</strong></td>
                        <td style="padding: 12px; color: rgba(254, 243, 199, 0.7);">${typeIcon} ${conn.type.toUpperCase()}</td>
                        <td style="padding: 12px; color: rgba(254, 243, 199, 0.7); font-family: monospace; font-size: 13px;">${conn.host}</td>
                        <td style="padding: 12px; color: rgba(254, 243, 199, 0.7); font-family: monospace; font-size: 13px;">${conn.dbName}</td>
                        <td style="padding: 12px; color: rgba(254, 243, 199, 0.7);">${conn.username}</td>
                        <td style="padding: 12px;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span id="pwd_${conn.id}" style="font-family: monospace; color: rgba(254, 243, 199, 0.7); font-size: 13px;">${maskedPassword}</span>
                                <button onclick="toggleTablePassword('${conn.id}', '${conn.password.replace(/'/g, "\\'")}')" style="background: rgba(59, 130, 246, 0.2); border: 1px solid #3b82f6; color: #93c5fd; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 14px; transition: all 0.3s;" id="pwdBtn_${conn.id}">
                                    👁️
                                </button>
                            </div>
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <button class="btn btn-secondary" style="padding: 6px 12px; margin-right: 5px; font-size: 12px;" onclick="editHostingerConnection('${conn.id}')">✏️ Edit</button>
                            <button class="btn btn-danger" style="padding: 6px 12px; font-size: 12px;" onclick="deleteHostingerConnection('${conn.id}')">🗑️ Delete</button>
                        </td>
                    </tr>`;
            });

            html += `
                        </tbody>
                    </table>
                </div>`;

            tableEl.innerHTML = html;
        }

        // Load connections in Dashboard with Test Connection
        async function loadDashboardConnections() {
            const listEl = document.getElementById('configuredConnectionsList');
            const hostingerConnections = getHostingerConnections();
            const localhostDatabases = getLocalhostDatabases();

            // Check if BOTH are disconnected
            if (!isHostingerConnected && !isLocalhostConnected) {
                // BOTH DISCONNECTED - Show security message
                listEl.innerHTML = `
                    <div style="text-align: center; padding: 60px 40px; background: linear-gradient(135deg, rgba(239, 68, 68, 0.15) 0%, rgba(220, 38, 38, 0.1) 100%); border: 2px dashed #ef4444; border-radius: 15px; backdrop-filter: blur(10px);">
                        <div style="font-size: 72px; margin-bottom: 20px; animation: disconnectPulse 2s ease-in-out infinite;">🔌</div>
                        <div style="font-size: 24px; color: #fca5a5; font-weight: bold; margin-bottom: 15px;">All Connections Disconnected</div>
                        <div style="font-size: 16px; color: rgba(254, 243, 199, 0.8); margin-bottom: 20px; line-height: 1.6;">
                            Your databases are hidden for security.<br>
                            Click connection buttons above to access your databases.
                        </div>
                        <div style="font-size: 14px; color: rgba(254, 243, 199, 0.6); background: rgba(239, 68, 68, 0.1); padding: 15px; border-radius: 8px; border: 1px solid rgba(239, 68, 68, 0.3); max-width: 500px; margin: 0 auto;">
                            🔒 <strong>Security:</strong> Click <strong style="color: #3b82f6;">🖥️ Localhost</strong> or <strong style="color: #fbbf24;">🌐 Hostinger</strong> to connect
                        </div>
                    </div>
                `;
                console.log('🔴 Dashboard: ALL DISCONNECTED - Hiding all databases');
                showConnectionStats(0, 0);
                return;
            }

            // Build HTML for connected sources
            let html = '';
            let totalConnected = 0;
            let hostingerCount = 0;
            let localhostCount = 0;

            // === LOCALHOST DATABASES ===
            if (isLocalhostConnected && localhostDatabases.length > 0) {
                console.log('🖥️ Dashboard: Displaying LOCALHOST databases');
                localhostCount = localhostDatabases.length;
                
                // Add section header for localhost
                html += `
                    <div style="grid-column: 1 / -1; background: linear-gradient(135deg, rgba(59, 130, 246, 0.15) 0%, rgba(30, 64, 175, 0.1) 100%); border: 2px solid rgba(59, 130, 246, 0.3); border-radius: 10px; padding: 12px 20px; margin-bottom: 10px; display: flex; align-items: center; gap: 12px;">
                        <div style="font-size: 28px;">🖥️</div>
                        <div style="flex: 1;">
                            <div style="font-size: 16px; color: #60a5fa; font-weight: bold;">Localhost Laragon Databases</div>
                            <div style="font-size: 12px; color: rgba(147, 197, 253, 0.7);">Local MySQL server • ${localhostCount} database${localhostCount !== 1 ? 's' : ''}</div>
                        </div>
                        <div style="background: rgba(59, 130, 246, 0.3); padding: 6px 14px; border-radius: 8px; font-size: 14px; font-weight: bold; color: #93c5fd;">
                            ${localhostCount}
                        </div>
                    </div>
                `;
                
                localhostDatabases.forEach(dbName => {
                    const connId = `localhost_${dbName}`;
                    html += `
                        <div class="database-item" id="${connId}" onclick="selectDatabase('${connId}')" style="border-color: rgba(59, 130, 246, 0.4); background: linear-gradient(135deg, rgba(59, 130, 246, 0.08) 0%, rgba(30, 64, 175, 0.05) 100%);">
                            <!-- Localhost Badge -->
                            <div style="position: absolute; top: 8px; right: 8px; background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%); color: white; padding: 4px 10px; border-radius: 12px; font-size: 10px; font-weight: bold; border: 1px solid rgba(59, 130, 246, 0.6); box-shadow: 0 2px 8px rgba(59, 130, 246, 0.4);">
                                LARAGON
                            </div>
                            
                            <!-- Row 1: Logo/Icon -->
                            <div class="database-icon-row" style="border-color: rgba(59, 130, 246, 0.15);">
                                <span class="database-icon" style="filter: drop-shadow(0 0 10px rgba(59, 130, 246, 0.5));">🖥️</span>
                            </div>
                            
                            <!-- Row 2: Text Content -->
                            <div class="database-text-row">
                                <div class="database-name" style="color: #60a5fa;">${dbName}</div>
                                <div class="database-info-item" style="color: rgba(147, 197, 253, 0.8);">📁 Localhost Database</div>
                                <div class="database-info-item" style="color: rgba(147, 197, 253, 0.7);">🖥️ ${LOCALHOST_CONFIG.host}</div>
                            </div>
                            
                            <!-- Row 3: Status + Buttons -->
                            <div class="database-actions-row" style="border-color: rgba(59, 130, 246, 0.15);">
                                <div id="${connId}_status" class="database-status" style="background: rgba(59, 130, 246, 0.2); border-color: #3b82f6; color: #93c5fd;">
                                    ✅ Laragon (Local)
                                </div>
                                <div class="database-buttons-row">
                                    <button class="btn btn-primary" style="background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%); border-color: #60a5fa;" onclick="event.stopPropagation(); showLocalhostInfo('${dbName}');">
                                        <span>ℹ️</span> Info
                                    </button>
                                    <button class="btn btn-credentials" onclick="event.stopPropagation(); showCredentialsModalLocalhost('${dbName}');">
                                        <span>🔑</span> Credentials
                                    </button>
                                </div>
                            </div>
                        </div>`;
                    totalConnected++;
                });
            }

            // === HOSTINGER CONNECTIONS ===
            if (isHostingerConnected && hostingerConnections.length > 0) {
                console.log('🌐 Dashboard: Displaying HOSTINGER databases');
                hostingerCount = hostingerConnections.length;
                
                // Add section header for Hostinger (only if localhost is also shown)
                if (isLocalhostConnected && localhostCount > 0) {
                    html += `
                        <div style="grid-column: 1 / -1; background: linear-gradient(135deg, rgba(251, 191, 36, 0.15) 0%, rgba(245, 158, 11, 0.1) 100%); border: 2px solid rgba(251, 191, 36, 0.3); border-radius: 10px; padding: 12px 20px; margin: 15px 0 10px 0; display: flex; align-items: center; gap: 12px;">
                            <div style="font-size: 28px;">🌐</div>
                            <div style="flex: 1;">
                                <div style="font-size: 16px; color: #fbbf24; font-weight: bold;">Hostinger Remote Databases</div>
                                <div style="font-size: 12px; color: rgba(251, 191, 36, 0.7);">Remote MySQL servers • ${hostingerCount} connection${hostingerCount !== 1 ? 's' : ''}</div>
                            </div>
                            <div style="background: rgba(251, 191, 36, 0.3); padding: 6px 14px; border-radius: 8px; font-size: 14px; font-weight: bold; color: #fbbf24;">
                                ${hostingerCount}
                            </div>
                        </div>
                    `;
                }
                
                hostingerConnections.forEach(conn => {
                    const typeIcon = conn.type === 'vps' ? '🖥️' : '🌐';
                    const connId = `conn_${conn.id}`;
                    html += `
                        <div class="database-item" id="${connId}" onclick="selectDatabase('${connId}')">
                            <!-- Row 1: Logo/Icon -->
                            <div class="database-icon-row">
                                <span class="database-icon">${typeIcon}</span>
                            </div>
                            
                            <!-- Row 2: Text Content -->
                            <div class="database-text-row">
                                <div class="database-name">${conn.name}</div>
                                <div class="database-info-item">📁 ${conn.dbName}</div>
                                <div class="database-info-item">🖥️ ${conn.host}</div>
                            </div>
                            
                            <!-- Row 3: Status + Buttons -->
                            <div class="database-actions-row">
                                <div id="${connId}_status" class="database-status testing">
                                    <span class="spinner"></span>
                                    <span>Testing connection...</span>
                                </div>
                                <div class="database-buttons-row">
                                    <button class="btn btn-primary" onclick="event.stopPropagation(); testConnectionManual('${conn.id}')">
                                        <span>🔄</span> Test
                                    </button>
                                    <button class="btn btn-credentials" onclick="event.stopPropagation(); showCredentialsModalHostinger('${conn.id}');">
                                        <span>🔑</span> Credentials
                                    </button>
                                </div>
                            </div>
                        </div>`;
                    totalConnected++;
                });
            }

            // Show results
            if (totalConnected === 0) {
                listEl.innerHTML = `
                    <div style="text-align: center; padding: 60px 40px; background: linear-gradient(135deg, rgba(34, 197, 94, 0.15) 0%, rgba(22, 163, 74, 0.1) 100%); border: 2px dashed #22c55e; border-radius: 15px; backdrop-filter: blur(10px);">
                        <div style="font-size: 72px; margin-bottom: 20px;">🗄️</div>
                        <div style="font-size: 20px; color: #86efac; font-weight: bold; margin-bottom: 10px;">Connected but No Databases Found</div>
                        <div style="font-size: 15px; color: rgba(254, 243, 199, 0.7);">
                            ${isLocalhostConnected ? '🖥️ Localhost: No databases found<br>' : ''}
                            ${isHostingerConnected ? '🌐 Hostinger: No connections configured' : ''}
                        </div>
                    </div>
                `;
            } else {
                listEl.innerHTML = html;

                // Auto-test Hostinger connections only
                if (isHostingerConnected) {
                    hostingerConnections.forEach(conn => {
                        testConnection(conn.id);
                    });
                }
            }

            // Restore selected database if exists
            setTimeout(() => {
                loadSelectedDatabase();
            }, 50);
            
            // Show connection stats
            showConnectionStats(hostingerCount, localhostCount);
        }

        // Show connection statistics when connected
        function showConnectionStats(hostingerCount, localhostCount) {
            const dashboardMessage = document.getElementById('dashboardMessage');
            if (!dashboardMessage) return;
            
            const totalCount = hostingerCount + localhostCount;
            
            // Clear if both disconnected or no databases
            if ((!isHostingerConnected && !isLocalhostConnected) || totalCount === 0) {
                dashboardMessage.innerHTML = '';
                return;
            }
            
            // Build connection status messages
            let statusParts = [];
            if (isLocalhostConnected && localhostCount > 0) {
                statusParts.push(`<strong style="color: #3b82f6;">🖥️ Localhost: ${localhostCount}</strong>`);
            }
            if (isHostingerConnected && hostingerCount > 0) {
                statusParts.push(`<strong style="color: #fbbf24;">🌐 Hostinger: ${hostingerCount}</strong>`);
            }
            
            const statusText = statusParts.join(' • ');
            
            // Add beautiful stats indicator
            dashboardMessage.innerHTML = `
                <div style="background: linear-gradient(135deg, rgba(34, 197, 94, 0.1) 0%, rgba(22, 163, 74, 0.05) 100%); border: 1px solid rgba(34, 197, 94, 0.3); border-radius: 10px; padding: 15px; margin-bottom: 20px; display: flex; align-items: center; gap: 15px; animation: fadeIn 0.5s ease-out;">
                    <div style="font-size: 36px;">✅</div>
                    <div style="flex: 1;">
                        <div style="font-size: 15px; color: #86efac; font-weight: bold; margin-bottom: 5px;">
                            Active Database Connections
                        </div>
                        <div style="font-size: 13px; color: rgba(254, 243, 199, 0.7);">
                            📊 Displaying <strong style="color: #fef3c7;">${totalCount}</strong> total • ${statusText}
                        </div>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        ${localhostCount > 0 ? `
                        <div style="padding: 10px 16px; background: rgba(59, 130, 246, 0.2); border-radius: 8px; border: 1px solid rgba(59, 130, 246, 0.4);">
                            <div style="font-size: 20px; font-weight: bold; color: #3b82f6;">${localhostCount}</div>
                            <div style="font-size: 9px; color: rgba(254, 243, 199, 0.6); text-transform: uppercase; letter-spacing: 0.5px;">Local</div>
                        </div>` : ''}
                        ${hostingerCount > 0 ? `
                        <div style="padding: 10px 16px; background: rgba(251, 191, 36, 0.2); border-radius: 8px; border: 1px solid rgba(251, 191, 36, 0.4);">
                            <div style="font-size: 20px; font-weight: bold; color: #fbbf24;">${hostingerCount}</div>
                            <div style="font-size: 9px; color: rgba(254, 243, 199, 0.6); text-transform: uppercase; letter-spacing: 0.5px;">Remote</div>
                        </div>` : ''}
                    </div>
                </div>
            `;
        }

        // Test connection (automatic on load)
        async function testConnection(connId) {
            const connections = getHostingerConnections();
            const conn = connections.find(c => c.id === connId);
            
            if (!conn) return;

            const statusEl = document.getElementById(`conn_${connId}_status`);
            
            // Show testing status
            statusEl.className = 'database-status info';
            statusEl.innerHTML = '🔄 Testing connection...';
            
            try {
                console.log('🔍 Testing Hostinger connection:', {
                    connId,
                    host: conn.host,
                    dbName: conn.dbName,
                    apiUrl: CURRENT_PAGE_URL
                });
                
                const result = await apiRequest('check_connection', {
                    db_host: conn.host,
                    db_name: conn.dbName,
                    db_user: conn.username,
                    db_pass: conn.password,
                    db_port: conn.port
                });

                if (result.success) {
                    statusEl.className = 'database-status success';
                    statusEl.innerHTML = '✅ Connected successfully!';
                } else {
                    statusEl.className = 'database-status error';
                    // Provide helpful error message
                    let errorMsg = result.message || 'Unknown error';
                    // Check for common Hostinger connection issues
                    if (errorMsg.includes('Access denied') || errorMsg.includes('Unknown database')) {
                        errorMsg += ' - Check username/password/database name';
                    } else if (errorMsg.includes('connect') || errorMsg.includes('refused')) {
                        errorMsg += ' - Enable Remote MySQL in Hostinger panel and add your IP';
                    }
                    statusEl.innerHTML = `❌ ${errorMsg}`;
                }
            } catch (error) {
                statusEl.className = 'database-status error';
                console.error('❌ Connection test error:', error);
                statusEl.innerHTML = `❌ Error: ${error.message}`;
            }
        }

        // Test connection manually (when clicking Test Again button)
        function testConnectionManual(connId) {
            const statusEl = document.getElementById(`conn_${connId}_status`);
            statusEl.className = 'database-status testing';
            statusEl.innerHTML = '<span class="spinner"></span><span>Testing connection...</span>';
            
            testConnection(connId);
        }

        // ========================================
        // NOTE: Old database operations functions below
        // These will need to be updated for Hostinger connections
        // ========================================

        // Load databases (deprecated - redirects to dashboard)
        async function loadDatabases() {
            console.log('ℹ️ loadDatabases() is deprecated - use Dashboard instead');
            showSection('dashboard');
        }

        // Load databases for dropdowns (Localhost only)
        async function loadDatabasesForDropdowns() {
            // Get localhost databases
            const localhostDbs = getLocalhostDatabases();
            
            if (localhostDbs.length === 0) {
                const emptyMsg = '<option value="">-- No databases (Connect to Localhost first) --</option>';
                document.getElementById('deleteDbSelect').innerHTML = emptyMsg;
                document.getElementById('renameOldSelect').innerHTML = emptyMsg;
                document.getElementById('credentialsDbSelect').innerHTML = emptyMsg;
                return;
            }
            
            const options = localhostDbs.map(db => 
                `<option value="${db}">🖥️ ${db} (Localhost)</option>`
            ).join('');
            
            document.getElementById('deleteDbSelect').innerHTML = '<option value="">-- Select Database --</option>' + options;
            document.getElementById('renameOldSelect').innerHTML = '<option value="">-- Select Database --</option>' + options;
            document.getElementById('credentialsDbSelect').innerHTML = '<option value="">-- Select Database --</option>' + options;
        }

        // Connect to database
        async function connectToDatabase(dbName) {
            currentDatabase = dbName;
            const savedCreds = getCredentialsForDatabase(dbName);
            
            // Try connection with saved credentials first
            let username = savedCreds ? savedCreds.username : '';
            let password = savedCreds ? savedCreds.password : '';
            
            showMessage('listMessage', `🔄 Connecting to ${dbName}...`, 'info');
            
            const result = await apiRequest('connect_database', {
                db_name: dbName,
                db_username: username,
                db_password: password
            });
            
            if (result.success) {
                showMessage('listMessage', `✅ ${result.message}`, 'success');
                updateConnectionStatus(dbName);
            } else if (result.requiresCredentials) {
                // Show credentials modal
                openConnectionModal(dbName, result.message);
            } else {
                showMessage('listMessage', `❌ ${result.message}`, 'error');
            }
        }

        // Open connection modal
        function openConnectionModal(dbName, message) {
            document.getElementById('modalTitle').textContent = `Connect to ${dbName}`;
            document.getElementById('modalMessage').textContent = message || 'This database requires authentication';
            document.getElementById('modalUsername').value = '';
            document.getElementById('modalPassword').value = '';
            populateSavedUsernames();
            document.getElementById('connectionModal').classList.add('active');
        }

        // Close connection modal
        function closeConnectionModal() {
            document.getElementById('connectionModal').classList.remove('active');
            currentDatabase = null;
        }

        // Submit connection
        async function submitConnection() {
            const username = document.getElementById('modalUsername').value;
            const password = document.getElementById('modalPassword').value;
            
            if (!username || !password) {
                alert('Please enter both username and password');
                return;
            }
            
            const result = await apiRequest('connect_database', {
                db_name: currentDatabase,
                db_username: username,
                db_password: password
            });
            
            if (result.success) {
                // Save credentials
                saveCredentials(currentDatabase, username, password);
                showMessage('listMessage', `✅ ${result.message} (Credentials saved)`, 'success');
                closeConnectionModal();
                loadDatabases(); // Refresh to show lock icon
                updateConnectionStatus(currentDatabase);
            } else {
                document.getElementById('modalMessage').textContent = '❌ ' + result.message;
            }
        }

        // Update create database status indicator
        function updateCreateDatabaseStatus() {
            const statusEl = document.getElementById('createDbStatus');
            if (!statusEl) return;
            
            if (isLocalhostConnected) {
                statusEl.style.background = 'rgba(34, 197, 94, 0.2)';
                statusEl.style.borderColor = '#22c55e';
                statusEl.style.color = '#86efac';
                statusEl.textContent = '🟢 Connected';
            } else {
                statusEl.style.background = 'rgba(239, 68, 68, 0.2)';
                statusEl.style.borderColor = '#ef4444';
                statusEl.style.color = '#fca5a5';
                statusEl.textContent = '🔴 Not Connected';
            }
        }

        // Set database name from suggestion
        function setDatabaseName(name) {
            const input = document.getElementById('newDbName');
            if (input) {
                input.value = name;
                input.focus();
                
                // Visual feedback
                input.style.background = 'rgba(34, 197, 94, 0.15)';
                input.style.borderColor = '#22c55e';
                
                setTimeout(() => {
                    input.style.background = '';
                    input.style.borderColor = '';
                }, 800);
                
                showCustomToast(`✅ Database name selected!\n📁 ${name}`, 'success', 1500);
            }
        }

        // Generate random database name
        function generateRandomDbName() {
            const prefixes = [
                'project', 'app', 'site', 'web', 'api', 'data', 'system', 
                'platform', 'service', 'portal', 'store', 'hub', 'network',
                'dashboard', 'panel', 'admin', 'client', 'backend', 'frontend'
            ];
            
            const suffixes = [
                'db', 'database', 'data', 'storage', 'repo', 'vault',
                'core', 'main', 'primary', 'master', 'prod', 'dev', 'test'
            ];
            
            const types = [
                'cms', 'crm', 'erp', 'blog', 'shop', 'forum', 'news',
                'analytics', 'reporting', 'inventory', 'logistics', 'sales'
            ];
            
            // Random combination strategy
            const strategy = Math.floor(Math.random() * 3);
            let randomName = '';
            
            if (strategy === 0) {
                // prefix + suffix
                const prefix = prefixes[Math.floor(Math.random() * prefixes.length)];
                const suffix = suffixes[Math.floor(Math.random() * suffixes.length)];
                randomName = `${prefix}_${suffix}`;
            } else if (strategy === 1) {
                // type + suffix
                const type = types[Math.floor(Math.random() * types.length)];
                const suffix = suffixes[Math.floor(Math.random() * suffixes.length)];
                randomName = `${type}_${suffix}`;
            } else {
                // prefix + type
                const prefix = prefixes[Math.floor(Math.random() * prefixes.length)];
                const type = types[Math.floor(Math.random() * types.length)];
                randomName = `${prefix}_${type}`;
            }
            
            // Add random number for uniqueness
            const randomNum = Math.floor(Math.random() * 999) + 1;
            randomName += `_${randomNum}`;
            
            setDatabaseName(randomName);
        }

        // Update delete database status indicator
        function updateDeleteDatabaseStatus() {
            const statusEl = document.getElementById('deleteDbStatus');
            if (!statusEl) return;
            
            if (isLocalhostConnected) {
                statusEl.style.background = 'rgba(34, 197, 94, 0.2)';
                statusEl.style.borderColor = '#22c55e';
                statusEl.style.color = '#86efac';
                statusEl.textContent = '🟢 Connected';
            } else {
                statusEl.style.background = 'rgba(239, 68, 68, 0.2)';
                statusEl.style.borderColor = '#ef4444';
                statusEl.style.color = '#fca5a5';
                statusEl.textContent = '🔴 Not Connected';
            }
        }

        // Update rename database status indicator
        function updateRenameDatabaseStatus() {
            const statusEl = document.getElementById('renameDbStatus');
            if (!statusEl) return;
            
            if (isLocalhostConnected) {
                statusEl.style.background = 'rgba(34, 197, 94, 0.2)';
                statusEl.style.borderColor = '#22c55e';
                statusEl.style.color = '#86efac';
                statusEl.textContent = '🟢 Connected';
            } else {
                statusEl.style.background = 'rgba(239, 68, 68, 0.2)';
                statusEl.style.borderColor = '#ef4444';
                statusEl.style.color = '#fca5a5';
                statusEl.textContent = '🔴 Not Connected';
            }
        }

        // Create database (Localhost Laragon only)
        async function createDatabase(event) {
            event.preventDefault();
            
            const dbName = document.getElementById('newDbName').value.trim();
            const username = document.getElementById('newDbUsername').value.trim();
            const password = document.getElementById('newDbPassword').value.trim();
            
            if (!dbName) {
                showMessage('createMessage', '❌ Please enter a database name', 'error');
                return;
            }
            
            // Validate database name format
            if (!/^[a-zA-Z0-9_-]+$/.test(dbName)) {
                showMessage('createMessage', '❌ Invalid database name! Use only letters, numbers, underscores, and hyphens.', 'error');
                return;
            }
            
            // Warn if trying to create with custom credentials
            if ((username && !password) || (!username && password)) {
                showMessage('createMessage', '⚠️ Both username and password are required if setting credentials!', 'error');
                return;
            }
            
            showMessage('createMessage', '🔄 Creating database on Localhost Laragon...', 'info');
            
            // Send request with localhost credentials
            // IMPORTANT: Use db_host, db_user, db_pass, db_port (what PHP expects)
            const result = await apiRequest('create_database', {
                // Server connection credentials (Localhost) - PHP expects these names
                db_host: LOCALHOST_CONFIG.host,
                db_user: LOCALHOST_CONFIG.username,
                db_pass: LOCALHOST_CONFIG.password,
                db_port: LOCALHOST_CONFIG.port,
                
                // Database details
                db_name: dbName,
                db_username: username,
                db_password: password
            });
            
            if (result.success) {
                showMessage('createMessage', `✅ ${result.message}`, 'success');
                
                // Show success animation
                const successOverlay = document.createElement('div');
                successOverlay.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.85); z-index: 9999; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(8px);';
                successOverlay.innerHTML = `
                    <div style="text-align: center; animation: scaleIn 0.5s ease-out;">
                        <div style="font-size: 100px; margin-bottom: 25px; animation: checkmarkPop 0.8s ease-out;">✅</div>
                        <div style="font-size: 32px; color: #22c55e; font-weight: bold; margin-bottom: 15px;">Database Created!</div>
                        <div style="font-size: 20px; color: #86efac; background: rgba(59, 130, 246, 0.2); padding: 12px 25px; border-radius: 10px; border: 2px solid #3b82f6; display: inline-block;">
                            🖥️ ${dbName}
                        </div>
                        ${username ? `<div style="font-size: 14px; color: rgba(254, 243, 199, 0.7); margin-top: 12px;">With user: <strong style="color: #60a5fa;">${username}</strong></div>` : ''}
                        <div style="font-size: 13px; color: rgba(254, 243, 199, 0.6); margin-top: 15px;">Created on Localhost Laragon</div>
                    </div>
                    <style>
                        @keyframes checkmarkPop {
                            0% { transform: scale(0); }
                            50% { transform: scale(1.2); }
                            100% { transform: scale(1); }
                        }
                    </style>
                `;
                document.body.appendChild(successOverlay);
                
                setTimeout(() => {
                    successOverlay.style.animation = 'fadeOut 0.4s ease-out forwards';
                    successOverlay.innerHTML += '<style>@keyframes fadeOut { to { opacity: 0; } }</style>';
                    setTimeout(() => {
                        if (successOverlay.parentNode) {
                            document.body.removeChild(successOverlay);
                        }
                    }, 400);
                }, 2000);
                
                // Clear form
                document.getElementById('newDbName').value = '';
                document.getElementById('newDbUsername').value = '';
                document.getElementById('newDbPassword').value = '';
                
                // Refresh localhost databases if connected
                if (isLocalhostConnected) {
                    setTimeout(async () => {
                        try {
                            const databases = await fetchLocalhostDatabases();
                            localStorage.setItem(LOCALHOST_DATABASES_KEY, JSON.stringify(databases));
                            updateLocalhostToggleButton();
                            loadDashboardConnections();
                            showCustomToast(`✅ Database updated in Dashboard!\n🖥️ ${dbName} is now accessible`, 'success', 3000);
                        } catch (error) {
                            console.error('Failed to refresh localhost databases:', error);
                        }
                    }, 2500);
                } else {
                    showCustomToast('💡 Database created! Click "Connect Localhost Laragon" to see it in Dashboard.', 'info', 3500);
                }
            } else {
                showMessage('createMessage', `❌ ${result.message}`, 'error');
            }
        }

        // Delete database (Localhost Laragon only)
        async function deleteDatabase(event) {
            event.preventDefault();
            
            const dbName = document.getElementById('deleteDbSelect').value;
            
            if (!dbName) {
                showMessage('deleteMessage', '❌ Please select a database', 'error');
                return;
            }
            
            if (!confirm(`⚠️ Delete Database from Localhost?\n\nDatabase: "${dbName}"\n\nThis will PERMANENTLY delete the database and ALL its data!\n\nAre you absolutely sure?`)) {
                return;
            }
            
            // Double confirmation
            if (!confirm(`🔴 FINAL WARNING!\n\nDelete "${dbName}" permanently?\n\nThis action CANNOT be undone!`)) {
                return;
            }
            
            showMessage('deleteMessage', '🔄 Deleting database from Localhost Laragon...', 'info');
            
            const result = await apiRequest('delete_database', { 
                db_name: dbName,
                db_host: LOCALHOST_CONFIG.host,
                db_user: LOCALHOST_CONFIG.username,
                db_pass: LOCALHOST_CONFIG.password,
                db_port: LOCALHOST_CONFIG.port
            });
            
            if (result.success) {
                showMessage('deleteMessage', `✅ ${result.message}`, 'success');
                
                // Show success animation
                const successOverlay = document.createElement('div');
                successOverlay.style.cssText = 'position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: linear-gradient(135deg, rgba(239, 68, 68, 0.95) 0%, rgba(220, 38, 38, 0.95) 100%); color: white; padding: 40px 60px; border-radius: 15px; border: 3px solid #fca5a5; z-index: 10000; text-align: center; box-shadow: 0 0 50px rgba(239, 68, 68, 0.7);';
                successOverlay.innerHTML = `
                    <div style="font-size: 72px; margin-bottom: 20px;">✅</div>
                    <div style="font-size: 24px; font-weight: bold; margin-bottom: 12px;">Database Deleted!</div>
                    <div style="font-size: 16px; background: rgba(0,0,0,0.3); padding: 10px 20px; border-radius: 8px; display: inline-block;">
                        🖥️ ${dbName}
                    </div>
                    <div style="font-size: 13px; opacity: 0.8; margin-top: 12px;">Permanently removed from Localhost</div>
                `;
                document.body.appendChild(successOverlay);
                
                setTimeout(() => {
                    document.body.removeChild(successOverlay);
                }, 2500);
                
                // Clear selection
                document.getElementById('deleteDbSelect').value = '';
                
                // Refresh localhost databases if connected
                if (isLocalhostConnected) {
                    setTimeout(async () => {
                        try {
                            const databases = await fetchLocalhostDatabases();
                            localStorage.setItem(LOCALHOST_DATABASES_KEY, JSON.stringify(databases));
                            updateLocalhostToggleButton();
                            loadDatabasesForDropdowns();
                            loadDashboardConnections();
                            showCustomToast(`✅ Database removed from Localhost!\n🖥️ ${dbName} deleted successfully`, 'success', 3000);
                        } catch (error) {
                            console.error('Failed to refresh localhost databases:', error);
                        }
                    }, 2500);
                }
            } else {
                showMessage('deleteMessage', `❌ ${result.message}`, 'error');
            }
        }

        // Rename database (Localhost Laragon only)
        async function renameDatabase(event) {
            event.preventDefault();
            
            const oldName = document.getElementById('renameOldSelect').value;
            const newName = document.getElementById('renameNewName').value.trim();
            
            if (!oldName || !newName) {
                showMessage('renameMessage', '❌ Please provide both old and new database names', 'error');
                return;
            }
            
            if (oldName === newName) {
                showMessage('renameMessage', '❌ New name must be different from old name', 'error');
                return;
            }
            
            // Validate new name
            if (!/^[a-zA-Z0-9_-]+$/.test(newName)) {
                showMessage('renameMessage', '❌ Invalid database name! Use only letters, numbers, underscores, and hyphens.', 'error');
                return;
            }
            
            showMessage('renameMessage', '🔄 Renaming database on Localhost Laragon...', 'info');
            
            const result = await apiRequest('rename_database', {
                old_name: oldName,
                new_name: newName,
                db_host: LOCALHOST_CONFIG.host,
                db_user: LOCALHOST_CONFIG.username,
                db_pass: LOCALHOST_CONFIG.password,
                db_port: LOCALHOST_CONFIG.port
            });
            
            if (result.success) {
                showMessage('renameMessage', `✅ ${result.message}`, 'success');
                
                // Show success animation
                const successOverlay = document.createElement('div');
                successOverlay.style.cssText = 'position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: linear-gradient(135deg, rgba(245, 158, 11, 0.95) 0%, rgba(217, 119, 6, 0.95) 100%); color: white; padding: 40px 60px; border-radius: 15px; border: 3px solid #fbbf24; z-index: 10000; text-align: center; box-shadow: 0 0 50px rgba(245, 158, 11, 0.7);';
                successOverlay.innerHTML = `
                    <div style="font-size: 72px; margin-bottom: 20px;">✅</div>
                    <div style="font-size: 24px; font-weight: bold; margin-bottom: 12px;">Database Renamed!</div>
                    <div style="font-size: 16px; background: rgba(0,0,0,0.3); padding: 10px 20px; border-radius: 8px; display: inline-block;">
                        ${oldName} → ${newName}
                    </div>
                    <div style="font-size: 13px; opacity: 0.8; margin-top: 12px;">🖥️ Updated on Localhost</div>
                `;
                document.body.appendChild(successOverlay);
                
                setTimeout(() => {
                    document.body.removeChild(successOverlay);
                }, 2500);
                
                // Clear form
                document.getElementById('renameOldSelect').value = '';
                document.getElementById('renameNewName').value = '';
                
                // Refresh localhost databases if connected
                if (isLocalhostConnected) {
                    setTimeout(async () => {
                        try {
                            const databases = await fetchLocalhostDatabases();
                            localStorage.setItem(LOCALHOST_DATABASES_KEY, JSON.stringify(databases));
                            updateLocalhostToggleButton();
                            loadDatabasesForDropdowns();
                            loadDashboardConnections();
                            showCustomToast(`✅ Database renamed in Localhost!\n🖥️ ${oldName} → ${newName}`, 'success', 3000);
                        } catch (error) {
                            console.error('Failed to refresh localhost databases:', error);
                        }
                    }, 2500);
                }
            } else {
                showMessage('renameMessage', `❌ ${result.message}`, 'error');
            }
        }

        // Check existing credentials
        function checkExistingCredentials() {
            const dbName = document.getElementById('credentialsDbSelect').value;
            const statusEl = document.getElementById('credentialStatus');
            
            if (!dbName) {
                statusEl.innerHTML = '';
                return;
            }
            
            const savedCreds = getCredentialsForDatabase(dbName);
            
            if (savedCreds) {
                statusEl.innerHTML = `<div class="message info" style="display: block;">ℹ️ This database already has saved credentials (User: ${savedCreds.username}). You can update them below.</div>`;
            } else {
                statusEl.innerHTML = `<div class="message info" style="display: block;">ℹ️ This database has no saved credentials. Add username and password to secure it.</div>`;
            }
        }

        // Set database credentials
        async function setDatabaseCredentials(event) {
            event.preventDefault();
            
            const dbName = document.getElementById('credentialsDbSelect').value;
            const username = document.getElementById('credentialsUsername').value.trim();
            const password = document.getElementById('credentialsPassword').value;
            const confirmPassword = document.getElementById('credentialsConfirmPassword').value;
            
            if (!dbName || !username || !password) {
                showMessage('credentialsMessage', '❌ Please fill in all fields', 'error');
                return;
            }
            
            if (password !== confirmPassword) {
                showMessage('credentialsMessage', '❌ Passwords do not match', 'error');
                return;
            }
            
            showMessage('credentialsMessage', '🔄 Setting credentials...', 'info');
            
            const result = await apiRequest('set_database_credentials', {
                db_name: dbName,
                db_username: username,
                db_password: password
            });
            
            if (result.success) {
                showMessage('credentialsMessage', `✅ ${result.message}`, 'success');
                
                // Save credentials
                saveCredentials(dbName, username, password);
                
                // Reset form
                document.getElementById('credentialsDbSelect').value = '';
                document.getElementById('credentialsUsername').value = '';
                document.getElementById('credentialsPassword').value = '';
                document.getElementById('credentialsConfirmPassword').value = '';
                document.getElementById('credentialStatus').innerHTML = '';
                
                loadDatabases(); // Refresh to show lock icon
            } else {
                showMessage('credentialsMessage', `❌ ${result.message}`, 'error');
            }
        }

        // ========================================
        // TABLE OPERATIONS FUNCTIONS
        // ========================================

        // Update connection status display
        function updateConnectionStatus(dbName) {
            const statusElements = [
                'tableConnectionStatus',
                'createTableConnectionStatus',
                'editTableConnectionStatus',
                'deleteTableConnectionStatus',
                'renameTableConnectionStatus'
            ];
            
            statusElements.forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.textContent = `✅ Connected to database: ${dbName}`;
                    el.classList.add('active');
                }
            });

            // Load tables for dropdowns when database is connected
            loadTablesForDropdowns();
        }

        // Tab switching for create table
        function switchTab(tabName) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            if (tabName === 'manual') {
                document.querySelector('.tab-btn:nth-child(1)').classList.add('active');
                document.getElementById('manualTab').classList.add('active');
            } else if (tabName === 'template') {
                document.querySelector('.tab-btn:nth-child(2)').classList.add('active');
                document.getElementById('templateTab').classList.add('active');
                loadTemplates();
            } else if (tabName === 'migration') {
                document.querySelector('.tab-btn:nth-child(3)').classList.add('active');
                document.getElementById('migrationTab').classList.add('active');
                loadMigrationTables();
                loadDestinationDropdown();
            }
        }

        // Migration: Get emoji based on table name
        function getTableEmoji(tableName) {
            const name = tableName.toLowerCase();
            
            // Common patterns
            if (name.includes('user') || name.includes('account') || name.includes('member')) return '👤';
            if (name.includes('product') || name.includes('item')) return '📦';
            if (name.includes('order') || name.includes('purchase')) return '🛒';
            if (name.includes('payment') || name.includes('transaction')) return '💳';
            if (name.includes('category') || name.includes('categor')) return '📂';
            if (name.includes('post') || name.includes('article') || name.includes('blog')) return '📝';
            if (name.includes('comment')) return '💬';
            if (name.includes('message') || name.includes('mail') || name.includes('email')) return '✉️';
            if (name.includes('image') || name.includes('photo') || name.includes('picture')) return '🖼️';
            if (name.includes('file') || name.includes('document')) return '📄';
            if (name.includes('video')) return '🎥';
            if (name.includes('audio') || name.includes('music')) return '🎵';
            if (name.includes('setting') || name.includes('config')) return '⚙️';
            if (name.includes('log') || name.includes('history')) return '📋';
            if (name.includes('notification') || name.includes('alert')) return '🔔';
            if (name.includes('tag')) return '🏷️';
            if (name.includes('session')) return '🔐';
            if (name.includes('token') || name.includes('api')) return '🔑';
            if (name.includes('report')) return '📊';
            if (name.includes('analytics') || name.includes('statistic')) return '📈';
            if (name.includes('invoice')) return '🧾';
            if (name.includes('cart') || name.includes('basket')) return '🛍️';
            if (name.includes('review') || name.includes('rating')) return '⭐';
            if (name.includes('wishlist') || name.includes('favorite')) return '❤️';
            if (name.includes('address') || name.includes('location')) return '📍';
            if (name.includes('country') || name.includes('city') || name.includes('region')) return '🌍';
            if (name.includes('language')) return '🌐';
            if (name.includes('permission') || name.includes('role')) return '🛡️';
            if (name.includes('backup')) return '💾';
            if (name.includes('cache')) return '⚡';
            if (name.includes('queue') || name.includes('job')) return '📮';
            if (name.includes('event')) return '📅';
            if (name.includes('customer') || name.includes('client')) return '👥';
            if (name.includes('supplier') || name.includes('vendor')) return '🏪';
            if (name.includes('employee') || name.includes('staff')) return '👔';
            if (name.includes('department')) return '🏢';
            if (name.includes('project')) return '📁';
            if (name.includes('task') || name.includes('todo')) return '✅';
            if (name.includes('ticket') || name.includes('support')) return '🎫';
            if (name.includes('feedback')) return '📣';
            if (name.includes('contact')) return '📞';
            if (name.includes('subscription')) return '📬';
            if (name.includes('coupon') || name.includes('discount') || name.includes('promo')) return '🎟️';
            if (name.includes('shipping') || name.includes('delivery')) return '🚚';
            if (name.includes('inventory') || name.includes('stock')) return '📦';
            if (name.includes('warehouse')) return '🏭';
            
            // Default
            return '📊';
        }

        // Migration: Load tables from selected database
        let migrationTables = [];
        let migrationTablesRowCount = {};  // Store row counts
        let selectedMigrationTables = new Set();
        let destinationTables = [];
        let destinationTablesRowCount = {};  // Store row counts
        let selectedDestinationTables = new Set();
        let selectedDestinationId = null;

        // Load row counts for tables (parallel for speed)
        async function loadTableRowCounts(conn, tables, rowCountObject) {
            console.log('Loading row counts for', tables.length, 'tables...');
            
            // Load all row counts in parallel for better performance
            const promises = tables.map(async (tableName) => {
                try {
                    const result = await apiRequest('get_table_data', {
                        db_host: conn.host,
                        db_name: conn.dbName,
                        db_user: conn.username,
                        db_pass: conn.password,
                        db_port: conn.port || '3306',
                        table_name: tableName,
                        page: 1,
                        limit: 1
                    });
                    
                    if (result.success && result.pagination) {
                        rowCountObject[tableName] = result.pagination.total_rows;
                        console.log(`  ✅ ${tableName}: ${result.pagination.total_rows.toLocaleString()} rows`);
                    } else {
                        rowCountObject[tableName] = 0;
                        console.log(`  ⚠️ ${tableName}: 0 rows (or error)`);
                    }
                } catch (error) {
                    console.error(`  ❌ Failed to get row count for ${tableName}:`, error);
                    rowCountObject[tableName] = 0;
                }
            });
            
            await Promise.all(promises);
            console.log('✅ Row counts loaded for all tables');
        }

        async function loadMigrationTables() {
            console.log('=== LOAD MIGRATION TABLES ===');
            console.log('Selected Database ID:', selectedDatabaseId);
            
            if (!selectedDatabaseId) {
                console.warn('⚠️ No database selected');
                document.getElementById('migrationSourceDbName').textContent = 'No database selected';
                document.getElementById('migrationTablesContainer').innerHTML = `
                    <div style="width: 100%; text-align: center; padding: 40px 20px; color: rgba(254, 243, 199, 0.5);">
                        <div style="font-size: 48px; margin-bottom: 15px;">⚠️</div>
                        <p style="font-size: 14px;">Please select a database first from Dashboard</p>
                    </div>
                `;
                updateMigrationCounts();
                return;
            }

            // Get connection using helper function
            const conn = getConnectionById(selectedDatabaseId);
            
            console.log('Connection Info:', conn);
            
            if (!conn) {
                console.error('❌ Connection not found for ID:', selectedDatabaseId);
                document.getElementById('migrationSourceDbName').textContent = 'Connection not found';
                document.getElementById('migrationTablesContainer').innerHTML = `
                    <div style="width: 100%; text-align: center; padding: 40px 20px; color: rgba(254, 243, 199, 0.5);">
                        <div style="font-size: 48px; margin-bottom: 15px;">❌</div>
                        <p style="font-size: 14px;">Connection not found</p>
                    </div>
                `;
                return;
            }

            const serverLabel = conn.isLocalhost ? '🖥️ Localhost' : '🌐 Hostinger';
            const serverType = conn.isLocalhost ? 'Localhost' : 'Hostinger';
            
            console.log(`📡 Fetching migration tables from ${serverType}:`, conn.dbName);
            console.log('Connection details:', { host: conn.host, user: conn.username, port: conn.port });
            
            document.getElementById('migrationSourceDbName').textContent = `${serverLabel}: ${conn.name}`;

            // Show loading state
            document.getElementById('migrationTablesContainer').innerHTML = `
                <div style="width: 100%; text-align: center; padding: 40px 20px; color: rgba(254, 243, 199, 0.5);">
                    <div style="font-size: 48px; margin-bottom: 15px;">⏳</div>
                    <p style="font-size: 14px;">Loading tables from ${serverType}...</p>
                </div>
            `;

            try {
                const result = await apiRequest('list_tables', {
                    db_host: conn.host,
                    db_name: conn.dbName,
                    db_user: conn.username,
                    db_pass: conn.password,
                    db_port: conn.port
                });
                
                console.log('Migration Tables API Result:', result);
                
                if (result.success && result.tables) {
                    migrationTables = result.tables;
                    selectedMigrationTables.clear(); // Clear selections when loading new tables
                    console.log(`✅ Successfully loaded ${migrationTables.length} tables from ${conn.name}`);
                    
                    // Load row counts for each table
                    await loadTableRowCounts(conn, migrationTables, migrationTablesRowCount);
                    
                    renderMigrationTables();
                    updateMigrationCounts();
                    
                    // Update destination dropdown to exclude current source
                    loadDestinationDropdown();
                } else {
                    console.warn('⚠️ No tables found or error:', result);
                    document.getElementById('migrationTablesContainer').innerHTML = `
                        <div style="width: 100%; text-align: center; padding: 40px 20px; color: rgba(254, 243, 199, 0.5);">
                            <div style="font-size: 48px; margin-bottom: 15px;">📭</div>
                            <p style="font-size: 14px;">No tables found in this database</p>
                            <p style="font-size: 12px; margin-top: 10px; color: rgba(254, 243, 199, 0.4);">${result.message || ''}</p>
                        </div>
                    `;
                    migrationTables = [];
                    updateMigrationCounts();
                    showCustomToast(`⚠️ No tables found in ${conn.name}`, 'warning', 3000);
                }
            } catch (error) {
                console.error('❌ Error loading migration tables:', error);
                document.getElementById('migrationTablesContainer').innerHTML = `
                    <div style="width: 100%; text-align: center; padding: 40px 20px; color: rgba(254, 243, 199, 0.5);">
                        <div style="font-size: 48px; margin-bottom: 15px;">❌</div>
                        <p style="font-size: 14px;">Error loading tables</p>
                        <p style="font-size: 12px; margin-top: 10px; color: #fca5a5;">${error.message || 'Unknown error'}</p>
                    </div>
                `;
                migrationTables = [];
                showCustomToast(`❌ Failed to load migration tables\n${error.message}`, 'error', 4000);
            }
        }

        // Migration: Render table boxes
        function renderMigrationTables() {
            const container = document.getElementById('migrationTablesContainer');
            
            console.log('Rendering migration tables:', migrationTables);
            
            if (!container) {
                console.error('Migration tables container not found!');
                return;
            }
            
            if (migrationTables.length === 0) {
                container.innerHTML = `
                    <div style="width: 100%; text-align: center; padding: 40px 20px; color: rgba(254, 243, 199, 0.5);">
                        <div style="font-size: 48px; margin-bottom: 15px;">📭</div>
                        <p style="font-size: 14px;">No tables found in this database</p>
                    </div>
                `;
                return;
            }

            const html = migrationTables.map(tableName => {
                const emoji = getTableEmoji(tableName);
                const isSelected = selectedMigrationTables.has(tableName);
                const rowCount = migrationTablesRowCount[tableName] !== undefined ? migrationTablesRowCount[tableName] : '...';
                
                return `
                    <div class="migration-table-box ${isSelected ? 'selected' : ''}" 
                         data-table="${tableName}"
                         draggable="true"
                         ondragstart="handleDragStart(event, '${tableName}', false)"
                         ondragend="handleDragEnd(event)"
                         style="cursor: grab; position: relative; display: grid; grid-template-rows: auto auto auto; gap: 10px; padding: 10px;">
                        
                        <span class="migration-check-icon" onclick="toggleMigrationTable('${tableName}')">✓</span>
                        
                        <!-- Row 1: 4 Action Buttons Only (Equal Distribution) -->
                        <div style="display: flex; gap: 4px; justify-content: space-between;">
                            <!-- Inject Button -->
                            <button onclick="injectRandomIntoTable('${tableName}'); event.stopPropagation();" title="Inject 10 random records" style="flex: 1; background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%); padding: 8px; border-radius: 6px; border: 1px solid rgba(59, 130, 246, 0.6); cursor: pointer; box-shadow: 0 2px 6px rgba(59, 130, 246, 0.3); transition: all 0.2s;" onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 4px 10px rgba(59, 130, 246, 0.6)';" onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 6px rgba(59, 130, 246, 0.3)';"><span style="font-size: 16px;">🎲</span></button>
                            
                            <!-- Copy Button -->
                            <button onclick="duplicateTable('${tableName}'); event.stopPropagation();" title="Duplicate table" style="flex: 1; background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%); padding: 8px; border-radius: 6px; border: 1px solid rgba(139, 92, 246, 0.6); cursor: pointer; box-shadow: 0 2px 6px rgba(139, 92, 246, 0.3); transition: all 0.2s;" onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 4px 10px rgba(139, 92, 246, 0.6)';" onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 6px rgba(139, 92, 246, 0.3)';"><span style="font-size: 16px;">📋</span></button>
                            
                            <!-- Empty Button -->
                            <button onclick="emptyTableData('${tableName}'); event.stopPropagation();" title="Empty table data" style="flex: 1; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); padding: 8px; border-radius: 6px; border: 1px solid rgba(245, 158, 11, 0.6); cursor: pointer; box-shadow: 0 2px 6px rgba(245, 158, 11, 0.3); transition: all 0.2s;" onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 4px 10px rgba(245, 158, 11, 0.6)';" onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 6px rgba(245, 158, 11, 0.3)';"><span style="font-size: 16px;">🧹</span></button>
                            
                            <!-- Delete Button -->
                            <button onclick="deleteTableFromMigration('${tableName}'); event.stopPropagation();" title="Delete table" style="flex: 1; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); padding: 8px; border-radius: 6px; border: 1px solid rgba(239, 68, 68, 0.6); cursor: pointer; box-shadow: 0 2px 6px rgba(239, 68, 68, 0.3); transition: all 0.2s;" onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 4px 10px rgba(239, 68, 68, 0.6)';" onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 6px rgba(239, 68, 68, 0.3)';"><span style="font-size: 16px;">🗑️</span></button>
                        </div>
                        
                        <!-- Row 2: Row Count + Emoji + Table Name -->
                        <div style="display: grid; grid-template-columns: auto auto 1fr; gap: 8px; align-items: center;">
                            <!-- Row Count Badge -->
                            <div style="background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%); padding: 8px 10px; border-radius: 8px; font-size: 10px; font-weight: bold; color: white; border: 1px solid rgba(59, 130, 246, 0.6); box-shadow: 0 2px 6px rgba(59, 130, 246, 0.3); text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 42px;">
                                <div style="font-size: 12px; margin-bottom: 2px;">📊</div>
                                <div style="font-size: 11px; font-weight: 700;">${typeof rowCount === 'number' ? rowCount.toLocaleString() : rowCount}</div>
                            </div>
                            
                            <!-- Table Emoji -->
                            <div style="font-size: 36px; display: flex; align-items: center; justify-content: center; min-height: 42px;">
                                ${emoji}
                            </div>
                            
                            <!-- Table Name (Takes most space) -->
                            <div oncontextmenu="startRenameTable('${tableName}', this); event.preventDefault(); event.stopPropagation(); return false;"
                                 onclick="event.stopPropagation(); toggleMigrationTable('${tableName}')"
                                 onmouseover="this.style.cursor='context-menu'; this.style.background='rgba(251, 191, 36, 0.2)'; this.style.transform='scale(1.02)';"
                                 onmouseout="this.style.cursor='pointer'; this.style.background='transparent'; this.style.transform='scale(1)';"
                                 title="Right-click to rename"
                                 style="font-size: 16px; font-weight: 700; color: #fef3c7; padding: 8px 12px; border-radius: 6px; cursor: pointer; user-select: none; transition: all 0.2s; text-align: left;">
                                ${tableName}
                            </div>
                        </div>
                        
                        <!-- Row 3: Structure (Left) + Data (Right) -->
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <!-- Structure Label (Left - Draggable) -->
                            <div draggable="true"
                                 ondragstart="handleDragStart(event, '${tableName}', false); event.stopPropagation();"
                                 ondragend="handleDragEnd(event)"
                                 onclick="event.stopPropagation();"
                                 title="🏗️ Drag to move Structure Only"
                                 style="background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); padding: 6px 12px; border-radius: 6px; border: 1px solid rgba(251, 191, 36, 0.6); cursor: grab; font-size: 11px; font-weight: bold; color: white; box-shadow: 0 2px 6px rgba(251, 191, 36, 0.3); transition: all 0.2s; text-shadow: 0 1px 2px rgba(0,0,0,0.3);"
                                 onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 4px 10px rgba(251, 191, 36, 0.5)';"
                                 onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 6px rgba(251, 191, 36, 0.3)';">
                                🏗️ Structure
                            </div>
                            
                            <!-- Data Badge (Right - Draggable) -->
                            <div draggable="true"
                                 ondragstart="handleDragStart(event, '${tableName}', true); event.stopPropagation();"
                                 ondragend="handleDragEnd(event)"
                                 onclick="event.stopPropagation();"
                                 title="✋ Drag for Structure + Data"
                                 style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 6px 12px; border-radius: 6px; cursor: grab; border: 1px solid rgba(16, 185, 129, 0.6); box-shadow: 0 2px 6px rgba(16, 185, 129, 0.3); transition: all 0.2s; font-size: 11px; font-weight: bold; color: white; text-shadow: 0 1px 2px rgba(0,0,0,0.3);"
                                 onmouseover="this.style.background='linear-gradient(135deg, #22c55e 0%, #15803d 100%)'; this.style.transform='scale(1.1)'; this.style.cursor='grab'; this.style.boxShadow='0 4px 10px rgba(34, 197, 94, 0.5)';"
                                 onmouseout="this.style.background='linear-gradient(135deg, #10b981 0%, #059669 100%)'; this.style.transform='scale(1)'; this.style.boxShadow='0 2px 6px rgba(16, 185, 129, 0.3)';">
                                ✋ Data
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
            
            console.log('Generated HTML length:', html.length);
            container.innerHTML = html;
        }

        // Migration: Toggle table selection
        function toggleMigrationTable(tableName) {
            if (selectedMigrationTables.has(tableName)) {
                selectedMigrationTables.delete(tableName);
            } else {
                selectedMigrationTables.add(tableName);
            }
            renderMigrationTables();
            updateMigrationCounts();
        }

        // Migration: Select all tables
        function selectAllMigrationTables() {
            selectedMigrationTables = new Set(migrationTables);
            renderMigrationTables();
            updateMigrationCounts();
        }

        // Migration: Deselect all tables
        function deselectAllMigrationTables() {
            selectedMigrationTables.clear();
            renderMigrationTables();
            updateMigrationCounts();
        }

        // Migration: Refresh tables
        function refreshMigrationTables() {
            selectedMigrationTables.clear();
            loadMigrationTables();
        }

        // Migration: Update counts
        function updateMigrationCounts() {
            document.getElementById('migrationSelectedCount').textContent = 
                `${selectedMigrationTables.size} table${selectedMigrationTables.size !== 1 ? 's' : ''} selected`;
            document.getElementById('migrationTotalCount').textContent = 
                `${migrationTables.length} table${migrationTables.length !== 1 ? 's' : ''} total`;
        }

        // ========================================
        // MIGRATION: DESTINATION DATABASE
        // ========================================

        // Load destination database dropdown
        function loadDestinationDropdown() {
            const select = document.getElementById('migrationDestinationSelect');
            if (!select) return;

            // Save current selection
            const currentSelection = select.value;
            console.log('Current destination selection before reload:', currentSelection);

            // Clear and rebuild dropdown
            select.innerHTML = '<option value="">-- Select Destination --</option>';

            // Add localhost databases (if connected)
            if (isLocalhostConnected) {
                const localhostDbs = getLocalhostDatabases();
                if (localhostDbs.length > 0) {
                    const optgroup = document.createElement('optgroup');
                    optgroup.label = '🖥️ LOCALHOST (LARAGON)';
                    
                    localhostDbs.forEach(dbName => {
                        const localhostId = `localhost_${dbName}`;
                        
                        // Skip if this is the source
                        if (selectedDatabaseId === localhostId) {
                            return;
                        }
                        
                        const option = document.createElement('option');
                        option.value = localhostId;
                        option.textContent = `🖥️ ${dbName} (Localhost)`;
                        option.style.background = 'rgba(0, 0, 0, 0.8)';
                        option.style.color = '#93c5fd';
                        optgroup.appendChild(option);
                    });
                    
                    if (optgroup.children.length > 0) {
                        select.appendChild(optgroup);
                    }
                }
            }

            // Add Hostinger connections (if connected)
            if (isHostingerConnected) {
                const allConnections = getHostingerConnections();
                
                if (allConnections.length > 0) {
                    const optgroup = document.createElement('optgroup');
                    optgroup.label = '🌐 HOSTINGER';
                    
                    allConnections.forEach(conn => {
                        const connId = `conn_${conn.id}`;
                        
                        // Skip if this is the source
                        if (selectedDatabaseId === connId) {
                            return;
                        }

                        const option = document.createElement('option');
                        option.value = connId;
                        option.textContent = `🌐 ${conn.name} (${conn.dbName})`;
                        option.style.background = 'rgba(0, 0, 0, 0.8)';
                        option.style.color = '#fef3c7';
                        optgroup.appendChild(option);
                    });
                    
                    if (optgroup.children.length > 0) {
                        select.appendChild(optgroup);
                    }
                }
            }

            // Restore previous selection if it still exists
            if (currentSelection && currentSelection !== '') {
                const optionExists = Array.from(select.options).some(opt => opt.value === currentSelection);
                if (optionExists) {
                    select.value = currentSelection;
                    console.log('Restored destination selection:', currentSelection);
                } else {
                    console.log('Previous selection no longer available:', currentSelection);
                }
            }

            console.log('Destination dropdown loaded with Localhost + Hostinger, excluded source:', selectedDatabaseId);
        }

        // Load destination tables
        async function loadDestinationTables() {
            const select = document.getElementById('migrationDestinationSelect');
            const destId = select ? select.value : null;

            console.log('=== LOAD DESTINATION TABLES ===');
            console.log('Destination ID:', destId);
            console.log('Current selectedDestinationId:', selectedDestinationId);

            if (!destId) {
                console.warn('⚠️ No destination selected');
                // Reset destination display
                document.getElementById('migrationDestinationInfo').style.display = 'none';
                document.getElementById('migrationDestinationTablesContainer').innerHTML = `
                    <div style="width: 100%; text-align: center; padding: 40px 20px; color: rgba(254, 243, 199, 0.5);">
                        <div style="font-size: 48px; margin-bottom: 15px;">🎯</div>
                        <p style="font-size: 14px;">Select a destination database</p>
                    </div>
                `;
                selectedDestinationId = null;
                destinationTables = [];
                selectedDestinationTables.clear();
                updateDestinationCount();
                return;
            }

            selectedDestinationId = destId;
            console.log('Set selectedDestinationId to:', selectedDestinationId);
            selectedDestinationTables.clear(); // Clear selections when loading new destination

            // Get connection using helper function
            const conn = getConnectionById(destId);
            
            console.log('Destination Connection Info:', conn);

            if (!conn) {
                console.error('❌ Destination connection not found:', destId);
                return;
            }

            const serverType = conn.isLocalhost ? 'Localhost' : 'Hostinger';
            console.log(`📡 Fetching destination tables from ${serverType}:`, conn.dbName);

            // Show destination info
            const serverLabel = conn.isLocalhost ? '🖥️ Localhost' : '🌐 Hostinger';
            document.getElementById('migrationDestinationInfo').style.display = 'block';
            document.getElementById('migrationDestDbName').textContent = `${serverLabel} ${conn.name}`;
            document.getElementById('migrationDestHost').textContent = `Host: ${conn.host} | DB: ${conn.dbName}`;

            // Show loading state
            document.getElementById('migrationDestinationTablesContainer').innerHTML = `
                <div style="width: 100%; text-align: center; padding: 40px 20px; color: rgba(254, 243, 199, 0.5);">
                    <div style="font-size: 48px; margin-bottom: 15px;">⏳</div>
                    <p style="font-size: 14px;">Loading tables from ${serverType}...</p>
                </div>
            `;

            try {
                const result = await apiRequest('list_tables', {
                    db_host: conn.host,
                    db_name: conn.dbName,
                    db_user: conn.username,
                    db_pass: conn.password,
                    db_port: conn.port
                });

                console.log('Destination Tables API Result:', result);

                if (result.success && result.tables) {
                    destinationTables = result.tables;
                    console.log(`✅ Successfully loaded ${destinationTables.length} destination tables from ${conn.name}`);
                    
                    // Load row counts for destination tables
                    await loadTableRowCounts(conn, destinationTables, destinationTablesRowCount);
                    
                    renderDestinationTables();
                    updateDestinationCount();
                } else {
                    console.warn('⚠️ No tables in destination or error:', result);
                    document.getElementById('migrationDestinationTablesContainer').innerHTML = `
                        <div style="width: 100%; text-align: center; padding: 40px 20px; color: rgba(254, 243, 199, 0.5);">
                            <div style="font-size: 48px; margin-bottom: 15px;">📭</div>
                            <p style="font-size: 14px;">No tables in destination database</p>
                            <p style="font-size: 12px; margin-top: 10px; color: rgba(34, 197, 94, 0.7);">✓ Empty database - perfect for migration!</p>
                        </div>
                    `;
                    destinationTables = [];
                    updateDestinationCount();
                }
            } catch (error) {
                console.error('❌ Error loading destination tables:', error);
                document.getElementById('migrationDestinationTablesContainer').innerHTML = `
                    <div style="width: 100%; text-align: center; padding: 40px 20px; color: rgba(254, 243, 199, 0.5);">
                        <div style="font-size: 48px; margin-bottom: 15px;">❌</div>
                        <p style="font-size: 14px;">Error loading destination tables</p>
                        <p style="font-size: 12px; margin-top: 10px; color: #fca5a5;">${error.message || 'Unknown error'}</p>
                    </div>
                `;
                destinationTables = [];
                showCustomToast(`❌ Failed to load destination tables\n${error.message}`, 'error', 4000);
            }
        }

        // Render destination tables (FULL FUNCTIONALITY - Same as Source but reversed drag)
        function renderDestinationTables() {
            const container = document.getElementById('migrationDestinationTablesContainer');

            if (!container) {
                console.error('Destination tables container not found!');
                return;
            }

            if (destinationTables.length === 0) {
                container.innerHTML = `
                    <div style="width: 100%; text-align: center; padding: 40px 20px; color: rgba(254, 243, 199, 0.5);">
                        <div style="font-size: 48px; margin-bottom: 15px;">📭</div>
                        <p style="font-size: 14px;">No tables in destination database</p>
                        <p style="font-size: 12px; margin-top: 10px; color: rgba(34, 197, 94, 0.7);">✓ Empty database - perfect for migration!</p>
                    </div>
                `;
                return;
            }

            const html = destinationTables.map(tableName => {
                const emoji = getTableEmoji(tableName);
                const isSelected = selectedDestinationTables.has(tableName);
                const rowCount = destinationTablesRowCount[tableName] !== undefined ? destinationTablesRowCount[tableName] : '...';

                return `
                    <div class="migration-table-box ${isSelected ? 'selected' : ''}" 
                         data-table="${tableName}"
                         draggable="true"
                         ondragstart="handleDestinationDragStart(event, '${tableName}', false)"
                         ondragend="handleDestinationDragEnd(event)"
                         style="cursor: grab; position: relative; display: grid; grid-template-rows: auto auto auto; gap: 10px; padding: 10px; border-color: rgba(34, 197, 94, 0.4); background: linear-gradient(135deg, rgba(34, 197, 94, 0.1) 0%, rgba(34, 197, 94, 0.05) 100%);">
                        
                        <span class="migration-check-icon" onclick="toggleDestinationTable('${tableName}')" style="background: #22c55e;">✓</span>
                        
                        <!-- Row 1: 4 Action Buttons Only (Equal Distribution) -->
                        <div style="display: flex; gap: 4px; justify-content: space-between;">
                            <!-- Inject Button -->
                            <button onclick="injectRandomIntoDestinationTable('${tableName}'); event.stopPropagation();" title="Inject 10 random records" style="flex: 1; background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%); padding: 8px; border-radius: 6px; border: 1px solid rgba(59, 130, 246, 0.6); cursor: pointer; box-shadow: 0 2px 6px rgba(59, 130, 246, 0.3); transition: all 0.2s;" onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 4px 10px rgba(59, 130, 246, 0.6)';" onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 6px rgba(59, 130, 246, 0.3)';"><span style="font-size: 16px;">🎲</span></button>
                            
                            <!-- Copy Button -->
                            <button onclick="duplicateDestinationTable('${tableName}'); event.stopPropagation();" title="Duplicate table" style="flex: 1; background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%); padding: 8px; border-radius: 6px; border: 1px solid rgba(139, 92, 246, 0.6); cursor: pointer; box-shadow: 0 2px 6px rgba(139, 92, 246, 0.3); transition: all 0.2s;" onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 4px 10px rgba(139, 92, 246, 0.6)';" onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 6px rgba(139, 92, 246, 0.3)';"><span style="font-size: 16px;">📋</span></button>
                            
                            <!-- Empty Button -->
                            <button onclick="emptyDestinationTableData('${tableName}'); event.stopPropagation();" title="Empty table data" style="flex: 1; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); padding: 8px; border-radius: 6px; border: 1px solid rgba(245, 158, 11, 0.6); cursor: pointer; box-shadow: 0 2px 6px rgba(245, 158, 11, 0.3); transition: all 0.2s;" onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 4px 10px rgba(245, 158, 11, 0.6)';" onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 6px rgba(245, 158, 11, 0.3)';"><span style="font-size: 16px;">🧹</span></button>
                            
                            <!-- Delete Button -->
                            <button onclick="deleteDestinationTableFromMigration('${tableName}'); event.stopPropagation();" title="Delete table" style="flex: 1; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); padding: 8px; border-radius: 6px; border: 1px solid rgba(239, 68, 68, 0.6); cursor: pointer; box-shadow: 0 2px 6px rgba(239, 68, 68, 0.3); transition: all 0.2s;" onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 4px 10px rgba(239, 68, 68, 0.6)';" onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 6px rgba(239, 68, 68, 0.3)';"><span style="font-size: 16px;">🗑️</span></button>
                        </div>
                        
                        <!-- Row 2: Row Count + Emoji + Table Name -->
                        <div style="display: grid; grid-template-columns: auto auto 1fr; gap: 8px; align-items: center;">
                            <!-- Row Count Badge -->
                            <div style="background: linear-gradient(135deg, #22c55e 0%, #15803d 100%); padding: 8px 10px; border-radius: 8px; font-size: 10px; font-weight: bold; color: white; border: 1px solid rgba(34, 197, 94, 0.6); box-shadow: 0 2px 6px rgba(34, 197, 94, 0.3); text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 42px;">
                                <div style="font-size: 12px; margin-bottom: 2px;">📊</div>
                                <div style="font-size: 11px; font-weight: 700;">${typeof rowCount === 'number' ? rowCount.toLocaleString() : rowCount}</div>
                            </div>
                            
                            <!-- Table Emoji -->
                            <div style="font-size: 36px; display: flex; align-items: center; justify-content: center; min-height: 42px;">
                                ${emoji}
                            </div>
                            
                            <!-- Table Name (Takes most space) -->
                            <div oncontextmenu="startRenameDestinationTable('${tableName}', this); event.preventDefault(); event.stopPropagation(); return false;"
                                 onclick="event.stopPropagation(); toggleDestinationTable('${tableName}')"
                                 onmouseover="this.style.cursor='context-menu'; this.style.background='rgba(34, 197, 94, 0.2)'; this.style.transform='scale(1.02)';"
                                 onmouseout="this.style.cursor='pointer'; this.style.background='transparent'; this.style.transform='scale(1)';"
                                 title="Right-click to rename"
                                 style="font-size: 16px; font-weight: 700; color: #86efac; padding: 8px 12px; border-radius: 6px; cursor: pointer; user-select: none; transition: all 0.2s; text-align: left;">
                                ${tableName}
                            </div>
                        </div>
                        
                        <!-- Row 3: Structure (Left) + Data (Right) - DRAGGABLE TO SOURCE -->
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <!-- Structure Label (Left - Draggable to Source) -->
                            <div draggable="true"
                                 ondragstart="handleDestinationDragStart(event, '${tableName}', false); event.stopPropagation();"
                                 ondragend="handleDestinationDragEnd(event)"
                                 onclick="event.stopPropagation();"
                                 title="🏗️ Drag to move Structure Only to Source"
                                 style="background: linear-gradient(135deg, #22c55e 0%, #15803d 100%); padding: 6px 12px; border-radius: 6px; border: 1px solid rgba(34, 197, 94, 0.6); cursor: grab; font-size: 11px; font-weight: bold; color: white; box-shadow: 0 2px 6px rgba(34, 197, 94, 0.3); transition: all 0.2s; text-shadow: 0 1px 2px rgba(0,0,0,0.3);"
                                 onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 4px 10px rgba(34, 197, 94, 0.5)';"
                                 onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 6px rgba(34, 197, 94, 0.3)';">
                                🏗️ Structure
                            </div>
                            
                            <!-- Data Badge (Right - Draggable to Source) -->
                            <div draggable="true"
                                 ondragstart="handleDestinationDragStart(event, '${tableName}', true); event.stopPropagation();"
                                 ondragend="handleDestinationDragEnd(event)"
                                 onclick="event.stopPropagation();"
                                 title="✋ Drag for Structure + Data to Source"
                                 style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 6px 12px; border-radius: 6px; cursor: grab; border: 1px solid rgba(16, 185, 129, 0.6); box-shadow: 0 2px 6px rgba(16, 185, 129, 0.3); transition: all 0.2s; font-size: 11px; font-weight: bold; color: white; text-shadow: 0 1px 2px rgba(0,0,0,0.3);"
                                 onmouseover="this.style.background='linear-gradient(135deg, #22c55e 0%, #15803d 100%)'; this.style.transform='scale(1.1)'; this.style.cursor='grab'; this.style.boxShadow='0 4px 10px rgba(34, 197, 94, 0.5)';"
                                 onmouseout="this.style.background='linear-gradient(135deg, #10b981 0%, #059669 100%)'; this.style.transform='scale(1)'; this.style.boxShadow='0 2px 6px rgba(16, 185, 129, 0.3)';">
                                ✋ Data
                            </div>
                        </div>
                    </div>
                `;
            }).join('');

            container.innerHTML = html;
        }

        // Update destination count
        function updateDestinationCount() {
            const selectedCount = selectedDestinationTables.size;
            const totalCount = destinationTables.length;
            
            document.getElementById('migrationDestinationSelectedCount').textContent = 
                `${selectedCount} table${selectedCount !== 1 ? 's' : ''} selected`;
            document.getElementById('migrationDestinationCount').textContent = 
                `${totalCount} table${totalCount !== 1 ? 's' : ''} total`;
        }

        // Toggle destination table selection
        function toggleDestinationTable(tableName) {
            if (selectedDestinationTables.has(tableName)) {
                selectedDestinationTables.delete(tableName);
            } else {
                selectedDestinationTables.add(tableName);
            }
            renderDestinationTables();
            updateDestinationCount();
        }

        // Select all destination tables
        function selectAllDestinationTables() {
            selectedDestinationTables.clear();
            destinationTables.forEach(tableName => {
                selectedDestinationTables.add(tableName);
            });
            renderDestinationTables();
            updateDestinationCount();
        }

        // Deselect all destination tables
        function deselectAllDestinationTables() {
            selectedDestinationTables.clear();
            renderDestinationTables();
            updateDestinationCount();
        }

        // Refresh destination tables
        function refreshDestinationTables() {
            loadDestinationTables();
        }

        // Start inline rename for table
        function startRenameTable(oldName, element) {
            console.log('✏️ Starting rename for:', oldName);
            console.log('Element:', element);
            
            // Prevent if already renaming
            if (element.querySelector('input')) {
                console.log('Already renaming, ignoring');
                return;
            }
            
            // Store original content
            const originalContent = element.innerHTML;
            console.log('Original content:', originalContent);
            
            // Create input element using innerHTML (more reliable)
            element.innerHTML = `
                <input type="text" 
                       value="${oldName}" 
                       placeholder="${oldName}"
                       class="rename-input"
                       style="width: 100%; padding: 6px 10px; background: linear-gradient(135deg, rgba(251, 191, 36, 0.3) 0%, rgba(245, 158, 11, 0.2) 100%); border: 2px solid #fbbf24; border-radius: 6px; color: #fef3c7; font-size: 14px; font-weight: 600; text-align: center; outline: none; box-shadow: 0 0 15px rgba(251, 191, 36, 0.5);">
            `;
            
            // Get the input element that was just created
            const input = element.querySelector('input');
            
            console.log('✅ Input created via innerHTML');
            console.log('Input value:', input.value);
            console.log('Input element:', input);
            
            // Focus and select
            setTimeout(() => {
                input.focus();
                input.select();
                console.log('✅ Input focused and selected');
                console.log('Input is focused:', document.activeElement === input);
                console.log('Input current value:', input.value);
            }, 50);
            
            // Handle Enter key (save)
            input.addEventListener('keydown', async function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const newName = input.value.trim();
                    
                    if (!newName) {
                        alert('❌ Table name cannot be empty!');
                        input.focus();
                        return;
                    }
                    
                    if (newName === oldName) {
                        // No change, just restore
                        element.innerHTML = originalContent;
                        return;
                    }
                    
                    // Validate table name
                    if (!/^[a-zA-Z0-9_]+$/.test(newName)) {
                        alert('❌ Table name can only contain letters, numbers, and underscores!');
                        input.focus();
                        return;
                    }
                    
                    // Check if new name already exists
                    if (migrationTables.includes(newName)) {
                        alert(`❌ Table "${newName}" already exists!`);
                        input.focus();
                        return;
                    }
                    
                    // Perform rename
                    await performTableRename(oldName, newName, element, originalContent);
                } else if (e.key === 'Escape') {
                    // Cancel rename
                    element.innerHTML = originalContent;
                }
            });
            
            // Handle blur (clicking outside)
            input.addEventListener('blur', function() {
                setTimeout(() => {
                    if (element.contains(input)) {
                        element.innerHTML = originalContent;
                    }
                }, 200);
            });
        }

        // Perform table rename
        async function performTableRename(oldName, newName, element, originalContent) {
            if (!selectedDatabaseId) {
                alert('❌ No database selected');
                element.innerHTML = originalContent;
                return;
            }

            // Use getConnectionById to support BOTH Localhost and Hostinger
            const sourceConn = getConnectionById(selectedDatabaseId);

            if (!sourceConn) {
                alert('❌ Connection not found');
                element.innerHTML = originalContent;
                return;
            }

            // Show loading in element
            element.innerHTML = '<div style="font-size: 11px; color: #93c5fd;">Renaming...</div>';

            try {
                console.log('Renaming table:', oldName, '→', newName);
                
                const result = await apiRequest('rename_table', {
                    db_host: sourceConn.host,
                    db_name: sourceConn.dbName,
                    db_user: sourceConn.username,
                    db_pass: sourceConn.password,
                    db_port: sourceConn.port || '3306',
                    old_table_name: oldName,
                    new_table_name: newName
                });

                if (!result.success) {
                    throw new Error(result.message || 'Failed to rename table');
                }

                console.log('✅ Table renamed successfully');

                // Show brief success indicator
                element.innerHTML = `<div style="color: #22c55e; font-size: 12px;">✅ Renamed!</div>`;
                
                // Refresh tables after short delay
                setTimeout(async () => {
                    await loadMigrationTables();
                    
                    // Show success toast
                    const toast = document.createElement('div');
                    toast.style.cssText = 'position: fixed; bottom: 30px; right: 30px; background: linear-gradient(135deg, #22c55e 0%, #15803d 100%); color: white; padding: 15px 25px; border-radius: 10px; border: 2px solid #86efac; z-index: 10000; box-shadow: 0 5px 20px rgba(34, 197, 94, 0.5); animation: slideInRight 0.3s ease-out;';
                    toast.innerHTML = `
                        <div style="font-size: 14px; font-weight: bold;">✅ Table Renamed!</div>
                        <div style="font-size: 12px; margin-top: 5px; opacity: 0.9;">${oldName} → ${newName}</div>
                        <style>
                            @keyframes slideInRight {
                                from { transform: translateX(400px); opacity: 0; }
                                to { transform: translateX(0); opacity: 1; }
                            }
                        </style>
                    `;
                    document.body.appendChild(toast);
                    
                    setTimeout(() => {
                        toast.style.animation = 'slideOutRight 0.3s ease-out forwards';
                        toast.innerHTML += '<style>@keyframes slideOutRight { from { transform: translateX(0); opacity: 1; } to { transform: translateX(400px); opacity: 0; } }</style>';
                        setTimeout(() => {
                            if (toast.parentNode) {
                                document.body.removeChild(toast);
                            }
                        }, 300);
                    }, 2500);
                }, 500);

            } catch (error) {
                console.error('❌ Rename error:', error);
                element.innerHTML = originalContent;
                alert(`❌ Rename Failed!\n\n${error.message}`);
            }
        }

        // Show database info (credentials only or with selected tables)
        async function showDatabaseInfo() {
            console.log('=== SHOW DATABASE INFO ===');
            console.log('selectedDatabaseId:', selectedDatabaseId);
            console.log('selectedMigrationTables:', selectedMigrationTables);
            
            if (!selectedDatabaseId) {
                alert('❌ No database selected\n\nPlease select a database first from Dashboard.');
                return;
            }

            // Use getConnectionById to support BOTH Localhost and Hostinger
            const conn = getConnectionById(selectedDatabaseId);

            console.log('Database connection:', conn);

            if (!conn) {
                alert('❌ Connection not found');
                return;
            }

            // Show modal - using separate database info modal
            const modal = document.getElementById('databaseInfoModal');
            console.log('Database Info Modal found:', !!modal);
            
            if (!modal) {
                alert('Error: Modal not found in DOM');
                return;
            }
            
            modal.classList.add('active');
            console.log('Database Info Modal opened');
            
            const titleEl = document.getElementById('databaseInfoDbName');
            const textArea = document.getElementById('databaseInfoText');
            
            console.log('Elements:', {
                title: !!titleEl,
                textArea: !!textArea
            });
            
            if (titleEl) titleEl.textContent = conn.dbName;
            if (textArea) textArea.value = 'Loading database information...';

            // Build database credentials prompt
            let dbPrompt = `DATABASE CONNECTION INFORMATION FOR AI
=====================================

🗄️ DATABASE CREDENTIALS:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Connection Name: ${conn.name}
Host: ${conn.host}
Database Name: ${conn.dbName}
Username: ${conn.username}
Password: ${conn.password}
Port: ${conn.port || '3306'}
Server Type: ${conn.type === 'vps' ? 'VPS Server' : 'Shared Hosting'}

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
`;

            // Check if any tables are selected
            if (selectedMigrationTables && selectedMigrationTables.size > 0) {
                console.log('📊 Tables selected, fetching table details...');
                
                dbPrompt += `
📊 SELECTED TABLES (${selectedMigrationTables.size}):
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

`;
                
                try {
                    const selectedTablesArray = Array.from(selectedMigrationTables);
                    
                    for (let i = 0; i < selectedTablesArray.length; i++) {
                        const tableName = selectedTablesArray[i];
                        console.log(`Fetching info for table ${i + 1}/${selectedTablesArray.length}: ${tableName}`);
                        
                        // Update progress in textarea
                        if (textArea) {
                            textArea.value = `Loading database information...\n\nFetching table ${i + 1}/${selectedTablesArray.length}: ${tableName}`;
                        }
                        
                        try {
                            // Get table structure
                            const structResult = await apiRequest('get_table_structure', {
                                db_host: conn.host,
                                db_name: conn.dbName,
                                db_user: conn.username,
                                db_pass: conn.password,
                                db_port: conn.port || '3306',
                                table_name: tableName
                            });

                            // Get table data count
                            const dataResult = await apiRequest('get_table_data', {
                                db_host: conn.host,
                                db_name: conn.dbName,
                                db_user: conn.username,
                                db_pass: conn.password,
                                db_port: conn.port || '3306',
                                table_name: tableName,
                                page: 1,
                                limit: 5
                            });

                            if (structResult.success && dataResult.success) {
                                const rowCount = dataResult.pagination.total_rows;
                                const engine = dataResult.table_info.engine;
                                const collation = dataResult.table_info.collation;
                                const dataSize = formatBytes(dataResult.table_info.data_length);
                                
                                dbPrompt += `
${i + 1}. TABLE: ${tableName}
   ├─ Total Records: ${rowCount.toLocaleString()}
   ├─ Columns: ${structResult.columns.length}
   ├─ Engine: ${engine}
   ├─ Collation: ${collation}
   ├─ Data Size: ${dataSize}
   └─ Structure:
`;
                                
                                structResult.columns.forEach((col, idx) => {
                                    const keyInfo = col.Key ? 
                                        (col.Key === 'PRI' ? ' [PRIMARY KEY]' : 
                                         col.Key === 'UNI' ? ' [UNIQUE]' : 
                                         col.Key === 'MUL' ? ' [INDEX]' : '') : '';
                                    const nullInfo = col.Null === 'YES' ? ' NULL' : ' NOT NULL';
                                    const defaultInfo = col.Default !== null ? ` DEFAULT '${col.Default}'` : '';
                                    const extraInfo = col.Extra ? ` ${col.Extra.toUpperCase()}` : '';
                                    
                                    dbPrompt += `      ${idx + 1}. ${col.Field} - ${col.Type}${nullInfo}${defaultInfo}${extraInfo}${keyInfo}\n`;
                                });
                                
                                dbPrompt += '\n';
                            } else {
                                dbPrompt += `
${i + 1}. TABLE: ${tableName}
   └─ Error: ${structResult.message || dataResult.message || 'Could not fetch table info'}

`;
                            }
                        } catch (tableError) {
                            console.error(`Error fetching table ${tableName}:`, tableError);
                            dbPrompt += `
${i + 1}. TABLE: ${tableName}
   └─ Error: ${tableError.message}

`;
                        }
                    }
                    
                    dbPrompt += `━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

`;
                } catch (error) {
                    console.error('Error fetching table details:', error);
                    dbPrompt += `
⚠️ Error loading table details: ${error.message}

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

`;
                }
            }

            // Add connection examples
            const phpOpen = '<' + '?php';
            const phpClose = '?' + '>';
            
            dbPrompt += `
📋 CONNECTION STRING (PHP - PDO):
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

\`\`\`php
${phpOpen}
// Database configuration
$host = '${conn.host}';
$dbname = '${conn.dbName}';
$username = '${conn.username}';
$password = '${conn.password}';
$port = '${conn.port || '3306'}';
$charset = 'utf8mb4';

// PDO Connection
try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, $username, $password, $options);
    echo "✅ Connected successfully to database: $dbname";
} catch (PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage();
    exit;
}
${phpClose}
\`\`\`

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

📋 CONNECTION STRING (MySQLi):
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

\`\`\`php
${phpOpen}
$host = '${conn.host}';
$dbname = '${conn.dbName}';
$username = '${conn.username}';
$password = '${conn.password}';
$port = ${conn.port || '3306'};

// MySQLi Connection
$mysqli = new mysqli($host, $username, $password, $dbname, $port);

if ($mysqli->connect_error) {
    die("❌ Connection failed: " . $mysqli->connect_error);
}

$mysqli->set_charset("utf8mb4");
echo "✅ Connected successfully";
${phpClose}
\`\`\`

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

📋 CONNECTION STRING (Node.js - MySQL2):
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

\`\`\`javascript
const mysql = require('mysql2/promise');

const dbConfig = {
    host: '${conn.host}',
    port: ${conn.port || '3306'},
    user: '${conn.username}',
    password: '${conn.password}',
    database: '${conn.dbName}',
    charset: 'utf8mb4',
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0
};

// Create connection pool
const pool = mysql.createPool(dbConfig);

// Test connection
async function testConnection() {
    try {
        const connection = await pool.getConnection();
        console.log('✅ Connected to database:', '${conn.dbName}');
        connection.release();
    } catch (error) {
        console.error('❌ Connection failed:', error.message);
    }
}

testConnection();
\`\`\`

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

📋 CONNECTION STRING (Python - MySQL Connector):
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

\`\`\`python
import mysql.connector
from mysql.connector import Error

def create_connection():
    try:
        connection = mysql.connector.connect(
            host='${conn.host}',
            port=${conn.port || '3306'},
            database='${conn.dbName}',
            user='${conn.username}',
            password='${conn.password}',
            charset='utf8mb4'
        )
        if connection.is_connected():
            print('✅ Connected to database: ${conn.dbName}')
            return connection
    except Error as e:
        print(f'❌ Connection failed: {e}')
        return None

# Usage
db = create_connection()
\`\`\`

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

💡 IMPORTANT NOTES FOR AI:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

✅ Use these exact credentials to connect
✅ Default charset: utf8mb4 (supports emojis & international chars)
✅ Always use prepared statements for security
✅ Port ${conn.port || '3306'} is the MySQL/MariaDB port
✅ Host may require SSL/TLS connection
✅ Keep credentials secure - never expose in client-side code`;

            if (selectedMigrationTables && selectedMigrationTables.size > 0) {
                dbPrompt += `
✅ ${selectedMigrationTables.size} table(s) selected - structure details included above`;
            }

            dbPrompt += `

🔒 SECURITY REMINDERS:
- Store credentials in environment variables (.env file)
- Never commit credentials to version control
- Use application-specific database users with minimal privileges
- Enable SSL/TLS for production connections
- Rotate passwords regularly

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

📊 CONNECTION SUMMARY:
Server: ${conn.host}:${conn.port || '3306'}
Database: ${conn.dbName}`;

            if (selectedMigrationTables && selectedMigrationTables.size > 0) {
                dbPrompt += `
Selected Tables: ${selectedMigrationTables.size}`;
            }

            dbPrompt += `
Status: Ready for AI integration

You can now use these credentials to build your application!
`;

            if (textArea) {
                textArea.value = dbPrompt;
                console.log('✅ Database info displayed');
            }

            console.log('✅ Database info modal opened');
        }

        // Close database info modal
        function closeDatabaseInfoModal() {
            document.getElementById('databaseInfoModal').classList.remove('active');
        }

        // Copy database info text
        async function copyDatabaseInfoText() {
            const text = document.getElementById('databaseInfoText').value;
            
            try {
                await navigator.clipboard.writeText(text);
                showCustomToast('Database information copied to clipboard successfully!', 'success', 3000);
            } catch (err) {
                // Fallback for older browsers
                try {
                    const textarea = document.getElementById('databaseInfoText');
                    textarea.select();
                    document.execCommand('copy');
                    showCustomToast('Database information copied to clipboard!', 'success', 3000);
                } catch (fallbackErr) {
                    showCustomToast('Failed to copy. Please copy manually.', 'error', 3000);
                }
            }
        }

        // Inject random records into specific table (called from button in card)
        async function injectRandomIntoTable(tableName) {
            if (!selectedDatabaseId) {
                alert('❌ No database selected');
                return;
            }

            console.log('🎲 Injecting random data into:', tableName);

            // Get connection info (supports both Localhost and Hostinger)
            const conn = getConnectionById(selectedDatabaseId);

            if (!conn) {
                alert('❌ Connection not found');
                return;
            }

            // Show loading toast
            const loadingToast = document.createElement('div');
            loadingToast.id = 'injectLoadingToast';
            loadingToast.style.cssText = 'position: fixed; bottom: 30px; right: 30px; background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%); color: white; padding: 15px 25px; border-radius: 10px; border: 2px solid #60a5fa; z-index: 10000; box-shadow: 0 5px 20px rgba(59, 130, 246, 0.5);';
            loadingToast.innerHTML = `
                <div style="font-size: 14px; font-weight: bold;">🎲 Generating random data...</div>
                <div style="font-size: 12px; margin-top: 5px; opacity: 0.9;">${tableName}</div>
            `;
            document.body.appendChild(loadingToast);

            try {
                // Get table structure
                const structResult = await apiRequest('get_table_structure', {
                    db_host: conn.host,
                    db_name: conn.dbName,
                    db_user: conn.username,
                    db_pass: conn.password,
                    db_port: conn.port || '3306',
                    table_name: tableName
                });

                if (!structResult.success) {
                    throw new Error(structResult.message || 'Failed to get table structure');
                }

                // Generate random records (use existing function)
                const randomRecords = generateRandomRecords(structResult.columns, 10);

                if (randomRecords.length === 0) {
                    throw new Error('Could not generate records (table has only auto-increment columns?)');
                }

                // Insert records
                const result = await apiRequest('insert_random_data', {
                    db_host: conn.host,
                    db_name: conn.dbName,
                    db_user: conn.username,
                    db_pass: conn.password,
                    db_port: conn.port || '3306',
                    table_name: tableName,
                    records_data: JSON.stringify(randomRecords),
                    record_count: 10
                });

                document.body.removeChild(loadingToast);

                if (result.success) {
                    // Show success toast
                    const successToast = document.createElement('div');
                    successToast.style.cssText = 'position: fixed; bottom: 30px; right: 30px; background: linear-gradient(135deg, #22c55e 0%, #15803d 100%); color: white; padding: 15px 25px; border-radius: 10px; border: 2px solid #86efac; z-index: 10000; box-shadow: 0 5px 20px rgba(34, 197, 94, 0.5);';
                    successToast.innerHTML = `
                        <div style="font-size: 14px; font-weight: bold;">✅ ${result.message}</div>
                        <div style="font-size: 12px; margin-top: 5px; opacity: 0.9;">${tableName}</div>
                    `;
                    document.body.appendChild(successToast);

                    setTimeout(() => {
                        document.body.removeChild(successToast);
                    }, 3000);

                    // Refresh tables to update row count
                    await loadMigrationTables();

                    console.log('✅ Random data injected successfully');
                } else {
                    alert(`❌ Failed to inject data:\n\n${result.message}`);
                }

            } catch (error) {
                if (document.getElementById('injectLoadingToast')) {
                    document.body.removeChild(loadingToast);
                }
                console.error('❌ Inject error:', error);
                alert(`❌ Inject Failed!\n\n${error.message}`);
            }
        }

        // Empty table data (keep structure)
        async function emptyTableData(tableName) {
            if (!selectedDatabaseId) {
                alert('❌ No database selected');
                return;
            }

            console.log('=== EMPTY TABLE DATA ===');
            console.log('Table to empty:', tableName);

            // Get connection info (supports both Localhost and Hostinger)
            const sourceConn = getConnectionById(selectedDatabaseId);

            if (!sourceConn) {
                alert('❌ Connection not found');
                return;
            }

            // Get current row count
            const currentRows = migrationTablesRowCount[tableName] || 0;

            // Confirmation
            if (!confirm(`🧹 Empty Table: ${tableName}\n\n⚠️ This will DELETE ALL ${currentRows.toLocaleString()} RECORD(S) from this table!\n\nTable structure will be preserved.\n\nAre you sure?`)) {
                console.log('User cancelled empty');
                return;
            }

            // Show loading
            const loadingDiv = document.createElement('div');
            loadingDiv.style.cssText = 'position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(245, 158, 11, 0.95); color: white; padding: 30px 50px; border-radius: 12px; border: 2px solid #fbbf24; z-index: 10000; text-align: center; box-shadow: 0 0 40px rgba(245, 158, 11, 0.6);';
            loadingDiv.innerHTML = `
                <div style="font-size: 48px; margin-bottom: 15px; animation: sweep 1s ease-in-out infinite;">🧹</div>
                <div style="font-size: 18px; font-weight: bold;">Emptying Table...</div>
                <div style="font-size: 14px; margin-top: 8px; opacity: 0.9;">${tableName}</div>
                <div style="font-size: 12px; margin-top: 8px; opacity: 0.7;">Deleting ${currentRows.toLocaleString()} record(s)...</div>
                <style>
                    @keyframes sweep {
                        0%, 100% { transform: translateX(0); }
                        25% { transform: translateX(-10px); }
                        75% { transform: translateX(10px); }
                    }
                </style>
            `;
            document.body.appendChild(loadingDiv);

            try {
                // Use TRUNCATE TABLE (faster) or DELETE FROM
                console.log('Emptying table data:', tableName);
                
                // Try TRUNCATE first (faster and resets auto-increment)
                let sql = `TRUNCATE TABLE \`${tableName}\`;`;
                
                const result = await apiRequest('execute_sql', {
                    db_host: sourceConn.host,
                    db_name: sourceConn.dbName,
                    db_user: sourceConn.username,
                    db_pass: sourceConn.password,
                    db_port: sourceConn.port || '3306',
                    sql_query: sql
                });

                if (!result.success) {
                    // If TRUNCATE fails, try DELETE FROM
                    console.log('TRUNCATE failed, trying DELETE FROM...');
                    sql = `DELETE FROM \`${tableName}\`;`;
                    
                    const deleteResult = await apiRequest('execute_sql', {
                        db_host: sourceConn.host,
                        db_name: sourceConn.dbName,
                        db_user: sourceConn.username,
                        db_pass: sourceConn.password,
                        db_port: sourceConn.port || '3306',
                        sql_query: sql
                    });
                    
                    if (!deleteResult.success) {
                        throw new Error(deleteResult.message || 'Failed to empty table');
                    }
                }

                console.log('✅ Table data emptied successfully');

                // Success!
                document.body.removeChild(loadingDiv);

                // Show success message
                const successDiv = document.createElement('div');
                successDiv.style.cssText = 'position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; padding: 35px 55px; border-radius: 12px; border: 2px solid #fbbf24; z-index: 10000; text-align: center; box-shadow: 0 0 40px rgba(245, 158, 11, 0.7);';
                successDiv.innerHTML = `
                    <div style="font-size: 56px; margin-bottom: 15px;">✅</div>
                    <div style="font-size: 20px; margin-bottom: 10px; font-weight: bold;">Table Emptied!</div>
                    <div style="font-size: 14px; background: rgba(0,0,0,0.3); padding: 8px 14px; border-radius: 6px; display: inline-block;">
                        ${tableName}
                    </div>
                    <div style="font-size: 12px; opacity: 0.8; margin-top: 10px;">All ${currentRows.toLocaleString()} record(s) deleted</div>
                    <div style="font-size: 11px; opacity: 0.7; margin-top: 8px;">📋 Table structure preserved</div>
                `;
                document.body.appendChild(successDiv);

                setTimeout(() => {
                    document.body.removeChild(successDiv);
                }, 3000);

                // Refresh source tables to update row count
                await loadMigrationTables();

                console.log('✅ Table emptying completed!');

            } catch (error) {
                console.error('❌ Empty error:', error);
                document.body.removeChild(loadingDiv);

                alert(`❌ Empty Failed!\n\n${error.message}`);
            }
        }

        // Delete table from source database
        async function deleteTableFromMigration(tableName) {
            if (!selectedDatabaseId) {
                alert('❌ No database selected');
                return;
            }

            console.log('=== DELETE TABLE ===');
            console.log('Table to delete:', tableName);

            // Get connection info (supports both Localhost and Hostinger)
            const sourceConn = getConnectionById(selectedDatabaseId);

            if (!sourceConn) {
                alert('❌ Connection not found');
                return;
            }

            // Confirmation
            if (!confirm(`🗑️ Delete Table: ${tableName}\n\n⚠️ This will PERMANENTLY delete this table and ALL its data!\n\nAre you absolutely sure?`)) {
                console.log('User cancelled delete');
                return;
            }

            // Double confirmation for safety
            if (!confirm(`⚠️ FINAL WARNING!\n\nDelete "${tableName}" permanently?\n\nThis action CANNOT be undone!`)) {
                console.log('User cancelled on second confirmation');
                return;
            }

            // Show loading
            const loadingDiv = document.createElement('div');
            loadingDiv.style.cssText = 'position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(239, 68, 68, 0.95); color: white; padding: 30px 50px; border-radius: 12px; border: 2px solid #f87171; z-index: 10000; text-align: center; box-shadow: 0 0 40px rgba(239, 68, 68, 0.6);';
            loadingDiv.innerHTML = `
                <div style="font-size: 48px; margin-bottom: 15px; animation: shake 0.5s ease-in-out infinite;">🗑️</div>
                <div style="font-size: 18px; font-weight: bold;">Deleting Table...</div>
                <div style="font-size: 14px; margin-top: 8px; opacity: 0.9;">${tableName}</div>
                <style>
                    @keyframes shake {
                        0%, 100% { transform: translateX(0); }
                        25% { transform: translateX(-5px); }
                        75% { transform: translateX(5px); }
                    }
                </style>
            `;
            document.body.appendChild(loadingDiv);

            try {
                // Delete table
                console.log('Deleting table:', tableName);
                const deleteResult = await apiRequest('delete_table', {
                    db_host: sourceConn.host,
                    db_name: sourceConn.dbName,
                    db_user: sourceConn.username,
                    db_pass: sourceConn.password,
                    db_port: sourceConn.port || '3306',
                    table_name: tableName
                });

                if (!deleteResult.success) {
                    throw new Error(deleteResult.message || 'Failed to delete table');
                }

                console.log('✅ Table deleted successfully');

                // Success!
                document.body.removeChild(loadingDiv);

                // Show success message
                const successDiv = document.createElement('div');
                successDiv.style.cssText = 'position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; padding: 35px 55px; border-radius: 12px; border: 2px solid #f87171; z-index: 10000; text-align: center; box-shadow: 0 0 40px rgba(239, 68, 68, 0.7);';
                successDiv.innerHTML = `
                    <div style="font-size: 56px; margin-bottom: 15px;">✅</div>
                    <div style="font-size: 20px; margin-bottom: 10px; font-weight: bold;">Table Deleted!</div>
                    <div style="font-size: 14px; background: rgba(0,0,0,0.3); padding: 8px 14px; border-radius: 6px; display: inline-block;">
                        ${tableName}
                    </div>
                    <div style="font-size: 12px; opacity: 0.8; margin-top: 10px;">Table and all data permanently removed</div>
                `;
                document.body.appendChild(successDiv);

                setTimeout(() => {
                    document.body.removeChild(successDiv);
                }, 3000);

                // Refresh source tables
                await loadMigrationTables();

                console.log('✅ Table deletion completed!');

            } catch (error) {
                console.error('❌ Delete error:', error);
                document.body.removeChild(loadingDiv);

                alert(`❌ Deletion Failed!\n\n${error.message}`);
            }
        }

        // Duplicate table in source database
        async function duplicateTable(tableName) {
            if (!selectedDatabaseId) {
                alert('❌ No database selected');
                return;
            }

            console.log('=== DUPLICATE TABLE ===');
            console.log('Original table:', tableName);

            // Get connection info (supports both Localhost and Hostinger)
            const sourceConn = getConnectionById(selectedDatabaseId);

            if (!sourceConn) {
                alert('❌ Connection not found');
                return;
            }

            // Prompt for new table name
            const newName = prompt(`📋 Duplicate Table: ${tableName}\n\nEnter name for the copy:`, `${tableName}_copy`);
            
            if (!newName) {
                console.log('User cancelled duplicate');
                return;
            }

            if (newName === tableName) {
                alert('❌ New name must be different from original table!');
                return;
            }

            // Check if new name already exists
            if (migrationTables.includes(newName)) {
                if (!confirm(`⚠️ Table "${newName}" already exists!\n\nOverwrite it?`)) {
                    return;
                }
            }

            // Show loading
            const loadingDiv = document.createElement('div');
            loadingDiv.style.cssText = 'position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(139, 92, 246, 0.95); color: white; padding: 30px 50px; border-radius: 12px; border: 2px solid #a78bfa; z-index: 10000; text-align: center; box-shadow: 0 0 40px rgba(139, 92, 246, 0.6);';
            loadingDiv.innerHTML = `
                <div style="font-size: 48px; margin-bottom: 15px; animation: spin 1.5s linear infinite;">📋</div>
                <div style="font-size: 18px; font-weight: bold;">Duplicating Table...</div>
                <div style="font-size: 14px; margin-top: 8px; opacity: 0.9;">${tableName} → ${newName}</div>
                <style>@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }</style>
            `;
            document.body.appendChild(loadingDiv);

            try {
                // Step 1: Generate SQL with data
                console.log('Step 1: Generating SQL with data for:', tableName);
                const sqlResult = await apiRequest('generate_table_sql', {
                    db_host: sourceConn.host,
                    db_name: sourceConn.dbName,
                    db_user: sourceConn.username,
                    db_pass: sourceConn.password,
                    db_port: sourceConn.port || '3306',
                    table_name: tableName,
                    include_data: 'true'
                });

                if (!sqlResult.success || !sqlResult.sql) {
                    throw new Error(sqlResult.message || 'Failed to generate SQL');
                }

                console.log(`✅ SQL generated (${sqlResult.row_count || 0} rows)`);

                // Step 2: Modify SQL to use new table name
                let modifiedSQL = sqlResult.sql;
                
                // Replace table name in all occurrences
                modifiedSQL = modifiedSQL.replace(new RegExp(`\`${tableName}\``, 'g'), `\`${newName}\``);
                modifiedSQL = modifiedSQL.replace(new RegExp(`DROP TABLE IF EXISTS \`${tableName}\``, 'g'), `DROP TABLE IF EXISTS \`${newName}\``);
                
                console.log('✅ SQL modified for new table name:', newName);

                // Step 3: Execute SQL in same database
                console.log('Step 3: Creating duplicate table:', newName);
                
                // Remove comment lines then split
                const sqlWithoutComments = modifiedSQL
                    .split('\n')
                    .filter(line => !line.trim().startsWith('--'))
                    .join('\n');
                
                const statements = sqlWithoutComments
                    .split(';')
                    .map(s => s.trim())
                    .filter(s => s.length > 10);

                console.log(`Found ${statements.length} statements to execute`);

                for (let i = 0; i < statements.length; i++) {
                    const trimmed = statements[i].trim();
                    if (!trimmed) continue;

                    const finalStmt = trimmed.endsWith(';') ? trimmed : trimmed + ';';
                    const isInsert = finalStmt.toUpperCase().includes('INSERT INTO');
                    
                    console.log(`📝 Statement ${i + 1}: ${finalStmt.substring(0, 30)}... (${finalStmt.length} chars, INSERT: ${isInsert})`);

                    const execResult = await apiRequest('execute_sql', {
                        db_host: sourceConn.host,
                        db_name: sourceConn.dbName,
                        db_user: sourceConn.username,
                        db_pass: sourceConn.password,
                        db_port: sourceConn.port || '3306',
                        sql_query: finalStmt
                    });

                    if (!execResult.success) {
                        console.error(`❌ Statement ${i + 1} failed:`, execResult.message);
                    } else {
                        console.log(`✅ Statement ${i + 1} success!`);
                    }
                }

                // Success!
                document.body.removeChild(loadingDiv);

                // Show success message
                const successDiv = document.createElement('div');
                successDiv.style.cssText = 'position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%); color: white; padding: 35px 55px; border-radius: 12px; border: 2px solid #a78bfa; z-index: 10000; text-align: center; box-shadow: 0 0 40px rgba(139, 92, 246, 0.7);';
                successDiv.innerHTML = `
                    <div style="font-size: 56px; margin-bottom: 15px;">✅</div>
                    <div style="font-size: 20px; margin-bottom: 10px; font-weight: bold;">Table Duplicated!</div>
                    <div style="font-size: 14px; background: rgba(0,0,0,0.2); padding: 8px 14px; border-radius: 6px; display: inline-block;">
                        ${tableName} → ${newName}
                    </div>
                    ${sqlResult.row_count > 0 ? `<div style="font-size: 12px; opacity: 0.8; margin-top: 10px;">📊 ${sqlResult.row_count.toLocaleString()} record(s) copied</div>` : ''}
                `;
                document.body.appendChild(successDiv);

                setTimeout(() => {
                    document.body.removeChild(successDiv);
                }, 3000);

                // Refresh source tables to show the duplicate
                await loadMigrationTables();

                console.log('✅ Table duplicated successfully!');

            } catch (error) {
                console.error('❌ Duplicate error:', error);
                document.body.removeChild(loadingDiv);

                alert(`❌ Duplication Failed!\n\n${error.message}`);
            }
        }


        // Start table migration
        function startTableMigration() {
            if (!selectedDatabaseId) {
                alert('⚠️ Please select a source database first!');
                return;
            }

            if (!selectedDestinationId) {
                alert('⚠️ Please select a destination database!');
                return;
            }

            if (selectedMigrationTables.size === 0) {
                alert('⚠️ Please select at least one table to migrate!');
                return;
            }

            // TODO: Implement migration logic
            const selectedTablesList = Array.from(selectedMigrationTables).join(', ');
            console.log('Starting migration...', {
                source: selectedDatabaseId,
                destination: selectedDestinationId,
                tables: selectedTablesList,
                count: selectedMigrationTables.size
            });

            alert(`🚀 Migration Ready!\n\n📤 Source: ${selectedDatabaseId}\n📥 Destination: ${selectedDestinationId}\n📊 Tables: ${selectedMigrationTables.size}\n\n✓ Feature will be implemented next!`);
        }

        // ========================================
        // Drag and Drop Functions
        // ========================================
        
        let draggedTableName = null;
        let draggedTables = []; // NEW: Array of tables to transfer (for multi-selection)
        let draggedElement = null;
        let dragIncludeData = false; // Track if dragging with data or structure only

        // Handle drag start
        function handleDragStart(event, tableName, includeData = false) {
            console.log('=== DRAG START ===');
            console.log('Table:', tableName);
            console.log('Include Data:', includeData);
            console.log('Currently selected tables:', Array.from(selectedMigrationTables));
            
            // Check if dragged table is part of multi-selection
            let tablesToDrag = [];
            
            if (selectedMigrationTables.has(tableName) && selectedMigrationTables.size > 1) {
                // Multi-selection mode: drag all selected tables
                tablesToDrag = Array.from(selectedMigrationTables);
                console.log('🎯 MULTI-SELECTION MODE: Dragging', tablesToDrag.length, 'tables');
            } else {
                // Single table mode
                tablesToDrag = [tableName];
                console.log('🎯 SINGLE TABLE MODE: Dragging 1 table');
            }
            
            draggedTableName = tableName; // Keep for compatibility
            draggedTables = tablesToDrag; // NEW: Array of tables
            draggedElement = event.target;
            dragIncludeData = includeData;
            
            // Store in event data
            event.target.setAttribute('data-include-data', includeData ? 'true' : 'false');
            
            console.log('✅ Set draggedTables to:', draggedTables);
            console.log('✅ Set dragIncludeData to:', dragIncludeData);
            
            event.target.classList.add('dragging');
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', JSON.stringify({
                tables: tablesToDrag,
                includeData: includeData
            }));
            
            // Add dragging class to all selected tables
            tablesToDrag.forEach(tName => {
                const card = document.querySelector(`.migration-table-box[data-table="${tName}"]`);
                if (card) {
                    card.classList.add('dragging');
                }
            });
            
            // Create custom drag image
            const dragImage = event.target.cloneNode(true);
            dragImage.style.cssText = `
                position: absolute;
                top: -1000px;
                transform: rotate(5deg) scale(1.1);
                opacity: 0.9;
                box-shadow: 0 15px 40px rgba(251, 191, 36, 0.6);
                border-color: #fbbf24;
            `;
            document.body.appendChild(dragImage);
            event.dataTransfer.setDragImage(dragImage, 60, 30);
            setTimeout(() => document.body.removeChild(dragImage), 0);
            
            // Visual feedback
            console.log(`🖱️ Started dragging ${tablesToDrag.length} table(s)`);
            
            // Show hint message with mode and count
            const hint = document.createElement('div');
            hint.id = 'dragHint';
            const bgColor = includeData ? 'rgba(16, 185, 129, 0.95)' : 'rgba(251, 191, 36, 0.95)';
            const modeText = includeData ? '📦 Structure + Data' : '🏗️ Structure Only';
            const borderColor = includeData ? '#22c55e' : '#fbbf24';
            
            const tableNames = tablesToDrag.length > 1 
                ? `${tablesToDrag.length} tables` 
                : tableName;
            
            hint.style.cssText = `position: fixed; top: 20px; left: 50%; transform: translateX(-50%); background: ${bgColor}; color: white; padding: 15px 30px; border-radius: 10px; font-size: 14px; font-weight: bold; z-index: 9999; box-shadow: 0 5px 20px rgba(0,0,0,0.5); border: 2px solid ${borderColor}; animation: slideDown 0.3s ease-out;`;
            hint.innerHTML = `🖱️ Dragging: <strong>${tableNames}</strong><br><span style="font-size: 12px; opacity: 0.9;">${modeText}</span>`;
            hint.innerHTML += '<style>@keyframes slideDown { from { top: -50px; opacity: 0; } to { top: 20px; opacity: 1; } }</style>';
            document.body.appendChild(hint);
        }

        // Handle drag end
        function handleDragEnd(event) {
            // Remove dragging class from all dragged tables
            if (draggedTables && draggedTables.length > 0) {
                draggedTables.forEach(tName => {
                    const card = document.querySelector(`.migration-table-box[data-table="${tName}"]`);
                    if (card) {
                        card.classList.remove('dragging');
                    }
                });
            }
            
            event.target.classList.remove('dragging');
            
            // Remove hint message
            const hint = document.getElementById('dragHint');
            if (hint) {
                hint.style.animation = 'slideUp 0.3s ease-out forwards';
                hint.innerHTML += '<style>@keyframes slideUp { from { top: 20px; opacity: 1; } to { top: -50px; opacity: 0; } }</style>';
                setTimeout(() => {
                    if (hint.parentNode) {
                        document.body.removeChild(hint);
                    }
                }, 300);
            }
            
            console.log('🖱️ Finished dragging');
        }

        // Handle drag over (allow drop)
        function handleDragOver(event) {
            if (!selectedDestinationId) {
                event.dataTransfer.dropEffect = 'none';
                return;
            }

            event.preventDefault(); // Must prevent default to allow drop
            event.dataTransfer.dropEffect = 'move';
            
            // Add visual feedback
            const dropZone = document.getElementById('migrationDestinationTablesContainer');
            if (dropZone && !dropZone.classList.contains('drag-over')) {
                dropZone.classList.add('drag-over');
            }
        }

        // Handle drag leave
        function handleDragLeave(event) {
            // Only remove class if leaving the drop zone itself, not child elements
            if (event.target.id === 'migrationDestinationTablesContainer') {
                event.target.classList.remove('drag-over');
            }
        }

        // Handle drop
        async function handleDrop(event) {
            event.preventDefault();
            
            const dropZone = document.getElementById('migrationDestinationTablesContainer');
            if (dropZone) {
                dropZone.classList.remove('drag-over');
            }

            if (!draggedTables || draggedTables.length === 0) {
                console.error('❌ No tables being dragged');
                return;
            }

            if (!selectedDestinationId) {
                alert('⚠️ Please select a destination database first!');
                return;
            }

            console.log('📥 Dropped tables:', draggedTables);
            console.log('📊 Total tables to migrate:', draggedTables.length);
            console.log('📊 Include data:', dragIncludeData);

            // Show loading indicator immediately (no confirmation for better UX)
            const loadingDiv = document.createElement('div');
            loadingDiv.id = 'dragMigrationLoading';
            loadingDiv.style.cssText = 'position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: linear-gradient(135deg, rgba(0, 0, 0, 0.95) 0%, rgba(34, 197, 94, 0.2) 100%); color: #22c55e; padding: 40px 60px; border-radius: 15px; border: 3px solid #22c55e; z-index: 10000; text-align: center; box-shadow: 0 0 50px rgba(34, 197, 94, 0.5); animation: fadeInScale 0.3s ease-out;';
            const modeText = dragIncludeData ? 'Structure + Data' : 'Structure Only';
            const tableCountText = draggedTables.length > 1 
                ? `${draggedTables.length} Tables` 
                : draggedTables[0];
            
            loadingDiv.innerHTML = `
                <div style="font-size: 56px; margin-bottom: 20px; animation: spin 2s linear infinite;">🔄</div>
                <div style="font-size: 20px; margin-bottom: 12px; font-weight: bold;">Migrating ${tableCountText}...</div>
                <div style="font-size: 14px; opacity: 0.8; color: #fbbf24;">${modeText}</div>
                <div id="migrationProgress" style="font-size: 13px; opacity: 0.7; margin-top: 10px;">Preparing...</div>
                <div style="font-size: 12px; opacity: 0.6; margin-top: 15px;">From Source → To Destination</div>
                <style>
                    @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
                </style>
            `;
            document.body.appendChild(loadingDiv);

            try {
                // Get source and destination connections (supports both Localhost and Hostinger)
                const sourceConn = getConnectionById(selectedDatabaseId);
                const destConn = getConnectionById(selectedDestinationId);

                console.log('🔍 Debug Info:', {
                    selectedDatabaseId,
                    selectedDestinationId,
                    sourceConn: sourceConn ? `Found (${sourceConn.isLocalhost ? 'Localhost' : 'Hostinger'})` : 'NOT FOUND',
                    destConn: destConn ? `Found (${destConn.isLocalhost ? 'Localhost' : 'Hostinger'})` : 'NOT FOUND'
                });

                if (!sourceConn) {
                    throw new Error('Source connection not found. Selected ID: ' + selectedDatabaseId);
                }

                if (!destConn) {
                    throw new Error('Destination connection not found. Selected ID: ' + selectedDestinationId);
                }

                console.log('📋 Source Connection:', {
                    host: sourceConn.host,
                    dbName: sourceConn.dbName,
                    user: sourceConn.username,
                    port: sourceConn.port
                });

                console.log('📥 Destination Connection:', {
                    host: destConn.host,
                    dbName: destConn.dbName,
                    user: destConn.username,
                    port: destConn.port
                });

                // Process all tables in draggedTables array
                console.log('=== INSIDE handleDrop ===');
                console.log('🔍 Current dragIncludeData value:', dragIncludeData);
                console.log('🔍 Tables to migrate:', draggedTables);
                
                const includeDataMode = dragIncludeData ? 'true' : 'false';
                const modeText = dragIncludeData ? 'structure & data' : 'structure only';
                
                let successCount = 0;
                let failCount = 0;
                const failedTables = [];
                
                console.log(`📋 Starting migration of ${draggedTables.length} table(s) (${modeText})`);
                
                // Loop through each table
                for (let tableIndex = 0; tableIndex < draggedTables.length; tableIndex++) {
                    const currentTable = draggedTables[tableIndex];
                    
                    console.log(`\n${'='.repeat(60)}`);
                    console.log(`📋 Processing table ${tableIndex + 1}/${draggedTables.length}: ${currentTable}`);
                    console.log(`${'='.repeat(60)}`);
                    
                    // Update progress
                    const progressEl = document.getElementById('migrationProgress');
                    if (progressEl) {
                        progressEl.textContent = `Table ${tableIndex + 1}/${draggedTables.length}: ${currentTable}`;
                    }
                    
                    try {
                        // Step 1: Generate SQL for current table
                        console.log(`📋 Step 1: Generating SQL for ${currentTable}`);
                        
                        const sqlRequestData = {
                            db_host: sourceConn.host,
                            db_name: sourceConn.dbName,
                            db_user: sourceConn.username,
                            db_pass: sourceConn.password,
                            db_port: sourceConn.port || '3306',
                            table_name: currentTable,
                            include_data: includeDataMode
                        };

                        const sqlResult = await apiRequest('generate_table_sql', sqlRequestData);

                        if (!sqlResult.success || !sqlResult.sql) {
                            throw new Error(sqlResult.message || 'Failed to generate SQL');
                        }

                        console.log(`✅ SQL generated: ${sqlResult.row_count || 0} rows, ${sqlResult.sql.length} bytes`);
                
                        // Step 2: Execute SQL on destination
                        console.log(`📥 Step 2: Executing SQL on destination for ${currentTable}`);
                        
                        // Remove comment lines then split
                        const sqlWithoutComments = sqlResult.sql
                            .split('\n')
                            .filter(line => !line.trim().startsWith('--'))
                            .join('\n');
                        
                        const sqlStatements = sqlWithoutComments
                            .split(';')
                            .map(s => s.trim())
                            .filter(s => s.length > 10);

                        console.log(`   Found ${sqlStatements.length} statements`);

                        // Execute each statement
                        for (let i = 0; i < sqlStatements.length; i++) {
                            const statement = sqlStatements[i].trim();
                            if (!statement) continue;

                            const finalStatement = statement.endsWith(';') ? statement : statement + ';';
                            
                            const executeResult = await apiRequest('execute_sql', {
                                db_host: destConn.host,
                                db_name: destConn.dbName,
                                db_user: destConn.username,
                                db_pass: destConn.password,
                                db_port: destConn.port || '3306',
                                sql_query: finalStatement
                            });

                            if (!executeResult.success) {
                                console.error(`   ❌ Statement ${i + 1} failed:`, executeResult.message);
                            } else {
                                console.log(`   ✅ Statement ${i + 1} executed`);
                            }
                        }

                        // Step 3: Delete table from source
                        console.log(`🗑️ Step 3: Deleting ${currentTable} from source database`);
                        
                        console.log(`📤 Sending delete table request for ${currentTable}`);

                        const deleteRequestData = {
                            db_host: sourceConn.host,
                            db_name: sourceConn.dbName,
                            db_user: sourceConn.username,
                            db_pass: sourceConn.password,
                            db_port: sourceConn.port || '3306',
                            table_name: currentTable
                        };

                        const deleteResult = await apiRequest('delete_table', deleteRequestData);

                        console.log(`📥 Delete table result for ${currentTable}:`, deleteResult.message);

                        if (!deleteResult.success) {
                            console.warn(`⚠️ ${currentTable} copied but not deleted from source:`, deleteResult.message);
                        }

                        // Success for this table!
                        successCount++;
                        console.log(`✅ Table ${tableIndex + 1}/${draggedTables.length} (${currentTable}) migrated successfully!`);
                        
                    } catch (tableError) {
                        // Error for this specific table
                        failCount++;
                        failedTables.push({ table: currentTable, error: tableError.message });
                        console.error(`❌ Failed to migrate ${currentTable}:`, tableError.message);
                    }
                } // End of tables loop
                
                console.log(`\n${'='.repeat(60)}`);
                console.log(`📊 MIGRATION SUMMARY:`);
                console.log(`   ✅ Successful: ${successCount}/${draggedTables.length}`);
                console.log(`   ❌ Failed: ${failCount}/${draggedTables.length}`);
                if (failedTables.length > 0) {
                    console.log(`   Failed tables:`, failedTables);
                }
                console.log(`${'='.repeat(60)}\n`);

                // Remove loading
                document.body.removeChild(loadingDiv);
                
                // Show success message with animation
                const successDiv = document.createElement('div');
                const modeIcon = dragIncludeData ? '📦' : '🏗️';
                const modeLabel = dragIncludeData ? 'Structure + Data' : 'Structure Only';
                const modeColor = dragIncludeData ? '#22c55e' : '#fbbf24';
                
                const tableText = draggedTables.length > 1 
                    ? `${successCount} Table(s) Migrated`
                    : `Table Moved: ${draggedTables[0]}`;
                
                const failInfo = failCount > 0 
                    ? `<div style="font-size: 12px; opacity: 0.8; margin-top: 8px; color: #fca5a5;">⚠️ ${failCount} table(s) failed</div>` 
                    : '';
                
                successDiv.style.cssText = 'position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: linear-gradient(135deg, rgba(34, 197, 94, 0.98) 0%, rgba(16, 185, 129, 0.98) 100%); color: white; padding: 40px 60px; border-radius: 15px; border: 3px solid #86efac; z-index: 10000; text-align: center; box-shadow: 0 0 50px rgba(34, 197, 94, 0.7); animation: bounceIn 0.5s ease-out;';
                successDiv.innerHTML = `
                    <div style="font-size: 64px; margin-bottom: 20px;">✅</div>
                    <div style="font-size: 22px; margin-bottom: 12px; font-weight: bold;">${tableText}</div>
                    <div style="font-size: 14px; opacity: 0.95; background: rgba(255,255,255,0.2); padding: 8px 16px; border-radius: 8px; display: inline-block; margin: 5px 0;">${successCount} of ${draggedTables.length} successful</div>
                    <div style="font-size: 13px; opacity: 0.9; margin-top: 10px; background: rgba(0,0,0,0.3); padding: 8px 14px; border-radius: 6px; display: inline-block; border: 1px solid ${modeColor};">
                        <span style="color: ${modeColor}; font-size: 16px;">${modeIcon}</span> <strong>${modeLabel}</strong>
                    </div>
                    ${failInfo}
                    <div style="font-size: 13px; opacity: 0.8; margin-top: 15px;">📤 Removed from source • 📥 Added to destination</div>
                    <style>
                        @keyframes bounceIn {
                            0% { transform: translate(-50%, -50%) scale(0.3); opacity: 0; }
                            50% { transform: translate(-50%, -50%) scale(1.05); }
                            100% { transform: translate(-50%, -50%) scale(1); opacity: 1; }
                        }
                    </style>
                `;
                document.body.appendChild(successDiv);

                setTimeout(() => {
                    document.body.removeChild(successDiv);
                }, 3000);

                // Refresh both source and destination tables
                // Note: loadMigrationTables() will preserve destination dropdown selection
                await loadMigrationTables();
                
                // Only reload destination if it's still selected
                if (selectedDestinationId) {
                    await loadDestinationTables();
                }

                console.log('✅ Migration completed successfully!');

            } catch (error) {
                console.error('❌ Migration error:', error);
                console.error('❌ Full error details:', {
                    message: error.message,
                    stack: error.stack,
                    error: error
                });
                
                // Remove loading indicator
                const loading = document.getElementById('dragMigrationLoading');
                if (loading && loading.parentNode) {
                    document.body.removeChild(loading);
                }

                // Show detailed error message
                const errorDiv = document.createElement('div');
                errorDiv.style.cssText = 'position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(239, 68, 68, 0.98); color: white; padding: 40px 60px; border-radius: 15px; border: 3px solid #fca5a5; z-index: 10000; text-align: center; box-shadow: 0 0 50px rgba(239, 68, 68, 0.7); max-width: 600px;';
                errorDiv.innerHTML = `
                    <div style="font-size: 64px; margin-bottom: 20px;">❌</div>
                    <div style="font-size: 22px; margin-bottom: 15px; font-weight: bold;">Migration Failed!</div>
                    <div style="font-size: 14px; opacity: 0.95; background: rgba(0,0,0,0.3); padding: 15px; border-radius: 8px; text-align: left; margin: 15px 0;">
                        <strong>Error:</strong><br>
                        ${error.message || error}
                    </div>
                    <div style="font-size: 12px; opacity: 0.8; margin-top: 15px;">Check browser console (F12) for more details</div>
                    <button onclick="this.parentElement.remove()" style="margin-top: 20px; padding: 10px 25px; background: white; color: #dc2626; border: none; border-radius: 8px; font-size: 14px; font-weight: bold; cursor: pointer;">Close</button>
                `;
                document.body.appendChild(errorDiv);

                // Auto-remove after 10 seconds
                setTimeout(() => {
                    if (errorDiv.parentNode) {
                        document.body.removeChild(errorDiv);
                    }
                }, 10000);
            } finally {
                draggedTableName = null;
            }
        }

        // Load table templates
        function loadTemplates() {
            const templates = [
                { name: 'Users', icon: '👤', desc: 'User accounts', columns: [
                    { name: 'id', type: 'INT', length: '11', nullable: 'no', autoIncrement: 'yes', primaryKey: 'yes' },
                    { name: 'username', type: 'VARCHAR', length: '50', nullable: 'no', unique: 'yes' },
                    { name: 'email', type: 'VARCHAR', length: '100', nullable: 'no', unique: 'yes' },
                    { name: 'password', type: 'VARCHAR', length: '255', nullable: 'no' },
                    { name: 'created_at', type: 'TIMESTAMP', defaultValue: 'CURRENT_TIMESTAMP', nullable: 'no' }
                ]},
                { name: 'Products', icon: '📦', desc: 'Product catalog', columns: [
                    { name: 'id', type: 'INT', length: '11', nullable: 'no', autoIncrement: 'yes', primaryKey: 'yes' },
                    { name: 'name', type: 'VARCHAR', length: '200', nullable: 'no' },
                    { name: 'description', type: 'TEXT', nullable: 'yes' },
                    { name: 'price', type: 'DECIMAL', length: '10,2', nullable: 'no' },
                    { name: 'stock', type: 'INT', length: '11', nullable: 'no', defaultValue: '0' },
                    { name: 'created_at', type: 'TIMESTAMP', defaultValue: 'CURRENT_TIMESTAMP', nullable: 'no' }
                ]},
                { name: 'Blog Posts', icon: '📝', desc: 'Blog articles', columns: [
                    { name: 'id', type: 'INT', length: '11', nullable: 'no', autoIncrement: 'yes', primaryKey: 'yes' },
                    { name: 'title', type: 'VARCHAR', length: '255', nullable: 'no' },
                    { name: 'slug', type: 'VARCHAR', length: '255', nullable: 'no', unique: 'yes' },
                    { name: 'content', type: 'TEXT', nullable: 'no' },
                    { name: 'author_id', type: 'INT', length: '11', nullable: 'no' },
                    { name: 'published_at', type: 'DATETIME', nullable: 'yes' },
                    { name: 'created_at', type: 'TIMESTAMP', defaultValue: 'CURRENT_TIMESTAMP', nullable: 'no' }
                ]},
                { name: 'Orders', icon: '🛒', desc: 'Customer orders', columns: [
                    { name: 'id', type: 'INT', length: '11', nullable: 'no', autoIncrement: 'yes', primaryKey: 'yes' },
                    { name: 'user_id', type: 'INT', length: '11', nullable: 'no' },
                    { name: 'total_amount', type: 'DECIMAL', length: '10,2', nullable: 'no' },
                    { name: 'status', type: 'VARCHAR', length: '50', nullable: 'no', defaultValue: 'pending' },
                    { name: 'created_at', type: 'TIMESTAMP', defaultValue: 'CURRENT_TIMESTAMP', nullable: 'no' }
                ]},
                { name: 'Categories', icon: '📁', desc: 'Content categories', columns: [
                    { name: 'id', type: 'INT', length: '11', nullable: 'no', autoIncrement: 'yes', primaryKey: 'yes' },
                    { name: 'name', type: 'VARCHAR', length: '100', nullable: 'no' },
                    { name: 'slug', type: 'VARCHAR', length: '100', nullable: 'no', unique: 'yes' },
                    { name: 'parent_id', type: 'INT', length: '11', nullable: 'yes' },
                    { name: 'created_at', type: 'TIMESTAMP', defaultValue: 'CURRENT_TIMESTAMP', nullable: 'no' }
                ]},
                { name: 'Comments', icon: '💬', desc: 'User comments', columns: [
                    { name: 'id', type: 'INT', length: '11', nullable: 'no', autoIncrement: 'yes', primaryKey: 'yes' },
                    { name: 'user_id', type: 'INT', length: '11', nullable: 'no' },
                    { name: 'post_id', type: 'INT', length: '11', nullable: 'no' },
                    { name: 'content', type: 'TEXT', nullable: 'no' },
                    { name: 'created_at', type: 'TIMESTAMP', defaultValue: 'CURRENT_TIMESTAMP', nullable: 'no' }
                ]},
                { name: 'Media', icon: '🖼️', desc: 'Media files', columns: [
                    { name: 'id', type: 'INT', length: '11', nullable: 'no', autoIncrement: 'yes', primaryKey: 'yes' },
                    { name: 'filename', type: 'VARCHAR', length: '255', nullable: 'no' },
                    { name: 'filepath', type: 'VARCHAR', length: '500', nullable: 'no' },
                    { name: 'filetype', type: 'VARCHAR', length: '50', nullable: 'no' },
                    { name: 'filesize', type: 'INT', length: '11', nullable: 'no' },
                    { name: 'uploaded_at', type: 'TIMESTAMP', defaultValue: 'CURRENT_TIMESTAMP', nullable: 'no' }
                ]},
                { name: 'Settings', icon: '⚙️', desc: 'App settings', columns: [
                    { name: 'id', type: 'INT', length: '11', nullable: 'no', autoIncrement: 'yes', primaryKey: 'yes' },
                    { name: 'key', type: 'VARCHAR', length: '100', nullable: 'no', unique: 'yes' },
                    { name: 'value', type: 'TEXT', nullable: 'yes' },
                    { name: 'updated_at', type: 'TIMESTAMP', defaultValue: 'CURRENT_TIMESTAMP', nullable: 'no' }
                ]},
                { name: 'Logs', icon: '📋', desc: 'Activity logs', columns: [
                    { name: 'id', type: 'INT', length: '11', nullable: 'no', autoIncrement: 'yes', primaryKey: 'yes' },
                    { name: 'user_id', type: 'INT', length: '11', nullable: 'yes' },
                    { name: 'action', type: 'VARCHAR', length: '100', nullable: 'no' },
                    { name: 'description', type: 'TEXT', nullable: 'yes' },
                    { name: 'ip_address', type: 'VARCHAR', length: '45', nullable: 'yes' },
                    { name: 'created_at', type: 'TIMESTAMP', defaultValue: 'CURRENT_TIMESTAMP', nullable: 'no' }
                ]},
                { name: 'Sessions', icon: '🔐', desc: 'User sessions', columns: [
                    { name: 'id', type: 'INT', length: '11', nullable: 'no', autoIncrement: 'yes', primaryKey: 'yes' },
                    { name: 'user_id', type: 'INT', length: '11', nullable: 'no' },
                    { name: 'token', type: 'VARCHAR', length: '255', nullable: 'no', unique: 'yes' },
                    { name: 'ip_address', type: 'VARCHAR', length: '45', nullable: 'yes' },
                    { name: 'user_agent', type: 'TEXT', nullable: 'yes' },
                    { name: 'expires_at', type: 'DATETIME', nullable: 'no' },
                    { name: 'created_at', type: 'TIMESTAMP', defaultValue: 'CURRENT_TIMESTAMP', nullable: 'no' }
                ]}
            ];

            const grid = document.getElementById('templateGrid');
            grid.innerHTML = templates.map(t => `
                <div class="template-card" onclick="selectTemplate('${t.name}')">
                    <div class="template-icon">${t.icon}</div>
                    <div class="template-name">${t.name}</div>
                    <div class="template-desc">${t.desc}</div>
                </div>
            `).join('');

            window.tableTemplates = templates;
        }

        // Suggested names for each template
        const templateSuggestedNames = {
            'Users': ['users', 'my_users', 'customers', 'members', 'accounts', 'profiles', 'user_accounts'],
            'Products': ['products', 'items', 'catalog', 'inventory', 'goods', 'articles', 'merchandise'],
            'Blog Posts': ['posts', 'blog_posts', 'articles', 'content', 'publications', 'entries'],
            'Orders': ['orders', 'purchases', 'transactions', 'sales', 'checkouts', 'invoices'],
            'Categories': ['categories', 'types', 'groups', 'sections', 'tags', 'classifications'],
            'Comments': ['comments', 'reviews', 'feedback', 'replies', 'notes', 'discussions'],
            'Media': ['media', 'files', 'uploads', 'attachments', 'images', 'documents', 'assets'],
            'Settings': ['settings', 'config', 'preferences', 'options', 'configuration'],
            'Logs': ['logs', 'activity_logs', 'audit_logs', 'system_logs', 'event_logs'],
            'Sessions': ['sessions', 'user_sessions', 'auth_sessions', 'login_sessions']
        };

        // Select template
        function selectTemplate(templateName) {
            selectedTemplate = window.tableTemplates.find(t => t.name === templateName);
            
            // Update UI
            document.querySelectorAll('.template-card').forEach(card => card.classList.remove('selected'));
            event.target.closest('.template-card').classList.add('selected');
            
            // Show template name section
            document.getElementById('templateNameSection').style.display = 'block';
            
            // Populate suggested names
            const suggestedSelect = document.getElementById('templateSuggestedNames');
            suggestedSelect.innerHTML = '<option value="">-- Choose Suggested Name --</option>';
            
            const suggestions = templateSuggestedNames[templateName] || [];
            suggestions.forEach(name => {
                suggestedSelect.innerHTML += `<option value="${name}">${name}</option>`;
            });
            
            // Auto-fill first suggestion in input placeholder
            const firstSuggestion = suggestions[0] || templateName.toLowerCase().replace(/\s+/g, '_');
            document.getElementById('templateTableName').placeholder = `e.g., ${firstSuggestion}`;
            
            // Show template columns
            const container = document.getElementById('templateColumns');
            container.innerHTML = '';
            
            selectedTemplate.columns.forEach((col, index) => {
                container.innerHTML += createColumnRow(col, index, true);
            });
            
            document.getElementById('templateColumnBuilder').style.display = 'block';
        }

        // Use suggested name
        function useSuggestedName() {
            const select = document.getElementById('templateSuggestedNames');
            const input = document.getElementById('templateTableName');
            
            if (select.value) {
                input.value = select.value;
                input.focus();
            }
        }

        // Clear template name
        function clearTemplateName() {
            document.getElementById('templateTableName').value = '';
            document.getElementById('templateSuggestedNames').value = '';
            document.getElementById('templateTableName').focus();
        }

        // Create column row HTML
        function createColumnRow(col = {}, index = columnCounter++, isTemplate = false) {
            const prefix = isTemplate ? 'template' : 'col';
            const id = `${prefix}_${index}`;
            
            return `
                <div class="column-row" id="${id}">
                    <div class="form-group" style="margin: 0;">
                        <input type="text" class="form-input col-name" placeholder="Column name" value="${col.name || ''}" required>
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <select class="form-select col-type">
                            <option value="INT" ${col.type === 'INT' ? 'selected' : ''}>INT</option>
                            <option value="VARCHAR" ${col.type === 'VARCHAR' ? 'selected' : ''}>VARCHAR</option>
                            <option value="TEXT" ${col.type === 'TEXT' ? 'selected' : ''}>TEXT</option>
                            <option value="DATE" ${col.type === 'DATE' ? 'selected' : ''}>DATE</option>
                            <option value="DATETIME" ${col.type === 'DATETIME' ? 'selected' : ''}>DATETIME</option>
                            <option value="TIMESTAMP" ${col.type === 'TIMESTAMP' ? 'selected' : ''}>TIMESTAMP</option>
                            <option value="BOOLEAN" ${col.type === 'BOOLEAN' ? 'selected' : ''}>BOOLEAN</option>
                            <option value="DECIMAL" ${col.type === 'DECIMAL' ? 'selected' : ''}>DECIMAL</option>
                            <option value="FLOAT" ${col.type === 'FLOAT' ? 'selected' : ''}>FLOAT</option>
                            <option value="DOUBLE" ${col.type === 'DOUBLE' ? 'selected' : ''}>DOUBLE</option>
                            <option value="BLOB" ${col.type === 'BLOB' ? 'selected' : ''}>BLOB</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <input type="text" class="form-input col-length" placeholder="Length" value="${col.length || ''}">
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <select class="form-select col-nullable">
                            <option value="yes" ${col.nullable === 'yes' ? 'selected' : ''}>NULL</option>
                            <option value="no" ${col.nullable === 'no' ? 'selected' : ''}>NOT NULL</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <input type="text" class="form-input col-default" placeholder="Default" value="${col.defaultValue || ''}">
                    </div>
                    <button type="button" class="btn btn-danger btn-icon" onclick="removeColumnRow('${id}')">×</button>
                </div>
                <div class="column-options">
                    <div class="checkbox-group">
                        <input type="checkbox" id="${id}_auto" class="col-auto" ${col.autoIncrement === 'yes' ? 'checked' : ''}>
                        <label for="${id}_auto">Auto Increment</label>
                    </div>
                    <div class="checkbox-group">
                        <input type="checkbox" id="${id}_pk" class="col-pk" ${col.primaryKey === 'yes' ? 'checked' : ''}>
                        <label for="${id}_pk">Primary Key</label>
                    </div>
                    <div class="checkbox-group">
                        <input type="checkbox" id="${id}_unique" class="col-unique" ${col.unique === 'yes' ? 'checked' : ''}>
                        <label for="${id}_unique">Unique</label>
                    </div>
                </div>
            `;
        }

        // Add column row
        function addColumnRow() {
            const builder = document.getElementById('columnBuilder');
            const div = document.createElement('div');
            div.innerHTML = createColumnRow();
            builder.appendChild(div);
        }

        // Remove column row
        function removeColumnRow(id) {
            document.getElementById(id).nextElementSibling.remove();
            document.getElementById(id).remove();
        }

        // Get columns data from form
        function getColumnsData(containerId) {
            const container = document.getElementById(containerId);
            const rows = container.querySelectorAll('.column-row');
            const columns = [];
            
            rows.forEach(row => {
                const col = {
                    name: row.querySelector('.col-name').value.trim(),
                    type: row.querySelector('.col-type').value,
                    length: row.querySelector('.col-length').value.trim(),
                    nullable: row.querySelector('.col-nullable').value,
                    defaultValue: row.querySelector('.col-default').value.trim(),
                    autoIncrement: row.querySelector('.col-auto')?.checked ? 'yes' : 'no',
                    primaryKey: row.querySelector('.col-pk')?.checked ? 'yes' : 'no',
                    unique: row.querySelector('.col-unique')?.checked ? 'yes' : 'no'
                };
                
                if (col.name) {
                    columns.push(col);
                }
            });
            
            return columns;
        }

        // Preview SQL
        function previewSQL() {
            const tableName = document.getElementById('newTableName').value.trim();
            const columns = getColumnsData('columnBuilder');
            
            if (!tableName || columns.length === 0) {
                alert('Please enter table name and add at least one column');
                return;
            }
            
            const sql = generateCreateTableSQL(tableName, columns);
            document.getElementById('sqlPreview').textContent = sql;
            document.getElementById('sqlPreview').style.display = 'block';
        }

        // Preview template SQL
        function previewTemplateSQL() {
            const tableName = document.getElementById('templateTableName').value.trim();
            const columns = getColumnsData('templateColumns');
            
            if (!tableName || columns.length === 0) {
                alert('Please enter table name and select a template');
                return;
            }
            
            const sql = generateCreateTableSQL(tableName, columns);
            document.getElementById('templateSqlPreview').textContent = sql;
            document.getElementById('templateSqlPreview').style.display = 'block';
        }

        // Generate CREATE TABLE SQL
        function generateCreateTableSQL(tableName, columns) {
            let sql = `CREATE TABLE \`${tableName}\` (\n`;
            const columnDefs = [];
            const primaryKeys = [];
            
            columns.forEach(col => {
                let def = `  \`${col.name}\` ${col.type}`;
                
                if (col.length && ['VARCHAR', 'CHAR', 'INT', 'DECIMAL', 'FLOAT', 'DOUBLE'].includes(col.type)) {
                    def += `(${col.length})`;
                }
                
                if (col.nullable === 'no') {
                    def += ' NOT NULL';
                }
                
                if (col.autoIncrement === 'yes') {
                    def += ' AUTO_INCREMENT';
                }
                
                if (col.defaultValue) {
                    if (col.defaultValue.toUpperCase() === 'NULL') {
                        def += ' DEFAULT NULL';
                    } else if (col.defaultValue.toUpperCase() === 'CURRENT_TIMESTAMP') {
                        def += ' DEFAULT CURRENT_TIMESTAMP';
                    } else {
                        def += ` DEFAULT '${col.defaultValue}'`;
                    }
                }
                
                if (col.unique === 'yes') {
                    def += ' UNIQUE';
                }
                
                columnDefs.push(def);
                
                if (col.primaryKey === 'yes') {
                    primaryKeys.push(`\`${col.name}\``);
                }
            });
            
            sql += columnDefs.join(',\n');
            
            if (primaryKeys.length > 0) {
                sql += ',\n  PRIMARY KEY (' + primaryKeys.join(', ') + ')';
            }
            
            sql += '\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';
            
            return sql;
        }

        // Create table (manual)
        async function createTableManual(event) {
            event.preventDefault();
            
            if (!selectedDatabaseId) {
                showMessage('createTableMessage', '❌ Please select a database from Dashboard first', 'error');
                return;
            }

            // Get connection info (supports both Localhost and Hostinger)
            const conn = getConnectionById(selectedDatabaseId);
            
            if (!conn) {
                showMessage('createTableMessage', '❌ Selected database connection not found', 'error');
                return;
            }
            
            const tableName = document.getElementById('newTableName').value.trim();
            const columns = getColumnsData('columnBuilder');
            
            if (!tableName) {
                showMessage('createTableMessage', '❌ Please enter a table name', 'error');
                return;
            }
            
            if (columns.length === 0) {
                showMessage('createTableMessage', '❌ Please add at least one column', 'error');
                return;
            }
            
            showMessage('createTableMessage', '🔄 Creating table...', 'info');
            
            const result = await apiRequest('create_table', {
                db_host: conn.host,
                db_name: conn.dbName,
                db_user: conn.username,
                db_pass: conn.password,
                db_port: conn.port,
                table_name: tableName,
                columns: JSON.stringify(columns)
            });
            
            if (result.success) {
                showMessage('createTableMessage', `✅ ${result.message}`, 'success');
                document.getElementById('newTableName').value = '';
                document.getElementById('columnBuilder').innerHTML = '';
                document.getElementById('sqlPreview').style.display = 'none';
                addColumnRow(); // Re-add one empty column row
                loadTablesForDropdowns();
                // Refresh table list if we're in that section
                if (document.getElementById('listTables').classList.contains('active')) {
                    loadTables();
                }
            } else {
                showMessage('createTableMessage', `❌ ${result.message}`, 'error');
            }
        }

        // Create table from template
        async function createTableFromTemplate(event) {
            event.preventDefault();
            
            if (!selectedDatabaseId) {
                showMessage('createTableMessage', '❌ Please select a database from Dashboard first', 'error');
                return;
            }

            // Get connection info (supports both Localhost and Hostinger)
            const conn = getConnectionById(selectedDatabaseId);
            
            if (!conn) {
                showMessage('createTableMessage', '❌ Selected database connection not found', 'error');
                return;
            }
            
            const tableName = document.getElementById('templateTableName').value.trim();
            const columns = getColumnsData('templateColumns');
            
            if (!tableName) {
                showMessage('createTableMessage', '❌ Please enter a table name', 'error');
                return;
            }
            
            if (columns.length === 0) {
                showMessage('createTableMessage', '❌ Please select a template', 'error');
                return;
            }
            
            showMessage('createTableMessage', '🔄 Creating table from template...', 'info');
            
            const result = await apiRequest('create_table', {
                db_host: conn.host,
                db_name: conn.dbName,
                db_user: conn.username,
                db_pass: conn.password,
                db_port: conn.port,
                table_name: tableName,
                columns: JSON.stringify(columns)
            });
            
            if (result.success) {
                showMessage('createTableMessage', `✅ ${result.message}`, 'success');
                document.getElementById('templateTableName').value = '';
                document.getElementById('templateColumns').innerHTML = '';
                document.getElementById('templateColumnBuilder').style.display = 'none';
                document.getElementById('templateSqlPreview').style.display = 'none';
                selectedTemplate = null;
                document.querySelectorAll('.template-card').forEach(card => card.classList.remove('selected'));
                loadTablesForDropdowns();
                // Refresh table list if we're in that section
                if (document.getElementById('listTables').classList.contains('active')) {
                    loadTables();
                }
            } else {
                showMessage('createTableMessage', `❌ ${result.message}`, 'error');
            }
        }

        // Global variable for current table view
        let currentTableName = null;
        let currentTablePage = 1;

        // Load tables from selected database
        async function loadTables() {
            console.log('=== LOAD TABLES (List Tables Section) ===');
            console.log('Selected Database ID:', selectedDatabaseId);
            
            // Check if database is selected
            if (!selectedDatabaseId) {
                console.warn('⚠️ No database selected');
                showMessage('listTablesMessage', '❌ Please select a database first', 'error');
                const listEl = document.getElementById('tableList');
                listEl.innerHTML = '<p style="color: rgba(254, 243, 199, 0.6); text-align: center; padding: 20px;">No database selected. Please select a database from the Dashboard.</p>';
                return;
            }
            
            // Get connection info from selected database (supports both Localhost and Hostinger)
            const conn = getConnectionById(selectedDatabaseId);
            
            console.log('Connection Info:', conn);
            
            if (!conn) {
                console.error('❌ Connection not found for ID:', selectedDatabaseId);
                showMessage('listTablesMessage', '❌ Selected database connection not found', 'error');
                return;
            }
            
            const serverType = conn.isLocalhost ? '🖥️ Localhost' : '🌐 Hostinger';
            console.log(`📡 Fetching tables from ${serverType}:`, conn.dbName, 'on', conn.host);
            
            const listEl = document.getElementById('tableList');
            listEl.innerHTML = `<div style="text-align: center; padding: 40px;"><div class="spinner"></div><div style="margin-top: 15px; color: rgba(254, 243, 199, 0.8);">Loading tables from ${serverType}...</div></div>`;
            
            // Call API with database credentials (Localhost or Hostinger)
            const result = await apiRequest('list_tables', {
                db_host: conn.host,
                db_name: conn.dbName,
                db_user: conn.username,
                db_pass: conn.password,
                db_port: conn.port
            });
            
            console.log('List Tables Result:', result);
            
            if (result.success && result.tables) {
                console.log(`✅ Successfully loaded ${result.tables.length} tables from ${conn.name}`);
                
                if (result.tables.length === 0) {
                    listEl.innerHTML = `<p style="color: rgba(254, 243, 199, 0.6); text-align: center; padding: 20px;">📭 No tables found in this database<br><span style="font-size: 13px; margin-top: 10px; display: block;">Create your first table using "Create Table" section</span></p>`;
                } else {
                    const tables = result.tables;
                    listEl.innerHTML = tables.map(table => `
                        <div class="table-item">
                            <div class="table-name" onclick="viewTableData('${table}')" style="cursor: pointer;">
                                <span class="table-icon">📄</span>
                                <span>${table}</span>
                            </div>
                            <div class="table-actions">
                                <button class="btn btn-primary" onclick="event.stopPropagation(); generateSQLFromList('${table}', false)" title="Generate CREATE TABLE only">
                                    <span>🏗️</span> Structure
                                </button>
                                <button class="btn btn-primary" onclick="event.stopPropagation(); generateSQLFromList('${table}', true)" title="Generate CREATE TABLE + INSERT data">
                                    <span>📦</span> + Data
                                </button>
                            </div>
                        </div>
                    `).join('');
                }
                showMessage('listTablesMessage', `✅ Found ${tables.length} table(s) in ${conn.name}`, 'success');
            } else {
                console.error('❌ List tables failed:', result.message);
                listEl.innerHTML = `<div style="text-align: center; padding: 40px; color: #fca5a5;">❌ Failed to load tables<br><span style="font-size: 13px; margin-top: 10px; display: block;">${result.message}</span></div>`;
                showMessage('listTablesMessage', '❌ ' + result.message, 'error');
                showCustomToast(`❌ Failed to load tables\n${result.message}`, 'error', 4000);
            }
        }

        // View table data
        async function viewTableData(tableName, page = 1) {
            if (!selectedDatabaseId) {
                showMessage('tableDataMessage', '❌ No database selected', 'error');
                return;
            }

            // Get connection info (supports both Localhost and Hostinger)
            const conn = getConnectionById(selectedDatabaseId);
            
            if (!conn) {
                showMessage('tableDataMessage', '❌ Connection not found', 'error');
                return;
            }

            currentTableName = tableName;
            currentTablePage = page;

            // Show table data view, hide tables list
            document.getElementById('tablesListView').style.display = 'none';
            document.getElementById('tableDataView').style.display = 'block';
            document.getElementById('currentTableName').textContent = tableName;

            // Show loading
            document.getElementById('tableDataContainer').innerHTML = '<div style="text-align: center; padding: 40px;"><div class="spinner"></div><div style="margin-top: 15px;">Loading table data...</div></div>';
            document.getElementById('tableInfoStats').innerHTML = '';
            document.getElementById('paginationTop').innerHTML = '';
            document.getElementById('paginationBottom').innerHTML = '';

            // Fetch table data
            const result = await apiRequest('get_table_data', {
                db_host: conn.host,
                db_name: conn.dbName,
                db_user: conn.username,
                db_pass: conn.password,
                db_port: conn.port,
                table_name: tableName,
                page: page,
                limit: 50
            });

            if (result.success) {
                // Render table info stats
                renderTableStats(result.table_info, result.pagination);
                
                // Render table data
                renderTableData(result.columns, result.data);
                
                // Render pagination
                renderPagination(result.pagination);
                
                showMessage('tableDataMessage', `✅ Showing ${result.pagination.showing_from} - ${result.pagination.showing_to} of ${result.pagination.total_rows} rows`, 'success');
            } else {
                document.getElementById('tableDataContainer').innerHTML = `<div style="text-align: center; padding: 40px; color: #fca5a5;">❌ ${result.message}</div>`;
                showMessage('tableDataMessage', `❌ ${result.message}`, 'error');
            }
        }

        // Render table statistics
        function renderTableStats(info, pagination) {
            const statsHtml = `
                <div class="stat-box">
                    <div class="stat-label">📊 Total Rows</div>
                    <div class="stat-value">${pagination.total_rows.toLocaleString()}</div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">⚙️ Engine</div>
                    <div class="stat-value">${info.engine}</div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">💾 Data Size</div>
                    <div class="stat-value">${formatBytes(info.data_length)}</div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">🔤 Collation</div>
                    <div class="stat-value" style="font-size: 12px;">${info.collation}</div>
                </div>
                ${info.created ? `
                <div class="stat-box">
                    <div class="stat-label">📅 Created</div>
                    <div class="stat-value" style="font-size: 12px;">${new Date(info.created).toLocaleDateString()}</div>
                </div>` : ''}
                ${info.updated ? `
                <div class="stat-box">
                    <div class="stat-label">🔄 Updated</div>
                    <div class="stat-value" style="font-size: 12px;">${new Date(info.updated).toLocaleDateString()}</div>
                </div>` : ''}
            `;
            document.getElementById('tableInfoStats').innerHTML = statsHtml;
        }

        // Render table data
        function renderTableData(columns, data) {
            if (data.length === 0) {
                document.getElementById('tableDataContainer').innerHTML = '<div style="text-align: center; padding: 40px; color: rgba(254, 243, 199, 0.6);">📭 No data in this table</div>';
                return;
            }

            const tableHtml = `
                <table class="data-table">
                    <thead>
                        <tr>
                            ${columns.map(col => `<th title="${col.Type}">${col.Field}${col.Key === 'PRI' ? ' 🔑' : ''}${col.Key === 'UNI' ? ' ⭐' : ''}</th>`).join('')}
                        </tr>
                    </thead>
                    <tbody>
                        ${data.map(row => `
                            <tr>
                                ${columns.map(col => {
                                    const value = row[col.Field];
                                    const displayValue = value === null ? '<span style="color: #fca5a5; font-style: italic;">NULL</span>' : 
                                                       typeof value === 'string' && value.length > 100 ? value.substring(0, 100) + '...' : 
                                                       value;
                                    return `<td title="${value}">${displayValue}</td>`;
                                }).join('')}
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
            document.getElementById('tableDataContainer').innerHTML = tableHtml;
        }

        // Render pagination controls
        function renderPagination(pagination) {
            const paginationHtml = `
                <div class="pagination-info">
                    Showing <strong>${pagination.showing_from}</strong> - <strong>${pagination.showing_to}</strong> of <strong>${pagination.total_rows.toLocaleString()}</strong> rows
                    (Page <strong>${pagination.current_page}</strong> of <strong>${pagination.total_pages}</strong>)
                </div>
                <div class="pagination-buttons">
                    <button class="btn btn-secondary" onclick="viewTableData('${currentTableName}', 1)" ${pagination.current_page === 1 ? 'disabled' : ''}>
                        ⏮️ First
                    </button>
                    <button class="btn btn-secondary" onclick="viewTableData('${currentTableName}', ${pagination.current_page - 1})" ${pagination.current_page === 1 ? 'disabled' : ''}>
                        ◀️ Prev
                    </button>
                    <input type="number" class="page-input" value="${pagination.current_page}" min="1" max="${pagination.total_pages}" onchange="viewTableData('${currentTableName}', parseInt(this.value))">
                    <button class="btn btn-secondary" onclick="viewTableData('${currentTableName}', ${pagination.current_page + 1})" ${pagination.current_page === pagination.total_pages ? 'disabled' : ''}>
                        Next ▶️
                    </button>
                    <button class="btn btn-secondary" onclick="viewTableData('${currentTableName}', ${pagination.total_pages})" ${pagination.current_page === pagination.total_pages ? 'disabled' : ''}>
                        Last ⏭️
                    </button>
                </div>
            `;
            document.getElementById('paginationTop').innerHTML = paginationHtml;
            document.getElementById('paginationBottom').innerHTML = paginationHtml;
        }

        // Back to tables list
        function backToTablesList() {
            document.getElementById('tablesListView').style.display = 'block';
            document.getElementById('tableDataView').style.display = 'none';
            currentTableName = null;
            currentTablePage = 1;
        }

        // Format bytes helper
        function formatBytes(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
        }

        // ========================================
        // RANDOM DATA GENERATOR
        // ========================================

        // Sample data pools for realistic generation
        const SAMPLE_DATA = {
            firstNames: ['John', 'Jane', 'Michael', 'Sarah', 'David', 'Emma', 'James', 'Emily', 'Robert', 'Lisa', 'William', 'Mary', 'Richard', 'Jennifer', 'Thomas', 'Linda', 'Charles', 'Patricia', 'Daniel', 'Elizabeth', 'Matthew', 'Susan', 'Anthony', 'Jessica', 'Donald', 'Karen', 'Mark', 'Nancy', 'Paul', 'Betty', 'Steven', 'Helen', 'Andrew', 'Sandra', 'Kenneth', 'Donna', 'Joshua', 'Carol', 'Kevin', 'Ruth', 'Brian', 'Sharon', 'George', 'Michelle', 'Edward', 'Laura', 'Ronald', 'Sarah', 'Timothy', 'Kimberly'],
            lastNames: ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez', 'Hernandez', 'Lopez', 'Gonzalez', 'Wilson', 'Anderson', 'Thomas', 'Taylor', 'Moore', 'Jackson', 'Martin', 'Lee', 'Thompson', 'White', 'Harris', 'Sanchez', 'Clark', 'Ramirez', 'Lewis', 'Robinson', 'Walker'],
            companies: ['TechCorp', 'DataSoft', 'CloudSys', 'WebFlow', 'AppWorks', 'NetCore', 'CodeBase', 'DevHub', 'InfoTech', 'SoftWare Inc', 'Digital Solutions', 'Smart Systems', 'Tech Innovators', 'Global Tech', 'Future Labs'],
            domains: ['gmail.com', 'yahoo.com', 'outlook.com', 'hotmail.com', 'company.com', 'business.net', 'email.com', 'mail.com'],
            cities: ['New York', 'Los Angeles', 'Chicago', 'Houston', 'Phoenix', 'Philadelphia', 'San Antonio', 'San Diego', 'Dallas', 'San Jose', 'Austin', 'Jacksonville', 'London', 'Paris', 'Tokyo', 'Berlin', 'Sydney', 'Toronto', 'Dubai', 'Singapore'],
            countries: ['USA', 'UK', 'Canada', 'Australia', 'Germany', 'France', 'Japan', 'China', 'India', 'Brazil', 'Mexico', 'Italy', 'Spain', 'South Korea', 'Netherlands'],
            statuses: ['active', 'inactive', 'pending', 'completed', 'cancelled', 'processing', 'approved', 'rejected', 'on_hold', 'verified'],
            categories: ['General', 'Technology', 'Business', 'Finance', 'Health', 'Education', 'Entertainment', 'Sports', 'News', 'Science'],
            products: ['Laptop', 'Smartphone', 'Tablet', 'Monitor', 'Keyboard', 'Mouse', 'Headphones', 'Camera', 'Printer', 'Scanner', 'Speaker', 'Router', 'Hard Drive', 'SSD', 'RAM'],
            descriptions: [
                'High quality product with excellent features',
                'Premium service for modern businesses',
                'Innovative solution for your needs',
                'Professional grade equipment',
                'Best value for money',
                'Industry leading performance',
                'Cutting-edge technology',
                'User-friendly interface',
                'Reliable and efficient',
                'Perfect for everyday use'
            ]
        };

        // Generate random data based on column name and type
        function generateRandomValue(columnName, columnType, isAutoIncrement, isPrimaryKey) {
            const colNameLower = columnName.toLowerCase();
            const typeUpper = columnType.toUpperCase();
            
            // Skip auto increment and primary key (will be auto-generated)
            if (isAutoIncrement || (isPrimaryKey && typeUpper.includes('INT'))) {
                return null; // Will be excluded from INSERT
            }
            
            // Name fields
            if (colNameLower.includes('first') && colNameLower.includes('name')) {
                return SAMPLE_DATA.firstNames[Math.floor(Math.random() * SAMPLE_DATA.firstNames.length)];
            }
            if (colNameLower.includes('last') && colNameLower.includes('name')) {
                return SAMPLE_DATA.lastNames[Math.floor(Math.random() * SAMPLE_DATA.lastNames.length)];
            }
            if (colNameLower === 'name' || colNameLower === 'username' || colNameLower === 'user_name' || colNameLower === 'full_name' || colNameLower === 'fullname') {
                const first = SAMPLE_DATA.firstNames[Math.floor(Math.random() * SAMPLE_DATA.firstNames.length)];
                const last = SAMPLE_DATA.lastNames[Math.floor(Math.random() * SAMPLE_DATA.lastNames.length)];
                return colNameLower.includes('user') ? first.toLowerCase() + last.toLowerCase() : first + ' ' + last;
            }
            
            // Email
            if (colNameLower.includes('email') || colNameLower.includes('mail')) {
                const name = SAMPLE_DATA.firstNames[Math.floor(Math.random() * SAMPLE_DATA.firstNames.length)].toLowerCase();
                const num = Math.floor(Math.random() * 999);
                const domain = SAMPLE_DATA.domains[Math.floor(Math.random() * SAMPLE_DATA.domains.length)];
                return `${name}${num}@${domain}`;
            }
            
            // Phone
            if (colNameLower.includes('phone') || colNameLower.includes('mobile') || colNameLower.includes('tel')) {
                return '+1' + Math.floor(Math.random() * 9000000000 + 1000000000);
            }
            
            // Address
            if (colNameLower.includes('address') || colNameLower.includes('street')) {
                return Math.floor(Math.random() * 9999 + 1) + ' ' + SAMPLE_DATA.lastNames[Math.floor(Math.random() * SAMPLE_DATA.lastNames.length)] + ' Street';
            }
            
            // City
            if (colNameLower.includes('city')) {
                return SAMPLE_DATA.cities[Math.floor(Math.random() * SAMPLE_DATA.cities.length)];
            }
            
            // Country
            if (colNameLower.includes('country')) {
                return SAMPLE_DATA.countries[Math.floor(Math.random() * SAMPLE_DATA.countries.length)];
            }
            
            // Company
            if (colNameLower.includes('company') || colNameLower.includes('organization')) {
                return SAMPLE_DATA.companies[Math.floor(Math.random() * SAMPLE_DATA.companies.length)];
            }
            
            // Title
            if (colNameLower.includes('title') && !colNameLower.includes('job')) {
                return 'Sample Title ' + Math.floor(Math.random() * 1000);
            }
            
            // Description
            if (colNameLower.includes('desc') || colNameLower.includes('content') || colNameLower.includes('comment') || colNameLower.includes('note')) {
                return SAMPLE_DATA.descriptions[Math.floor(Math.random() * SAMPLE_DATA.descriptions.length)];
            }
            
            // Status
            if (colNameLower.includes('status') || colNameLower === 'state') {
                return SAMPLE_DATA.statuses[Math.floor(Math.random() * SAMPLE_DATA.statuses.length)];
            }
            
            // Category
            if (colNameLower.includes('category') || colNameLower.includes('type') && !typeUpper.includes('INT')) {
                return SAMPLE_DATA.categories[Math.floor(Math.random() * SAMPLE_DATA.categories.length)];
            }
            
            // Product
            if (colNameLower.includes('product')) {
                return SAMPLE_DATA.products[Math.floor(Math.random() * SAMPLE_DATA.products.length)];
            }
            
            // Price/Amount/Cost
            if (colNameLower.includes('price') || colNameLower.includes('amount') || colNameLower.includes('cost') || colNameLower.includes('total')) {
                return (Math.random() * 999 + 1).toFixed(2);
            }
            
            // Quantity/Stock/Count
            if (colNameLower.includes('quantity') || colNameLower.includes('stock') || colNameLower.includes('count') || colNameLower.includes('qty')) {
                return Math.floor(Math.random() * 100);
            }
            
            // Age
            if (colNameLower === 'age') {
                return Math.floor(Math.random() * 60 + 18);
            }
            
            // Password/Hash
            if (colNameLower.includes('password') || colNameLower.includes('hash')) {
                return '$2y$10$' + Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
            }
            
            // Token
            if (colNameLower.includes('token') || colNameLower.includes('key') && typeUpper.includes('VARCHAR')) {
                return Math.random().toString(36).substring(2) + Math.random().toString(36).substring(2);
            }
            
            // URL/Link
            if (colNameLower.includes('url') || colNameLower.includes('link') || colNameLower.includes('website')) {
                return 'https://example.com/' + Math.random().toString(36).substring(7);
            }
            
            // Slug
            if (colNameLower.includes('slug')) {
                return 'sample-slug-' + Math.floor(Math.random() * 10000);
            }
            
            // IP Address
            if (colNameLower.includes('ip')) {
                return Math.floor(Math.random() * 255) + '.' + Math.floor(Math.random() * 255) + '.' + Math.floor(Math.random() * 255) + '.' + Math.floor(Math.random() * 255);
            }
            
            // Based on data type
            if (typeUpper.includes('INT') || typeUpper.includes('INTEGER')) {
                if (typeUpper.includes('TINYINT')) {
                    return Math.floor(Math.random() * 2); // 0 or 1 for boolean
                }
                return Math.floor(Math.random() * 10000);
            }
            
            if (typeUpper.includes('DECIMAL') || typeUpper.includes('FLOAT') || typeUpper.includes('DOUBLE')) {
                return (Math.random() * 1000).toFixed(2);
            }
            
            if (typeUpper.includes('DATE')) {
                const start = new Date(2020, 0, 1);
                const end = new Date();
                const randomDate = new Date(start.getTime() + Math.random() * (end.getTime() - start.getTime()));
                return randomDate.toISOString().split('T')[0];
            }
            
            if (typeUpper.includes('DATETIME') || typeUpper.includes('TIMESTAMP')) {
                const start = new Date(2020, 0, 1);
                const end = new Date();
                const randomDate = new Date(start.getTime() + Math.random() * (end.getTime() - start.getTime()));
                return randomDate.toISOString().slice(0, 19).replace('T', ' ');
            }
            
            if (typeUpper.includes('TIME') && !typeUpper.includes('DATETIME')) {
                const h = Math.floor(Math.random() * 24).toString().padStart(2, '0');
                const m = Math.floor(Math.random() * 60).toString().padStart(2, '0');
                const s = Math.floor(Math.random() * 60).toString().padStart(2, '0');
                return `${h}:${m}:${s}`;
            }
            
            if (typeUpper.includes('BOOL')) {
                return Math.random() > 0.5 ? 1 : 0;
            }
            
            if (typeUpper.includes('TEXT') || typeUpper.includes('VARCHAR') || typeUpper.includes('CHAR')) {
                // Generic text
                if (colNameLower.includes('desc') || colNameLower.includes('content')) {
                    return 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. ' + Math.random().toString(36).substring(7);
                }
                return 'Sample ' + columnName + ' ' + Math.floor(Math.random() * 1000);
            }
            
            // Default
            return 'Data_' + Math.floor(Math.random() * 10000);
        }

        // Generate random records for table
        function generateRandomRecords(columns, count = 10) {
            const records = [];
            
            for (let i = 0; i < count; i++) {
                const record = {};
                
                columns.forEach(col => {
                    const isAutoIncrement = col.Extra && col.Extra.toLowerCase().includes('auto_increment');
                    const isPrimaryKey = col.Key === 'PRI';
                    
                    // Skip auto increment columns
                    if (isAutoIncrement) {
                        return;
                    }
                    
                    const value = generateRandomValue(col.Field, col.Type, isAutoIncrement, isPrimaryKey);
                    
                    if (value !== null) {
                        record[col.Field] = value;
                    }
                });
                
                records.push(record);
            }
            
            return records;
        }

        // Inject random records into current table (from List Tables)
        async function injectRandomRecords() {
            if (!selectedDatabaseId || !currentTableName) {
                showMessage('tableDataMessage', '❌ No table selected', 'error');
                return;
            }

            // Get connection info (supports both Localhost and Hostinger)
            const conn = getConnectionById(selectedDatabaseId);
            
            if (!conn) {
                showMessage('tableDataMessage', '❌ Connection not found', 'error');
                return;
            }

            // Get table structure first
            showMessage('tableDataMessage', '🔄 Generating random data...', 'info');

            const structureResult = await apiRequest('get_table_structure', {
                db_host: conn.host,
                db_name: conn.dbName,
                db_user: conn.username,
                db_pass: conn.password,
                db_port: conn.port,
                table_name: currentTableName
            });

            if (!structureResult.success) {
                showMessage('tableDataMessage', `❌ ${structureResult.message}`, 'error');
                return;
            }

            // Generate random records
            const randomRecords = generateRandomRecords(structureResult.columns, 10);

            if (randomRecords.length === 0) {
                showMessage('tableDataMessage', '❌ Could not generate records (table has only auto-increment columns?)', 'error');
                return;
            }

            // Insert records
            showMessage('tableDataMessage', '🔄 Injecting 10 random records...', 'info');

            const result = await apiRequest('insert_random_data', {
                db_host: conn.host,
                db_name: conn.dbName,
                db_user: conn.username,
                db_pass: conn.password,
                db_port: conn.port,
                table_name: currentTableName,
                records_data: JSON.stringify(randomRecords),
                record_count: 10
            });

            if (result.success) {
                showMessage('tableDataMessage', `✅ ${result.message}`, 'success');
                // Reload table data to show new records
                viewTableData(currentTableName, currentTablePage);
            } else {
                showMessage('tableDataMessage', `❌ ${result.message}`, 'error');
            }
        }

        // Inject random records from Edit Table (Manage Data tab)
        async function injectRandomRecordsFromEdit() {
            if (!selectedDatabaseId || !currentEditTableName) {
                showMessage('editTableMessage', '❌ No table selected', 'error');
                return;
            }

            // Get connection info (supports both Localhost and Hostinger)
            const conn = getConnectionById(selectedDatabaseId);
            
            if (!conn) {
                showMessage('editTableMessage', '❌ Connection not found', 'error');
                return;
            }

            // Use existing columns structure if available
            if (currentEditTableColumns.length === 0) {
                showMessage('editTableMessage', '❌ Table structure not loaded', 'error');
                return;
            }

            // Generate random records
            showMessage('editTableMessage', '🔄 Generating random data...', 'info');
            const randomRecords = generateRandomRecords(currentEditTableColumns, 10);

            if (randomRecords.length === 0) {
                showMessage('editTableMessage', '❌ Could not generate records (table has only auto-increment columns?)', 'error');
                return;
            }

            // Insert records
            showMessage('editTableMessage', '🔄 Injecting 10 random records...', 'info');

            const result = await apiRequest('insert_random_data', {
                db_host: conn.host,
                db_name: conn.dbName,
                db_user: conn.username,
                db_pass: conn.password,
                db_port: conn.port,
                table_name: currentEditTableName,
                records_data: JSON.stringify(randomRecords),
                record_count: 10
            });

            if (result.success) {
                showMessage('editTableMessage', `✅ ${result.message}`, 'success');
                // Reload data to show new records
                loadTableRecordsForManagement(currentDataPage, currentSearchTerm);
            } else {
                showMessage('editTableMessage', `❌ ${result.message}`, 'error');
            }
        }

        // ========================================
        // SQL GENERATOR FUNCTIONS
        // ========================================

        let generatedSQL = '';

        // Generate full database SQL
        async function generateDatabaseSQL(includeCreateDB, includeData) {
            if (!selectedDatabaseId) {
                showMessage('generateDatabaseMessage', '❌ Please select a database from Dashboard first', 'error');
                return;
            }

            // Use getConnectionById to support BOTH Localhost and Hostinger
            const conn = getConnectionById(selectedDatabaseId);
            
            if (!conn) {
                showMessage('generateDatabaseMessage', '❌ Selected database connection not found', 'error');
                return;
            }
            
            console.log('📊 Generating SQL for:', conn.name, '| Type:', conn.type || 'hostinger');

            // Determine generation type
            let genType = '';
            if (includeCreateDB && includeData) {
                genType = 'Complete Database Dump (CREATE DATABASE + Structure + Data)';
            } else if (includeCreateDB && !includeData) {
                genType = 'Full Database (CREATE DATABASE + Structure Only)';
            } else if (!includeCreateDB && includeData) {
                genType = 'Tables Structure + Data';
            } else {
                genType = 'Tables Structure Only';
            }

            showMessage('generateDatabaseMessage', `🔄 Generating ${genType}...`, 'info');

            const result = await apiRequest('generate_database_sql', {
                db_host: conn.host,
                db_name: conn.dbName,
                db_user: conn.username,
                db_pass: conn.password,
                db_port: conn.port,
                include_create_db: includeCreateDB ? 'true' : 'false',
                include_data: includeData ? 'true' : 'false'
            });

            if (result.success) {
                generatedSQL = result.sql;
                showDatabaseSQLModal(result);
                showMessage('generateDatabaseMessage', `✅ ${genType} generated successfully!`, 'success');
            } else {
                showMessage('generateDatabaseMessage', `❌ ${result.message}`, 'error');
            }
        }

        // Show database SQL modal
        function showDatabaseSQLModal(result) {
            document.getElementById('sqlTableName').textContent = `Database: ${result.database_name}`;
            document.getElementById('sqlCode').value = result.sql;
            
            // Show info
            let typeLabel = '';
            if (result.has_create_db && result.has_data) {
                typeLabel = '💎 Complete Dump (DB + Tables + Data)';
            } else if (result.has_create_db && !result.has_data) {
                typeLabel = '🗄️ Full Database (DB + Tables)';
            } else if (!result.has_create_db && result.has_data) {
                typeLabel = '📦 Tables + Data';
            } else {
                typeLabel = '🏗️ Tables Structure Only';
            }

            const infoHtml = `
                <div class="stat-box" style="flex: 1; min-width: 150px;">
                    <div class="stat-label">📝 Generation Type</div>
                    <div class="stat-value" style="font-size: 12px;">${typeLabel}</div>
                </div>
                <div class="stat-box" style="flex: 1; min-width: 150px;">
                    <div class="stat-label">📊 Total Tables</div>
                    <div class="stat-value" style="font-size: 14px;">${result.total_tables}</div>
                </div>
                ${result.has_data ? `
                <div class="stat-box" style="flex: 1; min-width: 150px;">
                    <div class="stat-label">📈 Total Rows</div>
                    <div class="stat-value" style="font-size: 14px;">${result.total_rows.toLocaleString()}</div>
                </div>` : ''}
                <div class="stat-box" style="flex: 1; min-width: 150px;">
                    <div class="stat-label">📏 SQL Size</div>
                    <div class="stat-value" style="font-size: 14px;">${formatBytes(result.sql_length)}</div>
                </div>
            `;
            document.getElementById('sqlInfo').innerHTML = infoHtml;
            
            // Show success message
            const messageHtml = `
                <div class="message success" style="display: block;">
                    ✅ Database SQL generated successfully! Ready to copy or download.
                </div>
            `;
            document.getElementById('sqlMessage').innerHTML = messageHtml;
            
            document.getElementById('sqlModal').classList.add('active');
        }

        // Generate SQL from table list (when clicking buttons on table cards)
        async function generateSQLFromList(tableName, includeData) {
            if (!selectedDatabaseId) {
                alert('❌ No database selected');
                return;
            }

            // Get connection info (supports both Localhost and Hostinger)
            const conn = getConnectionById(selectedDatabaseId);
            
            if (!conn) {
                alert('❌ Connection not found');
                return;
            }

            // Show loading message
            showMessage('listTablesMessage', `🔄 Generating SQL for ${tableName} ${includeData ? 'with data' : 'structure only'}...`, 'info');

            const result = await apiRequest('generate_table_sql', {
                db_host: conn.host,
                db_name: conn.dbName,
                db_user: conn.username,
                db_pass: conn.password,
                db_port: conn.port,
                table_name: tableName,
                include_data: includeData ? 'true' : 'false'
            });

            if (result.success) {
                generatedSQL = result.sql;
                showSQLModal(result);
                showMessage('listTablesMessage', `✅ SQL generated successfully for ${tableName}!`, 'success');
            } else {
                showMessage('listTablesMessage', `❌ ${result.message}`, 'error');
            }
        }

        // Generate SQL for table (from table data view)
        async function generateSQL(includeData) {
            if (!selectedDatabaseId || !currentTableName) {
                alert('❌ No table selected');
                return;
            }

            // Get connection info (supports both Localhost and Hostinger)
            const conn = getConnectionById(selectedDatabaseId);
            
            if (!conn) {
                alert('❌ Connection not found');
                return;
            }

            // Show loading message
            showMessage('tableDataMessage', `🔄 Generating SQL ${includeData ? 'with data' : 'structure only'}...`, 'info');

            const result = await apiRequest('generate_table_sql', {
                db_host: conn.host,
                db_name: conn.dbName,
                db_user: conn.username,
                db_pass: conn.password,
                db_port: conn.port,
                table_name: currentTableName,
                include_data: includeData ? 'true' : 'false'
            });

            if (result.success) {
                generatedSQL = result.sql;
                showSQLModal(result);
                showMessage('tableDataMessage', `✅ SQL generated successfully!`, 'success');
            } else {
                showMessage('tableDataMessage', `❌ ${result.message}`, 'error');
            }
        }

        // Show SQL modal
        function showSQLModal(result) {
            document.getElementById('sqlTableName').textContent = result.table_name;
            document.getElementById('sqlCode').value = result.sql;
            
            // Show info
            const infoHtml = `
                <div class="stat-box" style="flex: 1; min-width: 150px;">
                    <div class="stat-label">${result.has_data ? '📦 Structure + Data' : '🏗️ Structure Only'}</div>
                    <div class="stat-value" style="font-size: 14px;">${result.has_data ? result.row_count + ' rows' : 'No data'}</div>
                </div>
                <div class="stat-box" style="flex: 1; min-width: 150px;">
                    <div class="stat-label">📏 SQL Size</div>
                    <div class="stat-value" style="font-size: 14px;">${formatBytes(result.sql_length)}</div>
                </div>
            `;
            document.getElementById('sqlInfo').innerHTML = infoHtml;
            
            // Show success message
            const messageHtml = `
                <div class="message success" style="display: block;">
                    ✅ SQL generated successfully! You can now copy or download it.
                </div>
            `;
            document.getElementById('sqlMessage').innerHTML = messageHtml;
            
            document.getElementById('sqlModal').classList.add('active');
        }

        // Close SQL modal
        function closeSQLModal() {
            document.getElementById('sqlModal').classList.remove('active');
            generatedSQL = '';
        }

        // Copy SQL to clipboard
        async function copySQLToClipboard() {
            const sqlCode = document.getElementById('sqlCode').value;
            
            try {
                await navigator.clipboard.writeText(sqlCode);
                
                // Visual feedback in modal
                const messageEl = document.getElementById('sqlMessage');
                messageEl.innerHTML = '<div class="message success" style="display: block;">✅ SQL copied to clipboard successfully!</div>';
                
                setTimeout(() => {
                    messageEl.innerHTML = '<div class="message success" style="display: block;">✅ SQL generated successfully! You can now copy or download it.</div>';
                }, 2000);

                // Show message in appropriate location
                const currentView = document.getElementById('tableDataView').style.display;
                if (currentView === 'none') {
                    showMessage('generateDatabaseMessage', '✅ SQL copied to clipboard!', 'success');
                } else {
                    showMessage('tableDataMessage', '✅ SQL copied to clipboard!', 'success');
                }
            } catch (err) {
                // Fallback for older browsers
                const textarea = document.getElementById('sqlCode');
                textarea.select();
                document.execCommand('copy');
                
                const currentView = document.getElementById('tableDataView').style.display;
                if (currentView === 'none') {
                    showMessage('generateDatabaseMessage', '✅ SQL copied to clipboard!', 'success');
                } else {
                    showMessage('tableDataMessage', '✅ SQL copied to clipboard!', 'success');
                }
            }
        }

        // Download SQL file
        function downloadSQL() {
            const sqlCode = document.getElementById('sqlCode').value;
            const nameText = document.getElementById('sqlTableName').textContent;
            const timestamp = new Date().toISOString().replace(/[:.]/g, '-').slice(0, -5);
            
            // Clean filename (remove special chars)
            const cleanName = nameText.replace(/[^a-zA-Z0-9_-]/g, '_').replace(/_{2,}/g, '_');
            const filename = `${cleanName}_${timestamp}.sql`;
            
            const blob = new Blob([sqlCode], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            
            // Show message in appropriate location
            const currentView = document.getElementById('tableDataView').style.display;
            if (currentView === 'none') {
                showMessage('generateDatabaseMessage', `✅ SQL file downloaded: ${filename}`, 'success');
            } else {
                showMessage('tableDataMessage', `✅ SQL file downloaded: ${filename}`, 'success');
            }
        }

        // Load tables for dropdowns
        async function loadTablesForDropdowns() {
            console.log('=== LOAD TABLES FOR DROPDOWNS ===');
            console.log('Selected Database ID:', selectedDatabaseId);
            
            if (!selectedDatabaseId) {
                console.warn('⚠️ No database selected');
                return;
            }

            // Get connection info (supports both Localhost and Hostinger)
            const conn = getConnectionById(selectedDatabaseId);
            
            console.log('Connection Info:', conn);
            
            if (!conn) {
                console.error('❌ Connection not found for ID:', selectedDatabaseId);
                return;
            }
            
            console.log('📡 Fetching tables from:', conn.dbName, 'on', conn.host);
            console.log('Using credentials:', { host: conn.host, user: conn.username, port: conn.port });
            
            // Show loading in dropdowns
            const loadingOption = '<option value="">🔄 Loading tables...</option>';
            document.getElementById('editTableSelect').innerHTML = loadingOption;
            document.getElementById('deleteTableSelect').innerHTML = loadingOption;
            document.getElementById('renameTableOldSelect').innerHTML = loadingOption;
            
            const result = await apiRequest('list_tables', {
                db_host: conn.host,
                db_name: conn.dbName,
                db_user: conn.username,
                db_pass: conn.password,
                db_port: conn.port
            });
            
            console.log('Tables API Result:', result);
            
            if (result.success && result.tables) {
                const tables = result.tables;
                console.log(`✅ Successfully loaded ${tables.length} tables`);
                
                const options = tables.map(table => 
                    `<option value="${table}">${table}</option>`
                ).join('');
                
                document.getElementById('editTableSelect').innerHTML = '<option value="">-- Select Table --</option>' + options;
                document.getElementById('deleteTableSelect').innerHTML = '<option value="">-- Select Table --</option>' + options;
                document.getElementById('renameTableOldSelect').innerHTML = '<option value="">-- Select Table --</option>' + options;
                
                // Update helper text to show tables loaded
                const helperEl = document.getElementById('editTableHelper');
                if (helperEl) {
                    if (tables.length > 0) {
                        helperEl.innerHTML = `✅ ${tables.length} table(s) loaded - Select one to edit`;
                        helperEl.style.color = '#86efac';
                    } else {
                        helperEl.innerHTML = '⚠️ No tables found in this database';
                        helperEl.style.color = '#fbbf24';
                    }
                }
            } else {
                console.error('❌ Failed to load tables:', result.message);
                
                // Show error in dropdowns
                const errorOption = `<option value="">❌ Error: ${result.message}</option>`;
                document.getElementById('editTableSelect').innerHTML = errorOption;
                document.getElementById('deleteTableSelect').innerHTML = errorOption;
                document.getElementById('renameTableOldSelect').innerHTML = errorOption;
                
                // Update helper text on error
                const helperEl = document.getElementById('editTableHelper');
                if (helperEl) {
                    helperEl.innerHTML = `❌ Failed to load tables: ${result.message}`;
                    helperEl.style.color = '#fca5a5';
                }
                
                // Show user-friendly error
                showCustomToast(`❌ Failed to load tables\n${result.message}`, 'error', 4000);
            }
        }

        // Delete table
        async function deleteTableAction(event) {
            event.preventDefault();
            
            if (!selectedDatabaseId) {
                showMessage('deleteTableMessage', '❌ Please select a database from Dashboard first', 'error');
                return;
            }

            // Get connection info (supports both Localhost and Hostinger)
            const conn = getConnectionById(selectedDatabaseId);
            
            if (!conn) {
                showMessage('deleteTableMessage', '❌ Connection not found', 'error');
                return;
            }
            
            const tableName = document.getElementById('deleteTableSelect').value;
            
            if (!tableName) {
                showMessage('deleteTableMessage', '❌ Please select a table', 'error');
                return;
            }
            
            if (!confirm(`Are you sure you want to delete the table "${tableName}"?\n\nThis action cannot be undone!`)) {
                return;
            }
            
            showMessage('deleteTableMessage', '🔄 Deleting table...', 'info');
            
            const result = await apiRequest('delete_table', {
                db_host: conn.host,
                db_name: conn.dbName,
                db_user: conn.username,
                db_pass: conn.password,
                db_port: conn.port,
                table_name: tableName
            });
            
            if (result.success) {
                showMessage('deleteTableMessage', `✅ ${result.message}`, 'success');
                document.getElementById('deleteTableSelect').value = '';
                loadTablesForDropdowns();
                // Refresh table list if we're in that section
                if (document.getElementById('listTables').classList.contains('active')) {
                    loadTables();
                }
            } else {
                showMessage('deleteTableMessage', `❌ ${result.message}`, 'error');
            }
        }

        // Rename table
        async function renameTableAction(event) {
            event.preventDefault();
            
            if (!selectedDatabaseId) {
                showMessage('renameTableMessage', '❌ Please select a database from Dashboard first', 'error');
                return;
            }

            // Get connection info (supports both Localhost and Hostinger)
            const conn = getConnectionById(selectedDatabaseId);
            
            if (!conn) {
                showMessage('renameTableMessage', '❌ Connection not found', 'error');
                return;
            }
            
            const oldName = document.getElementById('renameTableOldSelect').value;
            const newName = document.getElementById('renameTableNewName').value.trim();
            
            if (!oldName || !newName) {
                showMessage('renameTableMessage', '❌ Please provide both old and new table names', 'error');
                return;
            }
            
            if (oldName === newName) {
                showMessage('renameTableMessage', '❌ New name must be different from old name', 'error');
                return;
            }
            
            showMessage('renameTableMessage', '🔄 Renaming table...', 'info');
            
            const result = await apiRequest('rename_table', {
                db_host: conn.host,
                db_name: conn.dbName,
                db_user: conn.username,
                db_pass: conn.password,
                db_port: conn.port,
                old_table_name: oldName,
                new_table_name: newName
            });
            
            if (result.success) {
                showMessage('renameTableMessage', `✅ ${result.message}`, 'success');
                document.getElementById('renameTableOldSelect').value = '';
                document.getElementById('renameTableNewName').value = '';
                loadTablesForDropdowns();
                // Refresh table list if we're in that section
                if (document.getElementById('listTables').classList.contains('active')) {
                    loadTables();
                }
            } else {
                showMessage('renameTableMessage', `❌ ${result.message}`, 'error');
            }
        }

        // ========================================
        // EDIT TABLE - CRUD OPERATIONS
        // ========================================

        let currentEditTableName = null;
        let currentEditTableColumns = [];
        let currentEditTablePrimaryKey = null;
        let currentRecordFormMode = 'add'; // 'add' or 'edit'
        let currentEditingRecord = null;
        let currentDataPage = 1;
        let currentSearchTerm = '';

        // Switch between tabs in Edit Table
        function switchEditTab(tabName) {
            const editSection = document.getElementById('editTableTabs');
            if (!editSection) return;

            editSection.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            
            document.getElementById('editStructureTab').classList.remove('active');
            document.getElementById('editDataTab').classList.remove('active');
            document.getElementById('editTableInfoTab').classList.remove('active');
            
            if (tabName === 'structure') {
                editSection.querySelector('.tab-btn:nth-child(1)').classList.add('active');
                document.getElementById('editStructureTab').classList.add('active');
            } else if (tabName === 'manageData') {
                editSection.querySelector('.tab-btn:nth-child(2)').classList.add('active');
                document.getElementById('editDataTab').classList.add('active');
                // Load data when switching to manage data tab
                loadTableRecordsForManagement(1);
            } else if (tabName === 'tableInfo') {
                editSection.querySelector('.tab-btn:nth-child(3)').classList.add('active');
                document.getElementById('editTableInfoTab').classList.add('active');
                // Generate table info when switching to this tab
                generateTableInfo();
            }
        }

        // Load table for editing (called when table is selected)
        async function loadTableForEditing() {
            const tableName = document.getElementById('editTableSelect').value;
            
            console.log('=== LOAD TABLE FOR EDITING ===');
            console.log('Table Name:', tableName);
            console.log('Selected Database ID:', selectedDatabaseId);
            
            if (!tableName || !selectedDatabaseId) {
                console.warn('⚠️ Missing table name or database ID');
                document.getElementById('editTableTabs').style.display = 'none';
                return;
            }

            currentEditTableName = tableName;

            // Get connection info (supports both Localhost and Hostinger)
            const conn = getConnectionById(selectedDatabaseId);
            
            console.log('Connection Info:', conn);
            
            if (!conn) {
                console.error('❌ Connection not found for editing');
                showMessage('editTableMessage', '❌ Connection not found', 'error');
                return;
            }
            
            const serverType = conn.isLocalhost ? '🖥️ Localhost' : '🌐 Hostinger';
            console.log(`📡 Loading table structure from ${serverType}:`, tableName);
            
            showMessage('editTableMessage', `🔄 Loading table: ${tableName}...`, 'info');
            
            // Load table structure
            const result = await apiRequest('get_table_structure', {
                db_host: conn.host,
                db_name: conn.dbName,
                db_user: conn.username,
                db_pass: conn.password,
                db_port: conn.port,
                table_name: tableName
            });
            
            console.log('Table Structure Result:', result);
            
            if (result.success) {
                currentEditTableColumns = result.columns;
                console.log(`✅ Table structure loaded: ${result.columns.length} columns`);
                
                // Find primary key
                currentEditTablePrimaryKey = null;
                for (const col of result.columns) {
                    if (col.Key === 'PRI') {
                        currentEditTablePrimaryKey = col.Field;
                        break;
                    }
                }

                // Display structure
                const container = document.getElementById('currentColumns');
                container.innerHTML = result.columns.map(col => `
                    <div style="background: rgba(255,255,255,0.05); padding: 15px; border-radius: 8px; margin-bottom: 10px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <strong style="color: #fbbf24;">${col.Field}</strong>
                                <span style="color: rgba(254,243,199,0.7); margin-left: 15px;">${col.Type}</span>
                                ${col.Null === 'NO' ? '<span style="color: #ef4444; margin-left: 10px;">NOT NULL</span>' : ''}
                                ${col.Key === 'PRI' ? '<span style="color: #fbbf24; margin-left: 10px;">PRIMARY KEY</span>' : ''}
                                ${col.Key === 'UNI' ? '<span style="color: #3b82f6; margin-left: 10px;">UNIQUE</span>' : ''}
                                ${col.Extra ? `<span style="color: #86efac; margin-left: 10px;">${col.Extra}</span>` : ''}
                            </div>
                            <button type="button" class="btn btn-danger btn-icon" onclick="dropColumn('${tableName}', '${col.Field}')">×</button>
                        </div>
                        ${col.Default !== null ? `<div style="color: rgba(254,243,199,0.6); margin-top: 5px; font-size: 12px;">Default: ${col.Default}</div>` : ''}
                    </div>
                `).join('');
                
                // Show tabs
                document.getElementById('editTableTabs').style.display = 'block';
                
                showMessage('editTableMessage', `✅ Loaded table: ${tableName}`, 'success');
            } else {
                showMessage('editTableMessage', '❌ ' + result.message, 'error');
            }
        }

        // Load table structure (old function - for backward compatibility)
        function loadTableStructure() {
            loadTableForEditing();
        }

        // Load records for management
        async function loadTableRecordsForManagement(page = 1, searchTerm = '') {
            if (!selectedDatabaseId || !currentEditTableName) {
                document.getElementById('dataManagementContainer').innerHTML = '<p style="color: rgba(254, 243, 199, 0.6); text-align: center; padding: 20px;">Please select a table first</p>';
                return;
            }

            // Get connection info (supports both Localhost and Hostinger)
            const conn = getConnectionById(selectedDatabaseId);
            
            if (!conn) return;

            currentDataPage = page;
            currentSearchTerm = searchTerm;

            document.getElementById('dataManagementContainer').innerHTML = '<div style="text-align: center; padding: 40px;"><div class="spinner"></div><div style="margin-top: 15px;">Loading records...</div></div>';

            const result = await apiRequest('search_records', {
                db_host: conn.host,
                db_name: conn.dbName,
                db_user: conn.username,
                db_pass: conn.password,
                db_port: conn.port,
                table_name: currentEditTableName,
                search_term: searchTerm,
                page: page,
                limit: 20
            });

            if (result.success) {
                renderDataManagementTable(result.columns, result.data);
                renderDataPagination(result.pagination);
            } else {
                document.getElementById('dataManagementContainer').innerHTML = `<div style="text-align: center; padding: 40px; color: #fca5a5;">❌ ${result.message}</div>`;
            }
        }

        // Render data management table
        function renderDataManagementTable(columns, data) {
            if (data.length === 0) {
                document.getElementById('dataManagementContainer').innerHTML = '<div style="text-align: center; padding: 40px; color: rgba(254, 243, 199, 0.6);">📭 No records found</div>';
                return;
            }

            let html = '<div style="overflow-x: auto; background: rgba(0,0,0,0.2); border-radius: 8px; padding: 10px;"><table class="data-table">';
            
            // Header
            html += '<thead><tr>';
            html += '<th style="width: 180px; text-align: center; position: sticky; left: 0; background: rgba(251, 191, 36, 0.2); z-index: 5;">Actions</th>';
            columns.forEach(col => {
                html += `<th title="${col.Type}">${col.Field}${col.Key === 'PRI' ? ' 🔑' : ''}</th>`;
            });
            html += '</tr></thead>';
            
            // Body
            html += '<tbody>';
            data.forEach((row, index) => {
                const rowId = `row_${index}`;
                
                // Store row data in a global object for access
                if (!window.tableRowsData) window.tableRowsData = {};
                window.tableRowsData[rowId] = row;
                
                html += '<tr>';
                
                // Actions column (sticky) - ALWAYS show buttons
                html += `<td style="position: sticky; left: 0; background: rgba(0, 0, 0, 0.4); z-index: 4; backdrop-filter: blur(5px);">`;
                html += '<div style="display: flex; gap: 5px; justify-content: center;">';
                html += `<button class="btn btn-primary" onclick="editRecordById('${rowId}')" style="padding: 8px 14px; font-size: 13px; white-space: nowrap;">✏️ Edit</button>`;
                html += `<button class="btn btn-danger" onclick="deleteRecordByRow('${rowId}')" style="padding: 8px 14px; font-size: 13px;">🗑️</button>`;
                html += '</div></td>';
                
                // Data columns
                columns.forEach(col => {
                    const value = row[col.Field];
                    let displayValue;
                    if (value === null) {
                        displayValue = '<span style="color: #fca5a5; font-style: italic;">NULL</span>';
                    } else if (typeof value === 'string' && value.length > 50) {
                        displayValue = value.substring(0, 50) + '...';
                    } else {
                        displayValue = value;
                    }
                    html += `<td title="${value}">${displayValue}</td>`;
                });
                
                html += '</tr>';
            });
            html += '</tbody></table></div>';
            
            document.getElementById('dataManagementContainer').innerHTML = html;
        }

        // Edit record by row ID
        function editRecordById(rowId) {
            if (window.tableRowsData && window.tableRowsData[rowId]) {
                showRecordForm('edit', window.tableRowsData[rowId]);
            }
        }

        // Delete record by row (works with or without PK)
        function deleteRecordByRow(rowId) {
            if (!window.tableRowsData || !window.tableRowsData[rowId]) {
                showMessage('editTableMessage', '❌ Record data not found', 'error');
                return;
            }

            const record = window.tableRowsData[rowId];
            
            if (!confirm('Are you sure you want to delete this record?\n\nThis action cannot be undone!')) {
                return;
            }

            // If table has primary key, use it
            if (currentEditTablePrimaryKey && record[currentEditTablePrimaryKey]) {
                deleteRecordAction(record[currentEditTablePrimaryKey]);
            } else {
                // No primary key - use all columns as WHERE condition
                deleteRecordByAllColumns(record);
            }
        }

        // Delete record using all columns (when no PK)
        async function deleteRecordByAllColumns(record) {
            if (!selectedDatabaseId || !currentEditTableName) {
                showMessage('editTableMessage', '❌ No table selected', 'error');
                return;
            }

            // Get connection info (supports both Localhost and Hostinger)
            const conn = getConnectionById(selectedDatabaseId);
            
            if (!conn) return;

            showMessage('editTableMessage', '🔄 Deleting record...', 'info');

            // Build WHERE clause using all columns
            const whereConditions = [];
            const whereValues = [];
            
            currentEditTableColumns.forEach(col => {
                const value = record[col.Field];
                whereConditions.push(`\`${col.Field}\` = ?`);
                whereValues.push(value);
            });

            // For tables without PK, we'll delete using LIMIT 1 to be safe
            const whereSql = whereConditions.join(' AND ');

            // Since we can't send complex WHERE via our current API,
            // let's use the primary key method but with first column as fallback
            let identifierKey = currentEditTablePrimaryKey;
            let identifierValue = currentEditTablePrimaryKey ? record[currentEditTablePrimaryKey] : null;

            // Fallback: use first column
            if (!identifierValue && currentEditTableColumns.length > 0) {
                identifierKey = currentEditTableColumns[0].Field;
                identifierValue = record[identifierKey];
            }

            if (!identifierValue) {
                showMessage('editTableMessage', '❌ Cannot delete: No identifier found', 'error');
                return;
            }

            const result = await apiRequest('delete_record', {
                db_host: conn.host,
                db_name: conn.dbName,
                db_user: conn.username,
                db_pass: conn.password,
                db_port: conn.port,
                table_name: currentEditTableName,
                primary_key: identifierKey,
                primary_value: identifierValue
            });

            if (result.success) {
                showMessage('editTableMessage', `✅ ${result.message}`, 'success');
                loadTableRecordsForManagement(currentDataPage, currentSearchTerm);
            } else {
                showMessage('editTableMessage', `❌ ${result.message}`, 'error');
            }
        }

        // Render data pagination
        function renderDataPagination(pagination) {
            const html = `
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 15px; background: rgba(255,255,255,0.05); border-radius: 8px;">
                    <div class="pagination-info">
                        Showing <strong>${pagination.showing_from}</strong> - <strong>${pagination.showing_to}</strong> of <strong>${pagination.total_rows}</strong> records
                    </div>
                    <div class="pagination-buttons">
                        <button class="btn btn-secondary" onclick="loadTableRecordsForManagement(1, currentSearchTerm)" ${pagination.current_page === 1 ? 'disabled' : ''}>⏮️ First</button>
                        <button class="btn btn-secondary" onclick="loadTableRecordsForManagement(${pagination.current_page - 1}, currentSearchTerm)" ${pagination.current_page === 1 ? 'disabled' : ''}>◀️ Prev</button>
                        <span style="padding: 0 15px;">Page ${pagination.current_page} of ${pagination.total_pages}</span>
                        <button class="btn btn-secondary" onclick="loadTableRecordsForManagement(${pagination.current_page + 1}, currentSearchTerm)" ${pagination.current_page === pagination.total_pages ? 'disabled' : ''}>Next ▶️</button>
                        <button class="btn btn-secondary" onclick="loadTableRecordsForManagement(${pagination.total_pages}, currentSearchTerm)" ${pagination.current_page === pagination.total_pages ? 'disabled' : ''}>Last ⏭️</button>
                    </div>
                </div>
            `;
            document.getElementById('dataPagination').innerHTML = html;
        }

        // Instant search (real-time)
        let searchTimeout = null;
        function searchTableRecordsInstant() {
            // Clear previous timeout
            if (searchTimeout) {
                clearTimeout(searchTimeout);
            }

            const searchInput = document.getElementById('dataSearchInput');
            const searchTerm = searchInput.value.trim();
            const statusEl = document.getElementById('searchStatus');

            if (searchTerm) {
                statusEl.textContent = '🔍 Searching...';
            } else {
                statusEl.textContent = '';
            }

            // Debounce search (wait 300ms after user stops typing)
            searchTimeout = setTimeout(() => {
                loadTableRecordsForManagement(1, searchTerm);
                if (searchTerm) {
                    statusEl.textContent = `🔍 Search results for: "${searchTerm}"`;
                } else {
                    statusEl.textContent = '';
                }
            }, 300);
        }

        // Search table records (button click)
        function searchTableRecords() {
            const searchTerm = document.getElementById('dataSearchInput').value.trim();
            loadTableRecordsForManagement(1, searchTerm);
        }

        // Clear search
        function clearSearch() {
            document.getElementById('dataSearchInput').value = '';
            document.getElementById('searchStatus').textContent = '';
            loadTableRecordsForManagement(1, '');
        }

        // Show record form (add or edit)
        function showRecordForm(mode, record = null) {
            currentRecordFormMode = mode;
            currentEditingRecord = record;

            if (mode === 'add') {
                document.getElementById('recordFormTitle').textContent = `Add New Record to ${currentEditTableName}`;
                document.getElementById('recordFormSaveIcon').textContent = '➕';
                document.getElementById('recordFormSaveText').textContent = 'Add Record';
            } else {
                document.getElementById('recordFormTitle').textContent = `Edit Record in ${currentEditTableName}`;
                document.getElementById('recordFormSaveIcon').textContent = '💾';
                document.getElementById('recordFormSaveText').textContent = 'Update Record';
            }

            // Generate form fields dynamically
            let formHtml = '';
            
            // Add hidden field for identifier in edit mode
            if (mode === 'edit') {
                const identifierKey = currentEditTablePrimaryKey || (currentEditTableColumns.length > 0 ? currentEditTableColumns[0].Field : null);
                const identifierValue = identifierKey && record ? record[identifierKey] : '';
                if (identifierKey) {
                    formHtml += `<input type="hidden" id="recordIdentifierKey" value="${identifierKey}">`;
                    formHtml += `<input type="hidden" id="recordIdentifierValue" value="${identifierValue}">`;
                }
            }

            currentEditTableColumns.forEach(col => {
                const isAutoIncrement = col.Extra && col.Extra.toLowerCase().includes('auto_increment');
                const value = record ? record[col.Field] : '';
                
                // Skip auto increment in add mode
                if (mode === 'add' && isAutoIncrement) {
                    return;
                }

                // Determine identifier (PK or first column)
                const identifierKey = currentEditTablePrimaryKey || (currentEditTableColumns.length > 0 ? currentEditTableColumns[0].Field : null);
                
                // Read-only for identifier in edit mode ONLY if it's auto-increment
                const isReadOnly = mode === 'edit' && col.Field === identifierKey && isAutoIncrement;

                formHtml += `
                    <div class="form-group">
                        <label class="form-label">
                            ${col.Field}
                            ${col.Key === 'PRI' ? ' 🔑' : ''}
                            ${col.Field === identifierKey && mode === 'edit' ? ' <span style="color: #3b82f6;">(Identifier)</span>' : ''}
                            ${col.Null === 'NO' && !isAutoIncrement ? ' <span style="color: #ef4444;">*</span>' : ''}
                        </label>
                        ${generateFormInput(col, value, isReadOnly)}
                        <div class="helper-text">${col.Type}${col.Null === 'YES' ? ' (Optional)' : col.Null === 'NO' && !isAutoIncrement ? ' (Required)' : ''}</div>
                    </div>
                `;
            });

            document.getElementById('recordFormFields').innerHTML = formHtml;
            document.getElementById('recordFormMessage').innerHTML = '';
            document.getElementById('recordFormModal').classList.add('active');
        }

        // Generate form input based on column type
        function generateFormInput(col, value, isReadOnly) {
            const typeUpper = col.Type.toUpperCase();
            const required = col.Null === 'NO' && !col.Extra?.includes('auto_increment') ? 'required' : '';
            const readonlyAttr = isReadOnly ? 'readonly' : '';
            const readonlyStyle = isReadOnly ? 'style="background: rgba(255, 255, 255, 0.05); cursor: not-allowed;"' : '';
            
            // TEXT/LONGTEXT
            if (typeUpper.includes('TEXT') && !typeUpper.includes('TINY')) {
                return `<textarea name="${col.Field}" class="form-input" rows="4" ${required} ${readonlyAttr} ${readonlyStyle}>${value || ''}</textarea>`;
            }
            
            // DATE
            if (typeUpper.includes('DATE') && !typeUpper.includes('TIME')) {
                return `<input type="date" name="${col.Field}" class="form-input" value="${value || ''}" ${required} ${readonlyAttr} ${readonlyStyle}>`;
            }
            
            // DATETIME/TIMESTAMP
            if (typeUpper.includes('DATETIME') || typeUpper.includes('TIMESTAMP')) {
                const datetimeValue = value ? value.replace(' ', 'T').slice(0, 16) : '';
                return `<input type="datetime-local" name="${col.Field}" class="form-input" value="${datetimeValue}" ${required} ${readonlyAttr} ${readonlyStyle}>`;
            }
            
            // TIME
            if (typeUpper.includes('TIME') && !typeUpper.includes('DATE')) {
                return `<input type="time" name="${col.Field}" class="form-input" value="${value || ''}" ${required} ${readonlyAttr} ${readonlyStyle}>`;
            }
            
            // BOOLEAN/TINYINT(1)
            if (typeUpper.includes('TINYINT(1)') || typeUpper.includes('BOOLEAN') || typeUpper.includes('BOOL')) {
                return `
                    <select name="${col.Field}" class="form-select" ${required} ${readonlyAttr} ${readonlyStyle}>
                        <option value="">-- Select --</option>
                        <option value="1" ${value == 1 ? 'selected' : ''}>✅ True (1)</option>
                        <option value="0" ${value == 0 ? 'selected' : ''}>❌ False (0)</option>
                    </select>
                `;
            }
            
            // INT/DECIMAL/FLOAT - use number input
            if (typeUpper.includes('INT') || typeUpper.includes('DECIMAL') || typeUpper.includes('FLOAT') || typeUpper.includes('DOUBLE')) {
                const step = typeUpper.includes('DECIMAL') || typeUpper.includes('FLOAT') || typeUpper.includes('DOUBLE') ? '0.01' : '1';
                return `<input type="number" name="${col.Field}" class="form-input" value="${value || ''}" step="${step}" ${required} ${readonlyAttr} ${readonlyStyle}>`;
            }
            
            // Default: text input
            return `<input type="text" name="${col.Field}" class="form-input" value="${value || ''}" ${required} ${readonlyAttr} ${readonlyStyle}>`;
        }

        // Edit record
        function editRecord(record) {
            showRecordForm('edit', record);
        }

        // Close record form
        function closeRecordForm() {
            document.getElementById('recordFormModal').classList.remove('active');
            currentRecordFormMode = 'add';
            currentEditingRecord = null;
        }

        // Save record (add or update)
        async function saveRecord(event) {
            event.preventDefault();

            if (!selectedDatabaseId || !currentEditTableName) {
                showMessage('editTableMessage', '❌ No table selected', 'error');
                return;
            }

            // Get connection info (supports both Localhost and Hostinger)
            const conn = getConnectionById(selectedDatabaseId);
            
            if (!conn) {
                showMessage('editTableMessage', '❌ Connection not found', 'error');
                return;
            }

            // Get form data
            const formData = new FormData(document.getElementById('recordForm'));
            const recordData = {};
            for (const [key, value] of formData.entries()) {
                recordData[key] = value || null;
            }

            const messageEl = document.getElementById('recordFormMessage');

            if (currentRecordFormMode === 'add') {
                // Insert new record
                messageEl.className = 'message info';
                messageEl.textContent = '🔄 Adding record...';
                messageEl.style.display = 'block';

                const result = await apiRequest('insert_record', {
                    db_host: conn.host,
                    db_name: conn.dbName,
                    db_user: conn.username,
                    db_pass: conn.password,
                    db_port: conn.port,
                    table_name: currentEditTableName,
                    record_data: JSON.stringify(recordData)
                });

                if (result.success) {
                    showMessage('editTableMessage', `✅ ${result.message}`, 'success');
                    closeRecordForm();
                    loadTableRecordsForManagement(currentDataPage, currentSearchTerm);
                } else {
                    messageEl.className = 'message error';
                    messageEl.textContent = `❌ ${result.message}`;
                }
            } else {
                // Update existing record
                if (!currentEditingRecord) {
                    messageEl.className = 'message error';
                    messageEl.textContent = '❌ Cannot update: No record data';
                    return;
                }

                messageEl.className = 'message info';
                messageEl.textContent = '🔄 Updating record...';
                messageEl.style.display = 'block';

                // Determine identifier (Primary Key or first column)
                let identifierKey = currentEditTablePrimaryKey;
                let identifierValue = currentEditTablePrimaryKey ? currentEditingRecord[currentEditTablePrimaryKey] : null;

                // Fallback: use first column as identifier
                if (!identifierValue && currentEditTableColumns.length > 0) {
                    identifierKey = currentEditTableColumns[0].Field;
                    identifierValue = currentEditingRecord[identifierKey];
                }

                if (!identifierValue) {
                    messageEl.className = 'message error';
                    messageEl.textContent = '❌ Cannot update: No identifier found';
                    return;
                }

                const result = await apiRequest('update_record', {
                    db_host: conn.host,
                    db_name: conn.dbName,
                    db_user: conn.username,
                    db_pass: conn.password,
                    db_port: conn.port,
                    table_name: currentEditTableName,
                    record_data: JSON.stringify(recordData),
                    primary_key: identifierKey,
                    primary_value: identifierValue
                });

                if (result.success) {
                    showMessage('editTableMessage', `✅ ${result.message}`, 'success');
                    closeRecordForm();
                    loadTableRecordsForManagement(currentDataPage, currentSearchTerm);
                } else {
                    messageEl.className = 'message error';
                    messageEl.textContent = `❌ ${result.message}`;
                }
            }
        }

        // Delete record action
        async function deleteRecordAction(primaryValue) {
            if (!confirm('Are you sure you want to delete this record?\n\nThis action cannot be undone!')) {
                return;
            }

            if (!selectedDatabaseId || !currentEditTableName || !currentEditTablePrimaryKey) {
                showMessage('editTableMessage', '❌ Cannot delete: Missing information', 'error');
                return;
            }

            // Get connection info (supports both Localhost and Hostinger)
            const conn = getConnectionById(selectedDatabaseId);
            
            if (!conn) return;

            showMessage('editTableMessage', '🔄 Deleting record...', 'info');

            const result = await apiRequest('delete_record', {
                db_host: conn.host,
                db_name: conn.dbName,
                db_user: conn.username,
                db_pass: conn.password,
                db_port: conn.port,
                table_name: currentEditTableName,
                primary_key: currentEditTablePrimaryKey,
                primary_value: primaryValue
            });

            if (result.success) {
                showMessage('editTableMessage', `✅ ${result.message}`, 'success');
                loadTableRecordsForManagement(currentDataPage, currentSearchTerm);
            } else {
                showMessage('editTableMessage', `❌ ${result.message}`, 'error');
            }
        }

        // Generate Table Info for AI
        async function generateTableInfo() {
            if (!selectedDatabaseId || !currentEditTableName) {
                document.getElementById('tableInfoText').value = 'Please select a table first...';
                return;
            }

            // Get connection info (supports both Localhost and Hostinger)
            const conn = getConnectionById(selectedDatabaseId);
            
            if (!conn) {
                document.getElementById('tableInfoText').value = 'Connection not found...';
                return;
            }

            // Show loading
            document.getElementById('tableInfoLoading').style.display = 'block';
            document.getElementById('tableInfoText').value = 'Analyzing table structure...';

            // Get table data with info
            const dataResult = await apiRequest('get_table_data', {
                db_host: conn.host,
                db_name: conn.dbName,
                db_user: conn.username,
                db_pass: conn.password,
                db_port: conn.port,
                table_name: currentEditTableName,
                page: 1,
                limit: 5  // Get first 5 rows as sample
            });

            if (!dataResult.success) {
                document.getElementById('tableInfoLoading').style.display = 'none';
                document.getElementById('tableInfoText').value = `Error: ${dataResult.message}`;
                return;
            }

            // Build table info description
            const info = dataResult.table_info;
            const pagination = dataResult.pagination;
            const columns = dataResult.columns;
            const sampleData = dataResult.data;

            let description = `TABLE INFORMATION FOR AI
=====================================

Database: ${conn.dbName}
Table Name: ${currentEditTableName}
Connection: ${conn.name}

TABLE OVERVIEW:
- Total Records: ${pagination.total_rows.toLocaleString()} rows
- Total Columns: ${columns.length}
- Engine: ${info.engine}
- Collation: ${info.collation}
- Data Size: ${formatBytes(info.data_length)}
- Average Row Length: ${info.avg_row_length} bytes
${info.created ? `- Created: ${new Date(info.created).toLocaleString()}` : ''}
${info.updated ? `- Last Updated: ${new Date(info.updated).toLocaleString()}` : ''}

COLUMN STRUCTURE:
${'='.repeat(60)}

`;

            // List all columns with details
            columns.forEach((col, idx) => {
                description += `${idx + 1}. ${col.Field}\n`;
                description += `   Type: ${col.Type}\n`;
                description += `   Nullable: ${col.Null}\n`;
                if (col.Key) {
                    let keyType = '';
                    if (col.Key === 'PRI') keyType = 'PRIMARY KEY';
                    else if (col.Key === 'UNI') keyType = 'UNIQUE';
                    else if (col.Key === 'MUL') keyType = 'INDEX';
                    description += `   Key: ${keyType}\n`;
                }
                if (col.Default !== null) description += `   Default: ${col.Default}\n`;
                if (col.Extra) description += `   Extra: ${col.Extra}\n`;
                description += `\n`;
            });

            // Sample data (first 5 rows)
            if (sampleData.length > 0) {
                description += `\nSAMPLE DATA (First ${sampleData.length} rows):\n`;
                description += `${'='.repeat(60)}\n\n`;
                
                sampleData.forEach((row, idx) => {
                    description += `Record ${idx + 1}:\n`;
                    columns.forEach(col => {
                        const value = row[col.Field];
                        const displayValue = value === null ? 'NULL' : 
                                           typeof value === 'string' && value.length > 100 ? value.substring(0, 100) + '...' : 
                                           value;
                        description += `  ${col.Field}: ${displayValue}\n`;
                    });
                    description += `\n`;
                });
            }

            description += `\nDATA STATISTICS:\n`;
            description += `${'='.repeat(60)}\n`;
            description += `- Total Rows: ${pagination.total_rows.toLocaleString()}\n`;
            description += `- Table Size: ${formatBytes(info.data_length)}\n`;
            description += `- Average Row Size: ${info.avg_row_length} bytes\n`;
            description += `- Estimated Total Size: ${formatBytes(info.data_length)}\n\n`;

            description += `COLUMN SUMMARY:\n`;
            description += `- Total Columns: ${columns.length}\n`;
            
            // Count column types
            const primaryKeys = columns.filter(c => c.Key === 'PRI').map(c => c.Field);
            const uniqueKeys = columns.filter(c => c.Key === 'UNI').map(c => c.Field);
            const requiredCols = columns.filter(c => c.Null === 'NO').length;
            const autoIncrementCols = columns.filter(c => c.Extra && c.Extra.includes('auto_increment')).map(c => c.Field);
            
            if (primaryKeys.length > 0) description += `- Primary Keys: ${primaryKeys.join(', ')}\n`;
            if (uniqueKeys.length > 0) description += `- Unique Keys: ${uniqueKeys.join(', ')}\n`;
            if (autoIncrementCols.length > 0) description += `- Auto Increment: ${autoIncrementCols.join(', ')}\n`;
            description += `- Required Fields: ${requiredCols} columns\n`;
            description += `- Optional Fields: ${columns.length - requiredCols} columns\n\n`;

            description += `RECOMMENDATIONS FOR AI:\n`;
            description += `- Use column names exactly as shown above\n`;
            description += `- Respect NOT NULL constraints\n`;
            description += `- Auto-increment columns should not be included in INSERT statements\n`;
            description += `- Primary key is: ${primaryKeys.length > 0 ? primaryKeys[0] : 'Not defined'}\n`;
            description += `- Consider data types when generating INSERT/UPDATE queries\n`;
            description += `- Sample data above shows actual data patterns in this table\n`;

            // Set the description
            document.getElementById('tableInfoText').value = description;
            document.getElementById('tableInfoLoading').style.display = 'none';
        }

        // Copy Table Info to clipboard
        async function copyTableInfo() {
            const infoText = document.getElementById('tableInfoText').value;
            
            try {
                await navigator.clipboard.writeText(infoText);
                showMessage('editTableMessage', '✅ Table information copied to clipboard!', 'success');
                
                // Visual feedback
                const btns = document.querySelectorAll('#editTableInfoTab .btn-primary');
                btns.forEach(btn => {
                    if (btn.textContent.includes('Copy')) {
                        const originalHtml = btn.innerHTML;
                        btn.innerHTML = '<span>✅</span> Copied!';
                        btn.style.background = '#22c55e';
                        
                        setTimeout(() => {
                            btn.innerHTML = originalHtml;
                            btn.style.background = '';
                        }, 2000);
                    }
                });
            } catch (err) {
                // Fallback
                const textarea = document.getElementById('tableInfoText');
                textarea.select();
                document.execCommand('copy');
                showMessage('editTableMessage', '✅ Table information copied to clipboard!', 'success');
            }
        }

        // Add column to existing table
        async function addColumn() {
            if (!currentEditTableName || !selectedDatabaseId) {
                showMessage('editTableMessage', '❌ Please select a table first', 'error');
                return;
            }

            // Get connection info (supports both Localhost and Hostinger)
            const conn = getConnectionById(selectedDatabaseId);
            
            if (!conn) {
                showMessage('editTableMessage', '❌ Connection not found', 'error');
                return;
            }
            
            const columnData = {
                name: document.getElementById('newColName').value.trim(),
                type: document.getElementById('newColType').value,
                length: document.getElementById('newColLength').value.trim(),
                nullable: document.getElementById('newColNullable').value,
                defaultValue: document.getElementById('newColDefault').value.trim()
            };
            
            if (!columnData.name) {
                showMessage('editTableMessage', '❌ Please enter a column name', 'error');
                return;
            }
            
            showMessage('editTableMessage', '🔄 Adding column...', 'info');
            
            const result = await apiRequest('alter_table', {
                db_host: conn.host,
                db_name: conn.dbName,
                db_user: conn.username,
                db_pass: conn.password,
                db_port: conn.port,
                table_name: currentEditTableName,
                alter_action: 'add',
                column_data: JSON.stringify(columnData)
            });
            
            if (result.success) {
                showMessage('editTableMessage', `✅ ${result.message}`, 'success');
                document.getElementById('newColName').value = '';
                document.getElementById('newColLength').value = '';
                document.getElementById('newColDefault').value = '';
                // Reload table to refresh structure
                loadTableForEditing();
            } else {
                showMessage('editTableMessage', `❌ ${result.message}`, 'error');
            }
        }

        // Drop column from table
        async function dropColumn(tableName, columnName) {
            if (!confirm(`Are you sure you want to drop column "${columnName}" from table "${tableName}"?\n\nThis action cannot be undone!`)) {
                return;
            }

            if (!selectedDatabaseId) {
                showMessage('editTableMessage', '❌ No database selected', 'error');
                return;
            }

            // Get connection info (supports both Localhost and Hostinger)
            const conn = getConnectionById(selectedDatabaseId);
            
            if (!conn) {
                showMessage('editTableMessage', '❌ Connection not found', 'error');
                return;
            }
            
            showMessage('editTableMessage', '🔄 Dropping column...', 'info');
            
            const result = await apiRequest('alter_table', {
                db_host: conn.host,
                db_name: conn.dbName,
                db_user: conn.username,
                db_pass: conn.password,
                db_port: conn.port,
                table_name: tableName,
                alter_action: 'drop',
                column_data: JSON.stringify({ name: columnName })
            });
            
            if (result.success) {
                showMessage('editTableMessage', `✅ ${result.message}`, 'success');
                // Reload table to refresh structure
                loadTableForEditing();
            } else {
                showMessage('editTableMessage', `❌ ${result.message}`, 'error');
            }
        }

        // Initialize column builder on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Add initial column row
            addColumnRow();
        });

        // ========================================
        // EXPORT/IMPORT CONNECTIONS FUNCTIONS
        // ========================================

        const PREFERRED_EXPORT_PATH_KEY = 'preferred_export_path';

        // Export connections to file (Direct Download)
        async function exportConnectionsToFile() {
            const connections = getHostingerConnections();
            
            if (connections.length === 0) {
                showMessage('settingsMessage', '❌ No connections to export', 'error');
                return;
            }

            showMessage('settingsMessage', '🔄 Preparing export...', 'info');

            // Create export data
            const exportData = {
                exported_at: new Date().toLocaleString(),
                total_connections: connections.length,
                connections: connections
            };

            // Generate filename with timestamp
            const timestamp = new Date().toISOString().replace(/[:.]/g, '-').slice(0, -5);
            const filename = `hostinger_connections_${timestamp}.json`;
            
            // Convert to JSON
            const jsonData = JSON.stringify(exportData, null, 2);
            const blob = new Blob([jsonData], { type: 'application/json' });

            // Check if File System Access API is supported
            if ('showSaveFilePicker' in window) {
                try {
                    // Get preferred directory from localStorage
                    const preferredPath = localStorage.getItem(PREFERRED_EXPORT_PATH_KEY);
                    
                    const options = {
                        suggestedName: filename,
                        types: [{
                            description: 'JSON Files',
                            accept: { 'application/json': ['.json'] }
                        }]
                    };

                    // Show file picker
                    const fileHandle = await window.showSaveFilePicker(options);
                    
                    // Write file
                    const writable = await fileHandle.createWritable();
                    await writable.write(blob);
                    await writable.close();

                    // Save the directory path for next time (if possible)
                    try {
                        const dirHandle = await fileHandle.getParent?.();
                        if (dirHandle) {
                            localStorage.setItem(PREFERRED_EXPORT_PATH_KEY, dirHandle.name);
                        }
                    } catch (e) {
                        // Ignore if getParent is not supported
                    }

                    showMessage('settingsMessage', 
                        `✅ Connections exported successfully!\n📁 File: ${filename}\n📊 Total: ${connections.length} connections`, 
                        'success'
                    );
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        // Fallback to download if user didn't cancel
                        downloadFile(blob, filename);
                        showMessage('settingsMessage', 
                            `✅ Export started! Check your downloads folder.\n📁 File: ${filename}\n📊 Total: ${connections.length} connections`, 
                            'success'
                        );
                    }
                }
            } else {
                // Fallback: Direct download
                downloadFile(blob, filename);
                showMessage('settingsMessage', 
                    `✅ Export started! Check your downloads folder.\n📁 File: ${filename}\n📊 Total: ${connections.length} connections`, 
                    'success'
                );
            }
        }

        // Helper function to download file
        function downloadFile(blob, filename) {
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }

        // Import connections using File Picker
        async function showImportModal() {
            // Check if File System Access API is supported
            if ('showOpenFilePicker' in window) {
                importConnectionsWithFilePicker();
            } else {
                // Fallback: Use hidden input file
                importConnectionsWithInputFile();
            }
        }

        // Import using File System Access API
        async function importConnectionsWithFilePicker() {
            try {
                const options = {
                    types: [{
                        description: 'JSON Files',
                        accept: { 'application/json': ['.json'] }
                    }],
                    multiple: false
                };

                showMessage('settingsMessage', '📂 Opening file picker...', 'info');

                const [fileHandle] = await window.showOpenFilePicker(options);
                const file = await fileHandle.getFile();
                
                await processImportFile(file);
            } catch (error) {
                if (error.name !== 'AbortError') {
                    showMessage('settingsMessage', `❌ Error: ${error.message}`, 'error');
                }
            }
        }

        // Import using traditional input file (fallback)
        function importConnectionsWithInputFile() {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = '.json,application/json';
            
            input.onchange = async (e) => {
                const file = e.target.files[0];
                if (file) {
                    await processImportFile(file);
                }
            };
            
            input.click();
        }

        // Process the imported file
        async function processImportFile(file) {
            try {
                showMessage('settingsMessage', `🔄 Reading file: ${file.name}...`, 'info');

                const text = await file.text();
                const importData = JSON.parse(text);

                // Validate structure
                if (!importData.connections || !Array.isArray(importData.connections)) {
                    showMessage('settingsMessage', '❌ Invalid file format. Expected connections array.', 'error');
                    return;
                }

                // Confirm import
                if (!confirm(`Import ${importData.connections.length} connections from:\n${file.name}\n\nThis will MERGE with your existing connections. Continue?`)) {
                    return;
                }

                showMessage('settingsMessage', '🔄 Importing connections...', 'info');

                // Get current connections
                let currentConnections = getHostingerConnections();
                const importedConnections = importData.connections;

                // Merge strategy: Update existing by ID or add new
                let updatedCount = 0;
                let addedCount = 0;

                importedConnections.forEach(imported => {
                    const existingIndex = currentConnections.findIndex(c => c.id === imported.id);
                    if (existingIndex !== -1) {
                        // Update existing
                        currentConnections[existingIndex] = imported;
                        updatedCount++;
                    } else {
                        // Add new
                        currentConnections.push(imported);
                        addedCount++;
                    }
                });

                // Save merged connections
                saveHostingerConnections(currentConnections);

                // Refresh UI
                loadHostingerConnectionsTable();
                loadDashboardConnections();
                
                // Update toggle button to reflect new count
                updateConnectionToggleButton();

                showMessage('settingsMessage', 
                    `✅ Import successful!\n📊 Total: ${importData.connections.length}\n✏️ Updated: ${updatedCount}\n➕ Added: ${addedCount}\n📅 Exported: ${importData.exported_at || 'Unknown'}`, 
                    'success'
                );
            } catch (error) {
                showMessage('settingsMessage', `❌ Import failed: ${error.message}`, 'error');
            }
        }

        // Close import modal (not needed anymore but keeping for compatibility)
        function closeImportModal() {
            const modal = document.getElementById('importModal');
            if (modal) {
                modal.classList.remove('active');
            }
        }

        // ========================================
        // AI PROMPT GENERATOR FUNCTIONS
        // ========================================

        let promptGenTables = [];
        let promptGenTablesRowCount = {};
        let selectedPromptGenTables = new Set();
        let selectedPromptGenDatabaseId = null;

        // Load databases for prompt generator dropdown
        function loadPromptGenDatabases() {
            const select = document.getElementById('promptGenDatabaseSelect');
            if (!select) return;

            select.innerHTML = '<option value="">-- Select Database --</option>';
            
            // Add localhost databases (if connected)
            if (isLocalhostConnected) {
                const localhostDbs = getLocalhostDatabases();
                if (localhostDbs.length > 0) {
                    const optgroup = document.createElement('optgroup');
                    optgroup.label = '🖥️ LOCALHOST (LARAGON)';
                    
                    localhostDbs.forEach(dbName => {
                        const option = document.createElement('option');
                        option.value = `localhost_${dbName}`;
                        option.textContent = `🖥️ ${dbName} (Localhost)`;
                        option.style.background = 'rgba(0, 0, 0, 0.8)';
                        option.style.color = '#93c5fd';
                        optgroup.appendChild(option);
                    });
                    
                    select.appendChild(optgroup);
                }
            }
            
            // Add Hostinger connections (if connected)
            if (isHostingerConnected) {
                const allConnections = getHostingerConnections();
                
                if (allConnections.length > 0) {
                    const optgroup = document.createElement('optgroup');
                    optgroup.label = '🌐 HOSTINGER';
                    
                    allConnections.forEach(conn => {
                        const option = document.createElement('option');
                        option.value = `conn_${conn.id}`;  // ✅ FIX: Add conn_ prefix for getConnectionById()
                        option.textContent = `🌐 ${conn.name} (${conn.dbName})`;
                        option.style.background = 'rgba(0, 0, 0, 0.8)';
                        option.style.color = '#fef3c7';
                        optgroup.appendChild(option);
                    });
                    
                    select.appendChild(optgroup);
                }
            }
        }

        // Load tables for prompt generator
        async function loadPromptGenTables() {
            const select = document.getElementById('promptGenDatabaseSelect');
            const dbId = select ? select.value : null;

            console.log('Loading prompt gen tables for:', dbId);

            if (!dbId) {
                document.getElementById('promptGenTablesSection').style.display = 'none';
                document.getElementById('promptGenInputSection').style.display = 'none';
                document.getElementById('promptGenOutputSection').style.display = 'none';
                selectedPromptGenDatabaseId = null;
                promptGenTables = [];
                selectedPromptGenTables.clear();
                return;
            }

            selectedPromptGenDatabaseId = dbId;
            selectedPromptGenTables.clear();

            // Get connection (supports both Localhost and Hostinger)
            const conn = getConnectionById(dbId);

            if (!conn) {
                console.error('Connection not found:', dbId);
                return;
            }

            document.getElementById('promptGenDbName').textContent = `Database: ${conn.name}`;
            document.getElementById('promptGenTablesSection').style.display = 'block';
            document.getElementById('promptGenInputSection').style.display = 'block';

            // Show loading
            document.getElementById('promptGenTablesContainer').innerHTML = `
                <div style="width: 100%; text-align: center; padding: 40px 20px; color: rgba(254, 243, 199, 0.5);">
                    <div style="font-size: 48px; margin-bottom: 15px;">⏳</div>
                    <p style="font-size: 14px;">Loading tables...</p>
                </div>
            `;

            try {
                const result = await apiRequest('list_tables', {
                    db_host: conn.host,
                    db_name: conn.dbName,
                    db_user: conn.username,
                    db_pass: conn.password,
                    db_port: conn.port || '3306'
                });

                if (result.success && result.tables) {
                    promptGenTables = result.tables;
                    
                    // Load row counts
                    await loadTableRowCounts(conn, promptGenTables, promptGenTablesRowCount);
                    
                    renderPromptGenTables();
                    updatePromptGenCounts();
                } else {
                    document.getElementById('promptGenTablesContainer').innerHTML = `
                        <div style="width: 100%; text-align: center; padding: 40px 20px; color: rgba(254, 243, 199, 0.5);">
                            <div style="font-size: 48px; margin-bottom: 15px;">📭</div>
                            <p style="font-size: 14px;">No tables found in this database</p>
                        </div>
                    `;
                    promptGenTables = [];
                }
            } catch (error) {
                console.error('Error loading prompt gen tables:', error);
                document.getElementById('promptGenTablesContainer').innerHTML = `
                    <div style="width: 100%; text-align: center; padding: 40px 20px; color: rgba(254, 243, 199, 0.5);">
                        <div style="font-size: 48px; margin-bottom: 15px;">❌</div>
                        <p style="font-size: 14px;">Error loading tables</p>
                    </div>
                `;
                promptGenTables = [];
            }
        }

        // Render prompt generator tables
        function renderPromptGenTables() {
            const container = document.getElementById('promptGenTablesContainer');

            if (!container) {
                console.error('Prompt gen tables container not found!');
                return;
            }

            if (promptGenTables.length === 0) {
                container.innerHTML = `
                    <div style="width: 100%; text-align: center; padding: 40px 20px; color: rgba(254, 243, 199, 0.5);">
                        <div style="font-size: 48px; margin-bottom: 15px;">📭</div>
                        <p style="font-size: 14px;">No tables found in this database</p>
                    </div>
                `;
                return;
            }

            const html = promptGenTables.map(tableName => {
                const emoji = getTableEmoji(tableName);
                const isSelected = selectedPromptGenTables.has(tableName);
                const rowCount = promptGenTablesRowCount[tableName] !== undefined ? promptGenTablesRowCount[tableName] : '...';

                return `
                    <div class="migration-table-box ${isSelected ? 'selected' : ''}" 
                         data-table="${tableName}"
                         onclick="togglePromptGenTable('${tableName}')"
                         style="cursor: pointer; position: relative; display: grid; grid-template-rows: auto auto; gap: 10px; padding: 12px; border-color: rgba(139, 92, 246, 0.4); background: linear-gradient(135deg, rgba(139, 92, 246, 0.1) 0%, rgba(139, 92, 246, 0.05) 100%);">
                        
                        <span class="migration-check-icon" style="background: #8b5cf6;">✓</span>
                        
                        <!-- Row 1: Row Count + Emoji + Table Name -->
                        <div style="display: grid; grid-template-columns: auto auto 1fr; gap: 8px; align-items: center;">
                            <!-- Row Count Badge -->
                            <div style="background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%); padding: 8px 10px; border-radius: 8px; font-size: 10px; font-weight: bold; color: white; border: 1px solid rgba(139, 92, 246, 0.6); box-shadow: 0 2px 6px rgba(139, 92, 246, 0.3); text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 42px;">
                                <div style="font-size: 12px; margin-bottom: 2px;">📊</div>
                                <div style="font-size: 11px; font-weight: 700;">${typeof rowCount === 'number' ? rowCount.toLocaleString() : rowCount}</div>
                            </div>
                            
                            <!-- Table Emoji -->
                            <div style="font-size: 36px; display: flex; align-items: center; justify-content: center; min-height: 42px;">
                                ${emoji}
                            </div>
                            
                            <!-- Table Name -->
                            <div style="font-size: 16px; font-weight: 700; color: #a78bfa; padding: 8px 12px; border-radius: 6px; user-select: none; text-align: left;">
                                ${tableName}
                            </div>
                        </div>
                        
                        <!-- Row 2: Selection Indicator -->
                        <div style="text-align: center; font-size: 11px; color: rgba(167, 139, 250, 0.8); padding: 5px; background: rgba(139, 92, 246, 0.1); border-radius: 4px;">
                            ${isSelected ? '✅ Selected for prompt' : 'Click to select'}
                        </div>
                    </div>
                `;
            }).join('');

            container.innerHTML = html;
        }

        // Toggle prompt gen table selection
        function togglePromptGenTable(tableName) {
            if (selectedPromptGenTables.has(tableName)) {
                selectedPromptGenTables.delete(tableName);
            } else {
                selectedPromptGenTables.add(tableName);
            }
            renderPromptGenTables();
            updatePromptGenCounts();
        }

        // Select all prompt gen tables
        function selectAllPromptGenTables() {
            selectedPromptGenTables = new Set(promptGenTables);
            renderPromptGenTables();
            updatePromptGenCounts();
        }

        // Deselect all prompt gen tables
        function deselectAllPromptGenTables() {
            selectedPromptGenTables.clear();
            renderPromptGenTables();
            updatePromptGenCounts();
        }

        // Refresh prompt gen tables
        function refreshPromptGenTables() {
            selectedPromptGenTables.clear();
            loadPromptGenTables();
        }

        // Update prompt gen counts
        function updatePromptGenCounts() {
            document.getElementById('promptGenSelectedCount').textContent = 
                `${selectedPromptGenTables.size} table${selectedPromptGenTables.size !== 1 ? 's' : ''} selected`;
            document.getElementById('promptGenTotalCount').textContent = 
                `${promptGenTables.length} table${promptGenTables.length !== 1 ? 's' : ''} total`;
        }

        // Generate AI Prompt
        async function generateAIPrompt() {
            if (!selectedPromptGenDatabaseId) {
                alert('❌ Please select a database first!');
                return;
            }

            if (selectedPromptGenTables.size === 0) {
                alert('❌ Please select at least one table!');
                return;
            }

            const customPrompt = document.getElementById('promptGenCustomInput').value.trim();
            
            if (!customPrompt) {
                alert('❌ Please describe what you want to build!');
                return;
            }

            // Get connection (supports both Localhost and Hostinger)
            const conn = getConnectionById(selectedPromptGenDatabaseId);

            if (!conn) {
                alert('❌ Connection not found');
                return;
            }

            // Show loading
            const loadingDiv = document.createElement('div');
            loadingDiv.style.cssText = 'position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: linear-gradient(135deg, rgba(139, 92, 246, 0.95) 0%, rgba(109, 40, 217, 0.95) 100%); color: white; padding: 40px 60px; border-radius: 15px; border: 3px solid #a78bfa; z-index: 10000; text-align: center; box-shadow: 0 0 50px rgba(139, 92, 246, 0.7);';
            loadingDiv.innerHTML = `
                <div style="font-size: 56px; margin-bottom: 20px; animation: spin 2s linear infinite;">✨</div>
                <div style="font-size: 20px; margin-bottom: 12px; font-weight: bold;">Generating AI Prompt...</div>
                <div id="promptGenProgress" style="font-size: 14px; opacity: 0.9; margin-top: 10px;">Analyzing database structure...</div>
                <style>@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }</style>
            `;
            document.body.appendChild(loadingDiv);

            try {
                let fullPrompt = `AI APPLICATION DEVELOPMENT PROMPT
${'='.repeat(80)}

USER REQUEST:
${customPrompt}

${'='.repeat(80)}

DATABASE CONNECTION INFORMATION:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Connection Name: ${conn.name}
Server Type: ${conn.isLocalhost ? 'Localhost (Laragon)' : (conn.type === 'vps' ? 'VPS Server' : 'Shared Hosting')}
Host: ${conn.host}
Port: ${conn.port || '3306'}
Database Name: ${conn.dbName}
Username: ${conn.username}
Password: ${conn.password}
Charset: utf8mb4
Collation: utf8mb4_unicode_ci

CONNECTION CODE (PHP - PDO):
\`\`\`php
$host = '${conn.host}';
$port = '${conn.port || '3306'}';
$dbname = '${conn.dbName}';
$username = '${conn.username}';
$password = '${conn.password}';

$dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
$pdo = new PDO($dsn, $username, $password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
\`\`\`

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

SELECTED TABLES (${selectedPromptGenTables.size}):
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

`;

                const selectedTablesArray = Array.from(selectedPromptGenTables);
                let totalColumns = 0;
                let totalRecords = 0;

                // Fetch details for each selected table
                for (let i = 0; i < selectedTablesArray.length; i++) {
                    const tableName = selectedTablesArray[i];
                    
                    const progressEl = document.getElementById('promptGenProgress');
                    if (progressEl) {
                        progressEl.textContent = `Analyzing table ${i + 1}/${selectedTablesArray.length}: ${tableName}`;
                    }

                    // Get structure
                    const structResult = await apiRequest('get_table_structure', {
                        db_host: conn.host,
                        db_name: conn.dbName,
                        db_user: conn.username,
                        db_pass: conn.password,
                        db_port: conn.port || '3306',
                        table_name: tableName
                    });

                    // Get data
                    const dataResult = await apiRequest('get_table_data', {
                        db_host: conn.host,
                        db_name: conn.dbName,
                        db_user: conn.username,
                        db_pass: conn.password,
                        db_port: conn.port || '3306',
                        table_name: tableName,
                        page: 1,
                        limit: 3
                    });

                    if (structResult.success && dataResult.success) {
                        const rowCount = dataResult.pagination.total_rows;
                        totalRecords += rowCount;
                        totalColumns += structResult.columns.length;

                        fullPrompt += `
${i + 1}. TABLE: ${tableName}
${'-'.repeat(80)}

Total Records: ${rowCount.toLocaleString()} rows
Total Columns: ${structResult.columns.length}
Engine: ${dataResult.table_info.engine}
Collation: ${dataResult.table_info.collation}
Data Size: ${formatBytes(dataResult.table_info.data_length)}

COLUMN STRUCTURE:
`;

                        structResult.columns.forEach((col, idx) => {
                            const keyInfo = col.Key ? 
                                (col.Key === 'PRI' ? ' [PRIMARY KEY]' : 
                                 col.Key === 'UNI' ? ' [UNIQUE]' : 
                                 col.Key === 'MUL' ? ' [INDEX]' : '') : '';
                            const nullInfo = col.Null === 'YES' ? ' NULL' : ' NOT NULL';
                            const defaultInfo = col.Default !== null ? ` DEFAULT '${col.Default}'` : '';
                            const extraInfo = col.Extra ? ` ${col.Extra.toUpperCase()}` : '';
                            
                            fullPrompt += `   ${idx + 1}. ${col.Field} (${col.Type})${nullInfo}${defaultInfo}${extraInfo}${keyInfo}\n`;
                        });

                        // Add sample data if available
                        if (dataResult.data.length > 0) {
                            fullPrompt += `\nSAMPLE DATA (First ${dataResult.data.length} rows):\n`;
                            
                            dataResult.data.forEach((row, rowIdx) => {
                                fullPrompt += `   Row ${rowIdx + 1}: `;
                                const rowData = structResult.columns.map(col => {
                                    const val = row[col.Field];
                                    return `${col.Field}=${val === null ? 'NULL' : typeof val === 'string' && val.length > 30 ? val.substring(0, 30) + '...' : val}`;
                                }).join(', ');
                                fullPrompt += rowData + '\n';
                            });
                        }

                        fullPrompt += '\n';
                    }
                }

                fullPrompt += `
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

DATABASE SUMMARY:
- Total Selected Tables: ${selectedPromptGenTables.size}
- Total Columns Across Tables: ${totalColumns}
- Total Records Across Tables: ${totalRecords.toLocaleString()}
- Database Type: MySQL/MariaDB
- Character Set: utf8mb4

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
`;

                // Add Application Architecture instructions if selected
                const appTypeSingle = document.getElementById('appTypeSingle').checked;
                const appTypeDouble = document.getElementById('appTypeDouble').checked;

                if (appTypeSingle) {
                    const phpFilename = document.getElementById('singlePhpFilename').value.trim() || 'app.php';
                    
                    fullPrompt += `
APPLICATION ARCHITECTURE:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

📄 SINGLE-PHP PAGE (Direct Database Connection)

REQUIREMENT:
Generate ONE complete PHP file that connects DIRECTLY to the database (no API layer).

FILENAME: ${phpFilename}

STRUCTURE REQUIREMENTS:
1. Include database connection at the top using the credentials provided above
2. All database operations (SELECT, INSERT, UPDATE, DELETE) should be in the SAME file
3. Use PDO with prepared statements for security
4. Include HTML output mixed with PHP (traditional PHP approach)
5. Handle all CRUD operations for the selected tables: ${selectedTablesArray.join(', ')}
6. Add proper error handling and validation
7. Create a user-friendly interface with forms and data display
8. No separate API file needed - everything in ${phpFilename}

EXAMPLE STRUCTURE:
- Database connection section (top of file)
- HTML DOCTYPE and head section
- PHP logic for handling form submissions
- HTML body with forms and data tables
- Display data from selected tables
- CRUD operations for each table

This is a SINGLE-PAGE application - all logic and display in ONE file.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
`;
                } else if (appTypeDouble) {
                    const backendFilename = document.getElementById('doubleApiBackendFilename').value.trim() || 'api.php';
                    const frontendFilename = document.getElementById('doubleApiFrontendFilename').value.trim() || 'index.html';
                    
                    fullPrompt += `
APPLICATION ARCHITECTURE:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

🔄 DOUBLE-PAGE API ARCHITECTURE (Backend + Frontend Separation)

REQUIREMENT:
Generate TWO separate files with API-based communication.

FILE 1 - BACKEND API: ${backendFilename}
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

BACKEND REQUIREMENTS:
1. Pure PHP API file (no HTML output)
2. Connect to database using credentials provided above
3. Handle API requests via POST/GET with 'action' parameter
4. Respond with JSON format
5. Include CORS headers for frontend communication
6. Implement actions for ALL selected tables: ${selectedTablesArray.join(', ')}

REQUIRED API ACTIONS (for each table):
- list_[tablename] - Get all records with pagination
- get_[tablename] - Get single record by ID
- create_[tablename] - Insert new record
- update_[tablename] - Update existing record
- delete_[tablename] - Delete record
- search_[tablename] - Search records

EXAMPLE ACTIONS:
${selectedTablesArray.slice(0, 2).map(t => `- action=list_${t}, action=create_${t}, action=update_${t}, action=delete_${t}`).join('\n')}

SECURITY:
- Use PDO prepared statements
- Validate all inputs
- Send proper HTTP response codes
- Include error handling

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

FILE 2 - FRONTEND: ${frontendFilename}
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

FRONTEND REQUIREMENTS:
1. Single HTML file with vanilla JavaScript (no frameworks)
2. Modern, responsive UI design
3. Use Fetch API to communicate with ${backendFilename}
4. Display data in tables/cards for all selected tables
5. Include forms for CRUD operations
6. Add loading indicators and error messages
7. Handle API responses properly
8. Use async/await for API calls

API COMMUNICATION EXAMPLE:
\`\`\`javascript
const API_URL = '${backendFilename}';

async function apiRequest(action, data = {}) {
    const formData = new FormData();
    formData.append('action', action);
    for (const key in data) {
        formData.append(key, data[key]);
    }
    
    const response = await fetch(API_URL, {
        method: 'POST',
        body: formData
    });
    
    return await response.json();
}
\`\`\`

UI SECTIONS NEEDED (for each table):
- List view with pagination
- Add new record form
- Edit record form
- Delete confirmation
- Search functionality

STYLING:
- Use modern CSS (flexbox/grid)
- Responsive design
- Professional color scheme
- Smooth animations and transitions
- Similar to this Database Control Panel design

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

IMPORTANT: Generate BOTH files (${backendFilename} AND ${frontendFilename}) as separate, complete files.
The frontend should call the backend API for all database operations.
This is a TWO-PAGE architecture with API separation.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
`;
                }

                fullPrompt += `
AI INSTRUCTIONS:

Based on the USER REQUEST above and using the database information provided:

1. Use the exact database credentials shown above to establish connection
2. Work with the selected tables: ${selectedTablesArray.join(', ')}
3. Respect the column structures, data types, and constraints shown
4. Use the sample data to understand data patterns and relationships
5. Generate clean, production-ready code with proper error handling
6. Include comments and documentation
7. Follow best practices for security (prepared statements, input validation)
8. Implement the features described in the USER REQUEST
`;

                // Add architecture-specific instructions
                if (appTypeSingle) {
                    const phpFilename = document.getElementById('singlePhpFilename').value.trim() || 'app.php';
                    fullPrompt += `9. Generate as SINGLE PHP file: ${phpFilename} (all logic in one file)\n`;
                    fullPrompt += `10. Use direct database connection (no API layer)\n`;
                } else if (appTypeDouble) {
                    const backendFilename = document.getElementById('doubleApiBackendFilename').value.trim() || 'api.php';
                    const frontendFilename = document.getElementById('doubleApiFrontendFilename').value.trim() || 'index.html';
                    fullPrompt += `9. Generate TWO separate files:\n`;
                    fullPrompt += `   - Backend API: ${backendFilename} (handles all database operations)\n`;
                    fullPrompt += `   - Frontend: ${frontendFilename} (vanilla JS + HTML, communicates via API)\n`;
                    fullPrompt += `10. Use API architecture with JSON responses\n`;
                    fullPrompt += `11. Frontend should use Fetch API to call backend\n`;
                }

                fullPrompt += `
IMPORTANT NOTES:
- Primary keys are marked with [PRIMARY KEY]
- Auto-increment columns should not be included in INSERT operations
- Respect NOT NULL constraints
- Use utf8mb4 charset for proper emoji and international character support
- Connection parameters: host=${conn.host}, port=${conn.port || '3306'}, database=${conn.dbName}
`;

                if (appTypeSingle) {
                    fullPrompt += `- Create complete working application in ONE PHP file\n`;
                } else if (appTypeDouble) {
                    fullPrompt += `- Create complete working application in TWO files (backend + frontend)\n`;
                    fullPrompt += `- Backend returns JSON, Frontend displays UI\n`;
                }

                fullPrompt += `
Now, please implement the application according to the USER REQUEST while integrating with this database structure`;

                if (appTypeSingle || appTypeDouble) {
                    fullPrompt += ` and following the specified architecture`;
                }

                fullPrompt += `.

${'='.repeat(80)}
END OF PROMPT
${'='.repeat(80)}
`;

                // Display output
                document.getElementById('promptGenOutput').value = fullPrompt;
                document.getElementById('promptGenOutputSection').style.display = 'block';

                // Show stats
                const appTypeSingleChecked = document.getElementById('appTypeSingle').checked;
                const appTypeDoubleChecked = document.getElementById('appTypeDouble').checked;
                
                let archTypeLabel = 'Generic';
                let archTypeIcon = '📝';
                let archTypeColor = 'rgba(139, 92, 246, 0.1)';
                
                if (appTypeSingleChecked) {
                    archTypeLabel = 'Single-PHP';
                    archTypeIcon = '📄';
                    archTypeColor = 'rgba(59, 130, 246, 0.15)';
                } else if (appTypeDoubleChecked) {
                    archTypeLabel = 'Double-API';
                    archTypeIcon = '🔄';
                    archTypeColor = 'rgba(34, 197, 94, 0.15)';
                }
                
                const statsHtml = `
                    <div class="stat-box" style="background: ${archTypeColor}; border-color: rgba(139, 92, 246, 0.3);">
                        <div class="stat-label">🏗️ Architecture</div>
                        <div class="stat-value" style="font-size: 14px;">${archTypeIcon} ${archTypeLabel}</div>
                    </div>
                    <div class="stat-box" style="background: rgba(139, 92, 246, 0.1); border-color: rgba(139, 92, 246, 0.3);">
                        <div class="stat-label">📊 Tables</div>
                        <div class="stat-value">${selectedPromptGenTables.size}</div>
                    </div>
                    <div class="stat-box" style="background: rgba(139, 92, 246, 0.1); border-color: rgba(139, 92, 246, 0.3);">
                        <div class="stat-label">📋 Columns</div>
                        <div class="stat-value">${totalColumns}</div>
                    </div>
                    <div class="stat-box" style="background: rgba(139, 92, 246, 0.1); border-color: rgba(139, 92, 246, 0.3);">
                        <div class="stat-label">📈 Records</div>
                        <div class="stat-value">${totalRecords.toLocaleString()}</div>
                    </div>
                    <div class="stat-box" style="background: rgba(139, 92, 246, 0.1); border-color: rgba(139, 92, 246, 0.3);">
                        <div class="stat-label">📏 Prompt Size</div>
                        <div class="stat-value">${formatBytes(fullPrompt.length)}</div>
                    </div>
                `;
                document.getElementById('promptGenStats').innerHTML = statsHtml;

                // Remove loading
                document.body.removeChild(loadingDiv);

                // Clear edit mode (fresh generation)
                window.currentEditingPromptId = null;
                document.getElementById('saveEditBtn').style.display = 'none';
                document.getElementById('editingIndicator').style.display = 'none';

                // Show success message
                let successMsg = `✅ AI Prompt Generated!\n${selectedPromptGenTables.size} tables, ${totalColumns} columns analyzed`;
                if (appTypeSingleChecked) {
                    successMsg += `\n📄 Architecture: Single-PHP`;
                } else if (appTypeDoubleChecked) {
                    successMsg += `\n🔄 Architecture: Double-API`;
                }
                showCustomToast(successMsg, 'success', 3000);

                // Scroll to output
                setTimeout(() => {
                    document.getElementById('promptGenOutputSection').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }, 100);

            } catch (error) {
                console.error('❌ Prompt generation error:', error);
                
                if (document.body.contains(loadingDiv)) {
                    document.body.removeChild(loadingDiv);
                }
                
                alert(`❌ Prompt Generation Failed!\n\n${error.message}`);
            }
        }

        // Copy generated prompt
        async function copyGeneratedPrompt() {
            const promptText = document.getElementById('promptGenOutput').value;
            
            try {
                await navigator.clipboard.writeText(promptText);
                showCustomToast('✅ AI Prompt copied to clipboard successfully!', 'success', 3000);
                
                // Visual feedback
                const btn = event.target.closest('button');
                const originalHtml = btn.innerHTML;
                btn.innerHTML = '<span>✅</span> Copied!';
                btn.style.background = '#22c55e';
                
                setTimeout(() => {
                    btn.innerHTML = originalHtml;
                    btn.style.background = '';
                }, 2000);
            } catch (err) {
                const textarea = document.getElementById('promptGenOutput');
                textarea.select();
                document.execCommand('copy');
                showCustomToast('✅ AI Prompt copied to clipboard!', 'success', 3000);
            }
        }

        // Download generated prompt
        function downloadGeneratedPrompt() {
            const promptText = document.getElementById('promptGenOutput').value;
            const timestamp = new Date().toISOString().replace(/[:.]/g, '-').slice(0, -5);
            const filename = `ai_prompt_${timestamp}.txt`;
            
            const blob = new Blob([promptText], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            
            showCustomToast(`✅ Prompt downloaded: ${filename}`, 'success', 3000);
        }

        // Clear prompt generator
        function clearPromptGenerator() {
            document.getElementById('promptGenDatabaseSelect').value = '';
            document.getElementById('promptGenCustomInput').value = '';
            document.getElementById('promptGenTablesSection').style.display = 'none';
            document.getElementById('promptGenInputSection').style.display = 'none';
            document.getElementById('promptGenOutputSection').style.display = 'none';
            
            // Clear app type selection
            document.getElementById('appTypeSingle').checked = false;
            document.getElementById('appTypeDouble').checked = false;
            document.getElementById('singlePhpFilenameSection').style.display = 'none';
            document.getElementById('doubleApiFilenamesSection').style.display = 'none';
            resetAppTypeStyles();
            
            // Clear edit mode
            window.currentEditingPromptId = null;
            document.getElementById('saveEditBtn').style.display = 'none';
            document.getElementById('editingIndicator').style.display = 'none';
            
            selectedPromptGenDatabaseId = null;
            promptGenTables = [];
            selectedPromptGenTables.clear();
            updatePromptGenCounts();
        }

        // ========================================
        // SAVED PROMPTS MANAGEMENT (Simple & Clear)
        // ========================================

        const SAVED_PROMPTS_KEY = 'saved_ai_prompts';
        const PROMPTS_EXPORT_PATH_KEY = 'prompts_export_path';

        // Get saved prompts from localStorage
        function getSavedPrompts() {
            const saved = localStorage.getItem(SAVED_PROMPTS_KEY);
            return saved ? JSON.parse(saved) : [];
        }

        // Save prompts to localStorage
        function savePromptsToStorage(prompts) {
            localStorage.setItem(SAVED_PROMPTS_KEY, JSON.stringify(prompts));
        }

        // Save as NEW prompt (always creates new record)
        function saveAsNewPrompt() {
            const promptText = document.getElementById('promptGenOutput').value.trim();
            
            if (!promptText) {
                alert('❌ No prompt to save!');
                return;
            }

            // Ask for name - use getConnectionById for Localhost and Hostinger support
            const conn = selectedPromptGenDatabaseId ? getConnectionById(selectedPromptGenDatabaseId) : null;
            const dbName = conn ? conn.dbName : 'DB';
            const architecture = document.getElementById('appTypeSingle').checked ? 'PHP' :
                               document.getElementById('appTypeDouble').checked ? 'API' : 'Gen';
            const date = new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric' }).replace(',', '');
            
            const systematicName = `${dbName}_${selectedPromptGenTables.size}Tables_${architecture}_${date}`;
            const promptName = prompt(`💾 Save as New Prompt\n\nEnter name for this prompt:\n\n💡 Suggested: ${systematicName}`, systematicName);
            
            if (!promptName) return;

            const promptData = {
                id: Date.now().toString(),
                name: promptName.trim(),
                prompt: promptText,
                database: dbName,
                databaseId: selectedPromptGenDatabaseId,
                tablesCount: selectedPromptGenTables.size,
                tables: Array.from(selectedPromptGenTables),
                architecture: document.getElementById('appTypeSingle').checked ? 'Single-PHP' :
                             document.getElementById('appTypeDouble').checked ? 'Double-API' : 'Generic',
                createdAt: new Date().toISOString(),
                size: promptText.length
            };

            const prompts = getSavedPrompts();
            prompts.unshift(promptData);
            savePromptsToStorage(prompts);

            showCustomToast(`✅ Prompt saved!\n"${promptData.name}"`, 'success', 3000);
            loadSavedPromptsTable();
        }

        // Save edited prompt (updates existing record)
        function saveEditedPrompt() {
            if (!window.currentEditingPromptId) {
                alert('❌ No prompt in edit mode!');
                return;
            }

            const promptText = document.getElementById('promptGenOutput').value.trim();
            
            if (!promptText) {
                alert('❌ Prompt cannot be empty!');
                return;
            }

            const prompts = getSavedPrompts();
            const promptIndex = prompts.findIndex(p => p.id === window.currentEditingPromptId);
            
            if (promptIndex === -1) {
                alert('❌ Original prompt not found!');
                return;
            }

            // Confirm
            if (!confirm(`✏️ Save Edit\n\n"${prompts[promptIndex].name}"\n\nThis will update the saved prompt.\n\nContinue?`)) {
                return;
            }

            // Update
            prompts[promptIndex].prompt = promptText;
            prompts[promptIndex].size = promptText.length;
            prompts[promptIndex].updatedAt = new Date().toISOString();

            savePromptsToStorage(prompts);
            showCustomToast(`✅ Prompt updated!\n"${prompts[promptIndex].name}"`, 'success', 3000);
            loadSavedPromptsTable();
        }

        // Load saved prompts table
        function loadSavedPromptsTable() {
            const tableEl = document.getElementById('savedPromptsTable');
            if (!tableEl) return;

            const prompts = getSavedPrompts();

            if (prompts.length === 0) {
                tableEl.innerHTML = `
                    <div style="text-align: center; padding: 40px 20px; background: rgba(139, 92, 246, 0.05); border: 2px dashed rgba(139, 92, 246, 0.3); border-radius: 10px;">
                        <div style="font-size: 48px; margin-bottom: 15px;">📭</div>
                        <div style="font-size: 16px; color: #a78bfa; margin-bottom: 8px; font-weight: bold;">No Saved Prompts Yet</div>
                        <div style="font-size: 14px; color: rgba(254, 243, 199, 0.7);">
                            Generate a prompt and click <strong style="color: #fbbf24;">💾 Save as New</strong>
                        </div>
                    </div>
                `;
                return;
            }

            let html = `
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: rgba(139, 92, 246, 0.1); border-bottom: 2px solid rgba(139, 92, 246, 0.3);">
                                <th style="padding: 12px; text-align: left; color: #a78bfa;">Name</th>
                                <th style="padding: 12px; text-align: left; color: #a78bfa;">Database</th>
                                <th style="padding: 12px; text-align: center; color: #a78bfa;">Tables</th>
                                <th style="padding: 12px; text-align: center; color: #a78bfa;">Type</th>
                                <th style="padding: 12px; text-align: center; color: #a78bfa;">Size</th>
                                <th style="padding: 12px; text-align: left; color: #a78bfa;">Date</th>
                                <th style="padding: 12px; text-align: center; color: #a78bfa;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>`;

            prompts.forEach(p => {
                const date = new Date(p.createdAt).toLocaleString();
                const archIcon = p.architecture === 'Single-PHP' ? '📄' : p.architecture === 'Double-API' ? '🔄' : '📝';
                
                html += `
                    <tr style="border-bottom: 1px solid rgba(139, 92, 246, 0.1);" onmouseover="this.style.background='rgba(139, 92, 246, 0.1)'" onmouseout="this.style.background='transparent'">
                        <td style="padding: 12px; color: #fef3c7;"><strong>${p.name}</strong></td>
                        <td style="padding: 12px; color: rgba(254, 243, 199, 0.7); font-family: monospace; font-size: 13px;">${p.database}</td>
                        <td style="padding: 12px; text-align: center; color: #a78bfa; font-weight: bold;">${p.tablesCount}</td>
                        <td style="padding: 12px; text-align: center;">
                            <span style="background: rgba(139, 92, 246, 0.2); padding: 4px 10px; border-radius: 6px; font-size: 12px; color: #a78bfa;">
                                ${archIcon} ${p.architecture}
                            </span>
                        </td>
                        <td style="padding: 12px; text-align: center; color: rgba(254, 243, 199, 0.7); font-size: 13px;">${formatBytes(p.size)}</td>
                        <td style="padding: 12px; color: rgba(254, 243, 199, 0.7); font-size: 13px;">${date}</td>
                        <td style="padding: 12px; text-align: center;">
                            <div style="display: flex; gap: 5px; justify-content: center;">
                                <button class="btn btn-primary" style="padding: 6px 12px; font-size: 12px; background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);" onclick="editPrompt('${p.id}')">
                                    ✏️ Edit
                                </button>
                                <button class="btn btn-primary" style="padding: 6px 12px; font-size: 12px; background: linear-gradient(135deg, #22c55e 0%, #15803d 100%);" onclick="copyPromptById('${p.id}')">
                                    📋
                                </button>
                                <button class="btn btn-danger" style="padding: 6px 12px; font-size: 12px;" onclick="deletePrompt('${p.id}')">
                                    🗑️
                                </button>
                            </div>
                        </td>
                    </tr>`;
            });

            html += `
                        </tbody>
                    </table>
                </div>
                <div style="margin-top: 15px; padding: 10px; background: rgba(139, 92, 246, 0.05); border-radius: 6px; text-align: center; font-size: 13px; color: rgba(254, 243, 199, 0.7);">
                    📊 Total: <strong style="color: #a78bfa;">${prompts.length}</strong> saved prompt${prompts.length !== 1 ? 's' : ''}
                </div>
            `;

            tableEl.innerHTML = html;
        }

        // Edit prompt (load into generator)
        function editPrompt(promptId) {
            const prompts = getSavedPrompts();
            const p = prompts.find(pr => pr.id === promptId);
            
            if (!p) {
                alert('❌ Prompt not found!');
                return;
            }

            // Set edit mode
            window.currentEditingPromptId = promptId;

            // Show prompt in textarea
            document.getElementById('promptGenOutput').value = p.prompt;
            document.getElementById('promptGenOutputSection').style.display = 'block';

            // Show edit indicator
            document.getElementById('editingIndicator').style.display = 'block';
            document.getElementById('editingPromptName').textContent = p.name;

            // Show Save Edit button
            document.getElementById('saveEditBtn').style.display = 'inline-flex';

            // Scroll to prompt
            setTimeout(() => {
                document.getElementById('promptGenOutputSection').scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 100);

            showCustomToast(`✏️ Editing: "${p.name}"`, 'info', 2000);
        }

        // Copy prompt by ID
        async function copyPromptById(promptId) {
            const prompts = getSavedPrompts();
            const p = prompts.find(pr => pr.id === promptId);
            
            if (!p) {
                alert('❌ Prompt not found!');
                return;
            }

            try {
                await navigator.clipboard.writeText(p.prompt);
                showCustomToast(`✅ Copied!\n"${p.name}"`, 'success', 2000);
            } catch (err) {
                alert('❌ Copy failed!');
            }
        }

        // Delete prompt
        function deletePrompt(promptId) {
            const prompts = getSavedPrompts();
            const p = prompts.find(pr => pr.id === promptId);
            
            if (!p) {
                alert('❌ Prompt not found!');
                return;
            }

            if (!confirm(`🗑️ Delete Prompt\n\n"${p.name}"\n\nAre you sure?`)) {
                return;
            }

            const updated = prompts.filter(pr => pr.id !== promptId);
            savePromptsToStorage(updated);
            
            showCustomToast(`✅ Deleted!\n"${p.name}"`, 'success', 2000);
            loadSavedPromptsTable();

            // Clear edit mode if deleting current editing prompt
            if (window.currentEditingPromptId === promptId) {
                window.currentEditingPromptId = null;
                document.getElementById('saveEditBtn').style.display = 'none';
                document.getElementById('editingIndicator').style.display = 'none';
            }
        }

        // Clear all prompts
        function clearAllPrompts() {
            const prompts = getSavedPrompts();
            
            if (prompts.length === 0) {
                alert('ℹ️ No prompts to clear.');
                return;
            }

            if (!confirm(`🗑️ Clear All\n\nDelete ${prompts.length} prompt(s)?\n\nThis cannot be undone!`)) {
                return;
            }

            localStorage.removeItem(SAVED_PROMPTS_KEY);
            showCustomToast(`✅ All cleared! (${prompts.length} deleted)`, 'success', 3000);
            loadSavedPromptsTable();
        }

        // Export prompts
        async function exportSavedPrompts() {
            const prompts = getSavedPrompts();
            
            if (prompts.length === 0) {
                alert('❌ No prompts to export!');
                return;
            }

            const exportData = {
                exported_at: new Date().toLocaleString(),
                total: prompts.length,
                prompts: prompts
            };

            const timestamp = new Date().toISOString().replace(/[:.]/g, '-').slice(0, -5);
            const filename = `ai_prompts_backup_${timestamp}.json`;
            const jsonData = JSON.stringify(exportData, null, 2);
            const blob = new Blob([jsonData], { type: 'application/json' });

            if ('showSaveFilePicker' in window) {
                try {
                    const options = {
                        suggestedName: filename,
                        types: [{ description: 'JSON Files', accept: { 'application/json': ['.json'] } }]
                    };

                    const fileHandle = await window.showSaveFilePicker(options);
                    const writable = await fileHandle.createWritable();
                    await writable.write(blob);
                    await writable.close();

                    showCustomToast(`✅ Exported!\n📁 ${filename}\n📊 ${prompts.length} prompts`, 'success', 3000);
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        downloadFile(blob, filename);
                        showCustomToast(`✅ Exported!\n📁 ${filename}`, 'success', 3000);
                    }
                }
            } else {
                downloadFile(blob, filename);
                showCustomToast(`✅ Exported!\n📁 ${filename}`, 'success', 3000);
            }
        }

        // Import prompts
        async function importSavedPrompts() {
            if ('showOpenFilePicker' in window) {
                try {
                    const options = {
                        types: [{ description: 'JSON Files', accept: { 'application/json': ['.json'] } }],
                        multiple: false
                    };

                    const [fileHandle] = await window.showOpenFilePicker(options);
                    const file = await fileHandle.getFile();
                    await processPromptsImportFile(file);
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        alert(`❌ Error: ${error.message}`);
                    }
                }
            } else {
                const input = document.createElement('input');
                input.type = 'file';
                input.accept = '.json';
                input.onchange = async (e) => {
                    const file = e.target.files[0];
                    if (file) await processPromptsImportFile(file);
                };
                input.click();
            }
        }

        // Process import file for AI prompts
        async function processPromptsImportFile(file) {
            try {
                const text = await file.text();
                const data = JSON.parse(text);

                if (!data.prompts || !Array.isArray(data.prompts)) {
                    alert('❌ Invalid file format!');
                    return;
                }

                if (!confirm(`📥 Import ${data.prompts.length} prompts?\n\nFrom: ${file.name}\n\nThis will MERGE with existing prompts.`)) {
                    return;
                }

                let current = getSavedPrompts();
                let added = 0, updated = 0;

                data.prompts.forEach(imported => {
                    const existingIndex = current.findIndex(p => p.id === imported.id);
                    if (existingIndex !== -1) {
                        current[existingIndex] = imported;
                        updated++;
                    } else {
                        current.push(imported);
                        added++;
                    }
                });

                savePromptsToStorage(current);
                loadSavedPromptsTable();

                showCustomToast(`✅ Import complete!\n➕ ${added} added\n✏️ ${updated} updated`, 'success', 3000);
            } catch (error) {
                alert(`❌ Import failed: ${error.message}`);
            }
        }

        // Select app type (visual feedback)
        function selectAppType(type) {
            document.getElementById('appTypeSingle').checked = (type === 'single');
            document.getElementById('appTypeDouble').checked = (type === 'double');
            handleAppTypeChange();
        }

        // Handle app type change
        function handleAppTypeChange() {
            const singleSelected = document.getElementById('appTypeSingle').checked;
            const doubleSelected = document.getElementById('appTypeDouble').checked;
            
            // Show/hide filename inputs
            document.getElementById('singlePhpFilenameSection').style.display = singleSelected ? 'block' : 'none';
            document.getElementById('doubleApiFilenamesSection').style.display = doubleSelected ? 'block' : 'none';
            
            // Visual feedback on cards
            const singleCard = document.getElementById('appType_single');
            const doubleCard = document.getElementById('appType_double');
            
            if (singleSelected) {
                singleCard.style.borderColor = '#3b82f6';
                singleCard.style.background = 'rgba(59, 130, 246, 0.2)';
                singleCard.style.boxShadow = '0 0 20px rgba(59, 130, 246, 0.4)';
                
                doubleCard.style.borderColor = 'rgba(34, 197, 94, 0.3)';
                doubleCard.style.background = 'rgba(34, 197, 94, 0.1)';
                doubleCard.style.boxShadow = 'none';
            } else if (doubleSelected) {
                doubleCard.style.borderColor = '#22c55e';
                doubleCard.style.background = 'rgba(34, 197, 94, 0.2)';
                doubleCard.style.boxShadow = '0 0 20px rgba(34, 197, 94, 0.4)';
                
                singleCard.style.borderColor = 'rgba(59, 130, 246, 0.3)';
                singleCard.style.background = 'rgba(59, 130, 246, 0.1)';
                singleCard.style.boxShadow = 'none';
            } else {
                resetAppTypeStyles();
            }
        }

        // Reset app type styles
        function resetAppTypeStyles() {
            const singleCard = document.getElementById('appType_single');
            const doubleCard = document.getElementById('appType_double');
            
            singleCard.style.borderColor = 'rgba(59, 130, 246, 0.3)';
            singleCard.style.background = 'rgba(59, 130, 246, 0.1)';
            singleCard.style.boxShadow = 'none';
            
            doubleCard.style.borderColor = 'rgba(34, 197, 94, 0.3)';
            doubleCard.style.background = 'rgba(34, 197, 94, 0.1)';
            doubleCard.style.boxShadow = 'none';
        }

        // ========================================
        // DESTINATION DATABASE OPERATIONS (REVERSE FUNCTIONALITY)
        // Mirror of Source operations but applied to Destination
        // Drag direction: Destination → Source (opposite of Source → Destination)
        // ========================================

        // Inject random records into destination table
        async function injectRandomIntoDestinationTable(tableName) {
            if (!selectedDestinationId) {
                alert('❌ No destination database selected');
                return;
            }

            console.log('🎲 Injecting random data into DESTINATION table:', tableName);

            // Get connection info (supports both Localhost and Hostinger)
            const destConn = getConnectionById(selectedDestinationId);

            if (!destConn) {
                alert('❌ Destination connection not found');
                return;
            }

            // Show loading toast
            const loadingToast = document.createElement('div');
            loadingToast.id = 'injectLoadingToastDest';
            loadingToast.style.cssText = 'position: fixed; bottom: 30px; right: 30px; background: linear-gradient(135deg, #22c55e 0%, #15803d 100%); color: white; padding: 15px 25px; border-radius: 10px; border: 2px solid #86efac; z-index: 10000; box-shadow: 0 5px 20px rgba(34, 197, 94, 0.5);';
            loadingToast.innerHTML = `
                <div style="font-size: 14px; font-weight: bold;">🎲 Generating random data...</div>
                <div style="font-size: 12px; margin-top: 5px; opacity: 0.9;">📥 Destination: ${tableName}</div>
            `;
            document.body.appendChild(loadingToast);

            try {
                // Get table structure
                const structResult = await apiRequest('get_table_structure', {
                    db_host: destConn.host,
                    db_name: destConn.dbName,
                    db_user: destConn.username,
                    db_pass: destConn.password,
                    db_port: destConn.port || '3306',
                    table_name: tableName
                });

                if (!structResult.success) {
                    throw new Error(structResult.message || 'Failed to get table structure');
                }

                // Generate random records
                const randomRecords = generateRandomRecords(structResult.columns, 10);

                if (randomRecords.length === 0) {
                    throw new Error('Could not generate records (table has only auto-increment columns?)');
                }

                // Insert records
                const result = await apiRequest('insert_random_data', {
                    db_host: destConn.host,
                    db_name: destConn.dbName,
                    db_user: destConn.username,
                    db_pass: destConn.password,
                    db_port: destConn.port || '3306',
                    table_name: tableName,
                    records_data: JSON.stringify(randomRecords),
                    record_count: 10
                });

                document.body.removeChild(loadingToast);

                if (result.success) {
                    showCustomToast(`✅ ${result.message} in Destination: ${tableName}`, 'success', 3000);
                    // Refresh destination tables to update row count
                    await loadDestinationTables();
                    console.log('✅ Random data injected successfully into destination');
                } else {
                    alert(`❌ Failed to inject data:\n\n${result.message}`);
                }

            } catch (error) {
                if (document.getElementById('injectLoadingToastDest')) {
                    document.body.removeChild(loadingToast);
                }
                console.error('❌ Inject error:', error);
                alert(`❌ Inject Failed!\n\n${error.message}`);
            }
        }

        // Duplicate destination table in same destination database
        async function duplicateDestinationTable(tableName) {
            if (!selectedDestinationId) {
                alert('❌ No destination database selected');
                return;
            }

            console.log('=== DUPLICATE DESTINATION TABLE ===');
            console.log('Original table:', tableName);

            // Get connection info (supports both Localhost and Hostinger)
            const destConn = getConnectionById(selectedDestinationId);

            if (!destConn) {
                alert('❌ Destination connection not found');
                return;
            }

            // Prompt for new table name
            const newName = prompt(`📋 Duplicate Table: ${tableName}\n\n📥 In Destination Database\n\nEnter name for the copy:`, `${tableName}_copy`);
            
            if (!newName) {
                console.log('User cancelled duplicate');
                return;
            }

            if (newName === tableName) {
                alert('❌ New name must be different from original table!');
                return;
            }

            // Check if new name already exists
            if (destinationTables.includes(newName)) {
                if (!confirm(`⚠️ Table "${newName}" already exists in destination!\n\nOverwrite it?`)) {
                    return;
                }
            }

            // Show loading
            const loadingDiv = document.createElement('div');
            loadingDiv.style.cssText = 'position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(139, 92, 246, 0.95); color: white; padding: 30px 50px; border-radius: 12px; border: 2px solid #a78bfa; z-index: 10000; text-align: center; box-shadow: 0 0 40px rgba(139, 92, 246, 0.6);';
            loadingDiv.innerHTML = `
                <div style="font-size: 48px; margin-bottom: 15px; animation: spin 1.5s linear infinite;">📋</div>
                <div style="font-size: 18px; font-weight: bold;">Duplicating in Destination...</div>
                <div style="font-size: 14px; margin-top: 8px; opacity: 0.9;">${tableName} → ${newName}</div>
                <style>@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }</style>
            `;
            document.body.appendChild(loadingDiv);

            try {
                // Generate SQL with data from destination
                const sqlResult = await apiRequest('generate_table_sql', {
                    db_host: destConn.host,
                    db_name: destConn.dbName,
                    db_user: destConn.username,
                    db_pass: destConn.password,
                    db_port: destConn.port || '3306',
                    table_name: tableName,
                    include_data: 'true'
                });

                if (!sqlResult.success || !sqlResult.sql) {
                    throw new Error(sqlResult.message || 'Failed to generate SQL');
                }

                // Modify SQL for new name
                let modifiedSQL = sqlResult.sql;
                modifiedSQL = modifiedSQL.replace(new RegExp(`\`${tableName}\``, 'g'), `\`${newName}\``);
                modifiedSQL = modifiedSQL.replace(new RegExp(`DROP TABLE IF EXISTS \`${tableName}\``, 'g'), `DROP TABLE IF EXISTS \`${newName}\``);

                // Execute in destination
                const sqlWithoutComments = modifiedSQL
                    .split('\n')
                    .filter(line => !line.trim().startsWith('--'))
                    .join('\n');
                
                const statements = sqlWithoutComments
                    .split(';')
                    .map(s => s.trim())
                    .filter(s => s.length > 10);

                for (let i = 0; i < statements.length; i++) {
                    const finalStmt = statements[i].endsWith(';') ? statements[i] : statements[i] + ';';
                    await apiRequest('execute_sql', {
                        db_host: destConn.host,
                        db_name: destConn.dbName,
                        db_user: destConn.username,
                        db_pass: destConn.password,
                        db_port: destConn.port || '3306',
                        sql_query: finalStmt
                    });
                }

                // Success!
                document.body.removeChild(loadingDiv);
                showCustomToast(`✅ Table Duplicated in Destination!\n${tableName} → ${newName}`, 'success', 3000);

                // Refresh destination tables
                await loadDestinationTables();

            } catch (error) {
                console.error('❌ Duplicate error:', error);
                document.body.removeChild(loadingDiv);
                alert(`❌ Duplication Failed!\n\n${error.message}`);
            }
        }

        // Empty destination table data
        async function emptyDestinationTableData(tableName) {
            if (!selectedDestinationId) {
                alert('❌ No destination database selected');
                return;
            }

            // Get connection info (supports both Localhost and Hostinger)
            const destConn = getConnectionById(selectedDestinationId);

            if (!destConn) {
                alert('❌ Destination connection not found');
                return;
            }

            const currentRows = destinationTablesRowCount[tableName] || 0;

            if (!confirm(`🧹 Empty Table: ${tableName}\n\n📥 Destination Database\n\n⚠️ This will DELETE ALL ${currentRows.toLocaleString()} RECORD(S)!\n\nTable structure will be preserved.\n\nAre you sure?`)) {
                return;
            }

            // Show loading
            const loadingDiv = document.createElement('div');
            loadingDiv.style.cssText = 'position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(245, 158, 11, 0.95); color: white; padding: 30px 50px; border-radius: 12px; border: 2px solid #fbbf24; z-index: 10000; text-align: center; box-shadow: 0 0 40px rgba(245, 158, 11, 0.6);';
            loadingDiv.innerHTML = `
                <div style="font-size: 48px; margin-bottom: 15px; animation: sweep 1s ease-in-out infinite;">🧹</div>
                <div style="font-size: 18px; font-weight: bold;">Emptying Destination Table...</div>
                <div style="font-size: 14px; margin-top: 8px; opacity: 0.9;">${tableName}</div>
                <style>@keyframes sweep { 0%, 100% { transform: translateX(0); } 25% { transform: translateX(-10px); } 75% { transform: translateX(10px); } }</style>
            `;
            document.body.appendChild(loadingDiv);

            try {
                const result = await apiRequest('execute_sql', {
                    db_host: destConn.host,
                    db_name: destConn.dbName,
                    db_user: destConn.username,
                    db_pass: destConn.password,
                    db_port: destConn.port || '3306',
                    sql_query: `TRUNCATE TABLE \`${tableName}\`;`
                });

                if (!result.success) {
                    // Fallback to DELETE
                    await apiRequest('execute_sql', {
                        db_host: destConn.host,
                        db_name: destConn.dbName,
                        db_user: destConn.username,
                        db_pass: destConn.password,
                        db_port: destConn.port || '3306',
                        sql_query: `DELETE FROM \`${tableName}\`;`
                    });
                }

                document.body.removeChild(loadingDiv);
                showCustomToast(`✅ Destination Table Emptied!\n${tableName} - All ${currentRows.toLocaleString()} records deleted`, 'success', 3000);

                // Refresh destination tables
                await loadDestinationTables();

            } catch (error) {
                document.body.removeChild(loadingDiv);
                alert(`❌ Empty Failed!\n\n${error.message}`);
            }
        }

        // Delete table from destination database
        async function deleteDestinationTableFromMigration(tableName) {
            if (!selectedDestinationId) {
                alert('❌ No destination database selected');
                return;
            }

            // Get connection info (supports both Localhost and Hostinger)
            const destConn = getConnectionById(selectedDestinationId);

            if (!destConn) {
                alert('❌ Destination connection not found');
                return;
            }

            if (!confirm(`🗑️ Delete Table: ${tableName}\n\n📥 From Destination Database\n\n⚠️ This will PERMANENTLY delete this table and ALL its data!\n\nAre you absolutely sure?`)) {
                return;
            }

            if (!confirm(`⚠️ FINAL WARNING!\n\nDelete "${tableName}" from destination permanently?\n\nThis action CANNOT be undone!`)) {
                return;
            }

            // Show loading
            const loadingDiv = document.createElement('div');
            loadingDiv.style.cssText = 'position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(239, 68, 68, 0.95); color: white; padding: 30px 50px; border-radius: 12px; border: 2px solid #f87171; z-index: 10000; text-align: center; box-shadow: 0 0 40px rgba(239, 68, 68, 0.6);';
            loadingDiv.innerHTML = `
                <div style="font-size: 48px; margin-bottom: 15px; animation: shake 0.5s ease-in-out infinite;">🗑️</div>
                <div style="font-size: 18px; font-weight: bold;">Deleting from Destination...</div>
                <div style="font-size: 14px; margin-top: 8px; opacity: 0.9;">${tableName}</div>
                <style>@keyframes shake { 0%, 100% { transform: translateX(0); } 25% { transform: translateX(-5px); } 75% { transform: translateX(5px); } }</style>
            `;
            document.body.appendChild(loadingDiv);

            try {
                const deleteResult = await apiRequest('delete_table', {
                    db_host: destConn.host,
                    db_name: destConn.dbName,
                    db_user: destConn.username,
                    db_pass: destConn.password,
                    db_port: destConn.port || '3306',
                    table_name: tableName
                });

                if (!deleteResult.success) {
                    throw new Error(deleteResult.message || 'Failed to delete table');
                }

                document.body.removeChild(loadingDiv);
                showCustomToast(`✅ Table Deleted from Destination!\n${tableName}`, 'success', 3000);

                // Refresh destination tables
                await loadDestinationTables();

            } catch (error) {
                document.body.removeChild(loadingDiv);
                alert(`❌ Deletion Failed!\n\n${error.message}`);
            }
        }

        // Rename destination table (right-click inline rename)
        function startRenameDestinationTable(oldName, element) {
            console.log('✏️ Starting rename for DESTINATION table:', oldName);
            
            if (element.querySelector('input')) {
                return;
            }
            
            const originalContent = element.innerHTML;
            
            element.innerHTML = `
                <input type="text" 
                       value="${oldName}" 
                       placeholder="${oldName}"
                       class="rename-input"
                       style="width: 100%; padding: 6px 10px; background: linear-gradient(135deg, rgba(34, 197, 94, 0.3) 0%, rgba(16, 185, 129, 0.2) 100%); border: 2px solid #22c55e; border-radius: 6px; color: #fef3c7; font-size: 14px; font-weight: 600; text-align: center; outline: none; box-shadow: 0 0 15px rgba(34, 197, 94, 0.5);">
            `;
            
            const input = element.querySelector('input');
            
            setTimeout(() => {
                input.focus();
                input.select();
            }, 50);
            
            input.addEventListener('keydown', async function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const newName = input.value.trim();
                    
                    if (!newName) {
                        alert('❌ Table name cannot be empty!');
                        input.focus();
                        return;
                    }
                    
                    if (newName === oldName) {
                        element.innerHTML = originalContent;
                        return;
                    }
                    
                    if (!/^[a-zA-Z0-9_]+$/.test(newName)) {
                        alert('❌ Table name can only contain letters, numbers, and underscores!');
                        input.focus();
                        return;
                    }
                    
                    if (destinationTables.includes(newName)) {
                        alert(`❌ Table "${newName}" already exists in destination!`);
                        input.focus();
                        return;
                    }
                    
                    await performDestinationTableRename(oldName, newName, element, originalContent);
                } else if (e.key === 'Escape') {
                    element.innerHTML = originalContent;
                }
            });
            
            input.addEventListener('blur', function() {
                setTimeout(() => {
                    if (element.contains(input)) {
                        element.innerHTML = originalContent;
                    }
                }, 200);
            });
        }

        // Perform destination table rename
        async function performDestinationTableRename(oldName, newName, element, originalContent) {
            if (!selectedDestinationId) {
                alert('❌ No destination database selected');
                element.innerHTML = originalContent;
                return;
            }

            // Get connection using helper function (supports both Localhost and Hostinger)
            const destConn = getConnectionById(selectedDestinationId);

            if (!destConn) {
                alert('❌ Destination connection not found');
                element.innerHTML = originalContent;
                return;
            }

            element.innerHTML = '<div style="font-size: 11px; color: #86efac;">Renaming...</div>';

            try {
                const result = await apiRequest('rename_table', {
                    db_host: destConn.host,
                    db_name: destConn.dbName,
                    db_user: destConn.username,
                    db_pass: destConn.password,
                    db_port: destConn.port || '3306',
                    old_table_name: oldName,
                    new_table_name: newName
                });

                if (!result.success) {
                    throw new Error(result.message || 'Failed to rename table');
                }

                element.innerHTML = `<div style="color: #22c55e; font-size: 12px;">✅ Renamed!</div>`;
                
                setTimeout(async () => {
                    await loadDestinationTables();
                    showCustomToast(`✅ Destination Table Renamed!\n${oldName} → ${newName}`, 'success', 3000);
                }, 500);

            } catch (error) {
                element.innerHTML = originalContent;
                alert(`❌ Rename Failed!\n\n${error.message}`);
            }
        }

        // Show destination database info (credentials + selected tables)
        async function showDestinationDatabaseInfo() {
            if (!selectedDestinationId) {
                alert('❌ No destination database selected\n\nPlease select a destination database from the dropdown.');
                return;
            }

            // Use getConnectionById to support BOTH Localhost and Hostinger
            const destConn = getConnectionById(selectedDestinationId);

            if (!destConn) {
                alert('❌ Connection not found');
                return;
            }

            // Open modal
            const modal = document.getElementById('databaseInfoModal');
            modal.classList.add('active');
            
            const titleEl = document.getElementById('databaseInfoDbName');
            const textArea = document.getElementById('databaseInfoText');
            
            if (titleEl) titleEl.textContent = destConn.dbName + ' (Destination)';
            if (textArea) textArea.value = 'Loading destination database information...';

            // Build info
            let dbPrompt = `DATABASE CONNECTION INFORMATION FOR AI (DESTINATION)
=====================================

🗄️ DESTINATION DATABASE CREDENTIALS:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Connection Name: ${destConn.name}
Host: ${destConn.host}
Database Name: ${destConn.dbName}
Username: ${destConn.username}
Password: ${destConn.password}
Port: ${destConn.port || '3306'}
Server Type: ${destConn.type === 'vps' ? 'VPS Server' : 'Shared Hosting'}

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
`;

            // Add selected tables info if any
            if (selectedDestinationTables && selectedDestinationTables.size > 0) {
                dbPrompt += `
📊 SELECTED TABLES (${selectedDestinationTables.size}):
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

`;
                
                const selectedTablesArray = Array.from(selectedDestinationTables);
                
                for (let i = 0; i < selectedTablesArray.length; i++) {
                    const tableName = selectedTablesArray[i];
                    
                    if (textArea) {
                        textArea.value = `Loading destination database information...\n\nFetching table ${i + 1}/${selectedTablesArray.length}: ${tableName}`;
                    }
                    
                    const structResult = await apiRequest('get_table_structure', {
                        db_host: destConn.host,
                        db_name: destConn.dbName,
                        db_user: destConn.username,
                        db_pass: destConn.password,
                        db_port: destConn.port || '3306',
                        table_name: tableName
                    });

                    const dataResult = await apiRequest('get_table_data', {
                        db_host: destConn.host,
                        db_name: destConn.dbName,
                        db_user: destConn.username,
                        db_pass: destConn.password,
                        db_port: destConn.port || '3306',
                        table_name: tableName,
                        page: 1,
                        limit: 5
                    });

                    if (structResult.success && dataResult.success) {
                        const rowCount = dataResult.pagination.total_rows;
                        const engine = dataResult.table_info.engine;
                        const collation = dataResult.table_info.collation;
                        const dataSize = formatBytes(dataResult.table_info.data_length);
                        
                        dbPrompt += `
${i + 1}. TABLE: ${tableName}
   ├─ Total Records: ${rowCount.toLocaleString()}
   ├─ Columns: ${structResult.columns.length}
   ├─ Engine: ${engine}
   ├─ Collation: ${collation}
   ├─ Data Size: ${dataSize}
   └─ Structure:
`;
                        
                        structResult.columns.forEach((col, idx) => {
                            const keyInfo = col.Key ? 
                                (col.Key === 'PRI' ? ' [PRIMARY KEY]' : 
                                 col.Key === 'UNI' ? ' [UNIQUE]' : 
                                 col.Key === 'MUL' ? ' [INDEX]' : '') : '';
                            const nullInfo = col.Null === 'YES' ? ' NULL' : ' NOT NULL';
                            const defaultInfo = col.Default !== null ? ` DEFAULT '${col.Default}'` : '';
                            const extraInfo = col.Extra ? ` ${col.Extra.toUpperCase()}` : '';
                            
                            dbPrompt += `      ${idx + 1}. ${col.Field} - ${col.Type}${nullInfo}${defaultInfo}${extraInfo}${keyInfo}\n`;
                        });
                        
                        dbPrompt += '\n';
                    }
                }
                
                dbPrompt += `━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

`;
            }

            // Add connection examples (same as source)
            dbPrompt += `
📋 CONNECTION STRING (PHP - PDO):
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

\`\`\`php
$host = '${destConn.host}';
$dbname = '${destConn.dbName}';
$username = '${destConn.username}';
$password = '${destConn.password}';
$port = '${destConn.port || '3306'}';

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Connected to destination database!";
} catch(PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage();
}
\`\`\`

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

You can now use this DESTINATION database connection!
`;

            if (textArea) {
                textArea.value = dbPrompt;
            }
        }

        // ========================================
        // REVERSE DRAG & DROP HANDLERS (Destination → Source)
        // ========================================

        let draggedDestinationTables = [];
        let draggedDestinationIncludeData = false;

        // Handle destination drag start (drag FROM destination TO source)
        function handleDestinationDragStart(event, tableName, includeData = false) {
            console.log('=== DESTINATION DRAG START ===');
            console.log('Table:', tableName);
            console.log('Include Data:', includeData);
            console.log('Selected destination tables:', Array.from(selectedDestinationTables));
            
            let tablesToDrag = [];
            
            if (selectedDestinationTables.has(tableName) && selectedDestinationTables.size > 1) {
                tablesToDrag = Array.from(selectedDestinationTables);
                console.log('🎯 MULTI-SELECTION MODE: Dragging', tablesToDrag.length, 'tables from destination');
            } else {
                tablesToDrag = [tableName];
                console.log('🎯 SINGLE TABLE MODE: Dragging 1 table from destination');
            }
            
            draggedDestinationTables = tablesToDrag;
            draggedDestinationIncludeData = includeData;
            
            event.target.classList.add('dragging');
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', JSON.stringify({
                tables: tablesToDrag,
                includeData: includeData,
                source: 'destination'
            }));
            
            // Add dragging class to all selected destination tables
            tablesToDrag.forEach(tName => {
                const card = document.querySelector(`#migrationDestinationTablesContainer .migration-table-box[data-table="${tName}"]`);
                if (card) {
                    card.classList.add('dragging');
                }
            });
            
            // Hint message with green theme for destination
            const hint = document.createElement('div');
            hint.id = 'dragHintDestination';
            const bgColor = includeData ? 'rgba(16, 185, 129, 0.95)' : 'rgba(34, 197, 94, 0.95)';
            const modeText = includeData ? '📦 Structure + Data' : '🏗️ Structure Only';
            const borderColor = includeData ? '#22c55e' : '#86efac';
            
            const tableNames = tablesToDrag.length > 1 
                ? `${tablesToDrag.length} tables` 
                : tableName;
            
            hint.style.cssText = `position: fixed; top: 20px; left: 50%; transform: translateX(-50%); background: ${bgColor}; color: white; padding: 15px 30px; border-radius: 10px; font-size: 14px; font-weight: bold; z-index: 9999; box-shadow: 0 5px 20px rgba(0,0,0,0.5); border: 2px solid ${borderColor};`;
            hint.innerHTML = `⬅️ Dragging: <strong>${tableNames}</strong><br><span style="font-size: 12px; opacity: 0.9;">${modeText} (Destination → Source)</span>`;
            document.body.appendChild(hint);
            
            console.log(`🖱️ Started dragging ${tablesToDrag.length} table(s) from DESTINATION to SOURCE`);
        }

        // Handle destination drag end
        function handleDestinationDragEnd(event) {
            if (draggedDestinationTables && draggedDestinationTables.length > 0) {
                draggedDestinationTables.forEach(tName => {
                    const card = document.querySelector(`#migrationDestinationTablesContainer .migration-table-box[data-table="${tName}"]`);
                    if (card) {
                        card.classList.remove('dragging');
                    }
                });
            }
            
            event.target.classList.remove('dragging');
            
            const hint = document.getElementById('dragHintDestination');
            if (hint && hint.parentNode) {
                document.body.removeChild(hint);
            }
            
            console.log('🖱️ Finished dragging from DESTINATION');
        }

        // Handle SOURCE drag over (when dragging FROM destination TO source)
        function handleSourceDragOver(event) {
            event.preventDefault();
            event.dataTransfer.dropEffect = 'move';
            
            const dropZone = document.getElementById('migrationTablesContainer');
            if (dropZone && !dropZone.classList.contains('drag-over')) {
                dropZone.classList.add('drag-over');
            }
        }

        // Handle SOURCE drag leave
        function handleSourceDragLeave(event) {
            if (event.target.id === 'migrationTablesContainer') {
                event.target.classList.remove('drag-over');
            }
        }

        // Handle SOURCE drop (REVERSE: Destination → Source)
        async function handleSourceDrop(event) {
            event.preventDefault();
            
            const dropZone = document.getElementById('migrationTablesContainer');
            if (dropZone) {
                dropZone.classList.remove('drag-over');
            }

            if (!draggedDestinationTables || draggedDestinationTables.length === 0) {
                console.error('❌ No destination tables being dragged');
                return;
            }

            if (!selectedDatabaseId) {
                alert('⚠️ Please select a source database first!');
                return;
            }

            console.log('📥 Dropped DESTINATION tables to SOURCE:', draggedDestinationTables);
            console.log('📊 Total tables to migrate:', draggedDestinationTables.length);
            console.log('📊 Include data:', draggedDestinationIncludeData);

            // Show loading
            const loadingDiv = document.createElement('div');
            loadingDiv.id = 'reverseDragMigrationLoading';
            loadingDiv.style.cssText = 'position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: linear-gradient(135deg, rgba(0, 0, 0, 0.95) 0%, rgba(34, 197, 94, 0.2) 100%); color: #22c55e; padding: 40px 60px; border-radius: 15px; border: 3px solid #22c55e; z-index: 10000; text-align: center; box-shadow: 0 0 50px rgba(34, 197, 94, 0.5);';
            const modeText = draggedDestinationIncludeData ? 'Structure + Data' : 'Structure Only';
            const tableCountText = draggedDestinationTables.length > 1 
                ? `${draggedDestinationTables.length} Tables` 
                : draggedDestinationTables[0];
            
            loadingDiv.innerHTML = `
                <div style="font-size: 56px; margin-bottom: 20px; animation: spin 2s linear infinite;">🔄</div>
                <div style="font-size: 20px; margin-bottom: 12px; font-weight: bold;">⬅️ Reverse Migration</div>
                <div style="font-size: 16px; margin-bottom: 8px;">Moving ${tableCountText}...</div>
                <div style="font-size: 14px; opacity: 0.8; color: #fbbf24;">${modeText}</div>
                <div id="reverseMigrationProgress" style="font-size: 13px; opacity: 0.7; margin-top: 10px;">Preparing...</div>
                <div style="font-size: 12px; opacity: 0.6; margin-top: 15px;">From Destination ⬅️ To Source</div>
                <style>@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }</style>
            `;
            document.body.appendChild(loadingDiv);

            try {
                // Get source and destination connections (supports both Localhost and Hostinger)
                const sourceConn = getConnectionById(selectedDatabaseId);
                const destConn = getConnectionById(selectedDestinationId);

                if (!sourceConn || !destConn) {
                    throw new Error('Source or Destination connection not found');
                }

                const includeDataMode = draggedDestinationIncludeData ? 'true' : 'false';
                
                let successCount = 0;
                let failCount = 0;
                const failedTables = [];
                
                // Loop through each destination table
                for (let tableIndex = 0; tableIndex < draggedDestinationTables.length; tableIndex++) {
                    const currentTable = draggedDestinationTables[tableIndex];
                    
                    const progressEl = document.getElementById('reverseMigrationProgress');
                    if (progressEl) {
                        progressEl.textContent = `Table ${tableIndex + 1}/${draggedDestinationTables.length}: ${currentTable}`;
                    }
                    
                    try {
                        // Generate SQL from DESTINATION
                        const sqlResult = await apiRequest('generate_table_sql', {
                            db_host: destConn.host,
                            db_name: destConn.dbName,
                            db_user: destConn.username,
                            db_pass: destConn.password,
                            db_port: destConn.port || '3306',
                            table_name: currentTable,
                            include_data: includeDataMode
                        });

                        if (!sqlResult.success || !sqlResult.sql) {
                            throw new Error(sqlResult.message || 'Failed to generate SQL');
                        }

                        // Execute SQL on SOURCE
                        const sqlWithoutComments = sqlResult.sql
                            .split('\n')
                            .filter(line => !line.trim().startsWith('--'))
                            .join('\n');
                        
                        const sqlStatements = sqlWithoutComments
                            .split(';')
                            .map(s => s.trim())
                            .filter(s => s.length > 10);

                        for (let i = 0; i < sqlStatements.length; i++) {
                            const statement = sqlStatements[i].trim();
                            if (!statement) continue;

                            const finalStatement = statement.endsWith(';') ? statement : statement + ';';
                            
                            await apiRequest('execute_sql', {
                                db_host: sourceConn.host,
                                db_name: sourceConn.dbName,
                                db_user: sourceConn.username,
                                db_pass: sourceConn.password,
                                db_port: sourceConn.port || '3306',
                                sql_query: finalStatement
                            });
                        }

                        // Delete from DESTINATION
                        const deleteResult = await apiRequest('delete_table', {
                            db_host: destConn.host,
                            db_name: destConn.dbName,
                            db_user: destConn.username,
                            db_pass: destConn.password,
                            db_port: destConn.port || '3306',
                            table_name: currentTable
                        });

                        if (!deleteResult.success) {
                            console.warn(`⚠️ ${currentTable} copied but not deleted from destination:`, deleteResult.message);
                        }

                        successCount++;
                        
                    } catch (tableError) {
                        failCount++;
                        failedTables.push({ table: currentTable, error: tableError.message });
                        console.error(`❌ Failed to migrate ${currentTable}:`, tableError.message);
                    }
                }
                
                // Success!
                document.body.removeChild(loadingDiv);
                
                const successDiv = document.createElement('div');
                const modeIcon = draggedDestinationIncludeData ? '📦' : '🏗️';
                const modeLabel = draggedDestinationIncludeData ? 'Structure + Data' : 'Structure Only';
                const modeColor = draggedDestinationIncludeData ? '#22c55e' : '#86efac';
                
                const tableText = draggedDestinationTables.length > 1 
                    ? `${successCount} Table(s) Moved`
                    : `Table Moved: ${draggedDestinationTables[0]}`;
                
                successDiv.style.cssText = 'position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: linear-gradient(135deg, rgba(34, 197, 94, 0.98) 0%, rgba(16, 185, 129, 0.98) 100%); color: white; padding: 40px 60px; border-radius: 15px; border: 3px solid #86efac; z-index: 10000; text-align: center; box-shadow: 0 0 50px rgba(34, 197, 94, 0.7);';
                successDiv.innerHTML = `
                    <div style="font-size: 64px; margin-bottom: 20px;">✅</div>
                    <div style="font-size: 22px; margin-bottom: 12px; font-weight: bold;">⬅️ ${tableText}</div>
                    <div style="font-size: 14px; opacity: 0.95; background: rgba(255,255,255,0.2); padding: 8px 16px; border-radius: 8px; display: inline-block; margin: 5px 0;">${successCount} of ${draggedDestinationTables.length} successful</div>
                    <div style="font-size: 13px; opacity: 0.9; margin-top: 10px; background: rgba(0,0,0,0.3); padding: 8px 14px; border-radius: 6px; display: inline-block; border: 1px solid ${modeColor};">
                        <span style="color: ${modeColor}; font-size: 16px;">${modeIcon}</span> <strong>${modeLabel}</strong>
                    </div>
                    <div style="font-size: 13px; opacity: 0.8; margin-top: 15px;">📥 Removed from Destination • 📤 Added to Source</div>
                `;
                document.body.appendChild(successDiv);

                setTimeout(() => {
                    document.body.removeChild(successDiv);
                }, 3000);

                // Refresh both
                await loadMigrationTables();
                if (selectedDestinationId) {
                    await loadDestinationTables();
                }

            } catch (error) {
                console.error('❌ Reverse migration error:', error);
                
                const loading = document.getElementById('reverseDragMigrationLoading');
                if (loading && loading.parentNode) {
                    document.body.removeChild(loading);
                }

                alert(`❌ Reverse Migration Failed!\n\n${error.message}`);
            } finally {
                draggedDestinationTables = [];
                draggedDestinationIncludeData = false;
            }
        }
        
        // ========================================
        // SCRIPT LOAD COMPLETE
        // ========================================
        console.log('═══════════════════════════════════════════════════════');
        console.log('✅ ALL FUNCTIONS LOADED SUCCESSFULLY!');
        console.log('🎯 toggleHostingerConnection:', typeof toggleHostingerConnection);
        console.log('🎯 toggleLocalhostConnection:', typeof toggleLocalhostConnection);
        console.log('🎯 resetToAutoDetected:', typeof resetToAutoDetected);
        console.log('🎯 apiRequest:', typeof apiRequest);
        console.log('═══════════════════════════════════════════════════════');

    </script>

<!-- Back to Catalog Button -->
<a href="index.php" id="backToCatalogBtn" class="catalog-back-btn" style="position: fixed; bottom: 30px; left: 30px; width: 70px; height: 70px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 25px rgba(240, 147, 251, 0.5); z-index: 9999; text-decoration: none; transition: all 0.3s ease; border: 3px solid rgba(255, 255, 255, 0.3); animation: catalog-pulse 2s infinite;" title="Back to Catalog" onmouseover="this.style.transform='scale(1.15) rotate(-10deg)'; this.style.boxShadow='0 10px 35px rgba(240, 147, 251, 0.7)';" onmouseout="this.style.transform='scale(1) rotate(0deg)'; this.style.boxShadow='0 8px 25px rgba(240, 147, 251, 0.5)';">
    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));">
        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
        <polyline points="9 22 9 12 15 12 15 22"></polyline>
    </svg>
</a>
<style>
@keyframes catalog-pulse {
    0%, 100% { box-shadow: 0 8px 25px rgba(240, 147, 251, 0.5), 0 0 0 0 rgba(240, 147, 251, 0.4); }
    50% { box-shadow: 0 8px 25px rgba(240, 147, 251, 0.5), 0 0 0 10px rgba(240, 147, 251, 0); }
}
.catalog-back-btn::after {
    content: 'Catalog';
    position: absolute;
    left: 85px;
    background: rgba(0, 0, 0, 0.85);
    color: white;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 600;
    white-space: nowrap;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.3s ease;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}
.catalog-back-btn:hover::after {
    opacity: 1;
}
</style>
<!-- End Back to Catalog Button -->
</body>
</html>






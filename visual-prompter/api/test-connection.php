<?php
/**
 * API Endpoint to test database connection
 * Tests connection to the selected database and returns status
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

// Start timing
$startTime = microtime(true);

try {
    $pdo = null;
    $connectionInfo = '';
    
    switch ($dbType) {
        case 'mysql':
            $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 10
            ]);
            
            // Test query
            $stmt = $pdo->query("SELECT VERSION() as version, DATABASE() as db");
            $info = $stmt->fetch(PDO::FETCH_ASSOC);
            $connectionInfo = "MySQL " . ($info['version'] ?? 'Unknown');
            break;
            
        case 'postgresql':
        case 'postgres':
            $dsn = "pgsql:host={$host};port={$port};dbname={$dbName}";
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 10
            ]);
            
            // Test query
            $stmt = $pdo->query("SELECT version() as version");
            $info = $stmt->fetch(PDO::FETCH_ASSOC);
            $connectionInfo = "PostgreSQL " . substr($info['version'] ?? '', 0, 50);
            break;
            
        case 'sqlite':
            // For SQLite, dbName is the file path
            $dsn = "sqlite:{$dbName}";
            $pdo = new PDO($dsn, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            
            // Test query
            $stmt = $pdo->query("SELECT sqlite_version() as version");
            $info = $stmt->fetch(PDO::FETCH_ASSOC);
            $connectionInfo = "SQLite " . ($info['version'] ?? 'Unknown');
            break;
            
        case 'sqlsrv':
        case 'sqlserver':
            // Check if SQL Server extension is available
            if (!extension_loaded('sqlsrv') && !extension_loaded('pdo_sqlsrv') && !extension_loaded('pdo_dblib')) {
                throw new Exception('SQL Server extensions not installed. Please install sqlsrv or pdo_sqlsrv extension.');
            }
            
            if (extension_loaded('pdo_sqlsrv')) {
                $dsn = "sqlsrv:Server={$host}" . ($port ? ",{$port}" : '') . ";Database={$dbName};TrustServerCertificate=1";
                $pdo = new PDO($dsn, $username, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 10
                ]);
            } elseif (extension_loaded('pdo_dblib')) {
                $dsn = "dblib:host={$host}" . ($port ? ":{$port}" : '') . ";dbname={$dbName}";
                $pdo = new PDO($dsn, $username, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 10
                ]);
            }
            
            // Test query
            $stmt = $pdo->query("SELECT @@VERSION as version");
            $info = $stmt->fetch(PDO::FETCH_ASSOC);
            $connectionInfo = "SQL Server";
            break;
            
        default:
            // Try generic MySQL connection
            $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 10
            ]);
            $connectionInfo = "Connected";
    }
    
    // Calculate latency
    $endTime = microtime(true);
    $latencyMs = round(($endTime - $startTime) * 1000);
    
    // Close connection
    $pdo = null;
    
    echo json_encode([
        'success' => true,
        'message' => 'Connection successful',
        'latency' => $latencyMs,
        'info' => $connectionInfo,
        'host' => $host,
        'database' => $dbName,
        'dbType' => $dbType
    ]);
    
} catch (PDOException $e) {
    $endTime = microtime(true);
    $latencyMs = round(($endTime - $startTime) * 1000);
    
    // Parse error message for better display
    $errorMsg = $e->getMessage();
    $friendlyError = 'Connection failed';
    
    if (strpos($errorMsg, 'Access denied') !== false) {
        $friendlyError = 'Access denied - check username/password';
    } elseif (strpos($errorMsg, 'Unknown database') !== false || strpos($errorMsg, 'does not exist') !== false) {
        $friendlyError = 'Database not found';
    } elseif (strpos($errorMsg, 'Connection refused') !== false || strpos($errorMsg, 'could not connect') !== false) {
        $friendlyError = 'Connection refused - check host/port';
    } elseif (strpos($errorMsg, 'timed out') !== false) {
        $friendlyError = 'Connection timed out';
    } elseif (strpos($errorMsg, 'getaddrinfo') !== false || strpos($errorMsg, 'Name or service not known') !== false) {
        $friendlyError = 'Host not found';
    }
    
    http_response_code(200); // Still return 200 for proper JSON handling
    echo json_encode([
        'success' => false,
        'error' => $friendlyError,
        'message' => $errorMsg,
        'latency' => $latencyMs,
        'host' => $host,
        'database' => $dbName,
        'dbType' => $dbType
    ]);
    
} catch (Exception $e) {
    http_response_code(200);
    echo json_encode([
        'success' => false,
        'error' => 'Connection error',
        'message' => $e->getMessage(),
        'host' => $host,
        'database' => $dbName,
        'dbType' => $dbType
    ]);
}


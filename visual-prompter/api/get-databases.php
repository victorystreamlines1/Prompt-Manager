<?php
/**
 * API Endpoint to fetch database credentials from Hostinger
 * Returns JSON list of databases from report_prompt_databases table
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Database Configuration (same as report-prompt-databases.php)
$dbHost = 'srv1788.hstgr.io';
$dbName = 'u419999707_Mohamed';
$dbUser = 'u419999707_Abuammar';
$dbPass = 'P@master5007';
$dbPort = 3306;

$tableName = 'report_prompt_databases';

try {
    $pdo = new PDO(
        "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    // Fetch all database connections
    $stmt = $pdo->query("SELECT * FROM `$tableName` ORDER BY name ASC");
    $connections = $stmt->fetchAll();
    
    // Process and format for Visual Prompter
    $databases = [];
    foreach ($connections as $conn) {
        // Detect database type based on port
        $dbType = 'mysql'; // default
        $port = intval($conn['port']);
        
        if ($port === 5432) {
            $dbType = 'postgresql';
        } elseif ($port === 27017) {
            $dbType = 'mongodb';
        } elseif ($port === 6379) {
            $dbType = 'redis';
        } elseif (stripos($conn['name'], 'mongo') !== false || stripos($conn['dbName'], 'mongo') !== false) {
            $dbType = 'mongodb';
        } elseif (stripos($conn['name'], 'postgres') !== false || stripos($conn['dbName'], 'postgres') !== false) {
            $dbType = 'postgresql';
        } elseif (stripos($conn['name'], 'redis') !== false) {
            $dbType = 'redis';
        } elseif (stripos($conn['name'], 'sqlite') !== false || stripos($conn['dbName'], 'sqlite') !== false) {
            $dbType = 'sqlite';
        }
        
        $databases[] = [
            'id' => $conn['id'],
            'name' => $conn['name'],
            'type' => $conn['type'], // hosting type (shared, vps, etc.)
            'dbType' => $dbType,     // database type (mysql, postgresql, etc.)
            'host' => $conn['host'],
            'dbName' => $conn['dbName'],
            'username' => $conn['username'],
            'password' => $conn['password'],
            'port' => $conn['port'],
            'createdAt' => $conn['createdAt']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'databases' => $databases,
        'count' => count($databases),
        'timestamp' => date('c')
    ], JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database connection failed',
        'message' => $e->getMessage()
    ]);
}


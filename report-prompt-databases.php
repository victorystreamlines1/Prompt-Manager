<?php
/**
 * Report Prompt Databases - CRUD Management
 * Table: report_prompt_databases
 * 
 * Smart Auto-Switch Database Connection:
 * - Tries LOCALHOST first (faster when on Hostinger server)
 * - Falls back to REMOTE if localhost fails
 * - Toggle switch allows manual override
 */

// ========================================
// SMART DATABASE CONNECTION SYSTEM
// ========================================

// Database Credentials - Both connections
$dbCredentials = [
    'localhost' => [
        'host' => 'localhost',
        'dbname' => 'u419999707_prompt_manager',
        'username' => 'u419999707_prompt_manager',
        'password' => 'P@master5007',
        'port' => '3306'
    ],
    'remote' => [
        'host' => 'srv1788.hstgr.io',
        'dbname' => 'u419999707_prompt_manager',
        'username' => 'u419999707_prompt_manager',
        'password' => 'P@master5007',
        'port' => '3306'
    ]
];

// Table name
$tableName = 'report_prompt_databases';

// Connection state variables
$pdo = null;
$connectionType = 'localhost'; // Will be updated after connection
$connectionFallback = false;
$connectionError = null;

// ========================================
// TEST CONNECTION API ENDPOINT
// ========================================
if (isset($_GET['test_connection']) && isset($_GET['id'])) {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    
    $testId = $_GET['id'];
    
    // First, establish connection to main database to get the record
    $mainPdo = null;
    
    // Try remote connection first (more reliable from anywhere)
    $cred = $dbCredentials['remote'];
    try {
        $mainPdo = new PDO(
            "mysql:host={$cred['host']};port={$cred['port']};dbname={$cred['dbname']};charset=utf8mb4",
            $cred['username'],
            $cred['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]
        );
    } catch (PDOException $e) {
        // Try localhost as fallback
        $cred = $dbCredentials['localhost'];
        try {
            $mainPdo = new PDO(
                "mysql:host={$cred['host']};port={$cred['port']};dbname={$cred['dbname']};charset=utf8mb4",
                $cred['username'],
                $cred['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]
            );
        } catch (PDOException $e2) {
            echo json_encode(['success' => false, 'error' => 'Cannot connect to main database: ' . $e2->getMessage(), 'id' => $testId]);
            exit;
        }
    }
    
    // Get the connection details from database
    $stmt = $mainPdo->prepare("SELECT * FROM `$tableName` WHERE id = ?");
    $stmt->execute([$testId]);
    $conn = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$conn) {
        echo json_encode(['success' => false, 'error' => 'Connection record not found', 'id' => $testId]);
        exit;
    }
    
    // Try to connect to the target database
    $startTime = microtime(true);
    try {
        // Handle empty password
        $password = ($conn['password'] === '' || $conn['password'] === null) ? null : $conn['password'];
        
        $testPdo = new PDO(
            "mysql:host={$conn['host']};port={$conn['port']};dbname={$conn['dbName']};charset=utf8mb4",
            $conn['username'],
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, 
                PDO::ATTR_TIMEOUT => 5,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
        
        // Test with a simple query
        $testPdo->query("SELECT 1");
        
        $connectionTime = round((microtime(true) - $startTime) * 1000, 2);
        
        echo json_encode([
            'success' => true, 
            'id' => $testId,
            'message' => 'Connection successful',
            'time' => $connectionTime,
            'host' => $conn['host'],
            'dbName' => $conn['dbName']
        ]);
    } catch (PDOException $e) {
        $connectionTime = round((microtime(true) - $startTime) * 1000, 2);
        echo json_encode([
            'success' => false, 
            'id' => $testId,
            'error' => $e->getMessage(),
            'time' => $connectionTime,
            'host' => $conn['host'],
            'dbName' => $conn['dbName']
        ]);
    }
    exit;
}

// Check if user manually selected a connection type via AJAX
if (isset($_GET['switch_db']) && in_array($_GET['switch_db'], ['localhost', 'remote'])) {
    header('Content-Type: application/json');
    $requestedType = $_GET['switch_db'];
    $cred = $dbCredentials[$requestedType];
    
    try {
        $testPdo = new PDO(
            "mysql:host={$cred['host']};port={$cred['port']};dbname={$cred['dbname']};charset=utf8mb4",
            $cred['username'],
            $cred['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]
        );
        echo json_encode(['success' => true, 'type' => $requestedType, 'message' => "Connected to $requestedType"]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'type' => $requestedType, 'error' => $e->getMessage()]);
    }
    exit;
}

// Get preferred connection type from cookie (if set)
$preferredType = isset($_COOKIE['db_connection_type']) ? $_COOKIE['db_connection_type'] : 'localhost';
if (!in_array($preferredType, ['localhost', 'remote'])) {
    $preferredType = 'localhost';
}

// Smart Connection Function
function connectToDatabase($credentials, $preferredType) {
    global $connectionType, $connectionFallback, $connectionError;
    
    // Try preferred connection first
    $cred = $credentials[$preferredType];
    try {
        $pdo = new PDO(
            "mysql:host={$cred['host']};port={$cred['port']};dbname={$cred['dbname']};charset=utf8mb4",
            $cred['username'],
            $cred['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 5
            ]
        );
        $connectionType = $preferredType;
        $connectionFallback = false;
        return $pdo;
    } catch (PDOException $e) {
        // Try fallback connection
        $fallbackType = ($preferredType === 'localhost') ? 'remote' : 'localhost';
        $cred = $credentials[$fallbackType];
        
        try {
            $pdo = new PDO(
                "mysql:host={$cred['host']};port={$cred['port']};dbname={$cred['dbname']};charset=utf8mb4",
                $cred['username'],
                $cred['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT => 5
                ]
            );
            $connectionType = $fallbackType;
            $connectionFallback = true;
            return $pdo;
        } catch (PDOException $e2) {
            $connectionError = $e2->getMessage();
            return null;
        }
    }
}

// Establish connection
$pdo = connectToDatabase($dbCredentials, $preferredType);

if (!$pdo) {
    die("Connection failed: " . $connectionError);
}

// Set cookie to remember working connection type
setcookie('db_connection_type', $connectionType, time() + (86400 * 30), '/'); // 30 days

// Create table if not exists
$createTableSQL = "CREATE TABLE IF NOT EXISTS `$tableName` (
    `id` VARCHAR(50) PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `type` VARCHAR(50) DEFAULT 'shared',
    `host` VARCHAR(255) NOT NULL,
    `dbName` VARCHAR(255) NOT NULL,
    `username` VARCHAR(255) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `port` VARCHAR(10) DEFAULT '3306',
    `createdAt` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$pdo->exec($createTableSQL);

// ========================================
// JSON API ENDPOINT (for other apps)
// ========================================
// This allows other apps to fetch connections from this hub
if (isset($_GET['api']) && $_GET['api'] === 'list') {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET');
    
    $stmt = $pdo->query("SELECT * FROM `$tableName` ORDER BY createdAt DESC");
    $connections = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'connections' => $connections,
        'count' => count($connections)
    ], JSON_PRETTY_PRINT);
    exit;
}

// Handle Actions
$message = '';
$messageType = '';
$operationTime = 0; // Track operation time in milliseconds
$operationType = ''; // Type of operation performed

// DELETE SINGLE
if (isset($_GET['delete'])) {
    $startTime = microtime(true);
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM `$tableName` WHERE id = ?");
    $stmt->execute([$id]);
    $operationTime = round((microtime(true) - $startTime) * 1000, 2);
    $operationType = 'DELETE';
    $message = "Record deleted successfully! ⏱️ {$operationTime}ms";
    $messageType = 'success';
}

// DELETE MULTIPLE (Mass Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mass_delete') {
    if (isset($_POST['selected_ids']) && is_array($_POST['selected_ids']) && count($_POST['selected_ids']) > 0) {
        $startTime = microtime(true);
        $count = 0;
        foreach ($_POST['selected_ids'] as $id) {
            $stmt = $pdo->prepare("DELETE FROM `$tableName` WHERE id = ?");
            $stmt->execute([$id]);
            $count++;
        }
        $operationTime = round((microtime(true) - $startTime) * 1000, 2);
        $operationType = 'MASS_DELETE';
        $message = "$count record(s) deleted successfully! ⏱️ {$operationTime}ms";
        $messageType = 'success';
    } else {
        $message = 'No records selected for deletion!';
        $messageType = 'error';
    }
}

// ADD / UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        
        // ADD
        if ($_POST['action'] === 'add') {
            $startTime = microtime(true);
            $id = time() . rand(100, 999);
            $stmt = $pdo->prepare("INSERT INTO `$tableName` (id, name, type, host, dbName, username, password, port, createdAt) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([
                $id,
                $_POST['name'],
                $_POST['type'],
                $_POST['host'],
                $_POST['dbName'],
                $_POST['username'],
                $_POST['password'],
                $_POST['port']
            ]);
            $operationTime = round((microtime(true) - $startTime) * 1000, 2);
            $operationType = 'ADD';
            $message = "Record added successfully! ⏱️ {$operationTime}ms";
            $messageType = 'success';
        }
        
        // UPDATE
        if ($_POST['action'] === 'update') {
            $startTime = microtime(true);
            $stmt = $pdo->prepare("UPDATE `$tableName` SET name=?, type=?, host=?, dbName=?, username=?, password=?, port=? WHERE id=?");
            $stmt->execute([
                $_POST['name'],
                $_POST['type'],
                $_POST['host'],
                $_POST['dbName'],
                $_POST['username'],
                $_POST['password'],
                $_POST['port'],
                $_POST['id']
            ]);
            $operationTime = round((microtime(true) - $startTime) * 1000, 2);
            $operationType = 'UPDATE';
            $message = "Record updated successfully! ⏱️ {$operationTime}ms";
            $messageType = 'success';
        }
        
        // IMPORT
        if ($_POST['action'] === 'import' && isset($_FILES['jsonFile'])) {
            $startTime = microtime(true);
            $jsonContent = file_get_contents($_FILES['jsonFile']['tmp_name']);
            
            // Remove the markdown comments if present
            $jsonContent = preg_replace('/<!--.*?-->/s', '', $jsonContent);
            $jsonContent = preg_replace('/## .*?\n/', '', $jsonContent);
            $jsonContent = trim($jsonContent);
            
            $data = json_decode($jsonContent, true);
            
            if ($data && isset($data['connections'])) {
                $imported = 0;
                foreach ($data['connections'] as $conn) {
                    // Check if record exists
                    $check = $pdo->prepare("SELECT id FROM `$tableName` WHERE id = ?");
                    $check->execute([$conn['id']]);
                    
                    if ($check->rowCount() === 0) {
                        $stmt = $pdo->prepare("INSERT INTO `$tableName` (id, name, type, host, dbName, username, password, port, createdAt) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $createdAt = isset($conn['createdAt']) ? date('Y-m-d H:i:s', strtotime($conn['createdAt'])) : date('Y-m-d H:i:s');
                        $stmt->execute([
                            $conn['id'],
                            $conn['name'],
                            $conn['type'] ?? 'shared',
                            $conn['host'],
                            $conn['dbName'],
                            $conn['username'],
                            $conn['password'],
                            $conn['port'] ?? '3306',
                            $createdAt
                        ]);
                        $imported++;
                    }
                }
                $operationTime = round((microtime(true) - $startTime) * 1000, 2);
                $operationType = 'IMPORT';
                $message = "$imported records imported successfully! ⏱️ {$operationTime}ms";
                $messageType = 'success';
            } else {
                $message = 'Invalid JSON format!';
                $messageType = 'error';
            }
        }
    }
}

// ========================================
// COOL INSERT API ENDPOINT
// ========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cool_insert') {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    
    $rawText = $_POST['raw_text'] ?? '';
    
    if (empty(trim($rawText))) {
        echo json_encode(['success' => false, 'error' => 'No text provided']);
        exit;
    }
    
    // Parse credentials from raw text
    $credentials = parseDatabaseCredentials($rawText);
    
    if (empty($credentials)) {
        echo json_encode(['success' => false, 'error' => 'No valid database credentials found in the text']);
        exit;
    }
    
    $report = [
        'total_found' => count($credentials),
        'added' => [],
        'skipped' => [],
        'invalid' => []
    ];
    
    foreach ($credentials as $cred) {
        // Validate required fields
        if (empty($cred['host']) || empty($cred['dbName']) || empty($cred['username'])) {
            $report['invalid'][] = [
                'reason' => 'Missing required fields (host, dbName, or username)',
                'data' => $cred
            ];
            continue;
        }
        
        // Check if already exists (by host + dbName + username combination)
        $checkStmt = $pdo->prepare("SELECT id, name FROM `$tableName` WHERE host = ? AND dbName = ? AND username = ?");
        $checkStmt->execute([$cred['host'], $cred['dbName'], $cred['username']]);
        $existing = $checkStmt->fetch();
        
        if ($existing) {
            $report['skipped'][] = [
                'reason' => 'Already exists in database',
                'existing_id' => $existing['id'],
                'existing_name' => $existing['name'],
                'data' => $cred
            ];
            continue;
        }
        
        // Generate a nice name if not provided
        $name = generateConnectionName($cred);
        
        // Insert the new connection
        $id = time() . rand(100, 999);
        $insertStmt = $pdo->prepare("INSERT INTO `$tableName` (id, name, type, host, dbName, username, password, port, createdAt) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $insertStmt->execute([
            $id,
            $name,
            $cred['type'] ?? 'shared',
            $cred['host'],
            $cred['dbName'],
            $cred['username'],
            $cred['password'] ?? '',
            $cred['port'] ?? '3306'
        ]);
        
        $report['added'][] = [
            'id' => $id,
            'name' => $name,
            'data' => $cred
        ];
    }
    
    echo json_encode([
        'success' => true,
        'report' => $report,
        'summary' => [
            'found' => $report['total_found'],
            'added' => count($report['added']),
            'skipped' => count($report['skipped']),
            'invalid' => count($report['invalid'])
        ]
    ], JSON_PRETTY_PRINT);
    exit;
}

/**
 * Parse database credentials from raw/cluttered text
 * Supports various formats from Hostinger and other hosting providers
 */
function parseDatabaseCredentials($text) {
    $credentials = [];
    
    // Normalize line endings
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    
    // Split by potential separators (double newlines, dashes, equals signs patterns)
    $blocks = preg_split('/\n{2,}|={3,}|-{3,}|\*{3,}/', $text);
    
    // Also try to find credentials in the entire text as one block
    $blocks[] = $text;
    
    foreach ($blocks as $block) {
        $cred = extractCredentialsFromBlock($block);
        if ($cred && !empty($cred['host']) && !empty($cred['dbName']) && !empty($cred['username'])) {
            // Avoid duplicates
            $key = $cred['host'] . '|' . $cred['dbName'] . '|' . $cred['username'];
            if (!isset($credentials[$key])) {
                $credentials[$key] = $cred;
            }
        }
    }
    
    // Try alternative parsing: look for patterns throughout the entire text
    $altCreds = extractCredentialsAlternative($text);
    foreach ($altCreds as $cred) {
        if (!empty($cred['host']) && !empty($cred['dbName']) && !empty($cred['username'])) {
            $key = $cred['host'] . '|' . $cred['dbName'] . '|' . $cred['username'];
            if (!isset($credentials[$key])) {
                $credentials[$key] = $cred;
            }
        }
    }
    
    return array_values($credentials);
}

/**
 * Extract credentials from a text block
 */
function extractCredentialsFromBlock($block) {
    $cred = [
        'host' => '',
        'dbName' => '',
        'username' => '',
        'password' => '',
        'port' => '3306',
        'type' => 'shared'
    ];
    
    // Patterns for various credential formats
    $patterns = [
        // Host patterns
        'host' => [
            '/(?:host(?:name)?|server|mysql\s*host(?:name)?)\s*[:=]\s*["\']?([a-zA-Z0-9\.\-_]+)["\']?/i',
            '/(?:DB_HOST|DATABASE_HOST)\s*[:=]\s*["\']?([a-zA-Z0-9\.\-_]+)["\']?/i',
            '/(srv\d+\.hstgr\.io)/i', // Hostinger pattern
            '/(localhost|127\.0\.0\.1)/i',
            '/host["\']?\s*(?:=>|:)\s*["\']([^"\']+)["\']/i',
        ],
        // Database name patterns
        'dbName' => [
            '/(?:database(?:\s*name)?|db(?:name)?|mysql\s*database)\s*[:=]\s*["\']?([a-zA-Z0-9_]+)["\']?/i',
            '/(?:DB_NAME|DB_DATABASE|DATABASE_NAME)\s*[:=]\s*["\']?([a-zA-Z0-9_]+)["\']?/i',
            '/dbname["\']?\s*(?:=>|:)\s*["\']([^"\']+)["\']/i',
            '/(u\d+_[a-zA-Z0-9_]+)/i', // Hostinger pattern
        ],
        // Username patterns
        'username' => [
            '/(?:user(?:name)?|db\s*user(?:name)?|mysql\s*user(?:name)?)\s*[:=]\s*["\']?([a-zA-Z0-9_]+)["\']?/i',
            '/(?:DB_USER(?:NAME)?|DATABASE_USER(?:NAME)?)\s*[:=]\s*["\']?([a-zA-Z0-9_]+)["\']?/i',
            '/username["\']?\s*(?:=>|:)\s*["\']([^"\']+)["\']/i',
        ],
        // Password patterns
        'password' => [
            '/(?:pass(?:word)?|db\s*pass(?:word)?|mysql\s*pass(?:word)?)\s*[:=]\s*["\']?([^\s\n"\']+)["\']?/i',
            '/(?:DB_PASS(?:WORD)?|DATABASE_PASS(?:WORD)?)\s*[:=]\s*["\']?([^\s\n"\']+)["\']?/i',
            '/password["\']?\s*(?:=>|:)\s*["\']([^"\']+)["\']/i',
        ],
        // Port patterns
        'port' => [
            '/(?:port|db\s*port|mysql\s*port)\s*[:=]\s*["\']?(\d+)["\']?/i',
            '/(?:DB_PORT|DATABASE_PORT)\s*[:=]\s*["\']?(\d+)["\']?/i',
        ]
    ];
    
    foreach ($patterns as $field => $fieldPatterns) {
        foreach ($fieldPatterns as $pattern) {
            if (preg_match($pattern, $block, $matches)) {
                $value = trim($matches[1]);
                if (!empty($value) && empty($cred[$field])) {
                    $cred[$field] = $value;
                    break;
                }
            }
        }
    }
    
    // Detect hosting type
    if (stripos($cred['host'], 'hstgr') !== false || stripos($cred['host'], 'hostinger') !== false) {
        $cred['type'] = 'shared';
    } elseif ($cred['host'] === 'localhost' || $cred['host'] === '127.0.0.1') {
        $cred['type'] = 'local';
    } elseif (stripos($cred['host'], 'aws') !== false || stripos($cred['host'], 'rds') !== false) {
        $cred['type'] = 'cloud';
    }
    
    return $cred;
}

/**
 * Alternative extraction for multiple credentials in one text
 */
function extractCredentialsAlternative($text) {
    $results = [];
    
    // Find all Hostinger-style database names (u followed by numbers, underscore, then name)
    if (preg_match_all('/(u\d+_[a-zA-Z0-9_]+)/', $text, $dbMatches)) {
        $databases = array_unique($dbMatches[1]);
        
        // Find host
        $host = 'localhost';
        if (preg_match('/(srv\d+\.hstgr\.io)/i', $text, $hostMatch)) {
            $host = $hostMatch[1];
        }
        
        // Find passwords (look for strong passwords)
        $passwords = [];
        if (preg_match_all('/(?:pass(?:word)?|pwd)\s*[:=]\s*["\']?([^\s\n"\']{6,})["\']?/i', $text, $pwdMatches)) {
            $passwords = $pwdMatches[1];
        }
        
        foreach ($databases as $idx => $db) {
            // For Hostinger, username often matches database name or is similar
            $username = $db;
            
            // Check if there's a specific username pattern near this database
            $pattern = '/' . preg_quote($db, '/') . '[^}]*?user(?:name)?\s*[:=]\s*["\']?([a-zA-Z0-9_]+)/is';
            if (preg_match($pattern, $text, $userMatch)) {
                $username = $userMatch[1];
            }
            
            $results[] = [
                'host' => $host,
                'dbName' => $db,
                'username' => $username,
                'password' => $passwords[$idx] ?? ($passwords[0] ?? ''),
                'port' => '3306',
                'type' => 'shared'
            ];
        }
    }
    
    return $results;
}

/**
 * Generate a nice connection name from credentials
 */
function generateConnectionName($cred) {
    $dbName = $cred['dbName'] ?? '';
    
    // Remove common prefixes like u419999707_
    $cleanName = preg_replace('/^u\d+_/', '', $dbName);
    
    // Convert underscores and dashes to spaces
    $cleanName = str_replace(['_', '-'], ' ', $cleanName);
    
    // Title case
    $cleanName = ucwords(strtolower($cleanName));
    
    // If name is too short, use full dbName
    if (strlen($cleanName) < 3) {
        $cleanName = $dbName;
    }
    
    // Add suffix based on host
    $suffix = '';
    if (stripos($cred['host'], 'hstgr') !== false) {
        $suffix = ' (Hostinger)';
    } elseif ($cred['host'] === 'localhost' || $cred['host'] === '127.0.0.1') {
        $suffix = ' (Local)';
    } elseif (!empty($cred['host'])) {
        // Extract domain hint
        $hostParts = explode('.', $cred['host']);
        if (count($hostParts) > 1) {
            $suffix = ' (' . ucfirst($hostParts[0]) . ')';
        }
    }
    
    return trim($cleanName . $suffix);
}

// EXPORT - Returns JSON data for JavaScript to handle with file picker
if (isset($_GET['export'])) {
    $stmt = $pdo->query("SELECT * FROM `$tableName` ORDER BY createdAt DESC");
    $records = $stmt->fetchAll();
    
    $exportData = [
        'exported_at' => date('n/j/Y, g:i:s A'),
        'total_connections' => count($records),
        'connections' => []
    ];
    
    foreach ($records as $record) {
        $exportData['connections'][] = [
            'id' => $record['id'],
            'name' => $record['name'],
            'type' => $record['type'],
            'host' => $record['host'],
            'dbName' => $record['dbName'],
            'username' => $record['username'],
            'password' => $record['password'],
            'port' => $record['port'],
            'createdAt' => date('c', strtotime($record['createdAt']))
        ];
    }
    
    // Return JSON for JavaScript file picker (no download headers)
    header('Content-Type: application/json');
    echo json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

// Search
$search = isset($_GET['search']) ? $_GET['search'] : '';
$searchCondition = '';
$params = [];

if ($search) {
    $searchCondition = "WHERE name LIKE ? OR host LIKE ? OR dbName LIKE ? OR username LIKE ?";
    $searchTerm = "%$search%";
    $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
}

// Get records
$sql = "SELECT * FROM `$tableName` $searchCondition ORDER BY createdAt DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll();

// Get record for editing
$editRecord = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM `$tableName` WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editRecord = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Prompt Databases - CRUD</title>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-primary: #0a0e17;
            --bg-secondary: #111827;
            --bg-card: #1a2236;
            --bg-input: #0d1321;
            --accent-primary: #00d4aa;
            --accent-secondary: #7c3aed;
            --accent-warning: #f59e0b;
            --accent-danger: #ef4444;
            --text-primary: #f1f5f9;
            --text-secondary: #94a3b8;
            --text-muted: #64748b;
            --border-color: #2d3a4f;
            --glow-primary: rgba(0, 212, 170, 0.3);
            --glow-secondary: rgba(124, 58, 237, 0.3);
        }
        
        /* ========================================
           DATABASE CONNECTION TOGGLE SWITCH
           ======================================== */
        .db-toggle-container {
            position: fixed;
            top: 12px;
            right: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: rgba(0, 0, 0, 0.75);
            border-radius: 25px;
            z-index: 99999;
            opacity: 0.35;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }
        
        .db-toggle-container:hover {
            opacity: 1;
            border-color: rgba(255, 255, 255, 0.2);
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.4);
        }
        
        .toggle-label {
            font-size: 14px;
            transition: all 0.3s ease;
            opacity: 0.5;
        }
        
        .toggle-label.local {
            color: #22c55e;
        }
        
        .toggle-label.remote {
            color: #3b82f6;
        }
        
        /* Active state for labels */
        .db-toggle-container[data-active="local"] .toggle-label.local,
        .db-toggle-container[data-active="remote"] .toggle-label.remote {
            opacity: 1;
            transform: scale(1.1);
        }
        
        .db-toggle {
            position: relative;
            width: 42px;
            height: 22px;
            cursor: pointer;
        }
        
        .db-toggle input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, #22c55e 0%, #15803d 100%);
            border-radius: 22px;
            transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 3px;
            bottom: 3px;
            background: white;
            border-radius: 50%;
            transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
        }
        
        .db-toggle input:checked + .toggle-slider {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        }
        
        .db-toggle input:checked + .toggle-slider:before {
            transform: translateX(20px);
        }
        
        .db-toggle input:disabled + .toggle-slider {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .connection-status {
            font-size: 10px;
            color: var(--text-secondary);
            padding-left: 8px;
            border-left: 1px solid rgba(255, 255, 255, 0.1);
            margin-left: 4px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .fallback-badge {
            background: var(--accent-warning);
            color: #000;
            padding: 1px 5px;
            border-radius: 8px;
            font-size: 8px;
            font-weight: 600;
            animation: pulse-badge 2s infinite;
        }
        
        @keyframes pulse-badge {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }
        
        /* ========================================
           SPEED MONITOR BOX
           ======================================== */
        .speed-monitor {
            position: fixed;
            top: 12px;
            right: 280px;
            display: flex;
            flex-direction: column;
            gap: 4px;
            padding: 8px 12px;
            background: rgba(0, 0, 0, 0.85);
            border-radius: 12px;
            z-index: 99999;
            opacity: 0.6;
            transition: all 0.3s ease;
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            min-width: 180px;
            font-family: 'JetBrains Mono', 'Consolas', monospace;
        }
        
        .speed-monitor:hover {
            opacity: 1;
            border-color: rgba(255, 255, 255, 0.2);
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.4);
        }
        
        .speed-monitor-title {
            font-size: 9px;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 2px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .speed-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 3px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .speed-row:last-child {
            border-bottom: none;
        }
        
        .speed-label {
            font-size: 10px;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .speed-label .op-type {
            font-size: 8px;
            padding: 1px 4px;
            border-radius: 4px;
            background: rgba(255, 255, 255, 0.1);
            text-transform: uppercase;
        }
        
        .speed-value {
            font-size: 12px;
            font-weight: 600;
            font-family: 'JetBrains Mono', monospace;
        }
        
        .speed-value.local {
            color: var(--accent-success);
        }
        
        .speed-value.remote {
            color: var(--accent-secondary);
        }
        
        .speed-comparison {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 6px 8px;
            margin-top: 4px;
            border-radius: 8px;
            font-size: 10px;
            font-weight: 600;
        }
        
        .speed-comparison.faster {
            background: rgba(34, 197, 94, 0.15);
            color: var(--accent-success);
            border: 1px solid rgba(34, 197, 94, 0.3);
        }
        
        .speed-comparison.slower {
            background: rgba(239, 68, 68, 0.15);
            color: var(--accent-error);
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        
        .speed-comparison.equal {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-secondary);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .speed-diff {
            font-size: 11px;
            font-weight: bold;
        }
        
        .speed-winner {
            font-size: 14px;
        }
        
        .no-data {
            font-size: 10px;
            color: var(--text-muted);
            text-align: center;
            padding: 8px;
            font-style: italic;
        }
        
        @keyframes speed-pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .speed-new {
            animation: speed-pulse 0.5s ease;
        }
        
        /* Connection info in header */
        .connection-info {
            margin-top: 12px;
            font-size: 0.85rem;
            color: var(--text-secondary);
        }
        
        .connection-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 15px;
            font-weight: 600;
            font-size: 0.8rem;
        }
        
        .connection-badge.localhost {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.2) 0%, rgba(21, 128, 61, 0.2) 100%);
            color: #22c55e;
            border: 1px solid rgba(34, 197, 94, 0.3);
        }
        
        .connection-badge.remote {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.2) 0%, rgba(29, 78, 216, 0.2) 100%);
            color: #3b82f6;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }
        
        .fallback-notice {
            font-size: 0.75rem;
            color: var(--accent-warning);
            margin-left: 8px;
        }
        
        /* Toast for connection switch */
        .db-switch-toast {
            position: fixed;
            top: 60px;
            right: 12px;
            padding: 12px 20px;
            background: rgba(0, 0, 0, 0.9);
            border-radius: 10px;
            z-index: 99998;
            display: none;
            align-items: center;
            gap: 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            animation: slideInRight 0.3s ease;
            max-width: 300px;
        }
        
        .db-switch-toast.success {
            border-color: rgba(34, 197, 94, 0.5);
        }
        
        .db-switch-toast.error {
            border-color: rgba(239, 68, 68, 0.5);
        }
        
        .db-switch-toast .toast-icon {
            font-size: 20px;
        }
        
        .db-switch-toast .toast-message {
            font-size: 12px;
            color: var(--text-primary);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Outfit', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            background-image: 
                radial-gradient(ellipse at 20% 20%, rgba(124, 58, 237, 0.08) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 80%, rgba(0, 212, 170, 0.08) 0%, transparent 50%),
                linear-gradient(180deg, var(--bg-primary) 0%, var(--bg-secondary) 100%);
        }
        
        .container {
            max-width: 1650px;
            margin: 0 auto;
            padding: 30px 25px;
        }
        
        /* Header */
        .header {
            text-align: center;
            margin-bottom: 40px;
            padding: 30px;
            background: linear-gradient(135deg, var(--bg-card) 0%, var(--bg-secondary) 100%);
            border-radius: 20px;
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
        }
        
        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--accent-primary), var(--accent-secondary), var(--accent-primary));
        }
        
        .header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--accent-primary) 0%, var(--accent-secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
        }
        
        .header p {
            color: var(--text-secondary);
            font-size: 1.1rem;
        }
        
        /* Message Alert */
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .alert-success {
            background: rgba(0, 212, 170, 0.15);
            border: 1px solid var(--accent-primary);
            color: var(--accent-primary);
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid var(--accent-danger);
            color: var(--accent-danger);
        }
        
        /* Toolbar */
        .toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 30px;
            padding: 20px;
            background: var(--bg-card);
            border-radius: 16px;
            border: 1px solid var(--border-color);
        }
        
        .search-box {
            flex: 1;
            min-width: 250px;
            display: flex;
            gap: 10px;
        }
        
        .search-box input {
            flex: 1;
            padding: 12px 18px;
            background: var(--bg-input);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            color: var(--text-primary);
            font-size: 0.95rem;
            font-family: 'JetBrains Mono', monospace;
            transition: all 0.3s ease;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: var(--accent-primary);
            box-shadow: 0 0 0 3px var(--glow-primary);
        }
        
        .toolbar-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            font-family: 'Outfit', sans-serif;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--accent-primary) 0%, #00b894 100%);
            color: var(--bg-primary);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px var(--glow-primary);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, var(--accent-secondary) 0%, #6d28d9 100%);
            color: white;
        }
        
        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px var(--glow-secondary);
        }
        
        .btn-warning {
            background: linear-gradient(135deg, var(--accent-warning) 0%, #d97706 100%);
            color: var(--bg-primary);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, var(--accent-danger) 0%, #dc2626 100%);
            color: white;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.8rem;
            white-space: nowrap;
        }
        
        /* Form Card */
        .form-card {
            background: var(--bg-card);
            border-radius: 16px;
            border: 1px solid var(--border-color);
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .form-card h2 {
            font-size: 1.4rem;
            margin-bottom: 20px;
            color: var(--accent-primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 18px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        
        .form-group label {
            font-size: 0.9rem;
            color: var(--text-secondary);
            font-weight: 500;
        }
        
        .form-group input, .form-group select {
            padding: 12px 16px;
            background: var(--bg-input);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            color: var(--text-primary);
            font-size: 0.95rem;
            font-family: 'JetBrains Mono', monospace;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: var(--accent-primary);
            box-shadow: 0 0 0 3px var(--glow-primary);
        }
        
        .form-actions {
            margin-top: 20px;
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }
        
        /* Table */
        .table-container {
            background: var(--bg-card);
            border-radius: 16px;
            border: 1px solid var(--border-color);
            overflow: hidden;
        }
        
        .table-header {
            padding: 20px 25px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .table-header h2 {
            font-size: 1.3rem;
            color: var(--text-primary);
        }
        
        .record-count {
            background: var(--accent-primary);
            color: var(--bg-primary);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .table-wrapper {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: auto;
        }
        
        th, td {
            padding: 12px 14px;
            text-align: left;
            font-size: 0.85rem;
        }
        
        th {
            background: var(--bg-secondary);
            color: var(--text-secondary);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.8rem;
            white-space: nowrap;
        }
        
        tr {
            border-bottom: 1px solid var(--border-color);
            transition: background 0.2s ease;
        }
        
        tr:hover {
            background: rgba(0, 212, 170, 0.05);
        }
        
        tr:last-child {
            border-bottom: none;
        }
        
        td {
            color: var(--text-primary);
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.85rem;
        }
        
        .td-name {
            color: var(--accent-primary);
            font-weight: 600;
        }
        
        .td-type {
            display: inline-block;
            padding: 4px 12px;
            background: rgba(124, 58, 237, 0.2);
            color: var(--accent-secondary);
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .td-password {
            color: var(--text-muted);
            font-style: italic;
        }
        
        .td-actions {
            display: flex;
            gap: 6px;
            flex-wrap: nowrap;
        }
        
        /* Import Modal */
        .import-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }
        
        .import-section h3 {
            font-size: 1.1rem;
            margin-bottom: 15px;
            color: var(--accent-secondary);
        }
        
        .file-input-wrapper {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        
        .file-input-wrapper input[type="file"] {
            flex: 1;
            padding: 12px;
            background: var(--bg-input);
            border: 2px dashed var(--border-color);
            border-radius: 10px;
            color: var(--text-primary);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .file-input-wrapper input[type="file"]:hover {
            border-color: var(--accent-secondary);
        }
        
        /* Custom Checkbox */
        .checkbox-wrapper {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            position: relative;
            cursor: pointer;
            user-select: none;
            width: 22px;
            height: 22px;
        }
        
        .checkbox-wrapper input {
            position: absolute;
            opacity: 0;
            cursor: pointer;
            height: 0;
            width: 0;
        }
        
        .checkmark {
            position: absolute;
            top: 0;
            left: 0;
            height: 22px;
            width: 22px;
            background: var(--bg-input);
            border: 2px solid var(--border-color);
            border-radius: 6px;
            transition: all 0.2s ease;
        }
        
        .checkbox-wrapper:hover .checkmark {
            border-color: var(--accent-primary);
            background: rgba(0, 212, 170, 0.1);
        }
        
        .checkbox-wrapper input:checked ~ .checkmark {
            background: var(--accent-primary);
            border-color: var(--accent-primary);
        }
        
        .checkmark:after {
            content: "";
            position: absolute;
            display: none;
        }
        
        .checkbox-wrapper input:checked ~ .checkmark:after {
            display: block;
        }
        
        .checkbox-wrapper .checkmark:after {
            left: 6px;
            top: 2px;
            width: 6px;
            height: 11px;
            border: solid var(--bg-primary);
            border-width: 0 2.5px 2.5px 0;
            transform: rotate(45deg);
        }
        
        /* Mass Actions Bar */
        .mass-actions-bar {
            padding: 15px 25px;
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.15) 0%, rgba(220, 38, 38, 0.1) 100%);
            border-bottom: 1px solid rgba(239, 68, 68, 0.3);
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .selected-count {
            background: rgba(239, 68, 68, 0.2);
            color: var(--accent-danger);
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        /* Selected Row Highlight */
        .data-row.selected {
            background: rgba(0, 212, 170, 0.1) !important;
            border-left: 3px solid var(--accent-primary);
        }
        
        .data-row.selected td:first-child {
            padding-left: 15px;
        }
        
        /* Connection Test Button */
        .conn-test-btn {
            position: relative;
            width: 38px;
            height: 38px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: linear-gradient(135deg, rgba(100, 116, 139, 0.3) 0%, rgba(71, 85, 105, 0.3) 100%);
            border: 1px solid rgba(148, 163, 184, 0.2);
            color: var(--text-secondary);
        }
        
        .conn-test-btn:hover {
            transform: translateY(-2px) scale(1.05);
            background: linear-gradient(135deg, rgba(124, 58, 237, 0.4) 0%, rgba(99, 102, 241, 0.4) 100%);
            border-color: var(--accent-secondary);
            color: white;
            box-shadow: 0 4px 15px rgba(124, 58, 237, 0.3);
        }
        
        .conn-test-btn:active {
            transform: translateY(0) scale(0.98);
        }
        
        /* Testing State */
        .conn-test-btn.testing {
            background: linear-gradient(135deg, rgba(124, 58, 237, 0.5) 0%, rgba(99, 102, 241, 0.5) 100%);
            border-color: var(--accent-secondary);
            pointer-events: none;
        }
        
        .conn-test-btn.testing .btn-icon {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        /* Success State */
        .conn-test-btn.success {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            border-color: #22c55e;
            color: white;
            box-shadow: 
                0 0 20px rgba(34, 197, 94, 0.5),
                0 0 40px rgba(34, 197, 94, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
            animation: successPulse 2s ease-in-out infinite;
        }
        
        .conn-test-btn.success:hover {
            background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
            box-shadow: 
                0 0 25px rgba(34, 197, 94, 0.6),
                0 0 50px rgba(34, 197, 94, 0.4),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }
        
        @keyframes successPulse {
            0%, 100% { 
                box-shadow: 
                    0 0 20px rgba(34, 197, 94, 0.5),
                    0 0 40px rgba(34, 197, 94, 0.3),
                    inset 0 1px 0 rgba(255, 255, 255, 0.2);
            }
            50% { 
                box-shadow: 
                    0 0 25px rgba(34, 197, 94, 0.7),
                    0 0 50px rgba(34, 197, 94, 0.4),
                    inset 0 1px 0 rgba(255, 255, 255, 0.3);
            }
        }
        
        /* Error State */
        .conn-test-btn.error {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            border-color: #ef4444;
            color: white;
            box-shadow: 
                0 0 20px rgba(239, 68, 68, 0.5),
                0 0 40px rgba(239, 68, 68, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
            animation: errorShake 0.5s ease-in-out;
        }
        
        .conn-test-btn.error:hover {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            box-shadow: 
                0 0 25px rgba(239, 68, 68, 0.6),
                0 0 50px rgba(239, 68, 68, 0.4);
        }
        
        @keyframes errorShake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-3px); }
            20%, 40%, 60%, 80% { transform: translateX(3px); }
        }
        
        /* Tooltip hidden */
        .conn-test-btn .conn-tooltip {
            display: none;
        }
        
        /* Test All Button */
        .btn-test-all {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: white;
        }
        
        .btn-test-all:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.4);
        }
        
        /* ========================================
           COOL INSERT BUTTON STYLES
           ======================================== */
        .btn-cool-insert {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            background-size: 200% 200%;
            animation: coolGradient 3s ease infinite;
            color: white;
            border: none;
            position: relative;
            overflow: hidden;
            padding: 12px 20px !important;
            font-weight: 600;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        @keyframes coolGradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .btn-cool-insert:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.6), 0 0 30px rgba(240, 147, 251, 0.3);
        }
        
        .btn-cool-insert::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(
                45deg,
                transparent 30%,
                rgba(255, 255, 255, 0.1) 50%,
                transparent 70%
            );
            animation: coolShine 3s infinite;
        }
        
        @keyframes coolShine {
            0% { transform: translateX(-100%) rotate(45deg); }
            100% { transform: translateX(100%) rotate(45deg); }
        }
        
        .cool-icon {
            font-size: 1.1rem;
            margin-right: 4px;
            animation: coolBounce 2s infinite;
        }
        
        @keyframes coolBounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-2px); }
        }
        
        .cool-sparkle {
            font-size: 0.9rem;
            margin-left: 4px;
            animation: sparkle 1.5s infinite;
        }
        
        @keyframes sparkle {
            0%, 100% { opacity: 1; transform: scale(1) rotate(0deg); }
            50% { opacity: 0.6; transform: scale(1.2) rotate(15deg); }
        }
        
        /* ========================================
           COOL INSERT MODAL STYLES
           ======================================== */
        .cool-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(10px);
            z-index: 100000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            animation: fadeIn 0.3s ease;
        }
        
        .cool-modal-overlay.active {
            display: flex;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .cool-modal {
            background: linear-gradient(145deg, #1a1a2e 0%, #16213e 50%, #0f0f23 100%);
            border-radius: 24px;
            width: 100%;
            max-width: 800px;
            max-height: 90vh;
            overflow: hidden;
            border: 1px solid rgba(102, 126, 234, 0.3);
            box-shadow: 
                0 25px 80px rgba(0, 0, 0, 0.5),
                0 0 60px rgba(102, 126, 234, 0.15),
                inset 0 1px 0 rgba(255, 255, 255, 0.05);
            animation: coolModalSlide 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        
        @keyframes coolModalSlide {
            from { 
                opacity: 0;
                transform: translateY(-50px) scale(0.9);
            }
            to { 
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        .cool-modal-header {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.2) 0%, rgba(240, 147, 251, 0.2) 100%);
            padding: 24px 30px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .cool-modal-title {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.5rem;
            font-weight: 700;
            color: #fff;
        }
        
        .cool-modal-title-icon {
            font-size: 2rem;
            animation: coolBounce 2s infinite;
        }
        
        .cool-modal-subtitle {
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-top: 4px;
        }
        
        .cool-modal-close {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: none;
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-secondary);
            font-size: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .cool-modal-close:hover {
            background: rgba(239, 68, 68, 0.3);
            color: #ef4444;
            transform: rotate(90deg);
        }
        
        .cool-modal-body {
            padding: 30px;
            overflow-y: auto;
            max-height: 60vh;
        }
        
        .cool-textarea-wrapper {
            position: relative;
        }
        
        .cool-textarea {
            width: 100%;
            min-height: 250px;
            padding: 20px;
            background: rgba(0, 0, 0, 0.4);
            border: 2px solid rgba(102, 126, 234, 0.3);
            border-radius: 16px;
            color: var(--text-primary);
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.9rem;
            line-height: 1.6;
            resize: vertical;
            transition: all 0.3s ease;
        }
        
        .cool-textarea:focus {
            outline: none;
            border-color: rgba(102, 126, 234, 0.6);
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.15), 0 0 30px rgba(102, 126, 234, 0.1);
        }
        
        .cool-textarea::placeholder {
            color: var(--text-muted);
            font-style: italic;
        }
        
        .cool-helper-text {
            margin-top: 12px;
            padding: 15px;
            background: rgba(0, 212, 170, 0.1);
            border-radius: 12px;
            border: 1px solid rgba(0, 212, 170, 0.2);
        }
        
        .cool-helper-text h4 {
            color: var(--accent-primary);
            font-size: 0.9rem;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .cool-helper-text ul {
            margin: 0;
            padding-left: 20px;
            color: var(--text-secondary);
            font-size: 0.85rem;
        }
        
        .cool-helper-text li {
            margin-bottom: 4px;
        }
        
        .cool-modal-footer {
            padding: 20px 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            background: rgba(0, 0, 0, 0.2);
        }
        
        .cool-modal-actions {
            display: flex;
            gap: 12px;
        }
        
        .cool-btn {
            padding: 14px 28px;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .cool-btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        .cool-btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(102, 126, 234, 0.6);
        }
        
        .cool-btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .cool-btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-secondary);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .cool-btn-secondary:hover {
            background: rgba(255, 255, 255, 0.15);
            color: var(--text-primary);
        }
        
        .cool-char-count {
            font-size: 0.85rem;
            color: var(--text-muted);
            font-family: 'JetBrains Mono', monospace;
        }
        
        /* ========================================
           COOL INSERT REPORT STYLES
           ======================================== */
        .cool-report {
            margin-top: 20px;
            animation: fadeIn 0.5s ease;
        }
        
        .cool-report-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .cool-report-stat {
            padding: 20px;
            border-radius: 16px;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .cool-report-stat:hover {
            transform: translateY(-3px);
        }
        
        .cool-report-stat.found {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.2) 0%, rgba(102, 126, 234, 0.1) 100%);
            border: 1px solid rgba(102, 126, 234, 0.3);
        }
        
        .cool-report-stat.added {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.2) 0%, rgba(34, 197, 94, 0.1) 100%);
            border: 1px solid rgba(34, 197, 94, 0.3);
        }
        
        .cool-report-stat.skipped {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.2) 0%, rgba(245, 158, 11, 0.1) 100%);
            border: 1px solid rgba(245, 158, 11, 0.3);
        }
        
        .cool-report-stat.invalid {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.2) 0%, rgba(239, 68, 68, 0.1) 100%);
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        
        .cool-report-stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            line-height: 1;
            margin-bottom: 8px;
        }
        
        .cool-report-stat.found .cool-report-stat-value { color: #667eea; }
        .cool-report-stat.added .cool-report-stat-value { color: #22c55e; }
        .cool-report-stat.skipped .cool-report-stat-value { color: #f59e0b; }
        .cool-report-stat.invalid .cool-report-stat-value { color: #ef4444; }
        
        .cool-report-stat-label {
            font-size: 0.85rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .cool-report-details {
            margin-top: 20px;
        }
        
        .cool-report-section {
            margin-bottom: 20px;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .cool-report-section-header {
            padding: 15px 20px;
            background: rgba(255, 255, 255, 0.05);
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .cool-report-section-header:hover {
            background: rgba(255, 255, 255, 0.08);
        }
        
        .cool-report-section-icon {
            font-size: 1.2rem;
        }
        
        .cool-report-section-title {
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .cool-report-section-count {
            margin-left: auto;
            background: rgba(255, 255, 255, 0.1);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            color: var(--text-secondary);
        }
        
        .cool-report-section-body {
            max-height: 200px;
            overflow-y: auto;
        }
        
        .cool-report-item {
            padding: 12px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            display: flex;
            align-items: center;
            gap: 15px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.85rem;
        }
        
        .cool-report-item:last-child {
            border-bottom: none;
        }
        
        .cool-report-item-name {
            color: var(--accent-primary);
            font-weight: 600;
            min-width: 150px;
        }
        
        .cool-report-item-info {
            color: var(--text-secondary);
        }
        
        .cool-report-item-reason {
            font-size: 0.8rem;
            color: var(--text-muted);
            font-style: italic;
        }
        
        .cool-loading {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            padding: 40px;
            color: var(--text-secondary);
        }
        
        .cool-loading-spinner {
            width: 30px;
            height: 30px;
            border: 3px solid rgba(102, 126, 234, 0.2);
            border-top-color: #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
        }
        
        .empty-state svg {
            width: 80px;
            height: 80px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .empty-state p {
            font-size: 1.1rem;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .header h1 {
                font-size: 1.8rem;
            }
            
            .toolbar {
                flex-direction: column;
            }
            
            .search-box {
                width: 100%;
            }
            
            .toolbar-buttons {
                width: 100%;
                justify-content: center;
            }
            
            .td-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Database Connection Toggle Switch -->
    <div class="db-toggle-container" id="dbToggleContainer" title="Database Connection: Local (faster on server) / Remote (anywhere)">
        <span class="toggle-label local" id="labelLocal">🖥️</span>
        <label class="db-toggle">
            <input type="checkbox" id="dbConnectionToggle" onchange="switchDbConnection(this.checked)" <?php echo ($connectionType === 'remote') ? 'checked' : ''; ?>>
            <span class="toggle-slider"></span>
        </label>
        <span class="toggle-label remote" id="labelRemote">🌐</span>
        <div class="connection-status" id="connectionStatus">
            <?php echo ($connectionType === 'localhost') ? '🖥️ Local' : '🌐 Remote'; ?>
            <?php if ($connectionFallback): ?>
                <span class="fallback-badge">⚡ Auto</span>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Speed Monitor Box -->
    <div class="speed-monitor" id="speedMonitor" title="Database Operation Speed Comparison">
        <div class="speed-monitor-title">
            <span>⚡</span> Speed Monitor
        </div>
        <div id="speedContent">
            <div class="no-data">Perform an action to see speed...</div>
        </div>
    </div>
    
    <!-- Pass operation data to JavaScript -->
    <?php if ($operationTime > 0): ?>
    <script>
        // Store latest operation data
        window.latestOperation = {
            time: <?php echo $operationTime; ?>,
            type: '<?php echo $operationType; ?>',
            connection: '<?php echo $connectionType; ?>',
            timestamp: Date.now()
        };
    </script>
    <?php endif; ?>

    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>🗄️ Report Prompt Databases</h1>
            <p>Database Connection Manager - Full CRUD Operations</p>
            <div class="connection-info">
                Connected via: <span class="connection-badge <?php echo $connectionType; ?>"><?php echo ($connectionType === 'localhost') ? '🖥️ Localhost' : '🌐 Remote'; ?></span>
                <?php if ($connectionFallback): ?>
                    <span class="fallback-notice">(auto-switched)</span>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Alert Message -->
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
            <?php echo $messageType === 'success' ? '✅' : '❌'; ?>
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>
        
        <!-- Toolbar -->
        <div class="toolbar">
            <form class="search-box" method="GET">
                <input type="text" name="search" placeholder="🔍 Search by name, host, database, or username..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if ($search): ?>
                <a href="?" class="btn btn-secondary">Clear</a>
                <?php endif; ?>
            </form>
            <div class="toolbar-buttons">
                <button type="button" class="btn btn-cool-insert" onclick="openCoolInsert()" title="Smart bulk insert from text">
                    <span class="cool-icon">❄️</span>
                    <span class="cool-text">Cool Insert</span>
                    <span class="cool-sparkle">✨</span>
                </button>
                <button type="button" class="btn btn-test-all" onclick="testAllConnections()" title="Test all database connections">🔌 Test All</button>
                <button type="button" class="btn btn-warning" onclick="exportWithFilePicker()">📤 Export JSON</button>
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('addForm').scrollIntoView({behavior: 'smooth'})">➕ Add New</button>
            </div>
        </div>
        
        <!-- Add/Edit Form -->
        <div class="form-card" id="addForm">
            <h2><?php echo $editRecord ? '✏️ Edit Connection' : '➕ Add New Connection'; ?></h2>
            <form method="POST">
                <input type="hidden" name="action" value="<?php echo $editRecord ? 'update' : 'add'; ?>">
                <?php if ($editRecord): ?>
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($editRecord['id']); ?>">
                <?php endif; ?>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Connection Name</label>
                        <input type="text" name="name" required value="<?php echo $editRecord ? htmlspecialchars($editRecord['name']) : ''; ?>" placeholder="My Database">
                    </div>
                    <div class="form-group">
                        <label>Type</label>
                        <select name="type">
                            <option value="shared" <?php echo ($editRecord && $editRecord['type'] === 'shared') ? 'selected' : ''; ?>>Shared Hosting</option>
                            <option value="vps" <?php echo ($editRecord && $editRecord['type'] === 'vps') ? 'selected' : ''; ?>>VPS</option>
                            <option value="dedicated" <?php echo ($editRecord && $editRecord['type'] === 'dedicated') ? 'selected' : ''; ?>>Dedicated</option>
                            <option value="cloud" <?php echo ($editRecord && $editRecord['type'] === 'cloud') ? 'selected' : ''; ?>>Cloud</option>
                            <option value="local" <?php echo ($editRecord && $editRecord['type'] === 'local') ? 'selected' : ''; ?>>Local</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Host</label>
                        <input type="text" name="host" required value="<?php echo $editRecord ? htmlspecialchars($editRecord['host']) : ''; ?>" placeholder="localhost">
                    </div>
                    <div class="form-group">
                        <label>Database Name</label>
                        <input type="text" name="dbName" required value="<?php echo $editRecord ? htmlspecialchars($editRecord['dbName']) : ''; ?>" placeholder="my_database">
                    </div>
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" required value="<?php echo $editRecord ? htmlspecialchars($editRecord['username']) : ''; ?>" placeholder="db_user">
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="text" name="password" required value="<?php echo $editRecord ? htmlspecialchars($editRecord['password']) : ''; ?>" placeholder="********">
                    </div>
                    <div class="form-group">
                        <label>Port</label>
                        <input type="text" name="port" value="<?php echo $editRecord ? htmlspecialchars($editRecord['port']) : '3306'; ?>" placeholder="3306">
                    </div>
                </div>
                
                <div class="form-actions">
                    <?php if ($editRecord): ?>
                    <a href="?" class="btn btn-secondary">Cancel</a>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary"><?php echo $editRecord ? '💾 Update' : '➕ Add Connection'; ?></button>
                </div>
            </form>
            
            <!-- Import Section -->
            <div class="import-section">
                <h3>📥 Import from JSON</h3>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="import">
                    <div class="file-input-wrapper">
                        <input type="file" name="jsonFile" accept=".json" required>
                        <button type="submit" class="btn btn-secondary">Import</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Data Table -->
        <div class="table-container">
            <div class="table-header">
                <h2>📋 Database Connections</h2>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <span class="record-count"><?php echo count($records); ?> Records</span>
                    <?php if (count($records) > 0): ?>
                    <span id="selectedCount" class="selected-count" style="display: none;">0 selected</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (count($records) > 0): ?>
            <!-- Mass Actions Bar -->
            <div id="massActionsBar" class="mass-actions-bar" style="display: none;">
                <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                    <span style="color: var(--text-secondary);">
                        <span id="massSelectedCount">0</span> item(s) selected
                    </span>
                    <button type="button" class="btn btn-danger btn-sm" onclick="deleteSelected()">
                        🗑️ Delete Selected
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="clearSelection()">
                        ✖️ Clear Selection
                    </button>
                </div>
            </div>
            
            <form id="massDeleteForm" method="POST">
                <input type="hidden" name="action" value="mass_delete">
                <div class="table-wrapper">
                    <table>
<thead>
                                            <tr>
                                                <th style="width: 50px; text-align: center;">
                                                    <label class="checkbox-wrapper" title="Select All">
                                                        <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)">
                                                        <span class="checkmark"></span>
                                                    </label>
                                                </th>
                                                <th>ID</th>
                                                <th>Name</th>
                                                <th>Type</th>
                                                <th>Host</th>
                                                <th>Database</th>
                                                <th>Username</th>
                                                <th>Password</th>
                                                <th>Port</th>
                                                <th>Created</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                        <tbody>
<?php foreach ($records as $record): ?>
                                            <tr id="row_<?php echo $record['id']; ?>" class="data-row">
                                                <td style="text-align: center;">
                                                    <label class="checkbox-wrapper">
                                                        <input type="checkbox" name="selected_ids[]" value="<?php echo htmlspecialchars($record['id']); ?>" class="row-checkbox" onchange="updateSelection()">
                                                        <span class="checkmark"></span>
                                                    </label>
                                                </td>
                                                <td><?php echo htmlspecialchars(substr($record['id'], -8)); ?></td>
                                                <td class="td-name"><?php echo htmlspecialchars($record['name']); ?></td>
                                                <td><span class="td-type"><?php echo htmlspecialchars($record['type']); ?></span></td>
                                                <td><?php echo htmlspecialchars($record['host']); ?></td>
                                                <td><?php echo htmlspecialchars($record['dbName']); ?></td>
                                                <td><?php echo htmlspecialchars($record['username']); ?></td>
                                                <td class="td-password">••••••••</td>
                                                <td><?php echo htmlspecialchars($record['port']); ?></td>
                                                <td><?php echo date('M j, Y', strtotime($record['createdAt'])); ?></td>
                                                <td class="td-actions">
                                                    <a href="?edit=<?php echo $record['id']; ?>" class="btn btn-primary btn-sm">✏️ Edit</a>
                                                    <a href="?delete=<?php echo $record['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this connection?')">🗑️ Delete</a>
                                                    <button type="button" 
                                                            class="conn-test-btn" 
                                                            id="testBtn_<?php echo $record['id']; ?>"
                                                            onclick="testConnection('<?php echo $record['id']; ?>')"
                                                            title="Test connection">
                                                        <span class="btn-icon">🔌</span>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </form>
            <?php else: ?>
            <div class="empty-state">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                </svg>
                <p>No database connections found. Add your first connection above!</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

<!-- Export Status Toast -->
<div id="exportToast" style="position: fixed; top: 20px; right: 20px; padding: 16px 24px; background: linear-gradient(135deg, var(--bg-card) 0%, var(--bg-secondary) 100%); border: 1px solid var(--border-color); border-radius: 12px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4); z-index: 10000; display: none; min-width: 300px;">
    <div id="exportToastContent" style="display: flex; align-items: center; gap: 12px; color: var(--text-primary);"></div>
</div>

<!-- Cool Insert Modal -->
<div class="cool-modal-overlay" id="coolInsertModal">
    <div class="cool-modal">
        <div class="cool-modal-header">
            <div>
                <div class="cool-modal-title">
                    <span class="cool-modal-title-icon">❄️</span>
                    <span>Cool Insert</span>
                    <span style="font-size: 1rem;">✨</span>
                </div>
                <div class="cool-modal-subtitle">Paste your text with database credentials - we'll extract them smartly!</div>
            </div>
            <button class="cool-modal-close" onclick="closeCoolInsert()" title="Close">×</button>
        </div>
        
        <div class="cool-modal-body" id="coolModalBody">
            <!-- Input View -->
            <div id="coolInputView">
                <div class="cool-textarea-wrapper">
                    <textarea 
                        class="cool-textarea" 
                        id="coolTextarea" 
                        placeholder="Paste your database credentials here...

Example formats supported:
• MySQL hostname: srv1788.hstgr.io
• Database name: u419999707_mydb
• Username: u419999707_user
• Password: MyP@ssw0rd!
• Port: 3306

Or PHP config style:
'host' => 'localhost',
'dbname' => 'my_database',
'username' => 'root',
'password' => 'secret'

Paste any format - we'll figure it out! 🎯"
                        oninput="updateCharCount()"></textarea>
                </div>
                
                <div class="cool-helper-text">
                    <h4>💡 Tips for best results:</h4>
                    <ul>
                        <li>Paste hosting panel info, config files, or any text containing credentials</li>
                        <li>Multiple connections can be detected from one paste</li>
                        <li>Supports Hostinger, cPanel, Laravel, and standard formats</li>
                        <li>Incomplete credentials will be skipped automatically</li>
                    </ul>
                </div>
            </div>
            
            <!-- Loading View -->
            <div id="coolLoadingView" style="display: none;">
                <div class="cool-loading">
                    <div class="cool-loading-spinner"></div>
                    <span>Analyzing and extracting credentials...</span>
                </div>
            </div>
            
            <!-- Report View -->
            <div id="coolReportView" style="display: none;">
                <div class="cool-report" id="coolReportContent">
                    <!-- Report will be inserted here -->
                </div>
            </div>
        </div>
        
        <div class="cool-modal-footer">
            <span class="cool-char-count" id="coolCharCount">0 characters</span>
            <div class="cool-modal-actions">
                <button class="cool-btn cool-btn-secondary" onclick="closeCoolInsert()">Cancel</button>
                <button class="cool-btn cool-btn-primary" id="coolProcessBtn" onclick="processCoolInsert()">
                    <span>🚀</span>
                    <span>Process & Insert</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// ========================================
// DATABASE CONNECTION TOGGLE SYSTEM
// ========================================

// Current connection type from PHP
const CURRENT_CONNECTION_TYPE = '<?php echo $connectionType; ?>';
const CONNECTION_WAS_FALLBACK = <?php echo $connectionFallback ? 'true' : 'false'; ?>;

// LocalStorage key for connection preference
const DB_CONNECTION_KEY = 'db_connection_type';

// Initialize toggle on page load
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('dbToggleContainer');
    const toggle = document.getElementById('dbConnectionToggle');
    
    if (container && toggle) {
        // Set data attribute for styling
        container.setAttribute('data-active', CURRENT_CONNECTION_TYPE);
        
        // Update toggle state
        toggle.checked = (CURRENT_CONNECTION_TYPE === 'remote');
        
        // Show fallback notification if auto-switched
        if (CONNECTION_WAS_FALLBACK) {
            showDbSwitchToast(
                `Auto-switched to ${CURRENT_CONNECTION_TYPE === 'localhost' ? '🖥️ Localhost' : '🌐 Remote'}`,
                'warning'
            );
        }
    }
});

// Switch database connection
async function switchDbConnection(isRemote) {
    const targetType = isRemote ? 'remote' : 'localhost';
    const toggle = document.getElementById('dbConnectionToggle');
    const container = document.getElementById('dbToggleContainer');
    const statusEl = document.getElementById('connectionStatus');
    
    // Disable toggle during switch
    toggle.disabled = true;
    
    // Show switching message
    showDbSwitchToast(`Switching to ${isRemote ? '🌐 Remote' : '🖥️ Localhost'}...`, 'info', 0);
    
    try {
        const response = await fetch(`?switch_db=${targetType}`);
        const result = await response.json();
        
        if (result.success) {
            // Connection successful
            container.setAttribute('data-active', targetType);
            statusEl.innerHTML = `${isRemote ? '🌐 Remote' : '🖥️ Local'}`;
            
            // Save preference to cookie via reload
            document.cookie = `db_connection_type=${targetType};path=/;max-age=${86400 * 30}`;
            
            // Save to localStorage as well
            localStorage.setItem(DB_CONNECTION_KEY, targetType);
            
            showDbSwitchToast(`✅ Connected to ${isRemote ? '🌐 Remote' : '🖥️ Localhost'}`, 'success');
            
            // Reload page to use new connection
            setTimeout(() => {
                window.location.reload();
            }, 1000);
            
        } else {
            // Connection failed - revert toggle
            toggle.checked = !isRemote;
            container.setAttribute('data-active', isRemote ? 'localhost' : 'remote');
            
            showDbSwitchToast(
                `❌ Failed to connect to ${isRemote ? 'Remote' : 'Localhost'}. Staying on ${!isRemote ? '🌐 Remote' : '🖥️ Localhost'}`,
                'error'
            );
        }
    } catch (error) {
        // Network error - revert toggle
        toggle.checked = !isRemote;
        container.setAttribute('data-active', isRemote ? 'localhost' : 'remote');
        
        showDbSwitchToast(`❌ Connection error: ${error.message}`, 'error');
    }
    
    // Re-enable toggle
    toggle.disabled = false;
}

// Show toast notification for db switch
function showDbSwitchToast(message, type = 'info', duration = 3000) {
    // Remove existing toast if any
    const existingToast = document.getElementById('dbSwitchToast');
    if (existingToast) {
        existingToast.remove();
    }
    
    // Create toast element
    const toast = document.createElement('div');
    toast.id = 'dbSwitchToast';
    toast.className = `db-switch-toast ${type}`;
    toast.style.display = 'flex';
    
    const icons = {
        'success': '✅',
        'error': '❌',
        'info': '🔄',
        'warning': '⚠️'
    };
    
    toast.innerHTML = `
        <span class="toast-icon">${icons[type] || '📢'}</span>
        <span class="toast-message">${message}</span>
    `;
    
    document.body.appendChild(toast);
    
    // Auto-hide after duration (if not 0)
    if (duration > 0) {
        setTimeout(() => {
            toast.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => {
                toast.remove();
            }, 300);
        }, duration);
    }
}

// ========================================
// EXPORT FUNCTIONALITY
// ========================================

// LocalStorage key for remembering last export path
const EXPORT_PATH_KEY = 'report_prompt_db_export_path';

// Show toast notification
function showToast(message, type = 'info', duration = 3000) {
    const toast = document.getElementById('exportToast');
    const content = document.getElementById('exportToastContent');
    
    const icons = {
        'success': '✅',
        'error': '❌',
        'info': '🔄',
        'warning': '⚠️'
    };
    
    const colors = {
        'success': 'var(--accent-primary)',
        'error': 'var(--accent-danger)',
        'info': 'var(--accent-secondary)',
        'warning': 'var(--accent-warning)'
    };
    
    content.innerHTML = `
        <span style="font-size: 24px;">${icons[type]}</span>
        <div style="flex: 1;">
            <div style="font-weight: 600; color: ${colors[type]};">${type.charAt(0).toUpperCase() + type.slice(1)}</div>
            <div style="font-size: 0.9rem; color: var(--text-secondary);">${message}</div>
        </div>
    `;
    
    toast.style.display = 'block';
    toast.style.animation = 'slideInRight 0.3s ease';
    
    if (duration > 0) {
        setTimeout(() => {
            toast.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => {
                toast.style.display = 'none';
            }, 300);
        }, duration);
    }
}

// Export with File System Access API (File Picker)
async function exportWithFilePicker() {
    showToast('Fetching data from database...', 'info', 0);
    
    try {
        // Fetch export data from PHP
        const response = await fetch('?export=1');
        if (!response.ok) throw new Error('Failed to fetch export data');
        
        const exportData = await response.json();
        
        if (!exportData.connections || exportData.connections.length === 0) {
            showToast('No connections to export!', 'error');
            return;
        }
        
        // Generate filename with timestamp
        const timestamp = new Date().toISOString().replace(/[:.]/g, '-').slice(0, -5);
        const filename = `report_prompt_databases_${timestamp}.json`;
        
        // Convert to JSON with formatting
        const jsonData = JSON.stringify(exportData, null, 2);
        const blob = new Blob([jsonData], { type: 'application/json' });
        
        // Check if File System Access API is supported
        if ('showSaveFilePicker' in window) {
            await exportWithFileSystemAPI(blob, filename);
        } else {
            // Fallback: direct download
            showToast('File picker not supported. Downloading directly...', 'warning');
            downloadDirectly(blob, filename);
        }
        
    } catch (error) {
        console.error('Export error:', error);
        showToast('Export failed: ' + error.message, 'error');
    }
}

// Export using File System Access API
async function exportWithFileSystemAPI(blob, defaultFilename) {
    try {
        showToast('Opening file picker...', 'info', 0);
        
        const options = {
            suggestedName: defaultFilename,
            types: [{
                description: 'JSON Files',
                accept: { 'application/json': ['.json'] }
            }]
        };
        
        // Try to get stored directory handle
        let startIn = undefined;
        const storedHandle = await getStoredDirectoryHandle();
        if (storedHandle) {
            startIn = storedHandle;
            console.log('📂 Using remembered directory');
        }
        
        if (startIn) {
            options.startIn = startIn;
        }
        
        // Show file picker
        const fileHandle = await window.showSaveFilePicker(options);
        
        // Write the file
        showToast('Saving file...', 'info', 0);
        const writable = await fileHandle.createWritable();
        await writable.write(blob);
        await writable.close();
        
        // Try to remember the directory
        try {
            // Get parent directory handle (if supported)
            if (fileHandle.getParent) {
                const dirHandle = await fileHandle.getParent();
                await storeDirectoryHandle(dirHandle);
            } else {
                // Store the file handle's name at least
                localStorage.setItem(EXPORT_PATH_KEY + '_filename', fileHandle.name);
            }
        } catch (e) {
            console.log('Could not store directory handle:', e);
        }
        
        showToast(`Exported successfully!\n📁 ${fileHandle.name}`, 'success', 5000);
        
    } catch (error) {
        if (error.name === 'AbortError') {
            showToast('Export cancelled', 'warning');
        } else {
            console.error('File picker error:', error);
            // Fallback to direct download
            showToast('File picker failed. Using direct download...', 'warning');
            downloadDirectly(blob, defaultFilename);
        }
    }
}

// Store directory handle using IndexedDB (localStorage can't store handles)
async function storeDirectoryHandle(dirHandle) {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('ExportSettings', 1);
        
        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            if (!db.objectStoreNames.contains('directories')) {
                db.createObjectStore('directories');
            }
        };
        
        request.onsuccess = (event) => {
            const db = event.target.result;
            const transaction = db.transaction(['directories'], 'readwrite');
            const store = transaction.objectStore('directories');
            store.put(dirHandle, 'lastExportDir');
            
            transaction.oncomplete = () => {
                console.log('📁 Directory handle stored');
                resolve();
            };
            transaction.onerror = () => reject(transaction.error);
        };
        
        request.onerror = () => reject(request.error);
    });
}

// Get stored directory handle from IndexedDB
async function getStoredDirectoryHandle() {
    return new Promise((resolve) => {
        const request = indexedDB.open('ExportSettings', 1);
        
        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            if (!db.objectStoreNames.contains('directories')) {
                db.createObjectStore('directories');
            }
        };
        
        request.onsuccess = async (event) => {
            try {
                const db = event.target.result;
                const transaction = db.transaction(['directories'], 'readonly');
                const store = transaction.objectStore('directories');
                const getRequest = store.get('lastExportDir');
                
                getRequest.onsuccess = async () => {
                    const handle = getRequest.result;
                    if (handle) {
                        // Verify permission
                        try {
                            const permission = await handle.queryPermission({ mode: 'readwrite' });
                            if (permission === 'granted') {
                                resolve(handle);
                                return;
                            }
                            // Try to request permission
                            const newPermission = await handle.requestPermission({ mode: 'readwrite' });
                            if (newPermission === 'granted') {
                                resolve(handle);
                                return;
                            }
                        } catch (e) {
                            console.log('Permission check failed:', e);
                        }
                    }
                    resolve(null);
                };
                
                getRequest.onerror = () => resolve(null);
            } catch (e) {
                resolve(null);
            }
        };
        
        request.onerror = () => resolve(null);
    });
}

// Fallback: Direct download
function downloadDirectly(blob, filename) {
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    
    showToast(`Downloaded: ${filename}`, 'success');
}

// ========================================
// MASS SELECTION & DELETE FUNCTIONS
// ========================================

// Toggle Select All
function toggleSelectAll(checkbox) {
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');
    const rows = document.querySelectorAll('.data-row');
    
    rowCheckboxes.forEach((cb, index) => {
        cb.checked = checkbox.checked;
        if (rows[index]) {
            rows[index].classList.toggle('selected', checkbox.checked);
        }
    });
    
    updateSelection();
}

// Update selection count and UI
function updateSelection() {
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');
    const selectAllCheckbox = document.getElementById('selectAll');
    const massActionsBar = document.getElementById('massActionsBar');
    const selectedCount = document.getElementById('selectedCount');
    const massSelectedCount = document.getElementById('massSelectedCount');
    const rows = document.querySelectorAll('.data-row');
    
    let checkedCount = 0;
    
    rowCheckboxes.forEach((cb, index) => {
        if (cb.checked) {
            checkedCount++;
            if (rows[index]) {
                rows[index].classList.add('selected');
            }
        } else {
            if (rows[index]) {
                rows[index].classList.remove('selected');
            }
        }
    });
    
    // Update Select All checkbox state
    if (selectAllCheckbox) {
        selectAllCheckbox.checked = checkedCount === rowCheckboxes.length && rowCheckboxes.length > 0;
        selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < rowCheckboxes.length;
    }
    
    // Show/hide mass actions bar
    if (massActionsBar) {
        massActionsBar.style.display = checkedCount > 0 ? 'block' : 'none';
    }
    
    // Update selected count badges
    if (selectedCount) {
        selectedCount.style.display = checkedCount > 0 ? 'inline-block' : 'none';
        selectedCount.textContent = `${checkedCount} selected`;
    }
    
    if (massSelectedCount) {
        massSelectedCount.textContent = checkedCount;
    }
}

// Clear all selections
function clearSelection() {
    const selectAllCheckbox = document.getElementById('selectAll');
    if (selectAllCheckbox) {
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = false;
    }
    toggleSelectAll({ checked: false });
}

// Delete selected items
function deleteSelected() {
    const rowCheckboxes = document.querySelectorAll('.row-checkbox:checked');
    const count = rowCheckboxes.length;
    
    if (count === 0) {
        showToast('No records selected!', 'error');
        return;
    }
    
    const confirmMsg = count === 1 
        ? 'Are you sure you want to delete this record?' 
        : `Are you sure you want to delete ${count} records?\n\nThis action cannot be undone!`;
    
    if (confirm(confirmMsg)) {
        showToast(`Deleting ${count} record(s)...`, 'info', 0);
        document.getElementById('massDeleteForm').submit();
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Add click handler to rows for easier selection
    const rows = document.querySelectorAll('.data-row');
    rows.forEach(row => {
        row.addEventListener('click', function(e) {
            // Don't toggle if clicking on buttons, links, or checkbox itself
            if (e.target.closest('a, button, .checkbox-wrapper')) return;
            
            const checkbox = this.querySelector('.row-checkbox');
            if (checkbox) {
                checkbox.checked = !checkbox.checked;
                updateSelection();
            }
        });
    });
    
    // Update initial state
    updateSelection();
    
    // Initialize speed monitor
    initSpeedMonitor();
    
    // Auto-test all connections on page load
    autoTestAllConnections();
});

// Auto-test all connections on page load (silently)
async function autoTestAllConnections() {
    const testButtons = document.querySelectorAll('.conn-test-btn');
    
    if (testButtons.length === 0) return;
    
    // Small delay before starting tests to let page render
    await new Promise(resolve => setTimeout(resolve, 500));
    
    // Test connections sequentially with a small delay
    for (const btn of testButtons) {
        const id = btn.id.replace('testBtn_', '');
        await testConnection(id);
        
        // Small delay between tests to avoid overwhelming the server
        await new Promise(resolve => setTimeout(resolve, 300));
    }
}

// ========================================
// SPEED MONITOR FUNCTIONALITY
// ========================================
const SPEED_HISTORY_KEY = 'db_speed_history';

// Initialize speed monitor
function initSpeedMonitor() {
    // Load saved history
    const history = getSpeedHistory();
    
    // Check if there's a new operation from PHP
    if (window.latestOperation) {
        addSpeedEntry(window.latestOperation);
    }
    
    // Update the display
    updateSpeedMonitor();
}

// Get speed history from localStorage
function getSpeedHistory() {
    try {
        const data = localStorage.getItem(SPEED_HISTORY_KEY);
        return data ? JSON.parse(data) : [];
    } catch (e) {
        return [];
    }
}

// Save speed history to localStorage
function saveSpeedHistory(history) {
    try {
        // Keep only the last 10 entries
        if (history.length > 10) {
            history = history.slice(-10);
        }
        localStorage.setItem(SPEED_HISTORY_KEY, JSON.stringify(history));
    } catch (e) {
        console.error('Failed to save speed history:', e);
    }
}

// Add a new speed entry
function addSpeedEntry(operation) {
    const history = getSpeedHistory();
    
    // Avoid duplicate entries (same timestamp)
    const isDuplicate = history.some(h => 
        h.timestamp === operation.timestamp && 
        h.type === operation.type && 
        h.time === operation.time
    );
    
    if (!isDuplicate) {
        history.push({
            time: operation.time,
            type: operation.type,
            connection: operation.connection,
            timestamp: operation.timestamp
        });
        saveSpeedHistory(history);
    }
}

// Update speed monitor display
function updateSpeedMonitor() {
    const content = document.getElementById('speedContent');
    if (!content) return;
    
    const history = getSpeedHistory();
    
    if (history.length === 0) {
        content.innerHTML = '<div class="no-data">Perform an action to see speed...</div>';
        return;
    }
    
    // Get last two entries
    const lastTwo = history.slice(-2);
    
    let html = '';
    
    // Show the last two operations
    lastTwo.forEach((entry, idx) => {
        const isLatest = idx === lastTwo.length - 1;
        const connClass = entry.connection === 'localhost' ? 'local' : 'remote';
        const connIcon = entry.connection === 'localhost' ? '🖥️' : '🌐';
        
        html += `
            <div class="speed-row ${isLatest ? 'speed-new' : ''}">
                <span class="speed-label">
                    ${connIcon}
                    <span class="op-type">${entry.type}</span>
                </span>
                <span class="speed-value ${connClass}">${entry.time}ms</span>
            </div>
        `;
    });
    
    // Add comparison if we have two entries
    if (lastTwo.length === 2) {
        const [first, second] = lastTwo;
        const diff = Math.abs(first.time - second.time).toFixed(2);
        const percentage = first.time > 0 ? Math.round((diff / first.time) * 100) : 0;
        
        let comparisonClass, comparisonText, winner;
        
        if (second.time < first.time) {
            comparisonClass = 'faster';
            winner = second.connection === 'localhost' ? '🖥️' : '🌐';
            comparisonText = `${winner} ${percentage}% faster`;
        } else if (second.time > first.time) {
            comparisonClass = 'slower';
            winner = first.connection === 'localhost' ? '🖥️' : '🌐';
            comparisonText = `${winner} ${percentage}% faster`;
        } else {
            comparisonClass = 'equal';
            comparisonText = '⚖️ Equal speed';
        }
        
        html += `
            <div class="speed-comparison ${comparisonClass}">
                <span class="speed-winner">${comparisonClass !== 'equal' ? winner : '⚖️'}</span>
                <span class="speed-diff">Δ ${diff}ms</span>
                <span>${comparisonText}</span>
            </div>
        `;
    }
    
    // Add clear button
    html += `
        <div style="text-align: center; margin-top: 6px;">
            <button onclick="clearSpeedHistory()" style="
                background: rgba(255,255,255,0.1);
                border: none;
                color: var(--text-secondary);
                font-size: 8px;
                padding: 2px 8px;
                border-radius: 4px;
                cursor: pointer;
                transition: all 0.2s;
            " onmouseover="this.style.background='rgba(255,255,255,0.2)'" onmouseout="this.style.background='rgba(255,255,255,0.1)'">
                Clear History
            </button>
        </div>
    `;
    
    content.innerHTML = html;
}

// Clear speed history
function clearSpeedHistory() {
    localStorage.removeItem(SPEED_HISTORY_KEY);
    updateSpeedMonitor();
    showToast('Speed history cleared', 'success', 1500);
}

// Record speed from JavaScript operations (for AJAX calls)
function recordOperationSpeed(type, startTime, connection) {
    const endTime = performance.now();
    const operationTime = (endTime - startTime).toFixed(2);
    
    const operation = {
        time: parseFloat(operationTime),
        type: type,
        connection: connection || (document.getElementById('dbConnectionToggle')?.checked ? 'remote' : 'localhost'),
        timestamp: Date.now()
    };
    
    addSpeedEntry(operation);
    updateSpeedMonitor();
    
    return operationTime;
}

// ========================================
// CONNECTION TEST FUNCTIONALITY
// ========================================

// Test a single connection
async function testConnection(id) {
    const btn = document.getElementById(`testBtn_${id}`);
    if (!btn) return;
    
    // Reset and set testing state
    btn.classList.remove('success', 'error');
    btn.classList.add('testing');
    btn.querySelector('.btn-icon').textContent = '⏳';
    
    try {
        const response = await fetch(`?test_connection=1&id=${encodeURIComponent(id)}`);
        const result = await response.json();
        
        btn.classList.remove('testing');
        
        if (result.success) {
            // Success state
            btn.classList.add('success');
            btn.querySelector('.btn-icon').textContent = '✓';
            
            // Play success animation
            btn.style.animation = 'none';
            btn.offsetHeight; // Trigger reflow
            btn.style.animation = null;
            
        } else {
            // Error state
            btn.classList.add('error');
            btn.querySelector('.btn-icon').textContent = '✗';
        }
        
        return result;
        
    } catch (error) {
        btn.classList.remove('testing');
        btn.classList.add('error');
        btn.querySelector('.btn-icon').textContent = '✗';
        
        return { success: false, error: error.message, id };
    }
}

// Test all connections
async function testAllConnections() {
    const testButtons = document.querySelectorAll('.conn-test-btn');
    
    if (testButtons.length === 0) {
        showToast('No connections to test!', 'warning');
        return;
    }
    
    showToast(`Testing ${testButtons.length} connection(s)...`, 'info', 2000);
    
    let successCount = 0;
    let failCount = 0;
    
    // Test connections sequentially with a small delay
    for (const btn of testButtons) {
        const id = btn.id.replace('testBtn_', '');
        const result = await testConnection(id);
        
        if (result && result.success) {
            successCount++;
        } else {
            failCount++;
        }
        
        // Small delay between tests to avoid overwhelming the server
        await new Promise(resolve => setTimeout(resolve, 200));
    }
    
    // Show summary toast
    if (failCount === 0) {
        showToast(`All ${successCount} connection(s) successful! ✓`, 'success', 4000);
    } else if (successCount === 0) {
        showToast(`All ${failCount} connection(s) failed! ✗`, 'error', 4000);
    } else {
        showToast(`${successCount} successful, ${failCount} failed`, 'warning', 4000);
    }
}

// Reset connection test button to default state
function resetTestButton(id) {
    const btn = document.getElementById(`testBtn_${id}`);
    if (!btn) return;
    
    btn.classList.remove('success', 'error', 'testing');
    btn.querySelector('.btn-icon').textContent = '🔌';
}

// Reset all test buttons
function resetAllTestButtons() {
    document.querySelectorAll('.conn-test-btn').forEach(btn => {
        const id = btn.id.replace('testBtn_', '');
        resetTestButton(id);
    });
}

// ========================================
// COOL INSERT FUNCTIONALITY
// ========================================

// Open Cool Insert Modal
function openCoolInsert() {
    const modal = document.getElementById('coolInsertModal');
    const inputView = document.getElementById('coolInputView');
    const loadingView = document.getElementById('coolLoadingView');
    const reportView = document.getElementById('coolReportView');
    const textarea = document.getElementById('coolTextarea');
    const processBtn = document.getElementById('coolProcessBtn');
    
    // Reset to input view
    inputView.style.display = 'block';
    loadingView.style.display = 'none';
    reportView.style.display = 'none';
    textarea.value = '';
    processBtn.innerHTML = '<span>🚀</span><span>Process & Insert</span>';
    processBtn.onclick = processCoolInsert;
    processBtn.disabled = false;
    
    updateCharCount();
    
    // Show modal
    modal.classList.add('active');
    
    // Focus textarea
    setTimeout(() => textarea.focus(), 100);
}

// Close Cool Insert Modal
function closeCoolInsert() {
    const modal = document.getElementById('coolInsertModal');
    modal.classList.remove('active');
}

// Update character count
function updateCharCount() {
    const textarea = document.getElementById('coolTextarea');
    const countEl = document.getElementById('coolCharCount');
    const count = textarea.value.length;
    countEl.textContent = `${count.toLocaleString()} characters`;
}

// Process Cool Insert
async function processCoolInsert() {
    const textarea = document.getElementById('coolTextarea');
    const inputView = document.getElementById('coolInputView');
    const loadingView = document.getElementById('coolLoadingView');
    const reportView = document.getElementById('coolReportView');
    const processBtn = document.getElementById('coolProcessBtn');
    
    const rawText = textarea.value.trim();
    
    if (!rawText) {
        showToast('Please paste some text containing database credentials', 'error');
        return;
    }
    
    // Show loading
    inputView.style.display = 'none';
    loadingView.style.display = 'block';
    processBtn.disabled = true;
    
    try {
        const formData = new FormData();
        formData.append('action', 'cool_insert');
        formData.append('raw_text', rawText);
        
        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        // Hide loading, show report
        loadingView.style.display = 'none';
        reportView.style.display = 'block';
        
        if (result.success) {
            displayCoolReport(result);
        } else {
            document.getElementById('coolReportContent').innerHTML = `
                <div style="text-align: center; padding: 40px; color: var(--accent-danger);">
                    <div style="font-size: 3rem; margin-bottom: 15px;">😕</div>
                    <h3 style="margin-bottom: 10px;">Oops!</h3>
                    <p style="color: var(--text-secondary);">${result.error || 'Failed to process credentials'}</p>
                </div>
            `;
        }
        
        // Update button to "Done" or "Try Again"
        processBtn.innerHTML = '<span>✅</span><span>Done - Refresh Page</span>';
        processBtn.onclick = () => window.location.reload();
        processBtn.disabled = false;
        
    } catch (error) {
        console.error('Cool Insert error:', error);
        loadingView.style.display = 'none';
        reportView.style.display = 'block';
        
        document.getElementById('coolReportContent').innerHTML = `
            <div style="text-align: center; padding: 40px; color: var(--accent-danger);">
                <div style="font-size: 3rem; margin-bottom: 15px;">❌</div>
                <h3 style="margin-bottom: 10px;">Error</h3>
                <p style="color: var(--text-secondary);">${error.message}</p>
            </div>
        `;
        
        processBtn.innerHTML = '<span>🔄</span><span>Try Again</span>';
        processBtn.onclick = () => {
            reportView.style.display = 'none';
            inputView.style.display = 'block';
            processBtn.innerHTML = '<span>🚀</span><span>Process & Insert</span>';
            processBtn.onclick = processCoolInsert;
        };
        processBtn.disabled = false;
    }
}

// Display Cool Report
function displayCoolReport(result) {
    const summary = result.summary;
    const report = result.report;
    
    let html = `
        <div class="cool-report-summary">
            <div class="cool-report-stat found">
                <div class="cool-report-stat-value">${summary.found}</div>
                <div class="cool-report-stat-label">Found</div>
            </div>
            <div class="cool-report-stat added">
                <div class="cool-report-stat-value">${summary.added}</div>
                <div class="cool-report-stat-label">Added</div>
            </div>
            <div class="cool-report-stat skipped">
                <div class="cool-report-stat-value">${summary.skipped}</div>
                <div class="cool-report-stat-label">Skipped</div>
            </div>
            <div class="cool-report-stat invalid">
                <div class="cool-report-stat-value">${summary.invalid}</div>
                <div class="cool-report-stat-label">Invalid</div>
            </div>
        </div>
        
        <div class="cool-report-details">
    `;
    
    // Added section
    if (report.added && report.added.length > 0) {
        html += `
            <div class="cool-report-section">
                <div class="cool-report-section-header" onclick="toggleReportSection(this)">
                    <span class="cool-report-section-icon">✅</span>
                    <span class="cool-report-section-title">Successfully Added</span>
                    <span class="cool-report-section-count">${report.added.length}</span>
                </div>
                <div class="cool-report-section-body">
        `;
        report.added.forEach(item => {
            html += `
                <div class="cool-report-item">
                    <span class="cool-report-item-name">${escapeHtml(item.name)}</span>
                    <span class="cool-report-item-info">${escapeHtml(item.data.host)} → ${escapeHtml(item.data.dbName)}</span>
                </div>
            `;
        });
        html += `</div></div>`;
    }
    
    // Skipped section
    if (report.skipped && report.skipped.length > 0) {
        html += `
            <div class="cool-report-section">
                <div class="cool-report-section-header" onclick="toggleReportSection(this)">
                    <span class="cool-report-section-icon">⚠️</span>
                    <span class="cool-report-section-title">Skipped (Already Exists)</span>
                    <span class="cool-report-section-count">${report.skipped.length}</span>
                </div>
                <div class="cool-report-section-body">
        `;
        report.skipped.forEach(item => {
            html += `
                <div class="cool-report-item">
                    <span class="cool-report-item-name">${escapeHtml(item.existing_name)}</span>
                    <span class="cool-report-item-info">${escapeHtml(item.data.host)} → ${escapeHtml(item.data.dbName)}</span>
                    <span class="cool-report-item-reason">${escapeHtml(item.reason)}</span>
                </div>
            `;
        });
        html += `</div></div>`;
    }
    
    // Invalid section
    if (report.invalid && report.invalid.length > 0) {
        html += `
            <div class="cool-report-section">
                <div class="cool-report-section-header" onclick="toggleReportSection(this)">
                    <span class="cool-report-section-icon">❌</span>
                    <span class="cool-report-section-title">Invalid (Incomplete)</span>
                    <span class="cool-report-section-count">${report.invalid.length}</span>
                </div>
                <div class="cool-report-section-body">
        `;
        report.invalid.forEach(item => {
            const info = item.data ? `${item.data.host || '?'} → ${item.data.dbName || '?'}` : 'N/A';
            html += `
                <div class="cool-report-item">
                    <span class="cool-report-item-name" style="color: var(--accent-danger);">Invalid</span>
                    <span class="cool-report-item-info">${escapeHtml(info)}</span>
                    <span class="cool-report-item-reason">${escapeHtml(item.reason)}</span>
                </div>
            `;
        });
        html += `</div></div>`;
    }
    
    html += `</div>`;
    
    // Success message if any added
    if (summary.added > 0) {
        html = `
            <div style="text-align: center; margin-bottom: 25px;">
                <div style="font-size: 4rem; margin-bottom: 10px;">🎉</div>
                <h3 style="color: var(--accent-primary); margin-bottom: 5px;">Cool Insert Complete!</h3>
                <p style="color: var(--text-secondary);">${summary.added} new connection(s) added to your database</p>
            </div>
        ` + html;
    } else if (summary.found > 0 && summary.skipped === summary.found) {
        html = `
            <div style="text-align: center; margin-bottom: 25px;">
                <div style="font-size: 4rem; margin-bottom: 10px;">👍</div>
                <h3 style="color: var(--accent-warning); margin-bottom: 5px;">All Good!</h3>
                <p style="color: var(--text-secondary);">All detected connections already exist in your database</p>
            </div>
        ` + html;
    }
    
    document.getElementById('coolReportContent').innerHTML = html;
}

// Toggle report section
function toggleReportSection(header) {
    const body = header.nextElementSibling;
    const isHidden = body.style.display === 'none';
    body.style.display = isHidden ? 'block' : 'none';
}

// Escape HTML helper
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeCoolInsert();
    }
});

// Close modal on overlay click
document.getElementById('coolInsertModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeCoolInsert();
    }
});
</script>

<style>
@keyframes slideInRight {
    from { opacity: 0; transform: translateX(50px); }
    to { opacity: 1; transform: translateX(0); }
}

@keyframes slideOutRight {
    from { opacity: 1; transform: translateX(0); }
    to { opacity: 0; transform: translateX(50px); }
}
</style>

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

@keyframes logoFloat {
    0%, 100% { transform: translateY(0px) rotate(0deg); }
    25% { transform: translateY(-8px) rotate(-2deg); }
    50% { transform: translateY(-12px) rotate(0deg); }
    75% { transform: translateY(-8px) rotate(2deg); }
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


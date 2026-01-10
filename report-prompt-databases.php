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
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px 20px;
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
            padding: 8px 16px;
            font-size: 0.85rem;
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
        }
        
        th, td {
            padding: 16px 18px;
            text-align: left;
            font-size: 0.9rem;
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
            gap: 8px;
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
});

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


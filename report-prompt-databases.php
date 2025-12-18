<?php
/**
 * Report Prompt Databases - CRUD Management
 * Table: report_prompt_databases
 */

// Database Configuration
$dbHost = 'srv1788.hstgr.io';
$dbName = 'u419999707_Mohamed';
$dbUser = 'u419999707_Abuammar';
$dbPass = 'P@master5007';
$dbPort = 3306;

// Table name with prefix
$tableName = 'report_prompt_databases';

// PDO Connection
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
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

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

// Handle Actions
$message = '';
$messageType = '';

// DELETE
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM `$tableName` WHERE id = ?");
    $stmt->execute([$id]);
    $message = 'Record deleted successfully!';
    $messageType = 'success';
}

// ADD / UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        
        // ADD
        if ($_POST['action'] === 'add') {
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
            $message = 'Record added successfully!';
            $messageType = 'success';
        }
        
        // UPDATE
        if ($_POST['action'] === 'update') {
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
            $message = 'Record updated successfully!';
            $messageType = 'success';
        }
        
        // IMPORT
        if ($_POST['action'] === 'import' && isset($_FILES['jsonFile'])) {
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
                $message = "$imported records imported successfully!";
                $messageType = 'success';
            } else {
                $message = 'Invalid JSON format!';
                $messageType = 'error';
            }
        }
    }
}

// EXPORT
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
    
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="report_prompt_databases_' . date('Y-m-d_H-i-s') . '.json"');
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
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>🗄️ Report Prompt Databases</h1>
            <p>Database Connection Manager - Full CRUD Operations</p>
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
                <a href="?export=1" class="btn btn-warning">📤 Export JSON</a>
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
                <span class="record-count"><?php echo count($records); ?> Records</span>
            </div>
            
            <?php if (count($records) > 0): ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
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
                        <tr>
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


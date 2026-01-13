<?php
session_start();

// ============================================
// SUPER ADMIN AUTHENTICATION
// ============================================
define('ADMIN_PASSWORD', 'GL_Admin');

// Check for remember me cookie
if (!isset($_SESSION['admin_logged_in']) && isset($_COOKIE['admin_remember'])) {
    if ($_COOKIE['admin_remember'] === hash('sha256', ADMIN_PASSWORD . 'salt_key_2024')) {
        $_SESSION['admin_logged_in'] = true;
    }
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_login'])) {
    $inputPassword = $_POST['admin_password'] ?? '';
    
    if ($inputPassword === ADMIN_PASSWORD) {
        $_SESSION['admin_logged_in'] = true;
        
        // Set remember me cookie (30 days)
        if (isset($_POST['remember_me'])) {
            setcookie('admin_remember', hash('sha256', ADMIN_PASSWORD . 'salt_key_2024'), time() + (86400 * 30), '/');
        }
        
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $loginError = 'Invalid password. Please try again.';
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    $_SESSION['admin_logged_in'] = false;
    setcookie('admin_remember', '', time() - 3600, '/');
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Check if logged in
$isLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

// If not logged in, show login page
if (!$isLoggedIn) {
    showLoginPage($loginError ?? null);
    exit;
}

function showLoginPage($error = null) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Prompt Manager</title>
    <link rel="icon" type="image/png" href="logoPM.png">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Space Grotesk', sans-serif;
            background: linear-gradient(135deg, #0f0f23 0%, #1a1a3e 50%, #0f0f23 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            background: rgba(30, 30, 60, 0.9);
            border: 1px solid rgba(99, 102, 241, 0.3);
            border-radius: 20px;
            padding: 3rem;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5), 0 0 100px rgba(99, 102, 241, 0.1);
            backdrop-filter: blur(10px);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        
        .login-logo {
            width: 120px;
            height: auto;
            margin: 0 auto 1.5rem;
        }
        
        .login-logo img {
            width: 100%;
            height: auto;
            filter: drop-shadow(0 10px 30px rgba(99, 102, 241, 0.4));
        }
        
        .login-header h1 {
            color: #fff;
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .login-header p {
            color: #9ca3af;
            font-size: 0.95rem;
        }
        
        .login-form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .form-group {
            position: relative;
        }
        
        .form-group label {
            display: block;
            color: #9ca3af;
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        
        .password-wrapper {
            position: relative;
        }
        
        .password-wrapper input {
            width: 100%;
            padding: 1rem 3rem 1rem 1rem;
            background: rgba(15, 15, 35, 0.8);
            border: 2px solid rgba(99, 102, 241, 0.2);
            border-radius: 12px;
            color: #fff;
            font-size: 1rem;
            font-family: inherit;
            outline: none;
            transition: all 0.3s;
        }
        
        .password-wrapper input:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15);
        }
        
        .password-wrapper input::placeholder {
            color: #6b7280;
        }
        
        .toggle-password {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6b7280;
            cursor: pointer;
            padding: 0.25rem;
            font-size: 1.1rem;
            transition: color 0.2s;
        }
        
        .toggle-password:hover {
            color: #6366f1;
        }
        
        .remember-group {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .remember-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #6366f1;
            cursor: pointer;
        }
        
        .remember-group label {
            color: #9ca3af;
            font-size: 0.9rem;
            cursor: pointer;
            margin: 0;
        }
        
        .login-btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 1rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(99, 102, 241, 0.4);
        }
        
        .login-btn:active {
            transform: translateY(0);
        }
        
        .error-message {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #f87171;
            padding: 1rem;
            border-radius: 10px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .footer-text {
            text-align: center;
            margin-top: 2rem;
            color: #6b7280;
            font-size: 0.8rem;
        }
        
        .footer-text i {
            color: #6366f1;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="login-logo">
                <img src="logoPM.png" alt="Prompt Manager">
            </div>
            <p>Enter admin password to continue</p>
        </div>
        
        <?php if ($error): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>
        
        <form class="login-form" method="POST">
            <div class="form-group">
                <label for="admin_password">Password</label>
                <div class="password-wrapper">
                    <input type="password" id="admin_password" name="admin_password" placeholder="Enter admin password" required autofocus>
                    <button type="button" class="toggle-password" onclick="togglePassword()">
                        <i class="fas fa-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>
            
            <div class="remember-group">
                <input type="checkbox" id="remember_me" name="remember_me">
                <label for="remember_me">Remember me for 30 days</label>
            </div>
            
            <button type="submit" name="admin_login" class="login-btn">
                <i class="fas fa-sign-in-alt"></i>
                Login
            </button>
        </form>
        
        <p class="footer-text">
            <i class="fas fa-shield-alt"></i> Secured Admin Access
        </p>
    </div>
    
    <script>
        function togglePassword() {
            const input = document.getElementById('admin_password');
            const icon = document.getElementById('eyeIcon');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
<?php
}

// ============================================================================
// SMART DATABASE CONNECTION - Localhost/Remote Auto-Switch
// ============================================================================
// Tries LOCALHOST first (faster when on Hostinger server)
// Falls back to REMOTE if localhost fails (when accessing from anywhere)
// ============================================================================

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

// Connection state variables
$pdo = null;
$dbError = null;
$connectionType = 'localhost'; // Will be updated after connection
$connectionFallback = false;

// Handle AJAX request for testing/switching connection
if (isset($_GET['switch_db'])) {
    header('Content-Type: application/json');
    $requestedType = $_GET['switch_db'];
    
    if (!isset($dbCredentials[$requestedType])) {
        echo json_encode(['success' => false, 'error' => 'Invalid connection type']);
        exit;
    }
    
    $cred = $dbCredentials[$requestedType];
    try {
        $testPdo = new PDO(
            "mysql:host={$cred['host']};port={$cred['port']};dbname={$cred['dbname']};charset=utf8mb4",
            $cred['username'],
            $cred['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]
        );
        setcookie('pm_db_connection_type', $requestedType, time() + (86400 * 30), '/');
        echo json_encode(['success' => true, 'type' => $requestedType, 'message' => "Connected to $requestedType"]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'type' => $requestedType, 'error' => $e->getMessage()]);
    }
    exit;
}

// Get preferred connection type from cookie (if set)
$preferredType = isset($_COOKIE['pm_db_connection_type']) ? $_COOKIE['pm_db_connection_type'] : 'localhost';
if (!in_array($preferredType, ['localhost', 'remote'])) {
    $preferredType = 'localhost';
}

// Smart Connection Function
function connectToDatabase($credentials, $preferredType) {
    global $connectionType, $connectionFallback, $dbError;
    
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
            $dbError = $e2->getMessage();
            return null;
        }
    }
}

// Establish connection
$pdo = connectToDatabase($dbCredentials, $preferredType);

if ($pdo) {
    // Set cookie to remember working connection type
    setcookie('pm_db_connection_type', $connectionType, time() + (86400 * 30), '/');
    
    try {
        // Delete old tables with generic names (cleanup - remove after first run)
        $pdo->exec("DROP TABLE IF EXISTS saved_prompts");
        $pdo->exec("DROP TABLE IF EXISTS uploaded_files");
        
        // Create tables if not exist (prefixed with reporter_prompt_ to avoid conflicts)
        $pdo->exec("CREATE TABLE IF NOT EXISTS reporter_prompt_saved_prompts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            content TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS reporter_prompt_uploaded_files (
            id INT AUTO_INCREMENT PRIMARY KEY,
            filename VARCHAR(255) NOT NULL,
            filepath VARCHAR(500) NOT NULL,
            filesize INT,
            filetype VARCHAR(100),
            uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Create prompt templates table
        $pdo->exec("CREATE TABLE IF NOT EXISTS reporter_prompt_templates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            content TEXT NOT NULL,
            sort_order INT DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        
        // Create projects table for Development Dashboard (using TEXT for compatibility)
        $pdo->exec("CREATE TABLE IF NOT EXISTS reporter_prompt_projects (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            database_id INT,
            database_name VARCHAR(255),
            database_host VARCHAR(255),
            database_user VARCHAR(255),
            database_pass VARCHAR(255),
            database_port VARCHAR(10) DEFAULT '3306',
            include_remote TINYINT(1) DEFAULT 0,
            include_localhost TINYINT(1) DEFAULT 0,
            backends TEXT,
            pages TEXT,
            frontends TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        
        // Note: No auto-insertion of default templates
        // User will add templates manually via the UI
        
    } catch(PDOException $e) {
        $dbError = $e->getMessage();
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    // ============================================
    // DICTIONARY API PROXY (bypass CORS)
    // ============================================
    if ($action === 'dictionary_proxy') {
        $apiAction = $_POST['api_action'] ?? 'list';
        $query = $_POST['query'] ?? '';
        $groupId = $_POST['group_id'] ?? '';
        $page = $_POST['page'] ?? 1;
        $limit = $_POST['limit'] ?? 15;
        
        $apiUrl = 'https://frouty.com/api/search-api.php?';
        
        if ($apiAction === 'search' && $query) {
            $apiUrl .= 'action=search&q=' . urlencode($query);
        } elseif ($apiAction === 'groups') {
            $apiUrl .= 'action=groups';
        } else {
            $apiUrl .= 'action=list';
        }
        
        $apiUrl .= '&page=' . intval($page) . '&limit=' . intval($limit);
        
        if ($groupId) {
            $apiUrl .= '&group_id=' . intval($groupId);
        }
        
        // Make request to external API
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Prompt-Manager/1.0');
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($response === false || $httpCode !== 200) {
            echo json_encode(['success' => false, 'message' => 'API request failed: ' . ($error ?: 'HTTP ' . $httpCode)]);
        } else {
            // Pass through the API response
            echo $response;
        }
        exit;
    }
    
    // Save prompt
    if ($action === 'save_prompt') {
        $title = $_POST['title'] ?? '';
        $content = $_POST['content'] ?? '';
        
        if ($pdo && $title && $content) {
            try {
                $startTime = microtime(true);
                $stmt = $pdo->prepare("INSERT INTO reporter_prompt_saved_prompts (title, content) VALUES (?, ?)");
                $stmt->execute([$title, $content]);
                $operationTime = round((microtime(true) - $startTime) * 1000, 2);
                echo json_encode([
                    'success' => true, 
                    'id' => $pdo->lastInsertId(), 
                    'message' => 'Prompt saved successfully!',
                    'operationTime' => $operationTime,
                    'operationType' => 'ADD_PROMPT',
                    'connectionType' => $connectionType
                ]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Title and content are required']);
        }
        exit;
    }
    
    // Update prompt
    if ($action === 'update_prompt') {
        $id = $_POST['id'] ?? '';
        $title = $_POST['title'] ?? '';
        $content = $_POST['content'] ?? '';
        
        if ($pdo && $id && $title && $content) {
            try {
                $startTime = microtime(true);
                $stmt = $pdo->prepare("UPDATE reporter_prompt_saved_prompts SET title = ?, content = ? WHERE id = ?");
                $stmt->execute([$title, $content, $id]);
                $operationTime = round((microtime(true) - $startTime) * 1000, 2);
                echo json_encode([
                    'success' => true, 
                    'message' => 'Prompt updated successfully!',
                    'operationTime' => $operationTime,
                    'operationType' => 'UPDATE_PROMPT',
                    'connectionType' => $connectionType
                ]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        }
        exit;
    }
    
    // Delete prompt
    if ($action === 'delete_prompt') {
        $id = $_POST['id'] ?? '';
        
        if ($pdo && $id) {
            try {
                $startTime = microtime(true);
                $stmt = $pdo->prepare("DELETE FROM reporter_prompt_saved_prompts WHERE id = ?");
                $stmt->execute([$id]);
                $operationTime = round((microtime(true) - $startTime) * 1000, 2);
                echo json_encode([
                    'success' => true, 
                    'message' => 'Prompt deleted successfully!',
                    'operationTime' => $operationTime,
                    'operationType' => 'DELETE_PROMPT',
                    'connectionType' => $connectionType
                ]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        }
        exit;
    }
    
    // Get all prompts
    if ($action === 'get_prompts') {
        $search = $_POST['search'] ?? '';
        
        if ($pdo) {
            try {
                if ($search) {
                    $stmt = $pdo->prepare("SELECT * FROM reporter_prompt_saved_prompts WHERE title LIKE ? OR content LIKE ? ORDER BY created_at DESC");
                    $stmt->execute(["%$search%", "%$search%"]);
                } else {
                    $stmt = $pdo->query("SELECT * FROM reporter_prompt_saved_prompts ORDER BY created_at DESC");
                }
                $prompts = $stmt->fetchAll();
                echo json_encode(['success' => true, 'prompts' => $prompts]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        }
        exit;
    }
    
    // ============ PROMPT TEMPLATES CRUD ============
    
    // Get all templates
    if ($action === 'get_templates') {
        if ($pdo) {
            try {
                $stmt = $pdo->query("SELECT * FROM reporter_prompt_templates WHERE is_active = 1 ORDER BY sort_order ASC, id ASC");
                $templates = $stmt->fetchAll();
                echo json_encode(['success' => true, 'templates' => $templates]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Database not connected']);
        }
        exit;
    }
    
    // Add new template
    if ($action === 'add_template') {
        $name = $_POST['name'] ?? '';
        $content = $_POST['content'] ?? '';
        
        if ($pdo && $name && $content) {
            try {
                $startTime = microtime(true);
                // Get max sort_order
                $stmt = $pdo->query("SELECT MAX(sort_order) as max_order FROM reporter_prompt_templates");
                $maxOrder = $stmt->fetch()['max_order'] ?? 0;
                
                $stmt = $pdo->prepare("INSERT INTO reporter_prompt_templates (name, content, sort_order) VALUES (?, ?, ?)");
                $stmt->execute([$name, $content, $maxOrder + 1]);
                $id = $pdo->lastInsertId();
                $operationTime = round((microtime(true) - $startTime) * 1000, 2);
                
                echo json_encode([
                    'success' => true, 
                    'id' => $id, 
                    'message' => 'Template added successfully!',
                    'template' => ['id' => $id, 'name' => $name, 'content' => $content],
                    'operationTime' => $operationTime,
                    'operationType' => 'ADD_TEMPLATE',
                    'connectionType' => $connectionType
                ]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Name and content are required']);
        }
        exit;
    }
    
    // Update template
    if ($action === 'update_template') {
        $id = $_POST['id'] ?? '';
        $name = $_POST['name'] ?? '';
        $content = $_POST['content'] ?? '';
        
        if ($pdo && $id && $name && $content) {
            try {
                $startTime = microtime(true);
                $stmt = $pdo->prepare("UPDATE reporter_prompt_templates SET name = ?, content = ? WHERE id = ?");
                $stmt->execute([$name, $content, $id]);
                $operationTime = round((microtime(true) - $startTime) * 1000, 2);
                echo json_encode([
                    'success' => true, 
                    'message' => 'Template updated successfully!',
                    'operationTime' => $operationTime,
                    'operationType' => 'UPDATE_TEMPLATE',
                    'connectionType' => $connectionType
                ]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'ID, name and content are required']);
        }
        exit;
    }
    
    // Delete template
    if ($action === 'delete_template') {
        $id = $_POST['id'] ?? '';
        
        if ($pdo && $id) {
            try {
                $startTime = microtime(true);
                $stmt = $pdo->prepare("DELETE FROM reporter_prompt_templates WHERE id = ?");
                $stmt->execute([$id]);
                $operationTime = round((microtime(true) - $startTime) * 1000, 2);
                echo json_encode([
                    'success' => true, 
                    'message' => 'Template deleted successfully!',
                    'operationTime' => $operationTime,
                    'operationType' => 'DELETE_TEMPLATE',
                    'connectionType' => $connectionType
                ]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'ID is required']);
        }
        exit;
    }
    
    // ============ PROJECT MANAGEMENT CRUD ============
    
    // Get all projects
    if ($action === 'get_projects') {
        if ($pdo) {
            try {
                $stmt = $pdo->query("SELECT id, name, description, created_at, updated_at FROM reporter_prompt_projects ORDER BY updated_at DESC");
                $projects = $stmt->fetchAll();
                echo json_encode(['success' => true, 'projects' => $projects]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Database not connected']);
        }
        exit;
    }
    
    // Get single project with all data
    if ($action === 'get_project') {
        // Check database connection first
        if (!$pdo) {
            echo json_encode(['success' => false, 'message' => 'Database not connected. Please check your connection.']);
            exit;
        }
        
        $id = $_POST['id'] ?? '';
        
        if ($id) {
            try {
                $stmt = $pdo->prepare("SELECT * FROM reporter_prompt_projects WHERE id = ?");
                $stmt->execute([$id]);
                $project = $stmt->fetch();
                
                if ($project) {
                    // Decode JSON fields
                    $project['backends'] = json_decode($project['backends'] ?? '[]', true) ?: [];
                    $project['pages'] = json_decode($project['pages'] ?? '[]', true) ?: [];
                    $project['frontends'] = json_decode($project['frontends'] ?? '[]', true) ?: [];
                    echo json_encode(['success' => true, 'project' => $project]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Project not found']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Project ID is required']);
        }
        exit;
    }
    
    // Save project (create or update)
    if ($action === 'save_project') {
        // Check database connection first
        if (!$pdo) {
            echo json_encode(['success' => false, 'message' => 'Database not connected. Please check your connection.']);
            exit;
        }
        
        $id = $_POST['id'] ?? '';
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $databaseId = $_POST['database_id'] ?? '';
        $databaseName = $_POST['database_name'] ?? '';
        $databaseHost = $_POST['database_host'] ?? '';
        $databaseUser = $_POST['database_user'] ?? '';
        $databasePass = $_POST['database_pass'] ?? '';
        $databasePort = $_POST['database_port'] ?? '3306';
        $includeRemote = $_POST['include_remote'] ?? 0;
        $includeLocalhost = $_POST['include_localhost'] ?? 0;
        $backends = $_POST['backends'] ?? '[]';
        $pages = $_POST['pages'] ?? '[]';
        $frontends = $_POST['frontends'] ?? '[]';
        
        if ($name) {
            try {
                $startTime = microtime(true);
                
                if ($id) {
                    // Update existing project
                    $stmt = $pdo->prepare("UPDATE reporter_prompt_projects SET 
                        name = ?, description = ?, database_id = ?, database_name = ?, 
                        database_host = ?, database_user = ?, database_pass = ?, database_port = ?,
                        include_remote = ?, include_localhost = ?, backends = ?, pages = ?, frontends = ?
                        WHERE id = ?");
                    $stmt->execute([
                        $name, $description, $databaseId, $databaseName,
                        $databaseHost, $databaseUser, $databasePass, $databasePort,
                        $includeRemote, $includeLocalhost, $backends, $pages, $frontends, $id
                    ]);
                    $projectId = $id;
                    $message = 'Project updated successfully!';
                    $opType = 'UPDATE_PROJECT';
                } else {
                    // Create new project
                    $stmt = $pdo->prepare("INSERT INTO reporter_prompt_projects 
                        (name, description, database_id, database_name, database_host, database_user, 
                         database_pass, database_port, include_remote, include_localhost, backends, pages, frontends) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $name, $description, $databaseId, $databaseName,
                        $databaseHost, $databaseUser, $databasePass, $databasePort,
                        $includeRemote, $includeLocalhost, $backends, $pages, $frontends
                    ]);
                    $projectId = $pdo->lastInsertId();
                    $message = 'Project created successfully!';
                    $opType = 'CREATE_PROJECT';
                }
                
                $operationTime = round((microtime(true) - $startTime) * 1000, 2);
                echo json_encode([
                    'success' => true,
                    'id' => $projectId,
                    'message' => $message,
                    'operationTime' => $operationTime,
                    'operationType' => $opType,
                    'connectionType' => $connectionType
                ]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Project name is required']);
        }
        exit;
    }
    
    // Delete project
    if ($action === 'delete_project') {
        // Check database connection first
        if (!$pdo) {
            echo json_encode(['success' => false, 'message' => 'Database not connected. Please check your connection.']);
            exit;
        }
        
        $id = $_POST['id'] ?? '';
        
        if ($id) {
            try {
                $startTime = microtime(true);
                $stmt = $pdo->prepare("DELETE FROM reporter_prompt_projects WHERE id = ?");
                $stmt->execute([$id]);
                $operationTime = round((microtime(true) - $startTime) * 1000, 2);
                echo json_encode([
                    'success' => true,
                    'message' => 'Project deleted successfully!',
                    'operationTime' => $operationTime,
                    'operationType' => 'DELETE_PROJECT',
                    'connectionType' => $connectionType
                ]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Project ID is required']);
        }
        exit;
    }
    
    // File upload
    if ($action === 'upload_files') {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $uploadedFiles = [];
        
        if (isset($_FILES['files'])) {
            $files = $_FILES['files'];
            $fileCount = count($files['name']);
            
            for ($i = 0; $i < $fileCount; $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    $filename = basename($files['name'][$i]);
                    $filepath = $uploadDir . time() . '_' . $filename;
                    
                    if (move_uploaded_file($files['tmp_name'][$i], $filepath)) {
                        if ($pdo) {
                            $stmt = $pdo->prepare("INSERT INTO reporter_prompt_uploaded_files (filename, filepath, filesize, filetype) VALUES (?, ?, ?, ?)");
                            $stmt->execute([$filename, $filepath, $files['size'][$i], $files['type'][$i]]);
                        }
                        $uploadedFiles[] = [
                            'name' => $filename,
                            'path' => $filepath,
                            'size' => $files['size'][$i],
                            'type' => $files['type'][$i]
                        ];
                    }
                }
            }
        }
        
        echo json_encode(['success' => true, 'files' => $uploadedFiles]);
        exit;
    }
    
    // Get uploaded files
    if ($action === 'get_files') {
        if ($pdo) {
            try {
                $stmt = $pdo->query("SELECT * FROM reporter_prompt_uploaded_files ORDER BY uploaded_at DESC");
                $files = $stmt->fetchAll();
                echo json_encode(['success' => true, 'files' => $files]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        }
        exit;
    }
    
    // Delete file
    if ($action === 'delete_file') {
        $id = $_POST['id'] ?? '';
        
        if ($pdo && $id) {
            try {
                $startTime = microtime(true);
                $stmt = $pdo->prepare("SELECT filepath FROM reporter_prompt_uploaded_files WHERE id = ?");
                $stmt->execute([$id]);
                $file = $stmt->fetch();
                
                if ($file && file_exists($file['filepath'])) {
                    unlink($file['filepath']);
                }
                
                $stmt = $pdo->prepare("DELETE FROM reporter_prompt_uploaded_files WHERE id = ?");
                $stmt->execute([$id]);
                $operationTime = round((microtime(true) - $startTime) * 1000, 2);
                echo json_encode([
                    'success' => true, 
                    'message' => 'File deleted successfully!',
                    'operationTime' => $operationTime,
                    'operationType' => 'DELETE_FILE',
                    'connectionType' => $connectionType
                ]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prompt Manager - AI Prompt Generator</title>
    <link rel="icon" type="image/png" href="logoPM.png">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600&family=Space+Grotesk:wght@400;500;600;700&family=Caveat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-primary: #0a0a0f;
            --bg-secondary: #12121a;
            --bg-tertiary: #1a1a25;
            --bg-card: #15151f;
            --border-color: #2a2a3a;
            --border-glow: #4f46e5;
            --text-primary: #f0f0f5;
            --text-secondary: #a0a0b0;
            --text-muted: #606070;
            --accent-primary: #6366f1;
            --accent-secondary: #8b5cf6;
            --accent-tertiary: #a855f7;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --gradient-main: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #a855f7 100%);
            --gradient-dark: linear-gradient(180deg, #0a0a0f 0%, #12121a 100%);
            --shadow-glow: 0 0 40px rgba(99, 102, 241, 0.15);
            --shadow-card: 0 4px 24px rgba(0, 0, 0, 0.4);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Space Grotesk', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Background Effects */
        .bg-effects {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
        }

        .bg-effects::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at 30% 30%, rgba(99, 102, 241, 0.08) 0%, transparent 50%),
                        radial-gradient(circle at 70% 70%, rgba(139, 92, 246, 0.06) 0%, transparent 50%);
            animation: bgPulse 15s ease-in-out infinite;
        }

        @keyframes bgPulse {
            0%, 100% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(-5%, -5%) scale(1.1); }
        }

        /* Main Layout */
        .app-container {
            display: flex;
            min-height: 100vh;
            position: relative;
            z-index: 1;
        }

        /* Sidebar */
        .sidebar {
            width: 320px;
            min-width: 320px;
            background: var(--bg-secondary);
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            height: 100vh;
            position: sticky;
            top: 0;
            overflow: hidden;
        }

        .sidebar-header {
            padding: 0.75rem 1rem;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.15) 0%, rgba(139, 92, 246, 0.1) 50%, rgba(6, 182, 212, 0.1) 100%);
            border-bottom: 1px solid rgba(99, 102, 241, 0.2);
            text-align: center;
            position: relative;
        }

        .sidebar-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, #6366f1, #8b5cf6, #06b6d4);
        }

        .sidebar-header img {
            max-width: 140px;
            height: auto;
            filter: drop-shadow(0 2px 8px rgba(99, 102, 241, 0.3));
            transition: transform 0.3s ease;
        }

        .sidebar-header img:hover {
            transform: scale(1.05);
        }

        .sidebar-content {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
        }

        .sidebar-content::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar-content::-webkit-scrollbar-track {
            background: var(--bg-tertiary);
        }

        .sidebar-content::-webkit-scrollbar-thumb {
            background: var(--accent-primary);
            border-radius: 3px;
        }

        /* Section Titles */
        .section-title {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-title i {
            color: var(--accent-primary);
        }

        /* File Mode Toggle - Big Visible Buttons */
        .file-mode-toggle {
            margin-bottom: 1rem;
            padding: 0.75rem;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
        }

        .toggle-header {
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .toggle-header i {
            color: var(--accent-secondary);
        }

        .toggle-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .toggle-btn {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.3rem;
            padding: 0.6rem 0.5rem;
            background: var(--bg-tertiary);
            border: 2px solid var(--border-color);
            border-radius: 10px;
            color: var(--text-muted);
            font-family: inherit;
            font-size: 0.7rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.25s ease;
        }

        .toggle-btn i {
            font-size: 1.1rem;
        }

        .toggle-btn:hover {
            border-color: var(--accent-primary);
            color: var(--text-secondary);
            background: rgba(99, 102, 241, 0.1);
        }

        .toggle-btn.active {
            background: var(--gradient-main);
            border-color: var(--accent-primary);
            color: white;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);
        }

        .toggle-btn.active i {
            color: white;
        }

        /* Reference button special styling when active */
        .toggle-btn#btnReference.active {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            border-color: #f59e0b;
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.4);
        }

        /* Database Selector */
        .database-selector {
            margin-bottom: 1rem;
            padding: 0.75rem;
            background: linear-gradient(135deg, rgba(251, 191, 36, 0.08) 0%, rgba(245, 158, 11, 0.05) 100%);
            border: 1px solid rgba(251, 191, 36, 0.2);
            border-radius: 12px;
        }

        .db-selector-header {
            font-size: 0.7rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: space-between;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #fbbf24;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .db-selector-header i {
            color: #fbbf24;
        }

        .db-manage-btn {
            width: 24px;
            height: 24px;
            border: none;
            border-radius: 6px;
            background: rgba(251, 191, 36, 0.15);
            color: #fbbf24;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.25s ease;
            font-size: 0.7rem;
        }

        .db-manage-btn:hover {
            background: rgba(251, 191, 36, 0.3);
            transform: rotate(90deg);
        }

        .db-manage-btn i {
            font-size: 0.7rem;
        }

        /* Database Refresh Button */
        .db-refresh-btn {
            width: 32px;
            height: 32px;
            border: none;
            border-radius: 8px;
            background: linear-gradient(135deg, rgba(0, 212, 170, 0.15) 0%, rgba(0, 184, 148, 0.1) 100%);
            color: #00d4aa;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            flex-shrink: 0;
        }

        .db-refresh-btn:hover {
            background: linear-gradient(135deg, rgba(0, 212, 170, 0.3) 0%, rgba(0, 184, 148, 0.2) 100%);
            transform: rotate(180deg);
            box-shadow: 0 0 15px rgba(0, 212, 170, 0.3);
        }

        .db-refresh-btn:active {
            transform: rotate(360deg) scale(0.95);
        }

        .db-refresh-btn.spinning i {
            animation: dbRefreshSpin 0.8s linear infinite;
        }

        @keyframes dbRefreshSpin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .db-refresh-btn i {
            font-size: 0.8rem;
            transition: transform 0.3s ease;
        }

        .db-selector-row {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .db-checkbox-wrapper {
            flex-shrink: 0;
        }

        .db-checkbox-wrapper input {
            display: none;
        }

        .db-checkbox-box {
            width: 28px;
            height: 28px;
            border: 2px solid rgba(251, 191, 36, 0.3);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.25s ease;
            background: var(--bg-tertiary);
        }

        .db-checkbox-box:hover {
            border-color: #fbbf24;
            background: rgba(251, 191, 36, 0.1);
        }

        .db-checkbox-box i {
            font-size: 0.8rem;
            color: white;
            opacity: 0;
            transform: scale(0);
            transition: all 0.2s ease;
        }

        .db-checkbox-wrapper input:checked + .db-checkbox-box {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            border-color: #fbbf24;
            box-shadow: 0 3px 10px rgba(251, 191, 36, 0.4);
        }

        .db-checkbox-wrapper input:checked + .db-checkbox-box i {
            opacity: 1;
            transform: scale(1);
        }

        .db-dropdown-wrapper {
            flex: 1;
            position: relative;
        }

        .db-dropdown {
            width: 100%;
            padding: 0.6rem 2rem 0.6rem 0.75rem;
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-primary);
            font-family: inherit;
            font-size: 0.8rem;
            cursor: pointer;
            appearance: none;
            transition: all 0.2s ease;
        }

        .db-dropdown:hover {
            border-color: #fbbf24;
        }

        .db-dropdown:focus {
            outline: none;
            border-color: #fbbf24;
            box-shadow: 0 0 0 3px rgba(251, 191, 36, 0.15);
        }

        .db-dropdown option {
            background: var(--bg-secondary);
            color: var(--text-primary);
            padding: 8px;
        }

        .db-dropdown-arrow {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #fbbf24;
            font-size: 0.7rem;
            pointer-events: none;
        }

        .db-no-connections {
            font-size: 0.75rem;
            color: var(--text-muted);
            text-align: center;
            padding: 0.5rem;
            font-style: italic;
        }

        .db-no-connections a {
            color: #fbbf24;
            text-decoration: none;
        }

        .db-no-connections a:hover {
            text-decoration: underline;
        }

        /* Database Dropdown Row */
        .db-dropdown-row {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.6rem;
        }

        /* Credentials Checkboxes Row */
        .db-credentials-row {
            display: flex;
            gap: 0.5rem;
        }

        .db-cred-option {
            flex: 1;
            cursor: pointer;
        }

        .db-cred-option input {
            display: none;
        }

        .db-cred-box {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            padding: 0.5rem 0.6rem;
            background: var(--bg-tertiary);
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--text-muted);
            transition: all 0.25s ease;
        }

        .db-cred-box i {
            font-size: 0.8rem;
        }

        .db-cred-option:hover .db-cred-box {
            border-color: rgba(251, 191, 36, 0.5);
            background: rgba(251, 191, 36, 0.08);
            color: var(--text-secondary);
        }

        /* Remote checkbox active state - Blue */
        .db-cred-option.remote input:checked + .db-cred-box {
            background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
            border-color: #3b82f6;
            color: white;
            box-shadow: 0 3px 12px rgba(59, 130, 246, 0.4);
        }

        .db-cred-option.remote input:checked + .db-cred-box i {
            color: white;
        }

        /* Localhost checkbox active state - Green */
        .db-cred-option.localhost input:checked + .db-cred-box {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            border-color: #22c55e;
            color: white;
            box-shadow: 0 3px 12px rgba(34, 197, 94, 0.4);
        }

        .db-cred-option.localhost input:checked + .db-cred-box i {
            color: white;
        }

        /* File Upload Area */
        /* Two-Level File Picker */
        .file-picker-container {
            margin-bottom: 0.75rem;
        }

        /* Mini Drop Zone */
        .drop-zone-mini {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            padding: 0.75rem;
            background: var(--bg-card);
            border: 2px dashed var(--border-color);
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .drop-zone-mini:hover,
        .drop-zone-mini.dragover {
            border-color: var(--accent-primary);
            background: rgba(99, 102, 241, 0.08);
            border-style: solid;
        }

        .drop-zone-mini.dragover {
            transform: scale(1.02);
            box-shadow: 0 0 20px rgba(99, 102, 241, 0.2);
        }

        .drop-zone-mini i {
            font-size: 1.1rem;
            color: var(--accent-primary);
        }

        .drop-zone-mini span {
            font-size: 0.8rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        .drop-zone-mini:hover span {
            color: var(--accent-primary);
        }

        /* Uploaded Files Header */
        .uploaded-files-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .files-header-left {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 0.75rem;
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: 8px 8px 0 0;
            margin-bottom: 0;
        }

        .files-count {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .files-count i {
            color: var(--accent-secondary);
        }

        .btn-delete-all {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.35rem 0.7rem;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            border: none;
            border-radius: 6px;
            color: white;
            font-family: inherit;
            font-size: 0.7rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-delete-all:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
        }

        .btn-delete-all i {
            font-size: 0.75rem;
        }

        /* Uploaded Files List */
        .uploaded-files {
            margin-bottom: 1.5rem;
        }

        .uploaded-files-header + .uploaded-files {
            border-top: none;
            border-radius: 0 0 8px 8px;
        }

        .file-item {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.2s;
            position: relative;
        }

        .file-item:hover {
            border-color: var(--accent-primary);
            transform: translateX(3px);
            background: rgba(99, 102, 241, 0.05);
        }

        .file-item.checked {
            border-color: var(--success);
            background: rgba(16, 185, 129, 0.1);
        }

        .file-item-checkbox {
            flex-shrink: 0;
            cursor: pointer;
        }

        .file-item-checkbox input {
            display: none;
        }

        .file-item-checkbox .checkbox-box {
            width: 22px;
            height: 22px;
            border: 2px solid var(--border-color);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            background: var(--bg-tertiary);
        }

        .file-item-checkbox .checkbox-box i {
            font-size: 0.7rem;
            color: white;
            opacity: 0;
            transform: scale(0);
            transition: all 0.2s;
        }

        .file-item.checked .file-item-checkbox .checkbox-box {
            background: linear-gradient(135deg, var(--success), #059669);
            border-color: var(--success);
        }

        .file-item.checked .file-item-checkbox .checkbox-box i {
            opacity: 1;
            transform: scale(1);
        }

        .file-item-icon {
            color: var(--accent-secondary);
            font-size: 1.25rem;
        }

        .file-info {
            flex: 1;
            min-width: 0;
        }

        .file-name {
            font-size: 0.85rem;
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .file-size {
            font-size: 0.7rem;
            color: var(--text-muted);
        }

        .file-delete {
            background: none;
            border: none;
            color: var(--danger);
            cursor: pointer;
            padding: 0.25rem;
            opacity: 0.6;
            transition: opacity 0.2s;
        }

        .file-delete:hover {
            opacity: 1;
        }

        /* Prompt Search Box */
        .prompt-search-box {
            position: relative;
            margin-bottom: 0.75rem;
        }

        .prompt-search-box input {
            width: 100%;
            padding: 0.7rem 2.5rem 0.7rem 2.5rem;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            color: var(--text-primary);
            font-family: inherit;
            font-size: 0.85rem;
            outline: none;
            transition: all 0.2s ease;
        }

        .prompt-search-box input:focus {
            border-color: var(--accent-primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        }

        .prompt-search-box input::placeholder {
            color: var(--text-muted);
        }

        .prompt-search-box > i {
            position: absolute;
            left: 0.9rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 0.85rem;
            pointer-events: none;
        }

        .prompt-search-clear {
            position: absolute;
            right: 0.5rem;
            top: 50%;
            transform: translateY(-50%);
            background: var(--bg-tertiary);
            border: none;
            color: var(--text-muted);
            width: 24px;
            height: 24px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .prompt-search-clear:hover {
            background: var(--danger);
            color: white;
        }

        /* Prompt Actions (Select All / Deselect All) */
        .prompt-actions {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
            align-items: center;
        }

        .prompt-action-btn {
            flex: 1;
            padding: 0.45rem 0.5rem;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-muted);
            font-family: inherit;
            font-size: 0.7rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.3rem;
        }

        .prompt-action-btn:hover {
            border-color: var(--accent-primary);
            color: var(--accent-primary);
            background: rgba(99, 102, 241, 0.1);
        }

        .prompt-action-btn i {
            font-size: 0.75rem;
        }

        .prompt-counter {
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--accent-primary);
            background: rgba(99, 102, 241, 0.15);
            padding: 0.3rem 0.6rem;
            border-radius: 6px;
            white-space: nowrap;
        }

        /* Select All Checkbox Style */
        .select-all-checkbox {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            padding: 0.4rem 0.75rem;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            transition: all 0.2s ease;
            user-select: none;
        }

        .select-all-checkbox:hover {
            border-color: var(--accent-primary);
            background: rgba(99, 102, 241, 0.1);
        }

        .select-all-checkbox input[type="checkbox"] {
            display: none;
        }

        .select-all-checkbox .checkbox-custom {
            width: 18px;
            height: 18px;
            border: 2px solid var(--border-color);
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            background: var(--bg-tertiary);
            flex-shrink: 0;
        }

        .select-all-checkbox .checkbox-custom::after {
            content: '✓';
            font-size: 12px;
            font-weight: bold;
            color: white;
            opacity: 0;
            transform: scale(0);
            transition: all 0.2s ease;
        }

        .select-all-checkbox input[type="checkbox"]:checked + .checkbox-custom {
            background: linear-gradient(135deg, var(--accent-primary), #818cf8);
            border-color: var(--accent-primary);
        }

        .select-all-checkbox input[type="checkbox"]:checked + .checkbox-custom::after {
            opacity: 1;
            transform: scale(1);
        }

        .select-all-checkbox .checkbox-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-muted);
            transition: color 0.2s;
        }

        .select-all-checkbox:hover .checkbox-label {
            color: var(--accent-primary);
        }

        .select-all-checkbox input[type="checkbox"]:checked ~ .checkbox-label {
            color: var(--accent-primary);
        }

        /* Indeterminate state (partial selection) */
        .select-all-checkbox input[type="checkbox"]:indeterminate + .checkbox-custom {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            border-color: #f59e0b;
        }

        .select-all-checkbox input[type="checkbox"]:indeterminate + .checkbox-custom::after {
            content: '−';
            opacity: 1;
            transform: scale(1);
        }

        .select-all-checkbox input[type="checkbox"]:indeterminate ~ .checkbox-label {
            color: #f59e0b;
        }

        /* Prompt No Results */
        .prompt-no-results {
            text-align: center;
            padding: 1.5rem;
            color: var(--text-muted);
        }

        .prompt-no-results i {
            font-size: 2rem;
            margin-bottom: 0.75rem;
            opacity: 0.5;
            display: block;
        }

        .prompt-no-results p {
            font-size: 0.85rem;
        }

        /* Section Title Row */
        .section-title-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.75rem;
        }

        .section-title-row .section-title {
            margin-bottom: 0;
        }

        .btn-add-template {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
            border: none;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            font-size: 0.9rem;
        }

        .btn-add-template:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);
        }

        /* Template Loading */
        .template-loading {
            text-align: center;
            padding: 1.5rem;
            color: var(--text-muted);
        }

        .template-loading i {
            font-size: 1.5rem;
            margin-right: 0.5rem;
            color: var(--accent-primary);
        }

        /* Prompt Checkboxes */
        .prompt-list {
            margin-top: 0;
            max-height: calc(100vh - 480px);
            min-height: 300px;
            overflow-y: auto;
        }

        /* Prompt Item with Actions */
        .prompt-item {
            display: flex;
            align-items: center;
            padding: 0.65rem 0.75rem;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            margin-bottom: 0.5rem;
            transition: all 0.2s;
            gap: 0.5rem;
        }

        .prompt-item:hover {
            border-color: var(--accent-primary);
            background: rgba(99, 102, 241, 0.05);
        }

        .prompt-item.checked {
            border-color: var(--accent-primary);
            background: rgba(99, 102, 241, 0.1);
        }

        .prompt-item-checkbox {
            flex-shrink: 0;
        }

        .prompt-item-checkbox input {
            display: none;
        }

        .prompt-item-checkbox .checkbox-box {
            width: 22px;
            height: 22px;
            border: 2px solid var(--border-color);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            background: var(--bg-tertiary);
        }

        .prompt-item-checkbox .checkbox-box i {
            font-size: 0.7rem;
            color: white;
            opacity: 0;
            transform: scale(0);
            transition: all 0.2s;
        }

        .prompt-item.checked .checkbox-box {
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
            border-color: var(--accent-primary);
        }

        .prompt-item.checked .checkbox-box i {
            opacity: 1;
            transform: scale(1);
        }

        .prompt-item-content {
            flex: 1;
            min-width: 0;
            cursor: pointer;
        }

        .prompt-item-name {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .prompt-item-preview {
            font-size: 0.7rem;
            color: var(--text-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-top: 2px;
        }

        .prompt-item-actions {
            display: flex;
            gap: 4px;
            flex-shrink: 0;
            opacity: 0.5;
            transition: opacity 0.2s;
        }

        .prompt-item:hover .prompt-item-actions {
            opacity: 1;
        }

        .prompt-action-icon {
            width: 26px;
            height: 26px;
            border-radius: 6px;
            border: none;
            background: var(--bg-tertiary);
            color: var(--text-muted);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            font-size: 0.7rem;
        }

        .prompt-action-icon:hover {
            transform: scale(1.1);
        }

        .prompt-action-icon.copy:hover {
            background: rgba(16, 185, 129, 0.2);
            color: var(--success);
        }

        .prompt-action-icon.edit:hover {
            background: rgba(99, 102, 241, 0.2);
            color: var(--accent-primary);
        }

        .prompt-action-icon.delete:hover {
            background: rgba(239, 68, 68, 0.2);
            color: var(--danger);
        }

        .prompt-action-icon.pull:hover {
            background: rgba(251, 191, 36, 0.2);
            color: #fbbf24;
        }

        /* Template Modal */
        .template-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .template-modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .template-modal {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            width: 90%;
            max-width: 550px;
            max-height: 85vh;
            overflow: hidden;
            transform: scale(0.9) translateY(20px);
            transition: all 0.3s ease;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.4);
        }

        .template-modal-overlay.active .template-modal {
            transform: scale(1) translateY(0);
        }

        .template-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.1));
        }

        .template-modal-header h3 {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0;
        }

        .template-modal-header h3 i {
            color: var(--accent-primary);
        }

        .template-modal-close {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            border: none;
            background: var(--bg-tertiary);
            color: var(--text-muted);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .template-modal-close:hover {
            background: var(--danger);
            color: white;
            transform: scale(1.1);
        }

        .template-modal-body {
            padding: 1.5rem;
            overflow-y: auto;
            max-height: calc(85vh - 160px);
        }

        .template-form-group {
            margin-bottom: 1.25rem;
        }

        .template-form-group label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
        }

        .template-form-group label i {
            color: var(--accent-primary);
            font-size: 0.8rem;
        }

        .template-form-group input,
        .template-form-group textarea {
            width: 100%;
            padding: 0.85rem 1rem;
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            color: var(--text-primary);
            font-family: inherit;
            font-size: 0.9rem;
            outline: none;
            transition: all 0.2s;
        }

        .template-form-group input:focus,
        .template-form-group textarea:focus {
            border-color: var(--accent-primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        }

        .template-form-group textarea {
            resize: vertical;
            min-height: 150px;
            font-family: 'JetBrains Mono', 'Fira Code', monospace;
            font-size: 0.8rem;
            line-height: 1.6;
        }

        .template-modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--border-color);
            background: var(--bg-tertiary);
        }

        .template-btn {
            padding: 0.7rem 1.25rem;
            border-radius: 10px;
            border: none;
            font-family: inherit;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }

        .template-btn.cancel {
            background: var(--bg-card);
            color: var(--text-muted);
            border: 1px solid var(--border-color);
        }

        .template-btn.cancel:hover {
            background: var(--bg-tertiary);
            color: var(--text-primary);
        }

        .template-btn.save {
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
            color: white;
        }

        .template-btn.save:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(99, 102, 241, 0.4);
        }

        .template-btn.secondary {
            background: var(--bg-card);
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
        }

        .template-btn.secondary:hover {
            background: rgba(16, 185, 129, 0.1);
            border-color: var(--success);
            color: var(--success);
        }

        .template-btn.edit {
            background: rgba(99, 102, 241, 0.1);
            color: var(--accent-primary);
            border: 1px solid var(--accent-primary);
        }

        .template-btn.edit:hover {
            background: var(--accent-primary);
            color: white;
        }

        .template-btn.warning {
            background: rgba(251, 191, 36, 0.1);
            color: #fbbf24;
            border: 1px solid rgba(251, 191, 36, 0.5);
        }

        .template-btn.warning:hover {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            color: white;
            border-color: #fbbf24;
            box-shadow: 0 5px 20px rgba(251, 191, 36, 0.3);
        }

        /* Template Preview */
        .template-preview-modal {
            max-width: 650px;
        }

        .template-preview-name {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border-color);
        }

        .template-preview-content {
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 1rem;
            font-family: 'JetBrains Mono', 'Fira Code', monospace;
            font-size: 0.8rem;
            line-height: 1.6;
            color: var(--text-secondary);
            white-space: pre-wrap;
            max-height: 300px;
            overflow-y: auto;
        }

        /* Highlight search match */
        .prompt-label .highlight,
        .prompt-item-name .highlight {
            background: rgba(99, 102, 241, 0.3);
            color: var(--accent-primary);
            padding: 0 2px;
            border-radius: 2px;
        }

        .prompt-checkbox {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.85rem 1rem;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            margin-bottom: 0.5rem;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .prompt-checkbox::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 3px;
            background: var(--gradient-main);
            opacity: 0;
            transition: opacity 0.2s;
        }

        .prompt-checkbox:hover {
            border-color: var(--accent-primary);
            transform: translateX(3px);
        }

        .prompt-checkbox:hover::before {
            opacity: 1;
        }

        .prompt-checkbox.checked {
            background: rgba(99, 102, 241, 0.1);
            border-color: var(--accent-primary);
        }

        .prompt-checkbox.checked::before {
            opacity: 1;
        }

        .prompt-checkbox input {
            display: none;
        }

        .checkbox-custom {
            width: 20px;
            height: 20px;
            border: 2px solid var(--border-color);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            flex-shrink: 0;
        }

        .prompt-checkbox.checked .checkbox-custom {
            background: var(--gradient-main);
            border-color: var(--accent-primary);
        }

        .checkbox-custom i {
            color: white;
            font-size: 0.7rem;
            opacity: 0;
            transform: scale(0);
            transition: all 0.2s;
        }

        .prompt-checkbox.checked .checkbox-custom i {
            opacity: 1;
            transform: scale(1);
        }

        .prompt-label {
            font-size: 0.9rem;
            font-weight: 500;
            flex: 1;
        }

        .prompt-number {
            font-size: 0.7rem;
            color: var(--accent-primary);
            background: rgba(99, 102, 241, 0.15);
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-weight: 600;
        }

        /* Main Content Area */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 1.5rem;
            gap: 1.5rem;
            background: var(--gradient-dark);
            max-width: calc(100vw - 320px);
            overflow-x: hidden;
            box-sizing: border-box;
        }
        
        /* Ensure all main content children respect container width */
        .main-content > * {
            max-width: 100%;
            box-sizing: border-box;
        }

        /* Editor Container */
        .editor-container {
            display: flex;
            flex-direction: column;
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            overflow: visible;
            box-shadow: var(--shadow-card);
            max-width: 100%;
            width: 100%;
            box-sizing: border-box;
        }

        .editor-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.85rem 1.5rem;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.08) 0%, rgba(139, 92, 246, 0.05) 50%, rgba(6, 182, 212, 0.05) 100%);
            border-bottom: 1px solid rgba(99, 102, 241, 0.15);
            position: relative;
        }

        .editor-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, #6366f1, #8b5cf6, #06b6d4);
            border-radius: 16px 16px 0 0;
        }

        .editor-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 600;
        }

        .editor-title i {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 1.25rem;
        }

        .editor-title span {
            background: linear-gradient(135deg, #e0e7ff, #c7d2fe);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Editor Search Bar */
        .editor-search-bar {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-left: 1rem;
            padding: 0.35rem 0.6rem;
            background: rgba(15, 15, 35, 0.6);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .editor-search-bar:focus-within {
            border-color: var(--accent-primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
            background: rgba(15, 15, 35, 0.8);
        }

        .editor-search-bar.has-results {
            border-color: var(--success);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15);
        }

        .editor-search-bar.no-results {
            border-color: var(--danger);
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15);
        }

        .search-icon {
            color: var(--text-muted);
            font-size: 0.8rem;
            transition: color 0.2s;
        }

        .editor-search-bar:focus-within .search-icon {
            color: var(--accent-primary);
        }

        .editor-search-bar.has-results .search-icon {
            color: var(--success);
        }

        .editor-search-bar.no-results .search-icon {
            color: var(--danger);
        }

        .editor-search-input {
            width: 140px;
            padding: 0.3rem 0;
            background: transparent;
            border: none;
            outline: none;
            color: var(--text-primary);
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.8rem;
            transition: width 0.3s ease;
        }

        .editor-search-input::placeholder {
            color: var(--text-muted);
            font-style: italic;
        }

        .editor-search-bar:focus-within .editor-search-input {
            width: 180px;
        }

        .search-results-badge {
            display: none;
            padding: 0.15rem 0.5rem;
            background: rgba(99, 102, 241, 0.2);
            border-radius: 10px;
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--accent-primary);
            white-space: nowrap;
        }

        .editor-search-bar.has-results .search-results-badge {
            display: flex;
            background: rgba(16, 185, 129, 0.2);
            color: var(--success);
        }

        .editor-search-bar.no-results .search-results-badge {
            display: flex;
            background: rgba(239, 68, 68, 0.2);
            color: var(--danger);
        }

        .search-nav-btns {
            display: none;
            gap: 2px;
        }

        .editor-search-bar.has-results .search-nav-btns {
            display: flex;
        }

        .search-nav-btn {
            width: 22px;
            height: 22px;
            padding: 0;
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: 5px;
            color: var(--text-muted);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.65rem;
            transition: all 0.2s;
        }

        .search-nav-btn:hover {
            background: var(--accent-primary);
            border-color: var(--accent-primary);
            color: white;
        }

        /* Editor Search Clear Button - Elegant X */
        #searchClearBtn {
            position: static !important;
            right: unset !important;
            top: unset !important;
            transform: none !important;
            width: 24px !important;
            height: 24px !important;
            min-width: 24px !important;
            padding: 0 !important;
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.25) 0%, rgba(220, 38, 38, 0.35) 100%) !important;
            border: 2px solid rgba(239, 68, 68, 0.6) !important;
            border-radius: 50% !important;
            color: #f87171 !important;
            cursor: pointer !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 0.85rem !important;
            font-weight: bold !important;
            transition: all 0.25s ease !important;
            opacity: 0;
            pointer-events: none;
            margin-left: 6px !important;
            flex-shrink: 0 !important;
        }

        .editor-search-bar.has-value #searchClearBtn {
            opacity: 1 !important;
            pointer-events: auto !important;
            animation: fadeInScale 0.2s ease forwards;
        }

        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.8); }
            to { opacity: 1; transform: scale(1); }
        }

        #searchClearBtn:hover {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
            border-color: #ef4444 !important;
            color: white !important;
            transform: scale(1.15) rotate(90deg) !important;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.6) !important;
        }

        #searchClearBtn:active {
            transform: scale(0.95) rotate(90deg) !important;
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4) !important;
        }

        .logout-btn {
            margin-left: auto;
            padding: 0.4rem 0.6rem;
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 8px;
            color: #f87171;
            font-size: 0.85rem;
            text-decoration: none;
            transition: all 0.2s;
            display: flex;
            align-items: center;
        }

        .logout-btn:hover {
            background: rgba(239, 68, 68, 0.25);
            border-color: #ef4444;
            color: #ef4444;
        }

        .editor-actions {
            display: flex;
            gap: 0.75rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: 8px;
            font-family: inherit;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-primary {
            background: var(--gradient-main);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);
        }

        .btn-secondary {
            background: var(--bg-card);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            border-color: var(--accent-primary);
            background: rgba(99, 102, 241, 0.1);
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-success:hover {
            background: #0ea572;
            transform: translateY(-2px);
        }

        .btn-paste {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }

        .btn-paste:hover {
            background: linear-gradient(135deg, #d97706, #b45309);
            transform: translateY(-2px);
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        .btn-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }

        .btn-warning:hover {
            background: linear-gradient(135deg, #d97706, #b45309);
        }

        /* History Navigation (Undo/Redo) */
        .history-navigation {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.35rem;
            background: rgba(15, 15, 35, 0.6);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            margin-right: 0.75rem;
        }

        .history-btn {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 1rem;
        }

        .history-btn:hover:not(:disabled) {
            background: rgba(99, 102, 241, 0.15);
            border-color: var(--accent-primary);
            color: var(--accent-primary);
            transform: scale(1.08);
        }

        .history-btn:active:not(:disabled) {
            transform: scale(0.95);
        }

        .history-btn:disabled {
            opacity: 0.35;
            cursor: not-allowed;
            color: var(--text-muted);
        }

        .history-btn i {
            transition: transform 0.2s ease;
        }

        .history-btn:hover:not(:disabled) i {
            transform: translateX(-2px);
        }

        .history-btn.redo:hover:not(:disabled) i {
            transform: translateX(2px);
        }

        .history-btn .history-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            min-width: 18px;
            height: 18px;
            padding: 0 4px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border-radius: 9px;
            font-size: 0.65rem;
            font-weight: 700;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transform: scale(0);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 2px 8px rgba(99, 102, 241, 0.4);
        }

        .history-btn:not(:disabled) .history-badge {
            opacity: 1;
            transform: scale(1);
        }

        .history-btn.undo .history-badge {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            box-shadow: 0 2px 8px rgba(245, 158, 11, 0.4);
        }

        .history-btn.redo .history-badge {
            background: linear-gradient(135deg, #10b981, #059669);
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.4);
        }

        .history-btn.clear {
            width: 32px;
            height: 32px;
            font-size: 0.85rem;
            background: rgba(239, 68, 68, 0.1);
            border-color: rgba(239, 68, 68, 0.3);
            color: #f87171;
        }

        .history-btn.clear:hover:not(:disabled) {
            background: rgba(239, 68, 68, 0.25);
            border-color: #ef4444;
            color: #ef4444;
            transform: scale(1.1) rotate(90deg);
        }

        .history-btn.clear:active:not(:disabled) {
            transform: scale(0.9) rotate(90deg);
        }

        .history-divider {
            width: 1px;
            height: 24px;
            background: var(--border-color);
            margin: 0 0.15rem;
        }

        .history-info {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 0 0.5rem;
            min-width: 50px;
        }

        .history-position {
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--accent-primary);
            line-height: 1;
        }

        .history-label {
            font-size: 0.55rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 2px;
        }

        /* History button pulse animation on state change */
        @keyframes historyPulse {
            0% { box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.5); }
            70% { box-shadow: 0 0 0 8px rgba(99, 102, 241, 0); }
            100% { box-shadow: 0 0 0 0 rgba(99, 102, 241, 0); }
        }

        .history-btn.pulse {
            animation: historyPulse 0.5s ease-out;
        }

        /* Folder Picker Group */
        .folder-picker-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-left: 0.5rem;
            padding-left: 1rem;
            border-left: 2px solid var(--border-color);
        }

        .btn-folder {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
            color: white;
            position: relative;
        }

        .btn-folder:hover {
            background: linear-gradient(135deg, #7c3aed, #6d28d9);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(139, 92, 246, 0.4);
        }

        .btn-folder.connected {
            background: linear-gradient(135deg, #10b981, #059669);
        }

        .btn-folder.connected:hover {
            background: linear-gradient(135deg, #059669, #047857);
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
        }

        .btn-folder .folder-status {
            position: absolute;
            top: -4px;
            right: -4px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--danger);
            border: 2px solid var(--bg-card);
        }

        .btn-folder.connected .folder-status {
            background: #22c55e;
            animation: pulse-status 2s infinite;
        }

        .btn-folder.needs-reconnect {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            animation: pulse-reconnect 2s infinite;
        }

        .btn-folder.needs-reconnect:hover {
            background: linear-gradient(135deg, #d97706, #b45309);
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.4);
        }

        .btn-folder.needs-reconnect .folder-status {
            background: #f59e0b;
            animation: pulse-status 1s infinite;
        }

        @keyframes pulse-reconnect {
            0%, 100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.4); }
            50% { box-shadow: 0 0 0 6px rgba(245, 158, 11, 0); }
        }

        @keyframes pulse-status {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.2); opacity: 0.8; }
        }

        .btn-send {
            background: linear-gradient(135deg, #06b6d4, #0891b2);
            color: white;
        }

        .btn-send:hover {
            background: linear-gradient(135deg, #0891b2, #0e7490);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(6, 182, 212, 0.4);
        }

        .btn-send:disabled {
            background: var(--bg-tertiary);
            color: var(--text-muted);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .btn-send.auto-active {
            animation: pulse-send 1.5s infinite;
        }

        @keyframes pulse-send {
            0%, 100% { box-shadow: 0 0 0 0 rgba(6, 182, 212, 0.4); }
            50% { box-shadow: 0 0 0 8px rgba(6, 182, 212, 0); }
        }

        .btn-pull {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .btn-pull:hover {
            background: linear-gradient(135deg, #059669, #047857);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
        }

        .btn-pull:disabled {
            background: var(--bg-tertiary);
            color: var(--text-muted);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        /* Clear Buttons */
        .btn-clear-picker {
            padding: 0.4rem 0.5rem;
            background: transparent;
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 6px;
            color: #f87171;
            cursor: pointer;
            font-size: 0.75rem;
            transition: all 0.2s;
            display: none;
        }

        .btn-clear-picker:hover {
            background: rgba(239, 68, 68, 0.15);
            border-color: #ef4444;
            color: #ef4444;
        }

        .btn-clear-picker.show {
            display: flex;
            align-items: center;
        }

        /* Auto-Send Timer */
        .auto-send-timer {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.4rem 0.6rem;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            transition: all 0.2s;
        }

        .auto-send-timer:hover {
            border-color: var(--accent-primary);
        }

        .auto-send-timer.active {
            border-color: #06b6d4;
            background: rgba(6, 182, 212, 0.1);
        }

        .auto-send-timer i {
            color: var(--text-muted);
            font-size: 0.85rem;
        }

        .auto-send-timer.active i {
            color: #06b6d4;
            animation: spin-timer 2s linear infinite;
        }

        @keyframes spin-timer {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .timer-input {
            width: 45px;
            padding: 0.3rem 0.4rem;
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            color: var(--text-primary);
            font-size: 0.8rem;
            font-weight: 600;
            text-align: center;
            font-family: 'JetBrains Mono', monospace;
        }

        .timer-input:focus {
            outline: none;
            border-color: #06b6d4;
            box-shadow: 0 0 0 2px rgba(6, 182, 212, 0.2);
        }

        .timer-input::-webkit-inner-spin-button,
        .timer-input::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .timer-label {
            font-size: 0.7rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        .auto-send-timer.active .timer-label {
            color: #06b6d4;
        }

        .timer-countdown {
            display: none;
            font-size: 0.75rem;
            font-weight: 700;
            color: #06b6d4;
            min-width: 25px;
            text-align: center;
            font-family: 'JetBrains Mono', monospace;
        }

        .auto-send-timer.active .timer-countdown {
            display: inline-block;
        }

        .folder-path-indicator {
            display: none;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 0.75rem;
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 6px;
            font-size: 0.75rem;
            color: var(--success);
            max-width: 200px;
        }

        .folder-path-indicator.show {
            display: flex;
        }

        .folder-path-indicator i {
            flex-shrink: 0;
        }

        .folder-path-indicator span {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .folder-path-indicator.disconnected {
            background: rgba(245, 158, 11, 0.1);
            border-color: rgba(245, 158, 11, 0.3);
            color: #f59e0b;
        }

        .folder-path-indicator.disconnected i {
            animation: blink 1.5s infinite;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }

        /* Text Editor */
        .editor-body {
            position: relative;
            min-height: 200px;
        }

        /* Highlight Overlay for Search */
        .editor-highlight-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 20px; /* Account for resize handle */
            padding: 1.5rem;
            padding-bottom: 2rem;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.95rem;
            line-height: 1.7;
            white-space: pre-wrap;
            word-wrap: break-word;
            overflow: hidden;
            pointer-events: none;
            color: var(--text-primary);
            background: var(--bg-primary);
            box-sizing: border-box;
            z-index: 1;
            display: none;
        }

        /* Show overlay when searching */
        .editor-body.searching .editor-highlight-overlay {
            display: block;
        }

        .editor-highlight-overlay mark {
            background: #fef08a;
            color: #1a1a1a !important;
            border-radius: 3px;
            padding: 1px 3px;
            box-shadow: 0 0 0 2px #fef08a;
            font-weight: 600;
        }

        .editor-highlight-overlay mark.current {
            background: #facc15;
            color: #000000 !important;
            box-shadow: 0 0 0 3px #facc15, 0 0 10px rgba(250, 204, 21, 0.6);
            font-weight: 700;
        }

        #promptEditor {
            position: relative;
            z-index: 2;
            width: 100%;
            min-height: 200px;
            max-height: 80vh;
            height: 280px;
            padding: 1.5rem;
            padding-bottom: 2rem;
            background: transparent;
            color: var(--text-primary);
            border: none;
            border-radius: 0;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.95rem;
            line-height: 1.7;
            resize: vertical;
            outline: none;
            overflow-y: auto;
            box-sizing: border-box;
            caret-color: var(--text-primary);
        }

        /* When not searching, show solid background */
        #promptEditor:not(.searching) {
            background: var(--bg-primary);
        }

        /* When searching, make text transparent to show overlay highlights */
        #promptEditor.searching {
            color: transparent;
            caret-color: var(--accent-primary);
        }

        #promptEditor::placeholder {
            color: var(--text-muted);
        }

        /* Custom Resize Handle */
        .resize-handle {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 20px;
            background: var(--bg-tertiary);
            border-top: 1px solid var(--border-color);
            cursor: ns-resize;
            user-select: none;
            transition: all 0.2s ease;
        }

        .resize-handle:hover {
            background: rgba(99, 102, 241, 0.2);
        }

        .resize-handle:active {
            background: rgba(99, 102, 241, 0.3);
        }

        .resize-handle i {
            color: var(--text-muted);
            font-size: 0.7rem;
            transition: color 0.2s;
        }

        .resize-handle:hover i {
            color: var(--accent-primary);
        }

        /* File Transfer Section */
        .file-transfer-section {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.6rem 1rem;
            background: var(--bg-secondary);
            border-top: 1px solid var(--border-color);
            gap: 1rem;
        }

        .file-transfer-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex: 1;
        }

        .file-transfer-group.right {
            justify-content: flex-end;
        }

        .file-transfer-group .file-picker-btn {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.4rem 0.7rem;
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            color: var(--text-secondary);
            font-size: 0.75rem;
            cursor: pointer;
            transition: all 0.2s;
            max-width: 140px;
            overflow: hidden;
        }

        .file-transfer-group .file-picker-btn:hover {
            border-color: var(--accent-primary);
            color: var(--text-primary);
        }

        .file-transfer-group .file-picker-btn.has-file {
            border-color: var(--accent-secondary);
            background: rgba(139, 92, 246, 0.1);
            color: var(--accent-secondary);
        }

        .file-transfer-group .file-picker-btn.has-file.needs-reconnect {
            border-color: rgba(245, 158, 11, 0.5);
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
            border-style: dashed;
        }

        .file-transfer-group .file-picker-btn.has-file.needs-reconnect:hover {
            border-color: #f59e0b;
            background: rgba(245, 158, 11, 0.15);
        }

        .file-transfer-group .file-picker-btn .file-name {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 80px;
        }

        .file-transfer-group .btn-file-action {
            padding: 0.35rem 0.6rem;
            font-size: 0.7rem;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .file-transfer-group .btn-file-pull {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .file-transfer-group .btn-file-pull:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.4);
        }

        .file-transfer-group .btn-file-push {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }

        .file-transfer-group .btn-file-push:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(245, 158, 11, 0.4);
        }

        .file-transfer-group .btn-file-action:disabled {
            background: var(--bg-tertiary);
            color: var(--text-muted);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .file-transfer-divider {
            width: 1px;
            height: 30px;
            background: var(--border-color);
        }

        /* Move Folder Group */
        .move-folder-group {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0 0.6rem;
            border-left: 1px solid var(--border-color);
            border-right: 1px solid var(--border-color);
        }

        .btn-move-folder {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.3rem;
            padding: 0.4rem 0.7rem;
            border-radius: 6px;
            border: 1px solid rgba(59, 130, 246, 0.3);
            background: var(--bg-tertiary);
            color: #3b82f6;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.75rem;
            font-family: inherit;
            font-weight: 500;
        }

        .btn-move-folder:hover {
            transform: translateY(-1px);
            background: rgba(59, 130, 246, 0.15);
            border-color: #3b82f6;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
        }

        .btn-move-folder i {
            font-size: 0.85rem;
        }

        /* File Management Buttons (Create, Delete, Rename) */
        .file-management-group {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0 0.5rem;
            border-left: 1px solid var(--border-color);
            border-right: 1px solid var(--border-color);
        }

        .btn-file-manage {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 6px;
            border: 1px solid var(--border-color);
            background: var(--bg-tertiary);
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.8rem;
        }

        .btn-file-manage:hover {
            transform: translateY(-1px);
        }

        .btn-file-manage.btn-create {
            color: #10b981;
            border-color: rgba(16, 185, 129, 0.3);
        }

        .btn-file-manage.btn-create:hover {
            background: rgba(16, 185, 129, 0.15);
            border-color: #10b981;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
        }

        .btn-file-manage.btn-delete {
            color: #ef4444;
            border-color: rgba(239, 68, 68, 0.3);
        }

        .btn-file-manage.btn-delete:hover {
            background: rgba(239, 68, 68, 0.15);
            border-color: #ef4444;
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
        }

        .btn-file-manage.btn-rename {
            color: #f59e0b;
            border-color: rgba(245, 158, 11, 0.3);
        }

        .btn-file-manage.btn-rename:hover {
            background: rgba(245, 158, 11, 0.15);
            border-color: #f59e0b;
            box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
        }

        .btn-file-manage.btn-folder {
            color: #3b82f6;
            border-color: rgba(59, 130, 246, 0.3);
        }

        .btn-file-manage.btn-folder:hover {
            background: rgba(59, 130, 246, 0.15);
            border-color: #3b82f6;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
        }

        .btn-file-manage.btn-delete-folder {
            color: #3b82f6;
            border-color: rgba(59, 130, 246, 0.3);
        }

        .btn-file-manage.btn-delete-folder:hover {
            background: rgba(59, 130, 246, 0.15);
            border-color: #3b82f6;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
        }

        .editor-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 1.5rem;
            background: var(--bg-tertiary);
            border-top: 1px solid var(--border-color);
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .char-count {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .char-count i {
            color: var(--accent-secondary);
        }

        /* Saved Prompts Section - Horizontal Layout */
        .saved-prompts-section {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            position: relative;
            margin-bottom: 1rem;
            max-width: 100%;
            width: 100%;
            box-sizing: border-box;
        }

        .saved-resize-handle {
            display: none;
        }

        .saved-actions-bar {
            padding: 0.5rem 1rem !important;
            border-bottom: none !important;
            flex-shrink: 0;
        }

        /* Development Dashboard Section - PRO REDESIGN */
        .dev-dashboard-section {
            background: linear-gradient(180deg, rgba(18, 18, 28, 0.95) 0%, rgba(15, 15, 25, 1) 100%);
            border: 1px solid rgba(99, 102, 241, 0.12);
            border-radius: 20px;
            overflow: hidden;
            margin-bottom: 1.5rem;
            position: relative;
            max-width: 100%;
            width: 100%;
            box-sizing: border-box;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.3),
                        inset 0 1px 0 rgba(255, 255, 255, 0.03);
        }
        
        .dev-dashboard-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, 
                #06b6d4 0%, 
                #a78bfa 33%, 
                #fb923c 66%, 
                #10b981 100%);
            opacity: 0.8;
        }

        .dev-dashboard-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.25rem;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.04) 0%, rgba(139, 92, 246, 0.02) 50%, transparent 100%);
            border-bottom: 1px solid rgba(99, 102, 241, 0.1);
        }

        .dev-dashboard-left {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .dev-dashboard-title {
            display: flex;
            align-items: center;
            gap: 0.65rem;
        }
        
        .dev-dashboard-tabs {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .dev-tab {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.35rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-muted);
            background: rgba(99, 102, 241, 0.06);
            border: 1px solid transparent;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .dev-tab:hover {
            color: #818cf8;
            background: rgba(99, 102, 241, 0.1);
        }
        
        .dev-tab.active {
            color: #a5b4fc;
            background: rgba(99, 102, 241, 0.12);
            border-color: rgba(99, 102, 241, 0.25);
        }
        
        .dev-tab i {
            font-size: 0.7rem;
        }

        .dev-dashboard-title .dev-icon {
            font-size: 1.25rem;
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: devIconPulse 3s ease-in-out infinite;
            filter: drop-shadow(0 0 8px rgba(251, 191, 36, 0.4));
        }

        @keyframes devIconPulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.85; transform: scale(1.1); }
        }

        .dev-dashboard-title .dev-text {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.1rem;
            font-weight: 700;
            background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 50%, #a5b4fc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: 0.5px;
        }

        .dev-dashboard-actions {
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }
        
        .dash-reset-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            background: rgba(239, 68, 68, 0.08);
            border: 1px solid rgba(239, 68, 68, 0.15);
            border-radius: 10px;
            color: #f87171;
            font-size: 0.75rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .dash-reset-btn:hover {
            background: rgba(239, 68, 68, 0.15);
            border-color: rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            transform: rotate(-180deg);
            box-shadow: 0 0 15px rgba(239, 68, 68, 0.2);
        }
        
        .dev-dashboard-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 0.85rem;
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.08) 0%, rgba(16, 185, 129, 0.05) 100%);
            border: 1px solid rgba(34, 197, 94, 0.2);
            border-radius: 25px;
        }

        .dev-dashboard-status .status-dot {
            width: 8px;
            height: 8px;
            background: linear-gradient(135deg, #22c55e, #10b981);
            border-radius: 50%;
            animation: statusPulse 2s ease-in-out infinite;
            box-shadow: 0 0 10px rgba(34, 197, 94, 0.5);
        }

        @keyframes statusPulse {
            0%, 100% { opacity: 1; box-shadow: 0 0 10px rgba(34, 197, 94, 0.5); }
            50% { opacity: 0.7; box-shadow: 0 0 20px rgba(34, 197, 94, 0.8); }
        }

        .dev-dashboard-status .status-text {
            font-size: 0.7rem;
            font-weight: 600;
            color: #4ade80;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .dev-dashboard-content {
            padding: 1rem 1.25rem;
            background: linear-gradient(180deg, rgba(99, 102, 241, 0.015) 0%, transparent 100%);
        }

        /* Dashboard Database Widget - PRO (Now a section card) */
        .dash-db-widget {
            display: flex;
            flex-direction: column;
            gap: 0;
        }
        
        .dash-db-tools {
            display: none;
        }
        
        /* Dashboard Sections Row - All 4 in one row */
        .dashboard-sections-row {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        
        /* Dashboard Footer with Project Management & Generate Button */
        .dashboard-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.85rem 1.25rem;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.04) 0%, rgba(16, 185, 129, 0.03) 50%, transparent 100%);
            border: 1px solid rgba(99, 102, 241, 0.12);
            border-radius: 14px;
            margin-top: 0.75rem;
            position: relative;
        }
        
        .dashboard-footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 10%;
            right: 10%;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(99, 102, 241, 0.3), transparent);
        }
        
        /* Project Management Group */
        .project-management-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .project-selector-wrap {
            position: relative;
            min-width: 180px;
        }
        
        .project-selector {
            width: 100%;
            padding: 0.5rem 2rem 0.5rem 0.75rem;
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.1) 0%, rgba(124, 58, 237, 0.05) 100%);
            border: 1px solid rgba(139, 92, 246, 0.25);
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 0.78rem;
            font-family: inherit;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            appearance: none;
        }
        
        .project-selector:hover {
            border-color: rgba(139, 92, 246, 0.4);
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.15) 0%, rgba(124, 58, 237, 0.08) 100%);
        }
        
        .project-selector:focus {
            outline: none;
            border-color: #8b5cf6;
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.15);
        }
        
        .project-selector option {
            background: var(--bg-secondary);
            color: var(--text-primary);
            padding: 0.5rem;
        }
        
        .project-selector-wrap i {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #a78bfa;
            font-size: 0.65rem;
            pointer-events: none;
        }
        
        /* Project Buttons */
        .project-btn {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.5rem 0.75rem;
            border: 1px solid;
            border-radius: 8px;
            font-size: 0.72rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.25s ease;
        }
        
        .project-btn i {
            font-size: 0.7rem;
        }
        
        .project-btn span {
            display: inline;
        }
        
        @media (max-width: 1200px) {
            .project-btn span {
                display: none;
            }
            .project-selector-wrap {
                min-width: 140px;
            }
        }
        
        /* New Project Button - Green */
        .project-btn.new-btn {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.12) 0%, rgba(5, 150, 105, 0.08) 100%);
            border-color: rgba(16, 185, 129, 0.3);
            color: #34d399;
        }
        
        .project-btn.new-btn:hover {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.2) 0%, rgba(5, 150, 105, 0.15) 100%);
            border-color: rgba(16, 185, 129, 0.5);
            color: #4ade80;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);
        }
        
        /* Save Button - Blue */
        .project-btn.save-btn {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.12) 0%, rgba(37, 99, 235, 0.08) 100%);
            border-color: rgba(59, 130, 246, 0.3);
            color: #60a5fa;
        }
        
        .project-btn.save-btn:hover {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.2) 0%, rgba(37, 99, 235, 0.15) 100%);
            border-color: rgba(59, 130, 246, 0.5);
            color: #93c5fd;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25);
        }
        
        /* Load Button - Purple */
        .project-btn.load-btn {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.12) 0%, rgba(124, 58, 237, 0.08) 100%);
            border-color: rgba(139, 92, 246, 0.3);
            color: #a78bfa;
        }
        
        .project-btn.load-btn:hover {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.2) 0%, rgba(124, 58, 237, 0.15) 100%);
            border-color: rgba(139, 92, 246, 0.5);
            color: #c4b5fd;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.25);
        }
        
        /* Delete Button - Red (Icon only) */
        .project-btn.delete-btn {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(220, 38, 38, 0.06) 100%);
            border-color: rgba(239, 68, 68, 0.25);
            color: #f87171;
            padding: 0.5rem;
        }
        
        .project-btn.delete-btn:hover {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.18) 0%, rgba(220, 38, 38, 0.12) 100%);
            border-color: rgba(239, 68, 68, 0.4);
            color: #fca5a5;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2);
        }
        
        /* Reset Button - Orange (Icon only) */
        .project-btn.reset-btn {
            background: linear-gradient(135deg, rgba(251, 146, 60, 0.1) 0%, rgba(249, 115, 22, 0.06) 100%);
            border-color: rgba(251, 146, 60, 0.25);
            color: #fb923c;
            padding: 0.5rem;
        }
        
        .project-btn.reset-btn:hover {
            background: linear-gradient(135deg, rgba(251, 146, 60, 0.18) 0%, rgba(249, 115, 22, 0.12) 100%);
            border-color: rgba(251, 146, 60, 0.4);
            color: #fdba74;
            transform: rotate(-180deg);
            box-shadow: 0 4px 12px rgba(251, 146, 60, 0.2);
        }
        
        /* Footer Divider */
        .footer-divider {
            width: 1px;
            height: 32px;
            background: linear-gradient(180deg, transparent, rgba(99, 102, 241, 0.3), transparent);
            margin: 0 0.5rem;
        }
        
        /* Generate Group */
        .generate-group {
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }
        
        .footer-arrow-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, rgba(5, 150, 105, 0.1) 100%);
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 10px;
            color: #10b981;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.25s ease;
        }
        
        .footer-arrow-btn:hover {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.25) 0%, rgba(5, 150, 105, 0.2) 100%);
            border-color: rgba(16, 185, 129, 0.5);
            color: #34d399;
            transform: translateY(2px);
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }
        
        /* ════════════════════════════════════════════════════════════════
           PROJECT MANAGEMENT POPUPS
           ════════════════════════════════════════════════════════════════ */
        
        .project-popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(4px);
            z-index: 100000;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .project-popup-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .project-popup {
            background: linear-gradient(180deg, rgba(25, 25, 45, 0.98) 0%, rgba(18, 18, 35, 1) 100%);
            border: 1px solid rgba(139, 92, 246, 0.2);
            border-radius: 20px;
            padding: 0;
            min-width: 420px;
            max-width: 550px;
            max-height: 80vh;
            overflow: hidden;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.5),
                        0 0 60px rgba(139, 92, 246, 0.15);
            transform: scale(0.9) translateY(-20px);
            transition: all 0.3s ease;
        }
        
        .project-popup-overlay.active .project-popup {
            transform: scale(1) translateY(0);
        }
        
        .project-popup-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.25rem 1.5rem;
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.12) 0%, rgba(124, 58, 237, 0.06) 100%);
            border-bottom: 1px solid rgba(139, 92, 246, 0.15);
        }
        
        .project-popup-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.1rem;
            font-weight: 700;
            color: #c4b5fd;
        }
        
        .project-popup-title i {
            font-size: 1.2rem;
            color: #a78bfa;
        }
        
        .project-popup-close {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 8px;
            color: #f87171;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .project-popup-close:hover {
            background: rgba(239, 68, 68, 0.2);
            border-color: rgba(239, 68, 68, 0.4);
            transform: rotate(90deg);
        }
        
        .project-popup-body {
            padding: 1.5rem;
            max-height: 50vh;
            overflow-y: auto;
        }
        
        .project-popup-body::-webkit-scrollbar {
            width: 6px;
        }
        .project-popup-body::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.2);
        }
        .project-popup-body::-webkit-scrollbar-thumb {
            background: rgba(139, 92, 246, 0.4);
            border-radius: 3px;
        }
        
        .project-form-group {
            margin-bottom: 1.25rem;
        }
        
        .project-form-group:last-child {
            margin-bottom: 0;
        }
        
        .project-form-label {
            display: block;
            font-size: 0.78rem;
            font-weight: 600;
            color: #a0a0b0;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .project-form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            background: rgba(15, 15, 30, 0.8);
            border: 1px solid rgba(139, 92, 246, 0.2);
            border-radius: 10px;
            color: var(--text-primary);
            font-size: 0.9rem;
            font-family: inherit;
            transition: all 0.2s ease;
        }
        
        .project-form-input:focus {
            outline: none;
            border-color: rgba(139, 92, 246, 0.5);
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
        }
        
        .project-form-input::placeholder {
            color: var(--text-muted);
        }
        
        textarea.project-form-input {
            min-height: 80px;
            resize: vertical;
        }
        
        .project-popup-footer {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.75rem;
            padding: 1rem 1.5rem;
            background: rgba(0, 0, 0, 0.2);
            border-top: 1px solid rgba(139, 92, 246, 0.1);
        }
        
        .project-popup-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.65rem 1.25rem;
            border: none;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.25s ease;
        }
        
        .project-popup-btn.cancel {
            background: rgba(100, 100, 120, 0.2);
            color: #9ca3af;
        }
        
        .project-popup-btn.cancel:hover {
            background: rgba(100, 100, 120, 0.3);
            color: #d1d5db;
        }
        
        .project-popup-btn.primary {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(139, 92, 246, 0.3);
        }
        
        .project-popup-btn.primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(139, 92, 246, 0.4);
        }
        
        .project-popup-btn.danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
        }
        
        .project-popup-btn.danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(239, 68, 68, 0.4);
        }
        
        /* Project List in Load Popup */
        .project-list {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .project-list-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.85rem 1rem;
            background: rgba(20, 20, 40, 0.6);
            border: 1px solid rgba(139, 92, 246, 0.1);
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .project-list-item:hover {
            background: rgba(139, 92, 246, 0.1);
            border-color: rgba(139, 92, 246, 0.3);
            transform: translateX(4px);
        }
        
        .project-list-item.selected {
            background: rgba(139, 92, 246, 0.15);
            border-color: rgba(139, 92, 246, 0.4);
            box-shadow: 0 0 15px rgba(139, 92, 246, 0.15);
        }
        
        .project-list-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        
        .project-list-name {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .project-list-date {
            font-size: 0.72rem;
            color: var(--text-muted);
        }
        
        .project-list-badge {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .project-list-badge span {
            font-size: 0.65rem;
            padding: 0.2rem 0.5rem;
            border-radius: 6px;
            font-weight: 600;
        }
        
        .badge-backend {
            background: rgba(6, 182, 212, 0.15);
            color: #22d3ee;
        }
        
        .badge-page {
            background: rgba(167, 139, 250, 0.15);
            color: #c4b5fd;
        }
        
        .badge-frontend {
            background: rgba(251, 146, 60, 0.15);
            color: #fdba74;
        }
        
        .project-list-empty {
            text-align: center;
            padding: 2rem;
            color: var(--text-muted);
        }
        
        .project-list-empty i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            opacity: 0.4;
        }
        
        .project-list-empty p {
            font-size: 0.9rem;
        }
        
        /* Database as a Section Card */
        .database-section {
            background: linear-gradient(180deg, rgba(20, 20, 35, 0.8) 0%, rgba(15, 15, 30, 0.9) 100%);
            border: 1px solid rgba(251, 191, 36, 0.12);
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .database-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, transparent, #fbbf24, transparent);
            opacity: 0.6;
        }
        
        .database-section:hover {
            border-color: rgba(251, 191, 36, 0.25);
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
        }
        
        .database-section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.85rem 1rem;
            background: linear-gradient(135deg, rgba(251, 191, 36, 0.1) 0%, rgba(251, 191, 36, 0.02) 100%);
            border-bottom: 1px solid rgba(251, 191, 36, 0.1);
        }
        
        .database-section-title {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-size: 0.85rem;
            font-weight: 700;
            color: #fcd34d;
            letter-spacing: 0.3px;
        }
        
        .database-section-title i {
            font-size: 1rem;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: rgba(251, 191, 36, 0.15);
            color: #fbbf24;
        }
        
        .database-section-actions {
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }
        
        .database-section-arrow {
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            border: 1px solid rgba(251, 191, 36, 0.3);
            background: rgba(251, 191, 36, 0.1);
            color: #fcd34d;
            font-size: 0.7rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .database-section-arrow:hover {
            background: rgba(251, 191, 36, 0.2);
            box-shadow: 0 4px 12px rgba(251, 191, 36, 0.3);
            transform: translateY(2px);
        }
        
        .database-section-content {
            padding: 0.75rem;
            min-height: 100px;
            max-height: 300px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .database-section-content::-webkit-scrollbar {
            width: 5px;
        }
        .database-section-content::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 3px;
        }
        .database-section-content::-webkit-scrollbar-thumb {
            background: rgba(251, 191, 36, 0.4);
            border-radius: 3px;
        }
        
        .db-controls-compact {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .db-dropdown-row {
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }
        
        .db-dropdown-row .dash-db-dropdown-wrap {
            flex: 1;
            min-width: 0;
        }
        
        .db-dropdown-row .dash-db-dropdown {
            font-size: 0.72rem;
            padding: 0.4rem 1.8rem 0.4rem 0.6rem;
        }
        
        .db-dropdown-row .dash-db-btn {
            width: 28px;
            height: 28px;
            font-size: 0.7rem;
        }
        
        .db-cred-row-compact {
            display: flex;
            gap: 0.35rem;
        }
        
        .db-cred-row-compact .dash-cred-btn span {
            font-size: 0.65rem;
            padding: 0.3rem 0.5rem;
        }
        
        .db-prompt-row-compact {
            display: flex;
            gap: 0.3rem;
        }
        
        .db-prompt-row-compact .dash-db-prompt-btn {
            font-size: 0.6rem;
            padding: 0.35rem 0.4rem;
        }
        
        .dash-db-check {
            width: 18px;
            height: 18px;
            accent-color: #fbbf24;
            cursor: pointer;
            border-radius: 4px;
        }
        
        /* Arrow Buttons */
        .dash-arrow-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.15) 0%, rgba(139, 92, 246, 0.1) 100%);
            border: 1px solid rgba(99, 102, 241, 0.25);
            border-radius: 6px;
            color: #818cf8;
            font-size: 0.65rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .dash-arrow-btn:hover {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.25) 0%, rgba(139, 92, 246, 0.2) 100%);
            border-color: rgba(99, 102, 241, 0.4);
            color: #a5b4fc;
            transform: translateY(1px);
        }
        
        /* Color variants for arrows */
        .dash-arrow-btn.db-arrow {
            background: linear-gradient(135deg, rgba(251, 191, 36, 0.15) 0%, rgba(245, 158, 11, 0.1) 100%);
            border-color: rgba(251, 191, 36, 0.25);
            color: #fbbf24;
        }
        .dash-arrow-btn.db-arrow:hover {
            background: linear-gradient(135deg, rgba(251, 191, 36, 0.25) 0%, rgba(245, 158, 11, 0.2) 100%);
            border-color: rgba(251, 191, 36, 0.4);
            color: #fcd34d;
        }
        
        .dash-arrow-btn.backend-arrow {
            background: linear-gradient(135deg, rgba(6, 182, 212, 0.15) 0%, rgba(59, 130, 246, 0.1) 100%);
            border-color: rgba(6, 182, 212, 0.25);
            color: #22d3ee;
        }
        .dash-arrow-btn.backend-arrow:hover {
            background: linear-gradient(135deg, rgba(6, 182, 212, 0.25) 0%, rgba(59, 130, 246, 0.2) 100%);
            border-color: rgba(6, 182, 212, 0.4);
            color: #67e8f9;
        }
        
        .dash-arrow-btn.page-arrow {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.15) 0%, rgba(167, 139, 250, 0.1) 100%);
            border-color: rgba(139, 92, 246, 0.25);
            color: #a78bfa;
        }
        .dash-arrow-btn.page-arrow:hover {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.25) 0%, rgba(167, 139, 250, 0.2) 100%);
            border-color: rgba(139, 92, 246, 0.4);
            color: #c4b5fd;
        }
        
        .dash-arrow-btn.frontend-arrow {
            background: linear-gradient(135deg, rgba(251, 146, 60, 0.15) 0%, rgba(249, 115, 22, 0.1) 100%);
            border-color: rgba(251, 146, 60, 0.25);
            color: #fb923c;
        }
        .dash-arrow-btn.frontend-arrow:hover {
            background: linear-gradient(135deg, rgba(251, 146, 60, 0.25) 0%, rgba(249, 115, 22, 0.2) 100%);
            border-color: rgba(251, 146, 60, 0.4);
            color: #fdba74;
        }
        
        .dash-arrow-btn.generate-arrow {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, rgba(5, 150, 105, 0.1) 100%);
            border-color: rgba(16, 185, 129, 0.25);
            color: #10b981;
            align-self: center;
        }
        .dash-arrow-btn.generate-arrow:hover {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.25) 0%, rgba(5, 150, 105, 0.2) 100%);
            border-color: rgba(16, 185, 129, 0.4);
            color: #34d399;
        }
        
        
        .dash-db-separator {
            width: 1px;
            background: rgba(251, 191, 36, 0.2);
            margin: 0 0.25rem;
            align-self: stretch;
        }

        .dash-db-label-old {
            display: none;
            align-items: center;
            gap: 0.4rem;
            color: #fbbf24;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        .dash-db-label-old i {
            font-size: 0.9rem;
        }

        .dash-db-dropdown-wrap {
            position: relative;
            min-width: 200px;
            max-width: 320px;
            flex: 1;
        }

        .dash-db-dropdown {
            width: 100%;
            padding: 0.5rem 2rem 0.5rem 0.75rem;
            background: linear-gradient(135deg, rgba(251, 191, 36, 0.1) 0%, rgba(245, 158, 11, 0.05) 100%);
            border: 1px solid rgba(251, 191, 36, 0.3);
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 0.8rem;
            font-family: 'JetBrains Mono', monospace;
            cursor: pointer;
            transition: all 0.25s ease;
            appearance: none;
        }

        .dash-db-dropdown:hover {
            border-color: rgba(251, 191, 36, 0.5);
            background: linear-gradient(135deg, rgba(251, 191, 36, 0.15) 0%, rgba(245, 158, 11, 0.08) 100%);
        }

        .dash-db-dropdown:focus {
            outline: none;
            border-color: #fbbf24;
            box-shadow: 0 0 0 3px rgba(251, 191, 36, 0.2);
        }

        .dash-db-dropdown option {
            background: var(--bg-secondary);
            color: var(--text-primary);
            padding: 0.5rem;
        }

        .dash-db-dropdown-wrap i {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #fbbf24;
            font-size: 0.65rem;
            pointer-events: none;
        }

        .dash-db-btn {
            width: 32px;
            height: 32px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.25s ease;
            flex-shrink: 0;
            font-size: 0.8rem;
        }

        .dash-db-btn.refresh {
            background: linear-gradient(135deg, rgba(0, 212, 170, 0.15) 0%, rgba(0, 184, 148, 0.1) 100%);
            color: #00d4aa;
        }

        .dash-db-btn.refresh:hover {
            background: linear-gradient(135deg, rgba(0, 212, 170, 0.3) 0%, rgba(0, 184, 148, 0.2) 100%);
            transform: rotate(180deg);
        }

        .dash-db-btn.refresh.spinning i {
            animation: dbRefreshSpin 0.8s linear infinite;
        }

        .dash-db-btn.manage {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.15) 0%, rgba(124, 58, 237, 0.1) 100%);
            color: #a78bfa;
        }

        .dash-db-btn.manage:hover {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.3) 0%, rgba(124, 58, 237, 0.2) 100%);
            transform: rotate(90deg);
        }

        .dash-cred-btn {
            cursor: pointer;
            user-select: none;
        }

        .dash-cred-btn input {
            display: none;
        }

        .dash-cred-btn span {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.4rem 0.65rem;
            background: var(--bg-tertiary);
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--text-muted);
            transition: all 0.25s ease;
            white-space: nowrap;
        }

        .dash-cred-btn:hover span {
            border-color: rgba(251, 191, 36, 0.4);
            color: var(--text-secondary);
        }

        .dash-cred-btn.remote input:checked + span {
            background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
            border-color: #3b82f6;
            color: white;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4);
        }

        .dash-cred-btn.localhost input:checked + span {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            border-color: #22c55e;
            color: white;
            box-shadow: 0 4px 15px rgba(34, 197, 94, 0.4);
        }

        .dash-db-empty {
            grid-column: 1 / -1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            padding: 0.5rem 1rem;
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 8px;
            font-size: 0.8rem;
            color: #fca5a5;
        }

        .dash-db-empty a {
            color: #fbbf24;
            text-decoration: none;
            font-weight: 600;
        }

        .dash-db-empty a:hover {
            text-decoration: underline;
        }
        
        /* Backend Group with Label */
        .dash-backend-group {
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
            align-self: stretch;
        }
        
        .dash-backend-label {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.7rem;
            font-weight: 600;
            color: #06b6d4;
            padding-left: 0.1rem;
            min-height: 18px;
            line-height: 1;
        }
        
        .dash-backend-label i {
            font-size: 0.65rem;
        }
        
        .dash-backend-controls {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            min-height: 32px;
        }
        
        .dash-backend-check {
            width: 18px;
            height: 18px;
            accent-color: #06b6d4;
            cursor: pointer;
            border-radius: 4px;
        }
        
        .dash-backend-prompt {
            width: 200px;
            height: 50px;
            min-width: 120px;
            min-height: 36px;
            max-width: 400px;
            max-height: 150px;
            padding: 0.5rem 0.7rem;
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            color: var(--text-primary);
            font-size: 0.75rem;
            font-family: inherit;
            resize: both;
            overflow: auto;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        
        .dash-backend-prompt::placeholder {
            color: var(--text-muted);
        }
        
        .dash-backend-prompt:focus {
            outline: none;
            border-color: rgba(6, 182, 212, 0.5);
            box-shadow: 0 0 0 2px rgba(6, 182, 212, 0.1);
        }
        
        /* Page Section - Purple Theme */
        .dash-page-label {
            color: #a78bfa !important;
        }
        
        .dash-page-check {
            accent-color: #a78bfa !important;
        }
        
        .dash-page-btn {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.12) 0%, rgba(167, 139, 250, 0.08) 100%) !important;
            border-color: rgba(139, 92, 246, 0.25) !important;
            color: #a78bfa !important;
        }
        
        .dash-file-input-wrap:hover .dash-page-btn {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.2) 0%, rgba(167, 139, 250, 0.15) 100%) !important;
            border-color: rgba(139, 92, 246, 0.4) !important;
            box-shadow: 0 2px 8px rgba(139, 92, 246, 0.2) !important;
        }
        
        .dash-page-group .dash-file-info i {
            color: #a78bfa;
        }
        
        .dash-page-group .dash-file-info.has-files {
            color: #a78bfa;
            border-color: rgba(139, 92, 246, 0.3);
            background: rgba(139, 92, 246, 0.08);
        }
        
        .dash-page-prompt:focus {
            border-color: rgba(139, 92, 246, 0.5) !important;
            box-shadow: 0 0 0 2px rgba(139, 92, 246, 0.1) !important;
        }
        
        /* Frontend Section - Orange Theme */
        .dash-frontend-label {
            color: #fb923c !important;
        }
        
        .dash-frontend-check {
            accent-color: #fb923c !important;
        }
        
        .dash-frontend-btn {
            background: linear-gradient(135deg, rgba(251, 146, 60, 0.12) 0%, rgba(249, 115, 22, 0.08) 100%) !important;
            border-color: rgba(251, 146, 60, 0.25) !important;
            color: #fb923c !important;
        }
        
        .dash-file-input-wrap:hover .dash-frontend-btn {
            background: linear-gradient(135deg, rgba(251, 146, 60, 0.2) 0%, rgba(249, 115, 22, 0.15) 100%) !important;
            border-color: rgba(251, 146, 60, 0.4) !important;
            box-shadow: 0 2px 8px rgba(251, 146, 60, 0.2) !important;
        }
        
        .dash-frontend-group .dash-file-info i {
            color: #fb923c;
        }
        
        .dash-frontend-group .dash-file-info.has-files {
            color: #fb923c;
            border-color: rgba(251, 146, 60, 0.3);
            background: rgba(251, 146, 60, 0.08);
        }
        
        .dash-frontend-prompt:focus {
            border-color: rgba(251, 146, 60, 0.5) !important;
            box-shadow: 0 0 0 2px rgba(251, 146, 60, 0.1) !important;
        }
        
        /* Database Section - Golden Theme - Align with other sections */
        .dash-database-group {
            gap: 0.3rem !important;
        }
        
        .dash-database-group .dash-backend-controls {
            gap: 0.4rem;
        }
        
        .dash-db-cred-row {
            display: flex;
            gap: 0.4rem;
            margin-top: 0.3rem;
            width: 100%;
        }
        
        .dash-db-cred-row .dash-cred-btn {
            flex: 1;
        }
        
        .dash-db-cred-row .dash-cred-btn span {
            justify-content: center;
            width: 100%;
        }
        
        .dash-db-prompt-row {
            display: flex;
            gap: 0.3rem;
            width: 100%;
            margin-top: 0.3rem;
        }
        
        .dash-db-prompt-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.3rem;
            flex: 1;
            padding: 0.45rem 0.5rem;
            background: linear-gradient(135deg, rgba(168, 85, 247, 0.15) 0%, rgba(139, 92, 246, 0.1) 100%);
            border: 2px solid rgba(168, 85, 247, 0.3);
            border-radius: 8px;
            font-size: 0.65rem;
            font-weight: 600;
            color: #a855f7;
            cursor: pointer;
            transition: all 0.25s ease;
        }
        
        .dash-db-prompt-btn:hover {
            background: linear-gradient(135deg, rgba(168, 85, 247, 0.25) 0%, rgba(139, 92, 246, 0.2) 100%);
            border-color: rgba(168, 85, 247, 0.5);
            color: #c084fc;
            transform: translateY(-1px);
            box-shadow: 0 3px 12px rgba(168, 85, 247, 0.3);
        }
        
        .dash-db-prompt-btn:active {
            transform: translateY(0);
        }
        
        .dash-db-prompt-btn i {
            font-size: 0.7rem;
        }
        
        .dash-db-prompt-full {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.15) 0%, rgba(16, 185, 129, 0.1) 100%);
            border-color: rgba(34, 197, 94, 0.3);
            color: #22c55e;
        }
        
        .dash-db-prompt-full:hover {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.25) 0%, rgba(16, 185, 129, 0.2) 100%);
            border-color: rgba(34, 197, 94, 0.5);
            color: #4ade80;
            box-shadow: 0 3px 12px rgba(34, 197, 94, 0.3);
        }
        
        .dash-database-label {
            color: #fbbf24 !important;
        }
        
        /* Generate Button - Shiny Green */
        .dash-generate-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.2rem;
            background: linear-gradient(135deg, #10b981 0%, #059669 50%, #047857 100%);
            border: none;
            border-radius: 10px;
            color: #fff;
            font-size: 0.85rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4), 
                        inset 0 1px 0 rgba(255, 255, 255, 0.2);
            align-self: center;
            height: fit-content;
        }
        
        .dash-generate-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s ease;
        }
        
        .dash-generate-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(16, 185, 129, 0.5),
                        inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }
        
        .dash-generate-btn:hover::before {
            left: 100%;
        }
        
        .dash-generate-btn:active {
            transform: translateY(0);
            box-shadow: 0 2px 10px rgba(16, 185, 129, 0.4);
        }
        
        .dash-generate-btn i {
            font-size: 0.9rem;
        }
        
        /* ═══════════════════════════════════════════════════════════════
           DYNAMIC SECTIONS GRID SYSTEM - REDESIGNED PRO VERSION
           ═══════════════════════════════════════════════════════════════ */
        
        .dynamic-sections-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0.75rem;
            margin-top: 0;
            padding-top: 0;
        }
        
        @media (max-width: 1400px) {
            .dynamic-sections-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 900px) {
            .dynamic-sections-container {
                grid-template-columns: 1fr;
            }
        }
        
        .dynamic-section {
            background: linear-gradient(180deg, rgba(20, 20, 35, 0.8) 0%, rgba(15, 15, 30, 0.9) 100%);
            border: 1px solid rgba(99, 102, 241, 0.12);
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .dynamic-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, transparent, var(--section-color, #6366f1), transparent);
            opacity: 0.6;
        }
        
        .dynamic-section:hover {
            border-color: rgba(99, 102, 241, 0.25);
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
        }
        
        #backendSection { --section-color: #06b6d4; }
        #pageSection { --section-color: #a78bfa; }
        #frontendSection { --section-color: #fb923c; }
        
        #backendSection::before { background: linear-gradient(90deg, transparent, #06b6d4, transparent); }
        #pageSection::before { background: linear-gradient(90deg, transparent, #a78bfa, transparent); }
        #frontendSection::before { background: linear-gradient(90deg, transparent, #fb923c, transparent); }
        
        .dynamic-section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.85rem 1rem;
            background: linear-gradient(135deg, rgba(var(--section-rgb, 99, 102, 241), 0.08) 0%, transparent 100%);
            border-bottom: 1px solid rgba(99, 102, 241, 0.1);
        }
        
        #backendSection .dynamic-section-header { 
            background: linear-gradient(135deg, rgba(6, 182, 212, 0.1) 0%, rgba(6, 182, 212, 0.02) 100%); 
        }
        #pageSection .dynamic-section-header { 
            background: linear-gradient(135deg, rgba(167, 139, 250, 0.1) 0%, rgba(167, 139, 250, 0.02) 100%); 
        }
        #frontendSection .dynamic-section-header { 
            background: linear-gradient(135deg, rgba(251, 146, 60, 0.1) 0%, rgba(251, 146, 60, 0.02) 100%); 
        }
        
        .dynamic-section-title {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-size: 0.85rem;
            font-weight: 700;
            letter-spacing: 0.3px;
        }
        
        .dynamic-section-title i {
            font-size: 1rem;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.05);
        }
        
        .dynamic-section-title.backend-title { color: #22d3ee; }
        .dynamic-section-title.backend-title i { background: rgba(6, 182, 212, 0.15); color: #06b6d4; }
        
        .dynamic-section-title.page-title { color: #c4b5fd; }
        .dynamic-section-title.page-title i { background: rgba(167, 139, 250, 0.15); color: #a78bfa; }
        
        .dynamic-section-title.frontend-title { color: #fdba74; }
        .dynamic-section-title.frontend-title i { background: rgba(251, 146, 60, 0.15); color: #fb923c; }
        
        .dynamic-section-count {
            font-size: 0.65rem;
            font-weight: 700;
            padding: 0.2rem 0.6rem;
            border-radius: 12px;
            min-width: 24px;
            text-align: center;
        }
        
        #backendSection .dynamic-section-count { 
            background: rgba(6, 182, 212, 0.2); 
            color: #22d3ee;
            box-shadow: 0 0 10px rgba(6, 182, 212, 0.2);
        }
        #pageSection .dynamic-section-count { 
            background: rgba(167, 139, 250, 0.2); 
            color: #c4b5fd;
            box-shadow: 0 0 10px rgba(167, 139, 250, 0.2);
        }
        #frontendSection .dynamic-section-count { 
            background: rgba(251, 146, 60, 0.2); 
            color: #fdba74;
            box-shadow: 0 0 10px rgba(251, 146, 60, 0.2);
        }
        
        .dynamic-section-actions {
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }
        
        .dynamic-section-arrow {
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            border: 1px solid rgba(99, 102, 241, 0.2);
            background: rgba(99, 102, 241, 0.08);
            color: #818cf8;
            font-size: 0.7rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .dynamic-section-arrow:hover {
            transform: translateY(2px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }
        
        #backendSection .dynamic-section-arrow {
            border-color: rgba(6, 182, 212, 0.3);
            background: rgba(6, 182, 212, 0.1);
            color: #22d3ee;
        }
        #backendSection .dynamic-section-arrow:hover {
            background: rgba(6, 182, 212, 0.2);
            box-shadow: 0 4px 12px rgba(6, 182, 212, 0.3);
        }
        
        #pageSection .dynamic-section-arrow {
            border-color: rgba(167, 139, 250, 0.3);
            background: rgba(167, 139, 250, 0.1);
            color: #c4b5fd;
        }
        #pageSection .dynamic-section-arrow:hover {
            background: rgba(167, 139, 250, 0.2);
            box-shadow: 0 4px 12px rgba(167, 139, 250, 0.3);
        }
        
        #frontendSection .dynamic-section-arrow {
            border-color: rgba(251, 146, 60, 0.3);
            background: rgba(251, 146, 60, 0.1);
            color: #fdba74;
        }
        #frontendSection .dynamic-section-arrow:hover {
            background: rgba(251, 146, 60, 0.2);
            box-shadow: 0 4px 12px rgba(251, 146, 60, 0.3);
        }
        
        .dynamic-section-add-btn {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.4rem 0.85rem;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.12) 0%, rgba(5, 150, 105, 0.08) 100%);
            border: 1px solid rgba(16, 185, 129, 0.25);
            border-radius: 8px;
            color: #34d399;
            font-size: 0.7rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.25s ease;
        }
        
        .dynamic-section-add-btn:hover {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.2) 0%, rgba(5, 150, 105, 0.15) 100%);
            border-color: rgba(16, 185, 129, 0.4);
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.25);
            color: #4ade80;
        }
        
        .dynamic-section-add-btn i {
            font-size: 0.6rem;
        }
        
        /* Items Grid - Inside each section */
        .dynamic-items-grid {
            padding: 0.75rem;
            min-height: 100px;
            max-height: 300px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .dynamic-items-grid::-webkit-scrollbar {
            width: 5px;
        }
        .dynamic-items-grid::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 3px;
        }
        .dynamic-items-grid::-webkit-scrollbar-thumb {
            background: rgba(99, 102, 241, 0.4);
            border-radius: 3px;
        }
        .dynamic-items-grid::-webkit-scrollbar-thumb:hover {
            background: rgba(99, 102, 241, 0.6);
        }
        
        .dynamic-item {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            padding: 0.75rem;
            background: linear-gradient(135deg, rgba(25, 25, 45, 0.9) 0%, rgba(20, 20, 40, 0.95) 100%);
            border: 1px solid rgba(99, 102, 241, 0.12);
            border-radius: 10px;
            transition: all 0.25s ease;
            position: relative;
        }
        
        .dynamic-item:hover {
            border-color: rgba(99, 102, 241, 0.25);
            background: linear-gradient(135deg, rgba(30, 30, 55, 0.95) 0%, rgba(25, 25, 50, 1) 100%);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
        }
        
        .dynamic-item.backend-item {
            border-left: 3px solid #06b6d4;
        }
        .dynamic-item.backend-item:hover {
            border-left-color: #22d3ee;
            box-shadow: 0 4px 20px rgba(6, 182, 212, 0.15);
        }
        
        .dynamic-item.page-item {
            border-left: 3px solid #a78bfa;
        }
        .dynamic-item.page-item:hover {
            border-left-color: #c4b5fd;
            box-shadow: 0 4px 20px rgba(167, 139, 250, 0.15);
        }
        
        .dynamic-item.frontend-item {
            border-left: 3px solid #fb923c;
        }
        .dynamic-item.frontend-item:hover {
            border-left-color: #fdba74;
            box-shadow: 0 4px 20px rgba(251, 146, 60, 0.15);
        }
        
        .dynamic-item-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .dynamic-item-number {
            font-size: 0.6rem;
            font-weight: 800;
            padding: 0.2rem 0.45rem;
            border-radius: 6px;
            min-width: 22px;
            text-align: center;
            flex-shrink: 0;
        }
        
        .backend-item .dynamic-item-number {
            background: linear-gradient(135deg, rgba(6, 182, 212, 0.25) 0%, rgba(6, 182, 212, 0.15) 100%);
            color: #22d3ee;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }
        
        .page-item .dynamic-item-number {
            background: linear-gradient(135deg, rgba(167, 139, 250, 0.25) 0%, rgba(167, 139, 250, 0.15) 100%);
            color: #c4b5fd;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }
        
        .frontend-item .dynamic-item-number {
            background: linear-gradient(135deg, rgba(251, 146, 60, 0.25) 0%, rgba(251, 146, 60, 0.15) 100%);
            color: #fdba74;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }
        
        .dynamic-item-name {
            flex: 1;
            padding: 0.4rem 0.6rem;
            background: rgba(10, 10, 25, 0.7);
            border: 1px solid rgba(99, 102, 241, 0.12);
            border-radius: 6px;
            color: var(--text-primary);
            font-size: 0.75rem;
            font-weight: 500;
            outline: none;
            transition: all 0.2s;
        }
        
        .dynamic-item-name:focus {
            border-color: rgba(99, 102, 241, 0.35);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.08);
            background: rgba(15, 15, 30, 0.8);
        }
        
        .dynamic-item-name::placeholder {
            color: var(--text-muted);
            font-weight: 400;
        }
        
        .dynamic-item-actions {
            display: flex;
            gap: 0.3rem;
            margin-left: auto;
        }
        
        .dynamic-item-btn {
            width: 26px;
            height: 26px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid transparent;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.65rem;
        }
        
        .dynamic-item-btn.file-btn {
            background: rgba(99, 102, 241, 0.12);
            border-color: rgba(99, 102, 241, 0.2);
            color: #818cf8;
        }
        
        .dynamic-item-btn.file-btn:hover {
            background: rgba(99, 102, 241, 0.2);
            border-color: rgba(99, 102, 241, 0.35);
            color: #a5b4fc;
            transform: scale(1.05);
        }
        
        .dynamic-item-btn.file-btn.has-files {
            background: rgba(16, 185, 129, 0.15);
            border-color: rgba(16, 185, 129, 0.3);
            color: #34d399;
        }
        
        .dynamic-item-btn.delete-btn {
            background: rgba(239, 68, 68, 0.1);
            border-color: rgba(239, 68, 68, 0.15);
            color: #f87171;
        }
        
        .dynamic-item-btn.delete-btn:hover {
            background: rgba(239, 68, 68, 0.2);
            border-color: rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            transform: scale(1.05);
        }
        
        .dynamic-item-body {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }
        
        .dynamic-item-prompt {
            width: 100%;
            min-height: 45px;
            max-height: 100px;
            padding: 0.5rem 0.6rem;
            background: rgba(10, 10, 25, 0.7);
            border: 1px solid rgba(99, 102, 241, 0.12);
            border-radius: 6px;
            color: var(--text-primary);
            font-size: 0.72rem;
            font-family: inherit;
            line-height: 1.4;
            resize: vertical;
            outline: none;
            transition: all 0.2s;
        }
        
        .dynamic-item-prompt:focus {
            border-color: rgba(99, 102, 241, 0.35);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.08);
            background: rgba(15, 15, 30, 0.8);
        }
        
        .dynamic-item-prompt::placeholder {
            color: var(--text-muted);
            font-size: 0.68rem;
        }
        
        .dynamic-item-files {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.65rem;
            color: var(--text-muted);
            padding: 0.25rem 0;
        }
        
        .dynamic-item-files i {
            font-size: 0.6rem;
        }
        
        .dynamic-item-files.has-files {
            color: #34d399;
        }
        
        .dynamic-item-file-input {
            display: none;
        }
        
        /* Empty state for dynamic sections - Pro styling */
        .dynamic-empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            color: var(--text-muted);
            text-align: center;
            background: linear-gradient(180deg, rgba(99, 102, 241, 0.02) 0%, transparent 100%);
            border-radius: 8px;
            border: 1px dashed rgba(99, 102, 241, 0.15);
            transition: all 0.25s ease;
        }
        
        .dynamic-empty-state:hover {
            border-color: rgba(99, 102, 241, 0.25);
            background: linear-gradient(180deg, rgba(99, 102, 241, 0.04) 0%, transparent 100%);
        }
        
        #backendSection .dynamic-empty-state {
            border-color: rgba(6, 182, 212, 0.15);
        }
        #backendSection .dynamic-empty-state:hover {
            border-color: rgba(6, 182, 212, 0.3);
            background: linear-gradient(180deg, rgba(6, 182, 212, 0.04) 0%, transparent 100%);
        }
        #backendSection .dynamic-empty-state i { color: rgba(6, 182, 212, 0.5); }
        
        #pageSection .dynamic-empty-state {
            border-color: rgba(167, 139, 250, 0.15);
        }
        #pageSection .dynamic-empty-state:hover {
            border-color: rgba(167, 139, 250, 0.3);
            background: linear-gradient(180deg, rgba(167, 139, 250, 0.04) 0%, transparent 100%);
        }
        #pageSection .dynamic-empty-state i { color: rgba(167, 139, 250, 0.5); }
        
        #frontendSection .dynamic-empty-state {
            border-color: rgba(251, 146, 60, 0.15);
        }
        #frontendSection .dynamic-empty-state:hover {
            border-color: rgba(251, 146, 60, 0.3);
            background: linear-gradient(180deg, rgba(251, 146, 60, 0.04) 0%, transparent 100%);
        }
        #frontendSection .dynamic-empty-state i { color: rgba(251, 146, 60, 0.5); }
        
        .dynamic-empty-state i {
            font-size: 1.8rem;
            margin-bottom: 0.75rem;
            opacity: 0.6;
        }
        
        .dynamic-empty-state p {
            font-size: 0.8rem;
            font-weight: 500;
            margin: 0 0 0.25rem 0;
        }
        
        .dynamic-empty-state small {
            font-size: 0.68rem;
            opacity: 0.6;
        }
        
        /* File Picker - Inline Style */
        .dash-file-input-wrap {
            position: relative;
            cursor: pointer;
        }
        
        .dash-file-input-wrap input[type="file"] {
            position: absolute;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
            z-index: 2;
        }
        
        .dash-file-btn {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.45rem 0.7rem;
            background: linear-gradient(135deg, rgba(6, 182, 212, 0.12) 0%, rgba(59, 130, 246, 0.08) 100%);
            border: 2px solid rgba(6, 182, 212, 0.25);
            border-radius: 8px;
            color: #22d3ee;
            font-size: 0.7rem;
            font-weight: 600;
            transition: all 0.25s ease;
        }
        
        .dash-file-btn i {
            font-size: 0.75rem;
        }
        
        .dash-file-input-wrap:hover .dash-file-btn {
            background: linear-gradient(135deg, rgba(6, 182, 212, 0.2) 0%, rgba(59, 130, 246, 0.15) 100%);
            border-color: rgba(6, 182, 212, 0.4);
            box-shadow: 0 2px 8px rgba(6, 182, 212, 0.2);
        }
        
        .dash-file-info {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.35rem 0.6rem;
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 0.7rem;
            color: var(--text-muted);
            max-width: 120px;
            overflow: hidden;
        }
        
        .dash-file-info i {
            flex-shrink: 0;
            font-size: 0.65rem;
            color: #06b6d4;
        }
        
        .dash-file-info span {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .dash-file-info.has-files {
            color: #22d3ee;
            border-color: rgba(6, 182, 212, 0.3);
            background: rgba(6, 182, 212, 0.08);
        }

        /* Responsive for smaller screens */
        @media (max-width: 900px) {
            .dash-db-tools {
                flex-wrap: wrap;
            }
            
            .dash-db-dropdown-wrap {
                min-width: 100%;
                max-width: 100%;
            }
            
            .dash-db-separator {
                display: none;
            }
        }

        .saved-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0.75rem;
            background: var(--bg-tertiary);
            border-bottom: 1px solid var(--border-color);
        }

        .saved-header h3 {
            font-size: 0.8rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            white-space: nowrap;
        }

        .saved-header h3 i {
            color: var(--success);
            font-size: 0.85rem;
        }

        .saved-header .search-box {
            max-width: 200px;
        }

        .saved-header .search-box input {
            padding: 0.35rem 0.75rem 0.35rem 2rem;
            font-size: 0.75rem;
        }

        .saved-header .search-box i {
            font-size: 0.7rem;
            left: 0.65rem;
        }

        .search-box {
            flex: 1;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 0.5rem 1rem 0.5rem 2.5rem;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-primary);
            font-family: inherit;
            font-size: 0.85rem;
            outline: none;
            transition: all 0.2s;
        }

        .search-box input:focus {
            border-color: var(--accent-primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .search-box i {
            position: absolute;
            left: 0.85rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 0.85rem;
        }

        .saved-list {
            display: flex;
            flex-direction: row;
            flex-wrap: nowrap;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0.75rem;
            padding-bottom: 0.4rem;
            overflow-x: auto;
            overflow-y: hidden;
            scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch;
            max-width: 100%;
            width: 100%;
            box-sizing: border-box;
            /* Hide default browser scrollbar */
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* IE/Edge */
        }
        
        /* Hide default scrollbar for saved-list (Chrome, Safari, Opera) */
        .saved-list::-webkit-scrollbar {
            display: none;
            width: 0;
            height: 0;
        }

        .saved-list:empty::after {
            content: 'No saved prompts yet';
            color: var(--text-muted);
            font-size: 0.75rem;
            font-style: italic;
            white-space: nowrap;
        }

        /* Custom Scrollbar Track - Full Width Bar */
        .saved-scrollbar-track {
            width: calc(100% - 1.5rem);
            height: 14px;
            background: rgba(99, 102, 241, 0.2);
            border: 1px solid rgba(99, 102, 241, 0.3);
            border-radius: 7px;
            margin: 0.25rem 0.75rem 0.5rem 0.75rem;
            position: relative;
            cursor: pointer;
            overflow: visible;
            box-sizing: border-box;
        }

        .saved-scrollbar-thumb {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: 80px;
            min-width: 50px;
            background: linear-gradient(90deg, #6366f1, #8b5cf6);
            border-radius: 6px;
            cursor: grab;
            transition: background 0.2s, box-shadow 0.2s, left 0.1s ease-out;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            z-index: 10;
            box-shadow: 0 2px 8px rgba(99, 102, 241, 0.4);
        }

        .saved-scrollbar-thumb:hover {
            background: linear-gradient(90deg, #818cf8, #a78bfa);
            box-shadow: 0 0 12px rgba(99, 102, 241, 0.6);
            transform: scaleY(1.2);
        }

        .saved-scrollbar-thumb:active,
        .saved-scrollbar-thumb.dragging {
            cursor: grabbing;
            background: linear-gradient(90deg, #a5b4fc, #c4b5fd);
            box-shadow: 0 0 15px rgba(99, 102, 241, 0.7);
            transform: scaleY(1.3);
        }

        .saved-item {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.35rem 0.6rem;
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(99, 102, 241, 0.05) 100%);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.25s ease;
            flex-shrink: 0;
            white-space: nowrap;
        }

        .saved-item:hover {
            border-color: var(--accent-primary);
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(99, 102, 241, 0.2);
        }

        .saved-icon {
            width: 36px;
            height: 36px;
            background: var(--gradient-main);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            flex-shrink: 0;
        }

        .saved-info {
            flex: 1;
            min-width: 0;
        }

        .saved-title {
            font-size: 0.9rem;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .saved-date {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .saved-actions {
            display: flex;
            gap: 0.5rem;
        }

        .saved-actions button {
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 0.35rem;
            border-radius: 6px;
            transition: all 0.2s;
        }

        .saved-actions button:hover {
            background: var(--bg-tertiary);
        }

        .saved-actions .edit-btn:hover {
            color: var(--accent-primary);
        }

        .saved-actions .delete-btn:hover {
            color: var(--danger);
        }

        /* Saved Prompts Actions Bar */
        .saved-actions-bar {
            display: flex;
            gap: 0.5rem;
            padding: 0.5rem 0.75rem;
            background: var(--bg-tertiary);
            border-bottom: 1px solid var(--border-color);
            align-items: center;
        }

        .saved-action-btn {
            flex: 1;
            padding: 0.4rem 0.5rem;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-muted);
            font-family: inherit;
            font-size: 0.7rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.3rem;
        }

        .saved-action-btn:hover {
            border-color: var(--accent-primary);
            color: var(--accent-primary);
            background: rgba(99, 102, 241, 0.1);
        }

        .saved-action-btn i {
            font-size: 0.7rem;
        }

        .saved-counter {
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--success);
            background: rgba(16, 185, 129, 0.15);
            padding: 0.3rem 0.6rem;
            border-radius: 6px;
            white-space: nowrap;
        }

        .search-clear-btn {
            position: absolute;
            right: 0.5rem;
            top: 50%;
            transform: translateY(-50%);
            background: var(--bg-tertiary);
            border: none;
            color: var(--text-muted);
            width: 22px;
            height: 22px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.65rem;
            transition: all 0.2s;
        }

        .search-clear-btn:hover {
            background: var(--danger);
            color: white;
        }

        /* Updated Saved Item - Horizontal Pill Style */
        .saved-item.checked {
            border-color: var(--success);
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, rgba(16, 185, 129, 0.08) 100%);
            box-shadow: 0 0 10px rgba(16, 185, 129, 0.2);
        }

        .saved-item-checkbox {
            flex-shrink: 0;
        }

        .saved-item-checkbox input {
            display: none;
        }

        .saved-item-checkbox .checkbox-box {
            width: 16px;
            height: 16px;
            border: 2px solid var(--border-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            background: var(--bg-tertiary);
        }

        .saved-item-checkbox .checkbox-box i {
            font-size: 0.5rem;
            color: white;
            opacity: 0;
            transform: scale(0);
            transition: all 0.2s;
        }

        .saved-item.checked .saved-item-checkbox .checkbox-box {
            background: linear-gradient(135deg, var(--success), #059669);
            border-color: var(--success);
        }

        .saved-item.checked .saved-item-checkbox .checkbox-box i {
            opacity: 1;
            transform: scale(1);
        }

        .saved-item-content {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            cursor: pointer;
        }

        .saved-item-name {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-primary);
            max-width: 120px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .saved-item-preview {
            display: none;
        }

        .saved-item-date {
            display: none;
        }

        .saved-item-actions {
            display: flex;
            gap: 2px;
            flex-shrink: 0;
            opacity: 0;
            transition: all 0.2s;
        }

        .saved-item:hover .saved-item-actions {
            opacity: 1;
        }

        .saved-action-icon {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            border: none;
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-muted);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            font-size: 0.55rem;
            font-size: 0.7rem;
        }

        .saved-action-icon:hover {
            transform: scale(1.1);
        }

        .saved-action-icon.copy:hover {
            background: rgba(16, 185, 129, 0.2);
            color: var(--success);
        }

        .saved-action-icon.edit:hover {
            background: rgba(99, 102, 241, 0.2);
            color: var(--accent-primary);
        }

        .saved-action-icon.delete:hover {
            background: rgba(239, 68, 68, 0.2);
            color: var(--danger);
        }

        .saved-action-icon.pull:hover {
            background: rgba(251, 191, 36, 0.2);
            color: #fbbf24;
        }

        /* Saved Prompt Preview Modal */
        .saved-preview-modal {
            max-width: 650px;
        }

        .saved-preview-name {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .saved-preview-date {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border-color);
        }

        .saved-preview-content {
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 1rem;
            font-family: 'JetBrains Mono', 'Fira Code', monospace;
            font-size: 0.8rem;
            line-height: 1.6;
            color: var(--text-secondary);
            white-space: pre-wrap;
            max-height: 300px;
            overflow-y: auto;
        }

        /* Toast Notifications - Enhanced */
        .toast-container {
            position: fixed;
            top: 1.5rem;
            right: 1.5rem;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            pointer-events: none;
        }

        .toast {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.25rem;
            background: rgba(30, 30, 60, 0.95);
            border-radius: 14px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(255, 255, 255, 0.05);
            animation: toastSlideIn 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            min-width: 300px;
            max-width: 420px;
            backdrop-filter: blur(20px);
            pointer-events: auto;
            position: relative;
            overflow: hidden;
        }

        .toast::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            border-radius: 14px 0 0 14px;
        }

        .toast.success {
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        .toast.success::before {
            background: linear-gradient(180deg, #10b981, #059669);
        }

        .toast.error {
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        .toast.error::before {
            background: linear-gradient(180deg, #ef4444, #dc2626);
        }

        .toast.info {
            border: 1px solid rgba(99, 102, 241, 0.3);
        }
        .toast.info::before {
            background: linear-gradient(180deg, #6366f1, #8b5cf6);
        }

        .toast.warning {
            border: 1px solid rgba(245, 158, 11, 0.3);
        }
        .toast.warning::before {
            background: linear-gradient(180deg, #f59e0b, #d97706);
        }

        .toast-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .toast.success .toast-icon {
            background: rgba(16, 185, 129, 0.15);
            color: #10b981;
        }

        .toast.error .toast-icon {
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
        }

        .toast.info .toast-icon {
            background: rgba(99, 102, 241, 0.15);
            color: #6366f1;
        }

        .toast.warning .toast-icon {
            background: rgba(245, 158, 11, 0.15);
            color: #f59e0b;
        }

        .toast-content {
            flex: 1;
            min-width: 0;
        }

        .toast-title {
            font-weight: 600;
            font-size: 0.9rem;
            color: #fff;
            margin-bottom: 0.15rem;
        }

        .toast-message {
            font-size: 0.85rem;
            color: #a1a1aa;
            line-height: 1.4;
        }

        .toast-close {
            background: none;
            border: none;
            color: #6b7280;
            cursor: pointer;
            padding: 0.25rem;
            border-radius: 6px;
            transition: all 0.2s;
            font-size: 0.9rem;
        }

        .toast-close:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
        }

        .toast-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            background: rgba(255, 255, 255, 0.3);
            animation: toastProgress 3s linear forwards;
        }

        @keyframes toastSlideIn {
            from {
                opacity: 0;
                transform: translateX(100%) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateX(0) scale(1);
            }
        }

        @keyframes toastProgress {
            from { width: 100%; }
            to { width: 0%; }
        }

        /* Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
        }

        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .modal {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            width: 90%;
            max-width: 500px;
            overflow: hidden;
            transform: scale(0.9);
            transition: transform 0.3s;
        }

        .modal-overlay.active .modal {
            transform: scale(1);
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.25rem 1.5rem;
            background: var(--bg-tertiary);
            border-bottom: 1px solid var(--border-color);
        }

        .modal-header h3 {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.1rem;
        }

        .modal-header h3 i {
            color: var(--accent-primary);
        }

        .modal-close {
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 1.25rem;
            cursor: pointer;
            transition: color 0.2s;
        }

        .modal-close:hover {
            color: var(--danger);
        }

        .modal-body {
            padding: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-secondary);
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-primary);
            font-family: inherit;
            font-size: 0.9rem;
            outline: none;
            transition: all 0.2s;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            border-color: var(--accent-primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .form-group textarea {
            min-height: 120px;
            resize: vertical;
            font-family: 'JetBrains Mono', monospace;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            padding: 1.25rem 1.5rem;
            background: var(--bg-tertiary);
            border-top: 1px solid var(--border-color);
        }

        /* File Management Modal Styles */
        .file-modal-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            display: block;
            text-align: center;
        }

        .file-modal-icon.create { color: #10b981; }
        .file-modal-icon.delete { color: #ef4444; }
        .file-modal-icon.rename { color: #f59e0b; }

        .file-modal-message {
            text-align: center;
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }

        .file-list-container {
            max-height: 250px;
            overflow-y: auto;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: var(--bg-card);
            margin-bottom: 1rem;
        }

        .file-list-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--border-color);
            cursor: pointer;
            transition: background 0.2s;
        }

        .file-list-item:last-child {
            border-bottom: none;
        }

        .file-list-item:hover {
            background: var(--bg-tertiary);
        }

        .file-list-item.selected {
            background: rgba(99, 102, 241, 0.15);
            border-color: var(--accent-primary);
        }

        .file-list-item input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: var(--accent-primary);
            cursor: pointer;
        }

        .file-list-item input[type="radio"] {
            width: 18px;
            height: 18px;
            accent-color: var(--accent-primary);
            cursor: pointer;
        }

        .file-list-item .file-icon {
            color: var(--accent-secondary);
            font-size: 1rem;
        }

        .file-list-item .file-name {
            flex: 1;
            font-size: 0.9rem;
            color: var(--text-primary);
        }

        .file-list-empty {
            padding: 2rem;
            text-align: center;
            color: var(--text-muted);
        }

        .file-count-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.25rem 0.6rem;
            background: var(--bg-tertiary);
            border-radius: 12px;
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-left: 0.5rem;
        }

        .modal-input-row {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .modal-input-row input {
            flex: 1;
        }

        .modal-input-row .btn {
            white-space: nowrap;
        }

        .folder-select-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            background: var(--bg-card);
            border: 2px dashed var(--border-color);
            border-radius: 8px;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.2s;
            margin-bottom: 1rem;
            width: 100%;
            justify-content: center;
        }

        .folder-select-btn:hover {
            border-color: var(--accent-primary);
            color: var(--accent-primary);
        }

        .folder-select-btn.selected {
            border-style: solid;
            border-color: var(--accent-secondary);
            background: rgba(139, 92, 246, 0.1);
            color: var(--accent-secondary);
        }

        .content-option {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            margin-bottom: 0.5rem;
        }

        .content-option:hover {
            border-color: var(--accent-primary);
        }

        .content-option.selected {
            border-color: var(--accent-primary);
            background: rgba(99, 102, 241, 0.1);
        }

        .content-option input[type="radio"] {
            accent-color: var(--accent-primary);
        }

        .content-option-label {
            flex: 1;
        }

        .content-option-label strong {
            display: block;
            color: var(--text-primary);
            margin-bottom: 0.2rem;
        }

        .content-option-label span {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        /* Empty States */
        .empty-state {
            text-align: center;
            padding: 2rem;
            color: var(--text-muted);
        }

        .empty-state i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty-state p {
            font-size: 0.9rem;
        }

        /* Custom Confirm Modal */
        .confirm-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .confirm-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .confirm-box {
            background: linear-gradient(145deg, var(--bg-secondary) 0%, var(--bg-tertiary) 100%);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 2rem;
            width: 90%;
            max-width: 400px;
            text-align: center;
            transform: scale(0.8) translateY(20px);
            transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5),
                        0 0 100px rgba(239, 68, 68, 0.1);
        }

        .confirm-overlay.active .confirm-box {
            transform: scale(1) translateY(0);
        }

        .confirm-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 1.5rem;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: iconPulse 2s ease-in-out infinite;
            box-shadow: 0 10px 30px rgba(239, 68, 68, 0.4);
        }

        .confirm-icon i {
            font-size: 2rem;
            color: white;
        }

        .confirm-icon.warning {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            box-shadow: 0 10px 30px rgba(245, 158, 11, 0.4);
        }

        .confirm-icon.info {
            background: var(--gradient-main);
            box-shadow: 0 10px 30px rgba(99, 102, 241, 0.4);
        }

        @keyframes iconPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .confirm-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.75rem;
        }

        .confirm-message {
            font-size: 0.95rem;
            color: var(--text-secondary);
            margin-bottom: 1rem;
            line-height: 1.6;
        }

        .confirm-details {
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 0.75rem 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.85rem;
            color: var(--text-muted);
            display: none;
        }

        .confirm-details.show {
            display: block;
        }

        .confirm-details .file-count {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--danger);
            display: block;
            margin-bottom: 0.25rem;
        }

        .confirm-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        .confirm-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 10px;
            font-family: inherit;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .confirm-cancel {
            background: var(--bg-card);
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
        }

        .confirm-cancel:hover {
            background: var(--bg-tertiary);
            border-color: var(--text-muted);
            transform: translateY(-2px);
        }

        .confirm-delete {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4);
        }

        .confirm-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.5);
        }

        /* DB Status */
        .db-status {
            padding: 0.5rem 1rem;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--bg-tertiary);
            border-top: 1px solid var(--border-color);
        }

        .db-status.connected {
            color: var(--success);
        }

        .db-status.disconnected {
            color: var(--danger);
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        .db-status.connected .status-dot {
            background: var(--success);
        }

        .db-status.disconnected .status-dot {
            background: var(--danger);
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* Work Distribution Section - Ultra Compact */
        .distribution-section {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 0.5rem 0.75rem;
            position: relative;
        }

        .distribution-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.4rem;
        }

        .distribution-title {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--text-secondary);
        }

        .distribution-title i {
            color: var(--accent-primary);
            font-size: 0.75rem;
        }

        .distribution-value {
            display: flex;
            align-items: baseline;
            gap: 0.2rem;
            background: var(--gradient-main);
            padding: 0.2rem 0.5rem;
            border-radius: 6px;
            box-shadow: 0 2px 6px rgba(99, 102, 241, 0.2);
        }

        .value-number {
            font-size: 0.9rem;
            font-weight: 700;
            color: white;
            line-height: 1;
        }

        .value-label {
            font-size: 0.55rem;
            color: rgba(255, 255, 255, 0.8);
            font-weight: 500;
        }

        /* Distribution Append Checkbox */
        .dist-append-check {
            margin-left: auto;
            display: flex;
            align-items: center;
            cursor: pointer;
            padding: 0.2rem 0.35rem;
            border-radius: 4px;
            transition: all 0.2s;
            background: transparent;
            border: 1px solid transparent;
        }

        .dist-append-check:hover {
            background: rgba(139, 92, 246, 0.1);
            border-color: rgba(139, 92, 246, 0.2);
        }

        .dist-append-check.active {
            background: rgba(16, 185, 129, 0.15);
            border-color: rgba(16, 185, 129, 0.3);
        }

        .dist-append-check input {
            display: none;
        }

        .dist-append-check .check-box {
            width: 14px;
            height: 14px;
            border: 1.5px solid rgba(139, 92, 246, 0.4);
            border-radius: 3px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            background: rgba(30, 30, 60, 0.5);
        }

        .dist-append-check .check-box i {
            font-size: 0.5rem;
            color: transparent;
        }

        .dist-append-check.active .check-box {
            background: linear-gradient(135deg, #10b981, #34d399);
            border-color: #10b981;
        }

        .dist-append-check.active .check-box i {
            color: #fff;
        }

        /* Slider Container - Compact */
        .slider-container {
            margin-bottom: 0;
        }

        .slider-track {
            position: relative;
            height: 6px;
            background: var(--bg-tertiary);
            border-radius: 3px;
            overflow: visible;
        }

        .slider-fill {
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            background: var(--gradient-main);
            border-radius: 3px;
            transition: width 0.1s ease;
            pointer-events: none;
        }

        .slider-input {
            position: absolute;
            top: 50%;
            left: 0;
            transform: translateY(-50%);
            width: 100%;
            height: 16px;
            -webkit-appearance: none;
            appearance: none;
            background: transparent;
            cursor: pointer;
            margin: 0;
        }

        .slider-input::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: linear-gradient(145deg, #ffffff, #e6e6e6);
            border: 2px solid var(--accent-primary);
            box-shadow: 0 2px 6px rgba(99, 102, 241, 0.4);
            cursor: grab;
            transition: all 0.2s ease;
        }

        .slider-input::-webkit-slider-thumb:hover {
            transform: scale(1.1);
        }

        .slider-input::-webkit-slider-thumb:active {
            cursor: grabbing;
        }

        .slider-input::-moz-range-thumb {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: linear-gradient(145deg, #ffffff, #e6e6e6);
            border: 2px solid var(--accent-primary);
            box-shadow: 0 2px 6px rgba(99, 102, 241, 0.4);
            cursor: grab;
        }

        /* Distribution Active State */
        .distribution-section.active {
            border-color: var(--accent-primary);
            box-shadow: 0 0 8px rgba(99, 102, 241, 0.1);
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar {
                width: 280px;
                min-width: 280px;
            }
            
            .main-content {
                max-width: calc(100vw - 280px);
            }
        }

        @media (max-width: 768px) {
            .app-container {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                min-width: 100%;
                height: auto;
                max-height: 50vh;
                position: relative;
            }

            .main-content {
                padding: 1rem;
                max-width: 100vw;
            }

            .editor-actions {
                flex-wrap: wrap;
            }

            .btn {
                padding: 0.5rem 1rem;
                font-size: 0.8rem;
            }

            .folder-picker-group {
                width: 100%;
                margin-left: 0;
                padding-left: 0;
                border-left: none;
                border-top: 1px solid var(--border-color);
                padding-top: 0.75rem;
                margin-top: 0.5rem;
                justify-content: center;
                flex-wrap: wrap;
            }

            .folder-path-indicator {
                max-width: 150px;
            }

            .auto-send-timer {
                order: 3;
            }
        }

        /* ========================================== */
        /* AI PROMPT DICTIONARY SECTION - MODERN REDESIGN */
        /* ========================================== */
        .dictionary-section {
            background: linear-gradient(145deg, rgba(15, 15, 35, 0.95), rgba(20, 20, 45, 0.9));
            border: 1px solid rgba(99, 102, 241, 0.15);
            border-radius: 12px;
            overflow: hidden;
            margin-top: 1rem;
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.2), inset 0 1px 0 rgba(255, 255, 255, 0.05);
            max-width: 100%;
            width: 100%;
            box-sizing: border-box;
        }

        .dictionary-header {
            padding: 0.6rem 1rem;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.12) 0%, rgba(139, 92, 246, 0.08) 100%);
            border-bottom: 1px solid rgba(99, 102, 241, 0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
        }

        .dictionary-header h3 {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.4rem;
            margin: 0;
            letter-spacing: 0.3px;
        }

        .dictionary-header h3 i {
            color: #a78bfa;
            font-size: 0.85rem;
        }

        /* Quiz Button */
        .dict-quiz-btn {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.3rem 0.6rem;
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.15), rgba(99, 102, 241, 0.1));
            border: 1px solid rgba(139, 92, 246, 0.3);
            border-radius: 6px;
            color: #a78bfa;
            font-size: 0.65rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .dict-quiz-btn:hover {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.25), rgba(99, 102, 241, 0.2));
            border-color: rgba(139, 92, 246, 0.5);
            color: #c4b5fd;
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(139, 92, 246, 0.2);
        }

        .dict-quiz-btn i {
            font-size: 0.7rem;
            transition: transform 0.3s ease;
        }

        .dict-quiz-btn:hover i {
            transform: scale(1.1);
        }

        /* Admin Button */
        .dict-admin-btn {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.3rem 0.6rem;
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.15), rgba(99, 102, 241, 0.1));
            border: 1px solid rgba(139, 92, 246, 0.3);
            border-radius: 6px;
            color: #a78bfa;
            font-size: 0.65rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .dict-admin-btn:hover {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.25), rgba(99, 102, 241, 0.2));
            border-color: rgba(139, 92, 246, 0.5);
            color: #c4b5fd;
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(139, 92, 246, 0.2);
        }

        .dict-admin-btn i {
            font-size: 0.7rem;
            transition: transform 0.3s ease;
        }

        .dict-admin-btn:hover i {
            transform: rotate(90deg);
        }

        /* Refresh Button */
        .dict-refresh-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 26px;
            height: 26px;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(52, 211, 153, 0.1));
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 6px;
            color: #34d399;
            font-size: 0.7rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .dict-refresh-btn:hover {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.25), rgba(52, 211, 153, 0.2));
            border-color: rgba(16, 185, 129, 0.5);
            color: #6ee7b7;
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(16, 185, 129, 0.2);
        }

        .dict-refresh-btn i {
            transition: transform 0.4s ease;
        }

        .dict-refresh-btn:hover i {
            transform: rotate(180deg);
        }

        /* Reset Filter Button */
        .dict-reset-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 26px;
            height: 26px;
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.15), rgba(248, 113, 113, 0.1));
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 6px;
            color: #f87171;
            font-size: 0.65rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .dict-reset-btn:hover {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.25), rgba(248, 113, 113, 0.2));
            border-color: rgba(239, 68, 68, 0.5);
            color: #fca5a5;
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(239, 68, 68, 0.2);
        }

        /* Admin Popup Modal */
        .dict-admin-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 10000;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(4px);
            animation: fadeIn 0.2s ease;
        }

        .dict-admin-modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .dict-admin-popup {
            width: 90%;
            max-width: 1200px;
            height: 85vh;
            background: var(--bg-secondary);
            border: 1px solid rgba(139, 92, 246, 0.3);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4), 0 0 40px rgba(139, 92, 246, 0.15);
            animation: popupSlide 0.3s ease;
        }

        @keyframes popupSlide {
            from {
                opacity: 0;
                transform: scale(0.9) translateY(20px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .dict-admin-popup-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 1rem;
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.15), rgba(99, 102, 241, 0.1));
            border-bottom: 1px solid rgba(139, 92, 246, 0.2);
        }

        .dict-admin-popup-header h4 {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
        }

        .dict-admin-popup-header h4 i {
            color: #a78bfa;
        }

        .dict-admin-close-btn {
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 6px;
            color: #f87171;
            cursor: pointer;
            transition: all 0.2s;
        }

        .dict-admin-close-btn:hover {
            background: rgba(239, 68, 68, 0.2);
            border-color: rgba(239, 68, 68, 0.5);
            transform: scale(1.05);
        }

        .dict-admin-iframe {
            width: 100%;
            height: calc(100% - 48px);
            border: none;
            background: #fff;
        }

        /* Quiz Popup Modal */
        .dict-quiz-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 10000;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(4px);
            animation: fadeIn 0.2s ease;
        }

        .dict-quiz-modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .dict-quiz-popup {
            position: relative;
            width: 90%;
            max-width: 1200px;
            height: 85vh;
            min-width: 400px;
            min-height: 300px;
            background: var(--bg-secondary);
            border: 1px solid rgba(139, 92, 246, 0.3);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4), 0 0 40px rgba(139, 92, 246, 0.15);
            animation: popupSlide 0.3s ease;
            resize: both;
        }

        .dict-quiz-popup-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 1rem;
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.15), rgba(99, 102, 241, 0.1));
            border-bottom: 1px solid rgba(139, 92, 246, 0.2);
            cursor: move;
        }

        .dict-quiz-popup-header h4 {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
        }

        .dict-quiz-popup-header h4 i {
            color: #a78bfa;
        }

        .dict-quiz-close-btn {
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 6px;
            color: #f87171;
            cursor: pointer;
            transition: all 0.2s;
        }

        .dict-quiz-close-btn:hover {
            background: rgba(239, 68, 68, 0.2);
            border-color: rgba(239, 68, 68, 0.5);
            transform: scale(1.05);
        }

        .dict-quiz-iframe {
            width: 100%;
            height: calc(100% - 48px);
            border: none;
            background: #fff;
        }

        .dict-quiz-resize-handle {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 20px;
            height: 20px;
            cursor: se-resize;
            background: linear-gradient(135deg, transparent 0%, transparent 50%, rgba(139, 92, 246, 0.3) 50%);
        }

        .dictionary-wrapper {
            padding: 0.75rem;
        }

        /* Group Filter - Compact Pills */
        .group-slider-compact {
            background: rgba(30, 30, 60, 0.5);
            border: 1px solid rgba(99, 102, 241, 0.1);
            border-radius: 8px;
            padding: 0.5rem 0.6rem;
            margin-bottom: 0.6rem;
            overflow: hidden;
        }

        .slider-label {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 0.4rem;
            margin-bottom: 0.4rem;
            font-size: 0.65rem;
            color: var(--text-muted);
        }

        .slider-label .label-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.6rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: #a78bfa;
        }

        .slider-label .label-badge i {
            font-size: 0.55rem;
            color: #8b5cf6;
        }

        .slider-label .active-group {
            color: var(--text-muted);
            font-size: 0.55rem;
            padding-left: 0.3rem;
            border-left: 1px solid rgba(139, 92, 246, 0.3);
        }

        .slider-label .reset-btn,
        .slider-label .refresh-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            background: transparent;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            font-size: 0.6rem;
            width: 18px;
            height: 18px;
            border-radius: 4px;
            transition: all 0.2s;
            opacity: 0.7;
        }

        .slider-label .reset-btn:hover {
            color: #f87171;
            opacity: 1;
        }

        .slider-label .refresh-btn:hover {
            color: #34d399;
            opacity: 1;
        }

        .slider-label .refresh-btn.spinning i {
            animation: spin 0.8s linear infinite;
        }

        .slider-bar-wrapper {
            overflow-x: auto;
            scrollbar-width: none;
            margin: 0;
            padding: 0;
        }

        .slider-bar-wrapper::-webkit-scrollbar {
            display: none;
        }

        .slider-bar {
            display: flex;
            gap: 0.3rem;
            position: relative;
        }

        .slider-option {
            flex-shrink: 0;
            padding: 0.25rem 0.6rem;
            background: rgba(40, 40, 70, 0.6);
            border: 1px solid rgba(99, 102, 241, 0.1);
            border-radius: 12px;
            font-size: 0.65rem;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .slider-option:hover {
            border-color: rgba(139, 92, 246, 0.4);
            color: var(--text-secondary);
            background: rgba(139, 92, 246, 0.1);
        }

        .slider-option.active {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.9), rgba(139, 92, 246, 0.9));
            border-color: transparent;
            color: white;
            font-weight: 500;
            box-shadow: 0 2px 8px rgba(99, 102, 241, 0.3);
        }

        /* Dictionary Search - Sleek */
        .dict-search-container {
            position: relative;
            margin-bottom: 0.6rem;
        }

        .dict-search-container .search-icon {
            position: absolute;
            left: 0.6rem;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(167, 139, 250, 0.5);
            font-size: 0.75rem;
        }

        .dict-search-input {
            width: 100%;
            padding: 0.5rem 2rem 0.5rem 1.8rem;
            background: rgba(30, 30, 60, 0.6);
            border: 1px solid rgba(99, 102, 241, 0.15);
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 0.75rem;
            font-family: inherit;
            transition: all 0.2s;
        }

        .dict-search-input:focus {
            outline: none;
            border-color: rgba(139, 92, 246, 0.5);
            box-shadow: 0 0 0 2px rgba(139, 92, 246, 0.1);
            background: rgba(30, 30, 60, 0.8);
        }

        .dict-search-input::placeholder {
            color: var(--text-muted);
            font-size: 0.7rem;
        }

        .dict-search-loading {
            position: absolute;
            right: 2rem;
            top: 50%;
            transform: translateY(-50%);
            display: none;
        }

        .dict-search-loading.show {
            display: block;
        }

        .spinner-small {
            width: 12px;
            height: 12px;
            border: 2px solid rgba(139, 92, 246, 0.2);
            border-top-color: #a78bfa;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .dict-search-clear {
            position: absolute;
            right: 0.5rem;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            font-size: 0.7rem;
            padding: 0.2rem;
            border-radius: 4px;
            display: none;
            transition: all 0.2s;
        }

        .dict-search-clear.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .dict-search-clear:hover {
            color: #f87171;
            background: rgba(239, 68, 68, 0.1);
        }

        /* Results Info & Limit Selector */
        .dict-results-info {
            font-size: 0.6rem;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            justify-content: space-between;
        }

        .dict-results-info strong {
            color: #a78bfa;
        }

        .dict-limit-selector {
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .dict-limit-selector label {
            font-size: 0.55rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .dict-limit-selector select {
            background: rgba(40, 40, 70, 0.6);
            border: 1px solid rgba(99, 102, 241, 0.15);
            border-radius: 4px;
            color: #a78bfa;
            font-size: 0.6rem;
            padding: 0.2rem 0.4rem;
            cursor: pointer;
            font-family: inherit;
            outline: none;
            transition: all 0.2s;
        }

        .dict-limit-selector select:hover,
        .dict-limit-selector select:focus {
            border-color: rgba(139, 92, 246, 0.4);
        }

        .dict-limit-selector select option {
            background: #1a1a2e;
            color: var(--text-primary);
        }

        .dict-limit-reset {
            background: transparent;
            border: none;
            color: var(--text-muted);
            font-size: 0.5rem;
            cursor: pointer;
            opacity: 0.6;
            transition: all 0.2s;
            padding: 0.15rem;
        }

        .dict-limit-reset:hover {
            color: #a78bfa;
            opacity: 1;
        }

        /* Loading Skeleton - Compact */
        .dictionary-loading {
            display: none;
        }

        .dictionary-loading.show {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }

        .skeleton-card {
            background: rgba(40, 40, 70, 0.4);
            border: 1px solid rgba(99, 102, 241, 0.08);
            border-radius: 6px;
            padding: 0.6rem;
            height: 42px;
        }

        .skeleton-card::before {
            content: '';
            display: block;
            height: 10px;
            width: 50%;
            background: linear-gradient(90deg, rgba(60, 60, 90, 0.5) 25%, rgba(80, 80, 110, 0.5) 50%, rgba(60, 60, 90, 0.5) 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
            border-radius: 3px;
            margin-bottom: 0.4rem;
        }

        .skeleton-card::after {
            content: '';
            display: block;
            height: 8px;
            width: 80%;
            background: linear-gradient(90deg, rgba(60, 60, 90, 0.4) 25%, rgba(80, 80, 110, 0.4) 50%, rgba(60, 60, 90, 0.4) 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
            border-radius: 3px;
        }

        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }

        /* Dictionary List - Compact Rows */
        .dictionary-list {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
        }

        .dictionary-list.hidden {
            display: none;
        }

        /* Card Styles - Original Layout with Labels */
        .dict-card {
            background: rgba(30, 30, 60, 0.4);
            border: 1px solid rgba(99, 102, 241, 0.1);
            border-radius: 6px;
            padding: 0.5rem 0.6rem;
            transition: all 0.2s ease;
        }

        .dict-card:hover {
            border-color: rgba(139, 92, 246, 0.3);
            background: rgba(40, 40, 80, 0.5);
        }

        .dict-card-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.4rem;
            width: 100%;
        }

        .dict-card-title-wrap {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            flex: 0 1 auto;
            min-width: 120px;
            max-width: 280px;
            overflow: hidden;
        }

        .dict-label {
            font-size: 0.6rem;
            font-weight: 600;
            color: #8b5cf6;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            flex-shrink: 0;
        }

        .dict-card-title {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .dict-card-group-badge {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.6rem;
            padding: 0.15rem 0.35rem;
            background: rgba(139, 92, 246, 0.15);
            color: #a78bfa;
            border-radius: 3px;
            white-space: nowrap;
            flex-shrink: 0;
            margin-left: auto;
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .dict-card-group-badge .dict-label {
            color: #c4b5fd;
            font-size: 0.55rem;
        }

        .dict-card-phrase {
            display: flex;
            align-items: flex-start;
            gap: 0.4rem;
        }

        .dict-card-phrase .dict-label {
            padding-top: 0.1rem;
        }

        .dict-card-phrase p {
            flex: 1;
            font-size: 0.75rem;
            color: var(--text-muted);
            margin: 0;
            line-height: 1.4;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .dict-btn-copy {
            background: transparent;
            border: 1px solid rgba(99, 102, 241, 0.15);
            color: var(--text-muted);
            font-size: 0.55rem;
            padding: 0.2rem 0.35rem;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
            flex-shrink: 0;
        }

        .dict-btn-copy:hover {
            color: #a78bfa;
            border-color: rgba(139, 92, 246, 0.4);
            background: rgba(139, 92, 246, 0.1);
        }

        .dict-btn-copy.copied {
            color: #34d399;
            border-color: rgba(16, 185, 129, 0.4);
        }

        /* View Code Button */
        .dict-view-code-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.15), rgba(139, 92, 246, 0.2));
            border: 1px solid rgba(99, 102, 241, 0.3);
            color: #a78bfa;
            font-size: 0.6rem;
            font-weight: 500;
            padding: 0.25rem 0.5rem;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.25s ease;
            font-family: inherit;
            margin-left: 0.4rem;
        }

        .dict-view-code-btn:hover {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.25), rgba(139, 92, 246, 0.35));
            border-color: rgba(139, 92, 246, 0.5);
            color: #c4b5fd;
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(139, 92, 246, 0.3);
        }

        .dict-view-code-btn:active {
            transform: translateY(0);
        }

        .dict-view-code-btn i {
            font-size: 0.55rem;
        }

        /* Dictionary Field Checkboxes */
        .dict-field-checks {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            flex-shrink: 0;
        }

        .dict-field-check {
            display: flex;
            align-items: center;
            gap: 0.15rem;
            cursor: pointer;
            padding: 0.1rem 0.25rem;
            border-radius: 3px;
            transition: all 0.2s;
            background: transparent;
            border: 1px solid transparent;
        }

        .dict-field-check:hover {
            background: rgba(139, 92, 246, 0.1);
            border-color: rgba(139, 92, 246, 0.2);
        }

        .dict-field-check.active {
            background: rgba(16, 185, 129, 0.15);
            border-color: rgba(16, 185, 129, 0.3);
        }

        .dict-field-check input[type="checkbox"] {
            display: none;
        }

        .dict-field-check .check-icon {
            width: 12px;
            height: 12px;
            border: 1.5px solid rgba(139, 92, 246, 0.4);
            border-radius: 2px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            background: rgba(30, 30, 60, 0.5);
        }

        .dict-field-check .check-icon i {
            font-size: 0.45rem;
            color: transparent;
            transition: all 0.2s;
        }

        .dict-field-check.active .check-icon {
            background: linear-gradient(135deg, #10b981, #34d399);
            border-color: #10b981;
        }

        .dict-field-check.active .check-icon i {
            color: #fff;
        }

        .dict-field-check .check-label {
            font-size: 0.55rem;
            color: var(--text-muted);
            font-weight: 500;
            letter-spacing: 0.2px;
            transition: color 0.2s;
        }

        .dict-field-check:hover .check-label {
            color: #a78bfa;
        }

        .dict-field-check.active .check-label {
            color: #34d399;
        }

        /* Preview Button - Opens in new tab */
        .dict-preview-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.15), rgba(99, 102, 241, 0.15));
            border: 1.5px solid rgba(139, 92, 246, 0.3);
            color: #a78bfa;
            font-size: 0.6rem;
            font-weight: 600;
            padding: 0.3rem 0.6rem;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-left: 0.4rem;
            flex-shrink: 0;
            position: relative;
            overflow: hidden;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .dict-preview-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.5s;
        }

        .dict-preview-btn:hover {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.3), rgba(99, 102, 241, 0.25));
            border-color: rgba(139, 92, 246, 0.6);
            color: #c4b5fd;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
        }

        .dict-preview-btn:hover::before {
            left: 100%;
        }

        .dict-preview-btn:active {
            transform: translateY(0);
            box-shadow: 0 2px 6px rgba(139, 92, 246, 0.2);
        }

        .dict-preview-btn i {
            font-size: 0.65rem;
            filter: drop-shadow(0 0 2px rgba(139, 92, 246, 0.5));
        }

        /* Empty State - Compact */
        .dict-empty-state {
            text-align: center;
            padding: 1.5rem 1rem;
            display: none;
        }

        .dict-empty-state.show {
            display: block;
        }

        .dict-empty-state-icon {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            opacity: 0.7;
        }

        .dict-empty-state-title {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-secondary);
            margin: 0 0 0.3rem;
        }

        .dict-empty-state-text {
            font-size: 0.7rem;
            color: var(--text-muted);
            margin: 0;
        }

        /* Pagination - Minimal */
        .dict-pagination {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            padding-top: 0.6rem;
            margin-top: 0.6rem;
            border-top: 1px solid rgba(99, 102, 241, 0.08);
        }

        .dict-pagination.hidden {
            display: none;
        }

        .dict-btn-pagination {
            padding: 0.3rem 0.6rem;
            background: rgba(40, 40, 70, 0.5);
            border: 1px solid rgba(99, 102, 241, 0.15);
            border-radius: 5px;
            color: var(--text-muted);
            font-size: 0.65rem;
            cursor: pointer;
            transition: all 0.2s;
            font-family: inherit;
        }

        .dict-btn-pagination:hover:not(:disabled) {
            background: rgba(139, 92, 246, 0.15);
            border-color: rgba(139, 92, 246, 0.4);
            color: #a78bfa;
        }

        .dict-btn-pagination:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        .dict-pagination-info {
            text-align: center;
            font-size: 0.6rem;
            color: var(--text-muted);
        }

        .dict-pagination-info span {
            display: inline;
        }

        .dict-pagination-info #dict-page-info {
            color: #a78bfa;
            font-weight: 500;
        }

        .dict-pagination-info #dict-items-info {
            margin-left: 0.3rem;
            opacity: 0.7;
        }

        /* Error State - Compact */
        .dict-error-state {
            background: rgba(239, 68, 68, 0.08);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 6px;
            padding: 0.75rem;
            text-align: center;
            display: none;
        }

        .dict-error-state.show {
            display: block;
        }

        .dict-error-state p {
            color: #fca5a5;
            font-size: 0.7rem;
            margin: 0;
        }

        .dict-error-state button {
            margin-top: 0.5rem;
            padding: 0.3rem 0.75rem;
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 5px;
            color: #fca5a5;
            font-size: 0.65rem;
            cursor: pointer;
            transition: all 0.2s;
            font-family: inherit;
        }

        .dict-error-state button:hover {
            background: rgba(239, 68, 68, 0.3);
            color: #fecaca;
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
            background: var(--warning);
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
            min-width: 200px;
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
            color: var(--success);
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
            color: var(--success);
            border: 1px solid rgba(34, 197, 94, 0.3);
        }
        
        .speed-comparison.slower {
            background: rgba(239, 68, 68, 0.15);
            color: var(--danger);
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
        
        /* Toast for connection switch */
        .db-switch-toast {
            position: fixed;
            top: 60px;
            right: 12px;
            padding: 12px 20px;
            background: rgba(0, 0, 0, 0.9);
            border-radius: 10px;
            z-index: 100000;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.1);
            animation: slideInRight 0.3s ease;
            max-width: 300px;
        }
        
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(50px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        @keyframes slideOutRight {
            from { opacity: 1; transform: translateX(0); }
            to { opacity: 0; transform: translateX(50px); }
        }
    </style>
</head>
<body>
    <!-- Database Connection Toggle Switch -->
    <div class="db-toggle-container" id="dbToggleContainer" title="Database Connection: Local (faster on server) / Remote (anywhere)" data-active="<?php echo $connectionType; ?>">
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
    <div class="bg-effects"></div>
    
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="logoPM.png" alt="Prompt Manager">
            </div>
            
            <div class="sidebar-content">
                <!-- File Upload -->
                <div class="section-title"><i class="fas fa-cloud-upload-alt"></i> File Upload</div>
                
                <!-- Toggle: Full Content vs Reference Only -->
                <div class="file-mode-toggle">
                    <div class="toggle-header">
                        <i class="fas fa-sliders-h"></i> File Mode
                    </div>
                    <div class="toggle-buttons">
                        <button type="button" class="toggle-btn" id="btnFullContent" onclick="setFileMode('content')">
                            <i class="fas fa-file-code"></i>
                            <span>Full Content</span>
                        </button>
                        <button type="button" class="toggle-btn active" id="btnReference" onclick="setFileMode('reference')">
                            <i class="fas fa-link"></i>
                            <span>Reference Only</span>
                        </button>
                    </div>
                    <input type="hidden" id="fileContentToggle" value="reference">
                </div>
                
                <!-- Drag & Drop Zone -->
                <div class="file-picker-container">
                    <input type="file" id="fileInput" multiple style="display: none;">
                    <div class="drop-zone-mini" id="dropZone">
                        <i class="fas fa-cloud-arrow-up"></i>
                        <span>Drop files here</span>
                    </div>
                </div>
                
                <!-- Uploaded Files Header with Select All and Delete All -->
                <div class="uploaded-files-header" id="uploadedFilesHeader" style="display: none;">
                    <div class="files-header-left">
                        <label class="select-all-checkbox" title="Select/Deselect All Files">
                            <input type="checkbox" id="selectAllFilesCheckbox" onchange="toggleAllUploadedFiles(this.checked)">
                            <span class="checkbox-custom"></span>
                            <span class="checkbox-label">Select All</span>
                        </label>
                        <span class="files-count"><i class="fas fa-paperclip"></i> <span id="filesCount">0</span> file(s)</span>
                    </div>
                    <button type="button" class="btn-delete-all" onclick="deleteAllFiles()" title="Delete all files">
                        <i class="fas fa-trash-alt"></i> Delete All
                    </button>
                </div>
                
                <div class="uploaded-files" id="uploadedFiles">
                    <!-- Files will be loaded here -->
                </div>
                
                <!-- Prompt Templates -->
                <div class="section-title-row">
                    <div class="section-title"><i class="fas fa-list-check"></i> Prompt Templates</div>
                    <button type="button" class="btn-add-template" onclick="openAddTemplateModal()">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
                
                <!-- Search Prompt Templates -->
                <div class="prompt-search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="promptSearchInput" placeholder="Search templates..." oninput="filterPromptTemplates()">
                    <button type="button" class="prompt-search-clear" id="promptSearchClear" onclick="clearPromptSearch()" style="display: none;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <!-- Select/Deselect All -->
                <div class="prompt-actions">
                    <label class="select-all-checkbox" title="Select/Deselect All Templates">
                        <input type="checkbox" id="selectAllTemplatesCheckbox" onchange="toggleAllTemplates(this.checked)">
                        <span class="checkbox-custom"></span>
                        <span class="checkbox-label">Select All</span>
                    </label>
                    <span class="prompt-counter" id="promptCounter">0/0</span>
                </div>
                
                <!-- Loading State -->
                <div class="template-loading" id="templateLoading" style="display: none;">
                    <i class="fas fa-spinner fa-spin"></i>
                    <span>Loading templates...</span>
                </div>
                
                <div class="prompt-list" id="promptList">
                    <!-- Prompts will be generated here -->
                </div>
                
                <!-- No Results Message -->
                <div class="prompt-no-results" id="promptNoResults" style="display: none;">
                    <i class="fas fa-search"></i>
                    <p>No templates found</p>
                </div>
            </div>
            
            <div class="db-status <?php echo $pdo ? 'connected' : 'disconnected'; ?>">
                <span class="status-dot"></span>
                <?php echo $pdo ? 'Database Connected' : 'Database Offline'; ?>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Work Distribution Slider -->
            <div class="distribution-section">
                <div class="distribution-header">
                    <div class="distribution-title">
                        <i class="fas fa-layer-group"></i>
                        <span>Steps</span>
                    </div>
                    <div class="distribution-value" id="distributionValue">
                        <span class="value-number" id="valueNumber">1</span>
                        <span class="value-label">/ 30</span>
                    </div>
                    <label class="dist-append-check" id="distAppendCheck" onclick="toggleDistributionAppend(event)" title="Append steps to prompt">
                        <input type="checkbox" id="distributionEnabled">
                        <span class="check-box"><i class="fas fa-check"></i></span>
                    </label>
                </div>
                
                <div class="slider-container">
                    <div class="slider-track">
                        <div class="slider-fill" id="sliderFill"></div>
                        <input type="range" min="1" max="30" value="1" class="slider-input" id="distributionSlider" oninput="updateDistribution(this.value)">
                    </div>
                </div>
            </div>
            
            <!-- Saved Prompts -->
            <div class="saved-prompts-section">
                <div class="saved-header">
                    <h3><i class="fas fa-bookmark"></i> Saved Prompts</h3>
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchPrompts" placeholder="Search saved prompts...">
                        <button type="button" class="search-clear-btn" id="savedSearchClear" onclick="clearSavedSearch()" style="display: none;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Select/Deselect All for Saved Prompts -->
                <div class="saved-actions-bar">
                    <label class="select-all-checkbox" title="Select/Deselect All Saved Prompts">
                        <input type="checkbox" id="selectAllSavedCheckbox" onchange="toggleAllSavedPrompts(this.checked)">
                        <span class="checkbox-custom"></span>
                        <span class="checkbox-label">Select All</span>
                    </label>
                    <span class="saved-counter" id="savedCounter">0/0</span>
                </div>
                
                <div class="saved-list" id="savedList">
                    <!-- Saved prompts will be loaded here -->
                </div>
                <div class="saved-scrollbar-track" id="savedScrollbarTrack">
                    <div class="saved-scrollbar-thumb" id="savedScrollbarThumb"></div>
                </div>
            </div>
            
            <!-- Development Dashboard -->
            <div class="dev-dashboard-section">
                <div class="dev-dashboard-header">
                    <div class="dev-dashboard-left">
                        <div class="dev-dashboard-title">
                            <span class="dev-icon">⚡</span>
                            <span class="dev-text">Development Dashboard</span>
                        </div>
                    </div>
                    <div class="dev-dashboard-actions">
                        <button type="button" class="dash-reset-btn" id="dashResetBtn" onclick="resetDashboard()" title="Reset Dashboard">
                            <i class="fas fa-undo"></i>
                        </button>
                        <div class="dev-dashboard-status">
                            <span class="status-dot"></span>
                            <span class="status-text">Ready</span>
                        </div>
                    </div>
                </div>
                <div class="dev-dashboard-content" id="devDashboardContent">
                    <div class="dashboard-sections-row">
                        <!-- All 4 Sections in One Row -->
                        <div class="dynamic-sections-container" id="dynamicSectionsContainer">
                            <!-- Database Section -->
                            <div class="database-section" id="databaseSection">
                                <div class="database-section-header">
                                    <div class="database-section-title">
                                        <i class="fas fa-database"></i>
                                        <span>Database</span>
                                    </div>
                                    <div class="database-section-actions">
                                        <button type="button" class="database-section-arrow" onclick="appendDatabaseToPrompt()" title="Insert Database to Prompt">
                                            <i class="fas fa-arrow-down"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="database-section-content">
                                    <div class="db-controls-compact">
                                        <div class="db-dropdown-row">
                                            <div class="dash-db-dropdown-wrap">
                                                <select class="dash-db-dropdown" id="dbDropdown" onchange="onDatabaseSelect()">
                                                    <option value="">-- Select --</option>
                                                </select>
                                                <i class="fas fa-chevron-down"></i>
                                            </div>
                                            <button type="button" class="dash-db-btn refresh" id="dbRefreshBtn" onclick="refreshDatabaseList()" title="Refresh">
                                                <i class="fas fa-sync-alt"></i>
                                            </button>
                                            <button type="button" class="dash-db-btn manage" onclick="openDbManager()" title="Manage">
                                                <i class="fas fa-cog"></i>
                                            </button>
                                        </div>
                                        <div class="db-cred-row-compact">
                                            <label class="dash-cred-btn remote" title="Remote">
                                                <input type="checkbox" id="dbCredentialsCheckbox" onchange="toggleDatabaseCredentials('remote')">
                                                <span><i class="fas fa-globe"></i> Remote</span>
                                            </label>
                                            <label class="dash-cred-btn localhost" title="Localhost">
                                                <input type="checkbox" id="dbLocalhostCheckbox" onchange="toggleDatabaseCredentials('localhost')">
                                                <span><i class="fas fa-server"></i> Local</span>
                                            </label>
                                        </div>
                                        <div class="db-prompt-row-compact">
                                            <button type="button" class="dash-db-prompt-btn" onclick="generateDatabasePrompt()" title="Smart Connection Prompt">
                                                <i class="fas fa-code"></i> Prompt
                                            </button>
                                            <button type="button" class="dash-db-prompt-btn dash-db-prompt-full" onclick="generateFullDatabasePrompt()" title="Full Prompt">
                                                <i class="fas fa-tachometer-alt"></i> Full
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Backend Section -->
                            <div class="dynamic-section" id="backendSection">
                                <div class="dynamic-section-header">
                                    <div class="dynamic-section-title backend-title">
                                        <i class="fas fa-server"></i>
                                        <span>Backend</span>
                                        <span class="dynamic-section-count" id="backendCount">0</span>
                                    </div>
                                    <div class="dynamic-section-actions">
                                        <button type="button" class="dynamic-section-arrow" onclick="appendSectionToPrompt('backend')" title="Insert Backend to Prompt">
                                            <i class="fas fa-arrow-down"></i>
                                        </button>
                                        <button type="button" class="dynamic-section-add-btn" onclick="addDynamicItem('backend')">
                                            <i class="fas fa-plus"></i> Add
                                        </button>
                                    </div>
                                </div>
                                <div class="dynamic-items-grid" id="backendItemsGrid">
                                    <div class="dynamic-empty-state" id="backendEmptyState">
                                        <i class="fas fa-server"></i>
                                        <p>No backend items</p>
                                        <small>Click "Add" to create one</small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Pages Section -->
                            <div class="dynamic-section" id="pageSection">
                                <div class="dynamic-section-header">
                                    <div class="dynamic-section-title page-title">
                                        <i class="fas fa-layer-group"></i>
                                        <span>Pages</span>
                                        <span class="dynamic-section-count" id="pageCount">0</span>
                                    </div>
                                    <div class="dynamic-section-actions">
                                        <button type="button" class="dynamic-section-arrow" onclick="appendSectionToPrompt('page')" title="Insert Pages to Prompt">
                                            <i class="fas fa-arrow-down"></i>
                                        </button>
                                        <button type="button" class="dynamic-section-add-btn" onclick="addDynamicItem('page')">
                                            <i class="fas fa-plus"></i> Add
                                        </button>
                                    </div>
                                </div>
                                <div class="dynamic-items-grid" id="pageItemsGrid">
                                    <div class="dynamic-empty-state" id="pageEmptyState">
                                        <i class="fas fa-layer-group"></i>
                                        <p>No page items</p>
                                        <small>Click "Add" to create one</small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Frontend Section -->
                            <div class="dynamic-section" id="frontendSection">
                                <div class="dynamic-section-header">
                                    <div class="dynamic-section-title frontend-title">
                                        <i class="fas fa-paint-brush"></i>
                                        <span>Frontend</span>
                                        <span class="dynamic-section-count" id="frontendCount">0</span>
                                    </div>
                                    <div class="dynamic-section-actions">
                                        <button type="button" class="dynamic-section-arrow" onclick="appendSectionToPrompt('frontend')" title="Insert Frontend to Prompt">
                                            <i class="fas fa-arrow-down"></i>
                                        </button>
                                        <button type="button" class="dynamic-section-add-btn" onclick="addDynamicItem('frontend')">
                                            <i class="fas fa-plus"></i> Add
                                        </button>
                                    </div>
                                </div>
                                <div class="dynamic-items-grid" id="frontendItemsGrid">
                                    <div class="dynamic-empty-state" id="frontendEmptyState">
                                        <i class="fas fa-paint-brush"></i>
                                        <p>No frontend items</p>
                                        <small>Click "Add" to create one</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Dashboard Footer with Project Management & Generate Button -->
                        <div class="dashboard-footer">
                            <!-- Project Management Section (Left) -->
                            <div class="project-management-group">
                                <div class="project-selector-wrap">
                                    <select class="project-selector" id="projectSelector" onchange="onProjectSelect()">
                                        <option value="">-- No Project --</option>
                                    </select>
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                                <button type="button" class="project-btn new-btn" onclick="openNewProjectPopup()" title="New Project">
                                    <i class="fas fa-plus"></i>
                                    <span>New</span>
                                </button>
                                <button type="button" class="project-btn save-btn" onclick="saveCurrentProject()" title="Save Project">
                                    <i class="fas fa-save"></i>
                                    <span>Save</span>
                                </button>
                                <button type="button" class="project-btn load-btn" onclick="openLoadProjectPopup()" title="Load Project">
                                    <i class="fas fa-folder-open"></i>
                                    <span>Load</span>
                                </button>
                                <button type="button" class="project-btn delete-btn" onclick="deleteCurrentProject()" title="Delete Project">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <button type="button" class="project-btn reset-btn" onclick="resetDashboardProject()" title="Reset Dashboard">
                                    <i class="fas fa-undo-alt"></i>
                                </button>
                            </div>
                            
                            <!-- Divider -->
                            <div class="footer-divider"></div>
                            
                            <!-- Generate Section (Right) -->
                            <div class="generate-group">
                                <button type="button" class="footer-arrow-btn" title="Insert ALL sections to prompt" onclick="appendAllSectionsToPrompt()">
                                    <i class="fas fa-arrow-down"></i>
                                </button>
                                <button type="button" class="dash-generate-btn" id="dashGenerateBtn" onclick="generateComprehensivePrompt()">
                                    <i class="fas fa-magic"></i>
                                    <span>Generate</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="dash-db-empty" id="dbNoConnections" style="display: none;">
                        <span>No databases found</span>
                        <a href="javascript:void(0)" onclick="openDbManager()">+ Add</a>
                    </div>
                </div>
            </div>
            
            <!-- Editor -->
            <div class="editor-container">
                <div class="editor-header">
                    <div class="editor-title">
                        <i class="fas fa-terminal"></i>
                        <span>Prompt Editor</span>
                        
                        <!-- Editor Search Bar -->
                        <div class="editor-search-bar" id="editorSearchBar">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" 
                                   class="editor-search-input" 
                                   id="editorSearchInput" 
                                   placeholder="Search..." 
                                   autocomplete="off"
                                   spellcheck="false">
                            <span class="search-results-badge" id="searchResultsBadge">0/0</span>
                            <div class="search-nav-btns">
                                <button type="button" class="search-nav-btn" onclick="editorSearchPrev()" title="Previous (Shift+Enter)">
                                    <i class="fas fa-chevron-up"></i>
                                </button>
                                <button type="button" class="search-nav-btn" onclick="editorSearchNext()" title="Next (Enter)">
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                            </div>
                            <button type="button" class="search-clear-btn" id="searchClearBtn" onclick="clearEditorSearch()" title="Clear search (Esc)">
                                <i class="fas fa-times" style="font-weight: 900;"></i>
                            </button>
                        </div>
                        
                        <a href="?logout=1" class="logout-btn" title="Logout">
                            <i class="fas fa-sign-out-alt"></i>
                        </a>
                    </div>
                    
                    <!-- History Navigation (Undo/Redo) -->
                    <div class="history-navigation">
                        <button type="button" class="history-btn undo" id="btnUndo" onclick="historyUndo()" disabled title="Undo (Ctrl+Z)">
                            <i class="fas fa-arrow-left"></i>
                            <span class="history-badge" id="undoCount">0</span>
                        </button>
                        <div class="history-divider"></div>
                        <div class="history-info">
                            <span class="history-position" id="historyPosition">0/0</span>
                            <span class="history-label">History</span>
                        </div>
                        <div class="history-divider"></div>
                        <button type="button" class="history-btn redo" id="btnRedo" onclick="historyRedo()" disabled title="Redo (Ctrl+Y)">
                            <i class="fas fa-arrow-right"></i>
                            <span class="history-badge" id="redoCount">0</span>
                        </button>
                        <div class="history-divider"></div>
                        <button type="button" class="history-btn clear" id="btnClearHistory" onclick="resetHistory()" title="Clear History">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <div class="editor-actions">
                        <button class="btn btn-secondary" onclick="clearEditor()">
                            <i class="fas fa-eraser"></i> Clear
                        </button>
                        <button class="btn btn-paste" onclick="pasteToEditor()">
                            <i class="fas fa-paste"></i> Paste
                        </button>
                        <button class="btn btn-primary" onclick="copyPrompt()">
                            <i class="fas fa-copy"></i> Copy
                        </button>
                        <button class="btn btn-success" onclick="openSaveModal()">
                            <i class="fas fa-save"></i> Save
                        </button>
                        
                        <!-- Folder Picker Group -->
                        <div class="folder-picker-group">
                            <button class="btn btn-folder" id="btnFolderPicker" onclick="selectPromptFolder()" title="Select folder for prompt.txt">
                                <i class="fas fa-folder-open"></i> Folder
                                <span class="folder-status"></span>
                            </button>
                            <button class="btn-clear-picker" id="btnClearFolder" onclick="clearFolderSelection()" title="Clear folder selection">
                                <i class="fas fa-times"></i>
                            </button>
                            <button class="btn btn-send" id="btnSendToFile" onclick="sendToPromptFile()" disabled title="Send to prompt.txt">
                                <i class="fas fa-paper-plane"></i> Send
                            </button>
                            <button class="btn btn-pull" id="btnPullFromFile" onclick="pullFromPromptFile()" disabled title="Pull from prompt.txt">
                                <i class="fas fa-download"></i> Pull
                            </button>
                            
                            <!-- Auto-Send Timer -->
                            <div class="auto-send-timer" id="autoSendTimer" title="Auto-send interval (0 = disabled)">
                                <i class="fas fa-sync-alt"></i>
                                <input type="number" class="timer-input" id="timerInput" min="0" max="999" value="0" onchange="updateAutoSendTimer()" oninput="updateAutoSendTimer()">
                                <span class="timer-label">sec</span>
                                <span class="timer-countdown" id="timerCountdown">0</span>
                            </div>
                            
                            <div class="folder-path-indicator" id="folderPathIndicator">
                                <i class="fas fa-link"></i>
                                <span id="folderPathText">No folder selected</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="editor-body">
                    <div class="editor-highlight-overlay" id="editorHighlightOverlay"></div>
                    <textarea id="promptEditor" placeholder="Your generated prompt will appear here...&#10;&#10;Check the prompt templates on the left to build your prompt, or type directly."></textarea>
                    <div class="resize-handle" id="resizeHandle" title="Drag to resize">
                        <i class="fas fa-grip-lines"></i>
                    </div>
                </div>
                
                <!-- File Transfer Section -->
                <div class="file-transfer-section">
                    <!-- Left File Group -->
                    <div class="file-transfer-group left">
                        <button class="file-picker-btn" id="filePickerLeft" onclick="selectTransferFile('left')" title="Select a file">
                            <i class="fas fa-file"></i>
                            <span class="file-name" id="fileNameLeft">Select File</span>
                        </button>
                        <button class="btn-clear-picker" id="btnClearLeft" onclick="clearFileSelection('left')" title="Clear file">
                            <i class="fas fa-times"></i>
                        </button>
                        <button class="btn-file-action btn-file-pull" id="btnPullLeft" onclick="pullFromTransferFile('left')" disabled title="Pull content from file">
                            <i class="fas fa-download"></i> Pull
                        </button>
                        <button class="btn-file-action btn-file-push" id="btnPushLeft" onclick="pushToTransferFile('left')" disabled title="Push content to file">
                            <i class="fas fa-upload"></i> Push
                        </button>
                    </div>
                    
                    <!-- Move Folder Tools -->
                    <div class="move-folder-group">
                        <button class="btn-move-folder" onclick="openMoveFolderModal()" title="Move folder to another location">
                            <i class="fas fa-folder-open"></i>
                            <i class="fas fa-arrow-right"></i>
                            <i class="fas fa-folder"></i>
                            Move
                        </button>
                    </div>
                    
                    <!-- File Management Buttons -->
                    <div class="file-management-group">
                        <button class="btn-file-manage btn-create" onclick="createNewFile()" title="Create new file">
                            <i class="fas fa-file-medical"></i>
                        </button>
                        <button class="btn-file-manage btn-delete" onclick="deleteSelectedFile()" title="Delete files">
                            <i class="fas fa-file-excel"></i>
                        </button>
                        <button class="btn-file-manage btn-folder" onclick="createNewFolder()" title="Create new folder">
                            <i class="fas fa-folder-plus"></i>
                        </button>
                        <button class="btn-file-manage btn-delete-folder" onclick="deleteFolderModal()" title="Delete folder">
                            <i class="fas fa-folder-minus"></i>
                        </button>
                        <button class="btn-file-manage btn-rename" onclick="renameSelectedFile()" title="Rename a file">
                            <i class="fas fa-edit"></i>
                        </button>
                    </div>
                    
                    <!-- Move Files Tools -->
                    <div class="move-folder-group">
                        <button class="btn-move-folder" style="color: #8b5cf6; border-color: rgba(139, 92, 246, 0.3);" onclick="openMoveFilesModal()" title="Move files to another folder">
                            <i class="fas fa-file"></i>
                            <i class="fas fa-arrow-right"></i>
                            <i class="fas fa-folder"></i>
                            Move
                        </button>
                    </div>
                    
                    <!-- Right File Group -->
                    <div class="file-transfer-group right">
                        <button class="btn-file-action btn-file-pull" id="btnPullRight" onclick="pullFromTransferFile('right')" disabled title="Pull content from file">
                            <i class="fas fa-download"></i> Pull
                        </button>
                        <button class="btn-file-action btn-file-push" id="btnPushRight" onclick="pushToTransferFile('right')" disabled title="Push content to file">
                            <i class="fas fa-upload"></i> Push
                        </button>
                        <button class="file-picker-btn" id="filePickerRight" onclick="selectTransferFile('right')" title="Select a file">
                            <i class="fas fa-file"></i>
                            <span class="file-name" id="fileNameRight">Select File</span>
                        </button>
                        <button class="btn-clear-picker" id="btnClearRight" onclick="clearFileSelection('right')" title="Clear file">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                
                <div class="editor-footer">
                    <div class="char-count">
                        <i class="fas fa-font"></i>
                        <span id="charCount">0</span> characters
                    </div>
                    <div class="word-count">
                        <i class="fas fa-text-width"></i>
                        <span id="wordCount">0</span> words
                    </div>
                </div>
            </div>
            
            <!-- AI Prompt Dictionary Section -->
            <div class="dictionary-section">
                <div class="dictionary-header">
                    <h3><i class="fas fa-book"></i> AI Prompt Dictionary</h3>
                    <button type="button" class="dict-quiz-btn" onclick="openDictQuizPopup()" title="Open Quiz">
                        <i class="fas fa-question-circle"></i>
                        <span>Quiz</span>
                    </button>
                    <button type="button" class="dict-admin-btn" onclick="openDictAdminPopup()" title="Open Admin Panel">
                        <i class="fas fa-cog"></i>
                        <span>Admin</span>
                    </button>
                    <button type="button" class="dict-refresh-btn" onclick="dictRefreshAPI()" title="Refresh from API">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <button type="button" class="dict-reset-btn" id="dictClearGroupFilter" onclick="dictResetGroupFilter()" title="Reset filter to All">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="dictionary-wrapper" id="dictionaryWrapper">
                    <!-- Group Filter Slider -->
                    <div class="group-slider-compact" id="dictGroupSlider">
                        <div class="slider-label">
                            <span class="label-badge"><i class="fas fa-layer-group"></i> Group</span>
                            <span class="active-group" id="dictActiveGroupName">All</span>
                        </div>
                        <div class="slider-bar-wrapper">
                            <div class="slider-bar" id="dictSliderTrack">
                                <!-- Groups loaded dynamically -->
                            </div>
                        </div>
                    </div>
                    
                    <!-- Search Bar -->
                    <div class="dict-search-container">
                        <span class="search-icon"><i class="fas fa-search"></i></span>
                        <input type="text" 
                               class="dict-search-input" 
                               id="dictSearchInput"
                               placeholder="Search prompts by title or phrase..." 
                               autocomplete="off">
                        <div class="dict-search-loading" id="dictSearchLoading">
                            <div class="spinner-small"></div>
                        </div>
                        <button type="button" class="dict-search-clear" id="dictSearchClear" onclick="dictClearSearch()" title="Clear">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <!-- Results Info & Limit Selector -->
                    <div class="dict-results-info" id="dictResultsInfo">
                        <span>Showing <strong id="dictResultsCount">0</strong> prompts</span>
                        <div class="dict-limit-selector">
                            <label>Show:</label>
                            <select id="dictLimitSelect" onchange="dictChangeLimit(this.value)">
                                <option value="10">10</option>
                                <option value="20">20</option>
                                <option value="30" selected>30</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                            <button type="button" class="dict-limit-reset" onclick="dictResetLimit()" title="Reset to 30">↻</button>
                        </div>
                    </div>
                    
                    <!-- Loading Skeleton -->
                    <div class="dictionary-loading" id="dictLoading">
                        <div class="skeleton-card"></div>
                        <div class="skeleton-card"></div>
                        <div class="skeleton-card"></div>
                    </div>
                    
                    <!-- Error State -->
                    <div class="dict-error-state" id="dictErrorState">
                        <p id="dictErrorMessage">Failed to load prompts</p>
                        <button type="button" onclick="dictLoadPrompts()">Try Again</button>
                    </div>
                    
                    <!-- Prompts List -->
                    <div class="dictionary-list" id="dictList">
                        <!-- Prompt cards loaded dynamically -->
                    </div>
                    
                    <!-- Empty State -->
                    <div class="dict-empty-state" id="dictEmptyState">
                        <div class="dict-empty-state-icon">📭</div>
                        <h3 class="dict-empty-state-title">No prompts found</h3>
                        <p class="dict-empty-state-text">Try adjusting your search or filters</p>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="dict-pagination" id="dictPagination">
                        <button type="button" class="dict-btn-pagination" id="dictBtnPrev" onclick="dictPrevPage()" disabled>← Previous</button>
                        <div class="dict-pagination-info">
                            <span id="dict-page-info">Page 1 of 1</span>
                            <span id="dict-items-info">(0 items)</span>
                        </div>
                        <button type="button" class="dict-btn-pagination" id="dictBtnNext" onclick="dictNextPage()">Next →</button>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Save Modal -->
    <div class="modal-overlay" id="saveModal">
        <div class="modal">
            <div class="modal-header">
                <h3><i class="fas fa-save"></i> Save Prompt</h3>
                <button class="modal-close" onclick="closeModal('saveModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="promptTitle">Prompt Title</label>
                    <input type="text" id="promptTitle" placeholder="Enter a title for your prompt...">
                </div>
                <div class="form-group">
                    <label for="promptContent">Prompt Content</label>
                    <textarea id="promptContent" placeholder="Prompt content will be auto-filled..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('saveModal')">Cancel</button>
                <button class="btn btn-success" onclick="savePrompt()">
                    <i class="fas fa-check"></i> Save Prompt
                </button>
            </div>
            <input type="hidden" id="editPromptId" value="">
        </div>
    </div>
    
    <!-- Create File Modal -->
    <div class="modal-overlay" id="createFileModal">
        <div class="modal">
            <div class="modal-header">
                <h3><i class="fas fa-file-medical" style="color: #10b981;"></i> Create New File</h3>
                <button class="modal-close" onclick="closeModal('createFileModal')">&times;</button>
            </div>
            <div class="modal-body">
                <i class="fas fa-file-medical file-modal-icon create"></i>
                <p class="file-modal-message">Create a new file in your selected folder</p>
                
                <button class="folder-select-btn" id="createFolderBtn" onclick="selectCreateFolder()">
                    <i class="fas fa-folder-open"></i>
                    <span id="createFolderName">Click to select folder</span>
                </button>
                
                <div class="form-group">
                    <label for="newFileName">File Name</label>
                    <input type="text" id="newFileName" placeholder="Enter file name with extension (e.g., myfile.txt)">
                </div>
                
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.75rem; color: var(--text-secondary);">Initial Content</label>
                <div class="content-option" onclick="selectContentOption('empty')">
                    <input type="radio" name="contentOption" id="contentEmpty" value="empty" checked>
                    <div class="content-option-label">
                        <strong>Empty File</strong>
                        <span>Create an empty file</span>
                    </div>
                </div>
                <div class="content-option" onclick="selectContentOption('editor')">
                    <input type="radio" name="contentOption" id="contentEditor" value="editor">
                    <div class="content-option-label">
                        <strong>Editor Content</strong>
                        <span>Use current prompt editor content</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('createFileModal')">Cancel</button>
                <button class="btn btn-success" onclick="confirmCreateFile()">
                    <i class="fas fa-plus"></i> Create File
                </button>
            </div>
        </div>
    </div>
    
    <!-- Create Folder Modal -->
    <div class="modal-overlay" id="createFolderModal">
        <div class="modal">
            <div class="modal-header">
                <h3><i class="fas fa-folder-plus" style="color: #3b82f6;"></i> Create New Folder</h3>
                <button class="modal-close" onclick="closeModal('createFolderModal')">&times;</button>
            </div>
            <div class="modal-body">
                <i class="fas fa-folder-plus file-modal-icon" style="color: #3b82f6;"></i>
                <p class="file-modal-message">Create a new folder inside your selected directory</p>
                
                <button class="folder-select-btn" id="createFolderParentBtn" onclick="selectCreateFolderParent()">
                    <i class="fas fa-folder-open"></i>
                    <span id="createFolderParentName">Click to select parent folder</span>
                </button>
                
                <div class="form-group">
                    <label for="newFolderName">Folder Name</label>
                    <input type="text" id="newFolderName" placeholder="Enter folder name">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('createFolderModal')">Cancel</button>
                <button class="btn btn-success" onclick="confirmCreateFolder()">
                    <i class="fas fa-plus"></i> Create Folder
                </button>
            </div>
        </div>
    </div>
    
    <!-- Move Files Modal -->
    <div class="modal-overlay" id="moveFilesModal">
        <div class="modal" style="max-width: 550px;">
            <div class="modal-header">
                <h3><i class="fas fa-file-export" style="color: #8b5cf6;"></i> Move Files <span class="file-count-badge" id="moveFilesCountBadge"><i class="fas fa-file"></i> 0</span></h3>
                <button class="modal-close" onclick="closeModal('moveFilesModal')">&times;</button>
            </div>
            <div class="modal-body">
                <i class="fas fa-file-export file-modal-icon" style="color: #8b5cf6;"></i>
                <p class="file-modal-message">Select source folder, files to move, then destination</p>
                
                <!-- Source Selection -->
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-folder-open" style="color: #f59e0b;"></i> Source folder
                    </label>
                    <button class="folder-select-btn" id="moveFilesSourceBtn" onclick="selectMoveFilesSource()">
                        <i class="fas fa-folder-open"></i>
                        <span id="moveFilesSourceName">Click to select source folder</span>
                    </button>
                </div>
                
                <!-- Files to Move Selection -->
                <div id="moveFilesSelectContainer" style="display: none; margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-file" style="color: #8b5cf6;"></i> Select files to move (multiple allowed)
                    </label>
                    <div class="file-list-container" id="moveFilesList" style="max-height: 180px;">
                        <div class="file-list-empty">
                            <i class="fas fa-file"></i>
                            <p>No files found</p>
                        </div>
                    </div>
                </div>
                
                <!-- Destination Selection -->
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-folder" style="color: #10b981;"></i> Destination folder
                    </label>
                    <button class="folder-select-btn" id="moveFilesDestBtn" onclick="selectMoveFilesDestination()" disabled>
                        <i class="fas fa-folder"></i>
                        <span id="moveFilesDestName">Select files first</span>
                    </button>
                </div>
                
                <!-- Move Preview -->
                <div id="moveFilesPreview" style="display: none; margin-top: 1rem; padding: 0.75rem; background: var(--bg-tertiary); border-radius: 8px; font-size: 0.85rem; border: 1px solid var(--border-color);">
                    <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                        <span style="color: #8b5cf6;"><i class="fas fa-file"></i> <span id="previewFilesCount">0</span> file(s)</span>
                        <i class="fas fa-arrow-right" style="color: var(--accent-primary);"></i>
                        <span style="color: #10b981;"><i class="fas fa-folder"></i> <span id="previewFilesDestFolder">-</span></span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('moveFilesModal')">Cancel</button>
                <button class="btn btn-primary" id="confirmMoveFilesBtn" onclick="confirmMoveFiles()" disabled style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                    <i class="fas fa-file-export"></i> Move Files
                </button>
            </div>
        </div>
    </div>
    
    <!-- Move Folder Modal -->
    <div class="modal-overlay" id="moveFolderModal">
        <div class="modal" style="max-width: 550px;">
            <div class="modal-header">
                <h3><i class="fas fa-exchange-alt" style="color: #3b82f6;"></i> Move Folder</h3>
                <button class="modal-close" onclick="closeModal('moveFolderModal')">&times;</button>
            </div>
            <div class="modal-body">
                <i class="fas fa-exchange-alt file-modal-icon" style="color: #3b82f6;"></i>
                <p class="file-modal-message">Select source folder, then destination</p>
                
                <!-- Source Selection -->
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-folder-open" style="color: #f59e0b;"></i> Source (folder to move)
                    </label>
                    <button class="folder-select-btn" id="moveSourceBtn" onclick="selectMoveSource()">
                        <i class="fas fa-folder-open"></i>
                        <span id="moveSourceName">Click to select source folder</span>
                    </button>
                </div>
                
                <!-- Folder to Move Selection -->
                <div id="moveFolderSelectContainer" style="display: none; margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-folder" style="color: #8b5cf6;"></i> Select folder to move
                    </label>
                    <div class="file-list-container" id="moveFolderList" style="max-height: 150px;">
                        <div class="file-list-empty">
                            <i class="fas fa-folder-open"></i>
                            <p>No subfolders found</p>
                        </div>
                    </div>
                </div>
                
                <!-- Destination Selection -->
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-folder" style="color: #10b981;"></i> Destination (where to move)
                    </label>
                    <button class="folder-select-btn" id="moveDestBtn" onclick="selectMoveDestination()" disabled>
                        <i class="fas fa-folder"></i>
                        <span id="moveDestName">Select source first</span>
                    </button>
                </div>
                
                <!-- Move Preview -->
                <div id="movePreview" style="display: none; margin-top: 1rem; padding: 0.75rem; background: var(--bg-tertiary); border-radius: 8px; font-size: 0.85rem; border: 1px solid var(--border-color);">
                    <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                        <span style="color: #f59e0b;"><i class="fas fa-folder"></i> <span id="previewSourceFolder">-</span></span>
                        <i class="fas fa-arrow-right" style="color: var(--accent-primary);"></i>
                        <span style="color: #10b981;"><i class="fas fa-folder"></i> <span id="previewDestFolder">-</span></span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('moveFolderModal')">Cancel</button>
                <button class="btn btn-primary" id="confirmMoveBtn" onclick="confirmMoveFolder()" disabled>
                    <i class="fas fa-exchange-alt"></i> Move Folder
                </button>
            </div>
        </div>
    </div>
    
    <!-- Delete Folder Modal -->
    <div class="modal-overlay" id="deleteFolderModal">
        <div class="modal">
            <div class="modal-header">
                <h3><i class="fas fa-folder-minus" style="color: #ef4444;"></i> Delete Folder <span class="file-count-badge" id="deleteFolderCountBadge"><i class="fas fa-folder"></i> 0</span></h3>
                <button class="modal-close" onclick="closeModal('deleteFolderModal')">&times;</button>
            </div>
            <div class="modal-body">
                <i class="fas fa-folder-minus file-modal-icon" style="color: #ef4444;"></i>
                <p class="file-modal-message">Select the parent folder, then choose folder(s) to delete</p>
                
                <button class="folder-select-btn" id="deleteFolderTargetBtn" onclick="selectParentForFolderDelete()">
                    <i class="fas fa-folder-open"></i>
                    <span id="deleteFolderTargetName">Click to select parent folder</span>
                </button>
                
                <div class="file-list-container" id="deleteFolderList">
                    <div class="file-list-empty">
                        <i class="fas fa-folder-open"></i>
                        <p>Select a parent folder to see subfolders</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('deleteFolderModal')">Cancel</button>
                <button class="btn btn-danger" id="confirmDeleteFolderBtn" onclick="confirmDeleteFolders()" disabled>
                    <i class="fas fa-trash-alt"></i> Delete Selected Folder(s)
                </button>
            </div>
        </div>
    </div>
    
    <!-- Delete Files Modal -->
    <div class="modal-overlay" id="deleteFilesModal">
        <div class="modal">
            <div class="modal-header">
                <h3><i class="fas fa-trash-alt" style="color: #ef4444;"></i> Delete Files <span class="file-count-badge" id="deleteCountBadge"><i class="fas fa-file"></i> 0</span></h3>
                <button class="modal-close" onclick="closeModal('deleteFilesModal')">&times;</button>
            </div>
            <div class="modal-body">
                <i class="fas fa-trash-alt file-modal-icon delete"></i>
                <p class="file-modal-message">Select files to delete (multiple selection allowed)</p>
                
                <button class="folder-select-btn" id="deleteFolderBtn" onclick="selectDeleteFolder()">
                    <i class="fas fa-folder-open"></i>
                    <span id="deleteFolderName">Click to select folder</span>
                </button>
                
                <div class="file-list-container" id="deleteFileList">
                    <div class="file-list-empty">
                        <i class="fas fa-folder-open"></i>
                        <p>Select a folder to see files</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('deleteFilesModal')">Cancel</button>
                <button class="btn btn-danger" id="confirmDeleteBtn" onclick="confirmDeleteFiles()" disabled>
                    <i class="fas fa-trash-alt"></i> Delete Selected
                </button>
            </div>
        </div>
    </div>
    
    <!-- Rename File Modal -->
    <div class="modal-overlay" id="renameFileModal">
        <div class="modal">
            <div class="modal-header">
                <h3><i class="fas fa-edit" style="color: #f59e0b;"></i> Rename File</h3>
                <button class="modal-close" onclick="closeModal('renameFileModal')">&times;</button>
            </div>
            <div class="modal-body">
                <i class="fas fa-edit file-modal-icon rename"></i>
                <p class="file-modal-message">Select a file to rename</p>
                
                <button class="folder-select-btn" id="renameFolderBtn" onclick="selectRenameFolder()">
                    <i class="fas fa-folder-open"></i>
                    <span id="renameFolderName">Click to select folder</span>
                </button>
                
                <div class="file-list-container" id="renameFileList">
                    <div class="file-list-empty">
                        <i class="fas fa-folder-open"></i>
                        <p>Select a folder to see files</p>
                    </div>
                </div>
                
                <div class="form-group" id="newNameGroup" style="display: none;">
                    <label for="renameNewName">New File Name</label>
                    <input type="text" id="renameNewName" placeholder="Enter new file name">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('renameFileModal')">Cancel</button>
                <button class="btn btn-warning" id="confirmRenameBtn" onclick="confirmRenameFile()" disabled>
                    <i class="fas fa-check"></i> Rename
                </button>
            </div>
        </div>
    </div>
    
    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>
    
    <!-- Custom Confirm Modal -->
    <div class="confirm-overlay" id="confirmModal">
        <div class="confirm-box">
            <div class="confirm-icon" id="confirmIcon">
                <i class="fas fa-trash-alt"></i>
            </div>
            <h3 class="confirm-title" id="confirmTitle">Delete All Files?</h3>
            <p class="confirm-message" id="confirmMessage">This will remove all files from the list AND their content from the editor.</p>
            <div class="confirm-details" id="confirmDetails"></div>
            <div class="confirm-buttons">
                <button type="button" class="confirm-btn confirm-cancel" onclick="closeConfirmModal(false)">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="button" class="confirm-btn confirm-delete" id="confirmDeleteBtn" onclick="closeConfirmModal(true)">
                    <i class="fas fa-trash-alt"></i> Delete
                </button>
            </div>
        </div>
    </div>
    
    <!-- Template Edit/Add Modal -->
    <div class="template-modal-overlay" id="templateModal">
        <div class="template-modal">
            <div class="template-modal-header">
                <h3 id="templateModalTitle">
                    <i class="fas fa-file-alt"></i>
                    <span>Add New Template</span>
                </h3>
                <button type="button" class="template-modal-close" onclick="closeTemplateModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="template-modal-body">
                <div class="template-form-group">
                    <label for="templateNameInput">
                        <i class="fas fa-tag"></i> Template Name
                    </label>
                    <input type="text" id="templateNameInput" placeholder="Enter template name...">
                </div>
                <div class="template-form-group">
                    <label for="templateContentInput">
                        <i class="fas fa-align-left"></i> Template Content
                    </label>
                    <textarea id="templateContentInput" rows="10" placeholder="Enter your prompt content here..."></textarea>
                </div>
                <input type="hidden" id="templateEditId" value="">
            </div>
            <div class="template-modal-footer">
                <button type="button" class="template-btn cancel" onclick="closeTemplateModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="button" class="template-btn save" onclick="saveTemplate()">
                    <i class="fas fa-save"></i> <span id="templateSaveText">Add Template</span>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Template Preview Modal -->
    <div class="template-modal-overlay" id="templatePreviewModal">
        <div class="template-modal template-preview-modal">
            <div class="template-modal-header">
                <h3 id="templatePreviewTitle">
                    <i class="fas fa-eye"></i>
                    <span>Template Preview</span>
                </h3>
                <button type="button" class="template-modal-close" onclick="closeTemplatePreview()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="template-modal-body">
                <div class="template-preview-name" id="previewName"></div>
                <div class="template-preview-content" id="previewContent"></div>
            </div>
            <div class="template-modal-footer">
                <button type="button" class="template-btn secondary" onclick="copyTemplateContent()">
                    <i class="fas fa-copy"></i> Copy
                </button>
                <button type="button" class="template-btn warning" onclick="pullTemplateContent()">
                    <i class="fas fa-arrow-down"></i> Pull
                </button>
                <button type="button" class="template-btn edit" id="previewEditBtn">
                    <i class="fas fa-edit"></i> Edit
                </button>
                <button type="button" class="template-btn save" id="previewUseBtn">
                    <i class="fas fa-plus"></i> Use Template
                </button>
            </div>
        </div>
    </div>
    
    <!-- Saved Prompt Preview Modal -->
    <div class="template-modal-overlay" id="savedPreviewModal">
        <div class="template-modal saved-preview-modal">
            <div class="template-modal-header">
                <h3>
                    <i class="fas fa-bookmark"></i>
                    <span>Saved Prompt Preview</span>
                </h3>
                <button type="button" class="template-modal-close" onclick="closeSavedPreview()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="template-modal-body">
                <div class="saved-preview-name" id="savedPreviewName"></div>
                <div class="saved-preview-date" id="savedPreviewDate"></div>
                <div class="saved-preview-content" id="savedPreviewContent"></div>
            </div>
            <div class="template-modal-footer">
                <button type="button" class="template-btn secondary" onclick="copySavedContent()">
                    <i class="fas fa-copy"></i> Copy
                </button>
                <button type="button" class="template-btn warning" onclick="pullSavedContent()">
                    <i class="fas fa-arrow-down"></i> Pull
                </button>
                <button type="button" class="template-btn edit" id="savedPreviewEditBtn">
                    <i class="fas fa-edit"></i> Edit
                </button>
                <button type="button" class="template-btn save" id="savedPreviewUseBtn">
                    <i class="fas fa-plus"></i> Use Prompt
                </button>
            </div>
        </div>
    </div>
    
    <script>
        // Prompt Templates - loaded from database
        let promptTemplates = [];
        
        // Active prompts tracking (for templates)
        let activePrompts = new Set();
        
        // Current template being previewed
        let currentPreviewTemplate = null;
        
        // Saved Prompts - loaded from database
        let savedPromptsList = [];
        
        // Active saved prompts tracking
        let activeSavedPrompts = new Set();
        
        // ============================================
        // AI PROMPT DICTIONARY - API INTEGRATION
        // ============================================
        // Uses PHP proxy to bypass CORS restrictions
        
        // Dictionary State - limit from localStorage (default 30)
        const DICT_DEFAULT_LIMIT = 30;
        const dictState = {
            currentPage: 1,
            totalPages: 1,
            totalItems: 0,
            search: '',
            groupId: null,
            groupName: 'All',
            items: [],
            groups: [],
            isLoading: false,
            limit: parseInt(localStorage.getItem('dictLimit')) || DICT_DEFAULT_LIMIT,
            debounceTimer: null
        };
        
        // Initialize limit selector from localStorage
        function dictInitLimitSelector() {
            const select = document.getElementById('dictLimitSelect');
            if (select) {
                select.value = dictState.limit.toString();
            }
        }
        
        // Change limit and save to localStorage
        async function dictChangeLimit(value) {
            const newLimit = parseInt(value) || DICT_DEFAULT_LIMIT;
            dictState.limit = newLimit;
            dictState.currentPage = 1;
            localStorage.setItem('dictLimit', newLimit.toString());
            await dictLoadPrompts();
        }
        
        // Reset limit to default (30)
        async function dictResetLimit() {
            const select = document.getElementById('dictLimitSelect');
            dictState.limit = DICT_DEFAULT_LIMIT;
            dictState.currentPage = 1;
            localStorage.setItem('dictLimit', DICT_DEFAULT_LIMIT.toString());
            if (select) {
                select.value = DICT_DEFAULT_LIMIT.toString();
            }
            await dictLoadPrompts();
            showToast('Limit reset to ' + DICT_DEFAULT_LIMIT, 'info');
        }
        
        // Helper: Make API request DIRECTLY to frouty.com (bypassing PHP proxy)
        const DICT_API_BASE = 'https://frouty.com/api/search-api.php';
        
        async function dictApiRequest(params) {
            // Build URL with query parameters
            const url = new URL(DICT_API_BASE);
            
            // Map our params to API params
            if (params.api_action === 'groups') {
                url.searchParams.append('action', 'groups');
            } else if (params.api_action === 'search' && params.query) {
                url.searchParams.append('action', 'search');
                url.searchParams.append('q', params.query);
            } else {
                url.searchParams.append('action', 'list');
            }
            
            // Add pagination
            if (params.page) url.searchParams.append('page', params.page);
            if (params.limit) url.searchParams.append('limit', params.limit);
            if (params.group_id) url.searchParams.append('group_id', params.group_id);
            
            try {
                const response = await fetch(url.toString(), {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                
                const data = await response.json();
                
                // Wrap in success format if API returns raw data
                if (data.success === undefined) {
                    return { success: true, data: data };
                }
                return data;
            } catch (err) {
                console.error('Dictionary API error:', err);
                return { success: false, message: err.message };
            }
        }
        
        // Initialize Dictionary on page load
        document.addEventListener('DOMContentLoaded', () => {
            dictInit();
        });
        
        // Initialize dictionary
        async function dictInit() {
            dictInitLimitSelector();
            await dictLoadGroups();
            await dictLoadPrompts();
            dictSetupSearchListener();
        }
        
        // Load groups for filter
        async function dictLoadGroups() {
            try {
                const data = await dictApiRequest({ api_action: 'groups' });
                
                if (data.success && data.data && data.data.items) {
                    dictState.groups = data.data.items;
                    dictRenderGroups();
                }
            } catch (err) {
                console.error('Failed to load groups:', err);
            }
        }
        
        // Render group filter slider
        function dictRenderGroups() {
            const slider = document.getElementById('dictSliderTrack');
            if (!slider) return;
            
            let html = `
                <div class="slider-option ${dictState.groupId === null ? 'active' : ''}" 
                     data-group-id="all" 
                     data-group-name="All"
                     onclick="dictSelectGroup(null, 'All')">
                    <span>All</span>
                </div>
            `;
            
            dictState.groups.forEach(group => {
                const isActive = dictState.groupId === group.id;
                html += `
                    <div class="slider-option ${isActive ? 'active' : ''}" 
                         data-group-id="${group.id}" 
                         data-group-name="${dictEscapeHtml(group.title)}"
                         onclick="dictSelectGroup(${group.id}, '${dictEscapeHtml(group.title)}')">
                        <span>${dictEscapeHtml(group.title)}</span>
                    </div>
                `;
            });
            
            slider.innerHTML = html;
        }
        
        // Select a group filter
        async function dictSelectGroup(groupId, groupName) {
            dictState.groupId = groupId;
            dictState.groupName = groupName;
            dictState.currentPage = 1;
            
            // Update UI
            document.getElementById('dictActiveGroupName').textContent = groupName;
            
            // Update active state on buttons
            document.querySelectorAll('#dictSliderTrack .slider-option').forEach(opt => {
                const optGroupId = opt.dataset.groupId === 'all' ? null : parseInt(opt.dataset.groupId);
                opt.classList.toggle('active', optGroupId === groupId);
            });
            
            await dictLoadPrompts();
        }
        
        // Reset group filter
        async function dictResetGroupFilter() {
            await dictSelectGroup(null, 'All');
        }
        
        // Refresh from API (reload groups and prompts)
        async function dictRefreshAPI() {
            const btn = document.querySelector('.refresh-btn');
            if (btn) {
                btn.classList.add('spinning');
            }
            
            try {
                await dictLoadGroups();
                await dictLoadPrompts();
                showToast('✅ Dictionary refreshed from API', 'success');
            } catch (err) {
                showToast('❌ Failed to refresh', 'error');
            } finally {
                if (btn) {
                    btn.classList.remove('spinning');
                }
            }
        }
        
        // Setup search input listener with debounce
        function dictSetupSearchListener() {
            const searchInput = document.getElementById('dictSearchInput');
            const clearBtn = document.getElementById('dictSearchClear');
            
            if (!searchInput) return;
            
            searchInput.addEventListener('input', (e) => {
                const query = e.target.value.trim();
                
                // Show/hide clear button
                clearBtn.classList.toggle('show', query.length > 0);
                
                // Debounce search
                if (dictState.debounceTimer) {
                    clearTimeout(dictState.debounceTimer);
                }
                
                if (query.length >= 2 || query.length === 0) {
                    dictState.debounceTimer = setTimeout(() => {
                        dictState.search = query;
                        dictState.currentPage = 1;
                        dictLoadPrompts();
                    }, 400);
                }
            });
        }
        
        // Clear search
        async function dictClearSearch() {
            const searchInput = document.getElementById('dictSearchInput');
            const clearBtn = document.getElementById('dictSearchClear');
            
            searchInput.value = '';
            clearBtn.classList.remove('show');
            dictState.search = '';
            dictState.currentPage = 1;
            
            await dictLoadPrompts();
            searchInput.focus();
        }
        
        // Load prompts from API
        async function dictLoadPrompts() {
            if (dictState.isLoading) return;
            
            dictState.isLoading = true;
            dictShowLoading(true);
            dictShowError(false);
            
            try {
                const params = {
                    api_action: dictState.search ? 'search' : 'list',
                    query: dictState.search || '',
                    page: dictState.currentPage,
                    limit: dictState.limit,
                    group_id: dictState.groupId || ''
                };
                
                const data = await dictApiRequest(params);
                
                if (data.success && data.data) {
                    dictState.items = data.data.items || [];
                    
                    if (data.data.pagination) {
                        dictState.currentPage = data.data.pagination.current_page || 1;
                        dictState.totalPages = data.data.pagination.total_pages || 1;
                        dictState.totalItems = data.data.pagination.total_items || 0;
                    } else {
                        dictState.totalItems = dictState.items.length;
                        dictState.totalPages = 1;
                    }
                    
                    dictRenderPrompts();
                    dictUpdatePagination();
                    dictUpdateResultsInfo();
                } else {
                    throw new Error(data.message || 'Failed to load prompts');
                }
            } catch (err) {
                console.error('Dictionary load error:', err);
                dictShowError(true, err.message);
            } finally {
                dictState.isLoading = false;
                dictShowLoading(false);
            }
        }
        
        // Cache for storing HTML code (to avoid putting it in onclick attributes)
        const dictCodeCache = new Map();
        
        // Store field content to avoid massive inline attributes
        const dictFieldContentMap = new Map();
        
        // Render prompts list
        function dictRenderPrompts() {
            const listEl = document.getElementById('dictList');
            const emptyEl = document.getElementById('dictEmptyState');
            
            if (!listEl) return;
            
            if (dictState.items.length === 0) {
                listEl.classList.add('hidden');
                emptyEl.classList.add('show');
                return;
            }
            
            listEl.classList.remove('hidden');
            emptyEl.classList.remove('show');
            
            // Clear the code cache
            dictCodeCache.clear();
            
            let html = '';
            
            // Clear content map for fresh render
            dictFieldContentMap.clear();
            
            dictState.items.forEach(item => {
                const hasPreview = item.has_html || item.has_css || item.has_full_code;
                const groupBadge = item.group_title 
                    ? `<span class="dict-card-group-badge"><span class="dict-label">Group:</span> ${dictEscapeHtml(item.group_title)}</span>` 
                    : '';
                
                // Truncate phrase for display
                const phraseText = item.phrase || '';
                const truncatedPhrase = phraseText.length > 80 ? phraseText.substring(0, 80) + '...' : phraseText;
                
                // Store HTML code in cache (NOT in onclick attributes)
                const htmlCode = item.html_code || item.full_code || '';
                const hasHtml = htmlCode.length > 0;
                
                // Store content in map to avoid massive inline attributes
                dictFieldContentMap.set(`${item.id}-title`, item.title);
                dictFieldContentMap.set(`${item.id}-phrase`, phraseText);
                if (item.group_title) {
                    dictFieldContentMap.set(`${item.id}-group`, item.group_title);
                }
                if (hasHtml) {
                    dictFieldContentMap.set(`${item.id}-html`, htmlCode);
                }
                
                // Also store in cache for other functions
                dictCodeCache.set(`item-${item.id}`, {
                    title: item.title,
                    group_title: item.group_title || '',
                    phrase: phraseText,
                    html_code: htmlCode,
                    css_code: item.css_code || '',
                    full_code: item.full_code || ''
                });
                
                const checkboxes = `
                    <div class="dict-field-checks">
                        <label class="dict-field-check" data-id="${item.id}" data-field="title" onclick="dictToggleFieldById(event, '${item.id}', 'title')">
                            <input type="checkbox">
                            <span class="check-icon"><i class="fas fa-check"></i></span>
                            <span class="check-label">Title</span>
                        </label>
                        ${item.group_title ? `
                        <label class="dict-field-check" data-id="${item.id}" data-field="group" onclick="dictToggleFieldById(event, '${item.id}', 'group')">
                            <input type="checkbox">
                            <span class="check-icon"><i class="fas fa-check"></i></span>
                            <span class="check-label">Group</span>
                        </label>
                        ` : ''}
                        <label class="dict-field-check" data-id="${item.id}" data-field="phrase" onclick="dictToggleFieldById(event, '${item.id}', 'phrase')">
                            <input type="checkbox">
                            <span class="check-icon"><i class="fas fa-check"></i></span>
                            <span class="check-label">Phrase</span>
                        </label>
                        ${hasHtml ? `
                        <label class="dict-field-check" data-id="${item.id}" data-field="html" onclick="dictToggleFieldById(event, '${item.id}', 'html')">
                            <input type="checkbox">
                            <span class="check-icon"><i class="fas fa-check"></i></span>
                            <span class="check-label">HTML</span>
                        </label>
                        ` : ''}
                    </div>
                `;
                
                html += `
                    <div class="dict-card" data-id="${item.id}">
                        <div class="dict-card-header">
                            <div class="dict-card-title-wrap">
                                <span class="dict-label">Title:</span>
                                <h3 class="dict-card-title" title="${dictEscapeHtml(item.title)}">${dictEscapeHtml(item.title)}</h3>
                            </div>
                            ${checkboxes}
                            ${groupBadge}
                        </div>
                        <div class="dict-card-phrase">
                            <span class="dict-label">Phrase:</span>
                            <p title="${dictEscapeHtml(phraseText)}">${dictEscapeHtml(truncatedPhrase)}</p>
                            <button type="button" class="dict-btn-copy" onclick="dictCopyPhraseById(this, ${item.id})" title="Copy">
                                <i class="fas fa-copy"></i>
                            </button>
                            ${hasPreview ? `<button type="button" class="dict-preview-btn" onclick="openDictPreviewInTab(${item.id})" title="Preview in new tab"><i class="fas fa-eye"></i><span>Preview</span></button>` : ''}
                        </div>
                    </div>
                `;
            });
            
            listEl.innerHTML = html;
        }
        
        // Safe toggle field - gets content from cache instead of onclick attribute
        function dictToggleFieldSafe(event, itemId, field) {
            event.preventDefault();
            event.stopPropagation();
            
            const itemData = dictCodeCache.get(`item-${itemId}`);
            if (!itemData) {
                showToast('Item data not found', 'error');
                return;
            }
            
            let content = '';
            switch(field) {
                case 'title':
                    content = itemData.title;
                    break;
                case 'group':
                    content = itemData.group_title;
                    break;
                case 'phrase':
                    content = itemData.phrase;
                    break;
                case 'html':
                    content = itemData.html_code || itemData.full_code;
                    break;
                default:
                    content = '';
            }
            
            // Call the original toggle function with the content
            const key = `${itemId}-${field}`;
            const labelEl = event.currentTarget;
            const checkbox = labelEl.querySelector('input[type="checkbox"]');
            const editor = document.getElementById('promptEditor');
            
            if (!editor) {
                console.error('Prompt editor not found');
                return;
            }
            
            const isActive = dictActiveFields.has(key);
            
            if (isActive) {
                // Remove - uncheck
                dictActiveFields.delete(key);
                labelEl.classList.remove('active');
                if (checkbox) checkbox.checked = false;
                
                // Rebuild editor content without this field
                dictRebuildEditorFromFields();
                showToast(`${field.charAt(0).toUpperCase() + field.slice(1)} removed`, 'info');
            } else {
                // Add - check
                dictActiveFields.set(key, content);
                labelEl.classList.add('active');
                if (checkbox) checkbox.checked = true;
                
                // Append to editor
                const currentValue = editor.value.trim();
                if (currentValue) {
                    editor.value = currentValue + '\n\n' + content;
                } else {
                    editor.value = content;
                }
                
                showToast(`${field.charAt(0).toUpperCase() + field.slice(1)} added`, 'success');
            }
            
            updateCounts();
            if (typeof recordHistoryState === 'function') {
                recordHistoryState(true);
            }
        }
        
        // Copy phrase by ID from cache
        async function dictCopyPhraseById(button, itemId) {
            const itemData = dictCodeCache.get(`item-${itemId}`);
            if (!itemData) {
                showToast('Item not found', 'error');
                return;
            }
            
            try {
                await navigator.clipboard.writeText(itemData.phrase);
                button.classList.add('copied');
                button.innerHTML = '<i class="fas fa-check"></i>';
                
                setTimeout(() => {
                    button.classList.remove('copied');
                    button.innerHTML = '<i class="fas fa-copy"></i>';
                }, 1500);
            } catch (err) {
                console.error('Copy failed:', err);
            }
        }
        
        // Open code in new tab
        function dictOpenCodeInNewTab(itemId) {
            const itemData = dictCodeCache.get(`item-${itemId}`);
            if (!itemData) {
                showToast('Item not found', 'error');
                return;
            }
            
            let htmlContent = '';
            
            if (itemData.full_code) {
                htmlContent = itemData.full_code;
            } else if (itemData.html_code || itemData.css_code) {
                htmlContent = `<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>${dictEscapeHtml(itemData.title)} - Preview</title>
    <style>
        body { margin: 0; padding: 16px; font-family: system-ui, -apple-system, sans-serif; }
        ${itemData.css_code || ''}
    </style>
</head>
<body>
${itemData.html_code || ''}
</body>
</html>`;
            } else {
                showToast('No code available to preview', 'info');
                return;
            }
            
            // Create blob and open in new tab
            const blob = new Blob([htmlContent], { type: 'text/html' });
            const url = URL.createObjectURL(blob);
            
            const newTab = window.open(url, '_blank');
            
            // Clean up the URL after a delay
            setTimeout(() => {
                URL.revokeObjectURL(url);
            }, 1000);
            
            if (!newTab) {
                showToast('Popup blocked! Please allow popups for this site.', 'error');
            }
        }
        
        // Open preview in new browser tab
        function openDictPreviewInTab(itemId) {
            const item = dictState.items.find(i => i.id === itemId);
            if (!item) return;
            
            let htmlContent = '';
            
            if (item.full_code) {
                htmlContent = item.full_code;
            } else if (item.html_code || item.css_code) {
                htmlContent = `<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>${item.title || 'Preview'}</title>
<style>
* { box-sizing: border-box; }
body { margin: 0; padding: 16px; font-family: system-ui, sans-serif; font-size: 14px; }
${item.css_code || ''}
</style>
</head>
<body>
${item.html_code || ''}
</body>
</html>`;
            } else {
                showToast('No preview content available', 'info');
                return;
            }
            
            // Open in new tab
            const newTab = window.open('', '_blank');
            if (newTab) {
                newTab.document.open();
                newTab.document.write(htmlContent);
                newTab.document.close();
            } else {
                showToast('Please allow popups to view preview', 'warning');
            }
        }
        
        // Copy phrase to clipboard
        async function dictCopyPhrase(button, text) {
            try {
                await navigator.clipboard.writeText(text);
                button.classList.add('copied');
                button.innerHTML = '<i class="fas fa-check"></i>';
                
                setTimeout(() => {
                    button.classList.remove('copied');
                    button.innerHTML = '<i class="fas fa-copy"></i>';
                }, 1500);
            } catch (err) {
                console.error('Copy failed:', err);
            }
        }
        
        // Track active dictionary field selections
        const dictActiveFields = new Map(); // key: "itemId-field", value: content
        
        // Toggle dictionary field by ID - retrieve from map
        function dictToggleFieldById(event, itemId, field) {
            event.preventDefault();
            event.stopPropagation();
            
            const key = `${itemId}-${field}`;
            const content = dictFieldContentMap.get(key);
            
            if (!content) {
                console.error('Content not found for', key);
                return;
            }
            
            dictToggleField(event, itemId, field, content);
        }
        
        // Toggle dictionary field - append/remove from editor
        function dictToggleField(event, itemId, field, content) {
            event.preventDefault();
            event.stopPropagation();
            
            const key = `${itemId}-${field}`;
            const labelEl = event.currentTarget;
            const checkbox = labelEl.querySelector('input[type="checkbox"]');
            const editor = document.getElementById('promptEditor');
            
            if (!editor) {
                console.error('Prompt editor not found');
                return;
            }
            
            const isActive = dictActiveFields.has(key);
            
            if (isActive) {
                // Remove - uncheck
                dictActiveFields.delete(key);
                labelEl.classList.remove('active');
                if (checkbox) checkbox.checked = false;
                
                // Rebuild editor content without this field
                dictRebuildEditorFromFields();
                showToast(`${field.charAt(0).toUpperCase() + field.slice(1)} removed`, 'info');
            } else {
                // Add - check
                dictActiveFields.set(key, content);
                labelEl.classList.add('active');
                if (checkbox) checkbox.checked = true;
                
                // Append to editor
                const currentValue = editor.value.trim();
                if (currentValue) {
                    editor.value = currentValue + '\n\n' + content;
                } else {
                    editor.value = content;
                }
                
                showToast(`${field.charAt(0).toUpperCase() + field.slice(1)} added`, 'success');
            }
            
            updateCounts();
            if (typeof recordHistoryState === 'function') {
                recordHistoryState(true);
            }
        }
        
        // Rebuild editor from all active fields (templates, saved, dictionary)
        function dictRebuildEditorFromFields() {
            const editor = document.getElementById('promptEditor');
            const contents = [];
            
            // First add active template prompts
            promptTemplates.forEach(prompt => {
                if (activePrompts.has(prompt.id)) {
                    contents.push(prompt.content);
                }
            });
            
            // Then add active saved prompts
            savedPromptsList.forEach(prompt => {
                if (activeSavedPrompts.has(prompt.id)) {
                    contents.push(prompt.content);
                }
            });
            
            // Then add active dictionary fields
            dictActiveFields.forEach((content, key) => {
                contents.push(content);
            });
            
            editor.value = contents.join('\n\n');
            updateCounts();
        }
        
        // Update pagination UI
        function dictUpdatePagination() {
            const paginationEl = document.getElementById('dictPagination');
            const prevBtn = document.getElementById('dictBtnPrev');
            const nextBtn = document.getElementById('dictBtnNext');
            const pageInfo = document.getElementById('dict-page-info');
            const itemsInfo = document.getElementById('dict-items-info');
            
            if (!paginationEl) return;
            
            // Hide pagination if only one page
            paginationEl.classList.toggle('hidden', dictState.totalPages <= 1);
            
            // Update buttons
            prevBtn.disabled = dictState.currentPage <= 1;
            nextBtn.disabled = dictState.currentPage >= dictState.totalPages;
            
            // Update info
            pageInfo.textContent = `Page ${dictState.currentPage} of ${dictState.totalPages}`;
            itemsInfo.textContent = `(${dictState.totalItems} items)`;
        }
        
        // Update results info
        function dictUpdateResultsInfo() {
            const countEl = document.getElementById('dictResultsCount');
            if (countEl) {
                countEl.textContent = dictState.items.length;
            }
        }
        
        // Navigate to previous page
        async function dictPrevPage() {
            if (dictState.currentPage > 1) {
                dictState.currentPage--;
                await dictLoadPrompts();
                dictScrollToTop();
            }
        }
        
        // Navigate to next page
        async function dictNextPage() {
            if (dictState.currentPage < dictState.totalPages) {
                dictState.currentPage++;
                await dictLoadPrompts();
                dictScrollToTop();
            }
        }
        
        // Scroll to top of dictionary wrapper
        function dictScrollToTop() {
            const wrapper = document.getElementById('dictionaryWrapper');
            if (wrapper) {
                wrapper.scrollTop = 0;
            }
        }
        
        // Show/hide loading state
        function dictShowLoading(show) {
            const loadingEl = document.getElementById('dictLoading');
            const listEl = document.getElementById('dictList');
            const searchLoading = document.getElementById('dictSearchLoading');
            
            if (loadingEl) {
                loadingEl.classList.toggle('show', show);
            }
            if (listEl && show) {
                listEl.classList.add('hidden');
            }
            if (searchLoading) {
                searchLoading.classList.toggle('show', show);
            }
        }
        
        // Show/hide error state
        function dictShowError(show, message = 'Failed to load prompts') {
            const errorEl = document.getElementById('dictErrorState');
            const messageEl = document.getElementById('dictErrorMessage');
            const listEl = document.getElementById('dictList');
            
            if (errorEl) {
                errorEl.classList.toggle('show', show);
            }
            if (messageEl && message) {
                messageEl.textContent = message;
            }
            if (listEl && show) {
                listEl.classList.add('hidden');
            }
        }
        
        // Escape HTML for safe display
        function dictEscapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Escape text for JavaScript string
        function dictEscapeJs(text) {
            if (!text) return '';
            return text.replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/"/g, '\\"').replace(/\n/g, '\\n').replace(/\r/g, '\\r');
        }
        
        // Current saved prompt being previewed
        let currentPreviewSaved = null;
        
        // Distribution state
        let distributionState = {
            value: 1,
            enabled: false,
            startMarker: '═══ WORK DISTRIBUTION ═══',
            endMarker: '═══════════════════════════'
        };

        // ============================================
        // HISTORY SYSTEM (Undo/Redo) - 10 Steps
        // ============================================
        const historySystem = {
            states: [],           // Array of editor states
            currentIndex: -1,     // Current position in history
            maxStates: 10,        // Maximum number of states to keep
            isNavigating: false,  // Flag to prevent recording while navigating
            debounceTimer: null,  // Timer for debouncing input
            debounceDelay: 500,   // Delay before recording state (ms)
            lastRecordedValue: '' // Last value that was recorded
        };

        // Initialize history with empty state
        function initHistory() {
            const editor = document.getElementById('promptEditor');
            historySystem.states = [editor.value];
            historySystem.currentIndex = 0;
            historySystem.lastRecordedValue = editor.value;
            updateHistoryUI();
        }

        // Record current state to history
        function recordHistoryState(force = false) {
            if (historySystem.isNavigating) return;
            
            const editor = document.getElementById('promptEditor');
            const currentValue = editor.value;
            
            // Don't record if value hasn't changed
            if (currentValue === historySystem.lastRecordedValue && !force) return;
            
            // Remove any states after current index (when recording after undo)
            if (historySystem.currentIndex < historySystem.states.length - 1) {
                historySystem.states = historySystem.states.slice(0, historySystem.currentIndex + 1);
            }
            
            // Add new state
            historySystem.states.push(currentValue);
            historySystem.lastRecordedValue = currentValue;
            
            // Keep only last maxStates
            if (historySystem.states.length > historySystem.maxStates) {
                historySystem.states.shift();
            } else {
                historySystem.currentIndex++;
            }
            
            // Update UI
            updateHistoryUI();
            
            // Pulse animation
            const btnUndo = document.getElementById('btnUndo');
            btnUndo.classList.add('pulse');
            setTimeout(() => btnUndo.classList.remove('pulse'), 500);
        }

        // Debounced version for input events
        function recordHistoryDebounced() {
            if (historySystem.debounceTimer) {
                clearTimeout(historySystem.debounceTimer);
            }
            
            historySystem.debounceTimer = setTimeout(() => {
                recordHistoryState();
            }, historySystem.debounceDelay);
        }

        // Undo - go back in history
        function historyUndo() {
            if (historySystem.currentIndex <= 0) return;
            
            historySystem.isNavigating = true;
            historySystem.currentIndex--;
            
            const editor = document.getElementById('promptEditor');
            editor.value = historySystem.states[historySystem.currentIndex];
            historySystem.lastRecordedValue = editor.value;
            
            updateHistoryUI();
            updateCounts();
            
            // Visual feedback
            showToast(`↩️ Undo (Step ${historySystem.currentIndex + 1}/${historySystem.states.length})`, 'info');
            
            setTimeout(() => {
                historySystem.isNavigating = false;
            }, 100);
        }

        // Redo - go forward in history
        function historyRedo() {
            if (historySystem.currentIndex >= historySystem.states.length - 1) return;
            
            historySystem.isNavigating = true;
            historySystem.currentIndex++;
            
            const editor = document.getElementById('promptEditor');
            editor.value = historySystem.states[historySystem.currentIndex];
            historySystem.lastRecordedValue = editor.value;
            
            updateHistoryUI();
            updateCounts();
            
            // Visual feedback
            showToast(`↪️ Redo (Step ${historySystem.currentIndex + 1}/${historySystem.states.length})`, 'info');
            
            setTimeout(() => {
                historySystem.isNavigating = false;
            }, 100);
        }

        // Update history navigation UI
        function updateHistoryUI() {
            const btnUndo = document.getElementById('btnUndo');
            const btnRedo = document.getElementById('btnRedo');
            const undoCount = document.getElementById('undoCount');
            const redoCount = document.getElementById('redoCount');
            const historyPosition = document.getElementById('historyPosition');
            
            const canUndo = historySystem.currentIndex > 0;
            const canRedo = historySystem.currentIndex < historySystem.states.length - 1;
            
            const undoSteps = historySystem.currentIndex;
            const redoSteps = historySystem.states.length - 1 - historySystem.currentIndex;
            
            // Update buttons
            btnUndo.disabled = !canUndo;
            btnRedo.disabled = !canRedo;
            
            // Update badges
            undoCount.textContent = undoSteps;
            redoCount.textContent = redoSteps;
            
            // Update position indicator
            historyPosition.textContent = `${historySystem.currentIndex + 1}/${historySystem.states.length}`;
            
            // Update tooltips
            btnUndo.title = canUndo ? `Undo (${undoSteps} step${undoSteps !== 1 ? 's' : ''} back) - Ctrl+Z` : 'Nothing to undo';
            btnRedo.title = canRedo ? `Redo (${redoSteps} step${redoSteps !== 1 ? 's' : ''} forward) - Ctrl+Y` : 'Nothing to redo';
        }

        // Clear history (when editor is cleared)
        function clearHistory() {
            historySystem.states = [''];
            historySystem.currentIndex = 0;
            historySystem.lastRecordedValue = '';
            updateHistoryUI();
        }

        // Reset history to zeros (X button)
        function resetHistory() {
            const editor = document.getElementById('promptEditor');
            historySystem.states = [editor.value]; // Keep current state as starting point
            historySystem.currentIndex = 0;
            historySystem.lastRecordedValue = editor.value;
            updateHistoryUI();
            
            // Visual feedback
            const clearBtn = document.getElementById('btnClearHistory');
            clearBtn.classList.add('pulse');
            setTimeout(() => clearBtn.classList.remove('pulse'), 500);
            
            showToast('🗑️ History cleared', 'info');
        }

        // Keyboard shortcuts for undo/redo
        function setupHistoryKeyboardShortcuts() {
            document.addEventListener('keydown', (e) => {
                // Only work when editor is focused or no input is focused
                const activeElement = document.activeElement;
                const isEditorFocused = activeElement && activeElement.id === 'promptEditor';
                
                if (e.ctrlKey && e.key === 'z' && !e.shiftKey) {
                    if (isEditorFocused) {
                        e.preventDefault();
                        historyUndo();
                    }
                }
                
                if ((e.ctrlKey && e.key === 'y') || (e.ctrlKey && e.shiftKey && e.key === 'z')) {
                    if (isEditorFocused) {
                        e.preventDefault();
                        historyRedo();
                    }
                }
            });
        }

        // ============================================
        // DATABASE SELECTOR (Database Hub Integration)
        // ============================================
        // Connections are loaded from the central hub: report_prompt_databases table
        const DATABASE_HUB_API = 'report-prompt-databases.php?api=list';
        const HOSTINGER_CONNECTIONS_KEY = 'hostinger_connections'; // Fallback
        let selectedDatabaseConnection = null;

        // Load databases from Database Hub (report_prompt_databases table)
        async function loadHostingerDatabases() {
            const dropdown = document.getElementById('dbDropdown');
            const noConnections = document.getElementById('dbNoConnections');
            const dbWidget = document.getElementById('databaseSelector');
            const dbTools = dbWidget ? dbWidget.querySelector('.dash-db-tools') : null;
            
            // Show loading state
            dropdown.innerHTML = '<option value="">-- Loading... --</option>';
            
            try {
                // Fetch from Database Hub API
                const response = await fetch(DATABASE_HUB_API);
                const data = await response.json();
                
                // Clear existing options
                dropdown.innerHTML = '<option value="">-- Select Connection --</option>';
                
                if (data.success && data.connections && data.connections.length > 0) {
                    // Show controls, hide empty message
                    if (dbTools) dbTools.style.display = 'flex';
                    if (noConnections) noConnections.style.display = 'none';
                    
                    // Add database options from hub
                    data.connections.forEach(conn => {
                        const option = document.createElement('option');
                        option.value = conn.id;
                        option.textContent = `🌐 ${conn.name}`;
                        option.dataset.host = conn.host;
                        option.dataset.dbname = conn.dbName;
                        option.dataset.username = conn.username;
                        option.dataset.password = conn.password || '';
                        option.dataset.port = conn.port || '3306';
                        option.dataset.type = conn.type || 'shared';
                        dropdown.appendChild(option);
                    });
                    
                    console.log('✅ Loaded ' + data.connections.length + ' connections from Database Hub');
                } else {
                    // No connections in hub
                    if (dbTools) dbTools.style.display = 'none';
                    if (noConnections) noConnections.style.display = 'flex';
                }
            } catch (error) {
                console.error('❌ Failed to load from Database Hub:', error);
                
                // Fallback to localStorage
                const saved = localStorage.getItem(HOSTINGER_CONNECTIONS_KEY);
                const connections = saved ? JSON.parse(saved) : [];
                
                dropdown.innerHTML = '<option value="">-- Select Connection --</option>';
                
                if (connections.length === 0) {
                    if (dbTools) dbTools.style.display = 'none';
                    if (noConnections) noConnections.style.display = 'flex';
                    return;
                }
                
                if (dbTools) dbTools.style.display = 'flex';
                if (noConnections) noConnections.style.display = 'none';
                
                // Add database options from localStorage fallback
                connections.forEach(conn => {
                    const option = document.createElement('option');
                    option.value = conn.id;
                    option.textContent = `${conn.name}`;
                    option.dataset.host = conn.host;
                    option.dataset.dbname = conn.dbName;
                    option.dataset.username = conn.username;
                    option.dataset.password = conn.password || '';
                    option.dataset.port = conn.port || '3306';
                    option.dataset.type = conn.type || 'shared';
                    dropdown.appendChild(option);
                });
                
                console.log('⚠️ Using localStorage fallback');
            }
        }

        // Manual refresh database list with visual feedback
        async function refreshDatabaseList() {
            const refreshBtn = document.getElementById('dbRefreshBtn');
            const dropdown = document.getElementById('dbDropdown');
            const dbWidget = document.getElementById('databaseSelector');
            const dbTools = dbWidget ? dbWidget.querySelector('.dash-db-tools') : null;
            const noConnections = document.getElementById('dbNoConnections');
            
            // Prevent double-clicking
            if (refreshBtn.classList.contains('spinning')) return;
            
            // Add spinning animation
            refreshBtn.classList.add('spinning');
            
            // Store current selection
            const currentSelection = dropdown.value;
            
            try {
                // Force fresh fetch (bypass any cache)
                const response = await fetch(DATABASE_HUB_API + '&_t=' + Date.now());
                const data = await response.json();
                
                // Clear existing options
                dropdown.innerHTML = '<option value="">-- Select Connection --</option>';
                
                if (data.success && data.connections && data.connections.length > 0) {
                    if (dbTools) dbTools.style.display = 'flex';
                    if (noConnections) noConnections.style.display = 'none';
                    
                    // Add database options
                    data.connections.forEach(conn => {
                        const option = document.createElement('option');
                        option.value = conn.id;
                        option.textContent = `🌐 ${conn.name}`;
                        option.dataset.host = conn.host;
                        option.dataset.dbname = conn.dbName;
                        option.dataset.username = conn.username;
                        option.dataset.password = conn.password || '';
                        option.dataset.port = conn.port || '3306';
                        option.dataset.type = conn.type || 'shared';
                        dropdown.appendChild(option);
                    });
                    
                    // Restore previous selection if still exists
                    if (currentSelection) {
                        const stillExists = Array.from(dropdown.options).some(opt => opt.value === currentSelection);
                        if (stillExists) {
                            dropdown.value = currentSelection;
                        }
                    }
                    
                    showToast(`🔄 Refreshed! ${data.connections.length} database(s) loaded`, 'success');
                } else {
                    if (dbTools) dbTools.style.display = 'none';
                    if (noConnections) noConnections.style.display = 'flex';
                    showToast('📭 No databases found', 'info');
                }
                
            } catch (error) {
                console.error('Refresh error:', error);
                showToast('❌ Failed to refresh databases', 'error');
            } finally {
                // Remove spinning animation after a minimum delay for visual feedback
                setTimeout(() => {
                    refreshBtn.classList.remove('spinning');
                }, 500);
            }
        }

        // Development Dashboard LocalStorage
        const DASHBOARD_STORAGE_KEY = 'devDashboardSettings';
        
        // Save dashboard settings to localStorage
        function saveDashboardSettings() {
            const settings = {
                // Checkboxes
                dbMainCheckbox: document.getElementById('dbMainCheckbox')?.checked || false,
                dbCredentialsCheckbox: document.getElementById('dbCredentialsCheckbox')?.checked || false,
                dbLocalhostCheckbox: document.getElementById('dbLocalhostCheckbox')?.checked || false,
                
                // Database dropdown
                dbDropdown: document.getElementById('dbDropdown')?.value || ''
            };
            
            localStorage.setItem(DASHBOARD_STORAGE_KEY, JSON.stringify(settings));
        }
        
        // Load dashboard settings from localStorage
        function loadDashboardSettings() {
            const saved = localStorage.getItem(DASHBOARD_STORAGE_KEY);
            if (!saved) return;
            
            try {
                const settings = JSON.parse(saved);
                
                // Restore checkboxes
                const dbMainCheckbox = document.getElementById('dbMainCheckbox');
                if (dbMainCheckbox) dbMainCheckbox.checked = settings.dbMainCheckbox || false;
                
                const dbCredentialsCheckbox = document.getElementById('dbCredentialsCheckbox');
                if (dbCredentialsCheckbox) dbCredentialsCheckbox.checked = settings.dbCredentialsCheckbox || false;
                
                const dbLocalhostCheckbox = document.getElementById('dbLocalhostCheckbox');
                if (dbLocalhostCheckbox) dbLocalhostCheckbox.checked = settings.dbLocalhostCheckbox || false;
                
                // Restore database dropdown (after options are loaded)
                if (settings.dbDropdown) {
                    setTimeout(() => {
                        const dbDropdown = document.getElementById('dbDropdown');
                        if (dbDropdown) {
                            dbDropdown.value = settings.dbDropdown;
                        }
                    }, 1000);
                }
                
                console.log('✅ Dashboard settings loaded from localStorage');
            } catch (error) {
                console.error('Failed to load dashboard settings:', error);
            }
        }
        
        // Reset dashboard to default
        function resetDashboard() {
            if (!confirm('Reset all dashboard settings to default?')) return;
            
            // Clear localStorage
            localStorage.removeItem(DASHBOARD_STORAGE_KEY);
            
            // Reset checkboxes
            const dbMainCheckbox = document.getElementById('dbMainCheckbox');
            if (dbMainCheckbox) dbMainCheckbox.checked = false;
            
            const dbCredentialsCheckbox = document.getElementById('dbCredentialsCheckbox');
            if (dbCredentialsCheckbox) dbCredentialsCheckbox.checked = false;
            
            const dbLocalhostCheckbox = document.getElementById('dbLocalhostCheckbox');
            if (dbLocalhostCheckbox) dbLocalhostCheckbox.checked = false;
            
            // Reset dropdown
            const dbDropdown = document.getElementById('dbDropdown');
            if (dbDropdown) dbDropdown.value = '';
            
            // Reset dynamic items
            resetDynamicItems();
            
            showToast('🔄 Dashboard reset to default', 'info');
        }
        
        // ════════════════════════════════════════════════════════════════
        // DYNAMIC SECTIONS SYSTEM - Backend, Pages, Frontend
        // ════════════════════════════════════════════════════════════════
        
        // Store for dynamic items
        const dynamicItems = {
            backend: [],
            page: [],
            frontend: []
        };
        
        // Storage key for dynamic items
        const DYNAMIC_ITEMS_KEY = 'devDashboardDynamicItems';
        
        // Generate unique ID
        function generateItemId() {
            return 'item_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        }
        
        // Add new dynamic item
        function addDynamicItem(type, prefillName = '', prefillDesc = '') {
            const id = generateItemId();
            const item = {
                id: id,
                name: prefillName || '',
                prompt: prefillDesc || '',
                files: []
            };
            
            dynamicItems[type].push(item);
            renderDynamicItems(type);
            updateDynamicCount(type);
            saveDynamicItems();
            
            // Focus on the new item's name input only if not prefilled
            if (!prefillName) {
                setTimeout(() => {
                    const nameInput = document.querySelector(`#${type}ItemsGrid .dynamic-item[data-id="${id}"] .dynamic-item-name`);
                    if (nameInput) nameInput.focus();
                }, 50);
                
                showToast(`✅ New ${type} item added`, 'success');
            }
        }
        
        // Delete dynamic item
        function deleteDynamicItem(type, id) {
            const index = dynamicItems[type].findIndex(item => item.id === id);
            if (index !== -1) {
                dynamicItems[type].splice(index, 1);
                renderDynamicItems(type);
                updateDynamicCount(type);
                saveDynamicItems();
                showToast(`🗑️ ${type.charAt(0).toUpperCase() + type.slice(1)} item deleted`, 'info');
            }
        }
        
        // Update dynamic item
        function updateDynamicItem(type, id, field, value) {
            const item = dynamicItems[type].find(item => item.id === id);
            if (item) {
                item[field] = value;
                saveDynamicItems();
            }
        }
        
        // Handle file selection for dynamic item
        function handleDynamicItemFiles(type, id, input) {
            const item = dynamicItems[type].find(item => item.id === id);
            if (item && input.files.length > 0) {
                item.files = Array.from(input.files);
                renderDynamicItems(type);
                saveDynamicItems();
            }
        }
        
        // Render dynamic items for a section
        function renderDynamicItems(type) {
            const grid = document.getElementById(`${type}ItemsGrid`);
            const emptyState = document.getElementById(`${type}EmptyState`);
            
            if (!grid) return;
            
            // Clear existing items (except empty state)
            const existingItems = grid.querySelectorAll('.dynamic-item');
            existingItems.forEach(item => item.remove());
            
            if (dynamicItems[type].length === 0) {
                if (emptyState) emptyState.style.display = 'flex';
                return;
            }
            
            if (emptyState) emptyState.style.display = 'none';
            
            // Render items
            dynamicItems[type].forEach((item, index) => {
                const itemEl = createDynamicItemElement(type, item, index + 1);
                grid.appendChild(itemEl);
            });
        }
        
        // Create dynamic item element
        function createDynamicItemElement(type, item, number) {
            const div = document.createElement('div');
            div.className = `dynamic-item ${type}-item`;
            div.dataset.id = item.id;
            
            const fileCount = item.files ? item.files.length : 0;
            const hasFiles = fileCount > 0;
            
            div.innerHTML = `
                <div class="dynamic-item-header">
                    <span class="dynamic-item-number">#${number}</span>
                    <input type="text" 
                           class="dynamic-item-name" 
                           placeholder="${type.charAt(0).toUpperCase() + type.slice(1)} name..." 
                           value="${escapeHtml(item.name || '')}"
                           onchange="updateDynamicItem('${type}', '${item.id}', 'name', this.value)"
                           oninput="updateDynamicItem('${type}', '${item.id}', 'name', this.value)">
                    <div class="dynamic-item-actions">
                        <input type="file" 
                               class="dynamic-item-file-input" 
                               id="file_${item.id}" 
                               multiple 
                               accept="*/*"
                               onchange="handleDynamicItemFiles('${type}', '${item.id}', this)">
                        <button type="button" 
                                class="dynamic-item-btn file-btn ${hasFiles ? 'has-files' : ''}" 
                                onclick="document.getElementById('file_${item.id}').click()"
                                title="${hasFiles ? fileCount + ' file(s) selected' : 'Attach files'}">
                            <i class="fas fa-${hasFiles ? 'check-circle' : 'paperclip'}"></i>
                        </button>
                        <button type="button" 
                                class="dynamic-item-btn delete-btn" 
                                onclick="deleteDynamicItem('${type}', '${item.id}')"
                                title="Delete this item">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="dynamic-item-body">
                    <textarea class="dynamic-item-prompt" 
                              placeholder="Enter ${type} instructions or description..."
                              onchange="updateDynamicItem('${type}', '${item.id}', 'prompt', this.value)"
                              oninput="updateDynamicItem('${type}', '${item.id}', 'prompt', this.value)">${escapeHtml(item.prompt || '')}</textarea>
                    ${hasFiles ? `<div class="dynamic-item-files has-files"><i class="fas fa-paperclip"></i> ${fileCount} file${fileCount > 1 ? 's' : ''} attached</div>` : ''}
                </div>
            `;
            
            return div;
        }
        
        // Update dynamic count badge
        function updateDynamicCount(type) {
            const countEl = document.getElementById(`${type}Count`);
            if (countEl) {
                countEl.textContent = dynamicItems[type].length;
            }
        }
        
        // Save dynamic items to localStorage
        function saveDynamicItems() {
            // Convert files to file info (can't store actual File objects)
            const toSave = {
                backend: dynamicItems.backend.map(item => ({
                    ...item,
                    files: item.files ? item.files.map(f => ({ name: f.name, size: f.size, type: f.type })) : []
                })),
                page: dynamicItems.page.map(item => ({
                    ...item,
                    files: item.files ? item.files.map(f => ({ name: f.name, size: f.size, type: f.type })) : []
                })),
                frontend: dynamicItems.frontend.map(item => ({
                    ...item,
                    files: item.files ? item.files.map(f => ({ name: f.name, size: f.size, type: f.type })) : []
                }))
            };
            localStorage.setItem(DYNAMIC_ITEMS_KEY, JSON.stringify(toSave));
        }
        
        // Load dynamic items from localStorage
        function loadDynamicItems() {
            const saved = localStorage.getItem(DYNAMIC_ITEMS_KEY);
            if (!saved) return;
            
            try {
                const data = JSON.parse(saved);
                // Note: File objects can't be restored from localStorage, so files will be empty
                ['backend', 'page', 'frontend'].forEach(type => {
                    if (data[type]) {
                        dynamicItems[type] = data[type].map(item => ({
                            ...item,
                            files: [] // Files can't be restored
                        }));
                        renderDynamicItems(type);
                        updateDynamicCount(type);
                    }
                });
                console.log('✅ Dynamic items loaded from localStorage');
            } catch (error) {
                console.error('Failed to load dynamic items:', error);
            }
        }
        
        // Reset all dynamic items
        function resetDynamicItems() {
            ['backend', 'page', 'frontend'].forEach(type => {
                dynamicItems[type] = [];
                renderDynamicItems(type);
                updateDynamicCount(type);
            });
            localStorage.removeItem(DYNAMIC_ITEMS_KEY);
        }
        
        // Auto-save on changes
        function initDashboardAutoSave() {
            // Checkboxes
            ['dbMainCheckbox', 'dbCredentialsCheckbox', 'dbLocalhostCheckbox'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.addEventListener('change', saveDashboardSettings);
            });
            
            // Dropdown
            const dbDropdown = document.getElementById('dbDropdown');
            if (dbDropdown) dbDropdown.addEventListener('change', saveDashboardSettings);
        }

        // Handle file picker change
        document.addEventListener('DOMContentLoaded', function() {
            // Load saved settings
            loadDashboardSettings();
            
            // Load dynamic items
            loadDynamicItems();
            
            // Initialize auto-save
            initDashboardAutoSave();
        });

        // Handle database selection change
        function onDatabaseSelect() {
            const dropdown = document.getElementById('dbDropdown');
            const remoteCheckbox = document.getElementById('dbCredentialsCheckbox');
            const localhostCheckbox = document.getElementById('dbLocalhostCheckbox');
            const selectedOption = dropdown.options[dropdown.selectedIndex];
            
            if (dropdown.value && selectedOption.dataset) {
                selectedDatabaseConnection = {
                    id: dropdown.value,
                    name: selectedOption.textContent,
                    host: selectedOption.dataset.host,
                    dbName: selectedOption.dataset.dbname,
                    username: selectedOption.dataset.username,
                    password: selectedOption.dataset.password,
                    port: selectedOption.dataset.port,
                    type: selectedOption.dataset.type
                };
                
                // If remote checkbox is checked and dropdown changed, append new remote credentials
                if (remoteCheckbox.checked) {
                    appendCredentialsToEditor('remote');
                }
                // If localhost checkbox is checked and dropdown changed, append new localhost credentials
                if (localhostCheckbox.checked) {
                    appendCredentialsToEditor('localhost');
                }
            } else {
                selectedDatabaseConnection = null;
            }
        }

        // Track the last added credentials IDs for removal (separate for remote and localhost)
        let lastAddedRemoteCredId = null;
        let lastAddedLocalhostCredId = null;

        // Append database selection to prompt editor (arrow button) - BOTH Remote & Localhost
        function appendDatabaseToPrompt() {
            const dropdown = document.getElementById('dbDropdown');
            const selectedOption = dropdown.options[dropdown.selectedIndex];
            
            if (!dropdown.value || !selectedOption.dataset) {
                showToast('⚠️ Please select a database first', 'warning');
                return;
            }
            
            const editor = document.getElementById('promptEditor');
            const conn = {
                name: selectedOption.textContent.replace('🌐 ', ''),
                host: selectedOption.dataset.host,
                dbName: selectedOption.dataset.dbname,
                username: selectedOption.dataset.username,
                password: selectedOption.dataset.password || '',
                port: selectedOption.dataset.port || '3306',
                type: selectedOption.dataset.type || 'shared'
            };
            
            // Create REMOTE credentials block
            const remoteBlock = `
╔══════════════════════════════════════════════════════════════╗
║  🌐  DATABASE CREDENTIALS - REMOTE CONNECTION               ║
╠══════════════════════════════════════════════════════════════╣
║  Name:     ${conn.name.padEnd(48)}║
║  Host:     ${conn.host.padEnd(48)}║
║  Database: ${conn.dbName.padEnd(48)}║
║  Username: ${conn.username.padEnd(48)}║
║  Password: ${conn.password.padEnd(48)}║
║  Port:     ${conn.port.padEnd(48)}║
║  Type:     ${(conn.type === 'vps' ? 'VPS' : 'Shared Hosting').padEnd(48)}║
╚══════════════════════════════════════════════════════════════╝`.trim();

            // Create LOCALHOST credentials block
            const localhostBlock = `
╔══════════════════════════════════════════════════════════════╗
║  🖥️  DATABASE CREDENTIALS - LOCALHOST (ON-SERVER)           ║
╠══════════════════════════════════════════════════════════════╣
║  Name:     ${conn.name.padEnd(48)}║
║  Host:     ${'localhost'.padEnd(48)}║
║  Database: ${conn.dbName.padEnd(48)}║
║  Username: ${conn.username.padEnd(48)}║
║  Password: ${conn.password.padEnd(48)}║
║  Port:     ${conn.port.padEnd(48)}║
║  Type:     ${(conn.type === 'vps' ? 'VPS' : 'Shared Hosting').padEnd(48)}║
╚══════════════════════════════════════════════════════════════╝`.trim();
            
            // Combine both blocks
            const fullBlock = remoteBlock + '\n\n' + localhostBlock;
            
            // Append to editor
            if (editor.value.trim()) {
                editor.value = editor.value.trimEnd() + '\n\n' + fullBlock;
            } else {
                editor.value = fullBlock;
            }
            
            // Update counts and history
            updateCounts();
            recordHistoryState(true);
            
            // Show success toast
            showToast('📦 Database credentials (Remote + Localhost) appended', 'success');
            
            // Save dashboard settings
            saveDashboardSettings();
        }

        // Generate Smart Database Connection Prompt (with auto-switch toggle)
        function generateDatabasePrompt() {
            const dropdown = document.getElementById('dbDropdown');
            const selectedOption = dropdown.options[dropdown.selectedIndex];
            
            if (!dropdown.value || !selectedOption.dataset) {
                showToast('⚠️ Please select a database first', 'warning');
                return;
            }
            
            const editor = document.getElementById('promptEditor');
            const conn = {
                name: selectedOption.textContent.replace('🌐 ', ''),
                host: selectedOption.dataset.host,
                dbName: selectedOption.dataset.dbname,
                username: selectedOption.dataset.username,
                password: selectedOption.dataset.password || '',
                port: selectedOption.dataset.port || '3306',
                type: selectedOption.dataset.type || 'shared'
            };
            
            const smartPrompt = `
╔══════════════════════════════════════════════════════════════════════════════╗
║  🔄  SMART DATABASE CONNECTION - AUTO-SWITCH IMPLEMENTATION                  ║
╚══════════════════════════════════════════════════════════════════════════════╝

Please implement a smart database connection system with the following requirements:

────────────────────────────────────────────────────────────────────────────────
📦 DATABASE CREDENTIALS
────────────────────────────────────────────────────────────────────────────────

🌐 REMOTE CONNECTION:
   • Host:     ${conn.host}
   • Database: ${conn.dbName}
   • Username: ${conn.username}
   • Password: ${conn.password}
   • Port:     ${conn.port}

🖥️ LOCALHOST CONNECTION (On-Server):
   • Host:     localhost
   • Database: ${conn.dbName}
   • Username: ${conn.username}
   • Password: ${conn.password}
   • Port:     ${conn.port}

────────────────────────────────────────────────────────────────────────────────
⚙️ IMPLEMENTATION REQUIREMENTS
────────────────────────────────────────────────────────────────────────────────

1. **Connection Logic (PHP):**
   - Create a database connection class/function that tries BOTH connections
   - Use try-catch blocks to test each connection
   - Default to LOCALHOST first (faster when running on server)
   - If localhost fails, automatically switch to REMOTE
   - Store the working connection type in a session/cookie

2. **Toggle Switch (Frontend):**
   - Add a small, elegant toggle switch in the TOP RIGHT corner of the page
   - Make it subtle/minimal so regular users don't notice it (for admin use)
   - Two positions: "Local" (left/default) and "Remote" (right)
   - Use a smooth sliding animation

3. **Toggle Behavior:**
   - When user switches from Local to Remote:
     • Try to connect using REMOTE credentials
     • If successful: keep toggle on Remote
     • If fails: automatically slide back to Local with a subtle notification
   
   - When user switches from Remote to Local:
     • Try to connect using LOCALHOST credentials  
     • If successful: keep toggle on Local
     • If fails: automatically slide back to Remote with a subtle notification

4. **Visual Design:**
   - Toggle size: approximately 40px width, 20px height
   - Position: fixed, top: 10px, right: 10px (or similar corner position)
   - Colors: Green for Local (active), Blue for Remote (active)
   - Include tiny labels "L" and "R" or icons on each side
   - Add a subtle tooltip on hover explaining the toggle

5. **Example PHP Code Structure:**
   ~~~php
   class DatabaseConnection {
       private @@instance = null;
       private @@connection = null;
       private @@connectionType = 'localhost'; // default
       
       private @@credentials = [
           'localhost' => [
               'host' => 'localhost',
               'dbname' => '` + conn.dbName + `',
               'username' => '` + conn.username + `',
               'password' => '` + conn.password + `',
               'port' => '` + conn.port + `'
           ],
           'remote' => [
               'host' => '` + conn.host + `',
               'dbname' => '` + conn.dbName + `',
               'username' => '` + conn.username + `',
               'password' => '` + conn.password + `',
               'port' => '` + conn.port + `'
           ]
       ];
       
       public function connect(@@preferredType = 'localhost') {
           // Try preferred connection first
           try {
               @@cred = @@this->credentials[@@preferredType];
               @@this->connection = new PDO(
                   "mysql:host={@@cred['host']};dbname={@@cred['dbname']};port={@@cred['port']}",
                   @@cred['username'],
                   @@cred['password'],
                   [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
               );
               @@this->connectionType = @@preferredType;
               return ['success' => true, 'type' => @@preferredType];
           } catch (PDOException @@e) {
               // Try fallback connection
               @@fallbackType = (@@preferredType === 'localhost') ? 'remote' : 'localhost';
               try {
                   @@cred = @@this->credentials[@@fallbackType];
                   @@this->connection = new PDO(
                       "mysql:host={@@cred['host']};dbname={@@cred['dbname']};port={@@cred['port']}",
                       @@cred['username'],
                       @@cred['password'],
                       [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                   );
                   @@this->connectionType = @@fallbackType;
                   return ['success' => true, 'type' => @@fallbackType, 'fallback' => true];
               } catch (PDOException @@e2) {
                   return ['success' => false, 'error' => @@e2->getMessage()];
               }
           }
       }
       
       public function getConnectionType() {
           return @@this->connectionType;
       }
   }
   ~~~
   (Note: Replace @@ with $ in actual PHP code)

6. **Example Toggle HTML/CSS:**
   ~~~html
   <div class="db-toggle-container" title="Database Connection: Local/Remote">
       <span class="toggle-label local">L</span>
       <label class="db-toggle">
           <input type="checkbox" id="dbConnectionToggle" onchange="switchDbConnection(this.checked)">
           <span class="toggle-slider"></span>
       </label>
       <span class="toggle-label remote">R</span>
   </div>
   ~~~

   ~~~css
   .db-toggle-container {
       position: fixed;
       top: 10px;
       right: 10px;
       display: flex;
       align-items: center;
       gap: 4px;
       padding: 4px 8px;
       background: rgba(0,0,0,0.6);
       border-radius: 20px;
       z-index: 9999;
       opacity: 0.4;
       transition: opacity 0.3s;
   }
   .db-toggle-container:hover { opacity: 1; }
   .toggle-label { font-size: 9px; color: #888; }
   .toggle-label.local { color: #22c55e; }
   .toggle-label.remote { color: #3b82f6; }
   .db-toggle { position: relative; width: 36px; height: 18px; }
   .db-toggle input { opacity: 0; width: 0; height: 0; }
   .toggle-slider {
       position: absolute; cursor: pointer;
       top: 0; left: 0; right: 0; bottom: 0;
       background: #22c55e; border-radius: 18px;
       transition: 0.3s;
   }
   .toggle-slider:before {
       position: absolute; content: "";
       height: 14px; width: 14px;
       left: 2px; bottom: 2px;
       background: white; border-radius: 50%;
       transition: 0.3s;
   }
   .db-toggle input:checked + .toggle-slider { background: #3b82f6; }
   .db-toggle input:checked + .toggle-slider:before { transform: translateX(18px); }
   ~~~

────────────────────────────────────────────────────────────────────────────────
📝 NOTES
────────────────────────────────────────────────────────────────────────────────

• The toggle should be BARELY visible by default (low opacity)
• Only becomes fully visible on hover
• The auto-switch mechanism ensures the app always works regardless of environment
• Store user preference in localStorage to remember their choice
• The connection type should be available to JavaScript via a global variable or data attribute

`.trim();
            
            // Append to editor
            if (editor.value.trim()) {
                editor.value = editor.value.trimEnd() + '\n\n' + smartPrompt;
            } else {
                editor.value = smartPrompt;
            }
            
            // Update counts and history
            updateCounts();
            recordHistoryState(true);
            
            // Show success toast
            showToast('🔄 Smart database connection prompt generated', 'success');
            
            // Save dashboard settings
            saveDashboardSettings();
        }

        // Generate FULL Database Prompt with Toggle AND Speed Monitor
        function generateFullDatabasePrompt() {
            const dropdown = document.getElementById('dbDropdown');
            const selectedOption = dropdown.options[dropdown.selectedIndex];
            
            if (!dropdown.value || !selectedOption.dataset) {
                showToast('⚠️ Please select a database first', 'warning');
                return;
            }
            
            const editor = document.getElementById('promptEditor');
            const conn = {
                name: selectedOption.textContent.replace('🌐 ', ''),
                host: selectedOption.dataset.host,
                dbName: selectedOption.dataset.dbname,
                username: selectedOption.dataset.username,
                password: selectedOption.dataset.password || '',
                port: selectedOption.dataset.port || '3306',
                type: selectedOption.dataset.type || 'shared'
            };
            
            const fullPrompt = `
╔══════════════════════════════════════════════════════════════════════════════╗
║  🔄  FULL SMART DATABASE CONNECTION WITH TOGGLE & SPEED MONITOR              ║
╚══════════════════════════════════════════════════════════════════════════════╝

Please implement a comprehensive database connection system with:
1. Smart auto-switch between Localhost and Remote
2. Toggle switch for manual control
3. Speed monitor to track and compare operation performance

════════════════════════════════════════════════════════════════════════════════
📦 PART 1: DATABASE CREDENTIALS
════════════════════════════════════════════════════════════════════════════════

🌐 REMOTE CONNECTION (from anywhere):
   • Host:     ${conn.host}
   • Database: ${conn.dbName}
   • Username: ${conn.username}
   • Password: ${conn.password}
   • Port:     ${conn.port}

🖥️ LOCALHOST CONNECTION (on-server, faster):
   • Host:     localhost
   • Database: ${conn.dbName}
   • Username: ${conn.username}
   • Password: ${conn.password}
   • Port:     ${conn.port}

════════════════════════════════════════════════════════════════════════════════
⚙️ PART 2: PHP SMART CONNECTION CLASS
════════════════════════════════════════════════════════════════════════════════

~~~php
` + '<' + `?php
// Database credentials configuration
@@dbCredentials = [
    'localhost' => [
        'host' => 'localhost',
        'dbname' => '${conn.dbName}',
        'username' => '${conn.username}',
        'password' => '${conn.password}',
        'port' => '${conn.port}'
    ],
    'remote' => [
        'host' => '${conn.host}',
        'dbname' => '${conn.dbName}',
        'username' => '${conn.username}',
        'password' => '${conn.password}',
        'port' => '${conn.port}'
    ]
];

// Connection state
@@pdo = null;
@@connectionType = 'localhost';
@@connectionFallback = false;

// Handle AJAX connection switch request
if (isset(@@_GET['switch_db'])) {
    header('Content-Type: application/json');
    @@requestedType = @@_GET['switch_db'];
    
    if (!isset(@@dbCredentials[@@requestedType])) {
        echo json_encode(['success' => false, 'error' => 'Invalid connection type']);
        exit;
    }
    
    @@cred = @@dbCredentials[@@requestedType];
    try {
        @@testPdo = new PDO(
            "mysql:host={@@cred['host']};port={@@cred['port']};dbname={@@cred['dbname']};charset=utf8mb4",
            @@cred['username'],
            @@cred['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]
        );
        setcookie('db_connection_type', @@requestedType, time() + (86400 * 30), '/');
        echo json_encode(['success' => true, 'type' => @@requestedType]);
    } catch (PDOException @@e) {
        echo json_encode(['success' => false, 'error' => @@e->getMessage()]);
    }
    exit;
}

// Get preferred connection from cookie
@@preferredType = isset(@@_COOKIE['db_connection_type']) ? @@_COOKIE['db_connection_type'] : 'localhost';

// Smart connection function with try-catch fallback
function connectToDatabase(@@credentials, @@preferredType) {
    global @@connectionType, @@connectionFallback;
    
    // Try preferred connection first
    @@cred = @@credentials[@@preferredType];
    try {
        @@pdo = new PDO(
            "mysql:host={@@cred['host']};port={@@cred['port']};dbname={@@cred['dbname']};charset=utf8mb4",
            @@cred['username'],
            @@cred['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_TIMEOUT => 5]
        );
        @@connectionType = @@preferredType;
        @@connectionFallback = false;
        return @@pdo;
    } catch (PDOException @@e) {
        // Try fallback
        @@fallbackType = (@@preferredType === 'localhost') ? 'remote' : 'localhost';
        @@cred = @@credentials[@@fallbackType];
        try {
            @@pdo = new PDO(
                "mysql:host={@@cred['host']};port={@@cred['port']};dbname={@@cred['dbname']};charset=utf8mb4",
                @@cred['username'],
                @@cred['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_TIMEOUT => 5]
            );
            @@connectionType = @@fallbackType;
            @@connectionFallback = true;
            return @@pdo;
        } catch (PDOException @@e2) {
            return null;
        }
    }
}

// Establish connection
@@pdo = connectToDatabase(@@dbCredentials, @@preferredType);
if (@@pdo) {
    setcookie('db_connection_type', @@connectionType, time() + (86400 * 30), '/');
}

// For API operations - add timing measurement:
// @@startTime = microtime(true);
// ... your database operation ...
// @@operationTime = round((microtime(true) - @@startTime) * 1000, 2);
// Return in JSON: 'operationTime' => @@operationTime, 'connectionType' => @@connectionType
~~~
(Note: Replace @@ with $ in actual PHP code)

════════════════════════════════════════════════════════════════════════════════
🎨 PART 3: TOGGLE SWITCH (HTML + CSS)
════════════════════════════════════════════════════════════════════════════════

Add this HTML right after <body>:

~~~html
<!-- Database Connection Toggle Switch -->
<div class="db-toggle-container" id="dbToggleContainer" title="Database Connection: Local/Remote" data-active="` + '<' + `?php echo @@connectionType; ?>">
    <span class="toggle-label local">🖥️</span>
    <label class="db-toggle">
        <input type="checkbox" id="dbConnectionToggle" onchange="switchDbConnection(this.checked)" ` + '<' + `?php echo (@@connectionType === 'remote') ? 'checked' : ''; ?>>
        <span class="toggle-slider"></span>
    </label>
    <span class="toggle-label remote">🌐</span>
    <div class="connection-status" id="connectionStatus">
        ` + '<' + `?php echo (@@connectionType === 'localhost') ? '🖥️ Local' : '🌐 Remote'; ?>
        ` + '<' + `?php if (@@connectionFallback): ?><span class="fallback-badge">⚡ Auto</span>` + '<' + `?php endif; ?>
    </div>
</div>

<!-- Speed Monitor Box -->
<div class="speed-monitor" id="speedMonitor" title="Database Operation Speed Comparison">
    <div class="speed-monitor-title"><span>⚡</span> Speed Monitor</div>
    <div id="speedContent"><div class="no-data">Perform an action to see speed...</div></div>
</div>
~~~

CSS for both:

~~~css
/* Toggle Container */
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
}
.db-toggle-container:hover { opacity: 1; }

.toggle-label { font-size: 14px; opacity: 0.5; transition: all 0.3s; }
.toggle-label.local { color: #22c55e; }
.toggle-label.remote { color: #3b82f6; }
.db-toggle-container[data-active="local"] .toggle-label.local,
.db-toggle-container[data-active="remote"] .toggle-label.remote { opacity: 1; transform: scale(1.1); }

.db-toggle { position: relative; width: 42px; height: 22px; cursor: pointer; }
.db-toggle input { opacity: 0; width: 0; height: 0; }
.toggle-slider {
    position: absolute; cursor: pointer;
    top: 0; left: 0; right: 0; bottom: 0;
    background: linear-gradient(135deg, #22c55e 0%, #15803d 100%);
    border-radius: 22px;
    transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}
.toggle-slider:before {
    position: absolute; content: "";
    height: 16px; width: 16px; left: 3px; bottom: 3px;
    background: white; border-radius: 50%;
    transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    box-shadow: 0 2px 5px rgba(0,0,0,0.3);
}
.db-toggle input:checked + .toggle-slider { background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); }
.db-toggle input:checked + .toggle-slider:before { transform: translateX(20px); }

.connection-status { font-size: 10px; color: rgba(255,255,255,0.6); padding-left: 8px; border-left: 1px solid rgba(255,255,255,0.1); }
.fallback-badge { background: #f59e0b; color: #000; padding: 1px 5px; border-radius: 8px; font-size: 8px; font-weight: 600; }

/* Speed Monitor */
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
    min-width: 200px;
    font-family: 'JetBrains Mono', monospace;
}
.speed-monitor:hover { opacity: 1; }
.speed-monitor-title { font-size: 9px; color: rgba(255,255,255,0.6); text-transform: uppercase; letter-spacing: 1px; }
.speed-row { display: flex; justify-content: space-between; padding: 3px 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
.speed-label { font-size: 10px; color: rgba(255,255,255,0.6); }
.op-type { font-size: 8px; padding: 1px 4px; border-radius: 4px; background: rgba(255,255,255,0.1); }
.speed-value { font-size: 12px; font-weight: 600; }
.speed-value.local { color: #22c55e; }
.speed-value.remote { color: #8b5cf6; }
.speed-comparison { display: flex; justify-content: center; gap: 6px; padding: 6px 8px; margin-top: 4px; border-radius: 8px; font-size: 10px; font-weight: 600; }
.speed-comparison.faster { background: rgba(34,197,94,0.15); color: #22c55e; border: 1px solid rgba(34,197,94,0.3); }
.speed-comparison.slower { background: rgba(239,68,68,0.15); color: #ef4444; border: 1px solid rgba(239,68,68,0.3); }
.no-data { font-size: 10px; color: rgba(255,255,255,0.4); text-align: center; padding: 8px; font-style: italic; }
~~~

════════════════════════════════════════════════════════════════════════════════
📜 PART 4: JAVASCRIPT FUNCTIONALITY
════════════════════════════════════════════════════════════════════════════════

~~~javascript
// Connection state
let currentConnectionType = '` + '<' + `?php echo @@connectionType; ?>';
let connectionFallback = ` + '<' + `?php echo @@connectionFallback ? 'true' : 'false'; ?>;
const SPEED_HISTORY_KEY = 'db_speed_history';

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initSpeedMonitor();
    if (connectionFallback) {
        showToast('⚡ Auto-switched to ' + (currentConnectionType === 'localhost' ? '🖥️ Local' : '🌐 Remote'), 'warning');
    }
});

// Switch connection
async function switchDbConnection(isRemote) {
    const targetType = isRemote ? 'remote' : 'localhost';
    const toggle = document.getElementById('dbConnectionToggle');
    const container = document.getElementById('dbToggleContainer');
    const statusEl = document.getElementById('connectionStatus');
    
    toggle.disabled = true;
    showToast('Switching to ' + (isRemote ? '🌐 Remote' : '🖥️ Localhost') + '...', 'info');
    
    try {
        const response = await fetch('?switch_db=' + targetType);
        const result = await response.json();
        
        if (result.success) {
            currentConnectionType = result.type;
            container.setAttribute('data-active', result.type);
            statusEl.innerHTML = result.type === 'remote' ? '🌐 Remote' : '🖥️ Local';
            showToast('✅ Connected to ' + (result.type === 'remote' ? '🌐 Remote' : '🖥️ Localhost'), 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            toggle.checked = (currentConnectionType === 'remote');
            showToast('❌ Connection failed: ' + result.error, 'error');
        }
    } catch (error) {
        toggle.checked = (currentConnectionType === 'remote');
        showToast('❌ Network error', 'error');
    }
    toggle.disabled = false;
}

// Speed Monitor Functions
function initSpeedMonitor() {
    if (window.latestOperation) addSpeedEntry(window.latestOperation);
    updateSpeedMonitor();
}

function getSpeedHistory() {
    try { return JSON.parse(localStorage.getItem(SPEED_HISTORY_KEY)) || []; }
    catch (e) { return []; }
}

function saveSpeedHistory(history) {
    if (history.length > 10) history = history.slice(-10);
    localStorage.setItem(SPEED_HISTORY_KEY, JSON.stringify(history));
}

function addSpeedEntry(op) {
    const history = getSpeedHistory();
    if (!history.some(h => h.timestamp === op.timestamp && h.type === op.type)) {
        history.push({ time: op.time, type: op.type, connection: op.connection, timestamp: op.timestamp });
        saveSpeedHistory(history);
    }
}

function updateSpeedMonitor() {
    const content = document.getElementById('speedContent');
    if (!content) return;
    
    const history = getSpeedHistory();
    if (history.length === 0) {
        content.innerHTML = '<div class="no-data">Perform an action to see speed...</div>';
        return;
    }
    
    const lastTwo = history.slice(-2);
    let html = '';
    
    lastTwo.forEach((entry, idx) => {
        const connIcon = entry.connection === 'localhost' ? '🖥️' : '🌐';
        const connClass = entry.connection === 'localhost' ? 'local' : 'remote';
        html += '<div class="speed-row"><span class="speed-label">' + connIcon + ' <span class="op-type">' + entry.type + '</span></span><span class="speed-value ' + connClass + '">' + entry.time + 'ms</span></div>';
    });
    
    if (lastTwo.length === 2) {
        const [first, second] = lastTwo;
        const diff = Math.abs(first.time - second.time).toFixed(2);
        const pct = first.time > 0 ? Math.round((diff / first.time) * 100) : 0;
        let cls = second.time < first.time ? 'faster' : (second.time > first.time ? 'slower' : 'equal');
        let winner = second.time < first.time ? (second.connection === 'localhost' ? '🖥️' : '🌐') : (first.connection === 'localhost' ? '🖥️' : '🌐');
        html += '<div class="speed-comparison ' + cls + '"><span>' + winner + '</span><span>Δ ' + diff + 'ms</span><span>' + pct + '% faster</span></div>';
    }
    
    html += '<div style="text-align:center;margin-top:6px"><button onclick="clearSpeedHistory()" style="background:rgba(255,255,255,0.1);border:none;color:rgba(255,255,255,0.5);font-size:8px;padding:2px 8px;border-radius:4px;cursor:pointer">Clear</button></div>';
    content.innerHTML = html;
}

function clearSpeedHistory() {
    localStorage.removeItem(SPEED_HISTORY_KEY);
    updateSpeedMonitor();
}

// Record operation speed (call after API responses)
function recordSpeed(type, time, connection) {
    addSpeedEntry({ time: parseFloat(time), type: type, connection: connection || currentConnectionType, timestamp: Date.now() });
    updateSpeedMonitor();
}
~~~
(Note: Replace @@ with $ in PHP parts)

════════════════════════════════════════════════════════════════════════════════
📝 IMPLEMENTATION NOTES
════════════════════════════════════════════════════════════════════════════════

1. **Default Behavior:** Always tries LOCALHOST first (faster on-server), falls back to REMOTE
2. **Toggle:** Users can manually switch; if connection fails, auto-reverts to working one
3. **Speed Monitor:** Shows last 2 operations with timing comparison
4. **Auto Badge:** Shows "⚡ Auto" when connection was automatically switched
5. **Timing:** PHP measures with microtime(), returns in JSON, JS displays in monitor
6. **Persistence:** Connection preference saved in cookie (30 days), speed history in localStorage

`.trim();
            
            // Append to editor
            if (editor.value.trim()) {
                editor.value = editor.value.trimEnd() + '\n\n' + fullPrompt;
            } else {
                editor.value = fullPrompt;
            }
            
            // Update counts and history
            updateCounts();
            recordHistoryState(true);
            
            // Show success toast
            showToast('🚀 Full prompt with Toggle & Speed Monitor generated', 'success');
            
            // Save dashboard settings
            saveDashboardSettings();
        }

        // Append section (Backend/Page/Frontend) to prompt editor - Dynamic version
        function appendSectionToPrompt(section) {
            const editor = document.getElementById('promptEditor');
            
            // Get section info
            let sectionName, sectionIcon;
            
            switch(section) {
                case 'backend':
                    sectionName = 'BACKEND';
                    sectionIcon = '📄';
                    break;
                case 'page':
                    sectionName = 'PAGE';
                    sectionIcon = '🪟';
                    break;
                case 'frontend':
                    sectionName = 'FRONTEND';
                    sectionIcon = '🎨';
                    break;
                default:
                    return;
            }
            
            // Get dynamic items for this section
            const items = dynamicItems[section] || [];
            
            // Check if there's anything to send
            if (items.length === 0) {
                showToast(`⚠️ No ${sectionName.toLowerCase()} items added`, 'warning');
                return;
            }
            
            // Build the block
            let blockContent = `${sectionIcon} ${sectionName} COMPONENTS (${items.length})\n`;
            blockContent += '─'.repeat(50) + '\n';
            
            items.forEach((item, idx) => {
                const itemName = item.name || `${sectionName} ${idx + 1}`;
                blockContent += `\n#${idx + 1} ${itemName}\n`;
                
                if (item.files && item.files.length > 0) {
                    blockContent += `   📁 Files: ${item.files.map(f => f.name).join(', ')}\n`;
                }
                if (item.prompt && item.prompt.trim()) {
                    blockContent += `   📝 ${item.prompt}\n`;
                }
            });
            
            // Create full block
            const fullBlock = `╔══════════════════════════════════════════════════════════════╗
${blockContent}
╚══════════════════════════════════════════════════════════════╝`.trim();
            
            // Append to editor
            if (editor.value.trim()) {
                editor.value = editor.value.trimEnd() + '\n\n' + fullBlock;
            } else {
                editor.value = fullBlock;
            }
            
            // Update counts and history
            updateCounts();
            recordHistoryState(true);
            
            // Show success toast
            showToast(`${sectionIcon} ${sectionName} appended (${items.length} item${items.length > 1 ? 's' : ''})`, 'success');
            
            // Save
            saveDashboardSettings();
            saveDynamicItems();
        }

        // Append ALL sections to prompt editor (Generate arrow button)
        function appendAllSectionsToPrompt() {
            const editor = document.getElementById('promptEditor');
            let sectionsAdded = 0;
            let allBlocks = [];
            
            // 1. DATABASE SECTION
            const dropdown = document.getElementById('dbDropdown');
            const selectedOption = dropdown.options[dropdown.selectedIndex];
            
            if (dropdown.value && selectedOption.dataset) {
                const conn = {
                    name: selectedOption.textContent.replace('🌐 ', ''),
                    host: selectedOption.dataset.host,
                    dbName: selectedOption.dataset.dbname,
                    username: selectedOption.dataset.username,
                    password: selectedOption.dataset.password || '',
                    port: selectedOption.dataset.port || '3306',
                    type: selectedOption.dataset.type || 'shared'
                };
                
                // Remote block
                const remoteBlock = `╔══════════════════════════════════════════════════════════════╗
║  🌐  DATABASE CREDENTIALS - REMOTE CONNECTION               ║
╠══════════════════════════════════════════════════════════════╣
║  Name:     ${conn.name.padEnd(48)}║
║  Host:     ${conn.host.padEnd(48)}║
║  Database: ${conn.dbName.padEnd(48)}║
║  Username: ${conn.username.padEnd(48)}║
║  Password: ${conn.password.padEnd(48)}║
║  Port:     ${conn.port.padEnd(48)}║
║  Type:     ${(conn.type === 'vps' ? 'VPS' : 'Shared Hosting').padEnd(48)}║
╚══════════════════════════════════════════════════════════════╝`;

                // Localhost block
                const localhostBlock = `╔══════════════════════════════════════════════════════════════╗
║  🖥️  DATABASE CREDENTIALS - LOCALHOST (ON-SERVER)           ║
╠══════════════════════════════════════════════════════════════╣
║  Name:     ${conn.name.padEnd(48)}║
║  Host:     ${'localhost'.padEnd(48)}║
║  Database: ${conn.dbName.padEnd(48)}║
║  Username: ${conn.username.padEnd(48)}║
║  Password: ${conn.password.padEnd(48)}║
║  Port:     ${conn.port.padEnd(48)}║
║  Type:     ${(conn.type === 'vps' ? 'VPS' : 'Shared Hosting').padEnd(48)}║
╚══════════════════════════════════════════════════════════════╝`;
                
                allBlocks.push(remoteBlock);
                allBlocks.push(localhostBlock);
                sectionsAdded++;
            }
            
            // 2. BACKEND SECTION (Dynamic Items)
            if (dynamicItems.backend.length > 0) {
                let blockContent = `📄 BACKEND COMPONENTS (${dynamicItems.backend.length})\n`;
                blockContent += '─'.repeat(50) + '\n';
                
                dynamicItems.backend.forEach((item, idx) => {
                    const itemName = item.name || `Backend ${idx + 1}`;
                    blockContent += `\n#${idx + 1} ${itemName}\n`;
                    
                    if (item.files && item.files.length > 0) {
                        blockContent += `   📁 Files: ${item.files.map(f => f.name).join(', ')}\n`;
                    }
                    if (item.prompt && item.prompt.trim()) {
                        blockContent += `   📝 ${item.prompt}\n`;
                    }
                });
                
                allBlocks.push(`╔══════════════════════════════════════════════════════════════╗
${blockContent}
╚══════════════════════════════════════════════════════════════╝`);
                sectionsAdded++;
            }
            
            // 3. PAGE SECTION (Dynamic Items)
            if (dynamicItems.page.length > 0) {
                let blockContent = `🪟 PAGE COMPONENTS (${dynamicItems.page.length})\n`;
                blockContent += '─'.repeat(50) + '\n';
                
                dynamicItems.page.forEach((item, idx) => {
                    const itemName = item.name || `Page ${idx + 1}`;
                    blockContent += `\n#${idx + 1} ${itemName}\n`;
                    
                    if (item.files && item.files.length > 0) {
                        blockContent += `   📁 Files: ${item.files.map(f => f.name).join(', ')}\n`;
                    }
                    if (item.prompt && item.prompt.trim()) {
                        blockContent += `   📝 ${item.prompt}\n`;
                    }
                });
                
                allBlocks.push(`╔══════════════════════════════════════════════════════════════╗
${blockContent}
╚══════════════════════════════════════════════════════════════╝`);
                sectionsAdded++;
            }
            
            // 4. FRONTEND SECTION (Dynamic Items)
            if (dynamicItems.frontend.length > 0) {
                let blockContent = `🎨 FRONTEND COMPONENTS (${dynamicItems.frontend.length})\n`;
                blockContent += '─'.repeat(50) + '\n';
                
                dynamicItems.frontend.forEach((item, idx) => {
                    const itemName = item.name || `Frontend ${idx + 1}`;
                    blockContent += `\n#${idx + 1} ${itemName}\n`;
                    
                    if (item.files && item.files.length > 0) {
                        blockContent += `   📁 Files: ${item.files.map(f => f.name).join(', ')}\n`;
                    }
                    if (item.prompt && item.prompt.trim()) {
                        blockContent += `   📝 ${item.prompt}\n`;
                    }
                });
                
                allBlocks.push(`╔══════════════════════════════════════════════════════════════╗
${blockContent}
╚══════════════════════════════════════════════════════════════╝`);
                sectionsAdded++;
            }
            
            // Check if anything to add
            if (allBlocks.length === 0) {
                showToast('⚠️ No data in any section to append', 'warning');
                return;
            }
            
            // Combine all blocks
            const fullBlock = allBlocks.join('\n\n');
            
            // Append to editor
            if (editor.value.trim()) {
                editor.value = editor.value.trimEnd() + '\n\n' + fullBlock;
            } else {
                editor.value = fullBlock;
            }
            
            // Update counts and history
            updateCounts();
            recordHistoryState(true);
            
            // Show success toast
            showToast(`✨ All sections appended (${sectionsAdded} section${sectionsAdded > 1 ? 's' : ''})`, 'success');
            
            // Save dashboard settings
            saveDashboardSettings();
            saveDynamicItems();
        }

        // Generate Comprehensive AI Prompt (Generate Button)
        function generateComprehensivePrompt() {
            const editor = document.getElementById('promptEditor');
            
            // Check database checkbox
            const dbChecked = document.getElementById('dbMainCheckbox')?.checked;
            
            // Check if we have any dynamic items or database selected
            const hasBackendItems = dynamicItems.backend.length > 0;
            const hasPageItems = dynamicItems.page.length > 0;
            const hasFrontendItems = dynamicItems.frontend.length > 0;
            
            if (!dbChecked && !hasBackendItems && !hasPageItems && !hasFrontendItems) {
                showToast('⚠️ Please add at least one item or select database', 'warning');
                return;
            }
            
            let promptSections = [];
            let hasContent = false;
            
            // Count items for summary
            const backendCount = dynamicItems.backend.length;
            const pageCount = dynamicItems.page.length;
            const frontendCount = dynamicItems.frontend.length;
            
            // Header
            promptSections.push(`
╔══════════════════════════════════════════════════════════════════════════════╗
║                    🚀 AI DEVELOPMENT INSTRUCTIONS                            ║
║                    Generated by Prompt Manager                               ║
╚══════════════════════════════════════════════════════════════════════════════╝

Please follow the instructions below carefully. Each section contains specific 
details about the application components you need to work with.

📊 PROJECT OVERVIEW:
   • Backend Components: ${backendCount}
   • Page Components: ${pageCount}
   • Frontend Components: ${frontendCount}
`);

            // 1. DATABASE SECTION (if checked)
            if (dbChecked) {
                const dropdown = document.getElementById('dbDropdown');
                const selectedOption = dropdown.options[dropdown.selectedIndex];
                
                if (dropdown.value && selectedOption.dataset) {
                    hasContent = true;
                    const conn = {
                        name: selectedOption.textContent.replace('🌐 ', ''),
                        host: selectedOption.dataset.host,
                        dbName: selectedOption.dataset.dbname,
                        username: selectedOption.dataset.username,
                        password: selectedOption.dataset.password || '',
                        port: selectedOption.dataset.port || '3306',
                        type: selectedOption.dataset.type || 'shared'
                    };
                    
                    promptSections.push(`
════════════════════════════════════════════════════════════════════════════════
📦 DATABASE CONNECTION
════════════════════════════════════════════════════════════════════════════════

Below are the database credentials for this application. Use the REMOTE connection 
when connecting from external servers or development machines. Use the LOCALHOST 
connection when the code is running directly on the Hostinger server for faster 
database operations.

┌─────────────────────────────────────────────────────────────────────────────┐
│  🌐 REMOTE CONNECTION (External Access)                                     │
├─────────────────────────────────────────────────────────────────────────────┤
│  Connection Name: ${conn.name}
│  Host:           ${conn.host}
│  Database:       ${conn.dbName}
│  Username:       ${conn.username}
│  Password:       ${conn.password}
│  Port:           ${conn.port}
│  Type:           ${conn.type === 'vps' ? 'VPS' : 'Shared Hosting'}
└─────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────┐
│  🖥️ LOCALHOST CONNECTION (On-Server Access)                                 │
├─────────────────────────────────────────────────────────────────────────────┤
│  Connection Name: ${conn.name}
│  Host:           localhost
│  Database:       ${conn.dbName}
│  Username:       ${conn.username}
│  Password:       ${conn.password}
│  Port:           ${conn.port}
│  Type:           ${conn.type === 'vps' ? 'VPS' : 'Shared Hosting'}
└─────────────────────────────────────────────────────────────────────────────┘

⚠️ IMPORTANT: Use the appropriate connection based on where your code is running.
`);
                }
            }

            // 2. BACKEND SECTIONS (Dynamic)
            if (hasBackendItems) {
                hasContent = true;
                promptSections.push(`
════════════════════════════════════════════════════════════════════════════════
📄 BACKEND SECTION (${backendCount} Component${backendCount > 1 ? 's' : ''})
════════════════════════════════════════════════════════════════════════════════

This section contains backend components and instructions. The backend handles 
server-side logic, API endpoints, and database operations.
`);
                
                dynamicItems.backend.forEach((item, index) => {
                    const itemName = item.name || `Backend ${index + 1}`;
                    const fileCount = item.files ? item.files.length : 0;
                    
                    promptSections.push(`
┌─────────────────────────────────────────────────────────────────────────────┐
│  📄 BACKEND #${index + 1}: ${itemName.padEnd(55)}│
├─────────────────────────────────────────────────────────────────────────────┤`);
                    
                    if (fileCount > 0) {
                        const filesList = item.files.map(f => f.name).join(', ');
                        promptSections.push(`
│  📁 Files (${fileCount}): ${filesList.substring(0, 55).padEnd(55)}│`);
                    }
                    
                    if (item.prompt && item.prompt.trim()) {
                        promptSections.push(`
│  📝 Instructions:                                                            │
│  ${item.prompt.substring(0, 70).padEnd(73)}│`);
                        
                        // If prompt is longer, add continuation
                        if (item.prompt.length > 70) {
                            const remaining = item.prompt.substring(70);
                            const lines = remaining.match(/.{1,73}/g) || [];
                            lines.forEach(line => {
                                promptSections.push(`│  ${line.padEnd(73)}│`);
                            });
                        }
                    }
                    
                    promptSections.push(`└─────────────────────────────────────────────────────────────────────────────┘`);
                });
                
                promptSections.push(`
💡 NOTE: If database operations are required, refer to the DATABASE CONNECTION 
section above for credentials. Ensure proper error handling and security measures 
are implemented for each backend component.
`);
            }

            // 3. PAGE SECTIONS (Dynamic)
            if (hasPageItems) {
                hasContent = true;
                promptSections.push(`
════════════════════════════════════════════════════════════════════════════════
🪟 PAGE SECTION (${pageCount} Page${pageCount > 1 ? 's' : ''})
════════════════════════════════════════════════════════════════════════════════

This section contains page components and instructions. These are the main 
application pages that may include both frontend display and backend logic.
`);
                
                dynamicItems.page.forEach((item, index) => {
                    const itemName = item.name || `Page ${index + 1}`;
                    const fileCount = item.files ? item.files.length : 0;
                    
                    promptSections.push(`
┌─────────────────────────────────────────────────────────────────────────────┐
│  🪟 PAGE #${index + 1}: ${itemName.padEnd(58)}│
├─────────────────────────────────────────────────────────────────────────────┤`);
                    
                    if (fileCount > 0) {
                        const filesList = item.files.map(f => f.name).join(', ');
                        promptSections.push(`
│  📁 Files (${fileCount}): ${filesList.substring(0, 55).padEnd(55)}│`);
                    }
                    
                    if (item.prompt && item.prompt.trim()) {
                        promptSections.push(`
│  📝 Instructions:                                                            │
│  ${item.prompt.substring(0, 70).padEnd(73)}│`);
                        
                        if (item.prompt.length > 70) {
                            const remaining = item.prompt.substring(70);
                            const lines = remaining.match(/.{1,73}/g) || [];
                            lines.forEach(line => {
                                promptSections.push(`│  ${line.padEnd(73)}│`);
                            });
                        }
                    }
                    
                    promptSections.push(`└─────────────────────────────────────────────────────────────────────────────┘`);
                });
                
                promptSections.push(`
💡 NOTE: If these pages require database access, refer to the DATABASE CONNECTION 
section. If they interact with backend APIs, refer to the BACKEND SECTION. If they 
include frontend components, refer to the FRONTEND SECTION.
`);
            }

            // 4. FRONTEND SECTIONS (Dynamic)
            if (hasFrontendItems) {
                hasContent = true;
                promptSections.push(`
════════════════════════════════════════════════════════════════════════════════
🎨 FRONTEND SECTION (${frontendCount} Component${frontendCount > 1 ? 's' : ''})
════════════════════════════════════════════════════════════════════════════════

This section contains frontend components and instructions. The frontend handles 
user interface, styling, and client-side interactions.
`);
                
                dynamicItems.frontend.forEach((item, index) => {
                    const itemName = item.name || `Frontend ${index + 1}`;
                    const fileCount = item.files ? item.files.length : 0;
                    
                    promptSections.push(`
┌─────────────────────────────────────────────────────────────────────────────┐
│  🎨 FRONTEND #${index + 1}: ${itemName.padEnd(55)}│
├─────────────────────────────────────────────────────────────────────────────┤`);
                    
                    if (fileCount > 0) {
                        const filesList = item.files.map(f => f.name).join(', ');
                        promptSections.push(`
│  📁 Files (${fileCount}): ${filesList.substring(0, 55).padEnd(55)}│`);
                    }
                    
                    if (item.prompt && item.prompt.trim()) {
                        promptSections.push(`
│  📝 Instructions:                                                            │
│  ${item.prompt.substring(0, 70).padEnd(73)}│`);
                        
                        if (item.prompt.length > 70) {
                            const remaining = item.prompt.substring(70);
                            const lines = remaining.match(/.{1,73}/g) || [];
                            lines.forEach(line => {
                                promptSections.push(`│  ${line.padEnd(73)}│`);
                            });
                        }
                    }
                    
                    promptSections.push(`└─────────────────────────────────────────────────────────────────────────────┘`);
                });
                
                promptSections.push(`
💡 NOTE: If these frontend components connect to backend APIs, refer to the 
BACKEND SECTION. If they display data from the database, ensure the backend 
properly fetches and serves the data. Maintain consistent styling and responsive 
design across all components.
`);
            }

            // Check if we have any actual content
            if (!hasContent) {
                showToast('⚠️ No items have data (names or prompts)', 'warning');
                return;
            }

            // Footer / Summary
            promptSections.push(`
════════════════════════════════════════════════════════════════════════════════
📋 SUMMARY
════════════════════════════════════════════════════════════════════════════════

Components included in this prompt:
${dbChecked ? '  ✅ Database Connection (Remote + Localhost)' : '  ⬜ Database Connection'}
${hasBackendItems ? `  ✅ Backend Section (${backendCount} component${backendCount > 1 ? 's' : ''})` : '  ⬜ Backend Section'}
${hasPageItems ? `  ✅ Page Section (${pageCount} page${pageCount > 1 ? 's' : ''})` : '  ⬜ Page Section'}
${hasFrontendItems ? `  ✅ Frontend Section (${frontendCount} component${frontendCount > 1 ? 's' : ''})` : '  ⬜ Frontend Section'}

Total Components: ${backendCount + pageCount + frontendCount}

Please ensure all components work together seamlessly. Follow the instructions 
in each section carefully and maintain proper connections between components.

════════════════════════════════════════════════════════════════════════════════
`);

            // Combine all sections
            const fullPrompt = promptSections.join('');
            
            // Append to editor
            if (editor.value.trim()) {
                editor.value = editor.value.trimEnd() + '\n\n' + fullPrompt;
            } else {
                editor.value = fullPrompt;
            }
            
            // Update counts and history
            updateCounts();
            recordHistoryState(true);
            
            // Count total items
            const totalItems = (dbChecked ? 1 : 0) + backendCount + pageCount + frontendCount;
            
            // Show success toast
            showToast(`✨ AI prompt generated with ${totalItems} component${totalItems > 1 ? 's' : ''}`, 'success');
            
            // Save dashboard settings
            saveDashboardSettings();
            saveDynamicItems();
        }

        // Toggle database credentials in editor
        function toggleDatabaseCredentials(mode = 'remote') {
            const checkbox = mode === 'localhost' 
                ? document.getElementById('dbLocalhostCheckbox')
                : document.getElementById('dbCredentialsCheckbox');
            const dropdown = document.getElementById('dbDropdown');
            
            if (checkbox.checked) {
                if (!dropdown.value) {
                    showToast('⚠️ Please select a database first', 'warning');
                    checkbox.checked = false;
                    return;
                }
                appendCredentialsToEditor(mode);
            } else {
                // Remove only the last added credentials for this mode when unchecking
                if (mode === 'localhost' && lastAddedLocalhostCredId) {
                    removeCredentialsByIdFromEditor(lastAddedLocalhostCredId);
                    lastAddedLocalhostCredId = null;
                } else if (mode === 'remote' && lastAddedRemoteCredId) {
                    removeCredentialsByIdFromEditor(lastAddedRemoteCredId);
                    lastAddedRemoteCredId = null;
                }
            }
        }

        // Append credentials to the END of prompt editor (always append, never replace)
        function appendCredentialsToEditor(mode = 'remote') {
            if (!selectedDatabaseConnection) return;
            
            const editor = document.getElementById('promptEditor');
            const conn = selectedDatabaseConnection;
            
            // Determine host based on mode
            const host = mode === 'localhost' ? 'localhost' : conn.host;
            const modeLabel = mode === 'localhost' ? 'LOCALHOST (ON-SERVER)' : 'REMOTE CONNECTION';
            const modeIcon = mode === 'localhost' ? '🖥️' : '🌐';
            
            // Create unique ID for this credentials block
            const credId = `DB_${mode}_${conn.id}_${Date.now()}`;
            
            // Track the credential ID based on mode
            if (mode === 'localhost') {
                lastAddedLocalhostCredId = credId;
            } else {
                lastAddedRemoteCredId = credId;
            }
            
            // Create credentials block with ID marker
            const credentialsBlock = `
<!-- CRED:${credId} -->
╔══════════════════════════════════════════════════════════════╗
║  ${modeIcon}  DATABASE CREDENTIALS - ${modeLabel.padEnd(30)}║
╠══════════════════════════════════════════════════════════════╣
║  Name:     ${conn.name.padEnd(48)}║
║  Host:     ${host.padEnd(48)}║
║  Database: ${conn.dbName.padEnd(48)}║
║  Username: ${conn.username.padEnd(48)}║
║  Password: ${conn.password.padEnd(48)}║
║  Port:     ${conn.port.padEnd(48)}║
║  Type:     ${(conn.type === 'vps' ? 'VPS' : 'Shared Hosting').padEnd(48)}║
╚══════════════════════════════════════════════════════════════╝
<!-- /CRED:${credId} -->`.trim();
            
            // APPEND to the end (not prepend)
            if (editor.value.trim()) {
                editor.value = editor.value.trimEnd() + '\n\n' + credentialsBlock;
            } else {
                editor.value = credentialsBlock;
            }
            
            updateCounts();
            recordHistoryState(true);
            
            const toastMsg = mode === 'localhost' 
                ? `🖥️ Localhost credentials appended` 
                : `🌐 Remote credentials appended`;
            showToast(toastMsg, 'success');
        }

        // Remove specific credentials block by ID
        function removeCredentialsByIdFromEditor(credId) {
            const editor = document.getElementById('promptEditor');
            const startMarker = `<!-- CRED:${credId} -->`;
            const endMarker = `<!-- /CRED:${credId} -->`;
            
            let content = editor.value;
            const startIdx = content.indexOf(startMarker);
            
            if (startIdx !== -1) {
                const endIdx = content.indexOf(endMarker, startIdx);
                if (endIdx !== -1) {
                    const before = content.substring(0, startIdx).trimEnd();
                    const after = content.substring(endIdx + endMarker.length).trimStart();
                    editor.value = before + (before && after ? '\n\n' : '') + after;
                    
                    updateCounts();
                    recordHistoryState(true);
                    showToast('🗑️ Credentials removed', 'info');
                }
            }
        }

        // Remove ALL credentials blocks from the editor (legacy function - not used anymore)
        function removeCredentialsFromEditor() {
            const editor = document.getElementById('promptEditor');
            // Remove all credential blocks using regex
            let content = editor.value;
            const regex = /<!-- CRED:[^>]+-->[\s\S]*?<!-- \/CRED:[^>]+-->\n*/g;
            const newContent = content.replace(regex, '').trim();
            
            if (newContent !== content.trim()) {
                editor.value = newContent;
                updateCounts();
                recordHistoryState(true);
                showToast('🗑️ Credentials removed', 'info');
            }
        }

        // Refresh databases when window gains focus (syncs with Database Hub)
        window.addEventListener('focus', async () => {
            try {
                const currentCount = document.getElementById('dbDropdown').options.length - 1;
                const response = await fetch(DATABASE_HUB_API);
                const data = await response.json();
                
                // Only reload if count changed
                if (data.success && data.connections.length !== currentCount) {
                    await loadHostingerDatabases();
                    console.log('🔄 Database list refreshed from Database Hub');
                }
            } catch (e) {
                console.log('⚠️ Could not check for new connections');
            }
        });

        // Initialize
        document.addEventListener('DOMContentLoaded', async () => {
            loadPromptTemplates(); // Load from database
            loadSavedPrompts();
            loadUploadedFiles();
            setupEventListeners();
            initDistributionSlider(); // Initialize distribution slider
            initHistory(); // Initialize history system
            setupHistoryKeyboardShortcuts(); // Setup keyboard shortcuts
            await loadHostingerDatabases(); // Load database connections from Hub
        });
        
        // Load templates from database
        async function loadPromptTemplates() {
            const loading = document.getElementById('templateLoading');
            const promptList = document.getElementById('promptList');
            
            loading.style.display = 'block';
            promptList.innerHTML = '';
            
            try {
                const formData = new FormData();
                formData.append('action', 'get_templates');
                
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    promptTemplates = data.templates.map(t => ({
                        id: parseInt(t.id),
                        name: t.name,
                        content: t.content
                    }));
                    renderPromptList();
                } else {
                    showToast('Failed to load templates: ' + data.message, 'error');
                }
            } catch (error) {
                console.error('Error loading templates:', error);
                showToast('Error loading templates', 'error');
            } finally {
                loading.style.display = 'none';
            }
        }

        // Render prompt checkboxes
        function renderPromptList(searchTerm = '') {
            const container = document.getElementById('promptList');
            const noResults = document.getElementById('promptNoResults');
            const searchLower = searchTerm.toLowerCase().trim();
            
            // Filter templates based on search
            const filteredTemplates = searchLower 
                ? promptTemplates.filter(p => 
                    p.name.toLowerCase().includes(searchLower) || 
                    p.content.toLowerCase().includes(searchLower) ||
                    `#${p.id}`.includes(searchLower)
                )
                : promptTemplates;
            
            if (filteredTemplates.length === 0) {
                container.innerHTML = '';
                noResults.style.display = 'block';
                return;
            }
            
            noResults.style.display = 'none';
            
            container.innerHTML = filteredTemplates.map(prompt => {
                const isChecked = activePrompts.has(prompt.id);
                const highlightedName = searchLower 
                    ? highlightText(prompt.name, searchLower)
                    : prompt.name;
                const contentPreview = prompt.content.replace(/\n/g, ' ').substring(0, 50) + '...';
                
                return `
                    <div class="prompt-item ${isChecked ? 'checked' : ''}" data-id="${prompt.id}">
                        <div class="prompt-item-checkbox" onclick="togglePrompt(${prompt.id})">
                            <input type="checkbox" ${isChecked ? 'checked' : ''}>
                            <div class="checkbox-box"><i class="fas fa-check"></i></div>
                        </div>
                        <div class="prompt-item-content" onclick="openTemplatePreview(${prompt.id})">
                            <div class="prompt-item-name">${highlightedName}</div>
                            <div class="prompt-item-preview">${escapeHtml(contentPreview)}</div>
                        </div>
                        <div class="prompt-item-actions">
                            <button type="button" class="prompt-action-icon copy" onclick="copyTemplate(${prompt.id})" title="Copy">
                                <i class="fas fa-copy"></i>
                            </button>
                            <button type="button" class="prompt-action-icon pull" onclick="pullToTemplate(${prompt.id})" title="Pull from Editor">
                                <i class="fas fa-arrow-down"></i>
                            </button>
                            <button type="button" class="prompt-action-icon edit" onclick="openEditTemplateModal(${prompt.id})" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="prompt-action-icon delete" onclick="confirmDeleteTemplate(${prompt.id})" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
            }).join('');
            
            updatePromptCounter();
        }
        
        // Escape HTML for safe display
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Highlight search text
        function highlightText(text, search) {
            if (!search) return text;
            const regex = new RegExp(`(${escapeRegex(search)})`, 'gi');
            return text.replace(regex, '<span class="highlight">$1</span>');
        }
        
        // Escape regex special characters
        function escapeRegex(string) {
            return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        }
        
        // Filter prompt templates (called on input)
        function filterPromptTemplates() {
            const searchInput = document.getElementById('promptSearchInput');
            const clearBtn = document.getElementById('promptSearchClear');
            const searchTerm = searchInput.value;
            
            // Show/hide clear button
            clearBtn.style.display = searchTerm ? 'flex' : 'none';
            
            renderPromptList(searchTerm);
        }
        
        // Clear prompt search
        function clearPromptSearch() {
            const searchInput = document.getElementById('promptSearchInput');
            const clearBtn = document.getElementById('promptSearchClear');
            
            searchInput.value = '';
            clearBtn.style.display = 'none';
            renderPromptList();
            searchInput.focus();
        }
        
        // Select all visible prompts
        function selectAllPrompts() {
            const searchTerm = document.getElementById('promptSearchInput').value.toLowerCase().trim();
            const templatesToSelect = searchTerm 
                ? promptTemplates.filter(p => 
                    p.name.toLowerCase().includes(searchTerm) || 
                    p.content.toLowerCase().includes(searchTerm)
                )
                : promptTemplates;
            
            const editor = document.getElementById('promptEditor');
            let addedCount = 0;
            
            templatesToSelect.forEach(prompt => {
                if (!activePrompts.has(prompt.id)) {
                    activePrompts.add(prompt.id);
                    
                    // Append to editor
                    if (editor.value.trim()) {
                        editor.value += '\n\n';
                    }
                    editor.value += prompt.content;
                    addedCount++;
                }
            });
            
            renderPromptList(searchTerm);
            updateCounts();
            updateSelectAllTemplatesCheckbox();
            
            if (addedCount > 0) {
                showToast(`✅ ${addedCount} template(s) added to editor`, 'success');
            } else {
                showToast('All visible templates already selected', 'info');
            }
        }
        
        // Deselect all visible prompts
        function deselectAllPrompts() {
            const searchTerm = document.getElementById('promptSearchInput').value.toLowerCase().trim();
            const templatesToDeselect = searchTerm 
                ? promptTemplates.filter(p => 
                    p.name.toLowerCase().includes(searchTerm) || 
                    p.content.toLowerCase().includes(searchTerm)
                )
                : promptTemplates;
            
            let removedCount = 0;
            
            templatesToDeselect.forEach(prompt => {
                if (activePrompts.has(prompt.id)) {
                    activePrompts.delete(prompt.id);
                    removedCount++;
                }
            });
            
            rebuildEditor();
            renderPromptList(searchTerm);
            updateCounts();
            
            if (removedCount > 0) {
                showToast(`🗑️ ${removedCount} template(s) removed from editor`, 'info');
            } else {
                showToast('No templates to deselect', 'info');
            }
            
            // Update checkbox state
            updateSelectAllTemplatesCheckbox();
        }
        
        // Toggle all templates (checkbox handler)
        function toggleAllTemplates(checked) {
            if (checked) {
                selectAllPrompts();
            } else {
                deselectAllPrompts();
            }
        }
        
        // Update the "Select All" checkbox based on current selection
        function updateSelectAllTemplatesCheckbox() {
            const checkbox = document.getElementById('selectAllTemplatesCheckbox');
            if (!checkbox) return;
            
            const searchTerm = document.getElementById('promptSearchInput')?.value.toLowerCase().trim() || '';
            const visibleTemplates = searchTerm 
                ? promptTemplates.filter(p => 
                    p.name.toLowerCase().includes(searchTerm) || 
                    p.content.toLowerCase().includes(searchTerm)
                )
                : promptTemplates;
            
            if (visibleTemplates.length === 0) {
                checkbox.checked = false;
                checkbox.indeterminate = false;
            } else {
                const selectedCount = visibleTemplates.filter(p => activePrompts.has(p.id)).length;
                
                if (selectedCount === 0) {
                    checkbox.checked = false;
                    checkbox.indeterminate = false;
                } else if (selectedCount === visibleTemplates.length) {
                    checkbox.checked = true;
                    checkbox.indeterminate = false;
                } else {
                    checkbox.checked = false;
                    checkbox.indeterminate = true;
                }
            }
        }
        
        // Update prompt counter
        function updatePromptCounter() {
            const counter = document.getElementById('promptCounter');
            const total = promptTemplates.length;
            const selected = activePrompts.size;
            counter.textContent = `${selected}/${total}`;
            
            // Change color based on selection
            if (selected === 0) {
                counter.style.background = 'rgba(100, 100, 100, 0.15)';
                counter.style.color = 'var(--text-muted)';
            } else if (selected === total) {
                counter.style.background = 'rgba(16, 185, 129, 0.15)';
                counter.style.color = 'var(--success)';
            } else {
                counter.style.background = 'rgba(99, 102, 241, 0.15)';
                counter.style.color = 'var(--accent-primary)';
            }
        }
        
        // ============ TEMPLATE CRUD OPERATIONS ============
        
        // Open Add Template Modal
        function openAddTemplateModal() {
            const modal = document.getElementById('templateModal');
            const title = document.getElementById('templateModalTitle');
            const saveText = document.getElementById('templateSaveText');
            const nameInput = document.getElementById('templateNameInput');
            const contentInput = document.getElementById('templateContentInput');
            const editId = document.getElementById('templateEditId');
            
            title.innerHTML = '<i class="fas fa-plus-circle"></i> <span>Add New Template</span>';
            saveText.textContent = 'Add Template';
            nameInput.value = '';
            contentInput.value = '';
            editId.value = '';
            
            modal.classList.add('active');
            nameInput.focus();
        }
        
        // Open Edit Template Modal
        function openEditTemplateModal(id) {
            const template = promptTemplates.find(t => t.id === id);
            if (!template) return;
            
            const modal = document.getElementById('templateModal');
            const title = document.getElementById('templateModalTitle');
            const saveText = document.getElementById('templateSaveText');
            const nameInput = document.getElementById('templateNameInput');
            const contentInput = document.getElementById('templateContentInput');
            const editId = document.getElementById('templateEditId');
            
            title.innerHTML = '<i class="fas fa-edit"></i> <span>Edit Template</span>';
            saveText.textContent = 'Save Changes';
            nameInput.value = template.name;
            contentInput.value = template.content;
            editId.value = id;
            
            modal.classList.add('active');
            nameInput.focus();
        }
        
        // Close Template Modal
        function closeTemplateModal() {
            const modal = document.getElementById('templateModal');
            modal.classList.remove('active');
        }
        
        // Save Template (Add or Update)
        async function saveTemplate() {
            const nameInput = document.getElementById('templateNameInput');
            const contentInput = document.getElementById('templateContentInput');
            const editId = document.getElementById('templateEditId');
            
            const name = nameInput.value.trim();
            const content = contentInput.value.trim();
            const id = editId.value;
            
            if (!name || !content) {
                showToast('Please fill in both name and content', 'error');
                return;
            }
            
            const formData = new FormData();
            formData.append('name', name);
            formData.append('content', content);
            
            if (id) {
                formData.append('action', 'update_template');
                formData.append('id', id);
            } else {
                formData.append('action', 'add_template');
            }
            
            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Record operation speed
                    if (data.operationTime) {
                        addSpeedEntry({
                            time: data.operationTime,
                            type: data.operationType || (id ? 'UPDATE_TEMPLATE' : 'ADD_TEMPLATE'),
                            connection: data.connectionType || currentConnectionType,
                            timestamp: Date.now()
                        });
                        updateSpeedMonitor();
                    }
                    showToast(`${data.message} ⏱️ ${data.operationTime}ms`, 'success');
                    closeTemplateModal();
                    await loadPromptTemplates();
                } else {
                    showToast(data.message, 'error');
                }
            } catch (error) {
                console.error('Error saving template:', error);
                showToast('Error saving template', 'error');
            }
        }
        
        // Confirm Delete Template
        function confirmDeleteTemplate(id) {
            const template = promptTemplates.find(t => t.id === id);
            if (!template) return;
            
            showConfirmModal({
                title: 'Delete Template?',
                message: `Are you sure you want to delete "${template.name}"?`,
                icon: 'fa-trash-alt',
                type: 'warning',
                confirmText: 'Delete',
                confirmIcon: 'fa-trash-alt',
                details: `<div style="background: var(--bg-tertiary); padding: 0.75rem; border-radius: 8px; font-size: 0.8rem; color: var(--text-muted); margin-top: 0.5rem;">
                    <strong>${template.name}</strong><br>
                    ${escapeHtml(template.content.substring(0, 100))}...
                </div>`,
                onConfirm: () => deleteTemplate(id)
            });
        }
        
        // Delete Template
        async function deleteTemplate(id) {
            const formData = new FormData();
            formData.append('action', 'delete_template');
            formData.append('id', id);
            
            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Record operation speed
                    if (data.operationTime) {
                        addSpeedEntry({
                            time: data.operationTime,
                            type: data.operationType || 'DELETE_TEMPLATE',
                            connection: data.connectionType || currentConnectionType,
                            timestamp: Date.now()
                        });
                        updateSpeedMonitor();
                    }
                    
                    // Remove from active prompts if selected
                    if (activePrompts.has(id)) {
                        activePrompts.delete(id);
                        rebuildEditor();
                    }
                    
                    showToast(`${data.message} ⏱️ ${data.operationTime}ms`, 'success');
                    await loadPromptTemplates();
                } else {
                    showToast(data.message, 'error');
                }
            } catch (error) {
                console.error('Error deleting template:', error);
                showToast('Error deleting template', 'error');
            }
        }
        
        // Copy Template Content
        function copyTemplate(id) {
            const template = promptTemplates.find(t => t.id === id);
            if (!template) return;
            
            navigator.clipboard.writeText(template.content).then(() => {
                showToast(`"${template.name}" copied to clipboard!`, 'success');
            }).catch(err => {
                console.error('Failed to copy:', err);
                showToast('Failed to copy template', 'error');
            });
        }
        
        // Pull editor content to Template (overwrite template with editor content)
        function pullToTemplate(id) {
            const template = promptTemplates.find(t => t.id === id);
            if (!template) return;
            
            const editor = document.getElementById('promptEditor');
            const editorContent = editor.value.trim();
            
            if (!editorContent) {
                showToast('Editor is empty! Nothing to pull.', 'warning');
                return;
            }
            
            // Confirm before overwriting
            if (!confirm(`Are you sure you want to overwrite "${template.name}" with the current editor content?`)) {
                return;
            }
            
            // Send update to server
            const formData = new FormData();
            formData.append('action', 'update_template');
            formData.append('id', id);
            formData.append('name', template.name);
            formData.append('content', editorContent);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Update local array
                    template.content = editorContent;
                    showToast(`✅ "${template.name}" updated with editor content!`, 'success');
                } else {
                    showToast(data.message || 'Failed to update template', 'error');
                }
            })
            .catch(err => {
                console.error('Error:', err);
                showToast('Failed to update template', 'error');
            });
        }
        
        // Open Template Preview Modal
        function openTemplatePreview(id) {
            const template = promptTemplates.find(t => t.id === id);
            if (!template) return;
            
            currentPreviewTemplate = template;
            
            const modal = document.getElementById('templatePreviewModal');
            const previewName = document.getElementById('previewName');
            const previewContent = document.getElementById('previewContent');
            const editBtn = document.getElementById('previewEditBtn');
            const useBtn = document.getElementById('previewUseBtn');
            
            previewName.textContent = template.name;
            previewContent.textContent = template.content;
            
            editBtn.onclick = () => {
                closeTemplatePreview();
                openEditTemplateModal(id);
            };
            
            useBtn.onclick = () => {
                if (!activePrompts.has(id)) {
                    togglePrompt(id);
                }
                closeTemplatePreview();
            };
            
            modal.classList.add('active');
        }
        
        // Close Template Preview
        function closeTemplatePreview() {
            const modal = document.getElementById('templatePreviewModal');
            modal.classList.remove('active');
            currentPreviewTemplate = null;
        }
        
        // Copy template content from preview
        function copyTemplateContent() {
            if (!currentPreviewTemplate) return;
            
            navigator.clipboard.writeText(currentPreviewTemplate.content).then(() => {
                showToast('Content copied to clipboard!', 'success');
            }).catch(err => {
                console.error('Failed to copy:', err);
                showToast('Failed to copy content', 'error');
            });
        }

        // Pull editor content to Template from preview modal
        function pullTemplateContent() {
            if (!currentPreviewTemplate) return;
            
            const editor = document.getElementById('promptEditor');
            const editorContent = editor.value.trim();
            
            if (!editorContent) {
                showToast('Editor is empty! Nothing to pull.', 'warning');
                return;
            }
            
            // Confirm before overwriting
            if (!confirm(`Are you sure you want to overwrite "${currentPreviewTemplate.name}" with the current editor content?`)) {
                return;
            }
            
            // Send update to server
            const formData = new FormData();
            formData.append('action', 'update_template');
            formData.append('id', currentPreviewTemplate.id);
            formData.append('name', currentPreviewTemplate.name);
            formData.append('content', editorContent);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Update local array and preview
                    currentPreviewTemplate.content = editorContent;
                    document.getElementById('previewContent').textContent = editorContent;
                    showToast(`✅ "${currentPreviewTemplate.name}" updated with editor content!`, 'success');
                } else {
                    showToast(data.message || 'Failed to update template', 'error');
                }
            })
            .catch(err => {
                console.error('Error:', err);
                showToast('Failed to update template', 'error');
            });
        }

        // Toggle prompt in editor
        function togglePrompt(id) {
            const prompt = promptTemplates.find(p => p.id === id);
            if (!prompt) return;
            
            const promptItem = document.querySelector(`.prompt-item[data-id="${id}"]`);
            const editor = document.getElementById('promptEditor');
            
            if (activePrompts.has(id)) {
                activePrompts.delete(id);
                if (promptItem) {
                    promptItem.classList.remove('checked');
                    const checkbox = promptItem.querySelector('input[type="checkbox"]');
                    if (checkbox) checkbox.checked = false;
                }
                rebuildEditor();
                showToast(`${prompt.name} removed`, 'info');
            } else {
                activePrompts.add(id);
                if (promptItem) {
                    promptItem.classList.add('checked');
                    const checkbox = promptItem.querySelector('input[type="checkbox"]');
                    if (checkbox) checkbox.checked = true;
                }
                
                // Append to editor
                if (editor.value.trim()) {
                    editor.value += '\n\n' + prompt.content;
                } else {
                    editor.value = prompt.content;
                }
                
                showToast(`${prompt.name} added`, 'success');
            }
            
            updateCounts();
            updatePromptCounter();
            updateSelectAllTemplatesCheckbox();
            recordHistoryState(true); // Record template toggle in history
        }

        // Rebuild editor content from active prompts
        function rebuildEditor() {
            const editor = document.getElementById('promptEditor');
            const contents = [];
            
            promptTemplates.forEach(prompt => {
                if (activePrompts.has(prompt.id)) {
                    contents.push(prompt.content);
                }
            });
            
            editor.value = contents.join('\n\n');
            recordHistoryState(true); // Record rebuild in history
        }

        // Clear editor
        function clearEditor() {
            document.getElementById('promptEditor').value = '';
            activePrompts.clear();
            activeSavedPrompts.clear(); // Also clear saved prompts
            editorFiles.clear(); // Also clear file references
            
            // Reset template checkboxes
            document.querySelectorAll('.prompt-item').forEach(item => {
                item.classList.remove('checked');
                const checkbox = item.querySelector('input[type="checkbox"]');
                if (checkbox) checkbox.checked = false;
            });
            
            // Reset saved prompts checkboxes
            document.querySelectorAll('.saved-item').forEach(item => {
                item.classList.remove('checked');
                const checkbox = item.querySelector('input[type="checkbox"]');
                if (checkbox) checkbox.checked = false;
            });
            
            // Reset file checkboxes
            document.querySelectorAll('.file-item').forEach(item => {
                item.classList.remove('checked');
                const checkbox = item.querySelector('input[type="checkbox"]');
                if (checkbox) checkbox.checked = false;
            });
            
            updateCounts();
            updatePromptCounter();
            updateSavedCounter();
            recordHistoryState(true); // Record clear action in history
            showToast('Editor cleared', 'info');
        }

        // Copy prompt
        async function copyPrompt() {
            const editor = document.getElementById('promptEditor');
            const text = editor.value;
            
            if (!text.trim()) {
                showToast('Nothing to copy!', 'error');
                return;
            }
            
            try {
                await navigator.clipboard.writeText(text);
                showToast('Copied to clipboard!', 'success');
            } catch (err) {
                // Fallback
                editor.select();
                document.execCommand('copy');
                showToast('Copied to clipboard!', 'success');
            }
        }
        
        // Paste from clipboard
        async function pasteToEditor() {
            const editor = document.getElementById('promptEditor');
            
            try {
                const clipboardText = await navigator.clipboard.readText();
                
                if (!clipboardText) {
                    showToast('Clipboard is empty!', 'error');
                    return;
                }
                
                // Get current cursor position or end of text
                const start = editor.selectionStart;
                const end = editor.selectionEnd;
                const currentText = editor.value;
                
                // Insert at cursor position or append with newline
                if (currentText.trim() && start === end && start === currentText.length) {
                    // Cursor at end, append with newlines
                    editor.value = currentText + '\n\n' + clipboardText;
                } else if (start !== end) {
                    // Replace selected text
                    editor.value = currentText.substring(0, start) + clipboardText + currentText.substring(end);
                    editor.setSelectionRange(start + clipboardText.length, start + clipboardText.length);
                } else {
                    // Insert at cursor position
                    editor.value = currentText.substring(0, start) + clipboardText + currentText.substring(start);
                    editor.setSelectionRange(start + clipboardText.length, start + clipboardText.length);
                }
                
                updateCounts();
                recordHistoryState(true); // Record paste action in history
                showToast('Pasted from clipboard!', 'success');
                editor.focus();
            } catch (err) {
                console.error('Paste failed:', err);
                showToast('Unable to paste. Please use Ctrl+V', 'error');
            }
        }

        // ============================================
        // FOLDER PICKER & PROMPT.TXT SYSTEM
        // ============================================
        
        // Store folder handle globally
        let promptFolderHandle = null;
        let promptFileHandle = null;
        
        // IndexedDB for storing file handles (allows auto-reconnect)
        const DB_NAME = 'PromptManagerDB';
        const DB_VERSION = 1;
        const STORE_NAME = 'fileHandles';
        
        function openDB() {
            return new Promise((resolve, reject) => {
                const request = indexedDB.open(DB_NAME, DB_VERSION);
                request.onerror = () => reject(request.error);
                request.onsuccess = () => resolve(request.result);
                request.onupgradeneeded = (event) => {
                    const db = event.target.result;
                    if (!db.objectStoreNames.contains(STORE_NAME)) {
                        db.createObjectStore(STORE_NAME, { keyPath: 'id' });
                    }
                };
            });
        }
        
        async function saveHandleToDB(id, handle) {
            try {
                const db = await openDB();
                const tx = db.transaction(STORE_NAME, 'readwrite');
                tx.objectStore(STORE_NAME).put({ id, handle });
                await new Promise(r => tx.oncomplete = r);
                db.close();
            } catch (err) {
                console.log('DB save error:', err);
            }
        }
        
        async function getHandleFromDB(id) {
            try {
                const db = await openDB();
                const tx = db.transaction(STORE_NAME, 'readonly');
                const request = tx.objectStore(STORE_NAME).get(id);
                return new Promise((resolve) => {
                    request.onsuccess = () => { db.close(); resolve(request.result?.handle || null); };
                    request.onerror = () => { db.close(); resolve(null); };
                });
            } catch (err) {
                return null;
            }
        }
        
        // Initialize folder connection on page load
        async function initFolderConnection() {
            const savedFolderName = localStorage.getItem('promptFolderName');
            
            if (savedFolderName) {
                updateFolderUI(savedFolderName, false);
                
                // Try auto-reconnect from IndexedDB
                try {
                    const savedHandle = await getHandleFromDB('promptFolder');
                    if (savedHandle) {
                        const perm = await savedHandle.requestPermission({ mode: 'readwrite' });
                        if (perm === 'granted') {
                            promptFolderHandle = savedHandle;
                            try {
                                promptFileHandle = await promptFolderHandle.getFileHandle('prompt.txt', { create: false });
                            } catch (e) {
                                promptFileHandle = await promptFolderHandle.getFileHandle('prompt.txt', { create: true });
                            }
                            updateFolderUI(savedFolderName, true);
                            showToast(`✅ Auto-connected to ${savedFolderName}`, 'success');
                            
                            const savedTimer = localStorage.getItem('autoSendTimer');
                            if (savedTimer && parseInt(savedTimer) > 0) {
                                document.getElementById('timerInput').value = savedTimer;
                                updateAutoSendTimer();
                            }
                        }
                    }
                } catch (err) {
                    console.log('Auto-reconnect failed:', err.message);
                }
            }
        }
        
        // Select folder and setup prompt.txt connection
        async function selectPromptFolder() {
            try {
                // Check if File System Access API is supported
                if (!('showDirectoryPicker' in window)) {
                    showToast('❌ Your browser does not support folder selection. Please use Chrome or Edge.', 'error');
                    return;
                }
                
                // Show folder picker dialog
                promptFolderHandle = await window.showDirectoryPicker({
                    mode: 'readwrite'
                });
                
                const folderName = promptFolderHandle.name;
                console.log('📁 Folder selected:', folderName);
                
                // Check if prompt.txt exists, if not create it
                try {
                    // Try to get existing prompt.txt
                    promptFileHandle = await promptFolderHandle.getFileHandle('prompt.txt', { create: false });
                    console.log('✅ prompt.txt found in folder');
                    showToast(`✅ Connected to ${folderName}/prompt.txt`, 'success');
                } catch (e) {
                    // File doesn't exist, create it
                    promptFileHandle = await promptFolderHandle.getFileHandle('prompt.txt', { create: true });
                    console.log('📝 prompt.txt created in folder');
                    showToast(`📝 Created prompt.txt in ${folderName}`, 'success');
                }
                
                // Save folder name to localStorage
                localStorage.setItem('promptFolderName', folderName);
                await saveHandleToDB('promptFolder', promptFolderHandle);
                
                // Update UI
                updateFolderUI(folderName, true);
                
                // Re-enable auto-send timer if it was set
                const savedTimer = localStorage.getItem('autoSendTimer');
                if (savedTimer && parseInt(savedTimer) > 0) {
                    document.getElementById('timerInput').value = savedTimer;
                    updateAutoSendTimer();
                }
                
            } catch (err) {
                if (err.name === 'AbortError') {
                    // User cancelled the picker
                    console.log('Folder selection cancelled');
                } else {
                    console.error('Folder selection error:', err);
                    showToast('❌ Error selecting folder: ' + err.message, 'error');
                }
            }
        }
        
        // Update folder picker UI
        function updateFolderUI(folderName, isConnected) {
            const btnFolder = document.getElementById('btnFolderPicker');
            const btnSend = document.getElementById('btnSendToFile');
            const btnPull = document.getElementById('btnPullFromFile');
            const btnClear = document.getElementById('btnClearFolder');
            const pathIndicator = document.getElementById('folderPathIndicator');
            const pathText = document.getElementById('folderPathText');
            
            if (isConnected) {
                btnFolder.classList.add('connected');
                btnFolder.classList.remove('needs-reconnect');
                btnFolder.title = `Connected to ${folderName}/prompt.txt`;
                btnSend.disabled = false;
                btnPull.disabled = false;
                btnClear.classList.add('show');
                pathIndicator.classList.add('show');
                pathIndicator.classList.remove('disconnected');
                pathText.textContent = `${folderName}/prompt.txt`;
            } else if (folderName) {
                // Has saved folder but needs reconnection
                btnFolder.classList.remove('connected');
                btnFolder.classList.add('needs-reconnect');
                btnFolder.title = `Click to reconnect to ${folderName}`;
                btnSend.disabled = true;
                btnPull.disabled = true;
                btnClear.classList.add('show');
                pathIndicator.classList.add('show', 'disconnected');
                pathText.textContent = `${folderName}/prompt.txt (click Folder to reconnect)`;
            } else {
                // No saved folder
                btnFolder.classList.remove('connected', 'needs-reconnect');
                btnFolder.title = 'Select folder for prompt.txt';
                btnSend.disabled = true;
                btnPull.disabled = true;
                btnClear.classList.remove('show');
                pathIndicator.classList.remove('show', 'disconnected');
            }
        }
        
        // Clear folder selection
        function clearFolderSelection() {
            promptFolderHandle = null;
            promptFileHandle = null;
            localStorage.removeItem('promptFolderName');
            localStorage.removeItem('autoSendTimer');
            stopAutoSendTimer();
            updateFolderUI(null, false);
            showToast('📁 Folder selection cleared', 'info');
        }
        
        // Send editor content to prompt.txt
        async function sendToPromptFile() {
            if (!promptFileHandle) {
                showToast('❌ No folder connected. Please select a folder first.', 'error');
                return;
            }
            
            const editor = document.getElementById('promptEditor');
            const content = editor.value;
            
            try {
                // Create a writable stream
                const writable = await promptFileHandle.createWritable();
                
                // Write the content (even if empty - perfect mirror)
                await writable.write(content);
                
                // Close the stream
                await writable.close();
                
                const folderName = localStorage.getItem('promptFolderName') || 'folder';
                if (content.trim()) {
                    showToast(`✅ Synced to ${folderName}/prompt.txt`, 'success');
                } else {
                    showToast(`🔄 Cleared ${folderName}/prompt.txt`, 'info');
                }
                console.log('📤 Content synced to prompt.txt, length:', content.length);
                
            } catch (err) {
                console.error('Error writing to prompt.txt:', err);
                
                if (err.name === 'NotAllowedError') {
                    showToast('❌ Permission denied. Please select the folder again.', 'error');
                    // Reset connection
                    promptFolderHandle = null;
                    promptFileHandle = null;
                    updateFolderUI(localStorage.getItem('promptFolderName') || '', false);
                    // Stop auto-send timer
                    stopAutoSendTimer();
                } else {
                    showToast('❌ Error writing to file: ' + err.message, 'error');
                }
            }
        }
        
        // Pull content from prompt.txt into editor
        async function pullFromPromptFile() {
            if (!promptFileHandle) {
                showToast('❌ No folder connected. Please select a folder first.', 'error');
                return;
            }
            
            try {
                // Get read permission
                const file = await promptFileHandle.getFile();
                const content = await file.text();
                
                // Get editor
                const editor = document.getElementById('promptEditor');
                
                if (content.trim() === '') {
                    showToast('📄 prompt.txt is empty', 'info');
                    return;
                }
                
                // Set editor content
                editor.value = content;
                
                // Update counts
                updateCounts();
                
                // Show success message
                showToast('✅ Content pulled from prompt.txt!', 'success');
                console.log('📥 Content pulled from prompt.txt, length:', content.length);
                
            } catch (err) {
                console.error('Error reading from prompt.txt:', err);
                
                if (err.name === 'NotAllowedError') {
                    showToast('❌ Permission denied. Please select the folder again.', 'error');
                    // Reset connection
                    promptFolderHandle = null;
                    promptFileHandle = null;
                    updateFolderUI(localStorage.getItem('promptFolderName') || '', false);
                } else {
                    showToast('❌ Error reading file: ' + err.message, 'error');
                }
            }
        }
        
        // ============================================
        // FILE TRANSFER SYSTEM (Left/Right Files)
        // ============================================
        
        let transferFileHandles = {
            left: null,
            right: null
        };
        
        // Select a file for transfer
        async function selectTransferFile(side) {
            try {
                const [fileHandle] = await window.showOpenFilePicker({
                    types: [
                        {
                            description: 'Text Files',
                            accept: {
                                'text/*': ['.txt', '.md', '.json', '.xml', '.html', '.css', '.js', '.php', '.py', '.java', '.c', '.cpp', '.h', '.sql', '.yaml', '.yml', '.ini', '.cfg', '.log']
                            }
                        }
                    ],
                    multiple: false
                });
                
                // Store the file handle
                transferFileHandles[side] = fileHandle;
                
                // Update UI
                const fileName = fileHandle.name;
                const sideCapitalized = side.charAt(0).toUpperCase() + side.slice(1);
                const filePickerBtn = document.getElementById(`filePicker${sideCapitalized}`);
                const fileNameSpan = document.getElementById(`fileName${sideCapitalized}`);
                const btnPull = document.getElementById(`btnPull${sideCapitalized}`);
                const btnPush = document.getElementById(`btnPush${sideCapitalized}`);
                const btnClear = document.getElementById(`btnClear${sideCapitalized}`);
                
                filePickerBtn.classList.remove('needs-reconnect');
                filePickerBtn.classList.add('has-file');
                fileNameSpan.textContent = fileName;
                filePickerBtn.title = `${fileName} - Click to change`;
                btnPull.disabled = false;
                btnPush.disabled = false;
                btnClear.classList.add('show');
                
                // Save to localStorage and IndexedDB
                localStorage.setItem(`transferFile_${side}`, fileName);
                await saveHandleToDB(`transferFile_${side}`, fileHandle);

                showToast(`📄 Selected: ${fileName}`, 'success');
                console.log(`📄 File selected (${side}):`, fileName);
                
            } catch (err) {
                if (err.name === 'AbortError') {
                    console.log('File selection cancelled');
                } else {
                    console.error('File selection error:', err);
                    showToast('❌ Error selecting file: ' + err.message, 'error');
                }
            }
        }
        
        // Initialize saved file names and try auto-reconnect from IndexedDB
        async function initSavedFileNames() {
            for (const side of ['left', 'right']) {
                const savedFileName = localStorage.getItem(`transferFile_${side}`);
                if (savedFileName) {
                    const sideCapitalized = side.charAt(0).toUpperCase() + side.slice(1);
                    const filePickerBtn = document.getElementById(`filePicker${sideCapitalized}`);
                    const fileNameSpan = document.getElementById(`fileName${sideCapitalized}`);
                    const btnPull = document.getElementById(`btnPull${sideCapitalized}`);
                    const btnPush = document.getElementById(`btnPush${sideCapitalized}`);
                    const btnClear = document.getElementById(`btnClear${sideCapitalized}`);

                    // Show as needs-reconnect first
                    fileNameSpan.textContent = savedFileName;
                    filePickerBtn.classList.add('has-file', 'needs-reconnect');
                    filePickerBtn.title = `Last: ${savedFileName} - Click to reconnect`;
                    btnClear.classList.add('show');
                    
                    // Try auto-reconnect from IndexedDB
                    try {
                        const savedHandle = await getHandleFromDB(`transferFile_${side}`);
                        if (savedHandle) {
                            const perm = await savedHandle.requestPermission({ mode: 'readwrite' });
                            if (perm === 'granted') {
                                transferFileHandles[side] = savedHandle;
                                filePickerBtn.classList.remove('needs-reconnect');
                                filePickerBtn.title = `${savedFileName} - Click to change`;
                                btnPull.disabled = false;
                                btnPush.disabled = false;
                            }
                        }
                    } catch (err) {
                        console.log(`Auto-reconnect failed (${side}):`, err.message);
                    }
                }
            }
        }
        
        // Pull content from selected file into editor
        async function pullFromTransferFile(side) {
            const fileHandle = transferFileHandles[side];
            
            if (!fileHandle) {
                showToast('❌ No file selected. Please select a file first.', 'error');
                return;
            }
            
            try {
                const file = await fileHandle.getFile();
                const content = await file.text();
                
                const editor = document.getElementById('promptEditor');
                
                if (content.trim() === '') {
                    showToast(`📄 ${file.name} is empty`, 'info');
                    return;
                }
                
                editor.value = content;
                updateCounts();
                
                showToast(`✅ Content pulled from ${file.name}!`, 'success');
                console.log(`📥 Content pulled from ${file.name}, length:`, content.length);
                
            } catch (err) {
                console.error('Error reading file:', err);
                
                if (err.name === 'NotAllowedError') {
                    showToast('❌ Permission denied. Please select the file again.', 'error');
                    resetTransferFile(side);
                } else {
                    showToast('❌ Error reading file: ' + err.message, 'error');
                }
            }
        }
        
        // Push editor content to selected file
        async function pushToTransferFile(side) {
            const fileHandle = transferFileHandles[side];
            
            if (!fileHandle) {
                showToast('❌ No file selected. Please select a file first.', 'error');
                return;
            }
            
            try {
                const editor = document.getElementById('promptEditor');
                const content = editor.value;
                
                // Get writable stream
                const writable = await fileHandle.createWritable();
                await writable.write(content);
                await writable.close();
                
                const file = await fileHandle.getFile();
                showToast(`✅ Content pushed to ${file.name}!`, 'success');
                console.log(`📤 Content pushed to ${file.name}, length:`, content.length);
                
            } catch (err) {
                console.error('Error writing to file:', err);
                
                if (err.name === 'NotAllowedError') {
                    showToast('❌ Permission denied. Please select the file again.', 'error');
                    resetTransferFile(side);
                } else {
                    showToast('❌ Error writing to file: ' + err.message, 'error');
                }
            }
        }
        
        // Reset transfer file state
        function resetTransferFile(side, clearStorage = false) {
            transferFileHandles[side] = null;

            const sideCapitalized = side.charAt(0).toUpperCase() + side.slice(1);
            const filePickerBtn = document.getElementById(`filePicker${sideCapitalized}`);
            const fileNameSpan = document.getElementById(`fileName${sideCapitalized}`);
            const btnPull = document.getElementById(`btnPull${sideCapitalized}`);
            const btnPush = document.getElementById(`btnPush${sideCapitalized}`);
            const btnClear = document.getElementById(`btnClear${sideCapitalized}`);

            filePickerBtn.classList.remove('has-file', 'needs-reconnect');
            fileNameSpan.textContent = 'Select File';
            filePickerBtn.title = 'Select a file';
            btnPull.disabled = true;
            btnPush.disabled = true;
            btnClear.classList.remove('show');

            // Clear localStorage if requested
            if (clearStorage) {
                localStorage.removeItem(`transferFile_${side}`);
            }
        }
        
        // Clear file selection
        function clearFileSelection(side) {
            resetTransferFile(side, true);
            showToast(`📄 File selection cleared (${side})`, 'info');
        }
        
        // ============================================
        // FILE MANAGEMENT (Create, Delete, Rename) - Modal Based
        // ============================================
        
        // State for file management modals
        let fileManagement = {
            createFolder: null,
            createFolderParent: null,
            deleteFolder: null,
            deleteFiles: [],
            deleteFolderParent: null,
            deleteFolderSelected: [],
            renameFolder: null,
            renameSelectedFile: null,
            renameFiles: []
        };
        
        // Open Create File Modal
        function createNewFile() {
            fileManagement.createFolder = null;
            document.getElementById('createFolderBtn').classList.remove('selected');
            document.getElementById('createFolderName').textContent = 'Click to select folder';
            document.getElementById('newFileName').value = '';
            document.getElementById('contentEmpty').checked = true;
            document.querySelectorAll('#createFileModal .content-option').forEach(opt => opt.classList.remove('selected'));
            document.querySelector('#createFileModal .content-option').classList.add('selected');
            openModal('createFileModal');
        }
        
        // Open Create Folder Modal
        function createNewFolder() {
            fileManagement.createFolderParent = null;
            document.getElementById('createFolderParentBtn').classList.remove('selected');
            document.getElementById('createFolderParentName').textContent = 'Click to select parent folder';
            document.getElementById('newFolderName').value = '';
            openModal('createFolderModal');
        }
        
        // Select folder for creating file
        async function selectCreateFolder() {
            try {
                const folderHandle = await window.showDirectoryPicker({ mode: 'readwrite' });
                fileManagement.createFolder = folderHandle;
                document.getElementById('createFolderBtn').classList.add('selected');
                document.getElementById('createFolderName').textContent = folderHandle.name;
            } catch (err) {
                if (err.name !== 'AbortError') {
                    showToast('❌ Error selecting folder: ' + err.message, 'error');
                }
            }
        }
        
        // Select parent folder for creating new folder
        async function selectCreateFolderParent() {
            try {
                const folderHandle = await window.showDirectoryPicker({ mode: 'readwrite' });
                fileManagement.createFolderParent = folderHandle;
                document.getElementById('createFolderParentBtn').classList.add('selected');
                document.getElementById('createFolderParentName').textContent = folderHandle.name;
            } catch (err) {
                if (err.name !== 'AbortError') {
                    showToast('❌ Error selecting folder: ' + err.message, 'error');
                }
            }
        }
        
        // Confirm and create the folder
        async function confirmCreateFolder() {
            if (!fileManagement.createFolderParent) {
                showToast('❌ Please select a parent folder first', 'error');
                return;
            }
            
            const folderName = document.getElementById('newFolderName').value.trim();
            if (!folderName) {
                showToast('❌ Please enter a folder name', 'error');
                return;
            }
            
            try {
                // Check if folder already exists
                try {
                    await fileManagement.createFolderParent.getDirectoryHandle(folderName, { create: false });
                    showToast('❌ Folder already exists: ' + folderName, 'error');
                    return;
                } catch (e) {
                    // Good, folder doesn't exist
                }
                
                // Create the folder
                await fileManagement.createFolderParent.getDirectoryHandle(folderName, { create: true });
                
                closeModal('createFolderModal');
                showToast(`✅ Folder created: ${folderName}`, 'success');
                
            } catch (err) {
                showToast('❌ Error creating folder: ' + err.message, 'error');
            }
        }
        
        // ============================================
        // MOVE FILES FUNCTIONALITY
        // ============================================
        
        let moveFilesState = {
            sourceFolder: null,
            selectedFiles: [],
            destination: null
        };
        
        // Open Move Files Modal
        function openMoveFilesModal() {
            moveFilesState = {
                sourceFolder: null,
                selectedFiles: [],
                destination: null
            };
            document.getElementById('moveFilesSourceBtn').classList.remove('selected');
            document.getElementById('moveFilesSourceName').textContent = 'Click to select source folder';
            document.getElementById('moveFilesSelectContainer').style.display = 'none';
            document.getElementById('moveFilesList').innerHTML = '<div class="file-list-empty"><i class="fas fa-file"></i><p>No files found</p></div>';
            document.getElementById('moveFilesDestBtn').classList.remove('selected');
            document.getElementById('moveFilesDestBtn').disabled = true;
            document.getElementById('moveFilesDestName').textContent = 'Select files first';
            document.getElementById('moveFilesPreview').style.display = 'none';
            document.getElementById('moveFilesCountBadge').innerHTML = '<i class="fas fa-file"></i> 0';
            document.getElementById('confirmMoveFilesBtn').disabled = true;
            openModal('moveFilesModal');
        }
        
        // Select source folder for files
        async function selectMoveFilesSource() {
            try {
                const folderHandle = await window.showDirectoryPicker({ mode: 'readwrite' });
                moveFilesState.sourceFolder = folderHandle;
                moveFilesState.selectedFiles = [];
                document.getElementById('moveFilesSourceBtn').classList.add('selected');
                document.getElementById('moveFilesSourceName').textContent = folderHandle.name;
                document.getElementById('moveFilesCountBadge').innerHTML = '<i class="fas fa-file"></i> 0';
                document.getElementById('moveFilesDestBtn').disabled = true;
                document.getElementById('moveFilesDestName').textContent = 'Select files first';
                document.getElementById('confirmMoveFilesBtn').disabled = true;
                
                // List files only
                const files = [];
                for await (const entry of folderHandle.values()) {
                    if (entry.kind === 'file') {
                        files.push(entry.name);
                    }
                }
                
                if (files.length === 0) {
                    document.getElementById('moveFilesSelectContainer').style.display = 'block';
                    document.getElementById('moveFilesList').innerHTML = '<div class="file-list-empty"><i class="fas fa-file"></i><p>No files in this folder</p></div>';
                    return;
                }
                
                // Build file list
                let html = '';
                files.sort().forEach(name => {
                    html += `
                        <div class="file-list-item" onclick="toggleMoveFile(this, '${name.replace(/'/g, "\\'")}')">
                            <input type="checkbox" onclick="event.stopPropagation(); toggleMoveFile(this.parentElement, '${name.replace(/'/g, "\\'")}')">
                            <i class="fas fa-file file-icon"></i>
                            <span class="file-name">${name}</span>
                        </div>
                    `;
                });
                
                document.getElementById('moveFilesSelectContainer').style.display = 'block';
                document.getElementById('moveFilesList').innerHTML = html;
                
            } catch (err) {
                if (err.name !== 'AbortError') {
                    showToast('❌ Error selecting folder: ' + err.message, 'error');
                }
            }
        }
        
        // Toggle file selection for move
        function toggleMoveFile(element, fileName) {
            const checkbox = element.querySelector('input[type="checkbox"]');
            const isSelected = element.classList.toggle('selected');
            checkbox.checked = isSelected;
            
            if (isSelected) {
                if (!moveFilesState.selectedFiles.includes(fileName)) {
                    moveFilesState.selectedFiles.push(fileName);
                }
            } else {
                moveFilesState.selectedFiles = moveFilesState.selectedFiles.filter(f => f !== fileName);
            }
            
            // Update badge
            document.getElementById('moveFilesCountBadge').innerHTML = `<i class="fas fa-file"></i> ${moveFilesState.selectedFiles.length}`;
            
            // Update destination button state
            if (moveFilesState.selectedFiles.length > 0) {
                document.getElementById('moveFilesDestBtn').disabled = false;
                document.getElementById('moveFilesDestName').textContent = 'Click to select destination';
            } else {
                document.getElementById('moveFilesDestBtn').disabled = true;
                document.getElementById('moveFilesDestName').textContent = 'Select files first';
                document.getElementById('moveFilesDestBtn').classList.remove('selected');
                moveFilesState.destination = null;
            }
            
            updateMoveFilesPreview();
        }
        
        // Select destination folder for files
        async function selectMoveFilesDestination() {
            try {
                const folderHandle = await window.showDirectoryPicker({ mode: 'readwrite' });
                moveFilesState.destination = folderHandle;
                document.getElementById('moveFilesDestBtn').classList.add('selected');
                document.getElementById('moveFilesDestName').textContent = folderHandle.name;
                document.getElementById('previewFilesDestFolder').textContent = folderHandle.name;
                
                updateMoveFilesPreview();
                
            } catch (err) {
                if (err.name !== 'AbortError') {
                    showToast('❌ Error selecting destination: ' + err.message, 'error');
                }
            }
        }
        
        // Update move files preview
        function updateMoveFilesPreview() {
            const hasFiles = moveFilesState.selectedFiles.length > 0;
            const hasDest = moveFilesState.destination;
            
            document.getElementById('previewFilesCount').textContent = moveFilesState.selectedFiles.length;
            
            if (hasFiles && hasDest) {
                document.getElementById('moveFilesPreview').style.display = 'block';
                document.getElementById('confirmMoveFilesBtn').disabled = false;
            } else if (hasFiles) {
                document.getElementById('moveFilesPreview').style.display = 'block';
                document.getElementById('previewFilesDestFolder').textContent = '?';
                document.getElementById('confirmMoveFilesBtn').disabled = true;
            } else {
                document.getElementById('moveFilesPreview').style.display = 'none';
                document.getElementById('confirmMoveFilesBtn').disabled = true;
            }
        }
        
        // Confirm and execute file move
        async function confirmMoveFiles() {
            if (!moveFilesState.sourceFolder || moveFilesState.selectedFiles.length === 0 || !moveFilesState.destination) {
                showToast('❌ Please select files and destination', 'error');
                return;
            }
            
            const btn = document.getElementById('confirmMoveFilesBtn');
            const totalFiles = moveFilesState.selectedFiles.length;
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Moving...';
            
            let moved = 0;
            let errors = 0;
            let skipped = 0;
            
            try {
                for (const fileName of moveFilesState.selectedFiles) {
                    try {
                        // Check if file already exists in destination
                        let destFileName = fileName;
                        try {
                            await moveFilesState.destination.getFileHandle(fileName);
                            // File exists, skip or rename
                            skipped++;
                            continue;
                        } catch (e) {
                            // Good, doesn't exist
                        }
                        
                        // Get source file
                        const sourceHandle = await moveFilesState.sourceFolder.getFileHandle(fileName);
                        const sourceFile = await sourceHandle.getFile();
                        const content = await sourceFile.arrayBuffer();
                        
                        // Create in destination
                        const destHandle = await moveFilesState.destination.getFileHandle(fileName, { create: true });
                        const writable = await destHandle.createWritable();
                        await writable.write(content);
                        await writable.close();
                        
                        // Delete from source
                        await moveFilesState.sourceFolder.removeEntry(fileName);
                        
                        moved++;
                        btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Moving ${moved}/${totalFiles}...`;
                        
                    } catch (e) {
                        console.error('Error moving file:', fileName, e);
                        errors++;
                    }
                }
                
                closeModal('moveFilesModal');
                
                if (errors > 0 || skipped > 0) {
                    let msg = `Moved ${moved} file(s)`;
                    if (skipped > 0) msg += `, ${skipped} skipped (already exist)`;
                    if (errors > 0) msg += `, ${errors} error(s)`;
                    showToast(`⚠️ ${msg}`, 'warning');
                } else {
                    showToast(`✅ Successfully moved ${moved} file(s) to "${moveFilesState.destination.name}"!`, 'success');
                }
                
            } catch (err) {
                console.error('Move files error:', err);
                showToast('❌ Error: ' + err.message, 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-file-export"></i> Move Files';
            }
        }
        
        // ============================================
        // MOVE FOLDER FUNCTIONALITY
        // ============================================
        
        let moveFolderState = {
            sourceParent: null,
            selectedFolder: null,
            selectedFolderName: null,
            destination: null
        };
        
        // Open Move Folder Modal
        function openMoveFolderModal() {
            moveFolderState = {
                sourceParent: null,
                selectedFolder: null,
                selectedFolderName: null,
                destination: null
            };
            document.getElementById('moveSourceBtn').classList.remove('selected');
            document.getElementById('moveSourceName').textContent = 'Click to select source folder';
            document.getElementById('moveFolderSelectContainer').style.display = 'none';
            document.getElementById('moveFolderList').innerHTML = '<div class="file-list-empty"><i class="fas fa-folder-open"></i><p>No subfolders found</p></div>';
            document.getElementById('moveDestBtn').classList.remove('selected');
            document.getElementById('moveDestBtn').disabled = true;
            document.getElementById('moveDestName').textContent = 'Select source first';
            document.getElementById('movePreview').style.display = 'none';
            document.getElementById('confirmMoveBtn').disabled = true;
            openModal('moveFolderModal');
        }
        
        // Select source parent folder
        async function selectMoveSource() {
            try {
                const folderHandle = await window.showDirectoryPicker({ mode: 'readwrite' });
                moveFolderState.sourceParent = folderHandle;
                moveFolderState.selectedFolder = null;
                moveFolderState.selectedFolderName = null;
                document.getElementById('moveSourceBtn').classList.add('selected');
                document.getElementById('moveSourceName').textContent = folderHandle.name;
                
                // List subfolders
                const folders = [];
                for await (const entry of folderHandle.values()) {
                    if (entry.kind === 'directory') {
                        folders.push(entry.name);
                    }
                }
                
                if (folders.length === 0) {
                    document.getElementById('moveFolderSelectContainer').style.display = 'block';
                    document.getElementById('moveFolderList').innerHTML = '<div class="file-list-empty"><i class="fas fa-folder-open"></i><p>No subfolders in this folder</p></div>';
                    document.getElementById('moveDestBtn').disabled = true;
                    return;
                }
                
                // Build folder list
                let html = '';
                folders.sort().forEach(name => {
                    html += `
                        <div class="file-list-item" onclick="selectFolderToMove(this, '${name.replace(/'/g, "\\'")}')">
                            <input type="radio" name="moveFolder" onclick="event.stopPropagation(); selectFolderToMove(this.parentElement, '${name.replace(/'/g, "\\'")}')">
                            <i class="fas fa-folder file-icon" style="color: #f59e0b;"></i>
                            <span class="file-name">${name}</span>
                        </div>
                    `;
                });
                
                document.getElementById('moveFolderSelectContainer').style.display = 'block';
                document.getElementById('moveFolderList').innerHTML = html;
                
            } catch (err) {
                if (err.name !== 'AbortError') {
                    showToast('❌ Error selecting folder: ' + err.message, 'error');
                }
            }
        }
        
        // Select specific folder to move
        async function selectFolderToMove(element, folderName) {
            // Deselect all
            document.querySelectorAll('#moveFolderList .file-list-item').forEach(item => {
                item.classList.remove('selected');
                item.querySelector('input[type="radio"]').checked = false;
            });
            
            // Select this one
            element.classList.add('selected');
            element.querySelector('input[type="radio"]').checked = true;
            
            try {
                moveFolderState.selectedFolder = await moveFolderState.sourceParent.getDirectoryHandle(folderName);
                moveFolderState.selectedFolderName = folderName;
                
                // Enable destination selection
                document.getElementById('moveDestBtn').disabled = false;
                document.getElementById('moveDestName').textContent = 'Click to select destination';
                
                // Update preview
                document.getElementById('previewSourceFolder').textContent = folderName;
                updateMovePreview();
                
            } catch (err) {
                showToast('❌ Error accessing folder: ' + err.message, 'error');
            }
        }
        
        // Select destination folder
        async function selectMoveDestination() {
            try {
                const folderHandle = await window.showDirectoryPicker({ mode: 'readwrite' });
                moveFolderState.destination = folderHandle;
                document.getElementById('moveDestBtn').classList.add('selected');
                document.getElementById('moveDestName').textContent = folderHandle.name;
                document.getElementById('previewDestFolder').textContent = folderHandle.name;
                
                updateMovePreview();
                
            } catch (err) {
                if (err.name !== 'AbortError') {
                    showToast('❌ Error selecting destination: ' + err.message, 'error');
                }
            }
        }
        
        // Update move preview
        function updateMovePreview() {
            const hasSource = moveFolderState.selectedFolderName;
            const hasDest = moveFolderState.destination;
            
            if (hasSource && hasDest) {
                document.getElementById('movePreview').style.display = 'block';
                document.getElementById('confirmMoveBtn').disabled = false;
            } else if (hasSource) {
                document.getElementById('movePreview').style.display = 'block';
                document.getElementById('previewDestFolder').textContent = '?';
                document.getElementById('confirmMoveBtn').disabled = true;
            } else {
                document.getElementById('movePreview').style.display = 'none';
                document.getElementById('confirmMoveBtn').disabled = true;
            }
        }
        
        // Recursive function to copy directory contents
        async function copyDirectoryContents(sourceDir, destDir) {
            for await (const entry of sourceDir.values()) {
                if (entry.kind === 'file') {
                    // Copy file
                    const file = await entry.getFile();
                    const content = await file.arrayBuffer();
                    const newFile = await destDir.getFileHandle(entry.name, { create: true });
                    const writable = await newFile.createWritable();
                    await writable.write(content);
                    await writable.close();
                } else if (entry.kind === 'directory') {
                    // Create subdirectory and copy contents recursively
                    const newSubDir = await destDir.getDirectoryHandle(entry.name, { create: true });
                    await copyDirectoryContents(entry, newSubDir);
                }
            }
        }
        
        // Confirm and execute move
        async function confirmMoveFolder() {
            if (!moveFolderState.sourceParent || !moveFolderState.selectedFolder || !moveFolderState.destination) {
                showToast('❌ Please select both source folder and destination', 'error');
                return;
            }
            
            const btn = document.getElementById('confirmMoveBtn');
            const folderName = moveFolderState.selectedFolderName;
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Moving...';
            
            try {
                // Check if folder with same name exists in destination
                try {
                    await moveFolderState.destination.getDirectoryHandle(folderName);
                    showToast(`❌ A folder named "${folderName}" already exists in the destination`, 'error');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-exchange-alt"></i> Move Folder';
                    return;
                } catch (e) {
                    // Good, doesn't exist
                }
                
                // Create folder in destination
                const newFolder = await moveFolderState.destination.getDirectoryHandle(folderName, { create: true });
                
                // Copy all contents recursively
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Copying contents...';
                await copyDirectoryContents(moveFolderState.selectedFolder, newFolder);
                
                // Delete source folder
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Removing source...';
                await moveFolderState.sourceParent.removeEntry(folderName, { recursive: true });
                
                closeModal('moveFolderModal');
                showToast(`✅ Successfully moved "${folderName}" to "${moveFolderState.destination.name}"!`, 'success');
                
            } catch (err) {
                console.error('Move folder error:', err);
                showToast('❌ Error moving folder: ' + err.message, 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-exchange-alt"></i> Move Folder';
            }
        }
        
        // ============================================
        // DELETE FOLDER FUNCTIONALITY
        // ============================================
        
        // Open Delete Folder Modal
        function deleteFolderModal() {
            fileManagement.deleteFolderParent = null;
            fileManagement.deleteFolderSelected = [];
            document.getElementById('deleteFolderTargetBtn').classList.remove('selected');
            document.getElementById('deleteFolderTargetName').textContent = 'Click to select parent folder';
            document.getElementById('deleteFolderList').innerHTML = '<div class="file-list-empty"><i class="fas fa-folder-open"></i><p>Select a parent folder to see subfolders</p></div>';
            document.getElementById('deleteFolderCountBadge').innerHTML = '<i class="fas fa-folder"></i> 0';
            document.getElementById('confirmDeleteFolderBtn').disabled = true;
            openModal('deleteFolderModal');
        }
        
        // Select parent folder to list subfolders for deletion
        async function selectParentForFolderDelete() {
            try {
                const folderHandle = await window.showDirectoryPicker({ mode: 'readwrite' });
                fileManagement.deleteFolderParent = folderHandle;
                fileManagement.deleteFolderSelected = [];
                document.getElementById('deleteFolderTargetBtn').classList.add('selected');
                document.getElementById('deleteFolderTargetName').textContent = folderHandle.name;
                
                // List subfolders only
                const folders = [];
                for await (const entry of folderHandle.values()) {
                    if (entry.kind === 'directory') {
                        // Count contents inside each subfolder
                        let itemCount = 0;
                        try {
                            for await (const subEntry of entry.values()) {
                                itemCount++;
                            }
                        } catch (e) {
                            // Can't access, still show folder
                        }
                        folders.push({ name: entry.name, itemCount: itemCount });
                    }
                }
                
                if (folders.length === 0) {
                    document.getElementById('deleteFolderList').innerHTML = '<div class="file-list-empty"><i class="fas fa-folder-open"></i><p>No subfolders in this folder</p></div>';
                    document.getElementById('confirmDeleteFolderBtn').disabled = true;
                    return;
                }
                
                // Build folder list HTML
                let html = '';
                folders.sort((a, b) => a.name.localeCompare(b.name)).forEach(folder => {
                    const itemText = folder.itemCount === 0 ? 'empty' : `${folder.itemCount} item(s)`;
                    html += `
                        <div class="file-list-item" onclick="toggleDeleteFolderItem(this, '${folder.name.replace(/'/g, "\\'")}')">
                            <input type="checkbox" onclick="event.stopPropagation(); toggleDeleteFolderItem(this.parentElement, '${folder.name.replace(/'/g, "\\'")}')">
                            <i class="fas fa-folder file-icon" style="color: #f59e0b;"></i>
                            <span class="file-name">${folder.name}</span>
                            <span style="font-size: 0.7rem; color: var(--text-muted); margin-left: auto;">${itemText}</span>
                        </div>
                    `;
                });
                document.getElementById('deleteFolderList').innerHTML = html;
                
            } catch (err) {
                if (err.name !== 'AbortError') {
                    showToast('❌ Error selecting folder: ' + err.message, 'error');
                }
            }
        }
        
        // Toggle folder selection for deletion
        function toggleDeleteFolderItem(element, folderName) {
            const checkbox = element.querySelector('input[type="checkbox"]');
            const isSelected = element.classList.toggle('selected');
            checkbox.checked = isSelected;
            
            if (isSelected) {
                if (!fileManagement.deleteFolderSelected.includes(folderName)) {
                    fileManagement.deleteFolderSelected.push(folderName);
                }
            } else {
                fileManagement.deleteFolderSelected = fileManagement.deleteFolderSelected.filter(f => f !== folderName);
            }
            
            // Update badge and button
            document.getElementById('deleteFolderCountBadge').innerHTML = `<i class="fas fa-folder"></i> ${fileManagement.deleteFolderSelected.length}`;
            document.getElementById('confirmDeleteFolderBtn').disabled = fileManagement.deleteFolderSelected.length === 0;
        }
        
        // Confirm and delete selected folders
        async function confirmDeleteFolders() {
            if (!fileManagement.deleteFolderParent || fileManagement.deleteFolderSelected.length === 0) {
                showToast('❌ No folders selected', 'error');
                return;
            }
            
            const btn = document.getElementById('confirmDeleteFolderBtn');
            const totalToDelete = fileManagement.deleteFolderSelected.length;
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';
            
            let deleted = 0;
            let errors = 0;
            
            try {
                // Delete each selected folder with recursive: true
                for (const folderName of fileManagement.deleteFolderSelected) {
                    try {
                        await fileManagement.deleteFolderParent.removeEntry(folderName, { recursive: true });
                        deleted++;
                        // Update progress
                        btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Deleting ${deleted}/${totalToDelete}...`;
                    } catch (e) {
                        console.error('Error deleting folder:', folderName, e);
                        errors++;
                    }
                }
                
                closeModal('deleteFolderModal');
                
                if (errors > 0) {
                    showToast(`⚠️ Deleted ${deleted} folder(s), ${errors} error(s)`, 'warning');
                } else {
                    showToast(`✅ Successfully deleted ${deleted} folder(s) with all contents!`, 'success');
                }
                
            } catch (err) {
                console.error('Delete folders error:', err);
                showToast('❌ Error: ' + err.message, 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-trash-alt"></i> Delete Selected Folder(s)';
            }
        }
        
        // Select content option for new file
        function selectContentOption(option) {
            document.querySelectorAll('#createFileModal .content-option').forEach(opt => opt.classList.remove('selected'));
            event.currentTarget.classList.add('selected');
            document.getElementById('content' + option.charAt(0).toUpperCase() + option.slice(1)).checked = true;
        }
        
        // Confirm and create the file
        async function confirmCreateFile() {
            if (!fileManagement.createFolder) {
                showToast('❌ Please select a folder first', 'error');
                return;
            }
            
            const fileName = document.getElementById('newFileName').value.trim();
            if (!fileName) {
                showToast('❌ Please enter a file name', 'error');
                return;
            }
            
            try {
                // Check if file exists
                try {
                    await fileManagement.createFolder.getFileHandle(fileName);
                    showToast('❌ File already exists: ' + fileName, 'error');
                    return;
                } catch (e) {
                    // Good, file doesn't exist
                }
                
                // Create the file
                const fileHandle = await fileManagement.createFolder.getFileHandle(fileName, { create: true });
                const writable = await fileHandle.createWritable();
                
                const useEditorContent = document.getElementById('contentEditor').checked;
                if (useEditorContent) {
                    await writable.write(document.getElementById('promptEditor').value);
                } else {
                    await writable.write('');
                }
                await writable.close();
                
                closeModal('createFileModal');
                showToast(`✅ File created: ${fileName}`, 'success');
                
            } catch (err) {
                showToast('❌ Error creating file: ' + err.message, 'error');
            }
        }
        
        // Open Delete Files Modal
        function deleteSelectedFile() {
            fileManagement.deleteFolder = null;
            fileManagement.deleteFiles = [];
            document.getElementById('deleteFolderBtn').classList.remove('selected');
            document.getElementById('deleteFolderName').textContent = 'Click to select folder';
            document.getElementById('deleteFileList').innerHTML = '<div class="file-list-empty"><i class="fas fa-folder-open"></i><p>Select a folder to see files</p></div>';
            document.getElementById('deleteCountBadge').innerHTML = '<i class="fas fa-file"></i> 0';
            document.getElementById('confirmDeleteBtn').disabled = true;
            openModal('deleteFilesModal');
        }
        
        // Select folder for deleting files
        async function selectDeleteFolder() {
            try {
                const folderHandle = await window.showDirectoryPicker({ mode: 'readwrite' });
                fileManagement.deleteFolder = folderHandle;
                fileManagement.deleteFiles = [];
                document.getElementById('deleteFolderBtn').classList.add('selected');
                document.getElementById('deleteFolderName').textContent = folderHandle.name;
                
                // List files
                const files = [];
                for await (const entry of folderHandle.values()) {
                    if (entry.kind === 'file') {
                        files.push(entry.name);
                    }
                }
                
                if (files.length === 0) {
                    document.getElementById('deleteFileList').innerHTML = '<div class="file-list-empty"><i class="fas fa-file-excel"></i><p>No files in this folder</p></div>';
                    return;
                }
                
                // Build file list HTML
                let html = '';
                files.sort().forEach(file => {
                    html += `
                        <div class="file-list-item" onclick="toggleDeleteFile(this, '${file.replace(/'/g, "\\'")}')">
                            <input type="checkbox" onclick="event.stopPropagation(); toggleDeleteFile(this.parentElement, '${file.replace(/'/g, "\\'")}')">
                            <i class="fas fa-file file-icon"></i>
                            <span class="file-name">${file}</span>
                        </div>
                    `;
                });
                document.getElementById('deleteFileList').innerHTML = html;
                
            } catch (err) {
                if (err.name !== 'AbortError') {
                    showToast('❌ Error selecting folder: ' + err.message, 'error');
                }
            }
        }
        
        // Toggle file selection for deletion
        function toggleDeleteFile(element, fileName) {
            const checkbox = element.querySelector('input[type="checkbox"]');
            const isSelected = element.classList.toggle('selected');
            checkbox.checked = isSelected;
            
            if (isSelected) {
                if (!fileManagement.deleteFiles.includes(fileName)) {
                    fileManagement.deleteFiles.push(fileName);
                }
            } else {
                fileManagement.deleteFiles = fileManagement.deleteFiles.filter(f => f !== fileName);
            }
            
            // Update badge and button
            document.getElementById('deleteCountBadge').innerHTML = `<i class="fas fa-file"></i> ${fileManagement.deleteFiles.length}`;
            document.getElementById('confirmDeleteBtn').disabled = fileManagement.deleteFiles.length === 0;
        }
        
        // Confirm and delete selected files
        async function confirmDeleteFiles() {
            if (!fileManagement.deleteFolder || fileManagement.deleteFiles.length === 0) {
                showToast('❌ No files selected', 'error');
                return;
            }
            
            let deleted = 0;
            let errors = 0;
            
            for (const fileName of fileManagement.deleteFiles) {
                try {
                    await fileManagement.deleteFolder.removeEntry(fileName);
                    deleted++;
                } catch (err) {
                    console.error(`Error deleting ${fileName}:`, err);
                    errors++;
                }
            }
            
            closeModal('deleteFilesModal');
            
            if (errors > 0) {
                showToast(`⚠️ Deleted ${deleted} file(s), ${errors} error(s)`, 'warning');
            } else {
                showToast(`✅ ${deleted} file(s) deleted successfully!`, 'success');
            }
        }
        
        // Open Rename File Modal
        function renameSelectedFile() {
            fileManagement.renameFolder = null;
            fileManagement.renameSelectedFile = null;
            fileManagement.renameFiles = [];
            document.getElementById('renameFolderBtn').classList.remove('selected');
            document.getElementById('renameFolderName').textContent = 'Click to select folder';
            document.getElementById('renameFileList').innerHTML = '<div class="file-list-empty"><i class="fas fa-folder-open"></i><p>Select a folder to see files</p></div>';
            document.getElementById('newNameGroup').style.display = 'none';
            document.getElementById('renameNewName').value = '';
            document.getElementById('confirmRenameBtn').disabled = true;
            openModal('renameFileModal');
        }
        
        // Select folder for renaming file
        async function selectRenameFolder() {
            try {
                const folderHandle = await window.showDirectoryPicker({ mode: 'readwrite' });
                fileManagement.renameFolder = folderHandle;
                fileManagement.renameSelectedFile = null;
                fileManagement.renameFiles = [];
                document.getElementById('renameFolderBtn').classList.add('selected');
                document.getElementById('renameFolderName').textContent = folderHandle.name;
                document.getElementById('newNameGroup').style.display = 'none';
                document.getElementById('confirmRenameBtn').disabled = true;
                
                // List files
                for await (const entry of folderHandle.values()) {
                    if (entry.kind === 'file') {
                        fileManagement.renameFiles.push(entry.name);
                    }
                }
                
                if (fileManagement.renameFiles.length === 0) {
                    document.getElementById('renameFileList').innerHTML = '<div class="file-list-empty"><i class="fas fa-file-excel"></i><p>No files in this folder</p></div>';
                    return;
                }
                
                // Build file list HTML
                let html = '';
                fileManagement.renameFiles.sort().forEach(file => {
                    html += `
                        <div class="file-list-item" onclick="selectRenameFile(this, '${file.replace(/'/g, "\\'")}')">
                            <input type="radio" name="renameFile" onclick="event.stopPropagation(); selectRenameFile(this.parentElement, '${file.replace(/'/g, "\\'")}')">
                            <i class="fas fa-file file-icon"></i>
                            <span class="file-name">${file}</span>
                        </div>
                    `;
                });
                document.getElementById('renameFileList').innerHTML = html;
                
            } catch (err) {
                if (err.name !== 'AbortError') {
                    showToast('❌ Error selecting folder: ' + err.message, 'error');
                }
            }
        }
        
        // Select a file to rename
        function selectRenameFile(element, fileName) {
            // Deselect all
            document.querySelectorAll('#renameFileList .file-list-item').forEach(item => {
                item.classList.remove('selected');
                item.querySelector('input[type="radio"]').checked = false;
            });
            
            // Select this one
            element.classList.add('selected');
            element.querySelector('input[type="radio"]').checked = true;
            fileManagement.renameSelectedFile = fileName;
            
            // Show new name input
            document.getElementById('newNameGroup').style.display = 'block';
            document.getElementById('renameNewName').value = fileName;
            document.getElementById('renameNewName').focus();
            document.getElementById('confirmRenameBtn').disabled = false;
        }
        
        // Confirm and rename the file
        async function confirmRenameFile() {
            if (!fileManagement.renameFolder || !fileManagement.renameSelectedFile) {
                showToast('❌ Please select a file to rename', 'error');
                return;
            }
            
            const newName = document.getElementById('renameNewName').value.trim();
            if (!newName) {
                showToast('❌ Please enter a new file name', 'error');
                return;
            }
            
            if (newName === fileManagement.renameSelectedFile) {
                showToast('❌ New name is the same as the old name', 'error');
                return;
            }
            
            try {
                // Check if new name exists
                try {
                    await fileManagement.renameFolder.getFileHandle(newName);
                    showToast('❌ A file with that name already exists', 'error');
                    return;
                } catch (e) {
                    // Good, doesn't exist
                }
                
                // Read old file
                const oldHandle = await fileManagement.renameFolder.getFileHandle(fileManagement.renameSelectedFile);
                const oldFile = await oldHandle.getFile();
                const content = await oldFile.text();
                
                // Create new file
                const newHandle = await fileManagement.renameFolder.getFileHandle(newName, { create: true });
                const writable = await newHandle.createWritable();
                await writable.write(content);
                await writable.close();
                
                // Delete old file
                await fileManagement.renameFolder.removeEntry(fileManagement.renameSelectedFile);
                
                closeModal('renameFileModal');
                showToast(`✅ Renamed: ${fileManagement.renameSelectedFile} → ${newName}`, 'success');
                
            } catch (err) {
                showToast('❌ Error renaming file: ' + err.message, 'error');
            }
        }
        
        // Open modal helper
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }
        
        // ============================================
        // AUTO-SEND TIMER SYSTEM
        // ============================================
        
        let autoSendInterval = null;
        let countdownInterval = null;
        let countdownValue = 0;
        
        // Update auto-send timer based on input
        function updateAutoSendTimer() {
            const timerInput = document.getElementById('timerInput');
            const timerContainer = document.getElementById('autoSendTimer');
            const btnSend = document.getElementById('btnSendToFile');
            const countdownEl = document.getElementById('timerCountdown');
            
            const seconds = parseInt(timerInput.value) || 0;
            
            // Clear existing intervals
            if (autoSendInterval) {
                clearInterval(autoSendInterval);
                autoSendInterval = null;
            }
            if (countdownInterval) {
                clearInterval(countdownInterval);
                countdownInterval = null;
            }
            
            // Reset UI
            timerContainer.classList.remove('active');
            btnSend.classList.remove('auto-active');
            countdownEl.textContent = '0';
            
            if (seconds > 0 && promptFileHandle) {
                // Activate auto-send
                timerContainer.classList.add('active');
                btnSend.classList.add('auto-active');
                countdownValue = seconds;
                countdownEl.textContent = countdownValue;
                
                // Start countdown display
                countdownInterval = setInterval(() => {
                    countdownValue--;
                    if (countdownValue <= 0) {
                        countdownValue = seconds;
                    }
                    countdownEl.textContent = countdownValue;
                }, 1000);
                
                // Start auto-send interval
                autoSendInterval = setInterval(() => {
                    if (promptFileHandle) {
                        sendToPromptFile();
                        console.log('🔄 Auto-send triggered');
                    }
                }, seconds * 1000);
                
                showToast(`🔄 Auto-send enabled: every ${seconds} second${seconds > 1 ? 's' : ''}`, 'success');
            } else if (seconds > 0 && !promptFileHandle) {
                showToast('⚠️ Please connect a folder first to enable auto-send', 'warning');
                timerInput.value = 0;
            } else {
                // Timer disabled
                if (timerInput.value !== '0' && timerInput.value !== '') {
                    // User just set it to 0
                } else {
                    // Already at 0, no need for toast
                }
            }
            
            // Save timer value to localStorage
            localStorage.setItem('autoSendTimer', seconds.toString());
        }
        
        // Stop auto-send timer
        function stopAutoSendTimer() {
            const timerInput = document.getElementById('timerInput');
            const timerContainer = document.getElementById('autoSendTimer');
            const btnSend = document.getElementById('btnSendToFile');
            const countdownEl = document.getElementById('timerCountdown');
            
            if (autoSendInterval) {
                clearInterval(autoSendInterval);
                autoSendInterval = null;
            }
            if (countdownInterval) {
                clearInterval(countdownInterval);
                countdownInterval = null;
            }
            
            timerContainer.classList.remove('active');
            btnSend.classList.remove('auto-active');
            countdownEl.textContent = '0';
            timerInput.value = 0;
            localStorage.setItem('autoSendTimer', '0');
        }
        
        // Initialize auto-send timer from localStorage
        function initAutoSendTimer() {
            const savedTimer = localStorage.getItem('autoSendTimer');
            if (savedTimer && parseInt(savedTimer) > 0) {
                document.getElementById('timerInput').value = savedTimer;
                // Don't auto-start, just show the value - user needs to reconnect folder first
            }
        }
        
        // Initialize folder connection on page load
        document.addEventListener('DOMContentLoaded', () => {
            initFolderConnection();
            initAutoSendTimer();
            initSavedFileNames();
        });

        // Update character and word counts
        function updateCounts() {
            const text = document.getElementById('promptEditor').value;
            document.getElementById('charCount').textContent = text.length;
            document.getElementById('wordCount').textContent = text.trim() ? text.trim().split(/\s+/).length : 0;
        }

        // Setup event listeners
        function setupEventListeners() {
            // Editor input - update counts and record history
            document.getElementById('promptEditor').addEventListener('input', () => {
                updateCounts();
                recordHistoryDebounced(); // Record state after typing stops
            });
            
            // File upload - Drop Zone
            const dropZone = document.getElementById('dropZone');
            const fileInput = document.getElementById('fileInput');
            
            // Drop zone click - opens file picker
            dropZone.addEventListener('click', () => fileInput.click());
            
            dropZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                dropZone.classList.add('dragover');
            });
            
            dropZone.addEventListener('dragleave', () => {
                dropZone.classList.remove('dragover');
            });
            
            dropZone.addEventListener('drop', (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropZone.classList.remove('dragover');
                console.log('📁 Files dropped:', e.dataTransfer.files.length);
                if (e.dataTransfer.files.length > 0) {
                    handleFiles(e.dataTransfer.files);
                }
            });
            
            // Single/Multiple file input
            fileInput.addEventListener('change', (e) => {
                console.log('📁 Files selected via file picker:', e.target.files.length);
                if (e.target.files.length > 0) {
                    handleFiles(e.target.files);
                    fileInput.value = ''; // Reset for next selection
                }
            });

            // Search saved prompts
            document.getElementById('searchPrompts').addEventListener('input', (e) => {
                const searchTerm = e.target.value;
                const clearBtn = document.getElementById('savedSearchClear');
                
                // Show/hide clear button
                if (clearBtn) {
                    clearBtn.style.display = searchTerm ? 'flex' : 'none';
                }
                
                // Re-render with filter (if prompts already loaded)
                if (savedPromptsList.length > 0) {
                    renderSavedPrompts(searchTerm);
                } else {
                    loadSavedPrompts(searchTerm);
                }
            });
        }

        // Track files added to editor
        let editorFiles = new Map(); // filename -> {id, content, marker, isReference}
        
        // Current file mode: 'content' or 'reference'
        let currentFileMode = 'reference';

        // Check if we should send full content or just reference
        function shouldSendFullContent() {
            return currentFileMode === 'content';
        }
        
        // Set file mode (called by toggle buttons)
        function setFileMode(mode) {
            currentFileMode = mode;
            document.getElementById('fileContentToggle').value = mode;
            
            // Update button states
            const btnContent = document.getElementById('btnFullContent');
            const btnReference = document.getElementById('btnReference');
            
            if (mode === 'content') {
                btnContent.classList.add('active');
                btnReference.classList.remove('active');
                showToast('📄 Mode: Full Content - Files will be added with full content', 'info');
            } else {
                btnContent.classList.remove('active');
                btnReference.classList.add('active');
                showToast('🔗 Mode: Reference Only - Files will be added as references', 'info');
            }
            
            console.log('📁 File mode changed to:', mode);
        }
        
        // Handle file upload - IMMEDIATELY reads and appends to editor
        async function handleFiles(files) {
            console.log('📂 handleFiles called with', files.length, 'files');
            
            if (!files || files.length === 0) {
                console.log('No files to process');
                return;
            }
            
            const editor = document.getElementById('promptEditor');
            const dropZone = document.getElementById('dropZone');
            let filesProcessed = 0;
            
            // Show processing state
            dropZone.innerHTML = '<i class="fas fa-spinner fa-spin"></i><p>Processing files...</p>';
            
            // Process each file immediately
            for (const file of Array.from(files)) {
                console.log('📄 Processing file:', file.name, 'Type:', file.type, 'Size:', file.size);
                
                try {
                    const sendFullContent = shouldSendFullContent();
                    let content = '';
                    let isReference = false;
                    
                    if (sendFullContent) {
                        // Read file content immediately
                        content = await readFileAsText(file);
                        console.log('✅ File read successfully, content length:', content.length);
                    } else {
                        // Just create a reference - simple format
                        content = `[📎 ${file.name}]`;
                        isReference = true;
                        console.log('✅ File reference created:', file.name);
                    }
                    
                    // Create simple marker without timestamp
                    const marker = `<!-- FILE:${file.name} -->`;
                    
                    // Append to editor with spacing
                    let textToAdd = '';
                    if (editor.value.trim()) {
                        textToAdd = '\n\n';
                    }
                    
                    if (isReference) {
                        textToAdd += `${marker}\n${content}\n${marker.replace('<!--', '<!-- /END ')}`;
                    } else {
                        textToAdd += `${marker}\n## 📄 ${file.name}\n${content}\n${marker.replace('<!--', '<!-- /END ')}`;
                    }
                    
                    editor.value += textToAdd;
                    
                    // Track this file
                    editorFiles.set(file.name, {
                        marker: marker,
                        content: content,
                        isReference: isReference,
                        addedAt: Date.now()
                    });
                    
                    filesProcessed++;
                    
                } catch (err) {
                    console.error('❌ Error reading file:', file.name, err);
                    showToast(`Error reading ${file.name}`, 'error');
                }
            }
            
            // Restore drop zone
            dropZone.innerHTML = '<i class="fas fa-cloud-arrow-up"></i><span>Drop files here</span>';
            
            if (filesProcessed > 0) {
                updateCounts();
                recordHistoryState(true); // Record file upload in history
                const modeText = shouldSendFullContent() ? 'with full content' : 'as references';
                showToast(`✅ ${filesProcessed} file(s) added ${modeText}!`, 'success');
                
                // Save to server in background
                saveFilesToServer(files);
            } else {
                showToast('No files were processed', 'error');
            }
            
            // Reset file input so same file can be selected again
            document.getElementById('fileInput').value = '';
        }
        
        // Read file as text - simple and direct
        function readFileAsText(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    console.log('FileReader onload triggered');
                    resolve(e.target.result);
                };
                
                reader.onerror = function(e) {
                    console.error('FileReader error:', e);
                    reject(new Error('Failed to read file'));
                };
                
                // Always try to read as text first
                console.log('Starting to read file as text...');
                reader.readAsText(file);
            });
        }
        
        // Save files to server (background)
        async function saveFilesToServer(files) {
            const formData = new FormData();
            formData.append('action', 'upload_files');
            
            for (const file of Array.from(files)) {
                formData.append('files[]', file);
            }
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                if (data.success) {
                    loadUploadedFiles();
                }
            } catch (err) {
                console.error('Server upload error:', err);
            }
        }
        
        // Remove file content from editor
        function removeFileFromEditor(filename) {
            const editor = document.getElementById('promptEditor');
            const fileData = editorFiles.get(filename);
            
            if (fileData) {
                // Remove the file section from editor
                const startMarker = fileData.marker;
                const endMarker = startMarker.replace('<!--', '<!-- /END ');
                
                const startIdx = editor.value.indexOf(startMarker);
                if (startIdx !== -1) {
                    const endIdx = editor.value.indexOf(endMarker);
                    if (endIdx !== -1) {
                        // Remove from start marker to end marker (including markers)
                        const before = editor.value.substring(0, startIdx);
                        const after = editor.value.substring(endIdx + endMarker.length);
                        editor.value = (before + after).replace(/\n{3,}/g, '\n\n').trim();
                    }
                }
                
                editorFiles.delete(filename);
                updateCounts();
                showToast(`📄 ${filename} removed from editor`, 'info');
            }
        }

        // Load uploaded files
        async function loadUploadedFiles() {
            try {
                const formData = new FormData();
                formData.append('action', 'get_files');
                
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                const container = document.getElementById('uploadedFiles');
                const header = document.getElementById('uploadedFilesHeader');
                const countSpan = document.getElementById('filesCount');
                
                if (data.success && data.files.length > 0) {
                    // Show header and update count
                    header.style.display = 'flex';
                    countSpan.textContent = data.files.length;
                    
                    container.innerHTML = data.files.map(file => {
                        const isChecked = editorFiles.has(file.filename);
                        return `
                        <div class="file-item ${isChecked ? 'checked' : ''}" data-filename="${escapeHtml(file.filename)}" data-filepath="${escapeHtml(file.filepath)}" data-fileid="${file.id}">
                            <div class="file-item-checkbox" onclick="toggleFileCheckbox('${escapeHtml(file.filepath)}', '${escapeHtml(file.filename)}', ${file.filesize || 0})">
                                <input type="checkbox" ${isChecked ? 'checked' : ''}>
                                <div class="checkbox-box"><i class="fas fa-check"></i></div>
                            </div>
                            <i class="fas fa-file-alt file-item-icon"></i>
                            <div class="file-info">
                                <div class="file-name">${escapeHtml(file.filename)}</div>
                                <div class="file-size">${formatFileSize(file.filesize)}</div>
                            </div>
                            <button class="file-delete" onclick="event.stopPropagation(); deleteFile(${file.id}, '${escapeHtml(file.filename)}')" title="Delete file">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    `}).join('');
                    
                    // Update Select All checkbox state
                    updateSelectAllFilesCheckbox();
                } else {
                    // Hide header when no files
                    header.style.display = 'none';
                    container.innerHTML = '';
                    updateSelectAllFilesCheckbox();
                }
            } catch (err) {
                console.error('Error loading files:', err);
            }
        }

        // Load file content to editor (for previously uploaded files)
        async function loadFileToEditor(filepath, filename, filesize = 0) {
            console.log('📂 Loading file from server:', filename, filepath);
            
            // Check if already in editor
            if (editorFiles.has(filename)) {
                showToast(`${filename} is already in editor`, 'info');
                return;
            }
            
            const sendFullContent = shouldSendFullContent();
            const editor = document.getElementById('promptEditor');
            
            try {
                let content = '';
                let isReference = false;
                
                if (sendFullContent) {
                    const response = await fetch(filepath);
                    
                    if (response.ok) {
                        content = await response.text();
                    } else {
                        showToast('Could not load file content', 'error');
                        return;
                    }
                } else {
                    // Just create a reference - extract direct parent folder from path
                    const folderMatch = filepath.match(/\/([^\/]+)\/[^\/]+$/);
                    const folder = folderMatch ? folderMatch[1] : 'root';
                    content = `[📎 ${filename} | Folder: ${folder}]`;
                    isReference = true;
                }
                
                // Create simple marker without timestamp
                const marker = `<!-- FILE:${filename} -->`;
                
                // Append with spacing
                let textToAdd = '';
                if (editor.value.trim()) {
                    textToAdd = '\n\n';
                }
                
                if (isReference) {
                    textToAdd += `${marker}\n${content}\n${marker.replace('<!--', '<!-- /END ')}`;
                } else {
                    textToAdd += `${marker}\n## 📄 ${filename}\n${content}\n${marker.replace('<!--', '<!-- /END ')}`;
                }
                
                editor.value += textToAdd;
                
                // Track this file
                editorFiles.set(filename, {
                    marker: marker,
                    content: content,
                    isReference: isReference,
                    addedAt: Date.now()
                });
                
                updateCounts();
                const modeText = isReference ? '(reference)' : '(full content)';
                showToast(`✅ ${filename} added ${modeText}!`, 'success');
                
            } catch (err) {
                showToast('Error loading file', 'error');
                console.error(err);
            }
        }

        // Toggle file checkbox - adds or removes file from editor
        async function toggleFileCheckbox(filepath, filename, filesize = 0) {
            const fileItem = document.querySelector(`.file-item[data-filename="${filename}"]`);
            const editor = document.getElementById('promptEditor');
            
            if (editorFiles.has(filename)) {
                // File is in editor - remove it
                removeFileFromEditor(filename);
                
                if (fileItem) {
                    fileItem.classList.remove('checked');
                    const checkbox = fileItem.querySelector('input[type="checkbox"]');
                    if (checkbox) checkbox.checked = false;
                }
                
                showToast(`📄 ${filename} removed from editor`, 'info');
            } else {
                // File not in editor - add it
                const sendFullContent = shouldSendFullContent();
                
                try {
                    let content = '';
                    let isReference = false;
                    
                    if (sendFullContent) {
                        const response = await fetch(filepath);
                        
                        if (response.ok) {
                            content = await response.text();
                        } else {
                            showToast('Could not load file content', 'error');
                            return;
                        }
                    } else {
                        // Just create a reference - extract direct parent folder from path
                        const folderMatch = filepath.match(/\/([^\/]+)\/[^\/]+$/);
                        const folder = folderMatch ? folderMatch[1] : 'root';
                        content = `[📎 ${filename} | Folder: ${folder}]`;
                        isReference = true;
                    }
                    
                    // Create simple marker without timestamp
                    const marker = `<!-- FILE:${filename} -->`;
                    
                    // Append with spacing
                    let textToAdd = '';
                    if (editor.value.trim()) {
                        textToAdd = '\n\n';
                    }
                    
                    if (isReference) {
                        textToAdd += `${marker}\n${content}\n${marker.replace('<!--', '<!-- /END ')}`;
                    } else {
                        textToAdd += `${marker}\n## 📄 ${filename}\n${content}\n${marker.replace('<!--', '<!-- /END ')}`;
                    }
                    
                    editor.value += textToAdd;
                    
                    // Track this file
                    editorFiles.set(filename, {
                        marker: marker,
                        content: content,
                        isReference: isReference,
                        addedAt: Date.now()
                    });
                    
                    if (fileItem) {
                        fileItem.classList.add('checked');
                        const checkbox = fileItem.querySelector('input[type="checkbox"]');
                        if (checkbox) checkbox.checked = true;
                    }
                    
                    updateCounts();
                    const modeText = isReference ? '(reference)' : '(full content)';
                    showToast(`✅ ${filename} added ${modeText}!`, 'success');
                    
                } catch (err) {
                    showToast('Error loading file', 'error');
                    console.error(err);
                }
            }
            
            updateCounts();
            updateSelectAllFilesCheckbox();
        }

        // Toggle all uploaded files (Select All checkbox handler)
        async function toggleAllUploadedFiles(checked) {
            const fileItems = document.querySelectorAll('.file-item');
            
            if (fileItems.length === 0) {
                showToast('No files to select', 'info');
                return;
            }
            
            let processedCount = 0;
            const sendFullContent = shouldSendFullContent();
            
            for (const fileItem of fileItems) {
                const filename = fileItem.dataset.filename;
                const filepath = fileItem.dataset.filepath;
                const isCurrentlyChecked = editorFiles.has(filename);
                
                if (checked && !isCurrentlyChecked) {
                    // Add file to editor
                    try {
                        let content = '';
                        let isReference = false;
                        
                        if (sendFullContent) {
                            const response = await fetch(filepath);
                            if (response.ok) {
                                content = await response.text();
                            } else {
                                continue;
                            }
                        } else {
                            // Just create a reference - extract direct parent folder from path
                            const folderMatch = filepath.match(/\/([^\/]+)\/[^\/]+$/);
                            const folder = folderMatch ? folderMatch[1] : 'root';
                            content = `[📎 ${filename} | Folder: ${folder}]`;
                            isReference = true;
                        }
                        
                        const marker = `<!-- FILE:${filename} -->`;
                        const endMarker = `<!-- /END  FILE:${filename} -->`;
                        const editor = document.getElementById('promptEditor');
                        
                        // Add to editor
                        const currentContent = editor.value;
                        const newContent = currentContent 
                            ? currentContent + '\n\n' + marker + '\n' + content + '\n' + endMarker
                            : marker + '\n' + content + '\n' + endMarker;
                        editor.value = newContent;
                        
                        // Track the file
                        editorFiles.set(filename, {
                            id: Date.now(),
                            content: content,
                            marker: marker,
                            isReference: isReference,
                            addedAt: Date.now()
                        });
                        
                        fileItem.classList.add('checked');
                        const checkbox = fileItem.querySelector('input[type="checkbox"]');
                        if (checkbox) checkbox.checked = true;
                        
                        processedCount++;
                    } catch (err) {
                        console.error('Error adding file:', filename, err);
                    }
                } else if (!checked && isCurrentlyChecked) {
                    // Remove file from editor
                    removeFileFromEditor(filename);
                    fileItem.classList.remove('checked');
                    const checkbox = fileItem.querySelector('input[type="checkbox"]');
                    if (checkbox) checkbox.checked = false;
                    processedCount++;
                }
            }
            
            updateCounts();
            
            if (checked) {
                const modeText = sendFullContent ? 'with full content' : 'as references';
                showToast(`✅ ${processedCount} file(s) added ${modeText}!`, 'success');
            } else {
                showToast(`📄 ${processedCount} file(s) removed from editor`, 'info');
            }
        }
        
        // Update the "Select All Files" checkbox based on current selection
        function updateSelectAllFilesCheckbox() {
            const checkbox = document.getElementById('selectAllFilesCheckbox');
            if (!checkbox) return;
            
            const fileItems = document.querySelectorAll('.file-item');
            const totalFiles = fileItems.length;
            
            if (totalFiles === 0) {
                checkbox.checked = false;
                checkbox.indeterminate = false;
                return;
            }
            
            let checkedCount = 0;
            fileItems.forEach(item => {
                if (item.classList.contains('checked')) {
                    checkedCount++;
                }
            });
            
            if (checkedCount === 0) {
                checkbox.checked = false;
                checkbox.indeterminate = false;
            } else if (checkedCount === totalFiles) {
                checkbox.checked = true;
                checkbox.indeterminate = false;
            } else {
                checkbox.checked = false;
                checkbox.indeterminate = true;
            }
        }

        // Delete file - removes from server AND from editor
        function deleteFile(id, filename) {
            showConfirmModal({
                title: 'Delete File?',
                message: `Are you sure you want to delete "${filename}"?`,
                details: `<span class="file-count">1</span> file will be removed from the list and editor`,
                icon: 'fa-file-times',
                type: 'warning',
                confirmText: 'Delete File',
                confirmIcon: 'fa-trash-alt',
                onConfirm: async () => {
                    // Remove from editor first (instant feedback)
                    removeFileFromEditor(filename);
                    
                    // Then delete from server
                    try {
                        const formData = new FormData();
                        formData.append('action', 'delete_file');
                        formData.append('id', id);
                        
                        const response = await fetch('', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            showToast('✅ File deleted!', 'success');
                            loadUploadedFiles();
                        }
                    } catch (err) {
                        showToast('Delete from server failed!', 'error');
                    }
                }
            });
        }
        
        // Delete ALL files - removes all from server AND from editor
        async function deleteAllFiles() {
            // Get file count from the displayed list
            const filesCountEl = document.getElementById('filesCount');
            const displayedCount = parseInt(filesCountEl?.textContent || '0');
            const editorCount = editorFiles.size;
            const totalCount = Math.max(displayedCount, editorCount);
            
            if (totalCount === 0) {
                showToast('No files to delete', 'info');
                return;
            }
            
            showConfirmModal({
                title: 'Delete All Files?',
                message: 'This action cannot be undone. All files will be permanently removed.',
                details: `<span class="file-count">${totalCount}</span> file(s) will be deleted from the list<br>and their content removed from the editor`,
                icon: 'fa-trash-alt',
                type: 'danger',
                confirmText: 'Delete All',
                confirmIcon: 'fa-trash-alt',
                onConfirm: async () => {
                    // Remove all files from editor first (instant feedback)
                    const filenames = Array.from(editorFiles.keys());
                    for (const filename of filenames) {
                        removeFileFromEditor(filename);
                    }
                    
                    // Clear the editorFiles map
                    editorFiles.clear();
                    
                    // Delete all from server
                    try {
                        const formData = new FormData();
                        formData.append('action', 'get_files');
                        
                        const response = await fetch('', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const data = await response.json();
                        
                        if (data.success && data.files) {
                            // Delete each file from server
                            for (const file of data.files) {
                                const deleteForm = new FormData();
                                deleteForm.append('action', 'delete_file');
                                deleteForm.append('id', file.id);
                                await fetch('', { method: 'POST', body: deleteForm });
                            }
                        }
                        
                        showToast(`✅ All ${totalCount} file(s) deleted!`, 'success');
                        loadUploadedFiles();
                        
                    } catch (err) {
                        console.error('Delete all error:', err);
                        showToast('Some files may not have been deleted from server', 'error');
                        loadUploadedFiles();
                    }
                }
            });
        }

        // Format file size
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // Open save modal
        function openSaveModal(id = null, title = '', content = null) {
            const modal = document.getElementById('saveModal');
            const titleInput = document.getElementById('promptTitle');
            const contentInput = document.getElementById('promptContent');
            const editIdInput = document.getElementById('editPromptId');
            
            if (id) {
                editIdInput.value = id;
                titleInput.value = title;
                contentInput.value = content;
                document.querySelector('#saveModal h3').innerHTML = '<i class="fas fa-edit"></i> Edit Prompt';
            } else {
                editIdInput.value = '';
                titleInput.value = '';
                contentInput.value = document.getElementById('promptEditor').value;
                document.querySelector('#saveModal h3').innerHTML = '<i class="fas fa-save"></i> Save Prompt';
            }
            
            modal.classList.add('active');
        }

        // Close modal
        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
        }

        // Save prompt
        async function savePrompt() {
            const title = document.getElementById('promptTitle').value.trim();
            const content = document.getElementById('promptContent').value.trim();
            const editId = document.getElementById('editPromptId').value;
            
            if (!title || !content) {
                showToast('Title and content required!', 'error');
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('action', editId ? 'update_prompt' : 'save_prompt');
                formData.append('title', title);
                formData.append('content', content);
                if (editId) formData.append('id', editId);
                
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Record operation speed
                    if (data.operationTime) {
                        addSpeedEntry({
                            time: data.operationTime,
                            type: data.operationType || (editId ? 'UPDATE' : 'ADD'),
                            connection: data.connectionType || currentConnectionType,
                            timestamp: Date.now()
                        });
                        updateSpeedMonitor();
                    }
                    showToast(`${data.message} ⏱️ ${data.operationTime}ms`, 'success');
                    closeModal('saveModal');
                    loadSavedPrompts();
                } else {
                    showToast(data.message || 'Save failed!', 'error');
                }
            } catch (err) {
                showToast('Save error!', 'error');
            }
        }

        // Load saved prompts
        async function loadSavedPrompts(search = '') {
            try {
                const formData = new FormData();
                formData.append('action', 'get_prompts');
                formData.append('search', search);
                
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                const container = document.getElementById('savedList');
                const clearBtn = document.getElementById('savedSearchClear');
                
                // Show/hide clear button
                if (clearBtn) {
                    clearBtn.style.display = search ? 'flex' : 'none';
                }
                
                if (data.success && data.prompts.length > 0) {
                    // Store prompts in the global array
                    savedPromptsList = data.prompts.map(p => ({
                        id: parseInt(p.id),
                        title: p.title,
                        content: p.content,
                        created_at: p.created_at
                    }));
                    
                    renderSavedPrompts(search);
                } else {
                    savedPromptsList = [];
                    container.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p>No saved prompts yet</p>
                        </div>
                    `;
                    updateSavedCounter();
                    setTimeout(updateSavedScrollbar, 100);
                }
            } catch (err) {
                console.error('Error loading prompts:', err);
            }
        }
        
        // Render saved prompts with checkbox style (like prompt templates)
        function renderSavedPrompts(searchTerm = '') {
            const container = document.getElementById('savedList');
            const searchLower = searchTerm.toLowerCase().trim();
            
            // Filter prompts based on search
            const filteredPrompts = searchLower 
                ? savedPromptsList.filter(p => 
                    p.title.toLowerCase().includes(searchLower) || 
                    p.content.toLowerCase().includes(searchLower)
                )
                : savedPromptsList;
            
            if (filteredPrompts.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-search"></i>
                        <p>No prompts found</p>
                    </div>
                `;
                updateSavedCounter();
                return;
            }
            
            container.innerHTML = filteredPrompts.map(prompt => {
                const isChecked = activeSavedPrompts.has(prompt.id);
                const highlightedTitle = searchLower 
                    ? highlightText(prompt.title, searchLower)
                    : prompt.title;
                const contentPreview = prompt.content.replace(/\n/g, ' ').substring(0, 50) + '...';
                
                return `
                    <div class="saved-item ${isChecked ? 'checked' : ''}" data-id="${prompt.id}">
                        <div class="saved-item-checkbox" onclick="toggleSavedPrompt(${prompt.id})">
                            <input type="checkbox" ${isChecked ? 'checked' : ''}>
                            <div class="checkbox-box"><i class="fas fa-check"></i></div>
                        </div>
                        <div class="saved-item-content" onclick="openSavedPreview(${prompt.id})">
                            <div class="saved-item-name">${highlightedTitle}</div>
                            <div class="saved-item-preview">${escapeHtmlDisplay(contentPreview)}</div>
                            <div class="saved-item-date">${formatDate(prompt.created_at)}</div>
                        </div>
                        <div class="saved-item-actions">
                            <button type="button" class="saved-action-icon copy" onclick="copySavedPrompt(${prompt.id})" title="Copy">
                                <i class="fas fa-copy"></i>
                            </button>
                            <button type="button" class="saved-action-icon pull" onclick="pullToSavedPrompt(${prompt.id})" title="Pull from Editor">
                                <i class="fas fa-arrow-down"></i>
                            </button>
                            <button type="button" class="saved-action-icon edit" onclick="editSavedPrompt(${prompt.id})" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="saved-action-icon delete" onclick="deletePrompt(${prompt.id})" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
            }).join('');
            
            updateSavedCounter();
            
            // Update scrollbar after content change
            setTimeout(updateSavedScrollbar, 100);
        }
        
        // Escape HTML for display (without breaking newlines)
        function escapeHtmlDisplay(str) {
            if (!str) return '';
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }
        
        // Toggle saved prompt in editor
        function toggleSavedPrompt(id) {
            const prompt = savedPromptsList.find(p => p.id === id);
            if (!prompt) return;
            
            const savedItem = document.querySelector(`.saved-item[data-id="${id}"]`);
            const editor = document.getElementById('promptEditor');
            
            if (activeSavedPrompts.has(id)) {
                activeSavedPrompts.delete(id);
                if (savedItem) {
                    savedItem.classList.remove('checked');
                    const checkbox = savedItem.querySelector('input[type="checkbox"]');
                    if (checkbox) checkbox.checked = false;
                }
                rebuildEditorFromSaved();
                showToast(`"${prompt.title}" removed`, 'info');
            } else {
                activeSavedPrompts.add(id);
                if (savedItem) {
                    savedItem.classList.add('checked');
                    const checkbox = savedItem.querySelector('input[type="checkbox"]');
                    if (checkbox) checkbox.checked = true;
                }
                
                // Append to editor
                if (editor.value.trim()) {
                    editor.value += '\n\n' + prompt.content;
                } else {
                    editor.value = prompt.content;
                }
                
                showToast(`"${prompt.title}" added`, 'success');
            }
            
            updateCounts();
            updateSavedCounter();
            updateSelectAllSavedCheckbox();
            recordHistoryState(true); // Record saved prompt toggle in history
        }
        
        // Custom Scrollbar for Saved Prompts
        let savedScrollbar = {
            list: null,
            track: null,
            thumb: null,
            isDragging: false,
            startX: 0,
            startScrollLeft: 0
        };
        
        function updateSavedScrollbar() {
            const list = savedScrollbar.list || document.getElementById('savedList');
            const track = savedScrollbar.track || document.getElementById('savedScrollbarTrack');
            const thumb = savedScrollbar.thumb || document.getElementById('savedScrollbarThumb');
            
            if (!list || !track || !thumb) return;
            
            // Store references
            savedScrollbar.list = list;
            savedScrollbar.track = track;
            savedScrollbar.thumb = thumb;
            
            // Wait a frame for proper dimensions
            requestAnimationFrame(() => {
                const listWidth = list.scrollWidth;
                const viewWidth = list.clientWidth;
                const trackWidth = track.clientWidth;
                
                console.log('Scrollbar update:', { listWidth, viewWidth, trackWidth });
                
                // Check if scrolling is needed
                if (listWidth <= viewWidth || listWidth === 0 || trackWidth === 0) {
                    track.style.opacity = '0.3';
                    thumb.style.width = '100%';
                    thumb.style.left = '0px';
                    return;
                }
                
                track.style.opacity = '1';
                
                // Calculate thumb width (proportional to visible area)
                const ratio = viewWidth / listWidth;
                const thumbWidth = Math.max(60, Math.min(trackWidth - 20, ratio * trackWidth));
                thumb.style.width = thumbWidth + 'px';
                
                // Calculate thumb position
                const maxScroll = listWidth - viewWidth;
                const scrollRatio = maxScroll > 0 ? (list.scrollLeft / maxScroll) : 0;
                const maxThumbLeft = trackWidth - thumbWidth;
                const thumbLeft = Math.max(0, Math.min(maxThumbLeft, scrollRatio * maxThumbLeft));
                thumb.style.left = thumbLeft + 'px';
            });
        }
        
        (function initSavedScrollbar() {
            document.addEventListener('DOMContentLoaded', () => {
                const list = document.getElementById('savedList');
                const track = document.getElementById('savedScrollbarTrack');
                const thumb = document.getElementById('savedScrollbarThumb');
                
                if (!list || !track || !thumb) return;
                
                savedScrollbar.list = list;
                savedScrollbar.track = track;
                savedScrollbar.thumb = thumb;
                
                // Sync list scroll when dragging thumb
                function onThumbMouseDown(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    savedScrollbar.isDragging = true;
                    savedScrollbar.startX = e.clientX;
                    savedScrollbar.startScrollLeft = list.scrollLeft;
                    thumb.classList.add('dragging');
                    document.body.style.userSelect = 'none';
                    document.body.style.cursor = 'grabbing';
                }
                
                function onMouseMove(e) {
                    if (!savedScrollbar.isDragging) return;
                    
                    const deltaX = e.clientX - savedScrollbar.startX;
                    const trackWidth = track.clientWidth;
                    const thumbWidth = thumb.clientWidth;
                    const maxThumbLeft = trackWidth - thumbWidth;
                    const listWidth = list.scrollWidth;
                    const viewWidth = list.clientWidth;
                    const maxScroll = listWidth - viewWidth;
                    
                    if (maxThumbLeft > 0) {
                        const scrollDelta = (deltaX / maxThumbLeft) * maxScroll;
                        list.scrollLeft = savedScrollbar.startScrollLeft + scrollDelta;
                    }
                }
                
                function onMouseUp() {
                    if (savedScrollbar.isDragging) {
                        savedScrollbar.isDragging = false;
                        thumb.classList.remove('dragging');
                        document.body.style.userSelect = '';
                        document.body.style.cursor = '';
                    }
                }
                
                // Click on track to jump
                function onTrackClick(e) {
                    if (e.target === thumb) return;
                    
                    const trackRect = track.getBoundingClientRect();
                    const clickX = e.clientX - trackRect.left;
                    const trackWidth = track.clientWidth;
                    const thumbWidth = thumb.clientWidth;
                    const listWidth = list.scrollWidth;
                    const viewWidth = list.clientWidth;
                    const maxScroll = listWidth - viewWidth;
                    
                    if (maxScroll <= 0) return;
                    
                    const targetRatio = Math.max(0, Math.min(1, (clickX - thumbWidth / 2) / (trackWidth - thumbWidth)));
                    const targetScroll = targetRatio * maxScroll;
                    
                    list.scrollTo({
                        left: targetScroll,
                        behavior: 'smooth'
                    });
                }
                
                // Event listeners
                thumb.addEventListener('mousedown', onThumbMouseDown);
                document.addEventListener('mousemove', onMouseMove);
                document.addEventListener('mouseup', onMouseUp);
                track.addEventListener('click', onTrackClick);
                
                // Update on list scroll
                list.addEventListener('scroll', updateSavedScrollbar);
                
                // Update on window resize
                window.addEventListener('resize', updateSavedScrollbar);
                
                // Initial update with delays
                setTimeout(updateSavedScrollbar, 200);
                setTimeout(updateSavedScrollbar, 500);
                setTimeout(updateSavedScrollbar, 1000);
                
                // MutationObserver for content changes
                const observer = new MutationObserver(() => {
                    setTimeout(updateSavedScrollbar, 50);
                });
                observer.observe(list, { childList: true, subtree: true });
            });
        })();
        
        // Rebuild editor from active saved prompts
        function rebuildEditorFromSaved() {
            const editor = document.getElementById('promptEditor');
            const contents = [];
            
            // First add active template prompts
            promptTemplates.forEach(prompt => {
                if (activePrompts.has(prompt.id)) {
                    contents.push(prompt.content);
                }
            });
            
            // Then add active saved prompts
            savedPromptsList.forEach(prompt => {
                if (activeSavedPrompts.has(prompt.id)) {
                    contents.push(prompt.content);
                }
            });
            
            editor.value = contents.join('\n\n');
            updateCounts();
            recordHistoryState(true); // Record rebuild in history
        }
        
        // Update saved prompts counter
        function updateSavedCounter() {
            const counter = document.getElementById('savedCounter');
            const total = savedPromptsList.length;
            const selected = activeSavedPrompts.size;
            counter.textContent = `${selected}/${total}`;
            
            // Change color based on selection
            if (selected === 0) {
                counter.style.background = 'rgba(100, 100, 100, 0.15)';
                counter.style.color = 'var(--text-muted)';
            } else if (selected === total && total > 0) {
                counter.style.background = 'rgba(16, 185, 129, 0.15)';
                counter.style.color = 'var(--success)';
            } else {
                counter.style.background = 'rgba(16, 185, 129, 0.15)';
                counter.style.color = 'var(--success)';
            }
        }
        
        // Select all saved prompts
        function selectAllSavedPrompts() {
            const searchTerm = document.getElementById('searchPrompts').value.toLowerCase().trim();
            const promptsToSelect = searchTerm 
                ? savedPromptsList.filter(p => 
                    p.title.toLowerCase().includes(searchTerm) || 
                    p.content.toLowerCase().includes(searchTerm)
                )
                : savedPromptsList;
            
            const editor = document.getElementById('promptEditor');
            let addedCount = 0;
            
            promptsToSelect.forEach(prompt => {
                if (!activeSavedPrompts.has(prompt.id)) {
                    activeSavedPrompts.add(prompt.id);
                    
                    // Append to editor
                    if (editor.value.trim()) {
                        editor.value += '\n\n';
                    }
                    editor.value += prompt.content;
                    addedCount++;
                }
            });
            
            renderSavedPrompts(searchTerm);
            updateCounts();
            updateSelectAllSavedCheckbox();
            
            if (addedCount > 0) {
                showToast(`✅ ${addedCount} prompt(s) added to editor`, 'success');
            } else {
                showToast('All visible prompts already selected', 'info');
            }
        }
        
        // Deselect all saved prompts
        function deselectAllSavedPrompts() {
            const searchTerm = document.getElementById('searchPrompts').value.toLowerCase().trim();
            const promptsToDeselect = searchTerm 
                ? savedPromptsList.filter(p => 
                    p.title.toLowerCase().includes(searchTerm) || 
                    p.content.toLowerCase().includes(searchTerm)
                )
                : savedPromptsList;
            
            let removedCount = 0;
            
            promptsToDeselect.forEach(prompt => {
                if (activeSavedPrompts.has(prompt.id)) {
                    activeSavedPrompts.delete(prompt.id);
                    removedCount++;
                }
            });
            
            rebuildEditorFromSaved();
            renderSavedPrompts(searchTerm);
            updateCounts();
            updateSelectAllSavedCheckbox();
            
            if (removedCount > 0) {
                showToast(`🗑️ ${removedCount} prompt(s) removed from editor`, 'info');
            } else {
                showToast('No prompts to deselect', 'info');
            }
        }
        
        // Toggle all saved prompts (checkbox handler)
        function toggleAllSavedPrompts(checked) {
            if (checked) {
                selectAllSavedPrompts();
            } else {
                deselectAllSavedPrompts();
            }
        }
        
        // Update the "Select All" checkbox for saved prompts based on current selection
        function updateSelectAllSavedCheckbox() {
            const checkbox = document.getElementById('selectAllSavedCheckbox');
            if (!checkbox) return;
            
            const searchTerm = document.getElementById('searchPrompts')?.value.toLowerCase().trim() || '';
            const visiblePrompts = searchTerm 
                ? savedPromptsList.filter(p => 
                    p.title.toLowerCase().includes(searchTerm) || 
                    p.content.toLowerCase().includes(searchTerm)
                )
                : savedPromptsList;
            
            if (visiblePrompts.length === 0) {
                checkbox.checked = false;
                checkbox.indeterminate = false;
            } else {
                const selectedCount = visiblePrompts.filter(p => activeSavedPrompts.has(p.id)).length;
                
                if (selectedCount === 0) {
                    checkbox.checked = false;
                    checkbox.indeterminate = false;
                } else if (selectedCount === visiblePrompts.length) {
                    checkbox.checked = true;
                    checkbox.indeterminate = false;
                } else {
                    checkbox.checked = false;
                    checkbox.indeterminate = true;
                }
            }
        }
        
        // Clear saved search
        function clearSavedSearch() {
            const searchInput = document.getElementById('searchPrompts');
            const clearBtn = document.getElementById('savedSearchClear');
            
            searchInput.value = '';
            clearBtn.style.display = 'none';
            renderSavedPrompts();
            searchInput.focus();
        }
        
        // Copy saved prompt content
        function copySavedPrompt(id) {
            const prompt = savedPromptsList.find(p => p.id === id);
            if (!prompt) return;
            
            navigator.clipboard.writeText(prompt.content).then(() => {
                showToast(`"${prompt.title}" copied to clipboard!`, 'success');
            }).catch(err => {
                console.error('Failed to copy:', err);
                showToast('Failed to copy prompt', 'error');
            });
        }
        
        // Pull editor content to Saved Prompt (overwrite prompt with editor content)
        function pullToSavedPrompt(id) {
            const prompt = savedPromptsList.find(p => p.id === id);
            if (!prompt) return;
            
            const editor = document.getElementById('promptEditor');
            const editorContent = editor.value.trim();
            
            if (!editorContent) {
                showToast('Editor is empty! Nothing to pull.', 'warning');
                return;
            }
            
            // Confirm before overwriting
            if (!confirm(`Are you sure you want to overwrite "${prompt.title}" with the current editor content?`)) {
                return;
            }
            
            // Send update to server
            const formData = new FormData();
            formData.append('action', 'update_prompt');
            formData.append('id', id);
            formData.append('title', prompt.title);
            formData.append('content', editorContent);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Update local array
                    prompt.content = editorContent;
                    showToast(`✅ "${prompt.title}" updated with editor content!`, 'success');
                } else {
                    showToast(data.message || 'Failed to update prompt', 'error');
                }
            })
            .catch(err => {
                console.error('Error:', err);
                showToast('Failed to update prompt', 'error');
            });
        }
        
        // Open saved prompt preview modal
        function openSavedPreview(id) {
            const prompt = savedPromptsList.find(p => p.id === id);
            if (!prompt) return;
            
            currentPreviewSaved = prompt;
            
            const modal = document.getElementById('savedPreviewModal');
            const previewName = document.getElementById('savedPreviewName');
            const previewDate = document.getElementById('savedPreviewDate');
            const previewContent = document.getElementById('savedPreviewContent');
            const editBtn = document.getElementById('savedPreviewEditBtn');
            const useBtn = document.getElementById('savedPreviewUseBtn');
            
            previewName.textContent = prompt.title;
            previewDate.textContent = `Created: ${formatDate(prompt.created_at)}`;
            previewContent.textContent = prompt.content;
            
            editBtn.onclick = () => {
                closeSavedPreview();
                editSavedPrompt(id);
            };
            
            useBtn.onclick = () => {
                if (!activeSavedPrompts.has(id)) {
                    toggleSavedPrompt(id);
                }
                closeSavedPreview();
            };
            
            modal.classList.add('active');
        }
        
        // Close saved preview modal
        function closeSavedPreview() {
            const modal = document.getElementById('savedPreviewModal');
            modal.classList.remove('active');
            currentPreviewSaved = null;
        }
        
        // Copy saved content from preview
        function copySavedContent() {
            if (!currentPreviewSaved) return;
            
            navigator.clipboard.writeText(currentPreviewSaved.content).then(() => {
                showToast('Content copied to clipboard!', 'success');
            }).catch(err => {
                console.error('Failed to copy:', err);
                showToast('Failed to copy content', 'error');
            });
        }
        
        // Pull editor content to Saved Prompt from preview modal
        function pullSavedContent() {
            if (!currentPreviewSaved) return;
            
            const editor = document.getElementById('promptEditor');
            const editorContent = editor.value.trim();
            
            if (!editorContent) {
                showToast('Editor is empty! Nothing to pull.', 'warning');
                return;
            }
            
            // Confirm before overwriting
            if (!confirm(`Are you sure you want to overwrite "${currentPreviewSaved.title}" with the current editor content?`)) {
                return;
            }
            
            // Send update to server
            const formData = new FormData();
            formData.append('action', 'update_prompt');
            formData.append('id', currentPreviewSaved.id);
            formData.append('title', currentPreviewSaved.title);
            formData.append('content', editorContent);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Update local array and preview
                    currentPreviewSaved.content = editorContent;
                    document.getElementById('savedPreviewContent').textContent = editorContent;
                    showToast(`✅ "${currentPreviewSaved.title}" updated with editor content!`, 'success');
                } else {
                    showToast(data.message || 'Failed to update prompt', 'error');
                }
            })
            .catch(err => {
                console.error('Error:', err);
                showToast('Failed to update prompt', 'error');
            });
        }
        
        // Edit saved prompt
        function editSavedPrompt(id) {
            const prompt = savedPromptsList.find(p => p.id === id);
            if (!prompt) return;
            
            const modal = document.getElementById('saveModal');
            const titleInput = document.getElementById('promptTitle');
            const contentInput = document.getElementById('promptContent');
            const editIdInput = document.getElementById('editPromptId');
            
            editIdInput.value = id;
            titleInput.value = prompt.title;
            contentInput.value = prompt.content;
            document.querySelector('#saveModal h3').innerHTML = '<i class="fas fa-edit"></i> Edit Prompt';
            
            modal.classList.add('active');
            titleInput.focus();
        }

        // Delete saved prompt - with fancy modal
        function deletePrompt(id) {
            const prompt = savedPromptsList.find(p => p.id === id);
            const promptTitle = prompt ? prompt.title : 'this prompt';
            
            showConfirmModal({
                title: 'Delete Saved Prompt?',
                message: `Are you sure you want to delete this saved prompt?`,
                icon: 'fa-trash-alt',
                type: 'warning',
                confirmText: 'Delete',
                confirmIcon: 'fa-trash-alt',
                details: `<div style="background: var(--bg-tertiary); padding: 0.75rem; border-radius: 8px; margin-top: 0.5rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-bookmark" style="color: var(--success);"></i>
                        <strong style="color: var(--text-primary);">${escapeHtmlDisplay(promptTitle)}</strong>
                    </div>
                </div>`,
                onConfirm: async () => {
                    try {
                        const formData = new FormData();
                        formData.append('action', 'delete_prompt');
                        formData.append('id', id);
                        
                        const response = await fetch('', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            // Record operation speed
                            if (data.operationTime) {
                                addSpeedEntry({
                                    time: data.operationTime,
                                    type: data.operationType || 'DELETE',
                                    connection: data.connectionType || currentConnectionType,
                                    timestamp: Date.now()
                                });
                                updateSpeedMonitor();
                            }
                            
                            // Remove from active if selected
                            if (activeSavedPrompts.has(id)) {
                                activeSavedPrompts.delete(id);
                                rebuildEditorFromSaved();
                            }
                            
                            showToast(`Prompt deleted! ⏱️ ${data.operationTime}ms`, 'success');
                            loadSavedPrompts();
                        } else {
                            showToast('Failed to delete prompt', 'error');
                        }
                    } catch (err) {
                        showToast('Delete failed!', 'error');
                    }
                }
            });
        }

        // Helper: Escape HTML
        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/[&<>"']/g, (m) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": "\\'"
            }[m])).replace(/\n/g, '\\n');
        }

        // Helper: Format date
        function formatDate(dateStr) {
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-US', { 
                month: 'short', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        // Custom Confirm Modal System
        let confirmCallback = null;
        
        function showConfirmModal(options) {
            const modal = document.getElementById('confirmModal');
            const icon = document.getElementById('confirmIcon');
            const title = document.getElementById('confirmTitle');
            const message = document.getElementById('confirmMessage');
            const details = document.getElementById('confirmDetails');
            const deleteBtn = document.getElementById('confirmDeleteBtn');
            
            // Set content
            title.textContent = options.title || 'Confirm Action';
            message.textContent = options.message || 'Are you sure?';
            
            // Set icon
            icon.className = 'confirm-icon';
            if (options.type === 'warning') icon.classList.add('warning');
            if (options.type === 'info') icon.classList.add('info');
            icon.innerHTML = `<i class="fas ${options.icon || 'fa-question-circle'}"></i>`;
            
            // Set details
            if (options.details) {
                details.innerHTML = options.details;
                details.classList.add('show');
            } else {
                details.classList.remove('show');
            }
            
            // Set button text
            deleteBtn.innerHTML = `<i class="fas ${options.confirmIcon || 'fa-check'}"></i> ${options.confirmText || 'Confirm'}`;
            
            // Store callback
            confirmCallback = options.onConfirm;
            
            // Show modal
            modal.classList.add('active');
            
            // Add escape key listener
            document.addEventListener('keydown', handleConfirmEscape);
        }
        
        function closeConfirmModal(confirmed) {
            const modal = document.getElementById('confirmModal');
            modal.classList.remove('active');
            
            // Remove escape key listener
            document.removeEventListener('keydown', handleConfirmEscape);
            
            // Execute callback if confirmed
            if (confirmed && confirmCallback) {
                confirmCallback();
            }
            
            confirmCallback = null;
        }
        
        function handleConfirmEscape(e) {
            if (e.key === 'Escape') {
                closeConfirmModal(false);
            }
        }

        // Show toast notification - Enhanced
        function showToast(message, type = 'info') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;

            const icons = {
                success: 'fa-check-circle',
                error: 'fa-times-circle',
                info: 'fa-info-circle',
                warning: 'fa-exclamation-triangle'
            };

            const titles = {
                success: 'Success',
                error: 'Error',
                info: 'Info',
                warning: 'Warning'
            };

            toast.innerHTML = `
                <div class="toast-icon">
                    <i class="fas ${icons[type]}"></i>
                </div>
                <div class="toast-content">
                    <div class="toast-title">${titles[type]}</div>
                    <div class="toast-message">${message}</div>
                </div>
                <button class="toast-close" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
                <div class="toast-progress"></div>
            `;

            container.appendChild(toast);

            // Auto remove after 3.5 seconds
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(100%) scale(0.9)';
                setTimeout(() => toast.remove(), 300);
            }, 3500);
        }
        
        // ============ WORK DISTRIBUTION FUNCTIONS ============
        
        // Update distribution value and UI
        function updateDistribution(value) {
            value = parseInt(value);
            distributionState.value = value;
            
            // Update value display
            const valueNumber = document.getElementById('valueNumber');
            valueNumber.textContent = value;
            
            // Update slider fill (30 steps)
            const fill = document.getElementById('sliderFill');
            const percentage = ((value - 1) / 29) * 100;
            fill.style.width = `${percentage}%`;
            
            // If enabled, update the editor
            if (distributionState.enabled) {
                updateEditorDistribution();
            }
        }
        
        // Set distribution from label click
        function setDistribution(value) {
            const slider = document.getElementById('distributionSlider');
            slider.value = value;
            updateDistribution(value);
        }
        
        // Get distribution messages based on value
        function getDistributionMessages(value) {
            if (value === 1) {
                return {
                    short: 'Complete this task in a single comprehensive response.',
                    full: '📋 WORK DISTRIBUTION: Single Step\n\nPlease complete this entire task in ONE comprehensive response.\nProvide a complete and thorough solution without breaking it into parts.'
                };
            } else if (value <= 5) {
                return {
                    short: `Distribute the work into ${value} steps.`,
                    full: `📋 WORK DISTRIBUTION: ${value} Steps\n\nPlease distribute and organize your work into ${value} distinct steps.\n\n✅ After completing each step, indicate "Step X/${value} Complete" before proceeding.`
                };
            } else {
                return {
                    short: `Distribute the work into ${value} steps.`,
                    full: `📋 WORK DISTRIBUTION: ${value} Steps\n\nPlease distribute and organize your work into ${value} distinct steps.\nBreak down the task systematically and work through each step methodically.\n\n✅ After completing each step, indicate "Step X/${value} Complete" before proceeding to the next.`
                };
            }
        }
        
        // Toggle distribution append - checkbox behavior
        function toggleDistributionAppend(event) {
            event.preventDefault();
            event.stopPropagation();
            
            const label = document.getElementById('distAppendCheck');
            const checkbox = document.getElementById('distributionEnabled');
            const section = document.querySelector('.distribution-section');
            const editor = document.getElementById('promptEditor');
            
            if (!editor) return;
            
            const isActive = label.classList.contains('active');
            
            if (isActive) {
                // Remove
                label.classList.remove('active');
                checkbox.checked = false;
                section.classList.remove('active');
                distributionState.enabled = false;
                removeDistributionFromEditor();
                showToast('Steps removed', 'info');
            } else {
                // Add - append to editor
                label.classList.add('active');
                checkbox.checked = true;
                section.classList.add('active');
                distributionState.enabled = true;
                
                const messages = getDistributionMessages(distributionState.value);
                const distributionBlock = `${distributionState.startMarker}\n${messages.full}\n${distributionState.endMarker}`;
                
                // Append to editor
                if (editor.value.trim()) {
                    editor.value = editor.value.trim() + '\n\n' + distributionBlock;
                } else {
                    editor.value = distributionBlock;
                }
                
                showToast(`${distributionState.value} steps added`, 'success');
                updateCounts();
            }
        }
        
        // Legacy toggle function for compatibility
        function toggleDistribution() {
            const label = document.getElementById('distAppendCheck');
            if (label) {
                toggleDistributionAppend({ preventDefault: () => {}, stopPropagation: () => {} });
            }
        }
        
        // Add distribution instruction to editor
        function addDistributionToEditor() {
            const editor = document.getElementById('promptEditor');
            const messages = getDistributionMessages(distributionState.value);
            
            // Check if distribution marker already exists
            if (editor.value.includes(distributionState.startMarker)) {
                updateEditorDistribution();
                return;
            }
            
            // Add at the END of the editor (append, not prepend)
            const distributionBlock = `${distributionState.startMarker}\n${messages.full}\n${distributionState.endMarker}`;
            
            if (editor.value.trim()) {
                editor.value = editor.value.trim() + '\n\n' + distributionBlock;
            } else {
                editor.value = distributionBlock;
            }
            
            // Scroll to bottom to show the new content
            editor.scrollTop = editor.scrollHeight;
            
            updateCounts();
        }
        
        // Update distribution instruction in editor
        function updateEditorDistribution() {
            const editor = document.getElementById('promptEditor');
            const messages = getDistributionMessages(distributionState.value);
            
            const startIdx = editor.value.indexOf(distributionState.startMarker);
            const endIdx = editor.value.indexOf(distributionState.endMarker);
            
            if (startIdx !== -1 && endIdx !== -1) {
                // Replace existing distribution block
                const before = editor.value.substring(0, startIdx);
                const after = editor.value.substring(endIdx + distributionState.endMarker.length);
                
                const newBlock = `${distributionState.startMarker}\n${messages.full}\n${distributionState.endMarker}`;
                editor.value = before + newBlock + after;
                
                updateCounts();
            } else if (distributionState.enabled) {
                // No existing block, add new one
                addDistributionToEditor();
            }
        }
        
        // Remove distribution instruction from editor
        function removeDistributionFromEditor() {
            const editor = document.getElementById('promptEditor');
            
            const startIdx = editor.value.indexOf(distributionState.startMarker);
            const endIdx = editor.value.indexOf(distributionState.endMarker);
            
            if (startIdx !== -1 && endIdx !== -1) {
                const before = editor.value.substring(0, startIdx);
                const after = editor.value.substring(endIdx + distributionState.endMarker.length);
                
                // Clean up extra newlines
                editor.value = (before + after).replace(/^\n+/, '').replace(/\n{3,}/g, '\n\n').trim();
                
                updateCounts();
            }
        }
        
        // Initialize distribution slider on page load
        function initDistributionSlider() {
            updateDistribution(1);
            updateActiveLabel(1);
        }
        
        // ============ CUSTOM RESIZE HANDLE ============
        
        function initResizeHandle() {
            const resizeHandle = document.getElementById('resizeHandle');
            const editor = document.getElementById('promptEditor');
            
            if (!resizeHandle || !editor) return;
            
            let isResizing = false;
            let startY = 0;
            let startHeight = 0;
            
            resizeHandle.addEventListener('mousedown', (e) => {
                isResizing = true;
                startY = e.clientY;
                startHeight = editor.offsetHeight;
                
                document.body.style.cursor = 'ns-resize';
                document.body.style.userSelect = 'none';
                
                e.preventDefault();
            });
            
            document.addEventListener('mousemove', (e) => {
                if (!isResizing) return;
                
                const deltaY = e.clientY - startY;
                const newHeight = Math.max(150, Math.min(startHeight + deltaY, window.innerHeight * 0.8));
                
                editor.style.height = newHeight + 'px';
            });
            
            document.addEventListener('mouseup', () => {
                if (isResizing) {
                    isResizing = false;
                    document.body.style.cursor = '';
                    document.body.style.userSelect = '';
                }
            });
            
            // Touch support for mobile
            resizeHandle.addEventListener('touchstart', (e) => {
                isResizing = true;
                startY = e.touches[0].clientY;
                startHeight = editor.offsetHeight;
                e.preventDefault();
            });
            
            document.addEventListener('touchmove', (e) => {
                if (!isResizing) return;
                
                const deltaY = e.touches[0].clientY - startY;
                const newHeight = Math.max(150, Math.min(startHeight + deltaY, window.innerHeight * 0.8));
                
                editor.style.height = newHeight + 'px';
            });
            
            document.addEventListener('touchend', () => {
                isResizing = false;
            });
        }
        
        // Initialize resize handle on page load
        document.addEventListener('DOMContentLoaded', initResizeHandle);

        // ============================================
        // SAVED PROMPTS RESIZE HANDLE
        // ============================================
        function initSavedResize() {
            const section = document.querySelector('.saved-prompts-section');
            const handle = document.getElementById('savedResizeHandle');
            
            if (!section || !handle) return;
            
            let isResizing = false;
            let startY = 0;
            let startHeight = 0;
            
            handle.addEventListener('mousedown', (e) => {
                e.preventDefault();
                isResizing = true;
                startY = e.clientY;
                startHeight = section.offsetHeight;
                section.classList.add('resizing');
                document.body.style.cursor = 'ns-resize';
            });
            
            document.addEventListener('mousemove', (e) => {
                if (!isResizing) return;
                
                const deltaY = e.clientY - startY;
                const newHeight = Math.min(Math.max(startHeight + deltaY, 120), 600);
                section.style.height = newHeight + 'px';
            });
            
            document.addEventListener('mouseup', () => {
                if (isResizing) {
                    isResizing = false;
                    section.classList.remove('resizing');
                    document.body.style.cursor = '';
                    // Save height to localStorage
                    localStorage.setItem('savedPromptsHeight', section.style.height);
                }
            });
            
            // Restore height from localStorage
            const savedHeight = localStorage.getItem('savedPromptsHeight');
            if (savedHeight) {
                section.style.height = savedHeight;
            }
        }
        
        document.addEventListener('DOMContentLoaded', initSavedResize);

        // ============================================
        // EDITOR SEARCH SYSTEM (with Yellow Highlight Overlay)
        // ============================================
        const editorSearch = {
            matches: [],
            currentIndex: -1,
            searchTerm: '',
            debounceTimer: null
        };

        // Initialize editor search
        function initEditorSearch() {
            const searchInput = document.getElementById('editorSearchInput');
            const searchBar = document.getElementById('editorSearchBar');
            const editor = document.getElementById('promptEditor');
            const overlay = document.getElementById('editorHighlightOverlay');
            
            if (!searchInput || !editor || !overlay) return;
            
            // Sync scroll between editor and overlay
            editor.addEventListener('scroll', syncOverlayScroll);
            
            // Update overlay when editor content changes
            editor.addEventListener('input', () => {
                if (editorSearch.searchTerm) {
                    performEditorSearch(editorSearch.searchTerm);
                }
            });
            
            // Dynamic search on input - cursor stays in search box
            searchInput.addEventListener('input', (e) => {
                const value = e.target.value;
                
                // Toggle has-value class for clear button
                if (value.length > 0) {
                    searchBar.classList.add('has-value');
                } else {
                    searchBar.classList.remove('has-value');
                    clearHighlightOverlay();
                }
                
                // Debounce the search
                clearTimeout(editorSearch.debounceTimer);
                editorSearch.debounceTimer = setTimeout(() => {
                    performEditorSearch(value);
                }, 80);
            });
            
            // Keyboard shortcuts - cursor stays in search box
            searchInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    if (e.shiftKey) {
                        editorSearchPrev();
                    } else {
                        editorSearchNext();
                    }
                    // Keep focus in search input
                    searchInput.focus();
                } else if (e.key === 'Escape') {
                    clearEditorSearch();
                }
            });
            
            // Ctrl+F to focus search
            document.addEventListener('keydown', (e) => {
                if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                    const editorArea = document.querySelector('.editor-container');
                    if (editorArea && editorArea.contains(document.activeElement)) {
                        e.preventDefault();
                        searchInput.focus();
                        searchInput.select();
                    }
                }
            });
        }

        // Sync overlay scroll with editor scroll
        function syncOverlayScroll() {
            const editor = document.getElementById('promptEditor');
            const overlay = document.getElementById('editorHighlightOverlay');
            if (overlay && editor) {
                overlay.scrollTop = editor.scrollTop;
            }
        }

        // Escape HTML entities for safe display
        function escapeHtmlForOverlay(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Clear the highlight overlay
        function clearHighlightOverlay() {
            const editor = document.getElementById('promptEditor');
            const editorBody = editor.closest('.editor-body');
            const overlay = document.getElementById('editorHighlightOverlay');
            
            overlay.innerHTML = '';
            editor.classList.remove('searching');
            if (editorBody) editorBody.classList.remove('searching');
        }

        // Update the highlight overlay with yellow marks
        function updateHighlightOverlay() {
            const editor = document.getElementById('promptEditor');
            const overlay = document.getElementById('editorHighlightOverlay');
            
            if (!editorSearch.searchTerm || editorSearch.matches.length === 0) {
                clearHighlightOverlay();
                return;
            }
            
            const text = editor.value;
            let html = '';
            let lastIndex = 0;
            
            // Build HTML with <mark> tags around matches
            editorSearch.matches.forEach((match, idx) => {
                // Add text before this match
                html += escapeHtmlForOverlay(text.substring(lastIndex, match.start));
                
                // Add the highlighted match
                const matchText = text.substring(match.start, match.end);
                const isCurrent = idx === editorSearch.currentIndex;
                html += `<mark${isCurrent ? ' class="current"' : ''}>${escapeHtmlForOverlay(matchText)}</mark>`;
                
                lastIndex = match.end;
            });
            
            // Add remaining text after last match
            html += escapeHtmlForOverlay(text.substring(lastIndex));
            
            overlay.innerHTML = html;
            editor.classList.add('searching');
            
            // Add searching class to editor-body for CSS targeting
            const editorBody = editor.closest('.editor-body');
            if (editorBody) editorBody.classList.add('searching');
            
            // Sync scroll
            syncOverlayScroll();
        }

        // Perform the search - NO focus change, just highlight
        function performEditorSearch(searchTerm) {
            const editor = document.getElementById('promptEditor');
            const searchBar = document.getElementById('editorSearchBar');
            const badge = document.getElementById('searchResultsBadge');
            
            editorSearch.searchTerm = searchTerm;
            editorSearch.matches = [];
            editorSearch.currentIndex = -1;
            
            // Clear classes
            searchBar.classList.remove('has-results', 'no-results');
            
            if (!searchTerm || searchTerm.length === 0) {
                badge.textContent = '0/0';
                clearHighlightOverlay();
                return;
            }
            
            const text = editor.value;
            const lowerText = text.toLowerCase();
            const lowerSearch = searchTerm.toLowerCase();
            
            // Find all matches (case-insensitive)
            let index = 0;
            while ((index = lowerText.indexOf(lowerSearch, index)) !== -1) {
                editorSearch.matches.push({
                    start: index,
                    end: index + searchTerm.length
                });
                index += 1;
            }
            
            // Update UI
            if (editorSearch.matches.length > 0) {
                searchBar.classList.add('has-results');
                editorSearch.currentIndex = 0;
                badge.textContent = `1/${editorSearch.matches.length}`;
                
                // Update overlay with highlights
                updateHighlightOverlay();
                
                // Scroll to first match (no focus change)
                scrollToMatch(editorSearch.currentIndex);
            } else {
                searchBar.classList.add('no-results');
                badge.textContent = '0/0';
                clearHighlightOverlay();
            }
        }

        // Scroll to a specific match without changing focus
        function scrollToMatch(matchIndex) {
            const editor = document.getElementById('promptEditor');
            
            if (editorSearch.matches.length === 0 || matchIndex < 0) return;
            
            const match = editorSearch.matches[matchIndex];
            const text = editor.value.substring(0, match.start);
            const lines = text.split('\n');
            const lineNumber = lines.length;
            
            // Calculate scroll position (line height ~22px)
            const lineHeight = 22;
            const editorHeight = editor.clientHeight;
            const targetScroll = Math.max(0, (lineNumber - 1) * lineHeight - editorHeight / 3);
            
            editor.scrollTop = targetScroll;
            
            // Sync overlay
            syncOverlayScroll();
        }

        // Go to next match - cursor stays in search box
        function editorSearchNext() {
            if (editorSearch.matches.length === 0) return;
            
            editorSearch.currentIndex = (editorSearch.currentIndex + 1) % editorSearch.matches.length;
            
            const badge = document.getElementById('searchResultsBadge');
            badge.textContent = `${editorSearch.currentIndex + 1}/${editorSearch.matches.length}`;
            
            // Update overlay to show new current match
            updateHighlightOverlay();
            
            // Scroll to match
            scrollToMatch(editorSearch.currentIndex);
        }

        // Go to previous match - cursor stays in search box
        function editorSearchPrev() {
            if (editorSearch.matches.length === 0) return;
            
            editorSearch.currentIndex = editorSearch.currentIndex - 1;
            if (editorSearch.currentIndex < 0) {
                editorSearch.currentIndex = editorSearch.matches.length - 1;
            }
            
            const badge = document.getElementById('searchResultsBadge');
            badge.textContent = `${editorSearch.currentIndex + 1}/${editorSearch.matches.length}`;
            
            // Update overlay to show new current match
            updateHighlightOverlay();
            
            // Scroll to match
            scrollToMatch(editorSearch.currentIndex);
        }

        // Clear search
        function clearEditorSearch() {
            const searchInput = document.getElementById('editorSearchInput');
            const searchBar = document.getElementById('editorSearchBar');
            const badge = document.getElementById('searchResultsBadge');
            
            searchInput.value = '';
            editorSearch.matches = [];
            editorSearch.currentIndex = -1;
            editorSearch.searchTerm = '';
            
            searchBar.classList.remove('has-results', 'no-results', 'has-value');
            badge.textContent = '0/0';
            
            clearHighlightOverlay();
        }

        // Initialize editor search on page load
        document.addEventListener('DOMContentLoaded', initEditorSearch);
        
        // ============================================
        // ADMIN - Open in new tab
        // ============================================
        function openDictAdminPopup() {
            window.open('https://frouty.com/pages/admin.php', '_blank');
        }

        // ============================================
        // QUIZ - Open in new tab
        // ============================================
        function openDictQuizPopup() {
            window.open('https://frouty.com/pages/quiz.php', '_blank');
        }

        // ============================================
        // DATABASE CONNECTION TOGGLE & SPEED MONITOR
        // ============================================
        const DB_CONNECTION_KEY = 'pm_db_connection_type';
        const SPEED_HISTORY_KEY = 'pm_db_speed_history';
        
        // Current connection type from PHP
        let currentConnectionType = '<?php echo $connectionType; ?>';
        let connectionFallback = <?php echo $connectionFallback ? 'true' : 'false'; ?>;
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize speed monitor
            initSpeedMonitor();
            
            // Show fallback notification if auto-switched
            if (connectionFallback) {
                showDbSwitchToast(`⚡ Auto-switched to ${currentConnectionType === 'localhost' ? '🖥️ Local' : '🌐 Remote'} (preferred unavailable)`, 'warning', 5000);
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
            
            showDbSwitchToast(`Switching to ${isRemote ? '🌐 Remote' : '🖥️ Localhost'}...`, 'info', 0);
            
            try {
                // Test the connection via PHP
                const response = await fetch(`?switch_db=${targetType}`);
                const result = await response.json();
                
                if (result.success) {
                    // Connection successful
                    currentConnectionType = result.type;
                    container.setAttribute('data-active', result.type);
                    statusEl.innerHTML = result.type === 'remote' ? '🌐 Remote' : '🖥️ Local';
                    
                    // Save to localStorage as backup
                    localStorage.setItem(DB_CONNECTION_KEY, result.type);
                    
                    showDbSwitchToast(`✅ Connected to ${result.type === 'remote' ? '🌐 Remote' : '🖥️ Localhost'}`, 'success');
                    
                    // Reload page to use new connection
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    // Connection failed - revert toggle
                    toggle.checked = (currentConnectionType === 'remote');
                    container.setAttribute('data-active', currentConnectionType);
                    
                    showDbSwitchToast(
                        `❌ Failed to connect to ${isRemote ? 'Remote' : 'Localhost'}. Error: ${result.error}`,
                        'error',
                        5000
                    );
                }
            } catch (error) {
                // Network error - revert toggle
                toggle.checked = (currentConnectionType === 'remote');
                container.setAttribute('data-active', currentConnectionType);
                
                showDbSwitchToast(`❌ Connection error: ${error.message}`, 'error', 5000);
            }
            
            // Re-enable toggle
            toggle.disabled = false;
        }
        
        // Show toast notification for db switch
        function showDbSwitchToast(message, type = 'info', duration = 3000) {
            const existingToast = document.getElementById('dbSwitchToast');
            if (existingToast) existingToast.remove();
            
            const toast = document.createElement('div');
            toast.id = 'dbSwitchToast';
            toast.className = `db-switch-toast ${type}`;
            
            const icons = { 'success': '✅', 'error': '❌', 'info': '🔄', 'warning': '⚠️' };
            toast.innerHTML = `<span>${icons[type] || '📢'}</span><span>${message}</span>`;
            
            document.body.appendChild(toast);
            
            if (duration > 0) {
                setTimeout(() => {
                    toast.style.animation = 'slideOutRight 0.3s ease';
                    setTimeout(() => toast.remove(), 300);
                }, duration);
            }
        }
        
        // ========================================
        // SPEED MONITOR FUNCTIONALITY
        // ========================================
        
        function initSpeedMonitor() {
            if (window.latestOperation) {
                addSpeedEntry(window.latestOperation);
            }
            updateSpeedMonitor();
        }
        
        function getSpeedHistory() {
            try {
                const data = localStorage.getItem(SPEED_HISTORY_KEY);
                return data ? JSON.parse(data) : [];
            } catch (e) {
                return [];
            }
        }
        
        function saveSpeedHistory(history) {
            try {
                if (history.length > 10) history = history.slice(-10);
                localStorage.setItem(SPEED_HISTORY_KEY, JSON.stringify(history));
            } catch (e) {
                console.error('Failed to save speed history:', e);
            }
        }
        
        function addSpeedEntry(operation) {
            const history = getSpeedHistory();
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
        
        function updateSpeedMonitor() {
            const content = document.getElementById('speedContent');
            if (!content) return;
            
            const history = getSpeedHistory();
            
            if (history.length === 0) {
                content.innerHTML = '<div class="no-data">Perform an action to see speed...</div>';
                return;
            }
            
            const lastTwo = history.slice(-2);
            let html = '';
            
            lastTwo.forEach((entry, idx) => {
                const isLatest = idx === lastTwo.length - 1;
                const connClass = entry.connection === 'localhost' ? 'local' : 'remote';
                const connIcon = entry.connection === 'localhost' ? '🖥️' : '🌐';
                
                html += `
                    <div class="speed-row ${isLatest ? 'speed-new' : ''}">
                        <span class="speed-label">${connIcon} <span class="op-type">${entry.type}</span></span>
                        <span class="speed-value ${connClass}">${entry.time}ms</span>
                    </div>
                `;
            });
            
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
                    " onmouseover="this.style.background='rgba(255,255,255,0.2)'" 
                       onmouseout="this.style.background='rgba(255,255,255,0.1)'">
                        Clear History
                    </button>
                </div>
            `;
            
            content.innerHTML = html;
        }
        
        function clearSpeedHistory() {
            localStorage.removeItem(SPEED_HISTORY_KEY);
            updateSpeedMonitor();
        }
        
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

<!-- Database Manager Modal -->
<div id="dbManagerModal" class="db-manager-modal" onclick="if(event.target === this) closeDbManager()">
    <div class="db-manager-container">
        <div class="db-manager-header">
            <div class="db-manager-title">
                <i class="fas fa-database"></i>
                <span>Database Manager</span>
            </div>
            <div class="db-manager-actions">
                <button type="button" class="db-manager-refresh" onclick="refreshDbManagerFrame()" title="Refresh">
                    <i class="fas fa-sync-alt"></i>
                </button>
                <button type="button" class="db-manager-close" onclick="closeDbManager()" title="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <div class="db-manager-body">
            <iframe id="dbManagerFrame" src="" frameborder="0"></iframe>
        </div>
    </div>
</div>

<style>
/* Database Manager Modal */
.db-manager-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.85);
    backdrop-filter: blur(8px);
    z-index: 100000;
    justify-content: center;
    align-items: center;
    padding: 30px;
    animation: dbModalFadeIn 0.3s ease;
}

@keyframes dbModalFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.db-manager-modal.active {
    display: flex;
}

.db-manager-container {
    width: 100%;
    max-width: 1400px;
    height: 90vh;
    background: linear-gradient(135deg, #0a0e17 0%, #111827 100%);
    border-radius: 20px;
    border: 1px solid rgba(0, 212, 170, 0.3);
    box-shadow: 0 25px 80px rgba(0, 0, 0, 0.6), 0 0 60px rgba(0, 212, 170, 0.15);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    animation: dbModalSlideIn 0.4s ease;
}

@keyframes dbModalSlideIn {
    from { 
        opacity: 0; 
        transform: scale(0.9) translateY(30px);
    }
    to { 
        opacity: 1; 
        transform: scale(1) translateY(0);
    }
}

.db-manager-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 24px;
    background: linear-gradient(135deg, rgba(0, 212, 170, 0.1) 0%, rgba(124, 58, 237, 0.1) 100%);
    border-bottom: 1px solid rgba(0, 212, 170, 0.2);
}

.db-manager-title {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 1.2rem;
    font-weight: 700;
    color: #00d4aa;
}

.db-manager-title i {
    font-size: 1.4rem;
}

.db-manager-actions {
    display: flex;
    gap: 10px;
}

.db-manager-refresh,
.db-manager-close {
    width: 38px;
    height: 38px;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    transition: all 0.25s ease;
}

.db-manager-refresh {
    background: rgba(0, 212, 170, 0.15);
    color: #00d4aa;
}

.db-manager-refresh:hover {
    background: rgba(0, 212, 170, 0.3);
    transform: rotate(180deg);
}

.db-manager-close {
    background: rgba(239, 68, 68, 0.15);
    color: #ef4444;
}

.db-manager-close:hover {
    background: rgba(239, 68, 68, 0.3);
    transform: scale(1.1);
}

.db-manager-body {
    flex: 1;
    overflow: hidden;
    position: relative;
}

.db-manager-body iframe {
    width: 100%;
    height: 100%;
    border: none;
    background: #0a0e17;
}

/* Loading state */
.db-manager-body::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 50px;
    height: 50px;
    margin: -25px 0 0 -25px;
    border: 4px solid rgba(0, 212, 170, 0.2);
    border-top-color: #00d4aa;
    border-radius: 50%;
    animation: dbSpinner 0.8s linear infinite;
    z-index: 1;
    pointer-events: none;
}

.db-manager-body.loaded::before {
    display: none;
}

@keyframes dbSpinner {
    to { transform: rotate(360deg); }
}
</style>

<script>
// Database Manager Modal Functions
function openDbManager() {
    const modal = document.getElementById('dbManagerModal');
    const iframe = document.getElementById('dbManagerFrame');
    const body = document.querySelector('.db-manager-body');
    
    // Remove loaded class to show spinner
    body.classList.remove('loaded');
    
    // Load the iframe
    iframe.src = 'report-prompt-databases.php';
    
    // Show modal
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
    
    // Mark as loaded when iframe loads
    iframe.onload = function() {
        body.classList.add('loaded');
    };
}

function closeDbManager() {
    const modal = document.getElementById('dbManagerModal');
    const iframe = document.getElementById('dbManagerFrame');
    
    // Hide modal
    modal.classList.remove('active');
    document.body.style.overflow = '';
    
    // Clear iframe to stop any processes
    iframe.src = '';
    
    // Refresh the database dropdown after closing (in case user added/modified databases)
    loadHostingerDatabases();
}

function refreshDbManagerFrame() {
    const iframe = document.getElementById('dbManagerFrame');
    const body = document.querySelector('.db-manager-body');
    
    // Show spinner
    body.classList.remove('loaded');
    
    // Reload iframe
    iframe.src = iframe.src;
}

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('dbManagerModal');
        if (modal.classList.contains('active')) {
            closeDbManager();
        }
    }
});
</script>
<!-- End Database Manager Modal -->

<!-- ════════════════════════════════════════════════════════════════
     PROJECT MANAGEMENT POPUPS
     ════════════════════════════════════════════════════════════════ -->

<!-- New/Edit Project Popup -->
<div class="project-popup-overlay" id="newProjectPopup">
    <div class="project-popup">
        <div class="project-popup-header">
            <div class="project-popup-title">
                <i class="fas fa-folder-plus"></i>
                <span id="projectPopupTitle">New Project</span>
            </div>
            <button class="project-popup-close" onclick="closeProjectPopup('newProjectPopup')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="project-popup-body">
            <input type="hidden" id="projectEditId" value="">
            <div class="project-form-group">
                <label class="project-form-label">Project Name *</label>
                <input type="text" class="project-form-input" id="projectNameInput" placeholder="Enter project name...">
            </div>
            <div class="project-form-group">
                <label class="project-form-label">Description</label>
                <textarea class="project-form-input" id="projectDescInput" placeholder="Brief description (optional)..."></textarea>
            </div>
        </div>
        <div class="project-popup-footer">
            <button class="project-popup-btn cancel" onclick="closeProjectPopup('newProjectPopup')">Cancel</button>
            <button class="project-popup-btn primary" onclick="saveProjectFromPopup()">
                <i class="fas fa-save"></i>
                <span>Save Project</span>
            </button>
        </div>
    </div>
</div>

<!-- Load Project Popup -->
<div class="project-popup-overlay" id="loadProjectPopup">
    <div class="project-popup">
        <div class="project-popup-header">
            <div class="project-popup-title">
                <i class="fas fa-folder-open"></i>
                <span>Load Project</span>
            </div>
            <button class="project-popup-close" onclick="closeProjectPopup('loadProjectPopup')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="project-popup-body">
            <div class="project-list" id="projectListContainer">
                <!-- Projects will be loaded here -->
            </div>
        </div>
        <div class="project-popup-footer">
            <button class="project-popup-btn cancel" onclick="closeProjectPopup('loadProjectPopup')">Cancel</button>
            <button class="project-popup-btn primary" onclick="loadSelectedProject()" id="loadProjectBtn" disabled>
                <i class="fas fa-folder-open"></i>
                <span>Load</span>
            </button>
        </div>
    </div>
</div>

<!-- Delete Confirmation Popup -->
<div class="project-popup-overlay" id="deleteProjectPopup">
    <div class="project-popup" style="min-width: 360px;">
        <div class="project-popup-header" style="background: linear-gradient(135deg, rgba(239, 68, 68, 0.12) 0%, rgba(220, 38, 38, 0.06) 100%); border-bottom-color: rgba(239, 68, 68, 0.15);">
            <div class="project-popup-title" style="color: #fca5a5;">
                <i class="fas fa-exclamation-triangle" style="color: #f87171;"></i>
                <span>Delete Project</span>
            </div>
            <button class="project-popup-close" onclick="closeProjectPopup('deleteProjectPopup')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="project-popup-body" style="text-align: center;">
            <p style="color: var(--text-primary); font-size: 1rem; margin-bottom: 0.5rem;">
                Are you sure you want to delete this project?
            </p>
            <p style="color: #f87171; font-size: 0.9rem; font-weight: 600;" id="deleteProjectName"></p>
            <p style="color: var(--text-muted); font-size: 0.8rem; margin-top: 1rem;">
                This action cannot be undone.
            </p>
        </div>
        <div class="project-popup-footer" style="justify-content: center;">
            <button class="project-popup-btn cancel" onclick="closeProjectPopup('deleteProjectPopup')">Cancel</button>
            <button class="project-popup-btn danger" onclick="confirmDeleteProject()">
                <i class="fas fa-trash"></i>
                <span>Delete</span>
            </button>
        </div>
    </div>
</div>

<script>
// ════════════════════════════════════════════════════════════════
// PROJECT MANAGEMENT FUNCTIONS
// ════════════════════════════════════════════════════════════════

let currentProjectId = null;
let selectedProjectToLoad = null;
let projectToDelete = null;

// Initialize projects on page load
document.addEventListener('DOMContentLoaded', function() {
    loadProjectsList();
});

// Load all projects into selector
function loadProjectsList() {
    const formData = new FormData();
    formData.append('action', 'get_projects');
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success && data.projects) {
            updateProjectSelector(data.projects);
        }
    })
    .catch(err => console.error('Error loading projects:', err));
}

// Update project selector dropdown
function updateProjectSelector(projects) {
    const selector = document.getElementById('projectSelector');
    if (!selector) return;
    
    selector.innerHTML = '<option value="">-- No Project --</option>';
    
    projects.forEach(project => {
        const option = document.createElement('option');
        option.value = project.id;
        option.textContent = project.name;
        if (project.id == currentProjectId) {
            option.selected = true;
        }
        selector.appendChild(option);
    });
}

// Open new project popup
function openNewProjectPopup() {
    // Reset current project ID
    currentProjectId = null;
    
    // Reset the project selector dropdown
    const selector = document.getElementById('projectSelector');
    if (selector) selector.value = '';
    
    // Reset the dashboard to empty state
    resetDashboardItems();
    
    // Clear popup fields
    document.getElementById('projectEditId').value = '';
    document.getElementById('projectNameInput').value = '';
    document.getElementById('projectDescInput').value = '';
    document.getElementById('projectPopupTitle').textContent = 'New Project';
    
    // Show popup
    const popup = document.getElementById('newProjectPopup');
    popup.classList.add('active');
    document.getElementById('projectNameInput').focus();
}

// Close popup
function closeProjectPopup(popupId) {
    const popup = document.getElementById(popupId);
    popup.classList.remove('active');
}

// Save project from popup
function saveProjectFromPopup() {
    const id = document.getElementById('projectEditId').value;
    const name = document.getElementById('projectNameInput').value.trim();
    const description = document.getElementById('projectDescInput').value.trim();
    
    if (!name) {
        showToast('Please enter a project name', 'error');
        document.getElementById('projectNameInput').focus();
        return;
    }
    
    // Collect current dashboard data
    const projectData = collectDashboardData();
    projectData.id = id;
    projectData.name = name;
    projectData.description = description;
    
    saveProject(projectData);
}

// Save current project (quick save)
function saveCurrentProject() {
    if (!currentProjectId) {
        openNewProjectPopup();
        return;
    }
    
    const projectData = collectDashboardData();
    projectData.id = currentProjectId;
    
    // Get current project name
    const selector = document.getElementById('projectSelector');
    const selectedOption = selector.options[selector.selectedIndex];
    projectData.name = selectedOption ? selectedOption.textContent : 'Untitled Project';
    
    saveProject(projectData);
}

// Collect all data from dashboard
function collectDashboardData() {
    const data = {
        // Database info
        database_id: '',
        database_name: document.getElementById('dbDropdown')?.value || '',
        database_host: '',
        database_user: '',
        database_pass: '',
        database_port: '3306',
        include_remote: document.getElementById('remoteCheckbox')?.checked ? 1 : 0,
        include_localhost: document.getElementById('localhostCheckbox')?.checked ? 1 : 0,
        
        // Items
        backends: [],
        pages: [],
        frontends: []
    };
    
    // Collect backend items
    const backendItems = document.querySelectorAll('#backendItemsGrid .dynamic-item');
    backendItems.forEach(item => {
        data.backends.push({
            name: item.querySelector('.dynamic-item-name')?.value || '',
            desc: item.querySelector('.dynamic-item-desc')?.value || ''
        });
    });
    
    // Collect page items
    const pageItems = document.querySelectorAll('#pageItemsGrid .dynamic-item');
    pageItems.forEach(item => {
        data.pages.push({
            name: item.querySelector('.dynamic-item-name')?.value || '',
            desc: item.querySelector('.dynamic-item-desc')?.value || ''
        });
    });
    
    // Collect frontend items
    const frontendItems = document.querySelectorAll('#frontendItemsGrid .dynamic-item');
    frontendItems.forEach(item => {
        data.frontends.push({
            name: item.querySelector('.dynamic-item-name')?.value || '',
            desc: item.querySelector('.dynamic-item-desc')?.value || ''
        });
    });
    
    return data;
}

// Save project to database
function saveProject(projectData) {
    const formData = new FormData();
    formData.append('action', 'save_project');
    formData.append('id', projectData.id || '');
    formData.append('name', projectData.name);
    formData.append('description', projectData.description || '');
    formData.append('database_id', projectData.database_id || '');
    formData.append('database_name', projectData.database_name || '');
    formData.append('database_host', projectData.database_host || '');
    formData.append('database_user', projectData.database_user || '');
    formData.append('database_pass', projectData.database_pass || '');
    formData.append('database_port', projectData.database_port || '3306');
    formData.append('include_remote', projectData.include_remote || 0);
    formData.append('include_localhost', projectData.include_localhost || 0);
    formData.append('backends', JSON.stringify(projectData.backends || []));
    formData.append('pages', JSON.stringify(projectData.pages || []));
    formData.append('frontends', JSON.stringify(projectData.frontends || []));
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            currentProjectId = data.id;
            showToast(data.message, 'success');
            closeProjectPopup('newProjectPopup');
            loadProjectsList();
            
            // Log operation time to speed monitor
            if (data.operationTime) {
                addSpeedEntry({ 
                    time: parseFloat(data.operationTime), 
                    type: data.operationType, 
                    connection: data.connectionType, 
                    timestamp: Date.now() 
                });
                updateSpeedMonitor();
            }
        } else {
            showToast(data.message || 'Error saving project', 'error');
        }
    })
    .catch(err => {
        console.error('Error saving project:', err);
        showToast('Error saving project', 'error');
    });
}

// Open load project popup
function openLoadProjectPopup() {
    const formData = new FormData();
    formData.append('action', 'get_projects');
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        const container = document.getElementById('projectListContainer');
        container.innerHTML = '';
        selectedProjectToLoad = null;
        document.getElementById('loadProjectBtn').disabled = true;
        
        if (data.success && data.projects && data.projects.length > 0) {
            data.projects.forEach(project => {
                const item = document.createElement('div');
                item.className = 'project-list-item';
                item.dataset.id = project.id;
                item.onclick = () => selectProjectToLoad(project.id, item);
                
                const date = new Date(project.updated_at);
                const dateStr = date.toLocaleDateString('en-US', { 
                    month: 'short', day: 'numeric', year: 'numeric' 
                });
                
                item.innerHTML = `
                    <div class="project-list-info">
                        <div class="project-list-name">${escapeHtml(project.name)}</div>
                        <div class="project-list-date">Updated: ${dateStr}</div>
                    </div>
                `;
                container.appendChild(item);
            });
        } else {
            container.innerHTML = `
                <div class="project-list-empty">
                    <i class="fas fa-folder-open"></i>
                    <p>No projects saved yet</p>
                </div>
            `;
        }
        
        document.getElementById('loadProjectPopup').classList.add('active');
    })
    .catch(err => {
        console.error('Error loading projects:', err);
        showToast('Error loading projects', 'error');
    });
}

// Select project to load
function selectProjectToLoad(id, element) {
    document.querySelectorAll('.project-list-item').forEach(item => {
        item.classList.remove('selected');
    });
    element.classList.add('selected');
    selectedProjectToLoad = id;
    document.getElementById('loadProjectBtn').disabled = false;
}

// Load selected project
function loadSelectedProject() {
    if (!selectedProjectToLoad) return;
    
    const formData = new FormData();
    formData.append('action', 'get_project');
    formData.append('id', selectedProjectToLoad);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success && data.project) {
            applyProjectToDashboard(data.project);
            currentProjectId = data.project.id;
            closeProjectPopup('loadProjectPopup');
            showToast('Project loaded successfully!', 'success');
            
            // Update selector
            loadProjectsList();
        } else {
            showToast(data.message || 'Error loading project', 'error');
        }
    })
    .catch(err => {
        console.error('Error loading project:', err);
        showToast('Error loading project', 'error');
    });
}

// Apply project data to dashboard
function applyProjectToDashboard(project) {
    // Reset first - clears dynamicItems and HTML
    resetDashboardItems();
    
    // Set database
    if (project.database_name) {
        const dbDropdown = document.getElementById('dbDropdown');
        if (dbDropdown) {
            // Try to find and select the matching option
            for (let i = 0; i < dbDropdown.options.length; i++) {
                if (dbDropdown.options[i].value === project.database_name) {
                    dbDropdown.selectedIndex = i;
                    break;
                }
            }
        }
    }
    
    // Set checkboxes
    const remoteChk = document.getElementById('remoteCheckbox');
    const localChk = document.getElementById('localhostCheckbox');
    if (remoteChk) remoteChk.checked = project.include_remote == 1;
    if (localChk) localChk.checked = project.include_localhost == 1;
    
    // Add backend items (use 'backend' key)
    if (project.backends && project.backends.length > 0) {
        project.backends.forEach(item => {
            addDynamicItem('backend', item.name, item.desc);
        });
    }
    
    // Add page items (use 'page' key - NOT 'pages')
    if (project.pages && project.pages.length > 0) {
        project.pages.forEach(item => {
            addDynamicItem('page', item.name, item.desc);
        });
    }
    
    // Add frontend items (use 'frontend' key)
    if (project.frontends && project.frontends.length > 0) {
        project.frontends.forEach(item => {
            addDynamicItem('frontend', item.name, item.desc);
        });
    }
    
    showToast('Project data loaded to dashboard', 'success');
}

// On project select from dropdown
function onProjectSelect() {
    const selector = document.getElementById('projectSelector');
    const selectedId = selector.value;
    
    if (!selectedId) {
        currentProjectId = null;
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'get_project');
    formData.append('id', selectedId);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success && data.project) {
            applyProjectToDashboard(data.project);
            currentProjectId = data.project.id;
            showToast('Project "' + data.project.name + '" loaded', 'info');
        }
    })
    .catch(err => console.error('Error:', err));
}

// Delete current project
function deleteCurrentProject() {
    const selector = document.getElementById('projectSelector');
    const selectedId = selector.value;
    
    if (!selectedId) {
        showToast('Please select a project to delete', 'warning');
        return;
    }
    
    const selectedOption = selector.options[selector.selectedIndex];
    projectToDelete = {
        id: selectedId,
        name: selectedOption.textContent
    };
    
    document.getElementById('deleteProjectName').textContent = '"' + projectToDelete.name + '"';
    document.getElementById('deleteProjectPopup').classList.add('active');
}

// Confirm delete project
function confirmDeleteProject() {
    if (!projectToDelete) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_project');
    formData.append('id', projectToDelete.id);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            if (currentProjectId == projectToDelete.id) {
                currentProjectId = null;
            }
            closeProjectPopup('deleteProjectPopup');
            showToast('Project deleted successfully!', 'success');
            loadProjectsList();
            resetDashboardProject();
            
            if (data.operationTime) {
                addSpeedEntry({ 
                    time: parseFloat(data.operationTime), 
                    type: data.operationType, 
                    connection: data.connectionType, 
                    timestamp: Date.now() 
                });
                updateSpeedMonitor();
            }
        } else {
            showToast(data.message || 'Error deleting project', 'error');
        }
    })
    .catch(err => {
        console.error('Error:', err);
        showToast('Error deleting project', 'error');
    });
}

// Reset dashboard project
function resetDashboardProject() {
    currentProjectId = null;
    document.getElementById('projectSelector').value = '';
    resetDashboardItems();
    showToast('Dashboard reset to empty state', 'info');
}

// Reset only the items (not the project selection)
function resetDashboardItems() {
    // IMPORTANT: Clear the dynamicItems arrays first!
    if (typeof dynamicItems !== 'undefined') {
        dynamicItems.backend = [];
        dynamicItems.page = [];
        dynamicItems.frontend = [];
        saveDynamicItems(); // Save cleared state
    }
    
    // Clear backend items HTML
    const backendGrid = document.getElementById('backendItemsGrid');
    if (backendGrid) {
        backendGrid.innerHTML = `
            <div class="dynamic-empty-state" id="backendEmptyState">
                <i class="fas fa-file-code"></i>
                <p>No backend items</p>
                <small>Click "Add" to create one</small>
            </div>
        `;
    }
    updateDynamicCount('backend');
    
    // Clear page items HTML
    const pageGrid = document.getElementById('pageItemsGrid');
    if (pageGrid) {
        pageGrid.innerHTML = `
            <div class="dynamic-empty-state" id="pageEmptyState">
                <i class="fas fa-file-alt"></i>
                <p>No page items</p>
                <small>Click "Add" to create one</small>
            </div>
        `;
    }
    updateDynamicCount('page');
    
    // Clear frontend items HTML
    const frontendGrid = document.getElementById('frontendItemsGrid');
    if (frontendGrid) {
        frontendGrid.innerHTML = `
            <div class="dynamic-empty-state" id="frontendEmptyState">
                <i class="fas fa-paint-brush"></i>
                <p>No frontend items</p>
                <small>Click "Add" to create one</small>
            </div>
        `;
    }
    updateDynamicCount('frontend');
    
    // Reset checkboxes
    const remoteChk = document.getElementById('remoteCheckbox');
    const localChk = document.getElementById('localhostCheckbox');
    if (remoteChk) remoteChk.checked = false;
    if (localChk) localChk.checked = false;
    
    // Reset database dropdown
    const dbDropdown = document.getElementById('dbDropdown');
    if (dbDropdown) dbDropdown.selectedIndex = 0;
}

// Helper: Escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Close popups on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.project-popup-overlay.active').forEach(popup => {
            popup.classList.remove('active');
        });
    }
});

// Close popup when clicking outside
document.querySelectorAll('.project-popup-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.remove('active');
        }
    });
});
</script>
<!-- End Project Management Popups -->

</body>
</html>





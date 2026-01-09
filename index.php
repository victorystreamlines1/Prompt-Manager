<?php
// ============================================
// SUPER ADMIN AUTHENTICATION SYSTEM
// ============================================

// Suppress PHP errors/warnings for AJAX requests to prevent HTML in JSON responses
if (isset($_POST['action'])) {
    error_reporting(0);
    ini_set('display_errors', 0);
}

session_start();

// Super Admin Password
define('SUPER_ADMIN_PASSWORD', 'GL_Admin');
define('REMEMBER_ME_COOKIE', 'super_admin_remember');
define('REMEMBER_ME_DURATION', 30 * 24 * 60 * 60); // 30 days

// Check Remember Me Cookie
if (!isset($_SESSION['super_admin_logged_in']) && isset($_COOKIE[REMEMBER_ME_COOKIE])) {
    $cookieValue = $_COOKIE[REMEMBER_ME_COOKIE];
    $expectedCookie = md5(SUPER_ADMIN_PASSWORD . 'super_admin_salt');
    
    if ($cookieValue === $expectedCookie) {
        $_SESSION['super_admin_logged_in'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['remembered'] = true;
    }
}

// Handle Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_login'])) {
    $password = $_POST['password'] ?? '';
    $rememberMe = isset($_POST['remember_me']) && $_POST['remember_me'] === '1';
    
    if ($password === SUPER_ADMIN_PASSWORD) {
        $_SESSION['super_admin_logged_in'] = true;
        $_SESSION['login_time'] = time();
        
        // Set Remember Me Cookie
        if ($rememberMe) {
            $cookieValue = md5(SUPER_ADMIN_PASSWORD . 'super_admin_salt');
            setcookie(REMEMBER_ME_COOKIE, $cookieValue, time() + REMEMBER_ME_DURATION, '/', '', false, true);
            $_SESSION['remembered'] = true;
        }
        
        header('Location: index.php');
        exit;
    } else {
        $login_error = 'Invalid password! Please try again.';
    }
}

// Handle Logout
if (isset($_GET['logout'])) {
    // Delete Remember Me Cookie
    if (isset($_COOKIE[REMEMBER_ME_COOKIE])) {
        setcookie(REMEMBER_ME_COOKIE, '', time() - 3600, '/', '', false, true);
        unset($_COOKIE[REMEMBER_ME_COOKIE]);
    }
    
    session_destroy();
    header('Location: index.php');
    exit;
}

// Check if logged in
$isLoggedIn = isset($_SESSION['super_admin_logged_in']) && $_SESSION['super_admin_logged_in'] === true;

// If not logged in, show login form
if (!$isLoggedIn) {
    showLoginForm($login_error ?? '');
    exit;
}

// Load excluded pages list
$excludedFile = __DIR__ . '/catalog_excluded.json';
$excludedPages = [];
if (file_exists($excludedFile)) {
    $excludedPages = json_decode(file_get_contents($excludedFile), true) ?: [];
}

// ============================================
// LOGIN FORM FUNCTION
// ============================================
function showLoginForm($error = '') {
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Login - Catalog</title>
    <link rel="icon" type="image/png" href="FuturisticLogo.png">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }
        
        /* Animated Background Particles */
        body::before {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(251,191,36,0.3) 0%, transparent 70%);
            border-radius: 50%;
            top: -100px;
            left: -100px;
            animation: float 20s infinite ease-in-out;
        }
        
        body::after {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(34,197,94,0.2) 0%, transparent 70%);
            border-radius: 50%;
            bottom: -150px;
            right: -150px;
            animation: float 25s infinite ease-in-out reverse;
        }
        
        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            25% { transform: translate(50px, -50px) scale(1.1); }
            50% { transform: translate(-30px, 30px) scale(0.9); }
            75% { transform: translate(40px, 50px) scale(1.05); }
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 50px 45px;
            width: 100%;
            max-width: 480px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
            border: 2px solid rgba(255, 255, 255, 0.2);
            animation: slideInUp 0.6s ease-out;
            position: relative;
            z-index: 10;
        }
        
        @keyframes slideInUp {
            from { opacity: 0; transform: translateY(50px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .logo-container {
            text-align: center;
            margin-bottom: 35px;
            animation: logoFloat 3s ease-in-out infinite;
        }
        
        @keyframes logoFloat {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        .logo-container img {
            width: 100px;
            height: 100px;
            filter: drop-shadow(0 8px 25px rgba(0,0,0,0.4));
            margin-bottom: 20px;
        }
        
        .login-title {
            font-size: 32px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .login-subtitle {
            text-align: center;
            color: rgba(255, 255, 255, 0.8);
            font-size: 15px;
            margin-bottom: 35px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-label {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
            color: #fff;
            font-weight: 600;
            font-size: 14px;
        }
        
        .password-input-wrapper {
            position: relative;
        }
        
        .form-input {
            width: 100%;
            padding: 16px 50px 16px 18px;
            background: rgba(255, 255, 255, 0.15);
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            color: #fff;
            font-size: 16px;
            transition: all 0.3s ease;
            font-family: 'Consolas', monospace;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #fbbf24;
            background: rgba(255, 255, 255, 0.2);
            box-shadow: 0 0 20px rgba(251, 191, 36, 0.4);
        }
        
        .form-input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }
        
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.2);
            border: none;
            width: 35px;
            height: 35px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            transition: all 0.3s ease;
        }
        
        .toggle-password:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-50%) scale(1.1);
        }
        
        .login-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #22c55e 0%, #15803d 100%);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 17px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(34, 197, 94, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .login-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(34, 197, 94, 0.6);
        }
        
        .login-btn:active {
            transform: translateY(-1px);
        }
        
        .error-message {
            background: rgba(239, 68, 68, 0.2);
            border: 2px solid rgba(239, 68, 68, 0.5);
            border-radius: 12px;
            padding: 14px 18px;
            margin-bottom: 25px;
            color: #fca5a5;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: shake 0.5s ease-in-out;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            50% { transform: translateX(10px); }
            75% { transform: translateX(-5px); }
        }
        
        .security-badge {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .security-badge-content {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(34, 197, 94, 0.2);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            color: #86efac;
            border: 1px solid rgba(34, 197, 94, 0.3);
        }
        
        /* Remember Me Checkbox Styles */
        .remember-me-container {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .remember-me-label {
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            user-select: none;
            padding: 10px 16px;
            background: rgba(255, 255, 255, 0.08);
            border: 2px solid transparent;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .remember-me-label:hover {
            background: rgba(255, 255, 255, 0.12);
        }
        
        .remember-me-checkbox {
            position: absolute;
            opacity: 0;
            cursor: pointer;
            height: 0;
            width: 0;
        }
        
        .checkbox-custom {
            position: relative;
            width: 22px;
            height: 22px;
            background: rgba(255, 255, 255, 0.15);
            border: 2px solid rgba(255, 255, 255, 0.4);
            border-radius: 6px;
            transition: all 0.3s ease;
            flex-shrink: 0;
        }
        
        .remember-me-checkbox:checked ~ .checkbox-custom {
            background: linear-gradient(135deg, #22c55e 0%, #15803d 100%);
            border-color: #22c55e;
            box-shadow: 0 4px 15px rgba(34, 197, 94, 0.4);
        }
        
        .checkbox-custom::after {
            content: '';
            position: absolute;
            display: none;
            left: 6px;
            top: 2px;
            width: 6px;
            height: 11px;
            border: solid white;
            border-width: 0 2.5px 2.5px 0;
            transform: rotate(45deg);
        }
        
        .remember-me-checkbox:checked ~ .checkbox-custom::after {
            display: block;
            animation: checkmark 0.3s ease-in-out;
        }
        
        @keyframes checkmark {
            0% { transform: rotate(45deg) scale(0); }
            50% { transform: rotate(45deg) scale(1.2); }
            100% { transform: rotate(45deg) scale(1); }
        }
        
        .checkbox-text {
            display: flex;
            align-items: center;
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
            font-weight: 500;
        }
        
        .remember-me-label:hover .checkbox-custom {
            border-color: rgba(251, 191, 36, 0.6);
            box-shadow: 0 0 10px rgba(251, 191, 36, 0.2);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-container">
            <img src="FuturisticLogo.png" alt="Logo">
            <div class="login-title">🔐 Super Admin</div>
            <div class="login-subtitle">Enter password to access Catalog</div>
        </div>
        
        <?php if ($error): ?>
            <div class="error-message">
                <span style="font-size: 20px;">❌</span>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label">
                    <span>🔑</span>
                    <span>Admin Password</span>
                </label>
                <div class="password-input-wrapper">
                    <input type="password" id="passwordInput" name="password" class="form-input" placeholder="Enter super admin password..." required autofocus>
                    <button type="button" class="toggle-password" onclick="togglePassword()" title="Show/Hide Password">
                        <span id="toggleIcon">👁️</span>
                    </button>
                </div>
            </div>
            
            <!-- Remember Me Checkbox -->
            <div class="remember-me-container" style="margin-bottom: 25px;">
                <label class="remember-me-label">
                    <input type="checkbox" id="rememberMeCheckbox" name="remember_me" value="1" class="remember-me-checkbox">
                    <span class="checkbox-custom"></span>
                    <span class="checkbox-text">
                        <span style="font-size: 16px; margin-right: 6px;">🔒</span>
                        <span>Remember me for 30 days</span>
                    </span>
                </label>
            </div>
            
            <button type="submit" name="admin_login" class="login-btn">
                <span style="font-size: 20px;">🚀</span>
                <span>Access Catalog</span>
            </button>
        </form>
        
        <div class="security-badge">
            <div class="security-badge-content">
                <span>🛡️</span>
                <span>Secured by Super Admin Authentication</span>
            </div>
        </div>
    </div>
    
    <script>
        function togglePassword() {
            const input = document.getElementById('passwordInput');
            const icon = document.getElementById('toggleIcon');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.textContent = '🙈';
            } else {
                input.type = 'password';
                icon.textContent = '👁️';
            }
        }
        
        // Auto-focus on password input
        document.getElementById('passwordInput').focus();
        
        // Add keyboard shortcut (Ctrl+Enter to submit)
        document.getElementById('passwordInput').addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'Enter') {
                this.form.submit();
            }
        });
        
        // Remember Me checkbox animation
        const rememberMeCheckbox = document.getElementById('rememberMeCheckbox');
        const rememberMeLabel = document.querySelector('.remember-me-label');
        
        rememberMeCheckbox.addEventListener('change', function() {
            if (this.checked) {
                rememberMeLabel.style.background = 'rgba(34, 197, 94, 0.15)';
                rememberMeLabel.style.borderColor = 'rgba(34, 197, 94, 0.3)';
                
                // Success animation
                rememberMeLabel.style.animation = 'none';
                setTimeout(() => {
                    rememberMeLabel.style.animation = 'pulse 0.5s ease-in-out';
                }, 10);
            } else {
                rememberMeLabel.style.background = 'rgba(255, 255, 255, 0.08)';
                rememberMeLabel.style.borderColor = 'transparent';
            }
        });
        
        // Pulse animation for checkbox
        const style = document.createElement('style');
        style.textContent = `
            @keyframes pulse {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.05); }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
    <?php
}

// Handle toggle page visibility via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_visibility') {
    // Prevent any output before JSON
    ob_clean();
    header('Content-Type: application/json');
    
    try {
        $filename = isset($_POST['filename']) ? $_POST['filename'] : '';
        $exclude = isset($_POST['exclude']) && $_POST['exclude'] === 'true';
        
        if (empty($filename)) {
            echo json_encode(['success' => false, 'message' => 'Filename required']);
            exit;
        }
        
        // Define excluded file path
        $excludedFile = __DIR__ . '/catalog_excluded.json';
        
        // Load current excluded list
        $excludedPages = [];
        if (file_exists($excludedFile)) {
            $content = @file_get_contents($excludedFile);
            if ($content !== false) {
                $decoded = @json_decode($content, true);
                if (is_array($decoded)) {
                    $excludedPages = $decoded;
                }
            }
        }
        
        if ($exclude) {
            // Add to excluded list
            if (!in_array($filename, $excludedPages)) {
                $excludedPages[] = $filename;
            }
        } else {
            // Remove from excluded list
            $excludedPages = array_values(array_diff($excludedPages, [$filename]));
        }
        
        // Save updated list
        $jsonContent = json_encode($excludedPages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($jsonContent !== false && @file_put_contents($excludedFile, $jsonContent) !== false) {
            echo json_encode([
                'success' => true,
                'message' => $exclude ? 'Page excluded from catalog' : 'Page included in catalog',
                'excluded' => $exclude
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update catalog. Check file permissions.']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// Handle file deletion via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    header('Content-Type: application/json');
    
    $filename = isset($_POST['filename']) ? $_POST['filename'] : '';
    $filepath = __DIR__ . '/' . basename($filename); // basename for security
    
    // Security check: don't allow deleting index.php or system files
    $blockedFiles = ['index.php', 'catalog.html', 'default.php', 'backend.php'];
    
    if (in_array(basename($filename), $blockedFiles)) {
        echo json_encode([
            'success' => false,
            'message' => 'This file is protected and cannot be deleted.'
        ]);
        exit;
    }
    
    if (file_exists($filepath) && is_file($filepath)) {
        if (unlink($filepath)) {
            echo json_encode([
                'success' => true,
                'message' => 'File deleted successfully!'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to delete file. Permission denied.'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'File not found.'
        ]);
    }
    exit;
}

// ============================================
// CHECK IF SUPER ADMIN EXISTS
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'check_super_admin') {
    header('Content-Type: application/json');
    
    $filename = $_POST['filename'] ?? '';
    $filepath = __DIR__ . '/' . basename($filename);
    
    if (!file_exists($filepath)) {
        echo json_encode(['exists' => false, 'error' => 'File not found']);
        exit;
    }
    
    $content = file_get_contents($filepath);
    $hasSuperAdmin = (strpos($content, 'SUPER_ADMIN_PASSWORD') !== false || 
                      strpos($content, 'page_admin_logged_in') !== false);
    
    echo json_encode(['exists' => $hasSuperAdmin]);
    exit;
}

// ============================================
// REMOVE SUPER ADMIN FROM PAGE
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove_super_admin') { // OPEN 1
    ob_clean();
    header('Content-Type: application/json');
    
    try { // OPEN 2
        $filename = $_POST['filename'] ?? '';
        $filepath = __DIR__ . '/' . basename($filename);
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (!in_array($extension, ['php', 'html'])) { // OPEN 3
            echo json_encode(['success' => false, 'message' => 'Only PHP and HTML files are supported']);
            exit;
        } // CLOSE 3
        
        if (!file_exists($filepath)) { // OPEN 4
            echo json_encode(['success' => false, 'message' => 'File not found']);
            exit;
        } // CLOSE 4
        
        // Read file content
        $content = file_get_contents($filepath);
        
        // Check if Super Admin exists
        $hasSuperAdmin = (strpos($content, 'SUPER_ADMIN_PASSWORD') !== false || strpos($content, 'page_admin_logged_in') !== false);
        
        if (!$hasSuperAdmin) { // OPEN 5
            echo json_encode(['success' => false, 'message' => 'No Super Admin protection found in this file!']);
            exit;
        } // CLOSE 5
        
        // Remove Super Admin code - Pattern 1 (supports both old full format and new compact format)
        $pattern1 = '/(<\?php\s*)(\/\/ ={40,}\s*\/\/ SUPER ADMIN AUTHENTICATION SYSTEM.*?(?:function showPageLoginForm.*?<\/html>|showPageLoginForm\(\);).*?\?>)/s';
        $newContent = preg_replace($pattern1, '', $content);
        
        // Pattern 2: If still contains markers, try alternative removal
        if (strpos($newContent, 'SUPER_ADMIN_PASSWORD') !== false) { // OPEN 6
            $startPos = strpos($content, '// ============================================');
            $endPos = strpos($content, '?>', $startPos);
            
            if ($startPos !== false && $endPos !== false) { // OPEN 7
                $phpTagPos = strrpos(substr($content, 0, $startPos), '<?php');
                
                if ($phpTagPos !== false && $phpTagPos < $startPos) { // OPEN 8
                    $beforeCode = substr($content, 0, $startPos);
                    $afterCode = substr($content, $endPos + 2);
                    
                    $betweenPhpAndCode = substr($content, $phpTagPos + 5, $startPos - $phpTagPos - 5);
                    if (trim($betweenPhpAndCode) === '') { // OPEN 9
                        $beforeCode = substr($content, 0, $phpTagPos);
                    } // CLOSE 9
                    
                    $newContent = $beforeCode . $afterCode;
                } // CLOSE 8
            } // CLOSE 7
        } // CLOSE 6
        
        // Clean up any remaining empty PHP tags
        $newContent = preg_replace('/<\?php\s*\?>/s', '', $newContent);
        
        // Clean up multiple empty lines
        $newContent = preg_replace('/\n{3,}/', "\n\n", $newContent);
        
        // Write back to file
        if (file_put_contents($filepath, $newContent) !== false) { // OPEN 10
            echo json_encode(['success' => true, 'message' => 'Super Admin protection removed successfully!']);
        } else { // CLOSE 10, OPEN 11
            echo json_encode(['success' => false, 'message' => 'Failed to write to file. Check permissions.']);
        } // CLOSE 11
        
    } catch (Exception $e) { // CLOSE 2, OPEN 12
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    } // CLOSE 12
    
    exit;
} // CLOSE 1

// ============================================
// BULK SUPER ADMIN OPERATIONS (ADD/REMOVE TO ALL)
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'bulk_super_admin') {
    ob_clean();
    header('Content-Type: application/json');
    
    try {
        $operation = $_POST['operation'] ?? ''; // 'add' or 'remove'
        $password = $_POST['password'] ?? 'GL_Admin';
        
        if (!in_array($operation, ['add', 'remove'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid operation']);
            exit;
        }
        
        // Get all PHP and HTML files
        $allFiles = array_merge(glob(__DIR__ . '/*.php'), glob(__DIR__ . '/*.html'));
        
        // Load excluded list
        $excludedFile = __DIR__ . '/catalog_excluded.json';
        $excludedPages = [];
        if (file_exists($excludedFile)) {
            $content = @file_get_contents($excludedFile);
            if ($content !== false) {
                $decoded = @json_decode($content, true);
                if (is_array($decoded)) {
                    $excludedPages = $decoded;
                }
            }
        }
        
        $results = [
            'total' => 0,
            'processed' => 0,
            'skipped' => 0,
            'failed' => 0,
            'details' => []
        ];
        
        foreach ($allFiles as $filepath) {
            $filename = basename($filepath);
            
            // Skip excluded files
            if (in_array($filename, $excludedPages)) {
                continue;
            }
            
            // Skip index.php itself
            if ($filename === 'index.php') {
                continue;
            }
            
            $results['total']++;
            
            // Check if file already has Super Admin
            $content = @file_get_contents($filepath);
            if ($content === false) {
                $results['failed']++;
                $results['details'][] = [
                    'file' => $filename,
                    'status' => 'failed',
                    'reason' => 'Could not read file'
                ];
                continue;
            }
            
            $hasSuperAdmin = (strpos($content, 'SUPER_ADMIN_PASSWORD') !== false || 
                              strpos($content, 'page_admin_logged_in') !== false);
            
            if ($operation === 'add') {
                if ($hasSuperAdmin) {
                    $results['skipped']++;
                    $results['details'][] = [
                        'file' => $filename,
                        'status' => 'skipped',
                        'reason' => 'Already has Super Admin'
                    ];
                    continue;
                }
                
                // Skip HTML files - they cannot execute PHP authentication
                $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                $newFilepath = $filepath;
                $newFilename = $filename;
                $converted = false;
                
                if ($extension === 'html') {
                    // HTML files cannot execute PHP code
                    $results['skipped']++;
                    $results['details'][] = [
                        'file' => $filename,
                        'status' => 'skipped',
                        'reason' => "HTML files not supported (cannot execute PHP). Rename to .php manually if needed."
                    ];
                    continue;
                }
                
                // Generate Super Admin code (using proper function)
                $superAdminCode = <<<'PHP_CODE'
<?php
// ============================================
// SUPER ADMIN AUTHENTICATION SYSTEM
// ============================================
session_start();

define('SUPER_ADMIN_PASSWORD', 'PASSWORD_PLACEHOLDER');
define('REMEMBER_ME_COOKIE', 'page_admin_remember_PAGE_ID');
define('REMEMBER_ME_DURATION', 30 * 24 * 60 * 60);

if (!isset($_SESSION['page_admin_logged_in_PAGE_ID']) && isset($_COOKIE[REMEMBER_ME_COOKIE])) {
    $cookieValue = $_COOKIE[REMEMBER_ME_COOKIE];
    $expectedCookie = md5(SUPER_ADMIN_PASSWORD . 'page_admin_salt');
    if ($cookieValue === $expectedCookie) {
        $_SESSION['page_admin_logged_in_PAGE_ID'] = true;
        $_SESSION['page_login_time_PAGE_ID'] = time();
        $_SESSION['page_remembered_PAGE_ID'] = true;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['page_admin_login'])) {
    $pwd = $_POST['password'] ?? '';
    $remMe = isset($_POST['remember_me']) && $_POST['remember_me'] === '1';
    if ($pwd === SUPER_ADMIN_PASSWORD) {
        $_SESSION['page_admin_logged_in_PAGE_ID'] = true;
        $_SESSION['page_login_time_PAGE_ID'] = time();
        if ($remMe) {
            $cookieValue = md5(SUPER_ADMIN_PASSWORD . 'page_admin_salt');
            setcookie(REMEMBER_ME_COOKIE, $cookieValue, time() + REMEMBER_ME_DURATION, '/', '', false, true);
            $_SESSION['page_remembered_PAGE_ID'] = true;
        }
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

if (isset($_GET['page_logout'])) {
    if (isset($_COOKIE[REMEMBER_ME_COOKIE])) {
        setcookie(REMEMBER_ME_COOKIE, '', time() - 3600, '/', '', false, true);
    }
    unset($_SESSION['page_admin_logged_in_PAGE_ID']);
    unset($_SESSION['page_login_time_PAGE_ID']);
    unset($_SESSION['page_remembered_PAGE_ID']);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

$page_isLoggedIn = isset($_SESSION['page_admin_logged_in_PAGE_ID']) && $_SESSION['page_admin_logged_in_PAGE_ID'] === true;

if (!$page_isLoggedIn) {
    function showPageLoginForm() {
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Login</title><style>*{margin:0;padding:0;box-sizing:border-box}body{font-family:Arial;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh;display:flex;align-items:center;justify-content:center}.login-box{background:rgba(255,255,255,0.95);padding:40px;border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,0.3);width:90%;max-width:400px}h2{color:#667eea;margin-bottom:20px;text-align:center}input{width:100%;padding:12px;margin:10px 0;border:2px solid #ddd;border-radius:8px;font-size:16px}input:focus{outline:none;border-color:#667eea}button{width:100%;padding:12px;background:linear-gradient(135deg,#22c55e 0%,#15803d 100%);color:white;border:none;border-radius:8px;font-size:16px;font-weight:600;cursor:pointer;margin-top:10px}button:hover{transform:translateY(-2px)}</style></head><body><div class="login-box"><h2>🔒 Protected Page</h2><form method="POST"><input type="password" name="password" placeholder="Enter password..." required autofocus><button type="submit" name="page_admin_login">Access Page</button></form></div></body></html>';
    }
    showPageLoginForm();
    exit;
}
?>
PHP_CODE;
                
                // Replace placeholders
                $pageId = preg_replace('/[^a-zA-Z0-9_]/', '_', pathinfo($filename, PATHINFO_FILENAME));
                $superAdminCode = str_replace('PASSWORD_PLACEHOLDER', addslashes($password), $superAdminCode);
                $superAdminCode = str_replace('PAGE_ID', $pageId, $superAdminCode);
                
                // Add code to content
                if (!preg_match('/^\s*<\?php/', $content)) {
                    $newContent = $superAdminCode . "\n" . $content;
                } else {
                    $newContent = preg_replace('/(<\?php\s*)/', '$1' . "\n" . substr($superAdminCode, 5) . "\n", $content, 1);
                }
                
                // Write to file
                if (@file_put_contents($newFilepath, $newContent) !== false) {
                    // If converted, delete old HTML file
                    if ($converted && $newFilepath !== $filepath) {
                        @unlink($filepath);
                    }
                    
                    $results['processed']++;
                    $results['details'][] = [
                        'file' => $converted ? "$filename → $newFilename" : $filename,
                        'status' => 'success',
                        'action' => $converted ? 'added & converted to PHP' : 'added'
                    ];
                } else {
                    $results['failed']++;
                    $results['details'][] = [
                        'file' => $filename,
                        'status' => 'failed',
                        'reason' => 'Could not write file'
                    ];
                }
                
            } else { // remove
                if (!$hasSuperAdmin) {
                    $results['skipped']++;
                    $results['details'][] = [
                        'file' => $filename,
                        'status' => 'skipped',
                        'reason' => 'No Super Admin found'
                    ];
                    continue;
                }
                
                // Remove Super Admin code (both old and new formats)
                $pattern1 = '/(<\?php\s*)(\/\/ ={40,}\s*\/\/ SUPER ADMIN AUTHENTICATION SYSTEM.*?(?:function showPageLoginForm.*?<\/html>|showPageLoginForm\(\);).*?\?>)/s';
                $newContent = preg_replace($pattern1, '', $content);
                
                // Alternative removal
                if (strpos($newContent, 'SUPER_ADMIN_PASSWORD') !== false) {
                    $startPos = strpos($content, '// ============================================');
                    $endPos = strpos($content, '?>', $startPos);
                    
                    if ($startPos !== false && $endPos !== false) {
                        $phpTagPos = strrpos(substr($content, 0, $startPos), '<?php');
                        
                        if ($phpTagPos !== false && $phpTagPos < $startPos) {
                            $beforeCode = substr($content, 0, $startPos);
                            $afterCode = substr($content, $endPos + 2);
                            
                            $betweenPhpAndCode = substr($content, $phpTagPos + 5, $startPos - $phpTagPos - 5);
                            if (trim($betweenPhpAndCode) === '') {
                                $beforeCode = substr($content, 0, $phpTagPos);
                            }
                            
                            $newContent = $beforeCode . $afterCode;
                        }
                    }
                }
                
                // Clean up
                $newContent = preg_replace('/<\?php\s*\?>/s', '', $newContent);
                $newContent = preg_replace('/\n{3,}/', "\n\n", $newContent);
                
                if (@file_put_contents($filepath, $newContent) !== false) {
                    $results['processed']++;
                    $results['details'][] = [
                        'file' => $filename,
                        'status' => 'success',
                        'action' => 'removed'
                    ];
                } else {
                    $results['failed']++;
                    $results['details'][] = [
                        'file' => $filename,
                        'status' => 'failed',
                        'reason' => 'Could not write file'
                    ];
                }
            }
        }
        
        echo json_encode([
            'success' => true,
            'operation' => $operation,
            'results' => $results
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
    exit;
}

// ============================================
// GET ACTIVE DATABASE CONNECTIONS
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_active_connections') {
    ob_clean();
    header('Content-Type: application/json');
    
    try {
        $activeConnections = [];
        
        // ========================================
        // FETCH FROM report_prompt_databases TABLE
        // ========================================
        $hubDbHost = 'srv1788.hstgr.io';
        $hubDbName = 'u419999707_Mohamed';
        $hubDbUser = 'u419999707_Abuammar';
        $hubDbPass = 'P@master5007';
        $hubDbPort = 3306;
        $hubTableName = 'report_prompt_databases';
        
        try {
            $hubPdo = new PDO(
                "mysql:host=$hubDbHost;port=$hubDbPort;dbname=$hubDbName;charset=utf8mb4",
                $hubDbUser,
                $hubDbPass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT => 5
                ]
            );
            
            // Fetch all connections from the hub table
            $stmt = $hubPdo->query("SELECT * FROM `$hubTableName` ORDER BY createdAt DESC");
            $hubConnections = $stmt->fetchAll();
            
            // Add all connections from the hub table
            foreach ($hubConnections as $conn) {
                $connId = $conn['id'] ?? uniqid();
                $connName = $conn['name'] ?? $conn['dbName'];
                $connType = $conn['type'] ?? 'remote';
                $host = $conn['host'] ?? '';
                $dbName = $conn['dbName'] ?? '';
                $username = $conn['username'] ?? '';
                $password = $conn['password'] ?? '';
                $port = $conn['port'] ?? '3306';
                
                // Determine icon based on type
                $icon = '🌐';
                if ($connType === 'local') $icon = '🖥️';
                elseif ($connType === 'cloud') $icon = '☁️';
                elseif ($connType === 'vps') $icon = '🖧';
                elseif ($connType === 'dedicated') $icon = '🏢';
                elseif ($connType === 'shared') $icon = '🌐';
                
                $activeConnections[] = [
                    'id' => $connId,
                    'name' => "{$icon} {$connName}",
                    'type' => $connType,
                    'host' => $host,
                    'dbName' => $dbName,
                    'username' => $username,
                    'password' => $password,
                    'port' => $port,
                    'status' => 'available',
                    'source' => 'hub'
                ];
            }
        } catch (Exception $e) {
            // Hub database not available, log error but continue
            error_log('Hub DB connection failed: ' . $e->getMessage());
        }
        
        // ========================================
        // ALSO ADD LOCALHOST DATABASES (Laragon)
        // ========================================
        try {
            $localPdo = new PDO("mysql:host=localhost;port=3306", 'root', '');
            $localPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Get databases
            $stmt = $localPdo->query("SHOW DATABASES");
            $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($databases as $db) {
                if (!in_array($db, ['information_schema', 'performance_schema', 'mysql', 'sys'])) {
                    $activeConnections[] = [
                        'id' => 'localhost_' . $db,
                        'name' => "🖥️ Localhost: $db",
                        'type' => 'local',
                        'host' => 'localhost',
                        'dbName' => $db,
                        'username' => 'root',
                        'password' => '',
                        'port' => '3306',
                        'status' => 'connected',
                        'source' => 'localhost'
                    ];
                }
            }
        } catch (Exception $e) {
            // Localhost not available, continue
        }
        
        echo json_encode([
            'success' => true,
            'connections' => $activeConnections,
            'total' => count($activeConnections)
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
    exit;
}

// Note: Super Admin code is now generated inline for better control and HTML->PHP conversion support

// ============================================
// ADD SUPER ADMIN TO PAGE
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_super_admin') {
    header('Content-Type: application/json');
    
    $filename = $_POST['filename'] ?? '';
    $password = $_POST['password'] ?? 'GL_Admin';
    
    // Security: only allow .php or .html files
    $filepath = __DIR__ . '/' . basename($filename);
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if (!in_array($extension, ['php', 'html'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Only PHP and HTML files are supported'
        ]);
        exit;
    }
    
    if (!file_exists($filepath)) {
        echo json_encode([
            'success' => false,
            'message' => 'File not found'
        ]);
        exit;
    }
    
    // Read file content
    $content = file_get_contents($filepath);
    
    // Check if Super Admin already exists
    if (strpos($content, 'SUPER_ADMIN_PASSWORD') !== false || strpos($content, 'super_admin_logged_in') !== false) {
        echo json_encode([
            'success' => false,
            'message' => 'Super Admin protection already exists in this file!'
        ]);
        exit;
    }
    
    // Generate Super Admin code
    $superAdminCode = <<<'PHP_CODE'
<?php
// ============================================
// SUPER ADMIN AUTHENTICATION SYSTEM
// ============================================
session_start();

// Super Admin Password
define('SUPER_ADMIN_PASSWORD', 'PASSWORD_PLACEHOLDER');
define('REMEMBER_ME_COOKIE', 'page_admin_remember_PAGE_ID');
define('REMEMBER_ME_DURATION', 30 * 24 * 60 * 60); // 30 days

// Check Remember Me Cookie
if (!isset($_SESSION['page_admin_logged_in_PAGE_ID']) && isset($_COOKIE[REMEMBER_ME_COOKIE])) {
    $cookieValue = $_COOKIE[REMEMBER_ME_COOKIE];
    $expectedCookie = md5(SUPER_ADMIN_PASSWORD . 'page_admin_salt');
    
    if ($cookieValue === $expectedCookie) {
        $_SESSION['page_admin_logged_in_PAGE_ID'] = true;
        $_SESSION['page_login_time_PAGE_ID'] = time();
        $_SESSION['page_remembered_PAGE_ID'] = true;
    }
}

// Handle Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['page_admin_login'])) {
    $password = $_POST['password'] ?? '';
    $rememberMe = isset($_POST['remember_me']) && $_POST['remember_me'] === '1';
    
    if ($password === SUPER_ADMIN_PASSWORD) {
        $_SESSION['page_admin_logged_in_PAGE_ID'] = true;
        $_SESSION['page_login_time_PAGE_ID'] = time();
        
        if ($rememberMe) {
            $cookieValue = md5(SUPER_ADMIN_PASSWORD . 'page_admin_salt');
            setcookie(REMEMBER_ME_COOKIE, $cookieValue, time() + REMEMBER_ME_DURATION, '/', '', false, true);
            $_SESSION['page_remembered_PAGE_ID'] = true;
        }
        
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $page_login_error = 'Invalid password! Please try again.';
    }
}

// Handle Logout
if (isset($_GET['page_logout'])) {
    if (isset($_COOKIE[REMEMBER_ME_COOKIE])) {
        setcookie(REMEMBER_ME_COOKIE, '', time() - 3600, '/', '', false, true);
        unset($_COOKIE[REMEMBER_ME_COOKIE]);
    }
    
    unset($_SESSION['page_admin_logged_in_PAGE_ID']);
    unset($_SESSION['page_login_time_PAGE_ID']);
    unset($_SESSION['page_remembered_PAGE_ID']);
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Check if logged in
$page_isLoggedIn = isset($_SESSION['page_admin_logged_in_PAGE_ID']) && $_SESSION['page_admin_logged_in_PAGE_ID'] === true;

// If not logged in, show login form
if (!$page_isLoggedIn) {
    showPageLoginForm($page_login_error ?? '');
    exit;
}

// ============================================
// LOGIN FORM FUNCTION
// ============================================
function showPageLoginForm($error = '') {
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Login</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; position: relative; overflow: hidden; }
        body::before { content: ''; position: absolute; width: 300px; height: 300px; background: radial-gradient(circle, rgba(251,191,36,0.3) 0%, transparent 70%); border-radius: 50%; top: -100px; left: -100px; animation: float 20s infinite ease-in-out; }
        body::after { content: ''; position: absolute; width: 400px; height: 400px; background: radial-gradient(circle, rgba(34,197,94,0.2) 0%, transparent 70%); border-radius: 50%; bottom: -150px; right: -150px; animation: float 25s infinite ease-in-out reverse; }
        @keyframes float { 0%, 100% { transform: translate(0, 0) scale(1); } 25% { transform: translate(50px, -50px) scale(1.1); } 50% { transform: translate(-30px, 30px) scale(0.9); } 75% { transform: translate(40px, 50px) scale(1.05); } }
        .login-container { background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(20px); border-radius: 24px; padding: 50px 45px; width: 100%; max-width: 480px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4); border: 2px solid rgba(255, 255, 255, 0.2); animation: slideInUp 0.6s ease-out; position: relative; z-index: 10; }
        @keyframes slideInUp { from { opacity: 0; transform: translateY(50px); } to { opacity: 1; transform: translateY(0); } }
        .logo-container { text-align: center; margin-bottom: 35px; animation: logoFloat 3s ease-in-out infinite; }
        @keyframes logoFloat { 0%, 100% { transform: translateY(0px); } 50% { transform: translateY(-10px); } }
        .login-title { font-size: 32px; font-weight: 700; text-align: center; margin-bottom: 10px; background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .login-subtitle { text-align: center; color: rgba(255, 255, 255, 0.8); font-size: 15px; margin-bottom: 35px; }
        .form-group { margin-bottom: 25px; }
        .form-label { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; color: #fff; font-weight: 600; font-size: 14px; }
        .password-input-wrapper { position: relative; }
        .form-input { width: 100%; padding: 16px 50px 16px 18px; background: rgba(255, 255, 255, 0.15); border: 2px solid rgba(255, 255, 255, 0.3); border-radius: 12px; color: #fff; font-size: 16px; transition: all 0.3s ease; font-family: 'Consolas', monospace; }
        .form-input:focus { outline: none; border-color: #fbbf24; background: rgba(255, 255, 255, 0.2); box-shadow: 0 0 20px rgba(251, 191, 36, 0.4); }
        .form-input::placeholder { color: rgba(255, 255, 255, 0.5); }
        .toggle-password { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); background: rgba(255, 255, 255, 0.2); border: none; width: 35px; height: 35px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 18px; transition: all 0.3s ease; }
        .toggle-password:hover { background: rgba(255, 255, 255, 0.3); transform: translateY(-50%) scale(1.1); }
        .login-btn { width: 100%; padding: 16px; background: linear-gradient(135deg, #22c55e 0%, #15803d 100%); border: none; border-radius: 12px; color: white; font-size: 17px; font-weight: 700; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 6px 20px rgba(34, 197, 94, 0.4); display: flex; align-items: center; justify-content: center; gap: 10px; }
        .login-btn:hover { transform: translateY(-3px); box-shadow: 0 10px 30px rgba(34, 197, 94, 0.6); }
        .error-message { background: rgba(239, 68, 68, 0.2); border: 2px solid rgba(239, 68, 68, 0.5); border-radius: 12px; padding: 14px 18px; margin-bottom: 25px; color: #fca5a5; font-size: 14px; display: flex; align-items: center; gap: 10px; animation: shake 0.5s ease-in-out; }
        @keyframes shake { 0%, 100% { transform: translateX(0); } 25% { transform: translateX(-10px); } 50% { transform: translateX(10px); } 75% { transform: translateX(-5px); } }
        .remember-me-container { display: flex; align-items: center; justify-content: center; margin-bottom: 25px; }
        .remember-me-label { display: flex; align-items: center; gap: 12px; cursor: pointer; user-select: none; padding: 10px 16px; background: rgba(255, 255, 255, 0.08); border: 2px solid transparent; border-radius: 10px; transition: all 0.3s ease; }
        .remember-me-label:hover { background: rgba(255, 255, 255, 0.12); }
        .remember-me-checkbox { position: absolute; opacity: 0; cursor: pointer; height: 0; width: 0; }
        .checkbox-custom { position: relative; width: 22px; height: 22px; background: rgba(255, 255, 255, 0.15); border: 2px solid rgba(255, 255, 255, 0.4); border-radius: 6px; transition: all 0.3s ease; flex-shrink: 0; }
        .remember-me-checkbox:checked ~ .checkbox-custom { background: linear-gradient(135deg, #22c55e 0%, #15803d 100%); border-color: #22c55e; box-shadow: 0 4px 15px rgba(34, 197, 94, 0.4); }
        .checkbox-custom::after { content: ''; position: absolute; display: none; left: 6px; top: 2px; width: 6px; height: 11px; border: solid white; border-width: 0 2.5px 2.5px 0; transform: rotate(45deg); }
        .remember-me-checkbox:checked ~ .checkbox-custom::after { display: block; animation: checkmark 0.3s ease-in-out; }
        @keyframes checkmark { 0% { transform: rotate(45deg) scale(0); } 50% { transform: rotate(45deg) scale(1.2); } 100% { transform: rotate(45deg) scale(1); } }
        .checkbox-text { display: flex; align-items: center; color: rgba(255, 255, 255, 0.9); font-size: 14px; font-weight: 500; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-container">
            <div style="font-size: 64px; margin-bottom: 15px;">🔐</div>
            <div class="login-title">Super Admin</div>
            <div class="login-subtitle">This page is protected</div>
        </div>
        
        <?php if ($error): ?>
            <div class="error-message">
                <span style="font-size: 20px;">❌</span>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label">
                    <span>🔑</span>
                    <span>Admin Password</span>
                </label>
                <div class="password-input-wrapper">
                    <input type="password" id="passwordInput" name="password" class="form-input" placeholder="Enter super admin password..." required autofocus>
                    <button type="button" class="toggle-password" onclick="togglePassword()" title="Show/Hide Password">
                        <span id="toggleIcon">👁️</span>
                    </button>
                </div>
            </div>
            
            <div class="remember-me-container">
                <label class="remember-me-label">
                    <input type="checkbox" id="rememberMeCheckbox" name="remember_me" value="1" class="remember-me-checkbox">
                    <span class="checkbox-custom"></span>
                    <span class="checkbox-text">
                        <span style="font-size: 16px; margin-right: 6px;">🔒</span>
                        <span>Remember me for 30 days</span>
                    </span>
                </label>
            </div>
            
            <button type="submit" name="page_admin_login" class="login-btn">
                <span style="font-size: 20px;">🚀</span>
                <span>Access Page</span>
            </button>
        </form>
    </div>
    
    <script>
        function togglePassword() {
            const input = document.getElementById('passwordInput');
            const icon = document.getElementById('toggleIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.textContent = '🙈';
            } else {
                input.type = 'password';
                icon.textContent = '👁️';
            }
        }
        document.getElementById('passwordInput').focus();
    </script>
</body>
</html>
    <?php
}
?>
PHP_CODE;
    
    // Replace placeholders
    $pageId = preg_replace('/[^a-zA-Z0-9_]/', '_', pathinfo($filename, PATHINFO_FILENAME));
    $superAdminCode = str_replace('PASSWORD_PLACEHOLDER', addslashes($password), $superAdminCode);
    $superAdminCode = str_replace('PAGE_ID', $pageId, $superAdminCode);
    
    // For HTML files, we cannot add PHP authentication
    $newFilepath = $filepath;
    $newFilename = $filename;
    
    if ($extension === 'html') {
        // HTML files cannot execute PHP code
        echo json_encode([
            'success' => false,
            'message' => "Cannot add Super Admin to HTML files!\n\nHTML files cannot execute PHP code. Please use one of these options:\n\n1. Manually rename '{$filename}' to a .php extension first\n2. Use the file as .php instead of .html\n3. Update references in other files (prompter.php, appmaker.php) to use the new .php filename\n\nNote: Automatic conversion has been disabled to prevent breaking links in other files.",
            'reason' => 'html_not_supported',
            'filename' => $filename,
            'suggestion' => 'Rename to .php extension manually'
        ]);
        exit;
    }
    
    // Add Super Admin code
    if (!preg_match('/^\s*<\?php/', $content)) {
        $newContent = $superAdminCode . "\n" . $content;
    } else {
        $newContent = preg_replace('/(<\?php\s*)/', '$1' . "\n" . substr($superAdminCode, 5) . "\n", $content, 1);
    }
    
    // Write to file
    if (file_put_contents($newFilepath, $newContent)) {
        // If we converted HTML to PHP, delete the old HTML file
        if ($extension === 'html' && $newFilepath !== $filepath) {
            @unlink($filepath);
            
            echo json_encode([
                'success' => true,
                'message' => 'Super Admin added successfully!',
                'password' => $password,
                'filename' => $filename,
                'converted' => true,
                'old_file' => $filename,
                'new_file' => $newFilename,
                'note' => "File converted from .html to .php to support authentication"
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'message' => 'Super Admin protection added successfully!',
                'password' => $password,
                'filename' => $filename
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to write to file. Check permissions.'
        ]);
    }
    exit;
}

// Handle file rename via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'rename') {
    header('Content-Type: application/json');
    
    $oldFilename = isset($_POST['old_filename']) ? $_POST['old_filename'] : '';
    $newFilename = isset($_POST['new_filename']) ? $_POST['new_filename'] : '';
    
    $oldFilepath = __DIR__ . '/' . basename($oldFilename);
    $newFilepath = __DIR__ . '/' . basename($newFilename);
    
    // Security check: don't allow renaming index.php or system files
    $blockedFiles = ['index.php', 'catalog.html', 'default.php', 'backend.php'];
    
    if (in_array(basename($oldFilename), $blockedFiles)) {
        echo json_encode([
            'success' => false,
            'message' => 'This file is protected and cannot be renamed.'
        ]);
        exit;
    }
    
    // Validate new filename
    if (empty($newFilename)) {
        echo json_encode([
            'success' => false,
            'message' => 'New filename cannot be empty.'
        ]);
        exit;
    }
    
    // Check if new filename already exists
    if (file_exists($newFilepath)) {
        echo json_encode([
            'success' => false,
            'message' => 'A file with this name already exists.'
        ]);
        exit;
    }
    
    // Validate file extension (must be .html or .php)
    $extension = strtolower(pathinfo($newFilename, PATHINFO_EXTENSION));
    if (!in_array($extension, ['html', 'php'])) {
        echo json_encode([
            'success' => false,
            'message' => 'File extension must be .html or .php'
        ]);
        exit;
    }
    
    // Check if old file exists
    if (!file_exists($oldFilepath) || !is_file($oldFilepath)) {
        echo json_encode([
            'success' => false,
            'message' => 'Original file not found.'
        ]);
        exit;
    }
    
    // Attempt to rename
    if (rename($oldFilepath, $newFilepath)) {
        echo json_encode([
            'success' => true,
            'message' => 'File renamed successfully!',
            'new_filename' => $newFilename
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to rename file. Permission denied.'
        ]);
    }
    exit;
}

// Handle file duplicate via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'duplicate') {
    header('Content-Type: application/json');
    
    $sourceFilename = isset($_POST['source_filename']) ? $_POST['source_filename'] : '';
    $newFilename = isset($_POST['new_filename']) ? $_POST['new_filename'] : '';
    
    $sourceFilepath = __DIR__ . '/' . basename($sourceFilename);
    $newFilepath = __DIR__ . '/' . basename($newFilename);
    
    // Validate source filename
    if (empty($sourceFilename)) {
        echo json_encode([
            'success' => false,
            'message' => 'Source filename cannot be empty.'
        ]);
        exit;
    }
    
    // Validate new filename
    if (empty($newFilename)) {
        echo json_encode([
            'success' => false,
            'message' => 'New filename cannot be empty.'
        ]);
        exit;
    }
    
    // Check if source file exists
    if (!file_exists($sourceFilepath) || !is_file($sourceFilepath)) {
        echo json_encode([
            'success' => false,
            'message' => 'Source file not found.'
        ]);
        exit;
    }
    
    // Check if new filename already exists
    if (file_exists($newFilepath)) {
        echo json_encode([
            'success' => false,
            'message' => 'A file with this name already exists.'
        ]);
        exit;
    }
    
    // Validate file extension (must be .html or .php)
    $extension = strtolower(pathinfo($newFilename, PATHINFO_EXTENSION));
    if (!in_array($extension, ['html', 'php'])) {
        echo json_encode([
            'success' => false,
            'message' => 'File extension must be .html or .php'
        ]);
        exit;
    }
    
    // Attempt to copy file
    if (copy($sourceFilepath, $newFilepath)) {
        echo json_encode([
            'success' => true,
            'message' => 'File duplicated successfully!',
            'new_filename' => $newFilename
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to duplicate file. Permission denied.'
        ]);
    }
    exit;
}

// Handle creating new file via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_file') {
    header('Content-Type: application/json');
    
    $filename = isset($_POST['filename']) ? $_POST['filename'] : '';
    $fileType = isset($_POST['file_type']) ? $_POST['file_type'] : 'html';
    $template = isset($_POST['template']) ? $_POST['template'] : 'empty';
    $pageTitle = isset($_POST['page_title']) ? $_POST['page_title'] : '';
    $includeTitle = isset($_POST['include_title']) && $_POST['include_title'] === 'true';
    
    $filepath = __DIR__ . '/' . basename($filename);
    
    // Validate filename
    if (empty($filename)) {
        echo json_encode([
            'success' => false,
            'message' => 'Filename cannot be empty.'
        ]);
        exit;
    }
    
    // Check if file already exists
    if (file_exists($filepath)) {
        echo json_encode([
            'success' => false,
            'message' => 'A file with this name already exists.'
        ]);
        exit;
    }
    
    // Validate file extension
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if (!in_array($extension, ['html', 'php'])) {
        echo json_encode([
            'success' => false,
            'message' => 'File extension must be .html or .php'
        ]);
        exit;
    }
    
    // Handle file uploads (Favicon & Logo)
    $faviconPath = null;
    $logoPath = null;
    
    // Create assets directory if it doesn't exist
    $assetsDir = __DIR__ . '/assets';
    if (!is_dir($assetsDir)) {
        mkdir($assetsDir, 0777, true);
    }
    
    // Process Favicon upload
    if (isset($_FILES['favicon']) && $_FILES['favicon']['error'] === UPLOAD_ERR_OK) {
        $faviconTmpPath = $_FILES['favicon']['tmp_name'];
        $faviconOriginalName = $_FILES['favicon']['name'];
        $faviconExtension = strtolower(pathinfo($faviconOriginalName, PATHINFO_EXTENSION));
        
        // Validate image
        $allowedImageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'ico', 'svg', 'webp'];
        if (in_array($faviconExtension, $allowedImageExtensions)) {
            // Generate unique filename
            $faviconNewName = 'favicon_' . time() . '_' . uniqid() . '.' . $faviconExtension;
            $faviconDestPath = $assetsDir . '/' . $faviconNewName;
            
            // Move uploaded file
            if (move_uploaded_file($faviconTmpPath, $faviconDestPath)) {
                $faviconPath = 'assets/' . $faviconNewName;
            }
        }
    }
    
    // Process Logo upload
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $logoTmpPath = $_FILES['logo']['tmp_name'];
        $logoOriginalName = $_FILES['logo']['name'];
        $logoExtension = strtolower(pathinfo($logoOriginalName, PATHINFO_EXTENSION));
        
        // Validate image
        $allowedImageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'];
        if (in_array($logoExtension, $allowedImageExtensions)) {
            // Generate unique filename
            $logoNewName = 'logo_' . time() . '_' . uniqid() . '.' . $logoExtension;
            $logoDestPath = $assetsDir . '/' . $logoNewName;
            
            // Move uploaded file
            if (move_uploaded_file($logoTmpPath, $logoDestPath)) {
                $logoPath = 'assets/' . $logoNewName;
            }
        }
    }
    
    // Load template generator
    require_once __DIR__ . '/template_generator.php';
    
    // Generate content using template generator (with favicon and logo paths)
    $content = generateTemplate($template, $pageTitle, $includeTitle, $fileType, $faviconPath, $logoPath);
    
    // Create the file
    if (file_put_contents($filepath, $content) !== false) {
        echo json_encode([
            'success' => true,
            'message' => 'File created successfully!',
            'filename' => $filename,
            'favicon' => $faviconPath,
            'logo' => $logoPath
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create file. Permission denied.'
        ]);
    }
    exit;
}

// Handle editing page (regenerate with new settings)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_page') {
    header('Content-Type: application/json');
    
    $filename = isset($_POST['filename']) ? $_POST['filename'] : '';
    $template = isset($_POST['template']) ? $_POST['template'] : 'empty';
    $pageTitle = isset($_POST['page_title']) ? $_POST['page_title'] : '';
    $includeTitle = isset($_POST['include_title']) && $_POST['include_title'] === 'true';
    
    $filepath = __DIR__ . '/' . basename($filename);
    
    // Validate filename
    if (empty($filename)) {
        echo json_encode([
            'success' => false,
            'message' => 'Filename cannot be empty.'
        ]);
        exit;
    }
    
    // Check if file exists
    if (!file_exists($filepath)) {
        echo json_encode([
            'success' => false,
            'message' => 'File not found.'
        ]);
        exit;
    }
    
    // Verify it's a catalog-generated page
    $currentContent = file_get_contents($filepath);
    if (strpos($currentContent, '<!-- CATALOG_GENERATED_PAGE -->') === false) {
        echo json_encode([
            'success' => false,
            'message' => 'This page was not created by catalog and cannot be edited.'
        ]);
        exit;
    }
    
    // Determine file type
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $fileType = $extension === 'php' ? 'php' : 'html';
    
    // Extract current favicon and logo paths from content
    $currentFaviconPath = null;
    $currentLogoPath = null;
    
    if (preg_match('/href="(assets\/favicon_[^"]+)"/i', $currentContent, $matches)) {
        $currentFaviconPath = $matches[1];
    }
    
    if (preg_match('/src="(assets\/logo_[^"]+)"/i', $currentContent, $matches)) {
        $currentLogoPath = $matches[1];
    }
    
    // Handle file uploads (Favicon & Logo)
    $faviconPath = $currentFaviconPath; // Keep current if not changed
    $logoPath = $currentLogoPath; // Keep current if not changed
    
    // Create assets directory if it doesn't exist
    $assetsDir = __DIR__ . '/assets';
    if (!is_dir($assetsDir)) {
        mkdir($assetsDir, 0777, true);
    }
    
    // Process Favicon upload if provided
    if (isset($_FILES['favicon']) && $_FILES['favicon']['error'] === UPLOAD_ERR_OK) {
        $faviconTmpPath = $_FILES['favicon']['tmp_name'];
        $faviconOriginalName = $_FILES['favicon']['name'];
        $faviconExtension = strtolower(pathinfo($faviconOriginalName, PATHINFO_EXTENSION));
        
        $allowedImageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'ico', 'svg', 'webp'];
        if (in_array($faviconExtension, $allowedImageExtensions)) {
            $faviconNewName = 'favicon_' . time() . '_' . uniqid() . '.' . $faviconExtension;
            $faviconDestPath = $assetsDir . '/' . $faviconNewName;
            
            if (move_uploaded_file($faviconTmpPath, $faviconDestPath)) {
                // Delete old favicon if exists
                if ($currentFaviconPath && file_exists(__DIR__ . '/' . $currentFaviconPath)) {
                    unlink(__DIR__ . '/' . $currentFaviconPath);
                }
                $faviconPath = 'assets/' . $faviconNewName;
            }
        }
    }
    
    // Process Logo upload if provided
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $logoTmpPath = $_FILES['logo']['tmp_name'];
        $logoOriginalName = $_FILES['logo']['name'];
        $logoExtension = strtolower(pathinfo($logoOriginalName, PATHINFO_EXTENSION));
        
        $allowedImageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'];
        if (in_array($logoExtension, $allowedImageExtensions)) {
            $logoNewName = 'logo_' . time() . '_' . uniqid() . '.' . $logoExtension;
            $logoDestPath = $assetsDir . '/' . $logoNewName;
            
            if (move_uploaded_file($logoTmpPath, $logoDestPath)) {
                // Delete old logo if exists
                if ($currentLogoPath && file_exists(__DIR__ . '/' . $currentLogoPath)) {
                    unlink(__DIR__ . '/' . $currentLogoPath);
                }
                $logoPath = 'assets/' . $logoNewName;
            }
        }
    }
    
    // Create backup of current file
    $backupPath = $filepath . '.backup_' . time();
    copy($filepath, $backupPath);
    
    // Load template generator
    require_once __DIR__ . '/template_generator.php';
    
    // Generate new content using template generator
    $content = generateTemplate($template, $pageTitle, $includeTitle, $fileType, $faviconPath, $logoPath);
    
    // Overwrite the file with new content
    if (file_put_contents($filepath, $content) !== false) {
        echo json_encode([
            'success' => true,
            'message' => 'Page updated successfully!',
            'filename' => $filename,
            'backup' => basename($backupPath)
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update file. Permission denied.'
        ]);
    }
    exit;
}

// Handle toggling back button (add or remove)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_back_button') {
    header('Content-Type: application/json');
    
    $filename = isset($_POST['filename']) ? $_POST['filename'] : '';
    $filepath = __DIR__ . '/' . basename($filename);
    
    if (!file_exists($filepath) || !is_file($filepath)) {
        echo json_encode([
            'success' => false,
            'message' => 'File not found.'
        ]);
        exit;
    }
    
    $content = file_get_contents($filepath);
    
    // Check if back button already exists
    $buttonExists = strpos($content, 'id="backToCatalogBtn"') !== false || strpos($content, 'catalog-back-btn') !== false;
    
    if ($buttonExists) {
        // Remove the back button
        // Remove everything from <!-- Back to Catalog Button --> to <!-- End Back to Catalog Button -->
        $pattern = '/\n?<!--\s*Back to Catalog Button\s*-->.*?<!--\s*End Back to Catalog Button\s*-->\n?/s';
        $content = preg_replace($pattern, '', $content);
        
        if (file_put_contents($filepath, $content)) {
            echo json_encode([
                'success' => true,
                'message' => 'Back button removed successfully!',
                'action' => 'removed'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to write to file. Permission denied.'
            ]);
        }
        exit;
    }
    
    // Back button HTML with inline CSS
    $backButtonCode = <<<'HTML'

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

HTML;
    
    // Insert before closing </body> tag, or at the end if no </body>
    if (stripos($content, '</body>') !== false) {
        $content = str_ireplace('</body>', $backButtonCode . '</body>', $content);
    } else {
        // If no </body> tag, append at the end
        $content .= $backButtonCode;
    }
    
    // Write back to file
    if (file_put_contents($filepath, $content)) {
        echo json_encode([
            'success' => true,
            'message' => 'Back button added successfully!',
            'action' => 'added'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to write to file. Permission denied.'
        ]);
    }
    exit;
}

// Handle toggling platform button (add or remove)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_platform_button') {
    header('Content-Type: application/json');
    
    $filename = isset($_POST['filename']) ? $_POST['filename'] : '';
    $filepath = __DIR__ . '/' . basename($filename);
    
    if (!file_exists($filepath) || !is_file($filepath)) {
        echo json_encode([
            'success' => false,
            'message' => 'File not found.'
        ]);
        exit;
    }
    
    $content = file_get_contents($filepath);
    
    // Check if platform button already exists
    $buttonExists = strpos($content, 'id="backToPlatformBtn"') !== false || strpos($content, 'platform-back-btn') !== false;
    
    if ($buttonExists) {
        // Remove the platform button
        // Remove everything from <!-- Back to Platform Button --> to <!-- End Back to Platform Button -->
        $pattern = '/\n?<!--\s*Back to Platform Button\s*-->.*?<!--\s*End Back to Platform Button\s*-->\n?/s';
        $content = preg_replace($pattern, '', $content);
        
        if (file_put_contents($filepath, $content)) {
            echo json_encode([
                'success' => true,
                'message' => 'Platform button removed successfully!',
                'action' => 'removed'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to write to file. Permission denied.'
            ]);
        }
        exit;
    }
    
    // Platform button HTML with inline CSS (positioned above catalog button)
    // Build the platform URL with the current filename
    $platformButtonCode = "\n\n<!-- Back to Platform Button -->
<a href=\"platform_ai.php?file=" . urlencode(basename($filename)) . "\" id=\"backToPlatformBtn\" class=\"platform-back-btn\" style=\"position: fixed; bottom: 120px; left: 30px; width: 70px; height: 70px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5); z-index: 9999; text-decoration: none; transition: all 0.3s ease; border: 3px solid rgba(255, 255, 255, 0.3); animation: platform-pulse 2s infinite;\" title=\"Back to AI Platform\" onmouseover=\"this.style.transform='scale(1.15) rotate(-10deg)'; this.style.boxShadow='0 10px 35px rgba(102, 126, 234, 0.7)';\" onmouseout=\"this.style.transform='scale(1) rotate(0deg)'; this.style.boxShadow='0 8px 25px rgba(102, 126, 234, 0.5)';\">
    <svg xmlns=\"http://www.w3.org/2000/svg\" width=\"32\" height=\"32\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"white\" stroke-width=\"2.5\" stroke-linecap=\"round\" stroke-linejoin=\"round\" style=\"filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));\">
        <rect x=\"2\" y=\"3\" width=\"20\" height=\"14\" rx=\"2\" ry=\"2\"></rect>
        <line x1=\"8\" y1=\"21\" x2=\"16\" y2=\"21\"></line>
        <line x1=\"12\" y1=\"17\" x2=\"12\" y2=\"21\"></line>
    </svg>
</a>
<style>
@keyframes platform-pulse {
    0%, 100% { box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5), 0 0 0 0 rgba(102, 126, 234, 0.4); }
    50% { box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5), 0 0 0 10px rgba(102, 126, 234, 0); }
}
.platform-back-btn::after {
    content: 'AI Platform';
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
.platform-back-btn:hover::after {
    opacity: 1;
}
</style>
<!-- End Back to Platform Button -->

";
    
    // Insert before closing </body> tag, or at the end if no </body>
    if (stripos($content, '</body>') !== false) {
        $content = str_ireplace('</body>', $platformButtonCode . '</body>', $content);
    } else {
        // If no </body> tag, append at the end
        $content .= $platformButtonCode;
    }
    
    // Write back to file
    if (file_put_contents($filepath, $content)) {
        echo json_encode([
            'success' => true,
            'message' => 'Platform button added successfully!',
            'action' => 'added'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to write to file. Permission denied.'
        ]);
    }
    exit;
}

// Handle toggling code editor button (add or remove) - platform.php without AI
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_code_editor_button') {
    header('Content-Type: application/json');
    
    $filename = isset($_POST['filename']) ? $_POST['filename'] : '';
    $filepath = __DIR__ . '/' . basename($filename);
    
    if (!file_exists($filepath) || !is_file($filepath)) {
        echo json_encode([
            'success' => false,
            'message' => 'File not found.'
        ]);
        exit;
    }
    
    $content = file_get_contents($filepath);
    
    // Check if code editor button already exists
    $buttonExists = strpos($content, 'id="backToCodeEditorBtn"') !== false || strpos($content, 'code-editor-back-btn') !== false;
    
    if ($buttonExists) {
        // Remove the code editor button
        // Remove everything from <!-- Back to Code Editor Button --> to <!-- End Back to Code Editor Button -->
        $pattern = '/\n?<!--\s*Back to Code Editor Button\s*-->.*?<!--\s*End Back to Code Editor Button\s*-->\n?/s';
        $content = preg_replace($pattern, '', $content);
        
        if (file_put_contents($filepath, $content)) {
            echo json_encode([
                'success' => true,
                'message' => 'Code Editor button removed successfully!',
                'action' => 'removed'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to write to file. Permission denied.'
            ]);
        }
        exit;
    }
    
    // Code Editor button HTML with inline CSS (positioned above platform button)
    // Build the code editor URL with the current filename
    $codeEditorButtonCode = "\n\n<!-- Back to Code Editor Button -->
<a href=\"platform.php?file=" . urlencode(basename($filename)) . "\" id=\"backToCodeEditorBtn\" class=\"code-editor-back-btn\" style=\"position: fixed; bottom: 210px; left: 30px; width: 70px; height: 70px; background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 25px rgba(56, 239, 125, 0.5); z-index: 9999; text-decoration: none; transition: all 0.3s ease; border: 3px solid rgba(255, 255, 255, 0.3); animation: code-editor-pulse 2s infinite;\" title=\"Back to Code Editor\" onmouseover=\"this.style.transform='scale(1.15) rotate(-10deg)'; this.style.boxShadow='0 10px 35px rgba(56, 239, 125, 0.7)';\" onmouseout=\"this.style.transform='scale(1) rotate(0deg)'; this.style.boxShadow='0 8px 25px rgba(56, 239, 125, 0.5)';\">
    <svg xmlns=\"http://www.w3.org/2000/svg\" width=\"32\" height=\"32\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"white\" stroke-width=\"2.5\" stroke-linecap=\"round\" stroke-linejoin=\"round\" style=\"filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));\">
        <polyline points=\"16 18 22 12 16 6\"></polyline>
        <polyline points=\"8 6 2 12 8 18\"></polyline>
    </svg>
</a>
<style>
@keyframes code-editor-pulse {
    0%, 100% { box-shadow: 0 8px 25px rgba(56, 239, 125, 0.5), 0 0 0 0 rgba(56, 239, 125, 0.4); }
    50% { box-shadow: 0 8px 25px rgba(56, 239, 125, 0.5), 0 0 0 10px rgba(56, 239, 125, 0); }
}
.code-editor-back-btn::after {
    content: 'Code Editor';
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
.code-editor-back-btn:hover::after {
    opacity: 1;
}
</style>
<!-- End Back to Code Editor Button -->

";
    
    // Insert before closing </body> tag, or at the end if no </body>
    if (stripos($content, '</body>') !== false) {
        $content = str_ireplace('</body>', $codeEditorButtonCode . '</body>', $content);
    } else {
        // If no </body> tag, append at the end
        $content .= $codeEditorButtonCode;
    }
    
    // Write back to file
    if (file_put_contents($filepath, $content)) {
        echo json_encode([
            'success' => true,
            'message' => 'Code Editor button added successfully!',
            'action' => 'added'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to write to file. Permission denied.'
        ]);
    }
    exit;
}

// Check Transfer Link Status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'check_transfer_link') {
    header('Content-Type: application/json');
    
    $filename = $_POST['filename'] ?? '';
    $filepath = __DIR__ . '/' . basename($filename);
    
    if (!file_exists($filepath)) {
        echo json_encode(['exists' => false, 'error' => 'File not found']);
        exit;
    }
    
    $content = file_get_contents($filepath);
    $hasTransferLink = (strpos($content, 'id="autoTransferScript"') !== false || 
                        strpos($content, 'auto-transfer-redirect') !== false);
    
    echo json_encode(['exists' => $hasTransferLink]);
    exit;
}

// Handle toggling transfer link (add or remove redirect)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_transfer_link') {
    header('Content-Type: application/json');
    
    $filename = isset($_POST['filename']) ? $_POST['filename'] : '';
    $targetUrl = isset($_POST['target_url']) ? $_POST['target_url'] : '';
    $filepath = __DIR__ . '/' . basename($filename);
    
    if (!file_exists($filepath) || !is_file($filepath)) {
        echo json_encode([
            'success' => false,
            'message' => 'File not found.'
        ]);
        exit;
    }
    
    $content = file_get_contents($filepath);
    
    // Check if transfer link already exists
    $transferExists = strpos($content, 'id="autoTransferScript"') !== false || strpos($content, 'auto-transfer-redirect') !== false;
    
    if ($transferExists) {
        // Remove the transfer link script
        $pattern = '/\n?<!--\s*Auto Transfer Script\s*-->.*?<!--\s*End Auto Transfer Script\s*-->\n?/s';
        $content = preg_replace($pattern, '', $content);
        
        if (file_put_contents($filepath, $content)) {
            echo json_encode([
                'success' => true,
                'message' => 'Transfer link removed successfully!',
                'action' => 'removed'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to write to file. Permission denied.'
            ]);
        }
        exit;
    }
    
    // Validate target URL
    if (empty($targetUrl)) {
        echo json_encode([
            'success' => false,
            'message' => 'Target URL is required.'
        ]);
        exit;
    }
    
    // Transfer link script code
    $transferScriptCode = "\n\n<!-- Auto Transfer Script -->\n<script id=\"autoTransferScript\" class=\"auto-transfer-redirect\">\n    // Auto Transfer to: " . htmlspecialchars($targetUrl) . "\n    (function() {\n        console.log('🔄 Auto-transferring to: " . htmlspecialchars($targetUrl) . "');\n        window.location.href = '" . addslashes($targetUrl) . "';\n    })();\n</script>\n<!-- End Auto Transfer Script -->\n\n";
    
    // Insert at the end of <head> or before </head> tag
    if (stripos($content, '</head>') !== false) {
        $content = str_ireplace('</head>', $transferScriptCode . '</head>', $content);
    } else if (stripos($content, '<body>') !== false) {
        // If no </head>, insert before <body>
        $content = str_ireplace('<body>', $transferScriptCode . '<body>', $content);
    } else {
        // If no structure, prepend at beginning
        $content = $transferScriptCode . $content;
    }
    
    // Write back to file
    if (file_put_contents($filepath, $content)) {
        echo json_encode([
            'success' => true,
            'message' => 'Transfer link added successfully!',
            'action' => 'added',
            'target_url' => $targetUrl
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to write to file. Permission denied.'
        ]);
    }
    exit;
}

// Handle toggling iframe append (add or remove iframe)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_iframe') {
    header('Content-Type: application/json');
    
    $filename = isset($_POST['filename']) ? $_POST['filename'] : '';
    $iframeUrl = isset($_POST['iframe_url']) ? $_POST['iframe_url'] : '';
    $filepath = __DIR__ . '/' . basename($filename);
    
    if (!file_exists($filepath) || !is_file($filepath)) {
        echo json_encode([
            'success' => false,
            'message' => 'File not found.'
        ]);
        exit;
    }
    
    $content = file_get_contents($filepath);
    
    // Check if iframe already exists
    $iframeExists = strpos($content, 'id="appendedIframe"') !== false || strpos($content, 'appended-iframe-container') !== false;
    
    if ($iframeExists) {
        // Remove the iframe
        $pattern = '/\n?<!--\s*Appended iFrame\s*-->.*?<!--\s*End Appended iFrame\s*-->\n?/s';
        $content = preg_replace($pattern, '', $content);
        
        if (file_put_contents($filepath, $content)) {
            echo json_encode([
                'success' => true,
                'message' => 'iFrame removed successfully!',
                'action' => 'removed'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to write to file. Permission denied.'
            ]);
        }
        exit;
    }
    
    // Validate iframe URL
    if (empty($iframeUrl)) {
        echo json_encode([
            'success' => false,
            'message' => 'iFrame URL is required.'
        ]);
        exit;
    }
    
    // iFrame HTML code with responsive styling
    $iframeCode = <<<HTML


<!-- Appended iFrame -->
<div id="appendedIframe" class="appended-iframe-container" style="width: 100%; margin: 40px 0; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);">
    <div style="background: white; border-radius: 15px; padding: 15px; box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #e2e8f0;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 1.5rem;">🖼️</span>
                <h3 style="margin: 0; color: #2d3748; font-size: 1.1rem; font-weight: 700;">Embedded Content</h3>
            </div>
            <span style="font-size: 0.85rem; color: #718096; font-family: 'Courier New', monospace;">{$iframeUrl}</span>
        </div>
        <iframe src="{$iframeUrl}" 
                style="width: 100%; height: 600px; border: none; border-radius: 10px; box-shadow: 0 3px 15px rgba(0, 0, 0, 0.1);" 
                frameborder="0" 
                allowfullscreen
                loading="lazy"
                title="Embedded Content">
        </iframe>
    </div>
</div>
<!-- End Appended iFrame -->

HTML;
    
    // Insert before closing </body> tag, or at the end if no </body>
    if (stripos($content, '</body>') !== false) {
        $content = str_ireplace('</body>', $iframeCode . '</body>', $content);
    } else {
        // If no </body> tag, append at the end
        $content .= $iframeCode;
    }
    
    // Write back to file
    if (file_put_contents($filepath, $content)) {
        echo json_encode([
            'success' => true,
            'message' => 'iFrame added successfully!',
            'action' => 'added',
            'iframe_url' => $iframeUrl
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to write to file. Permission denied.'
        ]);
    }
    exit;
}

// Scan directory for HTML and PHP files
$directory = __DIR__;
$htmlFiles = glob($directory . '/*.html');
$phpFiles = glob($directory . '/*.php');

// Merge both arrays
$allFiles = array_merge($htmlFiles, $phpFiles);

$fileList = [];
foreach ($allFiles as $file) {
    $filename = basename($file);
    
    // Skip catalog files
    if ($filename === 'catalog.html' || $filename === 'index.php' || $filename === 'catalog_scanner.php') {
        continue;
    }
    
    $fileList[] = [
        'name' => pathinfo($filename, PATHINFO_FILENAME),
        'filename' => $filename,
        'size' => filesize($file),
        'modified' => filemtime($file),
        'path' => $filename,
        'extension' => strtoupper(pathinfo($filename, PATHINFO_EXTENSION))
    ];
}

// Sort by modified date (newest first)
usort($fileList, function($a, $b) {
    return $b['modified'] - $a['modified'];
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>App-AI</title>
    <link rel="icon" type="image/png" href="FuturisticLogo.png">
    <link rel="shortcut icon" type="image/png" href="FuturisticLogo.png">
    <link rel="apple-touch-icon" href="FuturisticLogo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        /* Animated background particles */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            pointer-events: none;
        }

        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 50%;
            animation: float 15s infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) translateX(0) rotate(0deg); }
            33% { transform: translateY(-100px) translateX(100px) rotate(120deg); }
            66% { transform: translateY(-50px) translateX(-100px) rotate(240deg); }
        }

        .container {
            max-width: 1400px;
            margin: 0 auto 0 280px;
            position: relative;
            z-index: 1;
        }

        /* Excluded Pages Sidebar */
        .excluded-sidebar {
            position: fixed;
            left: 20px;
            top: 20px;
            width: 250px;
            max-height: calc(100vh - 40px);
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            padding: 20px;
            z-index: 100;
            overflow-y: auto;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .excluded-sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .excluded-sidebar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }

        .excluded-sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 10px;
        }

        .excluded-sidebar-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.3);
        }

        .excluded-sidebar-header i {
            font-size: 1.5rem;
            color: white;
        }

        .excluded-sidebar-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: white;
            flex: 1;
        }

        .excluded-count {
            background: rgba(255, 107, 107, 0.8);
            color: white;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .excluded-list {
            list-style: none;
        }

        .excluded-item {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 10px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }

        .excluded-item:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateX(5px);
        }

        .excluded-item-number {
            display: inline-block;
            width: 24px;
            height: 24px;
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
            color: white;
            border-radius: 50%;
            text-align: center;
            line-height: 24px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-right: 8px;
        }

        .excluded-item-name {
            color: white;
            font-size: 0.85rem;
            font-weight: 500;
            display: block;
            margin-bottom: 8px;
            word-break: break-word;
        }

        .include-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: linear-gradient(135deg, #38ef7d 0%, #11998e 100%);
            color: white;
            padding: 6px 12px;
            border-radius: 8px;
            border: none;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(56, 239, 125, 0.3);
        }

        .include-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(56, 239, 125, 0.4);
        }

        .include-btn i {
            font-size: 0.7rem;
        }

        .excluded-empty {
            text-align: center;
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
            padding: 20px 10px;
        }

        .excluded-empty i {
            font-size: 2.5rem;
            margin-bottom: 10px;
            display: block;
            opacity: 0.5;
        }

        .header {
            text-align: center;
            margin-bottom: 50px;
            animation: fadeInDown 0.8s ease;
        }

        .header h1 {
            font-size: 3.5rem;
            color: white;
            text-shadow: 2px 4px 8px rgba(0, 0, 0, 0.3);
            margin-bottom: 15px;
            letter-spacing: 2px;
        }

        .header p {
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.9);
            text-shadow: 1px 2px 4px rgba(0, 0, 0, 0.2);
        }

        .stats-bar {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 20px 40px;
            margin-bottom: 40px;
            display: flex;
            justify-content: space-around;
            align-items: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            animation: fadeInUp 0.8s ease 0.2s both;
        }

        .stat-item {
            text-align: center;
            color: white;
        }

        .stat-item i {
            font-size: 2rem;
            margin-bottom: 10px;
            display: block;
        }

        .stat-item .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            display: block;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }

        .stat-item .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .search-box {
            margin-bottom: 30px;
            animation: fadeInUp 0.8s ease 0.3s both;
        }

        .search-input {
            width: 100%;
            padding: 18px 25px;
            padding-left: 55px;
            font-size: 1.1rem;
            border: none;
            border-radius: 50px;
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            transform: translateY(-2px);
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.3);
        }

        .search-wrapper {
            position: relative;
        }

        .search-icon {
            position: absolute;
            left: 25px;
            top: 50%;
            transform: translateY(-50%);
            color: #667eea;
            font-size: 1.3rem;
            z-index: 2;
        }

        .create-new-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            padding: 18px 35px;
            border-radius: 50px;
            border: none;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 10px 40px rgba(17, 153, 142, 0.3);
            transition: all 0.3s ease;
            margin-top: 20px;
            animation: fadeInUp 0.8s ease 0.4s both;
        }

        .create-new-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 50px rgba(17, 153, 142, 0.4);
            background: linear-gradient(135deg, #38ef7d 0%, #11998e 100%);
        }

        .create-new-btn:active {
            transform: translateY(-1px);
        }

        .create-new-btn i {
            font-size: 1.3rem;
        }

        /* Sort Controls */
        .sort-controls {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 15px;
            animation: fadeInUp 0.8s ease 0.45s both;
        }

        .sort-label {
            font-size: 0.95rem;
            font-weight: 600;
            color: white;
            display: flex;
            align-items: center;
            gap: 8px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .sort-label i {
            font-size: 1.1rem;
        }

        .sort-toggle-group {
            display: flex;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border-radius: 50px;
            padding: 5px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .sort-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 20px;
            border: none;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            background: transparent;
            color: rgba(255, 255, 255, 0.8);
            white-space: nowrap;
            position: relative;
            overflow: hidden;
        }

        .sort-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, #fff 0%, rgba(255, 255, 255, 0.9) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
            border-radius: 50px;
        }

        .sort-btn.active::before {
            opacity: 1;
        }

        .sort-btn:hover:not(.active) {
            background: rgba(255, 255, 255, 0.15);
            color: white;
        }

        .sort-btn.active {
            color: #667eea;
            font-weight: 700;
            box-shadow: 0 4px 15px rgba(255, 255, 255, 0.3);
        }

        .sort-btn.active i {
            animation: sortPulse 1s ease-in-out infinite;
        }

        @keyframes sortPulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.2);
            }
        }

        .sort-btn i {
            font-size: 1rem;
            position: relative;
            z-index: 1;
        }

        .sort-btn span {
            position: relative;
            z-index: 1;
        }

        /* Reset Sort Button */
        .reset-sort-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border: none;
            border-radius: 50%;
            background: rgba(255, 107, 107, 0.3);
            backdrop-filter: blur(10px);
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .reset-sort-btn:hover {
            transform: scale(1.1) rotate(180deg);
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
            box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4);
        }

        .reset-sort-btn i {
            font-size: 1rem;
        }

        .catalog-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 25px;
            animation: fadeInUp 0.8s ease 0.4s both;
        }

        .page-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
            border: 2px solid transparent;
            display: flex;
            flex-direction: column;
        }

        .page-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s ease;
        }

        .page-card:hover::before {
            left: 100%;
        }

        .page-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
            border-color: #667eea;
        }

        /* Page icon removed for space optimization */

        .page-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 8px;
            word-break: break-word;
            line-height: 1.3;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .page-title-icon {
            font-size: 1.35rem;
            flex-shrink: 0;
            line-height: 1;
            filter: drop-shadow(0 2px 4px rgba(102, 126, 234, 0.3));
        }

        .page-filename {
            font-size: 0.85rem;
            color: #718096;
            margin-bottom: 12px;
            font-family: 'Courier New', monospace;
            background: #f7fafc;
            padding: 6px 12px;
            border-radius: 8px;
            display: inline-block;
            word-break: break-all;
        }

        .file-type-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .file-type-badge.html {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }

        .file-type-badge.php {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }

        .page-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
            padding-top: 15px;
            margin-bottom: 15px;
            border-top: 1px solid #e2e8f0;
            font-size: 0.8rem;
            color: #a0aec0;
        }

        .page-date {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .page-size {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .open-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 3px 12px rgba(102, 126, 234, 0.3);
            border: none;
            cursor: pointer;
            font-size: 0.85rem;
            white-space: nowrap;
        }

        .open-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        /* AI Platform Button - Premium Look */
        .platform-ai-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: linear-gradient(135deg, #00ff88 0%, #00cc66 100%);
            color: #000;
            padding: 10px 18px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 255, 136, 0.4);
            border: none;
            cursor: pointer;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
            white-space: nowrap;
        }

        .platform-ai-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s ease;
        }

        .platform-ai-btn:hover::before {
            left: 100%;
        }

        .platform-ai-btn:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 6px 25px rgba(0, 255, 136, 0.6);
            background: linear-gradient(135deg, #00cc66 0%, #00aa55 100%);
        }

        .platform-ai-btn:active {
            transform: translateY(-1px) scale(1);
        }

        .platform-ai-btn i {
            font-size: 1rem;
            animation: robotPulse 2s ease-in-out infinite;
        }

        @keyframes robotPulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.15);
            }
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .delete-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
            color: white;
            padding: 10px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 3px 12px rgba(255, 107, 107, 0.3);
            border: none;
            cursor: pointer;
            font-size: 0.85rem;
            white-space: nowrap;
        }

        .delete-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4);
            background: linear-gradient(135deg, #ff5252 0%, #d63031 100%);
        }

        .open-btn {
            flex: 1;
        }

        .add-back-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 10px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 3px 12px rgba(240, 147, 251, 0.3);
            border: none;
            cursor: pointer;
            font-size: 0.85rem;
            white-space: nowrap;
            flex: 1 1 100%;
        }

        .add-back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(240, 147, 251, 0.4);
            background: linear-gradient(135deg, #f5576c 0%, #f093fb 100%);
        }

        .add-platform-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 3px 12px rgba(102, 126, 234, 0.3);
            border: none;
            cursor: pointer;
            font-size: 0.85rem;
            white-space: nowrap;
            flex: 1 1 100%;
        }

        .add-platform-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }

        .add-code-editor-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            padding: 10px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 3px 12px rgba(56, 239, 125, 0.3);
            border: none;
            cursor: pointer;
            font-size: 0.85rem;
            white-space: nowrap;
            flex: 1 1 100%;
        }

        .add-code-editor-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(56, 239, 125, 0.4);
            background: linear-gradient(135deg, #38ef7d 0%, #11998e 100%);
        }

        .transfer-link-toggle-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 10px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 3px 12px rgba(240, 147, 251, 0.3);
            border: none;
            cursor: pointer;
            font-size: 0.85rem;
            white-space: nowrap;
            flex: 1 1 100%;
        }

        .transfer-link-toggle-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(240, 147, 251, 0.4);
            background: linear-gradient(135deg, #f5576c 0%, #f093fb 100%);
        }
        
        .transfer-link-toggle-btn.has-transfer {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            box-shadow: 0 3px 12px rgba(255, 107, 107, 0.3);
        }
        
        .transfer-link-toggle-btn.has-transfer:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4);
            background: linear-gradient(135deg, #ee5a24 0%, #ff6b6b 100%);
        }
        
        .transfer-link-toggle-btn.checking {
            background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%);
            cursor: wait;
            opacity: 0.7;
        }

        .add-iframe-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            padding: 10px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 3px 12px rgba(79, 172, 254, 0.3);
            border: none;
            cursor: pointer;
            font-size: 0.85rem;
            white-space: nowrap;
            flex: 1 1 100%;
        }
        
        /* Add Super Admin Button */
        .add-admin-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
            color: white;
            padding: 10px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 3px 12px rgba(6, 182, 212, 0.4);
            border: none;
            cursor: pointer;
            font-size: 0.85rem;
            white-space: nowrap;
            flex: 1 1 100%;
        }
        
        .add-admin-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(6, 182, 212, 0.6);
            background: linear-gradient(135deg, #0891b2 0%, #06b6d4 100%);
        }
        
        .add-admin-btn i {
            animation: shield-pulse 2s infinite;
        }
        
        @keyframes shield-pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        /* Admin Toggle Button */
        .admin-toggle-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
            color: white;
            padding: 10px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 3px 12px rgba(6, 182, 212, 0.4);
            border: none;
            cursor: pointer;
            font-size: 0.85rem;
            white-space: nowrap;
            flex: 1 1 100%;
        }
        
        .admin-toggle-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(6, 182, 212, 0.6);
            background: linear-gradient(135deg, #0891b2 0%, #06b6d4 100%);
        }
        
        .admin-toggle-btn.has-admin {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            box-shadow: 0 3px 12px rgba(255, 107, 107, 0.4);
        }
        
        .admin-toggle-btn.has-admin:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 107, 0.6);
            background: linear-gradient(135deg, #ee5a24 0%, #ff6b6b 100%);
        }
        
        .admin-toggle-btn.checking {
            background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%);
            cursor: wait;
            opacity: 0.7;
        }
        
        .admin-toggle-btn i {
            animation: shield-pulse 2s infinite;
        }

        .add-iframe-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(79, 172, 254, 0.4);
            background: linear-gradient(135deg, #00f2fe 0%, #4facfe 100%);
        }

        .rename-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            background: linear-gradient(135deg, #ffd700 0%, #ffa500 100%);
            color: white;
            padding: 10px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 3px 12px rgba(255, 215, 0, 0.3);
            border: none;
            cursor: pointer;
            font-size: 0.85rem;
            white-space: nowrap;
        }

        .rename-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 215, 0, 0.4);
            background: linear-gradient(135deg, #ffa500 0%, #ff8c00 100%);
        }

        .duplicate-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            background: linear-gradient(135deg, #38ef7d 0%, #11998e 100%);
            color: white;
            padding: 10px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 3px 12px rgba(56, 239, 125, 0.3);
            border: none;
            cursor: pointer;
            font-size: 0.85rem;
            white-space: nowrap;
        }

        .duplicate-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(56, 239, 125, 0.4);
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }

        .edit-page-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 3px 12px rgba(102, 126, 234, 0.3);
            border: none;
            cursor: pointer;
            font-size: 0.85rem;
            white-space: nowrap;
        }

        .edit-page-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }

        .exclude-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            background: linear-gradient(135deg, #718096 0%, #4a5568 100%);
            color: white;
            padding: 10px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 3px 12px rgba(113, 128, 150, 0.3);
            border: none;
            cursor: pointer;
            font-size: 0.85rem;
            white-space: nowrap;
            flex: 1 1 100%;
        }

        .exclude-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(113, 128, 150, 0.4);
            background: linear-gradient(135deg, #4a5568 0%, #2d3748 100%);
        }

        .exclude-btn.excluded {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
        }

        .exclude-btn.excluded:hover {
            background: linear-gradient(135deg, #ff5252 0%, #d63031 100%);
            box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4);
        }

        /* Editor Choice Buttons */
        .editor-choice-wrapper {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-shrink: 0;
        }

        .editor-choice-label {
            font-size: 0.7rem;
            color: #888;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            white-space: nowrap;
        }

        .editor-choice-buttons {
            display: flex;
            gap: 4px;
            flex-wrap: nowrap;
        }

        .editor-choice-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 5px 10px;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 5px;
            border: 2px solid transparent;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            position: relative;
            overflow: hidden;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .editor-choice-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.1);
            transition: left 0.4s ease;
        }

        .editor-choice-btn:hover::before {
            left: 100%;
        }

        .editor-choice-btn.ai {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
        }

        .editor-choice-btn.ai:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.5);
            border-color: rgba(255, 255, 255, 0.3);
        }

        .editor-choice-btn.classic {
            background: linear-gradient(135deg, #2d2d30 0%, #1e1e1e 100%);
            color: #d4d4d4;
            border-color: #3e3e42;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        .editor-choice-btn.classic:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 255, 255, 0.1);
            border-color: #667eea;
            background: linear-gradient(135deg, #3e3e42 0%, #2d2d30 100%);
        }

        .editor-choice-btn i {
            font-size: 0.9rem;
        }

        .editor-choice-btn.ai i {
            animation: robotPulse 2s infinite;
        }

        @keyframes robotPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .button-group-full {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .button-row {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            align-items: center;
        }

        .button-row > *:not(.editor-choice-wrapper) {
            flex: 1 1 auto;
            min-width: 100px;
        }

        .button-row .platform-ai-btn {
            flex: 1 1 auto;
            min-width: 130px;
        }

        .button-row .editor-choice-wrapper {
            flex: 0 0 auto;
        }

        /* Delete Confirmation Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 650px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            animation: slideUp 0.3s ease-out;
        }
        
        .modal-content::-webkit-scrollbar {
            width: 8px;
        }
        
        .modal-content::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        .modal-content::-webkit-scrollbar-thumb {
            background: #667eea;
            border-radius: 10px;
        }
        
        .modal-content::-webkit-scrollbar-thumb:hover {
            background: #764ba2;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from { 
                opacity: 0;
                transform: translateY(30px);
            }
            to { 
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .modal-icon i {
            font-size: 2.5rem;
            color: white;
        }

        .modal-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 10px;
            text-align: center;
        }

        .modal-message {
            font-size: 1.1rem;
            color: #718096;
            margin-bottom: 10px;
            text-align: center;
            line-height: 1.6;
        }

        .modal-filename {
            font-size: 1rem;
            color: #e53e3e;
            font-weight: 600;
            text-align: center;
            padding: 10px 20px;
            background: rgba(255, 107, 107, 0.1);
            border-radius: 10px;
            margin-bottom: 30px;
            font-family: 'Courier New', monospace;
        }

        .modal-input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            font-family: 'Courier New', monospace;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            background: #f7fafc;
        }

        .modal-input:focus {
            outline: none;
            border-color: #ffa500;
            box-shadow: 0 0 0 3px rgba(255, 165, 0, 0.1);
            background: white;
        }

        .modal-input::placeholder {
            color: #a0aec0;
        }

        .modal-hint {
            font-size: 0.85rem;
            color: #718096;
            margin-top: -15px;
            margin-bottom: 20px;
            text-align: left;
        }

        .modal-buttons {
            display: flex;
            gap: 15px;
        }

        .modal-btn {
            flex: 1;
            padding: 15px 30px;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .modal-btn-cancel {
            background: #e2e8f0;
            color: #4a5568;
        }

        .modal-btn-cancel:hover {
            background: #cbd5e0;
            transform: translateY(-2px);
        }

        .modal-btn-confirm {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
        }

        .modal-btn-confirm:hover {
            background: linear-gradient(135deg, #ff5252 0%, #d63031 100%);
            box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4);
            transform: translateY(-2px);
        }

        .modal-btn-confirm:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .modal-file-type {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            justify-content: center;
        }

        .file-type-option {
            flex: 1;
            padding: 15px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            background: #f7fafc;
        }

        .file-type-option:hover {
            border-color: #11998e;
            background: rgba(17, 153, 142, 0.05);
        }

        .file-type-option.active {
            border-color: #11998e;
            background: rgba(17, 153, 142, 0.1);
        }

        .file-type-option input[type="radio"] {
            display: none;
        }

        .file-type-label {
            font-weight: 600;
            color: #2d3748;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .file-type-option.active .file-type-label {
            color: #11998e;
        }

        .modal-checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            padding: 12px;
            background: rgba(17, 153, 142, 0.05);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .modal-checkbox-wrapper:hover {
            background: rgba(17, 153, 142, 0.1);
        }

        .modal-checkbox-wrapper input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: #11998e;
        }

        .modal-checkbox-wrapper label {
            cursor: pointer;
            font-weight: 600;
            color: #2d3748;
            flex: 1;
        }

        .toast {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            z-index: 10000;
            display: flex;
            align-items: center;
            gap: 15px;
            min-width: 300px;
            transition: transform 0.4s ease;
        }

        .toast.show {
            transform: translateX(-50%) translateY(0);
        }

        .toast.success {
            border-left: 5px solid #48bb78;
        }

        .toast.error {
            border-left: 5px solid #f56565;
        }

        .toast i {
            font-size: 1.5rem;
        }

        .toast.success i {
            color: #48bb78;
        }

        .toast.error i {
            color: #f56565;
        }

        .toast-message {
            flex: 1;
            color: #2d3748;
            font-weight: 600;
        }

        .no-results {
            text-align: center;
            padding: 60px 20px;
            color: white;
            font-size: 1.5rem;
            display: none;
        }

        .no-results i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .refresh-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #38ef7d 0%, #11998e 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 25px rgba(56, 239, 125, 0.4);
            cursor: pointer;
            transition: all 0.3s ease;
            border: 3px solid rgba(255, 255, 255, 0.3);
            z-index: 1000;
            animation: pulse-ring 2s infinite;
        }

        @keyframes pulse-ring {
            0%, 100% {
                box-shadow: 0 8px 25px rgba(56, 239, 125, 0.4), 0 0 0 0 rgba(56, 239, 125, 0.4);
            }
            50% {
                box-shadow: 0 8px 25px rgba(56, 239, 125, 0.4), 0 0 0 10px rgba(56, 239, 125, 0);
            }
        }

        .refresh-btn:hover {
            transform: scale(1.15) rotate(180deg);
            box-shadow: 0 10px 35px rgba(56, 239, 125, 0.6);
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }

        .refresh-btn:active {
            transform: scale(1.05) rotate(180deg);
        }

        .refresh-btn i {
            font-size: 1.8rem;
            color: white;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
        }

        .refresh-btn::after {
            content: 'Refresh';
            position: absolute;
            right: 85px;
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
        }

        .refresh-btn:hover::after {
            opacity: 1;
        }

        .refresh-btn::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: linear-gradient(135deg, #38ef7d 0%, #11998e 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .refresh-btn:hover::before {
            opacity: 0.5;
            animation: ripple 0.6s ease-out;
        }

        @keyframes ripple {
            from {
                transform: scale(1);
                opacity: 0.5;
            }
            to {
                transform: scale(1.5);
                opacity: 0;
            }
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header h1 {
                font-size: 2.5rem;
            }
            
            .header > div:first-child {
                flex-direction: column !important;
                align-items: center !important;
            }
            
            .header > div:first-child > div:last-child {
                align-items: center !important;
            }

            .catalog-grid {
                grid-template-columns: 1fr;
            }

            .stats-bar {
                flex-direction: column;
            }

            .excluded-sidebar {
                width: 200px;
            }

            .container {
                margin-left: 220px;
            }

            .page-title {
                font-size: 1.2rem;
                gap: 8px;
            }

            .page-title-icon {
                font-size: 1.2rem;
            }

            .button-row > * {
                flex: 1 1 100%;
            }

            .sort-controls {
                flex-wrap: wrap;
            }

            .sort-toggle-group {
                flex-wrap: wrap;
                justify-content: center;
            }

            .sort-btn {
                padding: 8px 15px;
                font-size: 0.85rem;
            }

            .sort-label {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <!-- Animated Background Particles -->
    <div class="particles" id="particles"></div>

    <!-- Excluded Pages Sidebar -->
    <div class="excluded-sidebar" id="excludedSidebar">
        <div class="excluded-sidebar-header">
            <i class="fas fa-eye-slash"></i>
            <span class="excluded-sidebar-title">Excluded</span>
            <span class="excluded-count" id="excludedCount">0</span>
        </div>
        <ul class="excluded-list" id="excludedList">
            <div class="excluded-empty">
                <i class="fas fa-inbox"></i>
                <p>No excluded pages</p>
            </div>
        </ul>
    </div>

    <div class="container">
        <div class="header">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 25px; margin-bottom: 20px;">
                <div style="display: flex; align-items: center; gap: 25px;">
                    <img src="FuturisticLogo.png" alt="App-AI Logo" style="width: 100px; height: 100px; filter: drop-shadow(0 8px 25px rgba(0,0,0,0.4)); animation: logoFloat 3s ease-in-out infinite;">
                    <div style="text-align: left;">
                        <h1 style="font-size: 48px; margin: 0; background: linear-gradient(135deg, #22c55e 0%, #fbbf24 50%, #667eea 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; font-weight: bold; display: inline-flex; align-items: center; gap: 15px;">
                            <i class="fas fa-book" style="font-size: 42px; color: #667eea;"></i>
                            <span>App-AI Catalog</span>
                        </h1>
                        <p style="font-size: 17px; margin: 8px 0 0 0; opacity: 0.9; color: rgba(255,255,255,0.9);">Explore all available HTML & PHP pages in your application</p>
                    </div>
                </div>
                
                <!-- Super Admin Logout Button -->
                <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 8px;">
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <div style="background: rgba(34,197,94,0.2); padding: 8px 16px; border-radius: 20px; border: 2px solid rgba(34,197,94,0.4); display: flex; align-items: center; gap: 8px;">
                            <span style="font-size: 16px;">🛡️</span>
                            <span style="color: #86efac; font-size: 13px; font-weight: 600;">Super Admin</span>
                        </div>
                        <?php if (isset($_SESSION['remembered']) && $_SESSION['remembered']): ?>
                        <div style="background: rgba(139,92,246,0.2); padding: 8px 16px; border-radius: 20px; border: 2px solid rgba(139,92,246,0.4); display: flex; align-items: center; gap: 6px;" title="Auto-login enabled">
                            <span style="font-size: 14px;">🔒</span>
                            <span style="color: #c4b5fd; font-size: 12px; font-weight: 600;">Remembered</span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <button onclick="showBulkAdminModal()" style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%); color: white; padding: 10px 20px; border-radius: 10px; font-size: 14px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 15px rgba(6,182,212,0.4); transition: all 0.3s; border: none; cursor: pointer;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(6,182,212,0.6)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(6,182,212,0.4)'">
                            <span style="font-size: 16px;">🛡️</span>
                            <span>Bulk Admin</span>
                        </button>
                        <a href="?logout" onclick="return confirm('Are you sure you want to logout?')" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; padding: 10px 20px; border-radius: 10px; text-decoration: none; font-size: 14px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 15px rgba(239,68,68,0.4); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(239,68,68,0.6)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(239,68,68,0.4)'">
                            <span style="font-size: 16px;">🚪</span>
                            <span>Logout</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="stats-bar">
            <div class="stat-item">
                <i class="fas fa-file-code"></i>
                <span class="stat-number"><?php echo count($fileList); ?></span>
                <span class="stat-label">Total Pages</span>
            </div>
            <div class="stat-item">
                <i class="fas fa-folder-open"></i>
                <span class="stat-number" id="visiblePages"><?php echo count($fileList); ?></span>
                <span class="stat-label">Visible Pages</span>
            </div>
            <div class="stat-item">
                <i class="fas fa-clock"></i>
                <span class="stat-number"><?php echo date('H:i'); ?></span>
                <span class="stat-label">Last Updated</span>
            </div>
        </div>

        <!-- Database Connections Selector -->
        <div style="background: linear-gradient(135deg, rgba(102,126,234,0.1) 0%, rgba(118,75,162,0.1) 100%); border-radius: 16px; padding: 20px; margin: 25px 0; border: 2px solid rgba(102,126,234,0.2); box-shadow: 0 8px 32px rgba(0,0,0,0.1);">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px;">
                <div style="display: flex; align-items: center; gap: 12px; flex: 1; min-width: 250px;">
                    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; box-shadow: 0 4px 15px rgba(102,126,234,0.3);">
                        🗄️
                    </div>
                    <div>
                        <h3 style="color: #fff; font-size: 18px; font-weight: 700; margin: 0 0 4px 0;">Database Connections</h3>
                        <p style="color: rgba(255,255,255,0.7); font-size: 13px; margin: 0;">
                            <span id="connStatusText">Loading...</span> • <span id="connCount">0</span> active
                        </p>
                    </div>
                </div>
                
                <div style="display: flex; align-items: center; gap: 12px; flex: 1; min-width: 300px;">
                    <select id="dbConnectionSelect" style="flex: 1; padding: 12px 16px; background: rgba(255,255,255,0.95); border: 2px solid rgba(102,126,234,0.3); border-radius: 10px; color: #1e293b; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.3s; outline: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);" onfocus="this.style.borderColor='#667eea'; this.style.boxShadow='0 4px 12px rgba(102,126,234,0.3)'" onblur="this.style.borderColor='rgba(102,126,234,0.3)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)'">
                        <option value="" style="color: #64748b;">Loading connections...</option>
                    </select>
                    
                    <button id="showCredentialsBtn" onclick="showDatabaseCredentials()" style="padding: 12px 24px; background: linear-gradient(135deg, #22c55e 0%, #15803d 100%); border: none; border-radius: 10px; color: white; font-size: 14px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 15px rgba(34,197,94,0.3); transition: all 0.3s; white-space: nowrap;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(34,197,94,0.5)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(34,197,94,0.3)'" disabled>
                        <span style="font-size: 16px;">🔑</span>
                        <span>Show Credentials</span>
                    </button>
                    
                    <button onclick="refreshDatabaseConnections()" style="padding: 12px; background: rgba(255,255,255,0.1); border: 2px solid rgba(255,255,255,0.2); border-radius: 10px; color: white; font-size: 18px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.3s; width: 44px; height: 44px;" onmouseover="this.style.background='rgba(255,255,255,0.2)'; this.style.transform='rotate(180deg)'" onmouseout="this.style.background='rgba(255,255,255,0.1)'; this.style.transform='rotate(0deg)'" title="Refresh Connections">
                        🔄
                    </button>
                </div>
            </div>
        </div>

        <div class="search-box">
            <div class="search-wrapper">
                <i class="fas fa-search search-icon"></i>
                <input 
                    type="text" 
                    id="searchInput" 
                    class="search-input" 
                    placeholder="🔍 Search pages by name or filename..."
                    autocomplete="off"
                >
            </div>
            <button class="create-new-btn" onclick="showCreateFileModal()">
                <i class="fas fa-plus-circle"></i>
                Create New File
            </button>
        </div>

        <!-- Sort Controls -->
        <div class="sort-controls">
            <div class="sort-label">
                <i class="fas fa-sort"></i>
                <span>Sort:</span>
            </div>
            <div class="sort-toggle-group">
                <button class="sort-btn" id="sortDateBtn" onclick="sortBy('date')" title="Sort by Date (Newest First)">
                    <i class="fas fa-clock"></i>
                    <span>Date</span>
                </button>
                <button class="sort-btn" id="sortAtoZBtn" onclick="sortBy('a-z')" title="Sort A to Z">
                    <i class="fas fa-sort-alpha-down"></i>
                    <span>A → Z</span>
                </button>
                <button class="sort-btn" id="sortZtoABtn" onclick="sortBy('z-a')" title="Sort Z to A">
                    <i class="fas fa-sort-alpha-up"></i>
                    <span>Z → A</span>
                </button>
            </div>
            <button class="reset-sort-btn" onclick="resetSort()" title="Reset to Default (Date)">
                <i class="fas fa-undo"></i>
            </button>
        </div>

        <div class="catalog-grid" id="catalogGrid">
            <?php if (empty($fileList)): ?>
                <div class="no-results" style="display: block; grid-column: 1/-1;">
                    <i class="fas fa-folder-open"></i>
                    <p>No HTML pages found in the directory</p>
                </div>
            <?php else: ?>
                <?php foreach ($fileList as $file): ?>
                    <?php
                    // Determine icon based on filename
                    $filename = strtolower($file['filename']);
                    $icon = '📄'; // Default icon
                    
                    // Page type detection with emojis
                    if (strpos($filename, 'dashboard') !== false) $icon = '📊';
                    elseif (strpos($filename, 'index') !== false || strpos($filename, 'home') !== false) $icon = '🏠';
                    elseif (strpos($filename, 'login') !== false || strpos($filename, 'signin') !== false) $icon = '🔐';
                    elseif (strpos($filename, 'register') !== false || strpos($filename, 'signup') !== false) $icon = '📝';
                    elseif (strpos($filename, 'profile') !== false || strpos($filename, 'account') !== false) $icon = '👤';
                    elseif (strpos($filename, 'user') !== false || strpos($filename, 'employee') !== false) $icon = '👥';
                    elseif (strpos($filename, 'product') !== false) $icon = '📦';
                    elseif (strpos($filename, 'cart') !== false || strpos($filename, 'shop') !== false) $icon = '🛒';
                    elseif (strpos($filename, 'order') !== false) $icon = '📋';
                    elseif (strpos($filename, 'payment') !== false || strpos($filename, 'checkout') !== false) $icon = '💳';
                    elseif (strpos($filename, 'setting') !== false || strpos($filename, 'config') !== false) $icon = '⚙️';
                    elseif (strpos($filename, 'report') !== false || strpos($filename, 'analytic') !== false) $icon = '📈';
                    elseif (strpos($filename, 'message') !== false || strpos($filename, 'chat') !== false) $icon = '💬';
                    elseif (strpos($filename, 'notification') !== false || strpos($filename, 'alert') !== false) $icon = '🔔';
                    elseif (strpos($filename, 'calendar') !== false || strpos($filename, 'event') !== false) $icon = '📅';
                    elseif (strpos($filename, 'contact') !== false) $icon = '📞';
                    elseif (strpos($filename, 'about') !== false) $icon = 'ℹ️';
                    elseif (strpos($filename, 'blog') !== false || strpos($filename, 'post') !== false) $icon = '✍️';
                    elseif (strpos($filename, 'gallery') !== false || strpos($filename, 'image') !== false) $icon = '🖼️';
                    elseif (strpos($filename, 'video') !== false || strpos($filename, 'media') !== false) $icon = '🎬';
                    elseif (strpos($filename, 'search') !== false) $icon = '🔍';
                    elseif (strpos($filename, 'help') !== false || strpos($filename, 'faq') !== false) $icon = '❓';
                    elseif (strpos($filename, 'api') !== false || strpos($filename, 'backend') !== false) $icon = '🔌';
                    elseif (strpos($filename, 'admin') !== false) $icon = '👑';
                    elseif (strpos($filename, 'database') !== false || strpos($filename, 'db') !== false) $icon = '🗄️';
                    elseif (strpos($filename, 'form') !== false) $icon = '📋';
                    elseif (strpos($filename, 'table') !== false) $icon = '📊';
                    elseif (strpos($filename, 'list') !== false) $icon = '📝';
                    elseif (strpos($filename, 'detail') !== false || strpos($filename, 'view') !== false) $icon = '👁️';
                    elseif (strpos($filename, 'edit') !== false || strpos($filename, 'update') !== false) $icon = '✏️';
                    elseif (strpos($filename, 'delete') !== false || strpos($filename, 'remove') !== false) $icon = '🗑️';
                    elseif (strpos($filename, 'create') !== false || strpos($filename, 'add') !== false || strpos($filename, 'new') !== false) $icon = '➕';
                    elseif (strpos($filename, 'download') !== false) $icon = '⬇️';
                    elseif (strpos($filename, 'upload') !== false) $icon = '⬆️';
                    elseif (strpos($filename, 'export') !== false) $icon = '📤';
                    elseif (strpos($filename, 'import') !== false) $icon = '📥';
                    elseif (strpos($filename, 'print') !== false) $icon = '🖨️';
                    elseif (strpos($filename, 'email') !== false || strpos($filename, 'mail') !== false) $icon = '📧';
                    elseif (strpos($filename, 'invoice') !== false || strpos($filename, 'bill') !== false) $icon = '🧾';
                    elseif (strpos($filename, 'customer') !== false || strpos($filename, 'client') !== false) $icon = '👔';
                    elseif (strpos($filename, 'supplier') !== false || strpos($filename, 'vendor') !== false) $icon = '🏭';
                    elseif (strpos($filename, 'inventory') !== false || strpos($filename, 'stock') !== false) $icon = '📦';
                    elseif (strpos($filename, 'category') !== false) $icon = '🏷️';
                    elseif (strpos($filename, 'tag') !== false) $icon = '🔖';
                    elseif (strpos($filename, 'comment') !== false || strpos($filename, 'review') !== false) $icon = '💭';
                    elseif (strpos($filename, 'rating') !== false || strpos($filename, 'star') !== false) $icon = '⭐';
                    elseif (strpos($filename, 'favorite') !== false || strpos($filename, 'wishlist') !== false) $icon = '❤️';
                    elseif (strpos($filename, 'bookmark') !== false) $icon = '🔖';
                    elseif (strpos($filename, 'share') !== false) $icon = '🔗';
                    elseif (strpos($filename, 'map') !== false || strpos($filename, 'location') !== false) $icon = '🗺️';
                    elseif (strpos($filename, 'weather') !== false) $icon = '🌤️';
                    elseif (strpos($filename, 'task') !== false || strpos($filename, 'todo') !== false) $icon = '✅';
                    elseif (strpos($filename, 'project') !== false) $icon = '📁';
                    elseif (strpos($filename, 'team') !== false) $icon = '👥';
                    elseif (strpos($filename, 'department') !== false) $icon = '🏢';
                    elseif (strpos($filename, 'company') !== false || strpos($filename, 'organization') !== false) $icon = '🏛️';
                    elseif (strpos($filename, 'service') !== false) $icon = '🛠️';
                    elseif (strpos($filename, 'price') !== false || strpos($filename, 'cost') !== false) $icon = '💰';
                    elseif (strpos($filename, 'discount') !== false || strpos($filename, 'coupon') !== false) $icon = '🎟️';
                    elseif (strpos($filename, 'promotion') !== false || strpos($filename, 'offer') !== false) $icon = '🎁';
                    elseif (strpos($filename, 'sale') !== false) $icon = '💸';
                    elseif (strpos($filename, 'transaction') !== false) $icon = '💳';
                    elseif (strpos($filename, 'history') !== false || strpos($filename, 'log') !== false) $icon = '📜';
                    elseif (strpos($filename, 'error') !== false || strpos($filename, '404') !== false) $icon = '❌';
                    elseif (strpos($filename, 'success') !== false) $icon = '✅';
                    elseif (strpos($filename, 'warning') !== false) $icon = '⚠️';
                    elseif (strpos($filename, 'info') !== false) $icon = 'ℹ️';
                    elseif (strpos($filename, 'test') !== false || strpos($filename, 'demo') !== false) $icon = '🧪';
                    elseif (strpos($filename, 'template') !== false) $icon = '📋';
                    elseif (strpos($filename, 'widget') !== false) $icon = '🧩';
                    elseif (strpos($filename, 'plugin') !== false || strpos($filename, 'extension') !== false) $icon = '🔌';
                    elseif (strpos($filename, 'theme') !== false || strpos($filename, 'style') !== false) $icon = '🎨';
                    elseif (strpos($filename, 'layout') !== false) $icon = '📐';
                    elseif (strpos($filename, 'component') !== false) $icon = '🧱';
                    elseif (strpos($filename, 'module') !== false) $icon = '📦';
                    elseif (strpos($filename, 'wizard') !== false || strpos($filename, 'step') !== false) $icon = '🪄';
                    elseif (strpos($filename, 'tutorial') !== false || strpos($filename, 'guide') !== false) $icon = '📖';
                    elseif (strpos($filename, 'documentation') !== false || strpos($filename, 'docs') !== false) $icon = '📚';
                    
                    // Format file size
                    $size = $file['size'];
                    $units = ['B', 'KB', 'MB', 'GB'];
                    $i = 0;
                    while ($size >= 1024 && $i < count($units) - 1) {
                        $size /= 1024;
                        $i++;
                    }
                    $sizeFormatted = round($size, 2) . ' ' . $units[$i];
                    
                    // Format date
                    $modified = $file['modified'];
                    $now = time();
                    $diff = $now - $modified;
                    $days = floor($diff / (60 * 60 * 24));
                    
                    if ($days == 0) $dateFormatted = 'Today';
                    elseif ($days == 1) $dateFormatted = 'Yesterday';
                    elseif ($days < 7) $dateFormatted = $days . ' days ago';
                    else $dateFormatted = date('M j, Y', $modified);
                    ?>
                    
                    <div class="page-card" 
                         data-filename="<?php echo strtolower($file['filename']); ?>" 
                         data-name="<?php echo strtolower($file['name']); ?>"
                         data-extension="<?php echo strtolower($file['extension']); ?>"
                         data-filepath="<?php echo htmlspecialchars($file['filename']); ?>"
                         data-modified="<?php echo $file['modified']; ?>">
                        <div class="page-title">
                            <span class="page-title-icon"><?php echo $icon; ?></span>
                            <span><?php echo htmlspecialchars($file['name']); ?></span>
                        </div>
                        <div class="page-filename">
                            <?php echo htmlspecialchars($file['filename']); ?>
                            <span class="file-type-badge <?php echo strtolower($file['extension']); ?>">
                                <?php echo $file['extension']; ?>
                            </span>
                        </div>
                        <div class="page-meta">
                            <div class="page-date">
                                <i class="far fa-calendar"></i>
                                <span><?php echo $dateFormatted; ?></span>
                            </div>
                            <div class="page-size">
                                <i class="far fa-file"></i>
                                <span><?php echo $sizeFormatted; ?></span>
                            </div>
                        </div>
                        <div class="button-group-full">
                            <div class="button-row">
                                <a href="<?php echo htmlspecialchars($file['path']); ?>" class="open-btn" target="_blank">
                                    <i class="fas fa-external-link-alt"></i>
                                    Open
                                </a>
                                <a href="platform_ai.php?file=<?php echo urlencode($file['filename']); ?>" class="platform-ai-btn" title="Open in AI Platform - Advanced Code Editor with Ollama">
                                    <i class="fas fa-robot"></i>
                                    AI Platform
                                </a>
                                <div class="editor-choice-wrapper">
                                    <span class="editor-choice-label">Editor:</span>
                                    <div class="editor-choice-buttons">
                                        <a href="platform_ai.php?file=<?php echo urlencode($file['filename']); ?>" class="editor-choice-btn ai" title="Ollama AI Editor">
                                            <i class="fas fa-robot"></i>
                                            <span>Ollama</span>
                                        </a>
                                        <a href="platform.php?file=<?php echo urlencode($file['filename']); ?>" class="editor-choice-btn classic" title="Classic Editor">
                                            <i class="fas fa-code"></i>
                                            <span>Classic</span>
                                        </a>
                                    </div>
                                </div>
                                <button class="rename-btn" onclick="showRenameModal('<?php echo htmlspecialchars($file['filename'], ENT_QUOTES); ?>')">
                                    <i class="fas fa-edit"></i>
                                    Rename
                                </button>
                                <button class="duplicate-btn" onclick="showDuplicateModal('<?php echo htmlspecialchars($file['filename'], ENT_QUOTES); ?>')">
                                    <i class="fas fa-copy"></i>
                                    Duplicate
                                </button>
                                <button class="edit-page-btn" 
                                        data-filename="<?php echo htmlspecialchars($file['filename'], ENT_QUOTES); ?>"
                                        onclick="checkAndShowEditModal('<?php echo htmlspecialchars($file['filename'], ENT_QUOTES); ?>')"
                                        style="display: none; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                    <i class="fas fa-cog"></i>
                                    Edit Page
                                </button>
                                <button class="delete-btn" onclick="showDeleteModal('<?php echo htmlspecialchars($file['filename'], ENT_QUOTES); ?>')">
                                    <i class="fas fa-trash-alt"></i>
                                    Delete
                                </button>
                                <button class="admin-toggle-btn" 
                                        id="admin-btn-<?php echo htmlspecialchars($file['filename'], ENT_QUOTES); ?>"
                                        data-filename="<?php echo htmlspecialchars($file['filename'], ENT_QUOTES); ?>"
                                        onclick="toggleAdminPassword('<?php echo htmlspecialchars($file['filename'], ENT_QUOTES); ?>')" 
                                        title="Toggle Super Admin Protection">
                                    <i class="fas fa-shield-alt"></i>
                                    <span class="admin-btn-text">Checking...</span>
                                </button>
                            </div>
                            <button class="add-back-btn" 
                                    data-filename="<?php echo htmlspecialchars($file['filename'], ENT_QUOTES); ?>"
                                    onclick="toggleBackButton('<?php echo htmlspecialchars($file['filename'], ENT_QUOTES); ?>', this)">
                                <i class="fas fa-home"></i>
                                Add Back Home
                            </button>
                            <button class="add-code-editor-btn" 
                                    data-filename="<?php echo htmlspecialchars($file['filename'], ENT_QUOTES); ?>"
                                    onclick="toggleCodeEditorButton('<?php echo htmlspecialchars($file['filename'], ENT_QUOTES); ?>', this)">
                                <i class="fas fa-code"></i>
                                Add Back to Code Editor
                            </button>
                            <button class="add-platform-btn" 
                                    data-filename="<?php echo htmlspecialchars($file['filename'], ENT_QUOTES); ?>"
                                    onclick="togglePlatformButton('<?php echo htmlspecialchars($file['filename'], ENT_QUOTES); ?>', this)">
                                <i class="fas fa-robot"></i>
                                Add Back to AI Platform
                            </button>
                            <button class="exclude-btn" 
                                    data-filename="<?php echo htmlspecialchars($file['filename'], ENT_QUOTES); ?>"
                                    onclick="togglePageVisibility('<?php echo htmlspecialchars($file['filename'], ENT_QUOTES); ?>')">
                                <i class="fas fa-eye-slash"></i>
                                Exclude Page
                            </button>
                            <button class="transfer-link-toggle-btn" 
                                    id="transfer-btn-<?php echo htmlspecialchars($file['filename'], ENT_QUOTES); ?>"
                                    data-filename="<?php echo htmlspecialchars($file['filename'], ENT_QUOTES); ?>"
                                    onclick="toggleTransferLink('<?php echo htmlspecialchars($file['filename'], ENT_QUOTES); ?>', this)">
                                <i class="fas fa-external-link-alt"></i>
                                <span class="transfer-btn-text">Checking...</span>
                            </button>
                            <button class="add-iframe-btn" 
                                    data-filename="<?php echo htmlspecialchars($file['filename'], ENT_QUOTES); ?>"
                                    onclick="showIframeModal('<?php echo htmlspecialchars($file['filename'], ENT_QUOTES); ?>', this)">
                                <i class="fas fa-window-restore"></i>
                                Append iFrame
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="no-results" id="noResults">
            <i class="fas fa-search-minus"></i>
            <p>No pages found matching your search</p>
        </div>
    </div>

    <!-- Refresh Button -->
    <button class="refresh-btn" onclick="location.reload()" title="Refresh Catalog">
        <i class="fas fa-sync-alt"></i>
    </button>

    <!-- Delete Confirmation Modal -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal-content">
            <div class="modal-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h2 class="modal-title">Delete File?</h2>
            <p class="modal-message">Are you sure you want to permanently delete this file? This action cannot be undone.</p>
            <div class="modal-filename" id="modalFilename"></div>
            <div class="modal-buttons">
                <button class="modal-btn modal-btn-cancel" onclick="closeDeleteModal()">
                    <i class="fas fa-times"></i>
                    Cancel
                </button>
                <button class="modal-btn modal-btn-confirm" id="confirmDeleteBtn" onclick="confirmDelete()">
                    <i class="fas fa-trash-alt"></i>
                    Yes, Delete
                </button>
            </div>
        </div>
    </div>

    <!-- Rename Modal -->
    <div class="modal-overlay" id="renameModal">
        <div class="modal-content">
            <div class="modal-icon" style="color: #ffa500;">
                <i class="fas fa-edit"></i>
            </div>
            <h2 class="modal-title">Rename File</h2>
            <p class="modal-message">Enter a new name for this file:</p>
            <div class="modal-filename" id="renameOldFilename" style="background: rgba(255, 165, 0, 0.1); color: #ff8c00;"></div>
            <input type="text" 
                   id="renameInput" 
                   class="modal-input" 
                   placeholder="new-filename.html"
                   autocomplete="off">
            <p class="modal-hint">
                <i class="fas fa-info-circle"></i>
                Include file extension (.html or .php)
            </p>
            <div class="modal-buttons">
                <button class="modal-btn modal-btn-cancel" onclick="closeRenameModal()">
                    <i class="fas fa-times"></i>
                    Cancel
                </button>
                <button class="modal-btn modal-btn-confirm" 
                        id="confirmRenameBtn" 
                        onclick="confirmRename()"
                        style="background: linear-gradient(135deg, #ffd700 0%, #ffa500 100%); box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);">
                    <i class="fas fa-check"></i>
                    Rename File
                </button>
            </div>
        </div>
    </div>

    <!-- Duplicate Modal -->
    <div class="modal-overlay" id="duplicateModal">
        <div class="modal-content">
            <div class="modal-icon" style="color: #38ef7d;">
                <i class="fas fa-copy"></i>
            </div>
            <h2 class="modal-title">✨ Duplicate File</h2>
            <p class="modal-message">Create a copy of this file with a new name:</p>
            <div class="modal-filename" id="duplicateSourceFilename" style="background: rgba(56, 239, 125, 0.1); color: #11998e; margin-bottom: 15px;"></div>
            
            <div style="background: rgba(56, 239, 125, 0.08); border: 2px solid rgba(56, 239, 125, 0.3); border-radius: 12px; padding: 15px; margin-bottom: 15px;">
                <label style="display: block; font-weight: 600; color: #11998e; margin-bottom: 10px; font-size: 14px;">
                    <i class="fas fa-file-signature"></i> New File Name:
                </label>
                <input type="text" 
                       id="duplicateInput" 
                       class="modal-input" 
                       placeholder="filename_copy.html"
                       autocomplete="off"
                       style="border: 2px solid rgba(56, 239, 125, 0.4); box-shadow: 0 3px 12px rgba(56, 239, 125, 0.15);">
            </div>
            
            <p class="modal-hint" style="background: rgba(56, 239, 125, 0.05); padding: 10px; border-radius: 8px; border-left: 3px solid #38ef7d;">
                <i class="fas fa-lightbulb"></i>
                <strong>Tip:</strong> The default name will be "<strong>originalname_copy.ext</strong>" but you can change it to anything you want!
            </p>
            <p class="modal-hint">
                <i class="fas fa-info-circle"></i>
                Include file extension (.html or .php)
            </p>
            <div class="modal-buttons">
                <button class="modal-btn modal-btn-cancel" onclick="closeDuplicateModal()">
                    <i class="fas fa-times"></i>
                    Cancel
                </button>
                <button class="modal-btn modal-btn-confirm" 
                        id="confirmDuplicateBtn" 
                        onclick="confirmDuplicate()"
                        style="background: linear-gradient(135deg, #38ef7d 0%, #11998e 100%); box-shadow: 0 4px 15px rgba(56, 239, 125, 0.3);">
                    <i class="fas fa-copy"></i>
                    Duplicate File
                </button>
            </div>
        </div>
    </div>

    <!-- Create File Modal -->
    <div class="modal-overlay" id="createFileModal">
        <div class="modal-content">
            <div class="modal-icon" style="color: #11998e;">
                <i class="fas fa-file-medical"></i>
            </div>
            <h2 class="modal-title">Create New File</h2>
            <p class="modal-message">Enter file details to create a new page:</p>
            
            <!-- File Type Selection -->
            <div class="modal-file-type">
                <div class="file-type-option active" id="htmlOption" onclick="selectFileType('html')">
                    <input type="radio" name="fileType" value="html" id="htmlRadio" checked>
                    <label class="file-type-label" for="htmlRadio">
                        <i class="fab fa-html5"></i>
                        HTML File
                    </label>
                </div>
                <div class="file-type-option" id="phpOption" onclick="selectFileType('php')">
                    <input type="radio" name="fileType" value="php" id="phpRadio">
                    <label class="file-type-label" for="phpRadio">
                        <i class="fab fa-php"></i>
                        PHP File
                    </label>
                </div>
            </div>

            <!-- Filename Input -->
            <input type="text" 
                   id="createFileInput" 
                   class="modal-input" 
                   placeholder="my-new-page.html"
                   autocomplete="off">
            <p class="modal-hint">
                <i class="fas fa-info-circle"></i>
                Include file extension (.html or .php)
            </p>

            <!-- Template Selection -->
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 600; color: #2d3748; margin-bottom: 8px;">
                    <i class="fas fa-layer-group"></i> Choose Template:
                </label>
                <select id="templateSelect" class="modal-input" style="cursor: pointer;">
                    <optgroup label="Empty Pages">
                        <option value="empty">📄 Empty Page (No Title)</option>
                        <option value="empty_with_title">📝 Empty Page with Title</option>
                    </optgroup>
                    <optgroup label="Landing Pages" id="htmlTemplates">
                        <option value="landing_modern">🚀 Modern Landing Page</option>
                        <option value="landing_startup">💡 Startup Landing Page</option>
                        <option value="landing_app">📱 App Landing Page</option>
                        <option value="landing_saas">☁️ SaaS Landing Page</option>
                        <option value="landing_product">🎯 Product Showcase</option>
                    </optgroup>
                    <optgroup label="Authentication" id="htmlAuth">
                        <option value="login_modern">🔐 Modern Login Page</option>
                        <option value="login_minimal">🔑 Minimal Login</option>
                        <option value="signup">📝 Sign Up Page</option>
                        <option value="forgot_password">🔄 Forgot Password</option>
                    </optgroup>
                    <optgroup label="Dashboard & Admin" id="htmlDashboard">
                        <option value="dashboard">📊 Dashboard</option>
                        <option value="admin_panel">⚙️ Admin Panel</option>
                        <option value="analytics">📈 Analytics Page</option>
                    </optgroup>
                    <optgroup label="Portfolio & Resume" id="htmlPortfolio">
                        <option value="portfolio">🎨 Portfolio Page</option>
                        <option value="resume">📄 Resume/CV Page</option>
                        <option value="about_me">👤 About Me Page</option>
                    </optgroup>
                    <optgroup label="E-Commerce" id="htmlEcommerce">
                        <option value="product_page">🛍️ Product Page</option>
                        <option value="cart">🛒 Shopping Cart</option>
                        <option value="checkout">💳 Checkout Page</option>
                    </optgroup>
                    <optgroup label="Blog & Content" id="htmlBlog">
                        <option value="blog_home">📰 Blog Homepage</option>
                        <option value="blog_post">📝 Blog Post</option>
                        <option value="article">📄 Article Page</option>
                    </optgroup>
                    <optgroup label="Contact & Forms" id="htmlContact">
                        <option value="contact">📧 Contact Page</option>
                        <option value="form_page">📋 Form Page</option>
                    </optgroup>
                    <optgroup label="Coming Soon & Maintenance" id="htmlStatus">
                        <option value="coming_soon">🚧 Coming Soon</option>
                        <option value="maintenance">🔧 Maintenance Mode</option>
                        <option value="404">❌ 404 Error Page</option>
                    </optgroup>
                </select>
            </div>

            <!-- Page Title Input -->
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 600; color: #2d3748; margin-bottom: 8px;">
                    <i class="fas fa-heading"></i> Page Title:
                </label>
                <input type="text" 
                       id="pageTitleInput" 
                       class="modal-input" 
                       placeholder="Enter page title..."
                       autocomplete="off">
                <p class="modal-hint">
                    <i class="fas fa-info-circle"></i>
                    Title will be displayed on the page (if option enabled)
                </p>
            </div>

            <!-- Include Title Checkbox -->
            <div class="modal-checkbox-wrapper">
                <input type="checkbox" id="includeTitleCheckbox" checked>
                <label for="includeTitleCheckbox">
                    <i class="fas fa-text-height"></i>
                    Show title on page
                </label>
            </div>

            <!-- Favicon Upload -->
            <div style="margin-bottom: 20px; background: rgba(79, 172, 254, 0.05); padding: 15px; border-radius: 12px; border: 2px dashed rgba(79, 172, 254, 0.3);">
                <label style="display: block; font-weight: 600; color: #2d3748; margin-bottom: 10px; font-size: 14px;">
                    <i class="fas fa-icons"></i> Favicon (Optional):
                </label>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <input type="file" 
                           id="faviconInput" 
                           accept="image/*"
                           style="flex: 1; padding: 10px; border: 2px solid rgba(79, 172, 254, 0.4); border-radius: 8px; background: white; cursor: pointer;">
                    <div id="faviconPreview" style="width: 40px; height: 40px; border: 2px solid rgba(79, 172, 254, 0.4); border-radius: 8px; display: none; align-items: center; justify-content: center; background: white; overflow: hidden;">
                        <img id="faviconPreviewImg" style="width: 100%; height: 100%; object-fit: contain;">
                    </div>
                </div>
                <p class="modal-hint" style="margin-top: 8px;">
                    <i class="fas fa-info-circle"></i>
                    Favicon will appear in the browser tab (recommended: 32×32px or 64×64px PNG/ICO)
                </p>
            </div>

            <!-- Logo Upload -->
            <div style="margin-bottom: 20px; background: rgba(56, 239, 125, 0.05); padding: 15px; border-radius: 12px; border: 2px dashed rgba(56, 239, 125, 0.3);">
                <label style="display: block; font-weight: 600; color: #2d3748; margin-bottom: 10px; font-size: 14px;">
                    <i class="fas fa-image"></i> Logo (Optional):
                </label>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <input type="file" 
                           id="logoInput" 
                           accept="image/*"
                           style="flex: 1; padding: 10px; border: 2px solid rgba(56, 239, 125, 0.4); border-radius: 8px; background: white; cursor: pointer;">
                    <div id="logoPreview" style="width: 80px; height: 60px; border: 2px solid rgba(56, 239, 125, 0.4); border-radius: 8px; display: none; align-items: center; justify-content: center; background: white; overflow: hidden;">
                        <img id="logoPreviewImg" style="width: 100%; height: 100%; object-fit: contain;">
                    </div>
                </div>
                <p class="modal-hint" style="margin-top: 8px;">
                    <i class="fas fa-info-circle"></i>
                    Logo will appear in the top-left corner of the page (any size, transparent PNG recommended)
                </p>
            </div>

            <div class="modal-buttons">
                <button class="modal-btn modal-btn-cancel" onclick="closeCreateFileModal()">
                    <i class="fas fa-times"></i>
                    Cancel
                </button>
                <button class="modal-btn modal-btn-confirm" 
                        id="confirmCreateBtn" 
                        onclick="confirmCreateFile()"
                        style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); box-shadow: 0 4px 15px rgba(17, 153, 142, 0.3);">
                    <i class="fas fa-plus-circle"></i>
                    Create File
                </button>
            </div>
        </div>
    </div>

    <!-- Edit Page Modal -->
    <div class="modal-overlay" id="editPageModal">
        <div class="modal-content">
            <div class="modal-icon" style="color: #667eea;">
                <i class="fas fa-cog"></i>
            </div>
            <h2 class="modal-title">✨ Edit Page Settings</h2>
            <p class="modal-message">Modify page configuration and regenerate:</p>
            <div class="modal-filename" id="editPageFilename" style="background: rgba(102, 126, 234, 0.1); color: #667eea; margin-bottom: 15px;"></div>
            
            <!-- Template Selection -->
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 600; color: #2d3748; margin-bottom: 8px;">
                    <i class="fas fa-layer-group"></i> Choose Template:
                </label>
                <select id="editTemplateSelect" class="modal-input" style="cursor: pointer;">
                    <optgroup label="Empty Pages">
                        <option value="empty">📄 Empty Page (No Title)</option>
                        <option value="empty_with_title">📝 Empty Page with Title</option>
                    </optgroup>
                    <optgroup label="Landing Pages">
                        <option value="landing_modern">🚀 Modern Landing</option>
                        <option value="landing_startup">💡 Startup Landing</option>
                        <option value="landing_app">📱 App Landing</option>
                    </optgroup>
                    <optgroup label="Authentication">
                        <option value="login_modern">🔐 Modern Login</option>
                        <option value="login_minimal">🔑 Minimal Login</option>
                        <option value="signup">📝 Sign Up</option>
                    </optgroup>
                    <optgroup label="Dashboard & Admin">
                        <option value="dashboard">📊 Dashboard</option>
                    </optgroup>
                    <optgroup label="Portfolio & Resume">
                        <option value="portfolio">🎨 Portfolio</option>
                    </optgroup>
                    <optgroup label="Contact & Forms">
                        <option value="contact">📧 Contact Page</option>
                    </optgroup>
                </select>
            </div>

            <!-- Page Title Input -->
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 600; color: #2d3748; margin-bottom: 8px;">
                    <i class="fas fa-heading"></i> Page Title:
                </label>
                <input type="text" 
                       id="editPageTitleInput" 
                       class="modal-input" 
                       placeholder="Enter page title..."
                       autocomplete="off">
            </div>

            <!-- Include Title Checkbox -->
            <div class="modal-checkbox-wrapper">
                <input type="checkbox" id="editIncludeTitleCheckbox" checked>
                <label for="editIncludeTitleCheckbox">
                    <i class="fas fa-text-height"></i>
                    Show title on page
                </label>
            </div>

            <!-- Favicon Upload -->
            <div style="margin-bottom: 20px; background: rgba(79, 172, 254, 0.05); padding: 15px; border-radius: 12px; border: 2px dashed rgba(79, 172, 254, 0.3);">
                <label style="display: block; font-weight: 600; color: #2d3748; margin-bottom: 10px; font-size: 14px;">
                    <i class="fas fa-icons"></i> Favicon (Optional - leave empty to keep current):
                </label>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <input type="file" 
                           id="editFaviconInput" 
                           accept="image/*"
                           style="flex: 1; padding: 10px; border: 2px solid rgba(79, 172, 254, 0.4); border-radius: 8px; background: white; cursor: pointer;">
                    <div id="editFaviconPreview" style="width: 40px; height: 40px; border: 2px solid rgba(79, 172, 254, 0.4); border-radius: 8px; display: none; align-items: center; justify-content: center; background: white; overflow: hidden;">
                        <img id="editFaviconPreviewImg" style="width: 100%; height: 100%; object-fit: contain;">
                    </div>
                </div>
                <div id="editCurrentFavicon" style="margin-top: 10px; display: none; padding: 8px; background: rgba(102, 126, 234, 0.1); border-radius: 6px; font-size: 12px; color: #667eea;">
                    <i class="fas fa-info-circle"></i> <span id="editCurrentFaviconText"></span>
                </div>
            </div>

            <!-- Logo Upload -->
            <div style="margin-bottom: 20px; background: rgba(56, 239, 125, 0.05); padding: 15px; border-radius: 12px; border: 2px dashed rgba(56, 239, 125, 0.3);">
                <label style="display: block; font-weight: 600; color: #2d3748; margin-bottom: 10px; font-size: 14px;">
                    <i class="fas fa-image"></i> Logo (Optional - leave empty to keep current):
                </label>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <input type="file" 
                           id="editLogoInput" 
                           accept="image/*"
                           style="flex: 1; padding: 10px; border: 2px solid rgba(56, 239, 125, 0.4); border-radius: 8px; background: white; cursor: pointer;">
                    <div id="editLogoPreview" style="width: 80px; height: 60px; border: 2px solid rgba(56, 239, 125, 0.4); border-radius: 8px; display: none; align-items: center; justify-content: center; background: white; overflow: hidden;">
                        <img id="editLogoPreviewImg" style="width: 100%; height: 100%; object-fit: contain;">
                    </div>
                </div>
                <div id="editCurrentLogo" style="margin-top: 10px; display: none; padding: 8px; background: rgba(102, 126, 234, 0.1); border-radius: 6px; font-size: 12px; color: #667eea;">
                    <i class="fas fa-info-circle"></i> <span id="editCurrentLogoText"></span>
                </div>
            </div>

            <div class="modal-buttons">
                <button class="modal-btn modal-btn-cancel" onclick="closeEditPageModal()">
                    <i class="fas fa-times"></i>
                    Cancel
                </button>
                <button class="modal-btn modal-btn-confirm" 
                        id="confirmEditPageBtn" 
                        onclick="confirmEditPage()"
                        style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);">
                    <i class="fas fa-save"></i>
                    Save & Regenerate
                </button>
            </div>
        </div>
    </div>

    <!-- Transfer Link Modal -->
    <div class="modal-overlay" id="transferLinkModal">
        <div class="modal-content">
            <div class="modal-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <i class="fas fa-external-link-alt"></i>
            </div>
            <h2 class="modal-title">🔄 Add Transfer Link</h2>
            <p class="modal-message">Enter the URL where this page should automatically redirect:</p>
            <div class="modal-filename" id="transferLinkFilename" style="background: rgba(240, 147, 251, 0.1); color: #f5576c; margin-bottom: 15px;"></div>
            
            <div style="background: rgba(240, 147, 251, 0.08); border: 2px solid rgba(240, 147, 251, 0.3); border-radius: 12px; padding: 15px; margin-bottom: 15px;">
                <label style="display: block; font-weight: 600; color: #f5576c; margin-bottom: 10px; font-size: 14px;">
                    <i class="fas fa-link"></i> Target URL:
                </label>
                <input type="url" 
                       id="transferLinkInput" 
                       class="modal-input" 
                       placeholder="https://example.com or page.html"
                       autocomplete="off"
                       style="border: 2px solid rgba(240, 147, 251, 0.4); box-shadow: 0 3px 12px rgba(240, 147, 251, 0.15);">
            </div>
            
            <p class="modal-hint" style="background: rgba(240, 147, 251, 0.05); padding: 10px; border-radius: 8px; border-left: 3px solid #f093fb;">
                <i class="fas fa-lightbulb"></i>
                <strong>Tip:</strong> When someone opens this page, they will be <strong>automatically redirected</strong> to the URL you specify.
            </p>
            <p class="modal-hint">
                <i class="fas fa-info-circle"></i>
                You can enter a full URL (https://...) or a relative path (page.html)
            </p>
            <div class="modal-buttons">
                <button class="modal-btn modal-btn-cancel" onclick="closeTransferLinkModal()">
                    <i class="fas fa-times"></i>
                    Cancel
                </button>
                <button class="modal-btn modal-btn-confirm" 
                        id="confirmTransferLinkBtn" 
                        onclick="confirmTransferLink()"
                        style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); box-shadow: 0 4px 15px rgba(240, 147, 251, 0.3);">
                    <i class="fas fa-link"></i>
                    Add Transfer Link
                </button>
            </div>
        </div>
    </div>

    <!-- iFrame Modal -->
    <div class="modal-overlay" id="iframeModal">
        <div class="modal-content">
            <div class="modal-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <i class="fas fa-window-restore"></i>
            </div>
            <h2 class="modal-title">🖼️ Append iFrame</h2>
            <p class="modal-message">Enter the URL of the content to embed in an iFrame:</p>
            <div class="modal-filename" id="iframeFilename" style="background: rgba(79, 172, 254, 0.1); color: #00f2fe; margin-bottom: 15px;"></div>
            
            <div style="background: rgba(79, 172, 254, 0.08); border: 2px solid rgba(79, 172, 254, 0.3); border-radius: 12px; padding: 15px; margin-bottom: 15px;">
                <label style="display: block; font-weight: 600; color: #00f2fe; margin-bottom: 10px; font-size: 14px;">
                    <i class="fas fa-globe"></i> iFrame URL:
                </label>
                <input type="url" 
                       id="iframeInput" 
                       class="modal-input" 
                       placeholder="https://example.com"
                       autocomplete="off"
                       style="border: 2px solid rgba(79, 172, 254, 0.4); box-shadow: 0 3px 12px rgba(79, 172, 254, 0.15);">
            </div>
            
            <p class="modal-hint" style="background: rgba(79, 172, 254, 0.05); padding: 10px; border-radius: 8px; border-left: 3px solid #4facfe;">
                <i class="fas fa-lightbulb"></i>
                <strong>Tip:</strong> The iFrame will be <strong>appended at the end</strong> of your page with a beautiful responsive design.
            </p>
            <p class="modal-hint">
                <i class="fas fa-info-circle"></i>
                Embedded content size: 100% width × 600px height (responsive)
            </p>
            <div class="modal-buttons">
                <button class="modal-btn modal-btn-cancel" onclick="closeIframeModal()">
                    <i class="fas fa-times"></i>
                    Cancel
                </button>
                <button class="modal-btn modal-btn-confirm" 
                        id="confirmIframeBtn" 
                        onclick="confirmIframe()"
                        style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); box-shadow: 0 4px 15px rgba(79, 172, 254, 0.3);">
                    <i class="fas fa-plus-square"></i>
                    Append iFrame
                </button>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div class="toast" id="toast">
        <i class="fas fa-check-circle"></i>
        <span class="toast-message" id="toastMessage">Action completed</span>
    </div>

    <script>
        // Generate animated background particles
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 30;

            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                
                const size = Math.random() * 60 + 20;
                particle.style.width = size + 'px';
                particle.style.height = size + 'px';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.top = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 15 + 's';
                particle.style.animationDuration = (Math.random() * 10 + 10) + 's';
                
                particlesContainer.appendChild(particle);
            }
        }

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase().trim();
            const cards = document.querySelectorAll('.page-card');
            const noResults = document.getElementById('noResults');
            const catalogGrid = document.getElementById('catalogGrid');
            let visibleCount = 0;

            cards.forEach(card => {
                const filename = card.dataset.filename;
                const name = card.dataset.name;
                
                if (filename.includes(searchTerm) || name.includes(searchTerm)) {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            document.getElementById('visiblePages').textContent = visibleCount;

            if (visibleCount === 0 && searchTerm !== '') {
                catalogGrid.style.display = 'none';
                noResults.style.display = 'block';
            } else {
                catalogGrid.style.display = 'grid';
                noResults.style.display = 'none';
            }
        });

        // Delete functionality
        let fileToDelete = null;

        function showDeleteModal(filename) {
            fileToDelete = filename;
            document.getElementById('modalFilename').textContent = filename;
            document.getElementById('deleteModal').classList.add('active');
            document.body.style.overflow = 'hidden'; // Prevent scrolling
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
            document.body.style.overflow = ''; // Restore scrolling
            fileToDelete = null;
        }

        async function confirmDelete() {
            if (!fileToDelete) return;

            const confirmBtn = document.getElementById('confirmDeleteBtn');
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';

            try {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('filename', fileToDelete);

                const response = await fetch('index.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showToast(result.message, 'success');
                    closeDeleteModal();
                    
                    // Refresh page after short delay to show success message
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showToast(result.message, 'error');
                }
            } catch (error) {
                showToast('Error: ' + error.message, 'error');
            } finally {
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = '<i class="fas fa-trash-alt"></i> Yes, Delete';
            }
        }

        // Toggle back button (add or remove)
        async function toggleBackButton(filename, button) {
            const isRemoveMode = button.classList.contains('remove-mode');
            
            button.disabled = true;
            button.innerHTML = isRemoveMode ? 
                '<i class="fas fa-spinner fa-spin"></i> Removing...' : 
                '<i class="fas fa-spinner fa-spin"></i> Adding...';

            try {
                const formData = new FormData();
                formData.append('action', 'toggle_back_button');
                formData.append('filename', filename);

                const response = await fetch('index.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showToast(result.message, 'success');
                    
                    if (result.action === 'added') {
                        // Switch to remove mode
                        button.classList.add('remove-mode');
                        button.innerHTML = '<i class="fas fa-trash-alt"></i> Remove Home';
                        button.style.background = 'linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%)';
                    } else if (result.action === 'removed') {
                        // Switch to add mode
                        button.classList.remove('remove-mode');
                        button.innerHTML = '<i class="fas fa-home"></i> Add Back Home';
                        button.style.background = 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)';
                    }
                    button.disabled = false;
                } else {
                    showToast(result.message, 'error');
                    button.disabled = false;
                    button.innerHTML = isRemoveMode ? 
                        '<i class="fas fa-trash-alt"></i> Remove Home' : 
                        '<i class="fas fa-home"></i> Add Back Home';
                }
            } catch (error) {
                showToast('Error: ' + error.message, 'error');
                button.disabled = false;
                button.innerHTML = isRemoveMode ? 
                    '<i class="fas fa-trash-alt"></i> Remove Home' : 
                    '<i class="fas fa-home"></i> Add Back Home';
            }
        }

        // Toggle platform button (add or remove)
        async function toggleCodeEditorButton(filename, button) {
            const isRemoveMode = button.classList.contains('remove-mode');
            
            console.log('🟢 Toggle Code Editor Button:', filename, 'Remove mode:', isRemoveMode);
            
            button.disabled = true;
            button.innerHTML = isRemoveMode ? 
                '<i class="fas fa-spinner fa-spin"></i> Removing...' : 
                '<i class="fas fa-spinner fa-spin"></i> Adding...';

            try {
                const formData = new FormData();
                formData.append('action', 'toggle_code_editor_button');
                formData.append('filename', filename);

                const response = await fetch('index.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                console.log('🟢 Code Editor Button Response:', result);

                if (result.success) {
                    showToast(result.message, 'success');
                    
                    if (result.action === 'added') {
                        // Switch to remove mode
                        button.classList.add('remove-mode');
                        button.innerHTML = '<i class="fas fa-trash-alt"></i> Remove Code Editor';
                        button.style.background = 'linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%)';
                        console.log('✅ Code Editor button added to file');
                    } else if (result.action === 'removed') {
                        // Switch to add mode
                        button.classList.remove('remove-mode');
                        button.innerHTML = '<i class="fas fa-code"></i> Add Back to Code Editor';
                        button.style.background = 'linear-gradient(135deg, #11998e 0%, #38ef7d 100%)';
                        console.log('✅ Code Editor button removed from file');
                    }
                    button.disabled = false;
                } else {
                    console.error('❌ Code Editor button error:', result.message);
                    showToast(result.message, 'error');
                    button.disabled = false;
                    button.innerHTML = isRemoveMode ? 
                        '<i class="fas fa-trash-alt"></i> Remove Code Editor' : 
                        '<i class="fas fa-code"></i> Add Back to Code Editor';
                }
            } catch (error) {
                console.error('❌ Code Editor button exception:', error);
                showToast('Error: ' + error.message, 'error');
                button.disabled = false;
                button.innerHTML = isRemoveMode ? 
                    '<i class="fas fa-trash-alt"></i> Remove Code Editor' : 
                    '<i class="fas fa-code"></i> Add Back to Code Editor';
            }
        }

        async function togglePlatformButton(filename, button) {
            const isRemoveMode = button.classList.contains('remove-mode');
            
            console.log('🔵 Toggle Platform Button:', filename, 'Remove mode:', isRemoveMode);
            
            button.disabled = true;
            button.innerHTML = isRemoveMode ? 
                '<i class="fas fa-spinner fa-spin"></i> Removing...' : 
                '<i class="fas fa-spinner fa-spin"></i> Adding...';

            try {
                const formData = new FormData();
                formData.append('action', 'toggle_platform_button');
                formData.append('filename', filename);

                const response = await fetch('index.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                console.log('🔵 Platform Button Response:', result);

                if (result.success) {
                    showToast(result.message, 'success');
                    
                    if (result.action === 'added') {
                        // Switch to remove mode
                        button.classList.add('remove-mode');
                        button.innerHTML = '<i class="fas fa-trash-alt"></i> Remove AI Platform';
                        button.style.background = 'linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%)';
                        console.log('✅ Platform button added to file');
                    } else if (result.action === 'removed') {
                        // Switch to add mode
                        button.classList.remove('remove-mode');
                        button.innerHTML = '<i class="fas fa-robot"></i> Add Back to AI Platform';
                        button.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
                        console.log('✅ Platform button removed from file');
                    }
                    button.disabled = false;
                } else {
                    console.error('❌ Platform button error:', result.message);
                    showToast(result.message, 'error');
                    button.disabled = false;
                    button.innerHTML = isRemoveMode ? 
                        '<i class="fas fa-trash-alt"></i> Remove AI Platform' : 
                        '<i class="fas fa-robot"></i> Add Back to AI Platform';
                }
            } catch (error) {
                console.error('❌ Platform button exception:', error);
                showToast('Error: ' + error.message, 'error');
                button.disabled = false;
                button.innerHTML = isRemoveMode ? 
                    '<i class="fas fa-trash-alt"></i> Remove AI Platform' : 
                    '<i class="fas fa-robot"></i> Add Back to AI Platform';
            }
        }

        // Rename functionality
        let fileToRename = null;

        function showRenameModal(filename) {
            fileToRename = filename;
            document.getElementById('renameOldFilename').textContent = filename;
            
            // Pre-fill input with current filename (without extension) for easy editing
            const nameWithoutExt = filename.substring(0, filename.lastIndexOf('.'));
            const extension = filename.substring(filename.lastIndexOf('.'));
            
            document.getElementById('renameInput').value = filename;
            document.getElementById('renameInput').focus();
            
            // Select filename part only (not extension)
            setTimeout(() => {
                const input = document.getElementById('renameInput');
                input.setSelectionRange(0, nameWithoutExt.length);
            }, 100);
            
            document.getElementById('renameModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeRenameModal() {
            document.getElementById('renameModal').classList.remove('active');
            document.body.style.overflow = '';
            document.getElementById('renameInput').value = '';
            fileToRename = null;
        }

        async function confirmRename() {
            if (!fileToRename) return;

            const newFilename = document.getElementById('renameInput').value.trim();
            
            if (!newFilename) {
                showToast('Please enter a new filename!', 'error');
                return;
            }

            if (newFilename === fileToRename) {
                showToast('New filename must be different!', 'error');
                return;
            }

            const confirmBtn = document.getElementById('confirmRenameBtn');
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Renaming...';

            try {
                const formData = new FormData();
                formData.append('action', 'rename');
                formData.append('old_filename', fileToRename);
                formData.append('new_filename', newFilename);

                const response = await fetch('index.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showToast(result.message, 'success');
                    closeRenameModal();
                    
                    // Refresh page after short delay
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showToast(result.message, 'error');
                }
            } catch (error) {
                showToast('Error: ' + error.message, 'error');
            } finally {
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = '<i class="fas fa-check"></i> Rename File';
            }
        }

        // Duplicate functionality
        let fileToDuplicate = null;

        function showDuplicateModal(filename) {
            fileToDuplicate = filename;
            document.getElementById('duplicateSourceFilename').textContent = filename;
            
            // Generate default duplicate name: originalname_copy.ext
            const nameWithoutExt = filename.substring(0, filename.lastIndexOf('.'));
            const extension = filename.substring(filename.lastIndexOf('.'));
            const defaultDuplicateName = nameWithoutExt + '_copy' + extension;
            
            document.getElementById('duplicateInput').value = defaultDuplicateName;
            document.getElementById('duplicateInput').focus();
            
            // Select filename part only (not extension)
            setTimeout(() => {
                const input = document.getElementById('duplicateInput');
                const namePartLength = (nameWithoutExt + '_copy').length;
                input.setSelectionRange(0, namePartLength);
            }, 100);
            
            document.getElementById('duplicateModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeDuplicateModal() {
            document.getElementById('duplicateModal').classList.remove('active');
            document.body.style.overflow = '';
            document.getElementById('duplicateInput').value = '';
            fileToDuplicate = null;
        }

        async function confirmDuplicate() {
            if (!fileToDuplicate) return;

            const newFilename = document.getElementById('duplicateInput').value.trim();
            
            if (!newFilename) {
                showToast('Please enter a filename for the duplicate!', 'error');
                return;
            }

            if (newFilename === fileToDuplicate) {
                showToast('Duplicate filename must be different from the original!', 'error');
                return;
            }

            const confirmBtn = document.getElementById('confirmDuplicateBtn');
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Duplicating...';

            try {
                const formData = new FormData();
                formData.append('action', 'duplicate');
                formData.append('source_filename', fileToDuplicate);
                formData.append('new_filename', newFilename);

                const response = await fetch('index.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showToast(result.message, 'success');
                    closeDuplicateModal();
                    
                    // Refresh page after short delay
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showToast(result.message, 'error');
                }
            } catch (error) {
                showToast('Error: ' + error.message, 'error');
            } finally {
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = '<i class="fas fa-copy"></i> Duplicate File';
            }
        }

        // Allow Enter key to confirm rename
        document.addEventListener('DOMContentLoaded', () => {
            const renameInput = document.getElementById('renameInput');
            if (renameInput) {
                renameInput.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        confirmRename();
                    }
                });
            }

            // Enter key for duplicate
            const duplicateInput = document.getElementById('duplicateInput');
            if (duplicateInput) {
                duplicateInput.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        confirmDuplicate();
                    }
                });
            }

            // Enter key for create file
            const createFileInput = document.getElementById('createFileInput');
            if (createFileInput) {
                createFileInput.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        confirmCreateFile();
                    }
                });
            }

            // Enter key for transfer link
            const transferLinkInput = document.getElementById('transferLinkInput');
            if (transferLinkInput) {
                transferLinkInput.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        confirmTransferLink();
                    }
                });
            }

            // Enter key for iframe
            const iframeInput = document.getElementById('iframeInput');
            if (iframeInput) {
                iframeInput.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        confirmIframe();
                    }
                });
            }
        });

        // Create File functionality
        let selectedFileType = 'html';

        function showCreateFileModal() {
            document.getElementById('createFileModal').classList.add('active');
            document.body.style.overflow = 'hidden';
            document.getElementById('createFileInput').focus();
        }

        function closeCreateFileModal() {
            document.getElementById('createFileModal').classList.remove('active');
            document.body.style.overflow = '';
            document.getElementById('createFileInput').value = '';
            document.getElementById('pageTitleInput').value = '';
            document.getElementById('includeTitleCheckbox').checked = true;
            selectedFileType = 'html';
            // Reset to HTML option
            document.getElementById('htmlOption').classList.add('active');
            document.getElementById('phpOption').classList.remove('active');
            document.getElementById('htmlRadio').checked = true;
            // Reset template select to first option
            document.getElementById('templateSelect').selectedIndex = 0;
            // Clear file inputs and previews
            document.getElementById('faviconInput').value = '';
            document.getElementById('logoInput').value = '';
            document.getElementById('faviconPreview').style.display = 'none';
            document.getElementById('logoPreview').style.display = 'none';
            document.getElementById('faviconPreviewImg').src = '';
            document.getElementById('logoPreviewImg').src = '';
        }

        function selectFileType(type) {
            selectedFileType = type;
            const htmlOption = document.getElementById('htmlOption');
            const phpOption = document.getElementById('phpOption');
            const input = document.getElementById('createFileInput');
            const templateSelect = document.getElementById('templateSelect');

            if (type === 'html') {
                htmlOption.classList.add('active');
                phpOption.classList.remove('active');
                document.getElementById('htmlRadio').checked = true;
                
                // Update placeholder
                if (!input.value || input.value.endsWith('.php')) {
                    input.placeholder = 'my-new-page.html';
                }
                
                // Show HTML templates
                templateSelect.innerHTML = `
                    <optgroup label="Empty Pages">
                        <option value="empty">📄 Empty Page (No Title)</option>
                        <option value="empty_with_title">📝 Empty Page with Title</option>
                    </optgroup>
                    <optgroup label="Landing Pages">
                        <option value="landing_modern">🚀 Modern Landing</option>
                        <option value="landing_startup">💡 Startup Landing</option>
                        <option value="landing_app">📱 App Landing</option>
                        <option value="landing_saas">☁️ SaaS Landing</option>
                        <option value="landing_product">🎯 Product Showcase</option>
                    </optgroup>
                    <optgroup label="Authentication">
                        <option value="login_modern">🔐 Modern Login</option>
                        <option value="login_minimal">🔑 Minimal Login</option>
                        <option value="signup">📝 Sign Up</option>
                        <option value="forgot_password">🔄 Forgot Password</option>
                    </optgroup>
                    <optgroup label="Dashboard & Admin">
                        <option value="dashboard">📊 Dashboard</option>
                        <option value="admin_panel">⚙️ Admin Panel</option>
                        <option value="analytics">📈 Analytics</option>
                    </optgroup>
                    <optgroup label="Portfolio & Resume">
                        <option value="portfolio">🎨 Portfolio</option>
                        <option value="resume">📄 Resume/CV</option>
                        <option value="about_me">👤 About Me</option>
                    </optgroup>
                    <optgroup label="E-Commerce">
                        <option value="product_page">🛍️ Product Page</option>
                        <option value="cart">🛒 Shopping Cart</option>
                        <option value="checkout">💳 Checkout</option>
                    </optgroup>
                    <optgroup label="Blog & Content">
                        <option value="blog_home">📰 Blog Homepage</option>
                        <option value="blog_post">📝 Blog Post</option>
                        <option value="article">📄 Article</option>
                    </optgroup>
                    <optgroup label="Contact & Forms">
                        <option value="contact">📧 Contact Page</option>
                        <option value="form_page">📋 Form Page</option>
                    </optgroup>
                    <optgroup label="Coming Soon & Maintenance">
                        <option value="coming_soon">🚧 Coming Soon</option>
                        <option value="maintenance">🔧 Maintenance</option>
                        <option value="404">❌ 404 Error</option>
                    </optgroup>
                `;
            } else {
                phpOption.classList.add('active');
                htmlOption.classList.remove('active');
                document.getElementById('phpRadio').checked = true;
                
                // Update placeholder
                if (!input.value || input.value.endsWith('.html')) {
                    input.placeholder = 'my-new-api.php';
                }
                
                // Show PHP templates
                templateSelect.innerHTML = `
                    <optgroup label="Empty Pages">
                        <option value="empty">📄 Empty Page (No Title)</option>
                        <option value="empty_with_title">📝 Empty Page with Title</option>
                    </optgroup>
                    <optgroup label="API Endpoints">
                        <option value="api_rest">🔌 REST API</option>
                        <option value="api_crud">🔄 CRUD API</option>
                        <option value="api_auth">🔐 Authentication API</option>
                        <option value="api_upload">📤 File Upload API</option>
                    </optgroup>
                    <optgroup label="Database Operations">
                        <option value="db_connect">🗄️ Database Connection</option>
                        <option value="db_operations">📊 DB Operations</option>
                        <option value="db_migration">🔄 Migration Script</option>
                    </optgroup>
                    <optgroup label="Form Handlers">
                        <option value="form_handler">📋 Form Handler</option>
                        <option value="contact_handler">📧 Contact Form</option>
                        <option value="ajax_handler">⚡ AJAX Handler</option>
                    </optgroup>
                    <optgroup label="Authentication & Security">
                        <option value="login_handler">🔑 Login Handler</option>
                        <option value="register_handler">📝 Registration</option>
                        <option value="session_manager">🔒 Session Manager</option>
                        <option value="jwt_auth">🎫 JWT Authentication</option>
                    </optgroup>
                    <optgroup label="Utilities">
                        <option value="mailer">📧 Email Sender</option>
                        <option value="image_processor">🖼️ Image Processor</option>
                        <option value="pdf_generator">📄 PDF Generator</option>
                        <option value="csv_exporter">📊 CSV Exporter</option>
                    </optgroup>
                `;
            }
        }

        // Image preview handlers
        document.addEventListener('DOMContentLoaded', () => {
            // Favicon preview
            const faviconInput = document.getElementById('faviconInput');
            const faviconPreview = document.getElementById('faviconPreview');
            const faviconPreviewImg = document.getElementById('faviconPreviewImg');
            
            if (faviconInput) {
                faviconInput.addEventListener('change', (e) => {
                    const file = e.target.files[0];
                    if (file && file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = (event) => {
                            faviconPreviewImg.src = event.target.result;
                            faviconPreview.style.display = 'flex';
                        };
                        reader.readAsDataURL(file);
                    } else {
                        faviconPreview.style.display = 'none';
                    }
                });
            }
            
            // Logo preview
            const logoInput = document.getElementById('logoInput');
            const logoPreview = document.getElementById('logoPreview');
            const logoPreviewImg = document.getElementById('logoPreviewImg');
            
            if (logoInput) {
                logoInput.addEventListener('change', (e) => {
                    const file = e.target.files[0];
                    if (file && file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = (event) => {
                            logoPreviewImg.src = event.target.result;
                            logoPreview.style.display = 'flex';
                        };
                        reader.readAsDataURL(file);
                    } else {
                        logoPreview.style.display = 'none';
                    }
                });
            }
        });

        async function confirmCreateFile() {
            const filename = document.getElementById('createFileInput').value.trim();
            const template = document.getElementById('templateSelect').value;
            const pageTitle = document.getElementById('pageTitleInput').value.trim();
            const includeTitle = document.getElementById('includeTitleCheckbox').checked;
            const faviconFile = document.getElementById('faviconInput').files[0];
            const logoFile = document.getElementById('logoInput').files[0];

            if (!filename) {
                showToast('Please enter a filename!', 'error');
                return;
            }

            // Check if filename has correct extension
            const extension = filename.substring(filename.lastIndexOf('.')).toLowerCase();
            if (extension !== '.html' && extension !== '.php') {
                showToast('File must have .html or .php extension!', 'error');
                return;
            }

            const confirmBtn = document.getElementById('confirmCreateBtn');
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';

            try {
                const formData = new FormData();
                formData.append('action', 'create_file');
                formData.append('filename', filename);
                formData.append('file_type', selectedFileType);
                formData.append('template', template);
                formData.append('page_title', pageTitle);
                formData.append('include_title', includeTitle ? 'true' : 'false');
                
                // Append favicon if selected
                if (faviconFile) {
                    formData.append('favicon', faviconFile);
                }
                
                // Append logo if selected
                if (logoFile) {
                    formData.append('logo', logoFile);
                }

                const response = await fetch('index.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showToast(result.message, 'success');
                    closeCreateFileModal();
                    
                    // Refresh page after short delay
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showToast(result.message, 'error');
                }
            } catch (error) {
                showToast('Error: ' + error.message, 'error');
            } finally {
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = '<i class="fas fa-plus-circle"></i> Create File';
            }
        }

        // ==========================================
        // TRANSFER LINK FUNCTIONALITY
        // ==========================================
        
        let currentTransferLinkFile = null;
        let currentTransferLinkButton = null;

        function showTransferLinkModal(filename, button) {
            currentTransferLinkFile = filename;
            currentTransferLinkButton = button;
            
            document.getElementById('transferLinkFilename').textContent = filename;
            document.getElementById('transferLinkInput').value = '';
            document.getElementById('transferLinkModal').classList.add('active');
            document.body.style.overflow = 'hidden';
            document.getElementById('transferLinkInput').focus();
        }

        function closeTransferLinkModal() {
            document.getElementById('transferLinkModal').classList.remove('active');
            document.body.style.overflow = '';
            document.getElementById('transferLinkInput').value = '';
            currentTransferLinkFile = null;
            currentTransferLinkButton = null;
        }

        async function confirmTransferLink() {
            if (!currentTransferLinkFile) return;

            const targetUrl = document.getElementById('transferLinkInput').value.trim();
            
            if (!targetUrl) {
                showToast('Please enter a target URL!', 'error');
                return;
            }

            const confirmBtn = document.getElementById('confirmTransferLinkBtn');
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';

            try {
                const formData = new FormData();
                formData.append('action', 'toggle_transfer_link');
                formData.append('filename', currentTransferLinkFile);
                formData.append('target_url', targetUrl);

                const response = await fetch('index.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showToast(result.message, 'success');
                    closeTransferLinkModal();
                    
                    // Update button state
                    if (currentTransferLinkFile && result.action === 'added') {
                        updateTransferLinkButton(currentTransferLinkFile, true);
                    }
                } else {
                    showToast(result.message, 'error');
                }
            } catch (error) {
                showToast('Error: ' + error.message, 'error');
            } finally {
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = '<i class="fas fa-link"></i> Add Transfer Link';
            }
        }

        // Check if Transfer Link exists
        async function checkTransferLinkStatus(filename) {
            try {
                const formData = new FormData();
                formData.append('action', 'check_transfer_link');
                formData.append('filename', filename);
                
                const response = await fetch('index.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                return result.exists;
            } catch (error) {
                console.error('Error checking transfer link status:', error);
                return false;
            }
        }
        
        // Update Transfer Link button UI
        function updateTransferLinkButton(filename, hasTransferLink) {
            const btn = document.getElementById('transfer-btn-' + filename);
            if (!btn) return;
            
            const textSpan = btn.querySelector('.transfer-btn-text');
            btn.classList.remove('checking');
            
            if (hasTransferLink) {
                btn.classList.add('has-transfer');
                textSpan.textContent = 'Remove Transfer Link';
            } else {
                btn.classList.remove('has-transfer');
                textSpan.textContent = 'Add Transfer Link';
            }
        }
        
        // Toggle Transfer Link (Add or Remove)
        async function toggleTransferLink(filename, button) {
            if (button.classList.contains('checking')) return;
            
            button.classList.add('checking');
            const textSpan = button.querySelector('.transfer-btn-text');
            textSpan.textContent = 'Checking...';
            
            const hasTransferLink = await checkTransferLinkStatus(filename);
            
            button.classList.remove('checking');
            
            if (hasTransferLink) {
                // Remove Transfer Link
                if (!confirm('Remove transfer link from this page?')) {
                    updateTransferLinkButton(filename, hasTransferLink);
                    return;
                }
                
                await removeTransferLink(filename, button);
            } else {
                // Show Add Modal
                showTransferLinkModal(filename, button);
            }
            
            updateTransferLinkButton(filename, hasTransferLink);
        }
        
        // Remove Transfer Link
        async function removeTransferLink(filename, button) {
            try {
                const formData = new FormData();
                formData.append('action', 'toggle_transfer_link');
                formData.append('filename', filename);
                formData.append('target_url', ''); // Empty URL means remove
                
                const response = await fetch('index.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success && result.action === 'removed') {
                    showToast(result.message, 'success');
                    updateTransferLinkButton(filename, false);
                } else {
                    showToast(result.message || 'Failed to remove transfer link', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Error removing transfer link', 'error');
            }
        }

        async function toggleRemoveTransferLink(filename, button) {
            if (!confirm('Remove transfer link from this page?')) return;

            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Removing...';

            try {
                const formData = new FormData();
                formData.append('action', 'toggle_transfer_link');
                formData.append('filename', filename);
                formData.append('target_url', ''); // Empty to trigger remove

                const response = await fetch('index.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success && result.action === 'removed') {
                    showToast(result.message, 'success');
                    // Update button to add mode
                    button.classList.remove('remove-mode');
                    button.innerHTML = '<i class="fas fa-external-link-alt"></i> Add Transfer Link';
                    button.style.background = 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)';
                    button.onclick = function() { showTransferLinkModal(filename, this); };
                    button.disabled = false;
                } else {
                    showToast(result.message || 'Failed to remove transfer link', 'error');
                    button.disabled = false;
                    button.innerHTML = '<i class="fas fa-trash-alt"></i> Remove Transfer Link';
                }
            } catch (error) {
                showToast('Error: ' + error.message, 'error');
                button.disabled = false;
                button.innerHTML = '<i class="fas fa-trash-alt"></i> Remove Transfer Link';
            }
        }

        // ==========================================
        // IFRAME FUNCTIONALITY
        // ==========================================
        
        let currentIframeFile = null;
        let currentIframeButton = null;

        function showIframeModal(filename, button) {
            currentIframeFile = filename;
            currentIframeButton = button;
            
            document.getElementById('iframeFilename').textContent = filename;
            document.getElementById('iframeInput').value = '';
            document.getElementById('iframeModal').classList.add('active');
            document.body.style.overflow = 'hidden';
            document.getElementById('iframeInput').focus();
        }

        function closeIframeModal() {
            document.getElementById('iframeModal').classList.remove('active');
            document.body.style.overflow = '';
            document.getElementById('iframeInput').value = '';
            currentIframeFile = null;
            currentIframeButton = null;
        }

        async function confirmIframe() {
            if (!currentIframeFile) return;

            const iframeUrl = document.getElementById('iframeInput').value.trim();
            
            if (!iframeUrl) {
                showToast('Please enter an iFrame URL!', 'error');
                return;
            }

            const confirmBtn = document.getElementById('confirmIframeBtn');
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';

            try {
                const formData = new FormData();
                formData.append('action', 'toggle_iframe');
                formData.append('filename', currentIframeFile);
                formData.append('iframe_url', iframeUrl);

                const response = await fetch('index.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showToast(result.message, 'success');
                    closeIframeModal();
                    
                    if (currentIframeButton && result.action === 'added') {
                        // Update button to remove mode
                        currentIframeButton.classList.add('remove-mode');
                        currentIframeButton.innerHTML = '<i class="fas fa-trash-alt"></i> Remove iFrame';
                        currentIframeButton.style.background = 'linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%)';
                        currentIframeButton.onclick = function() { toggleRemoveIframe(currentIframeFile, this); };
                    }
                } else {
                    showToast(result.message, 'error');
                }
            } catch (error) {
                showToast('Error: ' + error.message, 'error');
            } finally {
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = '<i class="fas fa-plus-square"></i> Append iFrame';
            }
        }

        async function toggleRemoveIframe(filename, button) {
            if (!confirm('Remove iFrame from this page?')) return;

            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Removing...';

            try {
                const formData = new FormData();
                formData.append('action', 'toggle_iframe');
                formData.append('filename', filename);
                formData.append('iframe_url', ''); // Empty to trigger remove

                const response = await fetch('index.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success && result.action === 'removed') {
                    showToast(result.message, 'success');
                    // Update button to add mode
                    button.classList.remove('remove-mode');
                    button.innerHTML = '<i class="fas fa-window-restore"></i> Append iFrame';
                    button.style.background = 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)';
                    button.onclick = function() { showIframeModal(filename, this); };
                    button.disabled = false;
                } else {
                    showToast(result.message || 'Failed to remove iFrame', 'error');
                    button.disabled = false;
                    button.innerHTML = '<i class="fas fa-trash-alt"></i> Remove iFrame';
                }
            } catch (error) {
                showToast('Error: ' + error.message, 'error');
                button.disabled = false;
                button.innerHTML = '<i class="fas fa-trash-alt"></i> Remove iFrame';
            }
        }

        // ==========================================
        // EDIT PAGE FUNCTIONALITY
        // ==========================================
        
        let currentEditFilename = null;

        async function checkAndShowEditModal(filename) {
            try {
                const response = await fetch(filename);
                const content = await response.text();
                
                if (content.includes('<!-- CATALOG_GENERATED_PAGE -->')) {
                    showEditPageModal(filename, content);
                } else {
                    showToast('This page was not created by catalog!', 'error');
                }
            } catch (error) {
                showToast('Error loading page: ' + error.message, 'error');
            }
        }

        function showEditPageModal(filename, content) {
            currentEditFilename = filename;
            document.getElementById('editPageFilename').textContent = filename;
            
            // Try to extract current settings from content
            // Extract page title from <title> tag
            const titleMatch = content.match(/<title>(.*?)<\/title>/i);
            if (titleMatch) {
                document.getElementById('editPageTitleInput').value = titleMatch[1];
            }
            
            // Check if favicon exists
            const faviconMatch = content.match(/href="(assets\/favicon_[^"]+)"/i);
            if (faviconMatch) {
                document.getElementById('editCurrentFavicon').style.display = 'block';
                document.getElementById('editCurrentFaviconText').textContent = `Current: ${faviconMatch[1]}`;
            } else {
                document.getElementById('editCurrentFavicon').style.display = 'none';
            }
            
            // Check if logo exists
            const logoMatch = content.match(/src="(assets\/logo_[^"]+)"/i);
            if (logoMatch) {
                document.getElementById('editCurrentLogo').style.display = 'block';
                document.getElementById('editCurrentLogoText').textContent = `Current: ${logoMatch[1]}`;
            } else {
                document.getElementById('editCurrentLogo').style.display = 'none';
            }
            
            // Show modal
            document.getElementById('editPageModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeEditPageModal() {
            document.getElementById('editPageModal').classList.remove('active');
            document.body.style.overflow = '';
            document.getElementById('editPageTitleInput').value = '';
            document.getElementById('editIncludeTitleCheckbox').checked = true;
            document.getElementById('editFaviconInput').value = '';
            document.getElementById('editLogoInput').value = '';
            document.getElementById('editFaviconPreview').style.display = 'none';
            document.getElementById('editLogoPreview').style.display = 'none';
            document.getElementById('editTemplateSelect').selectedIndex = 0;
            currentEditFilename = null;
        }

        async function confirmEditPage() {
            if (!currentEditFilename) return;

            const template = document.getElementById('editTemplateSelect').value;
            const pageTitle = document.getElementById('editPageTitleInput').value.trim();
            const includeTitle = document.getElementById('editIncludeTitleCheckbox').checked;
            const faviconFile = document.getElementById('editFaviconInput').files[0];
            const logoFile = document.getElementById('editLogoInput').files[0];

            const confirmBtn = document.getElementById('confirmEditPageBtn');
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

            try {
                const formData = new FormData();
                formData.append('action', 'edit_page');
                formData.append('filename', currentEditFilename);
                formData.append('template', template);
                formData.append('page_title', pageTitle);
                formData.append('include_title', includeTitle ? 'true' : 'false');
                
                // Append files if selected
                if (faviconFile) {
                    formData.append('favicon', faviconFile);
                }
                
                if (logoFile) {
                    formData.append('logo', logoFile);
                }

                const response = await fetch('index.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showToast(result.message, 'success');
                    closeEditPageModal();
                    
                    // Refresh page after short delay
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showToast(result.message, 'error');
                }
            } catch (error) {
                showToast('Error: ' + error.message, 'error');
            } finally {
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = '<i class="fas fa-save"></i> Save & Regenerate';
            }
        }

        // Edit page image preview handlers
        document.addEventListener('DOMContentLoaded', () => {
            // Edit Favicon preview
            const editFaviconInput = document.getElementById('editFaviconInput');
            const editFaviconPreview = document.getElementById('editFaviconPreview');
            const editFaviconPreviewImg = document.getElementById('editFaviconPreviewImg');
            
            if (editFaviconInput) {
                editFaviconInput.addEventListener('change', (e) => {
                    const file = e.target.files[0];
                    if (file && file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = (event) => {
                            editFaviconPreviewImg.src = event.target.result;
                            editFaviconPreview.style.display = 'flex';
                        };
                        reader.readAsDataURL(file);
                    } else {
                        editFaviconPreview.style.display = 'none';
                    }
                });
            }
            
            // Edit Logo preview
            const editLogoInput = document.getElementById('editLogoInput');
            const editLogoPreview = document.getElementById('editLogoPreview');
            const editLogoPreviewImg = document.getElementById('editLogoPreviewImg');
            
            if (editLogoInput) {
                editLogoInput.addEventListener('change', (e) => {
                    const file = e.target.files[0];
                    if (file && file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = (event) => {
                            editLogoPreviewImg.src = event.target.result;
                            editLogoPreview.style.display = 'flex';
                        };
                        reader.readAsDataURL(file);
                    } else {
                        editLogoPreview.style.display = 'none';
                    }
                });
            }
        });

        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            const toastMessage = document.getElementById('toastMessage');
            const icon = toast.querySelector('i');

            // Remove existing classes
            toast.classList.remove('success', 'error', 'show');
            
            // Set icon based on type
            if (type === 'success') {
                icon.className = 'fas fa-check-circle';
                toast.classList.add('success');
            } else {
                icon.className = 'fas fa-exclamation-circle';
                toast.classList.add('error');
            }

            toastMessage.textContent = message;
            
            // Show toast
            setTimeout(() => toast.classList.add('show'), 100);

            // Hide toast after 3 seconds
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }

        // Close delete modal on overlay click
        document.getElementById('deleteModal').addEventListener('click', (e) => {
            if (e.target.id === 'deleteModal') {
                closeDeleteModal();
            }
        });

        // Close rename modal on overlay click
        document.getElementById('renameModal').addEventListener('click', (e) => {
            if (e.target.id === 'renameModal') {
                closeRenameModal();
            }
        });

        // Close duplicate modal on overlay click
        document.getElementById('duplicateModal').addEventListener('click', (e) => {
            if (e.target.id === 'duplicateModal') {
                closeDuplicateModal();
            }
        });

        // Close create file modal on overlay click
        document.getElementById('createFileModal').addEventListener('click', (e) => {
            if (e.target.id === 'createFileModal') {
                closeCreateFileModal();
            }
        });

        // Close transfer link modal on overlay click
        document.getElementById('transferLinkModal').addEventListener('click', (e) => {
            if (e.target.id === 'transferLinkModal') {
                closeTransferLinkModal();
            }
        });

        // Close iframe modal on overlay click
        document.getElementById('iframeModal').addEventListener('click', (e) => {
            if (e.target.id === 'iframeModal') {
                closeIframeModal();
            }
        });

        // Close edit page modal on overlay click
        document.getElementById('editPageModal').addEventListener('click', (e) => {
            if (e.target.id === 'editPageModal') {
                closeEditPageModal();
            }
        });

        // Close modals on ESC key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeDeleteModal();
                closeRenameModal();
                closeDuplicateModal();
                closeCreateFileModal();
                closeTransferLinkModal();
                closeIframeModal();
                closeEditPageModal();
            }
        });

        // Initialize button states based on file type and content
        async function initializeButtonStates() {
            const cards = document.querySelectorAll('.page-card');
            console.log('🔄 Initializing button states for', cards.length, 'cards');
            
            for (const card of cards) {
                const extension = card.dataset.extension;
                const filepath = card.dataset.filepath;
                const homeButton = card.querySelector('.add-back-btn');
                const codeEditorButton = card.querySelector('.add-code-editor-btn');
                const platformButton = card.querySelector('.add-platform-btn');
                
                // For both HTML and PHP files - check if buttons exist
                if (extension === 'html' || extension === 'php') {
                    try {
                        const response = await fetch(filepath);
                        const content = await response.text();
                        
                        // Check if page is catalog-generated
                        const editPageButton = card.querySelector('.edit-page-btn');
                        if (editPageButton) {
                            const isCatalogGenerated = content.includes('<!-- CATALOG_GENERATED_PAGE -->');
                            console.log('  ⚙️', filepath, '- Catalog generated:', isCatalogGenerated);
                            
                            if (isCatalogGenerated) {
                                editPageButton.style.display = 'inline-flex';
                            }
                        }
                        
                        // Check Home button
                        if (homeButton) {
                            const hasHomeBtn = content.includes('id="backToCatalogBtn"') || content.includes('catalog-back-btn');
                            console.log('  🏠', filepath, '- Home button exists:', hasHomeBtn);
                            
                            if (hasHomeBtn) {
                                // Back button exists - show remove mode
                                homeButton.classList.add('remove-mode');
                                homeButton.disabled = false;
                                homeButton.style.opacity = '1';
                                homeButton.style.cursor = 'pointer';
                                homeButton.style.background = 'linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%)';
                                homeButton.innerHTML = '<i class="fas fa-trash-alt"></i> Remove Home';
                            } else {
                                // Back button doesn't exist - show add mode
                                homeButton.classList.remove('remove-mode');
                                homeButton.disabled = false;
                                homeButton.style.opacity = '1';
                                homeButton.style.cursor = 'pointer';
                                homeButton.style.background = 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)';
                                homeButton.innerHTML = '<i class="fas fa-home"></i> Add Back Home';
                            }
                        }
                        
                        // Check Code Editor button
                        if (codeEditorButton) {
                            const hasCodeEditorBtn = content.includes('id="backToCodeEditorBtn"') || content.includes('code-editor-back-btn');
                            console.log('  🟢', filepath, '- Code Editor button exists:', hasCodeEditorBtn);
                            
                            if (hasCodeEditorBtn) {
                                // Code Editor button exists - show remove mode
                                codeEditorButton.classList.add('remove-mode');
                                codeEditorButton.disabled = false;
                                codeEditorButton.style.opacity = '1';
                                codeEditorButton.style.cursor = 'pointer';
                                codeEditorButton.style.background = 'linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%)';
                                codeEditorButton.innerHTML = '<i class="fas fa-trash-alt"></i> Remove Code Editor';
                            } else {
                                // Code Editor button doesn't exist - show add mode
                                codeEditorButton.classList.remove('remove-mode');
                                codeEditorButton.disabled = false;
                                codeEditorButton.style.opacity = '1';
                                codeEditorButton.style.cursor = 'pointer';
                                codeEditorButton.style.background = 'linear-gradient(135deg, #11998e 0%, #38ef7d 100%)';
                                codeEditorButton.innerHTML = '<i class="fas fa-code"></i> Add Back to Code Editor';
                            }
                        }
                        
                        // Check Platform button
                        if (platformButton) {
                            const hasPlatformBtn = content.includes('id="backToPlatformBtn"') || content.includes('platform-back-btn');
                            console.log('  💻', filepath, '- Platform button exists:', hasPlatformBtn);
                            
                            if (hasPlatformBtn) {
                                // Platform button exists - show remove mode
                                platformButton.classList.add('remove-mode');
                                platformButton.disabled = false;
                                platformButton.style.opacity = '1';
                                platformButton.style.cursor = 'pointer';
                                platformButton.style.background = 'linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%)';
                                platformButton.innerHTML = '<i class="fas fa-trash-alt"></i> Remove AI Platform';
                            } else {
                                // Platform button doesn't exist - show add mode
                                platformButton.classList.remove('remove-mode');
                                platformButton.disabled = false;
                                platformButton.style.opacity = '1';
                                platformButton.style.cursor = 'pointer';
                                platformButton.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
                                platformButton.innerHTML = '<i class="fas fa-robot"></i> Add Back to AI Platform';
                            }
                        }
                        
                        // Check Transfer Link button
                        const transferLinkButton = card.querySelector('.transfer-link-toggle-btn');
                        if (transferLinkButton) {
                            const hasTransferLink = content.includes('id="autoTransferScript"') || content.includes('auto-transfer-redirect');
                            console.log('  🔄', filepath, '- Transfer Link exists:', hasTransferLink);
                            updateTransferLinkButton(filepath, hasTransferLink);
                        }
                        
                        // Check iFrame button
                        const iframeButton = card.querySelector('.add-iframe-btn');
                        if (iframeButton) {
                            const hasIframe = content.includes('id="appendedIframe"') || content.includes('appended-iframe-container');
                            console.log('  🖼️', filepath, '- iFrame exists:', hasIframe);
                            
                            if (hasIframe) {
                                // iFrame exists - show remove mode
                                iframeButton.classList.add('remove-mode');
                                iframeButton.disabled = false;
                                iframeButton.style.opacity = '1';
                                iframeButton.style.cursor = 'pointer';
                                iframeButton.style.background = 'linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%)';
                                iframeButton.innerHTML = '<i class="fas fa-trash-alt"></i> Remove iFrame';
                                iframeButton.onclick = function() { toggleRemoveIframe(filepath, this); };
                            } else {
                                // iFrame doesn't exist - show add mode
                                iframeButton.classList.remove('remove-mode');
                                iframeButton.disabled = false;
                                iframeButton.style.opacity = '1';
                                iframeButton.style.cursor = 'pointer';
                                iframeButton.style.background = 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)';
                                iframeButton.innerHTML = '<i class="fas fa-window-restore"></i> Append iFrame';
                                iframeButton.onclick = function() { showIframeModal(filepath, this); };
                            }
                        }
                    } catch (error) {
                        console.error('❌ Error checking file:', filepath, error);
                    }
                }
            }
            
            console.log('✅ Button states initialized');
        }

        // Excluded pages management
        const excludedPages = <?php echo json_encode($excludedPages); ?>;

        function loadExcludedSidebar() {
            const excludedList = document.getElementById('excludedList');
            const excludedCount = document.getElementById('excludedCount');
            
            if (excludedPages.length === 0) {
                excludedList.innerHTML = '<div class="excluded-empty"><i class="fas fa-inbox"></i><p>No excluded pages</p></div>';
                excludedCount.textContent = '0';
                return;
            }
            
            let html = '';
            excludedPages.forEach((filename, index) => {
                html += `
                    <li class="excluded-item">
                        <span class="excluded-item-number">${index + 1}</span>
                        <span class="excluded-item-name">${filename}</span>
                        <button class="include-btn" onclick="togglePageVisibility('${filename}', false)">
                            <i class="fas fa-eye"></i> Include
                        </button>
                    </li>
                `;
            });
            
            excludedList.innerHTML = html;
            excludedCount.textContent = excludedPages.length;
        }

        async function togglePageVisibility(filename, exclude = null) {
            // Determine if excluding or including
            if (exclude === null) {
                exclude = !excludedPages.includes(filename);
            }
            
            try {
                const formData = new FormData();
                formData.append('action', 'toggle_visibility');
                formData.append('filename', filename);
                formData.append('exclude', exclude ? 'true' : 'false');
                
                const response = await fetch('index.php', {
                    method: 'POST',
                    body: formData
                });
                
                // Check if response is JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    console.error('❌ Non-JSON response:', text);
                    throw new Error('Server returned invalid response. Check console for details.');
                }
                
                const result = await response.json();
                
                if (result.success) {
                    showToast(result.message, 'success');
                    // Reload page to update
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(result.message || 'Operation failed', 'error');
                }
            } catch (error) {
                console.error('❌ Toggle visibility error:', error);
                showToast('Error: ' + error.message, 'error');
            }
        }

        // Hide excluded pages from catalog
        function hideExcludedPages() {
            const cards = document.querySelectorAll('.page-card');
            let visibleCount = 0;
            
            cards.forEach(card => {
                const filename = card.dataset.filepath;
                if (excludedPages.includes(filename)) {
                    card.style.display = 'none';
                } else {
                    visibleCount++;
                }
            });
            
            document.getElementById('visiblePages').textContent = visibleCount;
        }

        // ========================================
        // SORTING FUNCTIONALITY
        // ========================================
        
        let currentSortMode = 'date'; // 'date', 'a-z', 'z-a'
        
        function sortBy(mode) {
            currentSortMode = mode;
            
            // Update active button
            document.querySelectorAll('.sort-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            if (mode === 'date') {
                document.getElementById('sortDateBtn').classList.add('active');
            } else if (mode === 'a-z') {
                document.getElementById('sortAtoZBtn').classList.add('active');
            } else if (mode === 'z-a') {
                document.getElementById('sortZtoABtn').classList.add('active');
            }
            
            // Get all cards
            const grid = document.getElementById('catalogGrid');
            const cards = Array.from(grid.querySelectorAll('.page-card'));
            
            // Sort cards based on mode
            cards.sort((a, b) => {
                if (mode === 'date') {
                    // Sort by date (already sorted by PHP, but we'll preserve original order)
                    // We need to get the actual date from the cards
                    const dateA = getCardDate(a);
                    const dateB = getCardDate(b);
                    return dateB - dateA; // Newest first
                } else if (mode === 'a-z') {
                    // Sort alphabetically A-Z by filename
                    const nameA = a.dataset.filename.toLowerCase();
                    const nameB = b.dataset.filename.toLowerCase();
                    return nameA.localeCompare(nameB);
                } else if (mode === 'z-a') {
                    // Sort alphabetically Z-A by filename
                    const nameA = a.dataset.filename.toLowerCase();
                    const nameB = b.dataset.filename.toLowerCase();
                    return nameB.localeCompare(nameA);
                }
                return 0;
            });
            
            // Add fade-out animation
            cards.forEach(card => {
                card.style.opacity = '0';
                card.style.transform = 'scale(0.95)';
            });
            
            // Re-append cards in sorted order after short delay
            setTimeout(() => {
                cards.forEach((card, index) => {
                    grid.appendChild(card);
                    
                    // Stagger animation for each card
                    setTimeout(() => {
                        card.style.transition = 'all 0.4s ease';
                        card.style.opacity = '1';
                        card.style.transform = 'scale(1)';
                    }, index * 30); // 30ms delay between each card
                });
            }, 100);
            
            // Show toast notification
            const messages = {
                'date': '📅 Sorted by Date (Newest First)',
                'a-z': '🔤 Sorted Alphabetically (A → Z)',
                'z-a': '🔤 Sorted Alphabetically (Z → A)'
            };
            
            showToast(messages[mode], 'success');
            console.log('📊 Sorted by:', mode);
            
            // Save preference
            saveSortPreference(mode);
        }
        
        function getCardDate(card) {
            // Get modified timestamp from data attribute
            const modified = parseInt(card.dataset.modified || 0);
            return modified;
        }
        
        function resetSort() {
            currentSortMode = 'date';
            
            // Add rotation animation to reset button
            const resetBtn = document.querySelector('.reset-sort-btn');
            resetBtn.style.transform = 'rotate(360deg)';
            setTimeout(() => {
                resetBtn.style.transform = '';
            }, 600);
            
            // Clear saved preference
            localStorage.removeItem('catalogSortMode');
            
            sortBy('date');
            showToast('🔄 Sorting reset to default (Date)', 'success');
        }
        
        // Set initial active state and load saved sort preference
        function initializeSortControls() {
            // Load saved sort mode from localStorage
            const savedSortMode = localStorage.getItem('catalogSortMode');
            
            if (savedSortMode && ['date', 'a-z', 'z-a'].includes(savedSortMode)) {
                currentSortMode = savedSortMode;
                sortBy(savedSortMode);
            } else {
                document.getElementById('sortDateBtn').classList.add('active');
            }
        }
        
        // Save sort preference
        function saveSortPreference(mode) {
            localStorage.setItem('catalogSortMode', mode);
            console.log('💾 Sort preference saved:', mode);
        }

        // Initialize
        createParticles();
        initializeButtonStates();
        loadExcludedSidebar();
        hideExcludedPages();
        initializeSortControls();

    </script>
    
    <!-- Add Super Admin Modal -->
    <div id="addSuperAdminModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); backdrop-filter: blur(10px); z-index: 10000; align-items: center; justify-content: center;">
        <div style="background: linear-gradient(135deg, rgba(6,182,212,0.98) 0%, rgba(8,145,178,0.98) 100%); border-radius: 24px; padding: 40px; max-width: 550px; width: 90%; box-shadow: 0 25px 80px rgba(0,0,0,0.6); border: 2px solid rgba(255,255,255,0.3); animation: modalSlideIn 0.4s ease-out;">
            <!-- Header -->
            <div style="text-align: center; margin-bottom: 30px;">
                <div style="font-size: 64px; margin-bottom: 15px; animation: shieldRotate 3s infinite ease-in-out;">🛡️</div>
                <h3 style="color: #fff; font-size: 28px; margin: 0 0 10px 0; font-weight: 700;">Add Super Admin</h3>
                <p style="color: rgba(255,255,255,0.85); font-size: 15px; margin: 0;">Protect this page with password authentication</p>
            </div>
            
            <!-- Password Input -->
            <div style="margin-bottom: 25px;">
                <label style="display: block; color: #fff; font-weight: 600; margin-bottom: 10px; display: flex; align-items: center; gap: 8px; font-size: 14px;">
                    <span style="font-size: 18px;">🔑</span>
                    <span>Admin Password</span>
                </label>
                <div style="position: relative;">
                    <input type="text" id="superAdminPassword" placeholder="GL_Admin (default)" style="width: 100%; padding: 16px 50px 16px 18px; background: rgba(255,255,255,0.15); border: 2px solid rgba(255,255,255,0.3); border-radius: 12px; color: #fff; font-size: 16px; font-family: 'Consolas', monospace; transition: all 0.3s;">
                    <div style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); background: rgba(255,255,255,0.2); padding: 8px 12px; border-radius: 8px; font-size: 12px; color: #fff; font-weight: 600;">Default</div>
                </div>
                <div style="font-size: 12px; color: rgba(255,255,255,0.7); margin-top: 8px; padding: 8px 12px; background: rgba(0,0,0,0.2); border-radius: 6px; border-left: 3px solid rgba(251,191,36,0.6);">
                    💡 Leave empty to use default password: <code style="color: #fbbf24; font-weight: 600;">GL_Admin</code>
                </div>
            </div>
            
            <!-- File Name Display -->
            <div style="background: rgba(0,0,0,0.3); padding: 15px; border-radius: 12px; margin-bottom: 25px; border: 2px solid rgba(255,255,255,0.2);">
                <div style="font-size: 12px; color: rgba(255,255,255,0.7); margin-bottom: 6px; font-weight: 600;">TARGET FILE:</div>
                <div id="targetFileName" style="color: #fbbf24; font-size: 14px; font-weight: 700; font-family: 'Consolas', monospace;"></div>
            </div>
            
            <!-- Warning -->
            <div style="background: rgba(245,158,11,0.2); border: 2px solid rgba(245,158,11,0.5); border-radius: 12px; padding: 15px; margin-bottom: 25px;">
                <div style="display: flex; align-items: start; gap: 10px;">
                    <span style="font-size: 24px; flex-shrink: 0;">⚠️</span>
                    <div style="font-size: 13px; color: rgba(255,255,255,0.9); line-height: 1.6;">
                        <strong style="color: #fbbf24;">Warning:</strong> This will add authentication code to the beginning of the file. The page will require password to access.
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div style="display: flex; gap: 12px;">
                <button onclick="closeAddSuperAdminModal()" style="flex: 1; padding: 14px; background: rgba(239,68,68,0.3); border: 1px solid rgba(239,68,68,0.5); border-radius: 12px; color: #fff; cursor: pointer; font-weight: 700; font-size: 15px; transition: all 0.3s; border: none;" onmouseover="this.style.background='rgba(239,68,68,0.5)'" onmouseout="this.style.background='rgba(239,68,68,0.3)'">
                    ✕ Cancel
                </button>
                <button onclick="addSuperAdminToPage()" style="flex: 2; padding: 14px; background: linear-gradient(135deg, #22c55e 0%, #15803d 100%); border: none; border-radius: 12px; color: #fff; cursor: pointer; font-weight: 700; font-size: 16px; box-shadow: 0 4px 15px rgba(34,197,94,0.4); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(34,197,94,0.6)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(34,197,94,0.4)'">
                    🛡️ Add Protection
                </button>
            </div>
        </div>
    </div>
    
    <!-- Remove Super Admin Modal -->
    <div id="removeSuperAdminModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); backdrop-filter: blur(10px); z-index: 10000; align-items: center; justify-content: center;">
        <div style="background: linear-gradient(135deg, rgba(255,107,107,0.98) 0%, rgba(238,90,36,0.98) 100%); border-radius: 24px; padding: 40px; max-width: 550px; width: 90%; box-shadow: 0 25px 80px rgba(0,0,0,0.6); border: 2px solid rgba(255,255,255,0.3); animation: modalSlideIn 0.4s ease-out;">
            <!-- Header -->
            <div style="text-align: center; margin-bottom: 30px;">
                <div style="font-size: 64px; margin-bottom: 15px; animation: shieldShake 0.5s ease-in-out;">⚠️</div>
                <h3 style="color: #fff; font-size: 28px; margin: 0 0 10px 0; font-weight: 700;">Remove Super Admin</h3>
                <p style="color: rgba(255,255,255,0.85); font-size: 15px; margin: 0;">Remove password protection from this page</p>
            </div>
            
            <!-- File Name Display -->
            <div style="background: rgba(0,0,0,0.3); padding: 15px; border-radius: 12px; margin-bottom: 25px; border: 2px solid rgba(255,255,255,0.2);">
                <div style="font-size: 12px; color: rgba(255,255,255,0.7); margin-bottom: 6px; font-weight: 600;">TARGET FILE:</div>
                <div id="removeTargetFileName" style="color: #fbbf24; font-size: 14px; font-weight: 700; font-family: 'Consolas', monospace;"></div>
            </div>
            
            <!-- Warning -->
            <div style="background: rgba(239,68,68,0.2); border: 2px solid rgba(239,68,68,0.5); border-radius: 12px; padding: 15px; margin-bottom: 25px;">
                <div style="display: flex; align-items: start; gap: 10px;">
                    <span style="font-size: 24px; flex-shrink: 0;">🚨</span>
                    <div style="font-size: 13px; color: rgba(255,255,255,0.9); line-height: 1.6;">
                        <strong style="color: #fbbf24;">Warning:</strong> This will remove all authentication code from the file. The page will become publicly accessible without password.
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div style="display: flex; gap: 12px;">
                <button onclick="closeRemoveSuperAdminModal()" style="flex: 1; padding: 14px; background: rgba(107,114,128,0.3); border: 1px solid rgba(107,114,128,0.5); border-radius: 12px; color: #fff; cursor: pointer; font-weight: 700; font-size: 15px; transition: all 0.3s; border: none;" onmouseover="this.style.background='rgba(107,114,128,0.5)'" onmouseout="this.style.background='rgba(107,114,128,0.3)'">
                    ✕ Cancel
                </button>
                <button onclick="removeSuperAdminFromPage()" style="flex: 2; padding: 14px; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); border: none; border-radius: 12px; color: #fff; cursor: pointer; font-weight: 700; font-size: 16px; box-shadow: 0 4px 15px rgba(239,68,68,0.4); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(239,68,68,0.6)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(239,68,68,0.4)'">
                    🗑️ Remove Protection
                </button>
            </div>
        </div>
    </div>
    
    <!-- Bulk Super Admin Modal -->
    <div id="bulkAdminModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); backdrop-filter: blur(10px); z-index: 10000; align-items: center; justify-content: center;">
        <div style="background: linear-gradient(135deg, rgba(6,182,212,0.98) 0%, rgba(8,145,178,0.98) 100%); border-radius: 24px; padding: 40px; max-width: 600px; width: 90%; box-shadow: 0 25px 80px rgba(0,0,0,0.6); border: 2px solid rgba(255,255,255,0.3); animation: modalSlideIn 0.4s ease-out;">
            <!-- Header -->
            <div style="text-align: center; margin-bottom: 30px;">
                <div style="font-size: 64px; margin-bottom: 15px; animation: shieldRotate 3s infinite ease-in-out;">🛡️</div>
                <h3 style="color: #fff; font-size: 28px; margin: 0 0 10px 0; font-weight: 700;">Bulk Super Admin</h3>
                <p style="color: rgba(255,255,255,0.85); font-size: 15px; margin: 0;">Add or remove Super Admin protection for all pages</p>
            </div>
            
            <!-- Password Input -->
            <div style="margin-bottom: 25px;">
                <label style="display: block; color: #fff; font-weight: 600; margin-bottom: 10px; display: flex; align-items: center; gap: 8px; font-size: 14px;">
                    <span style="font-size: 18px;">🔑</span>
                    <span>Admin Password</span>
                </label>
                <div style="position: relative;">
                    <input type="text" id="bulkAdminPassword" placeholder="GL_Admin (default)" style="width: 100%; padding: 16px 50px 16px 18px; background: rgba(255,255,255,0.15); border: 2px solid rgba(255,255,255,0.3); border-radius: 12px; color: #fff; font-size: 16px; font-family: 'Consolas', monospace; transition: all 0.3s;">
                    <div style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); background: rgba(255,255,255,0.2); padding: 8px 12px; border-radius: 8px; font-size: 12px; color: #fff; font-weight: 600;">Default</div>
                </div>
                <div style="font-size: 12px; color: rgba(255,255,255,0.7); margin-top: 8px; padding: 8px 12px; background: rgba(0,0,0,0.2); border-radius: 6px; border-left: 3px solid rgba(251,191,36,0.6);">
                    💡 This password will be applied to all pages
                </div>
            </div>
            
            <!-- Info Box -->
            <div style="background: rgba(0,0,0,0.3); padding: 15px; border-radius: 12px; margin-bottom: 25px; border: 2px solid rgba(255,255,255,0.2);">
                <div style="font-size: 12px; color: rgba(255,255,255,0.7); margin-bottom: 6px; font-weight: 600;">TARGET:</div>
                <div style="color: #fbbf24; font-size: 14px; font-weight: 700;">All included catalog pages (excluding index.php)</div>
            </div>
            
            <!-- Warning -->
            <div style="background: rgba(245,158,11,0.2); border: 2px solid rgba(245,158,11,0.5); border-radius: 12px; padding: 15px; margin-bottom: 25px;">
                <div style="display: flex; align-items: start; gap: 10px;">
                    <span style="font-size: 24px; flex-shrink: 0;">⚠️</span>
                    <div style="font-size: 13px; color: rgba(255,255,255,0.9); line-height: 1.6;">
                        <strong style="color: #fbbf24;">Warning:</strong> This will affect multiple files. Pages that already have protection will be skipped when adding, and pages without protection will be skipped when removing.
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div style="display: flex; gap: 12px; margin-bottom: 15px;">
                <button onclick="closeBulkAdminModal()" style="flex: 1; padding: 14px; background: rgba(107,114,128,0.3); border: 1px solid rgba(107,114,128,0.5); border-radius: 12px; color: #fff; cursor: pointer; font-weight: 700; font-size: 15px; transition: all 0.3s; border: none;" onmouseover="this.style.background='rgba(107,114,128,0.5)'" onmouseout="this.style.background='rgba(107,114,128,0.3)'">
                    ✕ Cancel
                </button>
            </div>
            
            <div style="display: flex; gap: 12px;">
                <button onclick="executeBulkOperation('add')" style="flex: 1; padding: 14px; background: linear-gradient(135deg, #22c55e 0%, #15803d 100%); border: none; border-radius: 12px; color: #fff; cursor: pointer; font-weight: 700; font-size: 16px; box-shadow: 0 4px 15px rgba(34,197,94,0.4); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(34,197,94,0.6)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(34,197,94,0.4)'">
                    ➕ Add to All
                </button>
                <button onclick="executeBulkOperation('remove')" style="flex: 1; padding: 14px; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); border: none; border-radius: 12px; color: #fff; cursor: pointer; font-weight: 700; font-size: 16px; box-shadow: 0 4px 15px rgba(239,68,68,0.4); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(239,68,68,0.6)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(239,68,68,0.4)'">
                    🗑️ Remove from All
                </button>
            </div>
        </div>
    </div>
    
    <!-- Bulk Results Modal -->
    <div id="bulkResultsModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); backdrop-filter: blur(10px); z-index: 10001; align-items: center; justify-content: center;">
        <div id="bulkResultsContent" style="background: linear-gradient(135deg, rgba(34,197,94,0.98) 0%, rgba(21,128,61,0.98) 100%); border-radius: 24px; padding: 40px; max-width: 700px; width: 90%; max-height: 80vh; overflow-y: auto; box-shadow: 0 25px 80px rgba(0,0,0,0.6); border: 2px solid rgba(255,255,255,0.3); animation: modalSlideIn 0.4s ease-out;">
            <!-- Content will be dynamically generated -->
        </div>
    </div>
    
    <!-- Database Credentials Modal -->
    <div id="dbCredentialsModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); backdrop-filter: blur(10px); z-index: 10000; align-items: center; justify-content: center;">
        <div style="background: linear-gradient(135deg, rgba(102,126,234,0.98) 0%, rgba(118,75,162,0.98) 100%); border-radius: 24px; padding: 40px; max-width: 700px; width: 95%; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 80px rgba(0,0,0,0.6); border: 2px solid rgba(255,255,255,0.3); animation: modalSlideIn 0.4s ease-out;">
            <!-- Header -->
            <div style="text-align: center; margin-bottom: 25px;">
                <div style="font-size: 50px; margin-bottom: 10px;">🔑</div>
                <h3 style="color: #fff; font-size: 24px; margin: 0 0 8px 0; font-weight: 700;">Database Credentials</h3>
                <p id="dbCredentialsName" style="color: rgba(255,255,255,0.85); font-size: 14px; margin: 0;">Connection Details</p>
            </div>
            
            <!-- Tab Navigation -->
            <div style="display: flex; gap: 0; margin-bottom: 20px; border-radius: 14px; overflow: hidden; background: rgba(0,0,0,0.3); padding: 5px;">
                <button id="credTabRemote" onclick="switchCredentialsTab('remote')" style="flex: 1; padding: 14px 20px; background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%); border: none; color: white; font-size: 14px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; transition: all 0.3s; border-radius: 10px;">
                    <span style="font-size: 20px;">🌐</span>
                    <span>Remote Connection</span>
                </button>
                <button id="credTabLocalhost" onclick="switchCredentialsTab('localhost')" style="flex: 1; padding: 14px 20px; background: transparent; border: none; color: rgba(255,255,255,0.6); font-size: 14px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; transition: all 0.3s; border-radius: 10px;">
                    <span style="font-size: 20px;">🖥️</span>
                    <span>Localhost (On-Server)</span>
                </button>
            </div>
            
            <!-- Tab Content: Remote Connection -->
            <div id="credTabContentRemote" style="display: block;">
                <!-- Info Banner Remote -->
                <div style="background: linear-gradient(135deg, rgba(59,130,246,0.2) 0%, rgba(30,64,175,0.2) 100%); border: 2px solid #3b82f6; border-radius: 12px; padding: 15px; margin-bottom: 20px;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span style="font-size: 28px;">🌐</span>
                        <div>
                            <div style="color: #93c5fd; font-weight: 700; font-size: 14px;">Remote Connection Mode</div>
                            <div style="color: rgba(147,197,253,0.8); font-size: 12px; margin-top: 3px;">Use this when connecting from external servers, local development, or AI IDEs</div>
                        </div>
                    </div>
                </div>
                
                <!-- Copy All Button Remote -->
                <div style="text-align: center; margin-bottom: 18px;">
                    <button onclick="copyAllCredentials('remote')" style="padding: 12px 28px; background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); border: none; border-radius: 10px; color: #1e293b; font-size: 14px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 10px; box-shadow: 0 4px 15px rgba(251,191,36,0.4); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-2px) scale(1.03)'; this.style.boxShadow='0 6px 20px rgba(251,191,36,0.6)'" onmouseout="this.style.transform='translateY(0) scale(1)'; this.style.boxShadow='0 4px 15px rgba(251,191,36,0.4)'">
                        <span style="font-size: 18px;">📋</span>
                        <span>Copy Remote Credentials for AI</span>
                    </button>
                </div>
                
                <!-- Credentials List Remote -->
                <div id="dbCredentialsListRemote" style="margin-bottom: 20px;"></div>
                
                <!-- Connection Examples Remote -->
                <div style="background: rgba(0,0,0,0.3); padding: 18px; border-radius: 12px; border: 2px solid rgba(59,130,246,0.3);">
                    <div style="font-size: 13px; color: rgba(255,255,255,0.9); font-weight: 600; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 16px;">📝</span>
                        <span>Remote Connection Examples:</span>
                    </div>
                    <div style="margin-bottom: 12px;">
                        <div style="color: #93c5fd; font-size: 12px; font-weight: 600; margin-bottom: 5px;">PHP PDO:</div>
                        <div style="position: relative; background: rgba(0,0,0,0.4); padding: 10px; border-radius: 8px; font-family: 'Consolas', monospace; font-size: 11px; color: #e2e8f0; overflow-x: auto;">
                            <code id="pdoExampleRemote"></code>
                            <button onclick="copyCode('pdoExampleRemote')" style="position: absolute; top: 6px; right: 6px; background: rgba(59,130,246,0.3); border: 1px solid #3b82f6; color: #93c5fd; padding: 3px 8px; border-radius: 4px; font-size: 10px; cursor: pointer;">📋</button>
                        </div>
                    </div>
                    <div>
                        <div style="color: #93c5fd; font-size: 12px; font-weight: 600; margin-bottom: 5px;">MySQL CLI:</div>
                        <div style="position: relative; background: rgba(0,0,0,0.4); padding: 10px; border-radius: 8px; font-family: 'Consolas', monospace; font-size: 11px; color: #e2e8f0; overflow-x: auto;">
                            <code id="cliExampleRemote"></code>
                            <button onclick="copyCode('cliExampleRemote')" style="position: absolute; top: 6px; right: 6px; background: rgba(59,130,246,0.3); border: 1px solid #3b82f6; color: #93c5fd; padding: 3px 8px; border-radius: 4px; font-size: 10px; cursor: pointer;">📋</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tab Content: Localhost Connection -->
            <div id="credTabContentLocalhost" style="display: none;">
                <!-- Info Banner Localhost -->
                <div style="background: linear-gradient(135deg, rgba(34,197,94,0.2) 0%, rgba(22,163,74,0.2) 100%); border: 2px solid #22c55e; border-radius: 12px; padding: 15px; margin-bottom: 20px;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span style="font-size: 28px;">🖥️</span>
                        <div>
                            <div style="color: #86efac; font-weight: 700; font-size: 14px;">Localhost Connection Mode (On-Server)</div>
                            <div style="color: rgba(134,239,172,0.8); font-size: 12px; margin-top: 3px;">⚡ <strong>Faster!</strong> Use this when your code runs directly on Hostinger server</div>
                        </div>
                    </div>
                </div>
                
                <!-- Speed Notice -->
                <div style="background: rgba(245,158,11,0.15); border: 1px solid #f59e0b; border-radius: 10px; padding: 12px 15px; margin-bottom: 18px; display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 22px;">⚡</span>
                    <div style="font-size: 12px; color: #fbbf24; line-height: 1.5;">
                        <strong>Performance Tip:</strong> Using <code style="background: rgba(0,0,0,0.3); padding: 2px 6px; border-radius: 4px;">localhost</code> instead of the remote hostname is significantly faster when your PHP code runs on the same Hostinger server.
                    </div>
                </div>
                
                <!-- Copy All Button Localhost -->
                <div style="text-align: center; margin-bottom: 18px;">
                    <button onclick="copyAllCredentials('localhost')" style="padding: 12px 28px; background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); border: none; border-radius: 10px; color: white; font-size: 14px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 10px; box-shadow: 0 4px 15px rgba(34,197,94,0.4); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-2px) scale(1.03)'; this.style.boxShadow='0 6px 20px rgba(34,197,94,0.6)'" onmouseout="this.style.transform='translateY(0) scale(1)'; this.style.boxShadow='0 4px 15px rgba(34,197,94,0.4)'">
                        <span style="font-size: 18px;">📋</span>
                        <span>Copy Localhost Credentials for AI</span>
                    </button>
                </div>
                
                <!-- Credentials List Localhost -->
                <div id="dbCredentialsListLocalhost" style="margin-bottom: 20px;"></div>
                
                <!-- Connection Examples Localhost -->
                <div style="background: rgba(0,0,0,0.3); padding: 18px; border-radius: 12px; border: 2px solid rgba(34,197,94,0.3);">
                    <div style="font-size: 13px; color: rgba(255,255,255,0.9); font-weight: 600; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 16px;">📝</span>
                        <span>Localhost Connection Examples:</span>
                    </div>
                    <div style="margin-bottom: 12px;">
                        <div style="color: #86efac; font-size: 12px; font-weight: 600; margin-bottom: 5px;">PHP PDO:</div>
                        <div style="position: relative; background: rgba(0,0,0,0.4); padding: 10px; border-radius: 8px; font-family: 'Consolas', monospace; font-size: 11px; color: #e2e8f0; overflow-x: auto;">
                            <code id="pdoExampleLocalhost"></code>
                            <button onclick="copyCode('pdoExampleLocalhost')" style="position: absolute; top: 6px; right: 6px; background: rgba(34,197,94,0.3); border: 1px solid #22c55e; color: #86efac; padding: 3px 8px; border-radius: 4px; font-size: 10px; cursor: pointer;">📋</button>
                        </div>
                    </div>
                    <div>
                        <div style="color: #86efac; font-size: 12px; font-weight: 600; margin-bottom: 5px;">MySQL CLI:</div>
                        <div style="position: relative; background: rgba(0,0,0,0.4); padding: 10px; border-radius: 8px; font-family: 'Consolas', monospace; font-size: 11px; color: #e2e8f0; overflow-x: auto;">
                            <code id="cliExampleLocalhost"></code>
                            <button onclick="copyCode('cliExampleLocalhost')" style="position: absolute; top: 6px; right: 6px; background: rgba(34,197,94,0.3); border: 1px solid #22c55e; color: #86efac; padding: 3px 8px; border-radius: 4px; font-size: 10px; cursor: pointer;">📋</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Close Button -->
            <div style="text-align: center; margin-top: 25px;">
                <button onclick="closeDatabaseCredentials()" style="padding: 12px 30px; background: white; color: #667eea; border: none; border-radius: 10px; font-size: 15px; font-weight: bold; cursor: pointer; box-shadow: 0 4px 15px rgba(0,0,0,0.2); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                    ✕ Close
                </button>
            </div>
        </div>
    </div>
    
    <style>
        @keyframes modalSlideIn {
            from { opacity: 0; transform: translateY(-50px) scale(0.9); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        
        @keyframes shieldRotate {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(-10deg); }
            75% { transform: rotate(10deg); }
        }
        
        @keyframes shieldShake {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(-15deg); }
            75% { transform: rotate(15deg); }
        }
        
        @keyframes progressPulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }
        
        #superAdminPassword:focus {
            outline: none;
            border-color: #fbbf24;
            background: rgba(255,255,255,0.2);
            box-shadow: 0 0 20px rgba(251,191,36,0.4);
        }
        
        #bulkAdminPassword:focus {
            outline: none;
            border-color: #fbbf24;
            background: rgba(255,255,255,0.2);
            box-shadow: 0 0 20px rgba(251,191,36,0.4);
        }
        
        /* Database Connection Select Styling */
        #dbConnectionSelect {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%231e293b' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 40px;
        }
        
        #dbConnectionSelect option {
            background: white;
            color: #1e293b;
            padding: 10px;
            font-weight: 600;
        }
        
        #dbConnectionSelect option:checked {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
    </style>
    
    <script>
        let currentSuperAdminFile = '';
        let currentRemoveSuperAdminFile = '';
        
        function showAddSuperAdminModal(filename) {
            currentSuperAdminFile = filename;
            document.getElementById('targetFileName').textContent = filename;
            document.getElementById('superAdminPassword').value = '';
            document.getElementById('addSuperAdminModal').style.display = 'flex';
        }
        
        function closeAddSuperAdminModal() {
            document.getElementById('addSuperAdminModal').style.display = 'none';
            currentSuperAdminFile = '';
        }
        
        async function addSuperAdminToPage() {
            if (!currentSuperAdminFile) {
                alert('No file selected!');
                return;
            }
            
            const password = document.getElementById('superAdminPassword').value.trim() || 'GL_Admin';
            
            // Show loading
            const modal = document.getElementById('addSuperAdminModal');
            const originalContent = modal.innerHTML;
            modal.innerHTML = `
                <div style="background: linear-gradient(135deg, rgba(6,182,212,0.98) 0%, rgba(8,145,178,0.98) 100%); border-radius: 24px; padding: 60px; text-align: center;">
                    <div style="font-size: 80px; margin-bottom: 20px; animation: spin 2s linear infinite;">⚙️</div>
                    <div style="font-size: 24px; color: white; font-weight: bold;">Adding Super Admin...</div>
                    <div style="font-size: 14px; color: rgba(255,255,255,0.8); margin-top: 10px;">Please wait</div>
                </div>
                <style>
                    @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
                </style>
            `;
            
            try {
                const formData = new FormData();
                formData.append('action', 'add_super_admin');
                formData.append('filename', currentSuperAdminFile);
                formData.append('password', password);
                
                const response = await fetch('index.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Show success
                    modal.innerHTML = `
                        <div style="background: linear-gradient(135deg, rgba(34,197,94,0.98) 0%, rgba(21,128,61,0.98) 100%); border-radius: 24px; padding: 50px; text-align: center;">
                            <div style="font-size: 80px; margin-bottom: 20px; animation: successPop 0.5s ease-out;">✅</div>
                            <div style="font-size: 28px; color: white; font-weight: bold; margin-bottom: 15px;">Protected!</div>
                            <div style="font-size: 16px; color: rgba(255,255,255,0.9); margin-bottom: 25px;">${result.message}</div>
                            <div style="background: rgba(0,0,0,0.3); padding: 18px; border-radius: 12px; margin-bottom: 25px;">
                                <div style="font-size: 13px; color: rgba(255,255,255,0.7); margin-bottom: 8px;">File Protected:</div>
                                <div style="color: #fbbf24; font-size: 15px; font-weight: 700; font-family: 'Consolas', monospace; margin-bottom: 15px;">${currentSuperAdminFile}</div>
                                <div style="font-size: 13px; color: rgba(255,255,255,0.7); margin-bottom: 8px;">Password:</div>
                                <div style="color: #86efac; font-size: 18px; font-weight: 700; font-family: 'Consolas', monospace;">${result.password}</div>
                            </div>
                            <div style="background: rgba(59,130,246,0.2); padding: 12px; border-radius: 10px; margin-bottom: 25px; border: 2px solid rgba(59,130,246,0.4);">
                                <div style="font-size: 13px; color: rgba(255,255,255,0.9);">
                                    💡 The page now requires this password to access!
                                </div>
                            </div>
                            <button onclick="location.reload()" style="padding: 14px 35px; background: white; color: #22c55e; border: none; border-radius: 12px; font-size: 16px; font-weight: bold; cursor: pointer; box-shadow: 0 4px 15px rgba(0,0,0,0.2); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                                🎉 Close & Refresh
                            </button>
                        </div>
                        <style>
                            @keyframes successPop {
                                0% { transform: scale(0); }
                                50% { transform: scale(1.2); }
                                100% { transform: scale(1); }
                            }
                        </style>
                    `;
                } else {
                    // Show error
                    modal.innerHTML = `
                        <div style="background: linear-gradient(135deg, rgba(239,68,68,0.98) 0%, rgba(220,38,38,0.98) 100%); border-radius: 24px; padding: 50px; text-align: center;">
                            <div style="font-size: 80px; margin-bottom: 20px; animation: errorShake 0.5s ease-out;">❌</div>
                            <div style="font-size: 28px; color: white; font-weight: bold; margin-bottom: 15px;">Error!</div>
                            <div style="font-size: 16px; color: rgba(255,255,255,0.9); margin-bottom: 25px;">${result.message}</div>
                            <button onclick="closeAddSuperAdminModal(); showAddSuperAdminModal('${currentSuperAdminFile}')" style="padding: 14px 35px; background: white; color: #ef4444; border: none; border-radius: 12px; font-size: 16px; font-weight: bold; cursor: pointer; margin-right: 10px;">
                                ↩️ Try Again
                            </button>
                            <button onclick="closeAddSuperAdminModal()" style="padding: 14px 35px; background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px; font-size: 16px; font-weight: bold; cursor: pointer;">
                                ✕ Close
                            </button>
                        </div>
                        <style>
                            @keyframes errorShake {
                                0%, 100% { transform: translateX(0); }
                                25% { transform: translateX(-15px); }
                                75% { transform: translateX(15px); }
                            }
                        </style>
                    `;
                }
            } catch (error) {
                modal.innerHTML = originalContent;
                alert('Error: ' + error.message);
                closeAddSuperAdminModal();
            }
        }
        
        // Remove Super Admin Modal Functions
        function showRemoveSuperAdminModal(filename) {
            currentRemoveSuperAdminFile = filename;
            document.getElementById('removeTargetFileName').textContent = filename;
            document.getElementById('removeSuperAdminModal').style.display = 'flex';
        }
        
        function closeRemoveSuperAdminModal() {
            document.getElementById('removeSuperAdminModal').style.display = 'none';
            currentRemoveSuperAdminFile = '';
        }
        
        async function removeSuperAdminFromPage() {
            if (!currentRemoveSuperAdminFile) {
                alert('No file selected!');
                return;
            }
            
            // Show loading
            const modal = document.getElementById('removeSuperAdminModal');
            const originalContent = modal.innerHTML;
            modal.innerHTML = `
                <div style="background: linear-gradient(135deg, rgba(255,107,107,0.98) 0%, rgba(238,90,36,0.98) 100%); border-radius: 24px; padding: 60px; text-align: center;">
                    <div style="font-size: 80px; margin-bottom: 20px; animation: spin 2s linear infinite;">⚙️</div>
                    <div style="font-size: 24px; color: white; font-weight: bold;">Removing Protection...</div>
                    <div style="font-size: 14px; color: rgba(255,255,255,0.8); margin-top: 10px;">Please wait</div>
                </div>
                <style>
                    @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
                </style>
            `;
            
            try {
                const formData = new FormData();
                formData.append('action', 'remove_super_admin');
                formData.append('filename', currentRemoveSuperAdminFile);
                
                const response = await fetch('index.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Show success
                    modal.innerHTML = `
                        <div style="background: linear-gradient(135deg, rgba(34,197,94,0.98) 0%, rgba(21,128,61,0.98) 100%); border-radius: 24px; padding: 50px; text-align: center;">
                            <div style="font-size: 80px; margin-bottom: 20px; animation: successPop 0.5s ease-out;">✅</div>
                            <div style="font-size: 28px; color: white; font-weight: bold; margin-bottom: 15px;">Removed!</div>
                            <div style="font-size: 16px; color: rgba(255,255,255,0.9); margin-bottom: 25px;">${result.message}</div>
                            <div style="background: rgba(0,0,0,0.3); padding: 18px; border-radius: 12px; margin-bottom: 25px;">
                                <div style="font-size: 13px; color: rgba(255,255,255,0.7); margin-bottom: 8px;">File Unprotected:</div>
                                <div style="color: #fbbf24; font-size: 15px; font-weight: 700; font-family: 'Consolas', monospace;">${currentRemoveSuperAdminFile}</div>
                            </div>
                            <div style="background: rgba(59,130,246,0.2); padding: 12px; border-radius: 10px; margin-bottom: 25px; border: 2px solid rgba(59,130,246,0.4);">
                                <div style="font-size: 13px; color: rgba(255,255,255,0.9);">
                                    🔓 The page is now publicly accessible!
                                </div>
                            </div>
                            <button onclick="location.reload()" style="padding: 14px 35px; background: white; color: #22c55e; border: none; border-radius: 12px; font-size: 16px; font-weight: bold; cursor: pointer; box-shadow: 0 4px 15px rgba(0,0,0,0.2); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                                🎉 Close & Refresh
                            </button>
                        </div>
                        <style>
                            @keyframes successPop {
                                0% { transform: scale(0); }
                                50% { transform: scale(1.2); }
                                100% { transform: scale(1); }
                            }
                        </style>
                    `;
                } else {
                    // Show error
                    modal.innerHTML = `
                        <div style="background: linear-gradient(135deg, rgba(239,68,68,0.98) 0%, rgba(220,38,38,0.98) 100%); border-radius: 24px; padding: 50px; text-align: center;">
                            <div style="font-size: 80px; margin-bottom: 20px; animation: errorShake 0.5s ease-out;">❌</div>
                            <div style="font-size: 28px; color: white; font-weight: bold; margin-bottom: 15px;">Error!</div>
                            <div style="font-size: 16px; color: rgba(255,255,255,0.9); margin-bottom: 25px;">${result.message}</div>
                            <button onclick="closeRemoveSuperAdminModal(); showRemoveSuperAdminModal('${currentRemoveSuperAdminFile}')" style="padding: 14px 35px; background: white; color: #ef4444; border: none; border-radius: 12px; font-size: 16px; font-weight: bold; cursor: pointer; margin-right: 10px;">
                                ↩️ Try Again
                            </button>
                            <button onclick="closeRemoveSuperAdminModal()" style="padding: 14px 35px; background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px; font-size: 16px; font-weight: bold; cursor: pointer;">
                                ✕ Close
                            </button>
                        </div>
                        <style>
                            @keyframes errorShake {
                                0%, 100% { transform: translateX(0); }
                                25% { transform: translateX(-15px); }
                                75% { transform: translateX(15px); }
                            }
                        </style>
                    `;
                }
            } catch (error) {
                modal.innerHTML = originalContent;
                alert('Error: ' + error.message);
                closeRemoveSuperAdminModal();
            }
        }
        
        // Check if file has Super Admin
        async function checkSuperAdminStatus(filename) {
            try {
                const formData = new FormData();
                formData.append('action', 'check_super_admin');
                formData.append('filename', filename);
                
                const response = await fetch('index.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                return result.exists;
            } catch (error) {
                console.error('Error checking super admin status:', error);
                return false;
            }
        }
        
        // Update button UI based on admin status
        function updateAdminButton(filename, hasAdmin) {
            const btn = document.getElementById('admin-btn-' + filename);
            if (!btn) return;
            
            const textSpan = btn.querySelector('.admin-btn-text');
            
            btn.classList.remove('checking');
            
            if (hasAdmin) {
                btn.classList.add('has-admin');
                textSpan.textContent = 'Remove Admin';
            } else {
                btn.classList.remove('has-admin');
                textSpan.textContent = 'Add Admin';
            }
        }
        
        // Toggle Admin Password (Add or Remove)
        async function toggleAdminPassword(filename) {
            const btn = document.getElementById('admin-btn-' + filename);
            if (!btn || btn.classList.contains('checking')) return;
            
            btn.classList.add('checking');
            const textSpan = btn.querySelector('.admin-btn-text');
            textSpan.textContent = 'Checking...';
            
            const hasAdmin = await checkSuperAdminStatus(filename);
            
            btn.classList.remove('checking');
            
            if (hasAdmin) {
                showRemoveSuperAdminModal(filename);
            } else {
                showAddSuperAdminModal(filename);
            }
            
            updateAdminButton(filename, hasAdmin);
        }
        
        // ========================================
        // BULK SUPER ADMIN OPERATIONS
        // ========================================
        
        function showBulkAdminModal() {
            document.getElementById('bulkAdminPassword').value = '';
            document.getElementById('bulkAdminModal').style.display = 'flex';
        }
        
        function closeBulkAdminModal() {
            document.getElementById('bulkAdminModal').style.display = 'none';
        }
        
        function closeBulkResultsModal() {
            document.getElementById('bulkResultsModal').style.display = 'none';
            location.reload(); // Refresh to update button states
        }
        
        async function executeBulkOperation(operation) {
            const password = document.getElementById('bulkAdminPassword').value.trim() || 'GL_Admin';
            
            // Close input modal
            closeBulkAdminModal();
            
            // Show loading modal
            const resultsModal = document.getElementById('bulkResultsModal');
            const resultsContent = document.getElementById('bulkResultsContent');
            
            const operationText = operation === 'add' ? 'Adding' : 'Removing';
            const bgColor = operation === 'add' ? 'rgba(6,182,212,0.98)' : 'rgba(255,107,107,0.98)';
            
            resultsContent.style.background = `linear-gradient(135deg, ${bgColor} 0%, ${bgColor} 100%)`;
            resultsContent.innerHTML = `
                <div style="text-align: center;">
                    <div style="font-size: 80px; margin-bottom: 20px; animation: spin 2s linear infinite;">⚙️</div>
                    <div style="font-size: 24px; color: white; font-weight: bold; margin-bottom: 10px;">${operationText} Super Admin...</div>
                    <div style="font-size: 14px; color: rgba(255,255,255,0.8);">Please wait while processing all files</div>
                    <div style="margin-top: 20px; background: rgba(0,0,0,0.3); height: 6px; border-radius: 10px; overflow: hidden;">
                        <div style="height: 100%; background: white; width: 0%; animation: progressPulse 1.5s ease-in-out infinite;"></div>
                    </div>
                </div>
                <style>
                    @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
                </style>
            `;
            resultsModal.style.display = 'flex';
            
            try {
                const formData = new FormData();
                formData.append('action', 'bulk_super_admin');
                formData.append('operation', operation);
                formData.append('password', password);
                
                const response = await fetch('index.php', {
                    method: 'POST',
                    body: formData
                });
                
                // Check if response is JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    console.error('❌ Non-JSON response:', text);
                    throw new Error('Server returned invalid response');
                }
                
                const result = await response.json();
                
                if (result.success) {
                    showBulkResults(result.operation, result.results);
                } else {
                    showBulkError(result.message);
                }
            } catch (error) {
                console.error('❌ Bulk operation error:', error);
                showBulkError(error.message);
            }
        }
        
        function showBulkResults(operation, results) {
            const resultsContent = document.getElementById('bulkResultsContent');
            const operationText = operation === 'add' ? 'Added' : 'Removed';
            const emoji = operation === 'add' ? '✅' : '🗑️';
            const bgColor = operation === 'add' 
                ? 'linear-gradient(135deg, rgba(34,197,94,0.98) 0%, rgba(21,128,61,0.98) 100%)'
                : 'linear-gradient(135deg, rgba(239,68,68,0.98) 0%, rgba(220,38,38,0.98) 100%)';
            
            resultsContent.style.background = bgColor;
            
            let detailsHtml = '';
            
            if (results.details && results.details.length > 0) {
                detailsHtml = '<div style="max-height: 300px; overflow-y: auto; margin-top: 20px;">';
                
                // Group by status
                const successFiles = results.details.filter(d => d.status === 'success');
                const skippedFiles = results.details.filter(d => d.status === 'skipped');
                const failedFiles = results.details.filter(d => d.status === 'failed');
                
                if (successFiles.length > 0) {
                    detailsHtml += '<div style="background: rgba(0,0,0,0.2); padding: 12px; border-radius: 8px; margin-bottom: 10px;">';
                    detailsHtml += '<div style="color: #86efac; font-weight: bold; margin-bottom: 8px;">✅ Processed (' + successFiles.length + '):</div>';
                    successFiles.forEach(file => {
                        detailsHtml += '<div style="color: rgba(255,255,255,0.9); font-size: 13px; padding: 4px; font-family: Consolas, monospace;">• ' + file.file + '</div>';
                    });
                    detailsHtml += '</div>';
                }
                
                if (skippedFiles.length > 0) {
                    detailsHtml += '<div style="background: rgba(0,0,0,0.2); padding: 12px; border-radius: 8px; margin-bottom: 10px;">';
                    detailsHtml += '<div style="color: #fbbf24; font-weight: bold; margin-bottom: 8px;">⏭️ Skipped (' + skippedFiles.length + '):</div>';
                    skippedFiles.forEach(file => {
                        detailsHtml += '<div style="color: rgba(255,255,255,0.8); font-size: 12px; padding: 4px; font-family: Consolas, monospace;">• ' + file.file + ' - ' + file.reason + '</div>';
                    });
                    detailsHtml += '</div>';
                }
                
                if (failedFiles.length > 0) {
                    detailsHtml += '<div style="background: rgba(0,0,0,0.2); padding: 12px; border-radius: 8px;">';
                    detailsHtml += '<div style="color: #fca5a5; font-weight: bold; margin-bottom: 8px;">❌ Failed (' + failedFiles.length + '):</div>';
                    failedFiles.forEach(file => {
                        detailsHtml += '<div style="color: rgba(255,255,255,0.8); font-size: 12px; padding: 4px; font-family: Consolas, monospace;">• ' + file.file + ' - ' + file.reason + '</div>';
                    });
                    detailsHtml += '</div>';
                }
                
                detailsHtml += '</div>';
            }
            
            resultsContent.innerHTML = `
                <div style="text-align: center;">
                    <div style="font-size: 80px; margin-bottom: 20px; animation: successPop 0.5s ease-out;">${emoji}</div>
                    <div style="font-size: 28px; color: white; font-weight: bold; margin-bottom: 15px;">Operation Complete!</div>
                    <div style="font-size: 16px; color: rgba(255,255,255,0.9); margin-bottom: 25px;">Super Admin ${operationText}</div>
                    
                    <div style="background: rgba(0,0,0,0.3); padding: 20px; border-radius: 12px; margin-bottom: 20px;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 15px; text-align: center;">
                            <div>
                                <div style="font-size: 24px; color: #fbbf24; font-weight: bold;">${results.total}</div>
                                <div style="font-size: 12px; color: rgba(255,255,255,0.7);">Total</div>
                            </div>
                            <div>
                                <div style="font-size: 24px; color: #86efac; font-weight: bold;">${results.processed}</div>
                                <div style="font-size: 12px; color: rgba(255,255,255,0.7);">Processed</div>
                            </div>
                            <div>
                                <div style="font-size: 24px; color: #fbbf24; font-weight: bold;">${results.skipped}</div>
                                <div style="font-size: 12px; color: rgba(255,255,255,0.7);">Skipped</div>
                            </div>
                            <div>
                                <div style="font-size: 24px; color: #fca5a5; font-weight: bold;">${results.failed}</div>
                                <div style="font-size: 12px; color: rgba(255,255,255,0.7);">Failed</div>
                            </div>
                        </div>
                    </div>
                    
                    ${detailsHtml}
                    
                    <button onclick="closeBulkResultsModal()" style="padding: 14px 35px; background: white; color: #22c55e; border: none; border-radius: 12px; font-size: 16px; font-weight: bold; cursor: pointer; box-shadow: 0 4px 15px rgba(0,0,0,0.2); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                        🎉 Close & Refresh
                    </button>
                </div>
                <style>
                    @keyframes successPop {
                        0% { transform: scale(0); }
                        50% { transform: scale(1.2); }
                        100% { transform: scale(1); }
                    }
                </style>
            `;
        }
        
        function showBulkError(message) {
            const resultsContent = document.getElementById('bulkResultsContent');
            resultsContent.style.background = 'linear-gradient(135deg, rgba(239,68,68,0.98) 0%, rgba(220,38,38,0.98) 100%)';
            resultsContent.innerHTML = `
                <div style="text-align: center;">
                    <div style="font-size: 80px; margin-bottom: 20px; animation: errorShake 0.5s ease-out;">❌</div>
                    <div style="font-size: 28px; color: white; font-weight: bold; margin-bottom: 15px;">Error!</div>
                    <div style="font-size: 16px; color: rgba(255,255,255,0.9); margin-bottom: 25px;">${message}</div>
                    <button onclick="closeBulkResultsModal()" style="padding: 14px 35px; background: white; color: #ef4444; border: none; border-radius: 12px; font-size: 16px; font-weight: bold; cursor: pointer;">
                        ✕ Close
                    </button>
                </div>
                <style>
                    @keyframes errorShake {
                        0%, 100% { transform: translateX(0); }
                        25% { transform: translateX(-15px); }
                        75% { transform: translateX(15px); }
                    }
                </style>
            `;
        }
        
        // ========================================
        // DATABASE CONNECTIONS MANAGEMENT
        // ========================================
        // Connections are now fetched from report_prompt_databases table
        
        let activeConnections = [];
        let selectedConnection = null;
        
        async function loadDatabaseConnections() {
            try {
                const formData = new FormData();
                formData.append('action', 'get_active_connections');
                
                const response = await fetch('index.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success && result.connections) {
                    activeConnections = result.connections;
                    
                    const select = document.getElementById('dbConnectionSelect');
                    const connStatusText = document.getElementById('connStatusText');
                    const connCount = document.getElementById('connCount');
                    
                    if (activeConnections.length === 0) {
                        select.innerHTML = '<option value="" style="color: #ef4444;">❌ No connections available</option>';
                        connStatusText.textContent = 'Not connected';
                        connCount.textContent = '0';
                        document.getElementById('showCredentialsBtn').disabled = true;
                    } else {
                        let options = '<option value="" style="color: #64748b;">Select a database...</option>';
                        
                        // Group connections by source
                        const hubConns = activeConnections.filter(c => c.source === 'hub');
                        const localConns = activeConnections.filter(c => c.source === 'localhost');
                        
                        if (hubConns.length > 0) {
                            options += '<optgroup label="📦 Hub Databases">';
                            hubConns.forEach(conn => {
                                options += `<option value="${conn.id}" style="color: #1e293b; font-weight: 600;">${conn.name}</option>`;
                            });
                            options += '</optgroup>';
                        }
                        
                        if (localConns.length > 0) {
                            options += '<optgroup label="🖥️ Localhost Databases">';
                            localConns.forEach(conn => {
                                options += `<option value="${conn.id}" style="color: #1e293b; font-weight: 600;">${conn.name}</option>`;
                            });
                            options += '</optgroup>';
                        }
                        
                        select.innerHTML = options;
                        connStatusText.textContent = 'Connected';
                        connCount.textContent = activeConnections.length;
                        
                        // Enable credentials button when a connection is selected
                        select.addEventListener('change', function() {
                            selectedConnection = activeConnections.find(c => c.id === this.value);
                            document.getElementById('showCredentialsBtn').disabled = !selectedConnection;
                        });
                    }
                } else {
                    document.getElementById('dbConnectionSelect').innerHTML = '<option value="" style="color: #ef4444;">❌ Failed to load connections</option>';
                    document.getElementById('connStatusText').textContent = 'Connection failed';
                    document.getElementById('connCount').textContent = '0';
                }
            } catch (error) {
                console.error('Error loading connections:', error);
                document.getElementById('dbConnectionSelect').innerHTML = '<option value="" style="color: #ef4444;">❌ Error loading connections</option>';
                document.getElementById('connStatusText').textContent = 'Error';
                document.getElementById('connCount').textContent = '0';
            }
        }
        
        function refreshDatabaseConnections() {
            document.getElementById('connStatusText').textContent = 'Refreshing...';
            loadDatabaseConnections();
        }
        
        // Switch between Remote and Localhost tabs
        function switchCredentialsTab(tab) {
            const remoteTab = document.getElementById('credTabRemote');
            const localhostTab = document.getElementById('credTabLocalhost');
            const remoteContent = document.getElementById('credTabContentRemote');
            const localhostContent = document.getElementById('credTabContentLocalhost');
            
            if (tab === 'remote') {
                remoteTab.style.background = 'linear-gradient(135deg, #3b82f6 0%, #1e40af 100%)';
                remoteTab.style.color = 'white';
                localhostTab.style.background = 'transparent';
                localhostTab.style.color = 'rgba(255,255,255,0.6)';
                remoteContent.style.display = 'block';
                localhostContent.style.display = 'none';
            } else {
                localhostTab.style.background = 'linear-gradient(135deg, #22c55e 0%, #16a34a 100%)';
                localhostTab.style.color = 'white';
                remoteTab.style.background = 'transparent';
                remoteTab.style.color = 'rgba(255,255,255,0.6)';
                remoteContent.style.display = 'none';
                localhostContent.style.display = 'block';
            }
        }
        
        function showDatabaseCredentials() {
            if (!selectedConnection) {
                alert('Please select a database connection first');
                return;
            }
            
            // Set modal title
            document.getElementById('dbCredentialsName').textContent = selectedConnection.name;
            
            // Build credentials for REMOTE connection
            buildCredentialsList('Remote', selectedConnection.host);
            
            // Build credentials for LOCALHOST connection
            buildCredentialsList('Localhost', 'localhost');
            
            // Generate connection examples for REMOTE
            generateConnectionExamples('Remote', selectedConnection.host);
            
            // Generate connection examples for LOCALHOST
            generateConnectionExamples('Localhost', 'localhost');
            
            // Reset to Remote tab
            switchCredentialsTab('remote');
            
            // Show modal
            document.getElementById('dbCredentialsModal').style.display = 'flex';
        }
        
        function buildCredentialsList(mode, host) {
            const suffix = mode;
            const borderColor = mode === 'Remote' ? 'rgba(59,130,246,0.3)' : 'rgba(34,197,94,0.3)';
            const highlightColor = mode === 'Remote' ? '#93c5fd' : '#86efac';
            
            const credentials = [
                { label: '🏠 Host', value: host, key: 'host_' + mode, highlight: mode === 'Localhost' },
                { label: '🗄️ Database Name', value: selectedConnection.dbName, key: 'dbName_' + mode },
                { label: '👤 Username', value: selectedConnection.username, key: 'username_' + mode },
                { label: '🔑 Password', value: selectedConnection.password || '(empty)', key: 'password_' + mode, isPassword: true },
                { label: '🔌 Port', value: selectedConnection.port, key: 'port_' + mode }
            ];
            
            let credHtml = '';
            credentials.forEach(cred => {
                const bgStyle = cred.highlight 
                    ? `background: linear-gradient(135deg, rgba(34,197,94,0.2) 0%, rgba(22,163,74,0.15) 100%); border: 2px solid #22c55e;`
                    : `background: rgba(255,255,255,0.08); border: 1px solid ${borderColor};`;
                const valueStyle = cred.highlight 
                    ? `color: #86efac; font-size: 16px;` 
                    : `color: #fff; font-size: 14px;`;
                    
                credHtml += `
                    <div style="${bgStyle} padding: 12px 15px; border-radius: 10px; margin-bottom: 10px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; gap: 10px;">
                            <div style="flex: 1;">
                                <div style="color: rgba(255,255,255,0.7); font-size: 11px; margin-bottom: 3px;">${cred.label}${cred.highlight ? ' <span style="color: #22c55e; font-weight: 700;">(LOCAL)</span>' : ''}</div>
                                <div style="${valueStyle} font-weight: 600; font-family: 'Consolas', monospace; word-break: break-all;">
                                    ${cred.isPassword && cred.value !== '(empty)' ? '<span id="pwd_' + cred.key + '">••••••••</span><span id="pwd_' + cred.key + '_text" style="display:none;">' + cred.value + '</span>' : cred.value}
                                </div>
                            </div>
                            <div style="display: flex; gap: 5px;">
                                ${cred.isPassword && cred.value !== '(empty)' ? '<button onclick="togglePasswordVisibility(\'' + cred.key + '\')" style="background: rgba(255,255,255,0.15); border: none; color: white; padding: 6px 10px; border-radius: 6px; font-size: 14px; cursor: pointer;">👁️</button>' : ''}
                                <button onclick="copyToClipboard(\'' + cred.value.replace(/'/g, "\\'") + '\', \'' + cred.label + ' copied!\')" style="background: rgba(255,255,255,0.15); border: none; color: white; padding: 6px 10px; border-radius: 6px; font-size: 12px; cursor: pointer;">📋</button>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            document.getElementById('dbCredentialsList' + suffix).innerHTML = credHtml;
        }
        
        function generateConnectionExamples(mode, host) {
            const pdoCode = `$pdo = new PDO(
    "mysql:host=${host};port=${selectedConnection.port};dbname=${selectedConnection.dbName}",
    "${selectedConnection.username}",
    "${selectedConnection.password || ''}"
);`;
            
            const cliCode = selectedConnection.password 
                ? `mysql -h ${host} -P ${selectedConnection.port} -u ${selectedConnection.username} -p${selectedConnection.password} ${selectedConnection.dbName}`
                : `mysql -h ${host} -P ${selectedConnection.port} -u ${selectedConnection.username} ${selectedConnection.dbName}`;
            
            document.getElementById('pdoExample' + mode).textContent = pdoCode;
            document.getElementById('cliExample' + mode).textContent = cliCode;
        }
        
        function closeDatabaseCredentials() {
            document.getElementById('dbCredentialsModal').style.display = 'none';
        }
        
        function togglePasswordVisibility(key) {
            const hiddenSpan = document.getElementById('pwd_' + key);
            const textSpan = document.getElementById('pwd_' + key + '_text');
            
            if (hiddenSpan.style.display === 'none') {
                hiddenSpan.style.display = 'inline';
                textSpan.style.display = 'none';
            } else {
                hiddenSpan.style.display = 'none';
                textSpan.style.display = 'inline';
            }
        }
        
        function copyAllCredentials(mode = 'remote') {
            if (!selectedConnection) {
                alert('No connection selected');
                return;
            }
            
            const host = mode === 'localhost' ? 'localhost' : selectedConnection.host;
            const modeLabel = mode === 'localhost' ? 'LOCALHOST (ON-SERVER)' : 'REMOTE CONNECTION';
            const modeIcon = mode === 'localhost' ? '🖥️' : '🌐';
            
            // Build formatted text with all information
            let text = `=============================================\n`;
            text += `${modeIcon} DATABASE CREDENTIALS - ${modeLabel}\n`;
            text += `=============================================\n\n`;
            
            text += `Connection Name: ${selectedConnection.name}\n`;
            text += `Connection Mode: ${modeLabel}\n`;
            if (mode === 'localhost') {
                text += `⚡ Note: Using localhost for faster on-server connections\n`;
            }
            text += `\n`;
            
            text += `--- Connection Details ---\n`;
            text += `Host: ${host}\n`;
            text += `Database Name: ${selectedConnection.dbName}\n`;
            text += `Username: ${selectedConnection.username}\n`;
            text += `Password: ${selectedConnection.password || '(empty)'}\n`;
            text += `Port: ${selectedConnection.port}\n\n`;
            
            text += `--- PHP PDO Connection ---\n`;
            text += `$pdo = new PDO(\n`;
            text += `    "mysql:host=${host};port=${selectedConnection.port};dbname=${selectedConnection.dbName}",\n`;
            text += `    "${selectedConnection.username}",\n`;
            text += `    "${selectedConnection.password || ''}"\n`;
            text += `);\n\n`;
            
            text += `--- MySQL CLI Connection ---\n`;
            if (selectedConnection.password) {
                text += `mysql -h ${host} -P ${selectedConnection.port} -u ${selectedConnection.username} -p${selectedConnection.password} ${selectedConnection.dbName}\n\n`;
            } else {
                text += `mysql -h ${host} -P ${selectedConnection.port} -u ${selectedConnection.username} ${selectedConnection.dbName}\n\n`;
            }
            
            text += `--- Connection URL ---\n`;
            text += `mysql://${selectedConnection.username}:${selectedConnection.password || ''}@${host}:${selectedConnection.port}/${selectedConnection.dbName}\n\n`;
            
            if (mode === 'localhost') {
                text += `--- IMPORTANT FOR AI/IDE ---\n`;
                text += `This uses 'localhost' which only works when your code\n`;
                text += `runs directly on the Hostinger server (same machine).\n`;
                text += `For external connections, use the Remote credentials.\n\n`;
            } else {
                text += `--- IMPORTANT FOR AI/IDE ---\n`;
                text += `This uses the remote hostname for external connections.\n`;
                text += `If running code on Hostinger server, use Localhost for speed.\n\n`;
            }
            
            text += `=============================================\n`;
            text += `Generated: ${new Date().toLocaleString()}\n`;
            text += `=============================================`;
            
            // Copy to clipboard
            const successMsg = mode === 'localhost' 
                ? '✅ Localhost credentials copied!' 
                : '✅ Remote credentials copied!';
            
            navigator.clipboard.writeText(text).then(() => {
                showToast(successMsg, 'success');
            }).catch(err => {
                console.error('Copy failed:', err);
                
                // Fallback: Create textarea and copy
                const textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.style.position = 'fixed';
                textarea.style.opacity = '0';
                document.body.appendChild(textarea);
                textarea.select();
                
                try {
                    document.execCommand('copy');
                    showToast(successMsg, 'success');
                } catch (e) {
                    showToast('❌ Failed to copy', 'error');
                }
                
                document.body.removeChild(textarea);
            });
        }
        
        function copyCode(elementId) {
            const text = document.getElementById(elementId).textContent;
            copyToClipboard(text, 'Code copied to clipboard!');
        }
        
        function copyToClipboard(text, message) {
            navigator.clipboard.writeText(text).then(() => {
                showToast(message || 'Copied to clipboard!', 'success');
            }).catch(err => {
                console.error('Copy failed:', err);
                showToast('Failed to copy', 'error');
            });
        }
        
        // Check all files on page load
        document.addEventListener('DOMContentLoaded', async function() {
            // Load database connections
            loadDatabaseConnections();
            
            const adminButtons = document.querySelectorAll('.admin-toggle-btn');
            
            for (const btn of adminButtons) {
                const filename = btn.getAttribute('data-filename');
                if (filename) {
                    const hasAdmin = await checkSuperAdminStatus(filename);
                    updateAdminButton(filename, hasAdmin);
                }
            }
            
            // Check all Transfer Link buttons
            const transferButtons = document.querySelectorAll('.transfer-link-toggle-btn');
            for (const btn of transferButtons) {
                const filename = btn.getAttribute('data-filename');
                if (filename) {
                    const hasTransferLink = await checkTransferLinkStatus(filename);
                    updateTransferLinkButton(filename, hasTransferLink);
                }
            }
        });
        
        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                if (document.getElementById('addSuperAdminModal').style.display === 'flex') {
                    closeAddSuperAdminModal();
                }
                if (document.getElementById('removeSuperAdminModal').style.display === 'flex') {
                    closeRemoveSuperAdminModal();
                }
                if (document.getElementById('bulkAdminModal').style.display === 'flex') {
                    closeBulkAdminModal();
                }
                if (document.getElementById('bulkResultsModal').style.display === 'flex') {
                    closeBulkResultsModal();
                }
                if (document.getElementById('dbCredentialsModal').style.display === 'flex') {
                    closeDatabaseCredentials();
                }
            }
        });
    </script>
</body>
</html>

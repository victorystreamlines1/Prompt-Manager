<?php
/**
 * ============================================
 * SQL Server Connection Test Page
 * ============================================
 * Simple connection test with hardcoded credentials
 * ============================================
 */

// Hardcoded credentials
$server = 'rbs-sql01';
$database = 'RBS-testinv';
$username = 'naif';
$password = 'P@master5007';
$port = '1433'; // Default SQL Server port

// Handle connection test
$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_connection'])) {
    // Try to connect using sqlsrv extension
    if (function_exists('sqlsrv_connect')) {
        $connectionInfo = [
            'Database' => $database,
            'UID' => $username,
            'PWD' => $password,
            'CharacterSet' => 'UTF-8',
            'ReturnDatesAsStrings' => true,
            'TrustServerCertificate' => true,
        ];
        
        $serverName = $server . ($port ? ',' . $port : '');
        $conn = @sqlsrv_connect($serverName, $connectionInfo);
        
        if ($conn === false) {
            $errors = sqlsrv_errors();
            $errorMsg = 'Connection failed';
            if ($errors) {
                $errorMsg .= ': ' . $errors[0]['message'];
            }
            $result = [
                'success' => false,
                'message' => $errorMsg,
                'server' => $server,
                'port' => $port
            ];
        } else {
            // Test query
            $query = "SELECT @@VERSION AS Version, DB_NAME() AS DatabaseName, SYSTEM_USER AS CurrentUser";
            $stmt = sqlsrv_query($conn, $query);
            
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                $errorMsg = 'Query execution failed';
                if ($errors) {
                    $errorMsg .= ': ' . $errors[0]['message'];
                }
                $result = [
                    'success' => false,
                    'message' => $errorMsg
                ];
            } else {
                $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
                $result = [
                    'success' => true,
                    'message' => 'Connection successful!',
                    'server' => $server,
                    'port' => $port,
                    'database' => $row['DatabaseName'] ?? $database,
                    'version' => $row['Version'] ?? 'Unknown',
                    'user' => $row['CurrentUser'] ?? $username
                ];
            }
            
            sqlsrv_close($conn);
        }
    } 
    // Try PDO with sqlsrv driver
    elseif (extension_loaded('pdo_sqlsrv')) {
        try {
            $dsn = "sqlsrv:Server={$server}" . ($port ? ",{$port}" : '') . ";Database={$database};TrustServerCertificate=1";
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 10
            ]);
            
            $stmt = $pdo->query("SELECT @@VERSION AS Version, DB_NAME() AS DatabaseName, SYSTEM_USER AS CurrentUser");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $result = [
                'success' => true,
                'message' => 'Connection successful!',
                'server' => $server,
                'port' => $port,
                'database' => $row['DatabaseName'] ?? $database,
                'version' => $row['Version'] ?? 'Unknown',
                'user' => $row['CurrentUser'] ?? $username
            ];
            
        } catch (PDOException $e) {
            $result = [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
                'server' => $server,
                'port' => $port
            ];
        }
    }
    // Try PDO with dblib (FreeTDS)
    elseif (extension_loaded('pdo_dblib')) {
        try {
            $dsn = "dblib:host={$server}" . ($port ? ":{$port}" : '') . ";dbname={$database}";
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 10
            ]);
            
            $stmt = $pdo->query("SELECT @@VERSION AS Version, DB_NAME() AS DatabaseName, SYSTEM_USER AS CurrentUser");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $result = [
                'success' => true,
                'message' => 'Connection successful!',
                'server' => $server,
                'port' => $port,
                'database' => $row['DatabaseName'] ?? $database,
                'version' => $row['Version'] ?? 'Unknown',
                'user' => $row['CurrentUser'] ?? $username
            ];
            
        } catch (PDOException $e) {
            $result = [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
                'server' => $server,
                'port' => $port
            ];
        }
    }
    // No SQL Server extension available
    else {
        $phpVersion = phpversion();
        $phpArch = (PHP_INT_SIZE * 8) . '-bit';
        $phpThreadSafety = (ZEND_THREAD_SAFE ? 'TS' : 'NTS');
        $phpExtensionDir = ini_get('extension_dir');
        
        $result = [
            'success' => false,
            'message' => 'SQL Server extensions not available. Please install sqlsrv or pdo_sqlsrv extension.',
            'extensions' => [
                'sqlsrv' => function_exists('sqlsrv_connect'),
                'pdo_sqlsrv' => extension_loaded('pdo_sqlsrv'),
                'pdo_dblib' => extension_loaded('pdo_dblib')
            ],
            'php_info' => [
                'version' => $phpVersion,
                'architecture' => $phpArch,
                'thread_safety' => $phpThreadSafety,
                'extension_dir' => $phpExtensionDir,
                'php_ini' => php_ini_loaded_file()
            ]
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SQL Server Connection Test</title>
    <link rel="icon" type="image/png" href="logoPM.png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        /* Animated Background */
        body::before {
            content: '';
            position: fixed;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            border-radius: 50%;
            top: -100px;
            left: -100px;
            animation: float 20s infinite ease-in-out;
            z-index: 0;
        }

        body::after {
            content: '';
            position: fixed;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.08) 0%, transparent 70%);
            border-radius: 50%;
            bottom: -150px;
            right: -150px;
            animation: float 25s infinite ease-in-out reverse;
            z-index: 0;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            25% { transform: translate(50px, -50px) scale(1.1); }
            50% { transform: translate(-30px, 30px) scale(0.9); }
            75% { transform: translate(40px, 50px) scale(1.05); }
        }

        .container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 50px 45px;
            width: 100%;
            max-width: 600px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
            border: 2px solid rgba(255, 255, 255, 0.2);
            animation: slideInUp 0.6s ease-out;
            position: relative;
            z-index: 10;
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #fff 0%, #f0f0f0 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }

        .header p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1rem;
        }

        .credentials-box {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            border: 2px solid rgba(255, 255, 255, 0.1);
        }

        .credentials-box h3 {
            color: rgba(255, 255, 255, 0.95);
            font-size: 1.1rem;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .credential-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.95rem;
        }

        .credential-item:last-child {
            border-bottom: none;
        }

        .credential-label {
            font-weight: 600;
            color: rgba(255, 255, 255, 0.8);
        }

        .credential-value {
            font-family: 'Courier New', monospace;
            color: #fbbf24;
            font-weight: 600;
        }

        .test-btn {
            width: 100%;
            padding: 18px 32px;
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            color: #000;
            border: none;
            border-radius: 12px;
            font-size: 1.2rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(251, 191, 36, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .test-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(251, 191, 36, 0.5);
        }

        .test-btn:active {
            transform: translateY(0);
        }

        .test-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .result {
            margin-top: 30px;
            padding: 20px;
            border-radius: 12px;
            animation: fadeIn 0.5s ease;
            border: 2px solid;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .result.success {
            background: rgba(16, 185, 129, 0.2);
            border-color: rgba(16, 185, 129, 0.5);
            color: #fff;
        }

        .result.error {
            background: rgba(239, 68, 68, 0.2);
            border-color: rgba(239, 68, 68, 0.5);
            color: #fff;
        }

        .result-title {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .result-title .icon {
            font-size: 1.5rem;
        }

        .result-details {
            background: rgba(0, 0, 0, 0.2);
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            line-height: 1.6;
        }

        .result-details strong {
            color: rgba(255, 255, 255, 0.9);
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s;
        }

        .back-link:hover {
            color: #fff;
            text-decoration: underline;
        }

        .result-details code {
            background: rgba(0, 0, 0, 0.3);
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
        }

        .result-details a {
            color: #60a5fa;
            text-decoration: underline;
            transition: color 0.3s;
        }

        .result-details a:hover {
            color: #93c5fd;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔌 SQL Server Connection Test</h1>
            <p>Test connection to RBS SQL Server</p>
        </div>

        <div class="credentials-box">
            <h3>📋 Connection Credentials</h3>
            <div class="credential-item">
                <span class="credential-label">Server:</span>
                <span class="credential-value"><?php echo htmlspecialchars($server); ?></span>
            </div>
            <div class="credential-item">
                <span class="credential-label">Database:</span>
                <span class="credential-value"><?php echo htmlspecialchars($database); ?></span>
            </div>
            <div class="credential-item">
                <span class="credential-label">Username:</span>
                <span class="credential-value"><?php echo htmlspecialchars($username); ?></span>
            </div>
            <div class="credential-item">
                <span class="credential-label">Port:</span>
                <span class="credential-value"><?php echo htmlspecialchars($port); ?></span>
            </div>
        </div>

        <?php if ($result): ?>
            <div class="result <?php echo $result['success'] ? 'success' : 'error'; ?>">
                <div class="result-title">
                    <span class="icon"><?php echo $result['success'] ? '✅' : '❌'; ?></span>
                    <span><?php echo $result['success'] ? 'Connection Successful' : 'Connection Failed'; ?></span>
                </div>
                <p><?php echo htmlspecialchars($result['message']); ?></p>
                
                <?php if ($result['success']): ?>
                    <div class="result-details">
                        <strong>Server:</strong> <?php echo htmlspecialchars($result['server']); ?><br>
                        <strong>Port:</strong> <?php echo htmlspecialchars($result['port']); ?><br>
                        <strong>Database:</strong> <?php echo htmlspecialchars($result['database'] ?? $database); ?><br>
                        <strong>User:</strong> <?php echo htmlspecialchars($result['user'] ?? $username); ?><br>
                        <?php if (isset($result['version'])): ?>
                            <strong>SQL Server Version:</strong><br>
                            <div style="margin-top: 5px; font-size: 0.85rem;">
                                <?php echo htmlspecialchars(substr($result['version'], 0, 200)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <?php if (isset($result['extensions'])): ?>
                        <div class="result-details">
                            <strong>Available Extensions:</strong><br>
                            sqlsrv: <?php echo $result['extensions']['sqlsrv'] ? '✅' : '❌'; ?><br>
                            pdo_sqlsrv: <?php echo $result['extensions']['pdo_sqlsrv'] ? '✅' : '❌'; ?><br>
                            pdo_dblib: <?php echo $result['extensions']['pdo_dblib'] ? '✅' : '❌'; ?><br>
                            <br>
                            
                            <?php if (isset($result['php_info'])): ?>
                                <strong>PHP Information:</strong><br>
                                Version: <?php echo htmlspecialchars($result['php_info']['version']); ?><br>
                                Architecture: <?php echo htmlspecialchars($result['php_info']['architecture']); ?><br>
                                Thread Safety: <?php echo htmlspecialchars($result['php_info']['thread_safety']); ?><br>
                                Extension Dir: <?php echo htmlspecialchars($result['php_info']['extension_dir']); ?><br>
                                PHP INI: <?php echo htmlspecialchars($result['php_info']['php_ini']); ?><br>
                                <br>
                            <?php endif; ?>
                            
                            <div style="background: rgba(251, 191, 36, 0.2); border: 2px solid rgba(251, 191, 36, 0.5); border-radius: 8px; padding: 15px; margin-bottom: 20px;">
                                <strong style="color: #fbbf24; font-size: 1.1rem;">⚠️ Important Notice:</strong><br>
                                PHP <?php echo isset($result['php_info']) ? htmlspecialchars($result['php_info']['version']) : '8.3'; ?> may not be officially supported by Microsoft SQL Server Driver.<br>
                                <strong>Recommended:</strong> Use PHP 8.2.x instead (available in Laragon Menu → PHP → Version)<br>
                                Or try using PHP 8.2 DLL files with PHP 8.3 (usually works).
                            </div>
                            
                            <strong style="color: #fbbf24; font-size: 1.1rem;">📥 Installation Instructions:</strong><br><br>
                            
                            <strong>Step 1:</strong> Download Microsoft SQL Server Driver for PHP<br>
                            <strong>Option A (GitHub - Latest):</strong> 
                            <a href="https://github.com/Microsoft/msphpsql/releases/latest" target="_blank" style="color: #60a5fa; text-decoration: underline;">
                                Download from GitHub (Recommended)
                            </a><br>
                            <strong>Option B (Microsoft Docs):</strong> 
                            <a href="https://docs.microsoft.com/en-us/sql/connect/php/download-drivers-php-sql-server" target="_blank" style="color: #60a5fa; text-decoration: underline;">
                                Download from Microsoft Docs
                            </a><br>
                            <strong>Option C (PECL):</strong> 
                            <a href="https://pecl.php.net/package/sqlsrv" target="_blank" style="color: #60a5fa; text-decoration: underline;">
                                Download from PECL
                            </a><br><br>
                            
                            <strong>Step 2:</strong> Extract the downloaded files<br>
                            You need these DLL files (for PHP <?php echo isset($result['php_info']) ? htmlspecialchars($result['php_info']['version']) : '8.3'; ?> <?php echo isset($result['php_info']) ? htmlspecialchars($result['php_info']['architecture']) : '64-bit'; ?> <?php echo isset($result['php_info']) ? htmlspecialchars($result['php_info']['thread_safety']) : 'TS'; ?>):<br>
                            • <code>php_sqlsrv_83_ts_x64.dll</code> (or <code>php_sqlsrv_82_ts_x64.dll</code> if 8.3 not available)<br>
                            • <code>php_pdo_sqlsrv_83_ts_x64.dll</code> (or <code>php_pdo_sqlsrv_82_ts_x64.dll</code> if 8.3 not available)<br><br>
                            
                            <strong>💡 Tip:</strong> If you can't find PHP 8.3 DLLs, use PHP 8.2 DLLs - they usually work with PHP 8.3!<br><br>
                            
                            <strong>Step 3:</strong> Copy DLL files to PHP extension directory<br>
                            Copy the DLL files to: <code style="background: rgba(0,0,0,0.3); padding: 2px 6px; border-radius: 4px;"><?php echo isset($result['php_info']) ? htmlspecialchars($result['php_info']['extension_dir']) : 'php/ext/'; ?></code><br><br>
                            
                            <strong>Step 4:</strong> Edit php.ini file<br>
                            Open: <code><?php echo isset($result['php_info']) ? htmlspecialchars($result['php_info']['php_ini']) : 'php.ini'; ?></code><br>
                            Add these lines at the end of the file:<br>
                            <code style="background: rgba(0,0,0,0.3); padding: 8px 12px; border-radius: 4px; display: block; margin: 8px 0; font-size: 0.9em; line-height: 1.6;">
                                ; SQL Server Extensions<br>
                                extension=php_sqlsrv_83_ts_x64.dll<br>
                                extension=php_pdo_sqlsrv_83_ts_x64.dll
                            </code><br>
                            <strong>Note:</strong> Use the actual filenames you downloaded (may be 82 instead of 83)<br><br>
                            
                            <strong>Step 5:</strong> Restart your web server (Laragon)<br>
                            Stop and Start Laragon services<br><br>
                            
                            <strong>Step 6:</strong> Test again by clicking "Test Connection" button<br><br>
                            
                            <strong style="color: #fbbf24;">⚠️ Important Notes:</strong><br>
                            • <strong>PHP 8.3 may not be officially supported</strong> - Consider switching to PHP 8.2 in Laragon<br>
                            • Make sure you download the correct version matching your PHP version (<?php echo isset($result['php_info']) ? htmlspecialchars($result['php_info']['version']) : 'check phpinfo()'; ?>)<br>
                            • Choose TS (Thread Safe) or NTS (Non-Thread Safe) based on your PHP build (<?php echo isset($result['php_info']) ? htmlspecialchars($result['php_info']['thread_safety']) : 'TS'; ?>)<br>
                            • <strong>You MUST install Microsoft ODBC Driver for SQL Server</strong><br>
                            <a href="https://docs.microsoft.com/en-us/sql/connect/odbc/download-odbc-driver-for-sql-server" target="_blank" style="color: #60a5fa; text-decoration: underline; font-weight: 600;">
                                Download ODBC Driver 17 or 18 (Required!)
                            </a><br><br>
                            
                            <strong>📖 For detailed guide:</strong> See <code>SQL_SERVER_INSTALLATION_GUIDE.md</code> file in project root<br><br>
                            
                            <strong>🔍 Verify Installation:</strong><br>
                            Open Laragon Terminal and run:<br>
                            <code style="background: rgba(0,0,0,0.3); padding: 4px 8px; border-radius: 4px; display: inline-block; margin: 5px 0;">
                                php -m | findstr sqlsrv
                            </code><br>
                            Should show: <code>pdo_sqlsrv</code> and <code>sqlsrv</code>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <button type="submit" name="test_connection" class="test-btn">
                <span>🔍</span>
                <span>Test Connection</span>
            </button>
        </form>

        <a href="index.php" class="back-link">← Back to Index</a>
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

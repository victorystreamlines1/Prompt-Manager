<?php
// ========================================
// PHP API HANDLER (MUST BE FIRST!)
// ========================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // ========================================
    // FOLDER BROWSER API
    // ========================================

    if ($_POST['action'] === 'browse_folders') {
        header('Content-Type: application/json');

        try {
            $path = $_POST['path'] ?? 'C:\\';

            // Normalize path
            $path = str_replace('/', '\\', $path);

            // Check if path exists
            if (!is_dir($path)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Path does not exist: ' . $path
                ]);
                exit();
            }

            // Read directories only
            $folders = [];

            $items = @scandir($path);

            if ($items === false) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Cannot read directory (Permission denied)'
                ]);
                exit();
            }

            foreach ($items as $item) {
                if ($item === '.' || $item === '..') continue;

                $fullPath = rtrim($path, '\\') . '\\' . $item;

                if (@is_dir($fullPath)) {
                    $folders[] = $item;
                }
            }

            // Sort folders
            sort($folders, SORT_NATURAL | SORT_FLAG_CASE);

            echo json_encode([
                'success' => true,
                'path' => $path,
                'folders' => $folders,
                'count' => count($folders)
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }

        exit();
    }

    // ========================================
    // CREATE APPLICATION API  
    // ========================================

    if ($_POST['action'] === 'create_application') {
        handleCreateApplication();
        exit();
    }
}

// ========================================
// CREATE APPLICATION HANDLER
// ========================================

function handleCreateApplication()
{
    header('Content-Type: application/json');

    try {
        $appName = $_POST['app_name'] ?? '';
        $frontendName = $_POST['frontend_name'] ?? 'app.html';
        $backendName = $_POST['backend_name'] ?? 'api.php';
        $frontendFolder = $_POST['frontend_folder'] ?? '';
        $backendFolder = $_POST['backend_folder'] ?? '';
        $backendUrl = $_POST['backend_url'] ?? '';
        $tableName = $_POST['table_name'] ?? '';
        $createTableSQL = $_POST['create_table_sql'] ?? '';
        $dbConfig = json_decode($_POST['db_config'] ?? '{}', true);
        $themeCode = $_POST['theme_code'] ?? '';

        // Debug logging
        error_log('=== AppMaker Generation Started ===');
        error_log('App Name: ' . $appName);
        error_log('Frontend Folder: ' . $frontendFolder);
        error_log('Backend Folder: ' . $backendFolder);
        error_log('Backend URL: ' . $backendUrl);
        error_log('Table Name: ' . $tableName);
        error_log('DB Config JSON: ' . $_POST['db_config']);
        error_log('DB Config Parsed: ' . print_r($dbConfig, true));
        error_log('Theme Code: ' . (!empty($themeCode) ? 'Custom theme provided (' . strlen($themeCode) . ' chars)' : 'No custom theme (using default)'));

        // Validate
        if (empty($appName) || empty($frontendFolder) || empty($backendFolder) || empty($tableName)) {
            throw new Exception('Missing required fields: appName, frontendFolder, backendFolder, or tableName');
        }

        // Validate database config
        if (empty($dbConfig) || !isset($dbConfig['host']) || !isset($dbConfig['dbName'])) {
            throw new Exception('Invalid database configuration. Please select a database connection first.');
        }

        // Test database connection BEFORE creating files
        error_log('🔌 Testing database connection...');
        try {
            $testDsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbName']};charset=utf8mb4";
            $testPassword = ($dbConfig['password'] === '' || $dbConfig['password'] === null) ? null : $dbConfig['password'];

            if ($testPassword === null) {
                $testPdo = new PDO($testDsn, $dbConfig['username']);
            } else {
                $testPdo = new PDO($testDsn, $dbConfig['username'], $testPassword);
            }

            $testPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            error_log('✅ Database connection successful!');
            unset($testPdo); // Close test connection
        } catch (PDOException $e) {
            error_log('❌ Database connection failed: ' . $e->getMessage());
            throw new Exception('Database connection failed: ' . $e->getMessage() . '. Please check your database credentials in Dashboard.');
        }

        // Ensure folders exist
        if (!is_dir($frontendFolder)) {
            if (!mkdir($frontendFolder, 0755, true)) {
                throw new Exception('Failed to create frontend folder: ' . $frontendFolder);
            }
        }

        if (!is_dir($backendFolder)) {
            if (!mkdir($backendFolder, 0755, true)) {
                throw new Exception('Failed to create backend folder: ' . $backendFolder);
            }
        }

        // Parse fields
        $fields = json_decode($_POST['fields_json'] ?? '[]', true);

        if (empty($fields)) {
            throw new Exception('No fields defined for table');
        }

        // Generate file paths
        $frontendPath = rtrim($frontendFolder, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $frontendName;
        $backendPath = rtrim($backendFolder, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $backendName;

        error_log('Frontend Path: ' . $frontendPath);
        error_log('Backend Path: ' . $backendPath);

        // Generate Frontend Code (with backend URL and custom theme if provided)
        $frontendCode = generateFrontendCode($appName, $tableName, $backendUrl, $dbConfig, $fields, $themeCode);

        // Generate Backend Code
        $backendCode = generateBackendCode($tableName, $dbConfig, $fields);

        // Write files
        if (file_put_contents($frontendPath, $frontendCode) === false) {
            throw new Exception('Failed to create frontend file');
        }

        if (file_put_contents($backendPath, $backendCode) === false) {
            throw new Exception('Failed to create backend file');
        }

        // Create table in database
        $tableCreated = createTableInDatabase($dbConfig, $createTableSQL);

        echo json_encode([
            'success' => true,
            'message' => 'Application generated successfully!',
            'frontend_file' => $frontendName,
            'backend_file' => $backendName,
            'frontend_path' => $frontendPath,
            'backend_path' => $backendPath,
            'backend_url' => $backendUrl,
            'table_created' => $tableCreated
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function createTableInDatabase($dbConfig, $createTableSQL)
{
    try {
        $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbName']};charset=utf8mb4";
        $password = ($dbConfig['password'] === '' || $dbConfig['password'] === null) ? null : $dbConfig['password'];

        if ($password === null) {
            $pdo = new PDO($dsn, $dbConfig['username']);
        } else {
            $pdo = new PDO($dsn, $dbConfig['username'], $password);
        }

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec($createTableSQL);

        return true;
    } catch (Exception $e) {
        return false;
    }
}

function generateFrontendCode($appName, $tableName, $backendUrl, $dbConfig, $fields, $themeCode = '')
{
    // Find primary key
    $primaryKey = 'id';
    foreach ($fields as $field) {
        if (isset($field['primaryKey']) && $field['primaryKey']) {
            $primaryKey = $field['fieldName'];
            break;
        }
    }

    $fieldsJson = json_encode($fields);

    // Prepare custom theme CSS (if provided)
    $customThemeCSS = '';
    if (!empty($themeCode)) {
        // Clean up the theme code - remove HTML comments and code fence markers
        $cleanedTheme = $themeCode;

        // Remove markdown code fences (```) if present
        $cleanedTheme = preg_replace('/^```[a-z]*\s*/m', '', $cleanedTheme);
        $cleanedTheme = preg_replace('/```\s*$/m', '', $cleanedTheme);

        // Check if theme already has <style> tags
        if (stripos($cleanedTheme, '<style') !== false) {
            // Theme already wrapped in style tags - use as-is
            $customThemeCSS = "\n    <!-- ===== CUSTOM THEME (User Provided) ===== -->\n" . trim($cleanedTheme);
        } else {
            // Raw CSS - wrap in style tags
            $customThemeCSS = "\n    <!-- ===== CUSTOM THEME (User Provided) ===== -->\n    <style>\n" . trim($cleanedTheme) . "\n    </style>";
        }
    }

    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>App-AI | {$appName}</title>
    <link rel="icon" type="image/png" href="../FuturisticLogo.png">
    <link rel="shortcut icon" type="image/png" href="../FuturisticLogo.png">
    <link rel="apple-touch-icon" href="../FuturisticLogo.png">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 30px; color: #fff; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { text-align: center; margin-bottom: 40px; }
        .header h1 { font-size: 42px; margin-bottom: 10px; text-shadow: 0 4px 15px rgba(0,0,0,0.3); }
        .card { background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border-radius: 16px; padding: 25px; margin-bottom: 25px; border: 1px solid rgba(255,255,255,0.2); box-shadow: 0 8px 32px rgba(0,0,0,0.2); }
        .card-title { font-size: 20px; font-weight: bold; color: #fbbf24; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .btn { padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 500; transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: linear-gradient(135deg, #22c55e 0%, #15803d 100%); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(34,197,94,0.4); }
        .btn-danger { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; }
        .form-group { margin-bottom: 15px; }
        .form-label { display: block; margin-bottom: 6px; font-weight: 500; font-size: 13px; }
        .form-input { width: 100%; padding: 10px 12px; background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.3); border-radius: 6px; color: #fff; font-size: 14px; }
        .form-input:focus { outline: none; border-color: #fbbf24; background: rgba(255,255,255,0.2); }
        .form-input::placeholder { color: rgba(255,255,255,0.5); }
        .data-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .data-table thead { background: rgba(251,191,36,0.2); }
        .data-table th { padding: 12px; text-align: left; color: #fbbf24; font-weight: bold; border-bottom: 2px solid rgba(251,191,36,0.3); }
        .data-table td { padding: 10px 12px; color: #fff; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .data-table tbody tr:hover { background: rgba(251,191,36,0.1); }
        .search-box { width: 100%; padding: 12px 15px; background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.3); border-radius: 8px; color: #fff; font-size: 14px; margin-bottom: 20px; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); backdrop-filter: blur(5px); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: linear-gradient(135deg, rgba(102,126,234,0.95) 0%, rgba(118,75,162,0.95) 100%); border-radius: 16px; padding: 30px; max-width: 600px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,0.5); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-close { background: none; border: none; color: #fff; font-size: 28px; cursor: pointer; }
    </style>
{$customThemeCSS}
</head>
<body>
<div class="container">
    <div class="header">
        <div style="display: flex; align-items: center; justify-content: center; gap: 20px; margin-bottom: 15px;">
            <img src="../FuturisticLogo.png" alt="App-AI Logo" style="width: 80px; height: 80px; filter: drop-shadow(0 6px 20px rgba(0,0,0,0.4)); animation: logoFloat 3s ease-in-out infinite;">
            <div style="text-align: left;">
                <h1 style="font-size: 42px; margin: 0; background: linear-gradient(135deg, #22c55e 0%, #fbbf24 50%, #667eea 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; font-weight: bold;">{$appName}</h1>
                <p style="font-size: 15px; margin: 5px 0 0 0; opacity: 0.9;">Powered by App-AI - Full CRUD Application</p>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-title">🔍 Search & Filter</div>
        <input type="text" id="searchInput" class="search-box" placeholder="🔍 Type to search instantly..." oninput="searchRecords()">
        <div style="display: flex; justify-content: space-between; align-items: center; gap: 10px;">
            <div id="resultCount" style="font-size: 13px; opacity: 0.8;"></div>
            <div style="display: flex; gap: 10px;">
                <button class="btn btn-primary" onclick="showAddModal()"><span>➕</span> Add New</button>
                <button class="btn btn-primary" onclick="refreshPage()" style="background: linear-gradient(135deg, #22c55e 0%, #15803d 100%); box-shadow: 0 4px 15px rgba(34, 197, 94, 0.4);" onmouseover="this.style.transform='translateY(-2px) rotate(180deg)'; this.style.boxShadow='0 6px 20px rgba(34, 197, 94, 0.6)'" onmouseout="this.style.transform='translateY(0) rotate(0deg)'; this.style.boxShadow='0 4px 15px rgba(34, 197, 94, 0.4)'"><span style="font-size: 16px;">🔄</span> Refresh</button>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-title">📊 {$tableName} Records</div>
        <div id="dataContainer" style="overflow-x: auto;">
            <div style="text-align: center; padding: 40px; opacity: 0.6;">Loading...</div>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div id="recordModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle" style="color: #fbbf24; font-size: 22px;">Add Record</h2>
            <button class="modal-close" onclick="closeModal()">×</button>
        </div>
        <form id="recordForm" onsubmit="saveRecord(event)">
            <div id="formFields"></div>
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="button" class="btn btn-danger" onclick="closeModal()" style="flex: 1;">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex: 1;"><span>💾</span> Save</button>
            </div>
        </form>
    </div>
</div>

<script>
const API_URL = '{$backendUrl}';
let records = [];
let currentEditId = null;

console.log('🚀 Application loaded - Generated by AppMaker');
console.log('📡 API URL:', API_URL);
console.log('🗄️ Table:', '{$tableName}');
console.log('🌐 Connection Type:', API_URL.startsWith('http') ? 'Absolute URL (Cross-origin)' : 'Relative Path (Same server)');
console.log('🔗 Full API Path:', new URL(API_URL, window.location.href).href);

document.addEventListener('DOMContentLoaded', () => {
    console.log('📋 Loading records...');
    loadRecords();
});

async function apiRequest(action, data = {}) {
    console.log('📤 API Request to:', API_URL);
    console.log('📤 Action:', action);
    console.log('📤 Data:', data);
    
    const formData = new FormData();
    formData.append('action', action);
    for (const key in data) {
        formData.append(key, data[key]);
    }
    
    try {
        const response = await fetch(API_URL, { method: 'POST', body: formData });
        
        console.log('📥 Response Status:', response.status, response.statusText);
        console.log('📥 Response OK:', response.ok);
        
        // Get response as text first
        const responseText = await response.text();
        console.log('📥 Raw Response (first 500 chars):', responseText.substring(0, 500));
        
        // Check if response is HTML error page
        if (responseText.trim().startsWith('<!DOCTYPE') || responseText.trim().startsWith('<html')) {
            console.error('❌ Server returned HTML instead of JSON!');
            console.error('❌ This usually means the API file was not found or has a PHP error');
            console.error('❌ Full response:', responseText);
            throw new Error('API file not found or returned HTML error. Check: ' + API_URL);
        }
        
        // Try to parse as JSON
        try {
            const result = JSON.parse(responseText);
            console.log('✅ Parsed JSON successfully:', result);
            return result;
        } catch (jsonError) {
            console.error('❌ JSON Parse Error:', jsonError);
            console.error('❌ Response was:', responseText);
            throw new Error('Server returned invalid JSON. Response: ' + responseText.substring(0, 200));
        }
    } catch (fetchError) {
        if (fetchError.message && fetchError.message.includes('Failed to fetch')) {
            console.error('❌ Network Error - Cannot reach API at:', API_URL);
            console.error('❌ Check if the backend file exists and is accessible');
        }
        console.error('❌ Fetch Error:', fetchError);
        throw fetchError;
    }
}

async function loadRecords(searchTerm = '') {
    try {
        console.log('🔍 Loading records, search:', searchTerm || 'none');
        const result = await apiRequest('list', { search: searchTerm });
        
        if (result.success) {
            console.log('✅ Records loaded:', result.records.length);
            records = result.records;
            renderRecords(records);
            document.getElementById('resultCount').textContent = '📊 ' + records.length + ' record(s) found';
        } else {
            console.error('❌ Failed to load records:', result.message);
            document.getElementById('dataContainer').innerHTML = '<div style="text-align: center; padding: 40px; color: #fca5a5;">❌ Error: ' + result.message + '</div>';
        }
    } catch (error) {
        console.error('❌ Error loading records:', error);
        console.error('❌ Error details:', error.stack);
        document.getElementById('dataContainer').innerHTML = '<div style="text-align: center; padding: 40px; color: #fca5a5;">❌ Error: ' + error.message + '<br><small>Check console for details</small></div>';
    }
}

function renderRecords(data) {
    const container = document.getElementById('dataContainer');
    
    if (data.length === 0) {
        container.innerHTML = '<div style="text-align: center; padding: 40px; opacity: 0.6;">📭 No records found</div>';
        return;
    }
    
    let html = '<table class="data-table"><thead><tr>';
    html += '<th style="width: 150px;">Actions</th>';
    
    const firstRecord = data[0];
    const columns = Object.keys(firstRecord);
    
    columns.forEach(col => {
        html += '<th>' + col + '</th>';
    });
    
    html += '</tr></thead><tbody>';
    
    data.forEach(record => {
        html += '<tr>';
        html += '<td><button class="btn btn-primary" onclick="editRecord(' + record['{$primaryKey}'] + ')" style="padding: 6px 12px; font-size: 12px; margin-right: 5px;">✏️</button><button class="btn btn-danger" onclick="deleteRecord(' + record['{$primaryKey}'] + ')" style="padding: 6px 12px; font-size: 12px;">🗑️</button></td>';
        
        columns.forEach(col => {
            const value = record[col] === null ? '<span style="color: #fca5a5; font-style: italic;">NULL</span>' : record[col];
            html += '<td>' + value + '</td>';
        });
        
        html += '</tr>';
    });
    
    html += '</tbody></table>';
    container.innerHTML = html;
}

let searchTimeout;
function searchRecords() {
    clearTimeout(searchTimeout);
    const searchTerm = document.getElementById('searchInput').value.trim();
    
    searchTimeout = setTimeout(() => {
        loadRecords(searchTerm);
    }, 300);
}

function showAddModal() {
    currentEditId = null;
    document.getElementById('modalTitle').textContent = 'Add New Record';
    document.getElementById('recordForm').reset();
    generateFormFields();
    document.getElementById('recordModal').classList.add('active');
}

async function editRecord(id) {
    currentEditId = id;
    const record = records.find(r => r['{$primaryKey}'] == id);
    
    if (!record) return;
    
    document.getElementById('modalTitle').textContent = 'Edit Record';
    generateFormFields(record);
    document.getElementById('recordModal').classList.add('active');
}

function generateFormFields(record = null) {
    const container = document.getElementById('formFields');
    let html = '';
    
    const fields = {$fieldsJson};
    
    console.log('Fields loaded:', fields);
    
    fields.forEach(field => {
        if (field.autoIncrement && !record) return;
        
        const value = record ? (record[field.fieldName] || '') : '';
        const required = field.notNull && !field.autoIncrement ? 'required' : '';
        
        html += '<div class="form-group">';
        html += '<label class="form-label">' + field.icon + ' ' + field.fieldName.replace(/_/g, ' ').replace(/\\b\\w/g, l => l.toUpperCase()) + (field.notNull ? ' *' : '') + '</label>';
        
        if (field.sqlType === 'TEXT') {
            html += '<textarea name="' + field.fieldName + '" class="form-input" rows="3" ' + required + '>' + value + '</textarea>';
        } else if (field.sqlType === 'DATE') {
            html += '<input type="date" name="' + field.fieldName + '" class="form-input" value="' + value + '" ' + required + '>';
        } else if (field.sqlType === 'DATETIME' || field.sqlType === 'TIMESTAMP') {
            const datetimeValue = value ? value.replace(' ', 'T').slice(0, 16) : '';
            html += '<input type="datetime-local" name="' + field.fieldName + '" class="form-input" value="' + datetimeValue + '" ' + required + '>';
        } else if (field.sqlType === 'TINYINT' && field.length === '1') {
            html += '<select name="' + field.fieldName + '" class="form-input" ' + required + '>';
            html += '<option value="">-- Select --</option>';
            html += '<option value="1" ' + (value == 1 ? 'selected' : '') + '>✅ Yes</option>';
            html += '<option value="0" ' + (value == 0 ? 'selected' : '') + '>❌ No</option>';
            html += '</select>';
        } else if (field.sqlType === 'INT' || field.sqlType === 'DECIMAL') {
            const step = field.sqlType === 'DECIMAL' ? '0.01' : '1';
            html += '<input type="number" name="' + field.fieldName + '" class="form-input" value="' + value + '" step="' + step + '" ' + required + '>';
        } else {
            html += '<input type="text" name="' + field.fieldName + '" class="form-input" value="' + value + '" ' + required + '>';
        }
        
        html += '</div>';
    });
    
    container.innerHTML = html;
}

function closeModal() {
    document.getElementById('recordModal').classList.remove('active');
    currentEditId = null;
}

async function saveRecord(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const data = {};
    for (const [key, value] of formData.entries()) {
        data[key] = value;
    }
    
    try {
        const action = currentEditId ? 'update' : 'create';
        if (currentEditId) {
            data['{$primaryKey}'] = currentEditId;
        }
        
        const result = await apiRequest(action, data);
        
        if (result.success) {
            closeModal();
            loadRecords();
            showToast('✅ ' + result.message, 'success');
        } else {
            showToast('❌ ' + result.message, 'error');
        }
    } catch (error) {
        showToast('❌ Error: ' + error.message, 'error');
    }
}

async function deleteRecord(id) {
    const confirmed = confirm('Delete this record? This cannot be undone!');
    if (!confirmed) return;
    
    try {
        const result = await apiRequest('delete', { '{$primaryKey}': id });
        
        if (result.success) {
            loadRecords();
            showToast('✅ Record deleted', 'success');
        } else {
            showToast('❌ ' + result.message, 'error');
        }
    } catch (error) {
        showToast('❌ Error: ' + error.message, 'error');
    }
}

function showToast(message, type) {
    const toast = document.createElement('div');
    const colors = {
        success: 'linear-gradient(135deg, #22c55e 0%, #15803d 100%)',
        error: 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)'
    };
    
    toast.style.cssText = 'position: fixed; bottom: 30px; right: 30px; background: ' + colors[type] + '; color: white; padding: 15px 25px; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.3); z-index: 10000; animation: slideIn 0.3s ease-out;';
    toast.innerHTML = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease-out forwards';
        setTimeout(() => document.body.removeChild(toast), 300);
    }, 3000);
}

function refreshPage() {
    const loadingDiv = document.createElement('div');
    loadingDiv.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(135deg, rgba(34,197,94,0.95) 0%, rgba(21,128,61,0.95) 100%); backdrop-filter: blur(10px); z-index: 11000; display: flex; align-items: center; justify-content: center; flex-direction: column;';
    loadingDiv.innerHTML = '<div style="font-size: 80px; margin-bottom: 20px; animation: refreshSpin 1s ease-in-out infinite;">🔄</div><div style="font-size: 28px; color: white; font-weight: bold; margin-bottom: 10px;">Refreshing...</div><div style="font-size: 16px; color: rgba(255,255,255,0.9);">Loading fresh data! ✨</div><style>@keyframes refreshSpin { 0% { transform: rotate(0deg) scale(1); } 50% { transform: rotate(180deg) scale(1.2); } 100% { transform: rotate(360deg) scale(1); } }</style>';
    document.body.appendChild(loadingDiv);
    setTimeout(() => location.reload(), 800);
}
</script>

<style>
@keyframes slideIn { from { transform: translateX(400px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
@keyframes slideOut { from { transform: translateX(0); opacity: 1; } to { transform: translateX(400px); opacity: 0; } }
@keyframes logoFloat {
    0%, 100% { transform: translateY(0px) rotate(0deg); }
    25% { transform: translateY(-6px) rotate(-1.5deg); }
    50% { transform: translateY(-10px) rotate(0deg); }
    75% { transform: translateY(-6px) rotate(1.5deg); }
}
</style>
</body>
</html>
HTML;
}

function generateBackendCode($tableName, $dbConfig, $fields)
{
    // Find primary key
    $primaryKey = 'id';
    foreach ($fields as $field) {
        if (isset($field['primaryKey']) && $field['primaryKey']) {
            $primaryKey = $field['fieldName'];
            break;
        }
    }

    $host = $dbConfig['host'] ?? 'localhost';
    $dbName = $dbConfig['dbName'] ?? '';
    $username = $dbConfig['username'] ?? 'root';
    $password = $dbConfig['password'] ?? '';
    $port = $dbConfig['port'] ?? '3306';

    // Use var_export for proper PHP array syntax instead of json_encode
    $fieldsArrayCode = var_export($fields, true);

    // Log for debugging
    error_log("=== Generating Backend Code ===");
    error_log("Host: $host");
    error_log("Database: $dbName");
    error_log("Username: $username");
    error_log("Port: $port");
    error_log("Table: $tableName");
    error_log("Primary Key: $primaryKey");
    error_log("Fields count: " . count($fields));

    $code = <<<'PHP'
<?php
// Prevent any output before JSON
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in output
ini_set('log_errors', 1); // Log errors instead

// Start output buffering to catch any accidental output
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_end_clean();
    exit();
}

// Database Configuration
$DB_CONFIG = [
    'host' => 'DB_HOST_PLACEHOLDER',
    'dbname' => 'DB_NAME_PLACEHOLDER',
    'username' => 'DB_USER_PLACEHOLDER',
    'password' => 'DB_PASS_PLACEHOLDER',
    'port' => 'DB_PORT_PLACEHOLDER',
    'charset' => 'utf8mb4'
];

function getConnection() {
    global $DB_CONFIG;
    try {
        error_log('Attempting connection to: ' . $DB_CONFIG['host'] . '/' . $DB_CONFIG['dbname']);
        
        $dsn = "mysql:host={$DB_CONFIG['host']};port={$DB_CONFIG['port']};dbname={$DB_CONFIG['dbname']};charset={$DB_CONFIG['charset']}";
        $password = ($DB_CONFIG['password'] === '' || $DB_CONFIG['password'] === null) ? null : $DB_CONFIG['password'];
        
        error_log('Using password: ' . ($password === null ? 'NO' : 'YES'));
        
        if ($password === null) {
            $pdo = new PDO($dsn, $DB_CONFIG['username']);
        } else {
            $pdo = new PDO($dsn, $DB_CONFIG['username'], $password);
        }
        
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        error_log('Connection successful!');
        
        return $pdo;
    } catch (PDOException $e) {
        error_log('Connection error: ' . $e->getMessage());
        jsonResponse(false, 'Connection failed: ' . $e->getMessage());
    }
}

function jsonResponse($success, $message, $data = []) {
    // Clear any accidental output
    if (ob_get_length()) ob_end_clean();
    
    $response = array_merge(['success' => $success, 'message' => $message], $data);
    echo json_encode($response);
    exit();
}

try {
    $action = $_POST['action'] ?? '';
    
    error_log('Backend API called - Action: ' . $action);
    
    $pdo = getConnection();
    
    error_log('Database connected successfully');
    
    switch ($action) {
        case 'list':
            $search = $_POST['search'] ?? '';
            $tableName = 'TABLE_NAME_PLACEHOLDER';
            $fields = FIELDS_ARRAY_PLACEHOLDER;
            
            if ($search) {
                $searchColumns = array_map(function($f) { return "`{$f['fieldName']}`"; }, $fields);
                $searchSql = "SELECT * FROM `$tableName` WHERE CONCAT_WS(' ', " . implode(', ', $searchColumns) . ") LIKE ?";
                $stmt = $pdo->prepare($searchSql);
                $stmt->execute(["%$search%"]);
            } else {
                $stmt = $pdo->query("SELECT * FROM `$tableName` ORDER BY `PRIMARY_KEY_PLACEHOLDER` DESC");
            }
            
            $records = $stmt->fetchAll();
            
            error_log('Records fetched: ' . count($records));
            
            jsonResponse(true, 'Records loaded', ['records' => $records]);
            break;
            
        case 'create':
            $tableName = 'TABLE_NAME_PLACEHOLDER';
            $data = [];
            
            foreach (FIELDS_ARRAY_PLACEHOLDER as $field) {
                if ($field['autoIncrement']) continue;
                if (isset($_POST[$field['fieldName']])) {
                    $data[$field['fieldName']] = $_POST[$field['fieldName']];
                }
            }
            
            error_log('Creating record with data: ' . print_r($data, true));
            
            $columns = array_keys($data);
            $placeholders = array_fill(0, count($columns), '?');
            $sql = "INSERT INTO `$tableName` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $placeholders) . ")";
            
            error_log('Insert SQL: ' . $sql);
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_values($data));
            
            error_log('Record created - ID: ' . $pdo->lastInsertId());
            
            jsonResponse(true, 'Record created successfully', ['id' => $pdo->lastInsertId()]);
            break;
            
        case 'update':
            $tableName = 'TABLE_NAME_PLACEHOLDER';
            $id = $_POST['PRIMARY_KEY_PLACEHOLDER'] ?? null;
            
            if (!$id) {
                jsonResponse(false, 'ID required for update');
            }
            
            $data = [];
            foreach (FIELDS_ARRAY_PLACEHOLDER as $field) {
                if ($field['autoIncrement'] || $field['primaryKey']) continue;
                if (isset($_POST[$field['fieldName']])) {
                    $data[$field['fieldName']] = $_POST[$field['fieldName']];
                }
            }
            
            $setClauses = [];
            foreach (array_keys($data) as $col) {
                $setClauses[] = "`$col` = ?";
            }
            
            $sql = "UPDATE `$tableName` SET " . implode(', ', $setClauses) . " WHERE `PRIMARY_KEY_PLACEHOLDER` = ?";
            $params = array_values($data);
            $params[] = $id;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            error_log('Record updated - ID: ' . $id);
            
            jsonResponse(true, 'Record updated successfully');
            break;
            
        case 'delete':
            $tableName = 'TABLE_NAME_PLACEHOLDER';
            $id = $_POST['PRIMARY_KEY_PLACEHOLDER'] ?? null;
            
            if (!$id) {
                error_log('Delete failed: No ID provided');
                jsonResponse(false, 'ID required for delete');
            }
            
            $stmt = $pdo->prepare("DELETE FROM `$tableName` WHERE `PRIMARY_KEY_PLACEHOLDER` = ?");
            $stmt->execute([$id]);
            
            error_log('Record deleted - ID: ' . $id);
            
            jsonResponse(true, 'Record deleted successfully');
            break;
            
        default:
            jsonResponse(false, 'Invalid action');
    }
    
} catch (PDOException $e) {
    error_log('PDO Error: ' . $e->getMessage());
    jsonResponse(false, 'Database error: ' . $e->getMessage());
} catch (Exception $e) {
    error_log('General Error: ' . $e->getMessage());
    jsonResponse(false, 'Error: ' . $e->getMessage());
} catch (Throwable $e) {
    error_log('Fatal Error: ' . $e->getMessage());
    if (ob_get_length()) ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Fatal error: ' . $e->getMessage()]);
    exit();
}
?>
PHP;

// Replace placeholders
$code = str_replace('DB_HOST_PLACEHOLDER', $host, $code);
$code = str_replace('DB_NAME_PLACEHOLDER', $dbName, $code);
$code = str_replace('DB_USER_PLACEHOLDER', $username, $code);
$code = str_replace('DB_PASS_PLACEHOLDER', $password, $code);
$code = str_replace('DB_PORT_PLACEHOLDER', $port, $code);
$code = str_replace('TABLE_NAME_PLACEHOLDER', $tableName, $code);
$code = str_replace('PRIMARY_KEY_PLACEHOLDER', $primaryKey, $code);
$code = str_replace('FIELDS_ARRAY_PLACEHOLDER', $fieldsArrayCode, $code);

// Verify replacements
if (strpos($code, 'DB_HOST_PLACEHOLDER') !== false) {
error_log('⚠️ WARNING: DB_HOST_PLACEHOLDER not replaced!');
}
if (strpos($code, 'TABLE_NAME_PLACEHOLDER') !== false) {
error_log('⚠️ WARNING: TABLE_NAME_PLACEHOLDER not replaced!');
}

error_log("=== Backend Code Generated Successfully ===");
error_log("Code length: " . strlen($code) . " bytes");

return $code;
}

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
        color: #fff;
        padding: 30px;
    }
    
    /* AI Prompt Modal Resize Handles */
    .resize-handle {
        position: absolute;
        z-index: 10;
        background: transparent;
        transition: background 0.2s ease;
    }
    .resize-handle:hover {
        background: rgba(251, 191, 36, 0.4);
    }
    .resize-handle.resize-ne:hover,
    .resize-handle.resize-nw:hover,
    .resize-handle.resize-se:hover,
    .resize-handle.resize-sw:hover {
        background: rgba(251, 191, 36, 0.6);
        border-radius: 50%;
    }
    #aiPromptModalContent {
        transition: box-shadow 0.3s ease;
    }
    #aiPromptModalContent:hover {
        box-shadow: 0 30px 100px rgba(0,0,0,0.8), 0 0 0 1px rgba(255,255,255,0.3);
    }
    #aiPromptModalHeader:active {
        cursor: grabbing;
    }

    .container {
        max-width: 1400px;
        margin: 0 auto;
    }

    .header {
        text-align: center;
        margin-bottom: 40px;
        animation: fadeIn 0.8s ease-out;
    }

    .header h1 {
        font-size: 48px;
        margin-bottom: 15px;
        text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    }

    .header p {
        font-size: 18px;
        opacity: 0.9;
    }

    .main-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 25px;
        margin-bottom: 30px;
    }

    .card {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        border-radius: 16px;
        padding: 25px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
    }

    .card-title {
        font-size: 22px;
        font-weight: bold;
        margin-bottom: 20px;
        color: #fbbf24;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
        font-size: 14px;
    }

    .form-input,
    .form-select {
        width: 100%;
        padding: 12px 15px;
        background: rgba(255, 255, 255, 0.15);
        border: 1px solid rgba(255, 255, 255, 0.3);
        border-radius: 8px;
        color: #fff;
        font-size: 14px;
        transition: all 0.3s ease;
    }

    .form-input:focus,
    .form-select:focus {
        outline: none;
        border-color: #fbbf24;
        background: rgba(255, 255, 255, 0.2);
        box-shadow: 0 0 15px rgba(251, 191, 36, 0.3);
    }

    .form-input::placeholder {
        color: rgba(255, 255, 255, 0.5);
    }

    .btn {
        padding: 12px 24px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-primary {
        background: linear-gradient(135deg, #22c55e 0%, #15803d 100%);
        color: white;
        box-shadow: 0 4px 15px rgba(34, 197, 94, 0.4);
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(34, 197, 94, 0.6);
    }

    .btn-danger {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
    }

    .btn-secondary {
        background: rgba(255, 255, 255, 0.2);
        color: white;
        border: 1px solid rgba(255, 255, 255, 0.3);
    }

    /* Drag & Drop Builder */
    .builder-container {
        display: grid;
        grid-template-columns: 350px 1fr;
        gap: 25px;
        min-height: 500px;
    }

    .fields-palette {
        background: rgba(0, 0, 0, 0.2);
        border-radius: 12px;
        padding: 20px;
        border: 2px solid rgba(251, 191, 36, 0.3);
        max-height: 600px;
        overflow-y: auto;
    }

    .fields-palette::-webkit-scrollbar {
        width: 8px;
    }

    .fields-palette::-webkit-scrollbar-track {
        background: rgba(0, 0, 0, 0.3);
        border-radius: 4px;
    }

    .fields-palette::-webkit-scrollbar-thumb {
        background: rgba(251, 191, 36, 0.5);
        border-radius: 4px;
    }

    .palette-title {
        font-size: 16px;
        font-weight: bold;
        margin-bottom: 15px;
        color: #fbbf24;
        position: sticky;
        top: 0;
        background: rgba(0, 0, 0, 0.4);
        padding: 10px;
        margin: -10px -10px 15px -10px;
        border-radius: 8px;
        z-index: 10;
    }

    .field-item {
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.3) 0%, rgba(37, 99, 235, 0.2) 100%);
        border: 2px solid rgba(59, 130, 246, 0.4);
        border-radius: 10px;
        padding: 12px 15px;
        margin-bottom: 10px;
        cursor: grab;
        transition: all 0.3s ease;
        user-select: none;
    }

    .field-item:hover {
        transform: translateX(5px) scale(1.02);
        border-color: #3b82f6;
        box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4);
    }

    .field-item:active {
        cursor: grabbing;
    }

    .field-icon {
        font-size: 20px;
        margin-right: 8px;
    }

    .field-name {
        font-weight: bold;
        font-size: 14px;
    }

    .field-type {
        font-size: 11px;
        opacity: 0.8;
        margin-top: 3px;
    }

    .table-builder {
        background: rgba(0, 0, 0, 0.3);
        border-radius: 12px;
        padding: 20px;
        border: 2px dashed rgba(34, 197, 94, 0.4);
        min-height: 500px;
        max-height: 600px;
        overflow-y: auto;
    }

    .table-builder::-webkit-scrollbar {
        width: 10px;
    }

    .table-builder::-webkit-scrollbar-track {
        background: rgba(0, 0, 0, 0.3);
        border-radius: 5px;
    }

    .table-builder::-webkit-scrollbar-thumb {
        background: rgba(34, 197, 94, 0.5);
        border-radius: 5px;
    }

    .builder-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 400px;
        color: rgba(255, 255, 255, 0.5);
    }

    .builder-empty-icon {
        font-size: 64px;
        margin-bottom: 15px;
    }

    .dropped-field {
        background: rgba(34, 197, 94, 0.2);
        border: 2px solid rgba(34, 197, 94, 0.5);
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 12px;
        animation: dropIn 0.3s ease-out;
    }

    @keyframes dropIn {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .field-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
    }

    .field-controls {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }

    .field-control-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
    }

    .toggle-switch {
        position: relative;
        width: 40px;
        height: 20px;
        background: rgba(239, 68, 68, 0.3);
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s;
    }

    .toggle-switch.active {
        background: rgba(34, 197, 94, 0.5);
    }

    .toggle-switch::after {
        content: '';
        position: absolute;
        width: 16px;
        height: 16px;
        background: white;
        border-radius: 50%;
        top: 2px;
        left: 2px;
        transition: all 0.3s;
    }

    .toggle-switch.active::after {
        left: 22px;
    }

    .generate-btn {
        width: 100%;
        padding: 20px;
        font-size: 20px;
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
        border: none;
        border-radius: 12px;
        cursor: pointer;
        font-weight: bold;
        box-shadow: 0 8px 25px rgba(240, 147, 251, 0.5);
        transition: all 0.3s ease;
    }

    .generate-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 35px rgba(240, 147, 251, 0.7);
    }

    .refresh-btn {
        padding: 20px 24px;
        background: linear-gradient(135deg, #22c55e 0%, #15803d 100%);
        color: white;
        border: none;
        border-radius: 12px;
        cursor: pointer;
        font-weight: bold;
        box-shadow: 0 8px 25px rgba(34, 197, 94, 0.5);
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 80px;
    }

    .refresh-btn:hover {
        transform: translateY(-3px) rotate(180deg);
        box-shadow: 0 12px 35px rgba(34, 197, 94, 0.7);
    }

    .refresh-btn:active {
        transform: translateY(-1px) rotate(360deg);
    }

    .helper-text {
        font-size: 12px;
        opacity: 0.7;
        margin-top: 5px;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes logoFloat {

        0%,
        100% {
            transform: translateY(0px) rotate(0deg);
        }

        25% {
            transform: translateY(-8px) rotate(-2deg);
        }

        50% {
            transform: translateY(-12px) rotate(0deg);
        }

        75% {
            transform: translateY(-8px) rotate(2deg);
        }
    }

    .dragging {
        opacity: 0.5;
        transform: rotate(3deg);
    }

    .drag-over {
        border-color: #22c55e !important;
        background: rgba(34, 197, 94, 0.1) !important;
        box-shadow: inset 0 0 30px rgba(34, 197, 94, 0.3);
    }

    @media (max-width: 968px) {
        .main-grid {
            grid-template-columns: 1fr;
        }

        .builder-container {
            grid-template-columns: 1fr;
        }

        .fields-palette {
            max-height: 300px;
        }
    }

    .success-message {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: linear-gradient(135deg, #22c55e 0%, #15803d 100%);
        color: white;
        padding: 40px 60px;
        border-radius: 15px;
        text-align: center;
        z-index: 10000;
        box-shadow: 0 0 50px rgba(34, 197, 94, 0.7);
        animation: scaleIn 0.5s ease-out;
    }

    @keyframes scaleIn {
        from {
            transform: translate(-50%, -50%) scale(0.5);
            opacity: 0;
        }

        to {
            transform: translate(-50%, -50%) scale(1);
            opacity: 1;
        }
    }

    option {
        background: rgba(0, 0, 0, 0.9);
        color: #fff;
    }

    @keyframes slideInRight {
        from {
            transform: translateX(400px);
            opacity: 0;
        }

        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }

        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }

    @keyframes slideInUp {
        from {
            transform: translateY(100px);
            opacity: 0;
        }

        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    @keyframes slideOutDown {
        from {
            transform: translateY(0);
            opacity: 1;
        }

        to {
            transform: translateY(100px);
            opacity: 0;
        }
    }

    @keyframes spin {
        from {
            transform: rotate(0deg);
        }

        to {
            transform: rotate(360deg);
        }
    }

    #foldersList::-webkit-scrollbar {
        width: 12px;
    }

    #foldersList::-webkit-scrollbar-track {
        background: rgba(0, 0, 0, 0.3);
        border-radius: 6px;
    }

    #foldersList::-webkit-scrollbar-thumb {
        background: linear-gradient(135deg, rgba(34, 197, 94, 0.6) 0%, rgba(16, 185, 129, 0.6) 100%);
        border-radius: 6px;
    }

    #foldersList::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(135deg, rgba(34, 197, 94, 0.8) 0%, rgba(16, 185, 129, 0.8) 100%);
    }

    .folder-dropdown::-webkit-scrollbar {
        width: 8px;
    }

    .folder-dropdown::-webkit-scrollbar-track {
        background: rgba(0, 0, 0, 0.3);
        border-radius: 4px;
    }

    .folder-dropdown::-webkit-scrollbar-thumb {
        background: rgba(251, 191, 36, 0.5);
        border-radius: 4px;
    }

    .folder-dropdown::-webkit-scrollbar-thumb:hover {
        background: rgba(251, 191, 36, 0.7);
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
            max-height: 0;
        }

        to {
            opacity: 1;
            transform: translateY(0);
            max-height: 300px;
        }
    }

    @keyframes slideUp {
        from {
            opacity: 1;
            transform: translateY(0);
        }

        to {
            opacity: 0;
            transform: translateY(-10px);
        }
    }

    /* Table Templates Dropdown Scrollbar */
    #tableTemplatesDropdown::-webkit-scrollbar {
        width: 10px;
    }

    #tableTemplatesDropdown::-webkit-scrollbar-track {
        background: rgba(0, 0, 0, 0.4);
        border-radius: 5px;
    }

    #tableTemplatesDropdown::-webkit-scrollbar-thumb {
        background: linear-gradient(135deg, rgba(251, 191, 36, 0.6) 0%, rgba(245, 158, 11, 0.6) 100%);
        border-radius: 5px;
    }

    #tableTemplatesDropdown::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(135deg, rgba(251, 191, 36, 0.8) 0%, rgba(245, 158, 11, 0.8) 100%);
    }
    </style>
</head>

<body>

    <div class="container">
        <div class="header">
            <div style="display: flex; align-items: center; justify-content: center; gap: 25px; margin-bottom: 20px;">
                <img src="FuturisticLogo.png" alt="App-AI Logo"
                    style="width: 120px; height: 120px; filter: drop-shadow(0 8px 25px rgba(0,0,0,0.4)); animation: logoFloat 3s ease-in-out infinite;">
                <div style="text-align: left;">
                    <h1
                        style="font-size: 56px; margin: 0; background: linear-gradient(135deg, #22c55e 0%, #fbbf24 50%, #667eea 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; font-weight: bold;">
                        App-AI</h1>
                    <p style="font-size: 18px; margin: 8px 0 0 0; opacity: 0.95;">Generate Complete CRUD Applications
                        Instantly</p>
                </div>
            </div>
            <a href="dashboard.html"
                style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; background: rgba(255,255,255,0.2); border-radius: 8px; text-decoration: none; color: #fff; font-size: 14px; margin-top: 15px; transition: all 0.3s;"
                onmouseover="this.style.background='rgba(255,255,255,0.3)'"
                onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                <span>⬅️</span> Back to Database Panel
            </a>
        </div>

        <!-- Step 1: Application Info -->
        <div class="main-grid">
            <div class="card">
                <div class="card-title">📝 Application Information</div>

                <div class="form-group">
                    <label class="form-label">Application Name</label>
                    <input type="text" id="appName" class="form-input"
                        placeholder="e.g., User Management, Product Catalog">
                    <div class="helper-text">Descriptive name for your application</div>
                </div>

                <div class="form-group">
                    <label class="form-label">Frontend Filename (HTML)</label>
                    <input type="text" id="frontendName" class="form-input" placeholder="e.g., users.html"
                        value="app.html">
                    <div class="helper-text">HTML file with UI and AJAX</div>
                </div>

                <div class="form-group">
                    <label class="form-label">Backend Filename (PHP)</label>
                    <input type="text" id="backendName" class="form-input" placeholder="e.g., users_api.php"
                        value="api.php">
                    <div class="helper-text">PHP API file for database operations</div>
                </div>

                <div class="form-group">
                    <label class="form-label">🎨 Frontend Folder (HTML File)</label>
                    <div style="display: flex; gap: 10px; align-items: start;">
                        <div style="flex: 1; position: relative;">
                            <input type="text" id="frontendFolder" class="form-input"
                                placeholder="e.g., C:\projects\frontend\" value="C:\laragon\www\generated\frontend\"
                                onblur="saveFolderOnBlur('frontend')"
                                onfocus="document.getElementById('frontendFolderDropdown').style.display='none'">
                            <button type="button" onclick="toggleFolderDropdown('frontend')"
                                style="position: absolute; right: 5px; top: 50%; transform: translateY(-50%); background: rgba(251,191,36,0.3); border: none; border-radius: 5px; padding: 5px 10px; cursor: pointer; color: #fbbf24; font-size: 12px; transition: all 0.3s;"
                                onmouseover="this.style.background='rgba(251,191,36,0.5)'"
                                onmouseout="this.style.background='rgba(251,191,36,0.3)'">
                                ▼
                            </button>
                            <div id="frontendFolderDropdown" class="folder-dropdown"
                                style="display: none; position: absolute; top: 100%; left: 0; right: 0; margin-top: 5px; background: rgba(0,0,0,0.95); border: 1px solid rgba(251,191,36,0.4); border-radius: 8px; max-height: 300px; overflow-y: auto; z-index: 100; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
                            </div>
                        </div>
                        <button type="button" class="btn btn-primary" onclick="pickFolder('frontend')"
                            style="white-space: nowrap; padding: 12px 20px;">
                            <span>📁</span> Browse
                        </button>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 8px;">
                        <div class="helper-text" style="margin: 0;">💡 Where to save the HTML file</div>
                        <button type="button" onclick="saveCurrentFolder('frontend')" class="btn btn-secondary"
                            style="padding: 6px 12px; font-size: 12px;">
                            <span>💾</span> Save Path
                        </button>
                    </div>
                </div>

                <div class="form-group" style="margin-top: 20px;">
                    <label class="form-label">🌐 API Connection Builder</label>

                    <!-- Domain/Base URL -->
                    <div style="margin-bottom: 12px;">
                        <label style="font-size: 12px; opacity: 0.9; margin-bottom: 5px; display: block;">1️⃣ Domain /
                            Base URL:</label>
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <div style="flex: 1; position: relative;">
                                <input type="text" id="apiDomain" class="form-input"
                                    placeholder="e.g., http://localhost or https://yourserver.com"
                                    value="http://localhost" oninput="buildApiUrl()"
                                    onfocus="document.getElementById('domainDropdown').style.display='none'"
                                    style="font-family: monospace;">
                                <button type="button" onclick="toggleDomainDropdown()"
                                    style="position: absolute; right: 5px; top: 50%; transform: translateY(-50%); background: rgba(59,130,246,0.3); border: none; border-radius: 5px; padding: 5px 10px; cursor: pointer; color: #60a5fa; font-size: 12px; transition: all 0.3s;"
                                    onmouseover="this.style.background='rgba(59,130,246,0.5)'"
                                    onmouseout="this.style.background='rgba(59,130,246,0.3)'">
                                    ▼
                                </button>
                                <div id="domainDropdown" class="folder-dropdown"
                                    style="display: none; position: absolute; top: 100%; left: 0; right: 0; margin-top: 5px; background: rgba(0,0,0,0.95); border: 1px solid rgba(59,130,246,0.4); border-radius: 8px; max-height: 250px; overflow-y: auto; z-index: 100; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
                                </div>
                            </div>
                            <button type="button" onclick="saveDomain()" class="btn btn-secondary"
                                style="padding: 10px 16px; font-size: 12px;">
                                💾
                            </button>
                        </div>
                        <div style="font-size: 11px; opacity: 0.7; margin-top: 4px;">💡 The domain where your backend
                            PHP will be hosted</div>
                    </div>

                    <!-- Backend Filename (Auto from Backend Filename input) -->
                    <div style="margin-bottom: 12px;">
                        <label style="font-size: 12px; opacity: 0.9; margin-bottom: 5px; display: block;">2️⃣ Backend
                            File (Auto from Backend Filename above):</label>
                        <input type="text" id="apiFilename" class="form-input" placeholder="api.php" value="" readonly
                            style="background: rgba(0,0,0,0.2); cursor: not-allowed; font-family: monospace; color: #fbbf24;">
                        <div style="font-size: 11px; opacity: 0.7; margin-top: 4px;">🔒 Read-only - Changes
                            automatically with "Backend Filename (PHP)" field</div>
                    </div>

                    <!-- Final Backend API URL -->
                    <div style="margin-bottom: 12px;">
                        <label style="font-size: 12px; opacity: 0.9; margin-bottom: 5px; display: block;">✅ Final
                            Backend API URL (Frontend will connect to this):</label>
                        <div style="display: flex; gap: 10px; align-items: start;">
                            <div style="flex: 1; position: relative;">
                                <input type="text" id="backendApiUrl" class="form-input"
                                    placeholder="Generated automatically..." value=""
                                    style="font-family: monospace; background: rgba(34,197,94,0.1); border-color: rgba(34,197,94,0.4); color: #86efac; font-weight: 600;"
                                    onfocus="document.getElementById('apiUrlDropdown').style.display='none'">
                                <button type="button" onclick="toggleApiUrlDropdown()"
                                    style="position: absolute; right: 5px; top: 50%; transform: translateY(-50%); background: rgba(34,197,94,0.3); border: none; border-radius: 5px; padding: 5px 10px; cursor: pointer; color: #22c55e; font-size: 12px; transition: all 0.3s;"
                                    onmouseover="this.style.background='rgba(34,197,94,0.5)'"
                                    onmouseout="this.style.background='rgba(34,197,94,0.3)'">
                                    ▼
                                </button>
                                <div id="apiUrlDropdown" class="folder-dropdown"
                                    style="display: none; position: absolute; top: 100%; left: 0; right: 0; margin-top: 5px; background: rgba(0,0,0,0.95); border: 1px solid rgba(34,197,94,0.4); border-radius: 8px; max-height: 300px; overflow-y: auto; z-index: 100; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
                                </div>
                            </div>
                            <button type="button" onclick="saveCurrentApiUrl()" class="btn btn-secondary"
                                style="white-space: nowrap; padding: 12px 20px;">
                                <span>💾</span> Save
                            </button>
                        </div>
                        <div style="font-size: 11px; opacity: 0.7; margin-top: 4px;">📡 This URL will be embedded in
                            your Frontend HTML to connect to Backend</div>
                    </div>
                </div>

                <div class="form-group" style="margin-top: 20px;">
                    <label class="form-label">🔧 Backend Folder (PHP API File)</label>
                    <div style="display: flex; gap: 10px; align-items: start;">
                        <div style="flex: 1; position: relative;">
                            <input type="text" id="backendFolder" class="form-input"
                                placeholder="e.g., C:\laragon\www\api\" value="C:\laragon\www\generated\backend\"
                                onblur="saveFolderOnBlur('backend')"
                                onfocus="document.getElementById('backendFolderDropdown').style.display='none'">
                            <button type="button" onclick="toggleFolderDropdown('backend')"
                                style="position: absolute; right: 5px; top: 50%; transform: translateY(-50%); background: rgba(251,191,36,0.3); border: none; border-radius: 5px; padding: 5px 10px; cursor: pointer; color: #fbbf24; font-size: 12px; transition: all 0.3s;"
                                onmouseover="this.style.background='rgba(251,191,36,0.5)'"
                                onmouseout="this.style.background='rgba(251,191,36,0.3)'">
                                ▼
                            </button>
                            <div id="backendFolderDropdown" class="folder-dropdown"
                                style="display: none; position: absolute; top: 100%; left: 0; right: 0; margin-top: 5px; background: rgba(0,0,0,0.95); border: 1px solid rgba(251,191,36,0.4); border-radius: 8px; max-height: 300px; overflow-y: auto; z-index: 100; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
                            </div>
                        </div>
                        <button type="button" class="btn btn-primary" onclick="pickFolder('backend')"
                            style="white-space: nowrap; padding: 12px 20px;">
                            <span>📁</span> Browse
                        </button>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 8px;">
                        <div class="helper-text" style="margin: 0;">💡 PHP API file will be saved here (Frontend will
                            connect automatically)</div>
                        <button type="button" onclick="saveCurrentFolder('backend')" class="btn btn-secondary"
                            style="padding: 6px 12px; font-size: 12px;">
                            <span>💾</span> Save Path
                        </button>
                    </div>
                </div>

                <div
                    style="margin-top: 12px; padding: 10px 12px; background: rgba(34,197,94,0.15); border-left: 4px solid rgba(34,197,94,0.5); border-radius: 6px; font-size: 11px; line-height: 1.6;">
                    <div style="font-weight: 600; color: #86efac; margin-bottom: 5px;">💡 How it works:</div>
                    <div style="opacity: 0.9;">
                        1️⃣ <strong>Domain</strong> = Where your backend is hosted<br>
                        2️⃣ <strong>Backend File</strong> = Auto-synced from "Backend Filename" above<br>
                        3️⃣ <strong>Final URL</strong> = Domain + "/" + Filename (Auto-combined)<br>
                        <div style="margin-top: 6px; padding: 6px; background: rgba(0,0,0,0.2); border-radius: 4px;">
                            📌 Example: <code style="color: #fbbf24;">http://localhost</code> + <code
                                style="color: #fbbf24;">api.php</code> = <code
                                style="color: #86efac;">http://localhost/api.php</code>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <div class="card-title" style="margin: 0;">🗄️ Database Connection</div>
                    <button type="button" id="showCredentialsBtn" onclick="showDatabaseCredentials()"
                        class="btn btn-secondary"
                        style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%); color: white; padding: 10px 20px; font-size: 13px; box-shadow: 0 4px 15px rgba(6, 182, 212, 0.4); display: none;"
                        onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(6, 182, 212, 0.6)'"
                        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(6, 182, 212, 0.4)'">
                        <span style="font-size: 16px;">🔑</span> Credentials
                    </button>
                </div>

                <div class="form-group">
                    <label class="form-label">Select Database</label>
                    <select id="databaseSelect" class="form-select" onchange="loadDatabaseInfo()">
                        <option value="">-- Select Database --</option>
                    </select>
                    <div class="helper-text">Choose from your saved connections</div>
                </div>

                <div id="dbInfo"
                    style="display: none; background: rgba(34, 197, 94, 0.15); border: 1px solid rgba(34, 197, 94, 0.4); border-radius: 8px; padding: 15px; margin-top: 15px;">
                    <div style="font-size: 13px; line-height: 1.8;">
                        <strong style="color: #86efac;">Selected Database:</strong><br>
                        <span id="dbInfoText"></span>
                    </div>
                </div>

                <div class="form-group" style="margin-top: 20px;">
                    <label class="form-label">Table Name</label>
                    <input type="text" id="tableName" class="form-input" placeholder="e.g., users, products, orders">
                    <div class="helper-text">Name for the table to be created</div>
                </div>

                <!-- Theme Code Section (Inside Database Connection Card) -->
                <div style="margin-top: 25px; padding-top: 20px; border-top: 2px solid rgba(251, 191, 36, 0.2);">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                        <span style="font-size: 20px;">🎨</span>
                        <span style="font-size: 16px; font-weight: bold; color: #fbbf24;">Custom Theme Code</span>
                        <span
                            style="font-size: 11px; opacity: 0.7; background: rgba(251,191,36,0.2); padding: 3px 8px; border-radius: 4px;">Optional</span>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Theme CSS & HTML</label>
                        <textarea id="themeCode" class="form-input" rows="6"
                            placeholder="Paste your custom theme CSS/HTML code here...&#10;&#10;Example:&#10;<style>&#10;  body { background: #your-color; }&#10;  .btn { border-radius: 10px; }&#10;</style>"
                            style="font-family: 'Consolas', 'Monaco', monospace; font-size: 12px; line-height: 1.5; resize: vertical; min-height: 120px;"></textarea>
                        <div class="helper-text">💡 Paste custom CSS/HTML to style the generated application</div>
                    </div>
                    <div style="display: flex; gap: 10px; margin-top: 12px;">
                        <button type="button" onclick="clearThemeCode()" class="btn btn-secondary"
                            style="flex: 1; padding: 8px 12px; font-size: 13px;">
                            <span>🗑️</span> Clear
                        </button>
                        <button type="button" onclick="copyThemeCode()" class="btn btn-primary"
                            style="flex: 1; padding: 8px 12px; font-size: 13px;">
                            <span>📋</span> Copy
                        </button>
                    </div>

                    <!-- AI Theme Extraction Prompts Section -->
                    <div style="margin-top: 20px; padding-top: 15px; border-top: 1px dashed rgba(251, 191, 36, 0.3);">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                            <span style="font-size: 16px;">🤖</span>
                            <span style="font-size: 13px; font-weight: 600; color: #a78bfa;">AI Theme Extraction
                                Prompts</span>
                        </div>
                        <div
                            style="font-size: 11px; opacity: 0.8; margin-bottom: 12px; line-height: 1.5; background: rgba(167, 139, 250, 0.1); padding: 8px 10px; border-radius: 6px; border-left: 3px solid rgba(167, 139, 250, 0.5);">
                            💡 Click to copy a prompt, then paste it to your AI assistant (Cursor, Claude, etc.) to
                            extract theme CSS/HTML
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <button type="button" onclick="copyExtractFromAppPrompt()" class="btn btn-secondary"
                                style="flex: 1; padding: 10px 12px; font-size: 12px; background: linear-gradient(135deg, rgba(139, 92, 246, 0.3) 0%, rgba(109, 40, 217, 0.3) 100%); border: 1px solid rgba(139, 92, 246, 0.5);"
                                onmouseover="this.style.background='linear-gradient(135deg, rgba(139, 92, 246, 0.5) 0%, rgba(109, 40, 217, 0.5) 100%)'"
                                onmouseout="this.style.background='linear-gradient(135deg, rgba(139, 92, 246, 0.3) 0%, rgba(109, 40, 217, 0.3) 100%)'">
                                <span>📁</span> Extract from App
                            </button>
                            <button type="button" onclick="copyExtractFromURLPrompt()" class="btn btn-secondary"
                                style="flex: 1; padding: 10px 12px; font-size: 12px; background: linear-gradient(135deg, rgba(236, 72, 153, 0.3) 0%, rgba(219, 39, 119, 0.3) 100%); border: 1px solid rgba(236, 72, 153, 0.5);"
                                onmouseover="this.style.background='linear-gradient(135deg, rgba(236, 72, 153, 0.5) 0%, rgba(219, 39, 119, 0.5) 100%)'"
                                onmouseout="this.style.background='linear-gradient(135deg, rgba(236, 72, 153, 0.3) 0%, rgba(219, 39, 119, 0.3) 100%)'">
                                <span>🔗</span> Extract from URL
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 2: Drag & Drop Table Builder -->
        <div class="card" style="grid-column: 1 / -1;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <div class="card-title" style="margin: 0;">🎨 Visual Table Builder - Drag & Drop Fields</div>
                <div id="fieldsCounter"
                    style="background: rgba(34,197,94,0.2); padding: 8px 16px; border-radius: 8px; border: 1px solid rgba(34,197,94,0.4); font-size: 14px; font-weight: bold;">
                    📊 0 Fields
                </div>
            </div>

            <div class="builder-container">
                <!-- Left: Fields Palette -->
                <div class="fields-palette">
                    <div class="palette-title">📦 Available Fields (Drag to build table)</div>

                    <div class="field-item" draggable="true" ondragstart="dragStart(event)"
                        data-field='{"type":"id","label":"ID","sqlType":"INT","length":"11","autoIncrement":true,"primaryKey":true,"notNull":true,"icon":"🔑"}'>
                        <span class="field-icon">🔑</span>
                        <div>
                            <div class="field-name">ID (Primary Key)</div>
                            <div class="field-type">INT AUTO_INCREMENT PRIMARY KEY</div>
                        </div>
                    </div>

                    <div class="field-item" draggable="true" ondragstart="dragStart(event)"
                        data-field='{"type":"text","label":"Text Field","sqlType":"VARCHAR","length":"255","icon":"📝"}'>
                        <span class="field-icon">📝</span>
                        <div>
                            <div class="field-name">Text Field</div>
                            <div class="field-type">VARCHAR(255)</div>
                        </div>
                    </div>

                    <div class="field-item" draggable="true" ondragstart="dragStart(event)"
                        data-field='{"type":"longtext","label":"Long Text","sqlType":"TEXT","icon":"📄"}'>
                        <span class="field-icon">📄</span>
                        <div>
                            <div class="field-name">Long Text</div>
                            <div class="field-type">TEXT</div>
                        </div>
                    </div>

                    <div class="field-item" draggable="true" ondragstart="dragStart(event)"
                        data-field='{"type":"number","label":"Number","sqlType":"INT","length":"11","icon":"🔢"}'>
                        <span class="field-icon">🔢</span>
                        <div>
                            <div class="field-name">Number</div>
                            <div class="field-type">INT(11)</div>
                        </div>
                    </div>

                    <div class="field-item" draggable="true" ondragstart="dragStart(event)"
                        data-field='{"type":"decimal","label":"Decimal","sqlType":"DECIMAL","length":"10,2","icon":"💰"}'>
                        <span class="field-icon">💰</span>
                        <div>
                            <div class="field-name">Decimal/Price</div>
                            <div class="field-type">DECIMAL(10,2)</div>
                        </div>
                    </div>

                    <div class="field-item" draggable="true" ondragstart="dragStart(event)"
                        data-field='{"type":"date","label":"Date","sqlType":"DATE","icon":"📅"}'>
                        <span class="field-icon">📅</span>
                        <div>
                            <div class="field-name">Date</div>
                            <div class="field-type">DATE</div>
                        </div>
                    </div>

                    <div class="field-item" draggable="true" ondragstart="dragStart(event)"
                        data-field='{"type":"datetime","label":"DateTime","sqlType":"DATETIME","icon":"🕐"}'>
                        <span class="field-icon">🕐</span>
                        <div>
                            <div class="field-name">DateTime</div>
                            <div class="field-type">DATETIME</div>
                        </div>
                    </div>

                    <div class="field-item" draggable="true" ondragstart="dragStart(event)"
                        data-field='{"type":"timestamp","label":"Timestamp","sqlType":"TIMESTAMP","defaultValue":"CURRENT_TIMESTAMP","icon":"⏰"}'>
                        <span class="field-icon">⏰</span>
                        <div>
                            <div class="field-name">Timestamp</div>
                            <div class="field-type">TIMESTAMP (auto)</div>
                        </div>
                    </div>

                    <div class="field-item" draggable="true" ondragstart="dragStart(event)"
                        data-field='{"type":"boolean","label":"Boolean","sqlType":"TINYINT","length":"1","icon":"✅"}'>
                        <span class="field-icon">✅</span>
                        <div>
                            <div class="field-name">Boolean (Yes/No)</div>
                            <div class="field-type">TINYINT(1)</div>
                        </div>
                    </div>

                    <div class="field-item" draggable="true" ondragstart="dragStart(event)"
                        data-field='{"type":"email","label":"Email","sqlType":"VARCHAR","length":"100","icon":"📧"}'>
                        <span class="field-icon">📧</span>
                        <div>
                            <div class="field-name">Email</div>
                            <div class="field-type">VARCHAR(100)</div>
                        </div>
                    </div>

                    <div class="field-item" draggable="true" ondragstart="dragStart(event)"
                        data-field='{"type":"phone","label":"Phone","sqlType":"VARCHAR","length":"20","icon":"📞"}'>
                        <span class="field-icon">📞</span>
                        <div>
                            <div class="field-name">Phone Number</div>
                            <div class="field-type">VARCHAR(20)</div>
                        </div>
                    </div>

                    <div class="field-item" draggable="true" ondragstart="dragStart(event)"
                        data-field='{"type":"password","label":"Password","sqlType":"VARCHAR","length":"255","icon":"🔐"}'>
                        <span class="field-icon">🔐</span>
                        <div>
                            <div class="field-name">Password</div>
                            <div class="field-type">VARCHAR(255)</div>
                        </div>
                    </div>

                    <div class="field-item" draggable="true" ondragstart="dragStart(event)"
                        data-field='{"type":"url","label":"URL","sqlType":"VARCHAR","length":"500","icon":"🌐"}'>
                        <span class="field-icon">🌐</span>
                        <div>
                            <div class="field-name">URL/Link</div>
                            <div class="field-type">VARCHAR(500)</div>
                        </div>
                    </div>

                    <div class="field-item" draggable="true" ondragstart="dragStart(event)"
                        data-field='{"type":"image","label":"Image Path","sqlType":"VARCHAR","length":"500","icon":"🖼️"}'>
                        <span class="field-icon">🖼️</span>
                        <div>
                            <div class="field-name">Image/File Path</div>
                            <div class="field-type">VARCHAR(500)</div>
                        </div>
                    </div>

                    <div class="field-item" draggable="true" ondragstart="dragStart(event)"
                        data-field='{"type":"json","label":"JSON Data","sqlType":"TEXT","icon":"📦"}'>
                        <span class="field-icon">📦</span>
                        <div>
                            <div class="field-name">JSON Data</div>
                            <div class="field-type">TEXT</div>
                        </div>
                    </div>

                    <div class="field-item" draggable="true" ondragstart="dragStart(event)"
                        data-field='{"type":"status","label":"Status","sqlType":"VARCHAR","length":"50","defaultValue":"active","icon":"⚡"}'>
                        <span class="field-icon">⚡</span>
                        <div>
                            <div class="field-name">Status</div>
                            <div class="field-type">VARCHAR(50)</div>
                        </div>
                    </div>

                    <div class="field-item" draggable="true" ondragstart="dragStart(event)"
                        data-field='{"type":"slug","label":"Slug/URI","sqlType":"VARCHAR","length":"255","unique":true,"icon":"🔗"}'>
                        <span class="field-icon">🔗</span>
                        <div>
                            <div class="field-name">Slug/URI</div>
                            <div class="field-type">VARCHAR(255) UNIQUE</div>
                        </div>
                    </div>

                    <div class="field-item" draggable="true" ondragstart="dragStart(event)"
                        data-field='{"type":"created_at","label":"Created At","sqlType":"TIMESTAMP","defaultValue":"CURRENT_TIMESTAMP","notNull":true,"icon":"📅"}'>
                        <span class="field-icon">📅</span>
                        <div>
                            <div class="field-name">Created At</div>
                            <div class="field-type">TIMESTAMP (auto)</div>
                        </div>
                    </div>

                    <div class="field-item" draggable="true" ondragstart="dragStart(event)"
                        data-field='{"type":"updated_at","label":"Updated At","sqlType":"TIMESTAMP","defaultValue":"CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP","notNull":true,"icon":"🔄"}'>
                        <span class="field-icon">🔄</span>
                        <div>
                            <div class="field-name">Updated At</div>
                            <div class="field-type">TIMESTAMP (auto-update)</div>
                        </div>
                    </div>
                </div>

                <!-- Right: Table Builder -->
                <div class="table-builder" id="tableBuilder" ondrop="drop(event)" ondragover="allowDrop(event)"
                    ondragleave="dragLeave(event)">
                    <div id="builderContent" class="builder-empty">
                        <div class="builder-empty-icon">📋</div>
                        <div style="font-size: 18px; margin-bottom: 10px;">Drag fields here to build your table</div>
                        <div style="font-size: 14px;">Start by dragging an ID field, then add other fields</div>
                    </div>
                </div>
            </div>

            <div style="display: flex; gap: 15px; margin-top: 20px; flex-wrap: wrap; align-items: center;">
                <button class="btn btn-danger" onclick="clearTable()">
                    <span>🗑️</span> Clear All
                </button>
                <button class="btn btn-secondary" onclick="previewSQL()">
                    <span>👁️</span> Preview SQL
                </button>
                <button class="btn btn-secondary" onclick="addQuickTemplate()">
                    <span>⚡</span> Quick Template (ID + Name + Timestamps)
                </button>

                <!-- Table Templates Search & Dropdown -->
                <div style="display: flex; gap: 10px; align-items: center; flex: 1; min-width: 400px;">
                    <input type="text" id="templateSearchBox" placeholder="🔍 Search table templates..."
                        oninput="filterTableTemplates()" class="form-input"
                        style="flex: 1; padding: 10px 15px; font-size: 13px;">
                    <div style="position: relative; flex: 1;">
                        <button class="btn btn-primary" onclick="toggleTemplateDropdown()"
                            style="width: 100%; justify-content: space-between;">
                            <span>📋 Table Templates</span>
                            <span id="templateDropdownArrow" style="transition: transform 0.3s;">▼</span>
                        </button>
                        <div id="tableTemplatesDropdown"
                            style="display: none; position: absolute; top: 100%; left: 0; right: 0; margin-top: 8px; background: rgba(0,0,0,0.95); border: 2px solid rgba(251,191,36,0.5); border-radius: 12px; max-height: 500px; overflow-y: auto; z-index: 1000; box-shadow: 0 15px 50px rgba(0,0,0,0.7);">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Custom AI Instructions Section -->
        <div class="card" style="background: linear-gradient(135deg, rgba(139, 92, 246, 0.15) 0%, rgba(109, 40, 217, 0.1) 100%); border: 2px solid rgba(139, 92, 246, 0.3); margin-top: 0;">
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
                <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; box-shadow: 0 4px 15px rgba(139, 92, 246, 0.4);">
                    📝
                </div>
                <div style="flex: 1;">
                    <div class="card-title" style="margin: 0; color: #a78bfa; font-size: 18px;">Custom AI Instructions</div>
                    <div style="font-size: 12px; color: rgba(167, 139, 250, 0.8); margin-top: 3px;">Add your own instructions to be included in the generated AI prompt</div>
                </div>
                <button type="button" onclick="clearCustomInstructions()" class="btn btn-secondary" style="padding: 8px 16px; font-size: 12px; background: rgba(239, 68, 68, 0.2); border-color: rgba(239, 68, 68, 0.4);">
                    <span>🗑️</span> Clear
                </button>
            </div>
            
            <textarea id="customAIInstructions" class="form-input" rows="4" 
                placeholder="💡 Write additional instructions for the AI here...

Examples:
• Use Tailwind CSS instead of custom CSS
• Add dark/light mode toggle
• Include pagination for the data table
• Add export to CSV functionality
• Use Bootstrap 5 for styling
• Make the design minimalist
• Add form validation with specific rules
• Include user authentication"
                style="font-family: 'Consolas', 'Monaco', monospace; font-size: 13px; line-height: 1.6; resize: vertical; min-height: 100px; background: rgba(0,0,0,0.3); border: 2px solid rgba(139, 92, 246, 0.3); color: #e9d5ff; transition: all 0.3s;"
                onfocus="this.style.borderColor='rgba(139, 92, 246, 0.6)'; this.style.boxShadow='0 0 0 3px rgba(139, 92, 246, 0.2)'"
                onblur="this.style.borderColor='rgba(139, 92, 246, 0.3)'; this.style.boxShadow='none'"></textarea>
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 12px;">
                <div style="font-size: 11px; color: rgba(167, 139, 250, 0.7);">
                    💡 These instructions will be added to the AI prompt when you click "Generate AI Prompt"
                </div>
                <div id="instructionsCharCount" style="font-size: 11px; color: rgba(167, 139, 250, 0.6);">
                    0 characters
                </div>
            </div>
        </div>

        <!-- Generate & Refresh Buttons -->
        <div style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
            <button class="generate-btn" onclick="generateApplication()" style="flex: 1; min-width: 300px;">
                <span style="font-size: 24px;">✨</span> Generate Complete Application
            </button>
            <button class="generate-btn" onclick="generateAIPrompt()" style="flex: 1; min-width: 300px; background: linear-gradient(135deg, #22c55e 0%, #15803d 100%); box-shadow: 0 8px 30px rgba(34, 197, 94, 0.5);" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 12px 40px rgba(34, 197, 94, 0.6)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 8px 30px rgba(34, 197, 94, 0.5)'">
                <span style="font-size: 24px;">🤖</span> Generate AI Prompt
            </button>
            <button class="refresh-btn" onclick="refreshPage()" title="Refresh Page">
                <span style="font-size: 28px;">🔄</span>
            </button>
        </div>
    </div>

    <!-- AI Prompt Modal - Draggable & Resizable -->
    <div id="aiPromptModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); backdrop-filter: blur(10px); z-index: 5000;">
        <div id="aiPromptModalContent" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: linear-gradient(135deg, rgba(34, 197, 94, 0.98) 0%, rgba(21, 128, 61, 0.98) 100%); border-radius: 20px; padding: 0; width: 80%; min-width: 400px; max-width: 95vw; height: 70vh; min-height: 300px; max-height: 95vh; display: flex; flex-direction: column; box-shadow: 0 25px 80px rgba(0,0,0,0.7), 0 0 0 1px rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.3); overflow: hidden;">
            <!-- Draggable Header -->
            <div id="aiPromptModalHeader" style="display: flex; justify-content: space-between; align-items: center; padding: 18px 25px; background: rgba(0,0,0,0.3); border-bottom: 2px solid rgba(255,255,255,0.2); cursor: move; user-select: none;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <span style="font-size: 36px;">🤖</span>
                    <div>
                        <h3 style="color: #fff; font-size: 22px; margin: 0; font-weight: 700; text-shadow: 0 2px 4px rgba(0,0,0,0.3);">AI Prompt Generated</h3>
                        <p style="color: rgba(255,255,255,0.8); font-size: 12px; margin: 3px 0 0 0;">🖱️ Drag header to move • Drag edges to resize</p>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <!-- Maximize/Restore Button -->
                    <button onclick="toggleAIPromptModalMaximize()" id="aiPromptMaximizeBtn" style="background: rgba(59,130,246,0.4); border: 1px solid rgba(59,130,246,0.6); width: 36px; height: 36px; border-radius: 8px; color: #fff; font-size: 18px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.3s;" title="Maximize/Restore" onmouseover="this.style.background='rgba(59,130,246,0.7)'" onmouseout="this.style.background='rgba(59,130,246,0.4)'">⬜</button>
                    <!-- Close Button -->
                    <button onclick="closeAIPromptModal()" style="background: rgba(239,68,68,0.4); border: 1px solid rgba(239,68,68,0.6); width: 36px; height: 36px; border-radius: 8px; color: #fff; font-size: 22px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.3s;" title="Close" onmouseover="this.style.background='rgba(239,68,68,0.7)'; this.style.transform='rotate(90deg)'" onmouseout="this.style.background='rgba(239,68,68,0.4)'; this.style.transform='rotate(0deg)'">×</button>
                </div>
            </div>
            
            <!-- Prompt Content -->
            <div style="flex: 1; overflow: hidden; display: flex; flex-direction: column; padding: 20px 25px;">
                <textarea id="aiPromptContent" readonly style="flex: 1; width: 100%; padding: 20px; background: rgba(0,0,0,0.5); border: 2px solid rgba(255,255,255,0.15); border-radius: 12px; color: #fff; font-family: 'Consolas', 'Monaco', 'Fira Code', monospace; font-size: 13px; line-height: 1.7; resize: none; outline: none; box-shadow: inset 0 4px 12px rgba(0,0,0,0.3);"></textarea>
            </div>
            
            <!-- Action Buttons -->
            <div style="display: flex; gap: 15px; padding: 0 25px 20px 25px;">
                <button onclick="closeAIPromptModal()" style="flex: 1; padding: 14px; background: rgba(239,68,68,0.3); border: 1px solid rgba(239,68,68,0.5); border-radius: 10px; color: #fff; cursor: pointer; font-weight: 700; font-size: 14px; transition: all 0.3s;" onmouseover="this.style.background='rgba(239,68,68,0.5)'" onmouseout="this.style.background='rgba(239,68,68,0.3)'">
                    ✕ Close
                </button>
                <button onclick="copyAIPrompt()" style="flex: 2; padding: 14px; background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); border: none; border-radius: 10px; color: #000; cursor: pointer; font-weight: 700; font-size: 14px; box-shadow: 0 4px 15px rgba(251,191,36,0.4); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(251,191,36,0.6)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(251,191,36,0.4)'">
                    📋 Copy Prompt to Clipboard
                </button>
            </div>
            
            <!-- Info -->
            <div style="padding: 0 25px 20px 25px;">
                <div style="padding: 12px; background: rgba(255,255,255,0.1); border-radius: 8px; font-size: 12px; color: rgba(255,255,255,0.85); text-align: center;">
                    💡 <strong>Tip:</strong> Paste this prompt in Cursor AI, Claude, or ChatGPT to generate the exact same application!
                </div>
            </div>
            
            <!-- Resize Handles -->
            <div class="resize-handle resize-n" data-resize="n" style="position: absolute; top: 0; left: 10px; right: 10px; height: 8px; cursor: n-resize;"></div>
            <div class="resize-handle resize-s" data-resize="s" style="position: absolute; bottom: 0; left: 10px; right: 10px; height: 8px; cursor: s-resize;"></div>
            <div class="resize-handle resize-e" data-resize="e" style="position: absolute; right: 0; top: 10px; bottom: 10px; width: 8px; cursor: e-resize;"></div>
            <div class="resize-handle resize-w" data-resize="w" style="position: absolute; left: 0; top: 10px; bottom: 10px; width: 8px; cursor: w-resize;"></div>
            <div class="resize-handle resize-ne" data-resize="ne" style="position: absolute; top: 0; right: 0; width: 16px; height: 16px; cursor: ne-resize;"></div>
            <div class="resize-handle resize-nw" data-resize="nw" style="position: absolute; top: 0; left: 0; width: 16px; height: 16px; cursor: nw-resize;"></div>
            <div class="resize-handle resize-se" data-resize="se" style="position: absolute; bottom: 0; right: 0; width: 16px; height: 16px; cursor: se-resize;"></div>
            <div class="resize-handle resize-sw" data-resize="sw" style="position: absolute; bottom: 0; left: 0; width: 16px; height: 16px; cursor: sw-resize;"></div>
        </div>
    </div>

    <!-- File Browser Modal -->
    <div id="fileBrowserModal"
        style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); backdrop-filter: blur(8px); z-index: 3000; align-items: center; justify-content: center;">
        <div
            style="background: linear-gradient(135deg, rgba(102,126,234,0.98) 0%, rgba(118,75,162,0.98) 100%); border-radius: 20px; padding: 25px; width: 95%; max-width: 1000px; height: 90vh; display: flex; flex-direction: column; box-shadow: 0 25px 80px rgba(0,0,0,0.6); border: 2px solid rgba(255,255,255,0.2);">
            <!-- Header -->
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid rgba(255,255,255,0.2);">
                <h3 style="color: #fbbf24; font-size: 24px; margin: 0; font-weight: 700;">🗂️ File Browser - Select
                    Folder</h3>
                <button onclick="closeFileBrowser()"
                    style="background: rgba(239,68,68,0.3); border: 1px solid rgba(239,68,68,0.5); color: #fff; width: 35px; height: 35px; border-radius: 50%; font-size: 22px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.3s;"
                    onmouseover="this.style.background='rgba(239,68,68,0.6)'; this.style.transform='rotate(90deg)'"
                    onmouseout="this.style.background='rgba(239,68,68,0.3)'; this.style.transform='rotate(0deg)'">×</button>
            </div>

            <!-- Navigation Bar -->
            <div style="display: flex; gap: 10px; margin-bottom: 15px; align-items: center;">
                <button onclick="navigateBack()" id="backBtn" disabled
                    style="padding: 10px 16px; background: rgba(59,130,246,0.3); border: 1px solid rgba(59,130,246,0.5); border-radius: 8px; color: #fff; cursor: pointer; font-weight: 600; font-size: 14px; transition: all 0.3s;">
                    ⬅️ Back
                </button>
                <button onclick="navigateUp()" id="upBtn"
                    style="padding: 10px 16px; background: rgba(245,158,11,0.3); border: 1px solid rgba(245,158,11,0.5); border-radius: 8px; color: #fff; cursor: pointer; font-weight: 600; font-size: 14px; transition: all 0.3s;">
                    ⬆️ Up
                </button>
                <div id="currentPathBar"
                    style="flex: 1; padding: 10px 15px; background: rgba(0,0,0,0.4); border-radius: 8px; font-family: 'Consolas', monospace; font-size: 14px; color: #fbbf24; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; border: 1px solid rgba(251,191,36,0.3);">
                    C:\
                </div>
                <button onclick="refreshBrowser()"
                    style="padding: 10px 16px; background: rgba(34,197,94,0.3); border: 1px solid rgba(34,197,94,0.5); border-radius: 8px; color: #fff; cursor: pointer; font-weight: 600; font-size: 14px; transition: all 0.3s;">
                    🔄
                </button>
            </div>

            <!-- Quick Drives -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 15px;">
                <button onclick="browseToPath('C:\\')"
                    style="padding: 15px; background: rgba(59,130,246,0.25); border: 2px solid rgba(59,130,246,0.5); border-radius: 10px; color: #fff; cursor: pointer; font-size: 15px; font-weight: 700; transition: all 0.3s;"
                    onmouseover="this.style.transform='scale(1.03)'; this.style.background='rgba(59,130,246,0.4)'; this.style.boxShadow='0 4px 15px rgba(59,130,246,0.4)'"
                    onmouseout="this.style.transform='scale(1)'; this.style.background='rgba(59,130,246,0.25)'; this.style.boxShadow='none'">💿
                    C:\ Drive</button>
                <button onclick="browseToPath('C:\\laragon\\')"
                    style="padding: 15px; background: rgba(34,197,94,0.25); border: 2px solid rgba(34,197,94,0.5); border-radius: 10px; color: #fff; cursor: pointer; font-size: 15px; font-weight: 700; transition: all 0.3s;"
                    onmouseover="this.style.transform='scale(1.03)'; this.style.background='rgba(34,197,94,0.4)'; this.style.boxShadow='0 4px 15px rgba(34,197,94,0.4)'"
                    onmouseout="this.style.transform='scale(1)'; this.style.background='rgba(34,197,94,0.25)'; this.style.boxShadow='none'">🖥️
                    Laragon</button>
            </div>

            <!-- Folders List -->
            <div id="foldersList"
                style="flex: 1; background: rgba(0,0,0,0.35); border-radius: 12px; border: 2px solid rgba(255,255,255,0.15); overflow-y: auto; padding: 15px;">
                <div style="text-align: center; padding: 80px 20px; color: rgba(255,255,255,0.5);">
                    <div style="font-size: 72px; margin-bottom: 20px;">📂</div>
                    <div style="font-size: 18px; font-weight: 600; margin-bottom: 10px;">Select a location to start
                        browsing</div>
                    <div style="font-size: 14px; opacity: 0.7;">Click C:\ Drive or Laragon WWW above</div>
                </div>
            </div>

            <!-- Selected Path -->
            <div
                style="margin-top: 15px; padding: 15px; background: rgba(34,197,94,0.2); border: 2px solid rgba(34,197,94,0.4); border-radius: 10px;">
                <div style="font-size: 13px; color: #86efac; margin-bottom: 8px; font-weight: 600;">✅ Selected
                    Destination Folder:</div>
                <div id="selectedPath"
                    style="font-family: 'Consolas', monospace; font-size: 15px; color: #fff; font-weight: 600; word-break: break-all;">
                    No folder selected yet</div>
            </div>

            <!-- Action Buttons -->
            <div style="display: flex; gap: 12px; margin-top: 15px;">
                <button onclick="closeFileBrowser()"
                    style="flex: 1; padding: 14px; background: rgba(239,68,68,0.3); border: 1px solid rgba(239,68,68,0.5); border-radius: 10px; color: #fff; cursor: pointer; font-weight: 700; font-size: 15px; transition: all 0.3s;"
                    onmouseover="this.style.background='rgba(239,68,68,0.5)'"
                    onmouseout="this.style.background='rgba(239,68,68,0.3)'">
                    ❌ Cancel
                </button>
                <button onclick="selectCurrentFolder()" id="selectBtn" disabled
                    style="flex: 2; padding: 14px; background: linear-gradient(135deg, #22c55e 0%, #15803d 100%); border: none; border-radius: 10px; color: #fff; cursor: pointer; font-weight: 700; font-size: 16px; box-shadow: 0 4px 15px rgba(34,197,94,0.4); transition: all 0.3s;"
                    onmouseover="if(!this.disabled) this.style.transform='translateY(-2px)'; if(!this.disabled) this.style.boxShadow='0 6px 20px rgba(34,197,94,0.6)'"
                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(34,197,94,0.4)'">
                    ✅ Select This Folder
                </button>
            </div>
        </div>
    </div>

    <script>
    // Load databases from localStorage (from dashboard.html)
    let droppedFields = [];
    let selectedDatabase = null;

    // Folder picker constants
    const SAVED_FRONTEND_FOLDERS_KEY = 'appmaker_recent_frontend_folders';
    const SAVED_BACKEND_FOLDERS_KEY = 'appmaker_recent_backend_folders';
    const SAVED_API_URLS_KEY = 'appmaker_recent_api_urls';
    const SAVED_API_DOMAINS_KEY = 'appmaker_recent_api_domains';
    const MAX_RECENT_FOLDERS = 10;
    const MAX_RECENT_URLS = 15;
    const MAX_RECENT_DOMAINS = 10;

    document.addEventListener('DOMContentLoaded', function() {
        loadDatabases();
        loadRecentFolders('frontend');
        loadRecentFolders('backend');
        loadRecentApiUrls();
        loadRecentDomains();

        // Auto-generate API URL on page load
        autoGenerateApiUrl();

        // Watch for Backend Filename changes to sync with API builder
        document.getElementById('backendName').addEventListener('input', function() {
            const filename = this.value.trim();
            document.getElementById('apiFilename').value = filename;
            buildApiUrl();
        });

        // Watch for Backend Folder changes to update domain
        document.getElementById('backendFolder').addEventListener('change', function() {
            autoGenerateApiUrl();
        });

        // Initialize API filename from backend name
        const initialFilename = document.getElementById('backendName').value.trim();
        if (initialFilename) {
            document.getElementById('apiFilename').value = initialFilename;
        }

        // Build API URL on page load
        buildApiUrl();

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            const frontendDropdown = document.getElementById('frontendFolderDropdown');
            const backendDropdown = document.getElementById('backendFolderDropdown');
            const apiUrlDropdown = document.getElementById('apiUrlDropdown');
            const domainDropdown = document.getElementById('domainDropdown');
            const templatesDropdown = document.getElementById('tableTemplatesDropdown');
            const frontendInput = document.getElementById('frontendFolder');
            const backendInput = document.getElementById('backendFolder');
            const apiUrlInput = document.getElementById('backendApiUrl');
            const domainInput = document.getElementById('apiDomain');
            const templateSearchBox = document.getElementById('templateSearchBox');
            const toggleBtn = e.target.closest('button');

            if (frontendDropdown && !frontendDropdown.contains(e.target) && e.target !==
                frontendInput && (!toggleBtn || toggleBtn.onclick.toString().indexOf('frontend') === -1)
            ) {
                frontendDropdown.style.display = 'none';
            }

            if (backendDropdown && !backendDropdown.contains(e.target) && e.target !== backendInput && (
                    !toggleBtn || toggleBtn.onclick.toString().indexOf('backend') === -1)) {
                backendDropdown.style.display = 'none';
            }

            if (apiUrlDropdown && !apiUrlDropdown.contains(e.target) && e.target !== apiUrlInput && (!
                    toggleBtn || toggleBtn.onclick.toString().indexOf('apiUrl') === -1)) {
                apiUrlDropdown.style.display = 'none';
            }

            if (domainDropdown && !domainDropdown.contains(e.target) && e.target !== domainInput && (!
                    toggleBtn || toggleBtn.onclick.toString().indexOf('Domain') === -1)) {
                domainDropdown.style.display = 'none';
            }

            // Close templates dropdown when clicking outside
            if (templatesDropdown && !templatesDropdown.contains(e.target) && e.target !==
                templateSearchBox && (!toggleBtn || toggleBtn.onclick.toString().indexOf(
                    'toggleTemplateDropdown') === -1)) {
                templatesDropdown.style.display = 'none';
                const arrow = document.getElementById('templateDropdownArrow');
                if (arrow) arrow.style.transform = 'rotate(0deg)';
            }
        });
    });

    // ========================================
    // API URL MANAGEMENT
    // ========================================

    // Build API URL from Domain + Filename
    function buildApiUrl() {
        const domain = document.getElementById('apiDomain').value.trim();
        const filename = document.getElementById('apiFilename').value.trim();

        if (!domain || !filename) {
            console.log('⚠️ Domain or filename not set');
            document.getElementById('backendApiUrl').value = '';
            return;
        }

        let url = domain;

        // Ensure domain doesn't end with slash
        url = url.replace(/\/+$/, '');

        // Add slash before filename
        url += '/' + filename;

        // Set the final URL
        document.getElementById('backendApiUrl').value = url;

        console.log('🔗 Built API URL:', url);
        console.log('   Domain:', domain);
        console.log('   Filename:', filename);

        // Visual feedback
        const input = document.getElementById('backendApiUrl');
        input.style.background = 'rgba(34, 197, 94, 0.15)';
        input.style.borderColor = '#22c55e';

        setTimeout(() => {
            input.style.background = 'rgba(34, 197, 94, 0.1)';
            input.style.borderColor = 'rgba(34, 197, 94, 0.4)';
        }, 400);
    }

    // Auto-generate API URL using smart folder detection
    function autoGenerateApiUrl() {
        const backendFolder = document.getElementById('backendFolder').value.trim();
        const backendName = document.getElementById('backendName').value.trim();

        if (!backendFolder || !backendName) {
            console.log('⚠️ Backend folder or name not set yet');
            return;
        }

        // Generate domain from backend folder
        let domain = 'http://localhost';

        // Extract relative path from backend folder
        if (backendFolder.toLowerCase().includes('laragon\\www\\')) {
            const relativePath = backendFolder.split(/laragon\\www\\/i)[1];
            domain = 'http://localhost/' + relativePath.replace(/\\/g, '/').replace(/\/+$/, '');
        } else if (backendFolder.toLowerCase().includes('xampp\\htdocs\\')) {
            const relativePath = backendFolder.split(/xampp\\htdocs\\/i)[1];
            domain = 'http://localhost/' + relativePath.replace(/\\/g, '/').replace(/\/+$/, '');
        } else if (backendFolder.toLowerCase().includes('wamp\\www\\')) {
            const relativePath = backendFolder.split(/wamp\\www\\/i)[1];
            domain = 'http://localhost/' + relativePath.replace(/\\/g, '/').replace(/\/+$/, '');
        }

        // Set domain and filename
        document.getElementById('apiDomain').value = domain;
        document.getElementById('apiFilename').value = backendName;

        // Build the final URL
        buildApiUrl();

        console.log('🤖 Auto-generated from backend folder');
    }

    // Generate Backend URL - Smart detection (internal function)
    function generateBackendUrl(frontendFolder, backendFolder, backendName) {
        if (!backendFolder || !backendName) return '';

        // Normalize paths for comparison
        const normalizePath = (path) => path.toLowerCase().replace(/\\/g, '/').replace(/\/+$/, '');
        const normalizedFrontend = normalizePath(frontendFolder || '');
        const normalizedBackend = normalizePath(backendFolder);

        // If same folder, use relative path (just filename)
        if (normalizedFrontend && normalizedFrontend === normalizedBackend) {
            console.log('✅ Same folder detected - using relative path:', backendName);
            return backendName;
        }

        // Check if Backend is subfolder of Frontend
        if (normalizedFrontend && normalizedBackend.startsWith(normalizedFrontend + '/')) {
            const relativePath = normalizedBackend.substring(normalizedFrontend.length + 1);
            const relativeUrl = relativePath + '/' + backendName;
            console.log('✅ Backend is subfolder - using relative path:', relativeUrl);
            return relativeUrl;
        }

        // Different folders - generate absolute URL
        let url = 'http://localhost/';

        // Extract relative path from common PHP server roots
        if (backendFolder.toLowerCase().includes('laragon\\www\\')) {
            const relativePath = backendFolder.split(/laragon\\www\\/i)[1];
            url += relativePath.replace(/\\/g, '/');
        } else if (backendFolder.toLowerCase().includes('xampp\\htdocs\\')) {
            const relativePath = backendFolder.split(/xampp\\htdocs\\/i)[1];
            url += relativePath.replace(/\\/g, '/');
        } else if (backendFolder.toLowerCase().includes('wamp\\www\\')) {
            const relativePath = backendFolder.split(/wamp\\www\\/i)[1];
            url += relativePath.replace(/\\/g, '/');
        } else {
            // Fallback: just use filename (relative)
            console.log('⚠️ Unknown path structure - using relative path as fallback');
            return backendName;
        }

        // Ensure trailing slash
        if (!url.endsWith('/')) url += '/';

        // Add filename
        url += backendName;

        console.log('🌐 Generated absolute URL:', url);
        return url;
    }

    // Load recent API URLs
    function loadRecentApiUrls() {
        const saved = localStorage.getItem(SAVED_API_URLS_KEY);
        const urls = saved ? JSON.parse(saved) : [];

        renderApiUrlDropdown(urls);
    }

    // Save API URL to recent list
    function saveApiUrl(url) {
        if (!url || url.trim() === '') return;

        const trimmedUrl = url.trim();
        let urls = JSON.parse(localStorage.getItem(SAVED_API_URLS_KEY) || '[]');

        // Remove if already exists
        urls = urls.filter(u => u !== trimmedUrl);

        // Add to beginning
        urls.unshift(trimmedUrl);

        // Keep only MAX_RECENT_URLS
        if (urls.length > MAX_RECENT_URLS) {
            urls = urls.slice(0, MAX_RECENT_URLS);
        }

        localStorage.setItem(SAVED_API_URLS_KEY, JSON.stringify(urls));
        renderApiUrlDropdown(urls);
    }

    // Save current API URL manually
    function saveCurrentApiUrl() {
        const url = document.getElementById('backendApiUrl').value.trim();

        if (!url) {
            showToastMessage('⚠️ Please enter an API URL first!', 'error');
            return;
        }

        saveApiUrl(url);
        showToastMessage('✅ API URL saved to recent list!', 'success');

        // Visual feedback
        const input = document.getElementById('backendApiUrl');
        input.style.background = 'rgba(34, 197, 94, 0.2)';
        input.style.borderColor = '#22c55e';
        input.style.boxShadow = '0 0 15px rgba(34, 197, 94, 0.3)';

        setTimeout(() => {
            input.style.background = '';
            input.style.borderColor = '';
            input.style.boxShadow = '';
        }, 1000);
    }

    // Handle API URL changes
    function handleBackendUrlChange() {
        // Auto-save on blur will handle this
    }

    // Toggle API URL dropdown
    function toggleApiUrlDropdown() {
        const dropdown = document.getElementById('apiUrlDropdown');

        if (dropdown.style.display === 'none') {
            dropdown.style.display = 'block';
            dropdown.style.animation = 'slideDown 0.3s ease-out';
        } else {
            dropdown.style.animation = 'slideUp 0.2s ease-out';
            setTimeout(() => {
                dropdown.style.display = 'none';
            }, 200);
        }
    }

    // Render API URL dropdown
    function renderApiUrlDropdown(urls) {
        const dropdown = document.getElementById('apiUrlDropdown');
        const currentUrl = document.getElementById('backendApiUrl').value;

        if (urls.length === 0) {
            dropdown.innerHTML = `
            <div style="padding: 20px; text-align: center; color: rgba(255,255,255,0.5);">
                <div style="font-size: 48px; margin-bottom: 10px;">🌐</div>
                <div style="font-size: 13px;">No recent URLs yet</div>
                <div style="font-size: 11px; margin-top: 5px; opacity: 0.7;">URLs will be saved automatically</div>
            </div>
        `;
            return;
        }

        let html = `
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; background: rgba(34, 197, 94, 0.2); border-bottom: 2px solid rgba(34, 197, 94, 0.4);">
            <span style="font-size: 11px; color: #86efac; font-weight: 600; letter-spacing: 0.5px;">🌐 RECENT API URLs (${urls.length})</span>
            <button onclick="clearAllApiUrls(); event.stopPropagation();" 
                    style="background: rgba(239, 68, 68, 0.3); border: none; border-radius: 5px; padding: 3px 10px; cursor: pointer; color: #fef3c7; font-size: 10px; font-weight: 600;"
                    onmouseover="this.style.background='rgba(239, 68, 68, 0.6)'"
                    onmouseout="this.style.background='rgba(239, 68, 68, 0.3)'">
                🗑️ CLEAR ALL
            </button>
        </div>
    `;

        urls.forEach(url => {
            const isCurrent = url === currentUrl;
            const escapedUrl = url.replace(/\\/g, '\\\\').replace(/'/g, "\\'");
            html += `
            <div onclick="selectApiUrl('${escapedUrl}'); event.stopPropagation();" 
                 style="padding: 10px 15px; cursor: pointer; color: #fef3c7; font-family: monospace; font-size: 12px; border-bottom: 1px solid rgba(34,197,94,0.1); transition: all 0.2s; display: flex; align-items: center; justify-content: space-between; ${isCurrent ? 'background: rgba(34, 197, 94, 0.2); border-left: 3px solid #22c55e;' : ''}"
                 onmouseover="this.style.background='rgba(34,197,94,0.2)'"
                 onmouseout="this.style.background='${isCurrent ? 'rgba(34, 197, 94, 0.2)' : ''}'">
                <span style="flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; padding-right: 10px;" title="${url}">
                    ${isCurrent ? '✓ ' : ''}${url}
                </span>
                <button onclick="deleteApiUrl('${escapedUrl}'); event.stopPropagation();" 
                        style="background: rgba(239, 68, 68, 0.3); border: none; border-radius: 5px; padding: 3px 8px; cursor: pointer; color: #fef3c7; font-size: 11px; flex-shrink: 0;"
                        onmouseover="this.style.background='rgba(239, 68, 68, 0.6)'"
                        onmouseout="this.style.background='rgba(239, 68, 68, 0.3)'"
                        title="Remove from list">
                    ✕
                </button>
            </div>
        `;
        });

        dropdown.innerHTML = html;
    }

    // Select API URL from dropdown
    function selectApiUrl(url) {
        document.getElementById('backendApiUrl').value = url;
        document.getElementById('apiUrlDropdown').style.display = 'none';

        // Visual feedback
        const input = document.getElementById('backendApiUrl');
        input.style.background = 'rgba(34, 197, 94, 0.2)';
        input.style.borderColor = '#22c55e';
        input.style.boxShadow = '0 0 15px rgba(34, 197, 94, 0.3)';

        setTimeout(() => {
            input.style.background = '';
            input.style.borderColor = '';
            input.style.boxShadow = '';
        }, 800);
    }

    // Delete API URL from list
    function deleteApiUrl(url) {
        if (!confirm('Remove this URL from recent list?\n\n' + url)) return;

        let urls = JSON.parse(localStorage.getItem(SAVED_API_URLS_KEY) || '[]');
        urls = urls.filter(u => u !== url);
        localStorage.setItem(SAVED_API_URLS_KEY, JSON.stringify(urls));

        renderApiUrlDropdown(urls);
        showToastMessage('🗑️ URL removed from recent list', 'info');
    }

    // Clear all API URLs
    function clearAllApiUrls() {
        if (!confirm('🗑️ Clear all recent API URLs?\n\nThis will remove all saved URLs from the list.')) return;

        localStorage.removeItem(SAVED_API_URLS_KEY);
        renderApiUrlDropdown([]);
        showToastMessage('✅ All URLs cleared!', 'success');
    }

    // ========================================
    // DOMAIN MANAGEMENT
    // ========================================

    // Load recent domains
    function loadRecentDomains() {
        const saved = localStorage.getItem(SAVED_API_DOMAINS_KEY);
        const domains = saved ? JSON.parse(saved) : [];

        renderDomainDropdown(domains);
    }

    // Save domain
    function saveDomain() {
        const domain = document.getElementById('apiDomain').value.trim();

        if (!domain) {
            showToastMessage('⚠️ Please enter a domain first!', 'error');
            return;
        }

        let domains = JSON.parse(localStorage.getItem(SAVED_API_DOMAINS_KEY) || '[]');

        // Remove if already exists
        domains = domains.filter(d => d !== domain);

        // Add to beginning
        domains.unshift(domain);

        // Keep only MAX_RECENT_DOMAINS
        if (domains.length > MAX_RECENT_DOMAINS) {
            domains = domains.slice(0, MAX_RECENT_DOMAINS);
        }

        localStorage.setItem(SAVED_API_DOMAINS_KEY, JSON.stringify(domains));
        renderDomainDropdown(domains);

        showToastMessage('✅ Domain saved!', 'success');

        // Visual feedback
        const input = document.getElementById('apiDomain');
        input.style.background = 'rgba(34, 197, 94, 0.2)';
        input.style.borderColor = '#22c55e';

        setTimeout(() => {
            input.style.background = '';
            input.style.borderColor = '';
        }, 800);
    }

    // Toggle domain dropdown
    function toggleDomainDropdown() {
        const dropdown = document.getElementById('domainDropdown');

        if (dropdown.style.display === 'none') {
            dropdown.style.display = 'block';
            dropdown.style.animation = 'slideDown 0.3s ease-out';
        } else {
            dropdown.style.animation = 'slideUp 0.2s ease-out';
            setTimeout(() => {
                dropdown.style.display = 'none';
            }, 200);
        }
    }

    // Render domain dropdown
    function renderDomainDropdown(domains) {
        const dropdown = document.getElementById('domainDropdown');
        const currentDomain = document.getElementById('apiDomain').value;

        if (domains.length === 0) {
            dropdown.innerHTML = `
            <div style="padding: 20px; text-align: center; color: rgba(255,255,255,0.5);">
                <div style="font-size: 48px; margin-bottom: 10px;">🌐</div>
                <div style="font-size: 13px;">No recent domains yet</div>
                <div style="font-size: 11px; margin-top: 5px; opacity: 0.7;">Common: http://localhost or https://yoursite.com</div>
            </div>
        `;
            return;
        }

        let html = `
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; background: rgba(59, 130, 246, 0.2); border-bottom: 2px solid rgba(59, 130, 246, 0.4);">
            <span style="font-size: 11px; color: #93c5fd; font-weight: 600; letter-spacing: 0.5px;">🌐 RECENT DOMAINS (${domains.length})</span>
            <button onclick="clearAllDomains(); event.stopPropagation();" 
                    style="background: rgba(239, 68, 68, 0.3); border: none; border-radius: 5px; padding: 3px 10px; cursor: pointer; color: #fef3c7; font-size: 10px; font-weight: 600;"
                    onmouseover="this.style.background='rgba(239, 68, 68, 0.6)'"
                    onmouseout="this.style.background='rgba(239, 68, 68, 0.3)'">
                🗑️ CLEAR
            </button>
        </div>
    `;

        domains.forEach(domain => {
            const isCurrent = domain === currentDomain;
            const escapedDomain = domain.replace(/\\/g, '\\\\').replace(/'/g, "\\'");
            html += `
            <div onclick="selectDomain('${escapedDomain}'); event.stopPropagation();" 
                 style="padding: 10px 15px; cursor: pointer; color: #fef3c7; font-family: monospace; font-size: 12px; border-bottom: 1px solid rgba(59,130,246,0.1); transition: all 0.2s; display: flex; align-items: center; justify-content: space-between; ${isCurrent ? 'background: rgba(59, 130, 246, 0.2); border-left: 3px solid #3b82f6;' : ''}"
                 onmouseover="this.style.background='rgba(59,130,246,0.2)'"
                 onmouseout="this.style.background='${isCurrent ? 'rgba(59, 130, 246, 0.2)' : ''}'">
                <span style="flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; padding-right: 10px;" title="${domain}">
                    ${isCurrent ? '✓ ' : ''}${domain}
                </span>
                <button onclick="deleteDomain('${escapedDomain}'); event.stopPropagation();" 
                        style="background: rgba(239, 68, 68, 0.3); border: none; border-radius: 5px; padding: 3px 8px; cursor: pointer; color: #fef3c7; font-size: 11px; flex-shrink: 0;"
                        onmouseover="this.style.background='rgba(239, 68, 68, 0.6)'"
                        onmouseout="this.style.background='rgba(239, 68, 68, 0.3)'"
                        title="Remove from list">
                    ✕
                </button>
            </div>
        `;
        });

        dropdown.innerHTML = html;
    }

    // Select domain from dropdown
    function selectDomain(domain) {
        document.getElementById('apiDomain').value = domain;
        document.getElementById('domainDropdown').style.display = 'none';
        buildApiUrl();

        // Visual feedback
        const input = document.getElementById('apiDomain');
        input.style.background = 'rgba(59, 130, 246, 0.15)';
        input.style.borderColor = '#3b82f6';

        setTimeout(() => {
            input.style.background = '';
            input.style.borderColor = '';
        }, 600);
    }

    // Delete domain
    function deleteDomain(domain) {
        if (!confirm('Remove this domain from recent list?\n\n' + domain)) return;

        let domains = JSON.parse(localStorage.getItem(SAVED_API_DOMAINS_KEY) || '[]');
        domains = domains.filter(d => d !== domain);
        localStorage.setItem(SAVED_API_DOMAINS_KEY, JSON.stringify(domains));

        renderDomainDropdown(domains);
        showToastMessage('🗑️ Domain removed', 'info');
    }

    // Clear all domains
    function clearAllDomains() {
        if (!confirm('🗑️ Clear all recent domains?')) return;

        localStorage.removeItem(SAVED_API_DOMAINS_KEY);
        renderDomainDropdown([]);
        showToastMessage('✅ All domains cleared!', 'success');
    }

    // Database Hub API URL
    const DATABASE_HUB_API = 'report-prompt-databases.php?api=list';
    
    // Load databases from the central hub (report_prompt_databases table)
    async function loadDatabases() {
        const select = document.getElementById('databaseSelect');
        
        // Clear existing options
        select.innerHTML = '<option value="">-- Loading databases... --</option>';

        // Load Localhost databases from localStorage (Laragon local)
        const localhostDbs = JSON.parse(localStorage.getItem('localhost_databases') || '[]');
        if (localhostDbs.length > 0) {
            const optgroup = document.createElement('optgroup');
            optgroup.label = '🖥️ LOCALHOST (LARAGON)';

            localhostDbs.forEach(dbName => {
                const option = document.createElement('option');
                option.value = 'localhost_' + dbName;
                option.textContent = '🖥️ ' + dbName + ' (Localhost)';
                optgroup.appendChild(option);
            });

            select.appendChild(optgroup);
        }

        // Load Hostinger connections from Database Hub API
        try {
            const response = await fetch(DATABASE_HUB_API);
            const data = await response.json();
            
            if (data.success && data.connections && data.connections.length > 0) {
                const optgroup = document.createElement('optgroup');
                optgroup.label = '🌐 HOSTINGER (Database Hub)';

                data.connections.forEach(conn => {
                    const option = document.createElement('option');
                    option.value = 'conn_' + conn.id;
                    option.textContent = '🌐 ' + conn.name + ' (' + conn.dbName + ')';
                    option.setAttribute('data-conn', JSON.stringify(conn));
                    optgroup.appendChild(option);
                });

                select.appendChild(optgroup);
                console.log('✅ Loaded ' + data.connections.length + ' connections from Database Hub');
            } else {
                console.log('ℹ️ No Hostinger connections found in Database Hub');
            }
        } catch (error) {
            console.error('❌ Failed to load from Database Hub:', error);
            // Fallback to localStorage if API fails
            const hostingerConns = JSON.parse(localStorage.getItem('hostinger_connections') || '[]');
            if (hostingerConns.length > 0) {
                const optgroup = document.createElement('optgroup');
                optgroup.label = '🌐 HOSTINGER (localStorage fallback)';

                hostingerConns.forEach(conn => {
                    const option = document.createElement('option');
                    option.value = 'conn_' + conn.id;
                    option.textContent = '🌐 ' + conn.name + ' (' + conn.dbName + ')';
                    option.setAttribute('data-conn', JSON.stringify(conn));
                    optgroup.appendChild(option);
                });

                select.appendChild(optgroup);
            }
        }
        
        // Update placeholder
        if (select.options.length <= 1) {
            select.innerHTML = '<option value="">-- No databases available --</option>';
        } else {
            // Add placeholder at the beginning
            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = '-- Select Database --';
            select.insertBefore(placeholder, select.firstChild);
            select.selectedIndex = 0;
        }
    }

    function loadDatabaseInfo() {
        const select = document.getElementById('databaseSelect');
        const value = select.value;
        const dbInfo = document.getElementById('dbInfo');
        const dbInfoText = document.getElementById('dbInfoText');
        const credentialsBtn = document.getElementById('showCredentialsBtn');

        if (!value) {
            dbInfo.style.display = 'none';
            selectedDatabase = null;
            credentialsBtn.style.display = 'none';
            return;
        }

        if (value.startsWith('localhost_')) {
            const dbName = value.replace('localhost_', '');
            selectedDatabase = {
                host: 'localhost',
                dbName: dbName,
                username: 'root',
                password: '',
                port: '3306',
                isLocalhost: true
            };

            dbInfoText.innerHTML =
                `Host: <strong>localhost</strong><br>Database: <strong>${dbName}</strong><br>User: <strong>root</strong> (no password)`;
        } else {
            const selectedOption = select.options[select.selectedIndex];
            const connData = JSON.parse(selectedOption.getAttribute('data-conn'));

            selectedDatabase = {
                host: connData.host,
                dbName: connData.dbName,
                username: connData.username,
                password: connData.password,
                port: connData.port || '3306',
                isLocalhost: false
            };

            dbInfoText.innerHTML =
                `Host: <strong>${connData.host}</strong><br>Database: <strong>${connData.dbName}</strong><br>User: <strong>${connData.username}</strong>`;
        }

        dbInfo.style.display = 'block';
        credentialsBtn.style.display = 'inline-flex';
    }

    // Show Database Credentials Modal
    function showDatabaseCredentials() {
        if (!selectedDatabase) {
            showCustomToast('⚠️ Please select a database first!', 'error');
            return;
        }

        // Create modal
        const modal = document.createElement('div');
        modal.style.cssText =
            'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); backdrop-filter: blur(10px); z-index: 5000; display: flex; align-items: center; justify-content: center; animation: fadeIn 0.3s ease-out;';

        // Password display toggle
        const passwordMasked = '••••••••••••';
        const actualPassword = selectedDatabase.password || '(empty)';
        let showPassword = false;

        modal.innerHTML = `
        <div style="background: linear-gradient(135deg, rgba(6,182,212,0.98) 0%, rgba(8,145,178,0.98) 100%); border-radius: 20px; padding: 35px; max-width: 650px; width: 90%; box-shadow: 0 25px 80px rgba(0,0,0,0.6); border: 2px solid rgba(255,255,255,0.3); animation: scaleIn 0.3s ease-out;">
            <!-- Header -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 2px solid rgba(255,255,255,0.3);">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 50px; height: 50px; background: rgba(255,255,255,0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px;">🔑</div>
                    <div>
                        <h3 style="color: #fff; font-size: 24px; margin: 0; font-weight: 700;">Database Credentials</h3>
                        <p style="color: rgba(255,255,255,0.8); font-size: 13px; margin: 3px 0 0 0;">Connection Details for API Integration</p>
                    </div>
                </div>
                <button onclick="this.closest('[style*=fixed]').remove()" style="background: rgba(239,68,68,0.3); border: 1px solid rgba(239,68,68,0.5); width: 40px; height: 40px; border-radius: 50%; color: #fff; font-size: 24px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.3s;" onmouseover="this.style.background='rgba(239,68,68,0.6)'; this.style.transform='rotate(90deg)'" onmouseout="this.style.background='rgba(239,68,68,0.3)'; this.style.transform='rotate(0deg)'">×</button>
            </div>
            
            <!-- Credentials Grid -->
            <div style="display: grid; gap: 18px; margin-bottom: 25px;">
                <!-- Database Type Badge -->
                <div style="background: rgba(255,255,255,0.15); padding: 12px 18px; border-radius: 10px; border-left: 4px solid #fbbf24; display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="font-size: 20px;">${selectedDatabase.isLocalhost ? '🖥️' : '🌐'}</span>
                        <div>
                            <div style="font-size: 11px; opacity: 0.8; margin-bottom: 2px;">Connection Type</div>
                            <div style="font-size: 15px; font-weight: 700; color: #fbbf24;">${selectedDatabase.isLocalhost ? 'Localhost (Laragon/XAMPP)' : 'Remote Server (Hostinger)'}</div>
                        </div>
                    </div>
                    <div style="background: rgba(251,191,36,0.2); padding: 6px 12px; border-radius: 6px; font-size: 11px; font-weight: 600; color: #fbbf24;">ACTIVE</div>
                </div>
                
                <!-- Host -->
                <div class="cred-item" style="background: rgba(255,255,255,0.1); padding: 15px 18px; border-radius: 10px; border: 2px solid rgba(255,255,255,0.2); transition: all 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.15)'; this.style.borderColor='rgba(255,255,255,0.4)'" onmouseout="this.style.background='rgba(255,255,255,0.1)'; this.style.borderColor='rgba(255,255,255,0.2)'">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                        <span style="font-size: 18px;">🌐</span>
                        <div style="font-size: 12px; opacity: 0.9; font-weight: 600; letter-spacing: 0.5px;">DATABASE HOST</div>
                    </div>
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px;">
                        <code style="font-size: 16px; font-weight: 700; color: #fff; font-family: 'Consolas', monospace; flex: 1; background: rgba(0,0,0,0.2); padding: 8px 12px; border-radius: 6px;">${selectedDatabase.host}</code>
                        <button onclick="copyToClipboard('${selectedDatabase.host}', 'Host')" style="background: rgba(34,197,94,0.3); border: 1px solid rgba(34,197,94,0.5); padding: 8px 14px; border-radius: 6px; color: #fff; cursor: pointer; font-size: 12px; font-weight: 600; transition: all 0.2s;" onmouseover="this.style.background='rgba(34,197,94,0.5)'" onmouseout="this.style.background='rgba(34,197,94,0.3)'">📋 Copy</button>
                    </div>
                </div>
                
                <!-- Database Name -->
                <div class="cred-item" style="background: rgba(255,255,255,0.1); padding: 15px 18px; border-radius: 10px; border: 2px solid rgba(255,255,255,0.2); transition: all 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.15)'; this.style.borderColor='rgba(255,255,255,0.4)'" onmouseout="this.style.background='rgba(255,255,255,0.1)'; this.style.borderColor='rgba(255,255,255,0.2)'">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                        <span style="font-size: 18px;">🗄️</span>
                        <div style="font-size: 12px; opacity: 0.9; font-weight: 600; letter-spacing: 0.5px;">DATABASE NAME</div>
                    </div>
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px;">
                        <code style="font-size: 16px; font-weight: 700; color: #fff; font-family: 'Consolas', monospace; flex: 1; background: rgba(0,0,0,0.2); padding: 8px 12px; border-radius: 6px;">${selectedDatabase.dbName}</code>
                        <button onclick="copyToClipboard('${selectedDatabase.dbName}', 'Database Name')" style="background: rgba(34,197,94,0.3); border: 1px solid rgba(34,197,94,0.5); padding: 8px 14px; border-radius: 6px; color: #fff; cursor: pointer; font-size: 12px; font-weight: 600; transition: all 0.2s;" onmouseover="this.style.background='rgba(34,197,94,0.5)'" onmouseout="this.style.background='rgba(34,197,94,0.3)'">📋 Copy</button>
                    </div>
                </div>
                
                <!-- Username -->
                <div class="cred-item" style="background: rgba(255,255,255,0.1); padding: 15px 18px; border-radius: 10px; border: 2px solid rgba(255,255,255,0.2); transition: all 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.15)'; this.style.borderColor='rgba(255,255,255,0.4)'" onmouseout="this.style.background='rgba(255,255,255,0.1)'; this.style.borderColor='rgba(255,255,255,0.2)'">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                        <span style="font-size: 18px;">👤</span>
                        <div style="font-size: 12px; opacity: 0.9; font-weight: 600; letter-spacing: 0.5px;">USERNAME</div>
                    </div>
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px;">
                        <code style="font-size: 16px; font-weight: 700; color: #fff; font-family: 'Consolas', monospace; flex: 1; background: rgba(0,0,0,0.2); padding: 8px 12px; border-radius: 6px;">${selectedDatabase.username}</code>
                        <button onclick="copyToClipboard('${selectedDatabase.username}', 'Username')" style="background: rgba(34,197,94,0.3); border: 1px solid rgba(34,197,94,0.5); padding: 8px 14px; border-radius: 6px; color: #fff; cursor: pointer; font-size: 12px; font-weight: 600; transition: all 0.2s;" onmouseover="this.style.background='rgba(34,197,94,0.5)'" onmouseout="this.style.background='rgba(34,197,94,0.3)'">📋 Copy</button>
                    </div>
                </div>
                
                <!-- Password -->
                <div class="cred-item" style="background: rgba(255,255,255,0.1); padding: 15px 18px; border-radius: 10px; border: 2px solid rgba(255,255,255,0.2); transition: all 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.15)'; this.style.borderColor='rgba(255,255,255,0.4)'" onmouseout="this.style.background='rgba(255,255,255,0.1)'; this.style.borderColor='rgba(255,255,255,0.2)'">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                        <span style="font-size: 18px;">🔐</span>
                        <div style="font-size: 12px; opacity: 0.9; font-weight: 600; letter-spacing: 0.5px;">PASSWORD</div>
                    </div>
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px;">
                        <code id="passwordDisplay" style="font-size: 16px; font-weight: 700; color: #fff; font-family: 'Consolas', monospace; flex: 1; background: rgba(0,0,0,0.2); padding: 8px 12px; border-radius: 6px;">${passwordMasked}</code>
                        <button id="togglePasswordBtn" onclick="togglePasswordVisibility()" style="background: rgba(139,92,246,0.3); border: 1px solid rgba(139,92,246,0.5); padding: 8px 14px; border-radius: 6px; color: #fff; cursor: pointer; font-size: 12px; font-weight: 600; transition: all 0.2s; min-width: 80px;" onmouseover="this.style.background='rgba(139,92,246,0.5)'" onmouseout="this.style.background='rgba(139,92,246,0.3)'">👁️ Show</button>
                        <button onclick="copyToClipboard('${actualPassword.replace(/'/g, "\\'")}', 'Password')" style="background: rgba(34,197,94,0.3); border: 1px solid rgba(34,197,94,0.5); padding: 8px 14px; border-radius: 6px; color: #fff; cursor: pointer; font-size: 12px; font-weight: 600; transition: all 0.2s;" onmouseover="this.style.background='rgba(34,197,94,0.5)'" onmouseout="this.style.background='rgba(34,197,94,0.3)'">📋 Copy</button>
                    </div>
                </div>
                
                <!-- Port -->
                <div class="cred-item" style="background: rgba(255,255,255,0.1); padding: 15px 18px; border-radius: 10px; border: 2px solid rgba(255,255,255,0.2); transition: all 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.15)'; this.style.borderColor='rgba(255,255,255,0.4)'" onmouseout="this.style.background='rgba(255,255,255,0.1)'; this.style.borderColor='rgba(255,255,255,0.2)'">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                        <span style="font-size: 18px;">🔌</span>
                        <div style="font-size: 12px; opacity: 0.9; font-weight: 600; letter-spacing: 0.5px;">PORT</div>
                    </div>
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px;">
                        <code style="font-size: 16px; font-weight: 700; color: #fff; font-family: 'Consolas', monospace; flex: 1; background: rgba(0,0,0,0.2); padding: 8px 12px; border-radius: 6px;">${selectedDatabase.port}</code>
                        <button onclick="copyToClipboard('${selectedDatabase.port}', 'Port')" style="background: rgba(34,197,94,0.3); border: 1px solid rgba(34,197,94,0.5); padding: 8px 14px; border-radius: 6px; color: #fff; cursor: pointer; font-size: 12px; font-weight: 600; transition: all 0.2s;" onmouseover="this.style.background='rgba(34,197,94,0.5)'" onmouseout="this.style.background='rgba(34,197,94,0.3)'">📋 Copy</button>
                    </div>
                </div>
            </div>
            
            <!-- Connection String Examples -->
            <div style="background: rgba(139,92,246,0.15); padding: 18px; border-radius: 12px; border: 2px solid rgba(139,92,246,0.3); margin-bottom: 20px;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                    <span style="font-size: 20px;">💡</span>
                    <div style="font-size: 14px; font-weight: 700; color: #fbbf24;">Connection String Examples</div>
                </div>
                
                <!-- PHP PDO -->
                <div style="margin-bottom: 12px;">
                    <div style="font-size: 11px; opacity: 0.9; margin-bottom: 5px; font-weight: 600;">PHP PDO:</div>
                    <code style="display: block; background: rgba(0,0,0,0.3); padding: 10px 12px; border-radius: 6px; font-size: 12px; color: #86efac; font-family: 'Consolas', monospace; overflow-x: auto; white-space: pre; line-height: 1.5;">$pdo = new PDO("mysql:host=${selectedDatabase.host};port=${selectedDatabase.port};dbname=${selectedDatabase.dbName}", "${selectedDatabase.username}", "${actualPassword === '(empty)' ? '' : actualPassword}");</code>
                </div>
                
                <!-- MySQL CLI -->
                <div>
                    <div style="font-size: 11px; opacity: 0.9; margin-bottom: 5px; font-weight: 600;">MySQL CLI:</div>
                    <code style="display: block; background: rgba(0,0,0,0.3); padding: 10px 12px; border-radius: 6px; font-size: 12px; color: #86efac; font-family: 'Consolas', monospace; overflow-x: auto; white-space: pre;">mysql -h ${selectedDatabase.host} -P ${selectedDatabase.port} -u ${selectedDatabase.username}${actualPassword === '(empty)' ? '' : ' -p'} ${selectedDatabase.dbName}</code>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div style="display: flex; gap: 12px;">
                <button onclick="this.closest('[style*=fixed]').remove()" style="flex: 1; padding: 14px; background: rgba(239,68,68,0.3); border: 1px solid rgba(239,68,68,0.5); border-radius: 10px; color: #fff; cursor: pointer; font-weight: 700; font-size: 15px; transition: all 0.3s;" onmouseover="this.style.background='rgba(239,68,68,0.5)'" onmouseout="this.style.background='rgba(239,68,68,0.3)'">
                    ✕ Close
                </button>
                <button onclick="copyAllCredentials()" style="flex: 2; padding: 14px; background: linear-gradient(135deg, #22c55e 0%, #15803d 100%); border: none; border-radius: 10px; color: #fff; cursor: pointer; font-weight: 700; font-size: 15px; box-shadow: 0 4px 15px rgba(34,197,94,0.4); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(34,197,94,0.6)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(34,197,94,0.4)'">
                    📋 Copy All Credentials
                </button>
            </div>
        </div>
    `;

        document.body.appendChild(modal);

        // Toggle password visibility function
        window.togglePasswordVisibility = function() {
            showPassword = !showPassword;
            const passwordDisplay = document.getElementById('passwordDisplay');
            const toggleBtn = document.getElementById('togglePasswordBtn');

            if (showPassword) {
                passwordDisplay.textContent = actualPassword;
                toggleBtn.innerHTML = '🙈 Hide';
            } else {
                passwordDisplay.textContent = passwordMasked;
                toggleBtn.innerHTML = '👁️ Show';
            }
        };

        // Copy all credentials function
        window.copyAllCredentials = function() {
            const credentials = `DATABASE CREDENTIALS
════════════════════════════════════════
Connection Type: ${selectedDatabase.isLocalhost ? 'Localhost (Laragon/XAMPP)' : 'Remote Server (Hostinger)'}

Host: ${selectedDatabase.host}
Database Name: ${selectedDatabase.dbName}
Username: ${selectedDatabase.username}
Password: ${actualPassword}
Port: ${selectedDatabase.port}

════════════════════════════════════════
PHP PDO Connection:
$pdo = new PDO("mysql:host=${selectedDatabase.host};port=${selectedDatabase.port};dbname=${selectedDatabase.dbName}", "${selectedDatabase.username}", "${actualPassword === '(empty)' ? '' : actualPassword}");

MySQL CLI:
mysql -h ${selectedDatabase.host} -P ${selectedDatabase.port} -u ${selectedDatabase.username}${actualPassword === '(empty)' ? '' : ' -p'} ${selectedDatabase.dbName}
════════════════════════════════════════`;

            copyToClipboard(credentials, 'All Credentials');
        };
    }

    // Copy to clipboard helper
    function copyToClipboard(text, label) {
        navigator.clipboard.writeText(text).then(() => {
            showCustomToast(`✅ ${label} copied to clipboard!`, 'success');
        }).catch(err => {
            showCustomToast(`❌ Failed to copy ${label}`, 'error');
        });
    }

    // ========================================
    // FOLDER PICKER FUNCTIONS
    // ========================================

    // pickFolder() function moved to FILE BROWSER SYSTEM section below
    // It now opens the visual file browser modal instead of using prompt

    // Load recent folders from localStorage
    function loadRecentFolders(type = 'frontend') {
        const storageKey = type === 'backend' ? SAVED_BACKEND_FOLDERS_KEY : SAVED_FRONTEND_FOLDERS_KEY;
        const saved = localStorage.getItem(storageKey);
        const folders = saved ? JSON.parse(saved) : [];

        // Set first folder as default if input is empty or is the default value
        const inputId = type === 'backend' ? 'backendFolder' : 'frontendFolder';
        const defaultValue = type === 'backend' ? 'C:\\laragon\\www\\generated\\backend\\' :
            'C:\\laragon\\www\\generated\\frontend\\';
        const input = document.getElementById(inputId);

        if ((!input.value || input.value === defaultValue) && folders.length > 0) {
            input.value = folders[0];
        }

        renderFolderDropdown(folders, type);
    }

    // Save folder to localStorage
    function saveFolder(folderPath, type = 'frontend') {
        if (!folderPath || folderPath.trim() === '') return;

        const trimmedPath = folderPath.trim();
        const storageKey = type === 'backend' ? SAVED_BACKEND_FOLDERS_KEY : SAVED_FRONTEND_FOLDERS_KEY;
        let folders = JSON.parse(localStorage.getItem(storageKey) || '[]');

        // Remove if already exists
        folders = folders.filter(f => f !== trimmedPath);

        // Add to beginning
        folders.unshift(trimmedPath);

        // Keep only MAX_RECENT_FOLDERS
        if (folders.length > MAX_RECENT_FOLDERS) {
            folders = folders.slice(0, MAX_RECENT_FOLDERS);
        }

        localStorage.setItem(storageKey, JSON.stringify(folders));
        renderFolderDropdown(folders, type);
    }

    // Render folder dropdown
    function renderFolderDropdown(folders, type = 'frontend') {
        const dropdownId = type === 'backend' ? 'backendFolderDropdown' : 'frontendFolderDropdown';
        const inputId = type === 'backend' ? 'backendFolder' : 'frontendFolder';
        const dropdown = document.getElementById(dropdownId);
        const currentPath = document.getElementById(inputId).value;

        if (folders.length === 0) {
            dropdown.innerHTML = `
            <div style="padding: 20px; text-align: center; color: rgba(255,255,255,0.5);">
                <div style="font-size: 48px; margin-bottom: 10px;">📁</div>
                <div style="font-size: 13px;">No recent folders yet</div>
                <div style="font-size: 11px; margin-top: 5px; opacity: 0.7;">Click Browse or type path manually</div>
            </div>
        `;
            return;
        }

        const title = type === 'backend' ? 'BACKEND FOLDERS' : 'FRONTEND FOLDERS';
        let html = `
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; background: rgba(34, 197, 94, 0.2); border-bottom: 2px solid rgba(34, 197, 94, 0.4);">
            <span style="font-size: 11px; color: #86efac; font-weight: 600; letter-spacing: 0.5px;">📁 ${title} (${folders.length})</span>
            <button onclick="clearAllFolders('${type}'); event.stopPropagation();" 
                    style="background: rgba(239, 68, 68, 0.3); border: none; border-radius: 5px; padding: 3px 10px; cursor: pointer; color: #fef3c7; font-size: 10px; font-weight: 600;"
                    onmouseover="this.style.background='rgba(239, 68, 68, 0.6)'"
                    onmouseout="this.style.background='rgba(239, 68, 68, 0.3)'">
                🗑️ CLEAR ALL
            </button>
        </div>
    `;

        folders.forEach((folder, index) => {
            const isCurrent = folder === currentPath;
            const escapedFolder = folder.replace(/\\/g, '\\\\').replace(/'/g, "\\'");
            html += `
            <div onclick="selectFolder('${escapedFolder}', '${type}'); event.stopPropagation();" 
                 style="padding: 10px 15px; cursor: pointer; color: #fef3c7; font-family: monospace; font-size: 12px; border-bottom: 1px solid rgba(251,191,36,0.1); transition: all 0.2s; display: flex; align-items: center; justify-content: space-between; ${isCurrent ? 'background: rgba(34, 197, 94, 0.2); border-left: 3px solid #22c55e;' : ''}"
                 onmouseover="this.style.background='rgba(251,191,36,0.2)'"
                 onmouseout="this.style.background='${isCurrent ? 'rgba(34, 197, 94, 0.2)' : ''}'">
                <span style="flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; padding-right: 10px;" title="${folder}">
                    ${isCurrent ? '✓ ' : ''}${folder}
                </span>
                <button onclick="deleteFolder('${escapedFolder}', '${type}'); event.stopPropagation();" 
                        style="background: rgba(239, 68, 68, 0.3); border: none; border-radius: 5px; padding: 3px 8px; cursor: pointer; color: #fef3c7; font-size: 11px; flex-shrink: 0;"
                        onmouseover="this.style.background='rgba(239, 68, 68, 0.6)'"
                        onmouseout="this.style.background='rgba(239, 68, 68, 0.3)'"
                        title="Remove from list">
                    ✕
                </button>
            </div>
        `;
        });

        dropdown.innerHTML = html;
    }

    // Clear all folders
    function clearAllFolders(type = 'frontend') {
        const title = type === 'backend' ? 'Backend' : 'Frontend';
        if (!confirm(
                `🗑️ Clear all recent ${title} folders?\n\nThis will remove all saved folder paths from the list.`))
            return;

        const storageKey = type === 'backend' ? SAVED_BACKEND_FOLDERS_KEY : SAVED_FRONTEND_FOLDERS_KEY;
        localStorage.removeItem(storageKey);
        renderFolderDropdown([], type);
        showToastMessage('✅ All folders cleared!', 'success');
    }

    // Save current folder manually
    function saveCurrentFolder(type = 'frontend') {
        const inputId = type === 'backend' ? 'backendFolder' : 'frontendFolder';
        const folderPath = document.getElementById(inputId).value.trim();

        if (!folderPath) {
            showToastMessage('⚠️ Please enter a folder path first!', 'error');
            return;
        }

        saveFolder(folderPath, type);
        showToastMessage('✅ Folder saved to recent list!', 'success');

        // Visual feedback
        const input = document.getElementById(inputId);
        input.style.background = 'rgba(34, 197, 94, 0.2)';
        input.style.borderColor = '#22c55e';
        input.style.boxShadow = '0 0 15px rgba(34, 197, 94, 0.3)';

        setTimeout(() => {
            input.style.background = '';
            input.style.borderColor = '';
            input.style.boxShadow = '';
        }, 1000);
    }

    // Toggle folder dropdown
    function toggleFolderDropdown(type = 'frontend') {
        const dropdownId = type === 'backend' ? 'backendFolderDropdown' : 'frontendFolderDropdown';
        const dropdown = document.getElementById(dropdownId);

        if (dropdown.style.display === 'none') {
            dropdown.style.display = 'block';
            dropdown.style.animation = 'slideDown 0.3s ease-out';
        } else {
            dropdown.style.animation = 'slideUp 0.2s ease-out';
            setTimeout(() => {
                dropdown.style.display = 'none';
            }, 200);
        }
    }

    // Select folder from dropdown
    function selectFolder(folderPath, type = 'frontend') {
        const inputId = type === 'backend' ? 'backendFolder' : 'frontendFolder';
        const dropdownId = type === 'backend' ? 'backendFolderDropdown' : 'frontendFolderDropdown';

        document.getElementById(inputId).value = folderPath;
        document.getElementById(dropdownId).style.display = 'none';

        // Visual feedback
        const input = document.getElementById(inputId);
        input.style.background = 'rgba(34, 197, 94, 0.2)';
        input.style.borderColor = '#22c55e';
        input.style.boxShadow = '0 0 15px rgba(34, 197, 94, 0.3)';

        setTimeout(() => {
            input.style.background = '';
            input.style.borderColor = '';
            input.style.boxShadow = '';
        }, 800);
    }

    // Delete folder from saved list
    function deleteFolder(folderPath, type = 'frontend') {
        if (!confirm('Remove this folder from recent list?\n\n' + folderPath)) return;

        const storageKey = type === 'backend' ? SAVED_BACKEND_FOLDERS_KEY : SAVED_FRONTEND_FOLDERS_KEY;
        let folders = JSON.parse(localStorage.getItem(storageKey) || '[]');
        folders = folders.filter(f => f !== folderPath);
        localStorage.setItem(storageKey, JSON.stringify(folders));

        renderFolderDropdown(folders, type);

        // Show feedback
        showToastMessage('🗑️ Folder removed from recent list', 'info');
    }

    // Save folder when input loses focus (manual typing)
    function saveFolderOnBlur(type = 'frontend') {
        const inputId = type === 'backend' ? 'backendFolder' : 'frontendFolder';
        const folderPath = document.getElementById(inputId).value.trim();
        if (folderPath && folderPath !== '') {
            const storageKey = type === 'backend' ? SAVED_BACKEND_FOLDERS_KEY : SAVED_FRONTEND_FOLDERS_KEY;
            let folders = JSON.parse(localStorage.getItem(storageKey) || '[]');

            // Remove if already exists
            folders = folders.filter(f => f !== folderPath);

            // Add to beginning
            folders.unshift(folderPath);

            // Keep only MAX_RECENT_FOLDERS
            if (folders.length > MAX_RECENT_FOLDERS) {
                folders = folders.slice(0, MAX_RECENT_FOLDERS);
            }

            localStorage.setItem(storageKey, JSON.stringify(folders));
            renderFolderDropdown(folders, type);
        }
    }

    // Show toast message
    function showToastMessage(message, type = 'success') {
        const colors = {
            success: 'linear-gradient(135deg, #22c55e 0%, #15803d 100%)',
            error: 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)',
            info: 'linear-gradient(135deg, #3b82f6 0%, #1e40af 100%)'
        };

        const toast = document.createElement('div');
        toast.style.cssText =
            `position: fixed; bottom: 30px; right: 30px; background: ${colors[type]}; color: white; padding: 15px 25px; border-radius: 10px; border: 2px solid rgba(255,255,255,0.3); z-index: 10000; box-shadow: 0 5px 20px rgba(0,0,0,0.3); animation: slideInRight 0.3s ease-out;`;
        toast.innerHTML = message;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.animation = 'slideOutRight 0.3s ease-out forwards';
            setTimeout(() => {
                if (toast.parentNode) document.body.removeChild(toast);
            }, 300);
        }, 2500);
    }

    // ========================================
    // FILE BROWSER SYSTEM  
    // ========================================

    // File Browser State
    let currentBrowsePath = 'C:\\';
    let selectedFolderPath = null;
    let browseHistory = [];
    let historyIndex = -1;
    let currentFolderType = 'frontend'; // Track which folder type we're selecting

    // Open file browser
    function pickFolder(type = 'frontend') {
        currentFolderType = type;
        document.getElementById('fileBrowserModal').style.display = 'flex';
        currentBrowsePath = 'C:\\';
        selectedFolderPath = null;
        browseHistory = ['C:\\'];
        historyIndex = 0;

        // Update modal title based on type
        const modalTitle = document.querySelector('#fileBrowserModal h3');
        if (modalTitle) {
            modalTitle.innerHTML = type === 'backend' ?
                '🗂️ File Browser - Select Backend Folder (PHP)' :
                '🗂️ File Browser - Select Frontend Folder (HTML)';
        }

        // Start browsing from C:\
        console.log('🚀 Opening file browser for:', type);
        browseToPath('C:\\');
    }

    // Close file browser
    function closeFileBrowser() {
        document.getElementById('fileBrowserModal').style.display = 'none';
    }

    // Helper function to browse - handles path escaping properly
    function browseToPath(path) {
        // Normalize the path
        let normalizedPath = path.replace(/\//g, '\\');

        // Ensure trailing backslash for directories
        if (!normalizedPath.endsWith('\\')) {
            normalizedPath += '\\';
        }

        console.log('📁 Browsing to:', normalizedPath);
        browseTo(normalizedPath);
    }

    // Browse to specific path
    async function browseTo(path) {
        // Normalize path
        path = path.replace(/\//g, '\\');
        if (!path.endsWith('\\')) {
            path += '\\';
        }

        console.log('🔍 browseTo called with:', path);

        currentBrowsePath = path;
        selectedFolderPath = path;

        // Update UI
        document.getElementById('currentPathBar').textContent = path;
        document.getElementById('selectedPath').textContent = path;
        document.getElementById('selectBtn').disabled = false;

        // Add to history
        if (historyIndex < browseHistory.length - 1) {
            browseHistory = browseHistory.slice(0, historyIndex + 1);
        }
        if (browseHistory[browseHistory.length - 1] !== path) {
            browseHistory.push(path);
            historyIndex = browseHistory.length - 1;
        }

        // Update back button
        document.getElementById('backBtn').disabled = historyIndex <= 0;

        // Show loading
        document.getElementById('foldersList').innerHTML = `
        <div style="text-align: center; padding: 60px 20px;">
            <div style="font-size: 64px; margin-bottom: 20px; animation: spin 1.5s linear infinite;">⏳</div>
            <div style="font-size: 16px; color: rgba(255,255,255,0.8); font-weight: 600;">Loading folders...</div>
            <div style="font-size: 13px; opacity: 0.7; margin-top: 8px;">${path}</div>
        </div>
    `;

        // Fetch folders via PHP
        try {
            const formData = new FormData();
            formData.append('action', 'browse_folders');
            formData.append('path', path);

            console.log('📤 Sending request to browse:', path);

            const response = await fetch('appmaker.php', {
                method: 'POST',
                body: formData
            });

            console.log('📥 Response status:', response.status);

            const responseText = await response.text();
            console.log('📥 Raw response:', responseText.substring(0, 500));

            let result;
            try {
                result = JSON.parse(responseText);
            } catch (parseError) {
                console.error('❌ JSON parse error:', parseError);
                console.error('❌ Response was:', responseText);
                throw new Error('Server returned invalid JSON. Check PHP errors.');
            }

            console.log('📥 Parsed result:', result);

            if (result.success) {
                console.log('✅ Found', result.folders.length, 'folders');
                renderFoldersList(result.folders, path);
            } else {
                console.error('❌ Browse failed:', result.message);
                document.getElementById('foldersList').innerHTML = `
                <div style="text-align: center; padding: 60px 20px; color: #fca5a5;">
                    <div style="font-size: 64px; margin-bottom: 20px;">⚠️</div>
                    <div style="font-size: 16px; font-weight: 600; margin-bottom: 10px;">Cannot Access</div>
                    <div style="font-size: 14px; opacity: 0.9;">${result.message}</div>
                </div>
            `;
            }
        } catch (error) {
            console.error('❌ Fetch error:', error);
            document.getElementById('foldersList').innerHTML = `
            <div style="text-align: center; padding: 60px 20px; color: #fca5a5;">
                <div style="font-size: 64px; margin-bottom: 20px;">❌</div>
                <div style="font-size: 16px; font-weight: 600;">Error</div>
                <div style="font-size: 14px; margin-top: 8px;">${error.message}</div>
            </div>
        `;
        }
    }

    // Render folders
    function renderFoldersList(folders, basePath) {
        const container = document.getElementById('foldersList');

        // Ensure basePath ends with backslash
        if (!basePath.endsWith('\\')) {
            basePath += '\\';
        }

        if (folders.length === 0) {
            container.innerHTML = `
            <div style="text-align: center; padding: 60px 20px; color: rgba(255,255,255,0.6);">
                <div style="font-size: 64px; margin-bottom: 20px;">📭</div>
                <div style="font-size: 18px; font-weight: 600; margin-bottom: 10px;">Empty Folder</div>
                <div style="font-size: 14px; opacity: 0.8; margin-bottom: 25px;">No subfolders in this location</div>
                <div style="padding: 15px 25px; background: rgba(34,197,94,0.2); border: 2px solid rgba(34,197,94,0.4); border-radius: 10px; display: inline-block;">
                    <div style="font-size: 13px; color: #86efac; font-weight: 600;">💡 You can still select this folder!</div>
                    <div style="font-size: 12px; margin-top: 5px; opacity: 0.9;">Click "Select This Folder" button below</div>
                </div>
            </div>
        `;
            return;
        }

        let html = `
        <div style="padding: 12px 18px; background: rgba(34,197,94,0.2); border-radius: 10px; margin-bottom: 15px; border: 2px solid rgba(34,197,94,0.4);">
            <div style="font-size: 14px; font-weight: 700; color: #86efac;">
                📊 ${folders.length} Folder${folders.length !== 1 ? 's' : ''} • Click to open
            </div>
        </div>
    `;

        folders.forEach((folder, index) => {
            const fullPath = basePath + folder;
            // Use data attribute to store path safely
            const escapedPath = fullPath.replace(/\\/g, '/'); // Convert to forward slashes for safe storage

            html += `
            <div class="folder-item" data-path="${escapedPath}" data-index="${index}"
                 style="display: flex; align-items: center; gap: 15px; padding: 14px 18px; margin-bottom: 10px; background: rgba(255,255,255,0.08); border: 2px solid rgba(255,255,255,0.12); border-radius: 10px; cursor: pointer; transition: all 0.25s ease;"
                 onmouseover="this.style.background='rgba(34,197,94,0.25)'; this.style.borderColor='rgba(34,197,94,0.5)'; this.style.transform='translateX(8px)'; this.style.boxShadow='0 4px 15px rgba(34,197,94,0.3)'"
                 onmouseout="this.style.background='rgba(255,255,255,0.08)'; this.style.borderColor='rgba(255,255,255,0.12)'; this.style.transform='translateX(0)'; this.style.boxShadow='none'">
                <div style="font-size: 36px; flex-shrink: 0;">📁</div>
                <div style="flex: 1; min-width: 0;">
                    <div style="font-size: 15px; font-weight: 700; color: #fff; margin-bottom: 4px;">${folder}</div>
                    <div style="font-size: 12px; opacity: 0.75; font-family: 'Consolas', monospace; color: #fbbf24; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${fullPath}</div>
                </div>
                <div style="font-size: 24px; opacity: 0.6; flex-shrink: 0;">▶️</div>
            </div>
        `;
        });

        container.innerHTML = html;

        // Add click handlers after rendering
        container.querySelectorAll('.folder-item').forEach(item => {
            item.addEventListener('click', function() {
                const path = this.getAttribute('data-path');
                // Convert back to backslashes for Windows
                const windowsPath = path.replace(/\//g, '\\');
                console.log('📂 Clicking folder:', windowsPath);
                browseTo(windowsPath);
            });
        });
    }

    // Navigate back in history
    function navigateBack() {
        if (historyIndex > 0) {
            historyIndex--;
            const path = browseHistory[historyIndex];
            console.log('⬅️ Going back to:', path);
            loadPath(path);
            document.getElementById('backBtn').disabled = historyIndex <= 0;
        }
    }

    // Navigate up one level
    function navigateUp() {
        let cleanPath = currentBrowsePath.replace(/\\+$/, ''); // Remove trailing backslashes
        const lastSlash = cleanPath.lastIndexOf('\\');

        console.log('⬆️ Current path:', cleanPath, 'Last slash at:', lastSlash);

        if (lastSlash > 2) {
            // Not at root, go up one level
            const parent = cleanPath.substring(0, lastSlash + 1);
            console.log('⬆️ Going up to:', parent);
            browseToPath(parent);
        } else if (lastSlash === 2 && cleanPath.charAt(1) === ':') {
            // At a folder directly under drive root, go to drive root
            const drive = cleanPath.substring(0, 3); // e.g., "C:\"
            console.log('⬆️ Going to drive root:', drive);
            browseToPath(drive);
        } else {
            console.log('⬆️ Already at root');
        }
    }

    // Refresh current folder
    function refreshBrowser() {
        console.log('🔄 Refreshing:', currentBrowsePath);
        browseToPath(currentBrowsePath);
    }

    // Load path (for back button - no history modification)
    async function loadPath(path) {
        // Normalize path
        path = path.replace(/\//g, '\\');
        if (!path.endsWith('\\')) {
            path += '\\';
        }

        currentBrowsePath = path;
        selectedFolderPath = path;
        document.getElementById('currentPathBar').textContent = path;
        document.getElementById('selectedPath').textContent = path;
        document.getElementById('selectBtn').disabled = false;

        console.log('📂 Loading path:', path);

        try {
            const formData = new FormData();
            formData.append('action', 'browse_folders');
            formData.append('path', path);

            const response = await fetch('appmaker.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            console.log('📥 Load result:', result);

            if (result.success) {
                renderFoldersList(result.folders, path);
            } else {
                document.getElementById('foldersList').innerHTML = `
                <div style="text-align: center; padding: 60px 20px; color: #fca5a5;">
                    <div style="font-size: 64px; margin-bottom: 20px;">⚠️</div>
                    <div style="font-size: 16px; font-weight: 600; margin-bottom: 10px;">Cannot Access</div>
                    <div style="font-size: 14px; opacity: 0.9;">${result.message}</div>
                </div>
            `;
            }
        } catch (error) {
            console.error('❌ Load error:', error);
            document.getElementById('foldersList').innerHTML = `
            <div style="text-align: center; padding: 60px 20px; color: #fca5a5;">
                <div style="font-size: 64px; margin-bottom: 20px;">❌</div>
                <div style="font-size: 16px; font-weight: 600;">Error</div>
                <div style="font-size: 14px; margin-top: 8px;">${error.message}</div>
            </div>
        `;
        }
    }

    // Select current folder
    function selectCurrentFolder() {
        if (!selectedFolderPath) {
            showCustomToast('⚠️ No folder selected', 'error');
            return;
        }

        // Set the appropriate input based on currentFolderType
        const inputId = currentFolderType === 'backend' ? 'backendFolder' : 'frontendFolder';
        const input = document.getElementById(inputId);

        input.value = selectedFolderPath;
        saveFolder(selectedFolderPath, currentFolderType);
        closeFileBrowser();

        // Visual feedback
        input.style.background = 'rgba(34, 197, 94, 0.25)';
        input.style.borderColor = '#22c55e';
        input.style.boxShadow = '0 0 20px rgba(34, 197, 94, 0.4)';

        setTimeout(() => {
            input.style.background = '';
            input.style.borderColor = '';
            input.style.boxShadow = '';
        }, 1200);

        const folderType = currentFolderType === 'backend' ? 'Backend' : 'Frontend';
        showCustomToast(`✅ ${folderType} folder selected: ${selectedFolderPath}`, 'success');
    }

    // Custom Toast Notification
    function showCustomToast(message, type = 'success') {
        const colors = {
            success: 'linear-gradient(135deg, #22c55e 0%, #15803d 100%)',
            error: 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)',
            info: 'linear-gradient(135deg, #3b82f6 0%, #1e40af 100%)'
        };

        const toast = document.createElement('div');
        toast.style.cssText =
            `position: fixed; bottom: 30px; right: 30px; background: ${colors[type]}; color: white; padding: 16px 28px; border-radius: 12px; border: 2px solid rgba(255,255,255,0.3); z-index: 9999; box-shadow: 0 8px 25px rgba(0,0,0,0.4); font-size: 15px; font-weight: 600; animation: slideInUp 0.4s ease-out;`;
        toast.innerHTML = message;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.animation = 'slideOutDown 0.4s ease-out';
            setTimeout(() => {
                if (toast.parentNode) document.body.removeChild(toast);
            }, 400);
        }, 3000);
    }

    // Drag & Drop Functions
    let draggedFieldData = null;

    function dragStart(event) {
        const fieldData = JSON.parse(event.target.getAttribute('data-field'));
        draggedFieldData = fieldData;
        event.target.classList.add('dragging');
        event.dataTransfer.effectAllowed = 'copy';
    }

    function allowDrop(event) {
        event.preventDefault();
        event.target.closest('.table-builder').classList.add('drag-over');
    }

    function dragLeave(event) {
        if (event.target.classList.contains('table-builder')) {
            event.target.classList.remove('drag-over');
        }
    }

    function drop(event) {
        event.preventDefault();
        document.querySelector('.table-builder').classList.remove('drag-over');

        if (!draggedFieldData) return;

        // Generate unique field name
        let baseName = draggedFieldData.label.toLowerCase().replace(/\s+/g, '_');
        let fieldName = baseName;
        let counter = 1;

        // Check for duplicates
        while (droppedFields.some(f => f.fieldName === fieldName)) {
            fieldName = baseName + '_' + counter;
            counter++;
        }

        // Add to dropped fields
        const fieldId = 'field_' + Date.now();
        const field = {
            id: fieldId,
            ...draggedFieldData,
            fieldName: fieldName
        };

        droppedFields.push(field);
        renderBuilderContent();

        // Show success animation
        setTimeout(() => {
            const fieldEl = document.getElementById(fieldId);
            if (fieldEl) {
                fieldEl.style.animation = 'none';
                setTimeout(() => {
                    fieldEl.style.animation = 'dropIn 0.3s ease-out';
                }, 10);
            }
        }, 10);

        // Reset
        draggedFieldData = null;
        document.querySelectorAll('.dragging').forEach(el => el.classList.remove('dragging'));
    }

    function renderBuilderContent() {
        const container = document.getElementById('builderContent');

        // Update counter
        document.getElementById('fieldsCounter').textContent =
            `📊 ${droppedFields.length} Field${droppedFields.length !== 1 ? 's' : ''}`;

        if (droppedFields.length === 0) {
            container.className = 'builder-empty';
            container.innerHTML = `
            <div class="builder-empty-icon">📋</div>
            <div style="font-size: 18px; margin-bottom: 10px;">Drag fields here to build your table</div>
            <div style="font-size: 14px;">Start by dragging an ID field, then add other fields</div>
        `;
            return;
        }

        container.className = '';
        container.innerHTML = droppedFields.map((field, index) => `
        <div class="dropped-field" id="${field.id}">
            <div class="field-header">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="display: flex; flex-direction: column; gap: 3px;">
                        <button onclick="moveFieldUp(${index})" ${index === 0 ? 'disabled' : ''} style="background: rgba(59,130,246,0.3); border: 1px solid rgba(59,130,246,0.5); color: white; padding: 2px 6px; border-radius: 4px; cursor: pointer; font-size: 10px;" title="Move up">▲</button>
                        <button onclick="moveFieldDown(${index})" ${index === droppedFields.length - 1 ? 'disabled' : ''} style="background: rgba(59,130,246,0.3); border: 1px solid rgba(59,130,246,0.5); color: white; padding: 2px 6px; border-radius: 4px; cursor: pointer; font-size: 10px;" title="Move down">▼</button>
                    </div>
                    <span style="font-size: 24px;">${field.icon}</span>
                    <div>
                        <input type="text" value="${field.fieldName}" onchange="updateFieldName('${field.id}', this.value)" 
                               style="background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); padding: 6px 10px; border-radius: 6px; color: #fff; font-weight: bold; font-size: 14px;">
                        <div style="font-size: 11px; margin-top: 3px; opacity: 0.8;">${field.sqlType}${field.length ? '(' + field.length + ')' : ''}</div>
                    </div>
                </div>
                <button onclick="removeField('${field.id}')" style="background: #ef4444; border: none; color: white; padding: 8px 12px; border-radius: 6px; cursor: pointer; font-size: 12px;">
                    🗑️ Remove
                </button>
            </div>
            
            <div class="field-controls">
                <div class="field-control-item">
                    <div class="toggle-switch ${field.notNull ? 'active' : ''}" onclick="toggleFieldProperty('${field.id}', 'notNull')"></div>
                    <span>NOT NULL</span>
                </div>
                <div class="field-control-item">
                    <div class="toggle-switch ${field.autoIncrement ? 'active' : ''}" onclick="toggleFieldProperty('${field.id}', 'autoIncrement')"></div>
                    <span>AUTO_INCREMENT</span>
                </div>
                <div class="field-control-item">
                    <div class="toggle-switch ${field.primaryKey ? 'active' : ''}" onclick="toggleFieldProperty('${field.id}', 'primaryKey')"></div>
                    <span>PRIMARY KEY</span>
                </div>
                <div class="field-control-item">
                    <div class="toggle-switch ${field.unique ? 'active' : ''}" onclick="toggleFieldProperty('${field.id}', 'unique')"></div>
                    <span>UNIQUE</span>
                </div>
            </div>
            
            <div style="margin-top: 10px; display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div>
                    <label style="font-size: 11px; opacity: 0.8;">Type:</label>
                    <select onchange="updateFieldType('${field.id}', this.value)" style="width: 100%; padding: 6px; background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.3); border-radius: 6px; color: #fff; font-size: 12px;">
                        <option value="INT" ${field.sqlType === 'INT' ? 'selected' : ''}>INT</option>
                        <option value="VARCHAR" ${field.sqlType === 'VARCHAR' ? 'selected' : ''}>VARCHAR</option>
                        <option value="TEXT" ${field.sqlType === 'TEXT' ? 'selected' : ''}>TEXT</option>
                        <option value="DATE" ${field.sqlType === 'DATE' ? 'selected' : ''}>DATE</option>
                        <option value="DATETIME" ${field.sqlType === 'DATETIME' ? 'selected' : ''}>DATETIME</option>
                        <option value="TIMESTAMP" ${field.sqlType === 'TIMESTAMP' ? 'selected' : ''}>TIMESTAMP</option>
                        <option value="DECIMAL" ${field.sqlType === 'DECIMAL' ? 'selected' : ''}>DECIMAL</option>
                        <option value="TINYINT" ${field.sqlType === 'TINYINT' ? 'selected' : ''}>TINYINT</option>
                    </select>
                </div>
                <div>
                    <label style="font-size: 11px; opacity: 0.8;">Length/Values:</label>
                    <input type="text" value="${field.length || ''}" onchange="updateFieldLength('${field.id}', this.value)" 
                           placeholder="e.g., 255, 10,2" style="width: 100%; padding: 6px; background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.3); border-radius: 6px; color: #fff; font-size: 12px;">
                </div>
            </div>
        </div>
    `).join('');
    }

    function updateFieldName(id, newName) {
        const field = droppedFields.find(f => f.id === id);
        if (field) field.fieldName = newName;
    }

    function updateFieldType(id, newType) {
        const field = droppedFields.find(f => f.id === id);
        if (field) {
            field.sqlType = newType;
            renderBuilderContent();
        }
    }

    function updateFieldLength(id, newLength) {
        const field = droppedFields.find(f => f.id === id);
        if (field) field.length = newLength;
    }

    function toggleFieldProperty(id, property) {
        const field = droppedFields.find(f => f.id === id);
        if (field) {
            field[property] = !field[property];
            renderBuilderContent();
        }
    }

    function removeField(id) {
        droppedFields = droppedFields.filter(f => f.id !== id);
        renderBuilderContent();
    }

    function moveFieldUp(index) {
        if (index === 0) return;
        [droppedFields[index - 1], droppedFields[index]] = [droppedFields[index], droppedFields[index - 1]];
        renderBuilderContent();
    }

    function moveFieldDown(index) {
        if (index === droppedFields.length - 1) return;
        [droppedFields[index], droppedFields[index + 1]] = [droppedFields[index + 1], droppedFields[index]];
        renderBuilderContent();
    }

    function clearTable() {
        if (droppedFields.length === 0) return;

        showConfirmDialog(
            'Clear All Fields?',
            `Remove all ${droppedFields.length} field(s) from the table builder?`,
            () => {
                droppedFields = [];
                renderBuilderContent();
                showCustomToast('✅ All fields cleared!', 'success');
            }
        );
    }

    function showConfirmDialog(title, message, onConfirm) {
        const modal = document.createElement('div');
        modal.style.cssText =
            'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); backdrop-filter: blur(8px); z-index: 3500; display: flex; align-items: center; justify-content: center; animation: fadeIn 0.3s ease-out;';

        modal.innerHTML = `
        <div style="background: linear-gradient(135deg, rgba(102,126,234,0.98) 0%, rgba(118,75,162,0.98) 100%); border-radius: 20px; padding: 30px; max-width: 500px; width: 90%; box-shadow: 0 25px 80px rgba(0,0,0,0.6); border: 2px solid rgba(255,255,255,0.2);">
            <div style="text-align: center; margin-bottom: 25px;">
                <div style="font-size: 56px; margin-bottom: 15px;">⚠️</div>
                <h3 style="color: #fbbf24; font-size: 20px; font-weight: 700; margin-bottom: 12px;">${title}</h3>
                <p style="font-size: 15px; opacity: 0.95; line-height: 1.6;">${message}</p>
            </div>
            <div style="display: flex; gap: 12px;">
                <button onclick="this.closest('[style*=fixed]').remove()" style="flex: 1; padding: 12px; background: rgba(239,68,68,0.3); border: 1px solid rgba(239,68,68,0.5); border-radius: 10px; color: #fff; cursor: pointer; font-weight: 600; font-size: 14px;">Cancel</button>
                <button onclick="(${onConfirm.toString()})(); this.closest('[style*=fixed]').remove();" style="flex: 1; padding: 12px; background: linear-gradient(135deg, #22c55e 0%, #15803d 100%); border: none; border-radius: 10px; color: #fff; cursor: pointer; font-weight: 700; font-size: 15px;">Confirm</button>
            </div>
        </div>
    `;

        document.body.appendChild(modal);
    }

    function addQuickTemplate() {
        const applyTemplate = () => {
            droppedFields = [{
                    id: 'field_' + Date.now() + '_1',
                    type: 'id',
                    label: 'ID',
                    sqlType: 'INT',
                    length: '11',
                    autoIncrement: true,
                    primaryKey: true,
                    notNull: true,
                    icon: '🔑',
                    fieldName: 'id'
                },
                {
                    id: 'field_' + Date.now() + '_2',
                    type: 'text',
                    label: 'Name',
                    sqlType: 'VARCHAR',
                    length: '255',
                    notNull: true,
                    icon: '📝',
                    fieldName: 'name'
                },
                {
                    id: 'field_' + Date.now() + '_3',
                    type: 'created_at',
                    label: 'Created At',
                    sqlType: 'TIMESTAMP',
                    defaultValue: 'CURRENT_TIMESTAMP',
                    notNull: true,
                    icon: '📅',
                    fieldName: 'created_at'
                },
                {
                    id: 'field_' + Date.now() + '_4',
                    type: 'updated_at',
                    label: 'Updated At',
                    sqlType: 'TIMESTAMP',
                    defaultValue: 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
                    notNull: true,
                    icon: '🔄',
                    fieldName: 'updated_at'
                }
            ];

            renderBuilderContent();
            showCustomToast('✅ Quick template applied!', 'success');
        };

        if (droppedFields.length > 0) {
            showConfirmDialog(
                'Replace Fields?',
                `Replace existing ${droppedFields.length} field(s) with quick template?`,
                applyTemplate
            );
        } else {
            applyTemplate();
        }
    }

    // ========================================
    // TABLE TEMPLATES SYSTEM
    // ========================================

    const TABLE_TEMPLATES = {
        'Human Resources': {
            icon: '👥',
            color: '#3b82f6',
            templates: {
                'Employees': [{
                        type: 'id',
                        label: 'ID',
                        sqlType: 'INT',
                        length: '11',
                        autoIncrement: true,
                        primaryKey: true,
                        notNull: true,
                        icon: '🔑',
                        fieldName: 'id'
                    },
                    {
                        type: 'text',
                        label: 'First Name',
                        sqlType: 'VARCHAR',
                        length: '100',
                        notNull: true,
                        icon: '📝',
                        fieldName: 'first_name'
                    },
                    {
                        type: 'text',
                        label: 'Last Name',
                        sqlType: 'VARCHAR',
                        length: '100',
                        notNull: true,
                        icon: '📝',
                        fieldName: 'last_name'
                    },
                    {
                        type: 'email',
                        label: 'Email',
                        sqlType: 'VARCHAR',
                        length: '100',
                        unique: true,
                        notNull: true,
                        icon: '📧',
                        fieldName: 'email'
                    },
                    {
                        type: 'phone',
                        label: 'Phone',
                        sqlType: 'VARCHAR',
                        length: '20',
                        icon: '📞',
                        fieldName: 'phone'
                    },
                    {
                        type: 'text',
                        label: 'Position',
                        sqlType: 'VARCHAR',
                        length: '100',
                        icon: '💼',
                        fieldName: 'position'
                    },
                    {
                        type: 'number',
                        label: 'Department ID',
                        sqlType: 'INT',
                        length: '11',
                        icon: '🏢',
                        fieldName: 'department_id'
                    },
                    {
                        type: 'date',
                        label: 'Hire Date',
                        sqlType: 'DATE',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'hire_date'
                    },
                    {
                        type: 'decimal',
                        label: 'Salary',
                        sqlType: 'DECIMAL',
                        length: '10,2',
                        icon: '💰',
                        fieldName: 'salary'
                    },
                    {
                        type: 'status',
                        label: 'Status',
                        sqlType: 'VARCHAR',
                        length: '20',
                        defaultValue: 'active',
                        icon: '⚡',
                        fieldName: 'status'
                    },
                    {
                        type: 'created_at',
                        label: 'Created At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'created_at'
                    }
                ],
                'Departments': [{
                        type: 'id',
                        label: 'ID',
                        sqlType: 'INT',
                        length: '11',
                        autoIncrement: true,
                        primaryKey: true,
                        notNull: true,
                        icon: '🔑',
                        fieldName: 'id'
                    },
                    {
                        type: 'text',
                        label: 'Department Name',
                        sqlType: 'VARCHAR',
                        length: '100',
                        notNull: true,
                        unique: true,
                        icon: '🏢',
                        fieldName: 'name'
                    },
                    {
                        type: 'text',
                        label: 'Description',
                        sqlType: 'TEXT',
                        icon: '📄',
                        fieldName: 'description'
                    },
                    {
                        type: 'number',
                        label: 'Manager ID',
                        sqlType: 'INT',
                        length: '11',
                        icon: '👤',
                        fieldName: 'manager_id'
                    },
                    {
                        type: 'text',
                        label: 'Location',
                        sqlType: 'VARCHAR',
                        length: '200',
                        icon: '📍',
                        fieldName: 'location'
                    },
                    {
                        type: 'created_at',
                        label: 'Created At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'created_at'
                    }
                ],
                'Attendance': [{
                        type: 'id',
                        label: 'ID',
                        sqlType: 'INT',
                        length: '11',
                        autoIncrement: true,
                        primaryKey: true,
                        notNull: true,
                        icon: '🔑',
                        fieldName: 'id'
                    },
                    {
                        type: 'number',
                        label: 'Employee ID',
                        sqlType: 'INT',
                        length: '11',
                        notNull: true,
                        icon: '👤',
                        fieldName: 'employee_id'
                    },
                    {
                        type: 'date',
                        label: 'Date',
                        sqlType: 'DATE',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'date'
                    },
                    {
                        type: 'datetime',
                        label: 'Check In',
                        sqlType: 'DATETIME',
                        icon: '🕐',
                        fieldName: 'check_in'
                    },
                    {
                        type: 'datetime',
                        label: 'Check Out',
                        sqlType: 'DATETIME',
                        icon: '🕐',
                        fieldName: 'check_out'
                    },
                    {
                        type: 'decimal',
                        label: 'Hours Worked',
                        sqlType: 'DECIMAL',
                        length: '5,2',
                        icon: '⏱️',
                        fieldName: 'hours_worked'
                    },
                    {
                        type: 'status',
                        label: 'Status',
                        sqlType: 'VARCHAR',
                        length: '20',
                        defaultValue: 'present',
                        icon: '⚡',
                        fieldName: 'status'
                    },
                    {
                        type: 'longtext',
                        label: 'Notes',
                        sqlType: 'TEXT',
                        icon: '📄',
                        fieldName: 'notes'
                    }
                ],
                'Leave Requests': [{
                        type: 'id',
                        label: 'ID',
                        sqlType: 'INT',
                        length: '11',
                        autoIncrement: true,
                        primaryKey: true,
                        notNull: true,
                        icon: '🔑',
                        fieldName: 'id'
                    },
                    {
                        type: 'number',
                        label: 'Employee ID',
                        sqlType: 'INT',
                        length: '11',
                        notNull: true,
                        icon: '👤',
                        fieldName: 'employee_id'
                    },
                    {
                        type: 'text',
                        label: 'Leave Type',
                        sqlType: 'VARCHAR',
                        length: '50',
                        notNull: true,
                        icon: '🏖️',
                        fieldName: 'leave_type'
                    },
                    {
                        type: 'date',
                        label: 'Start Date',
                        sqlType: 'DATE',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'start_date'
                    },
                    {
                        type: 'date',
                        label: 'End Date',
                        sqlType: 'DATE',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'end_date'
                    },
                    {
                        type: 'number',
                        label: 'Days Count',
                        sqlType: 'INT',
                        length: '11',
                        icon: '🔢',
                        fieldName: 'days_count'
                    },
                    {
                        type: 'longtext',
                        label: 'Reason',
                        sqlType: 'TEXT',
                        notNull: true,
                        icon: '📄',
                        fieldName: 'reason'
                    },
                    {
                        type: 'status',
                        label: 'Status',
                        sqlType: 'VARCHAR',
                        length: '20',
                        defaultValue: 'pending',
                        icon: '⚡',
                        fieldName: 'status'
                    },
                    {
                        type: 'number',
                        label: 'Approved By',
                        sqlType: 'INT',
                        length: '11',
                        icon: '✅',
                        fieldName: 'approved_by'
                    },
                    {
                        type: 'created_at',
                        label: 'Created At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'created_at'
                    }
                ]
            }
        },
        'E-commerce': {
            icon: '🛒',
            color: '#10b981',
            templates: {
                'Products': [{
                        type: 'id',
                        label: 'ID',
                        sqlType: 'INT',
                        length: '11',
                        autoIncrement: true,
                        primaryKey: true,
                        notNull: true,
                        icon: '🔑',
                        fieldName: 'id'
                    },
                    {
                        type: 'text',
                        label: 'Product Name',
                        sqlType: 'VARCHAR',
                        length: '255',
                        notNull: true,
                        icon: '📦',
                        fieldName: 'name'
                    },
                    {
                        type: 'slug',
                        label: 'Slug',
                        sqlType: 'VARCHAR',
                        length: '255',
                        unique: true,
                        notNull: true,
                        icon: '🔗',
                        fieldName: 'slug'
                    },
                    {
                        type: 'text',
                        label: 'SKU',
                        sqlType: 'VARCHAR',
                        length: '100',
                        unique: true,
                        icon: '🏷️',
                        fieldName: 'sku'
                    },
                    {
                        type: 'longtext',
                        label: 'Description',
                        sqlType: 'TEXT',
                        icon: '📄',
                        fieldName: 'description'
                    },
                    {
                        type: 'decimal',
                        label: 'Price',
                        sqlType: 'DECIMAL',
                        length: '10,2',
                        notNull: true,
                        icon: '💰',
                        fieldName: 'price'
                    },
                    {
                        type: 'decimal',
                        label: 'Sale Price',
                        sqlType: 'DECIMAL',
                        length: '10,2',
                        icon: '💸',
                        fieldName: 'sale_price'
                    },
                    {
                        type: 'number',
                        label: 'Stock Quantity',
                        sqlType: 'INT',
                        length: '11',
                        defaultValue: '0',
                        icon: '📊',
                        fieldName: 'stock_quantity'
                    },
                    {
                        type: 'number',
                        label: 'Category ID',
                        sqlType: 'INT',
                        length: '11',
                        icon: '📂',
                        fieldName: 'category_id'
                    },
                    {
                        type: 'image',
                        label: 'Image Path',
                        sqlType: 'VARCHAR',
                        length: '500',
                        icon: '🖼️',
                        fieldName: 'image_path'
                    },
                    {
                        type: 'boolean',
                        label: 'Is Featured',
                        sqlType: 'TINYINT',
                        length: '1',
                        defaultValue: '0',
                        icon: '⭐',
                        fieldName: 'is_featured'
                    },
                    {
                        type: 'status',
                        label: 'Status',
                        sqlType: 'VARCHAR',
                        length: '20',
                        defaultValue: 'active',
                        icon: '⚡',
                        fieldName: 'status'
                    },
                    {
                        type: 'created_at',
                        label: 'Created At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'created_at'
                    },
                    {
                        type: 'updated_at',
                        label: 'Updated At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
                        icon: '🔄',
                        fieldName: 'updated_at'
                    }
                ],
                'Categories': [{
                        type: 'id',
                        label: 'ID',
                        sqlType: 'INT',
                        length: '11',
                        autoIncrement: true,
                        primaryKey: true,
                        notNull: true,
                        icon: '🔑',
                        fieldName: 'id'
                    },
                    {
                        type: 'text',
                        label: 'Category Name',
                        sqlType: 'VARCHAR',
                        length: '100',
                        notNull: true,
                        icon: '📂',
                        fieldName: 'name'
                    },
                    {
                        type: 'slug',
                        label: 'Slug',
                        sqlType: 'VARCHAR',
                        length: '100',
                        unique: true,
                        icon: '🔗',
                        fieldName: 'slug'
                    },
                    {
                        type: 'longtext',
                        label: 'Description',
                        sqlType: 'TEXT',
                        icon: '📄',
                        fieldName: 'description'
                    },
                    {
                        type: 'number',
                        label: 'Parent ID',
                        sqlType: 'INT',
                        length: '11',
                        icon: '🔼',
                        fieldName: 'parent_id'
                    },
                    {
                        type: 'image',
                        label: 'Image',
                        sqlType: 'VARCHAR',
                        length: '500',
                        icon: '🖼️',
                        fieldName: 'image'
                    },
                    {
                        type: 'status',
                        label: 'Status',
                        sqlType: 'VARCHAR',
                        length: '20',
                        defaultValue: 'active',
                        icon: '⚡',
                        fieldName: 'status'
                    }
                ],
                'Orders': [{
                        type: 'id',
                        label: 'ID',
                        sqlType: 'INT',
                        length: '11',
                        autoIncrement: true,
                        primaryKey: true,
                        notNull: true,
                        icon: '🔑',
                        fieldName: 'id'
                    },
                    {
                        type: 'text',
                        label: 'Order Number',
                        sqlType: 'VARCHAR',
                        length: '50',
                        unique: true,
                        notNull: true,
                        icon: '🔢',
                        fieldName: 'order_number'
                    },
                    {
                        type: 'number',
                        label: 'Customer ID',
                        sqlType: 'INT',
                        length: '11',
                        notNull: true,
                        icon: '👤',
                        fieldName: 'customer_id'
                    },
                    {
                        type: 'decimal',
                        label: 'Subtotal',
                        sqlType: 'DECIMAL',
                        length: '10,2',
                        notNull: true,
                        icon: '💵',
                        fieldName: 'subtotal'
                    },
                    {
                        type: 'decimal',
                        label: 'Tax',
                        sqlType: 'DECIMAL',
                        length: '10,2',
                        defaultValue: '0.00',
                        icon: '📊',
                        fieldName: 'tax'
                    },
                    {
                        type: 'decimal',
                        label: 'Shipping',
                        sqlType: 'DECIMAL',
                        length: '10,2',
                        defaultValue: '0.00',
                        icon: '🚚',
                        fieldName: 'shipping'
                    },
                    {
                        type: 'decimal',
                        label: 'Total',
                        sqlType: 'DECIMAL',
                        length: '10,2',
                        notNull: true,
                        icon: '💰',
                        fieldName: 'total'
                    },
                    {
                        type: 'text',
                        label: 'Payment Method',
                        sqlType: 'VARCHAR',
                        length: '50',
                        icon: '💳',
                        fieldName: 'payment_method'
                    },
                    {
                        type: 'status',
                        label: 'Payment Status',
                        sqlType: 'VARCHAR',
                        length: '20',
                        defaultValue: 'pending',
                        icon: '💵',
                        fieldName: 'payment_status'
                    },
                    {
                        type: 'status',
                        label: 'Order Status',
                        sqlType: 'VARCHAR',
                        length: '20',
                        defaultValue: 'pending',
                        icon: '⚡',
                        fieldName: 'order_status'
                    },
                    {
                        type: 'longtext',
                        label: 'Shipping Address',
                        sqlType: 'TEXT',
                        icon: '📍',
                        fieldName: 'shipping_address'
                    },
                    {
                        type: 'longtext',
                        label: 'Notes',
                        sqlType: 'TEXT',
                        icon: '📄',
                        fieldName: 'notes'
                    },
                    {
                        type: 'created_at',
                        label: 'Created At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'created_at'
                    }
                ],
                'Customers': [{
                        type: 'id',
                        label: 'ID',
                        sqlType: 'INT',
                        length: '11',
                        autoIncrement: true,
                        primaryKey: true,
                        notNull: true,
                        icon: '🔑',
                        fieldName: 'id'
                    },
                    {
                        type: 'text',
                        label: 'First Name',
                        sqlType: 'VARCHAR',
                        length: '100',
                        notNull: true,
                        icon: '📝',
                        fieldName: 'first_name'
                    },
                    {
                        type: 'text',
                        label: 'Last Name',
                        sqlType: 'VARCHAR',
                        length: '100',
                        notNull: true,
                        icon: '📝',
                        fieldName: 'last_name'
                    },
                    {
                        type: 'email',
                        label: 'Email',
                        sqlType: 'VARCHAR',
                        length: '100',
                        unique: true,
                        notNull: true,
                        icon: '📧',
                        fieldName: 'email'
                    },
                    {
                        type: 'phone',
                        label: 'Phone',
                        sqlType: 'VARCHAR',
                        length: '20',
                        icon: '📞',
                        fieldName: 'phone'
                    },
                    {
                        type: 'longtext',
                        label: 'Address',
                        sqlType: 'TEXT',
                        icon: '📍',
                        fieldName: 'address'
                    },
                    {
                        type: 'text',
                        label: 'City',
                        sqlType: 'VARCHAR',
                        length: '100',
                        icon: '🏙️',
                        fieldName: 'city'
                    },
                    {
                        type: 'text',
                        label: 'Country',
                        sqlType: 'VARCHAR',
                        length: '100',
                        icon: '🌍',
                        fieldName: 'country'
                    },
                    {
                        type: 'text',
                        label: 'Postal Code',
                        sqlType: 'VARCHAR',
                        length: '20',
                        icon: '📮',
                        fieldName: 'postal_code'
                    },
                    {
                        type: 'created_at',
                        label: 'Created At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'created_at'
                    }
                ]
            }
        },
        'Blog & CMS': {
            icon: '📰',
            color: '#8b5cf6',
            templates: {
                'Posts': [{
                        type: 'id',
                        label: 'ID',
                        sqlType: 'INT',
                        length: '11',
                        autoIncrement: true,
                        primaryKey: true,
                        notNull: true,
                        icon: '🔑',
                        fieldName: 'id'
                    },
                    {
                        type: 'text',
                        label: 'Title',
                        sqlType: 'VARCHAR',
                        length: '255',
                        notNull: true,
                        icon: '📰',
                        fieldName: 'title'
                    },
                    {
                        type: 'slug',
                        label: 'Slug',
                        sqlType: 'VARCHAR',
                        length: '255',
                        unique: true,
                        notNull: true,
                        icon: '🔗',
                        fieldName: 'slug'
                    },
                    {
                        type: 'longtext',
                        label: 'Content',
                        sqlType: 'TEXT',
                        notNull: true,
                        icon: '📄',
                        fieldName: 'content'
                    },
                    {
                        type: 'longtext',
                        label: 'Excerpt',
                        sqlType: 'TEXT',
                        icon: '📝',
                        fieldName: 'excerpt'
                    },
                    {
                        type: 'number',
                        label: 'Author ID',
                        sqlType: 'INT',
                        length: '11',
                        notNull: true,
                        icon: '✍️',
                        fieldName: 'author_id'
                    },
                    {
                        type: 'number',
                        label: 'Category ID',
                        sqlType: 'INT',
                        length: '11',
                        icon: '📂',
                        fieldName: 'category_id'
                    },
                    {
                        type: 'image',
                        label: 'Featured Image',
                        sqlType: 'VARCHAR',
                        length: '500',
                        icon: '🖼️',
                        fieldName: 'featured_image'
                    },
                    {
                        type: 'number',
                        label: 'Views Count',
                        sqlType: 'INT',
                        length: '11',
                        defaultValue: '0',
                        icon: '👁️',
                        fieldName: 'views_count'
                    },
                    {
                        type: 'status',
                        label: 'Status',
                        sqlType: 'VARCHAR',
                        length: '20',
                        defaultValue: 'draft',
                        icon: '⚡',
                        fieldName: 'status'
                    },
                    {
                        type: 'datetime',
                        label: 'Published At',
                        sqlType: 'DATETIME',
                        icon: '📅',
                        fieldName: 'published_at'
                    },
                    {
                        type: 'created_at',
                        label: 'Created At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'created_at'
                    },
                    {
                        type: 'updated_at',
                        label: 'Updated At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
                        icon: '🔄',
                        fieldName: 'updated_at'
                    }
                ],
                'Comments': [{
                        type: 'id',
                        label: 'ID',
                        sqlType: 'INT',
                        length: '11',
                        autoIncrement: true,
                        primaryKey: true,
                        notNull: true,
                        icon: '🔑',
                        fieldName: 'id'
                    },
                    {
                        type: 'number',
                        label: 'Post ID',
                        sqlType: 'INT',
                        length: '11',
                        notNull: true,
                        icon: '📰',
                        fieldName: 'post_id'
                    },
                    {
                        type: 'number',
                        label: 'User ID',
                        sqlType: 'INT',
                        length: '11',
                        icon: '👤',
                        fieldName: 'user_id'
                    },
                    {
                        type: 'text',
                        label: 'Author Name',
                        sqlType: 'VARCHAR',
                        length: '100',
                        notNull: true,
                        icon: '📝',
                        fieldName: 'author_name'
                    },
                    {
                        type: 'email',
                        label: 'Author Email',
                        sqlType: 'VARCHAR',
                        length: '100',
                        icon: '📧',
                        fieldName: 'author_email'
                    },
                    {
                        type: 'longtext',
                        label: 'Content',
                        sqlType: 'TEXT',
                        notNull: true,
                        icon: '💬',
                        fieldName: 'content'
                    },
                    {
                        type: 'number',
                        label: 'Parent ID',
                        sqlType: 'INT',
                        length: '11',
                        icon: '🔼',
                        fieldName: 'parent_id'
                    },
                    {
                        type: 'status',
                        label: 'Status',
                        sqlType: 'VARCHAR',
                        length: '20',
                        defaultValue: 'pending',
                        icon: '⚡',
                        fieldName: 'status'
                    },
                    {
                        type: 'created_at',
                        label: 'Created At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'created_at'
                    }
                ],
                'Pages': [{
                        type: 'id',
                        label: 'ID',
                        sqlType: 'INT',
                        length: '11',
                        autoIncrement: true,
                        primaryKey: true,
                        notNull: true,
                        icon: '🔑',
                        fieldName: 'id'
                    },
                    {
                        type: 'text',
                        label: 'Title',
                        sqlType: 'VARCHAR',
                        length: '255',
                        notNull: true,
                        icon: '📄',
                        fieldName: 'title'
                    },
                    {
                        type: 'slug',
                        label: 'Slug',
                        sqlType: 'VARCHAR',
                        length: '255',
                        unique: true,
                        notNull: true,
                        icon: '🔗',
                        fieldName: 'slug'
                    },
                    {
                        type: 'longtext',
                        label: 'Content',
                        sqlType: 'TEXT',
                        notNull: true,
                        icon: '📝',
                        fieldName: 'content'
                    },
                    {
                        type: 'number',
                        label: 'Parent ID',
                        sqlType: 'INT',
                        length: '11',
                        icon: '🔼',
                        fieldName: 'parent_id'
                    },
                    {
                        type: 'text',
                        label: 'Template',
                        sqlType: 'VARCHAR',
                        length: '100',
                        icon: '🎨',
                        fieldName: 'template'
                    },
                    {
                        type: 'status',
                        label: 'Status',
                        sqlType: 'VARCHAR',
                        length: '20',
                        defaultValue: 'published',
                        icon: '⚡',
                        fieldName: 'status'
                    },
                    {
                        type: 'created_at',
                        label: 'Created At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'created_at'
                    },
                    {
                        type: 'updated_at',
                        label: 'Updated At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
                        icon: '🔄',
                        fieldName: 'updated_at'
                    }
                ]
            }
        },
        'Registration & Auth': {
            icon: '🔐',
            color: '#ef4444',
            templates: {
                'Users': [{
                        type: 'id',
                        label: 'ID',
                        sqlType: 'INT',
                        length: '11',
                        autoIncrement: true,
                        primaryKey: true,
                        notNull: true,
                        icon: '🔑',
                        fieldName: 'id'
                    },
                    {
                        type: 'text',
                        label: 'Username',
                        sqlType: 'VARCHAR',
                        length: '50',
                        unique: true,
                        notNull: true,
                        icon: '👤',
                        fieldName: 'username'
                    },
                    {
                        type: 'email',
                        label: 'Email',
                        sqlType: 'VARCHAR',
                        length: '100',
                        unique: true,
                        notNull: true,
                        icon: '📧',
                        fieldName: 'email'
                    },
                    {
                        type: 'password',
                        label: 'Password Hash',
                        sqlType: 'VARCHAR',
                        length: '255',
                        notNull: true,
                        icon: '🔐',
                        fieldName: 'password_hash'
                    },
                    {
                        type: 'text',
                        label: 'First Name',
                        sqlType: 'VARCHAR',
                        length: '100',
                        icon: '📝',
                        fieldName: 'first_name'
                    },
                    {
                        type: 'text',
                        label: 'Last Name',
                        sqlType: 'VARCHAR',
                        length: '100',
                        icon: '📝',
                        fieldName: 'last_name'
                    },
                    {
                        type: 'image',
                        label: 'Avatar',
                        sqlType: 'VARCHAR',
                        length: '500',
                        icon: '🖼️',
                        fieldName: 'avatar'
                    },
                    {
                        type: 'phone',
                        label: 'Phone',
                        sqlType: 'VARCHAR',
                        length: '20',
                        icon: '📞',
                        fieldName: 'phone'
                    },
                    {
                        type: 'datetime',
                        label: 'Last Login',
                        sqlType: 'DATETIME',
                        icon: '🕐',
                        fieldName: 'last_login'
                    },
                    {
                        type: 'text',
                        label: 'IP Address',
                        sqlType: 'VARCHAR',
                        length: '45',
                        icon: '🌐',
                        fieldName: 'ip_address'
                    },
                    {
                        type: 'boolean',
                        label: 'Is Active',
                        sqlType: 'TINYINT',
                        length: '1',
                        defaultValue: '1',
                        icon: '✅',
                        fieldName: 'is_active'
                    },
                    {
                        type: 'boolean',
                        label: 'Email Verified',
                        sqlType: 'TINYINT',
                        length: '1',
                        defaultValue: '0',
                        icon: '✉️',
                        fieldName: 'email_verified'
                    },
                    {
                        type: 'datetime',
                        label: 'Email Verified At',
                        sqlType: 'DATETIME',
                        icon: '📧',
                        fieldName: 'email_verified_at'
                    },
                    {
                        type: 'created_at',
                        label: 'Created At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'created_at'
                    },
                    {
                        type: 'updated_at',
                        label: 'Updated At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
                        icon: '🔄',
                        fieldName: 'updated_at'
                    }
                ],
                'User Profiles': [{
                        type: 'id',
                        label: 'ID',
                        sqlType: 'INT',
                        length: '11',
                        autoIncrement: true,
                        primaryKey: true,
                        notNull: true,
                        icon: '🔑',
                        fieldName: 'id'
                    },
                    {
                        type: 'number',
                        label: 'User ID',
                        sqlType: 'INT',
                        length: '11',
                        unique: true,
                        notNull: true,
                        icon: '👤',
                        fieldName: 'user_id'
                    },
                    {
                        type: 'text',
                        label: 'Bio',
                        sqlType: 'TEXT',
                        icon: '📝',
                        fieldName: 'bio'
                    },
                    {
                        type: 'date',
                        label: 'Date of Birth',
                        sqlType: 'DATE',
                        icon: '🎂',
                        fieldName: 'date_of_birth'
                    },
                    {
                        type: 'text',
                        label: 'Gender',
                        sqlType: 'VARCHAR',
                        length: '20',
                        icon: '⚧️',
                        fieldName: 'gender'
                    },
                    {
                        type: 'text',
                        label: 'Country',
                        sqlType: 'VARCHAR',
                        length: '100',
                        icon: '🌍',
                        fieldName: 'country'
                    },
                    {
                        type: 'text',
                        label: 'City',
                        sqlType: 'VARCHAR',
                        length: '100',
                        icon: '🏙️',
                        fieldName: 'city'
                    },
                    {
                        type: 'longtext',
                        label: 'Address',
                        sqlType: 'TEXT',
                        icon: '📍',
                        fieldName: 'address'
                    },
                    {
                        type: 'text',
                        label: 'Postal Code',
                        sqlType: 'VARCHAR',
                        length: '20',
                        icon: '📮',
                        fieldName: 'postal_code'
                    },
                    {
                        type: 'text',
                        label: 'Website',
                        sqlType: 'VARCHAR',
                        length: '255',
                        icon: '🌐',
                        fieldName: 'website'
                    },
                    {
                        type: 'text',
                        label: 'Company',
                        sqlType: 'VARCHAR',
                        length: '100',
                        icon: '🏢',
                        fieldName: 'company'
                    },
                    {
                        type: 'text',
                        label: 'Job Title',
                        sqlType: 'VARCHAR',
                        length: '100',
                        icon: '💼',
                        fieldName: 'job_title'
                    },
                    {
                        type: 'created_at',
                        label: 'Created At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'created_at'
                    },
                    {
                        type: 'updated_at',
                        label: 'Updated At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
                        icon: '🔄',
                        fieldName: 'updated_at'
                    }
                ],
                'Roles': [{
                        type: 'id',
                        label: 'ID',
                        sqlType: 'INT',
                        length: '11',
                        autoIncrement: true,
                        primaryKey: true,
                        notNull: true,
                        icon: '🔑',
                        fieldName: 'id'
                    },
                    {
                        type: 'text',
                        label: 'Role Name',
                        sqlType: 'VARCHAR',
                        length: '50',
                        unique: true,
                        notNull: true,
                        icon: '🎭',
                        fieldName: 'name'
                    },
                    {
                        type: 'text',
                        label: 'Slug',
                        sqlType: 'VARCHAR',
                        length: '50',
                        unique: true,
                        notNull: true,
                        icon: '🔗',
                        fieldName: 'slug'
                    },
                    {
                        type: 'text',
                        label: 'Display Name',
                        sqlType: 'VARCHAR',
                        length: '100',
                        icon: '📝',
                        fieldName: 'display_name'
                    },
                    {
                        type: 'longtext',
                        label: 'Description',
                        sqlType: 'TEXT',
                        icon: '📄',
                        fieldName: 'description'
                    },
                    {
                        type: 'number',
                        label: 'Level',
                        sqlType: 'INT',
                        length: '11',
                        defaultValue: '1',
                        icon: '📊',
                        fieldName: 'level'
                    },
                    {
                        type: 'boolean',
                        label: 'Is Active',
                        sqlType: 'TINYINT',
                        length: '1',
                        defaultValue: '1',
                        icon: '✅',
                        fieldName: 'is_active'
                    },
                    {
                        type: 'created_at',
                        label: 'Created At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'created_at'
                    },
                    {
                        type: 'updated_at',
                        label: 'Updated At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
                        icon: '🔄',
                        fieldName: 'updated_at'
                    }
                ],
                'Permissions': [{
                        type: 'id',
                        label: 'ID',
                        sqlType: 'INT',
                        length: '11',
                        autoIncrement: true,
                        primaryKey: true,
                        notNull: true,
                        icon: '🔑',
                        fieldName: 'id'
                    },
                    {
                        type: 'text',
                        label: 'Permission Name',
                        sqlType: 'VARCHAR',
                        length: '100',
                        unique: true,
                        notNull: true,
                        icon: '🔓',
                        fieldName: 'name'
                    },
                    {
                        type: 'text',
                        label: 'Slug',
                        sqlType: 'VARCHAR',
                        length: '100',
                        unique: true,
                        notNull: true,
                        icon: '🔗',
                        fieldName: 'slug'
                    },
                    {
                        type: 'text',
                        label: 'Display Name',
                        sqlType: 'VARCHAR',
                        length: '100',
                        icon: '📝',
                        fieldName: 'display_name'
                    },
                    {
                        type: 'text',
                        label: 'Category',
                        sqlType: 'VARCHAR',
                        length: '50',
                        icon: '📂',
                        fieldName: 'category'
                    },
                    {
                        type: 'longtext',
                        label: 'Description',
                        sqlType: 'TEXT',
                        icon: '📄',
                        fieldName: 'description'
                    },
                    {
                        type: 'created_at',
                        label: 'Created At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'created_at'
                    }
                ],
                'User Roles': [{
                        type: 'id',
                        label: 'ID',
                        sqlType: 'INT',
                        length: '11',
                        autoIncrement: true,
                        primaryKey: true,
                        notNull: true,
                        icon: '🔑',
                        fieldName: 'id'
                    },
                    {
                        type: 'number',
                        label: 'User ID',
                        sqlType: 'INT',
                        length: '11',
                        notNull: true,
                        icon: '👤',
                        fieldName: 'user_id'
                    },
                    {
                        type: 'number',
                        label: 'Role ID',
                        sqlType: 'INT',
                        length: '11',
                        notNull: true,
                        icon: '🎭',
                        fieldName: 'role_id'
                    },
                    {
                        type: 'datetime',
                        label: 'Assigned At',
                        sqlType: 'DATETIME',
                        icon: '📅',
                        fieldName: 'assigned_at'
                    },
                    {
                        type: 'number',
                        label: 'Assigned By',
                        sqlType: 'INT',
                        length: '11',
                        icon: '👨‍💼',
                        fieldName: 'assigned_by'
                    },
                    {
                        type: 'datetime',
                        label: 'Expires At',
                        sqlType: 'DATETIME',
                        icon: '⏰',
                        fieldName: 'expires_at'
                    },
                    {
                        type: 'created_at',
                        label: 'Created At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'created_at'
                    }
                ],
                'Role Permissions': [{
                        type: 'id',
                        label: 'ID',
                        sqlType: 'INT',
                        length: '11',
                        autoIncrement: true,
                        primaryKey: true,
                        notNull: true,
                        icon: '🔑',
                        fieldName: 'id'
                    },
                    {
                        type: 'number',
                        label: 'Role ID',
                        sqlType: 'INT',
                        length: '11',
                        notNull: true,
                        icon: '🎭',
                        fieldName: 'role_id'
                    },
                    {
                        type: 'number',
                        label: 'Permission ID',
                        sqlType: 'INT',
                        length: '11',
                        notNull: true,
                        icon: '🔓',
                        fieldName: 'permission_id'
                    },
                    {
                        type: 'created_at',
                        label: 'Created At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'created_at'
                    }
                ],
                'Subscription Tiers': [{
                        type: 'id',
                        label: 'ID',
                        sqlType: 'INT',
                        length: '11',
                        autoIncrement: true,
                        primaryKey: true,
                        notNull: true,
                        icon: '🔑',
                        fieldName: 'id'
                    },
                    {
                        type: 'text',
                        label: 'Tier Name',
                        sqlType: 'VARCHAR',
                        length: '50',
                        unique: true,
                        notNull: true,
                        icon: '🏆',
                        fieldName: 'name'
                    },
                    {
                        type: 'slug',
                        label: 'Slug',
                        sqlType: 'VARCHAR',
                        length: '50',
                        unique: true,
                        notNull: true,
                        icon: '🔗',
                        fieldName: 'slug'
                    },
                    {
                        type: 'text',
                        label: 'Display Name',
                        sqlType: 'VARCHAR',
                        length: '100',
                        icon: '📝',
                        fieldName: 'display_name'
                    },
                    {
                        type: 'longtext',
                        label: 'Description',
                        sqlType: 'TEXT',
                        icon: '📄',
                        fieldName: 'description'
                    },
                    {
                        type: 'decimal',
                        label: 'Price Monthly',
                        sqlType: 'DECIMAL',
                        length: '10,2',
                        defaultValue: '0.00',
                        icon: '💰',
                        fieldName: 'price_monthly'
                    },
                    {
                        type: 'decimal',
                        label: 'Price Yearly',
                        sqlType: 'DECIMAL',
                        length: '10,2',
                        defaultValue: '0.00',
                        icon: '💵',
                        fieldName: 'price_yearly'
                    },
                    {
                        type: 'text',
                        label: 'Currency',
                        sqlType: 'VARCHAR',
                        length: '10',
                        defaultValue: 'USD',
                        icon: '💱',
                        fieldName: 'currency'
                    },
                    {
                        type: 'number',
                        label: 'Max Users',
                        sqlType: 'INT',
                        length: '11',
                        icon: '👥',
                        fieldName: 'max_users'
                    },
                    {
                        type: 'number',
                        label: 'Max Projects',
                        sqlType: 'INT',
                        length: '11',
                        icon: '📊',
                        fieldName: 'max_projects'
                    },
                    {
                        type: 'number',
                        label: 'Storage Limit MB',
                        sqlType: 'INT',
                        length: '11',
                        icon: '💾',
                        fieldName: 'storage_limit_mb'
                    },
                    {
                        type: 'json',
                        label: 'Features JSON',
                        sqlType: 'TEXT',
                        icon: '📦',
                        fieldName: 'features'
                    },
                    {
                        type: 'number',
                        label: 'Sort Order',
                        sqlType: 'INT',
                        length: '11',
                        defaultValue: '0',
                        icon: '🔢',
                        fieldName: 'sort_order'
                    },
                    {
                        type: 'boolean',
                        label: 'Is Popular',
                        sqlType: 'TINYINT',
                        length: '1',
                        defaultValue: '0',
                        icon: '⭐',
                        fieldName: 'is_popular'
                    },
                    {
                        type: 'boolean',
                        label: 'Is Active',
                        sqlType: 'TINYINT',
                        length: '1',
                        defaultValue: '1',
                        icon: '✅',
                        fieldName: 'is_active'
                    },
                    {
                        type: 'created_at',
                        label: 'Created At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'created_at'
                    },
                    {
                        type: 'updated_at',
                        label: 'Updated At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
                        icon: '🔄',
                        fieldName: 'updated_at'
                    }
                ],
                'User Subscriptions': [{
                        type: 'id',
                        label: 'ID',
                        sqlType: 'INT',
                        length: '11',
                        autoIncrement: true,
                        primaryKey: true,
                        notNull: true,
                        icon: '🔑',
                        fieldName: 'id'
                    },
                    {
                        type: 'number',
                        label: 'User ID',
                        sqlType: 'INT',
                        length: '11',
                        notNull: true,
                        icon: '👤',
                        fieldName: 'user_id'
                    },
                    {
                        type: 'number',
                        label: 'Tier ID',
                        sqlType: 'INT',
                        length: '11',
                        notNull: true,
                        icon: '🏆',
                        fieldName: 'tier_id'
                    },
                    {
                        type: 'text',
                        label: 'Billing Cycle',
                        sqlType: 'VARCHAR',
                        length: '20',
                        notNull: true,
                        icon: '🔄',
                        fieldName: 'billing_cycle'
                    },
                    {
                        type: 'datetime',
                        label: 'Start Date',
                        sqlType: 'DATETIME',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'start_date'
                    },
                    {
                        type: 'datetime',
                        label: 'End Date',
                        sqlType: 'DATETIME',
                        icon: '📅',
                        fieldName: 'end_date'
                    },
                    {
                        type: 'datetime',
                        label: 'Trial End Date',
                        sqlType: 'DATETIME',
                        icon: '🎁',
                        fieldName: 'trial_end_date'
                    },
                    {
                        type: 'status',
                        label: 'Status',
                        sqlType: 'VARCHAR',
                        length: '20',
                        defaultValue: 'active',
                        icon: '⚡',
                        fieldName: 'status'
                    },
                    {
                        type: 'boolean',
                        label: 'Auto Renew',
                        sqlType: 'TINYINT',
                        length: '1',
                        defaultValue: '1',
                        icon: '♻️',
                        fieldName: 'auto_renew'
                    },
                    {
                        type: 'datetime',
                        label: 'Canceled At',
                        sqlType: 'DATETIME',
                        icon: '❌',
                        fieldName: 'canceled_at'
                    },
                    {
                        type: 'longtext',
                        label: 'Cancellation Reason',
                        sqlType: 'TEXT',
                        icon: '📄',
                        fieldName: 'cancellation_reason'
                    },
                    {
                        type: 'created_at',
                        label: 'Created At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'created_at'
                    },
                    {
                        type: 'updated_at',
                        label: 'Updated At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
                        icon: '🔄',
                        fieldName: 'updated_at'
                    }
                ],
                'Email Verifications': [{
                        type: 'id',
                        label: 'ID',
                        sqlType: 'INT',
                        length: '11',
                        autoIncrement: true,
                        primaryKey: true,
                        notNull: true,
                        icon: '🔑',
                        fieldName: 'id'
                    },
                    {
                        type: 'number',
                        label: 'User ID',
                        sqlType: 'INT',
                        length: '11',
                        notNull: true,
                        icon: '👤',
                        fieldName: 'user_id'
                    },
                    {
                        type: 'email',
                        label: 'Email',
                        sqlType: 'VARCHAR',
                        length: '100',
                        notNull: true,
                        icon: '📧',
                        fieldName: 'email'
                    },
                    {
                        type: 'text',
                        label: 'Token',
                        sqlType: 'VARCHAR',
                        length: '255',
                        unique: true,
                        notNull: true,
                        icon: '🎫',
                        fieldName: 'token'
                    },
                    {
                        type: 'datetime',
                        label: 'Expires At',
                        sqlType: 'DATETIME',
                        notNull: true,
                        icon: '⏰',
                        fieldName: 'expires_at'
                    },
                    {
                        type: 'boolean',
                        label: 'Is Verified',
                        sqlType: 'TINYINT',
                        length: '1',
                        defaultValue: '0',
                        icon: '✅',
                        fieldName: 'is_verified'
                    },
                    {
                        type: 'datetime',
                        label: 'Verified At',
                        sqlType: 'DATETIME',
                        icon: '📅',
                        fieldName: 'verified_at'
                    },
                    {
                        type: 'text',
                        label: 'IP Address',
                        sqlType: 'VARCHAR',
                        length: '45',
                        icon: '🌐',
                        fieldName: 'ip_address'
                    },
                    {
                        type: 'created_at',
                        label: 'Created At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'created_at'
                    }
                ],
                'Password Resets': [{
                        type: 'id',
                        label: 'ID',
                        sqlType: 'INT',
                        length: '11',
                        autoIncrement: true,
                        primaryKey: true,
                        notNull: true,
                        icon: '🔑',
                        fieldName: 'id'
                    },
                    {
                        type: 'email',
                        label: 'Email',
                        sqlType: 'VARCHAR',
                        length: '100',
                        notNull: true,
                        icon: '📧',
                        fieldName: 'email'
                    },
                    {
                        type: 'text',
                        label: 'Token',
                        sqlType: 'VARCHAR',
                        length: '255',
                        notNull: true,
                        icon: '🎫',
                        fieldName: 'token'
                    },
                    {
                        type: 'datetime',
                        label: 'Expires At',
                        sqlType: 'DATETIME',
                        notNull: true,
                        icon: '⏰',
                        fieldName: 'expires_at'
                    },
                    {
                        type: 'boolean',
                        label: 'Is Used',
                        sqlType: 'TINYINT',
                        length: '1',
                        defaultValue: '0',
                        icon: '✅',
                        fieldName: 'is_used'
                    },
                    {
                        type: 'datetime',
                        label: 'Used At',
                        sqlType: 'DATETIME',
                        icon: '📅',
                        fieldName: 'used_at'
                    },
                    {
                        type: 'text',
                        label: 'IP Address',
                        sqlType: 'VARCHAR',
                        length: '45',
                        icon: '🌐',
                        fieldName: 'ip_address'
                    },
                    {
                        type: 'created_at',
                        label: 'Created At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'created_at'
                    }
                ],
                'Login History': [{
                        type: 'id',
                        label: 'ID',
                        sqlType: 'INT',
                        length: '11',
                        autoIncrement: true,
                        primaryKey: true,
                        notNull: true,
                        icon: '🔑',
                        fieldName: 'id'
                    },
                    {
                        type: 'number',
                        label: 'User ID',
                        sqlType: 'INT',
                        length: '11',
                        notNull: true,
                        icon: '👤',
                        fieldName: 'user_id'
                    },
                    {
                        type: 'text',
                        label: 'IP Address',
                        sqlType: 'VARCHAR',
                        length: '45',
                        notNull: true,
                        icon: '🌐',
                        fieldName: 'ip_address'
                    },
                    {
                        type: 'text',
                        label: 'User Agent',
                        sqlType: 'VARCHAR',
                        length: '255',
                        icon: '🖥️',
                        fieldName: 'user_agent'
                    },
                    {
                        type: 'text',
                        label: 'Device Type',
                        sqlType: 'VARCHAR',
                        length: '50',
                        icon: '📱',
                        fieldName: 'device_type'
                    },
                    {
                        type: 'text',
                        label: 'Browser',
                        sqlType: 'VARCHAR',
                        length: '50',
                        icon: '🌐',
                        fieldName: 'browser'
                    },
                    {
                        type: 'text',
                        label: 'Operating System',
                        sqlType: 'VARCHAR',
                        length: '50',
                        icon: '💻',
                        fieldName: 'operating_system'
                    },
                    {
                        type: 'text',
                        label: 'Country',
                        sqlType: 'VARCHAR',
                        length: '100',
                        icon: '🌍',
                        fieldName: 'country'
                    },
                    {
                        type: 'text',
                        label: 'City',
                        sqlType: 'VARCHAR',
                        length: '100',
                        icon: '🏙️',
                        fieldName: 'city'
                    },
                    {
                        type: 'boolean',
                        label: 'Login Successful',
                        sqlType: 'TINYINT',
                        length: '1',
                        notNull: true,
                        icon: '✅',
                        fieldName: 'login_successful'
                    },
                    {
                        type: 'text',
                        label: 'Failure Reason',
                        sqlType: 'VARCHAR',
                        length: '255',
                        icon: '❌',
                        fieldName: 'failure_reason'
                    },
                    {
                        type: 'datetime',
                        label: 'Login At',
                        sqlType: 'DATETIME',
                        notNull: true,
                        icon: '🕐',
                        fieldName: 'login_at'
                    },
                    {
                        type: 'datetime',
                        label: 'Logout At',
                        sqlType: 'DATETIME',
                        icon: '🕐',
                        fieldName: 'logout_at'
                    },
                    {
                        type: 'created_at',
                        label: 'Created At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'created_at'
                    }
                ],
                'Social Logins': [{
                        type: 'id',
                        label: 'ID',
                        sqlType: 'INT',
                        length: '11',
                        autoIncrement: true,
                        primaryKey: true,
                        notNull: true,
                        icon: '🔑',
                        fieldName: 'id'
                    },
                    {
                        type: 'number',
                        label: 'User ID',
                        sqlType: 'INT',
                        length: '11',
                        notNull: true,
                        icon: '👤',
                        fieldName: 'user_id'
                    },
                    {
                        type: 'text',
                        label: 'Provider',
                        sqlType: 'VARCHAR',
                        length: '50',
                        notNull: true,
                        icon: '🔗',
                        fieldName: 'provider'
                    },
                    {
                        type: 'text',
                        label: 'Provider User ID',
                        sqlType: 'VARCHAR',
                        length: '255',
                        notNull: true,
                        icon: '🆔',
                        fieldName: 'provider_user_id'
                    },
                    {
                        type: 'text',
                        label: 'Access Token',
                        sqlType: 'VARCHAR',
                        length: '500',
                        icon: '🎫',
                        fieldName: 'access_token'
                    },
                    {
                        type: 'text',
                        label: 'Refresh Token',
                        sqlType: 'VARCHAR',
                        length: '500',
                        icon: '🔄',
                        fieldName: 'refresh_token'
                    },
                    {
                        type: 'datetime',
                        label: 'Token Expires At',
                        sqlType: 'DATETIME',
                        icon: '⏰',
                        fieldName: 'token_expires_at'
                    },
                    {
                        type: 'json',
                        label: 'Provider Data',
                        sqlType: 'TEXT',
                        icon: '📦',
                        fieldName: 'provider_data'
                    },
                    {
                        type: 'created_at',
                        label: 'Created At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'created_at'
                    },
                    {
                        type: 'updated_at',
                        label: 'Updated At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
                        icon: '🔄',
                        fieldName: 'updated_at'
                    }
                ],
                'Two Factor Auth': [{
                        type: 'id',
                        label: 'ID',
                        sqlType: 'INT',
                        length: '11',
                        autoIncrement: true,
                        primaryKey: true,
                        notNull: true,
                        icon: '🔑',
                        fieldName: 'id'
                    },
                    {
                        type: 'number',
                        label: 'User ID',
                        sqlType: 'INT',
                        length: '11',
                        unique: true,
                        notNull: true,
                        icon: '👤',
                        fieldName: 'user_id'
                    },
                    {
                        type: 'text',
                        label: 'Secret Key',
                        sqlType: 'VARCHAR',
                        length: '255',
                        notNull: true,
                        icon: '🔐',
                        fieldName: 'secret_key'
                    },
                    {
                        type: 'text',
                        label: 'Recovery Codes',
                        sqlType: 'TEXT',
                        icon: '🔑',
                        fieldName: 'recovery_codes'
                    },
                    {
                        type: 'boolean',
                        label: 'Is Enabled',
                        sqlType: 'TINYINT',
                        length: '1',
                        defaultValue: '0',
                        icon: '✅',
                        fieldName: 'is_enabled'
                    },
                    {
                        type: 'datetime',
                        label: 'Enabled At',
                        sqlType: 'DATETIME',
                        icon: '📅',
                        fieldName: 'enabled_at'
                    },
                    {
                        type: 'datetime',
                        label: 'Last Used At',
                        sqlType: 'DATETIME',
                        icon: '🕐',
                        fieldName: 'last_used_at'
                    },
                    {
                        type: 'created_at',
                        label: 'Created At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'created_at'
                    },
                    {
                        type: 'updated_at',
                        label: 'Updated At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
                        icon: '🔄',
                        fieldName: 'updated_at'
                    }
                ]
            }
        },
        'Education': {
            icon: '🎓',
            color: '#f59e0b',
            templates: {
                'Students': [{
                        type: 'id',
                        label: 'ID',
                        sqlType: 'INT',
                        length: '11',
                        autoIncrement: true,
                        primaryKey: true,
                        notNull: true,
                        icon: '🔑',
                        fieldName: 'id'
                    },
                    {
                        type: 'text',
                        label: 'Student ID',
                        sqlType: 'VARCHAR',
                        length: '50',
                        unique: true,
                        notNull: true,
                        icon: '🎓',
                        fieldName: 'student_id'
                    },
                    {
                        type: 'text',
                        label: 'First Name',
                        sqlType: 'VARCHAR',
                        length: '100',
                        notNull: true,
                        icon: '📝',
                        fieldName: 'first_name'
                    },
                    {
                        type: 'text',
                        label: 'Last Name',
                        sqlType: 'VARCHAR',
                        length: '100',
                        notNull: true,
                        icon: '📝',
                        fieldName: 'last_name'
                    },
                    {
                        type: 'email',
                        label: 'Email',
                        sqlType: 'VARCHAR',
                        length: '100',
                        unique: true,
                        icon: '📧',
                        fieldName: 'email'
                    },
                    {
                        type: 'phone',
                        label: 'Phone',
                        sqlType: 'VARCHAR',
                        length: '20',
                        icon: '📞',
                        fieldName: 'phone'
                    },
                    {
                        type: 'date',
                        label: 'Date of Birth',
                        sqlType: 'DATE',
                        icon: '🎂',
                        fieldName: 'date_of_birth'
                    },
                    {
                        type: 'text',
                        label: 'Gender',
                        sqlType: 'VARCHAR',
                        length: '10',
                        icon: '⚧️',
                        fieldName: 'gender'
                    },
                    {
                        type: 'longtext',
                        label: 'Address',
                        sqlType: 'TEXT',
                        icon: '📍',
                        fieldName: 'address'
                    },
                    {
                        type: 'date',
                        label: 'Enrollment Date',
                        sqlType: 'DATE',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'enrollment_date'
                    },
                    {
                        type: 'status',
                        label: 'Status',
                        sqlType: 'VARCHAR',
                        length: '20',
                        defaultValue: 'active',
                        icon: '⚡',
                        fieldName: 'status'
                    },
                    {
                        type: 'created_at',
                        label: 'Created At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'created_at'
                    }
                ],
                'Courses': [{
                        type: 'id',
                        label: 'ID',
                        sqlType: 'INT',
                        length: '11',
                        autoIncrement: true,
                        primaryKey: true,
                        notNull: true,
                        icon: '🔑',
                        fieldName: 'id'
                    },
                    {
                        type: 'text',
                        label: 'Course Code',
                        sqlType: 'VARCHAR',
                        length: '50',
                        unique: true,
                        notNull: true,
                        icon: '📚',
                        fieldName: 'course_code'
                    },
                    {
                        type: 'text',
                        label: 'Course Name',
                        sqlType: 'VARCHAR',
                        length: '255',
                        notNull: true,
                        icon: '📖',
                        fieldName: 'course_name'
                    },
                    {
                        type: 'longtext',
                        label: 'Description',
                        sqlType: 'TEXT',
                        icon: '📄',
                        fieldName: 'description'
                    },
                    {
                        type: 'number',
                        label: 'Credits',
                        sqlType: 'INT',
                        length: '11',
                        icon: '🎯',
                        fieldName: 'credits'
                    },
                    {
                        type: 'number',
                        label: 'Teacher ID',
                        sqlType: 'INT',
                        length: '11',
                        icon: '👨‍🏫',
                        fieldName: 'teacher_id'
                    },
                    {
                        type: 'text',
                        label: 'Semester',
                        sqlType: 'VARCHAR',
                        length: '50',
                        icon: '📆',
                        fieldName: 'semester'
                    },
                    {
                        type: 'status',
                        label: 'Status',
                        sqlType: 'VARCHAR',
                        length: '20',
                        defaultValue: 'active',
                        icon: '⚡',
                        fieldName: 'status'
                    },
                    {
                        type: 'created_at',
                        label: 'Created At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'created_at'
                    }
                ],
                'Grades': [{
                        type: 'id',
                        label: 'ID',
                        sqlType: 'INT',
                        length: '11',
                        autoIncrement: true,
                        primaryKey: true,
                        notNull: true,
                        icon: '🔑',
                        fieldName: 'id'
                    },
                    {
                        type: 'number',
                        label: 'Student ID',
                        sqlType: 'INT',
                        length: '11',
                        notNull: true,
                        icon: '🎓',
                        fieldName: 'student_id'
                    },
                    {
                        type: 'number',
                        label: 'Course ID',
                        sqlType: 'INT',
                        length: '11',
                        notNull: true,
                        icon: '📚',
                        fieldName: 'course_id'
                    },
                    {
                        type: 'number',
                        label: 'Assignment ID',
                        sqlType: 'INT',
                        length: '11',
                        icon: '📝',
                        fieldName: 'assignment_id'
                    },
                    {
                        type: 'decimal',
                        label: 'Score',
                        sqlType: 'DECIMAL',
                        length: '5,2',
                        notNull: true,
                        icon: '💯',
                        fieldName: 'score'
                    },
                    {
                        type: 'decimal',
                        label: 'Max Score',
                        sqlType: 'DECIMAL',
                        length: '5,2',
                        notNull: true,
                        icon: '🎯',
                        fieldName: 'max_score'
                    },
                    {
                        type: 'text',
                        label: 'Grade',
                        sqlType: 'VARCHAR',
                        length: '5',
                        icon: '📊',
                        fieldName: 'grade'
                    },
                    {
                        type: 'longtext',
                        label: 'Comments',
                        sqlType: 'TEXT',
                        icon: '📄',
                        fieldName: 'comments'
                    },
                    {
                        type: 'created_at',
                        label: 'Created At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'created_at'
                    }
                ]
            }
        },
        'Project Management': {
            icon: '📊',
            color: '#06b6d4',
            templates: {
                'Projects': [{
                        type: 'id',
                        label: 'ID',
                        sqlType: 'INT',
                        length: '11',
                        autoIncrement: true,
                        primaryKey: true,
                        notNull: true,
                        icon: '🔑',
                        fieldName: 'id'
                    },
                    {
                        type: 'text',
                        label: 'Project Name',
                        sqlType: 'VARCHAR',
                        length: '255',
                        notNull: true,
                        icon: '📊',
                        fieldName: 'name'
                    },
                    {
                        type: 'slug',
                        label: 'Slug',
                        sqlType: 'VARCHAR',
                        length: '255',
                        unique: true,
                        icon: '🔗',
                        fieldName: 'slug'
                    },
                    {
                        type: 'longtext',
                        label: 'Description',
                        sqlType: 'TEXT',
                        icon: '📄',
                        fieldName: 'description'
                    },
                    {
                        type: 'number',
                        label: 'Client ID',
                        sqlType: 'INT',
                        length: '11',
                        icon: '👤',
                        fieldName: 'client_id'
                    },
                    {
                        type: 'number',
                        label: 'Manager ID',
                        sqlType: 'INT',
                        length: '11',
                        icon: '👨‍💼',
                        fieldName: 'manager_id'
                    },
                    {
                        type: 'date',
                        label: 'Start Date',
                        sqlType: 'DATE',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'start_date'
                    },
                    {
                        type: 'date',
                        label: 'End Date',
                        sqlType: 'DATE',
                        icon: '📅',
                        fieldName: 'end_date'
                    },
                    {
                        type: 'decimal',
                        label: 'Budget',
                        sqlType: 'DECIMAL',
                        length: '10,2',
                        icon: '💰',
                        fieldName: 'budget'
                    },
                    {
                        type: 'status',
                        label: 'Status',
                        sqlType: 'VARCHAR',
                        length: '20',
                        defaultValue: 'planning',
                        icon: '⚡',
                        fieldName: 'status'
                    },
                    {
                        type: 'number',
                        label: 'Progress',
                        sqlType: 'INT',
                        length: '11',
                        defaultValue: '0',
                        icon: '📈',
                        fieldName: 'progress'
                    },
                    {
                        type: 'created_at',
                        label: 'Created At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'created_at'
                    },
                    {
                        type: 'updated_at',
                        label: 'Updated At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
                        icon: '🔄',
                        fieldName: 'updated_at'
                    }
                ],
                'Tasks': [{
                        type: 'id',
                        label: 'ID',
                        sqlType: 'INT',
                        length: '11',
                        autoIncrement: true,
                        primaryKey: true,
                        notNull: true,
                        icon: '🔑',
                        fieldName: 'id'
                    },
                    {
                        type: 'text',
                        label: 'Task Title',
                        sqlType: 'VARCHAR',
                        length: '255',
                        notNull: true,
                        icon: '✅',
                        fieldName: 'title'
                    },
                    {
                        type: 'longtext',
                        label: 'Description',
                        sqlType: 'TEXT',
                        icon: '📄',
                        fieldName: 'description'
                    },
                    {
                        type: 'number',
                        label: 'Project ID',
                        sqlType: 'INT',
                        length: '11',
                        notNull: true,
                        icon: '📊',
                        fieldName: 'project_id'
                    },
                    {
                        type: 'number',
                        label: 'Assigned To',
                        sqlType: 'INT',
                        length: '11',
                        icon: '👤',
                        fieldName: 'assigned_to'
                    },
                    {
                        type: 'text',
                        label: 'Priority',
                        sqlType: 'VARCHAR',
                        length: '20',
                        defaultValue: 'medium',
                        icon: '🔥',
                        fieldName: 'priority'
                    },
                    {
                        type: 'date',
                        label: 'Due Date',
                        sqlType: 'DATE',
                        icon: '📅',
                        fieldName: 'due_date'
                    },
                    {
                        type: 'number',
                        label: 'Estimated Hours',
                        sqlType: 'INT',
                        length: '11',
                        icon: '⏱️',
                        fieldName: 'estimated_hours'
                    },
                    {
                        type: 'status',
                        label: 'Status',
                        sqlType: 'VARCHAR',
                        length: '20',
                        defaultValue: 'todo',
                        icon: '⚡',
                        fieldName: 'status'
                    },
                    {
                        type: 'created_at',
                        label: 'Created At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'created_at'
                    },
                    {
                        type: 'updated_at',
                        label: 'Updated At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
                        icon: '🔄',
                        fieldName: 'updated_at'
                    }
                ]
            }
        },
        'Real Estate': {
            icon: '🏠',
            color: '#ec4899',
            templates: {
                'Properties': [{
                        type: 'id',
                        label: 'ID',
                        sqlType: 'INT',
                        length: '11',
                        autoIncrement: true,
                        primaryKey: true,
                        notNull: true,
                        icon: '🔑',
                        fieldName: 'id'
                    },
                    {
                        type: 'text',
                        label: 'Title',
                        sqlType: 'VARCHAR',
                        length: '255',
                        notNull: true,
                        icon: '🏠',
                        fieldName: 'title'
                    },
                    {
                        type: 'slug',
                        label: 'Slug',
                        sqlType: 'VARCHAR',
                        length: '255',
                        unique: true,
                        icon: '🔗',
                        fieldName: 'slug'
                    },
                    {
                        type: 'longtext',
                        label: 'Description',
                        sqlType: 'TEXT',
                        notNull: true,
                        icon: '📄',
                        fieldName: 'description'
                    },
                    {
                        type: 'text',
                        label: 'Property Type',
                        sqlType: 'VARCHAR',
                        length: '50',
                        notNull: true,
                        icon: '🏢',
                        fieldName: 'property_type'
                    },
                    {
                        type: 'decimal',
                        label: 'Price',
                        sqlType: 'DECIMAL',
                        length: '12,2',
                        notNull: true,
                        icon: '💰',
                        fieldName: 'price'
                    },
                    {
                        type: 'number',
                        label: 'Bedrooms',
                        sqlType: 'INT',
                        length: '11',
                        icon: '🛏️',
                        fieldName: 'bedrooms'
                    },
                    {
                        type: 'number',
                        label: 'Bathrooms',
                        sqlType: 'INT',
                        length: '11',
                        icon: '🚿',
                        fieldName: 'bathrooms'
                    },
                    {
                        type: 'decimal',
                        label: 'Area (sqm)',
                        sqlType: 'DECIMAL',
                        length: '10,2',
                        icon: '📐',
                        fieldName: 'area'
                    },
                    {
                        type: 'longtext',
                        label: 'Address',
                        sqlType: 'TEXT',
                        notNull: true,
                        icon: '📍',
                        fieldName: 'address'
                    },
                    {
                        type: 'text',
                        label: 'City',
                        sqlType: 'VARCHAR',
                        length: '100',
                        notNull: true,
                        icon: '🏙️',
                        fieldName: 'city'
                    },
                    {
                        type: 'text',
                        label: 'Country',
                        sqlType: 'VARCHAR',
                        length: '100',
                        icon: '🌍',
                        fieldName: 'country'
                    },
                    {
                        type: 'number',
                        label: 'Agent ID',
                        sqlType: 'INT',
                        length: '11',
                        icon: '👤',
                        fieldName: 'agent_id'
                    },
                    {
                        type: 'status',
                        label: 'Status',
                        sqlType: 'VARCHAR',
                        length: '20',
                        defaultValue: 'available',
                        icon: '⚡',
                        fieldName: 'status'
                    },
                    {
                        type: 'created_at',
                        label: 'Created At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'created_at'
                    },
                    {
                        type: 'updated_at',
                        label: 'Updated At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
                        icon: '🔄',
                        fieldName: 'updated_at'
                    }
                ]
            }
        },
        'Finance': {
            icon: '💳',
            color: '#14b8a6',
            templates: {
                'Invoices': [{
                        type: 'id',
                        label: 'ID',
                        sqlType: 'INT',
                        length: '11',
                        autoIncrement: true,
                        primaryKey: true,
                        notNull: true,
                        icon: '🔑',
                        fieldName: 'id'
                    },
                    {
                        type: 'text',
                        label: 'Invoice Number',
                        sqlType: 'VARCHAR',
                        length: '50',
                        unique: true,
                        notNull: true,
                        icon: '📄',
                        fieldName: 'invoice_number'
                    },
                    {
                        type: 'number',
                        label: 'Customer ID',
                        sqlType: 'INT',
                        length: '11',
                        notNull: true,
                        icon: '👤',
                        fieldName: 'customer_id'
                    },
                    {
                        type: 'date',
                        label: 'Invoice Date',
                        sqlType: 'DATE',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'invoice_date'
                    },
                    {
                        type: 'date',
                        label: 'Due Date',
                        sqlType: 'DATE',
                        notNull: true,
                        icon: '⏰',
                        fieldName: 'due_date'
                    },
                    {
                        type: 'decimal',
                        label: 'Subtotal',
                        sqlType: 'DECIMAL',
                        length: '10,2',
                        notNull: true,
                        icon: '💵',
                        fieldName: 'subtotal'
                    },
                    {
                        type: 'decimal',
                        label: 'Tax',
                        sqlType: 'DECIMAL',
                        length: '10,2',
                        defaultValue: '0.00',
                        icon: '📊',
                        fieldName: 'tax'
                    },
                    {
                        type: 'decimal',
                        label: 'Total',
                        sqlType: 'DECIMAL',
                        length: '10,2',
                        notNull: true,
                        icon: '💰',
                        fieldName: 'total'
                    },
                    {
                        type: 'status',
                        label: 'Status',
                        sqlType: 'VARCHAR',
                        length: '20',
                        defaultValue: 'pending',
                        icon: '⚡',
                        fieldName: 'status'
                    },
                    {
                        type: 'longtext',
                        label: 'Notes',
                        sqlType: 'TEXT',
                        icon: '📝',
                        fieldName: 'notes'
                    },
                    {
                        type: 'created_at',
                        label: 'Created At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'created_at'
                    }
                ],
                'Transactions': [{
                        type: 'id',
                        label: 'ID',
                        sqlType: 'INT',
                        length: '11',
                        autoIncrement: true,
                        primaryKey: true,
                        notNull: true,
                        icon: '🔑',
                        fieldName: 'id'
                    },
                    {
                        type: 'text',
                        label: 'Transaction ID',
                        sqlType: 'VARCHAR',
                        length: '100',
                        unique: true,
                        notNull: true,
                        icon: '💳',
                        fieldName: 'transaction_id'
                    },
                    {
                        type: 'number',
                        label: 'Account ID',
                        sqlType: 'INT',
                        length: '11',
                        notNull: true,
                        icon: '🏦',
                        fieldName: 'account_id'
                    },
                    {
                        type: 'text',
                        label: 'Type',
                        sqlType: 'VARCHAR',
                        length: '20',
                        notNull: true,
                        icon: '💸',
                        fieldName: 'type'
                    },
                    {
                        type: 'decimal',
                        label: 'Amount',
                        sqlType: 'DECIMAL',
                        length: '10,2',
                        notNull: true,
                        icon: '💰',
                        fieldName: 'amount'
                    },
                    {
                        type: 'text',
                        label: 'Currency',
                        sqlType: 'VARCHAR',
                        length: '10',
                        defaultValue: 'USD',
                        icon: '💱',
                        fieldName: 'currency'
                    },
                    {
                        type: 'longtext',
                        label: 'Description',
                        sqlType: 'TEXT',
                        icon: '📄',
                        fieldName: 'description'
                    },
                    {
                        type: 'text',
                        label: 'Category',
                        sqlType: 'VARCHAR',
                        length: '50',
                        icon: '📂',
                        fieldName: 'category'
                    },
                    {
                        type: 'datetime',
                        label: 'Transaction Date',
                        sqlType: 'DATETIME',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'transaction_date'
                    },
                    {
                        type: 'status',
                        label: 'Status',
                        sqlType: 'VARCHAR',
                        length: '20',
                        defaultValue: 'completed',
                        icon: '⚡',
                        fieldName: 'status'
                    },
                    {
                        type: 'created_at',
                        label: 'Created At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'created_at'
                    }
                ]
            }
        },
        'Accounting System': {
            icon: '📊',
            color: '#7c3aed',
            templates: {
                'Chart of Accounts': [{
                        type: 'id',
                        label: 'ID',
                        sqlType: 'INT',
                        length: '11',
                        autoIncrement: true,
                        primaryKey: true,
                        notNull: true,
                        icon: '🔑',
                        fieldName: 'id'
                    },
                    {
                        type: 'text',
                        label: 'Account Code',
                        sqlType: 'VARCHAR',
                        length: '50',
                        unique: true,
                        notNull: true,
                        icon: '🔢',
                        fieldName: 'account_code'
                    },
                    {
                        type: 'text',
                        label: 'Account Name',
                        sqlType: 'VARCHAR',
                        length: '200',
                        notNull: true,
                        icon: '📝',
                        fieldName: 'account_name'
                    },
                    {
                        type: 'text',
                        label: 'Account Type',
                        sqlType: 'VARCHAR',
                        length: '50',
                        notNull: true,
                        icon: '📂',
                        fieldName: 'account_type'
                    },
                    {
                        type: 'number',
                        label: 'Parent Account ID',
                        sqlType: 'INT',
                        length: '11',
                        icon: '🔼',
                        fieldName: 'parent_account_id'
                    },
                    {
                        type: 'text',
                        label: 'Currency',
                        sqlType: 'VARCHAR',
                        length: '10',
                        defaultValue: 'USD',
                        icon: '💱',
                        fieldName: 'currency'
                    },
                    {
                        type: 'decimal',
                        label: 'Opening Balance',
                        sqlType: 'DECIMAL',
                        length: '15,2',
                        defaultValue: '0.00',
                        icon: '💰',
                        fieldName: 'opening_balance'
                    },
                    {
                        type: 'decimal',
                        label: 'Current Balance',
                        sqlType: 'DECIMAL',
                        length: '15,2',
                        defaultValue: '0.00',
                        icon: '💵',
                        fieldName: 'current_balance'
                    },
                    {
                        type: 'longtext',
                        label: 'Description',
                        sqlType: 'TEXT',
                        icon: '📄',
                        fieldName: 'description'
                    },
                    {
                        type: 'boolean',
                        label: 'Is Active',
                        sqlType: 'TINYINT',
                        length: '1',
                        defaultValue: '1',
                        icon: '✅',
                        fieldName: 'is_active'
                    },
                    {
                        type: 'boolean',
                        label: 'Is System Account',
                        sqlType: 'TINYINT',
                        length: '1',
                        defaultValue: '0',
                        icon: '🔒',
                        fieldName: 'is_system_account'
                    },
                    {
                        type: 'number',
                        label: 'Level',
                        sqlType: 'INT',
                        length: '11',
                        defaultValue: '1',
                        icon: '📊',
                        fieldName: 'level'
                    },
                    {
                        type: 'created_at',
                        label: 'Created At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'created_at'
                    },
                    {
                        type: 'updated_at',
                        label: 'Updated At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
                        icon: '🔄',
                        fieldName: 'updated_at'
                    }
                ],
                'Journal Entries': [{
                        type: 'id',
                        label: 'ID',
                        sqlType: 'INT',
                        length: '11',
                        autoIncrement: true,
                        primaryKey: true,
                        notNull: true,
                        icon: '🔑',
                        fieldName: 'id'
                    },
                    {
                        type: 'text',
                        label: 'Entry Number',
                        sqlType: 'VARCHAR',
                        length: '50',
                        unique: true,
                        notNull: true,
                        icon: '📄',
                        fieldName: 'entry_number'
                    },
                    {
                        type: 'date',
                        label: 'Entry Date',
                        sqlType: 'DATE',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'entry_date'
                    },
                    {
                        type: 'text',
                        label: 'Entry Type',
                        sqlType: 'VARCHAR',
                        length: '50',
                        notNull: true,
                        icon: '📂',
                        fieldName: 'entry_type'
                    },
                    {
                        type: 'number',
                        label: 'Fiscal Year ID',
                        sqlType: 'INT',
                        length: '11',
                        icon: '📆',
                        fieldName: 'fiscal_year_id'
                    },
                    {
                        type: 'number',
                        label: 'Period ID',
                        sqlType: 'INT',
                        length: '11',
                        icon: '📊',
                        fieldName: 'period_id'
                    },
                    {
                        type: 'text',
                        label: 'Reference',
                        sqlType: 'VARCHAR',
                        length: '100',
                        icon: '🔗',
                        fieldName: 'reference'
                    },
                    {
                        type: 'longtext',
                        label: 'Description',
                        sqlType: 'TEXT',
                        notNull: true,
                        icon: '📝',
                        fieldName: 'description'
                    },
                    {
                        type: 'decimal',
                        label: 'Total Debit',
                        sqlType: 'DECIMAL',
                        length: '15,2',
                        defaultValue: '0.00',
                        icon: '➕',
                        fieldName: 'total_debit'
                    },
                    {
                        type: 'decimal',
                        label: 'Total Credit',
                        sqlType: 'DECIMAL',
                        length: '15,2',
                        defaultValue: '0.00',
                        icon: '➖',
                        fieldName: 'total_credit'
                    },
                    {
                        type: 'status',
                        label: 'Status',
                        sqlType: 'VARCHAR',
                        length: '20',
                        defaultValue: 'draft',
                        icon: '⚡',
                        fieldName: 'status'
                    },
                    {
                        type: 'boolean',
                        label: 'Is Reversed',
                        sqlType: 'TINYINT',
                        length: '1',
                        defaultValue: '0',
                        icon: '🔄',
                        fieldName: 'is_reversed'
                    },
                    {
                        type: 'number',
                        label: 'Reversed Entry ID',
                        sqlType: 'INT',
                        length: '11',
                        icon: '↩️',
                        fieldName: 'reversed_entry_id'
                    },
                    {
                        type: 'number',
                        label: 'Created By',
                        sqlType: 'INT',
                        length: '11',
                        icon: '👤',
                        fieldName: 'created_by'
                    },
                    {
                        type: 'number',
                        label: 'Approved By',
                        sqlType: 'INT',
                        length: '11',
                        icon: '✅',
                        fieldName: 'approved_by'
                    },
                    {
                        type: 'datetime',
                        label: 'Approved At',
                        sqlType: 'DATETIME',
                        icon: '📅',
                        fieldName: 'approved_at'
                    },
                    {
                        type: 'created_at',
                        label: 'Created At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'created_at'
                    },
                    {
                        type: 'updated_at',
                        label: 'Updated At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
                        icon: '🔄',
                        fieldName: 'updated_at'
                    }
                ],
                'Journal Entry Lines': [{
                        type: 'id',
                        label: 'ID',
                        sqlType: 'INT',
                        length: '11',
                        autoIncrement: true,
                        primaryKey: true,
                        notNull: true,
                        icon: '🔑',
                        fieldName: 'id'
                    },
                    {
                        type: 'number',
                        label: 'Entry ID',
                        sqlType: 'INT',
                        length: '11',
                        notNull: true,
                        icon: '📄',
                        fieldName: 'entry_id'
                    },
                    {
                        type: 'number',
                        label: 'Account ID',
                        sqlType: 'INT',
                        length: '11',
                        notNull: true,
                        icon: '🏦',
                        fieldName: 'account_id'
                    },
                    {
                        type: 'decimal',
                        label: 'Debit',
                        sqlType: 'DECIMAL',
                        length: '15,2',
                        defaultValue: '0.00',
                        icon: '➕',
                        fieldName: 'debit'
                    },
                    {
                        type: 'decimal',
                        label: 'Credit',
                        sqlType: 'DECIMAL',
                        length: '15,2',
                        defaultValue: '0.00',
                        icon: '➖',
                        fieldName: 'credit'
                    },
                    {
                        type: 'text',
                        label: 'Description',
                        sqlType: 'VARCHAR',
                        length: '255',
                        icon: '📝',
                        fieldName: 'description'
                    },
                    {
                        type: 'number',
                        label: 'Cost Center ID',
                        sqlType: 'INT',
                        length: '11',
                        icon: '🏢',
                        fieldName: 'cost_center_id'
                    },
                    {
                        type: 'number',
                        label: 'Line Order',
                        sqlType: 'INT',
                        length: '11',
                        defaultValue: '0',
                        icon: '🔢',
                        fieldName: 'line_order'
                    },
                    {
                        type: 'created_at',
                        label: 'Created At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'created_at'
                    }
                ],
                'Fiscal Years': [{
                        type: 'id',
                        label: 'ID',
                        sqlType: 'INT',
                        length: '11',
                        autoIncrement: true,
                        primaryKey: true,
                        notNull: true,
                        icon: '🔑',
                        fieldName: 'id'
                    },
                    {
                        type: 'text',
                        label: 'Year Name',
                        sqlType: 'VARCHAR',
                        length: '50',
                        unique: true,
                        notNull: true,
                        icon: '📆',
                        fieldName: 'year_name'
                    },
                    {
                        type: 'date',
                        label: 'Start Date',
                        sqlType: 'DATE',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'start_date'
                    },
                    {
                        type: 'date',
                        label: 'End Date',
                        sqlType: 'DATE',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'end_date'
                    },
                    {
                        type: 'boolean',
                        label: 'Is Current',
                        sqlType: 'TINYINT',
                        length: '1',
                        defaultValue: '0',
                        icon: '⭐',
                        fieldName: 'is_current'
                    },
                    {
                        type: 'boolean',
                        label: 'Is Closed',
                        sqlType: 'TINYINT',
                        length: '1',
                        defaultValue: '0',
                        icon: '🔒',
                        fieldName: 'is_closed'
                    },
                    {
                        type: 'datetime',
                        label: 'Closed At',
                        sqlType: 'DATETIME',
                        icon: '📅',
                        fieldName: 'closed_at'
                    },
                    {
                        type: 'number',
                        label: 'Closed By',
                        sqlType: 'INT',
                        length: '11',
                        icon: '👤',
                        fieldName: 'closed_by'
                    },
                    {
                        type: 'created_at',
                        label: 'Created At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'created_at'
                    }
                ],
                'Accounting Periods': [{
                        type: 'id',
                        label: 'ID',
                        sqlType: 'INT',
                        length: '11',
                        autoIncrement: true,
                        primaryKey: true,
                        notNull: true,
                        icon: '🔑',
                        fieldName: 'id'
                    },
                    {
                        type: 'number',
                        label: 'Fiscal Year ID',
                        sqlType: 'INT',
                        length: '11',
                        notNull: true,
                        icon: '📆',
                        fieldName: 'fiscal_year_id'
                    },
                    {
                        type: 'text',
                        label: 'Period Name',
                        sqlType: 'VARCHAR',
                        length: '50',
                        notNull: true,
                        icon: '📊',
                        fieldName: 'period_name'
                    },
                    {
                        type: 'number',
                        label: 'Period Number',
                        sqlType: 'INT',
                        length: '11',
                        notNull: true,
                        icon: '🔢',
                        fieldName: 'period_number'
                    },
                    {
                        type: 'date',
                        label: 'Start Date',
                        sqlType: 'DATE',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'start_date'
                    },
                    {
                        type: 'date',
                        label: 'End Date',
                        sqlType: 'DATE',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'end_date'
                    },
                    {
                        type: 'boolean',
                        label: 'Is Closed',
                        sqlType: 'TINYINT',
                        length: '1',
                        defaultValue: '0',
                        icon: '🔒',
                        fieldName: 'is_closed'
                    },
                    {
                        type: 'datetime',
                        label: 'Closed At',
                        sqlType: 'DATETIME',
                        icon: '📅',
                        fieldName: 'closed_at'
                    },
                    {
                        type: 'created_at',
                        label: 'Created At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'created_at'
                    }
                ],
                'Customers': [{
                        type: 'id',
                        label: 'ID',
                        sqlType: 'INT',
                        length: '11',
                        autoIncrement: true,
                        primaryKey: true,
                        notNull: true,
                        icon: '🔑',
                        fieldName: 'id'
                    },
                    {
                        type: 'text',
                        label: 'Customer Code',
                        sqlType: 'VARCHAR',
                        length: '50',
                        unique: true,
                        notNull: true,
                        icon: '🔢',
                        fieldName: 'customer_code'
                    },
                    {
                        type: 'text',
                        label: 'Customer Name',
                        sqlType: 'VARCHAR',
                        length: '200',
                        notNull: true,
                        icon: '👤',
                        fieldName: 'customer_name'
                    },
                    {
                        type: 'email',
                        label: 'Email',
                        sqlType: 'VARCHAR',
                        length: '100',
                        icon: '📧',
                        fieldName: 'email'
                    },
                    {
                        type: 'phone',
                        label: 'Phone',
                        sqlType: 'VARCHAR',
                        length: '20',
                        icon: '📞',
                        fieldName: 'phone'
                    },
                    {
                        type: 'longtext',
                        label: 'Address',
                        sqlType: 'TEXT',
                        icon: '📍',
                        fieldName: 'address'
                    },
                    {
                        type: 'text',
                        label: 'City',
                        sqlType: 'VARCHAR',
                        length: '100',
                        icon: '🏙️',
                        fieldName: 'city'
                    },
                    {
                        type: 'text',
                        label: 'Country',
                        sqlType: 'VARCHAR',
                        length: '100',
                        icon: '🌍',
                        fieldName: 'country'
                    },
                    {
                        type: 'text',
                        label: 'Tax Number',
                        sqlType: 'VARCHAR',
                        length: '50',
                        icon: '🏛️',
                        fieldName: 'tax_number'
                    },
                    {
                        type: 'number',
                        label: 'Payment Terms Days',
                        sqlType: 'INT',
                        length: '11',
                        defaultValue: '30',
                        icon: '📅',
                        fieldName: 'payment_terms_days'
                    },
                    {
                        type: 'decimal',
                        label: 'Credit Limit',
                        sqlType: 'DECIMAL',
                        length: '15,2',
                        defaultValue: '0.00',
                        icon: '💳',
                        fieldName: 'credit_limit'
                    },
                    {
                        type: 'decimal',
                        label: 'Current Balance',
                        sqlType: 'DECIMAL',
                        length: '15,2',
                        defaultValue: '0.00',
                        icon: '💰',
                        fieldName: 'current_balance'
                    },
                    {
                        type: 'boolean',
                        label: 'Is Active',
                        sqlType: 'TINYINT',
                        length: '1',
                        defaultValue: '1',
                        icon: '✅',
                        fieldName: 'is_active'
                    },
                    {
                        type: 'created_at',
                        label: 'Created At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'created_at'
                    },
                    {
                        type: 'updated_at',
                        label: 'Updated At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
                        icon: '🔄',
                        fieldName: 'updated_at'
                    }
                ],
                'Suppliers': [{
                        type: 'id',
                        label: 'ID',
                        sqlType: 'INT',
                        length: '11',
                        autoIncrement: true,
                        primaryKey: true,
                        notNull: true,
                        icon: '🔑',
                        fieldName: 'id'
                    },
                    {
                        type: 'text',
                        label: 'Supplier Code',
                        sqlType: 'VARCHAR',
                        length: '50',
                        unique: true,
                        notNull: true,
                        icon: '🔢',
                        fieldName: 'supplier_code'
                    },
                    {
                        type: 'text',
                        label: 'Supplier Name',
                        sqlType: 'VARCHAR',
                        length: '200',
                        notNull: true,
                        icon: '🏢',
                        fieldName: 'supplier_name'
                    },
                    {
                        type: 'email',
                        label: 'Email',
                        sqlType: 'VARCHAR',
                        length: '100',
                        icon: '📧',
                        fieldName: 'email'
                    },
                    {
                        type: 'phone',
                        label: 'Phone',
                        sqlType: 'VARCHAR',
                        length: '20',
                        icon: '📞',
                        fieldName: 'phone'
                    },
                    {
                        type: 'longtext',
                        label: 'Address',
                        sqlType: 'TEXT',
                        icon: '📍',
                        fieldName: 'address'
                    },
                    {
                        type: 'text',
                        label: 'City',
                        sqlType: 'VARCHAR',
                        length: '100',
                        icon: '🏙️',
                        fieldName: 'city'
                    },
                    {
                        type: 'text',
                        label: 'Country',
                        sqlType: 'VARCHAR',
                        length: '100',
                        icon: '🌍',
                        fieldName: 'country'
                    },
                    {
                        type: 'text',
                        label: 'Tax Number',
                        sqlType: 'VARCHAR',
                        length: '50',
                        icon: '🏛️',
                        fieldName: 'tax_number'
                    },
                    {
                        type: 'number',
                        label: 'Payment Terms Days',
                        sqlType: 'INT',
                        length: '11',
                        defaultValue: '30',
                        icon: '📅',
                        fieldName: 'payment_terms_days'
                    },
                    {
                        type: 'decimal',
                        label: 'Current Balance',
                        sqlType: 'DECIMAL',
                        length: '15,2',
                        defaultValue: '0.00',
                        icon: '💰',
                        fieldName: 'current_balance'
                    },
                    {
                        type: 'boolean',
                        label: 'Is Active',
                        sqlType: 'TINYINT',
                        length: '1',
                        defaultValue: '1',
                        icon: '✅',
                        fieldName: 'is_active'
                    },
                    {
                        type: 'created_at',
                        label: 'Created At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'created_at'
                    },
                    {
                        type: 'updated_at',
                        label: 'Updated At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
                        icon: '🔄',
                        fieldName: 'updated_at'
                    }
                ],
                'Invoices': [{
                        type: 'id',
                        label: 'ID',
                        sqlType: 'INT',
                        length: '11',
                        autoIncrement: true,
                        primaryKey: true,
                        notNull: true,
                        icon: '🔑',
                        fieldName: 'id'
                    },
                    {
                        type: 'text',
                        label: 'Invoice Number',
                        sqlType: 'VARCHAR',
                        length: '50',
                        unique: true,
                        notNull: true,
                        icon: '📄',
                        fieldName: 'invoice_number'
                    },
                    {
                        type: 'text',
                        label: 'Invoice Type',
                        sqlType: 'VARCHAR',
                        length: '20',
                        notNull: true,
                        icon: '📂',
                        fieldName: 'invoice_type'
                    },
                    {
                        type: 'number',
                        label: 'Customer ID',
                        sqlType: 'INT',
                        length: '11',
                        icon: '👤',
                        fieldName: 'customer_id'
                    },
                    {
                        type: 'number',
                        label: 'Supplier ID',
                        sqlType: 'INT',
                        length: '11',
                        icon: '🏢',
                        fieldName: 'supplier_id'
                    },
                    {
                        type: 'date',
                        label: 'Invoice Date',
                        sqlType: 'DATE',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'invoice_date'
                    },
                    {
                        type: 'date',
                        label: 'Due Date',
                        sqlType: 'DATE',
                        notNull: true,
                        icon: '⏰',
                        fieldName: 'due_date'
                    },
                    {
                        type: 'text',
                        label: 'Currency',
                        sqlType: 'VARCHAR',
                        length: '10',
                        defaultValue: 'USD',
                        icon: '💱',
                        fieldName: 'currency'
                    },
                    {
                        type: 'decimal',
                        label: 'Exchange Rate',
                        sqlType: 'DECIMAL',
                        length: '10,4',
                        defaultValue: '1.0000',
                        icon: '🔄',
                        fieldName: 'exchange_rate'
                    },
                    {
                        type: 'decimal',
                        label: 'Subtotal',
                        sqlType: 'DECIMAL',
                        length: '15,2',
                        notNull: true,
                        icon: '💵',
                        fieldName: 'subtotal'
                    },
                    {
                        type: 'decimal',
                        label: 'Tax Amount',
                        sqlType: 'DECIMAL',
                        length: '15,2',
                        defaultValue: '0.00',
                        icon: '📊',
                        fieldName: 'tax_amount'
                    },
                    {
                        type: 'decimal',
                        label: 'Discount Amount',
                        sqlType: 'DECIMAL',
                        length: '15,2',
                        defaultValue: '0.00',
                        icon: '🎁',
                        fieldName: 'discount_amount'
                    },
                    {
                        type: 'decimal',
                        label: 'Total Amount',
                        sqlType: 'DECIMAL',
                        length: '15,2',
                        notNull: true,
                        icon: '💰',
                        fieldName: 'total_amount'
                    },
                    {
                        type: 'decimal',
                        label: 'Paid Amount',
                        sqlType: 'DECIMAL',
                        length: '15,2',
                        defaultValue: '0.00',
                        icon: '💳',
                        fieldName: 'paid_amount'
                    },
                    {
                        type: 'decimal',
                        label: 'Balance',
                        sqlType: 'DECIMAL',
                        length: '15,2',
                        defaultValue: '0.00',
                        icon: '💵',
                        fieldName: 'balance'
                    },
                    {
                        type: 'status',
                        label: 'Status',
                        sqlType: 'VARCHAR',
                        length: '20',
                        defaultValue: 'draft',
                        icon: '⚡',
                        fieldName: 'status'
                    },
                    {
                        type: 'longtext',
                        label: 'Notes',
                        sqlType: 'TEXT',
                        icon: '📝',
                        fieldName: 'notes'
                    },
                    {
                        type: 'number',
                        label: 'Journal Entry ID',
                        sqlType: 'INT',
                        length: '11',
                        icon: '📄',
                        fieldName: 'journal_entry_id'
                    },
                    {
                        type: 'created_at',
                        label: 'Created At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'created_at'
                    },
                    {
                        type: 'updated_at',
                        label: 'Updated At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
                        icon: '🔄',
                        fieldName: 'updated_at'
                    }
                ],
                'Invoice Items': [{
                        type: 'id',
                        label: 'ID',
                        sqlType: 'INT',
                        length: '11',
                        autoIncrement: true,
                        primaryKey: true,
                        notNull: true,
                        icon: '🔑',
                        fieldName: 'id'
                    },
                    {
                        type: 'number',
                        label: 'Invoice ID',
                        sqlType: 'INT',
                        length: '11',
                        notNull: true,
                        icon: '📄',
                        fieldName: 'invoice_id'
                    },
                    {
                        type: 'text',
                        label: 'Item Description',
                        sqlType: 'VARCHAR',
                        length: '255',
                        notNull: true,
                        icon: '📝',
                        fieldName: 'item_description'
                    },
                    {
                        type: 'decimal',
                        label: 'Quantity',
                        sqlType: 'DECIMAL',
                        length: '10,2',
                        notNull: true,
                        icon: '🔢',
                        fieldName: 'quantity'
                    },
                    {
                        type: 'text',
                        label: 'Unit',
                        sqlType: 'VARCHAR',
                        length: '20',
                        icon: '📦',
                        fieldName: 'unit'
                    },
                    {
                        type: 'decimal',
                        label: 'Unit Price',
                        sqlType: 'DECIMAL',
                        length: '15,2',
                        notNull: true,
                        icon: '💰',
                        fieldName: 'unit_price'
                    },
                    {
                        type: 'decimal',
                        label: 'Discount Percent',
                        sqlType: 'DECIMAL',
                        length: '5,2',
                        defaultValue: '0.00',
                        icon: '🎁',
                        fieldName: 'discount_percent'
                    },
                    {
                        type: 'decimal',
                        label: 'Discount Amount',
                        sqlType: 'DECIMAL',
                        length: '15,2',
                        defaultValue: '0.00',
                        icon: '💸',
                        fieldName: 'discount_amount'
                    },
                    {
                        type: 'decimal',
                        label: 'Tax Percent',
                        sqlType: 'DECIMAL',
                        length: '5,2',
                        defaultValue: '0.00',
                        icon: '📊',
                        fieldName: 'tax_percent'
                    },
                    {
                        type: 'decimal',
                        label: 'Tax Amount',
                        sqlType: 'DECIMAL',
                        length: '15,2',
                        defaultValue: '0.00',
                        icon: '🏛️',
                        fieldName: 'tax_amount'
                    },
                    {
                        type: 'decimal',
                        label: 'Line Total',
                        sqlType: 'DECIMAL',
                        length: '15,2',
                        notNull: true,
                        icon: '💵',
                        fieldName: 'line_total'
                    },
                    {
                        type: 'number',
                        label: 'Account ID',
                        sqlType: 'INT',
                        length: '11',
                        icon: '🏦',
                        fieldName: 'account_id'
                    },
                    {
                        type: 'number',
                        label: 'Line Order',
                        sqlType: 'INT',
                        length: '11',
                        defaultValue: '0',
                        icon: '🔢',
                        fieldName: 'line_order'
                    },
                    {
                        type: 'created_at',
                        label: 'Created At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'created_at'
                    }
                ],
                'Payments': [{
                        type: 'id',
                        label: 'ID',
                        sqlType: 'INT',
                        length: '11',
                        autoIncrement: true,
                        primaryKey: true,
                        notNull: true,
                        icon: '🔑',
                        fieldName: 'id'
                    },
                    {
                        type: 'text',
                        label: 'Payment Number',
                        sqlType: 'VARCHAR',
                        length: '50',
                        unique: true,
                        notNull: true,
                        icon: '💳',
                        fieldName: 'payment_number'
                    },
                    {
                        type: 'text',
                        label: 'Payment Type',
                        sqlType: 'VARCHAR',
                        length: '20',
                        notNull: true,
                        icon: '📂',
                        fieldName: 'payment_type'
                    },
                    {
                        type: 'number',
                        label: 'Customer ID',
                        sqlType: 'INT',
                        length: '11',
                        icon: '👤',
                        fieldName: 'customer_id'
                    },
                    {
                        type: 'number',
                        label: 'Supplier ID',
                        sqlType: 'INT',
                        length: '11',
                        icon: '🏢',
                        fieldName: 'supplier_id'
                    },
                    {
                        type: 'number',
                        label: 'Invoice ID',
                        sqlType: 'INT',
                        length: '11',
                        icon: '📄',
                        fieldName: 'invoice_id'
                    },
                    {
                        type: 'date',
                        label: 'Payment Date',
                        sqlType: 'DATE',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'payment_date'
                    },
                    {
                        type: 'decimal',
                        label: 'Amount',
                        sqlType: 'DECIMAL',
                        length: '15,2',
                        notNull: true,
                        icon: '💰',
                        fieldName: 'amount'
                    },
                    {
                        type: 'text',
                        label: 'Currency',
                        sqlType: 'VARCHAR',
                        length: '10',
                        defaultValue: 'USD',
                        icon: '💱',
                        fieldName: 'currency'
                    },
                    {
                        type: 'number',
                        label: 'Payment Method ID',
                        sqlType: 'INT',
                        length: '11',
                        notNull: true,
                        icon: '💳',
                        fieldName: 'payment_method_id'
                    },
                    {
                        type: 'text',
                        label: 'Reference Number',
                        sqlType: 'VARCHAR',
                        length: '100',
                        icon: '🔗',
                        fieldName: 'reference_number'
                    },
                    {
                        type: 'longtext',
                        label: 'Notes',
                        sqlType: 'TEXT',
                        icon: '📝',
                        fieldName: 'notes'
                    },
                    {
                        type: 'number',
                        label: 'Journal Entry ID',
                        sqlType: 'INT',
                        length: '11',
                        icon: '📄',
                        fieldName: 'journal_entry_id'
                    },
                    {
                        type: 'status',
                        label: 'Status',
                        sqlType: 'VARCHAR',
                        length: '20',
                        defaultValue: 'completed',
                        icon: '⚡',
                        fieldName: 'status'
                    },
                    {
                        type: 'created_at',
                        label: 'Created At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'created_at'
                    }
                ],
                'Payment Methods': [{
                        type: 'id',
                        label: 'ID',
                        sqlType: 'INT',
                        length: '11',
                        autoIncrement: true,
                        primaryKey: true,
                        notNull: true,
                        icon: '🔑',
                        fieldName: 'id'
                    },
                    {
                        type: 'text',
                        label: 'Method Name',
                        sqlType: 'VARCHAR',
                        length: '50',
                        unique: true,
                        notNull: true,
                        icon: '💳',
                        fieldName: 'method_name'
                    },
                    {
                        type: 'text',
                        label: 'Method Type',
                        sqlType: 'VARCHAR',
                        length: '50',
                        icon: '📂',
                        fieldName: 'method_type'
                    },
                    {
                        type: 'number',
                        label: 'Default Account ID',
                        sqlType: 'INT',
                        length: '11',
                        icon: '🏦',
                        fieldName: 'default_account_id'
                    },
                    {
                        type: 'longtext',
                        label: 'Description',
                        sqlType: 'TEXT',
                        icon: '📝',
                        fieldName: 'description'
                    },
                    {
                        type: 'boolean',
                        label: 'Is Active',
                        sqlType: 'TINYINT',
                        length: '1',
                        defaultValue: '1',
                        icon: '✅',
                        fieldName: 'is_active'
                    },
                    {
                        type: 'created_at',
                        label: 'Created At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'created_at'
                    }
                ],
                'Banks': [{
                        type: 'id',
                        label: 'ID',
                        sqlType: 'INT',
                        length: '11',
                        autoIncrement: true,
                        primaryKey: true,
                        notNull: true,
                        icon: '🔑',
                        fieldName: 'id'
                    },
                    {
                        type: 'text',
                        label: 'Bank Name',
                        sqlType: 'VARCHAR',
                        length: '200',
                        notNull: true,
                        icon: '🏦',
                        fieldName: 'bank_name'
                    },
                    {
                        type: 'text',
                        label: 'Bank Code',
                        sqlType: 'VARCHAR',
                        length: '50',
                        icon: '🔢',
                        fieldName: 'bank_code'
                    },
                    {
                        type: 'text',
                        label: 'Swift Code',
                        sqlType: 'VARCHAR',
                        length: '20',
                        icon: '🌐',
                        fieldName: 'swift_code'
                    },
                    {
                        type: 'longtext',
                        label: 'Address',
                        sqlType: 'TEXT',
                        icon: '📍',
                        fieldName: 'address'
                    },
                    {
                        type: 'phone',
                        label: 'Phone',
                        sqlType: 'VARCHAR',
                        length: '20',
                        icon: '📞',
                        fieldName: 'phone'
                    },
                    {
                        type: 'email',
                        label: 'Email',
                        sqlType: 'VARCHAR',
                        length: '100',
                        icon: '📧',
                        fieldName: 'email'
                    },
                    {
                        type: 'boolean',
                        label: 'Is Active',
                        sqlType: 'TINYINT',
                        length: '1',
                        defaultValue: '1',
                        icon: '✅',
                        fieldName: 'is_active'
                    },
                    {
                        type: 'created_at',
                        label: 'Created At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'created_at'
                    }
                ],
                'Bank Accounts': [{
                        type: 'id',
                        label: 'ID',
                        sqlType: 'INT',
                        length: '11',
                        autoIncrement: true,
                        primaryKey: true,
                        notNull: true,
                        icon: '🔑',
                        fieldName: 'id'
                    },
                    {
                        type: 'number',
                        label: 'Bank ID',
                        sqlType: 'INT',
                        length: '11',
                        notNull: true,
                        icon: '🏦',
                        fieldName: 'bank_id'
                    },
                    {
                        type: 'text',
                        label: 'Account Name',
                        sqlType: 'VARCHAR',
                        length: '200',
                        notNull: true,
                        icon: '📝',
                        fieldName: 'account_name'
                    },
                    {
                        type: 'text',
                        label: 'Account Number',
                        sqlType: 'VARCHAR',
                        length: '50',
                        unique: true,
                        notNull: true,
                        icon: '🔢',
                        fieldName: 'account_number'
                    },
                    {
                        type: 'text',
                        label: 'IBAN',
                        sqlType: 'VARCHAR',
                        length: '50',
                        icon: '🌐',
                        fieldName: 'iban'
                    },
                    {
                        type: 'text',
                        label: 'Currency',
                        sqlType: 'VARCHAR',
                        length: '10',
                        defaultValue: 'USD',
                        icon: '💱',
                        fieldName: 'currency'
                    },
                    {
                        type: 'decimal',
                        label: 'Opening Balance',
                        sqlType: 'DECIMAL',
                        length: '15,2',
                        defaultValue: '0.00',
                        icon: '💰',
                        fieldName: 'opening_balance'
                    },
                    {
                        type: 'decimal',
                        label: 'Current Balance',
                        sqlType: 'DECIMAL',
                        length: '15,2',
                        defaultValue: '0.00',
                        icon: '💵',
                        fieldName: 'current_balance'
                    },
                    {
                        type: 'number',
                        label: 'GL Account ID',
                        sqlType: 'INT',
                        length: '11',
                        icon: '📊',
                        fieldName: 'gl_account_id'
                    },
                    {
                        type: 'boolean',
                        label: 'Is Active',
                        sqlType: 'TINYINT',
                        length: '1',
                        defaultValue: '1',
                        icon: '✅',
                        fieldName: 'is_active'
                    },
                    {
                        type: 'created_at',
                        label: 'Created At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'created_at'
                    },
                    {
                        type: 'updated_at',
                        label: 'Updated At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
                        icon: '🔄',
                        fieldName: 'updated_at'
                    }
                ],
                'Bank Transactions': [{
                        type: 'id',
                        label: 'ID',
                        sqlType: 'INT',
                        length: '11',
                        autoIncrement: true,
                        primaryKey: true,
                        notNull: true,
                        icon: '🔑',
                        fieldName: 'id'
                    },
                    {
                        type: 'number',
                        label: 'Bank Account ID',
                        sqlType: 'INT',
                        length: '11',
                        notNull: true,
                        icon: '🏦',
                        fieldName: 'bank_account_id'
                    },
                    {
                        type: 'date',
                        label: 'Transaction Date',
                        sqlType: 'DATE',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'transaction_date'
                    },
                    {
                        type: 'text',
                        label: 'Transaction Type',
                        sqlType: 'VARCHAR',
                        length: '20',
                        notNull: true,
                        icon: '📂',
                        fieldName: 'transaction_type'
                    },
                    {
                        type: 'text',
                        label: 'Reference Number',
                        sqlType: 'VARCHAR',
                        length: '100',
                        icon: '🔗',
                        fieldName: 'reference_number'
                    },
                    {
                        type: 'decimal',
                        label: 'Debit',
                        sqlType: 'DECIMAL',
                        length: '15,2',
                        defaultValue: '0.00',
                        icon: '➕',
                        fieldName: 'debit'
                    },
                    {
                        type: 'decimal',
                        label: 'Credit',
                        sqlType: 'DECIMAL',
                        length: '15,2',
                        defaultValue: '0.00',
                        icon: '➖',
                        fieldName: 'credit'
                    },
                    {
                        type: 'decimal',
                        label: 'Balance',
                        sqlType: 'DECIMAL',
                        length: '15,2',
                        defaultValue: '0.00',
                        icon: '💰',
                        fieldName: 'balance'
                    },
                    {
                        type: 'text',
                        label: 'Payee',
                        sqlType: 'VARCHAR',
                        length: '200',
                        icon: '👤',
                        fieldName: 'payee'
                    },
                    {
                        type: 'longtext',
                        label: 'Description',
                        sqlType: 'TEXT',
                        icon: '📝',
                        fieldName: 'description'
                    },
                    {
                        type: 'number',
                        label: 'Journal Entry ID',
                        sqlType: 'INT',
                        length: '11',
                        icon: '📄',
                        fieldName: 'journal_entry_id'
                    },
                    {
                        type: 'boolean',
                        label: 'Is Reconciled',
                        sqlType: 'TINYINT',
                        length: '1',
                        defaultValue: '0',
                        icon: '✅',
                        fieldName: 'is_reconciled'
                    },
                    {
                        type: 'datetime',
                        label: 'Reconciled At',
                        sqlType: 'DATETIME',
                        icon: '📅',
                        fieldName: 'reconciled_at'
                    },
                    {
                        type: 'created_at',
                        label: 'Created At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'created_at'
                    }
                ],
                'Expenses': [{
                        type: 'id',
                        label: 'ID',
                        sqlType: 'INT',
                        length: '11',
                        autoIncrement: true,
                        primaryKey: true,
                        notNull: true,
                        icon: '🔑',
                        fieldName: 'id'
                    },
                    {
                        type: 'text',
                        label: 'Expense Number',
                        sqlType: 'VARCHAR',
                        length: '50',
                        unique: true,
                        notNull: true,
                        icon: '📄',
                        fieldName: 'expense_number'
                    },
                    {
                        type: 'date',
                        label: 'Expense Date',
                        sqlType: 'DATE',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'expense_date'
                    },
                    {
                        type: 'number',
                        label: 'Category ID',
                        sqlType: 'INT',
                        length: '11',
                        notNull: true,
                        icon: '📂',
                        fieldName: 'category_id'
                    },
                    {
                        type: 'number',
                        label: 'Supplier ID',
                        sqlType: 'INT',
                        length: '11',
                        icon: '🏢',
                        fieldName: 'supplier_id'
                    },
                    {
                        type: 'decimal',
                        label: 'Amount',
                        sqlType: 'DECIMAL',
                        length: '15,2',
                        notNull: true,
                        icon: '💰',
                        fieldName: 'amount'
                    },
                    {
                        type: 'text',
                        label: 'Currency',
                        sqlType: 'VARCHAR',
                        length: '10',
                        defaultValue: 'USD',
                        icon: '💱',
                        fieldName: 'currency'
                    },
                    {
                        type: 'number',
                        label: 'Payment Method ID',
                        sqlType: 'INT',
                        length: '11',
                        icon: '💳',
                        fieldName: 'payment_method_id'
                    },
                    {
                        type: 'text',
                        label: 'Reference Number',
                        sqlType: 'VARCHAR',
                        length: '100',
                        icon: '🔗',
                        fieldName: 'reference_number'
                    },
                    {
                        type: 'longtext',
                        label: 'Description',
                        sqlType: 'TEXT',
                        notNull: true,
                        icon: '📝',
                        fieldName: 'description'
                    },
                    {
                        type: 'text',
                        label: 'Receipt Path',
                        sqlType: 'VARCHAR',
                        length: '500',
                        icon: '🖼️',
                        fieldName: 'receipt_path'
                    },
                    {
                        type: 'number',
                        label: 'Cost Center ID',
                        sqlType: 'INT',
                        length: '11',
                        icon: '🏢',
                        fieldName: 'cost_center_id'
                    },
                    {
                        type: 'number',
                        label: 'Journal Entry ID',
                        sqlType: 'INT',
                        length: '11',
                        icon: '📄',
                        fieldName: 'journal_entry_id'
                    },
                    {
                        type: 'status',
                        label: 'Status',
                        sqlType: 'VARCHAR',
                        length: '20',
                        defaultValue: 'pending',
                        icon: '⚡',
                        fieldName: 'status'
                    },
                    {
                        type: 'created_at',
                        label: 'Created At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'created_at'
                    }
                ],
                'Expense Categories': [{
                        type: 'id',
                        label: 'ID',
                        sqlType: 'INT',
                        length: '11',
                        autoIncrement: true,
                        primaryKey: true,
                        notNull: true,
                        icon: '🔑',
                        fieldName: 'id'
                    },
                    {
                        type: 'text',
                        label: 'Category Name',
                        sqlType: 'VARCHAR',
                        length: '100',
                        unique: true,
                        notNull: true,
                        icon: '📂',
                        fieldName: 'category_name'
                    },
                    {
                        type: 'text',
                        label: 'Category Code',
                        sqlType: 'VARCHAR',
                        length: '50',
                        icon: '🔢',
                        fieldName: 'category_code'
                    },
                    {
                        type: 'number',
                        label: 'Parent Category ID',
                        sqlType: 'INT',
                        length: '11',
                        icon: '🔼',
                        fieldName: 'parent_category_id'
                    },
                    {
                        type: 'number',
                        label: 'Default Account ID',
                        sqlType: 'INT',
                        length: '11',
                        icon: '🏦',
                        fieldName: 'default_account_id'
                    },
                    {
                        type: 'longtext',
                        label: 'Description',
                        sqlType: 'TEXT',
                        icon: '📝',
                        fieldName: 'description'
                    },
                    {
                        type: 'boolean',
                        label: 'Is Active',
                        sqlType: 'TINYINT',
                        length: '1',
                        defaultValue: '1',
                        icon: '✅',
                        fieldName: 'is_active'
                    },
                    {
                        type: 'created_at',
                        label: 'Created At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'created_at'
                    }
                ],
                'Fixed Assets': [{
                        type: 'id',
                        label: 'ID',
                        sqlType: 'INT',
                        length: '11',
                        autoIncrement: true,
                        primaryKey: true,
                        notNull: true,
                        icon: '🔑',
                        fieldName: 'id'
                    },
                    {
                        type: 'text',
                        label: 'Asset Code',
                        sqlType: 'VARCHAR',
                        length: '50',
                        unique: true,
                        notNull: true,
                        icon: '🔢',
                        fieldName: 'asset_code'
                    },
                    {
                        type: 'text',
                        label: 'Asset Name',
                        sqlType: 'VARCHAR',
                        length: '200',
                        notNull: true,
                        icon: '🏢',
                        fieldName: 'asset_name'
                    },
                    {
                        type: 'text',
                        label: 'Asset Category',
                        sqlType: 'VARCHAR',
                        length: '100',
                        icon: '📂',
                        fieldName: 'asset_category'
                    },
                    {
                        type: 'date',
                        label: 'Purchase Date',
                        sqlType: 'DATE',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'purchase_date'
                    },
                    {
                        type: 'decimal',
                        label: 'Purchase Cost',
                        sqlType: 'DECIMAL',
                        length: '15,2',
                        notNull: true,
                        icon: '💰',
                        fieldName: 'purchase_cost'
                    },
                    {
                        type: 'decimal',
                        label: 'Salvage Value',
                        sqlType: 'DECIMAL',
                        length: '15,2',
                        defaultValue: '0.00',
                        icon: '💵',
                        fieldName: 'salvage_value'
                    },
                    {
                        type: 'number',
                        label: 'Useful Life Years',
                        sqlType: 'INT',
                        length: '11',
                        notNull: true,
                        icon: '⏳',
                        fieldName: 'useful_life_years'
                    },
                    {
                        type: 'text',
                        label: 'Depreciation Method',
                        sqlType: 'VARCHAR',
                        length: '50',
                        notNull: true,
                        icon: '📊',
                        fieldName: 'depreciation_method'
                    },
                    {
                        type: 'decimal',
                        label: 'Accumulated Depreciation',
                        sqlType: 'DECIMAL',
                        length: '15,2',
                        defaultValue: '0.00',
                        icon: '📉',
                        fieldName: 'accumulated_depreciation'
                    },
                    {
                        type: 'decimal',
                        label: 'Book Value',
                        sqlType: 'DECIMAL',
                        length: '15,2',
                        defaultValue: '0.00',
                        icon: '💰',
                        fieldName: 'book_value'
                    },
                    {
                        type: 'number',
                        label: 'Asset Account ID',
                        sqlType: 'INT',
                        length: '11',
                        icon: '🏦',
                        fieldName: 'asset_account_id'
                    },
                    {
                        type: 'number',
                        label: 'Depreciation Account ID',
                        sqlType: 'INT',
                        length: '11',
                        icon: '📊',
                        fieldName: 'depreciation_account_id'
                    },
                    {
                        type: 'text',
                        label: 'Location',
                        sqlType: 'VARCHAR',
                        length: '200',
                        icon: '📍',
                        fieldName: 'location'
                    },
                    {
                        type: 'longtext',
                        label: 'Description',
                        sqlType: 'TEXT',
                        icon: '📝',
                        fieldName: 'description'
                    },
                    {
                        type: 'status',
                        label: 'Status',
                        sqlType: 'VARCHAR',
                        length: '20',
                        defaultValue: 'active',
                        icon: '⚡',
                        fieldName: 'status'
                    },
                    {
                        type: 'created_at',
                        label: 'Created At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'created_at'
                    },
                    {
                        type: 'updated_at',
                        label: 'Updated At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
                        icon: '🔄',
                        fieldName: 'updated_at'
                    }
                ],
                'Asset Depreciation': [{
                        type: 'id',
                        label: 'ID',
                        sqlType: 'INT',
                        length: '11',
                        autoIncrement: true,
                        primaryKey: true,
                        notNull: true,
                        icon: '🔑',
                        fieldName: 'id'
                    },
                    {
                        type: 'number',
                        label: 'Asset ID',
                        sqlType: 'INT',
                        length: '11',
                        notNull: true,
                        icon: '🏢',
                        fieldName: 'asset_id'
                    },
                    {
                        type: 'date',
                        label: 'Depreciation Date',
                        sqlType: 'DATE',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'depreciation_date'
                    },
                    {
                        type: 'number',
                        label: 'Period ID',
                        sqlType: 'INT',
                        length: '11',
                        icon: '📊',
                        fieldName: 'period_id'
                    },
                    {
                        type: 'decimal',
                        label: 'Depreciation Amount',
                        sqlType: 'DECIMAL',
                        length: '15,2',
                        notNull: true,
                        icon: '📉',
                        fieldName: 'depreciation_amount'
                    },
                    {
                        type: 'decimal',
                        label: 'Accumulated Depreciation',
                        sqlType: 'DECIMAL',
                        length: '15,2',
                        notNull: true,
                        icon: '💰',
                        fieldName: 'accumulated_depreciation'
                    },
                    {
                        type: 'decimal',
                        label: 'Book Value',
                        sqlType: 'DECIMAL',
                        length: '15,2',
                        notNull: true,
                        icon: '💵',
                        fieldName: 'book_value'
                    },
                    {
                        type: 'number',
                        label: 'Journal Entry ID',
                        sqlType: 'INT',
                        length: '11',
                        icon: '📄',
                        fieldName: 'journal_entry_id'
                    },
                    {
                        type: 'created_at',
                        label: 'Created At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'created_at'
                    }
                ],
                'Tax Rates': [{
                        type: 'id',
                        label: 'ID',
                        sqlType: 'INT',
                        length: '11',
                        autoIncrement: true,
                        primaryKey: true,
                        notNull: true,
                        icon: '🔑',
                        fieldName: 'id'
                    },
                    {
                        type: 'text',
                        label: 'Tax Name',
                        sqlType: 'VARCHAR',
                        length: '100',
                        unique: true,
                        notNull: true,
                        icon: '🏛️',
                        fieldName: 'tax_name'
                    },
                    {
                        type: 'text',
                        label: 'Tax Code',
                        sqlType: 'VARCHAR',
                        length: '50',
                        icon: '🔢',
                        fieldName: 'tax_code'
                    },
                    {
                        type: 'decimal',
                        label: 'Tax Rate',
                        sqlType: 'DECIMAL',
                        length: '5,2',
                        notNull: true,
                        icon: '📊',
                        fieldName: 'tax_rate'
                    },
                    {
                        type: 'text',
                        label: 'Tax Type',
                        sqlType: 'VARCHAR',
                        length: '50',
                        notNull: true,
                        icon: '📂',
                        fieldName: 'tax_type'
                    },
                    {
                        type: 'number',
                        label: 'Tax Account ID',
                        sqlType: 'INT',
                        length: '11',
                        icon: '🏦',
                        fieldName: 'tax_account_id'
                    },
                    {
                        type: 'longtext',
                        label: 'Description',
                        sqlType: 'TEXT',
                        icon: '📝',
                        fieldName: 'description'
                    },
                    {
                        type: 'boolean',
                        label: 'Is Active',
                        sqlType: 'TINYINT',
                        length: '1',
                        defaultValue: '1',
                        icon: '✅',
                        fieldName: 'is_active'
                    },
                    {
                        type: 'created_at',
                        label: 'Created At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'created_at'
                    }
                ],
                'Cost Centers': [{
                        type: 'id',
                        label: 'ID',
                        sqlType: 'INT',
                        length: '11',
                        autoIncrement: true,
                        primaryKey: true,
                        notNull: true,
                        icon: '🔑',
                        fieldName: 'id'
                    },
                    {
                        type: 'text',
                        label: 'Center Code',
                        sqlType: 'VARCHAR',
                        length: '50',
                        unique: true,
                        notNull: true,
                        icon: '🔢',
                        fieldName: 'center_code'
                    },
                    {
                        type: 'text',
                        label: 'Center Name',
                        sqlType: 'VARCHAR',
                        length: '200',
                        notNull: true,
                        icon: '🏢',
                        fieldName: 'center_name'
                    },
                    {
                        type: 'number',
                        label: 'Parent Center ID',
                        sqlType: 'INT',
                        length: '11',
                        icon: '🔼',
                        fieldName: 'parent_center_id'
                    },
                    {
                        type: 'number',
                        label: 'Manager ID',
                        sqlType: 'INT',
                        length: '11',
                        icon: '👨‍💼',
                        fieldName: 'manager_id'
                    },
                    {
                        type: 'longtext',
                        label: 'Description',
                        sqlType: 'TEXT',
                        icon: '📝',
                        fieldName: 'description'
                    },
                    {
                        type: 'boolean',
                        label: 'Is Active',
                        sqlType: 'TINYINT',
                        length: '1',
                        defaultValue: '1',
                        icon: '✅',
                        fieldName: 'is_active'
                    },
                    {
                        type: 'created_at',
                        label: 'Created At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'created_at'
                    }
                ],
                'Budgets': [{
                        type: 'id',
                        label: 'ID',
                        sqlType: 'INT',
                        length: '11',
                        autoIncrement: true,
                        primaryKey: true,
                        notNull: true,
                        icon: '🔑',
                        fieldName: 'id'
                    },
                    {
                        type: 'text',
                        label: 'Budget Name',
                        sqlType: 'VARCHAR',
                        length: '200',
                        notNull: true,
                        icon: '📊',
                        fieldName: 'budget_name'
                    },
                    {
                        type: 'number',
                        label: 'Fiscal Year ID',
                        sqlType: 'INT',
                        length: '11',
                        notNull: true,
                        icon: '📆',
                        fieldName: 'fiscal_year_id'
                    },
                    {
                        type: 'number',
                        label: 'Cost Center ID',
                        sqlType: 'INT',
                        length: '11',
                        icon: '🏢',
                        fieldName: 'cost_center_id'
                    },
                    {
                        type: 'date',
                        label: 'Start Date',
                        sqlType: 'DATE',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'start_date'
                    },
                    {
                        type: 'date',
                        label: 'End Date',
                        sqlType: 'DATE',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'end_date'
                    },
                    {
                        type: 'decimal',
                        label: 'Total Budget',
                        sqlType: 'DECIMAL',
                        length: '15,2',
                        notNull: true,
                        icon: '💰',
                        fieldName: 'total_budget'
                    },
                    {
                        type: 'decimal',
                        label: 'Total Actual',
                        sqlType: 'DECIMAL',
                        length: '15,2',
                        defaultValue: '0.00',
                        icon: '💵',
                        fieldName: 'total_actual'
                    },
                    {
                        type: 'decimal',
                        label: 'Variance',
                        sqlType: 'DECIMAL',
                        length: '15,2',
                        defaultValue: '0.00',
                        icon: '📊',
                        fieldName: 'variance'
                    },
                    {
                        type: 'status',
                        label: 'Status',
                        sqlType: 'VARCHAR',
                        length: '20',
                        defaultValue: 'draft',
                        icon: '⚡',
                        fieldName: 'status'
                    },
                    {
                        type: 'longtext',
                        label: 'Notes',
                        sqlType: 'TEXT',
                        icon: '📝',
                        fieldName: 'notes'
                    },
                    {
                        type: 'created_at',
                        label: 'Created At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'created_at'
                    },
                    {
                        type: 'updated_at',
                        label: 'Updated At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
                        icon: '🔄',
                        fieldName: 'updated_at'
                    }
                ],
                'Budget Items': [{
                        type: 'id',
                        label: 'ID',
                        sqlType: 'INT',
                        length: '11',
                        autoIncrement: true,
                        primaryKey: true,
                        notNull: true,
                        icon: '🔑',
                        fieldName: 'id'
                    },
                    {
                        type: 'number',
                        label: 'Budget ID',
                        sqlType: 'INT',
                        length: '11',
                        notNull: true,
                        icon: '📊',
                        fieldName: 'budget_id'
                    },
                    {
                        type: 'number',
                        label: 'Account ID',
                        sqlType: 'INT',
                        length: '11',
                        notNull: true,
                        icon: '🏦',
                        fieldName: 'account_id'
                    },
                    {
                        type: 'decimal',
                        label: 'Budgeted Amount',
                        sqlType: 'DECIMAL',
                        length: '15,2',
                        notNull: true,
                        icon: '💰',
                        fieldName: 'budgeted_amount'
                    },
                    {
                        type: 'decimal',
                        label: 'Actual Amount',
                        sqlType: 'DECIMAL',
                        length: '15,2',
                        defaultValue: '0.00',
                        icon: '💵',
                        fieldName: 'actual_amount'
                    },
                    {
                        type: 'decimal',
                        label: 'Variance',
                        sqlType: 'DECIMAL',
                        length: '15,2',
                        defaultValue: '0.00',
                        icon: '📊',
                        fieldName: 'variance'
                    },
                    {
                        type: 'text',
                        label: 'Notes',
                        sqlType: 'VARCHAR',
                        length: '255',
                        icon: '📝',
                        fieldName: 'notes'
                    },
                    {
                        type: 'created_at',
                        label: 'Created At',
                        sqlType: 'TIMESTAMP',
                        defaultValue: 'CURRENT_TIMESTAMP',
                        notNull: true,
                        icon: '📅',
                        fieldName: 'created_at'
                    }
                ]
            }
        }
    };

    // Toggle template dropdown
    function toggleTemplateDropdown() {
        const dropdown = document.getElementById('tableTemplatesDropdown');
        const arrow = document.getElementById('templateDropdownArrow');

        if (dropdown.style.display === 'none') {
            dropdown.style.display = 'block';
            arrow.style.transform = 'rotate(180deg)';
            renderTemplatesDropdown();
        } else {
            dropdown.style.display = 'none';
            arrow.style.transform = 'rotate(0deg)';
        }
    }

    // Filter templates by search
    function filterTableTemplates() {
        renderTemplatesDropdown();
    }

    // Render templates dropdown
    function renderTemplatesDropdown() {
        const dropdown = document.getElementById('tableTemplatesDropdown');
        const searchTerm = document.getElementById('templateSearchBox').value.toLowerCase().trim();

        let html = '';
        let hasResults = false;

        // Loop through all categories
        for (const [category, categoryData] of Object.entries(TABLE_TEMPLATES)) {
            let categoryHtml = '';
            let categoryHasResults = false;

            // Check if category name matches search
            const categoryMatches = category.toLowerCase().includes(searchTerm);

            // Loop through templates in this category
            for (const [templateName, fields] of Object.entries(categoryData.templates)) {
                const templateMatches = templateName.toLowerCase().includes(searchTerm);

                // Show if: no search term, category matches, or template matches
                if (!searchTerm || categoryMatches || templateMatches) {
                    categoryHasResults = true;
                    hasResults = true;

                    const fieldsCount = fields.length;
                    categoryHtml += `
                    <div onclick="applyTableTemplate('${category}', '${templateName}')" 
                         style="padding: 12px 20px; cursor: pointer; border-left: 3px solid ${categoryData.color}; margin-bottom: 8px; background: rgba(255,255,255,0.05); border-radius: 8px; transition: all 0.25s;"
                         onmouseover="this.style.background='${categoryData.color}22'; this.style.transform='translateX(5px)'"
                         onmouseout="this.style.background='rgba(255,255,255,0.05)'; this.style.transform='translateX(0)'">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <div style="font-size: 15px; font-weight: 700; color: #fff; margin-bottom: 3px;">${templateName}</div>
                                <div style="font-size: 11px; opacity: 0.7; color: #fbbf24;">${fieldsCount} field${fieldsCount !== 1 ? 's' : ''}</div>
                            </div>
                            <button onclick="event.stopPropagation(); applyTableTemplate('${category}', '${templateName}')" 
                                    style="background: ${categoryData.color}; border: none; padding: 6px 16px; border-radius: 6px; color: white; cursor: pointer; font-weight: 600; font-size: 12px; transition: all 0.2s;"
                                    onmouseover="this.style.transform='scale(1.05)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.3)'"
                                    onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='none'">
                                ➕ Add
                            </button>
                        </div>
                    </div>
                `;
                }
            }

            // Add category section if it has results
            if (categoryHasResults) {
                html += `
                <div style="margin-bottom: 20px;">
                    <div style="padding: 12px 20px; background: ${categoryData.color}33; border-left: 4px solid ${categoryData.color}; margin-bottom: 12px; font-weight: 700; font-size: 14px; color: #fff; display: flex; align-items: center; gap: 8px; border-radius: 6px;">
                        <span style="font-size: 20px;">${categoryData.icon}</span>
                        <span>${category}</span>
                        <span style="font-size: 11px; opacity: 0.7; margin-left: auto;">${Object.keys(categoryData.templates).length} template${Object.keys(categoryData.templates).length !== 1 ? 's' : ''}</span>
                    </div>
                    ${categoryHtml}
                </div>
            `;
            }
        }

        // Show message if no results
        if (!hasResults) {
            html = `
            <div style="padding: 60px 30px; text-align: center; color: rgba(255,255,255,0.5);">
                <div style="font-size: 64px; margin-bottom: 15px;">🔍</div>
                <div style="font-size: 16px; font-weight: 600; margin-bottom: 8px;">No templates found</div>
                <div style="font-size: 13px; opacity: 0.8;">Try a different search term</div>
            </div>
        `;
        }

        dropdown.innerHTML = `<div style="padding: 15px;">${html}</div>`;
    }

    // Apply table template
    function applyTableTemplate(category, templateName) {
        const template = TABLE_TEMPLATES[category].templates[templateName];

        if (!template) {
            showCustomToast('❌ Template not found!', 'error');
            return;
        }

        const applyTemplate = () => {
            // Clone template fields with unique IDs
            droppedFields = template.map((field, index) => ({
                ...field,
                id: 'field_' + Date.now() + '_' + index
            }));

            renderBuilderContent();

            // Close dropdown
            document.getElementById('tableTemplatesDropdown').style.display = 'none';
            document.getElementById('templateDropdownArrow').style.transform = 'rotate(0deg)';

            // Clear search
            document.getElementById('templateSearchBox').value = '';

            // Success message
            showCustomToast(`✅ Applied "${templateName}" template (${droppedFields.length} fields)`, 'success');

            // Scroll to table builder
            document.getElementById('tableBuilder').scrollIntoView({
                behavior: 'smooth',
                block: 'nearest'
            });
        };

        if (droppedFields.length > 0) {
            showConfirmDialog(
                'Replace Fields?',
                `Replace existing ${droppedFields.length} field(s) with "${templateName}" template (${template.length} fields)?`,
                applyTemplate
            );
        } else {
            applyTemplate();
        }
    }

    function previewSQL() {
        if (droppedFields.length === 0) {
            showCustomToast('❌ No fields added! Please drag and drop fields first.', 'error');
            return;
        }

        const tableName = document.getElementById('tableName').value.trim() || 'my_table';
        const sql = generateCreateTableSQL(tableName, droppedFields);

        // Show SQL in custom modal
        showSQLModal(sql);
    }

    function showSQLModal(sql) {
        const modal = document.createElement('div');
        modal.style.cssText =
            'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); backdrop-filter: blur(8px); z-index: 3500; display: flex; align-items: center; justify-content: center; animation: fadeIn 0.3s ease-out;';

        modal.innerHTML = `
        <div style="background: linear-gradient(135deg, rgba(102,126,234,0.98) 0%, rgba(118,75,162,0.98) 100%); border-radius: 20px; padding: 30px; max-width: 900px; width: 90%; max-height: 85vh; display: flex; flex-direction: column; box-shadow: 0 25px 80px rgba(0,0,0,0.6); border: 2px solid rgba(255,255,255,0.2);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="color: #fbbf24; font-size: 22px; margin: 0; font-weight: 700;">📝 SQL Preview</h3>
                <button onclick="this.closest('[style*=fixed]').remove()" style="background: rgba(239,68,68,0.3); border: 1px solid rgba(239,68,68,0.5); width: 35px; height: 35px; border-radius: 50%; color: #fff; font-size: 22px; cursor: pointer; display: flex; align-items: center; justify-content: center;">×</button>
            </div>
            <textarea readonly style="flex: 1; width: 100%; padding: 20px; background: rgba(0,0,0,0.4); border: 2px solid rgba(251,191,36,0.3); border-radius: 12px; color: #86efac; font-family: 'Consolas', monospace; font-size: 14px; resize: none; line-height: 1.6;">${sql.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</textarea>
            <div style="display: flex; gap: 12px; margin-top: 20px;">
                <button onclick="this.closest('[style*=fixed]').remove()" style="flex: 1; padding: 12px; background: rgba(239,68,68,0.3); border: 1px solid rgba(239,68,68,0.5); border-radius: 10px; color: #fff; cursor: pointer; font-weight: 600; font-size: 14px;">Close</button>
                <button onclick="navigator.clipboard.writeText(\`${sql.replace(/`/g, '\\`')}\`).then(() => showCustomToast('✅ SQL copied to clipboard!', 'success')); this.closest('[style*=fixed]').remove();" style="flex: 2; padding: 12px; background: linear-gradient(135deg, #22c55e 0%, #15803d 100%); border: none; border-radius: 10px; color: #fff; cursor: pointer; font-weight: 700; font-size: 15px; box-shadow: 0 4px 15px rgba(34,197,94,0.4);">📋 Copy SQL</button>
            </div>
        </div>
    `;

        document.body.appendChild(modal);
    }

    function generateCreateTableSQL(tableName, fields) {
        let sql = `CREATE TABLE \`${tableName}\` (\n`;
        const columnDefs = [];
        const primaryKeys = [];

        fields.forEach(field => {
            let def = `  \`${field.fieldName}\` ${field.sqlType}`;

            if (field.length && ['VARCHAR', 'CHAR', 'INT', 'DECIMAL', 'TINYINT'].includes(field.sqlType)) {
                def += `(${field.length})`;
            }

            if (field.notNull) def += ' NOT NULL';

            if (field.defaultValue) {
                if (field.defaultValue === 'CURRENT_TIMESTAMP' || field.defaultValue.includes('ON UPDATE')) {
                    def += ` DEFAULT ${field.defaultValue}`;
                } else if (field.defaultValue === 'NULL') {
                    def += ' DEFAULT NULL';
                } else {
                    def += ` DEFAULT '${field.defaultValue}'`;
                }
            }

            if (field.autoIncrement) def += ' AUTO_INCREMENT';
            if (field.unique && !field.primaryKey) def += ' UNIQUE';

            columnDefs.push(def);

            if (field.primaryKey) {
                primaryKeys.push(`\`${field.fieldName}\``);
            }
        });

        sql += columnDefs.join(',\n');

        if (primaryKeys.length > 0) {
            sql += ',\n  PRIMARY KEY (' + primaryKeys.join(', ') + ')';
        }

        sql += '\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';

        return sql;
    }

    // Generate Application
    async function generateApplication() {
        // Validation
        const appName = document.getElementById('appName').value.trim();
        const frontendName = document.getElementById('frontendName').value.trim();
        const backendName = document.getElementById('backendName').value.trim();
        const frontendFolder = document.getElementById('frontendFolder').value.trim();
        const backendFolder = document.getElementById('backendFolder').value.trim();
        const tableName = document.getElementById('tableName').value.trim();

        if (!appName) {
            showCustomToast('❌ Please enter application name!', 'error');
            document.getElementById('appName').focus();
            return;
        }
        if (!frontendName) {
            showCustomToast('❌ Please enter frontend filename!', 'error');
            document.getElementById('frontendName').focus();
            return;
        }
        if (!backendName) {
            showCustomToast('❌ Please enter backend filename!', 'error');
            document.getElementById('backendName').focus();
            return;
        }
        if (!frontendFolder) {
            showCustomToast('❌ Please specify frontend folder!', 'error');
            document.getElementById('frontendFolder').focus();
            return;
        }
        if (!backendFolder) {
            showCustomToast('❌ Please specify backend folder!', 'error');
            document.getElementById('backendFolder').focus();
            return;
        }

        // Get backend API URL
        let backendApiUrl = document.getElementById('backendApiUrl').value.trim();

        // If empty, auto-generate it
        if (!backendApiUrl) {
            backendApiUrl = generateBackendUrl(frontendFolder, backendFolder, backendName);
            document.getElementById('backendApiUrl').value = backendApiUrl;
        }

        if (!backendApiUrl) {
            showCustomToast('❌ Backend API URL is required! Click Auto button to generate.', 'error');
            document.getElementById('backendApiUrl').focus();
            return;
        }

        if (!selectedDatabase) {
            showCustomToast('❌ Please select a database!', 'error');
            document.getElementById('databaseSelect').focus();
            return;
        }

        // Validate database config
        console.log('📊 Selected Database:', selectedDatabase);
        if (!selectedDatabase.host || !selectedDatabase.dbName) {
            showCustomToast('❌ Invalid database configuration! Please select a database again.', 'error');
            return;
        }
        if (!tableName) {
            showCustomToast('❌ Please enter table name!', 'error');
            document.getElementById('tableName').focus();
            return;
        }
        if (droppedFields.length === 0) {
            showCustomToast('❌ Please add at least one field to your table!', 'error');
            return;
        }

        // Show loading
        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'success-message';
        loadingDiv.innerHTML = `
        <div style="font-size: 64px; margin-bottom: 20px; animation: spin 2s linear infinite;">⚙️</div>
        <div style="font-size: 24px; font-weight: bold;">Generating Application...</div>
        <div style="font-size: 14px; margin-top: 10px; opacity: 0.9;">Creating ${appName}</div>
        <style>@keyframes spin { from { transform: translate(-50%, -50%) rotate(0deg); } to { transform: translate(-50%, -50%) rotate(360deg); } }</style>
    `;
        document.body.appendChild(loadingDiv);

        try {
            // Save folders, domain, and URL to recent lists
            saveFolder(frontendFolder, 'frontend');
            saveFolder(backendFolder, 'backend');

            const apiDomain = document.getElementById('apiDomain').value.trim();
            if (apiDomain) {
                let domains = JSON.parse(localStorage.getItem(SAVED_API_DOMAINS_KEY) || '[]');
                domains = domains.filter(d => d !== apiDomain);
                domains.unshift(apiDomain);
                if (domains.length > MAX_RECENT_DOMAINS) {
                    domains = domains.slice(0, MAX_RECENT_DOMAINS);
                }
                localStorage.setItem(SAVED_API_DOMAINS_KEY, JSON.stringify(domains));
            }

            saveApiUrl(backendApiUrl);

            console.log('🎨 Frontend Folder:', frontendFolder);
            console.log('🔧 Backend Folder:', backendFolder);
            console.log('🌐 Backend API URL:', backendApiUrl);

            // Generate SQL
            const createTableSQL = generateCreateTableSQL(tableName, droppedFields);

            // Send to PHP for file creation
            const formData = new FormData();
            formData.append('action', 'create_application');
            formData.append('app_name', appName);
            formData.append('frontend_name', frontendName);
            formData.append('backend_name', backendName);
            formData.append('frontend_folder', frontendFolder);
            formData.append('backend_folder', backendFolder);
            formData.append('backend_url', backendApiUrl);
            formData.append('table_name', tableName);
            formData.append('create_table_sql', createTableSQL);
            formData.append('db_config', JSON.stringify(selectedDatabase));
            formData.append('fields_json', JSON.stringify(droppedFields));

            // Get custom theme code (if any)
            const themeCode = document.getElementById('themeCode').value.trim();
            formData.append('theme_code', themeCode);

            const response = await fetch('appmaker.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            document.body.removeChild(loadingDiv);

            if (result.success) {
                showSuccess(result);
            } else {
                showErrorDialog('Generation Failed', result.message);
            }

        } catch (error) {
            document.body.removeChild(loadingDiv);
            showErrorDialog('Error Occurred', error.message);
        }
    }

    // ========================================
    // CUSTOM AI INSTRUCTIONS
    // ========================================
    
    // Update character count for custom instructions
    document.addEventListener('DOMContentLoaded', function() {
        const instructionsTextarea = document.getElementById('customAIInstructions');
        const charCount = document.getElementById('instructionsCharCount');
        
        if (instructionsTextarea && charCount) {
            instructionsTextarea.addEventListener('input', function() {
                const count = this.value.length;
                charCount.textContent = count + ' character' + (count !== 1 ? 's' : '');
                
                // Change color based on length
                if (count > 500) {
                    charCount.style.color = '#fbbf24';
                } else if (count > 1000) {
                    charCount.style.color = '#f87171';
                } else {
                    charCount.style.color = 'rgba(167, 139, 250, 0.6)';
                }
            });
        }
    });
    
    // Clear custom instructions
    function clearCustomInstructions() {
        const textarea = document.getElementById('customAIInstructions');
        const charCount = document.getElementById('instructionsCharCount');
        
        if (textarea) {
            textarea.value = '';
            if (charCount) charCount.textContent = '0 characters';
            showCustomToast('🗑️ Custom instructions cleared', 'info');
        }
    }
    
    // ========================================
    // GENERATE AI PROMPT FUNCTION
    // ========================================
    
    function generateAIPrompt() {
        // Gather all information
        const appName = document.getElementById('appName').value.trim();
        const frontendName = document.getElementById('frontendName').value.trim();
        const backendName = document.getElementById('backendName').value.trim();
        const frontendFolder = document.getElementById('frontendFolder').value.trim();
        const backendFolder = document.getElementById('backendFolder').value.trim();
        const backendApiUrl = document.getElementById('backendApiUrl').value.trim();
        const tableName = document.getElementById('tableName').value.trim();
        const themeCode = document.getElementById('themeCode').value.trim();
        
        // Validation
        if (!appName) {
            showCustomToast('❌ Please enter application name!', 'error');
            document.getElementById('appName').focus();
            return;
        }
        if (!frontendName) {
            showCustomToast('❌ Please enter frontend filename!', 'error');
            return;
        }
        if (!backendName) {
            showCustomToast('❌ Please enter backend filename!', 'error');
            return;
        }
        if (!frontendFolder) {
            showCustomToast('❌ Please specify frontend folder!', 'error');
            return;
        }
        if (!backendFolder) {
            showCustomToast('❌ Please specify backend folder!', 'error');
            return;
        }
        if (!selectedDatabase) {
            showCustomToast('❌ Please select a database!', 'error');
            return;
        }
        if (!tableName) {
            showCustomToast('❌ Please enter table name!', 'error');
            return;
        }
        if (droppedFields.length === 0) {
            showCustomToast('❌ Please add at least one field to your table!', 'error');
            return;
        }
        
        // Generate SQL for reference
        const createTableSQL = generateCreateTableSQL(tableName, droppedFields);
        
        // Build fields description
        let fieldsDescription = '';
        droppedFields.forEach((field, index) => {
            fieldsDescription += `   ${index + 1}. **${field.fieldName}**\n`;
            fieldsDescription += `      - Type: ${field.sqlType}${field.length ? `(${field.length})` : ''}\n`;
            if (field.primaryKey) fieldsDescription += `      - Primary Key: Yes\n`;
            if (field.autoIncrement) fieldsDescription += `      - Auto Increment: Yes\n`;
            if (field.notNull) fieldsDescription += `      - Not Null: Yes\n`;
            if (field.unique) fieldsDescription += `      - Unique: Yes\n`;
            if (field.defaultValue) fieldsDescription += `      - Default: ${field.defaultValue}\n`;
            fieldsDescription += `      - Icon: ${field.icon}\n\n`;
        });
        
        // Get custom AI instructions
        const customInstructions = document.getElementById('customAIInstructions').value.trim();
        
        // Build the AI prompt
        const prompt = `# 🚀 CRUD Application Generation Request

## 📋 Application Overview
Create a complete CRUD (Create, Read, Update, Delete) web application with the following specifications:

- **Application Name:** ${appName}
- **Database Table:** ${tableName}
- **Total Fields:** ${droppedFields.length}

---

## 📁 File Structure

### Frontend File
- **Filename:** ${frontendName}
- **Save Location:** ${frontendFolder}
- **Type:** HTML with embedded CSS and JavaScript
- **API Endpoint:** ${backendApiUrl || `[Backend folder URL]/${backendName}`}

### Backend File
- **Filename:** ${backendName}
- **Save Location:** ${backendFolder}
- **Type:** PHP API with PDO

---

## 🗄️ Database Configuration

- **Host:** ${selectedDatabase.host}
- **Database Name:** ${selectedDatabase.dbName}
- **Username:** ${selectedDatabase.username}
- **Password:** ${selectedDatabase.password ? '[PROVIDED]' : '[EMPTY]'}
- **Port:** ${selectedDatabase.port || '3306'}
- **Connection Type:** ${selectedDatabase.isLocalhost ? 'Localhost (Laragon/XAMPP)' : 'Remote Server (Hostinger)'}

---

## 📊 Table Structure

**Table Name:** \`${tableName}\`

### Fields:
${fieldsDescription}

### SQL Create Statement:
\`\`\`sql
${createTableSQL}
\`\`\`

---

## 🎨 Frontend Requirements

1. **UI Design:**
   - Modern, responsive design
   - Glassmorphism/gradient background
   - Clean typography
   - Smooth animations and transitions

2. **Features:**
   - 📊 Data table displaying all records
   - 🔍 Search/filter functionality (instant search)
   - ➕ Add new record (modal form)
   - ✏️ Edit existing record (modal form)
   - 🗑️ Delete record (with confirmation)
   - 🔄 Refresh button
   - 📱 Responsive (mobile-friendly)

3. **Form Fields:**
   Generate appropriate input types based on field types:
   - TEXT → textarea
   - VARCHAR → text input
   - INT → number input
   - DECIMAL → number input with step
   - DATE → date picker
   - DATETIME → datetime-local picker
   - TINYINT(1) → checkbox/toggle
   - Auto-increment fields → hidden/readonly on add

4. **JavaScript:**
   - Async/await for API calls
   - FormData for submissions
   - Toast notifications for feedback
   - Console logging for debugging

${themeCode ? `5. **Custom Theme (Apply this CSS):**
\`\`\`css
${themeCode}
\`\`\`` : ''}

---

## 🔧 Backend Requirements (PHP API)

1. **Actions to implement:**
   - \`list\` - Get all records (with search support)
   - \`create\` - Insert new record
   - \`update\` - Update existing record
   - \`delete\` - Delete record by ID

2. **Features:**
   - PDO connection with error handling
   - JSON responses
   - CORS headers for cross-origin requests
   - SQL injection prevention (prepared statements)
   - Error logging

3. **Response Format:**
\`\`\`json
{
    "success": true/false,
    "message": "Status message",
    "records": [...] // for list action
}
\`\`\`

---

## 🔗 API Integration

- Frontend should fetch from: \`${backendApiUrl || '[BACKEND_URL]'}\`
- All requests should use POST method with FormData
- Handle network errors gracefully
- Show loading states during API calls

${customInstructions ? `---

## 📝 Additional Instructions (IMPORTANT!)

**The following custom instructions MUST be followed when generating the application:**

${customInstructions}
` : ''}
---

## ✅ Summary

Please generate:
1. **${frontendName}** - Complete HTML file with CSS and JavaScript
2. **${backendName}** - Complete PHP API file

Both files should be production-ready, well-commented, and follow best practices.

**Primary Key Field:** ${droppedFields.find(f => f.primaryKey)?.fieldName || 'id'}

---

*Generated by App-AI - AppMaker*`;

        // Show the prompt in modal
        document.getElementById('aiPromptContent').value = prompt;
        document.getElementById('aiPromptModal').style.display = 'block';
        
        showCustomToast('✅ AI Prompt generated! Drag to move, edges to resize, copy when ready!', 'success');
    }
    
    // AI Prompt Modal State
    let aiPromptModalState = {
        isDragging: false,
        isResizing: false,
        resizeDir: null,
        startX: 0,
        startY: 0,
        startWidth: 0,
        startHeight: 0,
        startLeft: 0,
        startTop: 0,
        isMaximized: false,
        savedPosition: null
    };

    // Initialize AI Prompt Modal Drag & Resize
    function initAIPromptModalDragResize() {
        const modal = document.getElementById('aiPromptModalContent');
        const header = document.getElementById('aiPromptModalHeader');
        const resizeHandles = modal.querySelectorAll('.resize-handle');

        // Drag functionality
        header.addEventListener('mousedown', (e) => {
            if (aiPromptModalState.isMaximized) return;
            aiPromptModalState.isDragging = true;
            aiPromptModalState.startX = e.clientX;
            aiPromptModalState.startY = e.clientY;
            
            const rect = modal.getBoundingClientRect();
            aiPromptModalState.startLeft = rect.left;
            aiPromptModalState.startTop = rect.top;
            
            // Remove transform for absolute positioning
            modal.style.transform = 'none';
            modal.style.left = rect.left + 'px';
            modal.style.top = rect.top + 'px';
            
            document.body.style.userSelect = 'none';
            e.preventDefault();
        });

        // Resize functionality
        resizeHandles.forEach(handle => {
            handle.addEventListener('mousedown', (e) => {
                if (aiPromptModalState.isMaximized) return;
                aiPromptModalState.isResizing = true;
                aiPromptModalState.resizeDir = handle.dataset.resize;
                aiPromptModalState.startX = e.clientX;
                aiPromptModalState.startY = e.clientY;
                
                const rect = modal.getBoundingClientRect();
                aiPromptModalState.startWidth = rect.width;
                aiPromptModalState.startHeight = rect.height;
                aiPromptModalState.startLeft = rect.left;
                aiPromptModalState.startTop = rect.top;
                
                // Remove transform for absolute positioning
                modal.style.transform = 'none';
                modal.style.left = rect.left + 'px';
                modal.style.top = rect.top + 'px';
                modal.style.width = rect.width + 'px';
                modal.style.height = rect.height + 'px';
                
                document.body.style.userSelect = 'none';
                e.preventDefault();
                e.stopPropagation();
            });
        });

        // Mouse move handler
        document.addEventListener('mousemove', (e) => {
            if (aiPromptModalState.isDragging) {
                const dx = e.clientX - aiPromptModalState.startX;
                const dy = e.clientY - aiPromptModalState.startY;
                
                let newLeft = aiPromptModalState.startLeft + dx;
                let newTop = aiPromptModalState.startTop + dy;
                
                // Keep within viewport
                newLeft = Math.max(0, Math.min(newLeft, window.innerWidth - modal.offsetWidth));
                newTop = Math.max(0, Math.min(newTop, window.innerHeight - 50));
                
                modal.style.left = newLeft + 'px';
                modal.style.top = newTop + 'px';
            }
            
            if (aiPromptModalState.isResizing) {
                const dx = e.clientX - aiPromptModalState.startX;
                const dy = e.clientY - aiPromptModalState.startY;
                const dir = aiPromptModalState.resizeDir;
                
                let newWidth = aiPromptModalState.startWidth;
                let newHeight = aiPromptModalState.startHeight;
                let newLeft = aiPromptModalState.startLeft;
                let newTop = aiPromptModalState.startTop;
                
                // Handle resize directions
                if (dir.includes('e')) newWidth = Math.max(400, aiPromptModalState.startWidth + dx);
                if (dir.includes('w')) {
                    newWidth = Math.max(400, aiPromptModalState.startWidth - dx);
                    newLeft = aiPromptModalState.startLeft + (aiPromptModalState.startWidth - newWidth);
                }
                if (dir.includes('s')) newHeight = Math.max(300, aiPromptModalState.startHeight + dy);
                if (dir.includes('n')) {
                    newHeight = Math.max(300, aiPromptModalState.startHeight - dy);
                    newTop = aiPromptModalState.startTop + (aiPromptModalState.startHeight - newHeight);
                }
                
                // Apply constraints
                newWidth = Math.min(newWidth, window.innerWidth - newLeft);
                newHeight = Math.min(newHeight, window.innerHeight - newTop);
                
                modal.style.width = newWidth + 'px';
                modal.style.height = newHeight + 'px';
                modal.style.left = newLeft + 'px';
                modal.style.top = newTop + 'px';
            }
        });

        // Mouse up handler
        document.addEventListener('mouseup', () => {
            aiPromptModalState.isDragging = false;
            aiPromptModalState.isResizing = false;
            aiPromptModalState.resizeDir = null;
            document.body.style.userSelect = '';
        });
    }

    // Toggle Maximize/Restore
    function toggleAIPromptModalMaximize() {
        const modal = document.getElementById('aiPromptModalContent');
        const btn = document.getElementById('aiPromptMaximizeBtn');
        
        if (!aiPromptModalState.isMaximized) {
            // Save current position
            aiPromptModalState.savedPosition = {
                width: modal.style.width,
                height: modal.style.height,
                left: modal.style.left,
                top: modal.style.top,
                transform: modal.style.transform
            };
            
            // Maximize
            modal.style.width = '98vw';
            modal.style.height = '96vh';
            modal.style.left = '1vw';
            modal.style.top = '2vh';
            modal.style.transform = 'none';
            btn.textContent = '❐';
            btn.title = 'Restore';
            aiPromptModalState.isMaximized = true;
        } else {
            // Restore
            if (aiPromptModalState.savedPosition) {
                modal.style.width = aiPromptModalState.savedPosition.width || '80%';
                modal.style.height = aiPromptModalState.savedPosition.height || '70vh';
                modal.style.left = aiPromptModalState.savedPosition.left || '50%';
                modal.style.top = aiPromptModalState.savedPosition.top || '50%';
                modal.style.transform = aiPromptModalState.savedPosition.transform || 'translate(-50%, -50%)';
            } else {
                // Default centered position
                modal.style.width = '80%';
                modal.style.height = '70vh';
                modal.style.left = '50%';
                modal.style.top = '50%';
                modal.style.transform = 'translate(-50%, -50%)';
            }
            btn.textContent = '⬜';
            btn.title = 'Maximize';
            aiPromptModalState.isMaximized = false;
        }
    }

    // Close AI Prompt Modal
    function closeAIPromptModal() {
        document.getElementById('aiPromptModal').style.display = 'none';
        
        // Reset modal state
        const modal = document.getElementById('aiPromptModalContent');
        const btn = document.getElementById('aiPromptMaximizeBtn');
        
        modal.style.width = '80%';
        modal.style.height = '70vh';
        modal.style.left = '50%';
        modal.style.top = '50%';
        modal.style.transform = 'translate(-50%, -50%)';
        
        aiPromptModalState.isMaximized = false;
        aiPromptModalState.savedPosition = null;
        btn.textContent = '⬜';
        btn.title = 'Maximize';
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', initAIPromptModalDragResize);
    
    // Copy AI Prompt to Clipboard
    function copyAIPrompt() {
        const promptContent = document.getElementById('aiPromptContent').value;
        
        navigator.clipboard.writeText(promptContent).then(() => {
            showCustomToast('✅ AI Prompt copied to clipboard! Paste it to your AI assistant.', 'success');
            
            // Visual feedback
            const textarea = document.getElementById('aiPromptContent');
            textarea.style.borderColor = '#22c55e';
            textarea.style.background = 'rgba(34, 197, 94, 0.2)';
            
            setTimeout(() => {
                textarea.style.borderColor = 'rgba(255,255,255,0.2)';
                textarea.style.background = 'rgba(0,0,0,0.4)';
            }, 1000);
        }).catch(err => {
            showCustomToast('❌ Failed to copy prompt', 'error');
            console.error('Copy failed:', err);
        });
    }

    function showErrorDialog(title, message) {
        const modal = document.createElement('div');
        modal.style.cssText =
            'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); backdrop-filter: blur(8px); z-index: 3500; display: flex; align-items: center; justify-content: center; animation: fadeIn 0.3s ease-out;';

        modal.innerHTML = `
        <div style="background: linear-gradient(135deg, rgba(239,68,68,0.98) 0%, rgba(220,38,38,0.98) 100%); border-radius: 20px; padding: 35px; max-width: 600px; width: 90%; box-shadow: 0 25px 80px rgba(0,0,0,0.6); border: 2px solid rgba(252,165,165,0.4);">
            <div style="text-align: center; margin-bottom: 25px;">
                <div style="font-size: 72px; margin-bottom: 20px;">❌</div>
                <h3 style="color: #fef3c7; font-size: 24px; font-weight: 700; margin-bottom: 15px;">${title}</h3>
                <div style="background: rgba(0,0,0,0.3); padding: 18px; border-radius: 12px; font-size: 15px; line-height: 1.7; text-align: left; max-height: 300px; overflow-y: auto; word-wrap: break-word;">
                    ${message}
                </div>
            </div>
            <button onclick="this.closest('[style*=fixed]').remove()" style="width: 100%; padding: 14px; background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); border-radius: 10px; color: #fff; cursor: pointer; font-weight: 700; font-size: 16px; transition: all 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                Close
            </button>
        </div>
    `;

        document.body.appendChild(modal);
    }

    function showSuccess(result) {
        const successDiv = document.createElement('div');
        successDiv.className = 'success-message';
        successDiv.innerHTML = `
        <div style="font-size: 72px; margin-bottom: 20px;">✅</div>
        <div style="font-size: 28px; font-weight: bold; margin-bottom: 15px;">Application Generated!</div>
        <div style="font-size: 14px; opacity: 0.95; background: rgba(255,255,255,0.2); padding: 15px 20px; border-radius: 10px; margin: 15px 0; text-align: left; max-height: 400px; overflow-y: auto;">
            <div style="margin-bottom: 15px;">
                <strong style="color: #fbbf24; font-size: 15px;">🎨 Frontend (HTML):</strong><br>
                <code style="font-size: 12px; background: rgba(0,0,0,0.3); padding: 5px 10px; border-radius: 5px; display: inline-block; margin-top: 5px; word-break: break-all;">${result.frontend_path}</code>
            </div>
            <div style="margin-bottom: 15px;">
                <strong style="color: #fbbf24; font-size: 15px;">🔧 Backend (PHP):</strong><br>
                <code style="font-size: 12px; background: rgba(0,0,0,0.3); padding: 5px 10px; border-radius: 5px; display: inline-block; margin-top: 5px; word-break: break-all;">${result.backend_path}</code>
            </div>
            <div style="margin-bottom: 15px;">
                <strong style="color: #fbbf24; font-size: 15px;">🌐 API URL:</strong><br>
                <code style="font-size: 12px; background: rgba(0,0,0,0.3); padding: 5px 10px; border-radius: 5px; display: inline-block; margin-top: 5px; word-break: break-all;">${result.backend_url}</code>
            </div>
            ${result.table_created ? '<div style="background: rgba(34,197,94,0.3); padding: 10px; border-radius: 8px; border: 2px solid rgba(34,197,94,0.5);"><strong style="color: #86efac; font-size: 15px;">✅ Database table created successfully!</strong></div>' : '<div style="background: rgba(239,68,68,0.3); padding: 10px; border-radius: 8px; border: 2px solid rgba(239,68,68,0.5);"><strong style="color: #fca5a5; font-size: 15px;">⚠️ Table creation failed</strong></div>'}
        </div>
        <div style="font-size: 13px; opacity: 0.85; margin-top: 15px; background: rgba(59,130,246,0.2); padding: 12px; border-radius: 8px; border: 1px solid rgba(59,130,246,0.4);">
            <div style="margin-bottom: 5px;">💡 <strong>Next Steps:</strong></div>
            <div>1️⃣ Open <strong>${result.frontend_file}</strong> in your browser</div>
            <div>2️⃣ Frontend will connect to: <strong>${result.backend_url}</strong></div>
        </div>
        <button onclick="this.parentElement.remove(); location.reload();" style="margin-top: 25px; padding: 14px 35px; background: white; color: #667eea; border: none; border-radius: 10px; font-size: 16px; font-weight: bold; cursor: pointer; box-shadow: 0 4px 15px rgba(0,0,0,0.2); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0,0,0,0.3)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(0,0,0,0.2)'">
            🎉 Create Another App
        </button>
    `;
        document.body.appendChild(successDiv);
    }

    // Refresh page function
    function refreshPage() {
        // Show confirmation dialog
        showConfirmDialog(
            'Refresh Page?',
            'All unsaved changes will be lost. Are you sure you want to refresh the page?',
            () => {
                // Show loading animation
                const loadingDiv = document.createElement('div');
                loadingDiv.style.cssText =
                    'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(102,126,234,0.95); backdrop-filter: blur(10px); z-index: 10000; display: flex; align-items: center; justify-content: center; flex-direction: column;';
                loadingDiv.innerHTML = `
                <div style="font-size: 80px; margin-bottom: 20px; animation: spin 1s linear infinite;">🔄</div>
                <div style="font-size: 24px; color: white; font-weight: bold;">Refreshing Page...</div>
                <style>@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }</style>
            `;
                document.body.appendChild(loadingDiv);

                // Refresh after short delay for animation
                setTimeout(() => {
                    location.reload();
                }, 800);
            }
        );
    }

    // ========================================
    // THEME CODE FUNCTIONS
    // ========================================

    // Clear theme code textarea
    function clearThemeCode() {
        const textarea = document.getElementById('themeCode');
        if (textarea.value.trim() === '') {
            showCustomToast('📝 Theme code is already empty', 'info');
            return;
        }

        textarea.value = '';
        showCustomToast('🗑️ Theme code cleared!', 'success');

        // Visual feedback
        textarea.style.background = 'rgba(239, 68, 68, 0.15)';
        textarea.style.borderColor = 'rgba(239, 68, 68, 0.5)';
        setTimeout(() => {
            textarea.style.background = '';
            textarea.style.borderColor = '';
        }, 500);
    }

    // Copy theme code to clipboard
    function copyThemeCode() {
        const textarea = document.getElementById('themeCode');
        const code = textarea.value.trim();

        if (code === '') {
            showCustomToast('⚠️ No theme code to copy!', 'error');
            return;
        }

        navigator.clipboard.writeText(code).then(() => {
            showCustomToast('📋 Theme code copied to clipboard!', 'success');

            // Visual feedback
            textarea.style.background = 'rgba(34, 197, 94, 0.15)';
            textarea.style.borderColor = 'rgba(34, 197, 94, 0.5)';
            setTimeout(() => {
                textarea.style.background = '';
                textarea.style.borderColor = '';
            }, 800);
        }).catch(() => {
            showCustomToast('❌ Failed to copy theme code', 'error');
        });
    }

    // ========================================
    // AI THEME EXTRACTION PROMPTS
    // ========================================

    // Copy prompt for extracting theme from App/Route
    function copyExtractFromAppPrompt() {
        const prompt = `# 🎨 THEME EXTRACTION REQUEST - FROM APPLICATION FILES

## Task
Please analyze ALL files in the current project/workspace root directory and extract the complete CSS theme/styling to be used for generating CRUD applications.

## Instructions
1. Scan all HTML, CSS, PHP, and JS files in the root directory
2. Extract ALL styling information including:
   - CSS custom properties (variables)
   - Color schemes (backgrounds, text colors, borders, shadows)
   - Gradients and gradient definitions
   - Typography (fonts, sizes, weights, line-heights)
   - Spacing patterns (margins, paddings)
   - Border styles and border-radius values
   - Box shadows and effects
   - Button styles and hover effects
   - Form input styles
   - Card/container styles
   - Animation keyframes
   - Responsive breakpoints

## Required Output Format
Provide the extracted theme in this EXACT format that can be directly pasted into a theme textarea:

\`\`\`
<!-- ========================================
     EXTRACTED THEME - [App Name]
     Generated for AppMaker Integration
     ======================================== -->

<!-- CSS Variables & Root Styles -->
<style>
:root {
    /* Primary Colors */
    --primary-gradient: linear-gradient(135deg, #COLOR1 0%, #COLOR2 100%);
    --primary-color: #HEX;
    --secondary-color: #HEX;
    --accent-color: #HEX;
    
    /* Background Colors */
    --bg-primary: #HEX;
    --bg-secondary: #HEX;
    --bg-card: rgba(R, G, B, A);
    
    /* Text Colors */
    --text-primary: #HEX;
    --text-secondary: #HEX;
    --text-muted: #HEX;
    
    /* Status Colors */
    --success-color: #HEX;
    --error-color: #HEX;
    --warning-color: #HEX;
    --info-color: #HEX;
    
    /* Borders & Shadows */
    --border-color: rgba(R, G, B, A);
    --border-radius: Xpx;
    --box-shadow: VALUES;
    
    /* Typography */
    --font-family: 'FONT', sans-serif;
    --font-size-base: Xpx;
}

/* Body Styling */
body {
    font-family: var(--font-family);
    background: var(--primary-gradient);
    color: var(--text-primary);
}

/* Card Styles */
.card {
    background: var(--bg-card);
    backdrop-filter: blur(10px);
    border-radius: var(--border-radius);
    border: 1px solid var(--border-color);
    box-shadow: var(--box-shadow);
    padding: 25px;
}

/* Button Styles */
.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-primary {
    background: var(--primary-gradient);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
}

/* Form Inputs */
.form-input {
    width: 100%;
    padding: 12px 15px;
    background: rgba(255, 255, 255, 0.15);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    color: var(--text-primary);
    font-size: 14px;
}

.form-input:focus {
    outline: none;
    border-color: var(--accent-color);
}

/* Table Styles */
.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th {
    background: rgba(251, 191, 36, 0.2);
    color: var(--accent-color);
    padding: 12px;
    text-align: left;
}

.data-table td {
    padding: 10px 12px;
    border-bottom: 1px solid var(--border-color);
}

/* Animations */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
\`\`\`

## Important Notes
- Extract ACTUAL values from the source files, not placeholders
- Maintain the exact format above for AppMaker compatibility
- Include all color values as HEX or rgba()
- Preserve all gradient definitions
- Keep animation keyframes intact
- The output should be copy-paste ready for the "Custom Theme Code" textarea

Please proceed with the extraction now.`;

        navigator.clipboard.writeText(prompt).then(() => {
            showCustomToast('📁 App extraction prompt copied! Paste it to your AI assistant.', 'success');
            showPromptCopiedFeedback('app');
        }).catch(() => {
            showCustomToast('❌ Failed to copy prompt', 'error');
        });
    }

    // Copy prompt for extracting theme from URL
    function copyExtractFromURLPrompt() {
        const prompt = `# 🎨 THEME EXTRACTION REQUEST - FROM URL/WEBPAGE

## Task
Please visit the URL I will provide and extract the complete CSS theme/styling from that webpage to be used for generating CRUD applications.

## Instructions
When I provide a URL, please:
1. Analyze the webpage's HTML source and all linked/embedded CSS
2. Extract ALL styling information including:
   - CSS custom properties (variables)
   - Color schemes (backgrounds, text colors, borders, shadows)
   - Gradients and gradient definitions
   - Typography (fonts, sizes, weights, line-heights)
   - Spacing patterns (margins, paddings)
   - Border styles and border-radius values
   - Box shadows and effects
   - Button styles and hover effects
   - Form input styles
   - Card/container styles
   - Animation keyframes
   - Any unique visual effects

## URL to Extract From
[I WILL PASTE THE URL HERE]

## Required Output Format
Provide the extracted theme in this EXACT format that can be directly pasted into a theme textarea:

\`\`\`
<!-- ========================================
     EXTRACTED THEME - From URL
     Source: [URL]
     Generated for AppMaker Integration
     ======================================== -->

<!-- CSS Variables & Root Styles -->
<style>
:root {
    /* Primary Colors */
    --primary-gradient: linear-gradient(135deg, #COLOR1 0%, #COLOR2 100%);
    --primary-color: #HEX;
    --secondary-color: #HEX;
    --accent-color: #HEX;
    
    /* Background Colors */
    --bg-primary: #HEX;
    --bg-secondary: #HEX;
    --bg-card: rgba(R, G, B, A);
    
    /* Text Colors */
    --text-primary: #HEX;
    --text-secondary: #HEX;
    --text-muted: #HEX;
    
    /* Status Colors */
    --success-color: #HEX;
    --error-color: #HEX;
    --warning-color: #HEX;
    --info-color: #HEX;
    
    /* Borders & Shadows */
    --border-color: rgba(R, G, B, A);
    --border-radius: Xpx;
    --box-shadow: VALUES;
    
    /* Typography */
    --font-family: 'FONT', sans-serif;
    --font-size-base: Xpx;
}

/* Body Styling */
body {
    font-family: var(--font-family);
    background: var(--primary-gradient);
    color: var(--text-primary);
}

/* Card Styles */
.card {
    background: var(--bg-card);
    backdrop-filter: blur(10px);
    border-radius: var(--border-radius);
    border: 1px solid var(--border-color);
    box-shadow: var(--box-shadow);
    padding: 25px;
}

/* Button Styles */
.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-primary {
    background: var(--primary-gradient);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
}

/* Form Inputs */
.form-input {
    width: 100%;
    padding: 12px 15px;
    background: rgba(255, 255, 255, 0.15);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    color: var(--text-primary);
    font-size: 14px;
}

.form-input:focus {
    outline: none;
    border-color: var(--accent-color);
}

/* Table Styles */
.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th {
    background: rgba(251, 191, 36, 0.2);
    color: var(--accent-color);
    padding: 12px;
    text-align: left;
}

.data-table td {
    padding: 10px 12px;
    border-bottom: 1px solid var(--border-color);
}

/* Animations */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
\`\`\`

## Important Notes
- Extract ACTUAL values from the webpage, not placeholders
- Maintain the exact format above for AppMaker compatibility
- Include all color values as HEX or rgba()
- Preserve all gradient definitions
- Keep animation keyframes intact
- The output should be copy-paste ready for the "Custom Theme Code" textarea
- If the webpage has multiple themes/modes, extract the primary/default one

## How to Use
1. I will now paste the URL below this prompt
2. You analyze the webpage
3. You provide the extracted theme in the format above

Please wait for me to provide the URL, or if I've already included it above, proceed with the extraction.`;

        navigator.clipboard.writeText(prompt).then(() => {
            showCustomToast('🔗 URL extraction prompt copied! Paste it to your AI assistant.', 'success');
            showPromptCopiedFeedback('url');
        }).catch(() => {
            showCustomToast('❌ Failed to copy prompt', 'error');
        });
    }

    // Show visual feedback when prompt is copied
    function showPromptCopiedFeedback(type) {
        const modal = document.createElement('div');
        modal.style.cssText =
            'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); backdrop-filter: blur(8px); z-index: 10000; display: flex; align-items: center; justify-content: center; animation: fadeIn 0.3s ease-out;';

        const icon = type === 'app' ? '📁' : '🔗';
        const title = type === 'app' ? 'App Extraction Prompt Copied!' : 'URL Extraction Prompt Copied!';
        const step3 = type === 'url' ? '3️⃣ Add the URL you want to extract from' :
            '3️⃣ The AI will scan your project files';
        const step4num = type === 'url' ? '4️⃣' : '3️⃣';
        const step5num = type === 'url' ? '5️⃣' : '4️⃣';

        modal.innerHTML =
            '<div style="background: linear-gradient(135deg, rgba(102,126,234,0.98) 0%, rgba(118,75,162,0.98) 100%); border-radius: 20px; padding: 35px; max-width: 550px; width: 90%; box-shadow: 0 25px 80px rgba(0,0,0,0.6); border: 2px solid rgba(255,255,255,0.2); text-align: center;">' +
            '<div style="font-size: 72px; margin-bottom: 20px;">' + icon + '</div>' +
            '<h3 style="color: #fbbf24; font-size: 22px; font-weight: 700; margin-bottom: 15px;">' + title + '</h3>' +
            '<div style="background: rgba(0,0,0,0.3); padding: 20px; border-radius: 12px; margin-bottom: 20px; text-align: left;">' +
            '<div style="font-size: 14px; line-height: 1.8; color: rgba(255,255,255,0.95);">' +
            '<div style="margin-bottom: 10px;"><strong style="color: #86efac;">✅ Next Steps:</strong></div>' +
            '<div style="margin-bottom: 8px;">1️⃣ Open your AI assistant (Cursor, Claude, ChatGPT, etc.)</div>' +
            '<div style="margin-bottom: 8px;">2️⃣ Paste the prompt (Ctrl+V / Cmd+V)</div>' +
            '<div style="margin-bottom: 8px;">' + step3 + '</div>' +
            '<div style="margin-bottom: 8px;">' + step4num + ' Copy the generated theme CSS</div>' +
            '<div>' + step5num + ' Paste it in the "Custom Theme Code" textarea above</div>' +
            '</div>' +
            '</div>' +
            '<button onclick="this.closest(\'[style*=fixed]\').remove()" style="padding: 14px 40px; background: linear-gradient(135deg, #22c55e 0%, #15803d 100%); border: none; border-radius: 10px; color: #fff; cursor: pointer; font-weight: 700; font-size: 16px; box-shadow: 0 4px 15px rgba(34,197,94,0.4); transition: all 0.3s;" onmouseover="this.style.transform=\'translateY(-2px)\'; this.style.boxShadow=\'0 6px 20px rgba(34,197,94,0.6)\'" onmouseout="this.style.transform=\'translateY(0)\'; this.style.boxShadow=\'0 4px 15px rgba(34,197,94,0.4)\'">' +
            'Got it! 👍' +
            '</button>' +
            '</div>';

        document.body.appendChild(modal);

        // Auto-close after 8 seconds
        setTimeout(function() {
            if (modal.parentNode) {
                modal.style.animation = 'fadeOut 0.3s ease-out forwards';
                setTimeout(function() {
                    modal.remove();
                }, 300);
            }
        }, 8000);
    }
    </script>


    <!-- Back to Catalog Button -->
    <a href="prompt-manager.php" id="backToCatalogBtn" class="catalog-back-btn"
        style="position: fixed; bottom: 30px; left: 30px; width: 70px; height: 70px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 25px rgba(240, 147, 251, 0.5); z-index: 9999; text-decoration: none; transition: all 0.3s ease; border: 3px solid rgba(255, 255, 255, 0.3); animation: catalog-pulse 2s infinite;"
        title="Back to Catalog"
        onmouseover="this.style.transform='scale(1.15) rotate(-10deg)'; this.style.boxShadow='0 10px 35px rgba(240, 147, 251, 0.7)';"
        onmouseout="this.style.transform='scale(1) rotate(0deg)'; this.style.boxShadow='0 8px 25px rgba(240, 147, 251, 0.5)';">
        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white"
            stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
            style="filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));">
            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
            <polyline points="9 22 9 12 15 12 15 22"></polyline>
        </svg>
    </a>
    <style>
    @keyframes catalog-pulse {

        0%,
        100% {
            box-shadow: 0 8px 25px rgba(240, 147, 251, 0.5), 0 0 0 0 rgba(240, 147, 251, 0.4);
        }

        50% {
            box-shadow: 0 8px 25px rgba(240, 147, 251, 0.5), 0 0 0 10px rgba(240, 147, 251, 0);
        }
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
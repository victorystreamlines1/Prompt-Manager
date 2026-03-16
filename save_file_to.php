<?php
/**
 * Save File To Specific Location Endpoint
 * Receives content, filename, extension, and directory path via POST.
 * Saves the file to the specified directory on the server/local machine.
 * Returns JSON response indicating success or failure.
 */

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$content  = $_POST['content']   ?? '';
$filename = $_POST['filename']  ?? 'untitled';
$ext      = $_POST['extension'] ?? 'html';
$dirPath  = $_POST['directory'] ?? '';

// Sanitize filename
$filename = preg_replace('/[\/\\\\:*?"<>|]/', '', $filename);
$filename = trim($filename);
if ($filename === '') {
    $filename = 'untitled';
}

// Sanitize extension
$ext = preg_replace('/[^a-zA-Z0-9]/', '', $ext);
if ($ext === '') {
    $ext = 'html';
}

// Validate directory path
$dirPath = trim($dirPath);
$dirPath = rtrim($dirPath, '/\\');

if ($dirPath === '') {
    echo json_encode(['success' => false, 'error' => 'No directory path provided']);
    exit;
}

// Check if directory exists
if (!is_dir($dirPath)) {
    echo json_encode([
        'success' => false,
        'error' => 'Directory does not exist: ' . $dirPath
    ]);
    exit;
}

// Check if directory is writable
if (!is_writable($dirPath)) {
    echo json_encode([
        'success' => false,
        'error' => 'Directory is not writable: ' . $dirPath
    ]);
    exit;
}

$fullname = $filename . '.' . $ext;
$fullPath = $dirPath . DIRECTORY_SEPARATOR . $fullname;

// Write the file
$result = file_put_contents($fullPath, $content);

if ($result === false) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to write file to: ' . $fullPath
    ]);
    exit;
}

echo json_encode([
    'success'  => true,
    'path'     => $fullPath,
    'filename' => $fullname,
    'size'     => $result
]);

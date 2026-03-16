<?php
/**
 * Save File Endpoint
 * Receives content and filename via POST, returns as a downloadable file.
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$content  = $_POST['content']  ?? '';
$filename = $_POST['filename'] ?? 'untitled';
$ext      = $_POST['extension'] ?? 'html';

// Sanitize filename: remove path separators and dangerous characters
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

$fullname = $filename . '.' . $ext;

// Map extensions to MIME types
$mimeTypes = [
    'html' => 'text/html',
    'htm'  => 'text/html',
    'css'  => 'text/css',
    'js'   => 'application/javascript',
    'json' => 'application/json',
    'xml'  => 'application/xml',
    'svg'  => 'image/svg+xml',
    'txt'  => 'text/plain',
    'md'   => 'text/markdown',
    'csv'  => 'text/csv',
    'php'  => 'application/x-httpd-php',
    'py'   => 'text/x-python',
    'sql'  => 'application/sql',
];

$mime = $mimeTypes[strtolower($ext)] ?? 'application/octet-stream';

// Set headers for file download
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $fullname . '"');
header('Content-Length: ' . strlen($content));
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

echo $content;
exit;

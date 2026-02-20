<?php
/**
 * URL Autocomplete Endpoint
 * Returns HTML/PHP files and directories for a given path relative to the web document root.
 * Usage: url_suggest.php?q=Prompt-Manager/  → returns files/dirs inside that folder
 */

session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    exit(json_encode(['error' => 'Forbidden']));
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');

$q = isset($_GET['q']) ? trim($_GET['q']) : '';

// Determine the base directory to scan from.
// Priority: 1) explicit 'root' param  2) DOCUMENT_ROOT
$docRoot = '';
if (!empty($_GET['root'])) {
    $docRoot = rtrim(str_replace('\\', '/', $_GET['root']), '/');
} else {
    $docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
}

// Security: block directory traversal
$q = str_replace('..', '', $q);

// Build the full filesystem path
$fsPath = $docRoot . '/' . ltrim($q, '/');
$fsPath = str_replace('\\', '/', $fsPath);

// Determine which directory to scan and what prefix to filter by
$dirToScan = '';
$prefix = '';

if (is_dir($fsPath)) {
    // The path itself is a directory — list its contents
    $dirToScan = $fsPath;
    $prefix = '';
} else {
    // The path might be a partial file/folder name — scan the parent dir
    $dirToScan = dirname($fsPath);
    $prefix = strtolower(basename($fsPath));
}

$results = [];

if (is_dir($dirToScan)) {
    $items = @scandir($dirToScan);
    if ($items === false) $items = [];
    
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        
        // If there's a prefix, filter by it
        if ($prefix !== '' && stripos($item, $prefix) !== 0) continue;
        
        $fullPath = $dirToScan . '/' . $item;
        $isDir = is_dir($fullPath);
        
        if ($isDir) {
            $results[] = [
                'name'  => $item . '/',
                'type'  => 'dir',
                'icon'  => 'folder'
            ];
        } else {
            $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
            // Only suggest HTML and PHP files
            if (in_array($ext, ['html', 'htm', 'php'])) {
                $results[] = [
                    'name'  => $item,
                    'type'  => 'file',
                    'ext'   => $ext,
                    'icon'  => $ext === 'php' ? 'php' : 'html'
                ];
            }
        }
    }
    
    // Sort: directories first, then files, both alphabetical
    usort($results, function($a, $b) {
        if ($a['type'] !== $b['type']) return $a['type'] === 'dir' ? -1 : 1;
        return strnatcasecmp($a['name'], $b['name']);
    });
}

echo json_encode(['items' => $results, 'query' => $q]);

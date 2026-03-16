<?php
/**
 * Pathfinder API - Browse directories on the server filesystem
 * Returns JSON list of directories for a given path
 */

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

if ($action === 'list') {
    $path = $_GET['path'] ?? 'C:\\';
    
    // Normalize path
    $path = str_replace('/', '\\', $path);
    $path = rtrim($path, '\\') . '\\';
    
    // Validate path exists
    if (!is_dir($path)) {
        echo json_encode([
            'success' => false,
            'error' => 'Directory not found: ' . $path
        ]);
        exit;
    }
    
    $items = [];
    
    try {
        $entries = @scandir($path);
        if ($entries === false) {
            echo json_encode([
                'success' => false,
                'error' => 'Cannot read directory: ' . $path
            ]);
            exit;
        }
        
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') continue;
            
            $fullPath = $path . $entry;
            $isDir = is_dir($fullPath);
            
            // Only return directories for the pathfinder
            if ($isDir) {
                $items[] = [
                    'name' => $entry,
                    'path' => $fullPath,
                    'type' => 'directory',
                    'readable' => is_readable($fullPath),
                    'writable' => is_writable($fullPath)
                ];
            }
        }
        
        // Sort directories alphabetically
        usort($items, function($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });
        
        // Build breadcrumbs
        $breadcrumbs = [];
        $parts = array_filter(explode('\\', $path));
        $buildPath = '';
        foreach ($parts as $part) {
            $buildPath .= $part . '\\';
            $breadcrumbs[] = [
                'name' => $part,
                'path' => $buildPath
            ];
        }
        
        echo json_encode([
            'success' => true,
            'path' => $path,
            'breadcrumbs' => $breadcrumbs,
            'directories' => $items,
            'count' => count($items)
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Error reading directory: ' . $e->getMessage()
        ]);
    }
    
} elseif ($action === 'search') {
    $query = $_GET['query'] ?? '';
    $basePath = $_GET['base'] ?? 'C:\\';
    
    $basePath = str_replace('/', '\\', $basePath);
    $basePath = rtrim($basePath, '\\') . '\\';
    
    if (!$query || strlen($query) < 2) {
        echo json_encode([
            'success' => false,
            'error' => 'Search query must be at least 2 characters'
        ]);
        exit;
    }
    
    if (!is_dir($basePath)) {
        echo json_encode([
            'success' => false,
            'error' => 'Base directory not found'
        ]);
        exit;
    }
    
    $results = [];
    $maxResults = 50;
    $maxDepth = 3;
    
    function searchDirs($dir, $query, &$results, $maxResults, $depth, $maxDepth) {
        if ($depth > $maxDepth || count($results) >= $maxResults) return;
        
        $entries = @scandir($dir);
        if ($entries === false) return;
        
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') continue;
            if (count($results) >= $maxResults) return;
            
            $fullPath = rtrim($dir, '\\') . '\\' . $entry;
            
            if (is_dir($fullPath)) {
                if (stripos($entry, $query) !== false) {
                    $results[] = [
                        'name' => $entry,
                        'path' => $fullPath,
                        'writable' => is_writable($fullPath)
                    ];
                }
                // Recurse into subdirectories
                searchDirs($fullPath, $query, $results, $maxResults, $depth + 1, $maxDepth);
            }
        }
    }
    
    searchDirs($basePath, $query, $results, $maxResults, 0, $maxDepth);
    
    echo json_encode([
        'success' => true,
        'query' => $query,
        'base' => $basePath,
        'results' => $results,
        'count' => count($results)
    ]);
    
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Unknown action: ' . $action
    ]);
}

<?php
/**
 * Visual Prompter - Load Project API
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Projects directory
$projectsDir = __DIR__ . '/../projects';

// List all projects
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['file'])) {
    $projects = [];
    
    if (is_dir($projectsDir)) {
        $files = glob($projectsDir . '/*.json');
        foreach ($files as $file) {
            $content = file_get_contents($file);
            $data = json_decode($content, true);
            
            $projects[] = [
                'filename' => basename($file),
                'projectName' => $data['projectName'] ?? 'Untitled',
                'savedAt' => $data['savedAt'] ?? filemtime($file),
                'nodeCount' => isset($data['graph']['nodes']) ? count($data['graph']['nodes']) : 0
            ];
        }
    }
    
    // Sort by date (newest first)
    usort($projects, function($a, $b) {
        return strtotime($b['savedAt']) - strtotime($a['savedAt']);
    });
    
    echo json_encode([
        'success' => true,
        'projects' => $projects
    ]);
    exit;
}

// Load specific project
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['file'])) {
    $filename = basename($_GET['file']); // Security: prevent path traversal
    $filepath = $projectsDir . '/' . $filename;
    
    if (!file_exists($filepath)) {
        http_response_code(404);
        echo json_encode(['error' => 'Project not found']);
        exit;
    }
    
    $content = file_get_contents($filepath);
    $data = json_decode($content, true);
    
    if (!$data) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to parse project file']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'project' => $data
    ]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid request']);


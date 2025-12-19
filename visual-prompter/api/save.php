<?php
/**
 * Visual Prompter - Save Project API
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['projectName'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid data']);
    exit;
}

// Create projects directory if not exists
$projectsDir = __DIR__ . '/../projects';
if (!is_dir($projectsDir)) {
    mkdir($projectsDir, 0755, true);
}

// Generate filename
$filename = preg_replace('/[^a-z0-9]/i', '_', $data['projectName']);
$filename = strtolower($filename) . '_' . date('YmdHis') . '.json';
$filepath = $projectsDir . '/' . $filename;

// Add metadata
$data['savedAt'] = date('c');
$data['filename'] = $filename;

// Save file
if (file_put_contents($filepath, json_encode($data, JSON_PRETTY_PRINT))) {
    echo json_encode([
        'success' => true,
        'message' => 'Project saved successfully',
        'filename' => $filename,
        'savedAt' => $data['savedAt']
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save project']);
}


<?php
/**
 * AI Prompt Dictionary - Public Search API
 * 
 * A comprehensive API endpoint for external applications to access
 * the prompt dictionary database with full search functionality.
 * 
 * ============================================================
 * API DOCUMENTATION
 * ============================================================
 * 
 * BASE URL: /api/search-api.php
 * METHOD: GET
 * 
 * PARAMETERS:
 * -----------
 * action      (string)  - API action: 'search', 'list', 'groups', 'prompt', 'stats'
 * q           (string)  - Search query (for action=search)
 * group_id    (int)     - Filter by group ID
 * page        (int)     - Page number (default: 1)
 * limit       (int)     - Items per page (default: 20, max: 100)
 * order       (string)  - Order by: 'title', 'created_at', 'id' (default: 'title')
 * direction   (string)  - Sort direction: 'ASC', 'DESC' (default: 'ASC')
 * fields      (string)  - Comma-separated fields to include (optional)
 * 
 * ACTIONS:
 * --------
 * 1. search   - Search prompts by title or phrase
 * 2. list     - List all prompts with optional filters
 * 3. groups   - Get all available groups
 * 4. prompt   - Get single prompt by ID (requires 'id' parameter)
 * 5. stats    - Get database statistics
 * 
 * EXAMPLES:
 * ---------
 * GET /api/search-api.php?action=search&q=button
 * GET /api/search-api.php?action=list&group_id=1&page=1&limit=10
 * GET /api/search-api.php?action=groups
 * GET /api/search-api.php?action=prompt&id=5
 * GET /api/search-api.php?action=stats
 * 
 * ============================================================
 */

// CORS headers for external access
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Cache control - allow fresh data with ?refresh=1
$noCache = isset($_GET['refresh']) && $_GET['refresh'] == '1';
if ($noCache) {
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
} else {
    header('Cache-Control: public, max-age=30'); // Reduced to 30 seconds for faster updates
}

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed',
        'message' => 'Only GET requests are supported'
    ]);
    exit();
}

require_once __DIR__ . '/../classes/Prompt.php';
require_once __DIR__ . '/../classes/PromptGroup.php';

// Initialize classes
$promptClass = new Prompt();
$groupClass = new PromptGroup();

// Get parameters
$action = isset($_GET['action']) ? strtolower(trim($_GET['action'])) : 'list';
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$groupId = isset($_GET['group_id']) ? (int)$_GET['group_id'] : null;
$promptId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 20;
$orderBy = isset($_GET['order']) ? strtolower(trim($_GET['order'])) : 'title';
$direction = isset($_GET['direction']) ? strtoupper(trim($_GET['direction'])) : 'ASC';
$fields = isset($_GET['fields']) ? explode(',', $_GET['fields']) : null;
$offset = ($page - 1) * $limit;

// Validate order parameters
$allowedOrderBy = ['title', 'created_at', 'id', 'order_index'];
$orderBy = in_array($orderBy, $allowedOrderBy) ? $orderBy : 'title';
$direction = in_array($direction, ['ASC', 'DESC']) ? $direction : 'ASC';

/**
 * Format prompt data for API response
 */
function formatPrompt($prompt, $fields = null) {
    $data = [
        'id' => (int)$prompt['id'],
        'title' => $prompt['title'],
        'phrase' => $prompt['phrase'],
        'group_id' => $prompt['group_id'] ? (int)$prompt['group_id'] : null,
        'group_title' => $prompt['group_title'] ?? null,
        'order_index' => (int)($prompt['order_index'] ?? 0),
        'html_code' => $prompt['html_code'] ?? null,
        'css_code' => $prompt['css_code'] ?? null,
        'js_code' => $prompt['js_code'] ?? null,
        'full_code' => $prompt['full_code'] ?? null,
        'output_image' => $prompt['output_image'] ?? null,
        'has_html' => !empty($prompt['html_code']),
        'has_css' => !empty($prompt['css_code']),
        'has_js' => !empty($prompt['js_code']),
        'has_full_code' => !empty($prompt['full_code']),
        'has_output_image' => !empty($prompt['output_image']),
        'created_at' => $prompt['created_at'] ?? null,
        'updated_at' => $prompt['updated_at'] ?? null
    ];
    
    // Filter fields if specified
    if ($fields && is_array($fields)) {
        $filtered = [];
        foreach ($fields as $field) {
            $field = trim($field);
            if (isset($data[$field])) {
                $filtered[$field] = $data[$field];
            }
        }
        // Always include id
        $filtered['id'] = $data['id'];
        return $filtered;
    }
    
    return $data;
}

/**
 * Format group data for API response
 */
function formatGroup($group) {
    return [
        'id' => (int)$group['id'],
        'title' => $group['title'],
        'description' => $group['description'] ?? null,
        'order_index' => (int)($group['order_index'] ?? 0),
        'prompt_count' => (int)($group['prompt_count'] ?? 0),
        'created_at' => $group['created_at'] ?? null,
        'updated_at' => $group['updated_at'] ?? null
    ];
}

/**
 * Build pagination info
 */
function buildPagination($totalItems, $page, $limit) {
    $totalPages = $totalItems > 0 ? ceil($totalItems / $limit) : 1;
    return [
        'current_page' => $page,
        'total_pages' => $totalPages,
        'total_items' => $totalItems,
        'items_per_page' => $limit,
        'has_next' => $page < $totalPages,
        'has_prev' => $page > 1,
        'next_page' => $page < $totalPages ? $page + 1 : null,
        'prev_page' => $page > 1 ? $page - 1 : null
    ];
}

try {
    $response = [
        'success' => true,
        'api_version' => '1.0',
        'timestamp' => time(),
        'action' => $action
    ];
    
    switch ($action) {
        // ============================================================
        // ACTION: SEARCH - Search prompts by query
        // ============================================================
        case 'search':
            if (empty($search)) {
                $response['error'] = 'Search query is required';
                $response['message'] = 'Please provide a search query using the "q" parameter';
                $response['success'] = false;
                break;
            }
            
            $allPrompts = $promptClass->search($search);
            
            // Filter by group if specified
            if ($groupId) {
                $allPrompts = array_filter($allPrompts, function($p) use ($groupId) {
                    return isset($p['group_id']) && (int)$p['group_id'] === $groupId;
                });
                $allPrompts = array_values($allPrompts);
            }
            
            $totalCount = count($allPrompts);
            $prompts = array_slice($allPrompts, $offset, $limit);
            
            $response['data'] = [
                'query' => $search,
                'items' => array_map(function($p) use ($fields) {
                    return formatPrompt($p, $fields);
                }, $prompts),
                'pagination' => buildPagination($totalCount, $page, $limit),
                'filters' => [
                    'search' => $search,
                    'group_id' => $groupId
                ]
            ];
            break;
        
        // ============================================================
        // ACTION: LIST - List all prompts with filters
        // ============================================================
        case 'list':
            if (!empty($search)) {
                $allPrompts = $promptClass->search($search);
            } else {
                $allPrompts = $promptClass->getAll($orderBy, $direction);
            }
            
            // Filter by group if specified
            if ($groupId) {
                $allPrompts = array_filter($allPrompts, function($p) use ($groupId) {
                    return isset($p['group_id']) && (int)$p['group_id'] === $groupId;
                });
                $allPrompts = array_values($allPrompts);
            }
            
            $totalCount = count($allPrompts);
            $prompts = array_slice($allPrompts, $offset, $limit);
            
            $response['data'] = [
                'items' => array_map(function($p) use ($fields) {
                    return formatPrompt($p, $fields);
                }, $prompts),
                'pagination' => buildPagination($totalCount, $page, $limit),
                'filters' => [
                    'search' => $search ?: null,
                    'group_id' => $groupId,
                    'order' => $orderBy,
                    'direction' => $direction
                ]
            ];
            break;
        
        // ============================================================
        // ACTION: GROUPS - Get all groups
        // ============================================================
        case 'groups':
            $groups = $groupClass->getAll('title', 'ASC');
            
            $response['data'] = [
                'items' => array_map('formatGroup', $groups),
                'total_count' => count($groups)
            ];
            break;
        
        // ============================================================
        // ACTION: PROMPT - Get single prompt by ID
        // ============================================================
        case 'prompt':
            if (!$promptId) {
                $response['error'] = 'Prompt ID is required';
                $response['message'] = 'Please provide a prompt ID using the "id" parameter';
                $response['success'] = false;
                break;
            }
            
            $prompt = $promptClass->getById($promptId);
            
            if (!$prompt) {
                http_response_code(404);
                $response['error'] = 'Prompt not found';
                $response['message'] = "No prompt found with ID: {$promptId}";
                $response['success'] = false;
                break;
            }
            
            $response['data'] = formatPrompt($prompt, $fields);
            break;
        
        // ============================================================
        // ACTION: STATS - Get database statistics
        // ============================================================
        case 'stats':
            $totalPrompts = $promptClass->getCount();
            $totalGroups = $groupClass->getCount();
            $groups = $groupClass->getAll();
            
            // Count prompts by group
            $groupStats = array_map(function($g) {
                return [
                    'id' => (int)$g['id'],
                    'title' => $g['title'],
                    'prompt_count' => (int)$g['prompt_count']
                ];
            }, $groups);
            
            $response['data'] = [
                'total_prompts' => $totalPrompts,
                'total_groups' => $totalGroups,
                'groups' => $groupStats,
                'available_letters' => $promptClass->getAvailableLetters()
            ];
            break;
        
        // ============================================================
        // UNKNOWN ACTION
        // ============================================================
        default:
            $response['error'] = 'Unknown action';
            $response['message'] = "Action '{$action}' is not supported. Available actions: search, list, groups, prompt, stats";
            $response['success'] = false;
            break;
    }
    
    // Output response
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'message' => $e->getMessage(),
        'timestamp' => time()
    ], JSON_PRETTY_PRINT);
}

<?php
/**
 * Visual Prompter - Projects API
 * Handles CRUD operations for projects
 * 
 * Endpoints:
 * GET    ?action=list          - List all projects
 * GET    ?action=get&id=X      - Get single project with all data
 * POST   ?action=save          - Save/update project
 * POST   ?action=delete&id=X   - Delete project
 * GET    ?action=recent        - Get recent projects
 * POST   ?action=duplicate&id=X - Duplicate a project
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Database connection
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        $host = 'srv1788.hstgr.io';
        $dbname = 'u419999707_Mohamed';
        $username = 'u419999707_Abuammar';
        $password = 'P@master5007';
        $port = 3306;

        $pdo = new PDO(
            "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4",
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
    }
    return $pdo;
}

// Generate UUID
function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    $pdo = getDB();

    switch ($action) {
        // ============================================
        // LIST ALL PROJECTS
        // ============================================
        case 'list':
            $stmt = $pdo->query("
                SELECT 
                    p.id,
                    p.uuid,
                    p.name,
                    p.description,
                    p.thumbnail,
                    p.tags,
                    p.is_template,
                    p.created_at,
                    p.updated_at,
                    p.last_opened_at,
                    p.version,
                    (SELECT COUNT(*) FROM visual_prompter_nodes WHERE project_id = p.id) as node_count,
                    (SELECT COUNT(*) FROM visual_prompter_connections WHERE project_id = p.id) as connection_count
                FROM visual_prompter_projects p
                WHERE p.is_template = 0
                ORDER BY p.updated_at DESC
            ");
            $projects = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'projects' => $projects,
                'count' => count($projects)
            ]);
            break;

        // ============================================
        // GET RECENT PROJECTS
        // ============================================
        case 'recent':
            $limit = intval($_GET['limit'] ?? 10);
            $stmt = $pdo->prepare("
                SELECT 
                    p.id,
                    p.uuid,
                    p.name,
                    p.thumbnail,
                    p.updated_at,
                    (SELECT COUNT(*) FROM visual_prompter_nodes WHERE project_id = p.id) as node_count
                FROM visual_prompter_projects p
                WHERE p.is_template = 0
                ORDER BY COALESCE(p.last_opened_at, p.updated_at) DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            $projects = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'projects' => $projects
            ]);
            break;

        // ============================================
        // GET SINGLE PROJECT WITH ALL DATA
        // ============================================
        case 'get':
            $id = $_GET['id'] ?? null;
            $uuid = $_GET['uuid'] ?? null;
            
            if (!$id && !$uuid) {
                throw new Exception('Project ID or UUID required');
            }

            // Get project
            if ($uuid) {
                $stmt = $pdo->prepare("SELECT * FROM visual_prompter_projects WHERE uuid = ?");
                $stmt->execute([$uuid]);
            } else {
                $stmt = $pdo->prepare("SELECT * FROM visual_prompter_projects WHERE id = ?");
                $stmt->execute([$id]);
            }
            $project = $stmt->fetch();

            if (!$project) {
                throw new Exception('Project not found');
            }

            // Update last opened
            $pdo->prepare("UPDATE visual_prompter_projects SET last_opened_at = NOW() WHERE id = ?")
                ->execute([$project['id']]);

            // Get nodes
            $stmt = $pdo->prepare("SELECT * FROM visual_prompter_nodes WHERE project_id = ? ORDER BY id");
            $stmt->execute([$project['id']]);
            $nodes = $stmt->fetchAll();

            // Decode JSON properties for each node
            foreach ($nodes as &$node) {
                if ($node['properties_json']) {
                    $node['properties'] = json_decode($node['properties_json'], true);
                }
            }

            // Get connections
            $stmt = $pdo->prepare("SELECT * FROM visual_prompter_connections WHERE project_id = ?");
            $stmt->execute([$project['id']]);
            $connections = $stmt->fetchAll();

            // Parse canvas config
            if ($project['canvas_config']) {
                $project['canvas_config'] = json_decode($project['canvas_config'], true);
            }

            echo json_encode([
                'success' => true,
                'project' => $project,
                'nodes' => $nodes,
                'connections' => $connections
            ]);
            break;

        // ============================================
        // SAVE PROJECT (Create or Update)
        // ============================================
        case 'save':
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                throw new Exception('Invalid JSON input');
            }

            $projectData = $input['project'] ?? [];
            $nodesData = $input['nodes'] ?? [];
            $connectionsData = $input['connections'] ?? [];
            $graphData = $input['graph'] ?? null; // LiteGraph serialized data

            $pdo->beginTransaction();

            try {
                $projectId = $projectData['id'] ?? null;
                $uuid = $projectData['uuid'] ?? null;

                // Check if updating existing project
                if ($uuid) {
                    $stmt = $pdo->prepare("SELECT id FROM visual_prompter_projects WHERE uuid = ?");
                    $stmt->execute([$uuid]);
                    $existing = $stmt->fetch();
                    if ($existing) {
                        $projectId = $existing['id'];
                    }
                }

                if ($projectId) {
                    // UPDATE existing project
                    $stmt = $pdo->prepare("
                        UPDATE visual_prompter_projects SET
                            name = ?,
                            description = ?,
                            thumbnail = ?,
                            canvas_config = ?,
                            tags = ?,
                            version = version + 1,
                            updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $projectData['name'] ?? 'Untitled Project',
                        $projectData['description'] ?? null,
                        $projectData['thumbnail'] ?? null,
                        json_encode($projectData['canvas_config'] ?? null),
                        $projectData['tags'] ?? null,
                        $projectId
                    ]);

                    // Delete existing nodes and connections (will cascade)
                    $pdo->prepare("DELETE FROM visual_prompter_nodes WHERE project_id = ?")->execute([$projectId]);
                    $pdo->prepare("DELETE FROM visual_prompter_connections WHERE project_id = ?")->execute([$projectId]);

                } else {
                    // CREATE new project
                    $uuid = generateUUID();
                    $stmt = $pdo->prepare("
                        INSERT INTO visual_prompter_projects 
                        (uuid, name, description, thumbnail, canvas_config, tags)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $uuid,
                        $projectData['name'] ?? 'Untitled Project',
                        $projectData['description'] ?? null,
                        $projectData['thumbnail'] ?? null,
                        json_encode($projectData['canvas_config'] ?? null),
                        $projectData['tags'] ?? null
                    ]);
                    $projectId = $pdo->lastInsertId();
                }

                // Insert nodes
                if (!empty($nodesData)) {
                    $nodeStmt = $pdo->prepare("
                        INSERT INTO visual_prompter_nodes 
                        (project_id, node_id, node_type, title, description, 
                         position_x, position_y, size_width, size_height,
                         color, bgcolor, collapsed, properties_json)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");

                    foreach ($nodesData as $node) {
                        $nodeStmt->execute([
                            $projectId,
                            $node['id'] ?? 0,
                            $node['type'] ?? 'unknown',
                            $node['title'] ?? null,
                            $node['properties']['description'] ?? null,
                            $node['pos'][0] ?? 0,
                            $node['pos'][1] ?? 0,
                            $node['size'][0] ?? 200,
                            $node['size'][1] ?? 100,
                            $node['color'] ?? null,
                            $node['bgcolor'] ?? null,
                            $node['collapsed'] ?? 0,
                            json_encode($node['properties'] ?? [])
                        ]);
                    }
                }

                // Insert connections
                if (!empty($connectionsData)) {
                    $connStmt = $pdo->prepare("
                        INSERT INTO visual_prompter_connections 
                        (project_id, source_node_id, source_slot, target_node_id, target_slot, connection_type)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");

                    foreach ($connectionsData as $conn) {
                        $connStmt->execute([
                            $projectId,
                            $conn['origin_id'] ?? $conn['source_node_id'] ?? 0,
                            $conn['origin_slot'] ?? $conn['source_slot'] ?? 0,
                            $conn['target_id'] ?? $conn['target_node_id'] ?? 0,
                            $conn['target_slot'] ?? 0,
                            $conn['type'] ?? 'default'
                        ]);
                    }
                }

                // Save to history (optional - for version control)
                $historyStmt = $pdo->prepare("
                    INSERT INTO visual_prompter_project_history 
                    (project_id, version, snapshot_data, change_description)
                    SELECT id, version, ?, 'Auto-save'
                    FROM visual_prompter_projects WHERE id = ?
                ");
                $historyStmt->execute([
                    json_encode([
                        'project' => $projectData,
                        'nodes' => $nodesData,
                        'connections' => $connectionsData,
                        'graph' => $graphData
                    ]),
                    $projectId
                ]);

                // Clean old history (keep last N versions)
                $pdo->prepare("
                    DELETE FROM visual_prompter_project_history 
                    WHERE project_id = ? 
                    AND id NOT IN (
                        SELECT id FROM (
                            SELECT id FROM visual_prompter_project_history 
                            WHERE project_id = ? 
                            ORDER BY created_at DESC 
                            LIMIT 50
                        ) as recent
                    )
                ")->execute([$projectId, $projectId]);

                $pdo->commit();

                // Get updated project info
                $stmt = $pdo->prepare("SELECT uuid, version, updated_at FROM visual_prompter_projects WHERE id = ?");
                $stmt->execute([$projectId]);
                $updated = $stmt->fetch();

                echo json_encode([
                    'success' => true,
                    'message' => 'Project saved successfully',
                    'project_id' => $projectId,
                    'uuid' => $updated['uuid'],
                    'version' => $updated['version'],
                    'updated_at' => $updated['updated_at']
                ]);

            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;

        // ============================================
        // DELETE PROJECT
        // ============================================
        case 'delete':
            $id = $_GET['id'] ?? $_POST['id'] ?? null;
            $uuid = $_GET['uuid'] ?? $_POST['uuid'] ?? null;

            if (!$id && !$uuid) {
                throw new Exception('Project ID or UUID required');
            }

            if ($uuid) {
                $stmt = $pdo->prepare("DELETE FROM visual_prompter_projects WHERE uuid = ?");
                $stmt->execute([$uuid]);
            } else {
                $stmt = $pdo->prepare("DELETE FROM visual_prompter_projects WHERE id = ?");
                $stmt->execute([$id]);
            }

            echo json_encode([
                'success' => true,
                'message' => 'Project deleted successfully'
            ]);
            break;

        // ============================================
        // DUPLICATE PROJECT
        // ============================================
        case 'duplicate':
            $id = $_GET['id'] ?? $_POST['id'] ?? null;
            
            if (!$id) {
                throw new Exception('Project ID required');
            }

            $pdo->beginTransaction();

            try {
                // Get original project
                $stmt = $pdo->prepare("SELECT * FROM visual_prompter_projects WHERE id = ?");
                $stmt->execute([$id]);
                $original = $stmt->fetch();

                if (!$original) {
                    throw new Exception('Project not found');
                }

                // Create new project
                $newUuid = generateUUID();
                $stmt = $pdo->prepare("
                    INSERT INTO visual_prompter_projects 
                    (uuid, name, description, canvas_config, tags)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $newUuid,
                    $original['name'] . ' (Copy)',
                    $original['description'],
                    $original['canvas_config'],
                    $original['tags']
                ]);
                $newProjectId = $pdo->lastInsertId();

                // Copy nodes
                $pdo->prepare("
                    INSERT INTO visual_prompter_nodes 
                    (project_id, node_id, node_type, title, description, 
                     position_x, position_y, size_width, size_height,
                     color, bgcolor, collapsed, properties_json)
                    SELECT ?, node_id, node_type, title, description,
                           position_x, position_y, size_width, size_height,
                           color, bgcolor, collapsed, properties_json
                    FROM visual_prompter_nodes WHERE project_id = ?
                ")->execute([$newProjectId, $id]);

                // Copy connections
                $pdo->prepare("
                    INSERT INTO visual_prompter_connections 
                    (project_id, source_node_id, source_slot, target_node_id, target_slot, connection_type)
                    SELECT ?, source_node_id, source_slot, target_node_id, target_slot, connection_type
                    FROM visual_prompter_connections WHERE project_id = ?
                ")->execute([$newProjectId, $id]);

                $pdo->commit();

                echo json_encode([
                    'success' => true,
                    'message' => 'Project duplicated successfully',
                    'new_project_id' => $newProjectId,
                    'new_uuid' => $newUuid
                ]);

            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;

        default:
            throw new Exception('Invalid action. Use: list, get, save, delete, recent, duplicate');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}


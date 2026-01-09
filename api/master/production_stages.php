<?php
/**
 * Barron Production Management System
 * Production Stages API Endpoint
 * 
 * Handles CRUD operations for production stages
 */

header('Content-Type: application/json');
require_once '../config/config.php';
require_once '../classes/Database.php';
require_once '../classes/Auth.php';
require_once '../classes/ProductionStages.php';
require_once '../helpers/functions.php';

// Start session and check authentication
session_start();
$auth = new Auth();

if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check permissions
if (!hasPermission('master.view') && $_SERVER['REQUEST_METHOD'] === 'GET') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden: Insufficient permissions']);
    exit;
}

if (!hasPermission('master.edit') && in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'DELETE'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden: Insufficient permissions']);
    exit;
}

$productionStages = new ProductionStages();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            handleGet($productionStages);
            break;
            
        case 'POST':
            handlePost($productionStages);
            break;
            
        case 'PUT':
            handlePut($productionStages);
            break;
            
        case 'DELETE':
            handleDelete($productionStages);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Handle GET requests
 */
function handleGet($productionStages) {
    // Get all departments with stages
    if (isset($_GET['action']) && $_GET['action'] === 'departments') {
        $result = $productionStages->getAllWithDepartments();
        echo json_encode([
            'success' => true,
            'data' => $result
        ]);
        return;
    }
    
    // Get stages by department
    if (isset($_GET['department_id'])) {
        $active_only = isset($_GET['active_only']) && $_GET['active_only'] === 'true';
        
        if ($active_only) {
            $result = $productionStages->getActiveByDepartment($_GET['department_id']);
        } else {
            $result = $productionStages->getByDepartment($_GET['department_id']);
        }
        
        echo json_encode([
            'success' => true,
            'data' => $result
        ]);
        return;
    }
    
    // Get single stage by ID
    if (isset($_GET['id'])) {
        $result = $productionStages->getById($_GET['id']);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Production stage not found'
            ]);
        }
        return;
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Missing required parameter: department_id or id'
    ]);
}

/**
 * Handle POST requests (Create)
 */
function handlePost($productionStages) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid JSON data'
        ]);
        return;
    }
    
    // Handle reorder action
    if (isset($data['action']) && $data['action'] === 'reorder') {
        if (!isset($data['department_id']) || !isset($data['order'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Missing required fields: department_id, order'
            ]);
            return;
        }
        
        $result = $productionStages->reorder($data['department_id'], $data['order']);
        echo json_encode($result);
        return;
    }
    
    // Create new stage
    $result = $productionStages->create($data);
    http_response_code(201);
    echo json_encode($result);
}

/**
 * Handle PUT requests (Update)
 */
function handlePut($productionStages) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Missing required field: id'
        ]);
        return;
    }
    
    $id = $data['id'];
    unset($data['id']);
    
    // Handle toggle active action
    if (isset($data['action']) && $data['action'] === 'toggle_active') {
        $result = $productionStages->toggleActive($id);
        echo json_encode($result);
        return;
    }
    
    // Update stage
    $result = $productionStages->update($id, $data);
    echo json_encode($result);
}

/**
 * Handle DELETE requests
 */
function handleDelete($productionStages) {
    if (!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Missing required parameter: id'
        ]);
        return;
    }
    
    $result = $productionStages->delete($_GET['id']);
    echo json_encode($result);
}

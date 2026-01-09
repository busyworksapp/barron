<?php
/**
 * Barron Production Management System
 * Production Stages Assignment API
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/ProductionStages.php';
require_once __DIR__ . '/../../includes/auth_check.php';

header('Content-Type: application/json');

// Initialize
$stages = new ProductionStages();
$response = ['success' => false];

try {
    // Check authentication
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    // GET - Get stages for department
    if ($method === 'GET') {
        // Check view permission
        if (!checkPermission('planning.view')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            exit;
        }
        
        if (empty($_GET['department_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Department ID required']);
            exit;
        }
        
        $department_stages = $stages->getByDepartment($_GET['department_id']);
        
        $response = [
            'success' => true,
            'stages' => $department_stages,
            'count' => count($department_stages)
        ];
    }
    
    // POST - Assign stage to job
    elseif ($method === 'POST') {
        // Check edit permission
        if (!checkPermission('planning.edit')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['job_id']) || empty($input['stage_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Job ID and Stage ID required']);
            exit;
        }
        
        // Update job with stage assignment
        $database = new Database();
        $conn = $database->getConnection();
        
        $query = "UPDATE jobs 
                  SET stage_id = :stage_id,
                      updated_at = CURRENT_TIMESTAMP
                  WHERE id = :job_id";
        
        $stmt = $conn->prepare($query);
        $result = $stmt->execute([
            ':job_id' => $input['job_id'],
            ':stage_id' => $input['stage_id']
        ]);
        
        if ($result) {
            // Log activity
            logActivity('update', 'jobs', $input['job_id'], ['stage_id' => $input['stage_id']], null);
            
            $response = [
                'success' => true,
                'message' => 'Stage assigned to job successfully'
            ];
        } else {
            http_response_code(500);
            $response = ['success' => false, 'message' => 'Failed to assign stage'];
        }
    }
    
    // PUT - Update job stage
    elseif ($method === 'PUT') {
        // Check edit permission
        if (!checkPermission('planning.edit')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['job_id']) || empty($input['stage_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Job ID and Stage ID required']);
            exit;
        }
        
        // Update job stage
        $database = new Database();
        $conn = $database->getConnection();
        
        $query = "UPDATE jobs 
                  SET stage_id = :stage_id,
                      updated_at = CURRENT_TIMESTAMP
                  WHERE id = :job_id";
        
        $stmt = $conn->prepare($query);
        $result = $stmt->execute([
            ':job_id' => $input['job_id'],
            ':stage_id' => $input['stage_id']
        ]);
        
        if ($result) {
            // Log activity
            logActivity('update', 'jobs', $input['job_id'], ['stage_id' => $input['stage_id']], null);
            
            $response = [
                'success' => true,
                'message' => 'Job stage updated successfully'
            ];
        } else {
            http_response_code(500);
            $response = ['success' => false, 'message' => 'Failed to update stage'];
        }
    }
    
    else {
        http_response_code(405);
        $response = ['success' => false, 'message' => 'Method not allowed'];
    }
    
} catch (Exception $e) {
    http_response_code(500);
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
}

echo json_encode($response);

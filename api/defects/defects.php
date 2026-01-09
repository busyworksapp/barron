<?php
/**
 * Barron Production Management System
 * Defects API Endpoint
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/Defects.php';
require_once __DIR__ . '/../../includes/auth_check.php';

header('Content-Type: application/json');

// Initialize
$defects = new Defects();
$response = ['success' => false];

try {
    // Check authentication
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    // GET - List defects or get single defect
    if ($method === 'GET') {
        // Check view permission
        if (!checkPermission('defects.view')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            exit;
        }
        
        $action = $_GET['action'] ?? 'list';
        
        switch ($action) {
            case 'list':
                if (isset($_GET['id'])) {
                    // Get single defect
                    $defect = $defects->getDefectDetails($_GET['id']);
                    
                    if ($defect) {
                        $response = [
                            'success' => true,
                            'defect' => $defect
                        ];
                    } else {
                        http_response_code(404);
                        $response = ['success' => false, 'message' => 'Defect not found'];
                    }
                } else {
                    // Get defects list
                    $filters = [
                        'search' => $_GET['search'] ?? null,
                        'job_id' => $_GET['job_id'] ?? null,
                        'order_id' => $_GET['order_id'] ?? null,
                        'status' => $_GET['status'] ?? null,
                        'severity' => $_GET['severity'] ?? null,
                        'date_from' => $_GET['date_from'] ?? null,
                        'date_to' => $_GET['date_to'] ?? null,
                        'page' => isset($_GET['page']) ? (int)$_GET['page'] : 1,
                        'per_page' => isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20
                    ];
                    
                    $result = $defects->getDefects($filters);
                    
                    $response = [
                        'success' => true,
                        'defects' => $result['data'],
                        'pagination' => $result['pagination']
                    ];
                }
                break;
            
            case 'statistics':
                // Get defect statistics
                $date_from = $_GET['date_from'] ?? null;
                $date_to = $_GET['date_to'] ?? null;
                
                $stats = $defects->getStatistics($date_from, $date_to);
                
                $response = [
                    'success' => true,
                    'statistics' => $stats
                ];
                break;
            
            case 'by_department':
                // Get defects grouped by department
                $date_from = $_GET['date_from'] ?? null;
                $date_to = $_GET['date_to'] ?? null;
                
                $dept_data = $defects->getByDepartment($date_from, $date_to);
                
                $response = [
                    'success' => true,
                    'departments' => $dept_data
                ];
                break;
            
            case 'trend':
                // Get defect trend
                $period = $_GET['period'] ?? 'week';
                $trend = $defects->getTrend($period);
                
                $response = [
                    'success' => true,
                    'trend' => $trend
                ];
                break;
            
            default:
                http_response_code(400);
                $response = ['success' => false, 'message' => 'Invalid action'];
        }
    }
    
    // POST - Create new defect or update status
    elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? 'create';
        
        if ($action === 'update_status') {
            // Check edit permission
            if (!checkPermission('defects.edit')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Permission denied']);
                exit;
            }
            
            if (empty($input['id']) || empty($input['status'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Defect ID and status required']);
                exit;
            }
            
            $result = $defects->updateStatus($input['id'], $input['status'], $input['notes'] ?? null);
            
            if ($result) {
                $response = [
                    'success' => true,
                    'message' => 'Defect status updated successfully'
                ];
            } else {
                http_response_code(500);
                $response = ['success' => false, 'message' => 'Failed to update status'];
            }
        } else {
            // Create defect
            // Check create permission
            if (!checkPermission('defects.create')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Permission denied']);
                exit;
            }
            
            // Validate required fields
            if (empty($input['job_id']) || empty($input['quantity']) || empty($input['description'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Missing required fields']);
                exit;
            }
            
            $defect_id = $defects->create($input);
            
            if ($defect_id) {
                $response = [
                    'success' => true,
                    'message' => 'Defect reported successfully',
                    'defect_id' => $defect_id
                ];
            } else {
                http_response_code(500);
                $response = ['success' => false, 'message' => 'Failed to report defect'];
            }
        }
    }
    
    // PUT - Update defect
    elseif ($method === 'PUT') {
        // Check edit permission
        if (!checkPermission('defects.edit')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Defect ID required']);
            exit;
        }
        
        $result = $defects->update($input['id'], $input);
        
        if ($result) {
            $response = [
                'success' => true,
                'message' => 'Defect updated successfully'
            ];
        } else {
            http_response_code(500);
            $response = ['success' => false, 'message' => 'Failed to update defect'];
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

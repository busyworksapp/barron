<?php
/**
 * Barron Production Management System
 * Capacity Planning API Endpoint
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/CapacityPlanner.php';
require_once __DIR__ . '/../../includes/auth_check.php';

header('Content-Type: application/json');

// Initialize
$capacity = new CapacityPlanner();
$response = ['success' => false];

try {
    // Check authentication
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    // Check view permission
    if (!checkPermission('planning.view')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        exit;
    }
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    // GET - Get capacity information
    if ($method === 'GET') {
        $action = $_GET['action'] ?? 'overview';
        
        switch ($action) {
            case 'overview':
                // Get all departments capacity overview
                $date_from = $_GET['date_from'] ?? date('Y-m-d');
                $date_to = $_GET['date_to'] ?? date('Y-m-d', strtotime('+7 days'));
                
                $departments = $capacity->getAllDepartmentsCapacity($date_from, $date_to);
                
                $response = [
                    'success' => true,
                    'departments' => $departments,
                    'date_range' => [
                        'from' => $date_from,
                        'to' => $date_to
                    ]
                ];
                break;
            
            case 'department':
                // Get specific department capacity details
                if (empty($_GET['department_id'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Department ID required']);
                    exit;
                }
                
                $date_from = $_GET['date_from'] ?? date('Y-m-d');
                $date_to = $_GET['date_to'] ?? date('Y-m-d', strtotime('+7 days'));
                
                $dept_capacity = $capacity->getDepartmentCapacity(
                    $_GET['department_id'],
                    $date_from,
                    $date_to
                );
                
                $response = [
                    'success' => true,
                    'capacity' => $dept_capacity
                ];
                break;
            
            case 'available_slots':
                // Find available scheduling slots
                if (empty($_GET['department_id'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Department ID required']);
                    exit;
                }
                
                $date_from = $_GET['date_from'] ?? date('Y-m-d');
                $date_to = $_GET['date_to'] ?? date('Y-m-d', strtotime('+14 days'));
                $required_capacity = isset($_GET['required_capacity']) ? (int)$_GET['required_capacity'] : 1;
                
                $slots = $capacity->findAvailableSlots(
                    $_GET['department_id'],
                    $required_capacity,
                    $date_from,
                    $date_to
                );
                
                $response = [
                    'success' => true,
                    'available_slots' => $slots,
                    'parameters' => [
                        'department_id' => $_GET['department_id'],
                        'required_capacity' => $required_capacity,
                        'date_from' => $date_from,
                        'date_to' => $date_to
                    ]
                ];
                break;
            
            case 'trends':
                // Get capacity trends
                if (empty($_GET['department_id'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Department ID required']);
                    exit;
                }
                
                $weeks = isset($_GET['weeks']) ? (int)$_GET['weeks'] : 4;
                $trends = $capacity->getCapacityTrends($_GET['department_id'], $weeks);
                
                $response = [
                    'success' => true,
                    'trends' => $trends
                ];
                break;
            
            default:
                http_response_code(400);
                $response = ['success' => false, 'message' => 'Invalid action'];
        }
    }
    
    // POST - Validate scheduling
    elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? 'validate';
        
        if ($action === 'validate') {
            // Validate if job can be scheduled
            if (empty($input['department_id']) || empty($input['start_date'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Department ID and start date required']);
                exit;
            }
            
            $quantity = isset($input['quantity']) ? (int)$input['quantity'] : 1;
            
            $validation = $capacity->validateScheduling(
                $input['department_id'],
                $input['start_date'],
                $quantity
            );
            
            $response = [
                'success' => true,
                'validation' => $validation
            ];
        } else {
            http_response_code(400);
            $response = ['success' => false, 'message' => 'Invalid action'];
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

<?php
/**
 * Barron Production Management System
 * Replacement Suggestions API
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
    
    // GET - Get replacement suggestions
    if ($method === 'GET') {
        // Validate required parameters
        if (empty($_GET['department_id']) || empty($_GET['date'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Department ID and date required']);
            exit;
        }
        
        $suggestions = $capacity->getReplacementSuggestions(
            $_GET['department_id'],
            $_GET['date']
        );
        
        $response = [
            'success' => true,
            'suggestions' => $suggestions,
            'parameters' => [
                'department_id' => $_GET['department_id'],
                'date' => $_GET['date']
            ],
            'message' => empty($suggestions) 
                ? 'No unscheduled orders available for this slot' 
                : count($suggestions) . ' replacement suggestions found'
        ];
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

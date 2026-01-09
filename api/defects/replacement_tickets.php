<?php
/**
 * Barron Production Management System
 * Replacement Tickets API Endpoint
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/ReplacementTicket.php';
require_once __DIR__ . '/../../includes/auth_check.php';

header('Content-Type: application/json');

// Initialize
$tickets = new ReplacementTicket();
$response = ['success' => false];

try {
    // Check authentication
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    // GET - List tickets or get single ticket
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
                    // Get single ticket
                    $ticket = $tickets->getTicketDetails($_GET['id']);
                    
                    if ($ticket) {
                        $response = [
                            'success' => true,
                            'ticket' => $ticket
                        ];
                    } else {
                        http_response_code(404);
                        $response = ['success' => false, 'message' => 'Ticket not found'];
                    }
                } else {
                    // Get tickets list
                    $filters = [
                        'status' => $_GET['status'] ?? null,
                        'urgency' => $_GET['urgency'] ?? null,
                        'order_id' => $_GET['order_id'] ?? null,
                        'date_from' => $_GET['date_from'] ?? null,
                        'date_to' => $_GET['date_to'] ?? null,
                        'page' => isset($_GET['page']) ? (int)$_GET['page'] : 1,
                        'per_page' => isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20
                    ];
                    
                    $result = $tickets->getTickets($filters);
                    
                    $response = [
                        'success' => true,
                        'tickets' => $result['data'],
                        'pagination' => $result['pagination']
                    ];
                }
                break;
            
            case 'statistics':
                // Get ticket statistics
                $date_from = $_GET['date_from'] ?? null;
                $date_to = $_GET['date_to'] ?? null;
                
                $stats = $tickets->getStatistics($date_from, $date_to);
                
                $response = [
                    'success' => true,
                    'statistics' => $stats
                ];
                break;
            
            default:
                http_response_code(400);
                $response = ['success' => false, 'message' => 'Invalid action'];
        }
    }
    
    // POST - Handle ticket actions (approve, reject, update status)
    elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        
        switch ($action) {
            case 'approve':
                // Manager approval action
                if (!checkPermission('defects.approve')) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Permission denied - Manager role required']);
                    exit;
                }
                
                if (empty($input['id'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Ticket ID required']);
                    exit;
                }
                
                $result = $tickets->approve($input['id'], $input['notes'] ?? null);
                
                if ($result) {
                    $response = [
                        'success' => true,
                        'message' => 'Replacement ticket approved successfully'
                    ];
                } else {
                    http_response_code(500);
                    $response = ['success' => false, 'message' => 'Failed to approve ticket'];
                }
                break;
            
            case 'reject':
                // Manager rejection action
                if (!checkPermission('defects.approve')) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Permission denied - Manager role required']);
                    exit;
                }
                
                if (empty($input['id']) || empty($input['reason'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Ticket ID and rejection reason required']);
                    exit;
                }
                
                $result = $tickets->reject($input['id'], $input['reason']);
                
                if ($result) {
                    $response = [
                        'success' => true,
                        'message' => 'Replacement ticket rejected'
                    ];
                } else {
                    http_response_code(500);
                    $response = ['success' => false, 'message' => 'Failed to reject ticket'];
                }
                break;
            
            case 'update_status':
                // Planning team status update
                if (!checkPermission('planning.edit')) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Permission denied - Planning access required']);
                    exit;
                }
                
                if (empty($input['id']) || empty($input['status'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Ticket ID and status required']);
                    exit;
                }
                
                $result = $tickets->updateReplacementStatus($input['id'], $input['status'], $input['notes'] ?? null);
                
                if ($result) {
                    $message = 'Replacement status updated successfully';
                    if ($input['status'] === 'no_stock') {
                        $message .= ' - Order has been automatically placed on hold';
                    }
                    
                    $response = [
                        'success' => true,
                        'message' => $message
                    ];
                } else {
                    http_response_code(500);
                    $response = ['success' => false, 'message' => 'Failed to update status'];
                }
                break;
            
            default:
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

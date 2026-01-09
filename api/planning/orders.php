<?php
/**
 * Barron Production Management System
 * Orders API Endpoint
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/Planning.php';
require_once __DIR__ . '/../../includes/auth_check.php';

header('Content-Type: application/json');

// Initialize
$planning = new Planning();
$response = ['success' => false];

try {
    // Check authentication
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    // GET - List orders or get single order details
    if ($method === 'GET') {
        // Check view permission
        if (!checkPermission('planning.view')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            exit;
        }
        
        if (isset($_GET['id'])) {
            // Get single order details
            $order = $planning->getOrderDetails($_GET['id']);
            
            if ($order) {
                $response = [
                    'success' => true,
                    'order' => $order
                ];
            } else {
                http_response_code(404);
                $response = ['success' => false, 'message' => 'Order not found'];
            }
        } else {
            // Get orders list with filters
            $filters = [
                'search' => $_GET['search'] ?? null,
                'status' => $_GET['status'] ?? null,
                'priority' => $_GET['priority'] ?? null,
                'date_from' => $_GET['date_from'] ?? null,
                'date_to' => $_GET['date_to'] ?? null,
                'page' => isset($_GET['page']) ? (int)$_GET['page'] : 1,
                'per_page' => isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20
            ];
            
            $orders = $planning->getOrders($filters);
            
            $response = [
                'success' => true,
                'orders' => $orders['data'],
                'pagination' => $orders['pagination']
            ];
        }
    }
    
    // POST - Create new order
    elseif ($method === 'POST') {
        // Check create permission
        if (!checkPermission('planning.create')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        if (empty($input['order_number']) || empty($input['customer_name']) || empty($input['due_date'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit;
        }
        
        // Validate items
        if (empty($input['items']) || !is_array($input['items'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Order must have at least one item']);
            exit;
        }
        
        $order_id = $planning->createOrder($input);
        
        if ($order_id) {
            $response = [
                'success' => true,
                'message' => 'Order created successfully',
                'order_id' => $order_id
            ];
        } else {
            http_response_code(500);
            $response = ['success' => false, 'message' => 'Failed to create order'];
        }
    }
    
    // PUT - Update order
    elseif ($method === 'PUT') {
        // Check edit permission
        if (!checkPermission('planning.edit')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Order ID required']);
            exit;
        }
        
        $result = $planning->updateOrder($input['id'], $input);
        
        if ($result) {
            $response = [
                'success' => true,
                'message' => 'Order updated successfully'
            ];
        } else {
            http_response_code(500);
            $response = ['success' => false, 'message' => 'Failed to update order'];
        }
    }
    
    // DELETE - Delete order
    elseif ($method === 'DELETE') {
        // Check delete permission
        if (!checkPermission('planning.delete')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Order ID required']);
            exit;
        }
        
        // Check if order has jobs (prevent deletion if jobs exist)
        $order = $planning->getOrderDetails($input['id']);
        if ($order && !empty($order['jobs'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Cannot delete order with existing jobs']);
            exit;
        }
        
        // Soft delete by updating status to cancelled
        $result = $planning->updateOrder($input['id'], ['status' => 'cancelled']);
        
        if ($result) {
            $response = [
                'success' => true,
                'message' => 'Order cancelled successfully'
            ];
        } else {
            http_response_code(500);
            $response = ['success' => false, 'message' => 'Failed to cancel order'];
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

<?php
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isAuthenticated()) {
    echo jsonResponse(false, 'Unauthorized');
    exit;
}

if (!hasPermission('planning.delete')) {
    echo jsonResponse(false, 'Insufficient permissions');
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['order_id'])) {
    echo jsonResponse(false, 'Order ID is required');
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Get order details before deleting
    $stmt = $db->prepare("SELECT order_number, customer_name FROM orders WHERE id = ?");
    $stmt->execute([$data['order_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo jsonResponse(false, 'Order not found');
        exit;
    }
    
    // Check for relationships
    $stmt = $db->prepare("SELECT COUNT(*) FROM job_schedules WHERE order_id = ?");
    $stmt->execute([$data['order_id']]);
    $jobCount = $stmt->fetchColumn();
    
    if ($jobCount > 0) {
        echo jsonResponse(false, "Cannot delete order. Associated with {$jobCount} job schedule(s). Consider marking as cancelled instead.");
        exit;
    }
    
    $db->beginTransaction();
    
    // Delete order items
    $stmt = $db->prepare("DELETE FROM order_items WHERE order_id = ?");
    $stmt->execute([$data['order_id']]);
    
    // Delete order
    $stmt = $db->prepare("DELETE FROM orders WHERE id = ?");
    $stmt->execute([$data['order_id']]);
    
    // Log activity
    logActivity('order_deleted', 'orders', $data['order_id'], 
        "Deleted order: {$order['order_number']} - {$order['customer_name']}");
    
    $db->commit();
    
    echo jsonResponse(true, 'Order deleted successfully');
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Error in orders/delete.php: ' . $e->getMessage());
    echo jsonResponse(false, 'Error deleting order: ' . $e->getMessage());
}

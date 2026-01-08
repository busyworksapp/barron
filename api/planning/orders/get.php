<?php
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isAuthenticated()) {
    echo jsonResponse(false, 'Unauthorized');
    exit;
}

if (!hasPermission('planning.view')) {
    echo jsonResponse(false, 'Insufficient permissions');
    exit;
}

if (!isset($_GET['id'])) {
    echo jsonResponse(false, 'Order ID is required');
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Get order details
    $stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo jsonResponse(false, 'Order not found');
        exit;
    }
    
    // Get order items with product details
    $stmt = $db->prepare("
        SELECT oi.*, p.product_code, p.product_name, p.category
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
        ORDER BY oi.id
    ");
    $stmt->execute([$_GET['id']]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $order['items'] = $items;
    
    echo jsonResponse(true, 'Order retrieved successfully', $order);
    
} catch (Exception $e) {
    error_log('Error in orders/get.php: ' . $e->getMessage());
    echo jsonResponse(false, 'Error retrieving order: ' . $e->getMessage());
}

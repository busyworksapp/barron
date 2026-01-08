<?php
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isAuthenticated()) {
    echo jsonResponse(false, 'Unauthorized');
    exit;
}

if (!hasPermission('planning.edit')) {
    echo jsonResponse(false, 'Insufficient permissions');
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$required = ['order_number', 'customer_name', 'order_date', 'due_date', 'status'];
foreach ($required as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        echo jsonResponse(false, ucfirst(str_replace('_', ' ', $field)) . ' is required');
        exit;
    }
}

// Validate items
if (!isset($data['items']) || !is_array($data['items']) || empty($data['items'])) {
    echo jsonResponse(false, 'Order must have at least one item');
    exit;
}

// Validate status
$validStatuses = ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled'];
if (!in_array($data['status'], $validStatuses)) {
    echo jsonResponse(false, 'Invalid status');
    exit;
}

// Validate priority
$validPriorities = ['normal', 'high', 'urgent'];
$priority = $data['priority'] ?? 'normal';
if (!in_array($priority, $validPriorities)) {
    echo jsonResponse(false, 'Invalid priority');
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Check for duplicate order number
    $stmt = $db->prepare("SELECT id FROM orders WHERE order_number = ?");
    $stmt->execute([$data['order_number']]);
    if ($stmt->fetch()) {
        echo jsonResponse(false, 'Order number already exists');
        exit;
    }
    
    // Validate due date is after order date
    if (strtotime($data['due_date']) < strtotime($data['order_date'])) {
        echo jsonResponse(false, 'Due date must be after order date');
        exit;
    }
    
    $db->beginTransaction();
    
    // Insert order
    $stmt = $db->prepare("
        INSERT INTO orders (
            order_number, customer_name, customer_ref, po_number,
            order_date, due_date, status, priority, notes, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $data['order_number'],
        $data['customer_name'],
        $data['customer_ref'] ?? null,
        $data['po_number'] ?? null,
        $data['order_date'],
        $data['due_date'],
        $data['status'],
        $priority,
        $data['notes'] ?? null,
        getCurrentUser()['id']
    ]);
    
    $orderId = $db->lastInsertId();
    
    // Insert order items
    $stmt = $db->prepare("
        INSERT INTO order_items (
            order_id, product_id, quantity, unit_price, notes
        ) VALUES (?, ?, ?, ?, ?)
    ");
    
    foreach ($data['items'] as $item) {
        if (!isset($item['product_id']) || !isset($item['quantity'])) {
            $db->rollBack();
            echo jsonResponse(false, 'Invalid item data');
            exit;
        }
        
        // Verify product exists
        $checkStmt = $db->prepare("SELECT id FROM products WHERE id = ?");
        $checkStmt->execute([$item['product_id']]);
        if (!$checkStmt->fetch()) {
            $db->rollBack();
            echo jsonResponse(false, 'Product not found: ID ' . $item['product_id']);
            exit;
        }
        
        $stmt->execute([
            $orderId,
            $item['product_id'],
            $item['quantity'],
            $item['unit_price'] ?? null,
            $item['notes'] ?? null
        ]);
    }
    
    // Log activity
    logActivity('order_created', 'orders', $orderId, 
        "Created order: {$data['order_number']} for {$data['customer_name']}");
    
    $db->commit();
    
    echo jsonResponse(true, 'Order created successfully', ['id' => $orderId]);
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Error in orders/create.php: ' . $e->getMessage());
    echo jsonResponse(false, 'Error creating order: ' . $e->getMessage());
}

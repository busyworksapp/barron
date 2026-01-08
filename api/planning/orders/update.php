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
$required = ['order_id', 'order_number', 'customer_name', 'order_date', 'due_date', 'status'];
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
    
    // Check if order exists
    $stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$data['order_id']]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existing) {
        echo jsonResponse(false, 'Order not found');
        exit;
    }
    
    // Check for duplicate order number (excluding current order)
    $stmt = $db->prepare("SELECT id FROM orders WHERE order_number = ? AND id != ?");
    $stmt->execute([$data['order_number'], $data['order_id']]);
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
    
    // Update order
    $stmt = $db->prepare("
        UPDATE orders SET 
            order_number = ?, 
            customer_name = ?, 
            customer_ref = ?, 
            po_number = ?,
            order_date = ?, 
            due_date = ?, 
            status = ?, 
            priority = ?, 
            notes = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
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
        $data['order_id']
    ]);
    
    // Delete existing order items
    $stmt = $db->prepare("DELETE FROM order_items WHERE order_id = ?");
    $stmt->execute([$data['order_id']]);
    
    // Insert new order items
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
            $data['order_id'],
            $item['product_id'],
            $item['quantity'],
            $item['unit_price'] ?? null,
            $item['notes'] ?? null
        ]);
    }
    
    // Build change log
    $changes = [];
    foreach ($existing as $key => $value) {
        if (isset($data[$key]) && $data[$key] != $value && $key != 'updated_at') {
            $changes[] = "$key: '$value' → '{$data[$key]}'";
        }
    }
    
    // Log activity
    if (!empty($changes)) {
        logActivity('order_updated', 'orders', $data['order_id'], 
            "Updated order: {$data['order_number']} - " . implode(', ', $changes));
    }
    
    $db->commit();
    
    echo jsonResponse(true, 'Order updated successfully');
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Error in orders/update.php: ' . $e->getMessage());
    echo jsonResponse(false, 'Error updating order: ' . $e->getMessage());
}

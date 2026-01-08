<?php
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';

header('Content-Type: application/json');

$auth = new Auth();
$auth->requireLogin();

if (!hasPermission('defects.edit')) {
    echo jsonResponse(false, 'Permission denied');
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $required = ['rma_number', 'order_id', 'product_id', 'quantity_returned', 
                 'return_reason', 'customer_complaint', 'return_date', 'status'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            echo jsonResponse(false, ucfirst(str_replace('_', ' ', $field)) . ' is required');
            exit;
        }
    }
    
    // Validate return reason
    $validReasons = ['defective', 'wrong_item', 'damaged_shipping', 'not_as_described',
                    'quality_issue', 'customer_error', 'late_delivery', 'other'];
    if (!in_array($data['return_reason'], $validReasons)) {
        echo jsonResponse(false, 'Invalid return reason');
        exit;
    }
    
    // Validate status
    $validStatuses = ['received', 'investigating', 'approved', 'rejected', 'resolved'];
    if (!in_array($data['status'], $validStatuses)) {
        echo jsonResponse(false, 'Invalid status');
        exit;
    }
    
    // Validate resolution type if provided
    if (!empty($data['resolution_type'])) {
        $validResolutions = ['refund', 'replacement', 'credit', 'repair', 'no_action'];
        if (!in_array($data['resolution_type'], $validResolutions)) {
            echo jsonResponse(false, 'Invalid resolution type');
            exit;
        }
    }
    
    // Check for duplicate RMA number
    $stmt = $pdo->prepare("SELECT id FROM customer_returns WHERE rma_number = :rma_number");
    $stmt->execute([':rma_number' => $data['rma_number']]);
    if ($stmt->fetch()) {
        echo jsonResponse(false, 'RMA number already exists');
        exit;
    }
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // Verify order exists
    $stmt = $pdo->prepare("SELECT id, order_number, customer_name FROM orders WHERE id = :id");
    $stmt->execute([':id' => $data['order_id']]);
    $order = $stmt->fetch();
    
    if (!$order) {
        $pdo->rollBack();
        echo jsonResponse(false, 'Order not found');
        exit;
    }
    
    // Verify product exists
    $stmt = $pdo->prepare("SELECT id, product_code, product_name FROM products WHERE id = :id");
    $stmt->execute([':id' => $data['product_id']]);
    $product = $stmt->fetch();
    
    if (!$product) {
        $pdo->rollBack();
        echo jsonResponse(false, 'Product not found');
        exit;
    }
    
    // Insert return
    $stmt = $pdo->prepare("INSERT INTO customer_returns 
                          (rma_number, order_id, product_id, quantity_returned, return_reason,
                           customer_complaint, investigation_notes, return_date, status,
                           resolution_type, resolution_notes, refund_amount, restocking_fee, created_by) 
                          VALUES 
                          (:rma_number, :order_id, :product_id, :quantity_returned, :return_reason,
                           :customer_complaint, :investigation_notes, :return_date, :status,
                           :resolution_type, :resolution_notes, :refund_amount, :restocking_fee, :created_by)");
    
    $stmt->execute([
        ':rma_number' => $data['rma_number'],
        ':order_id' => $data['order_id'],
        ':product_id' => $data['product_id'],
        ':quantity_returned' => $data['quantity_returned'],
        ':return_reason' => $data['return_reason'],
        ':customer_complaint' => $data['customer_complaint'],
        ':investigation_notes' => $data['investigation_notes'] ?? null,
        ':return_date' => $data['return_date'],
        ':status' => $data['status'],
        ':resolution_type' => $data['resolution_type'] ?? null,
        ':resolution_notes' => $data['resolution_notes'] ?? null,
        ':refund_amount' => $data['refund_amount'] ?? null,
        ':restocking_fee' => $data['restocking_fee'] ?? null,
        ':created_by' => getCurrentUser()['id']
    ]);
    
    $returnId = $pdo->lastInsertId();
    
    // Set resolved_by and resolution_date if status is resolved
    if ($data['status'] === 'resolved') {
        $stmt = $pdo->prepare("UPDATE customer_returns 
                              SET resolved_by = :resolved_by, resolution_date = NOW()
                              WHERE id = :id");
        $stmt->execute([
            ':resolved_by' => getCurrentUser()['id'],
            ':id' => $returnId
        ]);
    }
    
    // Log activity
    logActivity(
        'return_created',
        'customer_returns',
        $returnId,
        "Created customer return {$data['rma_number']} for order {$order['order_number']}: {$product['product_code']} - {$data['quantity_returned']} units"
    );
    
    $pdo->commit();
    
    echo jsonResponse(true, 'Return logged successfully', ['return_id' => $returnId]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error in returns/create.php: " . $e->getMessage());
    echo jsonResponse(false, 'Error creating return: ' . $e->getMessage());
}

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
    
    // Validate return_id
    if (empty($data['return_id'])) {
        echo jsonResponse(false, 'Return ID is required');
        exit;
    }
    
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
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // Get existing return
    $stmt = $pdo->prepare("SELECT * FROM customer_returns WHERE id = :id");
    $stmt->execute([':id' => $data['return_id']]);
    $existingReturn = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existingReturn) {
        $pdo->rollBack();
        echo jsonResponse(false, 'Return not found');
        exit;
    }
    
    // Check for duplicate RMA number (excluding current)
    $stmt = $pdo->prepare("SELECT id FROM customer_returns 
                          WHERE rma_number = :rma_number AND id != :return_id");
    $stmt->execute([
        ':rma_number' => $data['rma_number'],
        ':return_id' => $data['return_id']
    ]);
    if ($stmt->fetch()) {
        $pdo->rollBack();
        echo jsonResponse(false, 'RMA number already exists');
        exit;
    }
    
    // Update return
    $stmt = $pdo->prepare("UPDATE customer_returns SET
                          rma_number = :rma_number,
                          order_id = :order_id,
                          product_id = :product_id,
                          quantity_returned = :quantity_returned,
                          return_reason = :return_reason,
                          customer_complaint = :customer_complaint,
                          investigation_notes = :investigation_notes,
                          return_date = :return_date,
                          status = :status,
                          resolution_type = :resolution_type,
                          resolution_notes = :resolution_notes,
                          refund_amount = :refund_amount,
                          restocking_fee = :restocking_fee
                          WHERE id = :return_id");
    
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
        ':return_id' => $data['return_id']
    ]);
    
    // Set resolved_by and resolution_date if status changed to resolved
    if ($data['status'] === 'resolved' && $existingReturn['status'] !== 'resolved') {
        $stmt = $pdo->prepare("UPDATE customer_returns 
                              SET resolved_by = :resolved_by, resolution_date = NOW()
                              WHERE id = :id");
        $stmt->execute([
            ':resolved_by' => getCurrentUser()['id'],
            ':id' => $data['return_id']
        ]);
    }
    
    // Log activity
    logActivity(
        'return_updated',
        'customer_returns',
        $data['return_id'],
        "Updated customer return {$data['rma_number']}"
    );
    
    $pdo->commit();
    
    echo jsonResponse(true, 'Return updated successfully');
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error in returns/update.php: " . $e->getMessage());
    echo jsonResponse(false, 'Error updating return: ' . $e->getMessage());
}

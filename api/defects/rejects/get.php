<?php
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';

header('Content-Type: application/json');

$auth = new Auth();
$auth->requireLogin();

if (!hasPermission('defects.view')) {
    echo jsonResponse(false, 'Permission denied');
    exit;
}

try {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        echo jsonResponse(false, 'Reject ID is required');
        exit;
    }
    
    // Get reject details
    $stmt = $pdo->prepare("SELECT 
                            ir.*,
                            js.job_number,
                            js.job_id,
                            o.order_number,
                            p.product_code,
                            p.product_name,
                            d.department_name,
                            CONCAT(u1.first_name, ' ', u1.last_name) as reported_by_name,
                            CONCAT(u2.first_name, ' ', u2.last_name) as approved_by_name
                          FROM internal_rejects ir
                          INNER JOIN job_schedules js ON ir.job_id = js.id
                          INNER JOIN orders o ON js.order_id = o.id
                          INNER JOIN order_items oi ON js.order_item_id = oi.id
                          INNER JOIN products p ON oi.product_id = p.id
                          INNER JOIN departments d ON js.department_id = d.id
                          INNER JOIN users u1 ON ir.reported_by = u1.id
                          LEFT JOIN users u2 ON ir.approved_by = u2.id
                          WHERE ir.id = :id");
    $stmt->execute([':id' => $id]);
    $reject = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reject) {
        echo jsonResponse(false, 'Reject not found');
        exit;
    }
    
    echo jsonResponse(true, 'Reject retrieved successfully', $reject);
    
} catch (Exception $e) {
    error_log("Error in rejects/get.php: " . $e->getMessage());
    echo jsonResponse(false, 'Error retrieving reject: ' . $e->getMessage());
}

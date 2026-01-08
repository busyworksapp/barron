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
        echo jsonResponse(false, 'Return ID is required');
        exit;
    }
    
    // Get return details
    $stmt = $pdo->prepare("SELECT 
                            cr.*,
                            o.order_number,
                            o.customer_name,
                            o.order_date,
                            p.product_code,
                            p.product_name,
                            CONCAT(u1.first_name, ' ', u1.last_name) as created_by_name,
                            CONCAT(u2.first_name, ' ', u2.last_name) as resolved_by_name
                          FROM customer_returns cr
                          INNER JOIN orders o ON cr.order_id = o.id
                          INNER JOIN products p ON cr.product_id = p.id
                          INNER JOIN users u1 ON cr.created_by = u1.id
                          LEFT JOIN users u2 ON cr.resolved_by = u2.id
                          WHERE cr.id = :id");
    $stmt->execute([':id' => $id]);
    $return = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$return) {
        echo jsonResponse(false, 'Return not found');
        exit;
    }
    
    echo jsonResponse(true, 'Return retrieved successfully', $return);
    
} catch (Exception $e) {
    error_log("Error in returns/get.php: " . $e->getMessage());
    echo jsonResponse(false, 'Error retrieving return: ' . $e->getMessage());
}

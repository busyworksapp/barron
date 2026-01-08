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
    // Get filter parameters
    $search = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? '';
    $resolution_type = $_GET['resolution_type'] ?? '';
    $date_from = $_GET['date_from'] ?? '';
    
    // Build query
    $query = "SELECT 
                cr.id,
                cr.rma_number,
                cr.quantity_returned,
                cr.return_reason,
                cr.customer_complaint,
                cr.investigation_notes,
                cr.return_date,
                cr.status,
                cr.resolution_type,
                cr.resolution_notes,
                cr.refund_amount,
                cr.restocking_fee,
                cr.created_at,
                o.order_number,
                o.customer_name,
                p.product_code,
                p.product_name,
                CONCAT(u1.first_name, ' ', u1.last_name) as created_by_name,
                CONCAT(u2.first_name, ' ', u2.last_name) as resolved_by_name,
                cr.resolution_date
              FROM customer_returns cr
              INNER JOIN orders o ON cr.order_id = o.id
              INNER JOIN products p ON cr.product_id = p.id
              INNER JOIN users u1 ON cr.created_by = u1.id
              LEFT JOIN users u2 ON cr.resolved_by = u2.id
              WHERE 1=1";
    
    $params = [];
    
    // Apply search filter
    if (!empty($search)) {
        $query .= " AND (cr.rma_number LIKE :search 
                    OR o.order_number LIKE :search 
                    OR o.customer_name LIKE :search 
                    OR p.product_code LIKE :search 
                    OR p.product_name LIKE :search
                    OR cr.return_reason LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    // Apply status filter
    if (!empty($status)) {
        $query .= " AND cr.status = :status";
        $params[':status'] = $status;
    }
    
    // Apply resolution type filter
    if (!empty($resolution_type)) {
        $query .= " AND cr.resolution_type = :resolution_type";
        $params[':resolution_type'] = $resolution_type;
    }
    
    // Apply date filter
    if (!empty($date_from)) {
        $query .= " AND cr.return_date >= :date_from";
        $params[':date_from'] = $date_from;
    }
    
    $query .= " ORDER BY cr.return_date DESC, cr.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $returns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo jsonResponse(true, 'Returns retrieved successfully', $returns);
    
} catch (Exception $e) {
    error_log("Error in returns/list.php: " . $e->getMessage());
    echo jsonResponse(false, 'Error retrieving returns: ' . $e->getMessage());
}

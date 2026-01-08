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
    $department_id = $_GET['department_id'] ?? '';
    $date_from = $_GET['date_from'] ?? '';
    
    // Build query
    $query = "SELECT 
                ir.id,
                ir.reject_number,
                ir.quantity_rejected,
                ir.defect_type,
                ir.defect_description,
                ir.root_cause,
                ir.disposition,
                ir.reject_date,
                ir.status,
                ir.created_at,
                js.job_number,
                p.product_code,
                p.product_name,
                d.department_name,
                CONCAT(u1.first_name, ' ', u1.last_name) as reported_by_name,
                CONCAT(u2.first_name, ' ', u2.last_name) as approved_by_name,
                ir.approval_date
              FROM internal_rejects ir
              INNER JOIN job_schedules js ON ir.job_id = js.id
              INNER JOIN order_items oi ON js.order_item_id = oi.id
              INNER JOIN products p ON oi.product_id = p.id
              INNER JOIN departments d ON js.department_id = d.id
              INNER JOIN users u1 ON ir.reported_by = u1.id
              LEFT JOIN users u2 ON ir.approved_by = u2.id
              WHERE 1=1";
    
    $params = [];
    
    // Apply search filter
    if (!empty($search)) {
        $query .= " AND (ir.reject_number LIKE :search 
                    OR js.job_number LIKE :search 
                    OR p.product_code LIKE :search 
                    OR p.product_name LIKE :search
                    OR ir.defect_type LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    // Apply status filter
    if (!empty($status)) {
        $query .= " AND ir.status = :status";
        $params[':status'] = $status;
    }
    
    // Apply department filter
    if (!empty($department_id)) {
        $query .= " AND js.department_id = :department_id";
        $params[':department_id'] = $department_id;
    }
    
    // Apply date filter
    if (!empty($date_from)) {
        $query .= " AND ir.reject_date >= :date_from";
        $params[':date_from'] = $date_from;
    }
    
    $query .= " ORDER BY ir.reject_date DESC, ir.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $rejects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo jsonResponse(true, 'Rejects retrieved successfully', $rejects);
    
} catch (Exception $e) {
    error_log("Error in rejects/list.php: " . $e->getMessage());
    echo jsonResponse(false, 'Error retrieving rejects: ' . $e->getMessage());
}

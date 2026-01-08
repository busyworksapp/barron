<?php
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';

header('Content-Type: application/json');

$auth = new Auth();
$auth->requireLogin();

if (!hasPermission('planning.view')) {
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
                j.id,
                j.job_number,
                j.quantity,
                j.scheduled_start,
                j.scheduled_end,
                j.actual_start,
                j.actual_end,
                j.status,
                j.priority,
                j.job_notes,
                j.created_at,
                o.order_number,
                o.customer_name,
                p.product_code,
                p.product_name,
                d.department_name,
                ps.stage_name,
                m.machine_name,
                m.machine_code,
                CONCAT(e.first_name, ' ', e.last_name) as assigned_to_name,
                CONCAT(c.first_name, ' ', c.last_name) as created_by_name
              FROM job_schedules j
              INNER JOIN orders o ON j.order_id = o.id
              INNER JOIN order_items oi ON j.order_item_id = oi.id
              INNER JOIN products p ON oi.product_id = p.id
              INNER JOIN departments d ON j.department_id = d.id
              LEFT JOIN production_stages ps ON j.production_stage_id = ps.id
              LEFT JOIN machines m ON j.machine_id = m.id
              LEFT JOIN employees e ON j.assigned_to = e.id
              LEFT JOIN users c ON j.created_by = c.id
              WHERE 1=1";
    
    $params = [];
    
    // Apply search filter
    if (!empty($search)) {
        $query .= " AND (j.job_number LIKE :search 
                    OR o.order_number LIKE :search 
                    OR o.customer_name LIKE :search 
                    OR p.product_code LIKE :search 
                    OR p.product_name LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    // Apply status filter
    if (!empty($status)) {
        $query .= " AND j.status = :status";
        $params[':status'] = $status;
    }
    
    // Apply department filter
    if (!empty($department_id)) {
        $query .= " AND j.department_id = :department_id";
        $params[':department_id'] = $department_id;
    }
    
    // Apply date filter
    if (!empty($date_from)) {
        $query .= " AND j.scheduled_start >= :date_from";
        $params[':date_from'] = $date_from;
    }
    
    $query .= " ORDER BY j.scheduled_start DESC, j.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo jsonResponse(true, 'Jobs retrieved successfully', $jobs);
    
} catch (Exception $e) {
    error_log("Error in jobs/list.php: " . $e->getMessage());
    echo jsonResponse(false, 'Error retrieving jobs: ' . $e->getMessage());
}

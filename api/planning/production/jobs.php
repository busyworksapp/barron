<?php
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';

header('Content-Type: application/json');

$auth = new Auth();
$auth->requireLogin();

if (!hasPermission('production.view')) {
    echo jsonResponse(false, 'Permission denied');
    exit;
}

try {
    // Get filter parameters
    $search = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? '';
    $department_id = $_GET['department_id'] ?? '';
    $assigned_to = $_GET['assigned_to'] ?? '';
    $unassigned = $_GET['unassigned'] ?? '';
    
    // Build query with production totals
    $query = "SELECT 
                j.id,
                j.job_number,
                j.quantity as total_quantity,
                j.scheduled_start,
                j.scheduled_end,
                j.actual_start,
                j.actual_end,
                j.status,
                j.priority,
                o.order_number,
                p.product_code,
                p.product_name,
                d.department_name,
                CONCAT(e.first_name, ' ', e.last_name) as assigned_to_name,
                COALESCE(SUM(jpl.quantity_produced), 0) as produced_quantity,
                COALESCE(SUM(jpl.quantity_rejected), 0) as rejected_quantity
              FROM job_schedules j
              INNER JOIN orders o ON j.order_id = o.id
              INNER JOIN order_items oi ON j.order_item_id = oi.id
              INNER JOIN products p ON oi.product_id = p.id
              INNER JOIN departments d ON j.department_id = d.id
              LEFT JOIN employees e ON j.assigned_to = e.id
              LEFT JOIN job_production_log jpl ON j.id = jpl.job_id
              WHERE 1=1";
    
    $params = [];
    
    // Apply search filter
    if (!empty($search)) {
        $query .= " AND (j.job_number LIKE :search 
                    OR o.order_number LIKE :search 
                    OR p.product_code LIKE :search 
                    OR p.product_name LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    // Apply status filter
    if (!empty($status)) {
        $statuses = explode(',', $status);
        $placeholders = [];
        foreach ($statuses as $i => $s) {
            $key = ":status$i";
            $placeholders[] = $key;
            $params[$key] = $s;
        }
        $query .= " AND j.status IN (" . implode(',', $placeholders) . ")";
    }
    
    // Apply department filter
    if (!empty($department_id)) {
        $query .= " AND j.department_id = :department_id";
        $params[':department_id'] = $department_id;
    }
    
    // Apply assigned to filter
    if (!empty($assigned_to)) {
        $query .= " AND j.assigned_to = :assigned_to";
        $params[':assigned_to'] = $assigned_to;
    }
    
    // Apply unassigned filter
    if (!empty($unassigned)) {
        $query .= " AND j.assigned_to IS NULL";
    }
    
    $query .= " GROUP BY j.id, j.job_number, j.quantity, j.scheduled_start, j.scheduled_end, 
                j.actual_start, j.actual_end, j.status, j.priority, o.order_number, 
                p.product_code, p.product_name, d.department_name, e.first_name, e.last_name";
    $query .= " ORDER BY j.scheduled_start ASC, j.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo jsonResponse(true, 'Jobs retrieved successfully', $jobs);
    
} catch (Exception $e) {
    error_log("Error in production/jobs.php: " . $e->getMessage());
    echo jsonResponse(false, 'Error retrieving jobs: ' . $e->getMessage());
}

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
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        echo jsonResponse(false, 'Job ID is required');
        exit;
    }
    
    // Get job details
    $stmt = $pdo->prepare("SELECT 
                            j.*,
                            o.order_number,
                            o.customer_name,
                            oi.quantity as order_item_quantity,
                            p.product_code,
                            p.product_name,
                            d.department_name,
                            ps.stage_name,
                            m.machine_name,
                            m.machine_code,
                            CONCAT(e.first_name, ' ', e.last_name) as assigned_to_name,
                            e.employee_number as assigned_to_number,
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
                          WHERE j.id = :id");
    $stmt->execute([':id' => $id]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$job) {
        echo jsonResponse(false, 'Job not found');
        exit;
    }
    
    echo jsonResponse(true, 'Job retrieved successfully', $job);
    
} catch (Exception $e) {
    error_log("Error in jobs/get.php: " . $e->getMessage());
    echo jsonResponse(false, 'Error retrieving job: ' . $e->getMessage());
}

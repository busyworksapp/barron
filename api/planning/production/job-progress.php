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
    $job_id = $_GET['job_id'] ?? null;
    
    if (!$job_id) {
        echo jsonResponse(false, 'Job ID is required');
        exit;
    }
    
    // Get job with production totals
    $stmt = $pdo->prepare("SELECT 
                            j.id,
                            j.job_number,
                            j.quantity as total_quantity,
                            j.scheduled_start,
                            j.scheduled_end,
                            j.actual_start,
                            j.actual_end,
                            j.status,
                            p.product_code,
                            p.product_name,
                            d.department_name,
                            COALESCE(SUM(jpl.quantity_produced), 0) as produced_quantity,
                            COALESCE(SUM(jpl.quantity_rejected), 0) as rejected_quantity
                          FROM job_schedules j
                          INNER JOIN order_items oi ON j.order_item_id = oi.id
                          INNER JOIN products p ON oi.product_id = p.id
                          INNER JOIN departments d ON j.department_id = d.id
                          LEFT JOIN job_production_log jpl ON j.id = jpl.job_id
                          WHERE j.id = :job_id
                          GROUP BY j.id");
    $stmt->execute([':job_id' => $job_id]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$job) {
        echo jsonResponse(false, 'Job not found');
        exit;
    }
    
    echo jsonResponse(true, 'Job progress retrieved successfully', $job);
    
} catch (Exception $e) {
    error_log("Error in production/job-progress.php: " . $e->getMessage());
    echo jsonResponse(false, 'Error retrieving job progress: ' . $e->getMessage());
}

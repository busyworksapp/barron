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
    
    // Get production logs
    $stmt = $pdo->prepare("SELECT 
                            jpl.*,
                            CONCAT(u.first_name, ' ', u.last_name) as logged_by_name
                          FROM job_production_log jpl
                          INNER JOIN users u ON jpl.logged_by = u.id
                          WHERE jpl.job_id = :job_id
                          ORDER BY jpl.log_time DESC");
    $stmt->execute([':job_id' => $job_id]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo jsonResponse(true, 'Production logs retrieved successfully', $logs);
    
} catch (Exception $e) {
    error_log("Error in production/logs.php: " . $e->getMessage());
    echo jsonResponse(false, 'Error retrieving production logs: ' . $e->getMessage());
}

<?php
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';

header('Content-Type: application/json');

$auth = new Auth();
$auth->requireLogin();

if (!hasPermission('maintenance.view')) {
    echo jsonResponse(false, 'Permission denied');
    exit;
}

try {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        echo jsonResponse(false, 'Schedule ID is required');
        exit;
    }
    
    // Get schedule details
    $stmt = $pdo->prepare("SELECT 
                            pms.*,
                            m.machine_name,
                            m.machine_code,
                            CONCAT(u.first_name, ' ', u.last_name) as assigned_to_name
                          FROM preventive_maintenance_schedules pms
                          INNER JOIN machines m ON pms.machine_id = m.id
                          LEFT JOIN users u ON pms.assigned_to = u.id
                          WHERE pms.id = :id");
    $stmt->execute([':id' => $id]);
    $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$schedule) {
        echo jsonResponse(false, 'Schedule not found');
        exit;
    }
    
    echo jsonResponse(true, 'Schedule retrieved successfully', $schedule);
    
} catch (Exception $e) {
    error_log("Error in maintenance/schedule/get.php: " . $e->getMessage());
    echo jsonResponse(false, 'Error retrieving schedule: ' . $e->getMessage());
}

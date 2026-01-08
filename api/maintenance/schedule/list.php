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
    // Get filter parameters
    $search = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? '';
    $frequency = $_GET['frequency'] ?? '';
    $machine_id = $_GET['machine_id'] ?? '';
    
    // Build query
    $query = "SELECT 
                pms.id,
                pms.task_name,
                pms.frequency,
                pms.last_performed_date,
                pms.next_due_date,
                pms.status,
                m.machine_name,
                CONCAT(u.first_name, ' ', u.last_name) as assigned_to_name
              FROM preventive_maintenance_schedules pms
              INNER JOIN machines m ON pms.machine_id = m.id
              LEFT JOIN users u ON pms.assigned_to = u.id
              WHERE 1=1";
    
    $params = [];
    
    // Apply search filter
    if (!empty($search)) {
        $query .= " AND (pms.task_name LIKE :search 
                    OR m.machine_name LIKE :search 
                    OR pms.task_description LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    // Apply status filter
    if (!empty($status)) {
        $query .= " AND pms.status = :status";
        $params[':status'] = $status;
    }
    
    // Apply frequency filter
    if (!empty($frequency)) {
        $query .= " AND pms.frequency = :frequency";
        $params[':frequency'] = $frequency;
    }
    
    // Apply machine filter
    if (!empty($machine_id)) {
        $query .= " AND pms.machine_id = :machine_id";
        $params[':machine_id'] = $machine_id;
    }
    
    $query .= " ORDER BY pms.next_due_date ASC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo jsonResponse(true, 'Schedules retrieved successfully', $schedules);
    
} catch (Exception $e) {
    error_log("Error in maintenance/schedule/list.php: " . $e->getMessage());
    echo jsonResponse(false, 'Error retrieving schedules: ' . $e->getMessage());
}

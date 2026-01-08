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
    $priority = $_GET['priority'] ?? '';
    $maintenance_type = $_GET['maintenance_type'] ?? '';
    $machine_id = $_GET['machine_id'] ?? '';
    
    // Build query
    $query = "SELECT 
                mt.id,
                mt.ticket_number,
                mt.maintenance_type,
                mt.issue_description,
                mt.priority,
                mt.status,
                mt.created_at,
                m.machine_name,
                m.machine_code,
                CONCAT(u1.first_name, ' ', u1.last_name) as assigned_to_name
              FROM maintenance_tickets mt
              INNER JOIN machines m ON mt.machine_id = m.id
              LEFT JOIN users u1 ON mt.assigned_to = u1.id
              WHERE 1=1";
    
    $params = [];
    
    // Apply search filter
    if (!empty($search)) {
        $query .= " AND (mt.ticket_number LIKE :search 
                    OR m.machine_name LIKE :search 
                    OR m.machine_code LIKE :search
                    OR mt.issue_description LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    // Apply status filter
    if (!empty($status)) {
        $query .= " AND mt.status = :status";
        $params[':status'] = $status;
    }
    
    // Apply priority filter
    if (!empty($priority)) {
        $query .= " AND mt.priority = :priority";
        $params[':priority'] = $priority;
    }
    
    // Apply type filter
    if (!empty($maintenance_type)) {
        $query .= " AND mt.maintenance_type = :maintenance_type";
        $params[':maintenance_type'] = $maintenance_type;
    }
    
    // Apply machine filter
    if (!empty($machine_id)) {
        $query .= " AND mt.machine_id = :machine_id";
        $params[':machine_id'] = $machine_id;
    }
    
    $query .= " ORDER BY 
                CASE mt.priority 
                    WHEN 'urgent' THEN 1 
                    WHEN 'high' THEN 2 
                    WHEN 'normal' THEN 3 
                    WHEN 'low' THEN 4 
                END,
                mt.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo jsonResponse(true, 'Tickets retrieved successfully', $tickets);
    
} catch (Exception $e) {
    error_log("Error in maintenance/tickets/list.php: " . $e->getMessage());
    echo jsonResponse(false, 'Error retrieving tickets: ' . $e->getMessage());
}

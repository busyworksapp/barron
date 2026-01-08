<?php
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';

header('Content-Type: application/json');

$auth = new Auth();
$auth->requireLogin();

if (!hasPermission('sop.view')) {
    echo jsonResponse(false, 'Permission denied');
    exit;
}

try {
    // Get filter parameters
    $search = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? '';
    $severity = $_GET['severity'] ?? '';
    $department_id = $_GET['department_id'] ?? '';
    $date_from = $_GET['date_from'] ?? '';
    
    // Build query
    $query = "SELECT 
                sf.id,
                sf.ticket_number,
                sf.sop_reference,
                sf.failure_description,
                sf.severity,
                sf.status,
                sf.incident_date,
                sf.created_at,
                d.name as department_name,
                CONCAT(u1.first_name, ' ', u1.last_name) as reported_by_name,
                CONCAT(u2.first_name, ' ', u2.last_name) as assigned_to_name
              FROM sop_failures sf
              INNER JOIN departments d ON sf.department_id = d.id
              INNER JOIN users u1 ON sf.reported_by = u1.id
              LEFT JOIN users u2 ON sf.assigned_to = u2.id
              WHERE 1=1";
    
    $params = [];
    
    // Apply search filter
    if (!empty($search)) {
        $query .= " AND (sf.ticket_number LIKE :search 
                    OR sf.sop_reference LIKE :search 
                    OR sf.failure_description LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    // Apply status filter
    if (!empty($status)) {
        $query .= " AND sf.status = :status";
        $params[':status'] = $status;
    }
    
    // Apply severity filter
    if (!empty($severity)) {
        $query .= " AND sf.severity = :severity";
        $params[':severity'] = $severity;
    }
    
    // Apply department filter
    if (!empty($department_id)) {
        $query .= " AND sf.department_id = :department_id";
        $params[':department_id'] = $department_id;
    }
    
    // Apply date filter
    if (!empty($date_from)) {
        $query .= " AND sf.incident_date >= :date_from";
        $params[':date_from'] = $date_from;
    }
    
    $query .= " ORDER BY 
                CASE sf.severity 
                    WHEN 'critical' THEN 1 
                    WHEN 'high' THEN 2 
                    WHEN 'medium' THEN 3 
                    WHEN 'low' THEN 4 
                END,
                sf.incident_date DESC, 
                sf.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo jsonResponse(true, 'Tickets retrieved successfully', $tickets);
    
} catch (Exception $e) {
    error_log("Error in tickets/list.php: " . $e->getMessage());
    echo jsonResponse(false, 'Error retrieving tickets: ' . $e->getMessage());
}

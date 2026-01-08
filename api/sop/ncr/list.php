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
    $ncr_type = $_GET['ncr_type'] ?? '';
    $department_id = $_GET['department_id'] ?? '';
    $date_from = $_GET['date_from'] ?? '';
    
    // Build query
    $query = "SELECT 
                n.id,
                n.ncr_number,
                n.ncr_type,
                n.description,
                n.status,
                n.date_raised,
                n.target_closure_date,
                n.created_at,
                d.name as department_name,
                CONCAT(u1.first_name, ' ', u1.last_name) as raised_by_name,
                CONCAT(u2.first_name, ' ', u2.last_name) as assigned_to_name
              FROM ncr_reports n
              INNER JOIN departments d ON n.department_id = d.id
              INNER JOIN users u1 ON n.raised_by = u1.id
              LEFT JOIN users u2 ON n.assigned_to = u2.id
              WHERE 1=1";
    
    $params = [];
    
    // Apply search filter
    if (!empty($search)) {
        $query .= " AND (n.ncr_number LIKE :search 
                    OR n.description LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    // Apply status filter
    if (!empty($status)) {
        $query .= " AND n.status = :status";
        $params[':status'] = $status;
    }
    
    // Apply type filter
    if (!empty($ncr_type)) {
        $query .= " AND n.ncr_type = :ncr_type";
        $params[':ncr_type'] = $ncr_type;
    }
    
    // Apply department filter
    if (!empty($department_id)) {
        $query .= " AND n.department_id = :department_id";
        $params[':department_id'] = $department_id;
    }
    
    // Apply date filter
    if (!empty($date_from)) {
        $query .= " AND n.date_raised >= :date_from";
        $params[':date_from'] = $date_from;
    }
    
    $query .= " ORDER BY n.date_raised DESC, n.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $ncrs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo jsonResponse(true, 'NCRs retrieved successfully', $ncrs);
    
} catch (Exception $e) {
    error_log("Error in ncr/list.php: " . $e->getMessage());
    echo jsonResponse(false, 'Error retrieving NCRs: ' . $e->getMessage());
}

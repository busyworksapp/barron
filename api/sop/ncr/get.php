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
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        echo jsonResponse(false, 'NCR ID is required');
        exit;
    }
    
    // Get NCR details
    $stmt = $pdo->prepare("SELECT 
                            n.*,
                            d.name as department_name,
                            CONCAT(u1.first_name, ' ', u1.last_name) as raised_by_name,
                            CONCAT(u2.first_name, ' ', u2.last_name) as assigned_to_name,
                            CONCAT(u3.first_name, ' ', u3.last_name) as closed_by_name
                          FROM ncr_reports n
                          INNER JOIN departments d ON n.department_id = d.id
                          INNER JOIN users u1 ON n.raised_by = u1.id
                          LEFT JOIN users u2 ON n.assigned_to = u2.id
                          LEFT JOIN users u3 ON n.closed_by = u3.id
                          WHERE n.id = :id");
    $stmt->execute([':id' => $id]);
    $ncr = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$ncr) {
        echo jsonResponse(false, 'NCR not found');
        exit;
    }
    
    echo jsonResponse(true, 'NCR retrieved successfully', $ncr);
    
} catch (Exception $e) {
    error_log("Error in ncr/get.php: " . $e->getMessage());
    echo jsonResponse(false, 'Error retrieving NCR: ' . $e->getMessage());
}

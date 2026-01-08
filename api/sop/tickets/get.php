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
        echo jsonResponse(false, 'Ticket ID is required');
        exit;
    }
    
    // Get ticket details
    $stmt = $pdo->prepare("SELECT 
                            sf.*,
                            d.name as department_name,
                            CONCAT(u1.first_name, ' ', u1.last_name) as reported_by_name,
                            CONCAT(u2.first_name, ' ', u2.last_name) as assigned_to_name,
                            CONCAT(u3.first_name, ' ', u3.last_name) as closed_by_name
                          FROM sop_failures sf
                          INNER JOIN departments d ON sf.department_id = d.id
                          INNER JOIN users u1 ON sf.reported_by = u1.id
                          LEFT JOIN users u2 ON sf.assigned_to = u2.id
                          LEFT JOIN users u3 ON sf.closed_by = u3.id
                          WHERE sf.id = :id");
    $stmt->execute([':id' => $id]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$ticket) {
        echo jsonResponse(false, 'Ticket not found');
        exit;
    }
    
    echo jsonResponse(true, 'Ticket retrieved successfully', $ticket);
    
} catch (Exception $e) {
    error_log("Error in tickets/get.php: " . $e->getMessage());
    echo jsonResponse(false, 'Error retrieving ticket: ' . $e->getMessage());
}

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
        echo jsonResponse(false, 'Ticket ID is required');
        exit;
    }
    
    // Get ticket details
    $stmt = $pdo->prepare("SELECT 
                            mt.*,
                            m.machine_name,
                            m.machine_code,
                            d.name as department_name,
                            CONCAT(u1.first_name, ' ', u1.last_name) as created_by_name,
                            CONCAT(u2.first_name, ' ', u2.last_name) as assigned_to_name
                          FROM maintenance_tickets mt
                          INNER JOIN machines m ON mt.machine_id = m.id
                          INNER JOIN departments d ON m.department_id = d.id
                          INNER JOIN users u1 ON mt.created_by = u1.id
                          LEFT JOIN users u2 ON mt.assigned_to = u2.id
                          WHERE mt.id = :id");
    $stmt->execute([':id' => $id]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$ticket) {
        echo jsonResponse(false, 'Ticket not found');
        exit;
    }
    
    echo jsonResponse(true, 'Ticket retrieved successfully', $ticket);
    
} catch (Exception $e) {
    error_log("Error in maintenance/tickets/get.php: " . $e->getMessage());
    echo jsonResponse(false, 'Error retrieving ticket: ' . $e->getMessage());
}

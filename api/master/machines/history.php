<?php
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isAuthenticated()) {
    echo jsonResponse(false, 'Unauthorized');
    exit;
}

if (!hasPermission('master.view')) {
    echo jsonResponse(false, 'Insufficient permissions');
    exit;
}

if (!isset($_GET['machine_id'])) {
    echo jsonResponse(false, 'Machine ID is required');
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Get maintenance tickets for this machine
    $stmt = $db->prepare("
        SELECT mt.*,
        CONCAT(reporter.first_name, ' ', reporter.last_name) as reported_by_name,
        CONCAT(assignee.first_name, ' ', assignee.last_name) as assigned_to_name
        FROM maintenance_tickets mt
        LEFT JOIN employees reporter ON mt.reported_by = reporter.id
        LEFT JOIN employees assignee ON mt.assigned_to = assignee.id
        WHERE mt.machine_id = ?
        ORDER BY mt.created_at DESC
    ");
    $stmt->execute([$_GET['machine_id']]);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo jsonResponse(true, 'Maintenance history retrieved successfully', $tickets);
    
} catch (Exception $e) {
    error_log('Error in machines/history.php: ' . $e->getMessage());
    echo jsonResponse(false, 'Error retrieving maintenance history: ' . $e->getMessage());
}

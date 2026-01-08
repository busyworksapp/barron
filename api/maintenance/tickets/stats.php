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
    // Open tickets
    $stmt = $pdo->prepare("SELECT COUNT(*) as count 
                          FROM maintenance_tickets 
                          WHERE status = 'open'");
    $stmt->execute();
    $openCount = $stmt->fetch()['count'];
    
    // In progress tickets
    $stmt = $pdo->prepare("SELECT COUNT(*) as count 
                          FROM maintenance_tickets 
                          WHERE status IN ('assigned', 'in_progress')");
    $stmt->execute();
    $inProgressCount = $stmt->fetch()['count'];
    
    // Completed tickets
    $stmt = $pdo->prepare("SELECT COUNT(*) as count 
                          FROM maintenance_tickets 
                          WHERE status = 'completed'");
    $stmt->execute();
    $completedCount = $stmt->fetch()['count'];
    
    // Urgent priority tickets (open)
    $stmt = $pdo->prepare("SELECT COUNT(*) as count 
                          FROM maintenance_tickets 
                          WHERE priority = 'urgent' 
                          AND status NOT IN ('completed', 'closed')");
    $stmt->execute();
    $urgentCount = $stmt->fetch()['count'];
    
    $stats = [
        'open_count' => $openCount,
        'in_progress_count' => $inProgressCount,
        'completed_count' => $completedCount,
        'urgent_count' => $urgentCount
    ];
    
    echo jsonResponse(true, 'Statistics retrieved successfully', $stats);
    
} catch (Exception $e) {
    error_log("Error in maintenance/tickets/stats.php: " . $e->getMessage());
    echo jsonResponse(false, 'Error retrieving statistics: ' . $e->getMessage());
}

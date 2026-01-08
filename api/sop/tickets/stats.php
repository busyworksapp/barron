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
    // Open tickets (not resolved or closed)
    $stmt = $pdo->prepare("SELECT COUNT(*) as count 
                          FROM sop_failures 
                          WHERE status NOT IN ('resolved', 'closed')");
    $stmt->execute();
    $openCount = $stmt->fetch()['count'];
    
    // Resolved tickets
    $stmt = $pdo->prepare("SELECT COUNT(*) as count 
                          FROM sop_failures 
                          WHERE status = 'resolved'");
    $stmt->execute();
    $resolvedCount = $stmt->fetch()['count'];
    
    // This month tickets
    $stmt = $pdo->prepare("SELECT COUNT(*) as count 
                          FROM sop_failures 
                          WHERE YEAR(incident_date) = YEAR(CURDATE()) 
                          AND MONTH(incident_date) = MONTH(CURDATE())");
    $stmt->execute();
    $thisMonthCount = $stmt->fetch()['count'];
    
    // Critical severity tickets
    $stmt = $pdo->prepare("SELECT COUNT(*) as count 
                          FROM sop_failures 
                          WHERE severity = 'critical' 
                          AND status NOT IN ('resolved', 'closed')");
    $stmt->execute();
    $criticalCount = $stmt->fetch()['count'];
    
    $stats = [
        'open_count' => $openCount,
        'resolved_count' => $resolvedCount,
        'this_month_count' => $thisMonthCount,
        'critical_count' => $criticalCount
    ];
    
    echo jsonResponse(true, 'Statistics retrieved successfully', $stats);
    
} catch (Exception $e) {
    error_log("Error in tickets/stats.php: " . $e->getMessage());
    echo jsonResponse(false, 'Error retrieving statistics: ' . $e->getMessage());
}

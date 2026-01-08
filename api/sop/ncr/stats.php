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
    // Open NCRs (not closed)
    $stmt = $pdo->prepare("SELECT COUNT(*) as count 
                          FROM ncr_reports 
                          WHERE status != 'closed'");
    $stmt->execute();
    $openCount = $stmt->fetch()['count'];
    
    // Closed NCRs
    $stmt = $pdo->prepare("SELECT COUNT(*) as count 
                          FROM ncr_reports 
                          WHERE status = 'closed'");
    $stmt->execute();
    $closedCount = $stmt->fetch()['count'];
    
    // This month NCRs
    $stmt = $pdo->prepare("SELECT COUNT(*) as count 
                          FROM ncr_reports 
                          WHERE YEAR(date_raised) = YEAR(CURDATE()) 
                          AND MONTH(date_raised) = MONTH(CURDATE())");
    $stmt->execute();
    $thisMonthCount = $stmt->fetch()['count'];
    
    // Overdue CAPA (target closure date passed and not closed)
    $stmt = $pdo->prepare("SELECT COUNT(*) as count 
                          FROM ncr_reports 
                          WHERE target_closure_date < CURDATE() 
                          AND status != 'closed'");
    $stmt->execute();
    $overdueCount = $stmt->fetch()['count'];
    
    $stats = [
        'open_count' => $openCount,
        'closed_count' => $closedCount,
        'this_month_count' => $thisMonthCount,
        'overdue_count' => $overdueCount
    ];
    
    echo jsonResponse(true, 'Statistics retrieved successfully', $stats);
    
} catch (Exception $e) {
    error_log("Error in ncr/stats.php: " . $e->getMessage());
    echo jsonResponse(false, 'Error retrieving statistics: ' . $e->getMessage());
}

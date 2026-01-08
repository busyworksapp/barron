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
    // Active schedules
    $stmt = $pdo->prepare("SELECT COUNT(*) as count 
                          FROM preventive_maintenance_schedules 
                          WHERE status = 'active'");
    $stmt->execute();
    $activeCount = $stmt->fetch()['count'];
    
    // Overdue tasks (past due date and active)
    $stmt = $pdo->prepare("SELECT COUNT(*) as count 
                          FROM preventive_maintenance_schedules 
                          WHERE next_due_date < CURDATE() 
                          AND status = 'active'");
    $stmt->execute();
    $overdueCount = $stmt->fetch()['count'];
    
    // Due this week
    $stmt = $pdo->prepare("SELECT COUNT(*) as count 
                          FROM preventive_maintenance_schedules 
                          WHERE next_due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                          AND status = 'active'");
    $stmt->execute();
    $dueThisWeekCount = $stmt->fetch()['count'];
    
    // Completed this month
    $stmt = $pdo->prepare("SELECT COUNT(*) as count 
                          FROM preventive_maintenance_schedules 
                          WHERE YEAR(last_performed_date) = YEAR(CURDATE()) 
                          AND MONTH(last_performed_date) = MONTH(CURDATE())");
    $stmt->execute();
    $completedThisMonthCount = $stmt->fetch()['count'];
    
    $stats = [
        'active_count' => $activeCount,
        'overdue_count' => $overdueCount,
        'due_this_week_count' => $dueThisWeekCount,
        'completed_this_month_count' => $completedThisMonthCount
    ];
    
    echo jsonResponse(true, 'Statistics retrieved successfully', $stats);
    
} catch (Exception $e) {
    error_log("Error in maintenance/schedule/stats.php: " . $e->getMessage());
    echo jsonResponse(false, 'Error retrieving statistics: ' . $e->getMessage());
}

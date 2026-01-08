<?php
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';

header('Content-Type: application/json');

$auth = new Auth();
$auth->requireLogin();

if (!hasPermission('defects.view')) {
    echo jsonResponse(false, 'Permission denied');
    exit;
}

try {
    // Pending rejects
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM internal_rejects WHERE status = 'pending'");
    $stmt->execute();
    $pendingCount = $stmt->fetch()['count'];
    
    // Approved rejects
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM internal_rejects WHERE status = 'approved'");
    $stmt->execute();
    $approvedCount = $stmt->fetch()['count'];
    
    // This month rejects
    $stmt = $pdo->prepare("SELECT COUNT(*) as count 
                          FROM internal_rejects 
                          WHERE YEAR(reject_date) = YEAR(CURDATE()) 
                          AND MONTH(reject_date) = MONTH(CURDATE())");
    $stmt->execute();
    $thisMonthCount = $stmt->fetch()['count'];
    
    // Reject rate calculation
    $stmt = $pdo->prepare("SELECT 
                            COALESCE(SUM(ir.quantity_rejected), 0) as total_rejected,
                            COALESCE(SUM(jpl.quantity_produced), 0) as total_produced
                          FROM internal_rejects ir
                          INNER JOIN job_schedules js ON ir.job_id = js.id
                          LEFT JOIN job_production_log jpl ON js.id = jpl.job_id
                          WHERE ir.status = 'approved'
                          AND YEAR(ir.reject_date) = YEAR(CURDATE()) 
                          AND MONTH(ir.reject_date) = MONTH(CURDATE())");
    $stmt->execute();
    $result = $stmt->fetch();
    
    $rejectRate = 0;
    if ($result['total_produced'] > 0) {
        $rejectRate = round(($result['total_rejected'] / $result['total_produced']) * 100, 2);
    }
    
    $stats = [
        'pending_count' => $pendingCount,
        'approved_count' => $approvedCount,
        'this_month_count' => $thisMonthCount,
        'reject_rate' => $rejectRate
    ];
    
    echo jsonResponse(true, 'Statistics retrieved successfully', $stats);
    
} catch (Exception $e) {
    error_log("Error in rejects/stats.php: " . $e->getMessage());
    echo jsonResponse(false, 'Error retrieving statistics: ' . $e->getMessage());
}

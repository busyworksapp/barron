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
    // Open returns (not resolved)
    $stmt = $pdo->prepare("SELECT COUNT(*) as count 
                          FROM customer_returns 
                          WHERE status NOT IN ('resolved')");
    $stmt->execute();
    $openCount = $stmt->fetch()['count'];
    
    // Resolved returns
    $stmt = $pdo->prepare("SELECT COUNT(*) as count 
                          FROM customer_returns 
                          WHERE status = 'resolved'");
    $stmt->execute();
    $resolvedCount = $stmt->fetch()['count'];
    
    // This month returns
    $stmt = $pdo->prepare("SELECT COUNT(*) as count 
                          FROM customer_returns 
                          WHERE YEAR(return_date) = YEAR(CURDATE()) 
                          AND MONTH(return_date) = MONTH(CURDATE())");
    $stmt->execute();
    $thisMonthCount = $stmt->fetch()['count'];
    
    // Return rate calculation (returns / total orders)
    $stmt = $pdo->prepare("SELECT 
                            COUNT(DISTINCT cr.order_id) as returned_orders,
                            (SELECT COUNT(*) FROM orders WHERE status = 'completed') as total_completed
                          FROM customer_returns cr
                          WHERE YEAR(cr.return_date) = YEAR(CURDATE()) 
                          AND MONTH(cr.return_date) = MONTH(CURDATE())");
    $stmt->execute();
    $result = $stmt->fetch();
    
    $returnRate = 0;
    if ($result['total_completed'] > 0) {
        $returnRate = round(($result['returned_orders'] / $result['total_completed']) * 100, 2);
    }
    
    $stats = [
        'open_count' => $openCount,
        'resolved_count' => $resolvedCount,
        'this_month_count' => $thisMonthCount,
        'return_rate' => $returnRate
    ];
    
    echo jsonResponse(true, 'Statistics retrieved successfully', $stats);
    
} catch (Exception $e) {
    error_log("Error in returns/stats.php: " . $e->getMessage());
    echo jsonResponse(false, 'Error retrieving statistics: ' . $e->getMessage());
}

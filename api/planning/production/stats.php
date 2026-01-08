<?php
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';

header('Content-Type: application/json');

$auth = new Auth();
$auth->requireLogin();

if (!hasPermission('production.view')) {
    echo jsonResponse(false, 'Permission denied');
    exit;
}

try {
    // Get current date
    $today = date('Y-m-d');
    
    // Active jobs (scheduled + in_progress)
    $stmt = $pdo->prepare("SELECT COUNT(*) as count 
                          FROM job_schedules 
                          WHERE status IN ('scheduled', 'in_progress')");
    $stmt->execute();
    $activeJobs = $stmt->fetch()['count'];
    
    // Overdue jobs
    $stmt = $pdo->prepare("SELECT COUNT(*) as count 
                          FROM job_schedules 
                          WHERE status IN ('scheduled', 'in_progress') 
                          AND scheduled_end < :today");
    $stmt->execute([':today' => $today]);
    $overdueJobs = $stmt->fetch()['count'];
    
    // Completed today
    $stmt = $pdo->prepare("SELECT COUNT(*) as count 
                          FROM job_schedules 
                          WHERE status = 'completed' 
                          AND DATE(actual_end) = :today");
    $stmt->execute([':today' => $today]);
    $completedToday = $stmt->fetch()['count'];
    
    // Average completion rate
    $stmt = $pdo->prepare("SELECT 
                            AVG(CASE 
                                WHEN j.quantity > 0 
                                THEN (COALESCE(SUM(jpl.quantity_produced), 0) / j.quantity) * 100 
                                ELSE 0 
                            END) as avg_completion
                          FROM job_schedules j
                          LEFT JOIN job_production_log jpl ON j.id = jpl.job_id
                          WHERE j.status IN ('scheduled', 'in_progress')
                          GROUP BY j.id");
    $stmt->execute();
    $avgCompletion = 0;
    $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (count($results) > 0) {
        $avgCompletion = round(array_sum($results) / count($results));
    }
    
    $stats = [
        'active_jobs' => $activeJobs,
        'overdue_jobs' => $overdueJobs,
        'completed_today' => $completedToday,
        'avg_completion' => $avgCompletion
    ];
    
    echo jsonResponse(true, 'Statistics retrieved successfully', $stats);
    
} catch (Exception $e) {
    error_log("Error in production/stats.php: " . $e->getMessage());
    echo jsonResponse(false, 'Error retrieving statistics: ' . $e->getMessage());
}

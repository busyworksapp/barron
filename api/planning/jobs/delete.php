<?php
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';

header('Content-Type: application/json');

$auth = new Auth();
$auth->requireLogin();

if (!hasPermission('planning.delete')) {
    echo jsonResponse(false, 'Permission denied');
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['job_id'])) {
        echo jsonResponse(false, 'Job ID is required');
        exit;
    }
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // Get job details
    $stmt = $pdo->prepare("SELECT job_number, status FROM job_schedules WHERE id = :id");
    $stmt->execute([':id' => $data['job_id']]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$job) {
        $pdo->rollBack();
        echo jsonResponse(false, 'Job not found');
        exit;
    }
    
    // Check if job has production logs
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM job_production_log WHERE job_id = :job_id");
    $stmt->execute([':job_id' => $data['job_id']]);
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        $pdo->rollBack();
        echo jsonResponse(false, 'Cannot delete job with production logs. Consider marking it as cancelled instead.');
        exit;
    }
    
    // Check if job is in progress or completed
    if ($job['status'] === 'in_progress' || $job['status'] === 'completed') {
        $pdo->rollBack();
        echo jsonResponse(false, 'Cannot delete jobs that are in progress or completed. Consider marking it as cancelled instead.');
        exit;
    }
    
    // Delete job
    $stmt = $pdo->prepare("DELETE FROM job_schedules WHERE id = :id");
    $stmt->execute([':id' => $data['job_id']]);
    
    // Log activity
    logActivity(
        'job_deleted',
        'job_schedules',
        $data['job_id'],
        "Deleted job {$job['job_number']}"
    );
    
    $pdo->commit();
    
    echo jsonResponse(true, 'Job deleted successfully');
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error in jobs/delete.php: " . $e->getMessage());
    echo jsonResponse(false, 'Error deleting job: ' . $e->getMessage());
}

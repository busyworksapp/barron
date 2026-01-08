<?php
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';

header('Content-Type: application/json');

$auth = new Auth();
$auth->requireLogin();

if (!hasPermission('production.edit')) {
    echo jsonResponse(false, 'Permission denied');
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (empty($data['job_id'])) {
        echo jsonResponse(false, 'Job ID is required');
        exit;
    }
    
    if (empty($data['status'])) {
        echo jsonResponse(false, 'Status is required');
        exit;
    }
    
    // Validate status
    $validStatuses = ['scheduled', 'in_progress', 'completed', 'on_hold', 'cancelled'];
    if (!in_array($data['status'], $validStatuses)) {
        echo jsonResponse(false, 'Invalid status value');
        exit;
    }
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // Get current job
    $stmt = $pdo->prepare("SELECT * FROM job_schedules WHERE id = :id");
    $stmt->execute([':id' => $data['job_id']]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$job) {
        $pdo->rollBack();
        echo jsonResponse(false, 'Job not found');
        exit;
    }
    
    $oldStatus = $job['status'];
    $newStatus = $data['status'];
    
    // Update status
    $updateFields = ['status = :status'];
    $params = [
        ':status' => $newStatus,
        ':job_id' => $data['job_id']
    ];
    
    // Set actual_start if moving to in_progress
    if ($newStatus === 'in_progress' && empty($job['actual_start'])) {
        $updateFields[] = 'actual_start = NOW()';
    }
    
    // Set actual_end if moving to completed
    if ($newStatus === 'completed' && empty($job['actual_end'])) {
        $updateFields[] = 'actual_end = NOW()';
    }
    
    $query = "UPDATE job_schedules SET " . implode(', ', $updateFields) . " WHERE id = :job_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    
    // Log activity
    $message = "Job {$job['job_number']} status changed from '$oldStatus' to '$newStatus'";
    if (!empty($data['notes'])) {
        $message .= ". Reason: " . $data['notes'];
    }
    
    logActivity(
        'job_status_updated',
        'job_schedules',
        $data['job_id'],
        $message
    );
    
    $pdo->commit();
    
    echo jsonResponse(true, 'Status updated successfully');
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error in production/update-status.php: " . $e->getMessage());
    echo jsonResponse(false, 'Error updating status: ' . $e->getMessage());
}

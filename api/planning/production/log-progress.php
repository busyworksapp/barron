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
    
    if (empty($data['quantity_produced']) || $data['quantity_produced'] < 1) {
        echo jsonResponse(false, 'Quantity produced must be at least 1');
        exit;
    }
    
    if (empty($data['log_time'])) {
        echo jsonResponse(false, 'Log time is required');
        exit;
    }
    
    $quantity_rejected = $data['quantity_rejected'] ?? 0;
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // Get job details
    $stmt = $pdo->prepare("SELECT j.*, 
                          COALESCE(SUM(jpl.quantity_produced), 0) as current_produced
                          FROM job_schedules j
                          LEFT JOIN job_production_log jpl ON j.id = jpl.job_id
                          WHERE j.id = :job_id
                          GROUP BY j.id");
    $stmt->execute([':job_id' => $data['job_id']]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$job) {
        $pdo->rollBack();
        echo jsonResponse(false, 'Job not found');
        exit;
    }
    
    // Validate quantity doesn't exceed remaining
    $remaining = $job['quantity'] - $job['current_produced'];
    if ($data['quantity_produced'] > $remaining) {
        $pdo->rollBack();
        echo jsonResponse(false, "Quantity produced ($data[quantity_produced]) exceeds remaining quantity ($remaining)");
        exit;
    }
    
    // Insert production log
    $stmt = $pdo->prepare("INSERT INTO job_production_log 
                          (job_id, quantity_produced, quantity_rejected, production_notes, log_time, logged_by)
                          VALUES 
                          (:job_id, :quantity_produced, :quantity_rejected, :production_notes, :log_time, :logged_by)");
    
    $stmt->execute([
        ':job_id' => $data['job_id'],
        ':quantity_produced' => $data['quantity_produced'],
        ':quantity_rejected' => $quantity_rejected,
        ':production_notes' => $data['production_notes'] ?? null,
        ':log_time' => $data['log_time'],
        ':logged_by' => getCurrentUser()['id']
    ]);
    
    $logId = $pdo->lastInsertId();
    
    // Update job actual_start if mark_started is true
    if (!empty($data['mark_started']) && empty($job['actual_start'])) {
        $stmt = $pdo->prepare("UPDATE job_schedules 
                              SET actual_start = :actual_start, status = 'in_progress'
                              WHERE id = :job_id");
        $stmt->execute([
            ':actual_start' => $data['log_time'],
            ':job_id' => $data['job_id']
        ]);
    }
    
    // Check if job is complete
    $newProduced = $job['current_produced'] + $data['quantity_produced'];
    if ($newProduced >= $job['quantity']) {
        $stmt = $pdo->prepare("UPDATE job_schedules 
                              SET status = 'completed', actual_end = :actual_end
                              WHERE id = :job_id");
        $stmt->execute([
            ':actual_end' => $data['log_time'],
            ':job_id' => $data['job_id']
        ]);
        
        logActivity(
            'job_completed',
            'job_schedules',
            $data['job_id'],
            "Job {$job['job_number']} completed with {$newProduced} units produced"
        );
    } else {
        // Just ensure status is in_progress
        $stmt = $pdo->prepare("UPDATE job_schedules 
                              SET status = 'in_progress'
                              WHERE id = :job_id AND status = 'scheduled'");
        $stmt->execute([':job_id' => $data['job_id']]);
    }
    
    // Log activity
    logActivity(
        'production_logged',
        'job_production_log',
        $logId,
        "Logged production for job {$job['job_number']}: {$data['quantity_produced']} produced" . 
        ($quantity_rejected > 0 ? ", {$quantity_rejected} rejected" : "")
    );
    
    $pdo->commit();
    
    echo jsonResponse(true, 'Progress logged successfully', [
        'log_id' => $logId,
        'new_total' => $newProduced,
        'completed' => $newProduced >= $job['quantity']
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error in production/log-progress.php: " . $e->getMessage());
    echo jsonResponse(false, 'Error logging progress: ' . $e->getMessage());
}

<?php
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';

header('Content-Type: application/json');

$auth = new Auth();
$auth->requireLogin();

if (!hasPermission('maintenance.edit')) {
    echo jsonResponse(false, 'Permission denied');
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['schedule_id'])) {
        echo jsonResponse(false, 'Schedule ID is required');
        exit;
    }
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // Get schedule details
    $stmt = $pdo->prepare("SELECT * FROM preventive_maintenance_schedules WHERE id = :id");
    $stmt->execute([':id' => $data['schedule_id']]);
    $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$schedule) {
        $pdo->rollBack();
        echo jsonResponse(false, 'Schedule not found');
        exit;
    }
    
    // Calculate next due date based on frequency
    $today = date('Y-m-d');
    $nextDueDate = $today;
    
    switch ($schedule['frequency']) {
        case 'daily':
            $nextDueDate = date('Y-m-d', strtotime('+1 day'));
            break;
        case 'weekly':
            $nextDueDate = date('Y-m-d', strtotime('+1 week'));
            break;
        case 'monthly':
            $nextDueDate = date('Y-m-d', strtotime('+1 month'));
            break;
        case 'quarterly':
            $nextDueDate = date('Y-m-d', strtotime('+3 months'));
            break;
        case 'semi_annual':
            $nextDueDate = date('Y-m-d', strtotime('+6 months'));
            break;
        case 'annual':
            $nextDueDate = date('Y-m-d', strtotime('+1 year'));
            break;
    }
    
    // Update schedule with performed date and next due date
    $stmt = $pdo->prepare("UPDATE preventive_maintenance_schedules 
                          SET last_performed_date = CURDATE(),
                              next_due_date = :next_due_date
                          WHERE id = :id");
    $stmt->execute([
        ':next_due_date' => $nextDueDate,
        ':id' => $data['schedule_id']
    ]);
    
    // Log activity
    logActivity(
        'pm_schedule_performed',
        'preventive_maintenance_schedules',
        $data['schedule_id'],
        "Marked PM schedule as performed: {$schedule['task_name']} - Next due: {$nextDueDate}"
    );
    
    $pdo->commit();
    
    echo jsonResponse(true, 'Schedule marked as performed. Next due date: ' . date('d/m/Y', strtotime($nextDueDate)));
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error in maintenance/schedule/mark_performed.php: " . $e->getMessage());
    echo jsonResponse(false, 'Error updating schedule: ' . $e->getMessage());
}

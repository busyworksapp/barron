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
    
    // Validate schedule_id
    if (empty($data['schedule_id'])) {
        echo jsonResponse(false, 'Schedule ID is required');
        exit;
    }
    
    // Validate required fields
    $required = ['task_name', 'machine_id', 'task_description', 'frequency',
                 'estimated_duration', 'next_due_date', 'status'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            echo jsonResponse(false, ucfirst(str_replace('_', ' ', $field)) . ' is required');
            exit;
        }
    }
    
    // Validate frequency
    $validFrequencies = ['daily', 'weekly', 'monthly', 'quarterly', 'semi_annual', 'annual'];
    if (!in_array($data['frequency'], $validFrequencies)) {
        echo jsonResponse(false, 'Invalid frequency');
        exit;
    }
    
    // Validate status
    $validStatuses = ['active', 'inactive'];
    if (!in_array($data['status'], $validStatuses)) {
        echo jsonResponse(false, 'Invalid status');
        exit;
    }
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // Get existing schedule
    $stmt = $pdo->prepare("SELECT * FROM preventive_maintenance_schedules WHERE id = :id");
    $stmt->execute([':id' => $data['schedule_id']]);
    $existingSchedule = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existingSchedule) {
        $pdo->rollBack();
        echo jsonResponse(false, 'Schedule not found');
        exit;
    }
    
    // Update schedule
    $stmt = $pdo->prepare("UPDATE preventive_maintenance_schedules SET
                          task_name = :task_name,
                          machine_id = :machine_id,
                          task_description = :task_description,
                          frequency = :frequency,
                          estimated_duration = :estimated_duration,
                          assigned_to = :assigned_to,
                          next_due_date = :next_due_date,
                          last_performed_date = :last_performed_date,
                          checklist_items = :checklist_items,
                          status = :status,
                          notes = :notes
                          WHERE id = :schedule_id");
    
    $stmt->execute([
        ':task_name' => $data['task_name'],
        ':machine_id' => $data['machine_id'],
        ':task_description' => $data['task_description'],
        ':frequency' => $data['frequency'],
        ':estimated_duration' => $data['estimated_duration'],
        ':assigned_to' => $data['assigned_to'] ?? null,
        ':next_due_date' => $data['next_due_date'],
        ':last_performed_date' => $data['last_performed_date'] ?? null,
        ':checklist_items' => $data['checklist_items'] ?? null,
        ':status' => $data['status'],
        ':notes' => $data['notes'] ?? null,
        ':schedule_id' => $data['schedule_id']
    ]);
    
    // Log activity
    logActivity(
        'pm_schedule_updated',
        'preventive_maintenance_schedules',
        $data['schedule_id'],
        "Updated PM schedule: {$data['task_name']}"
    );
    
    $pdo->commit();
    
    echo jsonResponse(true, 'Schedule updated successfully');
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error in maintenance/schedule/update.php: " . $e->getMessage());
    echo jsonResponse(false, 'Error updating schedule: ' . $e->getMessage());
}

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
    
    // Verify machine exists
    $stmt = $pdo->prepare("SELECT id, machine_name FROM machines WHERE id = :id");
    $stmt->execute([':id' => $data['machine_id']]);
    $machine = $stmt->fetch();
    
    if (!$machine) {
        $pdo->rollBack();
        echo jsonResponse(false, 'Machine not found');
        exit;
    }
    
    // Insert schedule
    $stmt = $pdo->prepare("INSERT INTO preventive_maintenance_schedules 
                          (task_name, machine_id, task_description, frequency, 
                           estimated_duration, assigned_to, next_due_date, 
                           last_performed_date, checklist_items, status, notes, created_by) 
                          VALUES 
                          (:task_name, :machine_id, :task_description, :frequency,
                           :estimated_duration, :assigned_to, :next_due_date,
                           :last_performed_date, :checklist_items, :status, :notes, :created_by)");
    
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
        ':created_by' => getCurrentUser()['id']
    ]);
    
    $scheduleId = $pdo->lastInsertId();
    
    // Log activity
    logActivity(
        'pm_schedule_created',
        'preventive_maintenance_schedules',
        $scheduleId,
        "Created PM schedule: {$data['task_name']} for {$machine['machine_name']} - Frequency: {$data['frequency']}"
    );
    
    $pdo->commit();
    
    echo jsonResponse(true, 'Schedule created successfully', ['schedule_id' => $scheduleId]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error in maintenance/schedule/create.php: " . $e->getMessage());
    echo jsonResponse(false, 'Error creating schedule: ' . $e->getMessage());
}

<?php
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';

header('Content-Type: application/json');

$auth = new Auth();
$auth->requireLogin();

if (!hasPermission('planning.edit')) {
    echo jsonResponse(false, 'Permission denied');
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate job_id
    if (empty($data['job_id'])) {
        echo jsonResponse(false, 'Job ID is required');
        exit;
    }
    
    // Validate required fields
    $required = ['job_number', 'order_id', 'order_item_id', 'quantity', 'department_id',
                 'scheduled_start', 'scheduled_end', 'status'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            echo jsonResponse(false, ucfirst(str_replace('_', ' ', $field)) . ' is required');
            exit;
        }
    }
    
    // Validate status
    $validStatuses = ['scheduled', 'in_progress', 'completed', 'on_hold', 'cancelled'];
    if (!in_array($data['status'], $validStatuses)) {
        echo jsonResponse(false, 'Invalid status value');
        exit;
    }
    
    // Validate priority
    $priority = $data['priority'] ?? 'normal';
    $validPriorities = ['normal', 'high', 'urgent'];
    if (!in_array($priority, $validPriorities)) {
        echo jsonResponse(false, 'Invalid priority value');
        exit;
    }
    
    // Check for duplicate job number (excluding current job)
    $stmt = $pdo->prepare("SELECT id FROM job_schedules 
                          WHERE job_number = :job_number AND id != :job_id");
    $stmt->execute([
        ':job_number' => $data['job_number'],
        ':job_id' => $data['job_id']
    ]);
    if ($stmt->fetch()) {
        echo jsonResponse(false, 'Job number already exists');
        exit;
    }
    
    // Validate dates
    $scheduledStart = strtotime($data['scheduled_start']);
    $scheduledEnd = strtotime($data['scheduled_end']);
    
    if ($scheduledEnd <= $scheduledStart) {
        echo jsonResponse(false, 'Scheduled end date must be after start date');
        exit;
    }
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // Get existing job
    $stmt = $pdo->prepare("SELECT * FROM job_schedules WHERE id = :id");
    $stmt->execute([':id' => $data['job_id']]);
    $existingJob = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existingJob) {
        $pdo->rollBack();
        echo jsonResponse(false, 'Job not found');
        exit;
    }
    
    // Verify order item exists
    $stmt = $pdo->prepare("SELECT oi.*, p.product_code, p.product_name 
                          FROM order_items oi 
                          INNER JOIN products p ON oi.product_id = p.id
                          WHERE oi.id = :id AND oi.order_id = :order_id");
    $stmt->execute([
        ':id' => $data['order_item_id'],
        ':order_id' => $data['order_id']
    ]);
    $orderItem = $stmt->fetch();
    
    if (!$orderItem) {
        $pdo->rollBack();
        echo jsonResponse(false, 'Order item not found or does not belong to selected order');
        exit;
    }
    
    // Validate quantity doesn't exceed order item quantity (excluding current job)
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity), 0) as scheduled_qty 
                          FROM job_schedules 
                          WHERE order_item_id = :order_item_id 
                          AND id != :job_id
                          AND status NOT IN ('cancelled')");
    $stmt->execute([
        ':order_item_id' => $data['order_item_id'],
        ':job_id' => $data['job_id']
    ]);
    $result = $stmt->fetch();
    $scheduledQty = $result['scheduled_qty'];
    
    $totalScheduled = $scheduledQty + $data['quantity'];
    if ($totalScheduled > $orderItem['quantity']) {
        $pdo->rollBack();
        $remaining = $orderItem['quantity'] - $scheduledQty;
        echo jsonResponse(false, "Quantity exceeds order item quantity. Order quantity: {$orderItem['quantity']}, Already scheduled: {$scheduledQty}, Remaining: {$remaining}");
        exit;
    }
    
    // Verify department exists
    $stmt = $pdo->prepare("SELECT id FROM departments WHERE id = :id AND is_active = 1");
    $stmt->execute([':id' => $data['department_id']]);
    if (!$stmt->fetch()) {
        $pdo->rollBack();
        echo jsonResponse(false, 'Department not found or inactive');
        exit;
    }
    
    // Verify production stage if provided
    if (!empty($data['production_stage_id'])) {
        $stmt = $pdo->prepare("SELECT id FROM production_stages 
                              WHERE id = :id AND department_id = :department_id");
        $stmt->execute([
            ':id' => $data['production_stage_id'],
            ':department_id' => $data['department_id']
        ]);
        if (!$stmt->fetch()) {
            $pdo->rollBack();
            echo jsonResponse(false, 'Production stage not found or does not belong to selected department');
            exit;
        }
    }
    
    // Verify machine if provided
    if (!empty($data['machine_id'])) {
        $stmt = $pdo->prepare("SELECT id FROM machines 
                              WHERE id = :id AND department_id = :department_id");
        $stmt->execute([
            ':id' => $data['machine_id'],
            ':department_id' => $data['department_id']
        ]);
        if (!$stmt->fetch()) {
            $pdo->rollBack();
            echo jsonResponse(false, 'Machine not found or does not belong to selected department');
            exit;
        }
    }
    
    // Verify employee if provided
    if (!empty($data['assigned_to'])) {
        $stmt = $pdo->prepare("SELECT ed.employee_id 
                              FROM employee_departments ed
                              INNER JOIN employees e ON ed.employee_id = e.id
                              WHERE ed.employee_id = :employee_id 
                              AND ed.department_id = :department_id
                              AND e.is_active = 1");
        $stmt->execute([
            ':employee_id' => $data['assigned_to'],
            ':department_id' => $data['department_id']
        ]);
        if (!$stmt->fetch()) {
            $pdo->rollBack();
            echo jsonResponse(false, 'Employee not found, inactive, or not assigned to selected department');
            exit;
        }
    }
    
    // Update job
    $stmt = $pdo->prepare("UPDATE job_schedules SET
                          job_number = :job_number,
                          order_id = :order_id,
                          order_item_id = :order_item_id,
                          quantity = :quantity,
                          department_id = :department_id,
                          production_stage_id = :production_stage_id,
                          machine_id = :machine_id,
                          assigned_to = :assigned_to,
                          scheduled_start = :scheduled_start,
                          scheduled_end = :scheduled_end,
                          job_notes = :job_notes,
                          status = :status,
                          priority = :priority
                          WHERE id = :job_id");
    
    $stmt->execute([
        ':job_number' => $data['job_number'],
        ':order_id' => $data['order_id'],
        ':order_item_id' => $data['order_item_id'],
        ':quantity' => $data['quantity'],
        ':department_id' => $data['department_id'],
        ':production_stage_id' => $data['production_stage_id'] ?? null,
        ':machine_id' => $data['machine_id'] ?? null,
        ':assigned_to' => $data['assigned_to'] ?? null,
        ':scheduled_start' => $data['scheduled_start'],
        ':scheduled_end' => $data['scheduled_end'],
        ':job_notes' => $data['job_notes'] ?? null,
        ':status' => $data['status'],
        ':priority' => $priority,
        ':job_id' => $data['job_id']
    ]);
    
    // Build change log
    $changes = [];
    $fields = [
        'job_number' => 'Job Number',
        'quantity' => 'Quantity',
        'scheduled_start' => 'Start Date',
        'scheduled_end' => 'End Date',
        'status' => 'Status',
        'priority' => 'Priority',
        'job_notes' => 'Notes'
    ];
    
    foreach ($fields as $field => $label) {
        $oldValue = $existingJob[$field] ?? '';
        $newValue = $data[$field] ?? '';
        if ($oldValue != $newValue) {
            $changes[] = "$label changed from '$oldValue' to '$newValue'";
        }
    }
    
    // Log activity only if there are changes
    if (!empty($changes)) {
        logActivity(
            'job_updated',
            'job_schedules',
            $data['job_id'],
            "Updated job {$data['job_number']}: " . implode(', ', $changes)
        );
    }
    
    $pdo->commit();
    
    echo jsonResponse(true, 'Job updated successfully');
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error in jobs/update.php: " . $e->getMessage());
    echo jsonResponse(false, 'Error updating job: ' . $e->getMessage());
}

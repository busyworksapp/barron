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
    
    // Check for duplicate job number
    $stmt = $pdo->prepare("SELECT id FROM job_schedules WHERE job_number = :job_number");
    $stmt->execute([':job_number' => $data['job_number']]);
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
    
    // Verify order exists and is confirmed
    $stmt = $pdo->prepare("SELECT id, status FROM orders WHERE id = :id");
    $stmt->execute([':id' => $data['order_id']]);
    $order = $stmt->fetch();
    
    if (!$order) {
        $pdo->rollBack();
        echo jsonResponse(false, 'Order not found');
        exit;
    }
    
    if ($order['status'] !== 'confirmed' && $order['status'] !== 'in_progress') {
        $pdo->rollBack();
        echo jsonResponse(false, 'Order must be confirmed before scheduling jobs');
        exit;
    }
    
    // Verify order item exists and belongs to order
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
    
    // Validate quantity doesn't exceed order item quantity
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity), 0) as scheduled_qty 
                          FROM job_schedules 
                          WHERE order_item_id = :order_item_id 
                          AND status NOT IN ('cancelled')");
    $stmt->execute([':order_item_id' => $data['order_item_id']]);
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
    
    // Insert job
    $stmt = $pdo->prepare("INSERT INTO job_schedules 
                          (job_number, order_id, order_item_id, quantity, department_id, 
                           production_stage_id, machine_id, assigned_to, scheduled_start, 
                           scheduled_end, job_notes, status, priority, created_by) 
                          VALUES 
                          (:job_number, :order_id, :order_item_id, :quantity, :department_id,
                           :production_stage_id, :machine_id, :assigned_to, :scheduled_start,
                           :scheduled_end, :job_notes, :status, :priority, :created_by)");
    
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
        ':created_by' => getCurrentUser()['id']
    ]);
    
    $jobId = $pdo->lastInsertId();
    
    // Log activity
    logActivity(
        'job_created',
        'job_schedules',
        $jobId,
        "Created job {$data['job_number']} for order item {$orderItem['product_code']} - {$orderItem['product_name']}"
    );
    
    $pdo->commit();
    
    echo jsonResponse(true, 'Job created successfully', ['job_id' => $jobId]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error in jobs/create.php: " . $e->getMessage());
    echo jsonResponse(false, 'Error creating job: ' . $e->getMessage());
}

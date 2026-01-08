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
    $required = ['ticket_number', 'machine_id', 'maintenance_type', 'priority', 
                 'issue_description', 'status'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            echo jsonResponse(false, ucfirst(str_replace('_', ' ', $field)) . ' is required');
            exit;
        }
    }
    
    // Validate maintenance type
    $validTypes = ['breakdown', 'preventive', 'inspection', 'calibration'];
    if (!in_array($data['maintenance_type'], $validTypes)) {
        echo jsonResponse(false, 'Invalid maintenance type');
        exit;
    }
    
    // Validate priority
    $validPriorities = ['low', 'normal', 'high', 'urgent'];
    if (!in_array($data['priority'], $validPriorities)) {
        echo jsonResponse(false, 'Invalid priority');
        exit;
    }
    
    // Validate status
    $validStatuses = ['open', 'assigned', 'in_progress', 'on_hold', 'completed', 'closed'];
    if (!in_array($data['status'], $validStatuses)) {
        echo jsonResponse(false, 'Invalid status');
        exit;
    }
    
    // Check for duplicate ticket number
    $stmt = $pdo->prepare("SELECT id FROM maintenance_tickets WHERE ticket_number = :ticket_number");
    $stmt->execute([':ticket_number' => $data['ticket_number']]);
    if ($stmt->fetch()) {
        echo jsonResponse(false, 'Ticket number already exists');
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
    
    // Insert ticket
    $stmt = $pdo->prepare("INSERT INTO maintenance_tickets 
                          (ticket_number, machine_id, maintenance_type, priority, 
                           issue_description, work_performed, assigned_to, status,
                           scheduled_date, completed_date, downtime_hours, cost,
                           parts_used, notes, created_by) 
                          VALUES 
                          (:ticket_number, :machine_id, :maintenance_type, :priority,
                           :issue_description, :work_performed, :assigned_to, :status,
                           :scheduled_date, :completed_date, :downtime_hours, :cost,
                           :parts_used, :notes, :created_by)");
    
    $stmt->execute([
        ':ticket_number' => $data['ticket_number'],
        ':machine_id' => $data['machine_id'],
        ':maintenance_type' => $data['maintenance_type'],
        ':priority' => $data['priority'],
        ':issue_description' => $data['issue_description'],
        ':work_performed' => $data['work_performed'] ?? null,
        ':assigned_to' => $data['assigned_to'] ?? null,
        ':status' => $data['status'],
        ':scheduled_date' => $data['scheduled_date'] ?? null,
        ':completed_date' => $data['completed_date'] ?? null,
        ':downtime_hours' => $data['downtime_hours'] ?? null,
        ':cost' => $data['cost'] ?? null,
        ':parts_used' => $data['parts_used'] ?? null,
        ':notes' => $data['notes'] ?? null,
        ':created_by' => getCurrentUser()['id']
    ]);
    
    $ticketId = $pdo->lastInsertId();
    
    // Update machine status if breakdown
    if ($data['maintenance_type'] === 'breakdown' && $data['status'] !== 'completed') {
        $stmt = $pdo->prepare("UPDATE machines SET status = 'down' WHERE id = :id");
        $stmt->execute([':id' => $data['machine_id']]);
    }
    
    // Log activity
    logActivity(
        'maintenance_ticket_created',
        'maintenance_tickets',
        $ticketId,
        "Created maintenance ticket {$data['ticket_number']} for {$machine['machine_name']} - Priority: {$data['priority']}"
    );
    
    $pdo->commit();
    
    echo jsonResponse(true, 'Ticket created successfully', ['ticket_id' => $ticketId]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error in maintenance/tickets/create.php: " . $e->getMessage());
    echo jsonResponse(false, 'Error creating ticket: ' . $e->getMessage());
}

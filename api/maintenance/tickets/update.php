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
    
    // Validate ticket_id
    if (empty($data['ticket_id'])) {
        echo jsonResponse(false, 'Ticket ID is required');
        exit;
    }
    
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
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // Get existing ticket
    $stmt = $pdo->prepare("SELECT * FROM maintenance_tickets WHERE id = :id");
    $stmt->execute([':id' => $data['ticket_id']]);
    $existingTicket = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existingTicket) {
        $pdo->rollBack();
        echo jsonResponse(false, 'Ticket not found');
        exit;
    }
    
    // Check for duplicate ticket number (excluding current)
    $stmt = $pdo->prepare("SELECT id FROM maintenance_tickets 
                          WHERE ticket_number = :ticket_number AND id != :ticket_id");
    $stmt->execute([
        ':ticket_number' => $data['ticket_number'],
        ':ticket_id' => $data['ticket_id']
    ]);
    if ($stmt->fetch()) {
        $pdo->rollBack();
        echo jsonResponse(false, 'Ticket number already exists');
        exit;
    }
    
    // Update ticket
    $stmt = $pdo->prepare("UPDATE maintenance_tickets SET
                          ticket_number = :ticket_number,
                          machine_id = :machine_id,
                          maintenance_type = :maintenance_type,
                          priority = :priority,
                          issue_description = :issue_description,
                          work_performed = :work_performed,
                          assigned_to = :assigned_to,
                          status = :status,
                          scheduled_date = :scheduled_date,
                          completed_date = :completed_date,
                          downtime_hours = :downtime_hours,
                          cost = :cost,
                          parts_used = :parts_used,
                          notes = :notes
                          WHERE id = :ticket_id");
    
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
        ':ticket_id' => $data['ticket_id']
    ]);
    
    // Update machine status if completed
    if ($data['status'] === 'completed' && $existingTicket['status'] !== 'completed') {
        $stmt = $pdo->prepare("UPDATE machines SET status = 'available' WHERE id = :id");
        $stmt->execute([':id' => $data['machine_id']]);
    }
    
    // Log activity
    logActivity(
        'maintenance_ticket_updated',
        'maintenance_tickets',
        $data['ticket_id'],
        "Updated maintenance ticket {$data['ticket_number']}"
    );
    
    $pdo->commit();
    
    echo jsonResponse(true, 'Ticket updated successfully');
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error in maintenance/tickets/update.php: " . $e->getMessage());
    echo jsonResponse(false, 'Error updating ticket: ' . $e->getMessage());
}

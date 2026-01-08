<?php
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';

header('Content-Type: application/json');

$auth = new Auth();
$auth->requireLogin();

if (!hasPermission('sop.edit')) {
    echo jsonResponse(false, 'Permission denied');
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $required = ['ticket_number', 'sop_reference', 'department_id', 'severity',
                 'failure_description', 'incident_date', 'status'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            echo jsonResponse(false, ucfirst(str_replace('_', ' ', $field)) . ' is required');
            exit;
        }
    }
    
    // Validate severity
    $validSeverities = ['low', 'medium', 'high', 'critical'];
    if (!in_array($data['severity'], $validSeverities)) {
        echo jsonResponse(false, 'Invalid severity level');
        exit;
    }
    
    // Validate status
    $validStatuses = ['open', 'investigating', 'action_required', 'resolved', 'closed'];
    if (!in_array($data['status'], $validStatuses)) {
        echo jsonResponse(false, 'Invalid status');
        exit;
    }
    
    // Check for duplicate ticket number
    $stmt = $pdo->prepare("SELECT id FROM sop_failures WHERE ticket_number = :ticket_number");
    $stmt->execute([':ticket_number' => $data['ticket_number']]);
    if ($stmt->fetch()) {
        echo jsonResponse(false, 'Ticket number already exists');
        exit;
    }
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // Verify department exists
    $stmt = $pdo->prepare("SELECT id, name FROM departments WHERE id = :id");
    $stmt->execute([':id' => $data['department_id']]);
    $department = $stmt->fetch();
    
    if (!$department) {
        $pdo->rollBack();
        echo jsonResponse(false, 'Department not found');
        exit;
    }
    
    // Insert ticket
    $stmt = $pdo->prepare("INSERT INTO sop_failures 
                          (ticket_number, sop_reference, department_id, severity, 
                           failure_description, immediate_action, incident_date, status,
                           root_cause, corrective_action, assigned_to, target_closure_date, reported_by) 
                          VALUES 
                          (:ticket_number, :sop_reference, :department_id, :severity,
                           :failure_description, :immediate_action, :incident_date, :status,
                           :root_cause, :corrective_action, :assigned_to, :target_closure_date, :reported_by)");
    
    $stmt->execute([
        ':ticket_number' => $data['ticket_number'],
        ':sop_reference' => $data['sop_reference'],
        ':department_id' => $data['department_id'],
        ':severity' => $data['severity'],
        ':failure_description' => $data['failure_description'],
        ':immediate_action' => $data['immediate_action'] ?? null,
        ':incident_date' => $data['incident_date'],
        ':status' => $data['status'],
        ':root_cause' => $data['root_cause'] ?? null,
        ':corrective_action' => $data['corrective_action'] ?? null,
        ':assigned_to' => $data['assigned_to'] ?? null,
        ':target_closure_date' => $data['target_closure_date'] ?? null,
        ':reported_by' => getCurrentUser()['id']
    ]);
    
    $ticketId = $pdo->lastInsertId();
    
    // Set closed_by and closed_date if status is closed
    if ($data['status'] === 'closed') {
        $stmt = $pdo->prepare("UPDATE sop_failures 
                              SET closed_by = :closed_by, closed_date = NOW()
                              WHERE id = :id");
        $stmt->execute([
            ':closed_by' => getCurrentUser()['id'],
            ':id' => $ticketId
        ]);
    }
    
    // Log activity
    logActivity(
        'sop_failure_created',
        'sop_failures',
        $ticketId,
        "Created SOP failure ticket {$data['ticket_number']}: {$data['sop_reference']} - Severity: {$data['severity']}"
    );
    
    $pdo->commit();
    
    echo jsonResponse(true, 'Ticket created successfully', ['ticket_id' => $ticketId]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error in tickets/create.php: " . $e->getMessage());
    echo jsonResponse(false, 'Error creating ticket: ' . $e->getMessage());
}

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
    
    // Validate ticket_id
    if (empty($data['ticket_id'])) {
        echo jsonResponse(false, 'Ticket ID is required');
        exit;
    }
    
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
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // Get existing ticket
    $stmt = $pdo->prepare("SELECT * FROM sop_failures WHERE id = :id");
    $stmt->execute([':id' => $data['ticket_id']]);
    $existingTicket = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existingTicket) {
        $pdo->rollBack();
        echo jsonResponse(false, 'Ticket not found');
        exit;
    }
    
    // Check for duplicate ticket number (excluding current)
    $stmt = $pdo->prepare("SELECT id FROM sop_failures 
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
    $stmt = $pdo->prepare("UPDATE sop_failures SET
                          ticket_number = :ticket_number,
                          sop_reference = :sop_reference,
                          department_id = :department_id,
                          severity = :severity,
                          failure_description = :failure_description,
                          immediate_action = :immediate_action,
                          incident_date = :incident_date,
                          status = :status,
                          root_cause = :root_cause,
                          corrective_action = :corrective_action,
                          assigned_to = :assigned_to,
                          target_closure_date = :target_closure_date
                          WHERE id = :ticket_id");
    
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
        ':ticket_id' => $data['ticket_id']
    ]);
    
    // Set closed_by and closed_date if status changed to closed
    if ($data['status'] === 'closed' && $existingTicket['status'] !== 'closed') {
        $stmt = $pdo->prepare("UPDATE sop_failures 
                              SET closed_by = :closed_by, closed_date = NOW()
                              WHERE id = :id");
        $stmt->execute([
            ':closed_by' => getCurrentUser()['id'],
            ':id' => $data['ticket_id']
        ]);
    }
    
    // Log activity
    logActivity(
        'sop_failure_updated',
        'sop_failures',
        $data['ticket_id'],
        "Updated SOP failure ticket {$data['ticket_number']}"
    );
    
    $pdo->commit();
    
    echo jsonResponse(true, 'Ticket updated successfully');
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error in tickets/update.php: " . $e->getMessage());
    echo jsonResponse(false, 'Error updating ticket: ' . $e->getMessage());
}

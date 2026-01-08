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
    
    // Validate ncr_id
    if (empty($data['ncr_id'])) {
        echo jsonResponse(false, 'NCR ID is required');
        exit;
    }
    
    // Validate required fields
    $required = ['ncr_number', 'ncr_type', 'department_id', 'date_raised', 'description', 'status'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            echo jsonResponse(false, ucfirst(str_replace('_', ' ', $field)) . ' is required');
            exit;
        }
    }
    
    // Validate NCR type
    $validTypes = ['internal', 'supplier', 'customer'];
    if (!in_array($data['ncr_type'], $validTypes)) {
        echo jsonResponse(false, 'Invalid NCR type');
        exit;
    }
    
    // Validate status
    $validStatuses = ['open', 'investigation', 'capa_pending', 'capa_in_progress', 'verification', 'closed'];
    if (!in_array($data['status'], $validStatuses)) {
        echo jsonResponse(false, 'Invalid status');
        exit;
    }
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // Get existing NCR
    $stmt = $pdo->prepare("SELECT * FROM ncr_reports WHERE id = :id");
    $stmt->execute([':id' => $data['ncr_id']]);
    $existingNCR = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existingNCR) {
        $pdo->rollBack();
        echo jsonResponse(false, 'NCR not found');
        exit;
    }
    
    // Check for duplicate NCR number (excluding current)
    $stmt = $pdo->prepare("SELECT id FROM ncr_reports 
                          WHERE ncr_number = :ncr_number AND id != :ncr_id");
    $stmt->execute([
        ':ncr_number' => $data['ncr_number'],
        ':ncr_id' => $data['ncr_id']
    ]);
    if ($stmt->fetch()) {
        $pdo->rollBack();
        echo jsonResponse(false, 'NCR number already exists');
        exit;
    }
    
    // Update NCR
    $stmt = $pdo->prepare("UPDATE ncr_reports SET
                          ncr_number = :ncr_number,
                          ncr_type = :ncr_type,
                          department_id = :department_id,
                          date_raised = :date_raised,
                          description = :description,
                          immediate_action = :immediate_action,
                          root_cause = :root_cause,
                          corrective_action = :corrective_action,
                          preventive_action = :preventive_action,
                          assigned_to = :assigned_to,
                          target_closure_date = :target_closure_date,
                          status = :status,
                          verification_notes = :verification_notes
                          WHERE id = :ncr_id");
    
    $stmt->execute([
        ':ncr_number' => $data['ncr_number'],
        ':ncr_type' => $data['ncr_type'],
        ':department_id' => $data['department_id'],
        ':date_raised' => $data['date_raised'],
        ':description' => $data['description'],
        ':immediate_action' => $data['immediate_action'] ?? null,
        ':root_cause' => $data['root_cause'] ?? null,
        ':corrective_action' => $data['corrective_action'] ?? null,
        ':preventive_action' => $data['preventive_action'] ?? null,
        ':assigned_to' => $data['assigned_to'] ?? null,
        ':target_closure_date' => $data['target_closure_date'] ?? null,
        ':status' => $data['status'],
        ':verification_notes' => $data['verification_notes'] ?? null,
        ':ncr_id' => $data['ncr_id']
    ]);
    
    // Set closed_by and closed_date if status changed to closed
    if ($data['status'] === 'closed' && $existingNCR['status'] !== 'closed') {
        $stmt = $pdo->prepare("UPDATE ncr_reports 
                              SET closed_by = :closed_by, closed_date = NOW()
                              WHERE id = :id");
        $stmt->execute([
            ':closed_by' => getCurrentUser()['id'],
            ':id' => $data['ncr_id']
        ]);
    }
    
    // Log activity
    logActivity(
        'ncr_updated',
        'ncr_reports',
        $data['ncr_id'],
        "Updated NCR {$data['ncr_number']}"
    );
    
    $pdo->commit();
    
    echo jsonResponse(true, 'NCR updated successfully');
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error in ncr/update.php: " . $e->getMessage());
    echo jsonResponse(false, 'Error updating NCR: ' . $e->getMessage());
}

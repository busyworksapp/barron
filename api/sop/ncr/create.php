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
    
    // Check for duplicate NCR number
    $stmt = $pdo->prepare("SELECT id FROM ncr_reports WHERE ncr_number = :ncr_number");
    $stmt->execute([':ncr_number' => $data['ncr_number']]);
    if ($stmt->fetch()) {
        echo jsonResponse(false, 'NCR number already exists');
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
    
    // Insert NCR
    $stmt = $pdo->prepare("INSERT INTO ncr_reports 
                          (ncr_number, ncr_type, department_id, date_raised, description,
                           immediate_action, root_cause, corrective_action, preventive_action,
                           assigned_to, target_closure_date, status, verification_notes, raised_by) 
                          VALUES 
                          (:ncr_number, :ncr_type, :department_id, :date_raised, :description,
                           :immediate_action, :root_cause, :corrective_action, :preventive_action,
                           :assigned_to, :target_closure_date, :status, :verification_notes, :raised_by)");
    
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
        ':raised_by' => getCurrentUser()['id']
    ]);
    
    $ncrId = $pdo->lastInsertId();
    
    // Set closed_by and closed_date if status is closed
    if ($data['status'] === 'closed') {
        $stmt = $pdo->prepare("UPDATE ncr_reports 
                              SET closed_by = :closed_by, closed_date = NOW()
                              WHERE id = :id");
        $stmt->execute([
            ':closed_by' => getCurrentUser()['id'],
            ':id' => $ncrId
        ]);
    }
    
    // Log activity
    logActivity(
        'ncr_created',
        'ncr_reports',
        $ncrId,
        "Created NCR {$data['ncr_number']}: {$data['ncr_type']} - {$data['description']}"
    );
    
    $pdo->commit();
    
    echo jsonResponse(true, 'NCR created successfully', ['ncr_id' => $ncrId]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error in ncr/create.php: " . $e->getMessage());
    echo jsonResponse(false, 'Error creating NCR: ' . $e->getMessage());
}

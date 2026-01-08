<?php
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';

header('Content-Type: application/json');

$auth = new Auth();
$auth->requireLogin();

if (!hasPermission('defects.edit')) {
    echo jsonResponse(false, 'Permission denied');
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate reject_id
    if (empty($data['reject_id'])) {
        echo jsonResponse(false, 'Reject ID is required');
        exit;
    }
    
    // Validate required fields
    $required = ['reject_number', 'job_id', 'quantity_rejected', 'defect_type',
                 'defect_description', 'reject_date', 'disposition'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            echo jsonResponse(false, ucfirst(str_replace('_', ' ', $field)) . ' is required');
            exit;
        }
    }
    
    // Validate defect type
    $validDefectTypes = ['material_defect', 'workmanship', 'machine_error', 'measurement_error',
                        'color_mismatch', 'contamination', 'incomplete', 'damaged', 'other'];
    if (!in_array($data['defect_type'], $validDefectTypes)) {
        echo jsonResponse(false, 'Invalid defect type');
        exit;
    }
    
    // Validate disposition
    $validDispositions = ['scrap', 'rework', 'use_as_is', 'return_supplier'];
    if (!in_array($data['disposition'], $validDispositions)) {
        echo jsonResponse(false, 'Invalid disposition');
        exit;
    }
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // Get existing reject
    $stmt = $pdo->prepare("SELECT * FROM internal_rejects WHERE id = :id");
    $stmt->execute([':id' => $data['reject_id']]);
    $existingReject = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existingReject) {
        $pdo->rollBack();
        echo jsonResponse(false, 'Reject not found');
        exit;
    }
    
    // Only allow editing if status is pending
    if ($existingReject['status'] !== 'pending') {
        $pdo->rollBack();
        echo jsonResponse(false, 'Cannot edit reject that has been approved or rejected');
        exit;
    }
    
    // Check for duplicate reject number (excluding current)
    $stmt = $pdo->prepare("SELECT id FROM internal_rejects 
                          WHERE reject_number = :reject_number AND id != :reject_id");
    $stmt->execute([
        ':reject_number' => $data['reject_number'],
        ':reject_id' => $data['reject_id']
    ]);
    if ($stmt->fetch()) {
        $pdo->rollBack();
        echo jsonResponse(false, 'Reject number already exists');
        exit;
    }
    
    // Update reject
    $stmt = $pdo->prepare("UPDATE internal_rejects SET
                          reject_number = :reject_number,
                          job_id = :job_id,
                          quantity_rejected = :quantity_rejected,
                          defect_type = :defect_type,
                          defect_description = :defect_description,
                          root_cause = :root_cause,
                          disposition = :disposition,
                          reject_date = :reject_date
                          WHERE id = :reject_id");
    
    $stmt->execute([
        ':reject_number' => $data['reject_number'],
        ':job_id' => $data['job_id'],
        ':quantity_rejected' => $data['quantity_rejected'],
        ':defect_type' => $data['defect_type'],
        ':defect_description' => $data['defect_description'],
        ':root_cause' => $data['root_cause'] ?? null,
        ':disposition' => $data['disposition'],
        ':reject_date' => $data['reject_date'],
        ':reject_id' => $data['reject_id']
    ]);
    
    // Log activity
    logActivity(
        'reject_updated',
        'internal_rejects',
        $data['reject_id'],
        "Updated internal reject {$data['reject_number']}"
    );
    
    $pdo->commit();
    
    echo jsonResponse(true, 'Reject updated successfully');
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error in rejects/update.php: " . $e->getMessage());
    echo jsonResponse(false, 'Error updating reject: ' . $e->getMessage());
}

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
    
    // Check for duplicate reject number
    $stmt = $pdo->prepare("SELECT id FROM internal_rejects WHERE reject_number = :reject_number");
    $stmt->execute([':reject_number' => $data['reject_number']]);
    if ($stmt->fetch()) {
        echo jsonResponse(false, 'Reject number already exists');
        exit;
    }
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // Verify job exists
    $stmt = $pdo->prepare("SELECT id, job_number FROM job_schedules WHERE id = :id");
    $stmt->execute([':id' => $data['job_id']]);
    $job = $stmt->fetch();
    
    if (!$job) {
        $pdo->rollBack();
        echo jsonResponse(false, 'Job not found');
        exit;
    }
    
    // Validate quantity against produced quantity
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity_produced), 0) as produced 
                          FROM job_production_log 
                          WHERE job_id = :job_id");
    $stmt->execute([':job_id' => $data['job_id']]);
    $produced = $stmt->fetch()['produced'];
    
    if ($data['quantity_rejected'] > $produced) {
        $pdo->rollBack();
        echo jsonResponse(false, "Quantity rejected ($data[quantity_rejected]) cannot exceed produced quantity ($produced)");
        exit;
    }
    
    // Insert reject
    $stmt = $pdo->prepare("INSERT INTO internal_rejects 
                          (reject_number, job_id, quantity_rejected, defect_type, 
                           defect_description, root_cause, disposition, reject_date, 
                           status, reported_by) 
                          VALUES 
                          (:reject_number, :job_id, :quantity_rejected, :defect_type,
                           :defect_description, :root_cause, :disposition, :reject_date,
                           'pending', :reported_by)");
    
    $stmt->execute([
        ':reject_number' => $data['reject_number'],
        ':job_id' => $data['job_id'],
        ':quantity_rejected' => $data['quantity_rejected'],
        ':defect_type' => $data['defect_type'],
        ':defect_description' => $data['defect_description'],
        ':root_cause' => $data['root_cause'] ?? null,
        ':disposition' => $data['disposition'],
        ':reject_date' => $data['reject_date'],
        ':reported_by' => getCurrentUser()['id']
    ]);
    
    $rejectId = $pdo->lastInsertId();
    
    // Log activity
    logActivity(
        'reject_created',
        'internal_rejects',
        $rejectId,
        "Created internal reject {$data['reject_number']} for job {$job['job_number']}: {$data['quantity_rejected']} units - {$data['defect_type']}"
    );
    
    $pdo->commit();
    
    echo jsonResponse(true, 'Reject logged successfully', ['reject_id' => $rejectId]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error in rejects/create.php: " . $e->getMessage());
    echo jsonResponse(false, 'Error creating reject: ' . $e->getMessage());
}

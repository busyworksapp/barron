<?php
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';

header('Content-Type: application/json');

$auth = new Auth();
$auth->requireLogin();

if (!hasPermission('defects.approve')) {
    echo jsonResponse(false, 'Permission denied');
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (empty($data['reject_id'])) {
        echo jsonResponse(false, 'Reject ID is required');
        exit;
    }
    
    if (empty($data['decision'])) {
        echo jsonResponse(false, 'Decision is required');
        exit;
    }
    
    if (empty($data['notes'])) {
        echo jsonResponse(false, 'Notes are required');
        exit;
    }
    
    // Validate decision
    $validDecisions = ['approved', 'rejected'];
    if (!in_array($data['decision'], $validDecisions)) {
        echo jsonResponse(false, 'Invalid decision');
        exit;
    }
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // Get existing reject
    $stmt = $pdo->prepare("SELECT ir.*, js.job_number 
                          FROM internal_rejects ir
                          INNER JOIN job_schedules js ON ir.job_id = js.id
                          WHERE ir.id = :id");
    $stmt->execute([':id' => $data['reject_id']]);
    $reject = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reject) {
        $pdo->rollBack();
        echo jsonResponse(false, 'Reject not found');
        exit;
    }
    
    // Only allow approval if status is pending
    if ($reject['status'] !== 'pending') {
        $pdo->rollBack();
        echo jsonResponse(false, 'Reject has already been processed');
        exit;
    }
    
    // Update reject status
    $stmt = $pdo->prepare("UPDATE internal_rejects SET
                          status = :status,
                          approved_by = :approved_by,
                          approval_date = NOW(),
                          approval_notes = :approval_notes
                          WHERE id = :reject_id");
    
    $stmt->execute([
        ':status' => $data['decision'],
        ':approved_by' => getCurrentUser()['id'],
        ':approval_notes' => $data['notes'],
        ':reject_id' => $data['reject_id']
    ]);
    
    // Log activity
    $actionText = $data['decision'] === 'approved' ? 'approved' : 'rejected';
    logActivity(
        'reject_' . $actionText,
        'internal_rejects',
        $data['reject_id'],
        "Internal reject {$reject['reject_number']} for job {$reject['job_number']} was {$actionText}"
    );
    
    $pdo->commit();
    
    echo jsonResponse(true, 'Decision submitted successfully');
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error in rejects/approve.php: " . $e->getMessage());
    echo jsonResponse(false, 'Error submitting decision: ' . $e->getMessage());
}

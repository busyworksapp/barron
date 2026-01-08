<?php
/**
 * Update Department API
 */

session_start();

require_once '../../../config/config.php';
require_once '../../../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Method not allowed', 405);
}

try {
    requireLogin();
    requirePermission('master.edit');
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['id'])) {
        throw new Exception('Department ID is required');
    }
    
    $database = new Database();
    $conn = $database->getConnection();
    
    // Get old values for audit
    $query = "SELECT * FROM departments WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => (int)$input['id']]);
    $old_values = $stmt->fetch();
    
    if (!$old_values) {
        throw new Exception('Department not found');
    }
    
    // Check if code already exists (excluding current department)
    if (isset($input['department_code'])) {
        $query = "SELECT id FROM departments WHERE department_code = :code AND id != :id";
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':code' => sanitize($input['department_code']),
            ':id' => (int)$input['id']
        ]);
        
        if ($stmt->rowCount() > 0) {
            throw new Exception('Department code already exists');
        }
    }
    
    // Update department
    $query = "UPDATE departments SET
                department_code = :department_code,
                department_name = :department_name,
                daily_target = :daily_target,
                weekly_target = :weekly_target,
                monthly_target = :monthly_target,
                is_active = :is_active,
                updated_by = :updated_by
              WHERE id = :id";
    
    $stmt = $conn->prepare($query);
    $result = $stmt->execute([
        ':department_code' => sanitize($input['department_code']),
        ':department_name' => sanitize($input['department_name']),
        ':daily_target' => $input['daily_target'] ?? 0,
        ':weekly_target' => $input['weekly_target'] ?? 0,
        ':monthly_target' => $input['monthly_target'] ?? 0,
        ':is_active' => $input['is_active'] ?? 1,
        ':updated_by' => getCurrentUserId(),
        ':id' => (int)$input['id']
    ]);
    
    // Log activity
    logActivity('update', 'departments', $input['id'], $old_values, $input);
    
    successResponse('Department updated successfully');
    
} catch (Exception $e) {
    errorResponse($e->getMessage());
}

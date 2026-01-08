<?php
/**
 * Create Department API
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
    requirePermission('master.create');
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $required = ['department_code', 'department_name'];
    $missing = validateRequired($input, $required);
    
    if (!empty($missing)) {
        throw new Exception('Missing required fields: ' . implode(', ', $missing));
    }
    
    $database = new Database();
    $conn = $database->getConnection();
    
    // Check if code already exists
    $query = "SELECT id FROM departments WHERE department_code = :code";
    $stmt = $conn->prepare($query);
    $stmt->execute([':code' => sanitize($input['department_code'])]);
    
    if ($stmt->rowCount() > 0) {
        throw new Exception('Department code already exists');
    }
    
    // Insert department
    $query = "INSERT INTO departments (
                department_code,
                department_name,
                daily_target,
                weekly_target,
                monthly_target,
                is_active,
                created_by
              ) VALUES (
                :department_code,
                :department_name,
                :daily_target,
                :weekly_target,
                :monthly_target,
                :is_active,
                :created_by
              )";
    
    $stmt = $conn->prepare($query);
    $result = $stmt->execute([
        ':department_code' => sanitize($input['department_code']),
        ':department_name' => sanitize($input['department_name']),
        ':daily_target' => $input['daily_target'] ?? 0,
        ':weekly_target' => $input['weekly_target'] ?? 0,
        ':monthly_target' => $input['monthly_target'] ?? 0,
        ':is_active' => $input['is_active'] ?? 1,
        ':created_by' => getCurrentUserId()
    ]);
    
    $department_id = $conn->lastInsertId();
    
    // Log activity
    logActivity('insert', 'departments', $department_id, null, $input);
    
    successResponse('Department created successfully', ['id' => $department_id]);
    
} catch (Exception $e) {
    errorResponse($e->getMessage());
}

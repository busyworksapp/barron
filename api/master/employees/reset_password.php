<?php
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isAuthenticated()) {
    echo jsonResponse(false, 'Unauthorized');
    exit;
}

if (!hasPermission('master.edit')) {
    echo jsonResponse(false, 'Insufficient permissions');
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['employee_id'])) {
    echo jsonResponse(false, 'Employee ID is required');
    exit;
}

if (!isset($data['new_password']) || empty($data['new_password'])) {
    echo jsonResponse(false, 'New password is required');
    exit;
}

if (strlen($data['new_password']) < 6) {
    echo jsonResponse(false, 'Password must be at least 6 characters');
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Get employee details
    $stmt = $db->prepare("SELECT id, first_name, last_name, employee_number FROM employees WHERE id = ?");
    $stmt->execute([$data['employee_id']]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$employee) {
        echo jsonResponse(false, 'Employee not found');
        exit;
    }
    
    // Hash new password
    $hashedPassword = password_hash($data['new_password'], PASSWORD_BCRYPT);
    
    // Update password
    $stmt = $db->prepare("UPDATE employees SET password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$hashedPassword, $data['employee_id']]);
    
    // Log activity
    logActivity('employee_password_reset', 'employees', $data['employee_id'], 
        "Password reset for employee: {$employee['first_name']} {$employee['last_name']} ({$employee['employee_number']})");
    
    echo jsonResponse(true, 'Password reset successfully');
    
} catch (Exception $e) {
    error_log('Error in employees/reset_password.php: ' . $e->getMessage());
    echo jsonResponse(false, 'Error resetting password: ' . $e->getMessage());
}

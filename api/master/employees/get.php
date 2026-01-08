<?php
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isAuthenticated()) {
    echo jsonResponse(false, 'Unauthorized');
    exit;
}

if (!hasPermission('master.view')) {
    echo jsonResponse(false, 'Insufficient permissions');
    exit;
}

if (!isset($_GET['id'])) {
    echo jsonResponse(false, 'Employee ID is required');
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Get employee details
    $stmt = $db->prepare("
        SELECT e.*, 
        d.department_name,
        r.role_name
        FROM employees e
        LEFT JOIN departments d ON e.primary_department_id = d.id
        LEFT JOIN roles r ON e.role_id = r.id
        WHERE e.id = ?
    ");
    $stmt->execute([$_GET['id']]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$employee) {
        echo jsonResponse(false, 'Employee not found');
        exit;
    }
    
    // Get additional departments
    $stmt = $db->prepare("
        SELECT department_id 
        FROM employee_departments 
        WHERE employee_id = ?
    ");
    $stmt->execute([$_GET['id']]);
    $depts = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $employee['additional_departments'] = $depts;
    
    // Remove password from response
    unset($employee['password']);
    
    echo jsonResponse(true, 'Employee retrieved successfully', $employee);
    
} catch (Exception $e) {
    error_log('Error in employees/get.php: ' . $e->getMessage());
    echo jsonResponse(false, 'Error retrieving employee: ' . $e->getMessage());
}

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

if (!isset($_GET['employee_id'])) {
    echo jsonResponse(false, 'Employee ID is required');
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Get departments assigned to employee
    $stmt = $db->prepare("
        SELECT ed.*, d.department_name, d.department_code
        FROM employee_departments ed
        JOIN departments d ON ed.department_id = d.id
        WHERE ed.employee_id = ?
        ORDER BY d.department_name
    ");
    $stmt->execute([$_GET['employee_id']]);
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo jsonResponse(true, 'Departments retrieved successfully', $departments);
    
} catch (Exception $e) {
    error_log('Error in employees/departments.php: ' . $e->getMessage());
    echo jsonResponse(false, 'Error retrieving departments: ' . $e->getMessage());
}

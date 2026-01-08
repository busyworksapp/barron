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

if (!isset($data['departments']) || !is_array($data['departments'])) {
    echo jsonResponse(false, 'Departments array is required');
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Verify employee exists
    $stmt = $db->prepare("SELECT id, first_name, last_name FROM employees WHERE id = ?");
    $stmt->execute([$data['employee_id']]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$employee) {
        echo jsonResponse(false, 'Employee not found');
        exit;
    }
    
    $db->beginTransaction();
    
    // Get current departments
    $stmt = $db->prepare("SELECT department_id FROM employee_departments WHERE employee_id = ?");
    $stmt->execute([$data['employee_id']]);
    $currentDepts = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Delete all existing assignments
    $stmt = $db->prepare("DELETE FROM employee_departments WHERE employee_id = ?");
    $stmt->execute([$data['employee_id']]);
    
    // Insert new assignments
    if (!empty($data['departments'])) {
        $stmt = $db->prepare("INSERT INTO employee_departments (employee_id, department_id) VALUES (?, ?)");
        foreach ($data['departments'] as $deptId) {
            $stmt->execute([$data['employee_id'], $deptId]);
        }
    }
    
    // Log activity
    $added = array_diff($data['departments'], $currentDepts);
    $removed = array_diff($currentDepts, $data['departments']);
    
    if (!empty($added) || !empty($removed)) {
        $changes = [];
        if (!empty($added)) {
            $changes[] = "Added to departments: " . implode(', ', $added);
        }
        if (!empty($removed)) {
            $changes[] = "Removed from departments: " . implode(', ', $removed);
        }
        
        logActivity('employee_departments_updated', 'employees', $data['employee_id'], 
            "Updated departments for {$employee['first_name']} {$employee['last_name']}: " . implode('; ', $changes));
    }
    
    $db->commit();
    
    echo jsonResponse(true, 'Departments updated successfully');
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Error in employees/save_departments.php: ' . $e->getMessage());
    echo jsonResponse(false, 'Error saving departments: ' . $e->getMessage());
}

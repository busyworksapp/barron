<?php
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isAuthenticated()) {
    echo jsonResponse(false, 'Unauthorized');
    exit;
}

if (!hasPermission('master.delete')) {
    echo jsonResponse(false, 'Insufficient permissions');
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['employee_id'])) {
    echo jsonResponse(false, 'Employee ID is required');
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Get employee details before deleting
    $stmt = $db->prepare("SELECT employee_number, first_name, last_name FROM employees WHERE id = ?");
    $stmt->execute([$data['employee_id']]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$employee) {
        echo jsonResponse(false, 'Employee not found');
        exit;
    }
    
    // Check for relationships
    $checks = [
        ['table' => 'job_schedules', 'field' => 'assigned_to', 'message' => 'job schedules'],
        ['table' => 'internal_rejects', 'field' => 'reported_by', 'message' => 'internal reject reports'],
        ['table' => 'customer_returns', 'field' => 'logged_by', 'message' => 'customer return logs'],
        ['table' => 'sop_failures', 'field' => 'reported_by', 'message' => 'SOP failure reports'],
        ['table' => 'maintenance_tickets', 'field' => 'assigned_to', 'message' => 'maintenance tickets'],
        ['table' => 'audit_log', 'field' => 'user_id', 'message' => 'audit log entries']
    ];
    
    foreach ($checks as $check) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM {$check['table']} WHERE {$check['field']} = ?");
        $stmt->execute([$data['employee_id']]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            echo jsonResponse(false, "Cannot delete employee. Associated with {$count} {$check['message']}. Consider marking as inactive instead.");
            exit;
        }
    }
    
    $db->beginTransaction();
    
    // Delete employee departments
    $stmt = $db->prepare("DELETE FROM employee_departments WHERE employee_id = ?");
    $stmt->execute([$data['employee_id']]);
    
    // Delete employee
    $stmt = $db->prepare("DELETE FROM employees WHERE id = ?");
    $stmt->execute([$data['employee_id']]);
    
    // Log activity
    logActivity('employee_deleted', 'employees', $data['employee_id'], 
        "Deleted employee: {$employee['first_name']} {$employee['last_name']} ({$employee['employee_number']})");
    
    $db->commit();
    
    echo jsonResponse(true, 'Employee deleted successfully');
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Error in employees/delete.php: ' . $e->getMessage());
    echo jsonResponse(false, 'Error deleting employee: ' . $e->getMessage());
}

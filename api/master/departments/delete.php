<?php
/**
 * Delete Department API
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
    requirePermission('master.delete');
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['id'])) {
        throw new Exception('Department ID is required');
    }
    
    $database = new Database();
    $conn = $database->getConnection();
    
    // Get department for audit
    $query = "SELECT * FROM departments WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => (int)$input['id']]);
    $department = $stmt->fetch();
    
    if (!$department) {
        throw new Exception('Department not found');
    }
    
    // Check if department has employees
    $query = "SELECT COUNT(*) as count FROM employees WHERE department_id = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => (int)$input['id']]);
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        throw new Exception('Cannot delete department with assigned employees');
    }
    
    // Check if department has orders
    $query = "SELECT COUNT(*) as count FROM job_schedules WHERE department_id = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => (int)$input['id']]);
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        throw new Exception('Cannot delete department with job schedules');
    }
    
    // Delete department (cascade will delete production_stages)
    $query = "DELETE FROM departments WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => (int)$input['id']]);
    
    // Log activity
    logActivity('delete', 'departments', $input['id'], $department, null);
    
    successResponse('Department deleted successfully');
    
} catch (Exception $e) {
    errorResponse($e->getMessage());
}

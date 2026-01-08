<?php
/**
 * Get Single Department API
 */

session_start();

require_once '../../../config/config.php';
require_once '../../../config/database.php';

header('Content-Type: application/json');

try {
    requireLogin();
    requirePermission('master.view');
    
    if (!isset($_GET['id'])) {
        throw new Exception('Department ID is required');
    }
    
    $database = new Database();
    $conn = $database->getConnection();
    
    $query = "SELECT * FROM departments WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => (int)$_GET['id']]);
    
    $department = $stmt->fetch();
    
    if (!$department) {
        throw new Exception('Department not found');
    }
    
    successResponse('Department loaded successfully', $department);
    
} catch (Exception $e) {
    errorResponse($e->getMessage());
}

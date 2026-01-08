<?php
/**
 * Get Department Production Stages API
 */

session_start();

require_once '../../../config/config.php';
require_once '../../../config/database.php';

header('Content-Type: application/json');

try {
    requireLogin();
    requirePermission('master.view');
    
    if (!isset($_GET['department_id'])) {
        throw new Exception('Department ID is required');
    }
    
    $database = new Database();
    $conn = $database->getConnection();
    
    $query = "SELECT * FROM production_stages 
              WHERE department_id = :department_id 
              ORDER BY stage_order ASC, id ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([':department_id' => (int)$_GET['department_id']]);
    
    $stages = $stmt->fetchAll();
    
    successResponse('Production stages loaded successfully', $stages);
    
} catch (Exception $e) {
    errorResponse($e->getMessage());
}

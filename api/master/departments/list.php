<?php
/**
 * List Departments API
 */

session_start();

require_once '../../../config/config.php';
require_once '../../../config/database.php';

header('Content-Type: application/json');

try {
    requireLogin();
    requirePermission('master.view');
    
    $database = new Database();
    $conn = $database->getConnection();
    
    // Get filters
    $search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
    $is_active = isset($_GET['is_active']) ? $_GET['is_active'] : '';
    
    // Build query
    $query = "SELECT 
                d.*,
                (SELECT COUNT(*) FROM production_stages WHERE department_id = d.id) as stages_count,
                u.name as created_by_name
              FROM departments d
              LEFT JOIN users u ON d.created_by = u.id
              WHERE 1=1";
    
    $params = [];
    
    if ($search !== '') {
        $query .= " AND (d.department_code LIKE :search OR d.department_name LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    if ($is_active !== '') {
        $query .= " AND d.is_active = :is_active";
        $params[':is_active'] = $is_active;
    }
    
    $query .= " ORDER BY d.department_name ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    
    $departments = $stmt->fetchAll();
    
    successResponse('Departments loaded successfully', $departments);
    
} catch (Exception $e) {
    errorResponse($e->getMessage());
}

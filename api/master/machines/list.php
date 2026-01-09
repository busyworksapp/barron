<?php
require_once '../../../config/config.php';
require_once '../../../classes/Database.php';

header('Content-Type: application/json');

requireLogin();
// Note: Machine list needed for dropdowns across the system

try {
    $conn = Database::getInstance()->getConnection();
    
    // Build query with filters
    $sql = "SELECT m.*, 
            d.department_name,
            u.name as created_by_name
            FROM machines m
            LEFT JOIN departments d ON m.department_id = d.id
            LEFT JOIN users u ON m.created_by = u.id
            WHERE 1=1";
    
    $params = [];
    
    // Search filter
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = '%' . $_GET['search'] . '%';
        $sql .= " AND (m.machine_code LIKE ? OR m.machine_name LIKE ? OR m.machine_number LIKE ?)";
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
    }
    
    // Department filter
    if (isset($_GET['department_id']) && !empty($_GET['department_id'])) {
        $sql .= " AND m.department_id = ?";
        $params[] = $_GET['department_id'];
    }
    
    // Status filter
    if (isset($_GET['status']) && !empty($_GET['status'])) {
        $sql .= " AND m.status = ?";
        $params[] = $_GET['status'];
    }
    
    $sql .= " ORDER BY m.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $machines = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    successResponse('Machines retrieved successfully', $machines);
    
} catch (Exception $e) {
    error_log('Error in machines/list.php: ' . $e->getMessage());
    errorResponse('Error retrieving machines: ' . $e->getMessage());
}

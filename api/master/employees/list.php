<?php
require_once '../../../config/config.php';
require_once '../../../config/database.php';

header('Content-Type: application/json');

try {
    requireLogin();
    
    $database = new Database();
    $conn = $database->getConnection();
    
    // Build query with filters
    $sql = "SELECT e.*, 
            d.department_name,
            r.role_name,
            u.name as created_by_name
            FROM employees e
            LEFT JOIN departments d ON e.primary_department_id = d.id
            LEFT JOIN roles r ON e.role_id = r.id
            LEFT JOIN users u ON e.created_by = u.id
            WHERE 1=1";
    
    $params = [];
    
    // Search filter
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = '%' . $_GET['search'] . '%';
        $sql .= " AND (e.employee_number LIKE ? OR e.first_name LIKE ? OR e.last_name LIKE ? 
                  OR e.username LIKE ? OR e.email LIKE ?)";
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
    }
    
    // Department filter
    if (isset($_GET['department_id']) && !empty($_GET['department_id'])) {
        $sql .= " AND e.primary_department_id = ?";
        $params[] = $_GET['department_id'];
    }
    
    // Role filter
    if (isset($_GET['role_id']) && !empty($_GET['role_id'])) {
        $sql .= " AND e.role_id = ?";
        $params[] = $_GET['role_id'];
    }
    
    // Status filter
    if (isset($_GET['is_active']) && $_GET['is_active'] !== '') {
        $sql .= " AND e.is_active = ?";
        $params[] = $_GET['is_active'];
    }
    
    $sql .= " ORDER BY e.first_name, e.last_name";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    successResponse('Employees retrieved successfully', $employees);
    
} catch (Exception $e) {
    error_log('Error in employees/list.php: ' . $e->getMessage());
    errorResponse('Error retrieving employees: ' . $e->getMessage());
}

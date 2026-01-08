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

try {
    $db = Database::getInstance()->getConnection();
    
    // Build query with filters
    $sql = "SELECT m.*, 
            d.department_name,
            CONCAT(creator.first_name, ' ', creator.last_name) as created_by_name
            FROM machines m
            LEFT JOIN departments d ON m.department_id = d.id
            LEFT JOIN employees creator ON m.created_by = creator.id
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
    
    $sql .= " ORDER BY m.machine_name";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $machines = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo jsonResponse(true, 'Machines retrieved successfully', $machines);
    
} catch (Exception $e) {
    error_log('Error in machines/list.php: ' . $e->getMessage());
    echo jsonResponse(false, 'Error retrieving machines: ' . $e->getMessage());
}

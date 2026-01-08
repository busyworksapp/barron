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

if (!isset($_GET['id'])) {
    echo jsonResponse(false, 'Machine ID is required');
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("
        SELECT m.*, d.department_name
        FROM machines m
        LEFT JOIN departments d ON m.department_id = d.id
        WHERE m.id = ?
    ");
    $stmt->execute([$_GET['id']]);
    $machine = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$machine) {
        echo jsonResponse(false, 'Machine not found');
        exit;
    }
    
    echo jsonResponse(true, 'Machine retrieved successfully', $machine);
    
} catch (Exception $e) {
    error_log('Error in machines/get.php: ' . $e->getMessage());
    echo jsonResponse(false, 'Error retrieving machine: ' . $e->getMessage());
}

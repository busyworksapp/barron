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
    
    $stmt = $db->query("
        SELECT id, role_name, role_code, description
        FROM roles
        ORDER BY role_name
    ");
    
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo jsonResponse(true, 'Roles retrieved successfully', $roles);
    
} catch (Exception $e) {
    error_log('Error in employees/roles.php: ' . $e->getMessage());
    echo jsonResponse(false, 'Error retrieving roles: ' . $e->getMessage());
}

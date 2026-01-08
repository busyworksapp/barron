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

if (!isset($data['machine_id'])) {
    echo jsonResponse(false, 'Machine ID is required');
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Get machine details before deleting
    $stmt = $db->prepare("SELECT machine_code, machine_name FROM machines WHERE id = ?");
    $stmt->execute([$data['machine_id']]);
    $machine = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$machine) {
        echo jsonResponse(false, 'Machine not found');
        exit;
    }
    
    // Check for relationships
    $checks = [
        ['table' => 'job_schedules', 'field' => 'machine_id', 'message' => 'job schedules'],
        ['table' => 'maintenance_tickets', 'field' => 'machine_id', 'message' => 'maintenance tickets'],
        ['table' => 'job_production_log', 'field' => 'machine_id', 'message' => 'production log entries']
    ];
    
    foreach ($checks as $check) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM {$check['table']} WHERE {$check['field']} = ?");
        $stmt->execute([$data['machine_id']]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            echo jsonResponse(false, "Cannot delete machine. Associated with {$count} {$check['message']}.");
            exit;
        }
    }
    
    // Delete machine
    $stmt = $db->prepare("DELETE FROM machines WHERE id = ?");
    $stmt->execute([$data['machine_id']]);
    
    // Log activity
    logActivity('machine_deleted', 'machines', $data['machine_id'], 
        "Deleted machine: {$machine['machine_name']} ({$machine['machine_code']})");
    
    echo jsonResponse(true, 'Machine deleted successfully');
    
} catch (Exception $e) {
    error_log('Error in machines/delete.php: ' . $e->getMessage());
    echo jsonResponse(false, 'Error deleting machine: ' . $e->getMessage());
}
